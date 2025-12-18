<?php
/**
 * The products management functionality of the plugin.
 *
 * @package    Region_Manager
 * @subpackage Region_Manager/admin
 */

/**
 * The products management functionality of the plugin.
 *
 * Handles product-region assignments, pricing overrides, and bulk operations.
 */
class RM_Products {

	/**
	 * Display the products page.
	 */
	public function display() {
		// Check user capabilities.
		if ( ! current_user_can( 'manage_woocommerce' ) ) {
			wp_die( esc_html__( 'You do not have sufficient permissions to access this page.', 'region-manager' ) );
		}

		require_once RM_PLUGIN_DIR . 'admin/partials/products-display.php';
	}

	/**
	 * Register AJAX handlers.
	 */
	public function register_ajax_handlers() {
		add_action( 'wp_ajax_rm_save_product_regions', array( $this, 'ajax_save_product_regions' ) );
		add_action( 'wp_ajax_rm_bulk_assign_region', array( $this, 'ajax_bulk_assign_region' ) );
		add_action( 'wp_ajax_rm_toggle_product_availability', array( $this, 'ajax_toggle_product_availability' ) );
		add_action( 'wp_ajax_rm_get_product_region_data', array( $this, 'ajax_get_product_region_data' ) );
		add_action( 'wp_ajax_rm_get_products_table', array( $this, 'ajax_get_products_table' ) );
	}

	/**
	 * Get products with region data.
	 *
	 * @param array $args Query arguments.
	 * @return array Array of products with region data.
	 */
	public function get_products_with_regions( $args = array() ) {
		global $wpdb;

		$defaults = array(
			'limit'     => 20,
			'page'      => 1,
			'search'    => '',
			'region_id' => null,
			'orderby'   => 'title',
			'order'     => 'ASC',
		);

		$args = wp_parse_args( $args, $defaults );

		// Base WC product query.
		$query_args = array(
			'limit'   => $args['limit'],
			'page'    => $args['page'],
			'orderby' => $args['orderby'],
			'order'   => $args['order'],
			'return'  => 'ids',
		);

		// Add search.
		if ( ! empty( $args['search'] ) ) {
			$query_args['s'] = $args['search'];
		}

		// Get products.
		$product_ids = wc_get_products( $query_args );
		$products    = array();

		foreach ( $product_ids as $product_id ) {
			$product = wc_get_product( $product_id );
			if ( ! $product ) {
				continue;
			}

			$product_data = array(
				'id'           => $product_id,
				'name'         => $product->get_name(),
				'sku'          => $product->get_sku(),
				'image'        => $product->get_image( 'thumbnail' ),
				'base_price'   => $product->get_regular_price(),
				'sale_price'   => $product->get_sale_price(),
				'price'        => $product->get_price(),
				'stock_status' => $product->get_stock_status(),
				'type'         => $product->get_type(),
				'regions'      => $this->get_product_regions( $product_id ),
			);

			// Filter by region if specified.
			if ( null !== $args['region_id'] ) {
				$region_data = $this->get_product_region_single( $product_id, $args['region_id'] );
				if ( $region_data ) {
					$product_data['regional_price']     = $region_data['price_override'] ?? $product_data['base_price'];
					$product_data['regional_available'] = true;
				} else {
					// Skip if filtering by region and not available.
					continue;
				}
			}

			$products[] = $product_data;
		}

		return $products;
	}

	/**
	 * Get total products count.
	 *
	 * @param array $args Query arguments.
	 * @return int Total count.
	 */
	public function get_products_count( $args = array() ) {
		$defaults = array(
			'search'    => '',
			'region_id' => null,
		);

		$args = wp_parse_args( $args, $defaults );

		$query_args = array(
			'limit'  => -1,
			'return' => 'ids',
		);

		if ( ! empty( $args['search'] ) ) {
			$query_args['s'] = $args['search'];
		}

		$product_ids = wc_get_products( $query_args );

		// Filter by region if needed.
		if ( null !== $args['region_id'] ) {
			$filtered_ids = array();
			foreach ( $product_ids as $product_id ) {
				if ( $this->get_product_region_single( $product_id, $args['region_id'] ) ) {
					$filtered_ids[] = $product_id;
				}
			}
			return count( $filtered_ids );
		}

		return count( $product_ids );
	}

	/**
	 * Get all regions for a product.
	 *
	 * @param int $product_id Product ID.
	 * @return array Array of region data.
	 */
	public function get_product_regions( $product_id ) {
		global $wpdb;

		$table_name = $wpdb->prefix . 'rm_product_regions';

		$results = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT pr.*, r.name, r.slug
				FROM {$table_name} pr
				JOIN {$wpdb->prefix}rm_regions r ON pr.region_id = r.id
				WHERE pr.product_id = %d
				ORDER BY r.name ASC",
				$product_id
			),
			ARRAY_A
		);

		return $results ? $results : array();
	}

	/**
	 * Get single product-region data.
	 *
	 * @param int $product_id Product ID.
	 * @param int $region_id Region ID.
	 * @return array|null Region data or null.
	 */
	public function get_product_region_single( $product_id, $region_id ) {
		global $wpdb;

		$table_name = $wpdb->prefix . 'rm_product_regions';

		$result = $wpdb->get_row(
			$wpdb->prepare(
				"SELECT * FROM {$table_name} WHERE product_id = %d AND region_id = %d",
				$product_id,
				$region_id
			),
			ARRAY_A
		);

		return $result;
	}

	/**
	 * Get regional price for a product.
	 *
	 * @param int $product_id Product ID.
	 * @param int $region_id Region ID.
	 * @return string|null Price or null.
	 */
	public function get_regional_price( $product_id, $region_id ) {
		$product = wc_get_product( $product_id );
		if ( ! $product ) {
			return null;
		}

		$region_data = $this->get_product_region_single( $product_id, $region_id );

		// Return override price if exists.
		if ( $region_data && ! empty( $region_data['price_override'] ) ) {
			return $region_data['price_override'];
		}

		// Return base price.
		return $product->get_price();
	}

	/**
	 * Save product-region assignment.
	 *
	 * @param int   $product_id Product ID.
	 * @param int   $region_id Region ID.
	 * @param array $data Region assignment data.
	 * @return bool Success status.
	 */
	public function save_product_region( $product_id, $region_id, $data = array() ) {
		global $wpdb;

		$table_name = $wpdb->prefix . 'rm_product_regions';

		$defaults = array(
			'price_override'      => null,
			'sale_price_override' => null,
		);

		$data = wp_parse_args( $data, $defaults );

		// Check if assignment exists.
		$exists = $this->get_product_region_single( $product_id, $region_id );

		if ( $exists ) {
			// Update existing.
			$result = $wpdb->update(
				$table_name,
				array(
					'price_override'      => $data['price_override'],
					'sale_price_override' => $data['sale_price_override'],
				),
				array(
					'product_id' => $product_id,
					'region_id'  => $region_id,
				),
				array( '%s', '%s' ),
				array( '%d', '%d' )
			);

			return false !== $result;
		} else {
			// Insert new.
			$result = $wpdb->insert(
				$table_name,
				array(
					'product_id'          => $product_id,
					'region_id'           => $region_id,
					'price_override'      => $data['price_override'],
					'sale_price_override' => $data['sale_price_override'],
				),
				array( '%d', '%d', '%s', '%s' )
			);

			return false !== $result;
		}
	}

	/**
	 * Remove product-region assignment.
	 *
	 * @param int $product_id Product ID.
	 * @param int $region_id Region ID.
	 * @return bool Success status.
	 */
	public function remove_product_region( $product_id, $region_id ) {
		global $wpdb;

		$table_name = $wpdb->prefix . 'rm_product_regions';

		$result = $wpdb->delete(
			$table_name,
			array(
				'product_id' => $product_id,
				'region_id'  => $region_id,
			),
			array( '%d', '%d' )
		);

		return false !== $result;
	}

	/**
	 * Bulk assign products to region.
	 *
	 * @param array $product_ids Array of product IDs.
	 * @param int   $region_id Region ID.
	 * @param array $data Assignment data.
	 * @return int Number of products assigned.
	 */
	public function bulk_assign_region( $product_ids, $region_id, $data = array() ) {
		$count = 0;

		foreach ( $product_ids as $product_id ) {
			if ( $this->save_product_region( $product_id, $region_id, $data ) ) {
				++$count;

				// Apply to variations if requested.
				if ( ! empty( $data['apply_to_variations'] ) ) {
					$product = wc_get_product( $product_id );
					if ( $product && $product->is_type( 'variable' ) ) {
						$variations = $product->get_children();
						foreach ( $variations as $variation_id ) {
							$this->save_product_region( $variation_id, $region_id, $data );
						}
					}
				}
			}
		}

		return $count;
	}

	/**
	 * AJAX: Save product regions.
	 */
	public function ajax_save_product_regions() {
		check_ajax_referer( 'rm_admin_nonce', 'nonce' );

		if ( ! current_user_can( 'manage_woocommerce' ) ) {
			wp_send_json_error( array( 'message' => __( 'Permission denied.', 'region-manager' ) ) );
		}

		$product_id = isset( $_POST['product_id'] ) ? absint( $_POST['product_id'] ) : 0;
		$regions    = isset( $_POST['regions'] ) ? json_decode( wp_unslash( $_POST['regions'] ), true ) : array(); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized

		if ( ! $product_id ) {
			wp_send_json_error( array( 'message' => __( 'Invalid product ID.', 'region-manager' ) ) );
		}

		// Ensure $regions is an array (json_decode can return null on invalid JSON).
		if ( ! is_array( $regions ) ) {
			$regions = array();
		}

		// Get all existing regions.
		global $wpdb;
		$all_regions = $wpdb->get_results( "SELECT id FROM {$wpdb->prefix}rm_regions WHERE status = 'active'", ARRAY_A );

		$success_count = 0;

		// Process each region.
		foreach ( $all_regions as $region ) {
			$region_id = $region['id'];
			$region_data = null;

			// Find region data in submitted data.
			foreach ( $regions as $submitted_region ) {
				if ( absint( $submitted_region['region_id'] ) === $region_id ) {
					$region_data = $submitted_region;
					break;
				}
			}

			if ( $region_data && ! empty( $region_data['available'] ) ) {
				// Save/update assignment.
				$data = array(
					'price_override'      => ! empty( $region_data['price_override'] ) ? sanitize_text_field( $region_data['price_override'] ) : null,
					'sale_price_override' => ! empty( $region_data['sale_price_override'] ) ? sanitize_text_field( $region_data['sale_price_override'] ) : null,
				);

				if ( $this->save_product_region( $product_id, $region_id, $data ) ) {
					++$success_count;
				}

				// Apply to variations if requested.
				if ( ! empty( $region_data['apply_to_variations'] ) ) {
					$product = wc_get_product( $product_id );
					if ( $product && $product->is_type( 'variable' ) ) {
						$variations = $product->get_children();
						foreach ( $variations as $variation_id ) {
							$this->save_product_region( $variation_id, $region_id, $data );
						}
					}
				}
			} else {
				// Remove assignment if not available.
				$this->remove_product_region( $product_id, $region_id );
			}
		}

		wp_send_json_success(
			array(
				'message' => sprintf(
					/* translators: %d: number of regions */
					_n( 'Product updated for %d region.', 'Product updated for %d regions.', $success_count, 'region-manager' ),
					$success_count
				),
			)
		);
	}

	/**
	 * AJAX: Bulk assign region.
	 */
	public function ajax_bulk_assign_region() {
		check_ajax_referer( 'rm_admin_nonce', 'nonce' );

		if ( ! current_user_can( 'manage_woocommerce' ) ) {
			wp_send_json_error( array( 'message' => __( 'Permission denied.', 'region-manager' ) ) );
		}

		$product_ids = isset( $_POST['product_ids'] ) ? array_map( 'absint', $_POST['product_ids'] ) : array();
		$region_id   = isset( $_POST['region_id'] ) ? absint( $_POST['region_id'] ) : 0;
		$action_type = isset( $_POST['action_type'] ) ? sanitize_text_field( $_POST['action_type'] ) : '';

		if ( empty( $product_ids ) || ! $region_id ) {
			wp_send_json_error( array( 'message' => __( 'Invalid data.', 'region-manager' ) ) );
		}

		if ( 'assign' === $action_type ) {
			$data = array(
				'price_override'        => isset( $_POST['price_override'] ) ? sanitize_text_field( $_POST['price_override'] ) : null,
				'sale_price_override'   => isset( $_POST['sale_price_override'] ) ? sanitize_text_field( $_POST['sale_price_override'] ) : null,
				'apply_to_variations'   => isset( $_POST['apply_to_variations'] ) && '1' === $_POST['apply_to_variations'],
			);

			$count = $this->bulk_assign_region( $product_ids, $region_id, $data );

			wp_send_json_success(
				array(
					'message' => sprintf(
						/* translators: %d: number of products */
						_n( '%d product assigned to region.', '%d products assigned to region.', $count, 'region-manager' ),
						$count
					),
				)
			);
		} elseif ( 'remove' === $action_type ) {
			$count = 0;
			foreach ( $product_ids as $product_id ) {
				if ( $this->remove_product_region( $product_id, $region_id ) ) {
					++$count;
				}
			}

			wp_send_json_success(
				array(
					'message' => sprintf(
						/* translators: %d: number of products */
						_n( '%d product removed from region.', '%d products removed from region.', $count, 'region-manager' ),
						$count
					),
				)
			);
		} else {
			wp_send_json_error( array( 'message' => __( 'Invalid action.', 'region-manager' ) ) );
		}
	}

	/**
	 * AJAX: Toggle product availability.
	 */
	public function ajax_toggle_product_availability() {
		check_ajax_referer( 'rm_admin_nonce', 'nonce' );

		if ( ! current_user_can( 'manage_woocommerce' ) ) {
			wp_send_json_error( array( 'message' => __( 'Permission denied.', 'region-manager' ) ) );
		}

		$product_id = isset( $_POST['product_id'] ) ? absint( $_POST['product_id'] ) : 0;
		$region_id  = isset( $_POST['region_id'] ) ? absint( $_POST['region_id'] ) : 0;
		$available  = isset( $_POST['available'] ) && '1' === $_POST['available'];

		if ( ! $product_id || ! $region_id ) {
			wp_send_json_error( array( 'message' => __( 'Invalid data.', 'region-manager' ) ) );
		}

		if ( $available ) {
			// Add to region with default pricing.
			$result = $this->save_product_region( $product_id, $region_id );
		} else {
			// Remove from region.
			$result = $this->remove_product_region( $product_id, $region_id );
		}

		if ( $result ) {
			wp_send_json_success( array( 'message' => __( 'Availability updated.', 'region-manager' ) ) );
		} else {
			wp_send_json_error( array( 'message' => __( 'Failed to update availability.', 'region-manager' ) ) );
		}
	}

	/**
	 * AJAX: Get product region data.
	 */
	public function ajax_get_product_region_data() {
		check_ajax_referer( 'rm_admin_nonce', 'nonce' );

		if ( ! current_user_can( 'manage_woocommerce' ) ) {
			wp_send_json_error( array( 'message' => __( 'Permission denied.', 'region-manager' ) ) );
		}

		$product_id = isset( $_POST['product_id'] ) ? absint( $_POST['product_id'] ) : 0;

		if ( ! $product_id ) {
			wp_send_json_error( array( 'message' => __( 'Invalid product ID.', 'region-manager' ) ) );
		}

		$product = wc_get_product( $product_id );
		if ( ! $product ) {
			wp_send_json_error( array( 'message' => __( 'Product not found.', 'region-manager' ) ) );
		}

		// Get all active regions.
		global $wpdb;
		$regions = $wpdb->get_results( "SELECT * FROM {$wpdb->prefix}rm_regions WHERE status = 'active' ORDER BY name ASC", ARRAY_A );

		// Get product regions.
		$product_regions = $this->get_product_regions( $product_id );

		// Map product regions by region ID.
		$region_map = array();
		foreach ( $product_regions as $pr ) {
			$region_map[ $pr['region_id'] ] = $pr;
		}

		// Build response.
		$region_data = array();
		foreach ( $regions as $region ) {
			$region_id = $region['id'];
			$assigned  = isset( $region_map[ $region_id ] );

			$region_data[] = array(
				'region_id'           => $region_id,
				'region_name'         => $region['name'],
				'available'           => $assigned,
				'price_override'      => $assigned ? $region_map[ $region_id ]['price_override'] : '',
				'sale_price_override' => $assigned ? $region_map[ $region_id ]['sale_price_override'] : '',
			);
		}

		wp_send_json_success(
			array(
				'product' => array(
					'id'         => $product_id,
					'name'       => $product->get_name(),
					'image'      => $product->get_image( 'thumbnail' ),
					'base_price' => $product->get_regular_price(),
					'sale_price' => $product->get_sale_price(),
					'type'       => $product->get_type(),
				),
				'regions' => $region_data,
			)
		);
	}

	/**
	 * AJAX: Get products table.
	 */
	public function ajax_get_products_table() {
		check_ajax_referer( 'rm_admin_nonce', 'nonce' );

		if ( ! current_user_can( 'manage_woocommerce' ) ) {
			wp_send_json_error( array( 'message' => __( 'Permission denied.', 'region-manager' ) ) );
		}

		$page      = isset( $_POST['page'] ) ? absint( $_POST['page'] ) : 1;
		$per_page  = isset( $_POST['per_page'] ) ? absint( $_POST['per_page'] ) : 20;
		$search    = isset( $_POST['search'] ) ? sanitize_text_field( $_POST['search'] ) : '';
		$region_id = isset( $_POST['region_id'] ) ? absint( $_POST['region_id'] ) : null;
		$orderby   = isset( $_POST['orderby'] ) ? sanitize_text_field( $_POST['orderby'] ) : 'title';
		$order     = isset( $_POST['order'] ) ? sanitize_text_field( $_POST['order'] ) : 'ASC';

		$args = array(
			'limit'     => $per_page,
			'page'      => $page,
			'search'    => $search,
			'region_id' => $region_id,
			'orderby'   => $orderby,
			'order'     => $order,
		);

		$products = $this->get_products_with_regions( $args );
		$total    = $this->get_products_count( array( 'search' => $search, 'region_id' => $region_id ) );

		wp_send_json_success(
			array(
				'products'   => $products,
				'total'      => $total,
				'total_pages' => ceil( $total / $per_page ),
			)
		);
	}
}
