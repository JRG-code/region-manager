<?php
/**
 * The customization-specific functionality of the plugin.
 *
 * @package    Region_Manager
 * @subpackage Region_Manager/admin
 */

/**
 * The customization-specific functionality of the plugin.
 *
 * Manages the Customization page with Landing Page, Menu Flag, and Translator Integration.
 */
class RM_Customization {

	/**
	 * The ID of this plugin.
	 *
	 * @var string
	 */
	private $plugin_name;

	/**
	 * The version of this plugin.
	 *
	 * @var string
	 */
	private $version;

	/**
	 * Initialize the class and set its properties.
	 *
	 * @param string $plugin_name The name of this plugin.
	 * @param string $version     The version of this plugin.
	 */
	public function __construct( $plugin_name, $version ) {
		$this->plugin_name = $plugin_name;
		$this->version     = $version;
	}

	/**
	 * Render the customization page.
	 */
	public function display_customization_page() {
		require_once plugin_dir_path( __FILE__ ) . 'partials/customization-display.php';
	}

	/**
	 * AJAX handler to save landing page settings.
	 */
	public function save_landing_page_settings() {
		check_ajax_referer( 'rm_admin_nonce', 'nonce' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => __( 'Permission denied.', 'region-manager' ) ) );
		}

		$enabled          = isset( $_POST['enabled'] ) ? (bool) $_POST['enabled'] : false;
		$template         = isset( $_POST['template'] ) ? sanitize_text_field( wp_unslash( $_POST['template'] ) ) : 'default';
		$title            = isset( $_POST['title'] ) ? sanitize_text_field( wp_unslash( $_POST['title'] ) ) : '';
		$description      = isset( $_POST['description'] ) ? sanitize_textarea_field( wp_unslash( $_POST['description'] ) ) : '';
		$auto_redirect    = isset( $_POST['auto_redirect'] ) ? (bool) $_POST['auto_redirect'] : false;
		$redirect_delay   = isset( $_POST['redirect_delay'] ) ? absint( $_POST['redirect_delay'] ) : 3;
		$show_flags       = isset( $_POST['show_flags'] ) ? (bool) $_POST['show_flags'] : true;
		$show_description = isset( $_POST['show_description'] ) ? (bool) $_POST['show_description'] : true;

		update_option( 'rm_landing_page_enabled', $enabled );
		update_option( 'rm_landing_page_template', $template );
		update_option( 'rm_landing_page_title', $title );
		update_option( 'rm_landing_page_description', $description );
		update_option( 'rm_landing_page_auto_redirect', $auto_redirect );
		update_option( 'rm_landing_page_redirect_delay', $redirect_delay );
		update_option( 'rm_landing_page_show_flags', $show_flags );
		update_option( 'rm_landing_page_show_description', $show_description );

		wp_send_json_success( array( 'message' => __( 'Landing page settings saved successfully.', 'region-manager' ) ) );
	}

	/**
	 * AJAX handler to create landing page.
	 */
	public function create_landing_page() {
		check_ajax_referer( 'rm_admin_nonce', 'nonce' );

		if ( ! current_user_can( 'edit_pages' ) ) {
			wp_send_json_error( array( 'message' => __( 'Permission denied. You do not have permission to create pages.', 'region-manager' ) ) );
		}

		$page_title     = isset( $_POST['page_title'] ) ? sanitize_text_field( wp_unslash( $_POST['page_title'] ) ) : __( 'Select Your Region', 'region-manager' );
		$set_as_home    = isset( $_POST['set_as_home'] ) ? (bool) $_POST['set_as_home'] : false;
		$enable_landing = isset( $_POST['enable_landing'] ) ? (bool) $_POST['enable_landing'] : true;

		// Check if a landing page already exists.
		$existing_page_id = get_option( 'rm_landing_page_id' );
		if ( $existing_page_id && get_post( $existing_page_id ) ) {
			$page_url  = get_permalink( $existing_page_id );
			$edit_url  = admin_url( 'post.php?post=' . $existing_page_id . '&action=edit' );
			wp_send_json_error(
				array(
					'message'  => __( 'A landing page already exists.', 'region-manager' ),
					'page_url' => $page_url,
					'edit_url' => $edit_url,
				)
			);
		}

		// Create the page.
		$page_data = array(
			'post_title'   => $page_title,
			'post_content' => '[region_landing_page]',
			'post_status'  => 'publish',
			'post_type'    => 'page',
			'post_author'  => get_current_user_id(),
		);

		$page_id = wp_insert_post( $page_data );

		if ( is_wp_error( $page_id ) ) {
			wp_send_json_error( array( 'message' => __( 'Failed to create landing page.', 'region-manager' ) ) );
		}

		// Store the page ID.
		update_option( 'rm_landing_page_id', $page_id );

		// Set as homepage if requested.
		if ( $set_as_home ) {
			update_option( 'show_on_front', 'page' );
			update_option( 'page_on_front', $page_id );
		}

		// Enable landing page if requested.
		if ( $enable_landing ) {
			update_option( 'rm_landing_page_enabled', true );
		}

		$page_url = get_permalink( $page_id );
		$edit_url = admin_url( 'post.php?post=' . $page_id . '&action=edit' );

		wp_send_json_success(
			array(
				'message'  => __( 'Landing page created successfully!', 'region-manager' ),
				'page_id'  => $page_id,
				'page_url' => $page_url,
				'edit_url' => $edit_url,
			)
		);
	}

	/**
	 * Get existing landing page info.
	 *
	 * @return array|null Landing page info or null.
	 */
	public function get_landing_page_info() {
		$page_id = get_option( 'rm_landing_page_id' );

		if ( ! $page_id ) {
			return null;
		}

		$page = get_post( $page_id );

		if ( ! $page || 'page' !== $page->post_type ) {
			return null;
		}

		$is_homepage = ( 'page' === get_option( 'show_on_front' ) && intval( get_option( 'page_on_front' ) ) === $page_id );

		return array(
			'id'          => $page_id,
			'title'       => $page->post_title,
			'url'         => get_permalink( $page_id ),
			'edit_url'    => admin_url( 'post.php?post=' . $page_id . '&action=edit' ),
			'status'      => $page->post_status,
			'is_homepage' => $is_homepage,
		);
	}

	/**
	 * AJAX handler to save menu flag settings.
	 */
	public function save_menu_flag_settings() {
		check_ajax_referer( 'rm_admin_nonce', 'nonce' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => __( 'Permission denied.', 'region-manager' ) ) );
		}

		$enabled       = isset( $_POST['enabled'] ) ? (bool) $_POST['enabled'] : false;
		$position      = isset( $_POST['position'] ) ? sanitize_text_field( wp_unslash( $_POST['position'] ) ) : 'right';
		$menu_location = isset( $_POST['menu_location'] ) ? sanitize_text_field( wp_unslash( $_POST['menu_location'] ) ) : 'primary';
		$show_text     = isset( $_POST['show_text'] ) ? (bool) $_POST['show_text'] : true;
		$show_dropdown = isset( $_POST['show_dropdown'] ) ? (bool) $_POST['show_dropdown'] : true;

		update_option( 'rm_menu_flag_enabled', $enabled );
		update_option( 'rm_menu_flag_position', $position );
		update_option( 'rm_menu_flag_menu_location', $menu_location );
		update_option( 'rm_menu_flag_show_text', $show_text );
		update_option( 'rm_menu_flag_show_dropdown', $show_dropdown );

		wp_send_json_success( array( 'message' => __( 'Menu flag settings saved successfully.', 'region-manager' ) ) );
	}

	/**
	 * AJAX handler to save translator integration settings.
	 */
	public function save_translator_settings() {
		check_ajax_referer( 'rm_admin_nonce', 'nonce' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => __( 'Permission denied.', 'region-manager' ) ) );
		}

		$enabled        = isset( $_POST['enabled'] ) ? (bool) $_POST['enabled'] : false;
		$plugin         = isset( $_POST['plugin'] ) ? sanitize_text_field( wp_unslash( $_POST['plugin'] ) ) : 'wpml';
		$sync_languages = isset( $_POST['sync_languages'] ) ? (bool) $_POST['sync_languages'] : true;
		$override_langs = isset( $_POST['override_langs'] ) ? (bool) $_POST['override_langs'] : false;

		update_option( 'rm_translator_enabled', $enabled );
		update_option( 'rm_translator_plugin', $plugin );
		update_option( 'rm_translator_sync_languages', $sync_languages );
		update_option( 'rm_translator_override_langs', $override_langs );

		wp_send_json_success( array( 'message' => __( 'Translator settings saved successfully.', 'region-manager' ) ) );
	}

	/**
	 * Get landing page settings.
	 *
	 * @return array Landing page settings.
	 */
	public function get_landing_page_settings() {
		return array(
			'enabled'          => (bool) get_option( 'rm_landing_page_enabled', false ),
			'template'         => get_option( 'rm_landing_page_template', 'default' ),
			'title'            => get_option( 'rm_landing_page_title', __( 'Select Your Region', 'region-manager' ) ),
			'description'      => get_option( 'rm_landing_page_description', __( 'Please choose your region to see relevant products and pricing.', 'region-manager' ) ),
			'auto_redirect'    => (bool) get_option( 'rm_landing_page_auto_redirect', false ),
			'redirect_delay'   => absint( get_option( 'rm_landing_page_redirect_delay', 3 ) ),
			'show_flags'       => (bool) get_option( 'rm_landing_page_show_flags', true ),
			'show_description' => (bool) get_option( 'rm_landing_page_show_description', true ),
		);
	}

	/**
	 * Get menu flag settings.
	 *
	 * @return array Menu flag settings.
	 */
	public function get_menu_flag_settings() {
		return array(
			'enabled'       => (bool) get_option( 'rm_menu_flag_enabled', false ),
			'position'      => get_option( 'rm_menu_flag_position', 'right' ),
			'menu_location' => get_option( 'rm_menu_flag_menu_location', 'primary' ),
			'show_text'     => (bool) get_option( 'rm_menu_flag_show_text', true ),
			'show_dropdown' => (bool) get_option( 'rm_menu_flag_show_dropdown', true ),
		);
	}

	/**
	 * Get translator integration settings.
	 *
	 * @return array Translator integration settings.
	 */
	public function get_translator_settings() {
		return array(
			'enabled'        => (bool) get_option( 'rm_translator_enabled', false ),
			'plugin'         => get_option( 'rm_translator_plugin', 'wpml' ),
			'sync_languages' => (bool) get_option( 'rm_translator_sync_languages', true ),
			'override_langs' => (bool) get_option( 'rm_translator_override_langs', false ),
		);
	}

	/**
	 * Get available menu locations.
	 *
	 * @return array Menu locations.
	 */
	public function get_menu_locations() {
		$locations = get_registered_nav_menus();
		return $locations;
	}

	/**
	 * AJAX handler to set landing page as homepage.
	 */
	public function set_as_homepage() {
		check_ajax_referer( 'rm_admin_nonce', 'nonce' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => __( 'Permission denied.', 'region-manager' ) ) );
		}

		$page_id = isset( $_POST['page_id'] ) ? absint( $_POST['page_id'] ) : 0;

		if ( ! $page_id ) {
			wp_send_json_error( array( 'message' => __( 'Invalid page ID.', 'region-manager' ) ) );
		}

		// Set the page as homepage
		update_option( 'page_on_front', $page_id );
		update_option( 'show_on_front', 'page' );

		wp_send_json_success( array( 'message' => __( 'Homepage updated successfully!', 'region-manager' ) ) );
	}
}
