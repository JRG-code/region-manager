<?php
/**
 * Product Meta Box for WooCommerce integration.
 *
 * Adds a WooCommerce Product Data tab for regional and country-specific pricing.
 *
 * @package    Region_Manager
 * @subpackage Region_Manager/admin
 */

/**
 * Product Meta Box class.
 *
 * Handles the region availability and multi-currency pricing as a WooCommerce Product Data tab.
 */
class RM_Product_Meta_Box {

	/**
	 * Initialize the class.
	 */
	public function __construct() {
		// Add WooCommerce Product Data tab.
		add_filter( 'woocommerce_product_data_tabs', array( $this, 'add_regional_pricing_tab' ) );
		add_action( 'woocommerce_product_data_panels', array( $this, 'render_regional_pricing_panel' ) );
		add_action( 'woocommerce_process_product_meta', array( $this, 'save_regional_pricing' ), 10, 2 );

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

		// Enqueue scripts for product edit page.
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_product_edit_scripts' ) );
	}

	/**
	 * Add Regional Pricing tab to WooCommerce Product Data.
	 *
	 * @param array $tabs Product data tabs.
	 * @return array Modified tabs.
	 */
	public function add_regional_pricing_tab( $tabs ) {
		$tabs['regional_pricing'] = array(
			'label'    => __( 'Regional Pricing', 'region-manager' ),
			'target'   => 'regional_pricing_product_data',
			'class'    => array( 'show_if_simple', 'show_if_variable' ),
			'priority' => 75,
		);
		return $tabs;
	}

	/**
	 * Render the Regional Pricing panel.
	 */
	public function render_regional_pricing_panel() {
		global $post;
		$product_id = $post->ID;
		$product    = wc_get_product( $product_id );
		$regions    = $this->get_all_regions();

		if ( empty( $regions ) ) {
			$this->render_no_regions_message();
			return;
		}

		// Get base WooCommerce price and currency.
		$base_price      = $product ? $product->get_regular_price() : '';
		$base_sale_price = $product ? $product->get_sale_price() : '';
		$base_currency   = get_woocommerce_currency();
		$base_symbol     = get_woocommerce_currency_symbol( $base_currency );

		// Get regional pricing data.
		$product_regions   = $this->get_product_regions( $product_id );
		$product_countries = $this->get_product_country_prices( $product_id );

		?>
		<div id="regional_pricing_product_data" class="panel woocommerce_options_panel">
			<div class="rm-regional-pricing-wrapper">

				<!-- Base Price Info -->
				<div class="rm-base-price-info">
					<h4><?php esc_html_e( 'WooCommerce Base Price', 'region-manager' ); ?></h4>
					<p class="description">
						<?php
						printf(
							/* translators: 1: currency symbol, 2: price, 3: sale price, 4: currency code */
							esc_html__( 'Regular: %1$s%2$s | Sale: %1$s%3$s | Currency: %4$s', 'region-manager' ),
							esc_html( $base_symbol ),
							esc_html( $base_price ?: '—' ),
							esc_html( $base_sale_price ?: '—' ),
							esc_html( $base_currency )
						);
						?>
					</p>
					<p class="description">
						<?php esc_html_e( 'Set regional and country-specific prices below. Leave empty to use base price.', 'region-manager' ); ?>
					</p>
				</div>

				<hr style="margin: 20px 0;">

				<!-- Region Tabs -->
				<div class="rm-region-tabs">
					<ul class="rm-tab-nav">
						<?php foreach ( $regions as $index => $region ) : ?>
							<li class="<?php echo 0 === $index ? 'active' : ''; ?>">
								<a href="#rm-region-<?php echo esc_attr( $region->id ); ?>" data-region-id="<?php echo esc_attr( $region->id ); ?>">
									<?php echo esc_html( $region->name ); ?>
								</a>
							</li>
						<?php endforeach; ?>
					</ul>

					<div class="rm-tab-content">
						<?php foreach ( $regions as $index => $region ) : ?>
							<?php
							$region_data         = isset( $product_regions[ $region->id ] ) ? $product_regions[ $region->id ] : null;
							$is_available        = $region_data ? true : false;
							$price_override      = $region_data && $region_data->price_override ? $region_data->price_override : '';
							$sale_price_override = $region_data && $region_data->sale_price_override ? $region_data->sale_price_override : '';
							$countries           = $this->get_region_countries( $region->id );
							?>
							<div id="rm-region-<?php echo esc_attr( $region->id ); ?>" class="rm-tab-panel <?php echo 0 === $index ? 'active' : ''; ?>" data-region-id="<?php echo esc_attr( $region->id ); ?>">

								<!-- Region Level Settings -->
								<div class="rm-region-settings">
									<h4><?php echo esc_html( $region->name ); ?> - <?php esc_html_e( 'Region Settings', 'region-manager' ); ?></h4>

									<p class="form-field">
										<label>
											<input type="checkbox"
												   name="rm_regions[<?php echo esc_attr( $region->id ); ?>][available]"
												   class="rm-region-available"
												   value="1"
												   <?php checked( $is_available ); ?>>
											<?php esc_html_e( 'Product available in this region', 'region-manager' ); ?>
										</label>
									</p>

									<p class="description" style="margin-bottom: 15px;">
										<?php esc_html_e( 'Set default prices for this region (applies to all countries unless overridden below):', 'region-manager' ); ?>
									</p>

									<?php
									woocommerce_wp_text_input(
										array(
											'id'          => 'rm_region_price_' . $region->id,
											'name'        => 'rm_regions[' . $region->id . '][price]',
											'label'       => __( 'Region Price', 'region-manager' ) . ' (' . $base_currency . ')',
											'value'       => $price_override,
											'placeholder' => $base_price,
											'type'        => 'text',
											'data_type'   => 'price',
											'desc_tip'    => true,
											'description' => __( 'Default price for all countries in this region. Leave empty to use base price.', 'region-manager' ),
										)
									);

									woocommerce_wp_text_input(
										array(
											'id'          => 'rm_region_sale_price_' . $region->id,
											'name'        => 'rm_regions[' . $region->id . '][sale_price]',
											'label'       => __( 'Region Sale Price', 'region-manager' ) . ' (' . $base_currency . ')',
											'value'       => $sale_price_override,
											'placeholder' => $base_sale_price,
											'type'        => 'text',
											'data_type'   => 'price',
											'desc_tip'    => true,
											'description' => __( 'Sale price for all countries in this region. Leave empty to use base sale price.', 'region-manager' ),
										)
									);
									?>
								</div>

								<hr style="margin: 20px 0;">

								<!-- Country Specific Pricing -->
								<div class="rm-country-pricing">
									<h4><?php esc_html_e( 'Country-Specific Pricing', 'region-manager' ); ?></h4>
									<p class="description" style="margin-bottom: 15px;">
										<?php esc_html_e( 'Override prices for specific countries. These prices take priority over region-level prices.', 'region-manager' ); ?>
									</p>

									<?php if ( ! empty( $countries ) ) : ?>
										<table class="widefat rm-country-prices-table">
											<thead>
												<tr>
													<th><?php esc_html_e( 'Country', 'region-manager' ); ?></th>
													<th><?php esc_html_e( 'Currency', 'region-manager' ); ?></th>
													<th><?php esc_html_e( 'Price', 'region-manager' ); ?></th>
													<th><?php esc_html_e( 'Sale Price', 'region-manager' ); ?></th>
												</tr>
											</thead>
											<tbody>
												<?php foreach ( $countries as $country ) : ?>
													<?php
													$country_price_data = isset( $product_countries[ $country->country_code ] ) ? $product_countries[ $country->country_code ] : null;
													$country_price      = $country_price_data ? $country_price_data->price : '';
													$country_sale_price = $country_price_data ? $country_price_data->sale_price : '';
													$currency_code      = $country->currency_code ?: 'EUR';
													$currency_symbol    = get_woocommerce_currency_symbol( $currency_code );
													$is_diff_currency   = $currency_code !== $base_currency;
													$country_name       = $this->get_country_name( $country->country_code );
													?>
													<tr>
														<td>
															<strong><?php echo esc_html( $country_name ); ?></strong>
															<br>
															<small><?php echo esc_html( $country->country_code ); ?></small>
														</td>
														<td>
															<?php echo esc_html( $currency_code ); ?> (<?php echo esc_html( $currency_symbol ); ?>)
															<?php if ( $is_diff_currency ) : ?>
																<br><span class="rm-currency-warning">⚠ <?php esc_html_e( 'Different currency', 'region-manager' ); ?></span>
															<?php endif; ?>
														</td>
														<td>
															<input type="text"
																   name="rm_countries[<?php echo esc_attr( $country->country_code ); ?>][price]"
																   value="<?php echo esc_attr( $country_price ); ?>"
																   placeholder="<?php echo esc_attr( $price_override ?: $base_price ); ?>"
																   class="short wc_input_price">
															<input type="hidden"
																   name="rm_countries[<?php echo esc_attr( $country->country_code ); ?>][currency]"
																   value="<?php echo esc_attr( $currency_code ); ?>">
														</td>
														<td>
															<input type="text"
																   name="rm_countries[<?php echo esc_attr( $country->country_code ); ?>][sale_price]"
																   value="<?php echo esc_attr( $country_sale_price ); ?>"
																   placeholder="<?php echo esc_attr( $sale_price_override ?: $base_sale_price ); ?>"
																   class="short wc_input_price">
														</td>
													</tr>
												<?php endforeach; ?>
											</tbody>
										</table>
									<?php else : ?>
										<p class="description">
											<?php esc_html_e( 'No countries assigned to this region yet.', 'region-manager' ); ?>
											<a href="<?php echo esc_url( admin_url( 'admin.php?page=region-manager-settings' ) ); ?>">
												<?php esc_html_e( 'Add countries in region settings.', 'region-manager' ); ?>
											</a>
										</p>
									<?php endif; ?>
								</div>

							</div>
						<?php endforeach; ?>
					</div>
				</div>

				<style>
					.rm-regional-pricing-wrapper { padding: 12px; }
					.rm-base-price-info { background: #f0f6fc; padding: 12px; border-left: 4px solid #2271b1; margin-bottom: 20px; }
					.rm-base-price-info h4 { margin: 0 0 10px 0; color: #2271b1; }
					.rm-base-price-info p { margin: 5px 0; }

					.rm-tab-nav { margin: 0; padding: 0; list-style: none; border-bottom: 1px solid #ddd; }
					.rm-tab-nav li { display: inline-block; margin: 0; }
					.rm-tab-nav li a { display: block; padding: 10px 15px; text-decoration: none; color: #555; border: 1px solid transparent; border-bottom: none; background: #f9f9f9; }
					.rm-tab-nav li.active a { background: #fff; border-color: #ddd; color: #2271b1; font-weight: 600; }
					.rm-tab-nav li a:hover { background: #fff; }

					.rm-tab-content { border: 1px solid #ddd; border-top: none; }
					.rm-tab-panel { display: none; padding: 20px; }
					.rm-tab-panel.active { display: block; }

					.rm-region-settings { background: #fafafa; padding: 15px; border: 1px solid #e0e0e0; }
					.rm-country-pricing { margin-top: 20px; }
					.rm-country-prices-table { margin-top: 10px; }
					.rm-country-prices-table th { background: #f9f9f9; padding: 8px; }
					.rm-country-prices-table td { padding: 8px; vertical-align: top; }
					.rm-currency-warning { color: #d63638; font-weight: 600; font-size: 11px; }
				</style>

				<script>
				jQuery(document).ready(function($) {
					// Tab switching
					$('.rm-tab-nav a').on('click', function(e) {
						e.preventDefault();
						var regionId = $(this).data('region-id');

						// Update active tab
						$('.rm-tab-nav li').removeClass('active');
						$(this).parent().addClass('active');

						// Show panel
						$('.rm-tab-panel').removeClass('active');
						$('#rm-region-' + regionId).addClass('active');
					});

					// Enable/disable region fields based on availability checkbox
					$('.rm-region-available').on('change', function() {
						var $panel = $(this).closest('.rm-tab-panel');
						var isChecked = $(this).is(':checked');

						// No need to disable fields - just let user configure them
						// The checkbox controls whether the product is available in this region
					});
				});
				</script>
			</div>
		</div>
		<?php
	}

	/**
	 * Render message when no regions are configured.
	 */
	private function render_no_regions_message() {
		?>
		<div id="regional_pricing_product_data" class="panel woocommerce_options_panel">
			<div class="rm-regional-pricing-wrapper" style="padding: 20px;">
				<p><?php esc_html_e( 'No regions configured.', 'region-manager' ); ?>
				<a href="<?php echo esc_url( admin_url( 'admin.php?page=region-manager-settings' ) ); ?>">
					<?php esc_html_e( 'Create regions in settings.', 'region-manager' ); ?>
				</a></p>
			</div>
		</div>
		<?php
	}

	/**
	 * Save regional and country-specific pricing.
	 *
	 * @param int $product_id Product ID.
	 */
	public function save_regional_pricing( $product_id ) {
		if ( ! current_user_can( 'edit_product', $product_id ) ) {
			return;
		}

		global $wpdb;
		$table_regions   = $wpdb->prefix . 'rm_product_regions';
		$table_countries = $wpdb->prefix . 'rm_product_country_prices';

		// Clear existing data.
		$wpdb->delete( $table_regions, array( 'product_id' => $product_id ), array( '%d' ) );
		$wpdb->delete( $table_countries, array( 'product_id' => $product_id ), array( '%d' ) );

		// Save region-level pricing.
		$regions_data = isset( $_POST['rm_regions'] ) ? $_POST['rm_regions'] : array(); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized, WordPress.Security.NonceVerification.Missing

		foreach ( $regions_data as $region_id => $data ) {
			if ( ! empty( $data['available'] ) ) {
				$price      = ! empty( $data['price'] ) ? floatval( $data['price'] ) : null;
				$sale_price = ! empty( $data['sale_price'] ) ? floatval( $data['sale_price'] ) : null;

				$wpdb->insert(
					$table_regions,
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

		// Save country-specific pricing.
		$countries_data = isset( $_POST['rm_countries'] ) ? $_POST['rm_countries'] : array(); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized, WordPress.Security.NonceVerification.Missing

		foreach ( $countries_data as $country_code => $data ) {
			// Only save if at least one price field is set.
			if ( ! empty( $data['price'] ) || ! empty( $data['sale_price'] ) ) {
				$price        = ! empty( $data['price'] ) ? floatval( $data['price'] ) : null;
				$sale_price   = ! empty( $data['sale_price'] ) ? floatval( $data['sale_price'] ) : null;
				$currency     = ! empty( $data['currency'] ) ? sanitize_text_field( wp_unslash( $data['currency'] ) ) : 'EUR';

				$wpdb->insert(
					$table_countries,
					array(
						'product_id'    => $product_id,
						'country_code'  => sanitize_text_field( $country_code ),
						'price'         => $price,
						'sale_price'    => $sale_price,
						'currency_code' => $currency,
					),
					array( '%d', '%s', '%f', '%f', '%s' )
				);
			}
		}

		// Clear WooCommerce product transients.
		if ( function_exists( 'wc_delete_product_transients' ) ) {
			wc_delete_product_transients( $product_id );
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
		); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
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
		); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching

		$indexed = array();
		foreach ( $results as $row ) {
			$indexed[ $row->region_id ] = $row;
		}
		return $indexed;
	}

	/**
	 * Get product's country-specific prices.
	 *
	 * @param int $product_id Product ID.
	 * @return array Indexed by country_code.
	 */
	private function get_product_country_prices( $product_id ) {
		global $wpdb;
		$results = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT * FROM {$wpdb->prefix}rm_product_country_prices WHERE product_id = %d",
				$product_id
			)
		); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching

		$indexed = array();
		foreach ( $results as $row ) {
			$indexed[ $row->country_code ] = $row;
		}
		return $indexed;
	}

	/**
	 * Get countries for a region with currency info.
	 *
	 * @param int $region_id Region ID.
	 * @return array Array of country objects.
	 */
	private function get_region_countries( $region_id ) {
		global $wpdb;
		return $wpdb->get_results(
			$wpdb->prepare(
				"SELECT * FROM {$wpdb->prefix}rm_region_countries WHERE region_id = %d ORDER BY country_code",
				$region_id
			)
		); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
	}

	/**
	 * Get country name from country code.
	 *
	 * @param string $country_code Two-letter country code.
	 * @return string Country name.
	 */
	private function get_country_name( $country_code ) {
		if ( function_exists( 'WC' ) && WC()->countries ) {
			$countries = WC()->countries->get_countries();
			return isset( $countries[ $country_code ] ) ? $countries[ $country_code ] : $country_code;
		}
		return $country_code;
	}

	/**
	 * Enqueue scripts for product edit page.
	 *
	 * @param string $hook Current admin page hook.
	 */
	public function enqueue_product_edit_scripts( $hook ) {
		if ( 'post.php' !== $hook && 'post-new.php' !== $hook ) {
			return;
		}

		$screen = get_current_screen();
		if ( $screen && 'product' === $screen->post_type ) {
			wp_enqueue_script( 'wc-admin-meta-boxes' );
			wp_enqueue_style( 'woocommerce_admin_styles' );
		}
	}

	/**
	 * Add Regions column to products list.
	 *
	 * @param array $columns Existing columns.
	 * @return array Modified columns.
	 */
	public function add_regions_column( $columns ) {
		$new_columns = array();
		foreach ( $columns as $key => $value ) {
			$new_columns[ $key ] = $value;
			if ( 'product_cat' === $key ) {
				$new_columns['regions'] = __( 'Regions', 'region-manager' );
			}
		}
		return $new_columns;
	}

	/**
	 * Render Regions column content.
	 *
	 * @param string $column  Column name.
	 * @param int    $post_id Product ID.
	 */
	public function render_regions_column( $column, $post_id ) {
		if ( 'regions' !== $column ) {
			return;
		}

		$product_regions = $this->get_product_regions( $post_id );

		if ( empty( $product_regions ) ) {
			echo '<div class="rm-regions-badge-wrapper">';
			echo '<span class="rm-no-regions">';

			// Add quick-assign dropdown.
			$regions = $this->get_all_regions();
			if ( ! empty( $regions ) ) {
				?>
				<select class="rm-quick-assign" data-product-id="<?php echo esc_attr( $post_id ); ?>" style="font-size: 11px; padding: 2px;">
					<option value=""><?php esc_html_e( 'Add to region...', 'region-manager' ); ?></option>
					<?php foreach ( $regions as $region ) : ?>
						<option value="<?php echo esc_attr( $region->id ); ?>"><?php echo esc_html( $region->name ); ?></option>
					<?php endforeach; ?>
				</select>
				<?php
			} else {
				esc_html_e( 'No regions', 'region-manager' );
			}

			echo '</span>';
			echo '</div>';
			return;
		}

		echo '<div class="rm-regions-badge-wrapper">';
		foreach ( $product_regions as $region_data ) {
			$region = $this->get_region_by_id( $region_data->region_id );
			if ( $region ) {
				echo '<span class="rm-region-badge" style="display: inline-block; background: #2271b1; color: white; padding: 2px 6px; border-radius: 3px; font-size: 11px; margin: 2px;">';
				echo esc_html( $region->name );
				echo '</span>';
			}
		}
		echo '</div>';
	}

	/**
	 * Get region by ID.
	 *
	 * @param int $region_id Region ID.
	 * @return object|null Region object or null.
	 */
	private function get_region_by_id( $region_id ) {
		global $wpdb;
		return $wpdb->get_row(
			$wpdb->prepare(
				"SELECT * FROM {$wpdb->prefix}rm_regions WHERE id = %d",
				$region_id
			)
		); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
	}

	/**
	 * Register bulk actions for products.
	 *
	 * @param array $bulk_actions Existing bulk actions.
	 * @return array Modified bulk actions.
	 */
	public function register_bulk_actions( $bulk_actions ) {
		$regions = $this->get_all_regions();
		foreach ( $regions as $region ) {
			$bulk_actions[ 'assign_region_' . $region->id ] = sprintf(
				/* translators: %s: region name */
				__( 'Assign to %s', 'region-manager' ),
				$region->name
			);
		}
		return $bulk_actions;
	}

	/**
	 * Handle bulk actions for region assignment.
	 *
	 * @param string $redirect_to Redirect URL.
	 * @param string $action      Action being performed.
	 * @param array  $post_ids    Selected product IDs.
	 * @return string Modified redirect URL.
	 */
	public function handle_bulk_actions( $redirect_to, $action, $post_ids ) {
		if ( 0 !== strpos( $action, 'assign_region_' ) ) {
			return $redirect_to;
		}

		$region_id = absint( str_replace( 'assign_region_', '', $action ) );

		global $wpdb;
		$table = $wpdb->prefix . 'rm_product_regions';

		foreach ( $post_ids as $product_id ) {
			// Check if already assigned.
			$exists = $wpdb->get_var(
				$wpdb->prepare(
					"SELECT COUNT(*) FROM {$table} WHERE product_id = %d AND region_id = %d",
					$product_id,
					$region_id
				)
			); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching

			if ( ! $exists ) {
				$wpdb->insert(
					$table,
					array(
						'product_id' => $product_id,
						'region_id'  => $region_id,
					),
					array( '%d', '%d' )
				);
			}
		}

		$redirect_to = add_query_arg( 'bulk_assigned_regions', count( $post_ids ), $redirect_to );
		return $redirect_to;
	}

	/**
	 * Show admin notice after bulk actions.
	 */
	public function bulk_action_notices() {
		if ( ! empty( $_REQUEST['bulk_assigned_regions'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			$count = intval( $_REQUEST['bulk_assigned_regions'] ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			printf(
				'<div class="notice notice-success is-dismissible"><p>' .
				/* translators: %d: number of products */
				esc_html( _n( '%d product assigned to region.', '%d products assigned to region.', $count, 'region-manager' ) ) .
				'</p></div>',
				esc_html( $count )
			);
		}
	}

	/**
	 * AJAX handler for quick region assignment.
	 */
	public function ajax_quick_assign_region() {
		check_ajax_referer( 'rm_admin_nonce', 'nonce' );

		if ( ! current_user_can( 'edit_products' ) ) {
			wp_send_json_error( array( 'message' => __( 'Permission denied.', 'region-manager' ) ) );
		}

		$product_id = isset( $_POST['product_id'] ) ? absint( $_POST['product_id'] ) : 0;
		$region_id  = isset( $_POST['region_id'] ) ? absint( $_POST['region_id'] ) : 0;

		if ( ! $product_id || ! $region_id ) {
			wp_send_json_error( array( 'message' => __( 'Invalid parameters.', 'region-manager' ) ) );
		}

		global $wpdb;
		$table = $wpdb->prefix . 'rm_product_regions';

		// Check if already assigned.
		$exists = $wpdb->get_var(
			$wpdb->prepare(
				"SELECT COUNT(*) FROM {$table} WHERE product_id = %d AND region_id = %d",
				$product_id,
				$region_id
			)
		); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching

		if ( $exists ) {
			wp_send_json_error( array( 'message' => __( 'Product already assigned to this region.', 'region-manager' ) ) );
		}

		// Insert assignment.
		$wpdb->insert(
			$table,
			array(
				'product_id' => $product_id,
				'region_id'  => $region_id,
			),
			array( '%d', '%d' )
		);

		wp_send_json_success( array( 'message' => __( 'Product assigned to region.', 'region-manager' ) ) );
	}

	/**
	 * Enqueue admin scripts for products list page.
	 *
	 * @param string $hook Current admin page hook.
	 */
	public function enqueue_admin_scripts( $hook ) {
		if ( 'edit.php' !== $hook ) {
			return;
		}

		$screen = get_current_screen();
		if ( $screen && 'product' === $screen->post_type ) {
			wp_add_inline_script(
				'jquery',
				"
				jQuery(document).ready(function($) {
					$('.rm-quick-assign').on('change', function() {
						var productId = $(this).data('product-id');
						var regionId = $(this).val();
						var \$select = $(this);

						if (!regionId) return;

						$.ajax({
							url: ajaxurl,
							type: 'POST',
							data: {
								action: 'rm_quick_assign_region',
								nonce: rmAdmin.nonce,
								product_id: productId,
								region_id: regionId
							},
							success: function(response) {
								if (response.success) {
									location.reload();
								} else {
									alert(response.data.message);
									\$select.val('');
								}
							}
						});
					});
				});
				"
			);
		}
	}
}
