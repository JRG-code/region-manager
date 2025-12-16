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

			$.ajax({
				url: rmAdmin.ajaxUrl,
				type: 'POST',
				data: data,
				success: function( response ) {
					if ( response.success && typeof success === 'function' ) {
						success( response.data );
					} else if ( ! response.success && typeof error === 'function' ) {
						error( response.data );
					}
				},
				error: function( jqXHR, textStatus, errorThrown ) {
					if ( typeof error === 'function' ) {
						error({
							message: rmAdmin.i18n.error,
							status: textStatus,
							error: errorThrown
						});
					}
				}
			});
		}
	};

	/**
	 * Initialize on document ready
	 */
	$( document ).ready( function() {
		RegionManagerAdmin.init();
	});

	// Expose to global scope
	window.RegionManagerAdmin = RegionManagerAdmin;

})( jQuery );
