<?php
/**
 * WooCommerce Integration
 *
 * Handles all WooCommerce-specific functionality including region detection,
 * price overrides, checkout validation, and order processing.
 *
 * @package    Region_Manager
 * @subpackage Region_Manager/includes
 */

/**
 * WooCommerce Integration Class.
 *
 * Manages WooCommerce integration for regional pricing, availability, and checkout.
 */
class RM_WooCommerce {

	/**
	 * Current detected region ID.
	 *
	 * @var int|null
	 */
	private $current_region = null;

	/**
	 * Current URL slug.
	 *
	 * @var string|null
	 */
	private $current_url_slug = null;

	/**
	 * Initialize the class and set up hooks.
	 */
	public function __construct() {
		// Region detection.
		add_action( 'template_redirect', array( $this, 'detect_region_from_url' ), 5 );

		// Price overrides.
		add_filter( 'woocommerce_product_get_price', array( $this, 'filter_product_price' ), 10, 2 );
		add_filter( 'woocommerce_product_get_sale_price', array( $this, 'filter_product_sale_price' ), 10, 2 );
		add_filter( 'woocommerce_product_get_regular_price', array( $this, 'filter_product_regular_price' ), 10, 2 );
		add_filter( 'woocommerce_variation_prices', array( $this, 'filter_variation_prices' ), 10, 3 );

		// Product availability.
		add_filter( 'woocommerce_is_purchasable', array( $this, 'filter_product_purchasable' ), 10, 2 );

		// Checkout validation.
		add_action( 'woocommerce_checkout_process', array( $this, 'validate_checkout_region' ) );

		// Cross-region fee.
		add_action( 'woocommerce_cart_calculate_fees', array( $this, 'maybe_add_cross_region_fee' ) );

		// Save region to order.
		add_action( 'woocommerce_checkout_order_processed', array( $this, 'save_order_region' ), 10, 1 );

		// Admin order display.
		add_action( 'woocommerce_admin_order_data_after_billing_address', array( $this, 'display_order_region_admin' ), 10, 1 );
	}

	/**
	 * Detect region from current URL.
	 *
	 * Parses the URL for region slug patterns and sets the current region.
	 * Stores the region in WooCommerce session.
	 */
	public function detect_region_from_url() {
		global $wpdb;

		// Get current URL path.
		$url_path = trim( $_SERVER['REQUEST_URI'], '/' );
		$url_parts = explode( '/', $url_path );

		// Check first part of URL for region slug.
		if ( ! empty( $url_parts[0] ) ) {
			$potential_slug = sanitize_text_field( $url_parts[0] );

			// Query for region by URL slug.
			$region = $wpdb->get_row(
				$wpdb->prepare(
					"SELECT r.id, r.slug, rc.language_code
					FROM {$wpdb->prefix}rm_regions r
					LEFT JOIN {$wpdb->prefix}rm_region_countries rc ON r.id = rc.region_id AND rc.is_default = 1
					WHERE r.status = 'active' AND r.slug = %s
					LIMIT 1",
					$potential_slug
				)
			);

			if ( $region ) {
				$this->current_region = (int) $region->id;
				$this->current_url_slug = $region->slug;

				// Store in WC session.
				if ( function_exists( 'WC' ) && WC()->session ) {
					WC()->session->set( 'rm_current_region', $this->current_region );
					WC()->session->set( 'rm_current_url_slug', $this->current_url_slug );
				}

				/**
				 * Fires when a region is detected from the URL.
				 *
				 * Allows translator plugins to integrate and set the appropriate language.
				 *
				 * @param int    $region_id The detected region ID.
				 * @param string $url_slug  The URL slug used for detection.
				 *
				 * @since 1.0.0
				 */
				do_action( 'rm_region_detected', $this->current_region, $this->current_url_slug );

				/**
				 * Fires when a language code is detected from the region.
				 *
				 * Allows translator plugins to switch language based on region.
				 *
				 * @param string $language_code The language code (e.g., 'en', 'es', 'pt').
				 *
				 * @since 1.0.0
				 */
				if ( ! empty( $region->language_code ) ) {
					do_action( 'rm_language_code_detected', $region->language_code );
				}
			}
		}

		// If no region detected, try to get from session.
		if ( ! $this->current_region && function_exists( 'WC' ) && WC()->session ) {
			$session_region = WC()->session->get( 'rm_current_region' );
			if ( $session_region ) {
				$this->current_region = (int) $session_region;
				$this->current_url_slug = WC()->session->get( 'rm_current_url_slug' );
			}
		}

		// If still no region, use default.
		if ( ! $this->current_region ) {
			$default_region = $this->get_default_region();
			if ( $default_region ) {
				$this->current_region = $default_region['id'];
				$this->current_url_slug = $default_region['slug'];
			}
		}
	}

	/**
	 * Get the current region ID.
	 *
	 * @return int|null Current region ID or null.
	 */
	public function get_current_region() {
		/**
		 * Filters the current region ID.
		 *
		 * Allows modification of the detected region.
		 *
		 * @param int|null $region_id The current region ID.
		 *
		 * @since 1.0.0
		 */
		return apply_filters( 'rm_current_region', $this->current_region );
	}

	/**
	 * Get the current URL slug.
	 *
	 * @return string|null Current URL slug or null.
	 */
	public function get_current_url_slug() {
		return $this->current_url_slug;
	}

	/**
	 * Filter product price.
	 *
	 * @param float      $price   Product price.
	 * @param WC_Product $product Product object.
	 * @return float Modified price.
	 */
	public function filter_product_price( $price, $product ) {
		$region_id = $this->get_current_region();

		if ( ! $region_id ) {
			return $price;
		}

		$regional_price = $this->get_regional_product_price( $product->get_id(), 'price', $region_id );

		return $regional_price !== null ? $regional_price : $price;
	}

	/**
	 * Filter product sale price.
	 *
	 * @param float      $price   Sale price.
	 * @param WC_Product $product Product object.
	 * @return float Modified sale price.
	 */
	public function filter_product_sale_price( $price, $product ) {
		$region_id = $this->get_current_region();

		if ( ! $region_id ) {
			return $price;
		}

		$regional_price = $this->get_regional_product_price( $product->get_id(), 'sale_price', $region_id );

		return $regional_price !== null ? $regional_price : $price;
	}

	/**
	 * Filter product regular price.
	 *
	 * @param float      $price   Regular price.
	 * @param WC_Product $product Product object.
	 * @return float Modified regular price.
	 */
	public function filter_product_regular_price( $price, $product ) {
		$region_id = $this->get_current_region();

		if ( ! $region_id ) {
			return $price;
		}

		$regional_price = $this->get_regional_product_price( $product->get_id(), 'regular_price', $region_id );

		return $regional_price !== null ? $regional_price : $price;
	}

	/**
	 * Filter variation prices.
	 *
	 * @param array                   $prices  Variation prices.
	 * @param WC_Product_Variable     $product Variable product object.
	 * @param bool                    $display Display prices.
	 * @return array Modified prices.
	 */
	public function filter_variation_prices( $prices, $product, $display ) {
		$region_id = $this->get_current_region();

		if ( ! $region_id ) {
			return $prices;
		}

		// Get all variation IDs.
		$variations = $product->get_children();

		foreach ( $variations as $variation_id ) {
			$regional_price = $this->get_regional_product_price( $variation_id, 'price', $region_id );

			if ( $regional_price !== null ) {
				$prices['price'][ $variation_id ] = $regional_price;
				$prices['regular_price'][ $variation_id ] = $regional_price;
			}

			$regional_sale = $this->get_regional_product_price( $variation_id, 'sale_price', $region_id );
			if ( $regional_sale !== null ) {
				$prices['sale_price'][ $variation_id ] = $regional_sale;
			}
		}

		return $prices;
	}

	/**
	 * Get regional product price.
	 *
	 * @param int    $product_id Product ID.
	 * @param string $price_type Price type ('price', 'regular_price', 'sale_price').
	 * @param int    $region_id  Region ID (optional, uses current region).
	 * @return float|null Regional price or null if not set.
	 */
	public function get_regional_product_price( $product_id, $price_type = 'price', $region_id = null ) {
		global $wpdb;

		if ( ! $region_id ) {
			$region_id = $this->get_current_region();
		}

		if ( ! $region_id ) {
			return null;
		}

		$table_name = $wpdb->prefix . 'rm_product_regions';

		$result = $wpdb->get_row(
			$wpdb->prepare(
				"SELECT price_override, sale_price_override FROM {$table_name} WHERE product_id = %d AND region_id = %d",
				$product_id,
				$region_id
			),
			ARRAY_A
		);

		if ( ! $result ) {
			return null;
		}

		// Determine which price to return.
		switch ( $price_type ) {
			case 'sale_price':
				return ! empty( $result['sale_price_override'] ) ? floatval( $result['sale_price_override'] ) : null;

			case 'regular_price':
				return ! empty( $result['price_override'] ) ? floatval( $result['price_override'] ) : null;

			case 'price':
			default:
				// Return sale price if set, otherwise regular price override.
				if ( ! empty( $result['sale_price_override'] ) ) {
					return floatval( $result['sale_price_override'] );
				}
				return ! empty( $result['price_override'] ) ? floatval( $result['price_override'] ) : null;
		}
	}

	/**
	 * Filter product purchasability.
	 *
	 * @param bool       $purchasable Is purchasable.
	 * @param WC_Product $product     Product object.
	 * @return bool Modified purchasability.
	 */
	public function filter_product_purchasable( $purchasable, $product ) {
		$region_id = $this->get_current_region();

		if ( ! $region_id ) {
			return $purchasable;
		}

		$is_available = $this->is_product_available_in_region( $product->get_id(), $region_id );

		return $is_available ? $purchasable : false;
	}

	/**
	 * Check if product is available in region.
	 *
	 * @param int $product_id Product ID.
	 * @param int $region_id  Region ID.
	 * @return bool True if available.
	 */
	public function is_product_available_in_region( $product_id, $region_id ) {
		global $wpdb;

		$table_name = $wpdb->prefix . 'rm_product_regions';

		$count = $wpdb->get_var(
			$wpdb->prepare(
				"SELECT COUNT(*) FROM {$table_name} WHERE product_id = %d AND region_id = %d",
				$product_id,
				$region_id
			)
		);

		return $count > 0;
	}

	/**
	 * Validate checkout region.
	 *
	 * Checks if shipping country matches current region's countries.
	 * Applies cross-region policy from settings.
	 */
	public function validate_checkout_region() {
		$region_id = $this->get_current_region();

		if ( ! $region_id ) {
			return;
		}

		// Get shipping country from POST data.
		$shipping_country = isset( $_POST['shipping_country'] ) ? sanitize_text_field( $_POST['shipping_country'] ) : '';
		if ( empty( $shipping_country ) ) {
			$shipping_country = isset( $_POST['billing_country'] ) ? sanitize_text_field( $_POST['billing_country'] ) : '';
		}

		if ( empty( $shipping_country ) ) {
			return;
		}

		// Check if country is in region.
		$is_in_region = $this->is_country_in_region( $shipping_country, $region_id );

		if ( ! $is_in_region ) {
			// Get cross-region policy from settings.
			$cross_region_policy = get_option( 'rm_cross_region_policy', 'allow' );

			if ( 'block' === $cross_region_policy ) {
				// Block checkout with error message.
				$error_message = get_option( 'rm_cross_region_error_message', __( 'We do not ship to your country from this region. Please select the appropriate regional store.', 'region-manager' ) );
				wc_add_notice( $error_message, 'error' );
			}
		}
	}

	/**
	 * Maybe add cross-region fee.
	 *
	 * Adds extra charge if shipping to a country outside the current region.
	 */
	public function maybe_add_cross_region_fee() {
		$region_id = $this->get_current_region();

		if ( ! $region_id || ! WC()->cart ) {
			return;
		}

		// Get shipping country from customer data.
		$shipping_country = WC()->customer->get_shipping_country();
		if ( empty( $shipping_country ) ) {
			$shipping_country = WC()->customer->get_billing_country();
		}

		if ( empty( $shipping_country ) ) {
			return;
		}

		// Check if country is in region.
		$is_in_region = $this->is_country_in_region( $shipping_country, $region_id );

		if ( ! $is_in_region ) {
			// Get cross-region policy.
			$cross_region_policy = get_option( 'rm_cross_region_policy', 'allow' );

			if ( 'charge' === $cross_region_policy ) {
				// Add extra charge.
				$extra_charge = floatval( get_option( 'rm_cross_region_charge', 0 ) );

				if ( $extra_charge > 0 ) {
					WC()->cart->add_fee(
						__( 'International Shipping Surcharge', 'region-manager' ),
						$extra_charge,
						true
					);
				}
			}
		}
	}

	/**
	 * Save region to order.
	 *
	 * Stores region information in order meta.
	 *
	 * @param int $order_id Order ID.
	 */
	public function save_order_region( $order_id ) {
		$region_id = $this->get_current_region();
		$url_slug  = $this->get_current_url_slug();

		if ( ! $region_id ) {
			return;
		}

		$order = wc_get_order( $order_id );
		if ( ! $order ) {
			return;
		}

		// Save region ID.
		$order->update_meta_data( '_rm_region_id', $region_id );

		// Save URL slug.
		if ( $url_slug ) {
			$order->update_meta_data( '_rm_order_url_slug', $url_slug );
		}

		// Check if cross-region fee was applied.
		$shipping_country = $order->get_shipping_country();
		if ( empty( $shipping_country ) ) {
			$shipping_country = $order->get_billing_country();
		}

		$is_in_region = $this->is_country_in_region( $shipping_country, $region_id );
		if ( ! $is_in_region ) {
			$order->update_meta_data( '_rm_cross_region_order', 'yes' );

			// Get cross-region policy.
			$cross_region_policy = get_option( 'rm_cross_region_policy', 'allow' );
			if ( 'charge' === $cross_region_policy ) {
				$extra_charge = floatval( get_option( 'rm_cross_region_charge', 0 ) );
				if ( $extra_charge > 0 ) {
					$order->update_meta_data( '_rm_cross_region_fee', $extra_charge );
				}
			}
		}

		$order->save();
	}

	/**
	 * Display order region in admin.
	 *
	 * Shows region badge and cross-region warning in admin order view.
	 *
	 * @param WC_Order $order Order object.
	 */
	public function display_order_region_admin( $order ) {
		$region_id = $order->get_meta( '_rm_region_id' );

		if ( ! $region_id ) {
			return;
		}

		global $wpdb;
		$region = $wpdb->get_row(
			$wpdb->prepare(
				"SELECT name, slug FROM {$wpdb->prefix}rm_regions WHERE id = %d",
				$region_id
			)
		);

		if ( ! $region ) {
			return;
		}

		$is_cross_region = $order->get_meta( '_rm_cross_region_order' ) === 'yes';
		$cross_region_fee = $order->get_meta( '_rm_cross_region_fee' );

		echo '<div class="rm-order-region-info" style="margin-top: 15px; padding: 10px; background: #f0f6fc; border: 1px solid #c3d9f1; border-radius: 4px;">';
		echo '<h4 style="margin: 0 0 8px;">' . esc_html__( 'Region Information', 'region-manager' ) . '</h4>';

		echo '<p style="margin: 0 0 5px;"><strong>' . esc_html__( 'Region:', 'region-manager' ) . '</strong> ';
		echo $this->format_region_badge( $region_id );
		echo '</p>';

		if ( $is_cross_region ) {
			echo '<p style="margin: 0; color: #f0ad4e;">';
			echo '<span class="dashicons dashicons-warning" style="font-size: 16px; width: 16px; height: 16px; vertical-align: middle;"></span> ';
			echo '<strong>' . esc_html__( 'Cross-Region Order', 'region-manager' ) . '</strong> - ';
			echo esc_html__( 'Customer shipped to a country outside the region.', 'region-manager' );
			echo '</p>';

			if ( $cross_region_fee ) {
				echo '<p style="margin: 5px 0 0;">';
				echo '<strong>' . esc_html__( 'Cross-Region Fee Applied:', 'region-manager' ) . '</strong> ';
				echo wc_price( $cross_region_fee );
				echo '</p>';
			}
		}

		echo '</div>';
	}

	/**
	 * Get region by URL slug.
	 *
	 * @param string $slug URL slug.
	 * @return array|null Region data or null.
	 */
	public function get_region_by_url_slug( $slug ) {
		global $wpdb;

		$region = $wpdb->get_row(
			$wpdb->prepare(
				"SELECT * FROM {$wpdb->prefix}rm_regions WHERE slug = %s AND status = 'active' LIMIT 1",
				$slug
			),
			ARRAY_A
		);

		return $region;
	}

	/**
	 * Get region countries.
	 *
	 * @param int $region_id Region ID.
	 * @return array Array of country codes.
	 */
	public function get_region_countries( $region_id ) {
		global $wpdb;

		$countries = $wpdb->get_col(
			$wpdb->prepare(
				"SELECT country_code FROM {$wpdb->prefix}rm_region_countries WHERE region_id = %d",
				$region_id
			)
		);

		return $countries ? $countries : array();
	}

	/**
	 * Check if country is in region.
	 *
	 * @param string $country_code Country code (e.g., 'US', 'GB').
	 * @param int    $region_id    Region ID.
	 * @return bool True if country is in region.
	 */
	public function is_country_in_region( $country_code, $region_id ) {
		global $wpdb;

		$count = $wpdb->get_var(
			$wpdb->prepare(
				"SELECT COUNT(*) FROM {$wpdb->prefix}rm_region_countries WHERE region_id = %d AND country_code = %s",
				$region_id,
				$country_code
			)
		);

		return $count > 0;
	}

	/**
	 * Get default region.
	 *
	 * @return array|null Default region data or null.
	 */
	public function get_default_region() {
		global $wpdb;

		$region = $wpdb->get_row(
			"SELECT id, slug, name FROM {$wpdb->prefix}rm_regions WHERE status = 'active' ORDER BY id ASC LIMIT 1",
			ARRAY_A
		);

		return $region;
	}

	/**
	 * Format region badge HTML.
	 *
	 * @param int $region_id Region ID.
	 * @return string HTML badge.
	 */
	public function format_region_badge( $region_id ) {
		global $wpdb;

		$region = $wpdb->get_row(
			$wpdb->prepare(
				"SELECT name FROM {$wpdb->prefix}rm_regions WHERE id = %d",
				$region_id
			)
		);

		if ( ! $region ) {
			return '';
		}

		return sprintf(
			'<span style="display: inline-block; padding: 4px 10px; background: #f0f6fc; border: 1px solid #c3d9f1; border-radius: 3px; font-size: 12px; font-weight: 600; color: #135e96;">%s</span>',
			esc_html( $region->name )
		);
	}
}
