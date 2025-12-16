<?php
/**
 * License Management Class
 *
 * Handles license tier validation and restrictions for the Region Manager plugin.
 *
 * @package    RegionManager
 * @subpackage RegionManager/includes
 * @since      1.0.0
 */

/**
 * License Management Class
 *
 * @since 1.0.0
 */
class RM_License {

	/**
	 * The single instance of the class.
	 *
	 * @var RM_License
	 * @since 1.0.0
	 */
	private static $instance = null;

	/**
	 * License status option key.
	 *
	 * @var string
	 * @since 1.0.0
	 */
	const LICENSE_STATUS_OPTION = 'rm_license_status';

	/**
	 * License key option key.
	 *
	 * @var string
	 * @since 1.0.0
	 */
	const LICENSE_KEY_OPTION = 'rm_license_key';

	/**
	 * Free tier region limit.
	 *
	 * @var int
	 * @since 1.0.0
	 */
	const FREE_TIER_MAX_REGIONS = 2;

	/**
	 * Pro tier region limit (unlimited).
	 *
	 * @var int
	 * @since 1.0.0
	 */
	const PRO_TIER_MAX_REGIONS = -1;

	/**
	 * Get singleton instance.
	 *
	 * @since  1.0.0
	 * @return RM_License
	 */
	public static function get_instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * Private constructor to prevent direct instantiation.
	 *
	 * @since 1.0.0
	 */
	private function __construct() {
		// Private constructor for singleton pattern.
	}

	/**
	 * Get the current license status.
	 *
	 * @since  1.0.0
	 * @return string License status: 'free' or 'pro'
	 */
	public function get_license_status() {
		$status = get_option( self::LICENSE_STATUS_OPTION, 'free' );
		return in_array( $status, array( 'free', 'pro' ), true ) ? $status : 'free';
	}

	/**
	 * Check if the current license is Pro.
	 *
	 * @since  1.0.0
	 * @return bool True if Pro license, false otherwise.
	 */
	public function is_pro() {
		return 'pro' === $this->get_license_status();
	}

	/**
	 * Get the maximum number of regions allowed for the current license.
	 *
	 * @since  1.0.0
	 * @return int Maximum regions allowed. -1 means unlimited.
	 */
	public function get_max_regions() {
		return $this->is_pro() ? self::PRO_TIER_MAX_REGIONS : self::FREE_TIER_MAX_REGIONS;
	}

	/**
	 * Get the current number of regions in the database.
	 *
	 * @since  1.0.0
	 * @return int Current region count.
	 */
	public function get_current_region_count() {
		global $wpdb;

		$table_name = $wpdb->prefix . 'rm_regions';
		$count      = $wpdb->get_var( "SELECT COUNT(*) FROM {$table_name}" ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching

		return absint( $count );
	}

	/**
	 * Check if a new region can be created based on license limits.
	 *
	 * @since  1.0.0
	 * @return bool True if region can be created, false otherwise.
	 */
	public function can_create_region() {
		$max_regions     = $this->get_max_regions();
		$current_regions = $this->get_current_region_count();

		// Unlimited regions.
		if ( -1 === $max_regions ) {
			return true;
		}

		// Check if under limit.
		return $current_regions < $max_regions;
	}

	/**
	 * Get the license key.
	 *
	 * @since  1.0.0
	 * @return string License key or empty string.
	 */
	public function get_license_key() {
		return get_option( self::LICENSE_KEY_OPTION, '' );
	}

	/**
	 * Activate a license key.
	 *
	 * @since  1.0.0
	 * @param  string $key The license key to activate.
	 * @return array       Associative array with 'success' (bool) and 'message' (string).
	 */
	public function activate_license( $key ) {
		// Sanitize the license key.
		$key = sanitize_text_field( trim( $key ) );

		// Validate input.
		if ( empty( $key ) ) {
			return array(
				'success' => false,
				'message' => __( 'License key cannot be empty.', 'region-manager' ),
			);
		}

		// TODO: Implement actual license validation API
		// Example API call:
		// $response = wp_remote_post('https://your-license-server.com/api/validate', [
		//     'body' => [
		//         'license_key' => $key,
		//         'site_url'    => home_url(),
		//         'product_id'  => 'region-manager',
		//     ],
		//     'timeout' => 15,
		// ]);
		//
		// if ( is_wp_error( $response ) ) {
		//     return array(
		//         'success' => false,
		//         'message' => __( 'Could not connect to license server.', 'region-manager' ),
		//     );
		// }
		//
		// $body = json_decode( wp_remote_retrieve_body( $response ), true );
		//
		// if ( ! isset( $body['valid'] ) || ! $body['valid'] ) {
		//     return array(
		//         'success' => false,
		//         'message' => $body['message'] ?? __( 'Invalid license key.', 'region-manager' ),
		//     );
		// }

		// Validate the license key (placeholder).
		if ( ! $this->validate_license( $key ) ) {
			return array(
				'success' => false,
				'message' => __( 'Invalid license key format.', 'region-manager' ),
			);
		}

		// Store the license key and activate Pro.
		update_option( self::LICENSE_KEY_OPTION, $key );
		update_option( self::LICENSE_STATUS_OPTION, 'pro' );

		// Log activation.
		do_action( 'rm_license_activated', $key );

		return array(
			'success' => true,
			'message' => __( 'License activated successfully! You now have unlimited regions.', 'region-manager' ),
		);
	}

	/**
	 * Deactivate the current license.
	 *
	 * @since  1.0.0
	 * @return array Associative array with 'success' (bool) and 'message' (string).
	 */
	public function deactivate_license() {
		$current_key = $this->get_license_key();

		// TODO: Implement actual license deactivation API
		// Example API call:
		// $response = wp_remote_post('https://your-license-server.com/api/deactivate', [
		//     'body' => [
		//         'license_key' => $current_key,
		//         'site_url'    => home_url(),
		//     ],
		//     'timeout' => 15,
		// ]);

		// Remove license data.
		delete_option( self::LICENSE_KEY_OPTION );
		update_option( self::LICENSE_STATUS_OPTION, 'free' );

		// Log deactivation.
		do_action( 'rm_license_deactivated', $current_key );

		return array(
			'success' => true,
			'message' => __( 'License deactivated successfully. You are now on the Free tier (2 regions maximum).', 'region-manager' ),
		);
	}

	/**
	 * Validate a license key format.
	 *
	 * Placeholder method for future API integration.
	 *
	 * @since  1.0.0
	 * @param  string $key The license key to validate.
	 * @return bool        True if valid format, false otherwise.
	 */
	public function validate_license( $key ) {
		// Basic validation - key must be at least 10 characters.
		// In production, this would call an external API.
		if ( strlen( $key ) < 10 ) {
			return false;
		}

		// Check for valid characters (alphanumeric and dashes).
		if ( ! preg_match( '/^[A-Za-z0-9\-]+$/', $key ) ) {
			return false;
		}

		// Placeholder: Always return true for now.
		// In production, this would verify against license server.
		return true;
	}

	/**
	 * Get upgrade notice HTML.
	 *
	 * @since  1.0.0
	 * @return string HTML for upgrade notice.
	 */
	public function get_upgrade_notice() {
		if ( $this->is_pro() ) {
			return '';
		}

		$current = $this->get_current_region_count();
		$max     = $this->get_max_regions();

		$message = sprintf(
			/* translators: 1: current region count, 2: maximum regions allowed */
			__( 'You are using %1$d of %2$d regions available on the Free tier.', 'region-manager' ),
			$current,
			$max
		);

		if ( $current >= $max ) {
			$message = sprintf(
				/* translators: %d: maximum regions allowed */
				__( 'You have reached the maximum of %d regions on the Free tier.', 'region-manager' ),
				$max
			);
		}

		$settings_url = admin_url( 'admin.php?page=rm-settings&tab=license' );

		ob_start();
		?>
		<div class="notice notice-warning is-dismissible rm-license-notice">
			<p>
				<strong><?php esc_html_e( 'Region Manager License:', 'region-manager' ); ?></strong>
				<?php echo esc_html( $message ); ?>
			</p>
			<p>
				<a href="<?php echo esc_url( $settings_url ); ?>" class="button button-primary">
					<?php esc_html_e( 'Upgrade to Pro for Unlimited Regions', 'region-manager' ); ?>
				</a>
			</p>
		</div>
		<?php
		return ob_get_clean();
	}

	/**
	 * Show admin notice when region limit is reached.
	 *
	 * @since 1.0.0
	 */
	public function show_limit_notice() {
		if ( ! current_user_can( 'manage_woocommerce' ) ) {
			return;
		}

		// Only show on region management pages.
		$screen = get_current_screen();
		if ( ! $screen || false === strpos( $screen->id, 'rm-regions' ) ) {
			return;
		}

		// Check if limit is reached or close to limit.
		$current = $this->get_current_region_count();
		$max     = $this->get_max_regions();

		// Don't show for Pro users or if unlimited.
		if ( -1 === $max ) {
			return;
		}

		// Show if at or near limit.
		if ( $current >= $max - 1 ) {
			echo wp_kses_post( $this->get_upgrade_notice() );
		}
	}

	/**
	 * Get license information for display.
	 *
	 * @since  1.0.0
	 * @return array License information.
	 */
	public function get_license_info() {
		return array(
			'status'         => $this->get_license_status(),
			'is_pro'         => $this->is_pro(),
			'max_regions'    => $this->get_max_regions(),
			'current_count'  => $this->get_current_region_count(),
			'can_create'     => $this->can_create_region(),
			'license_key'    => $this->get_license_key(),
		);
	}
}
