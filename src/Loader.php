<?php
namespace ERocket;

class Loader {
	public function __construct() {
		add_action( 'widgets_init', [ $this, 'register_widgets' ] );
	}

	public function register_widgets() {
		register_widget( __NAMESPACE__ . '\Widgets\RecentPosts' );
	}
}