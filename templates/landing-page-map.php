<?php
/**
 * Template for the map landing page layout (interactive).
 *
 * @package    Region_Manager
 * @subpackage Region_Manager/templates
 *
 * Available variables:
 * @var array $settings  Landing page settings
 * @var array $countries Active countries from all regions
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
			<?php foreach ( $countries as $country ) : ?>
				<div class="rm-map-country"
				     data-country-code="<?php echo esc_attr( $country->code ); ?>"
				     data-url-slug="<?php echo esc_attr( $country->url_slug ); ?>"
				     data-language-code="<?php echo esc_attr( $country->language_code ); ?>"
				     data-region-id="<?php echo esc_attr( $country->region_id ); ?>">
					<a href="#" class="rm-map-country-link">
						<?php if ( $settings['show_flags'] && $country->flag_html ) : ?>
							<span class="rm-map-flag"><?php echo esc_html( $country->flag_html ); ?></span>
						<?php endif; ?>
						<span class="rm-map-name"><?php echo esc_html( $country->name ); ?></span>
					</a>
				</div>
			<?php endforeach; ?>
		</div>

		<div class="rm-map-sidebar">
			<h3><?php esc_html_e( 'Available Countries', 'region-manager' ); ?></h3>
			<ul class="rm-map-list">
				<?php foreach ( $countries as $country ) : ?>
					<li class="rm-map-list-item"
					    data-country-code="<?php echo esc_attr( $country->code ); ?>"
					    data-url-slug="<?php echo esc_attr( $country->url_slug ); ?>"
					    data-language-code="<?php echo esc_attr( $country->language_code ); ?>"
					    data-region-id="<?php echo esc_attr( $country->region_id ); ?>">
						<a href="#">
							<?php if ( $settings['show_flags'] && $country->flag_html ) : ?>
								<span class="rm-flag"><?php echo esc_html( $country->flag_html ); ?></span>
							<?php endif; ?>
							<span class="rm-name"><?php echo esc_html( $country->name ); ?></span>
						</a>
					</li>
				<?php endforeach; ?>
			</ul>
		</div>
	</div>
</div>
