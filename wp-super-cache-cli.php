<?php
/*
Plugin Name: WP Super Cache CLI
Version: 1.0
Description: A CLI interface for the WP Super Cache plugin
Author: WP-CLI Team
Author URI: http://github.com/wp-cli
Plugin URI: http://github.com/wp-cli/wp-super-cache-cli
License: MIT
*/

$autoload = __DIR__ . '/vendor/autoload.php';
if ( file_exists( $autoload ) ) {
	require_once $autoload;
}
