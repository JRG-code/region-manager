<?php
/**
 * License tab content.
 *
 * @package    RegionManager
 * @subpackage RegionManager/admin/partials
 * @since      1.0.0
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

$license      = RM_License::get_instance();
$license_info = $license->get_license_info();
?>

<div class="rm-license-tab">
	<h2><?php esc_html_e( 'License Management', 'region-manager' ); ?></h2>
	<p class="description">
		<?php esc_html_e( 'Manage your Region Manager license and unlock premium features.', 'region-manager' ); ?>
	</p>

	<div class="rm-license-settings">
		<table class="form-table">
			<tbody>
				<tr>
					<th scope="row">
						<?php esc_html_e( 'License Status', 'region-manager' ); ?>
					</th>
					<td>
						<span class="rm-status-badge <?php echo esc_attr( $license_info['status'] ); ?>">
							<?php echo esc_html( strtoupper( $license_info['status'] ) ); ?>
						</span>
						<?php if ( $license_info['is_pro'] ) : ?>
							<p class="description">
								<?php esc_html_e( 'You have unlimited access to all features.', 'region-manager' ); ?>
							</p>
						<?php else : ?>
							<p class="description">
								<?php
								printf(
									/* translators: %d: maximum regions allowed */
									esc_html__( 'Free tier: Limited to %d regions.', 'region-manager' ),
									absint( $license_info['max_regions'] )
								);
								?>
							</p>
						<?php endif; ?>
					</td>
				</tr>
				<tr>
					<th scope="row">
						<?php esc_html_e( 'Region Usage', 'region-manager' ); ?>
					</th>
					<td>
						<?php if ( -1 === $license_info['max_regions'] ) : ?>
							<strong>
								<?php
								printf(
									/* translators: %d: current region count */
									esc_html__( '%d regions created (Unlimited)', 'region-manager' ),
									absint( $license_info['current_count'] )
								);
								?>
							</strong>
						<?php else : ?>
							<strong>
								<?php
								printf(
									/* translators: 1: current region count, 2: maximum regions allowed */
									esc_html__( '%1$d of %2$d regions used', 'region-manager' ),
									absint( $license_info['current_count'] ),
									absint( $license_info['max_regions'] )
								);
								?>
							</strong>
							<?php if ( ! $license_info['can_create'] ) : ?>
								<p class="description" style="color: #d63638;">
									<?php esc_html_e( 'You have reached your region limit. Upgrade to Pro for unlimited regions.', 'region-manager' ); ?>
								</p>
							<?php endif; ?>
						<?php endif; ?>
					</td>
				</tr>
			</tbody>
		</table>

		<?php if ( $license_info['is_pro'] ) : ?>
			<!-- Pro License - Show deactivation form -->
			<h3><?php esc_html_e( 'License Key', 'region-manager' ); ?></h3>
			<form method="post" action="">
				<?php wp_nonce_field( 'rm_license_action', 'rm_license_nonce' ); ?>
				<table class="form-table">
					<tbody>
						<tr>
							<th scope="row">
								<?php esc_html_e( 'Your License Key', 'region-manager' ); ?>
							</th>
							<td>
								<input type="text" class="regular-text" value="<?php echo esc_attr( str_repeat( '*', max( 0, strlen( $license_info['license_key'] ) - 4 ) ) . substr( $license_info['license_key'], -4 ) ); ?>" disabled />
							</td>
						</tr>
					</tbody>
				</table>
				<input type="hidden" name="license_action" value="deactivate" />
				<p>
					<button type="submit" class="button button-secondary">
						<?php esc_html_e( 'Deactivate License', 'region-manager' ); ?>
					</button>
				</p>
			</form>
		<?php else : ?>
			<!-- Free Tier - Show activation form -->
			<h3><?php esc_html_e( 'Activate Pro License', 'region-manager' ); ?></h3>
			<form method="post" action="">
				<?php wp_nonce_field( 'rm_license_action', 'rm_license_nonce' ); ?>
				<table class="form-table">
					<tbody>
						<tr>
							<th scope="row">
								<label for="license_key">
									<?php esc_html_e( 'License Key', 'region-manager' ); ?>
								</label>
							</th>
							<td>
								<input type="text" name="license_key" id="license_key" class="regular-text" placeholder="<?php esc_attr_e( 'Enter your license key', 'region-manager' ); ?>" />
								<p class="description">
									<?php esc_html_e( 'Enter your Pro license key to unlock unlimited regions.', 'region-manager' ); ?>
								</p>
							</td>
						</tr>
					</tbody>
				</table>
				<input type="hidden" name="license_action" value="activate" />
				<p>
					<button type="submit" class="button button-primary">
						<?php esc_html_e( 'Activate License', 'region-manager' ); ?>
					</button>
				</p>
			</form>

			<!-- Features Comparison -->
			<div class="rm-license-comparison" style="margin-top: 40px;">
				<h3><?php esc_html_e( 'Compare Free vs Pro', 'region-manager' ); ?></h3>
				<table class="rm-table widefat">
					<thead>
						<tr>
							<th><?php esc_html_e( 'Feature', 'region-manager' ); ?></th>
							<th style="text-align: center;"><?php esc_html_e( 'Free', 'region-manager' ); ?></th>
							<th style="text-align: center;"><?php esc_html_e( 'Pro', 'region-manager' ); ?></th>
						</tr>
					</thead>
					<tbody>
						<tr>
							<td><?php esc_html_e( 'Maximum Regions', 'region-manager' ); ?></td>
							<td style="text-align: center;">2</td>
							<td style="text-align: center;"><strong><?php esc_html_e( 'Unlimited', 'region-manager' ); ?></strong></td>
						</tr>
						<tr>
							<td><?php esc_html_e( 'Region-based Pricing', 'region-manager' ); ?></td>
							<td style="text-align: center;"><span class="dashicons dashicons-yes" style="color: #46b450;"></span></td>
							<td style="text-align: center;"><span class="dashicons dashicons-yes" style="color: #46b450;"></span></td>
						</tr>
						<tr>
							<td><?php esc_html_e( 'Country Management', 'region-manager' ); ?></td>
							<td style="text-align: center;"><span class="dashicons dashicons-yes" style="color: #46b450;"></span></td>
							<td style="text-align: center;"><span class="dashicons dashicons-yes" style="color: #46b450;"></span></td>
						</tr>
						<tr>
							<td><?php esc_html_e( 'URL-based Region Detection', 'region-manager' ); ?></td>
							<td style="text-align: center;"><span class="dashicons dashicons-yes" style="color: #46b450;"></span></td>
							<td style="text-align: center;"><span class="dashicons dashicons-yes" style="color: #46b450;"></span></td>
						</tr>
						<tr>
							<td><?php esc_html_e( 'Translation Plugin Integration', 'region-manager' ); ?></td>
							<td style="text-align: center;"><span class="dashicons dashicons-yes" style="color: #46b450;"></span></td>
							<td style="text-align: center;"><span class="dashicons dashicons-yes" style="color: #46b450;"></span></td>
						</tr>
						<tr>
							<td><?php esc_html_e( 'Priority Support', 'region-manager' ); ?></td>
							<td style="text-align: center;"><span class="dashicons dashicons-no-alt" style="color: #dc3232;"></span></td>
							<td style="text-align: center;"><span class="dashicons dashicons-yes" style="color: #46b450;"></span></td>
						</tr>
						<tr>
							<td><?php esc_html_e( 'Regular Updates', 'region-manager' ); ?></td>
							<td style="text-align: center;"><span class="dashicons dashicons-yes" style="color: #46b450;"></span></td>
							<td style="text-align: center;"><span class="dashicons dashicons-yes" style="color: #46b450;"></span></td>
						</tr>
					</tbody>
				</table>
			</div>

			<!-- Upgrade CTA -->
			<div class="rm-license-upgrade-box" style="margin-top: 30px;">
				<h3><?php esc_html_e( 'Upgrade to Pro Today', 'region-manager' ); ?></h3>
				<p><?php esc_html_e( 'Get unlimited regions and premium support to grow your international WooCommerce business.', 'region-manager' ); ?></p>
				<ul style="list-style: disc; margin-left: 20px; margin-bottom: 20px;">
					<li><?php esc_html_e( 'Create unlimited regions for global expansion', 'region-manager' ); ?></li>
					<li><?php esc_html_e( 'Priority email support with 24-hour response time', 'region-manager' ); ?></li>
					<li><?php esc_html_e( 'Access to advanced features and future updates', 'region-manager' ); ?></li>
					<li><?php esc_html_e( '30-day money-back guarantee', 'region-manager' ); ?></li>
				</ul>
				<p>
					<a href="https://example.com/region-manager-pro" class="button button-primary button-hero" target="_blank">
						<?php esc_html_e( 'Purchase Pro License', 'region-manager' ); ?>
					</a>
				</p>
				<p class="description">
					<?php esc_html_e( 'After purchase, you will receive a license key via email.', 'region-manager' ); ?>
				</p>
			</div>
		<?php endif; ?>
	</div>
</div>
