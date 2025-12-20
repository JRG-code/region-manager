<?php
/**
 * Region Manager
 *
 * @package           RegionManager
 * @author            JRG
 * @copyright         2025 Region Manager
 * @license           GPL-2.0-or-later
 *
 * @wordpress-plugin
 * Plugin Name:       Region Manager
 * Plugin URI:        https://datalab.com/region-manager
 * Description:       Manage WooCommerce products and pricing across multiple regions with country-specific settings.
 * Version:           1.1.0
 * Requires at least: 5.8
 * Requires PHP:      7.4
 * Author:            Region Manager Team
 * Author URI:        https://example.com
 * Text Domain:       region-manager
 * Domain Path:       /languages
 * License:           GPL v2 or later
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 * WC requires at least: 5.0
 * WC tested up to:   8.0
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

/**
 * Current plugin version.
 */
define( 'RM_VERSION', '1.1.0' );

/**
 * Plugin directory path.
 */
define( 'RM_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );

/**
 * Plugin directory URL.
 */
define( 'RM_PLUGIN_URL', plugin_dir_url( __FILE__ ) );

/**
 * Plugin basename.
 */
define( 'RM_PLUGIN_BASENAME', plugin_basename( __FILE__ ) );

/**
 * Initialize Plugin Update Checker for GitHub updates.
 */
require RM_PLUGIN_DIR . 'plugin-update-checker/plugin-update-checker.php';
use YahnisElsts\PluginUpdateChecker\v5\PucFactory;

$rm_update_checker = PucFactory::buildUpdateChecker(
	'https://github.com/JRG-code/region-manager/',
	__FILE__,
	'region-manager'
);

// Set the branch that contains the stable release.
$rm_update_checker->setBranch( 'main' );

// Optional: Enable update notifications from GitHub releases.
// Uncomment the line below if you want to use GitHub Releases for updates.
// $rm_update_checker->getVcsApi()->enableReleaseAssets();

/**
 * The code that runs during plugin activation.
 */
function activate_region_manager() {
	require_once RM_PLUGIN_DIR . 'includes/class-rm-activator.php';
	RM_Activator::activate();
}

/**
 * The code that runs during plugin deactivation.
 */
function deactivate_region_manager() {
	require_once RM_PLUGIN_DIR . 'includes/class-rm-deactivator.php';
	RM_Deactivator::deactivate();
}

register_activation_hook( __FILE__, 'activate_region_manager' );
register_deactivation_hook( __FILE__, 'deactivate_region_manager' );

/**
 * The core plugin class.
 */
require RM_PLUGIN_DIR . 'includes/class-rm-loader.php';
require RM_PLUGIN_DIR . 'includes/class-rm-i18n.php';
require RM_PLUGIN_DIR . 'includes/class-rm-license.php';
require RM_PLUGIN_DIR . 'includes/class-rm-order-status.php';
require RM_PLUGIN_DIR . 'includes/class-rm-woocommerce.php';
require RM_PLUGIN_DIR . 'includes/class-rm-rewrite.php';
require RM_PLUGIN_DIR . 'includes/class-rm-landing-page.php';
require RM_PLUGIN_DIR . 'includes/class-rm-menu-flag.php';
require RM_PLUGIN_DIR . 'admin/class-rm-admin.php';
require RM_PLUGIN_DIR . 'admin/class-rm-settings.php';
require RM_PLUGIN_DIR . 'admin/class-rm-dashboard.php';
require RM_PLUGIN_DIR . 'admin/class-rm-products.php';
require RM_PLUGIN_DIR . 'admin/class-rm-product-meta-box.php';
require RM_PLUGIN_DIR . 'admin/class-rm-orders.php';
require RM_PLUGIN_DIR . 'admin/class-rm-customization.php';
require RM_PLUGIN_DIR . 'public/class-rm-public.php';

/**
 * Load widget class after WordPress widgets are available.
 */
add_action(
	'widgets_init',
	function () {
		require_once RM_PLUGIN_DIR . 'includes/class-rm-widget-region-switcher.php';
		register_widget( 'RM_Widget_Region_Switcher' );
	}
);

/**
 * Main Region Manager Class.
 *
 * @since 1.0.0
 */
final class Region_Manager {

	/**
	 * The single instance of the class.
	 *
	 * @var Region_Manager
	 * @since 1.0.0
	 */
	protected static $instance = null;

	/**
	 * The loader that's responsible for maintaining and registering all hooks.
	 *
	 * @since  1.0.0
	 * @access protected
	 * @var    RM_Loader    $loader    Maintains and registers all hooks for the plugin.
	 */
	protected $loader;

	/**
	 * Main Region_Manager Instance.
	 *
	 * Ensures only one instance of Region_Manager is loaded or can be loaded.
	 *
	 * @since  1.0.0
	 * @static
	 * @return Region_Manager - Main instance.
	 */
	public static function instance() {
		if ( is_null( self::$instance ) ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * Region_Manager Constructor.
	 *
	 * @since 1.0.0
	 */
	private function __construct() {
		$this->loader = new RM_Loader();

		// Check if WooCommerce is active.
		add_action( 'admin_init', array( $this, 'check_woocommerce_active' ) );

		if ( $this->is_woocommerce_active() ) {
			$this->load_dependencies();
			$this->set_locale();
			$this->define_admin_hooks();
			$this->define_public_hooks();
			$this->define_woocommerce_hooks();
			$this->loader->run();
		}
	}

	/**
	 * Check if WooCommerce is active.
	 *
	 * @since  1.0.0
	 * @return bool True if WooCommerce is active.
	 */
	private function is_woocommerce_active() {
		return class_exists( 'WooCommerce' );
	}

	/**
	 * Check WooCommerce dependency and show admin notice if not active.
	 *
	 * @since 1.0.0
	 */
	public function check_woocommerce_active() {
		if ( ! $this->is_woocommerce_active() ) {
			add_action( 'admin_notices', array( $this, 'woocommerce_missing_notice' ) );
		}
	}

	/**
	 * Display admin notice when WooCommerce is not active.
	 *
	 * @since 1.0.0
	 */
	public function woocommerce_missing_notice() {
		?>
		<div class="error notice">
			<p>
				<?php
				printf(
					/* translators: %s: WooCommerce plugin link */
					esc_html__( 'Region Manager requires WooCommerce to be installed and active. You can download %s here.', 'region-manager' ),
					'<a href="https://wordpress.org/plugins/woocommerce/" target="_blank">WooCommerce</a>'
				);
				?>
			</p>
		</div>
		<?php
	}

	/**
	 * Load the required dependencies for this plugin.
	 *
	 * @since  1.0.0
	 * @access private
	 */
	private function load_dependencies() {
		// Already loaded in the main file.
	}

	/**
	 * Define the locale for this plugin for internationalization.
	 *
	 * @since  1.0.0
	 * @access private
	 */
	private function set_locale() {
		$plugin_i18n = new RM_i18n();
		$this->loader->add_action( 'plugins_loaded', $plugin_i18n, 'load_plugin_textdomain' );
	}

	/**
	 * Register all hooks related to the admin area functionality.
	 *
	 * @since  1.0.0
	 * @access private
	 */
	private function define_admin_hooks() {
		$plugin_admin         = new RM_Admin();
		$plugin_license       = RM_License::get_instance();
		$plugin_settings      = new RM_Settings();
		$plugin_products      = new RM_Products();
		$product_meta_box     = new RM_Product_Meta_Box();
		$plugin_orders        = new RM_Orders();
		$plugin_customization = new RM_Customization( 'region-manager', RM_VERSION );
		$order_status         = new RM_Order_Status();

		$this->loader->add_action( 'admin_menu', $plugin_admin, 'add_admin_menu' );
		$this->loader->add_action( 'admin_enqueue_scripts', $plugin_admin, 'enqueue_styles' );
		$this->loader->add_action( 'admin_enqueue_scripts', $plugin_admin, 'enqueue_scripts' );
		$this->loader->add_action( 'admin_notices', $plugin_license, 'show_limit_notice' );

		// Register AJAX handlers for products and orders.
		$plugin_products->register_ajax_handlers();
		$plugin_orders->register_ajax_handlers();

		// Customization AJAX handlers.
		$this->loader->add_action( 'wp_ajax_rm_save_landing_page_settings', $plugin_customization, 'save_landing_page_settings' );
		$this->loader->add_action( 'wp_ajax_rm_create_landing_page', $plugin_customization, 'create_landing_page' );
		$this->loader->add_action( 'wp_ajax_rm_save_menu_flag_settings', $plugin_customization, 'save_menu_flag_settings' );
		$this->loader->add_action( 'wp_ajax_rm_save_translator_settings', $plugin_customization, 'save_translator_settings' );
	}

	/**
	 * Register all public-facing hooks.
	 *
	 * @since  1.0.0
	 * @access private
	 */
	private function define_public_hooks() {
		$plugin_public = new RM_Public( 'region-manager', RM_VERSION );

		$this->loader->add_action( 'wp_enqueue_scripts', $plugin_public, 'enqueue_styles' );
		$this->loader->add_action( 'wp_enqueue_scripts', $plugin_public, 'enqueue_scripts' );

		// Public AJAX handlers.
		$this->loader->add_action( 'wp_ajax_rm_set_region', $plugin_public, 'ajax_set_region' );
		$this->loader->add_action( 'wp_ajax_nopriv_rm_set_region', $plugin_public, 'ajax_set_region' );

		// Initialize Landing Page and Menu Flag.
		RM_Landing_Page::get_instance();
		RM_Menu_Flag::get_instance();
	}

	/**
	 * Register all WooCommerce integration hooks.
	 *
	 * @since  1.0.0
	 * @access private
	 */
	private function define_woocommerce_hooks() {
		// Initialize WooCommerce integration.
		$woocommerce = new RM_WooCommerce();

		// Initialize URL rewrite handler.
		$rewrite = new RM_Rewrite();
	}

	/**
	 * Get the loader.
	 *
	 * @since  1.0.0
	 * @return RM_Loader The loader instance.
	 */
	public function get_loader() {
		return $this->loader;
	}
}

/**
 * Begins execution of the plugin.
 *
 * Initializes the plugin after all plugins are loaded to ensure
 * dependencies like WooCommerce are available.
 *
 * @since 1.0.0
 */
function run_region_manager() {
	return Region_Manager::instance();
}

// Start the plugin after all plugins are loaded.
add_action( 'plugins_loaded', 'run_region_manager', 10 );
