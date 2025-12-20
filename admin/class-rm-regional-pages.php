<?php
/**
 * Regional Pages management functionality.
 *
 * @package    Region_Manager
 * @subpackage Region_Manager/admin
 */

/**
 * Regional Pages management class.
 *
 * Handles regional page assignments and content customization per region.
 */
class RM_Regional_Pages {

	/**
	 * Initialize the class.
	 */
	public function __construct() {
		add_action( 'wp_ajax_rm_save_regional_pages', array( $this, 'ajax_save_pages' ) );
		add_action( 'wp_ajax_rm_create_regional_page', array( $this, 'ajax_create_page' ) );
	}

	/**
	 * Display the regional pages management page.
	 */
	public function display() {
		// Check user capabilities.
		if ( ! current_user_can( 'manage_woocommerce' ) ) {
			wp_die( esc_html__( 'You do not have sufficient permissions to access this page.', 'region-manager' ) );
		}

		require_once RM_PLUGIN_DIR . 'admin/partials/regional-pages-display.php';
	}

	/**
	 * Get all WordPress pages for dropdown.
	 *
	 * @return array Array of WP_Post objects.
	 */
	public function get_all_wp_pages() {
		return get_pages(
			array(
				'post_status' => 'publish',
				'sort_column' => 'post_title',
				'sort_order'  => 'ASC',
			)
		);
	}

	/**
	 * Get all active regions.
	 *
	 * @return array Array of region objects.
	 */
	public function get_all_regions() {
		global $wpdb;
		return $wpdb->get_results(
			"SELECT * FROM {$wpdb->prefix}rm_regions WHERE status = 'active' ORDER BY name ASC"
		);
	}

	/**
	 * Get region by ID.
	 *
	 * @param int $region_id Region ID.
	 * @return object|null Region object or null.
	 */
	public function get_region( $region_id ) {
		global $wpdb;
		return $wpdb->get_row(
			$wpdb->prepare(
				"SELECT * FROM {$wpdb->prefix}rm_regions WHERE id = %d",
				$region_id
			)
		);
	}

	/**
	 * Get regional page assignments.
	 *
	 * @param int $region_id Region ID.
	 * @return array Indexed array of page assignments by page_type.
	 */
	public function get_regional_pages( $region_id ) {
		global $wpdb;

		$results = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT * FROM {$wpdb->prefix}rm_regional_pages WHERE region_id = %d",
				$region_id
			)
		);

		$indexed = array();
		foreach ( $results as $row ) {
			$indexed[ $row->page_type ] = $row;
		}
		return $indexed;
	}

	/**
	 * Get regional content blocks.
	 *
	 * @param int $region_id Region ID.
	 * @return array Indexed array of content by content_key.
	 */
	public function get_regional_content( $region_id ) {
		global $wpdb;

		$results = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT * FROM {$wpdb->prefix}rm_regional_content WHERE region_id = %d",
				$region_id
			)
		);

		$indexed = array();
		foreach ( $results as $row ) {
			$indexed[ $row->content_key ] = $row->content_value;
		}
		return $indexed;
	}

	/**
	 * AJAX: Create a new page for a region.
	 */
	public function ajax_create_page() {
		check_ajax_referer( 'rm_admin_nonce', 'nonce' );

		if ( ! current_user_can( 'edit_pages' ) ) {
			wp_send_json_error( array( 'message' => __( 'Permission denied. You do not have permission to create pages.', 'region-manager' ) ) );
		}

		$region_id  = isset( $_POST['region_id'] ) ? absint( $_POST['region_id'] ) : 0;
		$page_type  = isset( $_POST['page_type'] ) ? sanitize_text_field( wp_unslash( $_POST['page_type'] ) ) : '';
		$page_title = isset( $_POST['page_title'] ) ? sanitize_text_field( wp_unslash( $_POST['page_title'] ) ) : '';

		if ( ! $region_id || ! $page_type || ! $page_title ) {
			wp_send_json_error( array( 'message' => __( 'Invalid parameters.', 'region-manager' ) ) );
		}

		// Get region info.
		$region = $this->get_region( $region_id );

		if ( ! $region ) {
			wp_send_json_error( array( 'message' => __( 'Region not found.', 'region-manager' ) ) );
		}

		// Create the page.
		$page_id = wp_insert_post(
			array(
				'post_title'   => $page_title,
				'post_content' => $this->get_default_page_content( $page_type ),
				'post_status'  => 'publish',
				'post_type'    => 'page',
				'post_name'    => sanitize_title( $region->slug . '-' . $page_type ),
			)
		);

		if ( is_wp_error( $page_id ) ) {
			wp_send_json_error( array( 'message' => $page_id->get_error_message() ) );
		}

		// Save page meta to identify it as regional.
		update_post_meta( $page_id, '_rm_region_id', $region_id );
		update_post_meta( $page_id, '_rm_page_type', $page_type );

		// Save to regional pages table.
		global $wpdb;
		$wpdb->replace(
			$wpdb->prefix . 'rm_regional_pages',
			array(
				'region_id'  => $region_id,
				'page_type'  => $page_type,
				'page_id'    => $page_id,
				'is_active'  => 1,
				'created_at' => current_time( 'mysql' ),
				'updated_at' => current_time( 'mysql' ),
			),
			array( '%d', '%s', '%d', '%d', '%s', '%s' )
		);

		wp_send_json_success(
			array(
				'page_id'  => $page_id,
				'edit_url' => get_edit_post_link( $page_id, 'raw' ),
				'view_url' => get_permalink( $page_id ),
				'message'  => __( 'Page created successfully!', 'region-manager' ),
			)
		);
	}

	/**
	 * AJAX: Save regional page assignments and content.
	 */
	public function ajax_save_pages() {
		check_ajax_referer( 'rm_admin_nonce', 'nonce' );

		if ( ! current_user_can( 'manage_woocommerce' ) ) {
			wp_send_json_error( array( 'message' => __( 'Permission denied.', 'region-manager' ) ) );
		}

		$region_id = isset( $_POST['region_id'] ) ? absint( $_POST['region_id'] ) : 0;
		$pages     = isset( $_POST['pages'] ) ? $_POST['pages'] : array();
		$content   = isset( $_POST['content'] ) ? $_POST['content'] : array();

		if ( ! $region_id ) {
			wp_send_json_error( array( 'message' => __( 'Invalid region ID.', 'region-manager' ) ) );
		}

		global $wpdb;
		$table_pages   = $wpdb->prefix . 'rm_regional_pages';
		$table_content = $wpdb->prefix . 'rm_regional_content';

		// Save page assignments.
		foreach ( $pages as $page_type => $page_id ) {
			$page_id   = absint( $page_id );
			$page_type = sanitize_key( $page_type );

			if ( $page_id > 0 ) {
				$wpdb->replace(
					$table_pages,
					array(
						'region_id'  => $region_id,
						'page_type'  => $page_type,
						'page_id'    => $page_id,
						'is_active'  => 1,
						'updated_at' => current_time( 'mysql' ),
					),
					array( '%d', '%s', '%d', '%d', '%s' )
				);

				// Update page meta.
				update_post_meta( $page_id, '_rm_region_id', $region_id );
				update_post_meta( $page_id, '_rm_page_type', $page_type );
			} else {
				// Remove assignment.
				$wpdb->delete(
					$table_pages,
					array(
						'region_id' => $region_id,
						'page_type' => $page_type,
					),
					array( '%d', '%s' )
				);
			}
		}

		// Save content blocks.
		foreach ( $content as $content_key => $content_value ) {
			$content_key   = sanitize_key( $content_key );
			$content_value = wp_kses_post( $content_value );

			$wpdb->replace(
				$table_content,
				array(
					'region_id'     => $region_id,
					'content_key'   => $content_key,
					'content_value' => $content_value,
					'updated_at'    => current_time( 'mysql' ),
				),
				array( '%d', '%s', '%s', '%s' )
			);
		}

		wp_send_json_success( array( 'message' => __( 'Settings saved successfully!', 'region-manager' ) ) );
	}

	/**
	 * Get default content for new pages.
	 *
	 * @param string $page_type Page type.
	 * @return string Default page content.
	 */
	private function get_default_page_content( $page_type ) {
		switch ( $page_type ) {
			case 'shop':
				return '<!-- wp:woocommerce/all-products {"columns":4,"rows":4} /-->';
			case 'welcome':
				return '<!-- wp:paragraph --><p>' . __( 'Welcome to our store!', 'region-manager' ) . '</p><!-- /wp:paragraph -->';
			case 'categories':
				return '<!-- wp:woocommerce/product-categories /-->';
			default:
				return '';
		}
	}
}
