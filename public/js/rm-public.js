/**
 * Public-facing JavaScript for Region Manager
 *
 * @package Region_Manager
 */

(function($) {
	'use strict';

	$(document).ready(function() {

		/**
		 * Handle region selection on landing page
		 */
		$('.rm-landing-page').on('click', '.rm-country-item, .rm-country-card, .rm-map-country, .rm-map-list-item', function(e) {
			var $link = $(this).find('a');
			var countryCode = $(this).data('country-code');
			var urlSlug = $(this).data('url-slug');
			var languageCode = $(this).data('language-code') || '';

			// Fallback to old region-slug for compatibility
			if (!countryCode && !urlSlug) {
				var regionSlug = $(this).data('region-slug');
				if (regionSlug) {
					countryCode = regionSlug.toUpperCase();
					urlSlug = regionSlug;
				}
			}

			if (countryCode && urlSlug && rmPublic) {
				e.preventDefault();

				// DEBUG
				console.log('Country selection:', {
					countryCode: countryCode,
					urlSlug: urlSlug,
					languageCode: languageCode
				});

				// Set region via AJAX
				$.ajax({
					url: rmPublic.ajaxurl,
					type: 'POST',
					data: {
						action: 'rm_set_region',
						nonce: rmPublic.nonce,
						country_code: countryCode,
						url_slug: urlSlug,
						language_code: languageCode
					},
					success: function(response) {
						console.log('AJAX response:', response);
						if (response.success) {
							// Redirect to region URL
							console.log('Redirecting to:', response.data.redirect_url);
							window.location.href = response.data.redirect_url;
						} else {
							console.error('Region selection failed:', response.data.message);
							// Fallback to direct link
							window.location.href = $link.attr('href');
						}
					},
					error: function(xhr, status, error) {
						console.error('AJAX error:', error);
						// Fallback to direct link on error
						window.location.href = $link.attr('href');
					}
				});
			}
		});

		/**
		 * Handle country selection from menu dropdown
		 */
		$(document).on('click', '.rm-dropdown-country-item .rm-menu-country-link', function(e) {
			e.preventDefault();
			e.stopPropagation();

			var $item = $(this).closest('.rm-dropdown-country-item');
			var countryCode = $item.data('country-code');
			var urlSlug = $item.data('url-slug');
			var languageCode = $item.data('language-code') || '';
			var regionId = $item.data('region-id') || 0;

			if (!countryCode || !urlSlug || !rmPublic) {
				return;
			}

			// DEBUG
			console.log('Menu country selection:', {
				countryCode: countryCode,
				urlSlug: urlSlug,
				languageCode: languageCode,
				regionId: regionId
			});

			// Close dropdown
			$('.rm-menu-flag-item').removeClass('open');

			// Show loading state
			$(this).css('opacity', '0.5');

			// Switch country via AJAX
			$.ajax({
				url: rmPublic.ajaxurl,
				type: 'POST',
				data: {
					action: 'rm_switch_country',
					nonce: rmPublic.nonce,
					country_code: countryCode,
					url_slug: urlSlug,
					language_code: languageCode,
					region_id: regionId
				},
				success: function(response) {
					console.log('Switch response:', response);
					if (response.success && response.data.redirect_url) {
						console.log('Redirecting to:', response.data.redirect_url);
						window.location.href = response.data.redirect_url;
					} else {
						alert('Error switching country');
						$(this).css('opacity', '1');
					}
				},
				error: function(xhr, status, error) {
					console.error('Switch error:', error);
					alert('Error: ' + error);
					$(this).css('opacity', '1');
				}
			});
		});

		/**
		 * Auto-redirect functionality
		 */
		if ($('.rm-landing-page').length > 0) {
			var autoRedirect = $('.rm-landing-page').data('auto-redirect');
			var redirectDelay = parseInt($('.rm-landing-page').data('redirect-delay')) || 3;

			if (autoRedirect) {
				// Check if geolocation data is available
				detectRegionAndRedirect(redirectDelay);
			}
		}

		/**
		 * Detect region based on IP and auto-redirect
		 */
		function detectRegionAndRedirect(delay) {
			// This is a placeholder for geolocation detection
			// In a real implementation, this would call a geolocation API
			// or integrate with a WordPress geolocation plugin

			// Example implementation:
			/*
			$.ajax({
				url: rmPublic.ajaxurl,
				type: 'POST',
				data: {
					action: 'rm_detect_region',
					nonce: rmPublic.nonce
				},
				success: function(response) {
					if (response.success && response.data.region_slug) {
						setTimeout(function() {
							window.location.href = rmPublic.home_url + response.data.region_slug + '/';
						}, delay * 1000);
					}
				}
			});
			*/
		}

		/**
		 * Menu flag dropdown toggle for mobile
		 */
		if ($(window).width() < 768) {
			$('.rm-menu-flag-link').on('click', function(e) {
				e.preventDefault();
				$(this).siblings('.rm-region-dropdown').slideToggle(200);
			});
		}

		/**
		 * Highlight current region in map view
		 */
		if ($('.rm-landing-map').length > 0) {
			var currentRegion = getCookie('rm_selected_region');
			if (currentRegion) {
				$('.rm-map-region[data-region-slug="' + currentRegion + '"]').addClass('current-region');
				$('.rm-map-list-item[data-region-slug="' + currentRegion + '"]').addClass('current-region');
			}
		}

		/**
		 * Interactive map hover effect
		 */
		$('.rm-map-list-item').on('mouseenter', function() {
			var regionSlug = $(this).data('region-slug');
			$('.rm-map-region[data-region-slug="' + regionSlug + '"]').addClass('highlighted');
		}).on('mouseleave', function() {
			$('.rm-map-region').removeClass('highlighted');
		});

		$('.rm-map-region').on('mouseenter', function() {
			var regionSlug = $(this).data('region-slug');
			$('.rm-map-list-item[data-region-slug="' + regionSlug + '"]').addClass('highlighted');
		}).on('mouseleave', function() {
			$('.rm-map-list-item').removeClass('highlighted');
		});

	});

	/**
	 * Utility function to get cookie value
	 */
	function getCookie(name) {
		var value = '; ' + document.cookie;
		var parts = value.split('; ' + name + '=');
		if (parts.length === 2) {
			return parts.pop().split(';').shift();
		}
		return null;
	}

})(jQuery);
