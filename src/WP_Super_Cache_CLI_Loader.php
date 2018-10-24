<?php

/**
 * Load WPSC files and config file if they aren't loaded.
 */
final class WP_Super_Cache_CLI_Loader {

	/**
	 * Version of WP Super Cache plugin.
	 *
	 * @var string Version.
	 */
	protected $wpsc_version;

	/**
	 * Absolute path to the plugin file.
	 *
	 * @var string File path.
	 */
	protected $wpsc_plugin_file;

	/**
	 * Checks status of WP Super Cache and loads config/dependencies if it needs.
	 *
	 * @return void
	 */
	public function load() {
		// If WP isn't loaded then registers hooks.
		if ( ! function_exists( 'add_filter' ) ) {
			$this->register_hooks();
			return;
		}

		$error_msg = '';

		// Before loading files check is plugin installed/activated.
		if ( $this->get_wpsc_version() === '' ) {
			$error_msg = 'WP Super Cache needs to be installed to use its WP-CLI commands.';
		} elseif ( version_compare( $this->get_wpsc_version(), '1.5.2', '<' ) ) {
			$error_msg = 'Minimum required version of WP Super Cache is 1.5.2';
		} elseif ( ! $this->is_wpsc_plugin_active() ) {
			$error_msg = 'WP Super Cache needs to be activated to use its WP-CLI commands.';
		} elseif ( ! defined( 'WP_CACHE' ) || ! WP_CACHE ) {
			$error_msg = 'WP_CACHE constant is false or not defined';
		} elseif ( defined( 'WP_CACHE' ) && WP_CACHE && defined( 'ADVANCEDCACHEPROBLEM' ) ) {
			$error_msg = 'WP Super Cache caching is broken';
		}

		if ( $error_msg ) {
			WP_CLI::error( $error_msg );
		}

		// Initialization of cache-base.
		$this->init_cache_base();

		// Load dependencies if they aren't loaded.
		$this->maybe_load_files();
	}

	/**
	 * Registers the hooks.
	 *
	 * @return void
	 */
	private function register_hooks() {
		WP_CLI::add_wp_hook( 'muplugins_loaded', array( $this, 'init_cache_base' ) );
		WP_CLI::add_wp_hook( 'plugins_loaded', array( $this, 'maybe_load_files' ) );
	}

	/**
	 * Initialization of cache-base.
	 *
	 * @global string $WPSC_HTTP_HOST
	 *
	 * @return void
	 */
	public function init_cache_base() {
		global $WPSC_HTTP_HOST;

		if ( ! defined( 'WPCACHEHOME' ) ) {
			return;
		}

		// Loads config file.
		$this->maybe_load_config();

		// If the parameter --url doesn't exist then gets HTTP_HOST from WordPress Address.
		if ( empty( $_SERVER['HTTP_HOST'] ) ) {
			$_SERVER['HTTP_HOST'] = $this->parse_home_url( PHP_URL_HOST );
		}

		if ( empty( $WPSC_HTTP_HOST ) ) {
			$this->maybe_include_file( 'include', 'wp-cache-base.php' );
		}
	}

	/**
	 * Loads config file and populates globals.
	 *
	 * @return void
	 */
	private function maybe_load_config() {
		global $cache_enabled, $super_cache_enabled, $cache_path, $wp_cache_mod_rewrite, $wp_cache_debug_log;
		global $wp_cache_config_file, $wp_cache_config_file_sample, $wp_cache_home_path;

		if ( empty( $wp_cache_config_file ) ) {
			return;
		}

		if ( ! isset( $cache_enabled, $super_cache_enabled, $cache_path, $wp_cache_mod_rewrite, $wp_cache_debug_log )
			&& ! $this->maybe_include_file( 'include', $wp_cache_config_file )
		) {
			if ( ! defined( 'WPCACHEHOME' )
				|| empty( $wp_cache_config_file_sample )
				|| ! $this->maybe_include_file( 'include', $wp_cache_config_file_sample )
			) {
				WP_CLI::error( 'Cannot load cache config file.' );
			}

			WP_CLI::warning( 'Default cache config file loaded - ' . str_replace( ABSPATH, '', $wp_cache_config_file_sample ) );
		}

		$wp_cache_home_path = trailingslashit( $this->parse_home_url( PHP_URL_PATH ) );
	}

	/**
	 * Loads config file, PHP files and overrides multisite settings.
	 *
	 * @return void
	 */
	public function maybe_load_files() {
		// WPSC >= 1.5.2 and it's active?
		if ( ! defined( 'WPCACHEHOME' ) || ! function_exists( 'wpsc_init' ) ) {
			return;
		}

		if ( version_compare( $this->get_wpsc_version(), '1.5.9', '>=' ) ) {
			// In rare cases, loading of wp-cache-phase2.php may be necessary.
			$this->maybe_include_file( 'wp_cache_phase2', 'wp-cache-phase2.php' );
		} else {
			// Prevents creation of output buffer or serving file for older versions.
			$request_method            = $_SERVER['REQUEST_METHOD'];
			$_SERVER['REQUEST_METHOD'] = 'POST';
		}

		// List of required files.
		$include_files = array(
			'wp_cache_postload'             => array(
				'file' => 'wp-cache-phase1.php',
				'run'  => '',
			),
			'domain_mapping_actions'        => array(
				'file' => 'plugins/domain-mapping.php',
				'run'  => 'domain_mapping_actions',
			),
			'wp_super_cache_multisite_init' => array(
				'file' => 'plugins/multisite.php',
				'run'  => 'wp_super_cache_override_on_flag',
			),
		);

		foreach ( $include_files as $func => $file ) {
			$this->maybe_include_file( $func, $file['file'], $file['run'] );
		}

		if ( ! empty( $request_method ) ) {
			$_SERVER['REQUEST_METHOD'] = $request_method;
		}

		$this->multisite_override_settings();
	}

	/**
	 * Overrides multisite settings.
	 *
	 * @global string $cache_path     Absolute path to cache directory.
	 * @global string $blogcacheid
	 * @global string $blog_cache_dir
	 * @global object $current_site   The current site.
	 *
	 * @return void
	 */
	private function multisite_override_settings() {
		global $cache_path, $blogcacheid, $blog_cache_dir, $current_site;

		if ( ! is_multisite() ) {
			// Prevents PHP notices for single site installation.
			if ( ! isset( $blog_cache_dir ) ) {
				$blog_cache_dir = $cache_path;
			}

			return;
		}

		if ( is_object( $current_site ) ) {
			$blogcacheid    = trim( is_subdomain_install() ? $current_site->domain : $current_site->path, '/' );
			$blog_cache_dir = $cache_path . 'blogs/' . $blogcacheid . '/';
		}
	}

	/**
	 * Gets absolute path for file if file exists.
	 * Returns empty string if file doesn't exist or isn't readable.
	 *
	 * @param string $filename File name.
	 *
	 * @return string
	 */
	private function get_wpsc_filename( $filename ) {
		if ( 0 !== strpos( $filename, ABSPATH ) ) {
			$filename = WPCACHEHOME . $filename;
		}

		if ( ! is_file( $filename ) || ! is_readable( $filename ) ) {
			return '';
		}

		return $filename;
	}

	/**
	 * If function doesn't exist then loads file and ivokes function if it needs.
	 * Explicitly declares all globals which WPSC uses.
	 *
	 * @param string $func     Function name.
	 * @param string $filename File name.
	 * @param string $run      Optional function will be called if file is included.
	 *
	 * @return boolean True if file is included or false if it isn't included.
	 */
	private function maybe_include_file( $func, $filename, $run = '' ) {
		// Globals from wp-cache-config.php.
		global $super_cache_enabled, $cache_enabled, $wp_cache_mod_rewrite, $wp_cache_home_path, $cache_path, $file_prefix;
		global $wp_cache_mutex_disabled, $mutex_filename, $sem_id, $wp_super_cache_late_init;
		global $cache_compression, $cache_max_time, $wp_cache_shutdown_gc, $cache_rebuild_files;
		global $wp_super_cache_debug, $wp_super_cache_advanced_debug, $wp_cache_debug_level, $wp_cache_debug_to_file;
		global $wp_cache_debug_log, $wp_cache_debug_ip, $wp_cache_debug_username, $wp_cache_debug_email;
		global $cache_time_interval, $cache_scheduled_time, $cache_schedule_interval, $cache_schedule_type, $cache_gc_email_me;
		global $wp_cache_preload_on, $wp_cache_preload_interval, $wp_cache_preload_posts, $wp_cache_preload_taxonomies;
		global $wp_cache_preload_email_me, $wp_cache_preload_email_volume;
		global $wp_cache_mobile, $wp_cache_mobile_enabled, $wp_cache_mobile_browsers, $wp_cache_mobile_prefixes;
		// Globals from other files.
		global $wp_cache_config_file, $wp_cache_config_file_sample, $cache_domain_mapping;
		global $WPSC_HTTP_HOST, $blogcacheid, $blog_cache_dir;

		$file = $this->get_wpsc_filename( $filename );

		if ( empty( $file ) ||
			( ! in_array( $func, array( 'require', 'require_once', 'include', 'include_once' ), true )
				&& function_exists( $func )
			)
		) {
			return false;
		}

		switch ( $func ) {
			case 'require':
				$loaded = require $file;
				break;
			case 'require_once':
				$loaded = require_once $file;
				break;
			case 'include':
				$loaded = include $file;
				break;
			case 'include_once':
			default:
				$loaded = include_once $file;
				break;
		}

		if ( $loaded && ! empty( $run ) && function_exists( $run ) ) {
			call_user_func( $run );
		}

		return $loaded;
	}

	/**
	 * Gets version of WP Super Cache.
	 *
	 * @global string $wp_cache_config_file_sample Absolute path to wp-cache config sample file.
	 *
	 * @return string
	 */
	public function get_wpsc_version() {
		global $wp_cache_config_file_sample;

		if ( isset( $this->wpsc_version ) ) {
			return $this->wpsc_version;
		}

		if ( ! function_exists( 'get_file_data' ) ) {
			return '';
		}

		if ( ! function_exists( 'get_plugin_data' ) ) {
			require_once ABSPATH . 'wp-admin/includes/plugin.php';
		}

		$this->wpsc_version     = '';
		$this->wpsc_plugin_file = empty( $wp_cache_config_file_sample )
			? trailingslashit( WP_PLUGIN_DIR ) . 'wp-super-cache/wp-cache.php'
			: plugin_dir_path( $wp_cache_config_file_sample ) . 'wp-cache.php';

		if ( ! is_file( $this->wpsc_plugin_file ) || ! is_readable( $this->wpsc_plugin_file ) ) {
			return $this->wpsc_version;
		}

		$plugin_details = get_plugin_data( $this->wpsc_plugin_file );
		if ( ! empty( $plugin_details['Version'] ) ) {
			$this->wpsc_version = $plugin_details['Version'];
		}

		return $this->wpsc_version;
	}

	/**
	 * Check whether wp-super-cache plugin is active.
	 *
	 * @return bool
	 */
	private function is_wpsc_plugin_active() {
		if ( $this->get_wpsc_version() && is_plugin_active( plugin_basename( $this->wpsc_plugin_file ) ) ) {
			return true;
		}

		return false;
	}

	/**
	 * Retrieves the component (PHP_URL_HOST or PHP_URL_PATH) from home URL.
	 *
	 * @param int $component The component to retrieve.
	 *
	 * @return string
	 */
	private function parse_home_url( $component ) {
		return function_exists( 'wp_parse_url' )
			? (string) wp_parse_url( get_option( 'home' ), $component )
			: (string) parse_url( get_option( 'home' ), $component );
	}
}
