<?php
namespace ERocket\Widgets;

use WP_Widget;
use WP_Query;

class RecentPosts extends WP_Widget {
	public function __construct() {
		$widget_ops = array(
			'classname'                   => 'erocket-recent-posts',
			'customize_selective_refresh' => true,
		);
		parent::__construct( 'erocket-recent-posts', __( '[eRocket] Recent Posts', 'erocket' ), $widget_ops );

		if ( is_active_widget( false, false, $this->id_base ) || is_customize_preview() ) {
			add_action( 'wp_head', [ $this, 'output_style' ] );
		}
	}

	public function output_style() {
		?>
		<style>
		.recent-post {
			display: flex;
		}
		.recent-post:not(:last-child) {
			margin-bottom: 16px;
		}
		.recent-post-thumbnail {
			display: block;
			margin-right: 12px;
		}
		.recent-post img {
			display: block;
			width: 64px;
			height: 64px;
			border-radius: 4px;
		}
		.recent-post-body {
			flex: 1;
			line-height: 1.25;
		}
		.recent-post-body a {
			font-weight: 700;
			color: inherit;
		}
		.recent-post-body time {
			display: block;
			margin-top: 4px;
			color: #a0aec0;
			font-size: .875em;
		}
		</style>
		<?php
	}

	public function widget( $args, $instance ) {
		$title = empty( $instance['title'] ) ? __( 'Recent Posts', 'erocket' ) : $instance['title'];
		$title = apply_filters( 'widget_title', $title, $instance, $this->id_base );

		$number = empty( $instance['number'] ) ? 5 : absint( $instance['number'] );
		if ( ! $number ) {
			$number = 5;
		}

		$query = new WP_Query( [
			'posts_per_page'         => $number,
			'no_found_rows'          => true,
			'post_status'            => 'publish',
			'ignore_sticky_posts'    => true,
			'update_post_meta_cache' => false,
			'update_post_term_cache' => false,
		] );

		if ( ! $query->have_posts() ) {
			return;
		}

		echo $args['before_widget'];

		if ( $title ) {
			echo $args['before_title'] . $title . $args['after_title'];
		}
		?>
		<ul class="recent-posts">
			<?php while ( $query->have_posts() ) : $query->the_post(); ?>
				<li class="recent-post">
					<?php if ( has_post_thumbnail() ) : ?>
						<a class="recent-post-thumbnail" href="<?php the_permalink(); ?>">
							<?php the_post_thumbnail( 'thumbnail' ); ?>
						</a>
					<?php endif; ?>
					<div class="recent-post-body">
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
		$instance           = $old_instance;
		$instance['title']  = sanitize_text_field( $new_instance['title'] );
		$instance['number'] = (int) $new_instance['number'];
		return $instance;
	}

	public function form( $instance ) {
		$title  = isset( $instance['title'] ) ? esc_attr( $instance['title'] ) : '';
		$number = isset( $instance['number'] ) ? absint( $instance['number'] ) : 5;
		?>
		<p><label for="<?php echo $this->get_field_id( 'title' ); ?>"><?php esc_html_e( 'Title:', 'erocket' ); ?></label>
		<input class="widefat" id="<?php echo $this->get_field_id( 'title' ); ?>" name="<?php echo $this->get_field_name( 'title' ); ?>" type="text" value="<?php echo esc_attr( $title ); ?>"></p>

		<p><label for="<?php echo $this->get_field_id( 'number' ); ?>"><?php esc_html_e( 'Number of posts to show:', 'erocket' ); ?></label>
		<input class="tiny-text" id="<?php echo $this->get_field_id( 'number' ); ?>" name="<?php echo $this->get_field_name( 'number' ); ?>" type="number" step="1" min="1" value="<?php echo esc_attr( $number ); ?>" size="3"></p>
		<?php
	}
}
