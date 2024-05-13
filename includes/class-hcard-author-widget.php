<?php

add_action( 'widgets_init', 'indieweb_register_hcard' );

function indieweb_register_hcard() {
	register_widget( 'HCard_Author_Widget' );
}

// phpcs:ignore Universal.Files.SeparateFunctionsFromOO.Mixed
class HCard_Author_Widget extends WP_Widget {
	/**
	 * Register widget with WordPress.
	 */
	public function __construct() {
		parent::__construct(
			'HCard_Widget',                // Base ID
			'Author Profile H-Card Widget',        // Name
			array(
				'classname'             => 'hcard_widget',
				'description'           => __( 'A widget that allows you to display author profile marked up as an h-card', 'indieweb' ),
				'show_instance_in_rest' => true,
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
		} elseif ( is_single() ) {
				global $wp_query;
				$display_author = $wp_query->post->post_author;
		} else {
			return;
		}

		$user_info = get_userdata( $display_author );

		// phpcs:ignore
		echo $args['before_widget'];

		?>

		<div id="hcard_widget">
			<?php // phpcs:ignore
			echo HCard_User::hcard( $user_info, $instance );
			?>
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
			if ( in_array( $k, array( 'notes', 'location', 'avatar' ), true ) ) {
				$v = (int) $v;
			}
			$instance[ $k ] = wp_strip_all_tags( $v );
		}

		// Apply changes to checkboxes which are unchecked when absent from the POST
		$instance['reveal_email'] = isset( $new_instance['reveal_email'] ) ? 'on' : '';

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
			'avatar'      => 1,
			'location'    => 1,
			'notes'       => 1,
			'avatar_size' => '125',
			'email'       => 0,
			'me'          => 0,
		);

		$instance = wp_parse_args( (array) $instance, $defaults );
		?>
	<p>
		<label for="<?php echo esc_attr( $this->get_field_id( 'avatar_size' ) ); ?>"><?php esc_html_e( 'Avatar Size:', 'indieweb' ); ?></label>
		<input type="text" name="<?php echo esc_attr( $this->get_field_name( 'avatar_size' ) ); ?>" id="<?php echo esc_attr( $this->get_field_id( 'avatar_size' ) ); ?>" value="<?php echo esc_attr( $instance['avatar_size'] ); ?>" />
	</p>
	<p>
		<label for="<?php echo esc_attr( $this->get_field_id( 'avatar' ) ); ?>"><?php esc_html_e( 'Show Avatar:', 'indieweb' ); ?></label>
		<input type="hidden" name="<?php echo esc_attr( $this->get_field_name( 'avatar' ) ); ?>" id="<?php echo esc_attr( $this->get_field_id( 'avatar' ) ); ?>" value="0" />
		<input type="checkbox" name="<?php echo esc_attr( $this->get_field_name( 'avatar' ) ); ?>" id="<?php echo esc_attr( $this->get_field_id( 'avatar' ) ); ?>" value="1" <?php checked( $instance['avatar'], 1 ); ?> />
	</p>
	<p>
		<label for="<?php echo esc_attr( $this->get_field_id( 'location' ) ); ?>"><?php esc_html_e( 'Show Location:', 'indieweb' ); ?></label>
		<input type="hidden" name="<?php echo esc_attr( $this->get_field_name( 'location' ) ); ?>" id="<?php echo esc_attr( $this->get_field_id( 'location' ) ); ?>" value="0" />
		<input type="checkbox" name="<?php echo esc_attr( $this->get_field_name( 'location' ) ); ?>" id="<?php echo esc_attr( $this->get_field_id( 'location' ) ); ?>" value="1" <?php checked( $instance['location'], 1 ); ?> />
	</p>
	<p>
		<label for="<?php echo esc_attr( $this->get_field_id( 'notes' ) ); ?>"><?php esc_html_e( 'Show Notes:', 'indieweb' ); ?></label>
		<input type="hidden" name="<?php echo esc_attr( $this->get_field_name( 'notes' ) ); ?>" id="<?php echo esc_attr( $this->get_field_id( 'notes' ) ); ?>" value="0" />
		<input type="checkbox" name="<?php echo esc_attr( $this->get_field_name( 'notes' ) ); ?>" id="<?php echo esc_attr( $this->get_field_id( 'notes' ) ); ?>" value="1" <?php checked( $instance['notes'], 1 ); ?> />
	</p>
	<p>
		<label for="<?php echo esc_attr( $this->get_field_id( 'email' ) ); ?>"><?php esc_html_e( 'Show Email:', 'indieweb' ); ?></label>
		<input type="hidden" name="<?php echo esc_attr( $this->get_field_name( 'email' ) ); ?>" id="<?php echo esc_attr( $this->get_field_id( 'email' ) ); ?>" value="0" />
		<input type="checkbox" name="<?php echo esc_attr( $this->get_field_name( 'email' ) ); ?>" id="<?php echo esc_attr( $this->get_field_id( 'email' ) ); ?>" value="1" <?php checked( $instance['email'], 1 ); ?> />
	</p>
	<p>
		<label for="<?php echo esc_attr( $this->get_field_id( 'me' ) ); ?>"><?php esc_html_e( 'Show Rel-Me:', 'indieweb' ); ?></label>
		<input type="hidden" name="<?php echo esc_attr( $this->get_field_name( 'me' ) ); ?>" id="<?php echo esc_attr( $this->get_field_id( 'me' ) ); ?>" value="0" />
		<input type="checkbox" name="<?php echo esc_attr( $this->get_field_name( 'me' ) ); ?>" id="<?php echo esc_attr( $this->get_field_id( 'me' ) ); ?>" value="1" <?php checked( $instance['me'], 1 ); ?> />
	</p>
		<?php
	}
}
