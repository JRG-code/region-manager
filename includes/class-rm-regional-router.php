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
		// Clean the URL slug.
		$url_slug = trim( $url_slug, '/' );

		// DEBUG.
		error_log( '========== RM Router: get_redirect_url ==========' );
		error_log( 'Country Code: ' . $country_code );
		error_log( 'URL Slug: ' . $url_slug );
		error_log( 'Language Code: ' . $language_code );

		// Get region for this country.
		$region_id = $this->get_region_id_by_country( $country_code );

		error_log( 'Region ID from country: ' . ( $region_id ? $region_id : 'NULL' ) );

		if ( ! $region_id ) {
			// No region found, just go to homepage with URL slug.
			$fallback_url = home_url( '/' . $url_slug . '/' );
			error_log( 'No region found, fallback URL: ' . $fallback_url );
			error_log( '========== RM Router END ==========' );
			return $fallback_url;
		}

		// Get the "welcome" page setting from rm_regional_pages table.
		$welcome_page_setting = $this->get_regional_page_setting( $region_id, 'welcome' );

		error_log( 'Welcome page setting: ' . var_export( $welcome_page_setting, true ) );

		// Determine the page path based on setting.
		$page_path = '';

		if ( empty( $welcome_page_setting ) || 'shop' === $welcome_page_setting ) {
			// Shop page.
			$shop_page_id = function_exists( 'wc_get_page_id' ) ? wc_get_page_id( 'shop' ) : 0;
			if ( $shop_page_id > 0 ) {
				$shop_page = get_post( $shop_page_id );
				if ( $shop_page ) {
					$page_path = $shop_page->post_name;
				}
			}
			if ( empty( $page_path ) ) {
				$page_path = 'shop';
			}
			error_log( 'Using shop page, path: ' . $page_path );
		} elseif ( 'home' === $welcome_page_setting ) {
			// Site homepage - no additional path.
			$page_path = '';
			error_log( 'Using home page, no additional path' );
		} elseif ( is_numeric( $welcome_page_setting ) ) {
			// Specific page ID.
			$page_id = intval( $welcome_page_setting );

			error_log( 'Using specific page ID: ' . $page_id );

			if ( $page_id > 0 ) {
				$page = get_post( $page_id );

				if ( $page && 'publish' === $page->post_status ) {
					// Get the full page path (handles hierarchical pages).
					$page_path = $this->get_page_path( $page );
					error_log( 'Page found: ' . $page->post_title . ', path: ' . $page_path );
				} else {
					error_log( 'Page not found or not published' );
				}
			}
		}

		// Build the final URL.
		$final_url = $this->build_regional_url( $url_slug, $page_path );
		error_log( 'Final URL built: ' . $final_url );
		error_log( '========== RM Router END ==========' );

		return $final_url;
	}

	/**
	 * Get regional page setting from rm_regional_pages table.
	 *
	 * @param int    $region_id Region ID.
	 * @param string $page_type Page type (e.g., 'welcome', 'shop', 'categories').
	 * @return string|null Page ID, 'shop', 'home', or null.
	 */
	private function get_regional_page_setting( $region_id, $page_type ) {
		global $wpdb;

		$result = $wpdb->get_var(
			$wpdb->prepare(
				"SELECT page_id FROM {$wpdb->prefix}rm_regional_pages
				 WHERE region_id = %d AND page_type = %s AND is_active = 1",
				$region_id,
				$page_type
			)
		);

		return $result;
	}

	/**
	 * Get the full path for a page (handles parent/child pages).
	 *
	 * @param WP_Post $page Page object.
	 * @return string Page path.
	 */
	private function get_page_path( $page ) {
		// If page has no parent, just return the slug.
		if ( empty( $page->post_parent ) ) {
			return $page->post_name;
		}

		// Build path including parent pages.
		$path_parts    = array( $page->post_name );
		$current_page  = $page;
		$max_depth     = 10; // Prevent infinite loops.
		$current_depth = 0;

		while ( $current_page->post_parent > 0 && $current_depth < $max_depth ) {
			$parent = get_post( $current_page->post_parent );
			if ( ! $parent ) {
				break;
			}
			array_unshift( $path_parts, $parent->post_name );
			$current_page = $parent;
			$current_depth++;
		}

		return implode( '/', $path_parts );
	}

	/**
	 * Build the final URL with region slug and page path.
	 *
	 * @param string $url_slug Region URL slug (e.g., 'pt').
	 * @param string $page_path Page path (e.g., 'home' or 'shop' or 'parent/child').
	 * @return string Full URL.
	 */
	private function build_regional_url( $url_slug, $page_path = '' ) {
		$url_slug  = trim( $url_slug, '/' );
		$page_path = trim( $page_path, '/' );

		error_log( 'build_regional_url - URL slug: "' . $url_slug . '", Page path: "' . $page_path . '"' );

		// Build path: /url_slug/page_path/.
		$path = '/' . $url_slug;

		if ( ! empty( $page_path ) ) {
			$path .= '/' . $page_path;
		}

		$path .= '/';

		// Clean up any double slashes.
		$path = preg_replace( '#/+#', '/', $path );

		error_log( 'build_regional_url - Final path: "' . $path . '"' );

		$full_url = home_url( $path );

		error_log( 'build_regional_url - Full URL: "' . $full_url . '"' );

		return $full_url;
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
