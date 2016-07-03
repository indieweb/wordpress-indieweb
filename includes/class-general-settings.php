<?php

add_action( 'admin_menu', array( 'IndieWeb_General_Settings', 'admin_menu' ) );
add_action( 'admin_menu', array( 'IndieWeb_General_Settings', 'admin_settings' ), 11 );

class IndieWeb_General_Settings {

	public static function admin_menu() {
		$page = 'iw_general_options';
		// Add General Options Page.
		add_submenu_page(
			'indieweb',
			__( 'Options', 'indieweb' ), // page title
			__( 'Options', 'indieweb' ), // menu title
			'manage_options', // access capability
			$page,
			array( 'IndieWeb_General_Settings', 'general_options_page' )
		);
	}

	public static function admin_settings() {
		$page = 'iw_general_options';
		// Settings Section
		$section = 'iw_identity_settings';

		add_settings_section(
			$section, // ID used to identify this section and with which to register options
			'Identity Settings', // Title to be displayed on the administration page
			array( 'IndieWeb_General_Settings', 'identity_options_callback' ), // Callback used to render the description of the section
			$page // Page on which to add this section of options
		);

		register_setting( $section, 'iw_single_author' );

		add_settings_field(
			'iw_single_author', // ID used to identify the field throughout the theme
			'Single Author Site', // The label to the left of the option interface element
			array( 'IndieWeb_General_Settings', 'checkbox_callback' ),   // The name of the function responsible for rendering the option interface
			$page, // The page on which this option will be displayed
			$section, // The name of the section to which this field belongs
			array( // The array of arguments to pass to the callback. In this case, just a description.
				'name' => 'iw_single_author',
				'description' => 'If this website represents a single individual or entity, check this.',
			)
		);

		// Set Default Author
		register_setting( $section, 'iw_default_author' );

		add_settings_field(
			'iw_default_author', // ID used to identify the field throughout the theme
			'Default Author', // The label to the left of the option interface element
			array( 'IndieWeb_General_Settings', 'default_author_callback' ), // The name of the function responsible for rendering the option interface
			$page, // The page on which this option will be displayed
			$section // The name of the section to which this field belongs
		);

		register_setting( $section, 'iw_relmehead' );

	}

	public static function identity_options_callback() {
		echo '<p>';
		esc_html_e( 'Using rel=me on a link indicates the link represents the same person or entity as
				the current page. On a site with a single author, rel=me links from their user profile will
				appear on the homepage. On a site with multiple authors rel=me will appear in the links on the author page only.' , 'indieweb' );
		echo '</p>';
		echo '<p>';
		esc_html_e( 'The Default Author is the one whose that will be used on the home pages and archive pages. If the single author setting is not set,
				on all other pages, the post author links will be used. To display the links, add the
				widget, otherwise they will remain hidden. ', 'indieweb' );
		echo '</p>';
	}

	public static function general_options_page() {
		// If this is not a multi-author site, always set single_author to checked.
		if ( ! is_multi_author() ) {
			update_option( 'iw_single_author', 1 );
		}

		echo '<div class="wrap">';
		echo '	<form method="post" action="options.php">';

		settings_fields( 'iw_identity_settings' );
		do_settings_sections( 'iw_general_options' );

		submit_button();

		echo '	</form>';
		echo '</div>';
	}

	public static function checkbox_callback( array $args ) {
		$option = get_option( $args['name'] );
		$checked = $option;

		echo "<input name='" . $args['name'] . "' type='hidden' value='0' />";
		echo "<input name='" . $args['name'] . "' type='checkbox' value='1' " . checked( 1, $checked, false ) . ' /> ';

		if ( array_key_exists( 'description', $args ) ) {
			echo '<label for="' . $args['name'] . '">' . $args['description'] . '</label>';
		}
	}

	public static function default_author_callback() {
		$users = get_users( array(
			'orderby' => 'ID',
			'fields' => array( 'ID', 'display_name' ),
		));

		$option = get_option( 'iw_default_author' );
		?>

		<select name="iw_default_author">
		<?php foreach ( $users as $user ) :   ?>
			<option value="<?php echo $user->ID; ?>" <?php selected( $option, $user->ID ); ?>><?php echo $user->display_name; ?></option>
		<?php endforeach; ?>
		</select>
	<?php
	}
}
