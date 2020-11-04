<?php
namespace ERocket;
use ERocket\Constants;

//if ( ! class_exists( 'FeaturedPosts' ) && isset( $GLOBALS['pagenow'] ) && 'plugins.php' !== $GLOBALS['pagenow'] ) {

	class FeaturedPosts {
		public static $max_posts = 15;

		public static $post_types = array( 'post' );

		public static $tag;

		public static function setup() {
			add_action( 'init', array( __CLASS__, 'init' ), 30 );
		}

		public static function init() {
			$theme_support = get_theme_support( 'featuredposts' );

			// Return early if theme does not support featured content.
			if ( ! $theme_support ) {
				return;
			}

			if ( ! isset( $theme_support[0] ) ) {
				return;
			}

			if ( isset( $theme_support[0]['FeaturedPosts_filter'] ) ) {
				$theme_support[0]['filter'] = $theme_support[0]['FeaturedPosts_filter'];
				unset( $theme_support[0]['FeaturedPosts_filter'] );
			}

			if ( ! isset( $theme_support[0]['filter'] ) ) {
				return;
			}

			if ( isset( $theme_support[0]['max_posts'] ) ) {
				self::$max_posts = absint( $theme_support[0]['max_posts'] );
			}

			add_filter( $theme_support[0]['filter'], array( __CLASS__, 'get_featured_posts' ) );
			add_action( 'customize_register', array( __CLASS__, 'customize_register' ), 9 );
			add_action( 'admin_init', array( __CLASS__, 'register_setting' ) );
			add_action( 'save_post', array( __CLASS__, 'delete_transient' ) );
			add_action( 'delete_post_tag', array( __CLASS__, 'delete_post_tag' ) );
			add_action( 'customize_controls_enqueue_scripts', array( __CLASS__, 'enqueue_scripts' ) );
			add_action( 'pre_get_posts', array( __CLASS__, 'pre_get_posts' ) );
			add_action( 'switch_theme', array( __CLASS__, 'switch_theme' ) );
			add_action( 'switch_theme', array( __CLASS__, 'delete_transient' ) );
			add_action( 'wp_loaded', array( __CLASS__, 'wp_loaded' ) );
			add_action( 'update_option_featuredposts', array( __CLASS__, 'flush_post_tag_cache' ), 10, 2 );
			add_action( 'delete_option_featuredposts', array( __CLASS__, 'flush_post_tag_cache' ), 10, 2 );
			add_action( 'split_shared_term', array( __CLASS__, 'erocket_update_FeaturedPosts_for_split_terms', 10, 4 ) );

			if ( isset( $theme_support[0]['additional_post_types'] ) ) {
				$theme_support[0]['post_types'] = array_merge( array( 'post' ), (array) $theme_support[0]['additional_post_types'] );
				unset( $theme_support[0]['additional_post_types'] );
			}

			if ( isset( $theme_support[0]['post_types'] ) ) {
				self::$post_types = array_merge( self::$post_types, (array) $theme_support[0]['post_types'] );
				self::$post_types = array_unique( self::$post_types );

				foreach ( self::$post_types as $post_type ) {
					register_taxonomy_for_object_type( 'post_tag', $post_type );
				}
			}
		}

		public static function wp_loaded() {
			if ( self::get_setting( 'hide-tag' ) ) {
				$settings = self::get_setting();

				// This is done before setting filters for get_terms in order to avoid an infinite filter loop
				self::$tag = get_term_by( 'name', $settings['tag-name'], 'post_tag' );

				add_filter( 'get_terms', array( __CLASS__, 'hide_featured_term' ), 10, 3 );
				add_filter( 'get_the_terms', array( __CLASS__, 'hide_the_featured_term' ), 10, 3 );
			}
		}

		public static function get_featured_posts() {
			$post_ids = self::get_featured_post_ids();

			if ( empty( $post_ids ) ) {
				return array();
			}

			$featured_posts = get_posts(
				array(
					'include'          => $post_ids,
					'posts_per_page'   => count( $post_ids ),
					'post_type'        => self::$post_types,
					'suppress_filters' => false,
				)
			);

			return $featured_posts;
		}

		public static function get_featured_post_ids() {
			$featured_ids = get_transient( 'FeaturedPosts_ids' );
			if ( ! empty( $featured_ids ) ) {
				return array_map(
					'absint',
					apply_filters( 'FeaturedPosts_post_ids', (array) $featured_ids )
				);
			}

			$settings = self::get_setting();

			$term = get_term_by( 'name', $settings['tag-name'], 'post_tag' );
			if ( ! $term ) {
				$term = get_term_by( 'id', $settings['tag-id'], 'post_tag' );
			}
			if ( $term ) {
				$tag = $term->term_id;
			} else {
				return apply_filters( 'FeaturedPosts_post_ids', array() );
			}

			$quantity = isset( $settings['quantity'] ) ? $settings['quantity'] : self::$max_posts;

			$featured = get_posts(
				array(
					'numberposts'      => $quantity,
					'post_type'        => self::$post_types,
					'suppress_filters' => false,
					'tax_query'        => array(
						array(
							'field'    => 'term_id',
							'taxonomy' => 'post_tag',
							'terms'    => $tag,
						),
					),
				)
			);

			if ( ! $featured ) {
				return apply_filters( 'FeaturedPosts_post_ids', array() );
			}

			$featured_ids = wp_list_pluck( (array) $featured, 'ID' );
			$featured_ids = array_map( 'absint', $featured_ids );

			set_transient( 'FeaturedPosts_ids', $featured_ids );

			return apply_filters( 'FeaturedPosts_post_ids', $featured_ids );
		}

		public static function delete_transient() {
			delete_transient( 'FeaturedPosts_ids' );
		}

		public static function flush_post_tag_cache( $prev, $opts ) {
			if ( ! empty( $opts ) && ! empty( $opts['tag-id'] ) ) {
				$query = new WP_Query(
					array(
						'tag_id'         => (int) $opts['tag-id'],
						'posts_per_page' => -1,
					)
				);
				foreach ( $query->posts as $post ) {
					wp_cache_delete( $post->ID, 'post_tag_relationships' );
				}
			}
		}
		public static function pre_get_posts( $query ) {

			if ( ! $query->is_home() || ! $query->is_main_query() ) {
				return;
			}

			if ( 'posts' !== get_option( 'show_on_front' ) ) {
				return;
			}

			$featured = self::get_featured_post_ids();

			if ( ! $featured ) {
				return;
			}

			$settings = self::get_setting();

			if ( true == $settings['show-all'] ) {
				return;
			}

			$post__not_in = $query->get( 'post__not_in' );

			if ( ! empty( $post__not_in ) ) {
				$featured = array_merge( (array) $post__not_in, $featured );
				$featured = array_unique( $featured );
			}

			$query->set( 'post__not_in', $featured );
		}
		public static function delete_post_tag( $tag_id ) {
			$settings = self::get_setting();

			if ( empty( $settings['tag-id'] ) || $tag_id != $settings['tag-id'] ) {
				return;
			}

			$settings['tag-id'] = 0;
			$settings           = self::validate_settings( $settings );
			update_option( 'featuredposts', $settings );
		}
		public static function hide_featured_term( $terms, $taxonomies, $args ) {

			if ( is_admin() || ( defined( 'REST_REQUEST' ) && REST_REQUEST ) || ( defined( 'XMLRPC_REQUEST' ) && XMLRPC_REQUEST ) ) {
				return $terms;
			}

			if ( ! in_array( 'post_tag', $taxonomies ) ) {
				return $terms;
			}

			if ( empty( $terms ) ) {
				return $terms;
			}

			if ( 'all' != $args['fields'] ) {
				return $terms;
			}

			$settings = self::get_setting();

			if ( false !== self::$tag ) {
				foreach ( $terms as $order => $term ) {
					if (
					is_object( $term )
					&& (
						$settings['tag-id'] === $term->term_id
						|| $settings['tag-name'] === $term->name
					)
					) {
						unset( $terms[ $order ] );
					}
				}
			}

			return $terms;
		}
		public static function hide_the_featured_term( $terms, $id, $taxonomy ) {

			// This filter is only appropriate on the front-end.
			if ( is_admin() || ( defined( 'REST_REQUEST' ) && REST_REQUEST ) || ( defined( 'XMLRPC_REQUEST' ) && XMLRPC_REQUEST ) ) {
				return $terms;
			}

			// Make sure we are in the correct taxonomy.
			if ( 'post_tag' != $taxonomy ) {
				return $terms;
			}

			// No terms? Return early!
			if ( empty( $terms ) ) {
				return $terms;
			}

			$settings = self::get_setting();
			$tag      = get_term_by( 'name', $settings['tag-name'], 'post_tag' );

			if ( false !== $tag ) {
				foreach ( $terms as $order => $term ) {
					if ( $settings['tag-id'] === $term->term_id || $settings['tag-name'] === $term->name ) {
						unset( $terms[ $order ] );
					}
				}
			}

			return $terms;
		}
		public static function register_setting() {
			add_settings_field( 'featuredposts', __( 'Featured Content', 'erocket' ), array( __class__, 'render_form' ), 'reading' );

			// Register sanitization callback for the Customizer.
			register_setting( 'featuredposts', 'featuredposts', array( __class__, 'validate_settings' ) );
		}
		public static function customize_register( $wp_customize ) {
			$wp_customize->add_section(
				'FeaturedPosts',
				array(
					'title'          => esc_html__( 'Featured Content', 'erocket' ),
					'description'    => sprintf( __( 'Easily feature all posts with the <a href="%1$s">"featured" tag</a> or a tag of your choice. Your theme supports up to %2$s posts in its featured content area.', 'erocket' ), admin_url( '/edit.php?tag=featured' ), absint( self::$max_posts ) ),
					'priority'       => 130,
					'theme_supports' => 'featuredposts',
				)
			);
			$wp_customize->add_setting(
				'featuredposts[tag-name]',
				array(
					'type'                 => 'option',
					'sanitize_js_callback' => array( __CLASS__, 'delete_transient' ),
				)
			);
			$wp_customize->add_setting(
				'featuredposts[hide-tag]',
				array(
					'default'              => true,
					'type'                 => 'option',
					'sanitize_js_callback' => array( __CLASS__, 'delete_transient' ),
				)
			);
			$wp_customize->add_setting(
				'featuredposts[show-all]',
				array(
					'default'              => false,
					'type'                 => 'option',
					'sanitize_js_callback' => array( __CLASS__, 'delete_transient' ),
				)
			);

			$wp_customize->add_control(
				'featuredposts[tag-name]',
				array(
					'label'          => esc_html__( 'Tag name', 'erocket' ),
					'section'        => 'FeaturedPosts',
					'theme_supports' => 'featuredposts',
					'priority'       => 20,
				)
			);
			$wp_customize->add_control(
				'featuredposts[hide-tag]',
				array(
					'label'          => esc_html__( 'Do not display tag in post details and tag clouds.', 'erocket' ),
					'section'        => 'FeaturedPosts',
					'theme_supports' => 'featuredposts',
					'type'           => 'checkbox',
					'priority'       => 30,
				)
			);
			$wp_customize->add_control(
				'featuredposts[show-all]',
				array(
					'label'          => esc_html__( 'Also display tagged posts outside the Featured Content area.', 'erocket' ),
					'section'        => 'FeaturedPosts',
					'theme_supports' => 'featuredposts',
					'type'           => 'checkbox',
					'priority'       => 40,
				)
			);
		}
		public static function enqueue_scripts() {
			wp_enqueue_script( 'featuredposts-suggest', plugins_url( 'js/suggest.js', __FILE__ ), array( 'suggest' ), '20131022', true );
		}
		public static function render_form() {
			printf( __( 'The settings for Featured Content have <a href="%s">moved to Appearance &rarr; Customize</a>.', 'erocket' ), admin_url( 'customize.php?#accordion-section-FeaturedPosts' ) );
		}
		public static function get_setting( $key = 'all' ) {
			$saved = (array) get_option( 'featuredposts' );

			$defaults = apply_filters(
				'FeaturedPosts_default_settings',
				array(
					'hide-tag' => 1,
					'tag-id'   => 0,
					'tag-name' => '',
					'show-all' => 0,
				)
			);

			$options = wp_parse_args( $saved, $defaults );
			$options = array_intersect_key( $options, $defaults );

			if ( 'all' != $key ) {
				return isset( $options[ $key ] ) ? $options[ $key ] : false;
			}

			return $options;
		}
		public static function validate_settings( $input ) {
			$output = array();

			if ( empty( $input['tag-name'] ) ) {
				$output['tag-id'] = 0;
			} else {
				$term = get_term_by( 'name', $input['tag-name'], 'post_tag' );

				if ( $term ) {
					$output['tag-id'] = $term->term_id;
				} else {
					$new_tag = wp_create_tag( $input['tag-name'] );

					if ( ! is_wp_error( $new_tag ) && isset( $new_tag['term_id'] ) ) {
						$output['tag-id'] = $new_tag['term_id'];
					}
				}

				$output['tag-name'] = $input['tag-name'];
			}

			$output['hide-tag'] = isset( $input['hide-tag'] ) && $input['hide-tag'] ? 1 : 0;

			$output['show-all'] = isset( $input['show-all'] ) && $input['show-all'] ? 1 : 0;

			self::delete_transient();

			return $output;
		}
		public static function switch_theme() {
			$option = (array) get_option( 'featuredposts' );

			if ( isset( $option['quantity'] ) ) {
				unset( $option['quantity'] );
				update_option( 'featuredposts', $option );
			}
		}

		public static function erocket_update_FeaturedPosts_for_split_terms( $old_term_id, $new_term_id, $term_taxonomy_id, $taxonomy ) {
			$FeaturedPosts_settings = get_option( 'featuredposts', array() );

			if ( isset( $FeaturedPosts_settings['tag-id'] ) && $old_term_id == $FeaturedPosts_settings['tag-id'] && 'post_tag' == $taxonomy ) {
				$FeaturedPosts_settings['tag-id'] = $new_term_id;
				update_option( 'featuredposts', $FeaturedPosts_settings );
			}
		}
	}
	function wpcom_rest_api_FeaturedPosts_copy_plugin_actions( $copy_dirs ) {
		$copy_dirs[] = __FILE__;
		return $copy_dirs;
	}
	add_action( 'restapi_theme_action_copy_dirs', 'wpcom_rest_api_FeaturedPosts_copy_plugin_actions' );

	function wpcom_rest_request_before_callbacks( $request ) {
		FeaturedPosts::init();
		return $request;
	}

	if ( Constants::is_true( 'IS_WPCOM' ) && Constants::is_true( 'REST_API_REQUEST' ) ) {
		add_filter( 'rest_request_before_callbacks', 'wpcom_rest_request_before_callbacks');
	}

	FeaturedPosts::setup();
//}