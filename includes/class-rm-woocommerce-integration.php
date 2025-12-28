<?php
/**
 * WooCommerce Integration for Regional Pricing.
 *
 * Handles price filters, currency switching, and cart/checkout integration.
 *
 * @package    Region_Manager
 * @subpackage Region_Manager/includes
 */

/**
 * WooCommerce Integration class.
 *
 * Filters product prices based on customer's region/country and handles currency switching.
 */
class RM_WooCommerce_Integration {

	/**
	 * Singleton instance.
	 *
	 * @var RM_WooCommerce_Integration
	 */
	private static $instance = null;

	/**
	 * Current country code.
	 *
	 * @var string
	 */
	private $current_country = null;

	/**
	 * Current region ID.
	 *
	 * @var int
	 */
	private $current_region_id = null;

	/**
	 * Current currency code.
	 *
	 * @var string
	 */
	private $current_currency = null;

	/**
	 * Get singleton instance.
	 *
	 * @return RM_WooCommerce_Integration
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
		// Load current region/country on init.
		add_action( 'init', array( $this, 'load_current_region' ) );

		// Hook into WooCommerce price filters.
		add_filter( 'woocommerce_product_get_price', array( $this, 'get_regional_price' ), 10, 2 );
		add_filter( 'woocommerce_product_get_regular_price', array( $this, 'get_regional_price' ), 10, 2 );
		add_filter( 'woocommerce_product_get_sale_price', array( $this, 'get_regional_sale_price' ), 10, 2 );

		// Variable product price filters.
		add_filter( 'woocommerce_product_variation_get_price', array( $this, 'get_regional_price' ), 10, 2 );
		add_filter( 'woocommerce_product_variation_get_regular_price', array( $this, 'get_regional_price' ), 10, 2 );
		add_filter( 'woocommerce_product_variation_get_sale_price', array( $this, 'get_regional_sale_price' ), 10, 2 );

		// Variation price hash for caching.
		add_filter( 'woocommerce_get_variation_prices_hash', array( $this, 'variation_prices_hash' ), 10, 3 );

		// Currency filter.
		add_filter( 'woocommerce_currency', array( $this, 'get_regional_currency' ) );

		// Product visibility filters.
		add_filter( 'woocommerce_product_is_visible', array( $this, 'filter_product_visibility' ), 10, 2 );
		add_filter( 'woocommerce_is_purchasable', array( $this, 'filter_product_purchasability' ), 10, 2 );

		// Cart integration.
		add_action( 'woocommerce_before_calculate_totals', array( $this, 'update_cart_prices' ), 10, 1 );

		// Order integration.
		add_action( 'woocommerce_checkout_create_order', array( $this, 'save_region_to_order' ), 10, 2 );
		add_action( 'woocommerce_checkout_create_order_line_item', array( $this, 'save_region_to_line_item' ), 10, 4 );

		// Admin order display.
		add_action( 'woocommerce_admin_order_data_after_billing_address', array( $this, 'display_order_region_info' ), 10, 1 );
		add_filter( 'woocommerce_order_item_get_formatted_meta_data', array( $this, 'format_order_item_meta' ), 10, 2 );
	}

	/**
	 * Load current region and country from session/cookie.
	 */
	public function load_current_region() {
		// Get country from session.
		if ( function_exists( 'WC' ) && WC()->session ) {
			$this->current_country = WC()->session->get( 'rm_current_country' );
		}

		// Fallback to cookie.
		if ( ! $this->current_country && isset( $_COOKIE['rm_country'] ) ) {
			$this->current_country = sanitize_text_field( $_COOKIE['rm_country'] );
		}

		if ( $this->current_country ) {
			// Get region ID and currency for this country.
			global $wpdb;
			$country_data = $wpdb->get_row(
				$wpdb->prepare(
					"SELECT region_id, currency_code FROM {$wpdb->prefix}rm_region_countries WHERE country_code = %s LIMIT 1",
					$this->current_country
				)
			); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching

			if ( $country_data ) {
				$this->current_region_id = intval( $country_data->region_id );
				$this->current_currency  = $country_data->currency_code;
			}
		}
	}

	/**
	 * Get regional price for a product.
	 *
	 * Priority: Country price > Region price > Base price.
	 *
	 * @param mixed      $price   Current price.
	 * @param WC_Product $product Product object.
	 * @return mixed Modified price.
	 */
	public function get_regional_price( $price, $product ) {
		if ( ! $this->current_country && ! $this->current_region_id ) {
			return $price;
		}

		$product_id = $product->get_id();

		// Check country-specific price first (highest priority).
		if ( $this->current_country ) {
			$country_price = $this->get_product_country_price( $product_id, $this->current_country, 'price' );
			if ( null !== $country_price ) {
				return $country_price;
			}
		}

		// Check region-level price.
		if ( $this->current_region_id ) {
			$region_price = $this->get_product_region_price( $product_id, $this->current_region_id, 'price_override' );
			if ( null !== $region_price ) {
				return $region_price;
			}
		}

		// Return base price.
		return $price;
	}

	/**
	 * Get regional sale price for a product.
	 *
	 * @param mixed      $sale_price Current sale price.
	 * @param WC_Product $product    Product object.
	 * @return mixed Modified sale price.
	 */
	public function get_regional_sale_price( $sale_price, $product ) {
		if ( ! $this->current_country && ! $this->current_region_id ) {
			return $sale_price;
		}

		$product_id = $product->get_id();

		// Check country-specific sale price first.
		if ( $this->current_country ) {
			$country_sale_price = $this->get_product_country_price( $product_id, $this->current_country, 'sale_price' );
			if ( null !== $country_sale_price ) {
				return $country_sale_price;
			}
		}

		// Check region-level sale price.
		if ( $this->current_region_id ) {
			$region_sale_price = $this->get_product_region_price( $product_id, $this->current_region_id, 'sale_price_override' );
			if ( null !== $region_sale_price ) {
				return $region_sale_price;
			}
		}

		// Return base sale price.
		return $sale_price;
	}

	/**
	 * Get product country-specific price.
	 *
	 * @param int    $product_id   Product ID.
	 * @param string $country_code Country code.
	 * @param string $field        Field name (price or sale_price).
	 * @return float|null Price or null.
	 */
	private function get_product_country_price( $product_id, $country_code, $field ) {
		global $wpdb;

		static $cache = array();
		$cache_key = $product_id . '_' . $country_code . '_' . $field;

		if ( isset( $cache[ $cache_key ] ) ) {
			return $cache[ $cache_key ];
		}

		$result = $wpdb->get_var(
			$wpdb->prepare(
				"SELECT {$field} FROM {$wpdb->prefix}rm_product_country_prices WHERE product_id = %d AND country_code = %s LIMIT 1",
				$product_id,
				$country_code
			)
		); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching

		$cache[ $cache_key ] = $result;
		return $result;
	}

	/**
	 * Get product region-level price.
	 *
	 * @param int    $product_id Product ID.
	 * @param int    $region_id  Region ID.
	 * @param string $field      Field name (price_override or sale_price_override).
	 * @return float|null Price or null.
	 */
	private function get_product_region_price( $product_id, $region_id, $field ) {
		global $wpdb;

		static $cache = array();
		$cache_key = $product_id . '_' . $region_id . '_' . $field;

		if ( isset( $cache[ $cache_key ] ) ) {
			return $cache[ $cache_key ];
		}

		$result = $wpdb->get_var(
			$wpdb->prepare(
				"SELECT {$field} FROM {$wpdb->prefix}rm_product_regions WHERE product_id = %d AND region_id = %d LIMIT 1",
				$product_id,
				$region_id
			)
		); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching

		$cache[ $cache_key ] = $result;
		return $result;
	}

	/**
	 * Add region/country info to variation prices hash for proper caching.
	 *
	 * @param array      $hash    Current hash.
	 * @param WC_Product $product Product object.
	 * @param bool       $display Display context.
	 * @return array Modified hash.
	 */
	public function variation_prices_hash( $hash, $product, $display ) {
		$hash[] = $this->current_country;
		$hash[] = $this->current_region_id;
		$hash[] = $this->current_currency;
		return $hash;
	}

	/**
	 * Get regional currency.
	 *
	 * @param string $currency Current currency.
	 * @return string Modified currency.
	 */
	public function get_regional_currency( $currency ) {
		if ( $this->current_currency ) {
			return $this->current_currency;
		}
		return $currency;
	}

	/**
	 * Filter product visibility based on region availability.
	 *
	 * @param bool       $visible Current visibility.
	 * @param int|object $product Product ID or object.
	 * @return bool Modified visibility.
	 */
	public function filter_product_visibility( $visible, $product ) {
		if ( ! $visible ) {
			return $visible;
		}

		if ( ! $this->current_region_id ) {
			return $visible;
		}

		$product_id = is_object( $product ) ? $product->get_id() : $product;

		// Check if product is available in current region.
		return $this->is_product_available_in_region( $product_id, $this->current_region_id );
	}

	/**
	 * Filter product purchasability based on region availability.
	 *
	 * @param bool       $purchasable Current purchasability.
	 * @param WC_Product $product     Product object.
	 * @return bool Modified purchasability.
	 */
	public function filter_product_purchasability( $purchasable, $product ) {
		if ( ! $purchasable ) {
			return $purchasable;
		}

		if ( ! $this->current_region_id ) {
			return $purchasable;
		}

		$product_id = $product->get_id();

		// Check if product is available in current region.
		return $this->is_product_available_in_region( $product_id, $this->current_region_id );
	}

	/**
	 * Check if product is available in region.
	 *
	 * @param int $product_id Product ID.
	 * @param int $region_id  Region ID.
	 * @return bool True if available.
	 */
	private function is_product_available_in_region( $product_id, $region_id ) {
		global $wpdb;

		static $cache = array();
		$cache_key = $product_id . '_' . $region_id;

		if ( isset( $cache[ $cache_key ] ) ) {
			return $cache[ $cache_key ];
		}

		$exists = $wpdb->get_var(
			$wpdb->prepare(
				"SELECT COUNT(*) FROM {$wpdb->prefix}rm_product_regions WHERE product_id = %d AND region_id = %d AND is_available = 1",
				$product_id,
				$region_id
			)
		); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching

		$result                = $exists > 0;
		$cache[ $cache_key ] = $result;
		return $result;
	}

	/**
	 * Update cart item prices based on regional pricing.
	 *
	 * @param WC_Cart $cart Cart object.
	 */
	public function update_cart_prices( $cart ) {
		if ( did_action( 'woocommerce_before_calculate_totals' ) >= 2 ) {
			return;
		}

		foreach ( $cart->get_cart() as $cart_item_key => $cart_item ) {
			$product = $cart_item['data'];

			if ( ! $product ) {
				continue;
			}

			$product_id = $product->get_id();

			// Get regional price.
			$regional_price = null;

			// Country-specific price has highest priority.
			if ( $this->current_country ) {
				$regional_price = $this->get_product_country_price( $product_id, $this->current_country, 'price' );

				// Check for sale price.
				if ( null === $regional_price ) {
					$regional_price = $this->get_product_country_price( $product_id, $this->current_country, 'sale_price' );
				}
			}

			// Region-level price.
			if ( null === $regional_price && $this->current_region_id ) {
				$regional_price = $this->get_product_region_price( $product_id, $this->current_region_id, 'price_override' );

				// Check for sale price.
				if ( null === $regional_price ) {
					$regional_price = $this->get_product_region_price( $product_id, $this->current_region_id, 'sale_price_override' );
				}
			}

			// Set price if we have a regional override.
			if ( null !== $regional_price ) {
				$product->set_price( $regional_price );
			}
		}
	}

	/**
	 * Save region/country/currency info to order meta.
	 *
	 * @param WC_Order $order Order object.
	 * @param array    $data  Checkout data.
	 */
	public function save_region_to_order( $order, $data ) {
		if ( $this->current_country ) {
			$order->update_meta_data( '_rm_country', $this->current_country );
		}

		if ( $this->current_region_id ) {
			$order->update_meta_data( '_rm_region_id', $this->current_region_id );
		}

		if ( $this->current_currency ) {
			$order->update_meta_data( '_rm_currency', $this->current_currency );
		}
	}

	/**
	 * Save region/country info to order line item meta.
	 *
	 * @param WC_Order_Item_Product $item          Order item.
	 * @param string                $cart_item_key Cart item key.
	 * @param array                 $values        Cart item values.
	 * @param WC_Order              $order         Order object.
	 */
	public function save_region_to_line_item( $item, $cart_item_key, $values, $order ) {
		if ( $this->current_country ) {
			$item->add_meta_data( '_rm_country', $this->current_country, true );
		}

		if ( $this->current_region_id ) {
			$item->add_meta_data( '_rm_region_id', $this->current_region_id, true );
		}

		if ( $this->current_currency ) {
			$item->add_meta_data( '_rm_currency', $this->current_currency, true );
		}
	}

	/**
	 * Get current country code.
	 *
	 * @return string|null Country code.
	 */
	public function get_current_country() {
		return $this->current_country;
	}

	/**
	 * Get current region ID.
	 *
	 * @return int|null Region ID.
	 */
	public function get_current_region_id() {
		return $this->current_region_id;
	}

	/**
	 * Get current currency.
	 *
	 * @return string|null Currency code.
	 */
	public function get_current_currency() {
		return $this->current_currency;
	}

	/**
	 * Display regional information on admin order page.
	 *
	 * @param WC_Order $order Order object.
	 */
	public function display_order_region_info( $order ) {
		$region_id = $order->get_meta( '_rm_region_id' );
		$country   = $order->get_meta( '_rm_country' );
		$currency  = $order->get_meta( '_rm_currency' );

		if ( ! $region_id && ! $country ) {
			return;
		}

		?>
		<div class="rm-order-region-info" style="margin-top: 15px; padding: 12px; background: #f0f6fc; border-left: 4px solid #2271b1;">
			<h3 style="margin: 0 0 10px 0; color: #2271b1;">
				<?php esc_html_e( 'Regional Pricing Information', 'region-manager' ); ?>
			</h3>

			<?php if ( $region_id ) : ?>
				<?php
				global $wpdb;
				$region = $wpdb->get_row(
					$wpdb->prepare(
						"SELECT * FROM {$wpdb->prefix}rm_regions WHERE id = %d",
						$region_id
					)
				); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
				?>
				<p style="margin: 5px 0;">
					<strong><?php esc_html_e( 'Region:', 'region-manager' ); ?></strong>
					<?php echo $region ? esc_html( $region->name ) : esc_html__( 'Unknown', 'region-manager' ); ?>
				</p>
			<?php endif; ?>

			<?php if ( $country ) : ?>
				<p style="margin: 5px 0;">
					<strong><?php esc_html_e( 'Country:', 'region-manager' ); ?></strong>
					<?php
					if ( function_exists( 'WC' ) && WC()->countries ) {
						$countries    = WC()->countries->get_countries();
						$country_name = isset( $countries[ $country ] ) ? $countries[ $country ] : $country;
						echo esc_html( $country_name ) . ' (' . esc_html( $country ) . ')';
					} else {
						echo esc_html( $country );
					}
					?>
				</p>
			<?php endif; ?>

			<?php if ( $currency ) : ?>
				<p style="margin: 5px 0;">
					<strong><?php esc_html_e( 'Currency:', 'region-manager' ); ?></strong>
					<?php
					$symbol = get_woocommerce_currency_symbol( $currency );
					echo esc_html( $currency ) . ' (' . esc_html( $symbol ) . ')';
					?>
				</p>
			<?php endif; ?>
		</div>
		<?php
	}

	/**
	 * Format order item meta display (hide internal region meta from customers).
	 *
	 * @param array             $formatted_meta Formatted meta data.
	 * @param WC_Order_Item $item Order item.
	 * @return array Modified meta data.
	 */
	public function format_order_item_meta( $formatted_meta, $item ) {
		// Remove internal region meta from customer view.
		$internal_keys = array( '_rm_country', '_rm_region_id', '_rm_currency' );

		foreach ( $formatted_meta as $key => $meta ) {
			if ( in_array( $meta->key, $internal_keys, true ) ) {
				unset( $formatted_meta[ $key ] );
			}
		}

		return $formatted_meta;
	}
}
