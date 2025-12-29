<?php
/**
 * Provide an admin area view for products management (VIEW/FILTER ONLY).
 *
 * This page displays WooCommerce products filtered by region.
 * For editing products, users are directed to WooCommerce product editor.
 *
 * @package    Region_Manager
 * @subpackage Region_Manager/admin/partials
 */

// Get all active regions for filters.
global $wpdb;
$regions = $wpdb->get_results( "SELECT * FROM {$wpdb->prefix}rm_regions WHERE status = 'active' ORDER BY name ASC", ARRAY_A );
?>

<div class="wrap rm-products-page">
	<h1 class="wp-heading-inline"><?php esc_html_e( 'Products by Region', 'region-manager' ); ?></h1>
	<a href="<?php echo esc_url( admin_url( 'post-new.php?post_type=product' ) ); ?>" class="page-title-action">
		<?php esc_html_e( 'Add New Product', 'region-manager' ); ?>
	</a>
	<hr class="wp-header-end">

	<p class="description">
		<?php esc_html_e( 'View products filtered by region. To edit product details or assign regions, click the "Edit Product" button which will take you to the WooCommerce product editor.', 'region-manager' ); ?>
	</p>

	<!-- Filter Tabs -->
	<div class="rm-filter-tabs">
		<ul class="subsubsub">
			<li>
				<a href="#" class="rm-filter-link current" data-filter="all">
					<?php esc_html_e( 'All Products', 'region-manager' ); ?>
					<span class="count" id="count-all">(0)</span>
				</a> |
			</li>
			<li>
				<a href="#" class="rm-filter-link" data-filter="all_regions">
					<?php esc_html_e( 'All Regions', 'region-manager' ); ?>
					<span class="count" id="count-all-regions">(0)</span>
				</a> |
			</li>
			<li>
				<a href="#" class="rm-filter-link" data-filter="without_region">
					<?php esc_html_e( 'Without Region', 'region-manager' ); ?>
					<span class="count" id="count-without-region">(0)</span>
				</a>
				<?php if ( ! empty( $regions ) ) : ?>
					|
				<?php endif; ?>
			</li>
			<?php foreach ( $regions as $index => $region ) : ?>
				<li>
					<a href="#" class="rm-filter-link" data-filter="<?php echo esc_attr( $region['id'] ); ?>">
						<?php echo esc_html( $region['name'] ); ?>
						<span class="count" id="count-region-<?php echo esc_attr( $region['id'] ); ?>">(0)</span>
					</a>
					<?php if ( $index < count( $regions ) - 1 ) : ?>
						|
					<?php endif; ?>
				</li>
			<?php endforeach; ?>
		</ul>
	</div>

	<!-- Search Box -->
	<div class="rm-products-search">
		<input type="search" id="rm-product-search" class="rm-product-search" placeholder="<?php esc_attr_e( 'Search products by name or SKU...', 'region-manager' ); ?>">
	</div>

	<!-- Products Table -->
	<div class="rm-products-table-wrapper">
		<table class="wp-list-table widefat fixed striped rm-products-table">
			<thead>
				<tr>
					<th class="column-image"><?php esc_html_e( 'Image', 'region-manager' ); ?></th>
					<th class="column-name"><?php esc_html_e( 'Product Name', 'region-manager' ); ?></th>
					<th class="column-sku"><?php esc_html_e( 'SKU', 'region-manager' ); ?></th>
					<th class="column-price"><?php esc_html_e( 'Base Price', 'region-manager' ); ?></th>
					<th class="column-regions"><?php esc_html_e( 'Available In', 'region-manager' ); ?></th>
					<th class="column-stock"><?php esc_html_e( 'Stock', 'region-manager' ); ?></th>
					<th class="column-actions" style="width: 200px;"><?php esc_html_e( 'Actions', 'region-manager' ); ?></th>
				</tr>
			</thead>
			<tbody id="rm-products-tbody">
				<tr class="rm-loading-row">
					<td colspan="7" class="rm-loading">
						<span class="spinner is-active"></span>
						<?php esc_html_e( 'Loading products...', 'region-manager' ); ?>
					</td>
				</tr>
			</tbody>
		</table>
	</div>

	<!-- Pagination -->
	<div class="rm-pagination">
		<div class="rm-pagination-info"></div>
		<div class="rm-pagination-links"></div>
	</div>

	<!-- Regional Pricing Modal -->
	<div id="rm-regional-pricing-modal" class="rm-modal" style="display: none;">
		<div class="rm-modal-backdrop"></div>
		<div class="rm-modal-dialog" style="max-width: 900px;">
			<div class="rm-modal-header">
				<h2><?php esc_html_e( 'Regional Pricing', 'region-manager' ); ?></h2>
				<button type="button" class="rm-modal-close">&times;</button>
			</div>
			<div class="rm-modal-body">
				<div id="rm-pricing-content">
					<p class="rm-loading-text">
						<span class="spinner is-active"></span>
						<?php esc_html_e( 'Loading regional pricing...', 'region-manager' ); ?>
					</p>
				</div>
			</div>
			<div class="rm-modal-footer">
				<button type="button" class="button rm-modal-close"><?php esc_html_e( 'Cancel', 'region-manager' ); ?></button>
				<button type="button" class="button button-primary" id="rm-save-regional-pricing"><?php esc_html_e( 'Save Pricing', 'region-manager' ); ?></button>
			</div>
		</div>
	</div>
</div>

<style>
	.rm-products-page .description {
		margin: 15px 0;
		color: #666;
	}

	.rm-filter-tabs {
		margin: 20px 0 15px;
	}

	.rm-filter-tabs .subsubsub {
		margin: 0;
		padding: 0;
	}

	.rm-filter-tabs .rm-filter-link.current {
		color: #000;
		font-weight: 600;
	}

	.rm-products-search {
		margin: 15px 0;
	}

	.rm-product-search {
		width: 300px;
		padding: 6px 10px;
	}

	.rm-products-table-wrapper {
		margin: 20px 0;
	}

	.rm-products-table .column-image {
		width: 60px;
	}

	.rm-products-table .column-name {
		width: auto;
	}

	.rm-products-table .column-sku {
		width: 120px;
	}

	.rm-products-table .column-price {
		width: 100px;
	}

	.rm-products-table .column-regions {
		width: 200px;
	}

	.rm-products-table .column-stock {
		width: 100px;
	}

	.rm-products-table .column-actions {
		width: 150px;
	}

	.rm-products-table .rm-loading {
		text-align: center;
		padding: 40px 20px;
	}

	.rm-products-table .rm-no-products {
		text-align: center;
		padding: 40px 20px;
		color: #666;
	}

	.rm-region-badge {
		display: inline-block;
		padding: 3px 8px;
		margin: 2px;
		background: #2271b1;
		color: #fff;
		border-radius: 3px;
		font-size: 12px;
		white-space: nowrap;
	}

	.rm-region-badge.all-regions {
		background: #00a32a;
	}

	.rm-no-regions {
		display: inline-block;
		padding: 3px 8px;
		background: #dba617;
		color: #fff;
		border-radius: 3px;
		font-size: 12px;
	}

	.rm-price-override {
		display: block;
		font-size: 11px;
		color: #666;
		margin-top: 2px;
	}

	.rm-stock-status {
		display: inline-block;
		padding: 3px 8px;
		border-radius: 3px;
		font-size: 12px;
	}

	.rm-stock-status.in-stock {
		background: #d5e8d4;
		color: #1a5228;
	}

	.rm-stock-status.out-of-stock {
		background: #f8d7da;
		color: #721c24;
	}

	.rm-pagination {
		display: flex;
		justify-content: space-between;
		align-items: center;
		margin: 20px 0;
		padding: 10px 0;
	}

	.rm-pagination-info {
		color: #666;
	}

	.rm-pagination-links a {
		display: inline-block;
		padding: 5px 10px;
		margin: 0 2px;
		border: 1px solid #ddd;
		border-radius: 3px;
		text-decoration: none;
		color: #2271b1;
	}

	.rm-pagination-links a:hover {
		background: #f6f7f7;
	}

	.rm-pagination-links a.current {
		background: #2271b1;
		color: #fff;
		border-color: #2271b1;
	}

	.rm-pagination-links a.disabled {
		color: #ddd;
		pointer-events: none;
	}

	.rm-pagination-links .rm-page-dots {
		padding: 5px;
		color: #666;
	}

	.rm-warning-notice {
		background: #fff3cd;
		border-left: 4px solid #ffc107;
		padding: 12px;
		margin: 10px 0;
	}

	.rm-warning-notice p {
		margin: 0;
		color: #856404;
	}

	/* Modal Styles */
	.rm-modal {
		position: fixed;
		top: 0;
		left: 0;
		right: 0;
		bottom: 0;
		z-index: 100000;
	}

	.rm-modal-backdrop {
		position: absolute;
		top: 0;
		left: 0;
		right: 0;
		bottom: 0;
		background: rgba(0, 0, 0, 0.5);
	}

	.rm-modal-dialog {
		position: relative;
		max-width: 900px;
		margin: 50px auto;
		background: #fff;
		border-radius: 4px;
		box-shadow: 0 5px 15px rgba(0, 0, 0, 0.3);
		max-height: calc(100vh - 100px);
		display: flex;
		flex-direction: column;
	}

	.rm-modal-header {
		padding: 15px 20px;
		border-bottom: 1px solid #ddd;
		display: flex;
		justify-content: space-between;
		align-items: center;
	}

	.rm-modal-header h2 {
		margin: 0;
		font-size: 18px;
	}

	.rm-modal-close {
		background: none;
		border: none;
		font-size: 28px;
		line-height: 1;
		color: #666;
		cursor: pointer;
		padding: 0;
		width: 30px;
		height: 30px;
	}

	.rm-modal-close:hover {
		color: #000;
	}

	.rm-modal-body {
		padding: 20px;
		overflow-y: auto;
		flex: 1;
	}

	.rm-modal-footer {
		padding: 15px 20px;
		border-top: 1px solid #ddd;
		text-align: right;
		background: #f9f9f9;
	}

	.rm-modal-footer .button {
		margin-left: 10px;
	}

	.rm-loading-text {
		text-align: center;
		padding: 40px;
		color: #666;
	}

	.rm-pricing-section {
		margin-bottom: 20px;
	}

	.rm-pricing-tabs {
		border-bottom: 1px solid #ddd;
		margin-bottom: 20px;
	}

	.rm-pricing-tabs .nav-tab {
		margin: 0;
		padding: 10px 15px;
		cursor: pointer;
	}

	.rm-pricing-tab-content {
		display: none;
	}

	.rm-pricing-tab-content.active {
		display: block;
	}

	.rm-country-pricing-table {
		width: 100%;
		margin-top: 15px;
	}

	.rm-country-pricing-table th {
		background: #f9f9f9;
		padding: 8px;
		text-align: left;
	}

	.rm-country-pricing-table td {
		padding: 8px;
		border-bottom: 1px solid #ddd;
	}

	.rm-price-input {
		width: 100px;
	}

	.rm-currency-badge {
		display: inline-block;
		padding: 2px 6px;
		background: #f0f0f0;
		border-radius: 3px;
		font-size: 11px;
		font-weight: bold;
	}

	.rm-currency-warning {
		color: #d63638;
		font-weight: bold;
		font-size: 11px;
	}
</style>

<script type="text/javascript">
jQuery(document).ready(function($) {
	'use strict';

	var ProductsViewer = {
		currentPage: 1,
		perPage: 20,
		currentFilter: 'all',
		searchTerm: '',

		init: function() {
			this.bindEvents();
			this.loadProducts();
			this.loadCounts();
		},

		bindEvents: function() {
			var self = this;

			// Filter tabs
			$('.rm-filter-link').on('click', function(e) {
				e.preventDefault();
				$('.rm-filter-link').removeClass('current');
				$(this).addClass('current');
				self.currentFilter = $(this).data('filter');
				self.currentPage = 1;
				self.loadProducts();
			});

			// Search
			$('#rm-product-search').on('input', $.debounce(500, function() {
				self.searchTerm = $(this).val();
				self.currentPage = 1;
				self.loadProducts();
			}));

			// Pagination
			$(document).on('click', '.rm-page-link', function(e) {
				e.preventDefault();
				var page = $(this).data('page');
				if (page && page !== self.currentPage) {
					self.currentPage = page;
					self.loadProducts();
					$('html, body').animate({ scrollTop: $('.rm-products-table').offset().top - 50 }, 300);
				}
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
					search: self.searchTerm,
					filter: self.currentFilter
				},
				beforeSend: function() {
					$('#rm-products-tbody').html(
						'<tr class="rm-loading-row">' +
						'<td colspan="7" class="rm-loading">' +
						'<span class="spinner is-active"></span> <?php esc_html_e( 'Loading products...', 'region-manager' ); ?>' +
						'</td>' +
						'</tr>'
					);
				},
				success: function(response) {
					if (response.success) {
						self.renderProducts(response.data.products);
						self.renderPagination(response.data.total, response.data.total_pages);
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
				html = '<tr class="rm-no-products">' +
					   '<td colspan="7"><?php esc_html_e( 'No products found.', 'region-manager' ); ?></td>' +
					   '</tr>';
			} else {
				$.each(products, function(i, product) {
					var regionsHtml = '';

					if (product.regions && product.regions.length > 0) {
						$.each(product.regions, function(j, region) {
							var badgeClass = region.region_id === 0 ? 'rm-region-badge all-regions' : 'rm-region-badge';
							var priceInfo = '';

							if (region.price_override && region.region_id !== 0) {
								priceInfo = '<span class="rm-price-override"><?php esc_html_e( 'Override:', 'region-manager' ); ?> ' + region.price_override + '</span>';
							}

							regionsHtml += '<span class="' + badgeClass + '" title="' + region.name + '">' +
										   region.name +
										   '</span>' + priceInfo + ' ';
						});
					} else {
						regionsHtml = '<span class="rm-no-regions"><?php esc_html_e( '⚠ No regions', 'region-manager' ); ?></span>';
					}

					var stockClass = product.stock_status === 'instock' ? 'in-stock' : 'out-of-stock';
					var stockText = product.stock_status === 'instock' ?
						'<?php esc_html_e( 'In stock', 'region-manager' ); ?>' :
						'<?php esc_html_e( 'Out of stock', 'region-manager' ); ?>';

					html += '<tr data-product-id="' + product.id + '">';
					html += '<td class="column-image">' + product.image + '</td>';
					html += '<td class="column-name">' +
						   '<strong><a href="' + product.edit_url + '" target="_blank">' + product.name + '</a></strong>' +
						   '</td>';
					html += '<td class="column-sku">' + (product.sku || '—') + '</td>';
					html += '<td class="column-price">' + (product.price || '—') + '</td>';
					html += '<td class="column-regions">' + regionsHtml + '</td>';
					html += '<td class="column-stock">' +
						   '<span class="rm-stock-status ' + stockClass + '">' + stockText + '</span>' +
						   '</td>';
					html += '<td class="column-actions">' +
						   '<button type="button" class="button button-small rm-manage-pricing" data-product-id="' + product.id + '" style="margin-right: 5px;">' +
						   '<?php esc_html_e( 'Manage Pricing', 'region-manager' ); ?>' +
						   '</button>' +
						   '<a href="' + product.edit_url + '" class="button button-small" target="_blank">' +
						   '<?php esc_html_e( 'Edit', 'region-manager' ); ?>' +
						   '</a>' +
						   '</td>';
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
				linksHtml += '<a href="#" class="rm-page-link ' + (self.currentPage === 1 ? 'disabled' : '') +
							 '" data-page="' + (self.currentPage - 1) + '">‹</a>';

				// Pages
				for (var i = 1; i <= totalPages; i++) {
					if (i === 1 || i === totalPages || (i >= self.currentPage - 2 && i <= self.currentPage + 2)) {
						linksHtml += '<a href="#" class="rm-page-link ' + (i === self.currentPage ? 'current' : '') +
									 '" data-page="' + i + '">' + i + '</a>';
					} else if (i === self.currentPage - 3 || i === self.currentPage + 3) {
						linksHtml += '<span class="rm-page-dots">...</span>';
					}
				}

				// Next
				linksHtml += '<a href="#" class="rm-page-link ' + (self.currentPage === totalPages ? 'disabled' : '') +
							 '" data-page="' + (self.currentPage + 1) + '">›</a>';
			}

			$('.rm-pagination-links').html(linksHtml);
		},

		loadCounts: function() {
			var self = this;
			var filters = ['all', 'all_regions', 'without_region'];

			// Add region IDs
			<?php foreach ( $regions as $region ) : ?>
			filters.push('<?php echo esc_js( $region['id'] ); ?>');
			<?php endforeach; ?>

			// Load count for each filter
			$.each(filters, function(i, filter) {
				$.ajax({
					url: ajaxurl,
					type: 'POST',
					data: {
						action: 'rm_get_products_table',
						nonce: rmAdmin.nonce,
						page: 1,
						per_page: 1,
						filter: filter
					},
					success: function(response) {
						if (response.success) {
							var countId = filter === 'all' ? '#count-all' :
										 filter === 'all_regions' ? '#count-all-regions' :
										 filter === 'without_region' ? '#count-without-region' :
										 '#count-region-' + filter;
							$(countId).text('(' + response.data.total + ')');
						}
					}
				});
			});
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
	ProductsViewer.init();

	// Regional Pricing Modal Handler
	var RegionalPricingModal = {
		currentProductId: null,
		regionsData: null,
		countriesData: null,

		init: function() {
			var self = this;

			// Open modal
			$(document).on('click', '.rm-manage-pricing', function() {
				self.currentProductId = $(this).data('product-id');
				self.openModal();
			});

			// Close modal
			$(document).on('click', '.rm-modal-close, .rm-modal-backdrop', function() {
				self.closeModal();
			});

			// Save pricing
			$('#rm-save-regional-pricing').on('click', function() {
				self.savePricing();
			});

			// Tab switching
			$(document).on('click', '.rm-pricing-tab', function() {
				$('.rm-pricing-tab').removeClass('nav-tab-active');
				$(this).addClass('nav-tab-active');
				var regionId = $(this).data('region-id');
				$('.rm-pricing-tab-content').removeClass('active');
				$('#rm-region-tab-' + regionId).addClass('active');
			});
		},

		openModal: function() {
			var self = this;
			$('#rm-regional-pricing-modal').fadeIn(200);
			$('body').addClass('modal-open');
			self.loadPricingData();
		},

		closeModal: function() {
			$('#rm-regional-pricing-modal').fadeOut(200);
			$('body').removeClass('modal-open');
		},

		loadPricingData: function() {
			var self = this;

			$.ajax({
				url: ajaxurl,
				type: 'POST',
				data: {
					action: 'rm_get_regional_pricing_data',
					nonce: rmAdmin.nonce,
					product_id: self.currentProductId
				},
				success: function(response) {
					if (response.success) {
						self.regionsData = response.data.regions;
						self.countriesData = response.data.countries;
						self.renderPricingInterface(response.data);
					} else {
						alert(response.data.message || 'Failed to load pricing data');
					}
				},
				error: function() {
					alert('Failed to load pricing data');
				}
			});
		},

		renderPricingInterface: function(data) {
			var self = this;
			var html = '';

			// Product info
			html += '<div class="rm-pricing-section" style="background: #f0f6fc; padding: 12px; border-left: 4px solid #2271b1; margin-bottom: 20px;">';
			html += '<h3 style="margin: 0 0 10px 0; color: #2271b1;">' + data.product.name + '</h3>';
			html += '<p style="margin: 5px 0;"><strong><?php esc_html_e( 'Base Price:', 'region-manager' ); ?></strong> ' +
					(data.product.base_price || '—') + ' ' + data.base_currency + '</p>';
			html += '<p style="margin: 5px 0;"><strong><?php esc_html_e( 'Sale Price:', 'region-manager' ); ?></strong> ' +
					(data.product.sale_price || '—') + ' ' + data.base_currency + '</p>';
			html += '</div>';

			// Tabs
			html += '<div class="rm-pricing-tabs nav-tab-wrapper">';
			$.each(data.regions, function(i, region) {
				var activeClass = i === 0 ? ' nav-tab-active' : '';
				html += '<a href="#" class="nav-tab rm-pricing-tab' + activeClass + '" data-region-id="' + region.id + '">' +
						region.name + '</a>';
			});
			html += '</div>';

			// Tab contents
			$.each(data.regions, function(i, region) {
				var activeClass = i === 0 ? ' active' : '';
				html += '<div id="rm-region-tab-' + region.id + '" class="rm-pricing-tab-content' + activeClass + '">';

				// Region level pricing
				html += '<div class="rm-pricing-section" style="background: #fafafa; padding: 15px; border: 1px solid #e0e0e0;">';
				html += '<h4>' + region.name + ' - <?php esc_html_e( 'Region Settings', 'region-manager' ); ?></h4>';
				html += '<p><label>';
				html += '<input type="checkbox" class="rm-region-available" data-region-id="' + region.id + '" ' +
						(region.is_available ? 'checked' : '') + '> ';
				html += '<?php esc_html_e( 'Product available in this region', 'region-manager' ); ?>';
				html += '</label></p>';
				html += '<p><label><?php esc_html_e( 'Region Price:', 'region-manager' ); ?> (' + data.base_currency + ')<br>';
				html += '<input type="text" class="rm-price-input rm-region-price" data-region-id="' + region.id + '" ' +
						'value="' + (region.price_override || '') + '" placeholder="' + (data.product.base_price || '') + '"></label></p>';
				html += '<p><label><?php esc_html_e( 'Region Sale Price:', 'region-manager' ); ?> (' + data.base_currency + ')<br>';
				html += '<input type="text" class="rm-price-input rm-region-sale-price" data-region-id="' + region.id + '" ' +
						'value="' + (region.sale_price_override || '') + '" placeholder="' + (data.product.sale_price || '') + '"></label></p>';
				html += '</div>';

				// Country-specific pricing
				if (region.countries && region.countries.length > 0) {
					html += '<h4 style="margin-top: 20px;"><?php esc_html_e( 'Country-Specific Pricing', 'region-manager' ); ?></h4>';
					html += '<table class="widefat rm-country-pricing-table">';
					html += '<thead><tr>';
					html += '<th><?php esc_html_e( 'Country', 'region-manager' ); ?></th>';
					html += '<th><?php esc_html_e( 'Currency', 'region-manager' ); ?></th>';
					html += '<th><?php esc_html_e( 'Price', 'region-manager' ); ?></th>';
					html += '<th><?php esc_html_e( 'Sale Price', 'region-manager' ); ?></th>';
					html += '</tr></thead><tbody>';

					$.each(region.countries, function(j, country) {
						var countryPrice = self.getCountryPrice(country.country_code);
						var isDiffCurrency = country.currency_code !== data.base_currency;

						html += '<tr>';
						html += '<td><strong>' + country.name + '</strong><br><small>' + country.country_code + '</small></td>';
						html += '<td><span class="rm-currency-badge">' + country.currency_code + ' (' + country.currency_symbol + ')</span>';
						if (isDiffCurrency) {
							html += '<br><span class="rm-currency-warning">⚠ <?php esc_html_e( 'Different currency', 'region-manager' ); ?></span>';
						}
						html += '</td>';
						html += '<td><input type="text" class="rm-price-input rm-country-price" data-country="' + country.country_code + '" ' +
								'data-currency="' + country.currency_code + '" value="' + (countryPrice.price || '') + '" ' +
								'placeholder="' + (region.price_override || data.product.base_price || '') + '"></td>';
						html += '<td><input type="text" class="rm-price-input rm-country-sale-price" data-country="' + country.country_code + '" ' +
								'value="' + (countryPrice.sale_price || '') + '" ' +
								'placeholder="' + (region.sale_price_override || data.product.sale_price || '') + '"></td>';
						html += '</tr>';
					});

					html += '</tbody></table>';
				}

				html += '</div>';
			});

			$('#rm-pricing-content').html(html);
		},

		getCountryPrice: function(countryCode) {
			var self = this;
			if (self.countriesData && self.countriesData[countryCode]) {
				return self.countriesData[countryCode];
			}
			return { price: '', sale_price: '' };
		},

		savePricing: function() {
			var self = this;
			var regionsData = [];
			var countriesData = [];

			// Collect region-level data
			$('.rm-region-available').each(function() {
				var regionId = $(this).data('region-id');
				var isAvailable = $(this).is(':checked');
				var price = $('.rm-region-price[data-region-id="' + regionId + '"]').val();
				var salePrice = $('.rm-region-sale-price[data-region-id="' + regionId + '"]').val();

				if (isAvailable) {
					regionsData.push({
						region_id: regionId,
						price: price,
						sale_price: salePrice
					});
				}
			});

			// Collect country-level data
			$('.rm-country-price').each(function() {
				var countryCode = $(this).data('country');
				var currency = $(this).data('currency');
				var price = $(this).val();
				var salePrice = $('.rm-country-sale-price[data-country="' + countryCode + '"]').val();

				if (price || salePrice) {
					countriesData.push({
						country_code: countryCode,
						currency: currency,
						price: price,
						sale_price: salePrice
					});
				}
			});

			// Save via AJAX
			$.ajax({
				url: ajaxurl,
				type: 'POST',
				data: {
					action: 'rm_save_regional_pricing_data',
					nonce: rmAdmin.nonce,
					product_id: self.currentProductId,
					regions: JSON.stringify(regionsData),
					countries: JSON.stringify(countriesData)
				},
				beforeSend: function() {
					$('#rm-save-regional-pricing').prop('disabled', true).text('<?php esc_html_e( 'Saving...', 'region-manager' ); ?>');
				},
				success: function(response) {
					if (response.success) {
						alert(response.data.message || '<?php esc_html_e( 'Pricing saved successfully!', 'region-manager' ); ?>');
						self.closeModal();
						ProductsViewer.loadProducts();
					} else {
						alert(response.data.message || '<?php esc_html_e( 'Failed to save pricing.', 'region-manager' ); ?>');
					}
				},
				error: function() {
					alert('<?php esc_html_e( 'Failed to save pricing.', 'region-manager' ); ?>');
				},
				complete: function() {
					$('#rm-save-regional-pricing').prop('disabled', false).text('<?php esc_html_e( 'Save Pricing', 'region-manager' ); ?>');
				}
			});
		}
	};

	RegionalPricingModal.init();
});
</script>
