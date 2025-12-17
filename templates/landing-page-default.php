<?php
/**
 * Template for the default landing page layout (list view).
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

<div class="rm-landing-page rm-landing-default">
	<?php if ( $settings['title'] ) : ?>
		<h2 class="rm-landing-title"><?php echo esc_html( $settings['title'] ); ?></h2>
	<?php endif; ?>

	<?php if ( $settings['description'] ) : ?>
		<p class="rm-landing-description"><?php echo esc_html( $settings['description'] ); ?></p>
	<?php endif; ?>

	<div class="rm-regions-list">
		<?php foreach ( $regions as $region ) : ?>
			<?php
			$countries  = $landing_page->get_region_countries( $region['id'] );
			$flag_emoji = ! empty( $countries ) ? $landing_page->get_flag_emoji( $countries[0] ) : '';
			$region_url = home_url( '/' . $region['slug'] . '/' );
			?>
			<div class="rm-region-item" data-region-slug="<?php echo esc_attr( $region['slug'] ); ?>">
				<a href="<?php echo esc_url( $region_url ); ?>" class="rm-region-link">
					<?php if ( $settings['show_flags'] && $flag_emoji ) : ?>
						<span class="rm-region-flag"><?php echo esc_html( $flag_emoji ); ?></span>
					<?php endif; ?>
					<div class="rm-region-info">
						<h3 class="rm-region-name"><?php echo esc_html( $region['name'] ); ?></h3>
						<?php if ( $settings['show_description'] && ! empty( $region['description'] ) ) : ?>
							<p class="rm-region-desc"><?php echo esc_html( $region['description'] ); ?></p>
						<?php endif; ?>
					</div>
					<span class="rm-region-arrow">→</span>
				</a>
			</div>
		<?php endforeach; ?>
	</div>
</div>
