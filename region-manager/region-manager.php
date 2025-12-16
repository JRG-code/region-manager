<?php
/**
 * Region Manager
 *
 * @package           RegionManager
 * @author            Region Manager Team
 * @copyright         2025 Region Manager
 * @license           GPL-2.0-or-later
 *
 * @wordpress-plugin
 * Plugin Name:       Region Manager
 * Plugin URI:        https://example.com/region-manager
 * Description:       Manage WooCommerce products and pricing across multiple regions with country-specific settings.
 * Version:           1.0.0
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
define( 'RM_VERSION', '1.0.0' );

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
require RM_PLUGIN_DIR . 'admin/class-rm-admin.php';
require RM_PLUGIN_DIR . 'admin/class-rm-settings.php';
require RM_PLUGIN_DIR . 'admin/class-rm-dashboard.php';

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
		$plugin_admin = new RM_Admin();
		$plugin_license = RM_License::get_instance();

		$this->loader->add_action( 'admin_menu', $plugin_admin, 'add_admin_menu' );
		$this->loader->add_action( 'admin_enqueue_scripts', $plugin_admin, 'enqueue_styles' );
		$this->loader->add_action( 'admin_enqueue_scripts', $plugin_admin, 'enqueue_scripts' );
		$this->loader->add_action( 'admin_notices', $plugin_license, 'show_limit_notice' );
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
 * @since 1.0.0
 */
function run_region_manager() {
	return Region_Manager::instance();
}

// Start the plugin.
run_region_manager();
