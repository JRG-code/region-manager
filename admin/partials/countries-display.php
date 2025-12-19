<?php
/**
 * Countries page display.
 *
 * @package    RegionManager
 * @subpackage RegionManager/admin/partials
 * @since      1.0.0
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

$settings = new RM_Settings();
$regions  = $settings->get_regions();

// Get selected region from query param.
$selected_region_id = isset( $_GET['region_id'] ) ? absint( $_GET['region_id'] ) : 0;

// If no region selected, use the first one.
if ( 0 === $selected_region_id && ! empty( $regions ) ) {
	$selected_region_id = $regions[0]->id;
}

$countries = array();
if ( $selected_region_id > 0 ) {
	$countries = $settings->get_region_countries( $selected_region_id );
}

$wc_countries     = new WC_Countries();
$all_countries    = $wc_countries->get_countries();
$country_codes_in_region = wp_list_pluck( $countries, 'country_code' );
?>

<div class="wrap rm-admin-wrap">
	<h1><?php echo esc_html( get_admin_page_title() ); ?></h1>

	<div class="rm-countries-page">
		<?php if ( empty( $regions ) ) : ?>
			<div class="notice notice-warning">
				<p>
					<?php
					printf(
						/* translators: %s: link to regions page */
						__( 'No regions found. Please <a href="%s">create a region</a> first.', 'region-manager' ), // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
						esc_url( admin_url( 'admin.php?page=rm-regions' ) )
					);
					?>
				</p>
			</div>
		<?php else : ?>
			<div class="rm-tab-header" style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 20px;">
				<div class="rm-tab-header-left" style="flex: 1;">
					<p class="description">
						<?php esc_html_e( 'Manage countries within each region. Assign countries to regions and configure language and URL settings.', 'region-manager' ); ?>
					</p>
					<div style="margin-top: 15px;">
						<label for="region-selector" style="font-weight: 600; margin-right: 10px;">
							<?php esc_html_e( 'Select Region:', 'region-manager' ); ?>
						</label>
						<select id="region-selector" style="min-width: 250px;">
							<?php foreach ( $regions as $region ) : ?>
								<option value="<?php echo esc_attr( $region->id ); ?>" <?php selected( $selected_region_id, $region->id ); ?>>
									<?php echo esc_html( $region->name ); ?>
									(<?php echo esc_html( $region->slug ); ?>)
								</option>
							<?php endforeach; ?>
						</select>
					</div>
				</div>
				<div class="rm-tab-header-right">
					<button type="button" class="button button-primary" id="rm-add-country">
						<?php esc_html_e( 'Add Country to Region', 'region-manager' ); ?>
					</button>
				</div>
			</div>

			<div class="rm-table-wrapper">
				<?php if ( empty( $countries ) ) : ?>
					<div class="rm-empty-state" style="text-align: center; padding: 60px 20px; border: 2px dashed #c3c4c7; border-radius: 4px; background: #f9f9f9;">
						<div class="rm-empty-icon" style="font-size: 48px; color: #c3c4c7; margin-bottom: 20px;">
							<span class="dashicons dashicons-admin-site" style="width: 64px; height: 64px; font-size: 64px;"></span>
						</div>
						<h3><?php esc_html_e( 'No countries in this region', 'region-manager' ); ?></h3>
						<p><?php esc_html_e( 'Add countries to this region to start managing regional settings.', 'region-manager' ); ?></p>
						<button type="button" class="button button-primary" onclick="document.getElementById('rm-add-country').click();">
							<?php esc_html_e( 'Add Your First Country', 'region-manager' ); ?>
						</button>
					</div>
				<?php else : ?>
					<table class="wp-list-table widefat fixed striped" id="countries-table">
						<thead>
							<tr>
								<th style="width: 5%;"><?php esc_html_e( 'Flag', 'region-manager' ); ?></th>
								<th style="width: 25%;"><?php esc_html_e( 'Country', 'region-manager' ); ?></th>
								<th style="width: 10%;"><?php esc_html_e( 'Code', 'region-manager' ); ?></th>
								<th style="width: 15%;"><?php esc_html_e( 'URL Slug', 'region-manager' ); ?></th>
								<th style="width: 15%;"><?php esc_html_e( 'Language', 'region-manager' ); ?></th>
								<th style="width: 10%;"><?php esc_html_e( 'Default', 'region-manager' ); ?></th>
								<th style="width: 20%;"><?php esc_html_e( 'Actions', 'region-manager' ); ?></th>
							</tr>
						</thead>
						<tbody>
							<?php foreach ( $countries as $country ) : ?>
								<tr data-country-id="<?php echo esc_attr( $country->id ); ?>">
									<td style="text-align: center; font-size: 24px;">
										<?php
										// Simple flag emoji generator.
										$flag = '';
										if ( 2 === strlen( $country->country_code ) ) {
											$code_points = array();
											for ( $i = 0; $i < 2; $i++ ) {
												$code_points[] = 127397 + ord( $country->country_code[ $i ] );
											}
											$flag = mb_convert_encoding( '&#' . implode( ';&#', $code_points ) . ';', 'UTF-8', 'HTML-ENTITIES' );
										}
										echo $flag ? $flag : '🏳️'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
										?>
									</td>
									<td><strong><?php echo esc_html( $all_countries[ $country->country_code ] ?? $country->country_code ); ?></strong></td>
									<td><code><?php echo esc_html( $country->country_code ); ?></code></td>
									<td><code><?php echo esc_html( $country->url_slug ); ?></code></td>
									<td><?php echo esc_html( $country->language_code ); ?></td>
									<td>
										<?php if ( $country->is_default ) : ?>
											<span class="dashicons dashicons-yes-alt" style="color: #00a32a;"></span>
										<?php else : ?>
											<span style="color: #dcdcde;">—</span>
										<?php endif; ?>
									</td>
									<td class="actions">
										<button type="button" class="button button-small rm-edit-country" data-country-id="<?php echo esc_attr( $country->id ); ?>">
											<?php esc_html_e( 'Edit', 'region-manager' ); ?>
										</button>
										<button type="button" class="button button-small rm-delete-country" data-country-id="<?php echo esc_attr( $country->id ); ?>" style="margin-left: 5px;">
											<?php esc_html_e( 'Remove', 'region-manager' ); ?>
										</button>
									</td>
								</tr>
							<?php endforeach; ?>
						</tbody>
					</table>
				<?php endif; ?>
			</div>
		<?php endif; ?>
	</div>
</div>

<script type="text/javascript">
jQuery(document).ready(function($) {
	// Region selector change handler.
	$('#region-selector').on('change', function() {
		const regionId = $(this).val();
		window.location.href = '<?php echo esc_js( admin_url( 'admin.php?page=rm-countries' ) ); ?>&region_id=' + regionId;
	});
});
</script>
