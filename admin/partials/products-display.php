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
					<th class="column-actions"><?php esc_html_e( 'Actions', 'region-manager' ); ?></th>
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
						   '<a href="' + product.edit_url + '" class="button button-small" target="_blank">' +
						   '<?php esc_html_e( 'Edit Product', 'region-manager' ); ?>' +
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
});
</script>
