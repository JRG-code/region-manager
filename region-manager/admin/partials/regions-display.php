<?php
/**
 * Regions page display.
 *
 * @package    RegionManager
 * @subpackage RegionManager/admin/partials
 * @since      1.0.0
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

$settings     = new RM_Settings();
$regions      = $settings->get_regions();
$license      = RM_License::get_instance();
$license_info = $license->get_license_info();
?>

<div class="wrap rm-admin-wrap">
	<h1><?php echo esc_html( get_admin_page_title() ); ?></h1>

	<div class="rm-regions-page">
		<div class="rm-tab-header" style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 20px;">
			<div class="rm-tab-header-left">
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
							__( 'Region limit reached. <a href="%s">Upgrade to Pro</a>', 'region-manager' ), // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
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
				<div class="rm-empty-state" style="text-align: center; padding: 60px 20px; border: 2px dashed #c3c4c7; border-radius: 4px; background: #f9f9f9;">
					<div class="rm-empty-icon" style="font-size: 48px; color: #c3c4c7; margin-bottom: 20px;">
						<span class="dashicons dashicons-admin-site-alt3" style="width: 64px; height: 64px; font-size: 64px;"></span>
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
				<table class="wp-list-table widefat fixed striped" id="regions-table">
					<thead>
						<tr>
							<th style="width: 25%;"><?php esc_html_e( 'Name', 'region-manager' ); ?></th>
							<th style="width: 15%;"><?php esc_html_e( 'Slug', 'region-manager' ); ?></th>
							<th style="width: 15%;"><?php esc_html_e( 'Countries', 'region-manager' ); ?></th>
							<th style="width: 10%;"><?php esc_html_e( 'Status', 'region-manager' ); ?></th>
							<th style="width: 15%;"><?php esc_html_e( 'Created', 'region-manager' ); ?></th>
							<th style="width: 20%;"><?php esc_html_e( 'Actions', 'region-manager' ); ?></th>
						</tr>
					</thead>
					<tbody>
						<?php foreach ( $regions as $region ) : ?>
							<?php
							$countries       = $settings->get_region_countries( $region->id );
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
										echo '<span style="color: #999;">' . esc_html__( 'No countries', 'region-manager' ) . '</span>';
									}
									?>
								</td>
								<td>
									<span class="rm-status-badge <?php echo esc_attr( $region->status ); ?>" style="display: inline-block; padding: 3px 10px; border-radius: 3px; font-size: 11px; font-weight: 600; text-transform: uppercase; <?php echo 'active' === $region->status ? 'background: #00a32a; color: white;' : 'background: #dcdcde; color: #50575e;'; ?>">
										<?php echo esc_html( ucfirst( $region->status ) ); ?>
									</span>
								</td>
								<td>
									<?php
									echo esc_html(
										date_i18n(
											get_option( 'date_format' ),
											strtotime( $region->created_at )
										)
									);
									?>
								</td>
								<td class="actions">
									<button type="button" class="button button-small rm-edit-region" data-region-id="<?php echo esc_attr( $region->id ); ?>">
										<?php esc_html_e( 'Edit', 'region-manager' ); ?>
									</button>
									<button type="button" class="button button-small rm-delete-region" data-region-id="<?php echo esc_attr( $region->id ); ?>" style="margin-left: 5px;">
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

	<!-- Region Modal -->
	<div id="rm-region-modal" class="rm-modal" style="display: none;">
		<div class="rm-modal-overlay"></div>
		<div class="rm-modal-content">
			<div class="rm-modal-header">
				<h2 id="rm-modal-title"><?php esc_html_e( 'Add New Region', 'region-manager' ); ?></h2>
				<button type="button" class="rm-modal-close">&times;</button>
			</div>
			<div class="rm-modal-body">
				<form id="rm-region-form">
					<input type="hidden" id="region-id" name="region_id" value="">

					<table class="form-table" role="presentation">
						<tbody>
							<tr>
								<th scope="row">
									<label for="region-name"><?php esc_html_e( 'Region Name', 'region-manager' ); ?> <span class="required">*</span></label>
								</th>
								<td>
									<input type="text" id="region-name" name="region_name" class="regular-text" required>
									<p class="description"><?php esc_html_e( 'E.g., "Europe", "North America", "Asia Pacific"', 'region-manager' ); ?></p>
								</td>
							</tr>
							<tr>
								<th scope="row">
									<label for="region-slug"><?php esc_html_e( 'URL Slug', 'region-manager' ); ?> <span class="required">*</span></label>
								</th>
								<td>
									<input type="text" id="region-slug" name="region_slug" class="regular-text" required pattern="[a-z0-9\-]+">
									<p class="description"><?php esc_html_e( 'Used in URLs. Lowercase letters, numbers, and hyphens only.', 'region-manager' ); ?></p>
								</td>
							</tr>
							<tr>
								<th scope="row">
									<label for="region-status"><?php esc_html_e( 'Status', 'region-manager' ); ?></label>
								</th>
								<td>
									<select id="region-status" name="region_status" class="regular-text">
										<option value="active"><?php esc_html_e( 'Active', 'region-manager' ); ?></option>
										<option value="inactive"><?php esc_html_e( 'Inactive', 'region-manager' ); ?></option>
									</select>
								</td>
							</tr>
							<tr>
								<th scope="row">
									<label for="region-countries"><?php esc_html_e( 'Countries', 'region-manager' ); ?></label>
								</th>
								<td>
									<select id="region-countries" name="region_countries[]" multiple class="regular-text" style="height: 200px;">
										<?php
										$countries_obj = new WC_Countries();
										$countries     = $countries_obj->get_countries();
										foreach ( $countries as $code => $name ) {
											echo '<option value="' . esc_attr( $code ) . '">' . esc_html( $name ) . '</option>';
										}
										?>
									</select>
									<p class="description"><?php esc_html_e( 'Hold Ctrl (Cmd on Mac) to select multiple countries.', 'region-manager' ); ?></p>
								</td>
							</tr>
						</tbody>
					</table>
				</form>
			</div>
			<div class="rm-modal-footer">
				<button type="button" class="button button-secondary rm-modal-close"><?php esc_html_e( 'Cancel', 'region-manager' ); ?></button>
				<button type="button" class="button button-primary" id="rm-save-region"><?php esc_html_e( 'Save Region', 'region-manager' ); ?></button>
			</div>
		</div>
	</div>
</div>
