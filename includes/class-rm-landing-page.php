<?php
/**
 * Landing Page functionality for region selection.
 *
 * @package    Region_Manager
 * @subpackage Region_Manager/includes
 */

/**
 * Landing Page class.
 *
 * Handles the region selector landing page with multiple templates.
 */
class RM_Landing_Page {

	/**
	 * Singleton instance.
	 *
	 * @var RM_Landing_Page
	 */
	private static $instance = null;

	/**
	 * Get singleton instance.
	 *
	 * @return RM_Landing_Page
	 */
	public static function get_instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * Initialize the class.
	 */
	private function __construct() {
		add_shortcode( 'region_landing_page', array( $this, 'render_landing_page' ) );
	}

	/**
	 * Render the landing page shortcode.
	 *
	 * @param array $atts Shortcode attributes.
	 * @return string Landing page HTML.
	 */
	public function render_landing_page( $atts ) {
		$atts = shortcode_atts(
			array(
				'template' => '',
			),
			$atts,
			'region_landing_page'
		);

		$settings = $this->get_settings();

		if ( ! $settings['enabled'] ) {
			return '';
		}

		$template = ! empty( $atts['template'] ) ? $atts['template'] : $settings['template'];
		$regions  = $this->get_active_regions();

		ob_start();

		$template_file = $this->get_template_file( $template );

		if ( file_exists( $template_file ) ) {
			include $template_file;
		} else {
			include $this->get_template_file( 'default' );
		}

		return ob_get_clean();
	}

	/**
	 * Get landing page settings.
	 *
	 * @return array Settings.
	 */
	private function get_settings() {
		return array(
			'enabled'          => (bool) get_option( 'rm_landing_page_enabled', false ),
			'template'         => get_option( 'rm_landing_page_template', 'default' ),
			'title'            => get_option( 'rm_landing_page_title', __( 'Select Your Region', 'region-manager' ) ),
			'description'      => get_option( 'rm_landing_page_description', __( 'Please choose your region to see relevant products and pricing.', 'region-manager' ) ),
			'auto_redirect'    => (bool) get_option( 'rm_landing_page_auto_redirect', false ),
			'redirect_delay'   => absint( get_option( 'rm_landing_page_redirect_delay', 3 ) ),
			'show_flags'       => (bool) get_option( 'rm_landing_page_show_flags', true ),
			'show_description' => (bool) get_option( 'rm_landing_page_show_description', true ),
		);
	}

	/**
	 * Get active regions.
	 *
	 * @return array Active regions.
	 */
	private function get_active_regions() {
		global $wpdb;
		$table_name = $wpdb->prefix . 'rm_regions';

		$results = $wpdb->get_results(
			"SELECT * FROM {$table_name} WHERE is_active = 1 ORDER BY name ASC",
			ARRAY_A
		);

		return $results;
	}

	/**
	 * Get template file path.
	 *
	 * @param string $template Template name.
	 * @return string Template file path.
	 */
	private function get_template_file( $template ) {
		$template_name = 'landing-page-' . $template . '.php';
		$plugin_dir    = plugin_dir_path( dirname( __FILE__ ) );

		// Check theme override first.
		$theme_template = get_stylesheet_directory() . '/region-manager/' . $template_name;
		if ( file_exists( $theme_template ) ) {
			return $theme_template;
		}

		// Use plugin template.
		return $plugin_dir . 'templates/' . $template_name;
	}

	/**
	 * Get region country codes.
	 *
	 * @param int $region_id Region ID.
	 * @return array Country codes.
	 */
	public function get_region_countries( $region_id ) {
		global $wpdb;
		$table_name = $wpdb->prefix . 'rm_countries';

		$results = $wpdb->get_col(
			$wpdb->prepare(
				"SELECT code FROM {$table_name} WHERE region_id = %d",
				$region_id
			)
		);

		return $results;
	}

	/**
	 * Get flag emoji for country code.
	 *
	 * @param string $country_code Two-letter country code.
	 * @return string Flag emoji.
	 */
	public function get_flag_emoji( $country_code ) {
		$country_code = strtoupper( $country_code );
		$flag         = '';

		// Convert country code to regional indicator symbols.
		for ( $i = 0; $i < strlen( $country_code ); $i++ ) {
			$flag .= mb_chr( 127397 + ord( $country_code[ $i ] ) );
		}

		return $flag;
	}

	/**
	 * Detect user's region based on IP geolocation.
	 *
	 * @return int|null Region ID or null.
	 */
	public function detect_region_by_ip() {
		// Placeholder for geolocation detection.
		// This would integrate with a geolocation service or plugin.
		return apply_filters( 'rm_detected_region_by_ip', null );
	}
}
