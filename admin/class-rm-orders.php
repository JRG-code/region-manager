<?php
/**
 * The orders management functionality of the plugin.
 *
 * @package    Region_Manager
 * @subpackage Region_Manager/admin
 */

/**
 * The orders management functionality of the plugin.
 *
 * Handles order-region tracking, status updates, and cross-region order detection.
 */
class RM_Orders {

	/**
	 * Initialize the class.
	 */
	public function __construct() {
		// Register WooCommerce hooks.
		add_action( 'woocommerce_checkout_order_processed', array( $this, 'save_order_region' ), 10, 1 );
		add_action( 'woocommerce_order_status_changed', array( $this, 'log_status_change' ), 10, 4 );
	}

	/**
	 * Display the orders page.
	 */
	public function display() {
		// Check user capabilities.
		if ( ! current_user_can( 'manage_woocommerce' ) ) {
			wp_die( esc_html__( 'You do not have sufficient permissions to access this page.', 'region-manager' ) );
		}

		require_once RM_PLUGIN_DIR . 'admin/partials/orders-display.php';
	}

	/**
	 * Register AJAX handlers.
	 */
	public function register_ajax_handlers() {
		add_action( 'wp_ajax_rm_update_order_status', array( $this, 'ajax_update_order_status' ) );
		add_action( 'wp_ajax_rm_bulk_update_orders', array( $this, 'ajax_bulk_update_orders' ) );
		add_action( 'wp_ajax_rm_get_order_details', array( $this, 'ajax_get_order_details' ) );
		add_action( 'wp_ajax_rm_export_orders_csv', array( $this, 'ajax_export_orders_csv' ) );
		add_action( 'wp_ajax_rm_get_orders_table', array( $this, 'ajax_get_orders_table' ) );
	}

	/**
	 * Save region ID to order meta on checkout.
	 *
	 * @param int $order_id Order ID.
	 */
	public function save_order_region( $order_id ) {
		// Get current region from session or detection logic.
		// This is a placeholder - you would implement region detection based on:
		// - Customer's billing/shipping country
		// - Session data
		// - URL parameters
		// - Cookie data

		// For now, we'll try to determine region from shipping country.
		$order = wc_get_order( $order_id );
		if ( ! $order ) {
			return;
		}

		$shipping_country = $order->get_shipping_country();
		$region_id        = $this->detect_region_from_country( $shipping_country );

		if ( $region_id ) {
			$order->update_meta_data( '_rm_region_id', $region_id );
			$order->save();
		}
	}

	/**
	 * Detect region from country code.
	 *
	 * @param string $country_code Country code.
	 * @return int|null Region ID or null.
	 */
	private function detect_region_from_country( $country_code ) {
		global $wpdb;

		if ( empty( $country_code ) ) {
			return null;
		}

		$region_id = $wpdb->get_var(
			$wpdb->prepare(
				"SELECT region_id FROM {$wpdb->prefix}rm_region_countries WHERE country_code = %s LIMIT 1",
				$country_code
			)
		);

		return $region_id ? (int) $region_id : null;
	}

	/**
	 * Log order status changes.
	 *
	 * @param int      $order_id Order ID.
	 * @param string   $from_status Old status.
	 * @param string   $to_status New status.
	 * @param WC_Order $order Order object.
	 */
	public function log_status_change( $order_id, $from_status, $to_status, $order ) {
		$order->add_order_note(
			sprintf(
				/* translators: 1: old status, 2: new status */
				__( 'Order status changed from %1$s to %2$s.', 'region-manager' ),
				wc_get_order_status_name( $from_status ),
				wc_get_order_status_name( $to_status )
			)
		);

		// Log for debugging.
		if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
			error_log( sprintf( 'Order #%d status changed: %s -> %s', $order_id, $from_status, $to_status ) );
		}
	}

	/**
	 * Get orders by region.
	 *
	 * @param int   $region_id Region ID (null for all).
	 * @param array $args Query arguments.
	 * @return array Array of orders.
	 */
	public function get_orders_by_region( $region_id = null, $args = array() ) {
		$defaults = array(
			'limit'       => 20,
			'page'        => 1,
			'status'      => 'any',
			'search'      => '',
			'date_after'  => null,
			'date_before' => null,
			'orderby'     => 'date',
			'order'       => 'DESC',
		);

		$args = wp_parse_args( $args, $defaults );

		// Build WC order query.
		$query_args = array(
			'limit'   => $args['limit'],
			'page'    => $args['page'],
			'orderby' => $args['orderby'],
			'order'   => $args['order'],
		);

		// Add status filter.
		if ( 'any' !== $args['status'] ) {
			$query_args['status'] = $args['status'];
		}

		// Add search.
		if ( ! empty( $args['search'] ) ) {
			$query_args['s'] = $args['search'];
		}

		// Add date filters.
		if ( ! empty( $args['date_after'] ) ) {
			$query_args['date_after'] = $args['date_after'];
		}
		if ( ! empty( $args['date_before'] ) ) {
			$query_args['date_before'] = $args['date_before'];
		}

		// Add region filter via meta query.
		if ( null !== $region_id ) {
			$query_args['meta_query'] = array(
				array(
					'key'   => '_rm_region_id',
					'value' => $region_id,
					'type'  => 'NUMERIC',
				),
			);
		}

		// Get orders.
		$orders = wc_get_orders( $query_args );

		// Batch load region data to avoid N+1 queries.
		$region_ids = array();
		$order_data = array();
		foreach ( $orders as $order ) {
			$region_id_for_order = $this->get_order_region( $order->get_id() );
			if ( $region_id_for_order ) {
				$region_ids[] = $region_id_for_order;
			}
			$order_data[ $order->get_id() ] = array(
				'order'     => $order,
				'region_id' => $region_id_for_order,
			);
		}

		// Batch load all regions at once.
		$regions_map = $this->get_regions_map( array_unique( $region_ids ) );

		// Batch load cross-region status.
		$cross_region_map = $this->get_cross_region_map( $order_data, $regions_map );

		// Format order data with cached region info.
		$results = array();
		foreach ( $order_data as $order_id => $data ) {
			$results[] = $this->format_order_data_optimized( $data['order'], $data['region_id'], $regions_map, $cross_region_map );
		}

		return $results;
	}

	/**
	 * Batch load regions map.
	 *
	 * @param array $region_ids Array of region IDs.
	 * @return array Map of region_id => region data.
	 */
	private function get_regions_map( $region_ids ) {
		if ( empty( $region_ids ) ) {
			return array();
		}

		global $wpdb;
		$placeholders = implode( ',', array_fill( 0, count( $region_ids ), '%d' ) );
		$query        = $wpdb->prepare(
			"SELECT id, name FROM {$wpdb->prefix}rm_regions WHERE id IN ($placeholders)", // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
			$region_ids
		);
		$regions = $wpdb->get_results( $query, ARRAY_A ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching

		$regions_map = array();
		foreach ( $regions as $region ) {
			$regions_map[ $region['id'] ] = $region;
		}

		return $regions_map;
	}

	/**
	 * Batch check cross-region orders.
	 *
	 * @param array $order_data Array of order data.
	 * @param array $regions_map Map of region data.
	 * @return array Map of order_id => is_cross_region.
	 */
	private function get_cross_region_map( $order_data, $regions_map ) {
		global $wpdb;
		$cross_region_map = array();

		// Build a query to check all region-country combinations at once.
		$region_ids       = array();
		$country_codes    = array();
		$order_region_map = array();

		foreach ( $order_data as $order_id => $data ) {
			$order      = $data['order'];
			$region_id  = $data['region_id'];
			$country    = $order->get_shipping_country();

			if ( $region_id && $country ) {
				$region_ids[]                   = $region_id;
				$country_codes[]                = $country;
				$order_region_map[ $order_id ]  = array(
					'region_id' => $region_id,
					'country'   => $country,
				);
			}
		}

		if ( empty( $order_region_map ) ) {
			return $cross_region_map;
		}

		// Get all region-country associations in one query.
		$unique_regions   = array_unique( $region_ids );
		$unique_countries = array_unique( $country_codes );

		if ( ! empty( $unique_regions ) && ! empty( $unique_countries ) ) {
			$region_placeholders  = implode( ',', array_fill( 0, count( $unique_regions ), '%d' ) );
			$country_placeholders = implode( ',', array_fill( 0, count( $unique_countries ), '%s' ) );

			$query = $wpdb->prepare(
				"SELECT region_id, country_code FROM {$wpdb->prefix}rm_region_countries WHERE region_id IN ($region_placeholders) AND country_code IN ($country_placeholders)", // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
				array_merge( $unique_regions, $unique_countries )
			);

			$region_countries = $wpdb->get_results( $query, ARRAY_A ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching

			// Build a lookup map.
			$region_country_lookup = array();
			foreach ( $region_countries as $rc ) {
				$region_country_lookup[ $rc['region_id'] . '_' . $rc['country_code'] ] = true;
			}

			// Check each order.
			foreach ( $order_region_map as $order_id => $info ) {
				$key = $info['region_id'] . '_' . $info['country'];
				// If the combination is NOT in the lookup, it's cross-region.
				$cross_region_map[ $order_id ] = ! isset( $region_country_lookup[ $key ] );
			}
		}

		return $cross_region_map;
	}

	/**
	 * Format order data for display (optimized version).
	 *
	 * @param WC_Order $order Order object.
	 * @param int|null $region_id Region ID.
	 * @param array    $regions_map Preloaded regions map.
	 * @param array    $cross_region_map Preloaded cross-region status map.
	 * @return array Formatted order data.
	 */
	private function format_order_data_optimized( $order, $region_id, $regions_map, $cross_region_map ) {
		$region_name  = '';
		$cross_region = false;

		if ( $region_id && isset( $regions_map[ $region_id ] ) ) {
			$region_name = $regions_map[ $region_id ]['name'];
		}

		if ( isset( $cross_region_map[ $order->get_id() ] ) ) {
			$cross_region = $cross_region_map[ $order->get_id() ];
		}

		return array(
			'id'               => $order->get_id(),
			'order_number'     => $order->get_order_number(),
			'date'             => $order->get_date_created()->format( 'Y-m-d H:i:s' ),
			'customer_name'    => $order->get_billing_first_name() . ' ' . $order->get_billing_last_name(),
			'customer_country' => $order->get_billing_country(),
			'shipping_country' => $order->get_shipping_country(),
			'region_id'        => $region_id,
			'region_name'      => $region_name,
			'items_count'      => $order->get_item_count(),
			'total'            => $order->get_total(),
			'currency'         => $order->get_currency(),
			'status'           => $order->get_status(),
			'cross_region'     => $cross_region,
			'edit_url'         => $order->get_edit_order_url(),
		);
	}

	/**
	 * Format order data for display.
	 *
	 * @param WC_Order $order Order object.
	 * @return array Formatted order data.
	 */
	private function format_order_data( $order ) {
		$region_id      = $this->get_order_region( $order->get_id() );
		$region_name    = '';
		$cross_region   = false;

		if ( $region_id ) {
			global $wpdb;
			$region = $wpdb->get_row(
				$wpdb->prepare(
					"SELECT name FROM {$wpdb->prefix}rm_regions WHERE id = %d",
					$region_id
				)
			);
			$region_name = $region ? $region->name : '';

			// Check if cross-region order.
			$cross_region = $this->check_cross_region_order( $order );
		}

		return array(
			'id'               => $order->get_id(),
			'order_number'     => $order->get_order_number(),
			'date'             => $order->get_date_created()->format( 'Y-m-d H:i:s' ),
			'customer_name'    => $order->get_billing_first_name() . ' ' . $order->get_billing_last_name(),
			'customer_country' => $order->get_billing_country(),
			'shipping_country' => $order->get_shipping_country(),
			'region_id'        => $region_id,
			'region_name'      => $region_name,
			'items_count'      => $order->get_item_count(),
			'total'            => $order->get_total(),
			'currency'         => $order->get_currency(),
			'status'           => $order->get_status(),
			'cross_region'     => $cross_region,
			'edit_url'         => $order->get_edit_order_url(),
		);
	}

	/**
	 * Get total orders count.
	 *
	 * @param int   $region_id Region ID (null for all).
	 * @param array $args Query arguments.
	 * @return int Total count.
	 */
	public function get_orders_count( $region_id = null, $args = array() ) {
		$defaults = array(
			'status'      => 'any',
			'search'      => '',
			'date_after'  => null,
			'date_before' => null,
		);

		$args = wp_parse_args( $args, $defaults );

		$query_args = array(
			'limit'  => -1,
			'return' => 'ids',
		);

		if ( 'any' !== $args['status'] ) {
			$query_args['status'] = $args['status'];
		}

		if ( ! empty( $args['search'] ) ) {
			$query_args['s'] = $args['search'];
		}

		if ( ! empty( $args['date_after'] ) ) {
			$query_args['date_after'] = $args['date_after'];
		}
		if ( ! empty( $args['date_before'] ) ) {
			$query_args['date_before'] = $args['date_before'];
		}

		if ( null !== $region_id ) {
			$query_args['meta_query'] = array(
				array(
					'key'   => '_rm_region_id',
					'value' => $region_id,
					'type'  => 'NUMERIC',
				),
			);
		}

		$orders = wc_get_orders( $query_args );
		return count( $orders );
	}

	/**
	 * Get order region ID.
	 *
	 * @param int $order_id Order ID.
	 * @return int|null Region ID or null.
	 */
	public function get_order_region( $order_id ) {
		$order = wc_get_order( $order_id );
		if ( ! $order ) {
			return null;
		}

		$region_id = $order->get_meta( '_rm_region_id' );
		return $region_id ? (int) $region_id : null;
	}

	/**
	 * Update order status.
	 *
	 * @param int    $order_id Order ID.
	 * @param string $status New status.
	 * @return bool Success status.
	 */
	public function update_order_status( $order_id, $status ) {
		$order = wc_get_order( $order_id );
		if ( ! $order ) {
			return false;
		}

		// Remove 'wc-' prefix if present.
		$status = str_replace( 'wc-', '', $status );

		$order->update_status( $status );
		return true;
	}

	/**
	 * Bulk update order statuses.
	 *
	 * @param array  $order_ids Array of order IDs.
	 * @param string $status New status.
	 * @return int Number of updated orders.
	 */
	public function bulk_update_status( $order_ids, $status ) {
		$count = 0;

		foreach ( $order_ids as $order_id ) {
			if ( $this->update_order_status( $order_id, $status ) ) {
				++$count;
			}
		}

		return $count;
	}

	/**
	 * Check if order is cross-region.
	 *
	 * @param WC_Order $order Order object.
	 * @return bool True if cross-region.
	 */
	public function check_cross_region_order( $order ) {
		global $wpdb;

		$region_id        = $this->get_order_region( $order->get_id() );
		$shipping_country = $order->get_shipping_country();

		if ( ! $region_id || ! $shipping_country ) {
			return false;
		}

		// Check if shipping country belongs to order's region.
		$country_in_region = $wpdb->get_var(
			$wpdb->prepare(
				"SELECT COUNT(*) FROM {$wpdb->prefix}rm_region_countries WHERE region_id = %d AND country_code = %s",
				$region_id,
				$shipping_country
			)
		);

		return ! $country_in_region;
	}

	/**
	 * Get orders statistics.
	 *
	 * @param int $region_id Region ID (null for all).
	 * @return array Statistics array.
	 */
	public function get_orders_stats( $region_id = null ) {
		$statuses = array( 'pending', 'processing', 'in-transit', 'on-hold', 'completed', 'cancelled', 'refunded', 'failed' );
		$stats    = array();

		foreach ( $statuses as $status ) {
			$count = $this->get_orders_count( $region_id, array( 'status' => $status ) );
			$stats[ $status ] = $count;
		}

		$stats['total'] = array_sum( $stats );

		return $stats;
	}

	/**
	 * AJAX: Update order status.
	 */
	public function ajax_update_order_status() {
		check_ajax_referer( 'rm_admin_nonce', 'nonce' );

		if ( ! current_user_can( 'manage_woocommerce' ) ) {
			wp_send_json_error( array( 'message' => __( 'Permission denied.', 'region-manager' ) ) );
		}

		$order_id = isset( $_POST['order_id'] ) ? absint( $_POST['order_id'] ) : 0;
		$status   = isset( $_POST['status'] ) ? sanitize_text_field( $_POST['status'] ) : '';

		if ( ! $order_id || ! $status ) {
			wp_send_json_error( array( 'message' => __( 'Invalid data.', 'region-manager' ) ) );
		}

		if ( $this->update_order_status( $order_id, $status ) ) {
			wp_send_json_success(
				array(
					'message' => __( 'Order status updated.', 'region-manager' ),
					'status'  => $status,
				)
			);
		} else {
			wp_send_json_error( array( 'message' => __( 'Failed to update order status.', 'region-manager' ) ) );
		}
	}

	/**
	 * AJAX: Bulk update orders.
	 */
	public function ajax_bulk_update_orders() {
		check_ajax_referer( 'rm_admin_nonce', 'nonce' );

		if ( ! current_user_can( 'manage_woocommerce' ) ) {
			wp_send_json_error( array( 'message' => __( 'Permission denied.', 'region-manager' ) ) );
		}

		$order_ids = isset( $_POST['order_ids'] ) ? array_map( 'absint', $_POST['order_ids'] ) : array();
		$status    = isset( $_POST['status'] ) ? sanitize_text_field( $_POST['status'] ) : '';

		if ( empty( $order_ids ) || ! $status ) {
			wp_send_json_error( array( 'message' => __( 'Invalid data.', 'region-manager' ) ) );
		}

		$count = $this->bulk_update_status( $order_ids, $status );

		wp_send_json_success(
			array(
				'message' => sprintf(
					/* translators: %d: number of orders */
					_n( '%d order updated.', '%d orders updated.', $count, 'region-manager' ),
					$count
				),
			)
		);
	}

	/**
	 * AJAX: Get order details.
	 */
	public function ajax_get_order_details() {
		check_ajax_referer( 'rm_admin_nonce', 'nonce' );

		if ( ! current_user_can( 'manage_woocommerce' ) ) {
			wp_send_json_error( array( 'message' => __( 'Permission denied.', 'region-manager' ) ) );
		}

		$order_id = isset( $_POST['order_id'] ) ? absint( $_POST['order_id'] ) : 0;

		if ( ! $order_id ) {
			wp_send_json_error( array( 'message' => __( 'Invalid order ID.', 'region-manager' ) ) );
		}

		$order = wc_get_order( $order_id );
		if ( ! $order ) {
			wp_send_json_error( array( 'message' => __( 'Order not found.', 'region-manager' ) ) );
		}

		// Get order items.
		$items = array();
		foreach ( $order->get_items() as $item ) {
			$product = $item->get_product();
			$items[] = array(
				'name'     => $item->get_name(),
				'quantity' => $item->get_quantity(),
				'total'    => $item->get_total(),
				'image'    => $product ? $product->get_image( 'thumbnail' ) : '',
			);
		}

		// Get shipping address.
		$shipping_address = $order->get_formatted_shipping_address();

		// Get order notes.
		$notes = wc_get_order_notes(
			array(
				'order_id' => $order_id,
				'limit'    => 5,
			)
		);

		$formatted_notes = array();
		foreach ( $notes as $note ) {
			$formatted_notes[] = array(
				'date'    => $note->date_created->format( 'Y-m-d H:i:s' ),
				'content' => $note->content,
			);
		}

		wp_send_json_success(
			array(
				'items'            => $items,
				'shipping_address' => $shipping_address,
				'notes'            => $formatted_notes,
			)
		);
	}

	/**
	 * AJAX: Export orders to CSV.
	 */
	public function ajax_export_orders_csv() {
		check_ajax_referer( 'rm_admin_nonce', 'nonce' );

		if ( ! current_user_can( 'manage_woocommerce' ) ) {
			wp_send_json_error( array( 'message' => __( 'Permission denied.', 'region-manager' ) ) );
		}

		$region_id   = isset( $_POST['region_id'] ) && '' !== $_POST['region_id'] ? absint( $_POST['region_id'] ) : null;
		$status      = isset( $_POST['status'] ) ? sanitize_text_field( $_POST['status'] ) : 'any';
		$date_after  = isset( $_POST['date_after'] ) ? sanitize_text_field( $_POST['date_after'] ) : null;
		$date_before = isset( $_POST['date_before'] ) ? sanitize_text_field( $_POST['date_before'] ) : null;

		$args = array(
			'limit'       => -1,
			'status'      => $status,
			'date_after'  => $date_after,
			'date_before' => $date_before,
		);

		$orders = $this->get_orders_by_region( $region_id, $args );

		// Generate CSV.
		$filename = 'orders-export-' . gmdate( 'Y-m-d-His' ) . '.csv';
		$filepath = wp_upload_dir()['basedir'] . '/' . $filename;

		$fp = fopen( $filepath, 'w' );

		// CSV Headers.
		fputcsv( $fp, array( 'Order #', 'Date', 'Customer', 'Country', 'Region', 'Items', 'Total', 'Status' ) );

		// CSV Data.
		foreach ( $orders as $order ) {
			fputcsv(
				$fp,
				array(
					$order['order_number'],
					$order['date'],
					$order['customer_name'],
					$order['shipping_country'],
					$order['region_name'],
					$order['items_count'],
					$order['total'] . ' ' . $order['currency'],
					$order['status'],
				)
			);
		}

		fclose( $fp );

		// Return download URL.
		$download_url = wp_upload_dir()['baseurl'] . '/' . $filename;

		wp_send_json_success(
			array(
				'download_url' => $download_url,
				'filename'     => $filename,
			)
		);
	}

	/**
	 * AJAX: Get orders table data.
	 */
	public function ajax_get_orders_table() {
		check_ajax_referer( 'rm_admin_nonce', 'nonce' );

		if ( ! current_user_can( 'manage_woocommerce' ) ) {
			wp_send_json_error( array( 'message' => __( 'Permission denied.', 'region-manager' ) ) );
		}

		$page        = isset( $_POST['page'] ) ? absint( $_POST['page'] ) : 1;
		$per_page    = isset( $_POST['per_page'] ) ? absint( $_POST['per_page'] ) : 20;
		$region_id   = isset( $_POST['region_id'] ) && '' !== $_POST['region_id'] ? absint( $_POST['region_id'] ) : null;
		$status      = isset( $_POST['status'] ) ? sanitize_text_field( $_POST['status'] ) : 'any';
		$search      = isset( $_POST['search'] ) ? sanitize_text_field( $_POST['search'] ) : '';
		$date_after  = isset( $_POST['date_after'] ) ? sanitize_text_field( $_POST['date_after'] ) : null;
		$date_before = isset( $_POST['date_before'] ) ? sanitize_text_field( $_POST['date_before'] ) : null;
		$orderby     = isset( $_POST['orderby'] ) ? sanitize_text_field( $_POST['orderby'] ) : 'date';
		$order       = isset( $_POST['order'] ) ? sanitize_text_field( $_POST['order'] ) : 'DESC';

		$args = array(
			'limit'       => $per_page,
			'page'        => $page,
			'status'      => $status,
			'search'      => $search,
			'date_after'  => $date_after,
			'date_before' => $date_before,
			'orderby'     => $orderby,
			'order'       => $order,
		);

		$orders = $this->get_orders_by_region( $region_id, $args );
		$total  = $this->get_orders_count( $region_id, array( 'status' => $status, 'search' => $search, 'date_after' => $date_after, 'date_before' => $date_before ) );

		wp_send_json_success(
			array(
				'orders'      => $orders,
				'total'       => $total,
				'total_pages' => ceil( $total / $per_page ),
			)
		);
	}
}
