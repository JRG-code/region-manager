<?php
/**
 * Fired when the plugin is uninstalled.
 *
 * When populating this file, consider the following flow
 * of control:
 *
 * - This method should be static
 * - Check if the $_REQUEST content actually is the plugin name
 * - Run an admin referrer check to make sure it goes through authentication
 * - Verify the output of $_GET makes sense
 * - Repeat with other user roles. Best directly by using the links/query string parameters.
 * - Repeat things for multisite. Once for a single site in the network, once sitewide.
 *
 * @package    RegionManager
 * @since      1.0.0
 */

// If uninstall not called from WordPress, then exit.
if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

/**
 * Clean up plugin data on uninstall.
 *
 * @since 1.0.0
 */
function rm_uninstall_plugin() {
	global $wpdb;

	// Check if user wants to keep data on uninstall.
	$keep_data = get_option( 'rm_keep_data_on_uninstall', 'no' );

	if ( 'yes' === $keep_data ) {
		return;
	}

	// Delete custom database tables.
	$table_prefix = $wpdb->prefix;

	$tables = array(
		"{$table_prefix}rm_regions",
		"{$table_prefix}rm_region_countries",
		"{$table_prefix}rm_product_regions",
		"{$table_prefix}rm_region_settings",
	);

	foreach ( $tables as $table ) {
		$wpdb->query( "DROP TABLE IF EXISTS {$table}" ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
	}

	// Delete plugin options.
	$options = array(
		'rm_db_version',
		'rm_default_region',
		'rm_auto_detect_region',
		'rm_allow_region_switching',
		'rm_region_cookie_lifetime',
		'rm_fallback_behavior',
		'rm_hide_unavailable_products',
		'rm_activation_date',
		'rm_keep_data_on_uninstall',
	);

	foreach ( $options as $option ) {
		delete_option( $option );
	}

	// Clear any transients.
	delete_transient( 'rm_active_regions' );
	delete_transient( 'rm_region_countries' );

	// For multisite installations, clean up options for all sites.
	if ( is_multisite() ) {
		$sites = get_sites( array( 'number' => 0 ) );

		foreach ( $sites as $site ) {
			switch_to_blog( $site->blog_id );

			// Delete tables for each site.
			foreach ( $tables as $table ) {
				$wpdb->query( "DROP TABLE IF EXISTS {$table}" ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
			}

			// Delete options for each site.
			foreach ( $options as $option ) {
				delete_option( $option );
			}

			// Clear transients for each site.
			delete_transient( 'rm_active_regions' );
			delete_transient( 'rm_region_countries' );

			restore_current_blog();
		}
	}
}

rm_uninstall_plugin();
