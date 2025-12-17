<?php
/**
 * Provide an admin area view for orders management.
 *
 * @package    Region_Manager
 * @subpackage Region_Manager/admin/partials
 */

// Get all active regions for filters.
global $wpdb;
$regions = $wpdb->get_results( "SELECT * FROM {$wpdb->prefix}rm_regions WHERE status = 'active' ORDER BY name ASC", ARRAY_A );

// Get all countries for flag display.
$countries = WC()->countries->get_countries();
?>

<div class="wrap rm-orders-page">
	<h1 class="wp-heading-inline"><?php esc_html_e( 'Orders', 'region-manager' ); ?></h1>
	<hr class="wp-header-end">

	<!-- Top Bar -->
	<div class="rm-orders-topbar">
		<div class="rm-topbar-filters">
			<!-- Region Filter -->
			<select id="rm-order-region-filter" class="rm-filter-select">
				<option value=""><?php esc_html_e( 'All Regions', 'region-manager' ); ?></option>
				<?php foreach ( $regions as $region ) : ?>
					<option value="<?php echo esc_attr( $region['id'] ); ?>">
						<?php echo esc_html( $region['name'] ); ?>
					</option>
				<?php endforeach; ?>
			</select>

			<!-- Status Filter -->
			<select id="rm-order-status-filter" class="rm-filter-select">
				<option value="any"><?php esc_html_e( 'All Statuses', 'region-manager' ); ?></option>
				<option value="processing"><?php esc_html_e( 'Processing', 'region-manager' ); ?></option>
				<option value="in-transit"><?php esc_html_e( 'In Transit', 'region-manager' ); ?></option>
				<option value="completed"><?php esc_html_e( 'Completed', 'region-manager' ); ?></option>
				<option value="on-hold"><?php esc_html_e( 'On Hold', 'region-manager' ); ?></option>
				<option value="pending"><?php esc_html_e( 'Pending', 'region-manager' ); ?></option>
				<option value="cancelled"><?php esc_html_e( 'Cancelled', 'region-manager' ); ?></option>
			</select>

			<!-- Date Range -->
			<select id="rm-order-date-range" class="rm-filter-select">
				<option value=""><?php esc_html_e( 'All Time', 'region-manager' ); ?></option>
				<option value="today"><?php esc_html_e( 'Today', 'region-manager' ); ?></option>
				<option value="yesterday"><?php esc_html_e( 'Yesterday', 'region-manager' ); ?></option>
				<option value="this_week"><?php esc_html_e( 'This Week', 'region-manager' ); ?></option>
				<option value="this_month"><?php esc_html_e( 'This Month', 'region-manager' ); ?></option>
				<option value="custom"><?php esc_html_e( 'Custom Range', 'region-manager' ); ?></option>
			</select>

			<!-- Custom Date Inputs (hidden by default) -->
			<div id="rm-custom-date-range" style="display: none;">
				<input type="date" id="rm-date-after" placeholder="<?php esc_attr_e( 'From', 'region-manager' ); ?>">
				<input type="date" id="rm-date-before" placeholder="<?php esc_attr_e( 'To', 'region-manager' ); ?>">
			</div>

			<!-- Search Box -->
			<input type="search" id="rm-order-search" class="rm-order-search" placeholder="<?php esc_attr_e( 'Search by order # or customer...', 'region-manager' ); ?>">
		</div>

		<div class="rm-topbar-actions">
			<!-- Bulk Actions -->
			<select id="rm-order-bulk-action" class="rm-bulk-action">
				<option value=""><?php esc_html_e( 'Bulk Actions', 'region-manager' ); ?></option>
				<option value="in-transit"><?php esc_html_e( 'Mark as In Transit', 'region-manager' ); ?></option>
				<option value="completed"><?php esc_html_e( 'Mark as Completed', 'region-manager' ); ?></option>
				<option value="export"><?php esc_html_e( 'Export to CSV', 'region-manager' ); ?></option>
			</select>

			<button type="button" id="rm-apply-order-bulk" class="button"><?php esc_html_e( 'Apply', 'region-manager' ); ?></button>

			<span class="rm-orders-count"></span>
		</div>
	</div>

	<!-- Orders Table -->
	<div class="rm-orders-table-wrapper">
		<table class="wp-list-table widefat fixed striped rm-orders-table">
			<thead>
				<tr>
					<td class="check-column">
						<input type="checkbox" id="rm-select-all-orders">
					</td>
					<th class="column-order-number" data-sort="id"><?php esc_html_e( 'Order #', 'region-manager' ); ?></th>
					<th class="column-date" data-sort="date"><?php esc_html_e( 'Date', 'region-manager' ); ?></th>
					<th class="column-customer"><?php esc_html_e( 'Customer', 'region-manager' ); ?></th>
					<th class="column-region"><?php esc_html_e( 'Region', 'region-manager' ); ?></th>
					<th class="column-shipping"><?php esc_html_e( 'Shipping Country', 'region-manager' ); ?></th>
					<th class="column-items"><?php esc_html_e( 'Items', 'region-manager' ); ?></th>
					<th class="column-total" data-sort="total"><?php esc_html_e( 'Total', 'region-manager' ); ?></th>
					<th class="column-status"><?php esc_html_e( 'Status', 'region-manager' ); ?></th>
					<th class="column-actions"><?php esc_html_e( 'Actions', 'region-manager' ); ?></th>
				</tr>
			</thead>
			<tbody id="rm-orders-tbody">
				<tr class="rm-loading-row">
					<td colspan="10" class="rm-loading">
						<span class="spinner is-active"></span>
						<?php esc_html_e( 'Loading orders...', 'region-manager' ); ?>
					</td>
				</tr>
			</tbody>
			<tfoot>
				<tr>
					<td class="check-column">
						<input type="checkbox">
					</td>
					<th><?php esc_html_e( 'Order #', 'region-manager' ); ?></th>
					<th><?php esc_html_e( 'Date', 'region-manager' ); ?></th>
					<th><?php esc_html_e( 'Customer', 'region-manager' ); ?></th>
					<th><?php esc_html_e( 'Region', 'region-manager' ); ?></th>
					<th><?php esc_html_e( 'Shipping Country', 'region-manager' ); ?></th>
					<th><?php esc_html_e( 'Items', 'region-manager' ); ?></th>
					<th><?php esc_html_e( 'Total', 'region-manager' ); ?></th>
					<th><?php esc_html_e( 'Status', 'region-manager' ); ?></th>
					<th><?php esc_html_e( 'Actions', 'region-manager' ); ?></th>
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

<!-- Order Details Modal -->
<div id="rm-order-details-modal" class="rm-modal" style="display: none;">
	<div class="rm-modal-overlay"></div>
	<div class="rm-modal-content rm-order-modal-content">
		<div class="rm-modal-header">
			<h2><?php esc_html_e( 'Order Details', 'region-manager' ); ?></h2>
			<button type="button" class="rm-modal-close">
				<span class="dashicons dashicons-no"></span>
			</button>
		</div>
		<div class="rm-modal-body">
			<div class="rm-order-details-content"></div>
		</div>
		<div class="rm-modal-footer">
			<button type="button" class="button rm-modal-cancel"><?php esc_html_e( 'Close', 'region-manager' ); ?></button>
		</div>
	</div>
</div>

<script type="text/javascript">
jQuery(document).ready(function($) {
	'use strict';

	var OrdersManager = {
		currentPage: 1,
		perPage: 20,
		selectedOrders: [],
		currentFilters: {
			region_id: null,
			status: 'any',
			search: '',
			date_after: null,
			date_before: null,
			orderby: 'date',
			order: 'DESC'
		},
		countries: <?php echo wp_json_encode( $countries ); ?>,

		init: function() {
			this.bindEvents();
			this.loadOrders();
		},

		bindEvents: function() {
			var self = this;

			// Filters
			$('#rm-order-region-filter').on('change', function() {
				var value = $(this).val();
				self.currentFilters.region_id = value ? parseInt(value) : null;
				self.currentPage = 1;
				self.loadOrders();
			});

			$('#rm-order-status-filter').on('change', function() {
				self.currentFilters.status = $(this).val();
				self.currentPage = 1;
				self.loadOrders();
			});

			$('#rm-order-date-range').on('change', function() {
				var range = $(this).val();
				if (range === 'custom') {
					$('#rm-custom-date-range').slideDown();
					return;
				} else {
					$('#rm-custom-date-range').slideUp();
				}

				var dates = self.getDateRange(range);
				self.currentFilters.date_after = dates.after;
				self.currentFilters.date_before = dates.before;
				self.currentPage = 1;
				self.loadOrders();
			});

			$('#rm-date-after, #rm-date-before').on('change', function() {
				self.currentFilters.date_after = $('#rm-date-after').val();
				self.currentFilters.date_before = $('#rm-date-before').val();
				self.currentPage = 1;
				self.loadOrders();
			});

			$('#rm-order-search').on('input', $.debounce(500, function() {
				self.currentFilters.search = $(this).val();
				self.currentPage = 1;
				self.loadOrders();
			}));

			// Select all checkbox
			$('#rm-select-all-orders').on('change', function() {
				$('.rm-order-checkbox').prop('checked', $(this).is(':checked'));
				self.updateSelectedOrders();
			});

			// Individual checkboxes
			$(document).on('change', '.rm-order-checkbox', function() {
				self.updateSelectedOrders();
			});

			// Quick status update
			$(document).on('click', '.rm-quick-status-btn', function(e) {
				e.preventDefault();
				var orderId = $(this).data('order-id');
				var status = $(this).data('status');
				self.updateOrderStatus(orderId, status, $(this));
			});

			// View order details
			$(document).on('click', '.rm-view-order-details', function(e) {
				e.preventDefault();
				var orderId = $(this).data('order-id');
				self.viewOrderDetails(orderId);
			});

			// Bulk actions
			$('#rm-apply-order-bulk').on('click', function() {
				self.handleBulkAction();
			});

			// Modal close
			$('.rm-modal-close, .rm-modal-cancel').on('click', function() {
				$(this).closest('.rm-modal').hide();
			});

			$('.rm-modal-overlay').on('click', function() {
				$(this).closest('.rm-modal').hide();
			});

			// Pagination
			$(document).on('click', '.rm-page-link', function(e) {
				e.preventDefault();
				var page = $(this).data('page');
				if (page && page !== self.currentPage) {
					self.currentPage = page;
					self.loadOrders();
				}
			});

			// Column sorting
			$('.rm-orders-table th[data-sort]').on('click', function() {
				var sortBy = $(this).data('sort');
				if (self.currentFilters.orderby === sortBy) {
					self.currentFilters.order = self.currentFilters.order === 'ASC' ? 'DESC' : 'ASC';
				} else {
					self.currentFilters.orderby = sortBy;
					self.currentFilters.order = 'DESC';
				}
				self.loadOrders();
			});
		},

		getDateRange: function(range) {
			var today = new Date();
			var after = null;
			var before = null;

			switch (range) {
				case 'today':
					after = this.formatDate(today);
					before = this.formatDate(today);
					break;
				case 'yesterday':
					var yesterday = new Date(today);
					yesterday.setDate(yesterday.getDate() - 1);
					after = this.formatDate(yesterday);
					before = this.formatDate(yesterday);
					break;
				case 'this_week':
					var firstDay = new Date(today);
					firstDay.setDate(today.getDate() - today.getDay());
					after = this.formatDate(firstDay);
					before = this.formatDate(today);
					break;
				case 'this_month':
					var firstDay = new Date(today.getFullYear(), today.getMonth(), 1);
					after = this.formatDate(firstDay);
					before = this.formatDate(today);
					break;
			}

			return { after: after, before: before };
		},

		formatDate: function(date) {
			var year = date.getFullYear();
			var month = String(date.getMonth() + 1).padStart(2, '0');
			var day = String(date.getDate()).padStart(2, '0');
			return year + '-' + month + '-' + day;
		},

		loadOrders: function() {
			var self = this;

			$.ajax({
				url: ajaxurl,
				type: 'POST',
				data: {
					action: 'rm_get_orders_table',
					nonce: rmAdmin.nonce,
					page: self.currentPage,
					per_page: self.perPage,
					region_id: self.currentFilters.region_id,
					status: self.currentFilters.status,
					search: self.currentFilters.search,
					date_after: self.currentFilters.date_after,
					date_before: self.currentFilters.date_before,
					orderby: self.currentFilters.orderby,
					order: self.currentFilters.order
				},
				beforeSend: function() {
					$('#rm-orders-tbody').html('<tr class="rm-loading-row"><td colspan="10" class="rm-loading"><span class="spinner is-active"></span> <?php esc_html_e( 'Loading orders...', 'region-manager' ); ?></td></tr>');
				},
				success: function(response) {
					if (response.success) {
						self.renderOrders(response.data.orders);
						self.renderPagination(response.data.total, response.data.total_pages);
						$('.rm-orders-count').text(response.data.total + ' <?php esc_html_e( 'orders', 'region-manager' ); ?>');
					} else {
						self.showError(response.data.message);
					}
				},
				error: function() {
					self.showError('<?php esc_html_e( 'Failed to load orders.', 'region-manager' ); ?>');
				}
			});
		},

		renderOrders: function(orders) {
			var html = '';

			if (orders.length === 0) {
				html = '<tr class="rm-no-orders"><td colspan="10"><?php esc_html_e( 'No orders found.', 'region-manager' ); ?></td></tr>';
			} else {
				$.each(orders, function(i, order) {
					var statusClass = 'rm-status-' + order.status;
					var statusIcon = self.getStatusIcon(order.status);
					var crossRegionHtml = '';

					if (order.cross_region) {
						crossRegionHtml = '<span class="rm-cross-region-indicator" title="<?php esc_attr_e( 'Cross-region order', 'region-manager' ); ?>">⚠️</span>';
					}

					var regionBadge = order.region_name ? '<span class="rm-region-badge">' + order.region_name + '</span>' : '<span class="rm-no-region"><?php esc_html_e( 'No region', 'region-manager' ); ?></span>';

					var customerFlag = self.getCountryFlag(order.customer_country);
					var shippingFlag = self.getCountryFlag(order.shipping_country);

					// Quick action button
					var quickActionHtml = '';
					if (order.status === 'processing') {
						quickActionHtml = '<button class="button button-small rm-quick-status-btn rm-btn-in-transit" data-order-id="' + order.id + '" data-status="in-transit">🚚 <?php esc_html_e( 'Mark In Transit', 'region-manager' ); ?></button>';
					} else if (order.status === 'in-transit') {
						quickActionHtml = '<button class="button button-small rm-quick-status-btn rm-btn-completed" data-order-id="' + order.id + '" data-status="completed">✅ <?php esc_html_e( 'Mark Completed', 'region-manager' ); ?></button>';
					}

					// Status flow visual
					var statusFlowHtml = self.renderStatusFlow(order.status);

					html += '<tr data-order-id="' + order.id + '">';
					html += '<th class="check-column"><input type="checkbox" class="rm-order-checkbox" value="' + order.id + '"></th>';
					html += '<td class="column-order-number"><a href="' + order.edit_url + '" target="_blank">#' + order.order_number + '</a></td>';
					html += '<td class="column-date">' + self.formatDateTime(order.date) + '</td>';
					html += '<td class="column-customer">' + customerFlag + ' ' + order.customer_name + '</td>';
					html += '<td class="column-region">' + regionBadge + '</td>';
					html += '<td class="column-shipping">' + shippingFlag + ' ' + order.shipping_country + ' ' + crossRegionHtml + '</td>';
					html += '<td class="column-items">' + order.items_count + '</td>';
					html += '<td class="column-total">' + order.total + ' ' + order.currency + '</td>';
					html += '<td class="column-status"><mark class="' + statusClass + ' ' + order.status + '">' + statusIcon + ' ' + self.getStatusLabel(order.status) + '</mark><div class="rm-status-flow">' + statusFlowHtml + '</div></td>';
					html += '<td class="column-actions">' + quickActionHtml + '<br><a href="#" class="rm-view-order-details" data-order-id="' + order.id + '"><?php esc_html_e( 'Details', 'region-manager' ); ?></a></td>';
					html += '</tr>';
				});
			}

			$('#rm-orders-tbody').html(html);
		},

		renderStatusFlow: function(currentStatus) {
			var steps = ['processing', 'in-transit', 'completed'];
			var html = '<div class="rm-progress-flow">';

			$.each(steps, function(i, step) {
				var className = 'rm-flow-step';
				var currentIndex = steps.indexOf(currentStatus);
				var stepIndex = i;

				if (stepIndex < currentIndex) {
					className += ' rm-step-completed';
				} else if (stepIndex === currentIndex) {
					className += ' rm-step-current';
				}

				html += '<span class="' + className + '" title="' + OrdersManager.getStatusLabel(step) + '"></span>';
				if (i < steps.length - 1) {
					html += '<span class="rm-flow-arrow">→</span>';
				}
			});

			html += '</div>';
			return html;
		},

		getCountryFlag: function(countryCode) {
			if (!countryCode) return '';
			var countryName = this.countries[countryCode] || countryCode;
			return '<span class="rm-country-flag" title="' + countryName + '">' + this.getFlagEmoji(countryCode) + '</span>';
		},

		getFlagEmoji: function(countryCode) {
			if (!countryCode || countryCode.length !== 2) return '🏳️';
			const codePoints = countryCode.toUpperCase().split('').map(char => 127397 + char.charCodeAt());
			return String.fromCodePoint(...codePoints);
		},

		getStatusIcon: function(status) {
			var icons = {
				'pending': '⏳',
				'processing': '⚙️',
				'in-transit': '🚚',
				'on-hold': '⏸️',
				'completed': '✅',
				'cancelled': '❌',
				'refunded': '↩️',
				'failed': '⚠️'
			};
			return icons[status] || '';
		},

		getStatusLabel: function(status) {
			var labels = {
				'pending': '<?php esc_html_e( 'Pending', 'region-manager' ); ?>',
				'processing': '<?php esc_html_e( 'Processing', 'region-manager' ); ?>',
				'in-transit': '<?php esc_html_e( 'In Transit', 'region-manager' ); ?>',
				'on-hold': '<?php esc_html_e( 'On Hold', 'region-manager' ); ?>',
				'completed': '<?php esc_html_e( 'Completed', 'region-manager' ); ?>',
				'cancelled': '<?php esc_html_e( 'Cancelled', 'region-manager' ); ?>',
				'refunded': '<?php esc_html_e( 'Refunded', 'region-manager' ); ?>',
				'failed': '<?php esc_html_e( 'Failed', 'region-manager' ); ?>'
			};
			return labels[status] || status;
		},

		formatDateTime: function(datetime) {
			var date = new Date(datetime);
			return date.toLocaleDateString() + ' ' + date.toLocaleTimeString([], {hour: '2-digit', minute:'2-digit'});
		},

		renderPagination: function(total, totalPages) {
			var self = this;
			var start = ((self.currentPage - 1) * self.perPage) + 1;
			var end = Math.min(self.currentPage * self.perPage, total);

			$('.rm-pagination-info').text(start + '-' + end + ' <?php esc_html_e( 'of', 'region-manager' ); ?> ' + total);

			var linksHtml = '';
			if (totalPages > 1) {
				linksHtml += '<a href="#" class="rm-page-link ' + (self.currentPage === 1 ? 'disabled' : '') + '" data-page="' + (self.currentPage - 1) + '">‹</a>';

				for (var i = 1; i <= totalPages; i++) {
					if (i === 1 || i === totalPages || (i >= self.currentPage - 2 && i <= self.currentPage + 2)) {
						linksHtml += '<a href="#" class="rm-page-link ' + (i === self.currentPage ? 'current' : '') + '" data-page="' + i + '">' + i + '</a>';
					} else if (i === self.currentPage - 3 || i === self.currentPage + 3) {
						linksHtml += '<span class="rm-page-dots">...</span>';
					}
				}

				linksHtml += '<a href="#" class="rm-page-link ' + (self.currentPage === totalPages ? 'disabled' : '') + '" data-page="' + (self.currentPage + 1) + '">›</a>';
			}

			$('.rm-pagination-links').html(linksHtml);
		},

		updateSelectedOrders: function() {
			this.selectedOrders = [];
			$('.rm-order-checkbox:checked').each(function() {
				OrdersManager.selectedOrders.push(parseInt($(this).val()));
			});
		},

		updateOrderStatus: function(orderId, status, $button) {
			var self = this;

			$.ajax({
				url: ajaxurl,
				type: 'POST',
				data: {
					action: 'rm_update_order_status',
					nonce: rmAdmin.nonce,
					order_id: orderId,
					status: status
				},
				beforeSend: function() {
					$button.prop('disabled', true).text('<?php esc_html_e( 'Updating...', 'region-manager' ); ?>');
				},
				success: function(response) {
					if (response.success) {
						self.showSuccess(response.data.message);
						self.loadOrders();
					} else {
						self.showError(response.data.message);
					}
				},
				error: function() {
					self.showError('<?php esc_html_e( 'Failed to update order status.', 'region-manager' ); ?>');
				},
				complete: function() {
					$button.prop('disabled', false);
				}
			});
		},

		handleBulkAction: function() {
			var self = this;
			var action = $('#rm-order-bulk-action').val();

			if (!action) {
				self.showError('<?php esc_html_e( 'Please select a bulk action.', 'region-manager' ); ?>');
				return;
			}

			if (self.selectedOrders.length === 0) {
				self.showError('<?php esc_html_e( 'Please select at least one order.', 'region-manager' ); ?>');
				return;
			}

			if (action === 'export') {
				self.exportOrders();
				return;
			}

			// Bulk status update
			$.ajax({
				url: ajaxurl,
				type: 'POST',
				data: {
					action: 'rm_bulk_update_orders',
					nonce: rmAdmin.nonce,
					order_ids: self.selectedOrders,
					status: action
				},
				beforeSend: function() {
					$('#rm-apply-order-bulk').prop('disabled', true).text('<?php esc_html_e( 'Processing...', 'region-manager' ); ?>');
				},
				success: function(response) {
					if (response.success) {
						self.showSuccess(response.data.message);
						self.loadOrders();
						self.selectedOrders = [];
						$('.rm-order-checkbox, #rm-select-all-orders').prop('checked', false);
						$('#rm-order-bulk-action').val('');
					} else {
						self.showError(response.data.message);
					}
				},
				error: function() {
					self.showError('<?php esc_html_e( 'Failed to process bulk action.', 'region-manager' ); ?>');
				},
				complete: function() {
					$('#rm-apply-order-bulk').prop('disabled', false).text('<?php esc_html_e( 'Apply', 'region-manager' ); ?>');
				}
			});
		},

		exportOrders: function() {
			var self = this;

			$.ajax({
				url: ajaxurl,
				type: 'POST',
				data: {
					action: 'rm_export_orders_csv',
					nonce: rmAdmin.nonce,
					region_id: self.currentFilters.region_id,
					status: self.currentFilters.status,
					date_after: self.currentFilters.date_after,
					date_before: self.currentFilters.date_before
				},
				success: function(response) {
					if (response.success) {
						// Trigger download
						window.location.href = response.data.download_url;
						self.showSuccess('<?php esc_html_e( 'Export file generated successfully.', 'region-manager' ); ?>');
					} else {
						self.showError(response.data.message);
					}
				},
				error: function() {
					self.showError('<?php esc_html_e( 'Failed to export orders.', 'region-manager' ); ?>');
				}
			});
		},

		viewOrderDetails: function(orderId) {
			var self = this;

			$.ajax({
				url: ajaxurl,
				type: 'POST',
				data: {
					action: 'rm_get_order_details',
					nonce: rmAdmin.nonce,
					order_id: orderId
				},
				success: function(response) {
					if (response.success) {
						self.renderOrderDetails(response.data);
						$('#rm-order-details-modal').show();
					} else {
						self.showError(response.data.message);
					}
				},
				error: function() {
					self.showError('<?php esc_html_e( 'Failed to load order details.', 'region-manager' ); ?>');
				}
			});
		},

		renderOrderDetails: function(data) {
			var html = '<div class="rm-order-details">';

			// Order items
			html += '<h3><?php esc_html_e( 'Order Items', 'region-manager' ); ?></h3>';
			html += '<table class="rm-order-items-table">';
			$.each(data.items, function(i, item) {
				html += '<tr>';
				html += '<td class="rm-item-image">' + item.image + '</td>';
				html += '<td class="rm-item-name">' + item.name + '</td>';
				html += '<td class="rm-item-qty">x' + item.quantity + '</td>';
				html += '<td class="rm-item-total">' + item.total + '</td>';
				html += '</tr>';
			});
			html += '</table>';

			// Shipping address
			html += '<h3><?php esc_html_e( 'Shipping Address', 'region-manager' ); ?></h3>';
			html += '<div class="rm-shipping-address">' + data.shipping_address + '</div>';

			// Order notes
			if (data.notes && data.notes.length > 0) {
				html += '<h3><?php esc_html_e( 'Recent Notes', 'region-manager' ); ?></h3>';
				html += '<ul class="rm-order-notes">';
				$.each(data.notes, function(i, note) {
					html += '<li><strong>' + note.date + ':</strong> ' + note.content + '</li>';
				});
				html += '</ul>';
			}

			html += '</div>';

			$('.rm-order-details-content').html(html);
		},

		showSuccess: function(message) {
			var notice = $('<div class="notice notice-success is-dismissible"><p>' + message + '</p></div>');
			$('.rm-orders-page h1').after(notice);
			setTimeout(function() { notice.fadeOut(); }, 3000);
		},

		showError: function(message) {
			var notice = $('<div class="notice notice-error is-dismissible"><p>' + message + '</p></div>');
			$('.rm-orders-page h1').after(notice);
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
	OrdersManager.init();
	window.OrdersManager = OrdersManager;
});
</script>
