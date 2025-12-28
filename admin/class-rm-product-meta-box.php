<?php
/**
 * Product Meta Box for WooCommerce integration.
 *
 * Adds a meta box to WooCommerce product edit pages for region assignment and pricing.
 *
 * @package    Region_Manager
 * @subpackage Region_Manager/admin
 */

/**
 * Product Meta Box class.
 *
 * Handles the region availability and pricing meta box on WooCommerce product pages.
 */
class RM_Product_Meta_Box {

	/**
	 * Initialize the class.
	 */
	public function __construct() {
		add_action( 'add_meta_boxes', array( $this, 'add_region_meta_box' ) );
		add_action( 'woocommerce_process_product_meta', array( $this, 'save_region_meta' ), 10, 2 );

		// Add Regions column to WooCommerce products list.
		add_filter( 'manage_edit-product_columns', array( $this, 'add_regions_column' ) );
		add_action( 'manage_product_posts_custom_column', array( $this, 'render_regions_column' ), 10, 2 );

		// Add bulk actions for region assignment.
		add_filter( 'bulk_actions-edit-product', array( $this, 'register_bulk_actions' ) );
		add_filter( 'handle_bulk_actions-edit-product', array( $this, 'handle_bulk_actions' ), 10, 3 );
		add_action( 'admin_notices', array( $this, 'bulk_action_notices' ) );

		// AJAX handler for quick region assignment.
		add_action( 'wp_ajax_rm_quick_assign_region', array( $this, 'ajax_quick_assign_region' ) );

		// Enqueue admin scripts for products page.
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_admin_scripts' ) );
	}

	/**
	 * Add meta box to product edit page.
	 */
	public function add_region_meta_box() {
		add_meta_box(
			'rm_product_regions',
			__( 'Region Availability & Pricing', 'region-manager' ),
			array( $this, 'render_meta_box' ),
			'product',
			'normal',
			'default'
		);
	}

	/**
	 * Render the meta box content.
	 *
	 * @param WP_Post $post Product post object.
	 */
	public function render_meta_box( $post ) {
		$product_id      = $post->ID;
		$regions         = $this->get_all_regions();
		$product_regions = $this->get_product_regions( $product_id );

		wp_nonce_field( 'rm_save_product_regions', 'rm_product_regions_nonce' );

		if ( empty( $regions ) ) {
			echo '<p>' . esc_html__( 'No regions configured. ', 'region-manager' );
			echo '<a href="' . esc_url( admin_url( 'admin.php?page=region-manager-settings' ) ) . '">';
			echo esc_html__( 'Create regions in settings.', 'region-manager' );
			echo '</a></p>';
			return;
		}

		?>
		<div class="rm-product-regions-wrapper">
			<p class="description">
				<?php esc_html_e( 'Select which regions this product is available in and optionally set regional pricing.', 'region-manager' ); ?>
			</p>

			<table class="rm-regions-table widefat">
				<thead>
					<tr>
						<th class="rm-col-available"><?php esc_html_e( 'Available', 'region-manager' ); ?></th>
						<th class="rm-col-region"><?php esc_html_e( 'Region', 'region-manager' ); ?></th>
						<th class="rm-col-countries"><?php esc_html_e( 'Countries', 'region-manager' ); ?></th>
						<th class="rm-col-price"><?php esc_html_e( 'Price Override', 'region-manager' ); ?></th>
						<th class="rm-col-sale-price"><?php esc_html_e( 'Sale Price Override', 'region-manager' ); ?></th>
					</tr>
				</thead>
				<tbody>
					<!-- ALL REGIONS option -->
					<tr class="rm-region-row rm-region-all">
						<td>
							<input type="checkbox"
								   name="rm_regions[all][available]"
								   id="rm_region_all"
								   value="1"
								   <?php checked( $this->is_available_in_all( $product_regions ) ); ?>
								   class="rm-toggle-all-regions">
						</td>
						<td>
							<label for="rm_region_all">
								<strong><?php esc_html_e( 'All Regions', 'region-manager' ); ?></strong>
							</label>
						</td>
						<td>
							<span class="description"><?php esc_html_e( 'Product available everywhere (uses base price)', 'region-manager' ); ?></span>
						</td>
						<td>—</td>
						<td>—</td>
					</tr>

					<?php
					foreach ( $regions as $region ) :
						$region_data         = isset( $product_regions[ $region->id ] ) ? $product_regions[ $region->id ] : null;
						$is_available        = $region_data ? true : false;
						$price_override      = $region_data ? $region_data->price_override : '';
						$sale_price_override = $region_data ? $region_data->sale_price_override : '';
						$countries           = $this->get_region_countries( $region->id );
						?>
						<tr class="rm-region-row" data-region-id="<?php echo esc_attr( $region->id ); ?>">
							<td>
								<input type="checkbox"
									   name="rm_regions[<?php echo esc_attr( $region->id ); ?>][available]"
									   id="rm_region_<?php echo esc_attr( $region->id ); ?>"
									   value="1"
									   <?php checked( $is_available ); ?>
									   class="rm-region-checkbox">
							</td>
							<td>
								<label for="rm_region_<?php echo esc_attr( $region->id ); ?>">
									<?php echo esc_html( $region->name ); ?>
								</label>
							</td>
							<td>
								<span class="rm-countries-list">
									<?php
									foreach ( $countries as $country ) {
										echo '<span class="rm-flag-small">' . esc_html( $this->get_flag_emoji( $country->country_code ) ) . '</span>';
									}
									?>
								</span>
							</td>
							<td>
								<input type="text"
									   name="rm_regions[<?php echo esc_attr( $region->id ); ?>][price]"
									   value="<?php echo esc_attr( $price_override ); ?>"
									   class="short wc_input_price rm-price-field"
									   placeholder="<?php esc_attr_e( 'Use base price', 'region-manager' ); ?>"
									   <?php disabled( ! $is_available ); ?>>
							</td>
							<td>
								<input type="text"
									   name="rm_regions[<?php echo esc_attr( $region->id ); ?>][sale_price]"
									   value="<?php echo esc_attr( $sale_price_override ); ?>"
									   class="short wc_input_price rm-price-field"
									   placeholder="<?php esc_attr_e( 'Use base sale price', 'region-manager' ); ?>"
									   <?php disabled( ! $is_available ); ?>>
							</td>
						</tr>
					<?php endforeach; ?>
				</tbody>
			</table>

			<p class="rm-help-text">
				<span class="dashicons dashicons-info"></span>
				<?php esc_html_e( 'Leave price fields empty to use the product\'s base price. Enter a value to override for that specific region.', 'region-manager' ); ?>
			</p>
		</div>

		<style>
			.rm-product-regions-wrapper { margin-top: 10px; }
			.rm-regions-table { margin: 15px 0; }
			.rm-regions-table th { text-align: left; padding: 10px; background: #f9f9f9; }
			.rm-regions-table td { padding: 10px; vertical-align: middle; }
			.rm-region-all { background: #f0f6fc; border-bottom: 2px solid #2271b1; font-weight: 600; }
			.rm-col-available { width: 70px; }
			.rm-col-region { width: 150px; }
			.rm-col-countries { width: 200px; }
			.rm-col-price, .rm-col-sale-price { width: 150px; }
			.rm-countries-list .rm-flag-small { margin-right: 5px; font-size: 16px; }
			.rm-price-field:disabled { background: #f5f5f5; }
			.rm-help-text { color: #666; font-style: italic; margin-top: 15px; }
			.rm-help-text .dashicons { font-size: 16px; vertical-align: middle; margin-right: 5px; }
		</style>

		<script>
		jQuery(document).ready(function($) {
			// Toggle all regions
			$('.rm-toggle-all-regions').on('change', function() {
				var isChecked = $(this).is(':checked');
				if (isChecked) {
					// Uncheck all individual regions when "All" is selected
					$('.rm-region-checkbox').prop('checked', false).trigger('change');
				}
			});

			// When individual region is checked
			$('.rm-region-checkbox').on('change', function() {
				var $row = $(this).closest('tr');
				var isChecked = $(this).is(':checked');

				// Enable/disable price fields
				$row.find('.rm-price-field').prop('disabled', !isChecked);

				// If any individual region is checked, uncheck "All"
				if (isChecked) {
					$('.rm-toggle-all-regions').prop('checked', false);
				}
			});
		});
		</script>
		<?php
	}

	/**
	 * Save region meta when product is saved.
	 *
	 * @param int        $product_id Product ID.
	 * @param WC_Product $product Product object.
	 */
	public function save_region_meta( $product_id, $product ) {
		if ( ! isset( $_POST['rm_product_regions_nonce'] ) ||
			! wp_verify_nonce( $_POST['rm_product_regions_nonce'], 'rm_save_product_regions' ) ) {
			return;
		}

		if ( ! current_user_can( 'edit_product', $product_id ) ) {
			return;
		}

		global $wpdb;
		$table = $wpdb->prefix . 'rm_product_regions';

		// Clear existing region assignments.
		$wpdb->delete( $table, array( 'product_id' => $product_id ), array( '%d' ) );

		$regions_data = isset( $_POST['rm_regions'] ) ? $_POST['rm_regions'] : array();

		// Check if "All Regions" is selected.
		if ( ! empty( $regions_data['all']['available'] ) ) {
			// Save with region_id = 0 to indicate "all regions".
			$wpdb->insert(
				$table,
				array(
					'product_id'         => $product_id,
					'region_id'          => 0,
					'price_override'     => null,
					'sale_price_override' => null,
				),
				array( '%d', '%d', '%s', '%s' )
			);
			return;
		}

		// Save individual region assignments.
		foreach ( $regions_data as $region_id => $data ) {
			if ( 'all' === $region_id ) {
				continue;
			}

			if ( ! empty( $data['available'] ) ) {
				$price      = ! empty( $data['price'] ) ? floatval( $data['price'] ) : null;
				$sale_price = ! empty( $data['sale_price'] ) ? floatval( $data['sale_price'] ) : null;

				$wpdb->insert(
					$table,
					array(
						'product_id'          => $product_id,
						'region_id'           => intval( $region_id ),
						'price_override'      => $price,
						'sale_price_override' => $sale_price,
					),
					array( '%d', '%d', '%f', '%f' )
				);
			}
		}
	}

	/**
	 * Get all active regions.
	 *
	 * @return array Array of region objects.
	 */
	private function get_all_regions() {
		global $wpdb;
		return $wpdb->get_results(
			"SELECT * FROM {$wpdb->prefix}rm_regions WHERE status = 'active' ORDER BY name ASC"
		);
	}

	/**
	 * Get product's region assignments.
	 *
	 * @param int $product_id Product ID.
	 * @return array Indexed by region_id.
	 */
	private function get_product_regions( $product_id ) {
		global $wpdb;
		$results = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT * FROM {$wpdb->prefix}rm_product_regions WHERE product_id = %d",
				$product_id
			)
		);

		$indexed = array();
		foreach ( $results as $row ) {
			$indexed[ $row->region_id ] = $row;
		}
		return $indexed;
	}

	/**
	 * Check if product is available in all regions.
	 *
	 * @param array $product_regions Product regions data.
	 * @return bool True if available in all regions.
	 */
	private function is_available_in_all( $product_regions ) {
		return isset( $product_regions[0] );
	}

	/**
	 * Get countries for a region.
	 *
	 * @param int $region_id Region ID.
	 * @return array Array of country objects.
	 */
	private function get_region_countries( $region_id ) {
		global $wpdb;
		return $wpdb->get_results(
			$wpdb->prepare(
				"SELECT * FROM {$wpdb->prefix}rm_region_countries WHERE region_id = %d",
				$region_id
			)
		);
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
	 * Add Regions column to WooCommerce products list.
	 *
	 * @param array $columns Existing columns.
	 * @return array Modified columns.
	 */
	public function add_regions_column( $columns ) {
		// Insert Regions column before the "Stock" column or at the end.
		$new_columns = array();

		foreach ( $columns as $key => $value ) {
			if ( 'product_tag' === $key ) {
				$new_columns['rm_regions'] = __( 'Regions', 'region-manager' );
			}
			$new_columns[ $key ] = $value;
		}

		// If product_tag doesn't exist, add at the end before date.
		if ( ! isset( $new_columns['rm_regions'] ) ) {
			$date = $new_columns['date'];
			unset( $new_columns['date'] );
			$new_columns['rm_regions'] = __( 'Regions', 'region-manager' );
			$new_columns['date']       = $date;
		}

		return $new_columns;
	}

	/**
	 * Render Regions column content.
	 *
	 * @param string $column Column name.
	 * @param int    $post_id Post ID.
	 */
	public function render_regions_column( $column, $post_id ) {
		if ( 'rm_regions' !== $column ) {
			return;
		}

		global $wpdb;
		$table_name = $wpdb->prefix . 'rm_product_regions';

		// Check if product is in all regions.
		$in_all_regions = $wpdb->get_var(
			$wpdb->prepare(
				"SELECT COUNT(*) FROM {$table_name} WHERE product_id = %d AND region_id = 0",
				$post_id
			)
		);

		if ( $in_all_regions > 0 ) {
			echo '<span class="rm-region-badge rm-all-regions" style="display: inline-block; padding: 3px 8px; background: #00a32a; color: #fff; border-radius: 3px; font-size: 12px;">';
			echo esc_html__( 'All', 'region-manager' );
			echo '</span>';
			return;
		}

		// Get specific region assignments.
		$results = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT r.name, r.slug
				FROM {$table_name} pr
				JOIN {$wpdb->prefix}rm_regions r ON pr.region_id = r.id
				WHERE pr.product_id = %d AND pr.region_id > 0
				ORDER BY r.name ASC",
				$post_id
			)
		);

		if ( ! empty( $results ) ) {
			foreach ( $results as $region ) {
				echo '<span class="rm-region-badge" style="display: inline-block; padding: 3px 8px; margin: 2px; background: #2271b1; color: #fff; border-radius: 3px; font-size: 12px; white-space: nowrap;" title="' . esc_attr( $region->name ) . '">';
				echo esc_html( $region->name );
				echo '</span> ';
			}
		} else {
			// Get all active regions for the dropdown.
			$all_regions = $wpdb->get_results(
				"SELECT id, name FROM {$wpdb->prefix}rm_regions WHERE status = 'active' ORDER BY name ASC"
			);

			if ( ! empty( $all_regions ) ) {
				// Show quick-assign dropdown.
				echo '<div class="rm-quick-assign-wrapper" style="display: inline-block;">';
				echo '<select class="rm-quick-assign-region" data-product-id="' . esc_attr( $post_id ) . '" style="font-size: 12px; padding: 2px 5px;">';
				echo '<option value="">' . esc_html__( '+ Add to region...', 'region-manager' ) . '</option>';
				echo '<option value="0">' . esc_html__( 'All Regions', 'region-manager' ) . '</option>';
				foreach ( $all_regions as $region ) {
					echo '<option value="' . esc_attr( $region->id ) . '">' . esc_html( $region->name ) . '</option>';
				}
				echo '</select>';
				echo '</div>';
			} else {
				echo '<span class="rm-no-regions" style="display: inline-block; padding: 3px 8px; background: #dba617; color: #fff; border-radius: 3px; font-size: 12px;">';
				echo esc_html__( 'No regions', 'region-manager' );
				echo '</span>';
			}
		}
	}

	/**
	 * Register bulk actions for region assignment.
	 *
	 * @param array $bulk_actions Existing bulk actions.
	 * @return array Modified bulk actions.
	 */
	public function register_bulk_actions( $bulk_actions ) {
		global $wpdb;

		// Add "Assign to All Regions" action.
		$bulk_actions['rm_assign_all_regions'] = __( 'Assign to All Regions', 'region-manager' );

		// Get all active regions.
		$regions = $wpdb->get_results(
			"SELECT id, name FROM {$wpdb->prefix}rm_regions WHERE status = 'active' ORDER BY name ASC"
		);

		// Add an action for each region.
		foreach ( $regions as $region ) {
			$bulk_actions[ 'rm_assign_region_' . $region->id ] = sprintf(
				/* translators: %s: Region name */
				__( 'Assign to: %s', 'region-manager' ),
				$region->name
			);
		}

		// Add "Remove from All Regions" action.
		$bulk_actions['rm_remove_all_regions'] = __( 'Remove from All Regions', 'region-manager' );

		return $bulk_actions;
	}

	/**
	 * Handle bulk actions for region assignment.
	 *
	 * @param string $redirect_to Redirect URL.
	 * @param string $action Action name.
	 * @param array  $post_ids Post IDs.
	 * @return string Modified redirect URL.
	 */
	public function handle_bulk_actions( $redirect_to, $action, $post_ids ) {
		// Handle "Assign to All Regions".
		if ( 'rm_assign_all_regions' === $action ) {
			$count = $this->bulk_assign_all_regions( $post_ids );
			$redirect_to = add_query_arg( 'rm_bulk_assigned_all', $count, $redirect_to );
			return $redirect_to;
		}

		// Handle "Remove from All Regions".
		if ( 'rm_remove_all_regions' === $action ) {
			$count = $this->bulk_remove_all_regions( $post_ids );
			$redirect_to = add_query_arg( 'rm_bulk_removed_all', $count, $redirect_to );
			return $redirect_to;
		}

		// Handle region-specific assignment.
		if ( strpos( $action, 'rm_assign_region_' ) === 0 ) {
			$region_id = intval( str_replace( 'rm_assign_region_', '', $action ) );
			if ( $region_id > 0 ) {
				$count = $this->bulk_assign_to_region( $post_ids, $region_id );
				$redirect_to = add_query_arg( 'rm_bulk_assigned', $count, $redirect_to );
				$redirect_to = add_query_arg( 'rm_region_id', $region_id, $redirect_to );
			}
			return $redirect_to;
		}

		return $redirect_to;
	}

	/**
	 * Bulk assign products to all regions.
	 *
	 * @param array $product_ids Product IDs.
	 * @return int Number of products assigned.
	 */
	private function bulk_assign_all_regions( $product_ids ) {
		global $wpdb;
		$table = $wpdb->prefix . 'rm_product_regions';
		$count = 0;

		foreach ( $product_ids as $product_id ) {
			// Clear existing assignments.
			$wpdb->delete( $table, array( 'product_id' => $product_id ), array( '%d' ) );

			// Insert "All Regions" assignment.
			$result = $wpdb->insert(
				$table,
				array(
					'product_id'          => $product_id,
					'region_id'           => 0,
					'price_override'      => null,
					'sale_price_override' => null,
				),
				array( '%d', '%d', '%s', '%s' )
			);

			if ( $result ) {
				++$count;
			}
		}

		return $count;
	}

	/**
	 * Bulk remove products from all regions.
	 *
	 * @param array $product_ids Product IDs.
	 * @return int Number of products updated.
	 */
	private function bulk_remove_all_regions( $product_ids ) {
		global $wpdb;
		$table = $wpdb->prefix . 'rm_product_regions';
		$count = 0;

		foreach ( $product_ids as $product_id ) {
			$result = $wpdb->delete( $table, array( 'product_id' => $product_id ), array( '%d' ) );
			if ( $result !== false ) {
				++$count;
			}
		}

		return $count;
	}

	/**
	 * Bulk assign products to a specific region.
	 *
	 * @param array $product_ids Product IDs.
	 * @param int   $region_id Region ID.
	 * @return int Number of products assigned.
	 */
	private function bulk_assign_to_region( $product_ids, $region_id ) {
		global $wpdb;
		$table = $wpdb->prefix . 'rm_product_regions';
		$count = 0;

		foreach ( $product_ids as $product_id ) {
			// Check if already assigned.
			$exists = $wpdb->get_var(
				$wpdb->prepare(
					"SELECT COUNT(*) FROM {$table} WHERE product_id = %d AND region_id = %d",
					$product_id,
					$region_id
				)
			);

			if ( $exists > 0 ) {
				// Already assigned, skip.
				continue;
			}

			// Remove "All Regions" if exists.
			$wpdb->delete( $table, array( 'product_id' => $product_id, 'region_id' => 0 ), array( '%d', '%d' ) );

			// Insert assignment.
			$result = $wpdb->insert(
				$table,
				array(
					'product_id'          => $product_id,
					'region_id'           => $region_id,
					'price_override'      => null,
					'sale_price_override' => null,
				),
				array( '%d', '%d', '%s', '%s' )
			);

			if ( $result ) {
				++$count;
			}
		}

		return $count;
	}

	/**
	 * Display admin notices for bulk actions.
	 */
	public function bulk_action_notices() {
		global $wpdb;

		// phpcs:disable WordPress.Security.NonceVerification.Recommended
		if ( ! empty( $_REQUEST['rm_bulk_assigned_all'] ) ) {
			$count = intval( $_REQUEST['rm_bulk_assigned_all'] );
			printf(
				'<div class="notice notice-success is-dismissible"><p>' .
				/* translators: %d: Number of products */
				esc_html( _n( '%d product assigned to All Regions.', '%d products assigned to All Regions.', $count, 'region-manager' ) ) .
				'</p></div>',
				$count
			);
		}

		if ( ! empty( $_REQUEST['rm_bulk_removed_all'] ) ) {
			$count = intval( $_REQUEST['rm_bulk_removed_all'] );
			printf(
				'<div class="notice notice-success is-dismissible"><p>' .
				/* translators: %d: Number of products */
				esc_html( _n( '%d product removed from all regions.', '%d products removed from all regions.', $count, 'region-manager' ) ) .
				'</p></div>',
				$count
			);
		}

		if ( ! empty( $_REQUEST['rm_bulk_assigned'] ) && ! empty( $_REQUEST['rm_region_id'] ) ) {
			$count     = intval( $_REQUEST['rm_bulk_assigned'] );
			$region_id = intval( $_REQUEST['rm_region_id'] );

			$region_name = $wpdb->get_var(
				$wpdb->prepare(
					"SELECT name FROM {$wpdb->prefix}rm_regions WHERE id = %d",
					$region_id
				)
			);

			printf(
				'<div class="notice notice-success is-dismissible"><p>' .
				/* translators: 1: Number of products, 2: Region name */
				esc_html( _n( '%1$d product assigned to %2$s.', '%1$d products assigned to %2$s.', $count, 'region-manager' ) ) .
				'</p></div>',
				$count,
				esc_html( $region_name )
			);
		}
		// phpcs:enable WordPress.Security.NonceVerification.Recommended
	}

	/**
	 * Enqueue admin scripts for products page.
	 *
	 * @param string $hook Current admin page hook.
	 */
	public function enqueue_admin_scripts( $hook ) {
		// Only load on products list page.
		// phpcs:disable WordPress.Security.NonceVerification.Recommended
		if ( 'edit.php' !== $hook || ! isset( $_GET['post_type'] ) || 'product' !== $_GET['post_type'] ) {
			return;
		}
		// phpcs:enable WordPress.Security.NonceVerification.Recommended

		// Add inline script for quick assign functionality.
		wp_add_inline_script( 'jquery', "
			jQuery(document).ready(function($) {
				// Handle quick assign dropdown
				$(document).on('change', '.rm-quick-assign-region', function() {
					var \$select = $(this);
					var productId = \$select.data('product-id');
					var regionId = \$select.val();

					if (!regionId) {
						return;
					}

					// Show loading
					\$select.prop('disabled', true);

					// Send AJAX request
					$.ajax({
						url: ajaxurl,
						type: 'POST',
						data: {
							action: 'rm_quick_assign_region',
							product_id: productId,
							region_id: regionId,
							nonce: '" . wp_create_nonce( 'rm_quick_assign' ) . "'
						},
						success: function(response) {
							if (response.success) {
								// Reload the page to show updated region badges
								location.reload();
							} else {
								alert(response.data.message || 'Error assigning region');
								\$select.prop('disabled', false).val('');
							}
						},
						error: function() {
							alert('Error: Could not assign region');
							\$select.prop('disabled', false).val('');
						}
					});
				});
			});
		" );
	}

	/**
	 * AJAX handler for quick region assignment.
	 */
	public function ajax_quick_assign_region() {
		check_ajax_referer( 'rm_quick_assign', 'nonce' );

		if ( ! current_user_can( 'edit_products' ) ) {
			wp_send_json_error( array( 'message' => __( 'Permission denied', 'region-manager' ) ) );
		}

		$product_id = isset( $_POST['product_id'] ) ? intval( $_POST['product_id'] ) : 0;
		$region_id  = isset( $_POST['region_id'] ) ? intval( $_POST['region_id'] ) : -1;

		if ( ! $product_id || $region_id < 0 ) {
			wp_send_json_error( array( 'message' => __( 'Invalid parameters', 'region-manager' ) ) );
		}

		global $wpdb;
		$table = $wpdb->prefix . 'rm_product_regions';

		// Clear existing assignments.
		$wpdb->delete( $table, array( 'product_id' => $product_id ), array( '%d' ) );

		// Insert new assignment.
		$result = $wpdb->insert(
			$table,
			array(
				'product_id'          => $product_id,
				'region_id'           => $region_id,
				'price_override'      => null,
				'sale_price_override' => null,
			),
			array( '%d', '%d', '%s', '%s' )
		);

		if ( false === $result ) {
			wp_send_json_error( array( 'message' => __( 'Database error', 'region-manager' ) ) );
		}

		if ( 0 === $region_id ) {
			wp_send_json_success( array( 'message' => __( 'Product assigned to All Regions', 'region-manager' ) ) );
		} else {
			$region_name = $wpdb->get_var(
				$wpdb->prepare(
					"SELECT name FROM {$wpdb->prefix}rm_regions WHERE id = %d",
					$region_id
				)
			);

			wp_send_json_success(
				array(
					/* translators: %s: Region name */
					'message' => sprintf( __( 'Product assigned to %s', 'region-manager' ), $region_name ),
				)
			);
		}
	}
}
