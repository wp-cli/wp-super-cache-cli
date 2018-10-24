<?php

$autoload = dirname( __FILE__ ) . '/vendor/autoload.php';

if ( file_exists( $autoload ) ) {
	require_once $autoload;
}

if ( ! class_exists( 'WP_CLI' ) ) {
	return;
}

WP_CLI::add_command( 'super-cache', 'WP_Super_Cache_Command' );
