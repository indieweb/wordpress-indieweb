<?php
add_action( 'widgets_init', 'indieweb_register_hcard' );
function indieweb_register_hcard() {
	register_widget( 'HCard_Author_Widget' );
}

class HCard_Author_Widget extends WP_Widget {
	/**
	 * Register widget with WordPress.
	 */
	public function __construct() {
		parent::__construct(
			'HCard_Widget',                // Base ID
			'Author Profile H-Card Widget',        // Name
			array(
				'classname'   => 'hcard_widget',
				'description' => __( 'A widget that allows you to display author profile marked up as an h-card', 'indieweb' ),
			)
		);

	} // end constructor

	/**
	 * Front-end display of widget.
	 *
	 * @see WP_Widget::widget()
	 *
	 * @param array $args     Widget arguments.
	 * @param array $instance Saved values from database.
	 */
	public function widget( $args, $instance ) {
		if ( 1 === (int) get_option( 'iw_single_author' ) ) {
			$display_author = get_option( 'iw_default_author' );
		} else {
			if ( is_single() ) {
				global $wp_query;
				$display_author = $wp_query->post->post_author;
			} else {
				return;
			}
		}

		$user_info = get_userdata( $display_author );

		// phpcs:ignore
		esc_html( $args['before_widget'] );

		?>

		<div id="hcard_widget">
			<?php // phpcs:ignore
				echo HCard_User::hcard( $user_info, $instance ); ?>
		</div>

		<?php
		// phpcs:ignore
		echo $args['after_widget'];
	}



	/**
	 * Sanitize widget form values as they are saved.
	 *
	 * @see WP_Widget::update()
	 *
	 * @param array $new_instance Values just sent to be saved.
	 * @param array $old_instance Previously saved values from database.
	 *
	 * @return array Updated safe values to be saved.
	 */
	public function update( $new_instance, $old_instance ) {
		$instance = $old_instance;

		// Strip tags to remove HTML (important for text inputs)
		foreach ( $new_instance as $k => $v ) {
			$instance[ $k ] = wp_strip_all_tags( $v );
		}

		return $instance;
	}


	/**
	 * Create the form for the Widget admin
	 *
	 * @see WP_Widget::form()
	 *
	 * @param array $instance Previously saved values from database.
	 */
	public function form( $instance ) {

		// Set up some default widget settings
		$defaults = array(
			'avatar_size' => '125',
		);

		$instance = wp_parse_args( (array) $instance, $defaults );
		?>
	<p>
		<label for="<?php echo esc_attr( $this->get_field_id( 'avatar_size' ) ); ?>"><?php esc_html_e( 'Avatar Size:', 'indieweb' ); ?></label>
		<input type="text" name="<?php echo esc_attr( $this->get_field_name( 'avatar_size' ) ); ?>" id="<?php echo esc_attr( $this->get_field_id( 'avatar_size' ) ); ?>" value="<?php echo esc_attr( $instance['avatar_size'] ); ?>" />
	</p>


		<?php
	}


}
?>
