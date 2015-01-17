<?php
/*
 Plugin Name: IndieWeb
 Plugin URI: https://github.com/indieweb/wordpress-indieweb
 Description: Interested in connecting your WordPress site to the Indieweb? Get the right plugins to do so. 
 Author: pfefferle, dshanske
 Author URI: http://indiewebcamp.org/
 Version: 2.1.0
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

/**
 * Include the TGM_Plugin_Activation class.
 */
require_once dirname( __FILE__ ) . '/class-tgm-plugin-activation.php';

// initialize plugin
add_action('init', array( 'IndieWebPlugin', 'init' ));
add_action('tgmpa_register', array('IndieWebPlugin', 'register_required_plugins'));

/**
 * IndieWeb Plugin Class
 *
 * @author Matthias Pfefferle
 */
class IndieWebPlugin
{
  /**
   * Initialize the plugin, registering WordPress hooks.
   */
  public static function init()
  {
    // hooks
    add_action('admin_menu', array('IndieWebPlugin', 'add_menu_item'));
    add_filter('tgmpa_admin_menu_use_add_theme_page', '__return_false');
  }

  /**
   * add menu item
   */
  public static function add_menu_item()
  {
    add_plugins_page('IndieWeb', 'IndieWeb', 'administrator', 'indieweb', array('IndieWebPlugin', 'settings'));
  }

  /**
   * settings page
   */
  public static function settings()
  {
?>
  <div class="wrap">
    <img src="<?php echo WP_PLUGIN_URL ?>/indieweb/static/img/indieweb-32.png" alt="OSstatus for WordPress" class="icon32" />

    <h2>IndieWebify your WordPress-Blog</h2>

<h3>Getting Started</h3>
  
    <p>It is easy to get started with <em>IndieWeb</em> on WordPress. This plugin will help you set up the plugins that will enhance your site. Not all plugins are right for you. Required plugins are those we recommend every site have.</p>
    <p>For more information on these plugins, visit the <a href="http://indiewebcamp.com/wordpress" target="_blank">WordPress page</a> on the Indiewebcamp wiki.</p>


<ul>
<li><strong>Webmention</strong> <em>(Required)</em> - adds webmention support to WordPress. The plumbing of the Indieweb</li>
<li><strong>Semantic Linkbacks</strong> <em>(Required)</em> - enhances linkbacks such as webmention to create richer comments.</li>
<li><strong>Webmention for (Threaded) Comments</strong> - Adds support for threaded comments for webmentions.</li>
<li><strong>Webactions</strong> - Adds webaction markups to WordPress elements</li> 
<li><strong>Post Kinds</strong> - Adds support for kinds of posts to WordPress as a custom taxonomy. Allows you to reply/like/etc . to another site from your own.</li>
<li><strong>Syndication Plugins</strong> - Both of the below offer differing ways to display links for POSSE/syndicated versions of a post.
   <ol>	<li><strong>Syndication Links</strong> - Adds fields to a post to allow manual entry of syndication links</li>
	<li><strong>WordPress Syndication</strong> - automatically adds link to a post from a supported syndication plugin. Fully supports Social, partial support for SNAP, and support for Bridgy Publish</li>
  </ol></li>
<li><strong>Indieweb Press-This</strong> - Adds Indieweb markup to the WordPressPress-This bookmarkets to allow you to respond on your site with one-click</li>
<li><strong>Hum URL Shortener</strong> - A personal URL shortener</li>

</ul>

    <p><a href="<?php echo admin_url('options-general.php?page=indieweb-installer'); ?>" class="button button-primary">Install Plugins</a></p>

<h3>What is the Indieweb?</h3>

    <p><strong>Own your data.</strong> Create and publish content on your own site, and only optionally syndicate to third-party silos.</p>  
    <p>This is the basis of the <strong>Indie Web</strong>. For more, see <a href="http://indiewebcamp.com/principles" target="_blank">principles</a> and <a href="http://indiewebcamp.com/why" target="_blank">why</a>.</p>

    <p>For even more information, please visit the <a href="http://indiewebcamp.com/" target="_blank"><em>Indie Web Camp</em> wiki</a>.</p>
  </div>
<?php
  }

  /**
   * Register the required plugins.
   *
   * This function is hooked into tgmpa_init, which is fired within the
   * TGM_Plugin_Activation class constructor.
   */
  public static function register_required_plugins()
  {
    /**
     * Array of plugin arrays. Required keys are name and slug.
     * If the source is NOT from the .org repo, then source is also required.
     */
    $plugins = array(

      // require the WebMention plugin
      array(
        'name'               => 'WebMention',
        'slug'               => 'webmention',
        'required'           => true,
      ),

      // require the Semantic Linkbacks plugin
      array(
        'name'               => 'Semantic Linkbacks',

        'required'           => true, // If false, the plugin is only 'recommended' instead of required.
      ),

      // recommend the Hum URL shortener
      array(
        'name'      => 'Hum (URL shortener)',
        'slug'      => 'hum',
        'required'  => false,
      ),

      // recommend the WebActions plugin
      array(
        'name'          => 'WebActions',
        'slug'          => 'wordpress-webactions-master',
        'source'        => 'https://github.com/pfefferle/wordpress-webactions/archive/master.zip',
        'required'      => false,
        'external_url'  => 'https://github.com/pfefferle/wordpress-webactions'
      ),

      // recommend the IndieWeb Press-This plugin
      array(
        'name'          => 'IndieWeb Press-This',
        'slug'          => 'wordpress-indieweb-press-this-master',
        'source'        => 'https://github.com/pfefferle/wordpress-indieweb-press-this/archive/master.zip',
        'required'      => false,
        'external_url'  => 'https://github.com/pfefferle/wordpress-indieweb-press-this'
      ),

      // recommend the "WebMention for Comments" plugin
      array(
        'name'          => 'WebMention support for (threaded) comments',
        'slug'          => 'wordpress-webmention-for-comments-master',
        'source'        => 'https://github.com/pfefferle/wordpress-webmention-for-comments/archive/master.zip',
        'required'      => false,
        'external_url'  => 'https://github.com/pfefferle/wordpress-webmention-for-comments'
      ),

      // recommend the Post Kinds plugin
      array(
        'name'          => 'Post Kinds',
        'slug'          => 'indieweb-post-kinds-master',
        'source'        => 'https://github.com/dshanske/indieweb-post-kinds/archive/master.zip',
        'required'      => false,
        'external_url'  => 'https://github.com/dshanske/indieweb-post-kinds'
      ),

      // recommend the Syndication Links plugin
      array(
        'name'          => 'Syndication Links',
        'slug'          => 'syndication-links-master',
        'source'        => 'https://github.com/dshanske/syndication-links/archive/master.zip',
        'required'      => false,
        'external_url'  => 'https://github.com/dshanske/syndication-links'
      ),

      // recommend the WordPress Syndication plugin
      array(  
        'name'          => 'WordPress Syndication',
        'slug'          => 'wordpress-syndication-master',  
        'source'        => 'https://github.com/jihaisse/wordpress-syndication/archive/master.zip',
        'required'      => false,
        'external_url'  => 'https://github.com/jihaisse/wordpress-syndication'
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
      'message'      => 'Choose the combination of plugins that are right for your site', // Message to output right before the plugins table.
      'strings'      => array(
        'page_title'                      => __('Install Indieweb Plugins', 'indieweb'),
        'menu_title'                      => __('IndieWeb Plugin Installer', 'indieweb'),
        'installing'                      => __('Installing Plugin: %s', 'indieweb'), // %s = plugin name.
        'oops'                            => __('Something went wrong with the plugin install.', 'indieweb'),
        'notice_can_install_required'     => _n_noop('The IndieWeb plugin requires the following plugin: %1$s.', 'The IndieWeb plugin requires the following plugins: %1$s.', 'indieweb'), // %1$s = plugin name(s).
        'notice_can_install_recommended'  => _n_noop('The IndieWeb plugin recommends the following plugin: %1$s.', 'The IndieWeb plugin recommends the following plugins: %1$s.', 'indieweb'), // %1$s = plugin name(s).
        'notice_cannot_install'           => _n_noop('Sorry, but you do not have the correct permissions to install the %s plugin. Contact the administrator of this site for help on getting the plugin installed.', 'Sorry, but you do not have the correct permissions to install the %s plugins. Contact the administrator of this site for help on getting the plugins installed.', 'indieweb'), // %1$s = plugin name(s).
        'notice_can_activate_required'    => _n_noop('The following required plugin is currently inactive: %1$s.', 'The following required plugins are currently inactive: %1$s.', 'indieweb'), // %1$s = plugin name(s).
        'notice_can_activate_recommended' => _n_noop('The following recommended plugin is currently inactive: %1$s.', 'The following recommended plugins are currently inactive: %1$s.', 'indieweb'), // %1$s = plugin name(s).
        'notice_cannot_activate'          => _n_noop('Sorry, but you do not have the correct permissions to activate the %s plugin. Contact the administrator of this site for help on getting the plugin activated.', 'Sorry, but you do not have the correct permissions to activate the %s plugins. Contact the administrator of this site for help on getting the plugins activated.', 'indieweb'), // %1$s = plugin name(s).
        'notice_ask_to_update'            => _n_noop('The following plugin needs to be updated to its latest version to ensure maximum compatibility with this plugin: %1$s.', 'The following plugins need to be updated to their latest version to ensure maximum compatibility with this theme: %1$s.', 'indieweb'), // %1$s = plugin name(s).
        'notice_cannot_update'            => _n_noop('Sorry, but you do not have the correct permissions to update the %s plugin. Contact the administrator of this site for help on getting the plugin updated.', 'Sorry, but you do not have the correct permissions to update the %s plugins. Contact the administrator of this site for help on getting the plugins updated.', 'indieweb'), // %1$s = plugin name(s).
        'install_link'                    => _n_noop('Begin installing plugin', 'Begin installing plugins', 'indieweb'),
        'activate_link'                   => _n_noop('Begin activating plugin', 'Begin activating plugins', 'indieweb'),
        'return'                          => __('Return to Indieweb Plugins Installer', 'indieweb'),
        'plugin_activated'                => __('Plugin activated successfully.', 'indieweb'),
        'complete'                        => __('All plugins installed and activated successfully. %s', 'indieweb'), // %s = dashboard link.
        'nag_type'                        => 'updated' // Determines admin notice type - can only be 'updated', 'update-nag' or 'error'.
      )
    );

    tgmpa($plugins, $config);
  }
}
