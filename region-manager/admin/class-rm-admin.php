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

		// Handle form submissions.
		if ( isset( $_POST['rm_settings_nonce'] ) && wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['rm_settings_nonce'] ) ), 'rm_save_settings' ) ) {
			$this->save_settings();
		}

		if ( isset( $_POST['rm_license_nonce'] ) && wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['rm_license_nonce'] ) ), 'rm_license_action' ) ) {
			$this->handle_license_action();
		}

		// Get current tab.
		$current_tab = isset( $_GET['tab'] ) ? sanitize_text_field( wp_unslash( $_GET['tab'] ) ) : 'general';
		?>
		<div class="wrap rm-admin-wrap">
			<h1><?php echo esc_html( get_admin_page_title() ); ?></h1>

			<nav class="nav-tab-wrapper">
				<a href="<?php echo esc_url( admin_url( 'admin.php?page=rm-settings&tab=general' ) ); ?>" class="nav-tab <?php echo 'general' === $current_tab ? 'nav-tab-active' : ''; ?>">
					<?php esc_html_e( 'General', 'region-manager' ); ?>
				</a>
				<a href="<?php echo esc_url( admin_url( 'admin.php?page=rm-settings&tab=license' ) ); ?>" class="nav-tab <?php echo 'license' === $current_tab ? 'nav-tab-active' : ''; ?>">
					<?php esc_html_e( 'License', 'region-manager' ); ?>
				</a>
			</nav>

			<div class="rm-settings-content">
				<?php
				if ( 'license' === $current_tab ) {
					$this->display_license_settings();
				} else {
					$this->display_general_settings();
				}
				?>
			</div>
		</div>
		<?php
	}

	/**
	 * Display general settings tab.
	 *
	 * @since 1.0.0
	 */
	private function display_general_settings() {
		// Get current settings.
		$auto_detect     = get_option( 'rm_auto_detect_region', 'yes' );
		$allow_switching = get_option( 'rm_allow_region_switching', 'yes' );
		$cookie_lifetime = get_option( 'rm_region_cookie_lifetime', 30 );
		$hide_unavailable = get_option( 'rm_hide_unavailable_products', 'no' );
		?>
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
		<?php
	}

	/**
	 * Display license settings tab.
	 *
	 * @since 1.0.0
	 */
	private function display_license_settings() {
		$license = RM_License::get_instance();
		$license_info = $license->get_license_info();
		?>
		<div class="rm-license-settings">
			<table class="form-table">
				<tbody>
					<tr>
						<th scope="row">
							<?php esc_html_e( 'License Status', 'region-manager' ); ?>
						</th>
						<td>
							<span class="rm-status-badge <?php echo esc_attr( $license_info['status'] ); ?>">
								<?php echo esc_html( strtoupper( $license_info['status'] ) ); ?>
							</span>
							<?php if ( $license_info['is_pro'] ) : ?>
								<p class="description">
									<?php esc_html_e( 'You have unlimited access to all features.', 'region-manager' ); ?>
								</p>
							<?php else : ?>
								<p class="description">
									<?php
									printf(
										/* translators: %d: maximum regions allowed */
										esc_html__( 'Free tier: Limited to %d regions.', 'region-manager' ),
										absint( $license_info['max_regions'] )
									);
									?>
								</p>
							<?php endif; ?>
						</td>
					</tr>
					<tr>
						<th scope="row">
							<?php esc_html_e( 'Region Usage', 'region-manager' ); ?>
						</th>
						<td>
							<?php if ( -1 === $license_info['max_regions'] ) : ?>
								<strong>
									<?php
									printf(
										/* translators: %d: current region count */
										esc_html__( '%d regions created (Unlimited)', 'region-manager' ),
										absint( $license_info['current_count'] )
									);
									?>
								</strong>
							<?php else : ?>
								<strong>
									<?php
									printf(
										/* translators: 1: current region count, 2: maximum regions allowed */
										esc_html__( '%1$d of %2$d regions used', 'region-manager' ),
										absint( $license_info['current_count'] ),
										absint( $license_info['max_regions'] )
									);
									?>
								</strong>
								<?php if ( ! $license_info['can_create'] ) : ?>
									<p class="description" style="color: #d63638;">
										<?php esc_html_e( 'You have reached your region limit. Upgrade to Pro for unlimited regions.', 'region-manager' ); ?>
									</p>
								<?php endif; ?>
							<?php endif; ?>
						</td>
					</tr>
				</tbody>
			</table>

			<?php if ( $license_info['is_pro'] ) : ?>
				<!-- Pro License - Show deactivation form -->
				<h3><?php esc_html_e( 'License Key', 'region-manager' ); ?></h3>
				<form method="post" action="">
					<?php wp_nonce_field( 'rm_license_action', 'rm_license_nonce' ); ?>
					<table class="form-table">
						<tbody>
							<tr>
								<th scope="row">
									<?php esc_html_e( 'Your License Key', 'region-manager' ); ?>
								</th>
								<td>
									<input type="text" class="regular-text" value="<?php echo esc_attr( str_repeat( '*', strlen( $license_info['license_key'] ) - 4 ) . substr( $license_info['license_key'], -4 ) ); ?>" disabled />
								</td>
							</tr>
						</tbody>
					</table>
					<input type="hidden" name="license_action" value="deactivate" />
					<p>
						<button type="submit" class="button button-secondary">
							<?php esc_html_e( 'Deactivate License', 'region-manager' ); ?>
						</button>
					</p>
				</form>
			<?php else : ?>
				<!-- Free Tier - Show activation form -->
				<h3><?php esc_html_e( 'Activate Pro License', 'region-manager' ); ?></h3>
				<form method="post" action="">
					<?php wp_nonce_field( 'rm_license_action', 'rm_license_nonce' ); ?>
					<table class="form-table">
						<tbody>
							<tr>
								<th scope="row">
									<label for="license_key">
										<?php esc_html_e( 'License Key', 'region-manager' ); ?>
									</label>
								</th>
								<td>
									<input type="text" name="license_key" id="license_key" class="regular-text" placeholder="<?php esc_attr_e( 'Enter your license key', 'region-manager' ); ?>" />
									<p class="description">
										<?php esc_html_e( 'Enter your Pro license key to unlock unlimited regions.', 'region-manager' ); ?>
									</p>
								</td>
							</tr>
						</tbody>
					</table>
					<input type="hidden" name="license_action" value="activate" />
					<p>
						<button type="submit" class="button button-primary">
							<?php esc_html_e( 'Activate License', 'region-manager' ); ?>
						</button>
					</p>
				</form>

				<div class="rm-license-upgrade-box" style="background: #f0f6fc; border: 1px solid #c3d9f1; padding: 20px; margin-top: 20px; border-radius: 4px;">
					<h3><?php esc_html_e( 'Upgrade to Pro', 'region-manager' ); ?></h3>
					<p><?php esc_html_e( 'Get unlimited regions and premium support with Region Manager Pro.', 'region-manager' ); ?></p>
					<ul style="list-style: disc; margin-left: 20px;">
						<li><?php esc_html_e( 'Unlimited regions', 'region-manager' ); ?></li>
						<li><?php esc_html_e( 'Priority support', 'region-manager' ); ?></li>
						<li><?php esc_html_e( 'Advanced features', 'region-manager' ); ?></li>
						<li><?php esc_html_e( 'Regular updates', 'region-manager' ); ?></li>
					</ul>
					<p>
						<a href="#" class="button button-primary button-hero" target="_blank">
							<?php esc_html_e( 'Purchase Pro License', 'region-manager' ); ?>
						</a>
					</p>
				</div>
			<?php endif; ?>
		</div>
		<?php
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
