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

	<!-- Add Country Modal -->
	<div id="rm-country-modal" class="rm-modal" style="display: none;">
		<div class="rm-modal-content" style="max-width: 600px;">
			<div class="rm-modal-header">
				<h2 id="rm-country-modal-title"><?php esc_html_e( 'Add Country to Region', 'region-manager' ); ?></h2>
				<button type="button" class="rm-modal-close">&times;</button>
			</div>
			<div class="rm-modal-body">
				<form id="rm-country-form">
					<input type="hidden" id="country_id" value="0">
					<input type="hidden" id="country_region_id" value="<?php echo esc_attr( $selected_region_id ); ?>">

					<table class="form-table">
						<tbody>
							<tr>
								<th scope="row">
									<label for="country_code"><?php esc_html_e( 'Country', 'region-manager' ); ?> <span style="color: #dc3232;">*</span></label>
								</th>
								<td>
									<select id="country_code" class="regular-text" required>
										<option value=""><?php esc_html_e( 'Select a country...', 'region-manager' ); ?></option>
										<?php
										// Get countries from WooCommerce or use fallback
										if ( class_exists( 'WC_Countries' ) ) {
											$countries_obj = new WC_Countries();
											$modal_all_countries = $countries_obj->get_countries();
										} else {
											$modal_all_countries = array(
												'PT' => 'Portugal',
												'ES' => 'Spain',
												'FR' => 'France',
												'DE' => 'Germany',
												'IT' => 'Italy',
												'GB' => 'United Kingdom',
												'US' => 'United States',
												'BR' => 'Brazil',
											);
										}
										foreach ( $modal_all_countries as $code => $name ) {
											// Skip countries already in this region
											if ( ! in_array( $code, $country_codes_in_region, true ) ) {
												echo '<option value="' . esc_attr( $code ) . '">' . esc_html( $name ) . ' (' . esc_html( $code ) . ')</option>';
											}
										}
										?>
									</select>
								</td>
							</tr>
							<tr>
								<th scope="row">
									<label for="url_slug"><?php esc_html_e( 'URL Slug', 'region-manager' ); ?> <span style="color: #dc3232;">*</span></label>
								</th>
								<td>
									<input type="text" id="url_slug" class="regular-text" placeholder="/pt" required>
									<p class="description"><?php esc_html_e( 'URL path for this country (e.g., /pt, /es)', 'region-manager' ); ?></p>
								</td>
							</tr>
							<tr>
								<th scope="row">
									<label for="language_code"><?php esc_html_e( 'Language', 'region-manager' ); ?> <span style="color: #dc3232;">*</span></label>
								</th>
								<td>
									<select id="language_code" class="regular-text" required>
										<option value="en_US">English (US)</option>
										<option value="en_GB">English (UK)</option>
										<option value="es_ES">Español</option>
										<option value="pt_PT">Português</option>
										<option value="pt_BR">Português (Brasil)</option>
										<option value="fr_FR">Français</option>
										<option value="de_DE">Deutsch</option>
										<option value="it_IT">Italiano</option>
									</select>
								</td>
							</tr>
							<tr>
								<th scope="row">
									<label for="currency_code"><?php esc_html_e( 'Currency', 'region-manager' ); ?> <span style="color: #dc3232;">*</span></label>
								</th>
								<td>
									<select id="currency_code" class="regular-text" required>
										<?php
										if ( function_exists( 'get_woocommerce_currencies' ) ) {
											$currencies = get_woocommerce_currencies();
											foreach ( $currencies as $code => $name ) {
												echo '<option value="' . esc_attr( $code ) . '">' . esc_html( $code ) . ' - ' . esc_html( $name ) . '</option>';
											}
										} else {
											?>
											<option value="EUR">EUR - Euro</option>
											<option value="USD">USD - US Dollar</option>
											<option value="GBP">GBP - Pound Sterling</option>
											<option value="BRL">BRL - Brazilian Real</option>
											<?php
										}
										?>
									</select>
								</td>
							</tr>
							<tr>
								<th scope="row">
									<label for="is_default"><?php esc_html_e( 'Default Country', 'region-manager' ); ?></label>
								</th>
								<td>
									<label>
										<input type="checkbox" id="is_default" value="1">
										<?php esc_html_e( 'Set as default country for this region', 'region-manager' ); ?>
									</label>
								</td>
							</tr>
						</tbody>
					</table>
				</form>
			</div>
			<div class="rm-modal-footer">
				<button type="button" class="button" id="rm-country-cancel"><?php esc_html_e( 'Cancel', 'region-manager' ); ?></button>
				<button type="button" class="button button-primary" id="rm-save-country"><?php esc_html_e( 'Save Country', 'region-manager' ); ?></button>
			</div>
		</div>
	</div>
</div>

<script type="text/javascript">
jQuery(document).ready(function($) {
	// Region selector change handler.
	$('#region-selector').on('change', function() {
		const regionId = $(this).val();
		window.location.href = '<?php echo esc_js( admin_url( 'admin.php?page=rm-countries' ) ); ?>&region_id=' + regionId;
	});

	// Add country button
	$('#rm-add-country').on('click', function() {
		openCountryModal();
	});

	// Close modal
	$('.rm-modal-close, #rm-country-cancel').on('click', function() {
		closeCountryModal();
	});

	// Click outside modal to close
	$('#rm-country-modal').on('click', function(e) {
		if ($(e.target).is('#rm-country-modal')) {
			closeCountryModal();
		}
	});

	// Auto-generate URL slug from country code
	$('#country_code').on('change', function() {
		const countryCode = $(this).val();
		if (countryCode) {
			$('#url_slug').val('/' + countryCode.toLowerCase());

			// Auto-fill language and currency based on country
			// IMPORTANT: Using EUR as default currency for all countries (single currency strategy)
			// You can manually change the currency if you have multi-currency configured in Stripe
			const countryDefaults = {
				'PT': { language: 'pt_PT', currency: 'EUR' },
				'ES': { language: 'es_ES', currency: 'EUR' },
				'FR': { language: 'fr_FR', currency: 'EUR' },
				'DE': { language: 'de_DE', currency: 'EUR' },
				'IT': { language: 'it_IT', currency: 'EUR' },
				'GB': { language: 'en_GB', currency: 'EUR' },
				'US': { language: 'en_US', currency: 'EUR' },
				'BR': { language: 'pt_BR', currency: 'EUR' },
				'NL': { language: 'nl_NL', currency: 'EUR' },
				'BE': { language: 'nl_NL', currency: 'EUR' },
				'CH': { language: 'de_DE', currency: 'EUR' },
				'AT': { language: 'de_DE', currency: 'EUR' },
				'IE': { language: 'en_GB', currency: 'EUR' },
				'CA': { language: 'en_US', currency: 'EUR' },
				'AU': { language: 'en_US', currency: 'EUR' },
				'NZ': { language: 'en_GB', currency: 'EUR' },
				'MX': { language: 'es_ES', currency: 'EUR' },
				'AR': { language: 'es_ES', currency: 'EUR' },
				'CL': { language: 'es_ES', currency: 'EUR' },
				'CO': { language: 'es_ES', currency: 'EUR' },
				'PE': { language: 'es_ES', currency: 'EUR' },
				'JP': { language: 'ja', currency: 'EUR' },
				'CN': { language: 'zh_CN', currency: 'EUR' },
				'KR': { language: 'ko_KR', currency: 'EUR' },
				'IN': { language: 'en_US', currency: 'EUR' },
				'RU': { language: 'ru_RU', currency: 'EUR' },
				'PL': { language: 'pl_PL', currency: 'EUR' },
				'SE': { language: 'en_GB', currency: 'EUR' },
				'NO': { language: 'en_GB', currency: 'EUR' },
				'DK': { language: 'en_GB', currency: 'EUR' },
				'FI': { language: 'fi_FI', currency: 'EUR' },
				'CZ': { language: 'cs_CZ', currency: 'EUR' },
				'HU': { language: 'hu_HU', currency: 'EUR' },
				'RO': { language: 'ro_RO', currency: 'EUR' },
				'GR': { language: 'el', currency: 'EUR' }
			};

			if (countryDefaults[countryCode]) {
				$('#language_code').val(countryDefaults[countryCode].language);
				$('#currency_code').val(countryDefaults[countryCode].currency);
			}
		}
	});

	// Save country
	$('#rm-save-country').on('click', function() {
		saveCountry();
	});

	function openCountryModal() {
		$('#rm-country-form')[0].reset();
		$('#country_id').val(0);
		$('#country_region_id').val(<?php echo esc_js( $selected_region_id ); ?>);
		$('#rm-country-modal').fadeIn(200);
		$('body').addClass('rm-modal-open');
	}

	function closeCountryModal() {
		$('#rm-country-modal').fadeOut(200);
		$('body').removeClass('rm-modal-open');
	}

	function saveCountry() {
		const countryId = $('#country_id').val();
		const regionId = $('#country_region_id').val();
		const countryCode = $('#country_code').val();
		const urlSlug = $('#url_slug').val();
		const languageCode = $('#language_code').val();
		const currencyCode = $('#currency_code').val();
		const isDefault = $('#is_default').is(':checked') ? 1 : 0;

		console.log('Saving country with data:', {
			country_id: countryId,
			region_id: regionId,
			country_code: countryCode,
			url_slug: urlSlug,
			language_code: languageCode,
			currency_code: currencyCode,
			is_default: isDefault
		});

		// Validation
		if (!countryCode || !urlSlug || !languageCode || !currencyCode) {
			alert('<?php esc_html_e( 'Please fill in all required fields.', 'region-manager' ); ?>');
			console.error('Validation failed. Missing:', {
				countryCode: !countryCode,
				urlSlug: !urlSlug,
				languageCode: !languageCode,
				currencyCode: !currencyCode
			});
			return;
		}

		// Show loading
		const $button = $('#rm-save-country');
		const originalText = $button.text();
		$button.text('<?php esc_html_e( 'Saving...', 'region-manager' ); ?>').prop('disabled', true);

		$.ajax({
			url: ajaxurl,
			type: 'POST',
			data: {
				action: 'rm_save_country',
				nonce: '<?php echo esc_js( wp_create_nonce( 'rm_admin_nonce' ) ); ?>',
				country_id: countryId,
				region_id: regionId,
				country_code: countryCode,
				url_slug: urlSlug,
				language_code: languageCode,
				currency_code: currencyCode,
				is_default: isDefault
			},
			success: function(response) {
				console.log('AJAX response:', response);
				if (response.success) {
					console.log('Country saved successfully!');
					// Reload page to show new country
					window.location.reload();
				} else {
					console.error('Save failed:', response.data);
					alert(response.data.message || '<?php esc_html_e( 'Failed to save country.', 'region-manager' ); ?>');
					$button.text(originalText).prop('disabled', false);
				}
			},
			error: function(xhr, status, error) {
				console.error('AJAX error:', {xhr: xhr, status: status, error: error});
				alert('<?php esc_html_e( 'An error occurred. Please try again.', 'region-manager' ); ?>');
				$button.text(originalText).prop('disabled', false);
			}
		});
	}

	// Edit country
	$('.rm-edit-country').on('click', function() {
		const countryId = $(this).data('country-id');
		loadCountryData(countryId);
	});

	function loadCountryData(countryId) {
		$.ajax({
			url: ajaxurl,
			type: 'POST',
			data: {
				action: 'rm_get_country',
				nonce: '<?php echo esc_js( wp_create_nonce( 'rm_admin_nonce' ) ); ?>',
				country_id: countryId
			},
			success: function(response) {
				if (response.success) {
					const country = response.data.country;
					$('#country_id').val(country.id);
					$('#country_region_id').val(country.region_id);
					$('#country_code').val(country.country_code);
					$('#url_slug').val(country.url_slug);
					$('#language_code').val(country.language_code);
					$('#currency_code').val(country.currency_code || 'EUR');
					$('#is_default').prop('checked', country.is_default == 1);
					$('#rm-country-modal-title').text('<?php esc_html_e( 'Edit Country', 'region-manager' ); ?>');
					$('#rm-country-modal').fadeIn(200);
					$('body').addClass('rm-modal-open');
				} else {
					alert(response.data.message || '<?php esc_html_e( 'Failed to load country data.', 'region-manager' ); ?>');
				}
			}
		});
	}

	// Delete country
	$('.rm-delete-country').on('click', function() {
		if (!confirm('<?php esc_html_e( 'Are you sure you want to remove this country from the region?', 'region-manager' ); ?>')) {
			return;
		}

		const countryId = $(this).data('country-id');
		const $button = $(this);
		$button.prop('disabled', true);

		$.ajax({
			url: ajaxurl,
			type: 'POST',
			data: {
				action: 'rm_delete_country',
				nonce: '<?php echo esc_js( wp_create_nonce( 'rm_admin_nonce' ) ); ?>',
				country_id: countryId
			},
			success: function(response) {
				if (response.success) {
					window.location.reload();
				} else {
					alert(response.data.message || '<?php esc_html_e( 'Failed to delete country.', 'region-manager' ); ?>');
					$button.prop('disabled', false);
				}
			}
		});
	});
});
</script>
