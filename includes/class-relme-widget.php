<?php

/**
 * adds widget to display rel-me links for indieauth with per-user profile support
 */
class RelMe_Widget extends WP_Widget {

	/**
	 * widget constructor
	 */
	public function __construct() {
		parent::__construct(
			'RelMe_Widget',
			__( 'Rel=Me Links', 'indieweb' ),
			array(
				'description' => __( 'Adds automatic rel-me URLs based on default author profile information. Rel=me links are links to your presence on other websites and visually appear like many social link widgets', 'indieweb' ),
			)
		);
		if ( ! is_active_widget( false, false, $this->id_base ) ) {
			add_action( 'wp_head', array( 'HCard_User', 'relme_head' ) );
		}
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

		$default_admin_user = $this->get_default_admin_author_id();

		$single_author = get_option( 'iw_single_author', is_multi_author() ? '0' : '1' );
		$author_id     = get_option( 'iw_default_author', $default_admin_user ); // Set the author ID to default.
		$include_rel   = false;
		if ( is_front_page() && '1' === $single_author ) {
			$include_rel = true;
		}
		if ( is_author() ) {
			global $authordata;
			$author_id = ( $authordata instanceof WP_User ) ? $authordata->ID : $author_id;
			if ( 0 === (int) $single_author ) {
				$include_rel = true;
			}
		}
		if ( is_singular() && '0' === $single_author ) {
				global $post;
				$author_id = $post->post_author;
		}

		echo hcard_user::rel_me_list( $author_id, $include_rel ); // phpcs:ignore
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
		return $new_instance;
	}

	/**
	 * widget form
	 *
	 * @param mixed $instance
	 *
	 * @output displays the widget form
	 */
	public function form( $instance ) {
		echo '<p>';
		esc_html_e( 'Displays rel=me links which appear as icons with the logo of the site linked to when possible', 'indieweb' );
		echo '</p>';
	}

	/**
	 * Fetch the first administrator ID.
	 *
	 * @return int Administrator user ID.
	 */
	public function get_default_admin_author_id() {
		$users = get_users(
			array(
				'role'   => 'administrator',
				'number' => 1,
				'fields' => 'ID',
			)
		);

		return $users[0];
	}
}
