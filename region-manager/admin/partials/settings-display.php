<?php
/**
 * Settings page display template.
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

<div class="wrap rm-admin-wrap">
	<h1><?php esc_html_e( 'Region Manager Settings', 'region-manager' ); ?></h1>

	<nav class="nav-tab-wrapper">
		<a href="<?php echo esc_url( admin_url( 'admin.php?page=rm-settings&tab=regions' ) ); ?>"
		   class="nav-tab <?php echo 'regions' === $current_tab ? 'nav-tab-active' : ''; ?>">
			<?php esc_html_e( 'Regions', 'region-manager' ); ?>
		</a>
		<a href="<?php echo esc_url( admin_url( 'admin.php?page=rm-settings&tab=checkout' ) ); ?>"
		   class="nav-tab <?php echo 'checkout' === $current_tab ? 'nav-tab-active' : ''; ?>">
			<?php esc_html_e( 'Checkout Settings', 'region-manager' ); ?>
		</a>
		<a href="<?php echo esc_url( admin_url( 'admin.php?page=rm-settings&tab=translator' ) ); ?>"
		   class="nav-tab <?php echo 'translator' === $current_tab ? 'nav-tab-active' : ''; ?>">
			<?php esc_html_e( 'Translator Integration', 'region-manager' ); ?>
		</a>
		<a href="<?php echo esc_url( admin_url( 'admin.php?page=rm-settings&tab=license' ) ); ?>"
		   class="nav-tab <?php echo 'license' === $current_tab ? 'nav-tab-active' : ''; ?>">
			<?php esc_html_e( 'License', 'region-manager' ); ?>
		</a>
	</nav>

	<div class="rm-settings-content">
		<?php
		switch ( $current_tab ) {
			case 'checkout':
				include RM_PLUGIN_DIR . 'admin/partials/tab-checkout.php';
				break;
			case 'translator':
				include RM_PLUGIN_DIR . 'admin/partials/tab-translator.php';
				break;
			case 'license':
				include RM_PLUGIN_DIR . 'admin/partials/tab-license.php';
				break;
			case 'regions':
			default:
				include RM_PLUGIN_DIR . 'admin/partials/tab-regions.php';
				break;
		}
		?>
	</div>
</div>

<!-- Region Modal -->
<div id="rm-region-modal" class="rm-modal" style="display:none;">
	<div class="rm-modal-dialog">
		<div class="rm-modal-content">
			<div class="rm-modal-header">
				<h2 id="rm-modal-title"><?php esc_html_e( 'Add New Region', 'region-manager' ); ?></h2>
				<button type="button" class="rm-modal-close" aria-label="<?php esc_attr_e( 'Close', 'region-manager' ); ?>">
					<span aria-hidden="true">&times;</span>
				</button>
			</div>
			<div class="rm-modal-body">
				<form id="rm-region-form">
					<input type="hidden" name="region_id" id="region_id" value="0">

					<div class="rm-form-group">
						<label for="region_name"><?php esc_html_e( 'Region Name', 'region-manager' ); ?> <span class="required">*</span></label>
						<input type="text" name="name" id="region_name" class="rm-input" required>
					</div>

					<div class="rm-form-group">
						<label for="region_slug"><?php esc_html_e( 'Region Slug', 'region-manager' ); ?> <span class="required">*</span></label>
						<input type="text" name="slug" id="region_slug" class="rm-input" required>
						<p class="description"><?php esc_html_e( 'The slug is auto-generated from the name but can be customized.', 'region-manager' ); ?></p>
					</div>

					<div class="rm-form-group">
						<label for="region_status"><?php esc_html_e( 'Status', 'region-manager' ); ?></label>
						<select name="status" id="region_status" class="rm-input">
							<option value="active"><?php esc_html_e( 'Active', 'region-manager' ); ?></option>
							<option value="inactive"><?php esc_html_e( 'Inactive', 'region-manager' ); ?></option>
						</select>
					</div>

					<div class="rm-form-group">
						<label><?php esc_html_e( 'Countries', 'region-manager' ); ?></label>
						<select id="country-select" class="rm-select2" style="width: 100%;">
							<option value=""><?php esc_html_e( 'Select countries...', 'region-manager' ); ?></option>
							<?php
							$countries = $this->get_available_countries();
							foreach ( $countries as $code => $name ) {
								echo '<option value="' . esc_attr( $code ) . '">' . esc_html( $name ) . '</option>';
							}
							?>
						</select>
					</div>

					<div class="rm-form-group">
						<table class="rm-countries-table" id="countries-table">
							<thead>
								<tr>
									<th><?php esc_html_e( 'Country', 'region-manager' ); ?></th>
									<th><?php esc_html_e( 'URL Slug', 'region-manager' ); ?></th>
									<th><?php esc_html_e( 'Language', 'region-manager' ); ?></th>
									<th><?php esc_html_e( 'Default', 'region-manager' ); ?></th>
									<th><?php esc_html_e( 'Remove', 'region-manager' ); ?></th>
								</tr>
							</thead>
							<tbody id="selected-countries">
								<!-- Countries will be added here dynamically -->
							</tbody>
						</table>
					</div>
				</form>
			</div>
			<div class="rm-modal-footer">
				<button type="button" class="button" id="rm-modal-cancel"><?php esc_html_e( 'Cancel', 'region-manager' ); ?></button>
				<button type="button" class="button button-primary" id="rm-save-region"><?php esc_html_e( 'Save Region', 'region-manager' ); ?></button>
			</div>
		</div>
	</div>
</div>

<script type="text/javascript">
	var rmLanguageCodes = <?php echo wp_json_encode( $this->get_language_codes() ); ?>;
	var rmCountries = <?php echo wp_json_encode( $this->get_available_countries() ); ?>;
	var rmLicenseInfo = <?php echo wp_json_encode( $license_info ); ?>;
</script>
