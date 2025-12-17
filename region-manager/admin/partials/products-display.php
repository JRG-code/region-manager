<?php
/**
 * Provide an admin area view for products management.
 *
 * @package    Region_Manager
 * @subpackage Region_Manager/admin/partials
 */

// Get all active regions for filters.
global $wpdb;
$regions = $wpdb->get_results( "SELECT * FROM {$wpdb->prefix}rm_regions WHERE status = 'active' ORDER BY name ASC", ARRAY_A );
?>

<div class="wrap rm-products-page">
	<h1 class="wp-heading-inline"><?php esc_html_e( 'Products', 'region-manager' ); ?></h1>
	<hr class="wp-header-end">

	<!-- Top Bar -->
	<div class="rm-products-topbar">
		<div class="rm-topbar-left">
			<!-- Region Filter -->
			<select id="rm-region-filter" class="rm-region-filter">
				<option value=""><?php esc_html_e( 'All Regions', 'region-manager' ); ?></option>
				<?php foreach ( $regions as $region ) : ?>
					<option value="<?php echo esc_attr( $region['id'] ); ?>">
						<?php echo esc_html( $region['name'] ); ?>
					</option>
				<?php endforeach; ?>
			</select>

			<!-- Search Box -->
			<input type="search" id="rm-product-search" class="rm-product-search" placeholder="<?php esc_attr_e( 'Search products by name or SKU...', 'region-manager' ); ?>">

			<!-- Bulk Actions -->
			<select id="rm-bulk-action" class="rm-bulk-action">
				<option value=""><?php esc_html_e( 'Bulk Actions', 'region-manager' ); ?></option>
				<option value="assign"><?php esc_html_e( 'Assign to Region', 'region-manager' ); ?></option>
				<option value="remove"><?php esc_html_e( 'Remove from Region', 'region-manager' ); ?></option>
			</select>

			<button type="button" id="rm-apply-bulk" class="button"><?php esc_html_e( 'Apply', 'region-manager' ); ?></button>
		</div>

		<div class="rm-topbar-right">
			<span class="rm-products-count"></span>
		</div>
	</div>

	<!-- Products Table -->
	<div class="rm-products-table-wrapper">
		<table class="wp-list-table widefat fixed striped rm-products-table">
			<thead>
				<tr>
					<td class="check-column">
						<input type="checkbox" id="rm-select-all-products">
					</td>
					<th class="column-image"><?php esc_html_e( 'Image', 'region-manager' ); ?></th>
					<th class="column-name" data-sort="title"><?php esc_html_e( 'Product Name', 'region-manager' ); ?></th>
					<th class="column-sku" data-sort="sku"><?php esc_html_e( 'SKU', 'region-manager' ); ?></th>
					<th class="column-price"><?php esc_html_e( 'Base Price', 'region-manager' ); ?></th>
					<th class="column-regions"><?php esc_html_e( 'Regions', 'region-manager' ); ?></th>
					<th class="column-stock"><?php esc_html_e( 'Stock', 'region-manager' ); ?></th>
					<th class="column-actions"><?php esc_html_e( 'Actions', 'region-manager' ); ?></th>
				</tr>
			</thead>
			<tbody id="rm-products-tbody">
				<tr class="rm-loading-row">
					<td colspan="8" class="rm-loading">
						<span class="spinner is-active"></span>
						<?php esc_html_e( 'Loading products...', 'region-manager' ); ?>
					</td>
				</tr>
			</tbody>
			<tfoot>
				<tr>
					<td class="check-column">
						<input type="checkbox">
					</td>
					<th class="column-image"><?php esc_html_e( 'Image', 'region-manager' ); ?></th>
					<th class="column-name"><?php esc_html_e( 'Product Name', 'region-manager' ); ?></th>
					<th class="column-sku"><?php esc_html_e( 'SKU', 'region-manager' ); ?></th>
					<th class="column-price"><?php esc_html_e( 'Base Price', 'region-manager' ); ?></th>
					<th class="column-regions"><?php esc_html_e( 'Regions', 'region-manager' ); ?></th>
					<th class="column-stock"><?php esc_html_e( 'Stock', 'region-manager' ); ?></th>
					<th class="column-actions"><?php esc_html_e( 'Actions', 'region-manager' ); ?></th>
				</tr>
			</tfoot>
		</table>
	</div>

	<!-- Pagination -->
	<div class="rm-pagination">
		<div class="rm-pagination-info"></div>
		<div class="rm-pagination-links"></div>
	</div>
</div>

<!-- Edit Regions Modal -->
<div id="rm-edit-regions-modal" class="rm-modal" style="display: none;">
	<div class="rm-modal-overlay"></div>
	<div class="rm-modal-content">
		<div class="rm-modal-header">
			<h2><?php esc_html_e( 'Edit Product Regions', 'region-manager' ); ?></h2>
			<button type="button" class="rm-modal-close">
				<span class="dashicons dashicons-no"></span>
			</button>
		</div>
		<div class="rm-modal-body">
			<div class="rm-product-info">
				<div class="rm-product-image"></div>
				<div class="rm-product-details">
					<h3 class="rm-product-name"></h3>
					<div class="rm-product-meta">
						<span class="rm-product-base-price"></span>
						<a href="#" class="rm-product-wc-link" target="_blank"><?php esc_html_e( 'View in WooCommerce', 'region-manager' ); ?></a>
					</div>
				</div>
			</div>

			<div class="rm-regions-assignment">
				<div class="rm-region-items"></div>
			</div>

			<div class="rm-variation-option" style="display: none;">
				<label>
					<input type="checkbox" id="rm-apply-to-variations">
					<?php esc_html_e( 'Apply to all variations', 'region-manager' ); ?>
				</label>
			</div>
		</div>
		<div class="rm-modal-footer">
			<button type="button" class="button rm-modal-cancel"><?php esc_html_e( 'Cancel', 'region-manager' ); ?></button>
			<button type="button" class="button button-primary rm-save-product-regions"><?php esc_html_e( 'Save Changes', 'region-manager' ); ?></button>
		</div>
	</div>
</div>

<!-- Bulk Assign Modal -->
<div id="rm-bulk-assign-modal" class="rm-modal" style="display: none;">
	<div class="rm-modal-overlay"></div>
	<div class="rm-modal-content">
		<div class="rm-modal-header">
			<h2 class="rm-bulk-modal-title"><?php esc_html_e( 'Bulk Assign to Region', 'region-manager' ); ?></h2>
			<button type="button" class="rm-modal-close">
				<span class="dashicons dashicons-no"></span>
			</button>
		</div>
		<div class="rm-modal-body">
			<div class="rm-bulk-info">
				<p class="rm-bulk-count"></p>
			</div>

			<div class="rm-form-group">
				<label for="rm-bulk-region">
					<?php esc_html_e( 'Select Region', 'region-manager' ); ?>
					<span class="required">*</span>
				</label>
				<select id="rm-bulk-region" class="widefat" required>
					<option value=""><?php esc_html_e( 'Choose a region...', 'region-manager' ); ?></option>
					<?php foreach ( $regions as $region ) : ?>
						<option value="<?php echo esc_attr( $region['id'] ); ?>">
							<?php echo esc_html( $region['name'] ); ?>
						</option>
					<?php endforeach; ?>
				</select>
			</div>

			<div class="rm-bulk-action-options" style="display: none;">
				<div class="rm-form-group">
					<label>
						<input type="checkbox" id="rm-bulk-set-price">
						<?php esc_html_e( 'Set price override', 'region-manager' ); ?>
					</label>
				</div>

				<div class="rm-form-group rm-bulk-price-fields" style="display: none;">
					<label for="rm-bulk-price-override">
						<?php esc_html_e( 'Regular Price Override', 'region-manager' ); ?>
					</label>
					<input type="text" id="rm-bulk-price-override" class="widefat" placeholder="<?php esc_attr_e( 'Leave empty to use base price', 'region-manager' ); ?>">
				</div>

				<div class="rm-form-group rm-bulk-price-fields" style="display: none;">
					<label for="rm-bulk-sale-price-override">
						<?php esc_html_e( 'Sale Price Override', 'region-manager' ); ?>
					</label>
					<input type="text" id="rm-bulk-sale-price-override" class="widefat" placeholder="<?php esc_attr_e( 'Leave empty to use base sale price', 'region-manager' ); ?>">
				</div>

				<div class="rm-form-group">
					<label>
						<input type="checkbox" id="rm-bulk-apply-variations">
						<?php esc_html_e( 'Apply to all variations of variable products', 'region-manager' ); ?>
					</label>
				</div>
			</div>
		</div>
		<div class="rm-modal-footer">
			<button type="button" class="button rm-modal-cancel"><?php esc_html_e( 'Cancel', 'region-manager' ); ?></button>
			<button type="button" class="button button-primary rm-bulk-assign-save"><?php esc_html_e( 'Apply', 'region-manager' ); ?></button>
		</div>
	</div>
</div>

<script type="text/javascript">
jQuery(document).ready(function($) {
	'use strict';

	var ProductsManager = {
		currentPage: 1,
		perPage: 20,
		selectedProducts: [],
		currentFilters: {
			search: '',
			region_id: null,
			orderby: 'title',
			order: 'ASC'
		},

		init: function() {
			this.bindEvents();
			this.loadProducts();
		},

		bindEvents: function() {
			var self = this;

			// Search
			$('#rm-product-search').on('input', $.debounce(500, function() {
				self.currentFilters.search = $(this).val();
				self.currentPage = 1;
				self.loadProducts();
			}));

			// Region filter
			$('#rm-region-filter').on('change', function() {
				var value = $(this).val();
				self.currentFilters.region_id = value ? parseInt(value) : null;
				self.currentPage = 1;
				self.loadProducts();
			});

			// Select all checkbox
			$('#rm-select-all-products').on('change', function() {
				$('.rm-product-checkbox').prop('checked', $(this).is(':checked'));
				self.updateSelectedProducts();
			});

			// Individual checkboxes
			$(document).on('change', '.rm-product-checkbox', function() {
				self.updateSelectedProducts();
			});

			// Edit regions button
			$(document).on('click', '.rm-edit-regions-btn', function(e) {
				e.preventDefault();
				var productId = $(this).data('product-id');
				self.openEditModal(productId);
			});

			// Modal close
			$('.rm-modal-close, .rm-modal-cancel').on('click', function() {
				$(this).closest('.rm-modal').hide();
			});

			$('.rm-modal-overlay').on('click', function() {
				$(this).closest('.rm-modal').hide();
			});

			// Save product regions
			$('.rm-save-product-regions').on('click', function() {
				self.saveProductRegions();
			});

			// Region availability toggle
			$(document).on('change', '.rm-region-available', function() {
				var $item = $(this).closest('.rm-region-item');
				if ($(this).is(':checked')) {
					$item.find('.rm-region-pricing').slideDown();
				} else {
					$item.find('.rm-region-pricing').slideUp();
				}
			});

			// Bulk actions
			$('#rm-apply-bulk').on('click', function() {
				self.handleBulkAction();
			});

			// Bulk assign modal
			$('#rm-bulk-region').on('change', function() {
				if ($(this).val()) {
					$('.rm-bulk-action-options').slideDown();
				} else {
					$('.rm-bulk-action-options').slideUp();
				}
			});

			$('#rm-bulk-set-price').on('change', function() {
				if ($(this).is(':checked')) {
					$('.rm-bulk-price-fields').slideDown();
				} else {
					$('.rm-bulk-price-fields').slideUp();
				}
			});

			$('.rm-bulk-assign-save').on('click', function() {
				self.saveBulkAssign();
			});

			// Pagination
			$(document).on('click', '.rm-page-link', function(e) {
				e.preventDefault();
				var page = $(this).data('page');
				if (page && page !== self.currentPage) {
					self.currentPage = page;
					self.loadProducts();
				}
			});

			// Column sorting
			$('.rm-products-table th[data-sort]').on('click', function() {
				var sortBy = $(this).data('sort');
				if (self.currentFilters.orderby === sortBy) {
					self.currentFilters.order = self.currentFilters.order === 'ASC' ? 'DESC' : 'ASC';
				} else {
					self.currentFilters.orderby = sortBy;
					self.currentFilters.order = 'ASC';
				}
				self.loadProducts();
			});
		},

		loadProducts: function() {
			var self = this;

			$.ajax({
				url: ajaxurl,
				type: 'POST',
				data: {
					action: 'rm_get_products_table',
					nonce: rmAdmin.nonce,
					page: self.currentPage,
					per_page: self.perPage,
					search: self.currentFilters.search,
					region_id: self.currentFilters.region_id,
					orderby: self.currentFilters.orderby,
					order: self.currentFilters.order
				},
				beforeSend: function() {
					$('#rm-products-tbody').html('<tr class="rm-loading-row"><td colspan="8" class="rm-loading"><span class="spinner is-active"></span> <?php esc_html_e( 'Loading products...', 'region-manager' ); ?></td></tr>');
				},
				success: function(response) {
					if (response.success) {
						self.renderProducts(response.data.products);
						self.renderPagination(response.data.total, response.data.total_pages);
						$('.rm-products-count').text(response.data.total + ' <?php esc_html_e( 'products', 'region-manager' ); ?>');
					} else {
						self.showError(response.data.message);
					}
				},
				error: function() {
					self.showError('<?php esc_html_e( 'Failed to load products.', 'region-manager' ); ?>');
				}
			});
		},

		renderProducts: function(products) {
			var html = '';

			if (products.length === 0) {
				html = '<tr class="rm-no-products"><td colspan="8"><?php esc_html_e( 'No products found.', 'region-manager' ); ?></td></tr>';
			} else {
				$.each(products, function(i, product) {
					var regionsHtml = '';
					if (product.regions && product.regions.length > 0) {
						$.each(product.regions, function(j, region) {
							var priceInfo = '';
							if (region.price_override) {
								priceInfo = ' (' + region.price_override + ')';
							}
							regionsHtml += '<span class="rm-region-badge" title="' + region.name + priceInfo + '">' + region.name + '</span> ';
						});
					} else {
						regionsHtml = '<span class="rm-no-regions"><?php esc_html_e( 'No regions', 'region-manager' ); ?></span>';
					}

					var stockClass = product.stock_status === 'instock' ? 'in-stock' : 'out-of-stock';
					var stockText = product.stock_status === 'instock' ? '<?php esc_html_e( 'In stock', 'region-manager' ); ?>' : '<?php esc_html_e( 'Out of stock', 'region-manager' ); ?>';

					html += '<tr data-product-id="' + product.id + '">';
					html += '<th class="check-column"><input type="checkbox" class="rm-product-checkbox" value="' + product.id + '"></th>';
					html += '<td class="column-image">' + product.image + '</td>';
					html += '<td class="column-name"><a href="<?php echo esc_url( admin_url( 'post.php?action=edit&post=' ) ); ?>' + product.id + '" target="_blank">' + product.name + '</a></td>';
					html += '<td class="column-sku">' + (product.sku || '—') + '</td>';
					html += '<td class="column-price">' + (product.price || '—') + '</td>';
					html += '<td class="column-regions">' + regionsHtml + '</td>';
					html += '<td class="column-stock"><span class="rm-stock-status ' + stockClass + '">' + stockText + '</span></td>';
					html += '<td class="column-actions"><button type="button" class="button button-small rm-edit-regions-btn" data-product-id="' + product.id + '"><?php esc_html_e( 'Edit Regions', 'region-manager' ); ?></button></td>';
					html += '</tr>';
				});
			}

			$('#rm-products-tbody').html(html);
		},

		renderPagination: function(total, totalPages) {
			var self = this;
			var start = ((self.currentPage - 1) * self.perPage) + 1;
			var end = Math.min(self.currentPage * self.perPage, total);

			$('.rm-pagination-info').text(start + '-' + end + ' <?php esc_html_e( 'of', 'region-manager' ); ?> ' + total);

			var linksHtml = '';
			if (totalPages > 1) {
				// Previous
				linksHtml += '<a href="#" class="rm-page-link ' + (self.currentPage === 1 ? 'disabled' : '') + '" data-page="' + (self.currentPage - 1) + '">‹</a>';

				// Pages
				for (var i = 1; i <= totalPages; i++) {
					if (i === 1 || i === totalPages || (i >= self.currentPage - 2 && i <= self.currentPage + 2)) {
						linksHtml += '<a href="#" class="rm-page-link ' + (i === self.currentPage ? 'current' : '') + '" data-page="' + i + '">' + i + '</a>';
					} else if (i === self.currentPage - 3 || i === self.currentPage + 3) {
						linksHtml += '<span class="rm-page-dots">...</span>';
					}
				}

				// Next
				linksHtml += '<a href="#" class="rm-page-link ' + (self.currentPage === totalPages ? 'disabled' : '') + '" data-page="' + (self.currentPage + 1) + '">›</a>';
			}

			$('.rm-pagination-links').html(linksHtml);
		},

		updateSelectedProducts: function() {
			this.selectedProducts = [];
			$('.rm-product-checkbox:checked').each(function() {
				ProductsManager.selectedProducts.push(parseInt($(this).val()));
			});
		},

		openEditModal: function(productId) {
			var self = this;

			$.ajax({
				url: ajaxurl,
				type: 'POST',
				data: {
					action: 'rm_get_product_region_data',
					nonce: rmAdmin.nonce,
					product_id: productId
				},
				success: function(response) {
					if (response.success) {
						self.renderEditModal(response.data);
						$('#rm-edit-regions-modal').show();
					} else {
						self.showError(response.data.message);
					}
				},
				error: function() {
					self.showError('<?php esc_html_e( 'Failed to load product data.', 'region-manager' ); ?>');
				}
			});
		},

		renderEditModal: function(data) {
			var product = data.product;
			var regions = data.regions;

			// Product info
			$('.rm-product-image').html(product.image);
			$('.rm-product-name').text(product.name);
			$('.rm-product-base-price').text('<?php esc_html_e( 'Base Price:', 'region-manager' ); ?> ' + (product.base_price || '—'));
			$('.rm-product-wc-link').attr('href', '<?php echo esc_url( admin_url( 'post.php?action=edit&post=' ) ); ?>' + product.id);
			$('#rm-edit-regions-modal').data('product-id', product.id);

			// Show variation option for variable products
			if (product.type === 'variable') {
				$('.rm-variation-option').show();
			} else {
				$('.rm-variation-option').hide();
			}

			// Regions
			var regionsHtml = '';
			$.each(regions, function(i, region) {
				var checked = region.available ? 'checked' : '';
				var displayStyle = region.available ? '' : 'style="display:none;"';

				regionsHtml += '<div class="rm-region-item">';
				regionsHtml += '<div class="rm-region-header">';
				regionsHtml += '<label><input type="checkbox" class="rm-region-available" data-region-id="' + region.region_id + '" ' + checked + '> ';
				regionsHtml += '<strong><?php esc_html_e( 'Available in', 'region-manager' ); ?> ' + region.region_name + '</strong></label>';
				regionsHtml += '</div>';
				regionsHtml += '<div class="rm-region-pricing" ' + displayStyle + '>';
				regionsHtml += '<div class="rm-price-field">';
				regionsHtml += '<label><?php esc_html_e( 'Regular Price Override', 'region-manager' ); ?></label>';
				regionsHtml += '<input type="text" class="rm-price-override" data-region-id="' + region.region_id + '" value="' + (region.price_override || '') + '" placeholder="<?php esc_attr_e( 'Leave empty to use base price', 'region-manager' ); ?>">';
				regionsHtml += '</div>';
				regionsHtml += '<div class="rm-price-field">';
				regionsHtml += '<label><?php esc_html_e( 'Sale Price Override', 'region-manager' ); ?></label>';
				regionsHtml += '<input type="text" class="rm-sale-price-override" data-region-id="' + region.region_id + '" value="' + (region.sale_price_override || '') + '" placeholder="<?php esc_attr_e( 'Leave empty to use base sale price', 'region-manager' ); ?>">';
				regionsHtml += '</div>';
				regionsHtml += '</div>';
				regionsHtml += '</div>';
			});

			$('.rm-region-items').html(regionsHtml);
		},

		saveProductRegions: function() {
			var self = this;
			var productId = $('#rm-edit-regions-modal').data('product-id');
			var regions = [];

			$('.rm-region-item').each(function() {
				var $item = $(this);
				var regionId = $item.find('.rm-region-available').data('region-id');
				var available = $item.find('.rm-region-available').is(':checked');
				var priceOverride = $item.find('.rm-price-override').val();
				var salePriceOverride = $item.find('.rm-sale-price-override').val();

				regions.push({
					region_id: regionId,
					available: available,
					price_override: priceOverride,
					sale_price_override: salePriceOverride,
					apply_to_variations: $('#rm-apply-to-variations').is(':checked')
				});
			});

			$.ajax({
				url: ajaxurl,
				type: 'POST',
				data: {
					action: 'rm_save_product_regions',
					nonce: rmAdmin.nonce,
					product_id: productId,
					regions: JSON.stringify(regions)
				},
				beforeSend: function() {
					$('.rm-save-product-regions').prop('disabled', true).text('<?php esc_html_e( 'Saving...', 'region-manager' ); ?>');
				},
				success: function(response) {
					if (response.success) {
						self.showSuccess(response.data.message);
						$('#rm-edit-regions-modal').hide();
						self.loadProducts();
					} else {
						self.showError(response.data.message);
					}
				},
				error: function() {
					self.showError('<?php esc_html_e( 'Failed to save changes.', 'region-manager' ); ?>');
				},
				complete: function() {
					$('.rm-save-product-regions').prop('disabled', false).text('<?php esc_html_e( 'Save Changes', 'region-manager' ); ?>');
				}
			});
		},

		handleBulkAction: function() {
			var self = this;
			var action = $('#rm-bulk-action').val();

			if (!action) {
				self.showError('<?php esc_html_e( 'Please select a bulk action.', 'region-manager' ); ?>');
				return;
			}

			if (self.selectedProducts.length === 0) {
				self.showError('<?php esc_html_e( 'Please select at least one product.', 'region-manager' ); ?>');
				return;
			}

			// Open bulk modal
			$('.rm-bulk-count').text(self.selectedProducts.length + ' <?php esc_html_e( 'products selected', 'region-manager' ); ?>');
			$('#rm-bulk-assign-modal').data('action-type', action);

			if (action === 'assign') {
				$('.rm-bulk-modal-title').text('<?php esc_html_e( 'Bulk Assign to Region', 'region-manager' ); ?>');
				$('.rm-bulk-assign-save').text('<?php esc_html_e( 'Assign', 'region-manager' ); ?>');
			} else {
				$('.rm-bulk-modal-title').text('<?php esc_html_e( 'Bulk Remove from Region', 'region-manager' ); ?>');
				$('.rm-bulk-assign-save').text('<?php esc_html_e( 'Remove', 'region-manager' ); ?>');
			}

			$('#rm-bulk-assign-modal').show();
		},

		saveBulkAssign: function() {
			var self = this;
			var actionType = $('#rm-bulk-assign-modal').data('action-type');
			var regionId = $('#rm-bulk-region').val();

			if (!regionId) {
				self.showError('<?php esc_html_e( 'Please select a region.', 'region-manager' ); ?>');
				return;
			}

			var data = {
				action: 'rm_bulk_assign_region',
				nonce: rmAdmin.nonce,
				product_ids: self.selectedProducts,
				region_id: regionId,
				action_type: actionType
			};

			if (actionType === 'assign') {
				if ($('#rm-bulk-set-price').is(':checked')) {
					data.price_override = $('#rm-bulk-price-override').val();
					data.sale_price_override = $('#rm-bulk-sale-price-override').val();
				}
				data.apply_to_variations = $('#rm-bulk-apply-variations').is(':checked') ? '1' : '0';
			}

			$.ajax({
				url: ajaxurl,
				type: 'POST',
				data: data,
				beforeSend: function() {
					$('.rm-bulk-assign-save').prop('disabled', true).text('<?php esc_html_e( 'Processing...', 'region-manager' ); ?>');
				},
				success: function(response) {
					if (response.success) {
						self.showSuccess(response.data.message);
						$('#rm-bulk-assign-modal').hide();
						self.loadProducts();
						self.selectedProducts = [];
						$('.rm-product-checkbox, #rm-select-all-products').prop('checked', false);
						$('#rm-bulk-action').val('');
						$('#rm-bulk-region').val('');
						$('.rm-bulk-action-options').hide();
					} else {
						self.showError(response.data.message);
					}
				},
				error: function() {
					self.showError('<?php esc_html_e( 'Failed to process bulk action.', 'region-manager' ); ?>');
				},
				complete: function() {
					$('.rm-bulk-assign-save').prop('disabled', false).text('<?php esc_html_e( 'Apply', 'region-manager' ); ?>');
				}
			});
		},

		showSuccess: function(message) {
			var notice = $('<div class="notice notice-success is-dismissible"><p>' + message + '</p></div>');
			$('.rm-products-page h1').after(notice);
			setTimeout(function() { notice.fadeOut(); }, 3000);
		},

		showError: function(message) {
			var notice = $('<div class="notice notice-error is-dismissible"><p>' + message + '</p></div>');
			$('.rm-products-page h1').after(notice);
			setTimeout(function() { notice.fadeOut(); }, 5000);
		}
	};

	// Debounce function
	$.debounce = function(delay, fn) {
		var timer;
		return function() {
			var context = this, args = arguments;
			clearTimeout(timer);
			timer = setTimeout(function() {
				fn.apply(context, args);
			}, delay);
		};
	};

	// Initialize
	ProductsManager.init();
});
</script>
