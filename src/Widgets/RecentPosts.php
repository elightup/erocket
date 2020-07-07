<?php
namespace ERocket\Widgets;

use WP_Widget;
use WP_Query;

class RecentPosts extends WP_Widget {
	private $defaults;

	public function __construct() {
		$this->defaults = [
			'title'    => __( 'Recent Posts', 'erocket' ),
			'category' => '',
			'style'    => 'small-thumb',
			'number'   => 5,
		];

		parent::__construct( 'erp', __( '[eRocket] Recent Posts', 'erocket' ), [
			'classname' => 'erp',
		] );

		if ( is_active_widget( false, false, $this->id_base ) || is_customize_preview() ) {
			add_action( 'wp_head', [ $this, 'output_style' ] );
		}
	}

	public function output_style() {
		?>
		<style>
		.erp li {
			display: flex;
		}
		.erp li:not(:last-child) {
			margin-bottom: 16px;
		}
		.erp-big-thumb {
			flex-direction: column;
		}
		.erp-big-thumb a {
			flex: 0 0 100%;
			width: 100%;
			flex: 0 0 100%;
			margin-right: 0;
		}
		.erp-small-thumb > a {
			display: block;
			margin-right: 12px;
		}
		.erp-small-thumb img {
			display: block;
			width: 64px;
			height: 64px;
		}
		.erp-body {
			flex: 1;
			line-height: 1.25;
		}
		.erp a {
			font-weight: 700;
			color: inherit;
		}
		.erp time {
			display: block;
			margin-top: 4px;
			color: #a0aec0;
			font-size: .875em;
		}
		</style>
		<?php
	}

	public function widget( $args, $instance ) {
		$instance = wp_parse_args( $instance, $this->defaults );

		$query_args = [
			'posts_per_page'         => $instance['number'],
			'no_found_rows'          => true,
			'post_status'            => 'publish',
			'ignore_sticky_posts'    => true,
			'update_post_meta_cache' => false,
			'update_post_term_cache' => false,
		];
		if ( ! empty( $instance['category'] ) ) {
			$query_args['cat'] = $instance['category'];
		}

		$query = new WP_Query( $query_args );
		if ( ! $query->have_posts() ) {
			return;
		}

		echo $args['before_widget'];

		$title = apply_filters( 'widget_title', $instance['title'], $instance, $this->id_base );
		if ( $title ) {
			echo $args['before_title'] . $title . $args['after_title'];
		}
		?>
		<ul>
			<?php while ( $query->have_posts() ) : $query->the_post(); ?>
				<li class="<?php esc_attr_e( 'small-thumb' === $instance['style'] ? 'erp-small-thumb' : 'erp-big-thumb', 'erocket' ); ?>">
					<?php if ( has_post_thumbnail() ) : ?>
						<a href="<?php the_permalink(); ?>">
							<?php
							if ( 'small-thumb' === $instance['style'] ) :
								the_post_thumbnail( 'thumbnail' );
							else :
								the_post_thumbnail( 'full' );
							endif;
							?>
						</a>
					<?php endif; ?>
					<div class="erp-body">
						<a href="<?php the_permalink(); ?>"><?php the_title(); ?></a>
						<time><?php echo esc_html( get_the_date() ); ?></time>
					</div>
				</li>
			<?php endwhile; ?>
		</ul>
		<?php
		wp_reset_postdata();

		echo $args['after_widget'];
	}

	public function update( $new_instance, $old_instance ) {
		$instance   = $old_instance;
		$instance['title']    = sanitize_text_field( $new_instance['title'] );
		$instance['category'] = absint( $new_instance['category'] );
		$instance['style']   = $new_instance['style'];
		$instance['number']   = absint( $new_instance['number'] );
		return $instance;
	}

	public function form( $instance ) {
		$instance = wp_parse_args( $instance, $this->defaults );
		?>
		<p>
			<label for="<?php echo $this->get_field_id( 'title' ); ?>"><?php esc_html_e( 'Title:', 'erocket' ); ?></label>
			<input class="widefat" id="<?php echo $this->get_field_id( 'title' ); ?>" name="<?php echo $this->get_field_name( 'title' ); ?>" type="text" value="<?php echo esc_attr( $instance['title'] ); ?>">
		</p>
		<p>
			<label for="<?php echo $this->get_field_id( 'category' ); ?>"><?php esc_html_e( 'Category:', 'erocket' ); ?></label>
			<?php
			wp_dropdown_categories( [
				'show_option_all' => __( 'All', 'erocket' ),
				'orderby'         => 'name',
				'order'           => 'ASC',
				'selected'        => $instance['category'],
				'hierarchical'    => true,
				'name'            => $this->get_field_name( 'category' ),
				'id'              => $this->get_field_id( 'category' ),
				'class'           => 'widefat',
			] );
			?>
		</p>
		<p>
			<label for="<?php echo $this->get_field_id( 'style' ); ?>"><?php esc_html_e( 'Style:', 'erocket' ); ?></label>
			<select class="widefat" id="<?php echo $this->get_field_id( 'style' ); ?>" name="<?php echo $this->get_field_name( 'style' ); ?>">
				<option <?php esc_attr_e( 'small-thumb' === $instance['style'] ? 'selected' : '', 'erocket' ); ?> value="small-thumb"><?php esc_html_e( 'Small thumbnail', 'erocket' ); ?></option>
				<option <?php esc_attr_e( 'big-thumb' === $instance['style'] ? 'selected' : '', 'erocket' ); ?> value="big-thumb"><?php esc_html_e( 'Big thumbnail', 'erocket' ); ?></option>
			</select>
		</p>
		<p>
			<label for="<?php echo $this->get_field_id( 'number' ); ?>"><?php esc_html_e( 'Number of posts to show:', 'erocket' ); ?></label>
			<input class="tiny-text" id="<?php echo $this->get_field_id( 'number' ); ?>" name="<?php echo $this->get_field_name( 'number' ); ?>" type="number" step="1" min="1" value="<?php echo esc_attr( $instance['number'] ); ?>" size="3">
		</p>
		<?php
	}
}
