<?php

/**
 * Manages the WP Super Cache plugin
 */
class WPSuperCache_Command extends WP_CLI_Command {

	/**
	 * Clear something from the cache.
	 *
	 * @synopsis [--post_id=<post-id>] [--permalink=<permalink>]
	 */
	function flush( $args = array(), $assoc_args = array() ) {
		require_once( WPCACHEHOME . '/wp-cache-phase1.php' );
		global $WPSC_HTTP_HOST;
		$WPSC_HTTP_HOST = parse_url(home_url())['host'];

		if ( isset($assoc_args['post_id']) ) {
			if ( is_numeric( $assoc_args['post_id'] ) ) {
				wp_cache_post_change( $assoc_args['post_id'] );
			} else {
				WP_CLI::error('This is not a valid post id.');
			}

			wp_cache_post_change( $assoc_args['post_id'] );
		}
		elseif ( isset( $assoc_args['permalink'] ) ) {
			$id = url_to_postid( $assoc_args['permalink'] );

			if ( is_numeric( $id ) ) {
				wp_cache_post_change( $id );
			} else {
				WP_CLI::error('There is no post with this permalink.');
			}
		} else {
			global $file_prefix;
			wp_cache_clean_cache( $file_prefix, true );
			WP_CLI::success( 'Cache cleared.' );
		}
	}

	/**
	 * Get the status of the cache.
	 */
	function status( $args = array(), $assoc_args = array() ) {
		$cache_stats = get_option( 'supercache_stats' );

		if ( !empty( $cache_stats ) ) {
			if ( $cache_stats['generated'] > time() - 3600 * 24 ) {
				global $super_cache_enabled;
				WP_CLI::line( 'Cache status: ' . ($super_cache_enabled ? '%gOn%n' : '%rOff%n') );
				WP_CLI::line( 'Cache content on ' . date('r', $cache_stats['generated'] ) . ': ' );
				WP_CLI::line();
				WP_CLI::line( '    WordPress cache:' );
				WP_CLI::line( '        Cached: ' . $cache_stats[ 'wpcache' ][ 'cached' ] );
				WP_CLI::line( '        Expired: ' . $cache_stats[ 'wpcache' ][ 'expired' ] );
				WP_CLI::line();
				WP_CLI::line( '    WP Super Cache:' );
				WP_CLI::line( '        Cached: ' . $cache_stats[ 'supercache' ][ 'cached' ] );
				WP_CLI::line( '        Expired: ' . $cache_stats[ 'supercache' ][ 'expired' ] );
			} else {
				WP_CLI::error('The WP Super Cache stats are too old to work with (older than 24 hours).');
			}
		} else {
			WP_CLI::error('No WP Super Cache stats found.');
		}
	}

	/**
	 * Enable the WP Super Cache.
	 */
	function enable( $args = array(), $assoc_args = array() ) {
		global $super_cache_enabled;

		wp_super_cache_enable();

		if($super_cache_enabled) {
			WP_CLI::success( 'The WP Super Cache is enabled.' );
		} else {
			WP_CLI::error('The WP Super Cache is not enabled, check its settings page for more info.');
		}
	}

	/**
	 * Disable the WP Super Cache.
	 */
	function disable( $args = array(), $assoc_args = array() ) {
		global $super_cache_enabled;

		wp_super_cache_disable();

		if(!$super_cache_enabled) {
			WP_CLI::success( 'The WP Super Cache is disabled.' );
		} else {
			WP_CLI::error('The WP Super Cache is still enabled, check its settings page for more info.');
		}
	}

	/**
	 * Primes the cache by creating static pages before users visit them
	 *
	 * @synopsis [--status] [--cancel]
	 */
	function preload( $args = array(), $assoc_args = array() ) {
		global $super_cache_enabled;
		$preload_counter = get_option( 'preload_cache_counter' );
		$preloading      = is_array( $preload_counter ) && $preload_counter['c'] > 0;
		$pending_cancel  = get_option( 'preload_cache_stop' );
			
		// Bail early if caching or preloading is disabled
		if( ! $super_cache_enabled ) {
			WP_CLI::error( 'The WP Super Cache is not enabled.' );
		}

		if ( defined( 'DISABLESUPERCACHEPRELOADING' ) && true == DISABLESUPERCACHEPRELOADING ) {
			WP_CLI::error( 'Cache preloading is not enabled.' );
		}

		// Display status
		if ( isset( $assoc_args['status'] ) ) {
			$this->preload_status( $preload_counter, $pending_cancel );
			exit();
		}

		// Cancel preloading if in progress
		if ( isset( $assoc_args['cancel'] ) ) {
			if ( $preloading ) {
				if ( $pending_cancel ) {
					WP_CLI::error( 'There is already a pending preload cancel. It may take up to a minute for it to cancel completely.' );
				} else {
					update_option( 'preload_cache_stop', true );
					WP_CLI::success( 'Scheduled preloading of cache almost cancelled. It may take up to a minute for it to cancel completely.' );
					exit();
				}
			} else {
				WP_CLI::error( 'Not currently preloading.' );
			}
		}
		 
		// Start preloading if not already in progress
		if ( $preloading ) {
			WP_CLI::warning( 'Cache preloading is already in progress.' );
			$this->preload_status( $preload_counter, $pending_cancel );
			exit();
		} else {
			wp_schedule_single_event( time(), 'wp_cache_full_preload_hook' );
			WP_CLI::success( 'Scheduled preload for next cron run.' );
		}
	}

	/**
	 * Outputs the status of preloading
	 *
	 * @param $preload_counter
	 * @param $pending_cancel
	 */
	protected function preload_status( $preload_counter, $pending_cancel ) {
		if ( is_array( $preload_counter ) && $preload_counter['c'] > 0 ) {
			WP_CLI::line( sprintf( 'Currently caching from post %d to %d.', $preload_counter[ 'c' ] - 100, $preload_counter[ 'c' ] ) );
			
			if ( $pending_cancel ) {
				WP_CLI::warning( 'Pending preload cancel. It may take up to a minute for it to cancel completely.' );
			}
		} else {
			WP_CLI::line( 'Not currently preloading.' );
		}
	}
}

WP_CLI::add_command( 'super-cache', 'WPSuperCache_Command' );

