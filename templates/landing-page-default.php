<?php
/**
 * Template for the default landing page layout (list view).
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

<div class="rm-landing-page rm-landing-default">
	<?php if ( $settings['title'] ) : ?>
		<h2 class="rm-landing-title"><?php echo esc_html( $settings['title'] ); ?></h2>
	<?php endif; ?>

	<?php if ( $settings['description'] ) : ?>
		<p class="rm-landing-description"><?php echo esc_html( $settings['description'] ); ?></p>
	<?php endif; ?>

	<div class="rm-countries-list">
		<?php foreach ( $countries as $country ) : ?>
			<?php
			$country_url = home_url( '/' . $country->url_slug . '/' );
			?>
			<div class="rm-country-item" data-country-code="<?php echo esc_attr( $country->code ); ?>" data-url-slug="<?php echo esc_attr( $country->url_slug ); ?>">
				<a href="<?php echo esc_url( $country_url ); ?>" class="rm-country-link">
					<?php if ( $settings['show_flags'] && $country->flag_html ) : ?>
						<span class="rm-country-flag"><?php echo esc_html( $country->flag_html ); ?></span>
					<?php endif; ?>
					<div class="rm-country-info">
						<h3 class="rm-country-name"><?php echo esc_html( $country->name ); ?></h3>
					</div>
					<span class="rm-country-arrow">→</span>
				</a>
			</div>
		<?php endforeach; ?>
	</div>
</div>
