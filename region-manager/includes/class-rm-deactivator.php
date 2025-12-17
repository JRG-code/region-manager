<?php
/**
 * Fired during plugin deactivation.
 *
 * This class defines all code necessary to run during the plugin's deactivation.
 *
 * @package    RegionManager
 * @subpackage RegionManager/includes
 * @since      1.0.0
 */

/**
 * Fired during plugin deactivation.
 *
 * @since 1.0.0
 */
class RM_Deactivator {

	/**
	 * Plugin deactivation handler.
	 *
	 * Flushes rewrite rules and clears scheduled hooks.
	 *
	 * @since 1.0.0
	 */
	public static function deactivate() {
		self::clear_scheduled_hooks();
		flush_rewrite_rules();
	}

	/**
	 * Clear all scheduled hooks created by the plugin.
	 *
	 * @since  1.0.0
	 * @access private
	 */
	private static function clear_scheduled_hooks() {
		// Clear any scheduled cron events.
		$scheduled_hooks = array(
			'rm_daily_cleanup',
			'rm_region_sync',
		);

		foreach ( $scheduled_hooks as $hook ) {
			$timestamp = wp_next_scheduled( $hook );
			if ( $timestamp ) {
				wp_unschedule_event( $timestamp, $hook );
			}
		}

		// Clear all instances of our hooks.
		wp_clear_scheduled_hook( 'rm_daily_cleanup' );
		wp_clear_scheduled_hook( 'rm_region_sync' );
	}
}
