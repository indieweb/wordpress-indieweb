<?php
add_action( 'widgets_init', 'indieweb_register_hcard' );
function indieweb_register_hcard() {
	register_widget( "HCard_Author_Widget" );
}

class HCard_Author_Widget extends WP_Widget {
	/**
	 * Register widget with WordPress.
	 */
	public function __construct() {
		parent::__construct(
			'HCard_Widget',				// Base ID
			'Author H-Card Widget',		// Name
			array(
				'classname'		=> 'hcard_widget',
				'description'	=> __( 'A widget that allows you to display h-cards for a specific author', 'framework' ),
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
		extract( $args );
		extract( $instance );

		if ( (int) $override === 1 && is_single() ) {
			$display_author = get_the_author_meta( 'ID' );
		}

		$user_info = get_userdata( $display_author );

		// Our variables from the widget settings
		$title = '';

		// Before widget (defined by theme functions file)
		echo $before_widget;

		// Display the widget title if one was input
		if ( $title ) {
			echo $before_title . $title . $after_title; }
		?>

		<div id="hcard_widget">
			<?php echo HCard_User::hcard( $user_info ); ?>
		</div>

		<?php

		// After widget (defined by theme functions file)
		echo $after_widget;
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
			$instance[ $k ] = strip_tags( $v );
		}

		$instance['display_author'] = $new_instance['display_author'];
		$instance['override'] = (bool) $new_instance['override'];

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
		'display_author' => '',
		'override' => '1',
		'background' => '#ffffff',
		'font_color' => '#000000',
		'avatar_size' => '125',
		'relme' => 'false',
		);

		$instance = wp_parse_args( (array) $instance, $defaults ); ?>

	<p>
		<label for="<?php echo $this->get_field_id( 'display_author' ); ?>"><?php _e( 'Display Author:', 'framework' ) ?></label>
		<?php wp_dropdown_users( array(
			'id' => $this->get_field_id( 'display_author' ),
									'name' => $this->get_field_name( 'display_author' ),
									'selected' => $instance['display_author'],
		) ); ?>
	</p>

	<p>
		<label for="<?php echo $this->get_field_id( 'avatar_size' ); ?>"><?php _e( 'Avatar Size:', 'framework' ) ?></label>
		<input type="text" name="<?php echo $this->get_field_name( 'avatar_size' ); ?>" id="<?php echo $this->get_field_id( 'avatar_size' ); ?>" value="<?php echo $instance['avatar_size']; ?>" />
	</p>


	<?php
	}


}
?>
