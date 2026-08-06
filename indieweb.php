<?php
/**
 * Plugin Name: IndieWeb
 * Plugin URI: https://github.com/indieweb/wordpress-indieweb
 * Description: Interested in connecting your WordPress site to the IndieWeb?
 * Author: IndieWebCamp WordPress Outreach Club
 * Author URI: https://indieweb.org/WordPress_Outreach_Club
 * Version: 5.1.1
 * License: MIT
 * License URI: http://opensource.org/licenses/MIT
 * Text Domain: indieweb
 *
 * @package Indieweb
 */

namespace Indieweb;

\define( 'INDIEWEB_VERSION', '5.1.1' );

\defined( 'INDIEWEB_ADD_HCARD_SUPPORT' ) || \define( 'INDIEWEB_ADD_HCARD_SUPPORT', true );
\defined( 'INDIEWEB_ADD_RELME_SUPPORT' ) || \define( 'INDIEWEB_ADD_RELME_SUPPORT', true );

\define( 'INDIEWEB_PLUGIN_DIR', \plugin_dir_path( __FILE__ ) );
\define( 'INDIEWEB_PLUGIN_BASENAME', \plugin_basename( __FILE__ ) );
\define( 'INDIEWEB_PLUGIN_FILE', \plugin_dir_path( __FILE__ ) . '/' . \basename( __FILE__ ) );
\define( 'INDIEWEB_PLUGIN_URL', \plugin_dir_url( __FILE__ ) );

require_once INDIEWEB_PLUGIN_DIR . '/includes/class-autoloader.php';

if ( INDIEWEB_ADD_HCARD_SUPPORT ) {
	// Require simple-icons data.
	require_once INDIEWEB_PLUGIN_DIR . '/includes/simple-icons.php';
}

// Register the autoloader.
Autoloader::register_path( __NAMESPACE__, INDIEWEB_PLUGIN_DIR . '/includes' );

// Initialize the plugin.
$indieweb = Indieweb::get_instance();
$indieweb->init();

/**
 * Plugin Version Number used for caching.
 *
 * @return string The plugin version.
 */
function version() {
	return INDIEWEB_VERSION;
}
