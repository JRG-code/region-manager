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
		$flag_emoji = '';
		$region_name = __( 'Select Region', 'region-manager' );

		if ( $current_region ) {
			$countries = $this->get_region_countries( $current_region['id'] );
			if ( ! empty( $countries ) ) {
				$flag_emoji = $this->get_flag_emoji( $countries[0] );
			}
			$region_name = $current_region['name'];
		}

		ob_start();
		?>
		<li class="menu-item rm-menu-flag-item">
			<a href="#" class="rm-menu-flag-link">
				<?php if ( $flag_emoji ) : ?>
					<span class="rm-flag-emoji"><?php echo esc_html( $flag_emoji ); ?></span>
				<?php endif; ?>
				<?php if ( $settings['show_text'] ) : ?>
					<span class="rm-region-name"><?php echo esc_html( $region_name ); ?></span>
				<?php endif; ?>
			</a>
			<?php if ( $settings['show_dropdown'] ) : ?>
				<ul class="sub-menu rm-region-dropdown">
					<?php foreach ( $regions as $region ) : ?>
						<?php
						$countries       = $this->get_region_countries( $region['id'] );
						$region_flag     = ! empty( $countries ) ? $this->get_flag_emoji( $countries[0] ) : '';
						$is_current      = $current_region && $current_region['id'] === $region['id'];
						$region_url      = $this->get_region_url( $region['slug'] );
						?>
						<li class="menu-item <?php echo $is_current ? 'current-menu-item' : ''; ?>">
							<a href="<?php echo esc_url( $region_url ); ?>">
								<?php if ( $region_flag ) : ?>
									<span class="rm-flag-emoji"><?php echo esc_html( $region_flag ); ?></span>
								<?php endif; ?>
								<span class="rm-region-name"><?php echo esc_html( $region['name'] ); ?></span>
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
}
