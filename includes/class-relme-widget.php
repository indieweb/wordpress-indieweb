<?php
/**
 * Rel-Me Widget.
 *
 * @package IndieWeb
 */

/**
 * Adds widget to display rel-me links for IndieAuth with per-user profile support.
 */
class RelMe_Widget extends WP_Widget {

	/**
	 * Widget constructor.
	 */
	public function __construct() {
		parent::__construct(
			'RelMe_Widget',
			__( 'Show My Profiles on Other Sites', 'indieweb' ),
			array(
				'description'           => __( 'Adds automatic rel-me URLs based on default author profile information. Rel=me links are links to your presence on other websites and visually appear like many social link widgets', 'indieweb' ),
				'show_instance_in_rest' => true,
			)
		);
		if ( ! is_active_widget( false, false, $this->id_base ) ) {
			add_action( 'wp_head', array( 'HCard_User', 'relme_head' ) );
		}
	}

	/**
	 * Widget worker.
	 *
	 * @param mixed $args     Widget parameters (unused, required by WP_Widget).
	 * @param mixed $instance Saved widget data (unused, required by WP_Widget).
	 */
	public function widget( $args, $instance ) { // phpcs:ignore VariableAnalysis.CodeAnalysis.VariableAnalysis.UnusedVariable
		global $authordata, $post;

		$default_admin_user = $this->get_default_admin_author_id();

		$single_author = get_option( 'iw_single_author', is_multi_author() ? '0' : '1' );
		$author_id     = get_option( 'iw_default_author', $default_admin_user );
		$include_rel   = false;
		if ( is_front_page() && '1' === $single_author ) {
			$include_rel = true;
		}
		if ( is_author() ) {
			$author_id = ( $authordata instanceof WP_User ) ? $authordata->ID : $author_id;
			if ( 0 === (int) $single_author ) {
				$include_rel = true;
			}
		}
		if ( is_singular() && '0' === $single_author ) {
				$author_id = $post->post_author;
		}

		echo HCard_User::rel_me_list( $author_id, $include_rel ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	}

	/**
	 * Widget data updater.
	 *
	 * @param mixed $new_instance New widget data.
	 * @param mixed $old_instance Current widget data (unused, required by WP_Widget).
	 *
	 * @return mixed Widget data.
	 */
	public function update( $new_instance, $old_instance ) { // phpcs:ignore VariableAnalysis.CodeAnalysis.VariableAnalysis.UnusedVariable
		return $new_instance;
	}

	/**
	 * Widget form.
	 *
	 * @param mixed $instance Widget instance (unused, required by WP_Widget).
	 */
	public function form( $instance ) { // phpcs:ignore VariableAnalysis.CodeAnalysis.VariableAnalysis.UnusedVariable
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
