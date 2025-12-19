<?php
/**
 * Provide a admin area view for the Customization page.
 *
 * @package    Region_Manager
 * @subpackage Region_Manager/admin/partials
 */

// Get settings.
$customization        = new RM_Customization( 'region-manager', '1.0.0' );
$landing_page_options = $customization->get_landing_page_settings();
$menu_flag_options    = $customization->get_menu_flag_settings();
$translator_options   = $customization->get_translator_settings();
$menu_locations       = $customization->get_menu_locations();

// Include plugin.php for is_plugin_active() function.
if ( ! function_exists( 'is_plugin_active' ) ) {
	require_once ABSPATH . 'wp-admin/includes/plugin.php';
}
?>

<div class="wrap rm-customization-page">
	<h1><?php echo esc_html( get_admin_page_title() ); ?></h1>

	<div class="rm-tabs">
		<nav class="nav-tab-wrapper">
			<a href="#landing-page" class="nav-tab nav-tab-active"><?php esc_html_e( 'Landing Page', 'region-manager' ); ?></a>
			<a href="#menu-flag" class="nav-tab"><?php esc_html_e( 'Menu Flag', 'region-manager' ); ?></a>
			<a href="#translator" class="nav-tab"><?php esc_html_e( 'Translator Integration', 'region-manager' ); ?></a>
		</nav>

		<!-- Landing Page Tab -->
		<div id="landing-page" class="rm-tab-content active">
			<h2><?php esc_html_e( 'Landing Page Settings', 'region-manager' ); ?></h2>
			<p class="description">
				<?php esc_html_e( 'Configure the region selector landing page. Use shortcode [region_landing_page] to display it on any page.', 'region-manager' ); ?>
			</p>

			<form id="rm-landing-page-form">
				<table class="form-table">
					<tbody>
						<tr>
							<th scope="row">
								<label for="landing-page-enabled"><?php esc_html_e( 'Enable Landing Page', 'region-manager' ); ?></label>
							</th>
							<td>
								<label>
									<input type="checkbox" id="landing-page-enabled" name="enabled" value="1" <?php checked( $landing_page_options['enabled'], true ); ?> />
									<?php esc_html_e( 'Enable the landing page functionality', 'region-manager' ); ?>
								</label>
							</td>
						</tr>
						<tr>
							<th scope="row">
								<label for="landing-page-template"><?php esc_html_e( 'Template', 'region-manager' ); ?></label>
							</th>
							<td>
								<select id="landing-page-template" name="template">
									<option value="default" <?php selected( $landing_page_options['template'], 'default' ); ?>><?php esc_html_e( 'Default (List)', 'region-manager' ); ?></option>
									<option value="grid" <?php selected( $landing_page_options['template'], 'grid' ); ?>><?php esc_html_e( 'Grid', 'region-manager' ); ?></option>
									<option value="map" <?php selected( $landing_page_options['template'], 'map' ); ?>><?php esc_html_e( 'Map (Interactive)', 'region-manager' ); ?></option>
								</select>
								<p class="description"><?php esc_html_e( 'Choose the layout template for the landing page.', 'region-manager' ); ?></p>
							</td>
						</tr>
						<tr>
							<th scope="row">
								<label for="landing-page-title"><?php esc_html_e( 'Page Title', 'region-manager' ); ?></label>
							</th>
							<td>
								<input type="text" id="landing-page-title" name="title" value="<?php echo esc_attr( $landing_page_options['title'] ); ?>" class="regular-text" />
							</td>
						</tr>
						<tr>
							<th scope="row">
								<label for="landing-page-description"><?php esc_html_e( 'Description', 'region-manager' ); ?></label>
							</th>
							<td>
								<textarea id="landing-page-description" name="description" rows="3" class="large-text"><?php echo esc_textarea( $landing_page_options['description'] ); ?></textarea>
							</td>
						</tr>
						<tr>
							<th scope="row">
								<label for="landing-page-auto-redirect"><?php esc_html_e( 'Auto-Redirect', 'region-manager' ); ?></label>
							</th>
							<td>
								<label>
									<input type="checkbox" id="landing-page-auto-redirect" name="auto_redirect" value="1" <?php checked( $landing_page_options['auto_redirect'], true ); ?> />
									<?php esc_html_e( 'Automatically redirect based on visitor IP geolocation', 'region-manager' ); ?>
								</label>
								<p class="description"><?php esc_html_e( 'Requires a geolocation service or plugin.', 'region-manager' ); ?></p>
							</td>
						</tr>
						<tr>
							<th scope="row">
								<label for="landing-page-redirect-delay"><?php esc_html_e( 'Redirect Delay (seconds)', 'region-manager' ); ?></label>
							</th>
							<td>
								<input type="number" id="landing-page-redirect-delay" name="redirect_delay" value="<?php echo esc_attr( $landing_page_options['redirect_delay'] ); ?>" min="0" max="10" step="1" class="small-text" />
								<p class="description"><?php esc_html_e( 'Delay before auto-redirecting (0-10 seconds).', 'region-manager' ); ?></p>
							</td>
						</tr>
						<tr>
							<th scope="row">
								<?php esc_html_e( 'Display Options', 'region-manager' ); ?>
							</th>
							<td>
								<label>
									<input type="checkbox" name="show_flags" value="1" <?php checked( $landing_page_options['show_flags'], true ); ?> />
									<?php esc_html_e( 'Show country flags', 'region-manager' ); ?>
								</label>
								<br />
								<label>
									<input type="checkbox" name="show_description" value="1" <?php checked( $landing_page_options['show_description'], true ); ?> />
									<?php esc_html_e( 'Show region descriptions', 'region-manager' ); ?>
								</label>
							</td>
						</tr>
					</tbody>
				</table>

				<p class="submit">
					<button type="submit" class="button button-primary"><?php esc_html_e( 'Save Landing Page Settings', 'region-manager' ); ?></button>
				</p>
			</form>

			<div class="rm-shortcode-info">
				<h3><?php esc_html_e( 'Shortcode Usage', 'region-manager' ); ?></h3>
				<code>[region_landing_page]</code>
				<p class="description"><?php esc_html_e( 'Place this shortcode on any page to display the region selector.', 'region-manager' ); ?></p>
			</div>
		</div>

		<!-- Menu Flag Tab -->
		<div id="menu-flag" class="rm-tab-content">
			<h2><?php esc_html_e( 'Menu Flag Settings', 'region-manager' ); ?></h2>
			<p class="description">
				<?php esc_html_e( 'Add a flag icon with region switcher to your site navigation menu.', 'region-manager' ); ?>
			</p>

			<form id="rm-menu-flag-form">
				<table class="form-table">
					<tbody>
						<tr>
							<th scope="row">
								<label for="menu-flag-enabled"><?php esc_html_e( 'Enable Menu Flag', 'region-manager' ); ?></label>
							</th>
							<td>
								<label>
									<input type="checkbox" id="menu-flag-enabled" name="enabled" value="1" <?php checked( $menu_flag_options['enabled'], true ); ?> />
									<?php esc_html_e( 'Display flag icon in navigation menu', 'region-manager' ); ?>
								</label>
							</td>
						</tr>
						<tr>
							<th scope="row">
								<label for="menu-flag-menu-location"><?php esc_html_e( 'Menu Location', 'region-manager' ); ?></label>
							</th>
							<td>
								<select id="menu-flag-menu-location" name="menu_location">
									<?php if ( ! empty( $menu_locations ) ) : ?>
										<?php foreach ( $menu_locations as $location => $description ) : ?>
											<option value="<?php echo esc_attr( $location ); ?>" <?php selected( $menu_flag_options['menu_location'], $location ); ?>>
												<?php echo esc_html( $description ); ?>
											</option>
										<?php endforeach; ?>
									<?php else : ?>
										<option value="primary"><?php esc_html_e( 'Primary Menu', 'region-manager' ); ?></option>
									<?php endif; ?>
								</select>
								<p class="description"><?php esc_html_e( 'Select which menu to add the flag to.', 'region-manager' ); ?></p>
							</td>
						</tr>
						<tr>
							<th scope="row">
								<label for="menu-flag-position"><?php esc_html_e( 'Position', 'region-manager' ); ?></label>
							</th>
							<td>
								<select id="menu-flag-position" name="position">
									<option value="left" <?php selected( $menu_flag_options['position'], 'left' ); ?>><?php esc_html_e( 'Left', 'region-manager' ); ?></option>
									<option value="right" <?php selected( $menu_flag_options['position'], 'right' ); ?>><?php esc_html_e( 'Right', 'region-manager' ); ?></option>
								</select>
								<p class="description"><?php esc_html_e( 'Position the flag on the left or right side of the menu.', 'region-manager' ); ?></p>
							</td>
						</tr>
						<tr>
							<th scope="row">
								<?php esc_html_e( 'Display Options', 'region-manager' ); ?>
							</th>
							<td>
								<label>
									<input type="checkbox" name="show_text" value="1" <?php checked( $menu_flag_options['show_text'], true ); ?> />
									<?php esc_html_e( 'Show region name text', 'region-manager' ); ?>
								</label>
								<br />
								<label>
									<input type="checkbox" name="show_dropdown" value="1" <?php checked( $menu_flag_options['show_dropdown'], true ); ?> />
									<?php esc_html_e( 'Show dropdown on hover', 'region-manager' ); ?>
								</label>
							</td>
						</tr>
					</tbody>
				</table>

				<p class="submit">
					<button type="submit" class="button button-primary"><?php esc_html_e( 'Save Menu Flag Settings', 'region-manager' ); ?></button>
				</p>
			</form>
		</div>

		<!-- Translator Integration Tab -->
		<div id="translator" class="rm-tab-content">
			<h2><?php esc_html_e( 'Translator Integration', 'region-manager' ); ?></h2>
			<p class="description">
				<?php esc_html_e( 'Integrate Region Manager with translation plugins to sync languages with regions.', 'region-manager' ); ?>
			</p>

			<form id="rm-translator-form">
				<table class="form-table">
					<tbody>
						<tr>
							<th scope="row">
								<label for="translator-enabled"><?php esc_html_e( 'Enable Integration', 'region-manager' ); ?></label>
							</th>
							<td>
								<label>
									<input type="checkbox" id="translator-enabled" name="enabled" value="1" <?php checked( $translator_options['enabled'], true ); ?> />
									<?php esc_html_e( 'Enable translator plugin integration', 'region-manager' ); ?>
								</label>
							</td>
						</tr>
						<tr>
							<th scope="row">
								<label for="translator-plugin"><?php esc_html_e( 'Translation Plugin', 'region-manager' ); ?></label>
							</th>
							<td>
								<select id="translator-plugin" name="plugin">
									<option value="wpml" <?php selected( $translator_options['plugin'], 'wpml' ); ?>>WPML</option>
									<option value="polylang" <?php selected( $translator_options['plugin'], 'polylang' ); ?>>Polylang</option>
									<option value="translatepress" <?php selected( $translator_options['plugin'], 'translatepress' ); ?>>TranslatePress</option>
									<option value="weglot" <?php selected( $translator_options['plugin'], 'weglot' ); ?>>Weglot</option>
								</select>
								<p class="description"><?php esc_html_e( 'Select your active translation plugin.', 'region-manager' ); ?></p>
							</td>
						</tr>
						<tr>
							<th scope="row">
								<label for="translator-sync-languages"><?php esc_html_e( 'Sync Languages', 'region-manager' ); ?></label>
							</th>
							<td>
								<label>
									<input type="checkbox" id="translator-sync-languages" name="sync_languages" value="1" <?php checked( $translator_options['sync_languages'], true ); ?> />
									<?php esc_html_e( 'Automatically sync region selection with language switcher', 'region-manager' ); ?>
								</label>
								<p class="description"><?php esc_html_e( 'When a user selects a region, automatically switch to the associated language.', 'region-manager' ); ?></p>
							</td>
						</tr>
						<tr>
							<th scope="row">
								<label for="translator-override-langs"><?php esc_html_e( 'Override Language Selector', 'region-manager' ); ?></label>
							</th>
							<td>
								<label>
									<input type="checkbox" id="translator-override-langs" name="override_langs" value="1" <?php checked( $translator_options['override_langs'], true ); ?> />
									<?php esc_html_e( 'Replace language selector with region selector', 'region-manager' ); ?>
								</label>
								<p class="description"><?php esc_html_e( 'Hide the translation plugin\'s language selector and use Region Manager instead.', 'region-manager' ); ?></p>
							</td>
						</tr>
					</tbody>
				</table>

				<p class="submit">
					<button type="submit" class="button button-primary"><?php esc_html_e( 'Save Translator Settings', 'region-manager' ); ?></button>
				</p>
			</form>

			<div class="rm-translator-status">
				<h3><?php esc_html_e( 'Detected Plugins', 'region-manager' ); ?></h3>
				<ul>
					<li>
						<strong>WPML:</strong>
						<?php echo is_plugin_active( 'sitepress-multilingual-cms/sitepress.php' ) ? '<span class="rm-status-active">' . esc_html__( 'Active', 'region-manager' ) . '</span>' : '<span class="rm-status-inactive">' . esc_html__( 'Not Active', 'region-manager' ) . '</span>'; ?>
					</li>
					<li>
						<strong>Polylang:</strong>
						<?php echo is_plugin_active( 'polylang/polylang.php' ) ? '<span class="rm-status-active">' . esc_html__( 'Active', 'region-manager' ) . '</span>' : '<span class="rm-status-inactive">' . esc_html__( 'Not Active', 'region-manager' ) . '</span>'; ?>
					</li>
					<li>
						<strong>TranslatePress:</strong>
						<?php echo is_plugin_active( 'translatepress-multilingual/index.php' ) ? '<span class="rm-status-active">' . esc_html__( 'Active', 'region-manager' ) . '</span>' : '<span class="rm-status-inactive">' . esc_html__( 'Not Active', 'region-manager' ) . '</span>'; ?>
					</li>
					<li>
						<strong>Weglot:</strong>
						<?php echo is_plugin_active( 'weglot/weglot.php' ) ? '<span class="rm-status-active">' . esc_html__( 'Active', 'region-manager' ) . '</span>' : '<span class="rm-status-inactive">' . esc_html__( 'Not Active', 'region-manager' ) . '</span>'; ?>
					</li>
				</ul>
			</div>
		</div>
	</div>
</div>

<script type="text/javascript">
(function($) {
	'use strict';

	$(document).ready(function() {
		// Tab switching
		$('.nav-tab').on('click', function(e) {
			e.preventDefault();
			var target = $(this).attr('href');

			$('.nav-tab').removeClass('nav-tab-active');
			$(this).addClass('nav-tab-active');

			$('.rm-tab-content').removeClass('active');
			$(target).addClass('active');
		});

		// Landing Page Form
		$('#rm-landing-page-form').on('submit', function(e) {
			e.preventDefault();

			var formData = {
				action: 'rm_save_landing_page_settings',
				nonce: rmAdmin.nonce,
				enabled: $('#landing-page-enabled').is(':checked'),
				template: $('#landing-page-template').val(),
				title: $('#landing-page-title').val(),
				description: $('#landing-page-description').val(),
				auto_redirect: $('#landing-page-auto-redirect').is(':checked'),
				redirect_delay: $('#landing-page-redirect-delay').val(),
				show_flags: $('input[name="show_flags"]').is(':checked'),
				show_description: $('input[name="show_description"]').is(':checked')
			};

			$.post(ajaxurl, formData, function(response) {
				if (response.success) {
					alert(response.data.message);
				} else {
					alert(response.data.message);
				}
			});
		});

		// Menu Flag Form
		$('#rm-menu-flag-form').on('submit', function(e) {
			e.preventDefault();

			var formData = {
				action: 'rm_save_menu_flag_settings',
				nonce: rmAdmin.nonce,
				enabled: $('#menu-flag-enabled').is(':checked'),
				position: $('#menu-flag-position').val(),
				menu_location: $('#menu-flag-menu-location').val(),
				show_text: $('input[name="show_text"]').is(':checked'),
				show_dropdown: $('input[name="show_dropdown"]').is(':checked')
			};

			$.post(ajaxurl, formData, function(response) {
				if (response.success) {
					alert(response.data.message);
				} else {
					alert(response.data.message);
				}
			});
		});

		// Translator Form
		$('#rm-translator-form').on('submit', function(e) {
			e.preventDefault();

			var formData = {
				action: 'rm_save_translator_settings',
				nonce: rmAdmin.nonce,
				enabled: $('#translator-enabled').is(':checked'),
				plugin: $('#translator-plugin').val(),
				sync_languages: $('#translator-sync-languages').is(':checked'),
				override_langs: $('#translator-override-langs').is(':checked')
			};

			$.post(ajaxurl, formData, function(response) {
				if (response.success) {
					alert(response.data.message);
				} else {
					alert(response.data.message);
				}
			});
		});
	});
})(jQuery);
</script>

<style>
.rm-customization-page .rm-tabs {
	margin-top: 20px;
}

.rm-tab-content {
	display: none;
	padding: 20px;
	background: #fff;
	border: 1px solid #ccd0d4;
	border-top: none;
}

.rm-tab-content.active {
	display: block;
}

.rm-shortcode-info {
	margin-top: 30px;
	padding: 15px;
	background: #f0f0f1;
	border-left: 4px solid #2271b1;
}

.rm-shortcode-info code {
	font-size: 14px;
	padding: 2px 8px;
	background: #fff;
}

.rm-translator-status {
	margin-top: 30px;
	padding: 15px;
	background: #f0f0f1;
	border-left: 4px solid #2271b1;
}

.rm-translator-status ul {
	margin: 10px 0;
}

.rm-translator-status li {
	margin-bottom: 8px;
}

.rm-status-active {
	color: #00a32a;
	font-weight: bold;
}

.rm-status-inactive {
	color: #999;
}
</style>
