<?php
/*
 Plugin Name: IndieWeb
 Plugin URI: https://github.com/pfefferle/wordpress-indieweb
 Description: The IndieWeb version of WordPress' Jetpack plugin
 Author: pfefferle
 Author URI: http://notizblog.org/
 Version: 1.0.0-dev
*/

// Pre-2.6 compatibility
if ( ! defined( 'WP_CONTENT_URL' ) )
    define( 'WP_CONTENT_URL', get_option( 'siteurl' ) . '/wp-content' );
if ( ! defined( 'WP_CONTENT_DIR' ) )
    define( 'WP_CONTENT_DIR', ABSPATH . 'wp-content' );
if ( ! defined( 'WP_PLUGIN_URL' ) )
    define( 'WP_PLUGIN_URL', WP_CONTENT_URL. '/plugins' );
if ( ! defined( 'WP_PLUGIN_DIR' ) )
    define( 'WP_PLUGIN_DIR', WP_CONTENT_DIR . '/plugins' );
if ( ! defined( 'WP_ADMIN_URL' ) )
    define( 'WP_ADMIN_URL', get_option('siteurl') . '/wp-admin' );

// initialize plugin
add_action('init', array( 'IndieWebPlugin', 'init' ), 99);

// include webmentions if not already installed
if (!class_exists("WebMentionPlugin")) {
  include_once "webmention/webmention.php";
}

// include semantic linkbacks if not already installed
if (!class_exists("SemanticLinkbacksPlugin")) {
  include_once "semantic-linkbacks/semantic-linkbacks.php";
}

/**
 *
 *
 * @author Matthias Pfefferle
 */
class IndieWebPlugin {

  /**
   * Initialize the plugin, registering WordPress hooks.
   */
  public static function init() {
    // hooks
    add_action('admin_menu', array('IndieWebPlugin', 'add_menu_item'));
  }

  /**
   * add menu item
   */
  public static function add_menu_item() {
    add_options_page('IndieWeb', 'IndieWeb', 'administrator', 'indieweb', array('IndieWebPlugin', 'settings'));
  }

  /**
   * settings page
   */
  public static function settings() {
    wp_enqueue_style( 'plugin-install' );
    wp_enqueue_script( 'plugin-install' );
    add_thickbox();
?>
  <div class="wrap">
    <img src="<?php echo WP_PLUGIN_URL ?>/indieweb/static/img/indieweb-32.png" alt="OSstatus for WordPress" class="icon32" />

    <h2>Indie Webify your WordPress-Blog</h2>

    <p><strong>Own your data.</strong> Create and publish content on your own site, and only optionally syndicate to third-party silos.</p>
    <p>This is the basis of the <strong>Indie Web</strong>. For more, see <a href="http://indiewebcamp.com/principles" target="_blank">principles</a> and <a href="http://indiewebcamp.com/why" target="_blank">why</a>.</p>

    <p>WordPress is an easy way to start your <em>Indie Web</em> live. There are a bunch of plugins that will help you to get you even more in control of
      your own data.</p>

    <p>For some more informations, please visit the <a href="http://indiewebcamp.com/" target="_blank"><em>Indie Web Camp</em> wiki</a>
      and especially the <a href="http://indiewebcamp.com/wordpress" target="_blank">WordPress page</a>.</p>
  </div>
<?php
  }
}