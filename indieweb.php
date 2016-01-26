<?php
/*
Plugin Name: IndieWeb
Plugin URI: https://github.com/indieweb/wordpress-indieweb
Description: Interested in connecting your WordPress site to the IndieWeb? Get the right plugins to do so.
Author: IndieWebCamp WordPress Outreach Club
Author URI: http://indiewebcamp.com/WordPress_Outreach_Club
Version: 2.2.0
Text Domain: indieweb
Domain Path: /languages
*/



// initialize plugin
add_action( 'plugins_loaded', array( 'IndieWebPlugin', 'init' ) );
// add widget
add_action( 'widgets_init', array( 'IndieWebPlugin', 'init_widgets' ) );


/**
 * adds widget to display rel-me links for indieauth with per-user profile support
 *
 *
 */
class IndieWebPlugin_Widget extends WP_Widget {

	/**
	 * widget constructor
	 */
	function __construct() {
		parent::__construct(
			'IndieWebPlugin_Widget',
			__('Rel-me URLs', 'indieweb'),
			array(
				'description' => __( 'Adds automatic rel-me URLs based on author profile information.', 'indieweb' ),
			)
		);
	}

	/**
	 * widget worker
	 *
	 * @param mixed $args widget parameters
	 * @param mixed $instance saved widget data
	 *
	 * @output echoes the list of rel-me links for the author
	 */
	public function widget( $args, $instance ) {

		$default_author = ( ! empty( $instance['default_author'] ) ) ? intval( $instance['default_author'] ) : 1;
		$use_post_author = ( ! empty( $instance['use_post_author'] ) ) ? intval( $instance['use_post_author'] ) : 1;

		if ( is_singular() && 1 == $use_post_author ) {
			global $post;
			$author_id = $post->post_author;
		}
		else {
			$author_id = $default_author;
		}

		echo IndieWebPlugin::rel_me_list ( $author_id );
	}

	/**
	 * widget data updater
	 *
	 * @param mixed $new_instance new widget data
	 * @param mixed $old_instance current widget data
	 *
	 * @return mixed widget data
	 */
	public function update( $new_instance, $old_instance ) {
		$instance = array();
		$instance['default_author'] = ( ! empty( $new_instance['default_author'] ) ) ? intval( $new_instance['default_author'] ) : 1;

		$instance['use_post_author'] = ( ! empty( $new_instance['use_post_author'] ) ) ? intval( $new_instance['use_post_author'] ) : 1;

		return $instance;
	}

	/**
	 * widget form
	 *
	 * @param mixed $instance
	 *
	 * @output displays the widget form
	 *
	 */
	public function form( $instance ) {
		$default_author = ( isset ( $instance['default_author'] ) ) ? $instance['default_author'] : 1;
		$use_post_author = ( isset ( $instance['use_post_author'] ) ) ? $instance['use_post_author'] : true;

		$users = get_users( array(
			'orderby' => 'ID',
			'fields' => array( 'ID', 'display_name' )
		));

		?>
		<p>
			<label for="<?php echo $this->get_field_id( 'default_author' ); ?>"><?php _e( 'Default author:', 'indieweb' ); ?></label>
			<select name="<?php echo $this->get_field_id( 'default_author' ); ?>" id="<?php echo $this->get_field_id( 'default_author' ); ?>">
				<?php foreach ( $users as $user ): ?>
				<option value="<?php echo $user->ID; ?>" <?php selected( $default_author , $user->ID ); ?>><?php echo $user->display_name; ?></option>
				<?php endforeach; ?>
			</select>
		</p>
		<p>
			<label for="<?php echo $this->get_field_id( 'use_post_author' ); ?>"><?php _e( 'Use post author for rel-me links source on post-like pages instead of default author:', 'indieweb' ); ?></label>
			<input id="<?php echo $this->get_field_id( 'use_post_author' ); ?>" name="<?php echo $this->get_field_name( 'use_post_author' ); ?>" type="checkbox" value="1" <?php checked( $instance['use_post_author'], $use_post_author ); ?> />
		</p>
		<?php
	}

}


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

		// add menu
		add_action( 'admin_menu', array( 'IndieWebPlugin', 'add_menu_item' ) );

		// show a link to the "Getting Started" page
		$plugin = plugin_basename( __FILE__ );
		add_filter( "plugin_action_links_$plugin", array( 'IndieWebPlugin', 'plugin_link' ) );

		// we're up and running
		do_action( 'indieweb_loaded' );

		// additional user meta fields
		add_filter('user_contactmethods', array( 'IndieWebPlugin', 'add_user_meta_fields' ) );
	}

	/**
	 * register WordPress widgets
	 */
	public static function init_widgets() {
		register_widget( 'IndieWebPlugin_Widget' );
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
				'name'          => __( 'WebMention', 'indieweb' ),
				'slug'          => 'webmention',
				'required'      => true,
			),

			// require the Semantic Linkbacks plugin
			array(
				'name'          => __( 'Semantic Linkbacks', 'indieweb' ),
				'slug'          => 'semantic-linkbacks',
				'required'      => true, // If false, the plugin is only 'recommended' instead of required.
			),

			// recommend the MicroPub server plugin
			array(
				'name'          => __( 'MicroPub Server', 'indieweb' ),
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
				'slug'          => 'indieweb-post-kinds',
				'required'      => false,
			),

			// recommend the Syndication Links plugin
			array(
				'name'          => __( 'Syndication Links', 'indieweb' ),
				'slug'          => 'syndication-links',
				'required'      => false,
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
			'parent_slug'  => 'plugins.php',
			'has_notices'  => true,                    // Show admin notices or not.
			'dismissable'  => true,                    // If false, a user cannot dismiss the nag message.
			'dismiss_msg'  => '',                      // If 'dismissable' is false, this message will be output at top of nag.
			'is_automatic' => false,                   // Automatically activate plugins after installation or not.
			'message'      => __( 'For descriptions of the plugins and more information, visit <a href="plugins.php?page=indieweb">Getting Started</a>', 'indieweb' ), // Message to output right before the plugins table.
			'strings'      => array(
				'page_title'                      => __( 'Install IndieWeb Plugins', 'indieweb' ),
				'page_title'                      => __( 'Install IndieWeb Plugins', 'indieweb' ),
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
				'return'                          => __( 'Return to IndieWeb Plugins Installer', 'indieweb' ),
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

	/**
	 *
	 * list of silos an profile url patterns which are supported by indieauth
	 * http://indiewebcamp.com/indieauth.com
	 */
	public static function silos () {

		$silos = array (
			'github' => array (
				'baseurl' => 'https://github.com/%s',
				'display' => __( 'Github username', 'indieweb' ),
			),
			'googleplus' => array (
				'baseurl' => 'https://plus.google.com/%s/posts',
				'display' => __( 'Google+ userID or username', 'indieweb' ),
			),
			'twitter' => array (
				'baseurl' => 'https://twitter.com/%s',
				'display' => __( 'Twitter username', 'indieweb' ),
			),
			'lastfm' => array (
				'baseurl' => 'https://last.fm/user/%s',
				'display' => __( 'Last.fm username', 'indieweb' ),
			),
			'flickr' => array (
				'baseurl' => 'https://www.flickr.com/people/%s',
				'display' => __( 'Flickr username', 'indieweb' ),
			),
		);

		return apply_filters( 'wp_relme_silos', $silos );
	}

	/**
	 * additional user fields
	 *
	 * @param array $profile_fields Current profile fields
	 *
	 * @return array $profile_fields extended
	 *
	 */
	public static function add_user_meta_fields ($profile_fields) {

		foreach ( self::silos() as $silo => $details ) {
			$profile_fields[ 'indieweb_' . $name ] = $details['display'];
		}

		return $profile_fields;
	}

	/**
	 * prints a formatted <ul> list of rel=me to supported silos
	 *
	 */
	public static function rel_me_list ( $author_id = null ) {

		if ( empty( $author_id ) )
			$author_id = get_the_author_id();

		if ( empty( $author_id ) || $author_id == 0 )
			return false;

		$author_name = get_the_author_meta ( 'display_name' , $author_id );

		$list = array();

		foreach ( self::silos() as $silo => $details ) {
			$socialmeta = get_the_author_meta ( 'indieweb_' . $silo, $author_id );

			if ( ! empty( $socialmeta ) )
				$list[ $silo ] = sprintf ( $details['baseurl'], $socialmeta );
		}

		$r = array();
		foreach ( $list as $silo => $profile_url ) {
			$r [ $silo ] = "<a rel=\"me\" class=\"u-{$silo} x-{$silo} icon-{$silo} url u-url\" href=\"{$profile_url}\" title=\"{$author_name} @ {$silo}\">{$silo}</a>";
		}

		$r = "<ul class=\"indieweb-rel-me\">\n<li>" . join ( "</li>\n<li>", $r ) . "</li>\n</ul>";
		echo apply_filters ( "indieweb_rel_me", $r, $author_id, $list );
	}

} // end class IndieWebPlugin
