<?php
/**
 * Template for the grid landing page layout.
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

<div class="rm-landing-page rm-landing-grid">
	<?php if ( $settings['title'] ) : ?>
		<h2 class="rm-landing-title"><?php echo esc_html( $settings['title'] ); ?></h2>
	<?php endif; ?>

	<?php if ( $settings['description'] ) : ?>
		<p class="rm-landing-description"><?php echo esc_html( $settings['description'] ); ?></p>
	<?php endif; ?>

	<div class="rm-countries-grid">
		<?php foreach ( $countries as $country ) : ?>
			<div class="rm-country-card"
			     data-country-code="<?php echo esc_attr( $country->code ); ?>"
			     data-url-slug="<?php echo esc_attr( $country->url_slug ); ?>"
			     data-language-code="<?php echo esc_attr( $country->language_code ); ?>"
			     data-region-id="<?php echo esc_attr( $country->region_id ); ?>">
				<a href="#" class="rm-country-card-link">
					<?php if ( $settings['show_flags'] && $country->flag_html ) : ?>
						<div class="rm-country-flag-large"><?php echo esc_html( $country->flag_html ); ?></div>
					<?php endif; ?>
					<h3 class="rm-country-name"><?php echo esc_html( $country->name ); ?></h3>
					<span class="rm-country-button"><?php esc_html_e( 'Select', 'region-manager' ); ?></span>
				</a>
			</div>
		<?php endforeach; ?>
	</div>
</div>
