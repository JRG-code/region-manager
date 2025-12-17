<?php
/**
 * Checkout settings tab content.
 *
 * @package    RegionManager
 * @subpackage RegionManager/admin/partials
 * @since      1.0.0
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

$cross_region_purchase = get_option( 'rm_cross_region_purchase', 'allow' );
$extra_charge          = get_option( 'rm_extra_charge', 0 );
$charge_type           = get_option( 'rm_charge_type', 'per_order' );
$block_message         = get_option( 'rm_block_message', __( 'Sorry, this product cannot be shipped to your location from this store region.', 'region-manager' ) );
$geoip_fallback        = get_option( 'rm_geoip_fallback', 0 );
?>

<div class="rm-checkout-tab">
	<h2><?php esc_html_e( 'Checkout Settings', 'region-manager' ); ?></h2>
	<p class="description">
		<?php esc_html_e( 'Configure how cross-region purchases are handled in your WooCommerce store.', 'region-manager' ); ?>
	</p>

	<form id="rm-checkout-settings-form">
		<!-- Cross-Region Purchase Handling -->
		<div class="rm-settings-section">
			<h3><?php esc_html_e( 'Cross-Region Purchase Handling', 'region-manager' ); ?></h3>
			<p class="description">
				<?php esc_html_e( 'Choose how to handle purchases when customers try to buy products from regions different than their detected location.', 'region-manager' ); ?>
			</p>

			<table class="form-table">
				<tbody>
					<tr>
						<th scope="row"><?php esc_html_e( 'Purchase Policy', 'region-manager' ); ?></th>
						<td>
							<fieldset>
								<label>
									<input type="radio" name="cross_region_purchase" value="allow" <?php checked( $cross_region_purchase, 'allow' ); ?>>
									<?php esc_html_e( 'Allow purchases from any region', 'region-manager' ); ?>
								</label>
								<p class="description">
									<?php esc_html_e( 'Customers can purchase products from any region without restrictions.', 'region-manager' ); ?>
								</p>
								<br>

								<label>
									<input type="radio" name="cross_region_purchase" value="charge" <?php checked( $cross_region_purchase, 'charge' ); ?>>
									<?php esc_html_e( 'Allow with extra shipping charge', 'region-manager' ); ?>
								</label>
								<p class="description">
									<?php esc_html_e( 'Apply an additional charge for cross-region purchases.', 'region-manager' ); ?>
								</p>

								<div id="charge-settings" class="rm-conditional-field" style="margin-left: 30px; margin-top: 10px; <?php echo 'charge' !== $cross_region_purchase ? 'display:none;' : ''; ?>">
									<p>
										<label for="extra_charge">
											<?php esc_html_e( 'Extra Charge Amount', 'region-manager' ); ?>
										</label>
										<input type="number" name="extra_charge" id="extra_charge" value="<?php echo esc_attr( $extra_charge ); ?>" step="0.01" min="0" class="small-text">
										<?php echo esc_html( get_woocommerce_currency_symbol() ); ?>
									</p>
									<p>
										<label>
											<input type="radio" name="charge_type" value="per_order" <?php checked( $charge_type, 'per_order' ); ?>>
											<?php esc_html_e( 'Apply once per order', 'region-manager' ); ?>
										</label>
										<br>
										<label>
											<input type="radio" name="charge_type" value="per_product" <?php checked( $charge_type, 'per_product' ); ?>>
											<?php esc_html_e( 'Apply per product', 'region-manager' ); ?>
										</label>
									</p>
								</div>
								<br>

								<label>
									<input type="radio" name="cross_region_purchase" value="block" <?php checked( $cross_region_purchase, 'block' ); ?>>
									<?php esc_html_e( 'Block purchases outside region', 'region-manager' ); ?>
								</label>
								<p class="description">
									<?php esc_html_e( 'Prevent customers from purchasing products from other regions.', 'region-manager' ); ?>
								</p>

								<div id="block-settings" class="rm-conditional-field" style="margin-left: 30px; margin-top: 10px; <?php echo 'block' !== $cross_region_purchase ? 'display:none;' : ''; ?>">
									<p>
										<label for="block_message">
											<?php esc_html_e( 'Custom Error Message', 'region-manager' ); ?>
										</label>
									</p>
									<textarea name="block_message" id="block_message" rows="3" class="large-text"><?php echo esc_textarea( $block_message ); ?></textarea>
									<p class="description">
										<?php esc_html_e( 'This message will be shown to customers when they try to purchase products from outside their region.', 'region-manager' ); ?>
									</p>
								</div>
							</fieldset>
						</td>
					</tr>
				</tbody>
			</table>
		</div>

		<!-- Region Detection -->
		<div class="rm-settings-section">
			<h3><?php esc_html_e( 'Region Detection', 'region-manager' ); ?></h3>

			<table class="form-table">
				<tbody>
					<tr>
						<th scope="row"><?php esc_html_e( 'Detection Method', 'region-manager' ); ?></th>
						<td>
							<p class="description">
								<span class="dashicons dashicons-info"></span>
								<?php esc_html_e( 'Region Manager primarily uses URL-based detection. When a customer visits a region-specific URL (e.g., /pt for Portugal), their region is automatically set.', 'region-manager' ); ?>
							</p>
							<br>
							<label>
								<input type="checkbox" name="geoip_fallback" value="1" <?php checked( $geoip_fallback, 1 ); ?>>
								<?php esc_html_e( 'Fallback to GeoIP detection if URL region is not detected', 'region-manager' ); ?>
							</label>
							<p class="description">
								<?php esc_html_e( '(Feature coming soon) Automatically detect customer region based on IP address when URL slug is not present.', 'region-manager' ); ?>
							</p>
						</td>
					</tr>
				</tbody>
			</table>
		</div>

		<p class="submit">
			<button type="submit" class="button button-primary" id="rm-save-checkout-settings">
				<?php esc_html_e( 'Save Changes', 'region-manager' ); ?>
			</button>
		</p>
	</form>
</div>

<script type="text/javascript">
jQuery(document).ready(function($) {
	// Show/hide conditional fields based on radio selection
	$('input[name="cross_region_purchase"]').on('change', function() {
		var value = $(this).val();
		$('#charge-settings').toggle(value === 'charge');
		$('#block-settings').toggle(value === 'block');
	});
});
</script>
