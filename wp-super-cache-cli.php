<?php

$wpsc_autoload = __DIR__ . '/vendor/autoload.php';

if ( file_exists( $wpsc_autoload ) ) {
	require_once $wpsc_autoload;
}

if ( ! class_exists( 'WP_CLI' ) ) {
	return;
}

WP_CLI::add_command( 'super-cache', 'WP_Super_Cache_Command' );
