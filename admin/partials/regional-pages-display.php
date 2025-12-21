<?php
/**
 * Provide a admin area view for Regional Pages management.
 *
 * @package    Region_Manager
 * @subpackage Region_Manager/admin/partials
 */

defined( 'ABSPATH' ) || exit;

// Get data.
$regional_pages_manager = new RM_Regional_Pages();
$regions                = $regional_pages_manager->get_all_regions();
$current_region         = isset( $_GET['region'] ) ? absint( $_GET['region'] ) : ( ! empty( $regions ) ? $regions[0]->id : 0 );
$current_region_data    = $current_region ? $regional_pages_manager->get_region( $current_region ) : null;
$regional_pages         = $current_region ? $regional_pages_manager->get_regional_pages( $current_region ) : array();
$regional_content       = $current_region ? $regional_pages_manager->get_regional_content( $current_region ) : array();
$all_pages              = $regional_pages_manager->get_all_wp_pages();
?>

<div class="wrap rm-regional-pages">
	<h1><?php esc_html_e( 'Regional Pages', 'region-manager' ); ?></h1>

	<p class="description">
		<?php esc_html_e( 'Customize pages and content for each region. Users will see these pages after selecting their country on the landing page.', 'region-manager' ); ?>
	</p>

	<!-- Region Tabs -->
	<?php if ( ! empty( $regions ) ) : ?>
	<div class="rm-region-tabs">
		<?php foreach ( $regions as $region ) : ?>
		<a href="<?php echo esc_url( add_query_arg( 'region', $region->id ) ); ?>"
		   class="rm-region-tab <?php echo $current_region === $region->id ? 'active' : ''; ?>">
			<?php echo esc_html( $region->name ); ?>
		</a>
		<?php endforeach; ?>
	</div>
	<?php endif; ?>

	<?php if ( empty( $regions ) ) : ?>
	<div class="notice notice-warning">
		<p>
			<?php esc_html_e( 'No regions configured.', 'region-manager' ); ?>
			<a href="<?php echo esc_url( admin_url( 'admin.php?page=region-manager-settings' ) ); ?>">
				<?php esc_html_e( 'Create regions first.', 'region-manager' ); ?>
			</a>
		</p>
	</div>
	<?php else : ?>

	<form id="rm-regional-pages-form" class="rm-form">
		<input type="hidden" name="region_id" value="<?php echo esc_attr( $current_region ); ?>">
		<?php wp_nonce_field( 'rm_admin_nonce', 'nonce' ); ?>

		<!-- Section: Store Pages -->
		<div class="rm-section">
			<h2><?php esc_html_e( 'Store Pages', 'region-manager' ); ?></h2>
			<p class="description">
				<?php esc_html_e( 'Assign or create custom pages for this region. Leave empty to use default WooCommerce pages.', 'region-manager' ); ?>
			</p>

			<table class="form-table rm-pages-table">
				<!-- Shop Page -->
				<tr>
					<th scope="row">
						<label><?php esc_html_e( 'Shop Page', 'region-manager' ); ?></label>
						<p class="description"><?php esc_html_e( 'Main store/catalog page', 'region-manager' ); ?></p>
					</th>
					<td>
						<div class="rm-page-selector">
							<select name="pages[shop]" class="rm-page-dropdown">
								<option value=""><?php esc_html_e( '— Use Default —', 'region-manager' ); ?></option>
								<?php foreach ( $all_pages as $page ) : ?>
								<option value="<?php echo esc_attr( $page->ID ); ?>"
										<?php selected( isset( $regional_pages['shop'] ) ? $regional_pages['shop']->page_id : '', $page->ID ); ?>>
									<?php echo esc_html( $page->post_title ); ?>
								</option>
								<?php endforeach; ?>
							</select>

							<button type="button" class="button rm-create-page-btn"
									data-page-type="shop"
									data-default-title="<?php echo esc_attr( $current_region_data->name ?? '' ); ?> - <?php esc_attr_e( 'Shop', 'region-manager' ); ?>">
								<span class="dashicons dashicons-plus"></span>
								<?php esc_html_e( 'Create New', 'region-manager' ); ?>
							</button>

							<?php if ( ! empty( $regional_pages['shop']->page_id ) ) : ?>
							<a href="<?php echo esc_url( get_edit_post_link( $regional_pages['shop']->page_id ) ); ?>"
							   class="button" target="_blank">
								<span class="dashicons dashicons-edit"></span>
								<?php esc_html_e( 'Edit', 'region-manager' ); ?>
							</a>
							<a href="<?php echo esc_url( get_permalink( $regional_pages['shop']->page_id ) ); ?>"
							   class="button" target="_blank">
								<span class="dashicons dashicons-external"></span>
								<?php esc_html_e( 'View', 'region-manager' ); ?>
							</a>
							<?php endif; ?>
						</div>
					</td>
				</tr>

				<!-- First Page After Country Selection -->
				<tr>
					<th scope="row">
						<label><?php esc_html_e( 'First Page After Country Selection', 'region-manager' ); ?></label>
						<p class="description"><?php esc_html_e( 'Where users go after selecting their country', 'region-manager' ); ?></p>
					</th>
					<td>
						<div class="rm-page-selector">
							<select name="pages[welcome]" class="rm-page-dropdown" id="rm-welcome-page-select">
								<option value="shop" <?php selected( isset( $regional_pages['welcome'] ) ? $regional_pages['welcome']->page_id : '', 'shop' ); ?>>
									<?php esc_html_e( '— Shop Page (Default) —', 'region-manager' ); ?>
								</option>
								<option value="home" <?php selected( isset( $regional_pages['welcome'] ) ? $regional_pages['welcome']->page_id : '', 'home' ); ?>>
									<?php esc_html_e( '— Site Homepage —', 'region-manager' ); ?>
								</option>

								<optgroup label="<?php esc_attr_e( 'Select a specific page:', 'region-manager' ); ?>">
									<?php foreach ( $all_pages as $page ) : ?>
									<option value="<?php echo esc_attr( $page->ID ); ?>"
											data-slug="<?php echo esc_attr( $page->post_name ); ?>"
											<?php selected( isset( $regional_pages['welcome'] ) ? $regional_pages['welcome']->page_id : '', $page->ID ); ?>>
										<?php echo esc_html( $page->post_title ); ?>
									</option>
									<?php endforeach; ?>
								</optgroup>
							</select>

							<button type="button" class="button rm-create-page-btn"
									data-page-type="welcome"
									data-default-title="<?php echo esc_attr( $current_region_data->name ?? '' ); ?> - <?php esc_attr_e( 'Welcome', 'region-manager' ); ?>">
								<span class="dashicons dashicons-plus"></span>
								<?php esc_html_e( 'Create New', 'region-manager' ); ?>
							</button>

							<?php
							$welcome_page_id = isset( $regional_pages['welcome'] ) ? $regional_pages['welcome']->page_id : '';
							if ( ! empty( $welcome_page_id ) && is_numeric( $welcome_page_id ) && $welcome_page_id > 0 ) :
								?>
							<a href="<?php echo esc_url( get_edit_post_link( $welcome_page_id ) ); ?>"
							   class="button" target="_blank">
								<span class="dashicons dashicons-edit"></span>
								<?php esc_html_e( 'Edit', 'region-manager' ); ?>
							</a>
							<a href="<?php echo esc_url( get_permalink( $welcome_page_id ) ); ?>"
							   class="button" target="_blank">
								<span class="dashicons dashicons-external"></span>
								<?php esc_html_e( 'View', 'region-manager' ); ?>
							</a>
							<?php endif; ?>
						</div>

						<p class="description" style="margin-top: 10px;">
							<?php
							$sample_country = $regional_pages_manager->get_first_country_for_region( $current_region );
							$sample_slug    = trim( $sample_country->url_slug ?? 'pt', '/' );
							?>
							<strong><?php esc_html_e( 'Example URL:', 'region-manager' ); ?></strong>
							<code id="rm-preview-url"><?php echo esc_html( home_url( '/' . $sample_slug . '/' ) ); ?></code>
						</p>
					</td>
				</tr>

				<!-- Categories Page -->
				<tr>
					<th scope="row">
						<label><?php esc_html_e( 'Categories Page', 'region-manager' ); ?></label>
						<p class="description"><?php esc_html_e( 'Custom product categories page', 'region-manager' ); ?></p>
					</th>
					<td>
						<div class="rm-page-selector">
							<select name="pages[categories]" class="rm-page-dropdown">
								<option value=""><?php esc_html_e( '— Use Default —', 'region-manager' ); ?></option>
								<?php foreach ( $all_pages as $page ) : ?>
								<option value="<?php echo esc_attr( $page->ID ); ?>"
										<?php selected( isset( $regional_pages['categories'] ) ? $regional_pages['categories']->page_id : '', $page->ID ); ?>>
									<?php echo esc_html( $page->post_title ); ?>
								</option>
								<?php endforeach; ?>
							</select>

							<button type="button" class="button rm-create-page-btn"
									data-page-type="categories"
									data-default-title="<?php echo esc_attr( $current_region_data->name ?? '' ); ?> - <?php esc_attr_e( 'Categories', 'region-manager' ); ?>">
								<span class="dashicons dashicons-plus"></span>
								<?php esc_html_e( 'Create New', 'region-manager' ); ?>
							</button>

							<?php if ( ! empty( $regional_pages['categories']->page_id ) ) : ?>
							<a href="<?php echo esc_url( get_edit_post_link( $regional_pages['categories']->page_id ) ); ?>"
							   class="button" target="_blank">
								<span class="dashicons dashicons-edit"></span>
								<?php esc_html_e( 'Edit', 'region-manager' ); ?>
							</a>
							<a href="<?php echo esc_url( get_permalink( $regional_pages['categories']->page_id ) ); ?>"
							   class="button" target="_blank">
								<span class="dashicons dashicons-external"></span>
								<?php esc_html_e( 'View', 'region-manager' ); ?>
							</a>
							<?php endif; ?>
						</div>
					</td>
				</tr>
			</table>
		</div>

		<!-- Section: Regional Content -->
		<div class="rm-section">
			<h2><?php esc_html_e( 'Regional Content', 'region-manager' ); ?></h2>
			<p class="description">
				<?php esc_html_e( 'Customize content blocks that appear on your store for this region. Use shortcode [rm_regional_content key="KEY"] to display.', 'region-manager' ); ?>
			</p>

			<table class="form-table">
				<!-- Shop Banner -->
				<tr>
					<th scope="row">
						<label><?php esc_html_e( 'Shop Banner', 'region-manager' ); ?></label>
						<p class="description">
							<?php esc_html_e( 'Shortcode:', 'region-manager' ); ?>
							<code>[rm_regional_content key="shop_banner"]</code>
						</p>
					</th>
					<td>
						<?php
						wp_editor(
							$regional_content['shop_banner'] ?? '',
							'rm_content_shop_banner',
							array(
								'textarea_name' => 'content[shop_banner]',
								'textarea_rows' => 5,
								'media_buttons' => true,
							)
						);
						?>
					</td>
				</tr>

				<!-- Welcome Message -->
				<tr>
					<th scope="row">
						<label><?php esc_html_e( 'Welcome Message', 'region-manager' ); ?></label>
						<p class="description">
							<?php esc_html_e( 'Shortcode:', 'region-manager' ); ?>
							<code>[rm_regional_content key="welcome_message"]</code>
						</p>
					</th>
					<td>
						<?php
						wp_editor(
							$regional_content['welcome_message'] ?? '',
							'rm_content_welcome_message',
							array(
								'textarea_name' => 'content[welcome_message]',
								'textarea_rows' => 4,
								'media_buttons' => true,
							)
						);
						?>
					</td>
				</tr>

				<!-- Shipping Info -->
				<tr>
					<th scope="row">
						<label><?php esc_html_e( 'Shipping Information', 'region-manager' ); ?></label>
						<p class="description">
							<?php esc_html_e( 'Shortcode:', 'region-manager' ); ?>
							<code>[rm_regional_content key="shipping_info"]</code>
						</p>
					</th>
					<td>
						<?php
						wp_editor(
							$regional_content['shipping_info'] ?? '',
							'rm_content_shipping_info',
							array(
								'textarea_name' => 'content[shipping_info]',
								'textarea_rows' => 4,
								'media_buttons' => false,
							)
						);
						?>
					</td>
				</tr>

				<!-- Footer Content -->
				<tr>
					<th scope="row">
						<label><?php esc_html_e( 'Footer Content', 'region-manager' ); ?></label>
						<p class="description">
							<?php esc_html_e( 'Shortcode:', 'region-manager' ); ?>
							<code>[rm_regional_content key="footer"]</code>
						</p>
					</th>
					<td>
						<?php
						wp_editor(
							$regional_content['footer'] ?? '',
							'rm_content_footer',
							array(
								'textarea_name' => 'content[footer]',
								'textarea_rows' => 4,
								'media_buttons' => true,
							)
						);
						?>
					</td>
				</tr>
			</table>
		</div>

		<p class="submit">
			<button type="submit" class="button button-primary button-large">
				<?php esc_html_e( 'Save Changes', 'region-manager' ); ?>
			</button>
		</p>
	</form>

	<?php endif; ?>
</div>

<!-- Create Page Modal -->
<div id="rm-create-page-modal" class="rm-modal" style="display:none;">
	<div class="rm-modal-overlay"></div>
	<div class="rm-modal-content">
		<div class="rm-modal-header">
			<h2><?php esc_html_e( 'Create New Page', 'region-manager' ); ?></h2>
			<button type="button" class="rm-modal-close">
				<span class="dashicons dashicons-no"></span>
			</button>
		</div>

		<div class="rm-modal-body">
			<form id="rm-create-page-form">
				<input type="hidden" name="page_type" id="rm-new-page-type">
				<input type="hidden" name="region_id" value="<?php echo esc_attr( $current_region ); ?>">

				<p>
					<label for="rm-new-page-title">
						<strong><?php esc_html_e( 'Page Title', 'region-manager' ); ?></strong>
					</label>
					<input type="text" id="rm-new-page-title" name="page_title" class="widefat" required>
				</p>

				<p class="description">
					<?php esc_html_e( 'The page will be created and automatically assigned to this region. You can edit it later in WordPress Pages.', 'region-manager' ); ?>
				</p>
			</form>
		</div>

		<div class="rm-modal-footer">
			<button type="button" class="button rm-modal-cancel">
				<?php esc_html_e( 'Cancel', 'region-manager' ); ?>
			</button>
			<button type="submit" form="rm-create-page-form" class="button button-primary" id="rm-create-page-submit">
				<span class="dashicons dashicons-plus"></span>
				<?php esc_html_e( 'Create Page', 'region-manager' ); ?>
			</button>
		</div>
	</div>
</div>

<style>
.rm-regional-pages {
	max-width: 1200px;
}

.rm-region-tabs {
	display: flex;
	gap: 0;
	margin: 20px 0;
	border-bottom: 1px solid #ccc;
}

.rm-region-tab {
	padding: 10px 20px;
	background: #f0f0f0;
	text-decoration: none;
	color: #333;
	border: 1px solid #ccc;
	border-bottom: none;
	margin-bottom: -1px;
	transition: background-color 0.2s;
}

.rm-region-tab.active {
	background: #fff;
	border-bottom-color: #fff;
	font-weight: 600;
}

.rm-region-tab:hover {
	background: #e5e5e5;
}

.rm-section {
	background: #fff;
	padding: 20px;
	margin: 20px 0;
	border: 1px solid #ccd0d4;
	box-shadow: 0 1px 1px rgba(0,0,0,0.04);
}

.rm-section h2 {
	margin-top: 0;
	padding-bottom: 10px;
	border-bottom: 1px solid #eee;
}

.rm-page-selector {
	display: flex;
	gap: 10px;
	align-items: center;
	flex-wrap: wrap;
}

.rm-page-dropdown {
	min-width: 300px;
}

.rm-page-selector .button .dashicons {
	margin-right: 5px;
	vertical-align: middle;
}

/* Modal Styles */
.rm-modal {
	position: fixed;
	top: 0;
	left: 0;
	right: 0;
	bottom: 0;
	background: rgba(0,0,0,0.7);
	z-index: 100000;
	display: flex;
	align-items: center;
	justify-content: center;
}

.rm-modal-overlay {
	position: absolute;
	top: 0;
	left: 0;
	right: 0;
	bottom: 0;
}

.rm-modal-content {
	background: #fff;
	border-radius: 4px;
	width: 500px;
	max-width: 90%;
	position: relative;
	z-index: 1;
	box-shadow: 0 5px 15px rgba(0,0,0,0.3);
}

.rm-modal-header {
	padding: 20px;
	border-bottom: 1px solid #ddd;
	position: relative;
}

.rm-modal-header h2 {
	margin: 0;
}

.rm-modal-close {
	position: absolute;
	top: 15px;
	right: 15px;
	background: none;
	border: none;
	font-size: 20px;
	cursor: pointer;
	color: #666;
	padding: 5px;
	line-height: 1;
}

.rm-modal-close:hover {
	color: #000;
}

.rm-modal-body {
	padding: 20px;
}

.rm-modal-footer {
	padding: 15px 20px;
	border-top: 1px solid #ddd;
	text-align: right;
	display: flex;
	gap: 10px;
	justify-content: flex-end;
}

.rm-modal-footer .button {
	margin: 0;
}
</style>

<script>
jQuery(document).ready(function($) {
	'use strict';

	// Save form
	$('#rm-regional-pages-form').on('submit', function(e) {
		e.preventDefault();

		var $btn = $(this).find('button[type="submit"]');
		var originalText = $btn.html();
		$btn.prop('disabled', true).html('<span class="dashicons dashicons-update spin"></span> <?php esc_html_e( 'Saving...', 'region-manager' ); ?>');

		// Get all editor content
		if (typeof tinyMCE !== 'undefined') {
			tinyMCE.triggerSave();
		}

		$.ajax({
			url: ajaxurl,
			type: 'POST',
			data: {
				action: 'rm_save_regional_pages',
				nonce: $('input[name="nonce"]').val(),
				region_id: $('input[name="region_id"]').val(),
				pages: {
					shop: $('select[name="pages[shop]"]').val(),
					welcome: $('select[name="pages[welcome]"]').val(),
					categories: $('select[name="pages[categories]"]').val()
				},
				content: {
					shop_banner: $('textarea[name="content[shop_banner]"]').val(),
					welcome_message: $('textarea[name="content[welcome_message]"]').val(),
					shipping_info: $('textarea[name="content[shipping_info]"]').val(),
					footer: $('textarea[name="content[footer]"]').val()
				}
			},
			success: function(response) {
				if (response.success) {
					alert(response.data.message);
				} else {
					alert(response.data.message || '<?php esc_html_e( 'Error saving settings.', 'region-manager' ); ?>');
				}
			},
			error: function() {
				alert('<?php esc_html_e( 'An error occurred. Please try again.', 'region-manager' ); ?>');
			},
			complete: function() {
				$btn.prop('disabled', false).html(originalText);
			}
		});
	});

	// Create page button
	$('.rm-create-page-btn').on('click', function() {
		var pageType = $(this).data('page-type');
		var defaultTitle = $(this).data('default-title');

		$('#rm-new-page-type').val(pageType);
		$('#rm-new-page-title').val(defaultTitle);
		$('#rm-create-page-modal').show();
		$('#rm-new-page-title').focus();
	});

	// Close modal
	$('.rm-modal-close, .rm-modal-cancel').on('click', function() {
		$('#rm-create-page-modal').hide();
	});

	// Close modal on overlay click
	$('.rm-modal-overlay').on('click', function() {
		$('#rm-create-page-modal').hide();
	});

	// Create page form
	$('#rm-create-page-form').on('submit', function(e) {
		e.preventDefault();

		var $btn = $('#rm-create-page-submit');
		var originalText = $btn.html();
		$btn.prop('disabled', true).html('<span class="dashicons dashicons-update spin"></span> <?php esc_html_e( 'Creating...', 'region-manager' ); ?>');

		$.ajax({
			url: ajaxurl,
			type: 'POST',
			data: {
				action: 'rm_create_regional_page',
				nonce: '<?php echo esc_js( wp_create_nonce( 'rm_admin_nonce' ) ); ?>',
				region_id: $('input[name="region_id"]').val(),
				page_type: $('#rm-new-page-type').val(),
				page_title: $('#rm-new-page-title').val()
			},
			success: function(response) {
				if (response.success) {
					alert(response.data.message + '\n\n<?php esc_html_e( 'Refreshing page...', 'region-manager' ); ?>');
					location.reload();
				} else {
					alert(response.data.message || '<?php esc_html_e( 'Error creating page.', 'region-manager' ); ?>');
					$btn.prop('disabled', false).html(originalText);
				}
			},
			error: function() {
				alert('<?php esc_html_e( 'An error occurred. Please try again.', 'region-manager' ); ?>');
				$btn.prop('disabled', false).html(originalText);
			}
		});
	});

	// Update preview URL when welcome page selection changes
	$('#rm-welcome-page-select').on('change', function() {
		var selected = $(this).val();
		var baseUrl = '<?php echo esc_js( home_url( '/' . $sample_slug ) ); ?>';
		var pagePath = '';

		if (selected === 'shop') {
			pagePath = '/shop';
		} else if (selected === 'home') {
			pagePath = '';
		} else if (selected && !isNaN(selected)) {
			// It's a page ID - get the slug from the data attribute
			var pageSlug = $(this).find('option:selected').data('slug');
			if (pageSlug) {
				pagePath = '/' + pageSlug;
			}
		}

		$('#rm-preview-url').text(baseUrl + pagePath + '/');
	});
});
</script>
