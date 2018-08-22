<?php

/**
 * Load WPSC files and config file if they aren't loaded.
 */
class WP_Super_Cache_Loader {

	public function __construct() {
		WP_CLI::add_wp_hook( 'muplugins_loaded', array( $this, 'init_cache_base' ) );
		WP_CLI::add_wp_hook( 'plugins_loaded', array( $this, 'maybe_load_files' ) );
	}

	/**
	 * Initialization of cache-base globals.
	 *
	 * @global string $WPSC_HTTP_HOST
	 * @global string $cache_path
	 * @global string $blogcacheid
	 * @global string $blog_cache_dir
	 * @global object $current_blog   The current site.
	 *
	 * @return void
	 */
	function init_cache_base() {
		global $WPSC_HTTP_HOST, $cache_path, $current_blog, $blogcacheid, $blog_cache_dir;

		if ( ! defined( 'WPCACHEHOME' ) ) {
			return;
		}

		if ( empty( $WPSC_HTTP_HOST ) ) {
			$home_http_host = (string) parse_url( get_option( 'home' ), PHP_URL_HOST );
			$WPSC_HTTP_HOST = empty( $_SERVER['HTTP_HOST'] ) ? $home_http_host : htmlentities( $_SERVER['HTTP_HOST'] );
		}

		if ( ! is_multisite() ) {
			return;
		}

		if ( empty( $blogcacheid ) && is_object( $current_blog ) ) {
			$blogcacheid = is_subdomain_install() ? $current_blog->domain : trim( $current_blog->path, '/' );
		}

		if ( empty( $blog_cache_dir ) && ! empty( $cache_path ) && ! empty( $blogcacheid ) ) {
			$blog_cache_dir = str_replace( '//', '/', $cache_path . 'blogs/' . $blogcacheid . '/' );
		}
	}

	/**
	 * Loads PHP files, config file and runs init cache base again.
	 *
	 * @return void
	 */
	function maybe_load_files() {
		if ( ! defined( 'WPCACHEHOME' ) || ! function_exists( 'wpsc_init' ) ) {
			return;
		}

		if ( ! function_exists( 'wp_cache_phase2' ) ) {
			require_once WPCACHEHOME . '/wp-cache-phase2.php';
		}

		if ( ! function_exists( 'wp_cache_postload' ) ) {
			require_once WPCACHEHOME . '/wp-cache-phase1.php';
		}

		if ( ! function_exists( 'domain_mapping_actions' ) ) {
			require_once WPCACHEHOME . '/plugins/domain-mapping.php';
		}

		if ( ! function_exists( 'wp_super_cache_multisite_init' ) ) {
			require_once WPCACHEHOME . '/plugins/multisite.php';
		}

		$this->maybe_load_config();

		$this->init_cache_base();
	}

	/**
	 * Loads config file and populates globals.
	 *
	 * @return void
	 */
	function maybe_load_config() {
		global $super_cache_enabled, $cache_enabled, $wp_cache_mod_rewrite, $wp_cache_home_path, $cache_path, $file_prefix;
		global $wp_cache_mutex_disabled, $mutex_filename, $sem_id, $wp_super_cache_late_init;
		global $cache_compression, $cache_max_time, $wp_cache_shutdown_gc, $cache_rebuild_files;
		global $wp_super_cache_debug, $wp_super_cache_advanced_debug, $wp_cache_debug_level, $wp_cache_debug_to_file;
		global $wp_cache_debug_log, $wp_cache_debug_ip, $wp_cache_debug_username, $wp_cache_debug_email;
		global $cache_time_interval, $cache_scheduled_time, $cache_schedule_interval, $cache_schedule_type, $cache_gc_email_me;
		global $wp_cache_preload_on, $wp_cache_preload_interval, $wp_cache_preload_posts, $wp_cache_preload_taxonomies;
		global $wp_cache_preload_email_me, $wp_cache_preload_email_volume;
		global $wp_cache_mobile, $wp_cache_mobile_enabled, $wp_cache_mobile_browsers, $wp_cache_mobile_prefixes;
		global $wp_cache_config_file, $wp_cache_config_file_sample;

		if ( empty( $wp_cache_config_file ) ) {
			return;
		}

		if ( ! isset( $cache_enabled, $super_cache_enabled, $wp_cache_mod_rewrite, $wp_cache_debug_log )
			&& ! @include( $wp_cache_config_file ) // phpcs:ignore Generic.PHP.NoSilencedErrors.Discouraged
		) {
			if ( defined( 'WPCACHEHOME' ) && ! emtpy( $wp_cache_config_file_sample ) ) {
				@include( $wp_cache_config_file_sample ); // phpcs:ignore Generic.PHP.NoSilencedErrors.Discouraged
				WP_CLI::line( 'sample config loaded' );
			}
		}
	}
}
