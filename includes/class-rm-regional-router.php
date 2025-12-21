<?php
/**
 * Regional Router functionality.
 *
 * Handles regional page overrides and redirects.
 *
 * @package    Region_Manager
 * @subpackage Region_Manager/includes
 */

/**
 * Regional Router class.
 *
 * Manages routing and page overrides based on selected region.
 */
class RM_Regional_Router {

	/**
	 * Singleton instance.
	 *
	 * @var RM_Regional_Router
	 */
	private static $instance = null;

	/**
	 * Get singleton instance.
	 *
	 * @return RM_Regional_Router
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
		// Override WooCommerce shop page based on region.
		add_filter( 'woocommerce_get_shop_page_id', array( $this, 'get_regional_shop_page' ) );

		// Register content shortcode.
		add_shortcode( 'rm_regional_content', array( $this, 'regional_content_shortcode' ) );
	}

	/**
	 * Override WooCommerce shop page with regional version.
	 *
	 * @param int $page_id Default shop page ID.
	 * @return int Regional or default shop page ID.
	 */
	public function get_regional_shop_page( $page_id ) {
		$region_id = $this->get_current_region_id();

		if ( ! $region_id ) {
			return $page_id;
		}

		$regional_shop = $this->get_regional_page( $region_id, 'shop' );

		if ( $regional_shop ) {
			return $regional_shop;
		}

		return $page_id;
	}

	/**
	 * Handle redirect after country selection.
	 *
	 * @param string $country_code Country code.
	 * @param string $url_slug URL slug.
	 * @param string $language_code Language code.
	 * @return string Redirect URL.
	 */
	public function get_redirect_url( $country_code, $url_slug, $language_code ) {
		$region_id = $this->get_region_id_by_country( $country_code );

		if ( ! $region_id ) {
			// No region found, go to homepage with URL slug.
			return home_url( '/' . trim( $url_slug, '/' ) . '/' );
		}

		$first_page_setting = $this->get_regional_content( $region_id, 'first_page_after_selection' );

		// Default to shop if not set.
		if ( empty( $first_page_setting ) ) {
			$first_page_setting = 'shop';
		}

		$base_url = '';

		switch ( $first_page_setting ) {
			case 'shop':
				// Use regional shop page if set, otherwise WooCommerce default.
				$regional_shop = $this->get_regional_page( $region_id, 'shop' );
				if ( $regional_shop ) {
					$base_url = get_permalink( $regional_shop );
				} elseif ( function_exists( 'wc_get_page_permalink' ) ) {
					$base_url = wc_get_page_permalink( 'shop' );
				}
				break;

			case 'home':
				// Site homepage.
				$base_url = home_url( '/' );
				break;

			default:
				// Check if it's a specific page (format: page_123).
				if ( strpos( $first_page_setting, 'page_' ) === 0 ) {
					$page_id = intval( str_replace( 'page_', '', $first_page_setting ) );
					if ( $page_id > 0 ) {
						$base_url = get_permalink( $page_id );
					}
				}

				// Fallback to homepage.
				if ( empty( $base_url ) ) {
					$base_url = home_url( '/' );
				}
				break;
		}

		// Add URL slug to the URL.
		return $this->add_url_slug( $base_url, $url_slug );
	}

	/**
	 * Shortcode to display regional content.
	 * Usage: [rm_regional_content key="shop_banner"]
	 * Usage: [rm_regional_content key="shop_banner" default="Fallback text"]
	 *
	 * @param array $atts Shortcode attributes.
	 * @return string Content HTML.
	 */
	public function regional_content_shortcode( $atts ) {
		$atts = shortcode_atts(
			array(
				'key'     => '',
				'default' => '',
			),
			$atts,
			'rm_regional_content'
		);

		if ( empty( $atts['key'] ) ) {
			return $atts['default'];
		}

		$region_id = $this->get_current_region_id();

		if ( ! $region_id ) {
			return $atts['default'];
		}

		$content = $this->get_regional_content( $region_id, $atts['key'] );

		if ( empty( $content ) ) {
			return $atts['default'];
		}

		// Apply content filters (shortcodes, embeds, etc.).
		return do_shortcode( wpautop( $content ) );
	}

	/**
	 * Get current region ID from session/cookie.
	 *
	 * @return int|null Region ID or null.
	 */
	public function get_current_region_id() {
		// Check session first.
		if ( function_exists( 'WC' ) && WC()->session ) {
			$region_id = WC()->session->get( 'rm_current_region_id' );
			if ( $region_id ) {
				return intval( $region_id );
			}
		}

		// Check cookie.
		if ( isset( $_COOKIE['rm_region_id'] ) ) {
			return intval( $_COOKIE['rm_region_id'] );
		}

		return null;
	}

	/**
	 * Get regional page ID by type.
	 *
	 * @param int    $region_id Region ID.
	 * @param string $page_type Page type.
	 * @return int|null Page ID or null.
	 */
	private function get_regional_page( $region_id, $page_type ) {
		global $wpdb;

		$page_id = $wpdb->get_var(
			$wpdb->prepare(
				"SELECT page_id FROM {$wpdb->prefix}rm_regional_pages
				 WHERE region_id = %d AND page_type = %s AND is_active = 1",
				$region_id,
				$page_type
			)
		);

		return $page_id ? intval( $page_id ) : null;
	}

	/**
	 * Get regional content by key.
	 *
	 * @param int    $region_id Region ID.
	 * @param string $content_key Content key.
	 * @return string|null Content value or null.
	 */
	private function get_regional_content( $region_id, $content_key ) {
		global $wpdb;

		return $wpdb->get_var(
			$wpdb->prepare(
				"SELECT content_value FROM {$wpdb->prefix}rm_regional_content
				 WHERE region_id = %d AND content_key = %s",
				$region_id,
				$content_key
			)
		);
	}

	/**
	 * Get region ID from country code.
	 *
	 * @param string $country_code Two-letter country code.
	 * @return int|null Region ID or null.
	 */
	private function get_region_id_by_country( $country_code ) {
		global $wpdb;

		return $wpdb->get_var(
			$wpdb->prepare(
				"SELECT region_id FROM {$wpdb->prefix}rm_region_countries
				 WHERE country_code = %s LIMIT 1",
				$country_code
			)
		);
	}

	/**
	 * Add URL slug prefix to URL if not already present.
	 *
	 * @param string $url URL.
	 * @param string $slug URL slug.
	 * @return string Modified URL.
	 */
	private function add_url_slug( $url, $slug ) {
		// Remove leading/trailing slashes from slug.
		$slug = trim( $slug, '/' );

		if ( empty( $slug ) ) {
			return $url;
		}

		$parsed = wp_parse_url( $url );
		$path   = $parsed['path'] ?? '/';

		// Check if slug already in path.
		if ( strpos( $path, '/' . $slug . '/' ) === 0 || $path === '/' . $slug ) {
			return $url;
		}

		// Add slug to path.
		$new_path = '/' . $slug . $path;

		return str_replace( $parsed['path'], $new_path, $url );
	}
}
