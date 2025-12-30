<?php
/**
 * Settings page functionality.
 *
 * Handles the settings interface including regions management,
 * checkout settings, translator integration, and license management.
 *
 * @package    RegionManager
 * @subpackage RegionManager/admin
 * @since      1.0.0
 */

/**
 * Settings page class.
 *
 * @since 1.0.0
 */
class RM_Settings {

	/**
	 * Initialize the class.
	 *
	 * @since 1.0.0
	 */
	public function __construct() {
		// AJAX handlers.
		add_action( 'wp_ajax_rm_save_region', array( $this, 'ajax_save_region' ) );
		add_action( 'wp_ajax_rm_delete_region', array( $this, 'ajax_delete_region' ) );
		add_action( 'wp_ajax_rm_get_region', array( $this, 'ajax_get_region' ) );
		add_action( 'wp_ajax_rm_save_country', array( $this, 'ajax_save_country' ) );
		add_action( 'wp_ajax_rm_get_country', array( $this, 'ajax_get_country' ) );
		add_action( 'wp_ajax_rm_delete_country', array( $this, 'ajax_delete_country' ) );
		add_action( 'wp_ajax_rm_save_checkout_settings', array( $this, 'ajax_save_checkout_settings' ) );
	}

	/**
	 * Display the settings page.
	 *
	 * @since 1.0.0
	 */
	public function display() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'You do not have sufficient permissions to access this page.', 'region-manager' ) );
		}

		// Get current tab.
		$current_tab = isset( $_GET['tab'] ) ? sanitize_text_field( wp_unslash( $_GET['tab'] ) ) : 'regions';

		// Handle form submissions for license tab.
		if ( 'license' === $current_tab ) {
			if ( isset( $_POST['rm_license_nonce'] ) && wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['rm_license_nonce'] ) ), 'rm_license_action' ) ) {
				$this->handle_license_action();
			}
		}

		include RM_PLUGIN_DIR . 'admin/partials/settings-display.php';
	}

	/**
	 * Get all regions from database.
	 *
	 * @since  1.0.0
	 * @return array Array of region objects.
	 */
	public function get_regions() {
		global $wpdb;

		$table_name = $wpdb->prefix . 'rm_regions';
		$results    = $wpdb->get_results( "SELECT * FROM {$table_name} ORDER BY created_at DESC" ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching

		return $results;
	}

	/**
	 * Get region by ID.
	 *
	 * @since  1.0.0
	 * @param  int $region_id Region ID.
	 * @return object|null Region object or null.
	 */
	public function get_region( $region_id ) {
		global $wpdb;

		$table_name = $wpdb->prefix . 'rm_regions';
		$region     = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$table_name} WHERE id = %d", $region_id ) ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching

		return $region;
	}

	/**
	 * Get countries for a region.
	 *
	 * @since  1.0.0
	 * @param  int $region_id Region ID.
	 * @return array Array of country objects.
	 */
	public function get_region_countries( $region_id ) {
		global $wpdb;

		$table_name = $wpdb->prefix . 'rm_region_countries';
		$countries  = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM {$table_name} WHERE region_id = %d ORDER BY country_code", $region_id ) ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching

		return $countries;
	}

	/**
	 * Get available WooCommerce countries.
	 *
	 * @since  1.0.0
	 * @return array Array of countries.
	 */
	public function get_available_countries() {
		if ( ! class_exists( 'WC_Countries' ) ) {
			return array();
		}

		$wc_countries = new WC_Countries();
		return $wc_countries->get_countries();
	}

	/**
	 * Get available language codes.
	 *
	 * @since  1.0.0
	 * @return array Array of language codes.
	 */
	public function get_language_codes() {
		return array(
			'en_US' => __( 'English (United States)', 'region-manager' ),
			'en_GB' => __( 'English (United Kingdom)', 'region-manager' ),
			'en_CA' => __( 'English (Canada)', 'region-manager' ),
			'en_AU' => __( 'English (Australia)', 'region-manager' ),
			'pt_PT' => __( 'Portuguese (Portugal)', 'region-manager' ),
			'pt_BR' => __( 'Portuguese (Brazil)', 'region-manager' ),
			'es_ES' => __( 'Spanish (Spain)', 'region-manager' ),
			'es_MX' => __( 'Spanish (Mexico)', 'region-manager' ),
			'es_AR' => __( 'Spanish (Argentina)', 'region-manager' ),
			'fr_FR' => __( 'French (France)', 'region-manager' ),
			'fr_CA' => __( 'French (Canada)', 'region-manager' ),
			'de_DE' => __( 'German (Germany)', 'region-manager' ),
			'de_AT' => __( 'German (Austria)', 'region-manager' ),
			'de_CH' => __( 'German (Switzerland)', 'region-manager' ),
			'it_IT' => __( 'Italian (Italy)', 'region-manager' ),
			'nl_NL' => __( 'Dutch (Netherlands)', 'region-manager' ),
			'nl_BE' => __( 'Dutch (Belgium)', 'region-manager' ),
			'pl_PL' => __( 'Polish (Poland)', 'region-manager' ),
			'ru_RU' => __( 'Russian (Russia)', 'region-manager' ),
			'ja_JP' => __( 'Japanese (Japan)', 'region-manager' ),
			'zh_CN' => __( 'Chinese (Simplified)', 'region-manager' ),
			'zh_TW' => __( 'Chinese (Traditional)', 'region-manager' ),
			'ko_KR' => __( 'Korean (Korea)', 'region-manager' ),
			'ar_SA' => __( 'Arabic (Saudi Arabia)', 'region-manager' ),
			'he_IL' => __( 'Hebrew (Israel)', 'region-manager' ),
			'tr_TR' => __( 'Turkish (Turkey)', 'region-manager' ),
			'sv_SE' => __( 'Swedish (Sweden)', 'region-manager' ),
			'no_NO' => __( 'Norwegian (Norway)', 'region-manager' ),
			'da_DK' => __( 'Danish (Denmark)', 'region-manager' ),
			'fi_FI' => __( 'Finnish (Finland)', 'region-manager' ),
		);
	}

	/**
	 * AJAX: Save region.
	 *
	 * @since 1.0.0
	 */
	public function ajax_save_region() {
		check_ajax_referer( 'rm_admin_nonce', 'nonce' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => __( 'Permission denied.', 'region-manager' ) ) );
		}

		global $wpdb;

		$region_id = isset( $_POST['region_id'] ) ? absint( $_POST['region_id'] ) : 0;
		$name      = isset( $_POST['name'] ) ? sanitize_text_field( wp_unslash( $_POST['name'] ) ) : '';
		$slug      = isset( $_POST['slug'] ) ? sanitize_title( wp_unslash( $_POST['slug'] ) ) : '';
		$status    = isset( $_POST['status'] ) ? sanitize_text_field( wp_unslash( $_POST['status'] ) ) : 'active';
		$countries = isset( $_POST['countries'] ) ? json_decode( wp_unslash( $_POST['countries'] ), true ) : array(); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized

		// Ensure $countries is an array (json_decode can return null on invalid JSON).
		if ( ! is_array( $countries ) ) {
			$countries = array();
		}

		// Validate.
		if ( empty( $name ) ) {
			wp_send_json_error( array( 'message' => __( 'Region name is required.', 'region-manager' ) ) );
		}

		if ( empty( $slug ) ) {
			$slug = sanitize_title( $name );
		}

		// Check license limits for new regions.
		if ( 0 === $region_id ) {
			$license = RM_License::get_instance();
			if ( ! $license->can_create_region() ) {
				wp_send_json_error( array(
					'message'      => __( 'You have reached your region limit. Please upgrade to Pro for unlimited regions.', 'region-manager' ),
					'upgrade_url'  => admin_url( 'admin.php?page=rm-settings&tab=license' ),
				) );
			}
		}

		$table_regions = $wpdb->prefix . 'rm_regions';

		// Check if slug already exists (exclude current region if editing).
		$existing = $wpdb->get_var( $wpdb->prepare(
			"SELECT COUNT(*) FROM {$table_regions} WHERE slug = %s AND id != %d",
			$slug,
			$region_id
		) ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching

		if ( $existing > 0 ) {
			wp_send_json_error( array( 'message' => __( 'Region slug already exists.', 'region-manager' ) ) );
		}

		// Insert or update region.
		$data = array(
			'name'   => $name,
			'slug'   => $slug,
			'status' => $status,
		);

		if ( $region_id > 0 ) {
			// Update existing region.
			$wpdb->update(
				$table_regions,
				$data,
				array( 'id' => $region_id ),
				array( '%s', '%s', '%s' ),
				array( '%d' )
			);
		} else {
			// Insert new region.
			$wpdb->insert(
				$table_regions,
				$data,
				array( '%s', '%s', '%s' )
			);
			$region_id = $wpdb->insert_id;
		}

		// Save countries.
		$table_countries = $wpdb->prefix . 'rm_region_countries';

		// Delete existing countries for this region.
		$wpdb->delete( $table_countries, array( 'region_id' => $region_id ), array( '%d' ) );

		// Insert new countries.
		if ( ! empty( $countries ) && is_array( $countries ) ) {
			foreach ( $countries as $country ) {
				$country_code  = isset( $country['country_code'] ) ? sanitize_text_field( $country['country_code'] ) : '';
				$url_slug      = isset( $country['url_slug'] ) ? sanitize_text_field( $country['url_slug'] ) : '';
				$language_code = isset( $country['language_code'] ) ? sanitize_text_field( $country['language_code'] ) : '';
				$currency_code = isset( $country['currency_code'] ) ? sanitize_text_field( $country['currency_code'] ) : 'EUR';
				$is_default    = isset( $country['is_default'] ) && $country['is_default'] ? 1 : 0;

				if ( empty( $country_code ) ) {
					continue;
				}

				// If this is set as default, unset other defaults.
				if ( $is_default ) {
					$wpdb->update(
						$table_countries,
						array( 'is_default' => 0 ),
						array( 'region_id' => $region_id ),
						array( '%d' ),
						array( '%d' )
					);
				}

				$wpdb->insert(
					$table_countries,
					array(
						'region_id'     => $region_id,
						'country_code'  => $country_code,
						'url_slug'      => $url_slug,
						'language_code' => $language_code,
						'currency_code' => $currency_code,
						'is_default'    => $is_default,
					),
					array( '%d', '%s', '%s', '%s', '%s', '%d' )
				);
			}
		}

		wp_send_json_success( array(
			'message'   => __( 'Region saved successfully.', 'region-manager' ),
			'region_id' => $region_id,
		) );
	}

	/**
	 * AJAX: Delete region.
	 *
	 * @since 1.0.0
	 */
	public function ajax_delete_region() {
		check_ajax_referer( 'rm_admin_nonce', 'nonce' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => __( 'Permission denied.', 'region-manager' ) ) );
		}

		$region_id = isset( $_POST['region_id'] ) ? absint( $_POST['region_id'] ) : 0;

		if ( ! $region_id ) {
			wp_send_json_error( array( 'message' => __( 'Invalid region ID.', 'region-manager' ) ) );
		}

		global $wpdb;

		// Delete region.
		$wpdb->delete(
			$wpdb->prefix . 'rm_regions',
			array( 'id' => $region_id ),
			array( '%d' )
		);

		// Delete region countries.
		$wpdb->delete(
			$wpdb->prefix . 'rm_region_countries',
			array( 'region_id' => $region_id ),
			array( '%d' )
		);

		// Delete region product associations.
		$wpdb->delete(
			$wpdb->prefix . 'rm_product_regions',
			array( 'region_id' => $region_id ),
			array( '%d' )
		);

		// Delete region settings.
		$wpdb->delete(
			$wpdb->prefix . 'rm_region_settings',
			array( 'region_id' => $region_id ),
			array( '%d' )
		);

		wp_send_json_success( array( 'message' => __( 'Region deleted successfully.', 'region-manager' ) ) );
	}

	/**
	 * AJAX: Get region data.
	 *
	 * @since 1.0.0
	 */
	public function ajax_get_region() {
		check_ajax_referer( 'rm_admin_nonce', 'nonce' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => __( 'Permission denied.', 'region-manager' ) ) );
		}

		$region_id = isset( $_POST['region_id'] ) ? absint( $_POST['region_id'] ) : 0;

		if ( ! $region_id ) {
			wp_send_json_error( array( 'message' => __( 'Invalid region ID.', 'region-manager' ) ) );
		}

		$region    = $this->get_region( $region_id );
		$countries = $this->get_region_countries( $region_id );

		if ( ! $region ) {
			wp_send_json_error( array( 'message' => __( 'Region not found.', 'region-manager' ) ) );
		}

		wp_send_json_success( array(
			'region'    => $region,
			'countries' => $countries,
		) );
	}

	/**
	 * AJAX: Save checkout settings.
	 *
	 * @since 1.0.0
	 */
	public function ajax_save_checkout_settings() {
		check_ajax_referer( 'rm_admin_nonce', 'nonce' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => __( 'Permission denied.', 'region-manager' ) ) );
		}

		$cross_region_purchase = isset( $_POST['cross_region_purchase'] ) ? sanitize_text_field( wp_unslash( $_POST['cross_region_purchase'] ) ) : 'allow';
		$extra_charge          = isset( $_POST['extra_charge'] ) ? floatval( $_POST['extra_charge'] ) : 0;
		$charge_type           = isset( $_POST['charge_type'] ) ? sanitize_text_field( wp_unslash( $_POST['charge_type'] ) ) : 'per_order';
		$block_message         = isset( $_POST['block_message'] ) ? sanitize_textarea_field( wp_unslash( $_POST['block_message'] ) ) : '';
		$geoip_fallback        = isset( $_POST['geoip_fallback'] ) ? 1 : 0;
		$default_region_id     = isset( $_POST['default_region_id'] ) && '' !== $_POST['default_region_id'] ? absint( $_POST['default_region_id'] ) : null;

		// Save settings.
		update_option( 'rm_cross_region_purchase', $cross_region_purchase );
		update_option( 'rm_extra_charge', $extra_charge );
		update_option( 'rm_charge_type', $charge_type );
		update_option( 'rm_block_message', $block_message );
		update_option( 'rm_geoip_fallback', $geoip_fallback );

		// Save default region (delete option if null to allow fallback to first region).
		if ( null === $default_region_id ) {
			delete_option( 'rm_default_region_id' );
		} else {
			update_option( 'rm_default_region_id', $default_region_id );
		}

		wp_send_json_success( array( 'message' => __( 'Checkout settings saved successfully.', 'region-manager' ) ) );
	}

	/**
	 * Handle license activation/deactivation.
	 *
	 * @since 1.0.0
	 */
	private function handle_license_action() {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		if ( ! isset( $_POST['license_action'] ) ) {
			return;
		}

		$license = RM_License::get_instance();
		$action  = sanitize_text_field( wp_unslash( $_POST['license_action'] ) );

		if ( 'activate' === $action && isset( $_POST['license_key'] ) ) {
			$license_key = sanitize_text_field( wp_unslash( $_POST['license_key'] ) );
			$result = $license->activate_license( $license_key );

			if ( $result['success'] ) {
				add_action( 'admin_notices', function() use ( $result ) {
					?>
					<div class="notice notice-success is-dismissible">
						<p><?php echo esc_html( $result['message'] ); ?></p>
					</div>
					<?php
				} );
			} else {
				add_action( 'admin_notices', function() use ( $result ) {
					?>
					<div class="notice notice-error is-dismissible">
						<p><?php echo esc_html( $result['message'] ); ?></p>
					</div>
					<?php
				} );
			}
		} elseif ( 'deactivate' === $action ) {
			$result = $license->deactivate_license();

			add_action( 'admin_notices', function() use ( $result ) {
				$notice_type = $result['success'] ? 'success' : 'error';
				?>
				<div class="notice notice-<?php echo esc_attr( $notice_type ); ?> is-dismissible">
					<p><?php echo esc_html( $result['message'] ); ?></p>
				</div>
				<?php
			} );
		}
	}

	/**
	 * Get detected translator plugins.
	 *
	 * @since  1.0.0
	 * @return array Array of detected plugins.
	 */
	public function get_translator_plugins() {
		$plugins = array();

		if ( defined( 'ICL_SITEPRESS_VERSION' ) ) {
			$plugins['wpml'] = array(
				'name'    => 'WPML',
				'version' => ICL_SITEPRESS_VERSION,
				'active'  => true,
			);
		}

		if ( defined( 'POLYLANG_VERSION' ) ) {
			$plugins['polylang'] = array(
				'name'    => 'Polylang',
				'version' => POLYLANG_VERSION,
				'active'  => true,
			);
		}

		if ( defined( 'TRP_PLUGIN_VERSION' ) ) {
			$plugins['translatepress'] = array(
				'name'    => 'TranslatePress',
				'version' => TRP_PLUGIN_VERSION,
				'active'  => true,
			);
		}

		return $plugins;
	}

	/**
	 * AJAX handler to save a country.
	 *
	 * @since 1.0.0
	 */
	public function ajax_save_country() {
		check_ajax_referer( 'rm_admin_nonce', 'nonce' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => __( 'Permission denied.', 'region-manager' ) ) );
		}

		global $wpdb;
		$table_name = $wpdb->prefix . 'rm_region_countries';

		$country_id    = isset( $_POST['country_id'] ) ? absint( $_POST['country_id'] ) : 0;
		$region_id     = isset( $_POST['region_id'] ) ? absint( $_POST['region_id'] ) : 0;
		$country_code  = isset( $_POST['country_code'] ) ? sanitize_text_field( wp_unslash( $_POST['country_code'] ) ) : '';
		$url_slug      = isset( $_POST['url_slug'] ) ? sanitize_text_field( wp_unslash( $_POST['url_slug'] ) ) : '';
		$language_code = isset( $_POST['language_code'] ) ? sanitize_text_field( wp_unslash( $_POST['language_code'] ) ) : '';
		$currency_code = isset( $_POST['currency_code'] ) ? sanitize_text_field( wp_unslash( $_POST['currency_code'] ) ) : '';
		$is_default    = isset( $_POST['is_default'] ) ? absint( $_POST['is_default'] ) : 0;

		if ( empty( $region_id ) || empty( $country_code ) || empty( $url_slug ) || empty( $language_code ) ) {
			wp_send_json_error( array( 'message' => __( 'Missing required fields.', 'region-manager' ) ) );
		}

		// If this is set as default, unset other defaults in the region
		if ( $is_default ) {
			$wpdb->update(
				$table_name,
				array( 'is_default' => 0 ),
				array( 'region_id' => $region_id ),
				array( '%d' ),
				array( '%d' )
			);
		}

		$data = array(
			'region_id'     => $region_id,
			'country_code'  => $country_code,
			'url_slug'      => $url_slug,
			'language_code' => $language_code,
			'currency_code' => $currency_code,
			'is_default'    => $is_default,
		);

		$format = array( '%d', '%s', '%s', '%s', '%s', '%d' );

		if ( $country_id > 0 ) {
			// Update existing country
			$result = $wpdb->update(
				$table_name,
				$data,
				array( 'id' => $country_id ),
				$format,
				array( '%d' )
			);
		} else {
			// Insert new country
			$result = $wpdb->insert(
				$table_name,
				$data,
				$format
			);
			$country_id = $wpdb->insert_id;
		}

		if ( false === $result ) {
			wp_send_json_error( array( 'message' => __( 'Failed to save country.', 'region-manager' ) ) );
		}

		wp_send_json_success(
			array(
				'message'    => __( 'Country saved successfully.', 'region-manager' ),
				'country_id' => $country_id,
			)
		);
	}

	/**
	 * AJAX handler to get a country.
	 *
	 * @since 1.0.0
	 */
	public function ajax_get_country() {
		check_ajax_referer( 'rm_admin_nonce', 'nonce' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => __( 'Permission denied.', 'region-manager' ) ) );
		}

		$country_id = isset( $_POST['country_id'] ) ? absint( $_POST['country_id'] ) : 0;

		if ( ! $country_id ) {
			wp_send_json_error( array( 'message' => __( 'Invalid country ID.', 'region-manager' ) ) );
		}

		global $wpdb;
		$table_name = $wpdb->prefix . 'rm_region_countries';

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$country = $wpdb->get_row(
			$wpdb->prepare(
				"SELECT * FROM {$table_name} WHERE id = %d", // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
				$country_id
			)
		);

		if ( ! $country ) {
			wp_send_json_error( array( 'message' => __( 'Country not found.', 'region-manager' ) ) );
		}

		wp_send_json_success( array( 'country' => $country ) );
	}

	/**
	 * AJAX handler to delete a country.
	 *
	 * @since 1.0.0
	 */
	public function ajax_delete_country() {
		check_ajax_referer( 'rm_admin_nonce', 'nonce' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => __( 'Permission denied.', 'region-manager' ) ) );
		}

		$country_id = isset( $_POST['country_id'] ) ? absint( $_POST['country_id'] ) : 0;

		if ( ! $country_id ) {
			wp_send_json_error( array( 'message' => __( 'Invalid country ID.', 'region-manager' ) ) );
		}

		global $wpdb;
		$table_name = $wpdb->prefix . 'rm_region_countries';

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$result = $wpdb->delete(
			$table_name,
			array( 'id' => $country_id ),
			array( '%d' )
		);

		if ( false === $result ) {
			wp_send_json_error( array( 'message' => __( 'Failed to delete country.', 'region-manager' ) ) );
		}

		wp_send_json_success( array( 'message' => __( 'Country deleted successfully.', 'region-manager' ) ) );
	}
}
