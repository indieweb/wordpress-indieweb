<?php
/**
 * Indieweb Class.
 *
 * @package Indieweb
 */

namespace Indieweb;

use Indieweb\Hcard\Author_Widget;
use Indieweb\Hcard\User;

/**
 * Indieweb Class.
 *
 * @package Indieweb
 */
class Indieweb {
	/**
	 * Instance of the class.
	 *
	 * @var Indieweb
	 */
	private static $instance;

	/**
	 * Text domain.
	 *
	 * @var string
	 */
	const TEXT_DOMAIN = 'indieweb';

	/**
	 * Whether the class has been initialized.
	 *
	 * @var boolean
	 */
	private $initialized = false;

	/**
	 * Get the instance of the class.
	 *
	 * @return Indieweb
	 */
	public static function get_instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	/**
	 * Do not allow multiple instances of the class.
	 */
	private function __construct() {
		// Do nothing.
	}

	/**
	 * Initialize the plugin.
	 */
	public function init() {
		if ( $this->initialized ) {
			return;
		}

		// Load language files.
		\load_plugin_textdomain( self::TEXT_DOMAIN, false, \dirname( \plugin_basename( INDIEWEB_PLUGIN_FILE ) ) . '/languages' );

		\add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_style' ) );
		\add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_admin_style' ) );

		// Add menu.
		\add_action( 'admin_menu', array( $this, 'add_menu_item' ), 9 );

		// Privacy Declaration.
		\add_action( 'admin_init', array( $this, 'privacy_declaration' ) );

		// General Settings.
		\add_action( 'admin_menu', array( General_Settings::class, 'admin_menu' ) );
		\add_action( 'init', array( General_Settings::class, 'register_settings' ) );
		\add_action( 'admin_menu', array( General_Settings::class, 'admin_settings' ), 11 );

		// Third party integrations.
		\add_action( 'init', array( Integrations::class, 'init' ) );

		// H-Card support.
		\add_action( 'init', array( User::class, 'init' ) );
		\add_action( 'widgets_init', array( User::class, 'init_widgets' ) );
		\add_action( 'widgets_init', array( Author_Widget::class, 'register' ) );

		// We're up and running.
		\do_action( 'indieweb_loaded' );

		$this->initialized = true;
	}

	/**
	 * Get the plugin version.
	 *
	 * @return string
	 */
	public function get_version() {
		return INDIEWEB_VERSION;
	}

	/**
	 * Enqueue frontend styles.
	 */
	public function enqueue_style() {
		if ( '1' === \get_option( 'iw_relme_bw' ) ) {
			\wp_enqueue_style( 'indieweb', INDIEWEB_PLUGIN_URL . 'static/css/indieweb-bw.css', array(), $this->get_version() );
		} else {
			\wp_enqueue_style( 'indieweb', INDIEWEB_PLUGIN_URL . 'static/css/indieweb.css', array(), $this->get_version() );
		}
	}

	/**
	 * Enqueue admin styles.
	 */
	public function enqueue_admin_style() {
		\wp_enqueue_style( 'indieweb-admin', INDIEWEB_PLUGIN_URL . 'static/css/indieweb-admin.css', array(), $this->get_version() );
	}

	/**
	 * Add Top Level Menu Item.
	 */
	public function add_menu_item() {
		\add_menu_page(
			'IndieWeb',
			'IndieWeb',
			'manage_options',
			'indieweb',
			array( $this, 'getting_started' ),
			INDIEWEB_PLUGIN_URL . 'static/img/indieweb.svg'
		);
		\add_submenu_page(
			'indieweb',
			\__( 'Extensions', 'indieweb' ), // Page title.
			\__( 'Extensions', 'indieweb' ), // Menu title.
			'manage_options', // Access capability.
			'indieweb-installer',
			array( $this, 'plugin_installer' )
		);
		$this->change_menu_title();
	}

	/**
	 * Changes the menu title.
	 */
	public function change_menu_title() {
		global $submenu;
		if ( isset( $submenu['indieweb'] ) && \current_user_can( 'manage_options' ) ) {
			// phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited
			$submenu['indieweb'][0][0] = \__( 'Getting Started', 'indieweb' );
		}
	}

	/**
	 * Callback from `add_plugins_page()` that shows the "Getting Started" page.
	 */
	public function getting_started() {
		require_once INDIEWEB_PLUGIN_DIR . '/includes/getting-started.php';
	}

	/**
	 * Render the plugin installer page.
	 */
	public function plugin_installer() {
		echo '<h1>' . \esc_html__( 'IndieWeb Plugin Installer', 'indieweb' ) . '</h1>';
		echo '<p>' . \esc_html__( 'The below plugins are recommended to enable additional IndieWeb functionality.', 'indieweb' ) . '</p>';
		Plugin_Installer::init( $this->register_plugins() );
	}

	/**
	 * Register the required plugins.
	 */
	public function register_plugins() {
		$plugin_array = array(
			array(
				'slug' => 'webmention',
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
				'slug' => 'simple-location',
			),
			array(
				'slug' => 'pubsubhubbub',
			),
			array(
				'slug' => 'indieblocks',
			),
		);
		return $plugin_array;
	}

	/**
	 * Add privacy policy content.
	 */
	public function privacy_declaration() {
		if ( \function_exists( 'wp_add_privacy_policy_content' ) ) {
			$content = \__(
				'Users can optionally add additional information to their profile. As this is part of your user profile you have control of this information and can remove
				it at your discretion.',
				'indieweb'
			);
			\wp_add_privacy_policy_content(
				'Indieweb',
				\wp_kses_post( \wpautop( $content, false ) )
			);
		}
	}
}
