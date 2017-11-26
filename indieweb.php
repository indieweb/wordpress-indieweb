<?php
/*
 * Plugin Name: IndieWeb
 * Plugin URI: https://github.com/indieweb/wordpress-indieweb
 * Description: Interested in connecting your WordPress site to the IndieWeb?
 * Author: IndieWebCamp WordPress Outreach Club
 * Author URI: https://indieweb.org/WordPress_Outreach_Club
 * Version: 3.3.3
 * License: MIT
 * License URI: http://opensource.org/licenses/MIT
 * Text Domain: indieweb
 * Domain Path: /languages
 */

// initialize plugin
add_action( 'plugins_loaded', array( 'IndieWeb_Plugin', 'init' ) );

defined( 'INDIEWEB_ADD_HCARD_SUPPORT' ) || define( 'INDIEWEB_ADD_HCARD_SUPPORT', true );
defined( 'INDIEWEB_ADD_RELME_SUPPORT' ) || define( 'INDIEWEB_ADD_RELME_SUPPORT', true );
define( 'CNKT_INSTALLER_PATH', plugins_url( '/', __FILE__ ) );

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

		require_once dirname( __FILE__ ) . '/includes/class-connekt-plugin-installer.php';

		if ( INDIEWEB_ADD_HCARD_SUPPORT ) {
			// Require H-Card Enhancements to User Profile
			require_once dirname( __FILE__ ) . '/includes/class-hcard-user.php';
			require_once dirname( __FILE__ ) . '/includes/class-hcard-author-widget.php';

		}

		if ( INDIEWEB_ADD_RELME_SUPPORT ) {
			// Require Rel Me Widget Class
			require_once dirname( __FILE__ ) . '/includes/class-relme-widget.php';
		}

		add_action( 'wp_enqueue_scripts', array( 'IndieWeb_Plugin', 'enqueue_style' ) );

		// Add General Settings Page
		require_once dirname( __FILE__ ) . '/includes/class-general-settings.php';

		// Add third party integrations
		require_once dirname( __FILE__ ) . '/includes/class-integrations.php';

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
			false,
			dirname( plugin_basename( __FILE__ ) ) . '/languages/' // path
		);
	}

	public static function enqueue_style() {
		wp_enqueue_style( 'indieweb', plugins_url( 'static/css/indieweb.css', __FILE__ ), array() );
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
			plugins_url( 'static/img/indieweb.svg', __FILE__ )
		);
		add_submenu_page(
			'indieweb',
			__( 'Extensions', 'indieweb' ), // page title
			__( 'Extensions', 'indieweb' ), // menu title
			'manage_options', // access capability
			'indieweb-installer',
			array( 'IndieWeb_Plugin', 'plugin_installer' )
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

	public static function plugin_installer() {
		echo '<h1>' . __( 'IndieWeb Plugin Installer', 'indieweb' ) . '</h1>';
		echo '<p>' . __( 'The below plugins are recommended to enable additional IndieWeb functionality.', 'indieweb' ) . '</p>';
		if ( class_exists( 'Connekt_Plugin_Installer' ) ) {
			Connekt_Plugin_Installer::init( self::register_plugins() );
		}
	}

	/**
	 * Register the required plugins.
	 *
	 *
	 */
	public static function register_plugins() {
		$plugin_array = array(
			array(
				'slug' => 'webmention',
			),
			array(
				'slug' => 'semantic-linkbacks',
			),
			array(
				'slug' => 'micropub',
			),
			array(
				'slug' => 'indieweb-post-kinds',
			),
			array(
				'slug' => 'syndication-links',
			),
			array(
				'slug' => 'bridgy-publish',
			),
			array(
				'slug' => 'indieauth',
			),
			array(
				'slug' => 'wp-uf2',
			),
			array(
				'slug' => 'indieweb-press-this',
			),
			array(
				'slug' => 'simple-location',
			),
		);
		return $plugin_array;
	}

} // end class IndieWeb_Plugin
