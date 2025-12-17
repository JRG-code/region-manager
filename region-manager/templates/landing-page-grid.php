<?php
/**
 * Template for the grid landing page layout.
 *
 * @package    Region_Manager
 * @subpackage Region_Manager/templates
 *
 * Available variables:
 * @var array $settings Landing page settings
 * @var array $regions  Active regions
 */

$landing_page = RM_Landing_Page::get_instance();
?>

<div class="rm-landing-page rm-landing-grid">
	<?php if ( $settings['title'] ) : ?>
		<h2 class="rm-landing-title"><?php echo esc_html( $settings['title'] ); ?></h2>
	<?php endif; ?>

	<?php if ( $settings['description'] ) : ?>
		<p class="rm-landing-description"><?php echo esc_html( $settings['description'] ); ?></p>
	<?php endif; ?>

	<div class="rm-regions-grid">
		<?php foreach ( $regions as $region ) : ?>
			<?php
			$countries  = $landing_page->get_region_countries( $region['id'] );
			$flag_emoji = ! empty( $countries ) ? $landing_page->get_flag_emoji( $countries[0] ) : '';
			$region_url = home_url( '/' . $region['slug'] . '/' );
			?>
			<div class="rm-region-card" data-region-slug="<?php echo esc_attr( $region['slug'] ); ?>">
				<a href="<?php echo esc_url( $region_url ); ?>" class="rm-region-card-link">
					<?php if ( $settings['show_flags'] && $flag_emoji ) : ?>
						<div class="rm-region-flag-large"><?php echo esc_html( $flag_emoji ); ?></div>
					<?php endif; ?>
					<h3 class="rm-region-name"><?php echo esc_html( $region['name'] ); ?></h3>
					<?php if ( $settings['show_description'] && ! empty( $region['description'] ) ) : ?>
						<p class="rm-region-desc"><?php echo esc_html( $region['description'] ); ?></p>
					<?php endif; ?>
					<span class="rm-region-button"><?php esc_html_e( 'Select', 'region-manager' ); ?></span>
				</a>
			</div>
		<?php endforeach; ?>
	</div>
</div>
