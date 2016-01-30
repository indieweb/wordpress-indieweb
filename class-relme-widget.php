<?php

// add widget
add_action( 'widgets_init', array( 'hcard_user', 'init_widgets' ) );


/**
 * adds widget to display rel-me links for indieauth with per-user profile support
 */
class RelMe_Widget extends WP_Widget {

	/**
	 * widget constructor
	 */
	function __construct() {
		parent::__construct(
			'RelMe_Widget',
			__( 'Rel-me URLs', 'indieweb' ),
			array(
				'description' => __( 'Adds automatic rel-me URLs based on author profile information.', 'indieweb' ),
			)
		);
	}

	/**
	 * widget worker
	 *
	 * @param mixed $args widget parameters
	 * @param mixed $instance saved widget data
	 *
	 * @output echoes the list of rel-me links for the author
	 */
	public function widget( $args, $instance ) {
		global $authordata;

		$include_rel = is_author() || (is_front_page() && ! is_multi_author());

		$default_author = ( ! empty( $instance['default_author'] ) ) ? intval( $instance['default_author'] ) : 1;
		$use_post_author = ( ! empty( $instance['use_post_author'] ) ) ? intval( $instance['use_post_author'] ) : 1;

		if ( is_author() ) {
			global $authordata;

			if ( 1 == $use_post_author ) {
				$author_id = $authordata->ID;
			} else if ( $default_author != $authordata->ID ) {
				$include_rel = false;
			}
		} else if ( is_singular() && 1 == $use_post_author ) {
			global $post;
			$author_id = $post->post_author;
		} else {
			$author_id = $default_author;
		}

		echo hcard_user::rel_me_list( $author_id, $include_rel );
	}

	/**
	 * widget data updater
	 *
	 * @param mixed $new_instance new widget data
	 * @param mixed $old_instance current widget data
	 *
	 * @return mixed widget data
	 */
	public function update( $new_instance, $old_instance ) {
		$instance = array();
		$instance['default_author'] = ( ! empty( $new_instance['default_author'] ) ) ? intval( $new_instance['default_author'] ) : 1;

		$instance['use_post_author'] = ( ! empty( $new_instance['use_post_author'] ) ) ? intval( $new_instance['use_post_author'] ) : 1;

		return $instance;
	}

	/**
	 * widget form
	 *
	 * @param mixed $instance
	 *
	 * @output displays the widget form
	 */
	public function form( $instance ) {
		$default_author = ( isset( $instance['default_author'] ) ) ? $instance['default_author'] : 1;
		$use_post_author = ( isset( $instance['use_post_author'] ) ) ? $instance['use_post_author'] : true;

		$users = get_users( array(
			'orderby' => 'ID',
			'fields' => array( 'ID', 'display_name' ),
		));

		?>
		<p>
			<label for="<?php echo $this->get_field_id( 'default_author' ); ?>"><?php _e( 'Default author:', 'indieweb' ); ?></label>
			<select name="<?php echo $this->get_field_id( 'default_author' ); ?>" id="<?php echo $this->get_field_id( 'default_author' ); ?>">
				<?php foreach ( $users as $user ) :   ?>
				<option value="<?php echo $user->ID; ?>" <?php selected( $default_author , $user->ID ); ?>><?php echo $user->display_name; ?></option>
				<?php endforeach; ?>
			</select>
		</p>
		<p>
			<label for="<?php echo $this->get_field_id( 'use_post_author' ); ?>"><?php _e( 'Use post author for rel-me links source on post-like pages instead of default author:', 'indieweb' ); ?></label>
			<input id="<?php echo $this->get_field_id( 'use_post_author' ); ?>" name="<?php echo $this->get_field_name( 'use_post_author' ); ?>" type="checkbox" value="1" <?php checked( $use_post_author ); ?> />
		</p>
		<?php
	}

}
