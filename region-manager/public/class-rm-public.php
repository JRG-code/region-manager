<?php
/**
 * The public-facing functionality of the plugin.
 *
 * @package    Region_Manager
 * @subpackage Region_Manager/public
 */

/**
 * The public-facing functionality of the plugin.
 *
 * Defines the plugin name, version, and hooks for enqueuing
 * public-facing stylesheet and JavaScript.
 */
class RM_Public {

	/**
	 * The ID of this plugin.
	 *
	 * @var string
	 */
	private $plugin_name;

	/**
	 * The version of this plugin.
	 *
	 * @var string
	 */
	private $version;

	/**
	 * Initialize the class and set its properties.
	 *
	 * @param string $plugin_name The name of the plugin.
	 * @param string $version     The version of this plugin.
	 */
	public function __construct( $plugin_name, $version ) {
		$this->plugin_name = $plugin_name;
		$this->version     = $version;
	}

	/**
	 * Register the stylesheets for the public-facing side of the site.
	 */
	public function enqueue_styles() {
		wp_enqueue_style(
			$this->plugin_name,
			plugin_dir_url( __FILE__ ) . 'css/rm-public.css',
			array(),
			$this->version,
			'all'
		);
	}

	/**
	 * Register the JavaScript for the public-facing side of the site.
	 */
	public function enqueue_scripts() {
		wp_enqueue_script(
			$this->plugin_name,
			plugin_dir_url( __FILE__ ) . 'js/rm-public.js',
			array( 'jquery' ),
			$this->version,
			false
		);

		wp_localize_script(
			$this->plugin_name,
			'rmPublic',
			array(
				'ajaxurl'       => admin_url( 'admin-ajax.php' ),
				'nonce'         => wp_create_nonce( 'rm_public_nonce' ),
				'home_url'      => home_url( '/' ),
				'current_url'   => ( is_ssl() ? 'https://' : 'http://' ) . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'],
			)
		);
	}

	/**
	 * AJAX handler to set selected region.
	 */
	public function ajax_set_region() {
		check_ajax_referer( 'rm_public_nonce', 'nonce' );

		$region_slug = isset( $_POST['region_slug'] ) ? sanitize_text_field( wp_unslash( $_POST['region_slug'] ) ) : '';

		if ( empty( $region_slug ) ) {
			wp_send_json_error( array( 'message' => __( 'Invalid region.', 'region-manager' ) ) );
		}

		global $wpdb;
		$table_name = $wpdb->prefix . 'rm_regions';

		$region = $wpdb->get_row(
			$wpdb->prepare(
				"SELECT * FROM {$table_name} WHERE slug = %s AND is_active = 1",
				$region_slug
			),
			ARRAY_A
		);

		if ( ! $region ) {
			wp_send_json_error( array( 'message' => __( 'Region not found.', 'region-manager' ) ) );
		}

		// Set cookie for 30 days.
		setcookie( 'rm_selected_region', $region_slug, time() + ( 30 * DAY_IN_SECONDS ), '/' );

		$redirect_url = home_url( '/' . $region_slug . '/' );

		wp_send_json_success(
			array(
				'message'      => __( 'Region selected successfully.', 'region-manager' ),
				'region'       => $region,
				'redirect_url' => $redirect_url,
			)
		);
	}
}
