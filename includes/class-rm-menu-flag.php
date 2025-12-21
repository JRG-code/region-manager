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
						$country_url = $this->get_region_url( $country['url_slug'] );
						?>
						<li class="menu-item <?php echo $is_current ? 'current-menu-item' : ''; ?>">
							<a href="<?php echo esc_url( $country_url ); ?>">
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
		$table_name = $wpdb->prefix . 'rm_regions';

		$region_slug = get_query_var( 'rm_region_slug', '' );

		if ( empty( $region_slug ) && isset( $_COOKIE['rm_selected_region'] ) ) {
			$region_slug = sanitize_text_field( wp_unslash( $_COOKIE['rm_selected_region'] ) );
		}

		if ( empty( $region_slug ) ) {
			return null;
		}

		$region = $wpdb->get_row(
			$wpdb->prepare(
				"SELECT * FROM {$table_name} WHERE slug = %s AND status = 'active'",
				$region_slug
			),
			ARRAY_A
		);

		return $region;
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
}
