<?php
/**
 * Dashboard display template.
 *
 * @package    RegionManager
 * @subpackage RegionManager/admin/partials
 * @since      1.0.0
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

// Get dashboard data.
$regions_count   = $this->get_regions_count();
$orders_comp     = $this->get_orders_comparison();
$revenue_comp    = $this->get_revenue_comparison();
$products_with   = $this->get_products_with_region();
$products_without = $this->get_products_without_region();
$regions         = $this->get_regions();
$orders_by_status = $this->get_orders_by_status();
?>

<div class="wrap rm-admin-wrap rm-dashboard-wrap">
	<h1><?php esc_html_e( 'Region Manager Dashboard', 'region-manager' ); ?></h1>

	<!-- Overview Cards -->
	<div class="rm-dashboard-cards">
		<!-- Card 1: Total Regions -->
		<div class="rm-dashboard-card">
			<div class="rm-card-icon">
				<span class="dashicons dashicons-admin-site-alt3"></span>
			</div>
			<div class="rm-card-content">
				<h3><?php esc_html_e( 'Total Regions', 'region-manager' ); ?></h3>
				<div class="rm-card-value"><?php echo absint( $regions_count['total'] ); ?></div>
				<div class="rm-card-subtitle">
					<?php
					printf(
						/* translators: 1: active count, 2: inactive count */
						esc_html__( '%1$d Active / %2$d Inactive', 'region-manager' ),
						absint( $regions_count['active'] ),
						absint( $regions_count['inactive'] )
					);
					?>
				</div>
				<a href="<?php echo esc_url( admin_url( 'admin.php?page=rm-settings&tab=regions' ) ); ?>" class="rm-card-link">
					<?php esc_html_e( 'Manage Regions', 'region-manager' ); ?> &rarr;
				</a>
			</div>
		</div>

		<!-- Card 2: Total Orders Today -->
		<div class="rm-dashboard-card">
			<div class="rm-card-icon">
				<span class="dashicons dashicons-cart"></span>
			</div>
			<div class="rm-card-content">
				<h3><?php esc_html_e( 'Orders Today', 'region-manager' ); ?></h3>
				<div class="rm-card-value"><?php echo absint( $orders_comp['today'] ); ?></div>
				<div class="rm-card-subtitle rm-trend-<?php echo esc_attr( $orders_comp['change_type'] ); ?>">
					<?php if ( 'up' === $orders_comp['change_type'] ) : ?>
						<span class="dashicons dashicons-arrow-up-alt"></span>
					<?php elseif ( 'down' === $orders_comp['change_type'] ) : ?>
						<span class="dashicons dashicons-arrow-down-alt"></span>
					<?php endif; ?>
					<?php echo abs( $orders_comp['change'] ); ?>% <?php esc_html_e( 'vs yesterday', 'region-manager' ); ?>
				</div>
				<?php if ( ! empty( $regions ) && count( $regions ) > 1 ) : ?>
					<div class="rm-card-meta">
						<?php
						foreach ( $regions as $region ) {
							$region_orders = $this->get_orders_count_today( $region->id );
							if ( $region_orders > 0 ) {
								echo '<span>' . esc_html( $region->name ) . ': ' . absint( $region_orders ) . '</span>';
							}
						}
						?>
					</div>
				<?php endif; ?>
			</div>
		</div>

		<!-- Card 3: Revenue Today -->
		<div class="rm-dashboard-card">
			<div class="rm-card-icon">
				<span class="dashicons dashicons-money-alt"></span>
			</div>
			<div class="rm-card-content">
				<h3><?php esc_html_e( 'Revenue Today', 'region-manager' ); ?></h3>
				<div class="rm-card-value"><?php echo wc_price( $revenue_comp['today'] ); ?></div>
				<div class="rm-card-subtitle rm-trend-<?php echo esc_attr( $revenue_comp['change_type'] ); ?>">
					<?php if ( 'up' === $revenue_comp['change_type'] ) : ?>
						<span class="dashicons dashicons-arrow-up-alt"></span>
					<?php elseif ( 'down' === $revenue_comp['change_type'] ) : ?>
						<span class="dashicons dashicons-arrow-down-alt"></span>
					<?php endif; ?>
					<?php echo abs( $revenue_comp['change'] ); ?>% <?php esc_html_e( 'vs yesterday', 'region-manager' ); ?>
				</div>
				<?php if ( ! empty( $regions ) && count( $regions ) > 1 ) : ?>
					<div class="rm-card-meta">
						<?php
						foreach ( $regions as $region ) {
							$region_revenue = $this->get_revenue_today( $region->id );
							if ( $region_revenue > 0 ) {
								echo '<span>' . esc_html( $region->name ) . ': ' . wc_price( $region_revenue ) . '</span>';
							}
						}
						?>
					</div>
				<?php endif; ?>
			</div>
		</div>

		<!-- Card 4: Products in Regions -->
		<div class="rm-dashboard-card">
			<div class="rm-card-icon">
				<span class="dashicons dashicons-products"></span>
			</div>
			<div class="rm-card-content">
				<h3><?php esc_html_e( 'Products in Regions', 'region-manager' ); ?></h3>
				<div class="rm-card-value"><?php echo absint( $products_with ); ?></div>
				<?php if ( $products_without > 0 ) : ?>
					<div class="rm-card-subtitle rm-text-warning">
						<span class="dashicons dashicons-warning"></span>
						<?php
						printf(
							/* translators: %d: number of products */
							esc_html( _n( '%d product without region', '%d products without region', $products_without, 'region-manager' ) ),
							absint( $products_without )
						);
						?>
					</div>
				<?php else : ?>
					<div class="rm-card-subtitle">
						<?php esc_html_e( 'All products assigned', 'region-manager' ); ?>
					</div>
				<?php endif; ?>
				<a href="<?php echo esc_url( admin_url( 'edit.php?post_type=product' ) ); ?>" class="rm-card-link">
					<?php esc_html_e( 'Manage Products', 'region-manager' ); ?> &rarr;
				</a>
			</div>
		</div>
	</div>

	<!-- Region Breakdown Table -->
	<?php if ( ! empty( $regions ) ) : ?>
		<div class="rm-dashboard-section">
			<h2><?php esc_html_e( 'Region Performance', 'region-manager' ); ?></h2>
			<div class="rm-table-wrapper">
				<table class="rm-table widefat rm-sortable-table">
					<thead>
						<tr>
							<th><?php esc_html_e( 'Region', 'region-manager' ); ?></th>
							<th><?php esc_html_e( 'Orders (Today)', 'region-manager' ); ?></th>
							<th><?php esc_html_e( 'Orders (This Month)', 'region-manager' ); ?></th>
							<th><?php esc_html_e( 'Revenue (Today)', 'region-manager' ); ?></th>
							<th><?php esc_html_e( 'Revenue (This Month)', 'region-manager' ); ?></th>
							<th><?php esc_html_e( 'Top Product', 'region-manager' ); ?></th>
						</tr>
					</thead>
					<tbody>
						<?php foreach ( $regions as $region ) : ?>
							<?php
							$orders_today      = $this->get_orders_count_today( $region->id );
							$orders_month      = count( $this->get_orders_by_region( $region->id, gmdate( 'Y-m-01' ), gmdate( 'Y-m-d' ) ) );
							$revenue_today     = $this->get_revenue_today( $region->id );
							$revenue_month     = $this->get_revenue_by_region( $region->id, gmdate( 'Y-m-01' ), gmdate( 'Y-m-d' ) );
							$top_products      = $this->get_top_products_by_region( $region->id, 1 );
							$top_product_name  = ! empty( $top_products ) ? $top_products[0]['name'] : esc_html__( 'N/A', 'region-manager' );
							?>
							<tr>
								<td>
									<strong><?php echo esc_html( $region->name ); ?></strong>
									<br>
									<code><?php echo esc_html( $region->slug ); ?></code>
								</td>
								<td><?php echo absint( $orders_today ); ?></td>
								<td><?php echo absint( $orders_month ); ?></td>
								<td><?php echo wc_price( $revenue_today ); ?></td>
								<td><?php echo wc_price( $revenue_month ); ?></td>
								<td><?php echo esc_html( $top_product_name ); ?></td>
							</tr>
						<?php endforeach; ?>
					</tbody>
				</table>
			</div>
		</div>
	<?php endif; ?>

	<!-- Recent Orders by Region -->
	<?php if ( ! empty( $regions ) ) : ?>
		<div class="rm-dashboard-section">
			<h2><?php esc_html_e( 'Recent Orders by Region', 'region-manager' ); ?></h2>
			<div class="rm-orders-grid">
				<?php
				$displayed = 0;
				foreach ( $regions as $region ) :
					if ( $displayed >= 4 ) {
						break;
					}
					$recent_orders = $this->get_recent_orders_by_region( $region->id, 5 );
					if ( empty( $recent_orders ) ) {
						continue;
					}
					$displayed++;
					?>
					<div class="rm-orders-panel">
						<div class="rm-panel-header">
							<h3><?php echo esc_html( $region->name ); ?></h3>
							<a href="<?php echo esc_url( admin_url( 'edit.php?post_type=shop_order' ) ); ?>" class="rm-panel-link">
								<?php esc_html_e( 'View All', 'region-manager' ); ?>
							</a>
						</div>
						<div class="rm-panel-content">
							<table class="rm-orders-table">
								<thead>
									<tr>
										<th><?php esc_html_e( 'Order', 'region-manager' ); ?></th>
										<th><?php esc_html_e( 'Customer', 'region-manager' ); ?></th>
										<th><?php esc_html_e( 'Total', 'region-manager' ); ?></th>
										<th><?php esc_html_e( 'Status', 'region-manager' ); ?></th>
									</tr>
								</thead>
								<tbody>
									<?php foreach ( $recent_orders as $order ) : ?>
										<tr>
											<td>
												<a href="<?php echo esc_url( $order->get_edit_order_url() ); ?>">
													#<?php echo esc_html( $order->get_order_number() ); ?>
												</a>
											</td>
											<td><?php echo esc_html( $order->get_billing_first_name() . ' ' . $order->get_billing_last_name() ); ?></td>
											<td><?php echo wc_price( $order->get_total() ); ?></td>
											<td>
												<span class="rm-status-badge rm-status-<?php echo esc_attr( $order->get_status() ); ?>">
													<?php echo esc_html( wc_get_order_status_name( $order->get_status() ) ); ?>
												</span>
											</td>
										</tr>
									<?php endforeach; ?>
								</tbody>
							</table>
						</div>
					</div>
				<?php endforeach; ?>
			</div>
		</div>
	<?php endif; ?>

	<!-- Quick Stats -->
	<div class="rm-dashboard-section">
		<h2><?php esc_html_e( 'Orders by Status', 'region-manager' ); ?></h2>
		<div class="rm-stats-container">
			<div class="rm-chart-wrapper">
				<canvas id="rm-status-chart" width="300" height="300"></canvas>
			</div>
			<div class="rm-chart-legend">
				<?php
				$status_colors = array(
					'processing' => '#7e3bd0',
					'on-hold'    => '#f0ad4e',
					'completed'  => '#5cb85c',
					'pending'    => '#999999',
				);

				$total_orders = array_sum( $orders_by_status );

				foreach ( $orders_by_status as $status => $count ) :
					$percentage = $total_orders > 0 ? round( ( $count / $total_orders ) * 100, 1 ) : 0;
					?>
					<div class="rm-legend-item">
						<span class="rm-legend-color" style="background-color: <?php echo esc_attr( $status_colors[ $status ] ); ?>;"></span>
						<span class="rm-legend-label"><?php echo esc_html( ucfirst( str_replace( '-', ' ', $status ) ) ); ?></span>
						<span class="rm-legend-value"><?php echo absint( $count ); ?> (<?php echo esc_html( $percentage ); ?>%)</span>
					</div>
				<?php endforeach; ?>
			</div>
		</div>
	</div>
</div>

<script type="text/javascript">
jQuery(document).ready(function($) {
	// Simple CSS-based pie chart alternative (if Chart.js not available)
	// For production, you may want to include Chart.js library
	var canvas = document.getElementById('rm-status-chart');
	if (canvas && typeof Chart !== 'undefined') {
		var ctx = canvas.getContext('2d');
		var data = {
			labels: <?php echo wp_json_encode( array_map( 'ucfirst', array_keys( $orders_by_status ) ) ); ?>,
			datasets: [{
				data: <?php echo wp_json_encode( array_values( $orders_by_status ) ); ?>,
				backgroundColor: [
					'<?php echo $status_colors['processing']; ?>',
					'<?php echo $status_colors['on-hold']; ?>',
					'<?php echo $status_colors['completed']; ?>',
					'<?php echo $status_colors['pending']; ?>'
				]
			}]
		};

		// Note: This requires Chart.js to be loaded
		// For now, we'll show a simple message
		if (<?php echo array_sum( $orders_by_status ); ?> === 0) {
			ctx.font = '16px Arial';
			ctx.textAlign = 'center';
			ctx.fillText('<?php esc_html_e( 'No orders yet', 'region-manager' ); ?>', 150, 150);
		}
	}
});
</script>
