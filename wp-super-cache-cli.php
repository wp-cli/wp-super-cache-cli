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

function wp_super_cache_cli_init() {
	if ( !function_exists( 'wp_super_cache_enable' ) )
		return;

	if ( defined('WP_CLI') && WP_CLI ) {
		include dirname(__FILE__) . '/cli.php';
	}
}
add_action( 'plugins_loaded', 'wp_super_cache_cli_init' );

