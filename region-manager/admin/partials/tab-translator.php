<?php
/**
 * Translator integration tab content.
 *
 * @package    RegionManager
 * @subpackage RegionManager/admin/partials
 * @since      1.0.0
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

$translator_plugins = $this->get_translator_plugins();
?>

<div class="rm-translator-tab">
	<h2><?php esc_html_e( 'Translator Integration', 'region-manager' ); ?></h2>
	<p class="description">
		<?php esc_html_e( 'Region Manager is designed to work seamlessly with popular translation plugins to provide localized content for each region.', 'region-manager' ); ?>
	</p>

	<!-- Detected Plugins -->
	<div class="rm-settings-section">
		<h3><?php esc_html_e( 'Detected Translation Plugins', 'region-manager' ); ?></h3>

		<?php if ( empty( $translator_plugins ) ) : ?>
			<div class="notice notice-warning inline">
				<p>
					<span class="dashicons dashicons-info"></span>
					<?php esc_html_e( 'No translation plugins detected.', 'region-manager' ); ?>
				</p>
			</div>
			<p>
				<?php esc_html_e( 'Region Manager integrates with the following translation plugins:', 'region-manager' ); ?>
			</p>
			<ul class="rm-plugin-list">
				<li><strong>WPML</strong> - The most popular translation plugin for WordPress</li>
				<li><strong>Polylang</strong> - Create a bilingual or multilingual site</li>
				<li><strong>TranslatePress</strong> - Translate your entire site directly from the front-end</li>
			</ul>
		<?php else : ?>
			<div class="notice notice-success inline">
				<p>
					<span class="dashicons dashicons-yes-alt"></span>
					<?php
					printf(
						/* translators: %d: number of plugins */
						esc_html( _n( '%d translation plugin detected', '%d translation plugins detected', count( $translator_plugins ), 'region-manager' ) ),
						count( $translator_plugins )
					);
					?>
				</p>
			</div>
			<table class="rm-table widefat">
				<thead>
					<tr>
						<th><?php esc_html_e( 'Plugin', 'region-manager' ); ?></th>
						<th><?php esc_html_e( 'Version', 'region-manager' ); ?></th>
						<th><?php esc_html_e( 'Status', 'region-manager' ); ?></th>
					</tr>
				</thead>
				<tbody>
					<?php foreach ( $translator_plugins as $plugin ) : ?>
						<tr>
							<td><strong><?php echo esc_html( $plugin['name'] ); ?></strong></td>
							<td><?php echo esc_html( $plugin['version'] ); ?></td>
							<td>
								<span class="rm-status-badge active">
									<?php esc_html_e( 'Active', 'region-manager' ); ?>
								</span>
							</td>
						</tr>
					<?php endforeach; ?>
				</tbody>
			</table>
		<?php endif; ?>
	</div>

	<!-- How It Works -->
	<div class="rm-settings-section">
		<h3><?php esc_html_e( 'How Translation Integration Works', 'region-manager' ); ?></h3>
		<div class="rm-info-box">
			<p>
				<?php esc_html_e( 'Region Manager uses URL slugs to determine the customer\'s region. When you configure regions with specific language codes, the plugin automatically:', 'region-manager' ); ?>
			</p>
			<ol>
				<li><?php esc_html_e( 'Detects the customer\'s region from the URL (e.g., /pt, /es, /fr)', 'region-manager' ); ?></li>
				<li><?php esc_html_e( 'Sets the appropriate language based on the region\'s language code', 'region-manager' ); ?></li>
				<li><?php esc_html_e( 'Displays region-specific pricing and product availability', 'region-manager' ); ?></li>
				<li><?php esc_html_e( 'Applies translation plugin content in the correct language', 'region-manager' ); ?></li>
			</ol>
		</div>
	</div>

	<!-- URL Slug Mapping -->
	<div class="rm-settings-section">
		<h3><?php esc_html_e( 'URL Slug to Language Mapping', 'region-manager' ); ?></h3>
		<p class="description">
			<?php esc_html_e( 'Configure your regions with URL slugs and language codes in the Regions tab. The mapping is automatic based on your configuration.', 'region-manager' ); ?>
		</p>

		<?php
		$regions = $this->get_regions();
		if ( ! empty( $regions ) ) :
			?>
			<table class="rm-table widefat">
				<thead>
					<tr>
						<th><?php esc_html_e( 'Region', 'region-manager' ); ?></th>
						<th><?php esc_html_e( 'Countries & URL Slugs', 'region-manager' ); ?></th>
						<th><?php esc_html_e( 'Language Codes', 'region-manager' ); ?></th>
					</tr>
				</thead>
				<tbody>
					<?php foreach ( $regions as $region ) : ?>
						<?php $countries = $this->get_region_countries( $region->id ); ?>
						<tr>
							<td><strong><?php echo esc_html( $region->name ); ?></strong></td>
							<td>
								<?php
								if ( ! empty( $countries ) ) {
									$slugs = array();
									foreach ( $countries as $country ) {
										$slugs[] = '<code>' . esc_html( $country->url_slug ) . '</code>';
									}
									echo wp_kses_post( implode( ', ', $slugs ) );
								} else {
									echo '<span class="rm-text-muted">' . esc_html__( 'No countries configured', 'region-manager' ) . '</span>';
								}
								?>
							</td>
							<td>
								<?php
								if ( ! empty( $countries ) ) {
									$langs = array();
									foreach ( $countries as $country ) {
										$langs[] = '<code>' . esc_html( $country->language_code ) . '</code>';
									}
									echo wp_kses_post( implode( ', ', $langs ) );
								} else {
									echo '<span class="rm-text-muted">' . esc_html__( 'No languages configured', 'region-manager' ) . '</span>';
								}
								?>
							</td>
						</tr>
					<?php endforeach; ?>
				</tbody>
			</table>
		<?php else : ?>
			<div class="notice notice-info inline">
				<p>
					<?php
					printf(
						/* translators: %s: link to regions tab */
						__( 'No regions configured yet. <a href="%s">Create your first region</a> to set up URL and language mappings.', 'region-manager' ),
						esc_url( admin_url( 'admin.php?page=rm-settings&tab=regions' ) )
					);
					?>
				</p>
			</div>
		<?php endif; ?>
	</div>

	<!-- Developer Hooks -->
	<div class="rm-settings-section">
		<h3><?php esc_html_e( 'Developer Hooks', 'region-manager' ); ?></h3>
		<p class="description">
			<?php esc_html_e( 'Use these hooks to customize the translation integration behavior.', 'region-manager' ); ?>
		</p>

		<div class="rm-code-box">
			<h4><?php esc_html_e( 'Detect Current Region', 'region-manager' ); ?></h4>
			<pre><code>// Get the current region object
$current_region = apply_filters( 'rm_current_region', null );

// Get the current region ID
$region_id = apply_filters( 'rm_current_region_id', 0 );</code></pre>
		</div>

		<div class="rm-code-box">
			<h4><?php esc_html_e( 'Modify Language Code', 'region-manager' ); ?></h4>
			<pre><code>// Modify the language code before it's applied
add_filter( 'rm_region_language_code', function( $language_code, $region_id ) {
    // Your custom logic
    return $language_code;
}, 10, 2 );</code></pre>
		</div>

		<div class="rm-code-box">
			<h4><?php esc_html_e( 'Region Switch Event', 'region-manager' ); ?></h4>
			<pre><code>// Run custom code when region is switched
add_action( 'rm_region_switched', function( $old_region_id, $new_region_id ) {
    // Your custom logic
}, 10, 2 );</code></pre>
		</div>
	</div>
</div>
