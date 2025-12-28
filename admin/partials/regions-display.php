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
							<th style="width: 30%;"><?php esc_html_e( 'Name', 'region-manager' ); ?></th>
							<th style="width: 20%;"><?php esc_html_e( 'Countries', 'region-manager' ); ?></th>
							<th style="width: 10%;"><?php esc_html_e( 'Status', 'region-manager' ); ?></th>
							<th style="width: 15%;"><?php esc_html_e( 'Created', 'region-manager' ); ?></th>
							<th style="width: 25%;"><?php esc_html_e( 'Actions', 'region-manager' ); ?></th>
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
	<div id="rm-region-modal" class="rm-modal" style="display: none; position: fixed; top: 0; left: 0; right: 0; bottom: 0; z-index: 100000; background: rgba(0,0,0,0.7);">
		<div class="rm-modal-content" style="position: absolute; top: 50%; left: 50%; transform: translate(-50%, -50%); background: #fff; border-radius: 4px; width: 90%; max-width: 800px; max-height: 90vh; overflow-y: auto; box-shadow: 0 5px 15px rgba(0,0,0,0.3);">
			<div class="rm-modal-header" style="padding: 20px; border-bottom: 1px solid #ddd; display: flex; justify-content: space-between; align-items: center;">
				<h2 id="rm-modal-title" style="margin: 0;"><?php esc_html_e( 'Add New Region', 'region-manager' ); ?></h2>
				<button type="button" class="rm-modal-close" style="background: none; border: none; font-size: 28px; cursor: pointer; color: #666; line-height: 1; padding: 0; width: 30px; height: 30px;">&times;</button>
			</div>
			<div class="rm-modal-body" style="padding: 20px;">
				<form id="rm-region-form">
					<input type="hidden" id="region_id" name="region_id" value="">

					<table class="form-table" role="presentation">
						<tbody>
							<tr>
								<th scope="row" style="width: 25%;">
									<label for="region_name"><?php esc_html_e( 'Region Name', 'region-manager' ); ?> <span class="required" style="color: #d63638;">*</span></label>
								</th>
								<td>
									<input type="text" id="region_name" name="region_name" class="regular-text" required>
									<p class="description"><?php esc_html_e( 'E.g., "Europe", "North America", "Asia Pacific". URL slugs are configured per country below.', 'region-manager' ); ?></p>
									<!-- Hidden field for auto-generated slug (for backward compatibility) -->
									<input type="hidden" id="region_slug" name="region_slug" required pattern="[a-z0-9\-]+">
								</td>
							</tr>
							<tr>
								<th scope="row">
									<label for="region_status"><?php esc_html_e( 'Status', 'region-manager' ); ?></label>
								</th>
								<td>
									<select id="region_status" name="region_status" class="regular-text">
										<option value="active"><?php esc_html_e( 'Active', 'region-manager' ); ?></option>
										<option value="inactive"><?php esc_html_e( 'Inactive', 'region-manager' ); ?></option>
									</select>
								</td>
							</tr>
							<tr>
								<th scope="row">
									<label for="country-select"><?php esc_html_e( 'Add Countries', 'region-manager' ); ?></label>
								</th>
								<td>
									<select id="country-select" class="regular-text" style="width: 100%;">
										<option value=""><?php esc_html_e( 'Select a country...', 'region-manager' ); ?></option>
										<?php
										$countries_obj = new WC_Countries();
										$all_countries = $countries_obj->get_countries();
										foreach ( $all_countries as $code => $name ) {
											echo '<option value="' . esc_attr( $code ) . '">' . esc_html( $name ) . ' (' . esc_html( $code ) . ')</option>';
										}
										?>
									</select>
									<p class="description"><?php esc_html_e( 'Select countries one at a time to add to this region.', 'region-manager' ); ?></p>
								</td>
							</tr>
						</tbody>
					</table>

					<div id="countries-section" style="margin-top: 20px;">
						<h3><?php esc_html_e( 'Selected Countries', 'region-manager' ); ?></h3>
						<table class="wp-list-table widefat" style="margin-top: 10px;">
							<thead>
								<tr>
									<th><?php esc_html_e( 'Country', 'region-manager' ); ?></th>
									<th><?php esc_html_e( 'URL Slug', 'region-manager' ); ?></th>
									<th><?php esc_html_e( 'Language', 'region-manager' ); ?></th>
									<th style="text-align: center;"><?php esc_html_e( 'Default', 'region-manager' ); ?></th>
									<th style="text-align: center;"><?php esc_html_e( 'Action', 'region-manager' ); ?></th>
								</tr>
							</thead>
							<tbody id="selected-countries">
								<!-- Countries will be added here by JavaScript -->
							</tbody>
						</table>
					</div>
				</form>
			</div>
			<div class="rm-modal-footer" style="padding: 15px 20px; border-top: 1px solid #ddd; text-align: right; background: #f9f9f9;">
				<button type="button" class="button button-secondary rm-modal-close" style="margin-right: 10px;"><?php esc_html_e( 'Cancel', 'region-manager' ); ?></button>
				<button type="button" class="button button-primary" id="rm-save-region"><?php esc_html_e( 'Save Region', 'region-manager' ); ?></button>
			</div>
		</div>
	</div>
</div>

<script type="text/javascript">
	// Store countries data for JavaScript
	var rmCountries = <?php echo wp_json_encode( $all_countries ); ?>;

	// Language codes
	var rmLanguageCodes = {
		'en_US': 'English (US)',
		'en_GB': 'English (UK)',
		'es_ES': 'Español',
		'pt_PT': 'Português',
		'pt_BR': 'Português (Brasil)',
		'fr_FR': 'Français',
		'de_DE': 'Deutsch',
		'it_IT': 'Italiano',
		'nl_NL': 'Nederlands',
		'pl_PL': 'Polski',
		'ru_RU': 'Русский',
		'ja': 'Japanese',
		'zh_CN': 'Chinese (Simplified)',
		'ko_KR': 'Korean'
	};
</script>
