/**
 * Region Manager Admin Scripts
 *
 * @package    RegionManager
 * @subpackage RegionManager/admin/js
 * @since      1.0.0
 */

(function( $ ) {
	'use strict';

	/**
	 * Region Manager Admin Class
	 *
	 * @since 1.0.0
	 */
	var RegionManagerAdmin = {

		/**
		 * Initialize the admin scripts
		 *
		 * @since 1.0.0
		 */
		init: function() {
			this.bindEvents();
			this.initComponents();
		},

		/**
		 * Bind event handlers
		 *
		 * @since 1.0.0
		 */
		bindEvents: function() {
			// Delete confirmation
			$( document ).on( 'click', '.rm-button-delete', this.confirmDelete );

			// Form submission handlers
			$( document ).on( 'submit', '.rm-ajax-form', this.handleAjaxForm );

			// Handle dismissible notices
			$( document ).on( 'click', '.rm-notice .notice-dismiss', this.dismissNotice );
		},

		/**
		 * Initialize UI components
		 *
		 * @since 1.0.0
		 */
		initComponents: function() {
			// Initialize any select2 dropdowns if available
			if ( $.fn.select2 ) {
				$( '.rm-select2' ).select2({
					width: '100%'
				});
			}

			// Initialize any datepickers if available
			if ( $.fn.datepicker ) {
				$( '.rm-datepicker' ).datepicker({
					dateFormat: 'yy-mm-dd'
				});
			}
		},

		/**
		 * Confirm delete action
		 *
		 * @since 1.0.0
		 * @param {Event} e The click event
		 */
		confirmDelete: function( e ) {
			var message = rmAdmin.i18n.confirmDelete;

			if ( ! confirm( message ) ) {
				e.preventDefault();
				return false;
			}
		},

		/**
		 * Handle AJAX form submission
		 *
		 * @since 1.0.0
		 * @param {Event} e The submit event
		 */
		handleAjaxForm: function( e ) {
			e.preventDefault();

			var $form = $( this );
			var $submitButton = $form.find( '[type="submit"]' );
			var buttonText = $submitButton.text();

			// Disable submit button
			$submitButton.prop( 'disabled', true ).text( 'Processing...' );

			// Get form data
			var formData = new FormData( this );
			formData.append( 'action', $form.data( 'action' ) );
			formData.append( 'nonce', rmAdmin.nonce );

			// Send AJAX request
			$.ajax({
				url: rmAdmin.ajaxUrl,
				type: 'POST',
				data: formData,
				processData: false,
				contentType: false,
				success: function( response ) {
					if ( response.success ) {
						RegionManagerAdmin.showNotice( 'success', response.data.message || rmAdmin.i18n.success );

						// Reload if needed
						if ( response.data.reload ) {
							setTimeout( function() {
								location.reload();
							}, 1000 );
						}
					} else {
						RegionManagerAdmin.showNotice( 'error', response.data.message || rmAdmin.i18n.error );
					}
				},
				error: function() {
					RegionManagerAdmin.showNotice( 'error', rmAdmin.i18n.error );
				},
				complete: function() {
					// Re-enable submit button
					$submitButton.prop( 'disabled', false ).text( buttonText );
				}
			});
		},

		/**
		 * Show admin notice
		 *
		 * @since 1.0.0
		 * @param {string} type The notice type (success, error, warning, info)
		 * @param {string} message The notice message
		 */
		showNotice: function( type, message ) {
			var $notice = $( '<div>' )
				.addClass( 'notice notice-' + type + ' is-dismissible' )
				.html( '<p>' + message + '</p>' );

			// Insert after page title
			$( '.wrap h1' ).first().after( $notice );

			// Initialize dismiss button
			if ( typeof wp !== 'undefined' && wp.notices ) {
				wp.notices.init();
			}

			// Auto-dismiss after 5 seconds
			setTimeout( function() {
				$notice.fadeOut( function() {
					$( this ).remove();
				});
			}, 5000 );
		},

		/**
		 * Dismiss notice
		 *
		 * @since 1.0.0
		 * @param {Event} e The click event
		 */
		dismissNotice: function( e ) {
			e.preventDefault();
			$( this ).closest( '.rm-notice' ).fadeOut( function() {
				$( this ).remove();
			});
		},

		/**
		 * Send AJAX request
		 *
		 * @since 1.0.0
		 * @param {string} action The AJAX action
		 * @param {Object} data Additional data to send
		 * @param {Function} success Success callback
		 * @param {Function} error Error callback
		 */
		ajax: function( action, data, success, error ) {
			data = data || {};
			data.action = action;
			data.nonce = rmAdmin.nonce;

			console.log('AJAX Request:', action, data); // Debug log

			$.ajax({
				url: rmAdmin.ajaxUrl,
				type: 'POST',
				data: data,
				success: function( response ) {
					console.log('AJAX Response:', response); // Debug log

					if ( response && response.success && typeof success === 'function' ) {
						success( response.data );
					} else if ( response && ! response.success && typeof error === 'function' ) {
						error( response.data || { message: 'Unknown error occurred' } );
					} else if ( typeof error === 'function' ) {
						// Invalid response format
						error({ message: 'Invalid response from server' });
					}
				},
				error: function( jqXHR, textStatus, errorThrown ) {
					console.error('AJAX Error:', textStatus, errorThrown, jqXHR); // Debug log

					if ( typeof error === 'function' ) {
						error({
							message: rmAdmin.i18n.error || 'Network error occurred',
							status: textStatus,
							error: errorThrown,
							statusCode: jqXHR.status
						});
					}
				}
			});
		}
	};

	/**
	 * Region Management
	 */
	var RegionManager = {
		selectedCountries: [],

		init: function() {
			// Add region button
			$( document ).on( 'click', '#rm-add-region', function() {
				RegionManager.openModal();
			});

			// Edit region button
			$( document ).on( 'click', '.rm-edit-region', function() {
				var regionId = $( this ).data( 'region-id' );
				RegionManager.openModal( regionId );
			});

			// Delete region button
			$( document ).on( 'click', '.rm-delete-region', function() {
				if ( confirm( rmAdmin.i18n.confirmDelete ) ) {
					var regionId = $( this ).data( 'region-id' );
					RegionManager.deleteRegion( regionId );
				}
			});

			// Modal close
			$( document ).on( 'click', '.rm-modal-close, #rm-modal-cancel', function() {
				RegionManager.closeModal();
			});

			// Click outside modal to close
			$( document ).on( 'click', '.rm-modal', function( e ) {
				if ( $( e.target ).hasClass( 'rm-modal' ) ) {
					RegionManager.closeModal();
				}
			});

			// Save region
			$( document ).on( 'click', '#rm-save-region', function() {
				RegionManager.saveRegion();
			});

			// Auto-generate slug from name
			$( document ).on( 'input', '#region_name', function() {
				var name = $( this ).val();
				var slug = name.toLowerCase()
					.replace( /[^\w\s-]/g, '' )
					.replace( /\s+/g, '-' )
					.replace( /-+/g, '-' )
					.trim();
				$( '#region_slug' ).val( slug );
			});

			// Country select
			$( document ).on( 'change', '#country-select', function() {
				var countryCode = $( this ).val();
				if ( countryCode && typeof rmCountries !== 'undefined' ) {
					RegionManager.addCountry( countryCode, rmCountries[countryCode] );
					$( this ).val( '' ).trigger( 'change' );
				}
			});

			// Remove country
			$( document ).on( 'click', '.rm-remove-country', function() {
				var countryCode = $( this ).data( 'country' );
				RegionManager.removeCountry( countryCode );
			});

			// Set default country
			$( document ).on( 'change', '.rm-country-default', function() {
				$( '.rm-country-default' ).not( this ).prop( 'checked', false );
			});
		},

		openModal: function( regionId ) {
			regionId = regionId || 0;

			// Reset form
			$( '#rm-region-form' )[0].reset();
			$( '#region_id' ).val( regionId );
			this.selectedCountries = [];
			$( '#selected-countries' ).empty();

			if ( regionId > 0 ) {
				// Edit mode - load region data
				$( '#rm-modal-title' ).text( 'Edit Region' );
				this.loadRegion( regionId );
			} else {
				// Add mode
				$( '#rm-modal-title' ).text( 'Add New Region' );
			}

			$( '#rm-region-modal' ).fadeIn( 200 );
			$( 'body' ).addClass( 'rm-modal-open' );
		},

		closeModal: function() {
			$( '#rm-region-modal' ).fadeOut( 200 );
			$( 'body' ).removeClass( 'rm-modal-open' );
		},

		loadRegion: function( regionId ) {
			RegionManagerAdmin.ajax( 'rm_get_region', { region_id: regionId }, function( data ) {
				// Fill form
				$( '#region_name' ).val( data.region.name );
				$( '#region_slug' ).val( data.region.slug );
				$( '#region_status' ).val( data.region.status );

				// Add countries
				if ( data.countries && typeof rmCountries !== 'undefined' ) {
					data.countries.forEach( function( country ) {
						RegionManager.addCountry(
							country.country_code,
							rmCountries[country.country_code],
							country.url_slug,
							country.language_code,
							country.currency_code || 'EUR',
							country.is_default == 1
						);
					});
				}
			}, function( error ) {
				alert( error.message || 'Failed to load region' );
			});
		},

		addCountry: function( code, name, urlSlug, languageCode, currencyCode, isDefault ) {
			// Check if already added
			if ( this.selectedCountries.indexOf( code ) !== -1 ) {
				return;
			}

			this.selectedCountries.push( code );

			urlSlug = urlSlug || '/' + code.toLowerCase();
			languageCode = languageCode || 'en_US';
			currencyCode = currencyCode || 'EUR';
			isDefault = isDefault || false;

			var row = '<tr data-country="' + code + '">' +
				'<td><strong>' + name + '</strong> <code>' + code + '</code></td>' +
				'<td>' +
				'<input type="text" class="rm-input rm-country-url-slug" value="' + urlSlug + '" placeholder="/pt" />' +
				'</td>' +
				'<td>' +
				'<select class="rm-input rm-country-language">' +
				this.getLanguageOptions( languageCode ) +
				'</select>' +
				'</td>' +
				'<td>' +
				'<select class="rm-input rm-country-currency">' +
				this.getCurrencyOptions( currencyCode ) +
				'</select>' +
				'</td>' +
				'<td class="rm-text-center">' +
				'<input type="radio" class="rm-country-default" name="default_country" value="' + code + '"' + ( isDefault ? ' checked' : '' ) + ' />' +
				'</td>' +
				'<td class="rm-text-center">' +
				'<button type="button" class="button button-small rm-remove-country" data-country="' + code + '">Remove</button>' +
				'</td>' +
				'</tr>';

			$( '#selected-countries' ).append( row );
		},

		removeCountry: function( code ) {
			var index = this.selectedCountries.indexOf( code );
			if ( index > -1 ) {
				this.selectedCountries.splice( index, 1 );
			}
			$( 'tr[data-country="' + code + '"]' ).remove();
		},

		getLanguageOptions: function( selected ) {
			var options = '';
			if ( typeof rmLanguageCodes !== 'undefined' ) {
				$.each( rmLanguageCodes, function( code, label ) {
					options += '<option value="' + code + '"' + ( code === selected ? ' selected' : '' ) + '>' + label + '</option>';
				});
			}
			return options;
		},

		getCurrencyOptions: function( selected ) {
			var options = '';
			if ( typeof rmCurrencyCodes !== 'undefined' && Object.keys(rmCurrencyCodes).length > 0 ) {
				$.each( rmCurrencyCodes, function( code, label ) {
					var symbol = ( typeof rmCurrencySymbols !== 'undefined' && rmCurrencySymbols[code] ) ? rmCurrencySymbols[code] : '';
					options += '<option value="' + code + '"' + ( code === selected ? ' selected' : '' ) + '>' +
						code + ' - ' + label + ( symbol ? ' (' + symbol + ')' : '' ) + '</option>';
				});
			} else {
				// Fallback if currencies not loaded
				console.warn('rmCurrencyCodes not defined, using fallback');
				var fallbackCurrencies = {
					'EUR': '€ Euro',
					'USD': '$ US Dollar',
					'GBP': '£ Pound Sterling',
					'BRL': 'R$ Brazilian Real'
				};
				$.each( fallbackCurrencies, function( code, label ) {
					options += '<option value="' + code + '"' + ( code === selected ? ' selected' : '' ) + '>' + label + '</option>';
				});
			}
			return options;
		},

		saveRegion: function() {
			var regionId = $( '#region_id' ).val();
			var name = $( '#region_name' ).val().trim();
			var slug = $( '#region_slug' ).val().trim();
			var status = $( '#region_status' ).val();

			// Validate
			if ( ! name ) {
				alert( 'Region name is required' );
				return;
			}

			if ( ! slug ) {
				alert( 'Region slug is required' );
				return;
			}

			// Collect countries
			var countries = [];
			$( '#selected-countries tr' ).each( function() {
				var countryCode = $( this ).data( 'country' );
				var urlSlug = $( this ).find( '.rm-country-url-slug' ).val();
				var languageCode = $( this ).find( '.rm-country-language' ).val();
				var currencyCode = $( this ).find( '.rm-country-currency' ).val();
				var isDefault = $( this ).find( '.rm-country-default' ).is( ':checked' );

				countries.push({
					country_code: countryCode,
					url_slug: urlSlug,
					language_code: languageCode,
					currency_code: currencyCode,
					is_default: isDefault
				});
			});

			// Show loading
			var $button = $( '#rm-save-region' );
			var buttonText = $button.text();
			$button.prop( 'disabled', true ).text( 'Saving...' );

			console.log('Saving region:', { regionId, name, slug, status, countries }); // Debug log

			// Send AJAX
			RegionManagerAdmin.ajax( 'rm_save_region', {
				region_id: regionId,
				name: name,
				slug: slug,
				status: status,
				countries: JSON.stringify( countries )
			}, function( data ) {
				console.log('Success response:', data); // Debug log

				// Show success feedback on button
				$button.text( '✓ Saved!' ).css({
					'background-color': '#00a32a',
					'border-color': '#00a32a',
					'color': '#fff'
				});

				// Show success notice
				RegionManagerAdmin.showNotice( 'success', data.message );

				// Close modal after short delay
				setTimeout( function() {
					RegionManager.closeModal();
				}, 500 );

				// Reload page after showing feedback
				setTimeout( function() {
					location.reload();
				}, 1500 );
			}, function( error ) {
				console.error('Error response:', error); // Debug log

				// Reset button on error
				$button.prop( 'disabled', false ).text( buttonText ).css({
					'background-color': '',
					'border-color': '',
					'color': ''
				});

				if ( error.upgrade_url ) {
					if ( confirm( error.message + '\n\nUpgrade now?' ) ) {
						window.location.href = error.upgrade_url;
					}
				} else {
					var errorMsg = error.message || 'Failed to save region';
					alert( errorMsg );
					RegionManagerAdmin.showNotice( 'error', errorMsg );
				}
			});
		},

		deleteRegion: function( regionId ) {
			RegionManagerAdmin.ajax( 'rm_delete_region', { region_id: regionId }, function( data ) {
				RegionManagerAdmin.showNotice( 'success', data.message );
				setTimeout( function() {
					location.reload();
				}, 1000 );
			}, function( error ) {
				alert( error.message || 'Failed to delete region' );
			});
		}
	};

	/**
	 * Checkout Settings
	 */
	var CheckoutSettings = {
		init: function() {
			$( document ).on( 'submit', '#rm-checkout-settings-form', function( e ) {
				e.preventDefault();
				CheckoutSettings.saveSettings();
			});
		},

		saveSettings: function() {
			var $button = $( '#rm-save-checkout-settings' );
			var buttonText = $button.text();
			$button.prop( 'disabled', true ).text( 'Saving...' );

			var data = {
				cross_region_purchase: $( 'input[name="cross_region_purchase"]:checked' ).val(),
				extra_charge: $( '#extra_charge' ).val(),
				charge_type: $( 'input[name="charge_type"]:checked' ).val(),
				block_message: $( '#block_message' ).val(),
				geoip_fallback: $( 'input[name="geoip_fallback"]' ).is( ':checked' ) ? 1 : 0
			};

			RegionManagerAdmin.ajax( 'rm_save_checkout_settings', data, function( response ) {
				$button.prop( 'disabled', false ).text( buttonText );
				RegionManagerAdmin.showNotice( 'success', response.message );
			}, function( error ) {
				$button.prop( 'disabled', false ).text( buttonText );
				alert( error.message || 'Failed to save settings' );
			});
		}
	};

	/**
	 * Initialize on document ready
	 */
	$( document ).ready( function() {
		RegionManagerAdmin.init();
		RegionManager.init();
		CheckoutSettings.init();

		// Initialize Select2 for country select if available
		if ( $.fn.select2 && $( '#country-select' ).length ) {
			$( '#country-select' ).select2({
				placeholder: 'Select countries...',
				width: '100%'
			});
		}
	});

	// Expose to global scope
	window.RegionManagerAdmin = RegionManagerAdmin;
	window.RegionManager = RegionManager;
	window.CheckoutSettings = CheckoutSettings;

})( jQuery );
