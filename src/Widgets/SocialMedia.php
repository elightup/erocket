<?php
/**
 * SVG icons: Boxicons https://boxicons.com/, (C) CC 4.0.
 * Using icons with square dimension (width = height)
 */
namespace ERocket\Widgets;

use WP_Widget_Text;
use WP_Widget;

class SocialMedia extends WP_Widget_Text {
	private $services;
	private $defaults;

	public function __construct() {
		$this->defaults = [
			'title'   => __( 'Social Media', 'erocket' ),
			'text'    => '',
			'address' => '',
			'email'   => '',
			'phone'   => '',
		];

		$widget_ops  = [
			'classname' => 'esm',
		];
		$control_ops = [
			'width'  => 400,
			'height' => 350,
		];

		WP_Widget::__construct( 'esm', __( '[eRocket] Social Media', 'erocket' ), $widget_ops, $control_ops );

		if ( is_active_widget( false, false, $this->id_base ) || is_customize_preview() ) {
			add_action( 'wp_head', [ $this, 'output_style' ] );
		}
	}

	public function output_style() {
		?>
		<style>

		</style>
		<?php
	}

	public function widget( $args, $instance ) {
		$instance = wp_parse_args( $instance, $this->defaults );

		$after_widget = $args['after_widget'];
		$args['after_widget'] = '';

		parent::widget( $args, $instance );
		?>
		<?php $services = array_intersect_key( $instance, block_core_social_link_services() ); ?>
		<?php if ( ! empty( $services ) ) : ?>
			<ul class="wp-block-social-links esm-profiles">
				<?php
				foreach ( $services as $service => $url ) {
					echo render_block_core_social_link( [
						'service' => $service,
						'url'     => $url,
					] );
				}
				?>
			</ul>
		<?php endif; ?>

		<?php
		echo $after_widget;
	}

	public function update( $new_instance, $old_instance ) {
		$instance = parent::update( $new_instance, $old_instance );
		$instance['title'] = sanitize_text_field( $new_instance['title'] );

		$services = block_core_social_link_services();
		foreach ( $services as $key => $service ) {
			$instance[ $key ] = esc_url_raw( $new_instance[ $key ] );
		}

		return array_filter( $instance );
	}

	public function form( $instance ) {
		$instance = wp_parse_args( $instance, $this->defaults );

		?>
		<p>
			<label for="<?= $this->get_field_id( 'title' ); ?>"><?php esc_html_e( 'Title:', 'erocket' ); ?></label>
			<input class="widefat" id="<?= $this->get_field_id( 'title' ); ?>" name="<?= $this->get_field_name( 'title' ); ?>" type="text" value="<?= esc_attr( $instance['title'] ); ?>">
		</p>
		<?php $services = block_core_social_link_services(); ?>
		<?php

		foreach ( $services as $key => $service ) : ?>
			<p>
				<label for="<?= $this->get_field_id( $key ); ?>"><?= esc_html( $service['name'] ); ?>:</label>
				<input class="widefat" id="<?= $this->get_field_id( $key ); ?>" name="<?= $this->get_field_name( $key ); ?>" type="text" value="<?= isset( $instance[ $key ] ) ? esc_attr( $instance[ $key ] ) : ''; ?>">
			</p>
		<?php endforeach; ?>
		<?php
	}

	private function output_svg( $key ) {
		echo file_get_contents( EROCKET_DIR . "/img/$key.svg" );
	}
}
