<?php
namespace ERocket;

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

		$wp_customize->add_control(
			'featuredposts[tag-name]',
			array(
				'label'          => esc_html__( 'Tag name', 'erocket' ),
				'section'        => 'FeaturedPosts',
				'theme_supports' => 'featuredposts',
				'priority'       => 20,
			)
		);

		$wp_customize-> add_setting(
			'show-style',
			array(
				'default' 	=> 'style2',
			)
		);
		$wp_customize->add_control(
			'show-style',
			array(
				'label' 			=> esc_html__( 'Style featured posts', 'erocket' ),
				'section' 			=> 'FeaturedPosts',
				'theme_supports' 	=> 'featuredposts',
				'type' 				=> 'select',
				'choices' 			=> array(
					'style1' 		=> 'Style 1',
					'style2' 		=> 'Style 2',
				),
			)
		);

	}

	public static function get_setting( $key = 'all' ) {
		$saved = (array) get_option( 'featuredposts' );

		$defaults = apply_filters(
			'FeaturedPosts_default_settings',
			array(
				'tag-name' => '',
				'show-style' => '',
			)
		);

		$options = wp_parse_args( $saved, $defaults );
		$options = array_intersect_key( $options, $defaults );

		if ( 'all' != $key ) {
			return isset( $options[ $key ] ) ? $options[ $key ] : false;
		}

		return $options;
	}

}