<?php
/**
 * URL Rewrite Handler
 *
 * Handles URL rewriting for region-based URLs and generates proper permalinks.
 *
 * @package    Region_Manager
 * @subpackage Region_Manager/includes
 */

/**
 * URL Rewrite Handler Class.
 *
 * Manages URL rewriting, region detection from URLs, and hreflang tag generation.
 */
class RM_Rewrite {

	/**
	 * Debug mode flag.
	 *
	 * @var bool
	 */
	private $debug_mode = false;

	/**
	 * Initialize the class and set up hooks.
	 */
	public function __construct() {
		// Check if URL-based regions are enabled.
		$url_regions_enabled = get_option( 'rm_enable_url_regions', '1' );

		if ( '1' !== $url_regions_enabled ) {
			return;
		}

		// Debug mode.
		$this->debug_mode = defined( 'WP_DEBUG' ) && WP_DEBUG;

		// Register rewrite rules.
		add_action( 'init', array( $this, 'register_rewrite_rules' ), 10 );
		add_filter( 'query_vars', array( $this, 'add_query_vars' ) );

		// Detect region from query.
		add_action( 'template_redirect', array( $this, 'set_region_from_query' ), 1 );

		// URL generation filters.
		add_filter( 'home_url', array( $this, 'localize_url' ), 10, 2 );
		add_filter( 'woocommerce_get_cart_url', array( $this, 'localize_wc_url' ) );
		add_filter( 'woocommerce_get_checkout_url', array( $this, 'localize_wc_url' ) );
		add_filter( 'wc_get_page_permalink', array( $this, 'localize_wc_url' ) );

		// Hreflang tags.
		add_action( 'wp_head', array( $this, 'output_hreflang_tags' ) );

		// Region switcher shortcode.
		add_shortcode( 'rm_region_switcher', array( $this, 'region_switcher_shortcode' ) );

		// Check if rules need flushing.
		add_action( 'admin_notices', array( $this, 'maybe_show_flush_notice' ) );
	}

	/**
	 * Register rewrite rules for each region.
	 */
	public function register_rewrite_rules() {
		global $wpdb;

		// Get all active country URL slugs (not region slugs).
		$url_slugs = $wpdb->get_results(
			"SELECT DISTINCT rc.url_slug
			FROM {$wpdb->prefix}rm_region_countries rc
			INNER JOIN {$wpdb->prefix}rm_regions r ON rc.region_id = r.id
			WHERE r.status = 'active' AND rc.url_slug IS NOT NULL AND rc.url_slug != ''
			ORDER BY rc.url_slug ASC",
			ARRAY_A
		);

		if ( ! $url_slugs ) {
			return;
		}

		foreach ( $url_slugs as $row ) {
			$slug = $row['url_slug'];

			// Main region root.
			add_rewrite_rule(
				'^' . $slug . '/?$',
				'index.php?rm_url_slug=' . $slug,
				'top'
			);

			// Region + any path.
			add_rewrite_rule(
				'^' . $slug . '/(.+)$',
				'index.php?rm_url_slug=' . $slug . '&__rm_path=$matches[1]',
				'top'
			);
		}

		$this->log_debug( 'Rewrite rules registered for URL slugs: ' . implode( ', ', wp_list_pluck( $url_slugs, 'url_slug' ) ) );
	}

	/**
	 * Add custom query vars.
	 *
	 * @param array $vars Query vars.
	 * @return array Modified query vars.
	 */
	public function add_query_vars( $vars ) {
		if ( ! is_array( $vars ) ) {
			$vars = array();
		}

		$vars[] = 'rm_region_slug'; // Keep for backward compatibility.
		$vars[] = 'rm_url_slug'; // New query var for country URL slugs.
		$vars[] = '__rm_path';

		return $vars;
	}

	/**
	 * Set region from query var.
	 *
	 * Detects region from URL and sets it in session.
	 */
	public function set_region_from_query() {
		// Try new URL slug query var first.
		$url_slug = get_query_var( 'rm_url_slug' );

		// Fallback to old region_slug for backward compatibility.
		if ( empty( $url_slug ) ) {
			$url_slug = get_query_var( 'rm_region_slug' );
		}

		if ( empty( $url_slug ) ) {
			return;
		}

		$this->log_debug( 'URL slug from query: ' . $url_slug );

		// Get country and region by URL slug.
		global $wpdb;
		$country = $wpdb->get_row(
			$wpdb->prepare(
				"SELECT rc.country_code, rc.url_slug, rc.language_code, rc.region_id, r.slug as region_slug
				FROM {$wpdb->prefix}rm_region_countries rc
				INNER JOIN {$wpdb->prefix}rm_regions r ON rc.region_id = r.id
				WHERE rc.url_slug = %s AND r.status = 'active'
				LIMIT 1",
				$url_slug
			)
		);

		if ( $country ) {
			// Store in WC session using new format.
			if ( function_exists( 'WC' ) && WC()->session ) {
				WC()->session->set( 'rm_current_region_id', $country->region_id );
				WC()->session->set( 'rm_current_country', $country->country_code );
				WC()->session->set( 'rm_current_url_slug', $country->url_slug );
				WC()->session->set( 'rm_current_language', $country->language_code );
			}

			// Also set cookies.
			$cookie_expiry = time() + ( 30 * DAY_IN_SECONDS );
			$cookie_path   = COOKIEPATH ? COOKIEPATH : '/';
			$cookie_domain = COOKIE_DOMAIN ? COOKIE_DOMAIN : '';
			$secure        = is_ssl();

			setcookie( 'rm_region_id', $country->region_id, $cookie_expiry, $cookie_path, $cookie_domain, $secure, true );
			setcookie( 'rm_country', $country->country_code, $cookie_expiry, $cookie_path, $cookie_domain, $secure, true );
			setcookie( 'rm_url_slug', $country->url_slug, $cookie_expiry, $cookie_path, $cookie_domain, $secure, true );
			setcookie( 'rm_language', $country->language_code, $cookie_expiry, $cookie_path, $cookie_domain, $secure, true );

			$this->log_debug( 'Country/Region set from query: Country=' . $country->country_code . ', Region ID=' . $country->region_id . ', URL Slug=' . $country->url_slug );

			// Handle nested path if present.
			$nested_path = get_query_var( '__rm_path' );
			if ( ! empty( $nested_path ) ) {
				// Parse the nested path and set appropriate query vars.
				global $wp;
				$wp->query_vars = array_merge( $wp->query_vars, $this->parse_nested_path( $nested_path ) );

				$this->log_debug( 'Nested path parsed: ' . $nested_path );
			}
		}
	}

	/**
	 * Parse nested path into query vars.
	 *
	 * @param string $path Nested path.
	 * @return array Query vars.
	 */
	private function parse_nested_path( $path ) {
		$query_vars = array();

		// Let WordPress parse the path normally.
		global $wp_rewrite;
		$rewrite_rules = $wp_rewrite->wp_rewrite_rules();

		foreach ( $rewrite_rules as $pattern => $replacement ) {
			if ( preg_match( '#^' . $pattern . '#', $path, $matches ) ) {
				// Extract query vars from matches.
				preg_match_all( '/\$matches\[([0-9]+)\]/', $replacement, $var_matches );

				if ( ! empty( $var_matches[1] ) ) {
					foreach ( $var_matches[1] as $index ) {
						if ( isset( $matches[ $index ] ) ) {
							$query_string = str_replace( '$matches[' . $index . ']', $matches[ $index ], $replacement );
						}
					}

					if ( ! empty( $query_string ) ) {
						parse_str( str_replace( 'index.php?', '', $query_string ), $query_vars );
						break;
					}
				}
			}
		}

		return $query_vars;
	}

	/**
	 * Localize URL with current region slug.
	 *
	 * @param string $url  URL to localize.
	 * @param string $path Path relative to home URL.
	 * @return string Localized URL.
	 */
	public function localize_url( $url, $path = '' ) {
		// Don't modify admin URLs.
		if ( is_admin() ) {
			return $url;
		}

		// Get current region.
		$region_slug = null;

		if ( function_exists( 'WC' ) && WC()->session ) {
			$region_slug = WC()->session->get( 'rm_current_url_slug' );
		}

		if ( empty( $region_slug ) ) {
			return $url;
		}

		// Check if URL already has region slug.
		if ( false !== strpos( $url, '/' . $region_slug . '/' ) ) {
			return $url;
		}

		// Add region slug after domain.
		$parsed_url = wp_parse_url( $url );
		$home_url = trailingslashit( home_url() );

		// Only modify URLs from this site.
		if ( isset( $parsed_url['host'] ) && $parsed_url['host'] !== wp_parse_url( $home_url, PHP_URL_HOST ) ) {
			return $url;
		}

		// Insert region slug.
		$localized_url = str_replace( $home_url, $home_url . $region_slug . '/', $url );

		$this->log_debug( 'URL localized: ' . $url . ' => ' . $localized_url );

		return $localized_url;
	}

	/**
	 * Localize WooCommerce URL.
	 *
	 * @param string $url URL to localize.
	 * @return string Localized URL.
	 */
	public function localize_wc_url( $url ) {
		return $this->localize_url( $url );
	}

	/**
	 * Output hreflang tags for SEO.
	 *
	 * Generates alternate language tags for all regions.
	 */
	public function output_hreflang_tags() {
		global $wpdb;

		// Get current URL.
		$current_url = ( is_ssl() ? 'https://' : 'http://' ) . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];

		// Get all active regions with language codes.
		$regions = $wpdb->get_results(
			"SELECT r.id, r.slug, rc.language_code, rc.country_code
			FROM {$wpdb->prefix}rm_regions r
			LEFT JOIN {$wpdb->prefix}rm_region_countries rc ON r.id = rc.region_id AND rc.is_default = 1
			WHERE r.status = 'active'
			ORDER BY r.slug ASC",
			ARRAY_A
		);

		if ( ! $regions ) {
			return;
		}

		$home_url = trailingslashit( home_url() );

		// Default/x-default.
		echo '<link rel="alternate" hreflang="x-default" href="' . esc_url( $home_url ) . '" />' . "\n";

		// Each region.
		foreach ( $regions as $region ) {
			if ( empty( $region['slug'] ) ) {
				continue;
			}

			$region_url = $home_url . $region['slug'] . '/';

			// Construct hreflang value.
			$hreflang = ! empty( $region['language_code'] ) ? $region['language_code'] : 'en';

			if ( ! empty( $region['country_code'] ) ) {
				$hreflang .= '-' . strtoupper( $region['country_code'] );
			}

			echo '<link rel="alternate" hreflang="' . esc_attr( $hreflang ) . '" href="' . esc_url( $region_url ) . '" />' . "\n";
		}
	}

	/**
	 * Region switcher shortcode.
	 *
	 * [rm_region_switcher style="dropdown|list|flags" show_flags="true|false"]
	 *
	 * @param array $atts Shortcode attributes.
	 * @return string HTML output.
	 */
	public function region_switcher_shortcode( $atts ) {
		$atts = shortcode_atts(
			array(
				'style'      => 'dropdown',
				'show_flags' => 'true',
			),
			$atts,
			'rm_region_switcher'
		);

		global $wpdb;

		// Get all active regions.
		$regions = $wpdb->get_results(
			"SELECT r.id, r.name, r.slug, rc.country_code
			FROM {$wpdb->prefix}rm_regions r
			LEFT JOIN {$wpdb->prefix}rm_region_countries rc ON r.id = rc.region_id AND rc.is_default = 1
			WHERE r.status = 'active'
			ORDER BY r.name ASC",
			ARRAY_A
		);

		if ( ! $regions ) {
			return '';
		}

		$current_region = null;
		if ( function_exists( 'WC' ) && WC()->session ) {
			$current_region = WC()->session->get( 'rm_current_region' );
		}

		$home_url = trailingslashit( home_url() );
		$show_flags = 'true' === $atts['show_flags'];

		ob_start();

		if ( 'dropdown' === $atts['style'] ) {
			// Dropdown style.
			echo '<div class="rm-region-switcher rm-switcher-dropdown">';
			echo '<select id="rm-region-select" onchange="window.location.href=this.value">';
			echo '<option value="' . esc_url( $home_url ) . '">' . esc_html__( 'Select Region', 'region-manager' ) . '</option>';

			foreach ( $regions as $region ) {
				$region_url = ! empty( $region['slug'] ) ? $home_url . $region['slug'] . '/' : $home_url;
				$selected = $current_region === $region['id'] ? 'selected' : '';
				$flag = $show_flags && ! empty( $region['country_code'] ) ? $this->get_flag_emoji( $region['country_code'] ) . ' ' : '';

				echo '<option value="' . esc_url( $region_url ) . '" ' . $selected . '>';
				echo $flag . esc_html( $region['name'] );
				echo '</option>';
			}

			echo '</select>';
			echo '</div>';

		} elseif ( 'list' === $atts['style'] ) {
			// List style.
			echo '<div class="rm-region-switcher rm-switcher-list">';
			echo '<ul class="rm-region-list">';

			foreach ( $regions as $region ) {
				$region_url = ! empty( $region['slug'] ) ? $home_url . $region['slug'] . '/' : $home_url;
				$current_class = $current_region === $region['id'] ? 'current-region' : '';
				$flag = $show_flags && ! empty( $region['country_code'] ) ? '<span class="rm-flag">' . $this->get_flag_emoji( $region['country_code'] ) . '</span> ' : '';

				echo '<li class="' . esc_attr( $current_class ) . '">';
				echo '<a href="' . esc_url( $region_url ) . '">';
				echo $flag . esc_html( $region['name'] );
				echo '</a>';
				echo '</li>';
			}

			echo '</ul>';
			echo '</div>';

		} elseif ( 'flags' === $atts['style'] ) {
			// Flags only style.
			echo '<div class="rm-region-switcher rm-switcher-flags">';

			foreach ( $regions as $region ) {
				if ( empty( $region['country_code'] ) ) {
					continue;
				}

				$region_url = ! empty( $region['slug'] ) ? $home_url . $region['slug'] . '/' : $home_url;
				$current_class = $current_region === $region['id'] ? 'current-region' : '';

				echo '<a href="' . esc_url( $region_url ) . '" class="rm-flag-link ' . esc_attr( $current_class ) . '" title="' . esc_attr( $region['name'] ) . '">';
				echo $this->get_flag_emoji( $region['country_code'] );
				echo '</a> ';
			}

			echo '</div>';
		}

		return ob_get_clean();
	}

	/**
	 * Get flag emoji for country code.
	 *
	 * @param string $country_code Country code (e.g., 'US', 'GB').
	 * @return string Flag emoji.
	 */
	private function get_flag_emoji( $country_code ) {
		if ( empty( $country_code ) || strlen( $country_code ) !== 2 ) {
			return '🏳️';
		}

		$code_points = array();
		for ( $i = 0; $i < strlen( $country_code ); $i++ ) {
			$code_points[] = 127397 + ord( $country_code[ $i ] );
		}

		return mb_convert_encoding( '&#' . implode( ';&#', $code_points ) . ';', 'UTF-8', 'HTML-ENTITIES' );
	}

	/**
	 * Flush rewrite rules.
	 */
	public function flush_rules() {
		$this->register_rewrite_rules();
		flush_rewrite_rules();
		update_option( 'rm_rewrite_rules_flushed', time() );

		$this->log_debug( 'Rewrite rules flushed' );
	}

	/**
	 * Maybe flush rewrite rules.
	 *
	 * Checks if rules need flushing and sets flag.
	 */
	public function maybe_flush_rules() {
		// Set flag to flush on next page load.
		update_option( 'rm_rewrite_rules_need_flush', '1' );
	}

	/**
	 * Maybe show flush notice in admin.
	 */
	public function maybe_show_flush_notice() {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		$needs_flush = get_option( 'rm_rewrite_rules_need_flush', '0' );

		if ( '1' === $needs_flush ) {
			?>
			<div class="notice notice-warning is-dismissible">
				<p>
					<strong><?php esc_html_e( 'Region Manager:', 'region-manager' ); ?></strong>
					<?php esc_html_e( 'URL rewrite rules need to be flushed.', 'region-manager' ); ?>
					<a href="<?php echo esc_url( admin_url( 'options-permalink.php' ) ); ?>" class="button button-small">
						<?php esc_html_e( 'Flush Rules', 'region-manager' ); ?>
					</a>
				</p>
			</div>
			<?php
		}
	}

	/**
	 * Log debug message.
	 *
	 * @param string $message Debug message.
	 */
	private function log_debug( $message ) {
		if ( $this->debug_mode ) {
			error_log( '[Region Manager Rewrite] ' . $message );
		}
	}
}
