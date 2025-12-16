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
			'rm-products',
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

		// Products submenu.
		add_submenu_page(
			'region-manager',
			esc_html__( 'Products', 'region-manager' ),
			esc_html__( 'Products', 'region-manager' ),
			'manage_woocommerce',
			'rm-products',
			array( $this, 'display_products_page' )
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
		$dashboard = new RM_Dashboard();
		$dashboard->display();
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
	 * Display the products page.
	 *
	 * @since 1.0.0
	 */
	public function display_products_page() {
		$products = new RM_Products();
		$products->display();
	}

	/**
	 * Display the settings page.
	 *
	 * @since 1.0.0
	 */
	public function display_settings_page() {
		$settings = new RM_Settings();
		$settings->display();
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
