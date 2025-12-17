<?php
/**
 * Region Switcher Widget
 *
 * Widget for displaying region switcher in sidebars.
 *
 * @package    Region_Manager
 * @subpackage Region_Manager/includes
 */

/**
 * Region Switcher Widget Class.
 *
 * Displays a region switcher widget in WordPress sidebars.
 */
class RM_Widget_Region_Switcher extends WP_Widget {

	/**
	 * Register widget with WordPress.
	 */
	public function __construct() {
		parent::__construct(
			'rm_region_switcher',
			__( 'Region Switcher', 'region-manager' ),
			array(
				'description' => __( 'Display a region switcher for visitors to select their region.', 'region-manager' ),
			)
		);
	}

	/**
	 * Front-end display of widget.
	 *
	 * @param array $args     Widget arguments.
	 * @param array $instance Saved values from database.
	 */
	public function widget( $args, $instance ) {
		$title = ! empty( $instance['title'] ) ? $instance['title'] : __( 'Select Region', 'region-manager' );
		$style = ! empty( $instance['style'] ) ? $instance['style'] : 'dropdown';
		$show_flags = isset( $instance['show_flags'] ) ? $instance['show_flags'] : true;

		echo $args['before_widget'];

		if ( ! empty( $title ) ) {
			echo $args['before_title'] . esc_html( $title ) . $args['after_title'];
		}

		// Use shortcode to display region switcher.
		echo do_shortcode( '[rm_region_switcher style="' . esc_attr( $style ) . '" show_flags="' . ( $show_flags ? 'true' : 'false' ) . '"]' );

		echo $args['after_widget'];
	}

	/**
	 * Back-end widget form.
	 *
	 * @param array $instance Previously saved values from database.
	 * @return string
	 */
	public function form( $instance ) {
		$title = ! empty( $instance['title'] ) ? $instance['title'] : __( 'Select Region', 'region-manager' );
		$style = ! empty( $instance['style'] ) ? $instance['style'] : 'dropdown';
		$show_flags = isset( $instance['show_flags'] ) ? $instance['show_flags'] : true;
		?>
		<p>
			<label for="<?php echo esc_attr( $this->get_field_id( 'title' ) ); ?>">
				<?php esc_html_e( 'Title:', 'region-manager' ); ?>
			</label>
			<input class="widefat" id="<?php echo esc_attr( $this->get_field_id( 'title' ) ); ?>" name="<?php echo esc_attr( $this->get_field_name( 'title' ) ); ?>" type="text" value="<?php echo esc_attr( $title ); ?>">
		</p>

		<p>
			<label for="<?php echo esc_attr( $this->get_field_id( 'style' ) ); ?>">
				<?php esc_html_e( 'Display Style:', 'region-manager' ); ?>
			</label>
			<select class="widefat" id="<?php echo esc_attr( $this->get_field_id( 'style' ) ); ?>" name="<?php echo esc_attr( $this->get_field_name( 'style' ) ); ?>">
				<option value="dropdown" <?php selected( $style, 'dropdown' ); ?>><?php esc_html_e( 'Dropdown', 'region-manager' ); ?></option>
				<option value="list" <?php selected( $style, 'list' ); ?>><?php esc_html_e( 'List', 'region-manager' ); ?></option>
				<option value="flags" <?php selected( $style, 'flags' ); ?>><?php esc_html_e( 'Flags Only', 'region-manager' ); ?></option>
			</select>
		</p>

		<p>
			<input class="checkbox" type="checkbox" <?php checked( $show_flags, true ); ?> id="<?php echo esc_attr( $this->get_field_id( 'show_flags' ) ); ?>" name="<?php echo esc_attr( $this->get_field_name( 'show_flags' ) ); ?>" value="1">
			<label for="<?php echo esc_attr( $this->get_field_id( 'show_flags' ) ); ?>">
				<?php esc_html_e( 'Show country flags', 'region-manager' ); ?>
			</label>
		</p>
		<?php
		return '';
	}

	/**
	 * Sanitize widget form values as they are saved.
	 *
	 * @param array $new_instance Values just sent to be saved.
	 * @param array $old_instance Previously saved values from database.
	 * @return array Updated safe values to be saved.
	 */
	public function update( $new_instance, $old_instance ) {
		$instance = array();

		$instance['title'] = ! empty( $new_instance['title'] ) ? sanitize_text_field( $new_instance['title'] ) : '';
		$instance['style'] = ! empty( $new_instance['style'] ) ? sanitize_text_field( $new_instance['style'] ) : 'dropdown';
		$instance['show_flags'] = isset( $new_instance['show_flags'] ) ? (bool) $new_instance['show_flags'] : false;

		return $instance;
	}
}

/**
 * Register region switcher widget.
 */
function rm_register_region_switcher_widget() {
	register_widget( 'RM_Widget_Region_Switcher' );
}
add_action( 'widgets_init', 'rm_register_region_switcher_widget' );
