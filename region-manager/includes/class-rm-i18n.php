<?php
/**
 * Define the internationalization functionality.
 *
 * Loads and defines the internationalization files for this plugin
 * so that it is ready for translation.
 *
 * @package    RegionManager
 * @subpackage RegionManager/includes
 * @since      1.0.0
 */

/**
 * Define the internationalization functionality.
 *
 * @since 1.0.0
 */
class RM_i18n {

	/**
	 * Load the plugin text domain for translation.
	 *
	 * @since 1.0.0
	 */
	public function load_plugin_textdomain() {
		load_plugin_textdomain(
			'region-manager',
			false,
			dirname( RM_PLUGIN_BASENAME ) . '/languages/'
		);
	}
}
