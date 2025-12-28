<?php
/**
 * Fired during plugin activation.
 *
 * This class defines all code necessary to run during the plugin's activation.
 *
 * @package    RegionManager
 * @subpackage RegionManager/includes
 * @since      1.0.0
 */

/**
 * Fired during plugin activation.
 *
 * @since 1.0.0
 */
class RM_Activator {

	/**
	 * Plugin activation handler.
	 *
	 * Creates custom database tables, sets default options, and flushes rewrite rules.
	 *
	 * @since 1.0.0
	 */
	public static function activate() {
		self::create_tables();
		self::set_default_options();
		flush_rewrite_rules();
	}

	/**
	 * Create custom database tables.
	 *
	 * @since  1.0.0
	 * @access private
	 */
	private static function create_tables() {
		global $wpdb;

		$charset_collate = $wpdb->get_charset_collate();
		$table_prefix    = $wpdb->prefix;

		require_once ABSPATH . 'wp-admin/includes/upgrade.php';

		// Table: rm_regions.
		$sql_regions = "CREATE TABLE {$table_prefix}rm_regions (
			id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			name varchar(255) NOT NULL,
			slug varchar(100) NOT NULL,
			status enum('active','inactive') NOT NULL DEFAULT 'active',
			created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
			updated_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
			PRIMARY KEY  (id),
			UNIQUE KEY slug (slug),
			KEY status (status)
		) $charset_collate;";

		dbDelta( $sql_regions );

		// Table: rm_region_countries.
		$sql_countries = "CREATE TABLE {$table_prefix}rm_region_countries (
			id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			region_id bigint(20) unsigned NOT NULL,
			country_code varchar(2) NOT NULL,
			url_slug varchar(10) NOT NULL,
			language_code varchar(10) NOT NULL,
			currency_code varchar(3) DEFAULT 'EUR',
			is_default tinyint(1) NOT NULL DEFAULT 0,
			created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
			PRIMARY KEY  (id),
			KEY region_id (region_id),
			KEY country_code (country_code),
			KEY url_slug (url_slug),
			KEY currency_code (currency_code),
			UNIQUE KEY unique_region_country (region_id, country_code)
		) $charset_collate;";

		dbDelta( $sql_countries );

		// Upgrade: Add currency_code to existing rm_region_countries table.
		$column_check = $wpdb->get_row( "SHOW COLUMNS FROM {$table_prefix}rm_region_countries WHERE Field = 'currency_code'" );
		if ( ! $column_check ) {
			$wpdb->query( "ALTER TABLE {$table_prefix}rm_region_countries ADD COLUMN currency_code VARCHAR(3) DEFAULT 'EUR' AFTER language_code" );
			$wpdb->query( "ALTER TABLE {$table_prefix}rm_region_countries ADD KEY currency_code (currency_code)" );
		}

		// Table: rm_product_regions.
		$sql_product_regions = "CREATE TABLE {$table_prefix}rm_product_regions (
			id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			product_id bigint(20) unsigned NOT NULL,
			region_id bigint(20) unsigned NOT NULL,
			price_override decimal(10,2) DEFAULT NULL,
			sale_price_override decimal(10,2) DEFAULT NULL,
			is_available tinyint(1) NOT NULL DEFAULT 1,
			created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
			updated_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
			PRIMARY KEY  (id),
			KEY product_id (product_id),
			KEY region_id (region_id),
			UNIQUE KEY unique_product_region (product_id, region_id)
		) $charset_collate;";

		dbDelta( $sql_product_regions );

		// Table: rm_product_country_prices (country-specific pricing).
		$sql_product_country_prices = "CREATE TABLE {$table_prefix}rm_product_country_prices (
			id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			product_id bigint(20) unsigned NOT NULL,
			country_code varchar(2) NOT NULL,
			price decimal(10,2) DEFAULT NULL,
			sale_price decimal(10,2) DEFAULT NULL,
			currency_code varchar(3) DEFAULT 'EUR',
			created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
			updated_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
			PRIMARY KEY  (id),
			KEY product_id (product_id),
			KEY country_code (country_code),
			KEY currency_code (currency_code),
			UNIQUE KEY unique_product_country (product_id, country_code)
		) $charset_collate;";

		dbDelta( $sql_product_country_prices );

		// Table: rm_region_settings.
		$sql_settings = "CREATE TABLE {$table_prefix}rm_region_settings (
			id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			region_id bigint(20) unsigned NOT NULL,
			setting_key varchar(100) NOT NULL,
			setting_value text,
			PRIMARY KEY  (id),
			KEY region_id (region_id),
			KEY setting_key (setting_key),
			UNIQUE KEY unique_region_setting (region_id, setting_key)
		) $charset_collate;";

		dbDelta( $sql_settings );

		// Table: rm_regional_pages.
		$sql_regional_pages = "CREATE TABLE {$table_prefix}rm_regional_pages (
			id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			region_id bigint(20) unsigned NOT NULL,
			page_type varchar(50) NOT NULL,
			page_id varchar(50) NOT NULL,
			is_active tinyint(1) NOT NULL DEFAULT 1,
			created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
			updated_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
			PRIMARY KEY  (id),
			KEY region_id (region_id),
			KEY page_type (page_type),
			KEY page_id (page_id),
			UNIQUE KEY unique_region_page_type (region_id, page_type)
		) $charset_collate;";

		dbDelta( $sql_regional_pages );

		// Upgrade existing table to support 'shop' and 'home' values.
		$column_info = $wpdb->get_row( "SHOW COLUMNS FROM {$table_prefix}rm_regional_pages WHERE Field = 'page_id'" );
		if ( $column_info && false !== stripos( $column_info->Type, 'int' ) ) {
			// Column is INT, need to change to VARCHAR.
			$wpdb->query( "ALTER TABLE {$table_prefix}rm_regional_pages MODIFY COLUMN page_id VARCHAR(50) NOT NULL" );
		}

		// Table: rm_regional_content.
		$sql_regional_content = "CREATE TABLE {$table_prefix}rm_regional_content (
			id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			region_id bigint(20) unsigned NOT NULL,
			content_key varchar(100) NOT NULL,
			content_value longtext,
			created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
			updated_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
			PRIMARY KEY  (id),
			KEY region_id (region_id),
			KEY content_key (content_key),
			UNIQUE KEY unique_region_content (region_id, content_key)
		) $charset_collate;";

		dbDelta( $sql_regional_content );

		// Store database version for future migrations.
		update_option( 'rm_db_version', RM_VERSION );
	}

	/**
	 * Set default plugin options.
	 *
	 * @since  1.0.0
	 * @access private
	 */
	private static function set_default_options() {
		$default_options = array(
			'rm_default_region'            => '',
			'rm_auto_detect_region'        => 'yes',
			'rm_allow_region_switching'    => 'yes',
			'rm_region_cookie_lifetime'    => 30,
			'rm_fallback_behavior'         => 'default',
			'rm_hide_unavailable_products' => 'no',
			'rm_license_status'            => 'free',
			'rm_license_key'               => '',
		);

		foreach ( $default_options as $option_name => $option_value ) {
			if ( false === get_option( $option_name ) ) {
				add_option( $option_name, $option_value );
			}
		}

		// Set activation timestamp.
		if ( false === get_option( 'rm_activation_date' ) ) {
			add_option( 'rm_activation_date', current_time( 'mysql' ) );
		}
	}
}
