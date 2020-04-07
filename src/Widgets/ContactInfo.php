<?php
/**
 * SVG icons: Boxicons https://boxicons.com/, (C) CC 4.0.
 * Using icons with square dimension (width = height)
 */
namespace ERocket\Widgets;

use WP_Widget_Text;
use WP_Widget;

class ContactInfo extends WP_Widget_Text {
	private $networks;
	private $defaults;

	public function __construct() {
		$this->networks = [
			'behance'    => __( 'Behance', 'erocket' ),
			'codepen'    => __( 'Codepen', 'erocket' ),
			'deviantart' => __( 'Devian Art', 'erocket' ),
			'dribbble'   => __( 'Dribbble', 'erocket' ),
			'etsy'       => __( 'Etsy', 'erocket' ),
			'facebook'   => __( 'Facebook', 'erocket' ),
			'flickr'     => __( 'Flickr', 'erocket' ),
			'github'     => __( 'Github', 'erocket' ),
			'google'     => __( 'Google', 'erocket' ),
			'instagram'  => __( 'Instagram', 'erocket' ),
			'linkedin'   => __( 'Linkedin', 'erocket' ),
			'medium'     => __( 'Medium', 'erocket' ),
			'pinterest'  => __( 'Pinterest', 'erocket' ),
			'reddit'     => __( 'Reddit', 'erocket' ),
			'rss'        => __( 'RSS', 'erocket' ),
			'skype'      => __( 'Skype', 'erocket' ),
			'slack'      => __( 'Slack', 'erocket' ),
			'snapchat'   => __( 'Snapchat', 'erocket' ),
			'soundcloud' => __( 'Soundcloud', 'erocket' ),
			'spotify'    => __( 'Spotify', 'erocket' ),
			'telegram'   => __( 'Telegram', 'erocket' ),
			'tumblr'     => __( 'Tumblr', 'erocket' ),
			'twitch'     => __( 'Twitch', 'erocket' ),
			'twitter'    => __( 'Twitter', 'erocket' ),
			'vimeo'      => __( 'Vimeo', 'erocket' ),
			'vk'         => __( 'VK', 'erocket' ),
			'whatsapp'   => __( 'Whatsapp', 'erocket' ),
			'wordpress'  => __( 'WordPress', 'erocket' ),
			'yelp'       => __( 'Yelp', 'erocket' ),
			'youtube'    => __( 'Youtube', 'erocket' ),
		];
		$this->defaults = [
			'title'   => __( 'Contact Info', 'erocket' ),
			'text'    => '',
			'address' => '',
			'phone'   => '',
			'email'   => '',
		];
		foreach ( $this->networks as $key => $label ) {
			$this->defaults[ $key ] = '';
		}

		$widget_ops  = [
			'classname' => 'eci',
		];
		$control_ops = [
			'width'  => 400,
			'height' => 350,
		];

		WP_Widget::__construct( 'eci', __( '[eRocket] Contact Info', 'erocket' ), $widget_ops, $control_ops );

		if ( is_active_widget( false, false, $this->id_base ) || is_customize_preview() ) {
			add_action( 'wp_head', [ $this, 'output_style' ] );
		}
	}

	public function output_style() {
		?>
		<style>
		.eci-info {
			display: flex;
			align-items: flex-start;
			margin-bottom: 4px;
		}
		.eci-info svg {
			width: 1em;
			height: 1em;
			margin-right: 4px;
			margin-top: 4px;
		}
		.eci-info address {
			margin: 0;
		}
		.eci-profiles {
			margin-top: 1.5em;
			display: flex;
			flex-wrap: wrap;
		}
		.eci-profiles a {
			display: flex;
			justify-content: center;
			align-items: center;
			width: 36px;
			height: 36px;
			background: #4a5568;
			margin-right: 4px;
			margin-bottom: 4px;
			border-radius: 50%;
		}
		.eci-profiles a:hover {
			opacity: .75;
		}
		.eci-profiles svg {
			fill: #fff;
		}
		</style>
		<?php
	}

	public function widget( $args, $instance ) {
		$instance = wp_parse_args( $instance, $this->defaults );

		$after_widget = $args['after_widget'];
		$args['after_widget'] = '';

		parent::widget( $args, $instance );
		?>

		<?php if ( ! empty( $instance[ 'address' ] ) ) : ?>
			<div class="eci-info">
				<?php $this->output_svg( 'map' ); ?>
				<address><?php echo esc_html( $instance['address'] ); ?></address>
			</div>
		<?php endif; ?>
		<?php if ( ! empty( $instance[ 'email' ] ) ) : ?>
			<div class="eci-info">
				<?php $this->output_svg( 'envelope' ); ?>
				<a href="mailto:<?php echo esc_attr( $instance['email'] ); ?>"><?php echo esc_html( $instance['email'] ); ?></a>
			</div>
		<?php endif; ?>
		<?php if ( ! empty( $instance[ 'phone' ] ) ) : ?>
			<div class="eci-info">
				<?php $this->output_svg( 'phone' ); ?>
				<a href="tel:<?php echo esc_attr( preg_replace( '/[^\+0-9]/', '', $instance['phone'] ) ); ?>"><?php echo esc_html( $instance['phone'] ); ?></a>
			</div>
		<?php endif; ?>

		<?php $instance = array_intersect_key( $instance, $this->networks ); ?>
		<?php if ( ! empty( $instance ) ) : ?>
			<div class="eci-profiles">
				<?php foreach ( $instance as $network => $url ) : ?>
					<a href="<?php echo esc_url( $url ); ?>"><?php $this->output_svg( $network ); ?></a>
				<?php endforeach; ?>
			</div>
		<?php endif; ?>

		<?php
		echo $after_widget;
	}

	public function update( $new_instance, $old_instance ) {
		$instance = parent::update( $new_instance, $old_instance );

		$instance['address'] = sanitize_text_field( $new_instance['address'] );
		$instance['email']   = is_email( $new_instance['email'] ) ? $new_instance['email'] : '';
		$instance['phone']   = sanitize_text_field( $new_instance['phone'] );

		foreach ( $this->networks as $key => $label ) {
			$instance[ $key ] = sanitize_url( $new_instance[ $key ] );
		}
		return $instance;
	}

	public function form( $instance ) {
		$instance = wp_parse_args( $instance, $this->defaults );

		parent::form( $instance );
		?>
		<p>
			<label for="<?php echo $this->get_field_id( 'address' ); ?>"><?php esc_html_e( 'Address:', 'erocket' ); ?></label>
			<input class="widefat" id="<?php echo $this->get_field_id( 'address' ); ?>" name="<?php echo $this->get_field_name( 'address' ); ?>" type="text" value="<?php echo esc_attr( $instance['address'] ); ?>">
		</p>
		<p>
			<label for="<?php echo $this->get_field_id( 'email' ); ?>"><?php esc_html_e( 'Email:', 'erocket' ); ?></label>
			<input class="widefat" id="<?php echo $this->get_field_id( 'email' ); ?>" name="<?php echo $this->get_field_name( 'email' ); ?>" type="text" value="<?php echo esc_attr( $instance['email'] ); ?>">
		</p>
		<p>
			<label for="<?php echo $this->get_field_id( 'phone' ); ?>"><?php esc_html_e( 'Phone:', 'erocket' ); ?></label>
			<input class="widefat" id="<?php echo $this->get_field_id( 'phone' ); ?>" name="<?php echo $this->get_field_name( 'phone' ); ?>" type="text" value="<?php echo esc_attr( $instance['phone'] ); ?>">
		</p>
		<?php foreach ( $this->networks as $key => $label ) : ?>
			<p>
				<label for="<?php echo $this->get_field_id( $key ); ?>"><?php echo esc_html( $label ); ?>:</label>
				<input class="widefat" id="<?php echo $this->get_field_id( $key ); ?>" name="<?php echo $this->get_field_name( $key ); ?>" type="text" value="<?php echo esc_attr( $instance[ $key ] ); ?>">
			</p>
		<?php endforeach; ?>
		<?php
	}

	private function output_svg( $key ) {
		echo file_get_contents( ER_DIR . "/img/$key.svg" );
	}
}
