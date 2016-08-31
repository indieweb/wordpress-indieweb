<?php
/*
Plugin Name: IndieWeb
Plugin URI: https://github.com/indieweb/wordpress-indieweb
Description: Interested in connecting your WordPress site to the IndieWeb?
Author: IndieWebCamp WordPress Outreach Club
Author URI: http://indiewebcamp.com/WordPress_Outreach_Club
Version: 3.0.5
Text Domain: indieweb
Domain Path: /languages
*/

// initialize plugin
add_action( 'plugins_loaded', array( 'IndieWeb_Plugin', 'init' ) );

/**
 * IndieWeb Plugin Class
 *
 * @author Matthias Pfefferle
 */
class IndieWeb_Plugin {

	/**
	 * Initialize the plugin, registering WordPress hooks.
	 */
	public static function init() {
		// enable translation
		self::enable_translation();

		// include the TGM_Plugin_Activation class
		require_once dirname( __FILE__ ) . '/includes/class-tgm-plugin-activation.php';

		// Require H-Card Enhancements to User Profile
		require_once dirname( __FILE__ ) . '/includes/class-hcard-user.php';

		// Require Rel Me Widget Class
		require_once dirname( __FILE__ ) . '/includes/class-relme-widget.php';
		add_action( 'wp_enqueue_scripts', array( 'IndieWeb_Plugin', 'enqueue_style' ) );

		// Add General Settings Page
		require_once dirname( __FILE__ ) . '/includes/class-general-settings.php';

		// register TGM hooks
		add_action( 'tgmpa_register', array( 'IndieWeb_Plugin', 'register_required_plugins' ) );

		// add menu
		add_action( 'admin_menu', array( 'IndieWeb_Plugin', 'add_menu_item' ), 9 );
		add_action( 'admin_menu', array( 'IndieWeb_Plugin', 'change_menu_title' ), 12 );

		// we're up and running
		do_action( 'indieweb_loaded' );
	}

	/**
	 * Load translation files.
	 *
	 * A good reference on how to implement translation in WordPress:
	 * http://ottopress.com/2012/internationalization-youre-probably-doing-it-wrong/
	 */
	public static function enable_translation() {
		// for plugins
		load_plugin_textdomain(
			'indieweb', // unique slug
			false, // deprecated
			dirname( plugin_basename( __FILE__ ) ) . '/languages/' // path
		);
	}

	public static function enqueue_style() {
		wp_enqueue_style( 'indieweb', plugin_dir_url( __FILE__ ) . 'css/indieweb.css', array() );
	}


	/**
	 * Add Top Level Menu Item
	 */
	public static function add_menu_item() {
		add_menu_page(
			'IndieWeb',
			'IndieWeb',
			'manage_options',
			'indieweb',
			array( 'IndieWeb_Plugin', 'getting_started' ),
			'dashicons-share-alt'
		);
	}

	/**
	 * Changes the menu title
	 */
	public static function change_menu_title() {
		global $submenu;
		if ( isset( $submenu['indieweb'] ) && current_user_can( 'manage_options' ) ) {
			$submenu['indieweb'][0][0] = __( 'Getting Started', 'indieweb' );
		}
	}

	/**
	 * Callback from `add_plugins_page()` that shows the "Getting Started" page.
	 */
	public static function getting_started() {
		require_once dirname( __FILE__ ) . '/includes/getting-started.php';
	}

	/**
	 * Register the required plugins.
	 *
	 * This function is hooked into tgmpa_init, which is fired within the
	 * TGM_Plugin_Activation class constructor.
	 */
	public static function register_required_plugins() {
		/**
		 * Array of plugin arrays. Required keys are name and slug.
		 * If the source is NOT from the .org repo, then source is also required.
		 */
		$plugins = array(

			// require the WebMention plugin
			array(
				'name'          => __( 'Webmention Support', 'indieweb' ),
				'slug'          => 'webmention',
				'required'      => true,
			),

			// require the Semantic Linkbacks plugin
			array(
				'name'          => __( 'Semantic Linkbacks - More Meaningful Linkbacks', 'indieweb' ),
				'slug'          => 'semantic-linkbacks',
				'required'      => true, // If false, the plugin is only 'recommended' instead of required.
			),

			// recommend the MicroPub server plugin
			array(
				'name'          => __( 'Publish to Your Site Using Micropub', 'indieweb' ),
				'slug'          => 'micropub',
				'required'      => false, // If false, the plugin is only 'recommended' instead of required.
			),

			// recommend the Hum URL shortener
			array(
				'name'          => __( 'Hum (URL shortener)', 'indieweb' ),
				'slug'          => 'hum',
				'required'      => false,
			),

			// recommend the WebActions plugin
			array(
				'name'          => __( 'WebActions', 'indieweb' ),
				'slug'          => 'wordpress-webactions-master',
				'source'        => 'https://github.com/pfefferle/wordpress-webactions/archive/master.zip',
				'required'      => false,
				'external_url'  => 'https://github.com/pfefferle/wordpress-webactions',
			),

			// recommend the IndieWeb Press-This plugin
			array(
				'name'          => __( 'IndieWeb Enhancements to Press-This', 'indieweb' ),
				'slug'          => 'wordpress-indieweb-press-this-master',
				'source'        => 'https://github.com/pfefferle/wordpress-indieweb-press-this/archive/master.zip',
				'required'      => false,
				'external_url'  => 'https://github.com/pfefferle/wordpress-indieweb-press-this',
			),

			// recommend the "WebMention for Comments" plugin
			array(
				'name'          => __( 'WebMention support for (threaded) comments', 'indieweb' ),
				'slug'          => 'wordpress-webmention-for-comments-master',
				'source'        => 'https://github.com/pfefferle/wordpress-webmention-for-comments/archive/master.zip',
				'required'      => false,
				'external_url'  => 'https://github.com/pfefferle/wordpress-webmention-for-comments',
			),

			// recommend the Post Kinds plugin
			array(
				'name'          => __( 'Post Kinds - adds support for responding to and interacting with
					other sites ', 'indieweb' ),
				'slug'          => 'indieweb-post-kinds',
				'required'      => false,
			),

			// recommend the Syndication Links plugin
			array(
				'name'          => __( 'Syndication Links - link to copies of your posts elsewhere', 'indieweb' ),
				'slug'          => 'syndication-links',
				'required'      => false,
			),

			// recommend the Indieauth plugin
			array(
				'name'          => __( 'Log into your site using Indieauth', 'indieweb' ),
				'slug'          => 'indieauth',
				'required'      => false,
			),
		);

		/**
		 * Array of configuration settings. Amend each line as needed.
		 * If you want the default strings to be available under your own theme domain,
		 * leave the strings uncommented.
		 * Some of the strings are added into a sprintf, so see the comments at the
		 * end of each line for what each argument will be.
		 */
		$config = array(
			'id'           => 'indieweb-installer',    // Unique ID for hashing notices for multiple instances of TGMPA.
			'capability'   => 'install_plugins',			//
			'default_path' => '',                      // Default absolute path to pre-packaged plugins.
			'menu'         => 'indieweb-installer',    // Menu slug.
			'parent_slug'  => 'indieweb',
			'has_notices'  => true,                    // Show admin notices or not.
			'dismissable'  => true,                    // If false, a user cannot dismiss the nag message.
			'dismiss_msg'  => '',                      // If 'dismissable' is false, this message will be output at top of nag.
			'is_automatic' => true,                   // Automatically activate plugins after installation or not.
			'message'      => '', // Message to output right before the plugins table.
			'strings'      => array(
				'page_title'                      => __( 'Install IndieWeb Plugins', 'indieweb' ),
				'page_title'                      => __( 'Install IndieWeb Plugins', 'indieweb' ),
				'menu_title'                      => __( 'Extensions', 'indieweb' ),
				'installing'                      => __( 'Installing Plugin: %s', 'indieweb' ), // %s = plugin name.
				'oops'                            => __( 'Something went wrong with the plugin install.', 'indieweb' ),
				'notice_can_install_required'     => _n_noop( 'The following plugin
					is required to send and receive webmentions: %1$s.', 'The following plugins are required to send and receive webmentions: %1$s.', 'indieweb' ), // %1$s = plugin name(s).
				'notice_can_install_recommended'  => _n_noop( 'To do more, add this plugin: %1$s.', 'To do
					more, install these plugins: %1$s.', 'indieweb' ), // %1$s = plugin name(s).
				'notice_cannot_install'           => _n_noop( 'Sorry, but you do not have the correct permissions to install the %s plugin. Contact the administrator of this site for help on getting the plugin installed.', 'Sorry, but you do not have the correct permissions to install the %s plugins. Contact the administrator of this site for help on getting the plugins installed.', 'indieweb' ), // %1$s = plugin name(s).
				'notice_can_activate_required'    => _n_noop( 'The following required plugin is currently inactive: %1$s.', 'The following required plugins are currently inactive: %1$s.', 'indieweb' ), // %1$s = plugin name(s).
				'notice_can_activate_recommended' => _n_noop( 'The following recommended plugin is currently inactive: %1$s.', 'The following recommended plugins are currently inactive: %1$s.', 'indieweb' ), // %1$s = plugin name(s).
				'notice_cannot_activate'          => _n_noop( 'Sorry, but you do not have the correct permissions to activate the %s plugin. Contact the administrator of this site for help on getting the plugin activated.', 'Sorry, but you do not have the correct permissions to activate the %s plugins. Contact the administrator of this site for help on getting the plugins activated.', 'indieweb' ), // %1$s = plugin name(s).
				'notice_ask_to_update'            => _n_noop( 'The following plugin should be updated to its
					latest version to ensure maximum compatibility: %1$s.', 'The following plugins should be updated to their latest version to ensure maximum compatibility: %1$s.', 'indieweb' ), // %1$s = plugin name(s).
				'notice_cannot_update'            => _n_noop( 'Sorry, but you do not have the correct permissions to update the %s plugin. Contact the administrator of this site for help on getting the plugin updated.', 'Sorry, but you do not have the correct permissions to update the %s plugins. Contact the administrator of this site for help on getting the plugins updated.', 'indieweb' ), // %1$s = plugin name(s).
				'install_link'                    => _n_noop( 'Install plugin', 'Install plugins', 'indieweb' ),
				'activate_link'                   => _n_noop( 'Activate plugin', 'Activate plugins', 'indieweb' ),
				'return'                          => __( 'Return to IndieWeb Extensions', 'indieweb' ),
				'plugin_activated'                => __( 'Plugin activated successfully.', 'indieweb' ),
				'complete'                        => __( 'All plugins installed and activated successfully. %s', 'indieweb' ), // %s = dashboard link.
				'nag_type'                        => 'update-nag',// Determines admin notice type - can only be 'updated', 'update-nag' or 'error'.
			),
		); // end config array

		// call TGM with filtered arrays
		tgmpa(
			apply_filters( 'indieweb_tgm_plugins', $plugins ),
			apply_filters( 'indieweb_tgm_config', $config )
		);
	}
} // end class IndieWeb_Plugin
