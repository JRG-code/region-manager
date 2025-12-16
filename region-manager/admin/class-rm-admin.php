<?php
/**
 * The admin-specific functionality of the plugin.
 *
 * Defines the plugin name, version, and hooks for managing the admin area.
 *
 * @package    RegionManager
 * @subpackage RegionManager/admin
 * @since      1.0.0
 */

/**
 * The admin-specific functionality of the plugin.
 *
 * @since 1.0.0
 */
class RM_Admin {

	/**
	 * The plugin's pages for enqueuing assets.
	 *
	 * @since  1.0.0
	 * @access private
	 * @var    array    $plugin_pages    Array of plugin page slugs.
	 */
	private $plugin_pages;

	/**
	 * Initialize the class and set its properties.
	 *
	 * @since 1.0.0
	 */
	public function __construct() {
		$this->plugin_pages = array(
			'region-manager',
			'rm-regions',
			'rm-countries',
			'rm-settings',
		);
	}

	/**
	 * Register the admin menu and submenus.
	 *
	 * @since 1.0.0
	 */
	public function add_admin_menu() {
		// Main menu.
		add_menu_page(
			esc_html__( 'Region Manager', 'region-manager' ),
			esc_html__( 'Regions', 'region-manager' ),
			'manage_woocommerce',
			'region-manager',
			array( $this, 'display_dashboard_page' ),
			'dashicons-admin-site-alt3',
			56
		);

		// Dashboard submenu (same as main menu).
		add_submenu_page(
			'region-manager',
			esc_html__( 'Dashboard', 'region-manager' ),
			esc_html__( 'Dashboard', 'region-manager' ),
			'manage_woocommerce',
			'region-manager',
			array( $this, 'display_dashboard_page' )
		);

		// Regions submenu.
		add_submenu_page(
			'region-manager',
			esc_html__( 'Manage Regions', 'region-manager' ),
			esc_html__( 'Manage Regions', 'region-manager' ),
			'manage_woocommerce',
			'rm-regions',
			array( $this, 'display_regions_page' )
		);

		// Countries submenu.
		add_submenu_page(
			'region-manager',
			esc_html__( 'Manage Countries', 'region-manager' ),
			esc_html__( 'Countries', 'region-manager' ),
			'manage_woocommerce',
			'rm-countries',
			array( $this, 'display_countries_page' )
		);

		// Settings submenu.
		add_submenu_page(
			'region-manager',
			esc_html__( 'Region Settings', 'region-manager' ),
			esc_html__( 'Settings', 'region-manager' ),
			'manage_options',
			'rm-settings',
			array( $this, 'display_settings_page' )
		);
	}

	/**
	 * Display the dashboard page.
	 *
	 * @since 1.0.0
	 */
	public function display_dashboard_page() {
		if ( ! current_user_can( 'manage_woocommerce' ) ) {
			wp_die( esc_html__( 'You do not have sufficient permissions to access this page.', 'region-manager' ) );
		}
		?>
		<div class="wrap rm-admin-wrap">
			<h1><?php echo esc_html( get_admin_page_title() ); ?></h1>
			<div class="rm-dashboard">
				<p><?php esc_html_e( 'Welcome to Region Manager! Manage your WooCommerce products across multiple regions.', 'region-manager' ); ?></p>
			</div>
		</div>
		<?php
	}

	/**
	 * Display the regions page.
	 *
	 * @since 1.0.0
	 */
	public function display_regions_page() {
		if ( ! current_user_can( 'manage_woocommerce' ) ) {
			wp_die( esc_html__( 'You do not have sufficient permissions to access this page.', 'region-manager' ) );
		}
		?>
		<div class="wrap rm-admin-wrap">
			<h1><?php echo esc_html( get_admin_page_title() ); ?></h1>
			<div class="rm-regions">
				<p><?php esc_html_e( 'Manage your regions here.', 'region-manager' ); ?></p>
			</div>
		</div>
		<?php
	}

	/**
	 * Display the countries page.
	 *
	 * @since 1.0.0
	 */
	public function display_countries_page() {
		if ( ! current_user_can( 'manage_woocommerce' ) ) {
			wp_die( esc_html__( 'You do not have sufficient permissions to access this page.', 'region-manager' ) );
		}
		?>
		<div class="wrap rm-admin-wrap">
			<h1><?php echo esc_html( get_admin_page_title() ); ?></h1>
			<div class="rm-countries">
				<p><?php esc_html_e( 'Manage countries within regions.', 'region-manager' ); ?></p>
			</div>
		</div>
		<?php
	}

	/**
	 * Display the settings page.
	 *
	 * @since 1.0.0
	 */
	public function display_settings_page() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'You do not have sufficient permissions to access this page.', 'region-manager' ) );
		}

		// Handle form submission.
		if ( isset( $_POST['rm_settings_nonce'] ) && wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['rm_settings_nonce'] ) ), 'rm_save_settings' ) ) {
			$this->save_settings();
		}

		// Get current settings.
		$auto_detect        = get_option( 'rm_auto_detect_region', 'yes' );
		$allow_switching    = get_option( 'rm_allow_region_switching', 'yes' );
		$cookie_lifetime    = get_option( 'rm_region_cookie_lifetime', 30 );
		$fallback_behavior  = get_option( 'rm_fallback_behavior', 'default' );
		$hide_unavailable   = get_option( 'rm_hide_unavailable_products', 'no' );
		?>
		<div class="wrap rm-admin-wrap">
			<h1><?php echo esc_html( get_admin_page_title() ); ?></h1>
			<form method="post" action="">
				<?php wp_nonce_field( 'rm_save_settings', 'rm_settings_nonce' ); ?>
				<table class="form-table">
					<tbody>
						<tr>
							<th scope="row">
								<label for="rm_auto_detect_region">
									<?php esc_html_e( 'Auto-detect Region', 'region-manager' ); ?>
								</label>
							</th>
							<td>
								<input type="checkbox" name="rm_auto_detect_region" id="rm_auto_detect_region" value="yes" <?php checked( $auto_detect, 'yes' ); ?> />
								<p class="description">
									<?php esc_html_e( 'Automatically detect user region based on their location.', 'region-manager' ); ?>
								</p>
							</td>
						</tr>
						<tr>
							<th scope="row">
								<label for="rm_allow_region_switching">
									<?php esc_html_e( 'Allow Region Switching', 'region-manager' ); ?>
								</label>
							</th>
							<td>
								<input type="checkbox" name="rm_allow_region_switching" id="rm_allow_region_switching" value="yes" <?php checked( $allow_switching, 'yes' ); ?> />
								<p class="description">
									<?php esc_html_e( 'Allow users to manually switch between regions.', 'region-manager' ); ?>
								</p>
							</td>
						</tr>
						<tr>
							<th scope="row">
								<label for="rm_region_cookie_lifetime">
									<?php esc_html_e( 'Cookie Lifetime (days)', 'region-manager' ); ?>
								</label>
							</th>
							<td>
								<input type="number" name="rm_region_cookie_lifetime" id="rm_region_cookie_lifetime" value="<?php echo esc_attr( $cookie_lifetime ); ?>" min="1" max="365" />
								<p class="description">
									<?php esc_html_e( 'How long to remember user region selection.', 'region-manager' ); ?>
								</p>
							</td>
						</tr>
						<tr>
							<th scope="row">
								<label for="rm_hide_unavailable_products">
									<?php esc_html_e( 'Hide Unavailable Products', 'region-manager' ); ?>
								</label>
							</th>
							<td>
								<input type="checkbox" name="rm_hide_unavailable_products" id="rm_hide_unavailable_products" value="yes" <?php checked( $hide_unavailable, 'yes' ); ?> />
								<p class="description">
									<?php esc_html_e( 'Hide products that are not available in the current region.', 'region-manager' ); ?>
								</p>
							</td>
						</tr>
					</tbody>
				</table>
				<?php submit_button( esc_html__( 'Save Settings', 'region-manager' ) ); ?>
			</form>
		</div>
		<?php
	}

	/**
	 * Save plugin settings.
	 *
	 * @since  1.0.0
	 * @access private
	 */
	private function save_settings() {
		// Verify capabilities.
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		// Update settings.
		update_option( 'rm_auto_detect_region', isset( $_POST['rm_auto_detect_region'] ) ? 'yes' : 'no' );
		update_option( 'rm_allow_region_switching', isset( $_POST['rm_allow_region_switching'] ) ? 'yes' : 'no' );

		if ( isset( $_POST['rm_region_cookie_lifetime'] ) ) {
			update_option( 'rm_region_cookie_lifetime', absint( $_POST['rm_region_cookie_lifetime'] ) );
		}

		update_option( 'rm_hide_unavailable_products', isset( $_POST['rm_hide_unavailable_products'] ) ? 'yes' : 'no' );

		// Show success message.
		add_action( 'admin_notices', array( $this, 'settings_saved_notice' ) );
	}

	/**
	 * Display settings saved notice.
	 *
	 * @since 1.0.0
	 */
	public function settings_saved_notice() {
		?>
		<div class="notice notice-success is-dismissible">
			<p><?php esc_html_e( 'Settings saved successfully.', 'region-manager' ); ?></p>
		</div>
		<?php
	}

	/**
	 * Register and enqueue admin-specific stylesheets.
	 *
	 * @since 1.0.0
	 * @param string $hook The current admin page.
	 */
	public function enqueue_styles( $hook ) {
		if ( ! $this->is_plugin_page( $hook ) ) {
			return;
		}

		wp_enqueue_style(
			'rm-admin',
			RM_PLUGIN_URL . 'admin/css/rm-admin.css',
			array(),
			RM_VERSION,
			'all'
		);
	}

	/**
	 * Register and enqueue admin-specific JavaScript.
	 *
	 * @since 1.0.0
	 * @param string $hook The current admin page.
	 */
	public function enqueue_scripts( $hook ) {
		if ( ! $this->is_plugin_page( $hook ) ) {
			return;
		}

		wp_enqueue_script(
			'rm-admin',
			RM_PLUGIN_URL . 'admin/js/rm-admin.js',
			array( 'jquery' ),
			RM_VERSION,
			true
		);

		// Localize script with data.
		wp_localize_script(
			'rm-admin',
			'rmAdmin',
			array(
				'ajaxUrl'   => admin_url( 'admin-ajax.php' ),
				'nonce'     => wp_create_nonce( 'rm_admin_nonce' ),
				'i18n'      => array(
					'confirmDelete' => esc_html__( 'Are you sure you want to delete this item?', 'region-manager' ),
					'error'         => esc_html__( 'An error occurred. Please try again.', 'region-manager' ),
					'success'       => esc_html__( 'Operation completed successfully.', 'region-manager' ),
				),
			)
		);
	}

	/**
	 * Check if the current page is a plugin page.
	 *
	 * @since  1.0.0
	 * @access private
	 * @param  string $hook The current admin page hook.
	 * @return bool         True if plugin page, false otherwise.
	 */
	private function is_plugin_page( $hook ) {
		// Check if we're on a toplevel_page or submenu.
		foreach ( $this->plugin_pages as $page ) {
			if ( false !== strpos( $hook, $page ) ) {
				return true;
			}
		}

		return false;
	}
}
