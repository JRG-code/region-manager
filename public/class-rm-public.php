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

		// Get parameters from request.
		$country_code  = isset( $_POST['country_code'] ) ? strtoupper( sanitize_text_field( wp_unslash( $_POST['country_code'] ) ) ) : '';
		$url_slug      = isset( $_POST['url_slug'] ) ? sanitize_text_field( wp_unslash( $_POST['url_slug'] ) ) : '';
		$language_code = isset( $_POST['language_code'] ) ? sanitize_text_field( wp_unslash( $_POST['language_code'] ) ) : '';

		// DEBUG.
		error_log( '========== RM DEBUG: ajax_set_region ==========' );
		error_log( 'Country Code: ' . $country_code );
		error_log( 'URL Slug: ' . $url_slug );
		error_log( 'Language Code: ' . $language_code );

		if ( empty( $country_code ) || empty( $url_slug ) ) {
			error_log( 'ERROR: Missing required parameters' );
			wp_send_json_error( array( 'message' => __( 'Invalid selection.', 'region-manager' ) ) );
			return;
		}

		$url_slug = trim( $url_slug, '/' );

		global $wpdb;

		// Get region ID for this country.
		$region_id = $wpdb->get_var(
			$wpdb->prepare(
				"SELECT rc.region_id
				 FROM {$wpdb->prefix}rm_region_countries rc
				 INNER JOIN {$wpdb->prefix}rm_regions r ON rc.region_id = r.id
				 WHERE rc.country_code = %s AND r.status = 'active'
				 LIMIT 1",
				$country_code
			)
		);

		error_log( 'Region ID found: ' . ( $region_id ? $region_id : 'NULL' ) );

		// Store in session if WooCommerce is available.
		if ( function_exists( 'WC' ) && WC()->session ) {
			WC()->session->set( 'rm_current_region_id', $region_id );
			WC()->session->set( 'rm_current_country', $country_code );
			WC()->session->set( 'rm_current_language', $language_code );
			WC()->session->set( 'rm_current_url_slug', $url_slug );
		}

		// Store in cookies (30 days).
		$cookie_expiry = time() + ( 30 * DAY_IN_SECONDS );
		$cookie_path   = COOKIEPATH ? COOKIEPATH : '/';
		$cookie_domain = COOKIE_DOMAIN ? COOKIE_DOMAIN : '';
		$secure        = is_ssl();

		setcookie( 'rm_region_id', $region_id, $cookie_expiry, $cookie_path, $cookie_domain, $secure, true );
		setcookie( 'rm_country', $country_code, $cookie_expiry, $cookie_path, $cookie_domain, $secure, true );
		setcookie( 'rm_language', $language_code, $cookie_expiry, $cookie_path, $cookie_domain, $secure, true );
		setcookie( 'rm_url_slug', $url_slug, $cookie_expiry, $cookie_path, $cookie_domain, $secure, true );

		// Get redirect URL using RegionalRouter.
		$router       = RM_Regional_Router::get_instance();
		$redirect_url = $router->get_redirect_url( $country_code, $url_slug, $language_code );

		error_log( 'Final Redirect URL: ' . $redirect_url );
		error_log( '========== RM DEBUG END ==========' );

		wp_send_json_success(
			array(
				'message'      => __( 'Country selected successfully.', 'region-manager' ),
				'redirect_url' => $redirect_url,
				'country'      => $country_code,
				'region_id'    => $region_id,
				'language'     => $language_code,
				'url_slug'     => $url_slug,
			)
		);
	}
}
