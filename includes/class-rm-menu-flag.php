<?php
/**
 * Menu Flag functionality for navigation integration.
 *
 * @package    Region_Manager
 * @subpackage Region_Manager/includes
 */

/**
 * Menu Flag class.
 *
 * Adds flag icon and region switcher to navigation menus.
 */
class RM_Menu_Flag {

	/**
	 * Singleton instance.
	 *
	 * @var RM_Menu_Flag
	 */
	private static $instance = null;

	/**
	 * Get singleton instance.
	 *
	 * @return RM_Menu_Flag
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
		$settings = $this->get_settings();

		if ( $settings['enabled'] ) {
			add_filter( 'wp_nav_menu_items', array( $this, 'add_flag_to_menu' ), 10, 2 );
		}

		// AJAX handler for menu dropdown country switch.
		add_action( 'wp_ajax_rm_switch_country', array( $this, 'handle_switch_country' ) );
		add_action( 'wp_ajax_nopriv_rm_switch_country', array( $this, 'handle_switch_country' ) );
	}

	/**
	 * Add flag to navigation menu.
	 *
	 * @param string $items Menu items HTML.
	 * @param object $args  Menu arguments.
	 * @return string Modified menu items HTML.
	 */
	public function add_flag_to_menu( $items, $args ) {
		$settings = $this->get_settings();

		if ( $args->theme_location !== $settings['menu_location'] ) {
			return $items;
		}

		$current_region = $this->get_current_region();
		$regions        = $this->get_active_regions();

		if ( empty( $regions ) ) {
			return $items;
		}

		$flag_html = $this->render_flag_menu_item( $current_region, $regions, $settings );

		if ( 'left' === $settings['position'] ) {
			return $flag_html . $items;
		} else {
			return $items . $flag_html;
		}
	}

	/**
	 * Render flag menu item.
	 *
	 * @param array|null $current_region Current region data.
	 * @param array      $regions        All active regions.
	 * @param array      $settings       Menu flag settings.
	 * @return string HTML output.
	 */
	private function render_flag_menu_item( $current_region, $regions, $settings ) {
		$flag_html = '';
		$current_country_code = '';

		if ( $current_region ) {
			$countries = $this->get_region_countries( $current_region['id'] );
			if ( ! empty( $countries ) ) {
				$current_country_code = $countries[0];
				$flag_html = '<span class="rm-flag-emoji">' . esc_html( $this->get_flag_emoji( $current_country_code ) ) . '</span>';
			}
		}

		// If no country selected, show globe icon.
		if ( empty( $flag_html ) ) {
			$flag_html = $this->get_global_flag_html();
		}

		// Get all countries from all active regions for dropdown.
		$all_countries = $this->get_all_countries_for_dropdown();

		ob_start();
		?>
		<li class="menu-item rm-menu-flag-item <?php echo $current_country_code ? 'rm-has-country' : 'rm-no-country'; ?>">
			<a href="#" class="rm-menu-flag-link">
				<?php echo $flag_html; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
			</a>
			<?php if ( $settings['show_dropdown'] ) : ?>
				<ul class="sub-menu rm-region-dropdown">
					<?php foreach ( $all_countries as $country ) : ?>
						<?php
						$is_current  = $current_country_code === $country['country_code'];
						?>
						<li class="menu-item rm-dropdown-country-item <?php echo $is_current ? 'current-menu-item' : ''; ?>"
						    data-country-code="<?php echo esc_attr( $country['country_code'] ); ?>"
						    data-url-slug="<?php echo esc_attr( $country['url_slug'] ); ?>"
						    data-language-code="<?php echo esc_attr( $country['language_code'] ); ?>"
						    data-region-id="<?php echo esc_attr( $country['region_id'] ); ?>">
							<a href="#" class="rm-menu-country-link">
								<span class="rm-flag-emoji"><?php echo esc_html( $this->get_flag_emoji( $country['country_code'] ) ); ?></span>
								<span class="rm-country-name"><?php echo esc_html( $country['country_name'] ); ?></span>
							</a>
						</li>
					<?php endforeach; ?>
				</ul>
			<?php endif; ?>
		</li>
		<?php
		return ob_get_clean();
	}

	/**
	 * Get menu flag settings.
	 *
	 * @return array Settings.
	 */
	private function get_settings() {
		return array(
			'enabled'       => (bool) get_option( 'rm_menu_flag_enabled', false ),
			'position'      => get_option( 'rm_menu_flag_position', 'right' ),
			'menu_location' => get_option( 'rm_menu_flag_menu_location', 'primary' ),
			'show_text'     => (bool) get_option( 'rm_menu_flag_show_text', true ),
			'show_dropdown' => (bool) get_option( 'rm_menu_flag_show_dropdown', true ),
		);
	}

	/**
	 * Get current region from URL or cookie.
	 *
	 * @return array|null Region data or null.
	 */
	private function get_current_region() {
		global $wpdb;

		// Check for region_id in new cookies first.
		$region_id = null;
		if ( isset( $_COOKIE['rm_region_id'] ) && ! empty( $_COOKIE['rm_region_id'] ) ) {
			$region_id = intval( $_COOKIE['rm_region_id'] );
		} elseif ( function_exists( 'WC' ) && WC()->session ) {
			$region_id = WC()->session->get( 'rm_current_region_id' );
		}

		if ( $region_id ) {
			$region = $wpdb->get_row(
				$wpdb->prepare(
					"SELECT * FROM {$wpdb->prefix}rm_regions WHERE id = %d AND status = 'active'",
					$region_id
				),
				ARRAY_A
			);

			if ( $region ) {
				return $region;
			}
		}

		// Fallback to old cookie for backward compatibility.
		$region_slug = get_query_var( 'rm_region_slug', '' );

		if ( empty( $region_slug ) && isset( $_COOKIE['rm_selected_region'] ) ) {
			$region_slug = sanitize_text_field( wp_unslash( $_COOKIE['rm_selected_region'] ) );
		}

		if ( ! empty( $region_slug ) ) {
			$region = $wpdb->get_row(
				$wpdb->prepare(
					"SELECT * FROM {$wpdb->prefix}rm_regions WHERE slug = %s AND status = 'active'",
					$region_slug
				),
				ARRAY_A
			);

			return $region;
		}

		return null;
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
			"SELECT * FROM {$table_name} WHERE status = 'active' ORDER BY name ASC",
			ARRAY_A
		);

		return $results;
	}

	/**
	 * Get region country codes.
	 *
	 * @param int $region_id Region ID.
	 * @return array Country codes.
	 */
	private function get_region_countries( $region_id ) {
		global $wpdb;
		$table_name = $wpdb->prefix . 'rm_region_countries';

		$results = $wpdb->get_col(
			$wpdb->prepare(
				"SELECT country_code FROM {$table_name} WHERE region_id = %d",
				$region_id
			)
		);

		return $results;
	}

	/**
	 * Get all countries from all active regions for dropdown.
	 *
	 * @return array Array of countries with country_code, country_name, url_slug.
	 */
	private function get_all_countries_for_dropdown() {
		global $wpdb;

		$table_regions = $wpdb->prefix . 'rm_regions';
		$table_countries = $wpdb->prefix . 'rm_region_countries';

		// Get all countries from active regions.
		$countries = $wpdb->get_results(
			"SELECT DISTINCT
				rc.country_code,
				rc.url_slug,
				rc.language_code,
				rc.region_id
			FROM {$table_countries} rc
			INNER JOIN {$table_regions} r ON rc.region_id = r.id
			WHERE r.status = 'active'
			ORDER BY rc.country_code ASC",
			ARRAY_A
		);

		if ( empty( $countries ) ) {
			return array();
		}

		// Get WooCommerce country names.
		$wc_countries = function_exists( 'WC' ) && WC()->countries ? WC()->countries->get_countries() : array();

		$display_countries = array();
		$seen_codes = array(); // Prevent duplicates.

		foreach ( $countries as $country ) {
			// Skip if already added (same country in multiple regions).
			if ( in_array( $country['country_code'], $seen_codes, true ) ) {
				continue;
			}

			$seen_codes[] = $country['country_code'];

			$display_countries[] = array(
				'country_code' => $country['country_code'],
				'country_name' => isset( $wc_countries[ $country['country_code'] ] ) ? $wc_countries[ $country['country_code'] ] : $country['country_code'],
				'url_slug'     => $country['url_slug'],
				'language_code' => $country['language_code'],
				'region_id'    => $country['region_id'],
			);
		}

		// Sort by country name alphabetically.
		usort(
			$display_countries,
			function ( $a, $b ) {
				return strcasecmp( $a['country_name'], $b['country_name'] );
			}
		);

		return $display_countries;
	}

	/**
	 * Get flag emoji for country code.
	 *
	 * @param string $country_code Two-letter country code.
	 * @return string Flag emoji.
	 */
	private function get_flag_emoji( $country_code ) {
		$country_code = strtoupper( $country_code );
		$flag         = '';

		for ( $i = 0; $i < strlen( $country_code ); $i++ ) {
			$flag .= mb_chr( 127397 + ord( $country_code[ $i ] ) );
		}

		return $flag;
	}

	/**
	 * Get URL for region.
	 *
	 * @param string $region_slug Region slug.
	 * @return string Region URL.
	 */
	private function get_region_url( $region_slug ) {
		return home_url( '/' . $region_slug . '/' );
	}

	/**
	 * Get global/world flag for unselected state.
	 *
	 * @return string HTML for globe icon.
	 */
	private function get_global_flag_html() {
		$globe_svg = '<svg xmlns="http://www.w3.org/2000/svg" width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="rm-flag-globe">
			<circle cx="12" cy="12" r="10"></circle>
			<line x1="2" y1="12" x2="22" y2="12"></line>
			<path d="M12 2a15.3 15.3 0 0 1 4 10 15.3 15.3 0 0 1-4 10 15.3 15.3 0 0 1-4-10 15.3 15.3 0 0 1 4-10z"></path>
		</svg>';

		return '<span class="rm-flag-emoji rm-flag-global" title="' . esc_attr__( 'Select your country', 'region-manager' ) . '">' . $globe_svg . '</span>';
	}

	/**
	 * AJAX handler for switching country from menu dropdown.
	 */
	public function handle_switch_country() {
		check_ajax_referer( 'rm_public_nonce', 'nonce' );

		// Get parameters from request.
		$country_code  = isset( $_POST['country_code'] ) ? strtoupper( sanitize_text_field( wp_unslash( $_POST['country_code'] ) ) ) : '';
		$url_slug      = isset( $_POST['url_slug'] ) ? sanitize_text_field( wp_unslash( $_POST['url_slug'] ) ) : '';
		$language_code = isset( $_POST['language_code'] ) ? sanitize_text_field( wp_unslash( $_POST['language_code'] ) ) : '';
		$region_id     = isset( $_POST['region_id'] ) ? intval( $_POST['region_id'] ) : 0;

		// DEBUG.
		error_log( '========== RM DEBUG: handle_switch_country ==========' );
		error_log( 'Country Code: ' . $country_code );
		error_log( 'URL Slug: ' . $url_slug );
		error_log( 'Language Code: ' . $language_code );
		error_log( 'Region ID: ' . $region_id );

		if ( empty( $country_code ) || empty( $url_slug ) ) {
			error_log( 'ERROR: Missing required parameters' );
			wp_send_json_error( array( 'message' => __( 'Invalid selection.', 'region-manager' ) ) );
			return;
		}

		$url_slug = trim( $url_slug, '/' );

		global $wpdb;

		// If region_id not provided, look it up.
		if ( ! $region_id ) {
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
		}

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

		// Get redirect URL using RegionalRouter - same logic as landing page.
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
