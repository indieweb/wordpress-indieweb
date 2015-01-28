<?php
/*
Plugin Name: IndieWeb
Plugin URI: https://github.com/indieweb/wordpress-indieweb
Description: Interested in connecting your WordPress site to the Indieweb? Get the right plugins to do so.
Author: IndieWebCamp WordPress Outreach Club
Author URI: http://indiewebcamp.com/WordPress_Outreach_Club
Version: 2.1.0
Text Domain: indieweb
Domain Path: /languages
*/



// initialize plugin
add_action( 'plugins_loaded', array( 'IndieWebPlugin', 'init' ) );



/**
 * IndieWeb Plugin Class
 *
 * @author Matthias Pfefferle
 */
class IndieWebPlugin {

	/**
	 * Initialize the plugin, registering WordPress hooks.
	 */
	public static function init() {

		// enable translation
		self::enable_translation();

		// include the TGM_Plugin_Activation class
		require_once dirname( __FILE__ ) . '/class-tgm-plugin-activation.php';

		// register TGM hooks
		add_action( 'tgmpa_register', array( 'IndieWebPlugin', 'register_required_plugins' ) );
		add_filter( 'tgmpa_admin_menu_use_add_theme_page', '__return_false' );

		// add menu
		add_action( 'admin_menu', array( 'IndieWebPlugin', 'add_menu_item' ) );

		// show a link to the "Getting Started" page
		$plugin = plugin_basename( __FILE__ );
		add_filter( "plugin_action_links_$plugin", array( 'IndieWebPlugin', 'plugin_link' ) );

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

	/**
	 * Add menu item to "Plugins" top-level menu.
	 */
	public static function add_menu_item() {

		// add to Plugins top-level menu
		add_plugins_page(
			__( 'IndieWeb', 'indieweb' ), // page title
			__( 'IndieWeb', 'indieweb' ), // menu title
			'manage_options', // access capability
			'indieweb', // menu slug
			array( 'IndieWebPlugin', 'getting_started' ) // callback
		);

	}

	/**
	 * Callback from `add_plugins_page()` that shows the "Getting Started" page.
	 */
	public static function getting_started() {
		require_once dirname( __FILE__ ) . '/getting_started.php';
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
				'name'               => __( 'WebMention', 'indieweb' ),
				'slug'               => 'webmention',
				'required'           => true,
			),

			// require the Semantic Linkbacks plugin
			array(
				'name'               => __( 'Semantic Linkbacks', 'indieweb' ),
				'slug'               => 'semantic-linkbacks',
				'required'           => true, // If false, the plugin is only 'recommended' instead of required.
			),

			// recommend the Hum URL shortener
			array(
				'name'      => __( 'Hum (URL shortener)', 'indieweb' ),
				'slug'      => 'hum',
				'required'  => false,
			),

			// recommend the WebActions plugin
			array(
				'name'          => __( 'WebActions', 'indieweb' ),
				'slug'          => 'wordpress-webactions-master',
				'source'        => 'https://github.com/pfefferle/wordpress-webactions/archive/master.zip',
				'required'      => false,
				'external_url'  => 'https://github.com/pfefferle/wordpress-webactions'
			),

			// recommend the IndieWeb Press-This plugin
			array(
				'name'          => __( 'IndieWeb Press-This', 'indieweb' ),
				'slug'          => 'wordpress-indieweb-press-this-master',
				'source'        => 'https://github.com/pfefferle/wordpress-indieweb-press-this/archive/master.zip',
				'required'      => false,
				'external_url'  => 'https://github.com/pfefferle/wordpress-indieweb-press-this'
			),

			// recommend the "WebMention for Comments" plugin
			array(
				'name'          => __( 'WebMention support for (threaded) comments', 'indieweb' ),
				'slug'          => 'wordpress-webmention-for-comments-master',
				'source'        => 'https://github.com/pfefferle/wordpress-webmention-for-comments/archive/master.zip',
				'required'      => false,
				'external_url'  => 'https://github.com/pfefferle/wordpress-webmention-for-comments'
			),

			// recommend the Post Kinds plugin
			array(
				'name'          => __( 'Post Kinds', 'indieweb' ),
				'slug'          => 'indieweb-post-kinds-master',
				'source'        => 'https://github.com/dshanske/indieweb-post-kinds/archive/master.zip',
				'required'      => false,
				'external_url'  => 'https://github.com/dshanske/indieweb-post-kinds'
			),

			// recommend the Syndication Links plugin
			array(
				'name'          => __( 'Syndication Links', 'indieweb' ),
				'slug'          => 'syndication-links-master',
				'source'        => 'https://github.com/dshanske/syndication-links/archive/master.zip',
				'required'      => false,
				'external_url'  => 'https://github.com/dshanske/syndication-links'
			),

			// recommend the WordPress Syndication plugin
			array(
				'name'          => __( 'WordPress Syndication', 'indieweb' ),
				'slug'          => 'wordpress-syndication-master',
				'source'        => 'https://github.com/jihaisse/wordpress-syndication/archive/master.zip',
				'required'      => false,
				'external_url'  => 'https://github.com/jihaisse/wordpress-syndication'
			),

			// recommend the Indieauth plugin
			array(
				'name'          => __( 'Indieauth', 'indieweb' ),
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
			'default_path' => '',                      // Default absolute path to pre-packaged plugins.
			'menu'         => 'indieweb-installer',    // Menu slug.
			'has_notices'  => true,                    // Show admin notices or not.
			'dismissable'  => true,                    // If false, a user cannot dismiss the nag message.
			'dismiss_msg'  => '',                      // If 'dismissable' is false, this message will be output at top of nag.
			'is_automatic' => false,                   // Automatically activate plugins after installation or not.
			'message'      => __( 'For descriptions of the plugins and more information, visit <a href="plugins.php?page=indieweb">Getting Started</a>', 'indieweb' ), // Message to output right before the plugins table.
			'strings'      => array(
				'page_title'                      => __( 'Install Indieweb Plugins', 'indieweb' ),
				'menu_title'                      => __( 'IndieWeb Plugin Installer', 'indieweb' ),
				'installing'                      => __( 'Installing Plugin: %s', 'indieweb' ), // %s = plugin name.
				'oops'                            => __( 'Something went wrong with the plugin install.', 'indieweb' ),
				'notice_can_install_required'     => _n_noop( 'The IndieWeb plugin requires the following plugin: %1$s.', 'The IndieWeb plugin requires the following plugins: %1$s.', 'indieweb' ), // %1$s = plugin name(s).
				'notice_can_install_recommended'  => _n_noop( 'The IndieWeb plugin recommends the following plugin: %1$s.', 'The IndieWeb plugin recommends the following plugins: %1$s.', 'indieweb' ), // %1$s = plugin name(s).
				'notice_cannot_install'           => _n_noop( 'Sorry, but you do not have the correct permissions to install the %s plugin. Contact the administrator of this site for help on getting the plugin installed.', 'Sorry, but you do not have the correct permissions to install the %s plugins. Contact the administrator of this site for help on getting the plugins installed.', 'indieweb' ), // %1$s = plugin name(s).
				'notice_can_activate_required'    => _n_noop( 'The following required plugin is currently inactive: %1$s.', 'The following required plugins are currently inactive: %1$s.', 'indieweb' ), // %1$s = plugin name(s).
				'notice_can_activate_recommended' => _n_noop( 'The following recommended plugin is currently inactive: %1$s.', 'The following recommended plugins are currently inactive: %1$s.', 'indieweb' ), // %1$s = plugin name(s).
				'notice_cannot_activate'          => _n_noop( 'Sorry, but you do not have the correct permissions to activate the %s plugin. Contact the administrator of this site for help on getting the plugin activated.', 'Sorry, but you do not have the correct permissions to activate the %s plugins. Contact the administrator of this site for help on getting the plugins activated.', 'indieweb' ), // %1$s = plugin name(s).
				'notice_ask_to_update'            => _n_noop( 'The following plugin needs to be updated to its latest version to ensure maximum compatibility with this plugin: %1$s.', 'The following plugins need to be updated to their latest version to ensure maximum compatibility with this theme: %1$s.', 'indieweb' ), // %1$s = plugin name(s).
				'notice_cannot_update'            => _n_noop( 'Sorry, but you do not have the correct permissions to update the %s plugin. Contact the administrator of this site for help on getting the plugin updated.', 'Sorry, but you do not have the correct permissions to update the %s plugins. Contact the administrator of this site for help on getting the plugins updated.', 'indieweb' ), // %1$s = plugin name(s).
				'install_link'                    => _n_noop( 'Begin installing plugin', 'Begin installing plugins', 'indieweb' ),
				'activate_link'                   => _n_noop( 'Begin activating plugin', 'Begin activating plugins', 'indieweb' ),
				'return'                          => __( 'Return to Indieweb Plugins Installer', 'indieweb' ),
				'plugin_activated'                => __( 'Plugin activated successfully.', 'indieweb' ),
				'complete'                        => __( 'All plugins installed and activated successfully. %s', 'indieweb' ), // %s = dashboard link.
				'nag_type'                        => 'updated' // Determines admin notice type - can only be 'updated', 'update-nag' or 'error'.
			)

		); // end config array

		// call TGM with filtered arrays
		tgmpa(
			apply_filters( 'indieweb_tgm_plugins', $plugins ),
			apply_filters( 'indieweb_tgm_config', $config )
		);

	}

	/**
	 * Show a link to the "Getting Started" page
	 *
	 * @param array $links The existing plugin links array
	 * @return array $links The modified plugin links array
	 */
	public static function plugin_link( $links ) {
		$settings_link = '<a href="' . admin_url( 'plugins.php?page=indieweb' ) . '">' . __( 'Getting Started', 'indieweb' ) . '</a>';
		array_unshift( $links, $settings_link);
		return $links;
	}

} // end class IndieWebPlugin
