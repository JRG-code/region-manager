<?php
/**
 * Template for the map landing page layout (interactive).
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

<div class="rm-landing-page rm-landing-map">
	<?php if ( $settings['title'] ) : ?>
		<h2 class="rm-landing-title"><?php echo esc_html( $settings['title'] ); ?></h2>
	<?php endif; ?>

	<?php if ( $settings['description'] ) : ?>
		<p class="rm-landing-description"><?php echo esc_html( $settings['description'] ); ?></p>
	<?php endif; ?>

	<div class="rm-map-container">
		<div class="rm-map-visual">
			<?php foreach ( $regions as $region ) : ?>
				<?php
				$countries  = $landing_page->get_region_countries( $region['id'] );
				$flag_emoji = ! empty( $countries ) ? $landing_page->get_flag_emoji( $countries[0] ) : '';
				$region_url = home_url( '/' . $region['slug'] . '/' );
				?>
				<div class="rm-map-region" data-region-slug="<?php echo esc_attr( $region['slug'] ); ?>">
					<a href="<?php echo esc_url( $region_url ); ?>" class="rm-map-region-link">
						<?php if ( $settings['show_flags'] && $flag_emoji ) : ?>
							<span class="rm-map-flag"><?php echo esc_html( $flag_emoji ); ?></span>
						<?php endif; ?>
						<span class="rm-map-name"><?php echo esc_html( $region['name'] ); ?></span>
					</a>
				</div>
			<?php endforeach; ?>
		</div>

		<div class="rm-map-sidebar">
			<h3><?php esc_html_e( 'Available Regions', 'region-manager' ); ?></h3>
			<ul class="rm-map-list">
				<?php foreach ( $regions as $region ) : ?>
					<?php
					$countries  = $landing_page->get_region_countries( $region['id'] );
					$flag_emoji = ! empty( $countries ) ? $landing_page->get_flag_emoji( $countries[0] ) : '';
					$region_url = home_url( '/' . $region['slug'] . '/' );
					?>
					<li class="rm-map-list-item" data-region-slug="<?php echo esc_attr( $region['slug'] ); ?>">
						<a href="<?php echo esc_url( $region_url ); ?>">
							<?php if ( $settings['show_flags'] && $flag_emoji ) : ?>
								<span class="rm-flag"><?php echo esc_html( $flag_emoji ); ?></span>
							<?php endif; ?>
							<span class="rm-name"><?php echo esc_html( $region['name'] ); ?></span>
						</a>
					</li>
				<?php endforeach; ?>
			</ul>
		</div>
	</div>
</div>
