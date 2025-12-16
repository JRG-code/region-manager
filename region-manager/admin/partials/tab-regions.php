<?php
/**
 * Regions tab content.
 *
 * @package    RegionManager
 * @subpackage RegionManager/admin/partials
 * @since      1.0.0
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

$regions      = $this->get_regions();
$license      = RM_License::get_instance();
$license_info = $license->get_license_info();
?>

<div class="rm-regions-tab">
	<div class="rm-tab-header">
		<div class="rm-tab-header-left">
			<h2><?php esc_html_e( 'Manage Regions', 'region-manager' ); ?></h2>
			<p class="description">
				<?php esc_html_e( 'Create and manage regions for your WooCommerce store. Each region can have multiple countries with custom URLs and languages.', 'region-manager' ); ?>
			</p>
		</div>
		<div class="rm-tab-header-right">
			<?php if ( $license_info['can_create'] ) : ?>
				<button type="button" class="button button-primary" id="rm-add-region">
					<?php esc_html_e( 'Add New Region', 'region-manager' ); ?>
				</button>
			<?php else : ?>
				<button type="button" class="button button-primary" disabled title="<?php esc_attr_e( 'Upgrade to Pro for unlimited regions', 'region-manager' ); ?>">
					<?php esc_html_e( 'Add New Region', 'region-manager' ); ?>
				</button>
				<p class="description" style="color: #d63638; margin-top: 10px;">
					<?php
					printf(
						/* translators: %s: upgrade link */
						__( 'Region limit reached. <a href="%s">Upgrade to Pro</a>', 'region-manager' ),
						esc_url( admin_url( 'admin.php?page=rm-settings&tab=license' ) )
					);
					?>
				</p>
			<?php endif; ?>
		</div>
	</div>

	<?php if ( ! $license_info['is_pro'] ) : ?>
		<div class="notice notice-info">
			<p>
				<?php
				printf(
					/* translators: 1: current count, 2: max regions */
					esc_html__( 'You are using %1$d of %2$d regions on the Free tier. Upgrade to Pro for unlimited regions.', 'region-manager' ),
					absint( $license_info['current_count'] ),
					absint( $license_info['max_regions'] )
				);
				?>
				<a href="<?php echo esc_url( admin_url( 'admin.php?page=rm-settings&tab=license' ) ); ?>" class="button button-small">
					<?php esc_html_e( 'Upgrade Now', 'region-manager' ); ?>
				</a>
			</p>
		</div>
	<?php endif; ?>

	<div class="rm-table-wrapper">
		<?php if ( empty( $regions ) ) : ?>
			<div class="rm-empty-state">
				<div class="rm-empty-icon">
					<span class="dashicons dashicons-admin-site-alt3"></span>
				</div>
				<h3><?php esc_html_e( 'No regions yet', 'region-manager' ); ?></h3>
				<p><?php esc_html_e( 'Get started by creating your first region.', 'region-manager' ); ?></p>
				<?php if ( $license_info['can_create'] ) : ?>
					<button type="button" class="button button-primary" onclick="document.getElementById('rm-add-region').click();">
						<?php esc_html_e( 'Create Your First Region', 'region-manager' ); ?>
					</button>
				<?php endif; ?>
			</div>
		<?php else : ?>
			<table class="rm-table widefat" id="regions-table">
				<thead>
					<tr>
						<th><?php esc_html_e( 'Name', 'region-manager' ); ?></th>
						<th><?php esc_html_e( 'Slug', 'region-manager' ); ?></th>
						<th><?php esc_html_e( 'Countries', 'region-manager' ); ?></th>
						<th><?php esc_html_e( 'Status', 'region-manager' ); ?></th>
						<th><?php esc_html_e( 'Created', 'region-manager' ); ?></th>
						<th><?php esc_html_e( 'Actions', 'region-manager' ); ?></th>
					</tr>
				</thead>
				<tbody>
					<?php foreach ( $regions as $region ) : ?>
						<?php
						$countries       = $this->get_region_countries( $region->id );
						$countries_count = count( $countries );
						?>
						<tr data-region-id="<?php echo esc_attr( $region->id ); ?>">
							<td><strong><?php echo esc_html( $region->name ); ?></strong></td>
							<td><code><?php echo esc_html( $region->slug ); ?></code></td>
							<td>
								<?php
								if ( $countries_count > 0 ) {
									echo esc_html( $countries_count ) . ' ' . esc_html( _n( 'country', 'countries', $countries_count, 'region-manager' ) );
								} else {
									echo '<span class="rm-text-muted">' . esc_html__( 'No countries', 'region-manager' ) . '</span>';
								}
								?>
							</td>
							<td>
								<span class="rm-status-badge <?php echo esc_attr( $region->status ); ?>">
									<?php echo esc_html( ucfirst( $region->status ) ); ?>
								</span>
							</td>
							<td>
								<?php
								echo esc_html( date_i18n(
									get_option( 'date_format' ),
									strtotime( $region->created_at )
								) );
								?>
							</td>
							<td class="actions">
								<button type="button" class="button button-small rm-edit-region" data-region-id="<?php echo esc_attr( $region->id ); ?>">
									<?php esc_html_e( 'Edit', 'region-manager' ); ?>
								</button>
								<button type="button" class="button button-small rm-delete-region" data-region-id="<?php echo esc_attr( $region->id ); ?>">
									<?php esc_html_e( 'Delete', 'region-manager' ); ?>
								</button>
							</td>
						</tr>
					<?php endforeach; ?>
				</tbody>
			</table>
		<?php endif; ?>
	</div>
</div>
