<?php
/**
 * Dashboard functionality.
 *
 * Handles the dashboard display and data retrieval for statistics.
 *
 * @package    RegionManager
 * @subpackage RegionManager/admin
 * @since      1.0.0
 */

/**
 * Dashboard class.
 *
 * @since 1.0.0
 */
class RM_Dashboard {

	/**
	 * Display the dashboard page.
	 *
	 * @since 1.0.0
	 */
	public function display() {
		if ( ! current_user_can( 'manage_woocommerce' ) ) {
			wp_die( esc_html__( 'You do not have sufficient permissions to access this page.', 'region-manager' ) );
		}

		include RM_PLUGIN_DIR . 'admin/partials/dashboard-display.php';
	}

	/**
	 * Get total regions count.
	 *
	 * @since  1.0.0
	 * @return array Array with total, active, and inactive counts.
	 */
	public function get_regions_count() {
		global $wpdb;

		$table_name = $wpdb->prefix . 'rm_regions';

		$total    = $wpdb->get_var( "SELECT COUNT(*) FROM {$table_name}" ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$active   = $wpdb->get_var( "SELECT COUNT(*) FROM {$table_name} WHERE status = 'active'" ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$inactive = $wpdb->get_var( "SELECT COUNT(*) FROM {$table_name} WHERE status = 'inactive'" ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching

		return array(
			'total'    => absint( $total ),
			'active'   => absint( $active ),
			'inactive' => absint( $inactive ),
		);
	}

	/**
	 * Get orders count for today.
	 *
	 * @since  1.0.0
	 * @param  int|null $region_id Optional region ID to filter by.
	 * @return int Order count.
	 */
	public function get_orders_count_today( $region_id = null ) {
		$args = array(
			'limit'       => -1,
			'status'      => array( 'wc-processing', 'wc-completed', 'wc-on-hold', 'wc-pending' ),
			'date_after'  => gmdate( 'Y-m-d 00:00:00' ),
			'date_before' => gmdate( 'Y-m-d 23:59:59' ),
			'return'      => 'ids',
		);

		if ( $region_id ) {
			$args['meta_key']   = '_rm_region_id'; // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key
			$args['meta_value'] = $region_id; // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_value
		}

		$orders = wc_get_orders( $args );

		return count( $orders );
	}

	/**
	 * Get revenue for today.
	 *
	 * @since  1.0.0
	 * @param  int|null $region_id Optional region ID to filter by.
	 * @return float Revenue amount.
	 */
	public function get_revenue_today( $region_id = null ) {
		$args = array(
			'limit'       => -1,
			'status'      => array( 'wc-processing', 'wc-completed' ),
			'date_after'  => gmdate( 'Y-m-d 00:00:00' ),
			'date_before' => gmdate( 'Y-m-d 23:59:59' ),
		);

		if ( $region_id ) {
			$args['meta_key']   = '_rm_region_id'; // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key
			$args['meta_value'] = $region_id; // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_value
		}

		$orders = wc_get_orders( $args );

		$revenue = 0;
		foreach ( $orders as $order ) {
			$revenue += $order->get_total();
		}

		return $revenue;
	}

	/**
	 * Get orders by region within date range.
	 *
	 * @since  1.0.0
	 * @param  int    $region_id  Region ID.
	 * @param  string $date_from  Start date (Y-m-d format).
	 * @param  string $date_to    End date (Y-m-d format).
	 * @return array Array of order objects.
	 */
	public function get_orders_by_region( $region_id, $date_from, $date_to ) {
		$args = array(
			'limit'       => -1,
			'status'      => array( 'wc-processing', 'wc-completed', 'wc-on-hold', 'wc-pending' ),
			'date_after'  => $date_from . ' 00:00:00',
			'date_before' => $date_to . ' 23:59:59',
			'meta_key'    => '_rm_region_id', // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key
			'meta_value'  => $region_id, // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_value
		);

		return wc_get_orders( $args );
	}

	/**
	 * Get revenue by region within date range.
	 *
	 * @since  1.0.0
	 * @param  int    $region_id  Region ID.
	 * @param  string $date_from  Start date (Y-m-d format).
	 * @param  string $date_to    End date (Y-m-d format).
	 * @return float Revenue amount.
	 */
	public function get_revenue_by_region( $region_id, $date_from, $date_to ) {
		$orders = $this->get_orders_by_region( $region_id, $date_from, $date_to );

		$revenue = 0;
		foreach ( $orders as $order ) {
			if ( $order->get_status() === 'completed' || $order->get_status() === 'processing' ) {
				$revenue += $order->get_total();
			}
		}

		return $revenue;
	}

	/**
	 * Get orders count comparison (today vs yesterday).
	 *
	 * @since  1.0.0
	 * @param  int|null $region_id Optional region ID to filter by.
	 * @return array Array with today, yesterday, and percentage change.
	 */
	public function get_orders_comparison( $region_id = null ) {
		$today     = $this->get_orders_count_today( $region_id );
		$yesterday = $this->get_orders_count_yesterday( $region_id );

		$change      = 0;
		$change_type = 'neutral';

		if ( $yesterday > 0 ) {
			$change = ( ( $today - $yesterday ) / $yesterday ) * 100;
			$change_type = $change > 0 ? 'up' : ( $change < 0 ? 'down' : 'neutral' );
		} elseif ( $today > 0 ) {
			$change      = 100;
			$change_type = 'up';
		}

		return array(
			'today'       => $today,
			'yesterday'   => $yesterday,
			'change'      => round( $change, 1 ),
			'change_type' => $change_type,
		);
	}

	/**
	 * Get revenue comparison (today vs yesterday).
	 *
	 * @since  1.0.0
	 * @param  int|null $region_id Optional region ID to filter by.
	 * @return array Array with today, yesterday, and percentage change.
	 */
	public function get_revenue_comparison( $region_id = null ) {
		$today     = $this->get_revenue_today( $region_id );
		$yesterday = $this->get_revenue_yesterday( $region_id );

		$change      = 0;
		$change_type = 'neutral';

		if ( $yesterday > 0 ) {
			$change = ( ( $today - $yesterday ) / $yesterday ) * 100;
			$change_type = $change > 0 ? 'up' : ( $change < 0 ? 'down' : 'neutral' );
		} elseif ( $today > 0 ) {
			$change      = 100;
			$change_type = 'up';
		}

		return array(
			'today'       => $today,
			'yesterday'   => $yesterday,
			'change'      => round( $change, 1 ),
			'change_type' => $change_type,
		);
	}

	/**
	 * Get orders count for yesterday.
	 *
	 * @since  1.0.0
	 * @param  int|null $region_id Optional region ID to filter by.
	 * @return int Order count.
	 */
	private function get_orders_count_yesterday( $region_id = null ) {
		$args = array(
			'limit'       => -1,
			'status'      => array( 'wc-processing', 'wc-completed', 'wc-on-hold', 'wc-pending' ),
			'date_after'  => gmdate( 'Y-m-d 00:00:00', strtotime( '-1 day' ) ),
			'date_before' => gmdate( 'Y-m-d 23:59:59', strtotime( '-1 day' ) ),
			'return'      => 'ids',
		);

		if ( $region_id ) {
			$args['meta_key']   = '_rm_region_id'; // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key
			$args['meta_value'] = $region_id; // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_value
		}

		$orders = wc_get_orders( $args );

		return count( $orders );
	}

	/**
	 * Get revenue for yesterday.
	 *
	 * @since  1.0.0
	 * @param  int|null $region_id Optional region ID to filter by.
	 * @return float Revenue amount.
	 */
	private function get_revenue_yesterday( $region_id = null ) {
		$args = array(
			'limit'       => -1,
			'status'      => array( 'wc-processing', 'wc-completed' ),
			'date_after'  => gmdate( 'Y-m-d 00:00:00', strtotime( '-1 day' ) ),
			'date_before' => gmdate( 'Y-m-d 23:59:59', strtotime( '-1 day' ) ),
		);

		if ( $region_id ) {
			$args['meta_key']   = '_rm_region_id'; // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key
			$args['meta_value'] = $region_id; // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_value
		}

		$orders = wc_get_orders( $args );

		$revenue = 0;
		foreach ( $orders as $order ) {
			$revenue += $order->get_total();
		}

		return $revenue;
	}

	/**
	 * Get top products by region.
	 *
	 * @since  1.0.0
	 * @param  int $region_id  Region ID.
	 * @param  int $limit      Number of products to return.
	 * @return array Array of product data.
	 */
	public function get_top_products_by_region( $region_id, $limit = 5 ) {
		global $wpdb;

		$orders = $this->get_orders_by_region( $region_id, gmdate( 'Y-m-01' ), gmdate( 'Y-m-d' ) );

		$products = array();

		foreach ( $orders as $order ) {
			foreach ( $order->get_items() as $item ) {
				$product_id = $item->get_product_id();

				if ( ! isset( $products[ $product_id ] ) ) {
					$product = $item->get_product();
					if ( $product ) {
						$products[ $product_id ] = array(
							'id'       => $product_id,
							'name'     => $product->get_name(),
							'quantity' => 0,
							'revenue'  => 0,
						);
					}
				}

				if ( isset( $products[ $product_id ] ) ) {
					$products[ $product_id ]['quantity'] += $item->get_quantity();
					$products[ $product_id ]['revenue']  += $item->get_total();
				}
			}
		}

		// Sort by quantity.
		usort( $products, function( $a, $b ) {
			return $b['quantity'] - $a['quantity'];
		} );

		return array_slice( $products, 0, $limit );
	}

	/**
	 * Get all regions.
	 *
	 * @since  1.0.0
	 * @return array Array of region objects.
	 */
	public function get_regions() {
		global $wpdb;

		$table_name = $wpdb->prefix . 'rm_regions';
		$results    = $wpdb->get_results( "SELECT * FROM {$table_name} WHERE status = 'active' ORDER BY name ASC" ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching

		return $results;
	}

	/**
	 * Get recent orders by region.
	 *
	 * @since  1.0.0
	 * @param  int $region_id  Region ID.
	 * @param  int $limit      Number of orders to return.
	 * @return array Array of order objects.
	 */
	public function get_recent_orders_by_region( $region_id, $limit = 5 ) {
		$args = array(
			'limit'      => $limit,
			'orderby'    => 'date',
			'order'      => 'DESC',
			'meta_key'   => '_rm_region_id', // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key
			'meta_value' => $region_id, // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_value
		);

		return wc_get_orders( $args );
	}

	/**
	 * Get orders by status for all regions.
	 *
	 * @since  1.0.0
	 * @return array Array with status counts.
	 */
	public function get_orders_by_status() {
		$statuses = array( 'processing', 'on-hold', 'completed', 'pending' );
		$counts   = array();

		foreach ( $statuses as $status ) {
			$args = array(
				'limit'  => -1,
				'status' => 'wc-' . $status,
				'return' => 'ids',
			);

			$orders = wc_get_orders( $args );
			$counts[ $status ] = count( $orders );
		}

		return $counts;
	}

	/**
	 * Get products without region assignment.
	 *
	 * @since  1.0.0
	 * @return int Count of products without region.
	 */
	public function get_products_without_region() {
		global $wpdb;

		$product_table = $wpdb->prefix . 'posts';
		$region_table  = $wpdb->prefix . 'rm_product_regions';

		$query = "SELECT COUNT(DISTINCT p.ID)
				  FROM {$product_table} p
				  LEFT JOIN {$region_table} pr ON p.ID = pr.product_id
				  WHERE p.post_type = 'product'
				  AND p.post_status = 'publish'
				  AND pr.id IS NULL";

		$count = $wpdb->get_var( $query ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.NotPrepared

		return absint( $count );
	}

	/**
	 * Get total products with region assignment.
	 *
	 * @since  1.0.0
	 * @return int Count of products with region.
	 */
	public function get_products_with_region() {
		global $wpdb;

		$region_table = $wpdb->prefix . 'rm_product_regions';

		$query = "SELECT COUNT(DISTINCT product_id) FROM {$region_table}";

		$count = $wpdb->get_var( $query ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.NotPrepared

		return absint( $count );
	}
}
