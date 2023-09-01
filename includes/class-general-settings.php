<?php

add_action( 'admin_menu', array( 'IndieWeb_General_Settings', 'admin_menu' ) );
add_action( 'init', array( 'IndieWeb_General_Settings', 'register_settings' ) );
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

	public static function register_settings() {
		$section = 'iw_identity_settings';
		register_setting(
			$section,
			'iw_single_author',
			array(
				'type'         => 'boolean',
				'description'  => __( 'Single Author Site', 'indieweb' ),
				'show_in_rest' => true,
				'default'      => is_multi_author() ? 0 : 1,
			)
		);

		// Set Default Author
		register_setting(
			$section,
			'iw_default_author',
			array(
				'type'         => 'integer',
				'description'  => __( 'Default Author ID for this Site', 'indieweb' ),
				'show_in_rest' => true,
				'default'      => 1,
			)
		);

		register_setting(
			$section,
			'iw_author_url',
			array(
				'type'         => 'boolean',
				'description'  => __( 'Replace Author URL with User Website URL', 'indieweb' ),
				'show_in_rest' => true,
				'default'      => 1,
			)
		);

		register_setting(
			$section,
			'iw_relme_bw',
			array(
				'type'         => 'boolean',
				'description'  => __( 'Black and White Rel-Me Icons', 'indieweb' ),
				'show_in_rest' => true,
				'default'      => 0,
			)
		);
	}

	public static function admin_settings() {
		$page = 'iw_general_options';
		// Settings Section
		$section = 'iw_identity_settings';

		add_settings_section(
			$section, // ID used to identify this section and with which to register options
			__( 'Identity Settings', 'indieweb' ), // Title to be displayed on the administration page
			array( 'IndieWeb_General_Settings', 'identity_options_callback' ), // Callback used to render the description of the section
			$page // Page on which to add this section of options
		);

		add_settings_field(
			'iw_single_author', // ID used to identify the field throughout the theme
			'Single Author Site', // The label to the left of the option interface element
			array( 'IndieWeb_General_Settings', 'checkbox_callback' ),   // The name of the function responsible for rendering the option interface
			$page, // The page on which this option will be displayed
			$section, // The name of the section to which this field belongs
			array( // The array of arguments to pass to the callback. In this case, just a description.
				'name'        => 'iw_single_author',
				'description' => __( 'If this website represents a single individual or entity, check this. This setting is disabled if you only have one user who has made a post.', 'indieweb' ),
				'disabled'    => ! is_multi_author(),
			)
		);

		add_settings_field(
			'iw_default_author', // ID used to identify the field throughout the theme
			'Default Author', // The label to the left of the option interface element
			array( 'IndieWeb_General_Settings', 'default_author_callback' ), // The name of the function responsible for rendering the option interface
			$page, // The page on which this option will be displayed
			$section // The name of the section to which this field belongs
		);

		add_settings_field(
			'iw_author_url', // ID used to identify the field throughout the theme
			__( 'Use User Website URL for Author', 'indieweb' ), // The label to the left of the option interface element
			array( 'IndieWeb_General_Settings', 'checkbox_callback' ),   // The name of the function responsible for rendering the option interface
			$page, // The page on which this option will be displayed
			$section, // The name of the section to which this field belongs
			array( // The array of arguments to pass to the callback. In this case, just a description.
				'name'        => 'iw_author_url',
				'description' => __( 'If checked, this will replace the author page URL with the website URL from your user profile.', 'indieweb' ),
				'disabled'    => false,
			)
		);

		add_settings_field(
			'iw_relme_bw', // ID used to identify the field throughout the theme
			__( 'Black and White Icons', 'indieweb' ), // The label to the left of the option interface element
			array( 'IndieWeb_General_Settings', 'checkbox_callback' ),   // The name of the function responsible for rendering the option interface
			$page, // The page on which this option will be displayed
			$section, // The name of the section to which this field belongs
			array( // The array of arguments to pass to the callback. In this case, just a description.
				'name'        => 'iw_relme_bw',
				'description' => __( 'If checked, the icon colors will not be loaded', 'indieweb' ),
				'disabled'    => false,
			)
		);
	}


	public static function identity_options_callback() {
		echo '<p>';
		esc_html_e(
			'Using rel=me on a link indicates the link represents the same person or entity as
				the current page. On a site with a single author, links to other profiles from their user profile will
				appear on the homepage. On a site with multiple authors these links will appear on the author page only.',
			'indieweb'
		);
		echo '</p>';
		echo '<p>';
		esc_html_e(
			'The Default Author is the one whose that will be used on the home pages and archive pages.  If the single author setting is not set,
				on all other pages, the post author links will be used. To display the links, add the
				widget, otherwise they will remain hidden. ',
			'indieweb'
		);
		echo '</p>';
	}

	public static function general_options_page() {
		// If this is not a multi-author site, remove the single author setting
		if ( ! is_multi_author() ) {
			delete_option( 'iw_single_author' );
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
		$option   = get_option( $args['name'] );
		$disabled = isset( $args['disabled'] ) ? $args['disabled'] : false;

		$checked = $option;

		echo "<input name='" . esc_html( $args['name'] ) . "' type='hidden' value='0' />";
		echo "<input name='" . esc_html( $args['name'] ) . "' type='checkbox' value='1' " . checked( $checked, 1, false ) . ( $disabled ? ' disabled ' : ' ' ) . '/> ';

		if ( array_key_exists( 'description', $args ) ) {
			echo '<label for="' . esc_html( $args['name'] ) . '">' . esc_html( $args['description'] ) . '</label>';
		}
	}

	public static function default_author_callback() {
		$users = get_users(
			array(
				'orderby' => 'ID',
				'fields'  => array( 'ID', 'display_name' ),
			)
		);

		$option = get_option( 'iw_default_author' );
		?>

		<select name="iw_default_author">
		<?php foreach ( $users as $user ) : ?>
			<option value="<?php echo absint( $user->ID ); ?>" <?php selected( $option, $user->ID ); ?>>
				<?php
				echo esc_html( $user->display_name );
				?>
			</option>
		<?php endforeach; ?>
		</select>
		<?php
	}
}
