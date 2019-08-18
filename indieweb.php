<?php
/*
 * Plugin Name: IndieWeb
 * Plugin URI: https://github.com/indieweb/wordpress-indieweb
 * Description: Interested in connecting your WordPress site to the IndieWeb?
 * Author: IndieWebCamp WordPress Outreach Club
 * Author URI: https://indieweb.org/WordPress_Outreach_Club
 * Version: 3.4.1
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

	public static $version = '3.4.1';

	/**
	 * Initialize the plugin, registering WordPress hooks.
	 */
	public static function init() {
		// enable translation
		self::enable_translation();

		require_once dirname( __FILE__ ) . '/includes/class-plugin-installer.php';

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

		add_action( 'admin_enqueue_scripts', array( 'IndieWeb_Plugin', 'enqueue_admin_style' ) );

		// Add General Settings Page
		require_once dirname( __FILE__ ) . '/includes/class-general-settings.php';

		// Add third party integrations
		require_once dirname( __FILE__ ) . '/includes/class-integrations.php';

		// add menu
		add_action( 'admin_menu', array( 'IndieWeb_Plugin', 'add_menu_item' ), 9 );

		// Privacy Declaration
		add_action( 'admin_init', array( 'Indieweb_Plugin', 'privacy_declaration' ) );

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
			'indieweb',
			false,
			dirname( plugin_basename( __FILE__ ) ) . '/languages/' // path
		);
	}

	public static function enqueue_style() {
		if ( '1' === get_option( 'iw_relme_bw' ) ) {
			wp_enqueue_style( 'indieweb', plugins_url( 'static/css/indieweb-bw.css', __FILE__ ), array(), self::$version );
		} else {
			wp_enqueue_style( 'indieweb', plugins_url( 'static/css/indieweb.css', __FILE__ ), array(), self::$version );
		}
	}

	public static function enqueue_admin_style() {
		wp_enqueue_style( 'indieweb-admin', plugins_url( 'static/css/indieweb-admin.css', __FILE__ ), array(), self::$version );
	}

	/**
	 * Add Top Level Menu Item
	 */
	public static function add_menu_item() {
		$options_page = add_menu_page(
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
		self::change_menu_title();
	}

	/**
	 * Changes the menu title
	 */
	public static function change_menu_title() {
		global $submenu;
		if ( isset( $submenu['indieweb'] ) && current_user_can( 'manage_options' ) ) {
			// phpcs:ignore
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
		echo '<h1>' . esc_html__( 'IndieWeb Plugin Installer', 'indieweb' ) . '</h1>';
		echo '<p>' . esc_html__( 'The below plugins are recommended to enable additional IndieWeb functionality.', 'indieweb' ) . '</p>';
		if ( class_exists( 'IndieWeb_Plugin_Installer' ) ) {
			IndieWeb_Plugin_Installer::init( self::register_plugins() );
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
				'slug' => 'indieauth',
			),
			array(
				'slug' => 'wp-uf2',
			),
			array(
				'slug' => 'simple-location',
			),
			array(
				'slug' => 'pubsubhubbub',
			),
			array(
				'slug' => 'classic-editor',
			),
		);
		return $plugin_array;
	}

	public static function privacy_declaration() {
		if ( function_exists( 'wp_add_privacy_policy_content' ) ) {
			$content = __(
				'Users can optionally add additional information to their profile. As this is part of your user profile you have control of this information and can remove
				it at your discretion.',
				'indieweb'
			);
			wp_add_privacy_policy_content(
				'Indieweb',
				wp_kses_post( wpautop( $content, false ) )
			);
		}
	}
}
