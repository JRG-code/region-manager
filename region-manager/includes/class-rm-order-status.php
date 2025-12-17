<?php
/**
 * Custom Order Status Handler
 *
 * Registers and manages custom WooCommerce order statuses.
 *
 * @package    Region_Manager
 * @subpackage Region_Manager/includes
 */

/**
 * Custom Order Status Handler Class.
 *
 * Registers the 'In Transit' custom order status for WooCommerce.
 */
class RM_Order_Status {

	/**
	 * Initialize the custom order status.
	 */
	public function __construct() {
		// Register custom order status.
		add_action( 'init', array( $this, 'register_order_status' ) );

		// Add to WooCommerce order statuses list.
		add_filter( 'wc_order_statuses', array( $this, 'add_to_order_statuses' ) );

		// Add to bulk actions.
		add_filter( 'bulk_actions-edit-shop_order', array( $this, 'add_bulk_actions' ) );
		add_filter( 'bulk_actions-woocommerce_page_wc-orders', array( $this, 'add_bulk_actions' ) );

		// Add to order status filters.
		add_filter( 'woocommerce_reports_order_statuses', array( $this, 'add_to_reports' ) );

		// Add custom status to admin order list.
		add_filter( 'display_post_states', array( $this, 'display_order_state' ), 10, 2 );

		// Add CSS for status badge.
		add_action( 'admin_head', array( $this, 'add_admin_styles' ) );

		// Email notification hooks (placeholder for future implementation).
		add_action( 'woocommerce_order_status_in-transit', array( $this, 'trigger_in_transit_email' ), 10, 2 );
	}

	/**
	 * Register custom order status.
	 */
	public function register_order_status() {
		register_post_status(
			'wc-in-transit',
			array(
				'label'                     => _x( 'In Transit', 'Order status', 'region-manager' ),
				'public'                    => true,
				'exclude_from_search'       => false,
				'show_in_admin_all_list'    => true,
				'show_in_admin_status_list' => true,
				/* translators: %s: number of orders */
				'label_count'               => _n_noop( 'In Transit <span class="count">(%s)</span>', 'In Transit <span class="count">(%s)</span>', 'region-manager' ),
			)
		);
	}

	/**
	 * Add custom status to WooCommerce order statuses.
	 *
	 * @param array $order_statuses Existing order statuses.
	 * @return array Modified order statuses.
	 */
	public function add_to_order_statuses( $order_statuses ) {
		$new_statuses = array();

		// Add In Transit status after Processing.
		foreach ( $order_statuses as $key => $status ) {
			$new_statuses[ $key ] = $status;

			if ( 'wc-processing' === $key ) {
				$new_statuses['wc-in-transit'] = _x( 'In Transit', 'Order status', 'region-manager' );
			}
		}

		return $new_statuses;
	}

	/**
	 * Add custom status to bulk actions.
	 *
	 * @param array $bulk_actions Existing bulk actions.
	 * @return array Modified bulk actions.
	 */
	public function add_bulk_actions( $bulk_actions ) {
		$bulk_actions['mark_in-transit'] = __( 'Mark as In Transit', 'region-manager' );
		return $bulk_actions;
	}

	/**
	 * Add custom status to reports.
	 *
	 * @param array $statuses Existing statuses.
	 * @return array Modified statuses.
	 */
	public function add_to_reports( $statuses ) {
		if ( ! in_array( 'in-transit', $statuses, true ) ) {
			$statuses[] = 'in-transit';
		}
		return $statuses;
	}

	/**
	 * Display custom order state in admin order list.
	 *
	 * @param array   $states Existing states.
	 * @param WP_Post $post Post object.
	 * @return array Modified states.
	 */
	public function display_order_state( $states, $post ) {
		if ( 'shop_order' === get_post_type( $post->ID ) || 'woocommerce_page_wc-orders' === get_post_type( $post->ID ) ) {
			$order = wc_get_order( $post->ID );
			if ( $order && 'in-transit' === $order->get_status() ) {
				$states[] = _x( 'In Transit', 'Order status', 'region-manager' );
			}
		}
		return $states;
	}

	/**
	 * Add custom CSS for In Transit status badge.
	 */
	public function add_admin_styles() {
		?>
		<style>
			.order-status.status-in-transit {
				background: #3498db;
				color: #fff;
			}
			mark.in-transit {
				background: #e3f2fd;
				color: #1976d2;
				font-weight: 600;
			}
			mark.in-transit::after {
				content: " 🚚";
			}
			.widefat .column-order_status mark.in-transit {
				background: #e3f2fd;
				color: #1976d2;
			}
		</style>
		<?php
	}

	/**
	 * Trigger email notification when order status changes to In Transit.
	 *
	 * Placeholder for future email notification implementation.
	 *
	 * @param int      $order_id Order ID.
	 * @param WC_Order $order Order object.
	 */
	public function trigger_in_transit_email( $order_id, $order = null ) {
		if ( ! $order ) {
			$order = wc_get_order( $order_id );
		}

		if ( ! $order ) {
			return;
		}

		// Placeholder for future email implementation.
		// You can add custom email template here.
		// Example: WC()->mailer()->get_emails()['WC_Email_Customer_In_Transit']->trigger( $order_id );

		// Add order note.
		$order->add_order_note( __( 'Order marked as In Transit.', 'region-manager' ) );

		// Log for debugging.
		if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
			error_log( sprintf( 'Order #%d marked as In Transit', $order_id ) );
		}
	}

	/**
	 * Get status color.
	 *
	 * @param string $status Order status.
	 * @return string Color code.
	 */
	public static function get_status_color( $status ) {
		$colors = array(
			'pending'    => '#999999',
			'processing' => '#c6e1c6',
			'in-transit' => '#3498db',
			'on-hold'    => '#f8dda7',
			'completed'  => '#c8d7e1',
			'cancelled'  => '#e5e5e5',
			'refunded'   => '#c8d7e1',
			'failed'     => '#eba3a3',
		);

		return isset( $colors[ $status ] ) ? $colors[ $status ] : '#999999';
	}

	/**
	 * Get status icon.
	 *
	 * @param string $status Order status.
	 * @return string Icon HTML or emoji.
	 */
	public static function get_status_icon( $status ) {
		$icons = array(
			'pending'    => '⏳',
			'processing' => '⚙️',
			'in-transit' => '🚚',
			'on-hold'    => '⏸️',
			'completed'  => '✅',
			'cancelled'  => '❌',
			'refunded'   => '↩️',
			'failed'     => '⚠️',
		);

		return isset( $icons[ $status ] ) ? $icons[ $status ] : '';
	}
}
