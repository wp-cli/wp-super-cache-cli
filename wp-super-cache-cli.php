<?php

$autoload = dirname( __FILE__ ) . '/vendor/autoload.php';

if ( file_exists( $autoload ) ) {
	require_once $autoload;
}

if ( ! class_exists( 'WP_CLI' ) ) {
	return;
}

WP_CLI::add_command(
	'super-cache', 'WP_Super_Cache_Command', array(
		'before_invoke' => function() {
			if ( ! function_exists( 'wp_super_cache_enable' ) ) {
				WP_CLI::error( 'WP Super Cache needs to be enabled to use its WP-CLI commands.' );
			}
		},
	)
);
