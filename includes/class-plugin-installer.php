<?php
/**
 * IndieWeb_Plugin_Installer
 *
 * @author   Darren Cooney
 * @link     https://github.com/dcooney/wordpress-plugin-installer
 * @link     https://connekthq.com
 * @version  1.0
 */


if ( ! defined( 'ABSPATH' ) ) {
	exit;
}



if ( ! class_exists( 'IndieWeb_Plugin_Installer' ) ) {

	class IndieWeb_Plugin_Installer {

		public function start() {
			if ( ! defined( 'CNKT_INSTALLER_PATH' ) ) {
				// Update this constant to use outside the plugins directory
				define( 'CNKT_INSTALLER_PATH', plugins_url( '/', __FILE__ ) );
			}
			add_action( 'admin_enqueue_scripts', array( &$this, 'enqueue_scripts' ) ); // Enqueue scripts and Localize
			add_action( 'wp_ajax_cnkt_plugin_installer', array( &$this, 'cnkt_plugin_installer' ) ); // Install plugin
			add_action( 'wp_ajax_cnkt_plugin_activation', array( &$this, 'cnkt_plugin_activation' ) ); // Activate plugin
		}




		/*
		* init
		* Initialize the display of the plugins.
		*
		*
		* @param $plugin            Array - plugin data
		*
		* @since 1.0
		*/
		public static function init( $plugins ) {
			?>

			<div class="cnkt-plugin-installer">
			<?php
			require_once ABSPATH . 'wp-admin/includes/plugin-install.php';

			foreach ( $plugins as $plugin ) :

				$button_classes = 'install button';
				$button_text    = __( 'Install Now', 'indieweb' );

				$api = plugins_api(
					'plugin_information',
					array(
						'slug'   => sanitize_file_name( $plugin['slug'] ),
						'fields' => array(
							'short_description' => true,
							'sections'          => false,
							'requires'          => false,
							'downloaded'        => true,
							'last_updated'      => false,
							'added'             => false,
							'tags'              => false,
							'compatibility'     => false,
							'homepage'          => false,
							'donate_link'       => false,
							'icons'             => true,
							'banners'           => true,
						),
					)
				);

				if ( ! is_wp_error( $api ) ) { // confirm error free

					$main_plugin_file = self::get_plugin_file( $plugin['slug'] ); // Get main plugin file
					//echo $main_plugin_file;
					if ( self::check_file_extension( $main_plugin_file ) ) { // check file extension
						if ( is_plugin_active( $main_plugin_file ) ) {
							// plugin activation, confirmed!
							$button_classes = 'button disabled';
							$button_text    = __( 'Activated', 'indieweb' );
						} else {
							// It's installed, let's activate it
							$button_classes = 'activate button button-primary';
							$button_text    = __( 'Activate', 'indieweb' );
						}
					}

					// Send plugin data to template
					self::render_template( $plugin, $api, $button_text, $button_classes );

				}

			endforeach;
			?>
			</div>
			<?php
		}




		/*
		* render_template
		* Render display template for each plugin.
		*
		*
		* @param $plugin            Array - Original data passed to init()
		* @param $api               Array - Results from plugins_api
		* @param $button_text       String - text for the button
		* @param $button_classes    String - classnames for the button
		*
		* @since 1.0
		*/
		public static function render_template( $plugin, $api, $button_text, $button_classes ) {
			if ( isset( $api->icons['1x'] ) ) {
				$icon = $api->icons['1x'];
			} else {
				$icon = $api->icons['default'];
			}

			$install_url = add_query_arg(
				array(
					'action'   => 'install-plugin',
					'plugin'   => $api->slug,
					'_wpnonce' => wp_create_nonce( 'install-plugin_' . $api->slug ),
				),
				get_admin_url( null, '/update.php' )
			);
			?>
			<div class="plugin">
				<div class="plugin-wrap">
					<img src="<?php echo esc_url( $icon ); ?>" alt="">
					<h2><?php echo esc_html( $api->name ); ?></h2>
					<p><?php echo esc_html( $api->short_description ); ?></p>

					<p class="plugin-author"><?php esc_html_e( 'By', 'indieweb' ); ?> <?php echo $api->author; // phpcs:ignore ?></p>
				</div>
				<ul class="activation-row">
					<li>
						<a class="<?php echo esc_attr( $button_classes ); ?>"
							data-slug="<?php echo esc_attr( $api->slug ); ?>"
							data-name="<?php echo esc_attr( $api->name ); ?>"
							href="<?php echo esc_url( $install_url ); ?>">
							<?php echo esc_html( $button_text ); ?>
						</a>
					</li>
					<li>
						<a href="https://wordpress.org/plugins/<?php echo esc_attr( $api->slug ); ?>/" target="_blank">
							<?php esc_html_e( 'More Details', 'indieweb' ); ?>
						</a>
					</li>
				</ul>
			</div>
			<?php
		}




		/*
		* cnkt_plugin_installer
		* An Ajax method for installing plugin.
		*
		* @return $json
		*
		* @since 1.0
		*/
		public function cnkt_plugin_installer() {

			if ( ! current_user_can( 'install_plugins' ) ) {
				wp_die( esc_html( __( 'Sorry, you are not allowed to install plugins on this site.', 'indieweb' ) ) );
			}

			$nonce  = $_POST['nonce']; // phpcs:ignore
			$plugin = $_POST['plugin']; // phpcs:ignore

			// Check our nonce, if they don't match then bounce!
			if ( ! wp_verify_nonce( $nonce, 'cnkt_installer_nonce' ) ) {
				wp_die( esc_html( __( 'Error - unable to verify nonce, please try again.', 'indieweb' ) ) );
			}

			// Include required libs for installation
			require_once ABSPATH . 'wp-admin/includes/plugin-install.php';
			require_once ABSPATH . 'wp-admin/includes/class-wp-upgrader.php';
			require_once ABSPATH . 'wp-admin/includes/class-wp-ajax-upgrader-skin.php';
			require_once ABSPATH . 'wp-admin/includes/class-plugin-upgrader.php';

			// Get Plugin Info
			$api = plugins_api(
				'plugin_information',
				array(
					'slug'   => $plugin,
					'fields' => array(
						'short_description' => false,
						'sections'          => false,
						'requires'          => false,
						'rating'            => false,
						'ratings'           => false,
						'downloaded'        => false,
						'last_updated'      => false,
						'added'             => false,
						'tags'              => false,
						'compatibility'     => false,
						'homepage'          => false,
						'donate_link'       => false,
					),
				)
			);

			$skin     = new WP_Ajax_Upgrader_Skin();
			$upgrader = new Plugin_Upgrader( $skin );
			$upgrader->install( $api->download_link );

			if ( $api->name ) {
				$status = 'success';
				$msg    = $api->name . ' successfully installed.';
			} else {
				$status = 'failed';
				$msg    = 'There was an error installing ' . $api->name . '.';
			}

			$json = array(
				'status' => $status,
				'msg'    => $msg,
			);

			wp_send_json( $json );
		}




		/*
		* cnkt_plugin_activation
		* Activate plugin via Ajax.
		*
		* @return $json
		*
		* @since 1.0
		*/
		public function cnkt_plugin_activation() {
			if ( ! current_user_can( 'install_plugins' ) ) {
				wp_die( esc_html( __( 'Sorry, you are not allowed to activate plugins on this site.', 'indieweb' ) ) );
			}

			$nonce  = $_POST['nonce']; // phpcs:ignore
			$plugin = $_POST['plugin']; // phpcs:ignore

			// Check our nonce, if they don't match then bounce!
			if ( ! wp_verify_nonce( $nonce, 'cnkt_installer_nonce' ) ) {
				die( esc_html( __( 'Error - unable to verify nonce, please try again.', 'indieweb' ) ) );
			}

			// Include required libs for activation
			require_once ABSPATH . 'wp-admin/includes/plugin-install.php';
			require_once ABSPATH . 'wp-admin/includes/class-wp-upgrader.php';
			require_once ABSPATH . 'wp-admin/includes/class-plugin-upgrader.php';

			// Get Plugin Info
			$api = plugins_api(
				'plugin_information',
				array(
					'slug'   => $plugin,
					'fields' => array(
						'short_description' => false,
						'sections'          => false,
						'requires'          => false,
						'rating'            => false,
						'ratings'           => false,
						'downloaded'        => false,
						'last_updated'      => false,
						'added'             => false,
						'tags'              => false,
						'compatibility'     => false,
						'homepage'          => false,
						'donate_link'       => false,
					),
				)
			);

			if ( $api->name ) {
				$main_plugin_file = self::get_plugin_file( $plugin );
				$status           = 'success';
				if ( $main_plugin_file ) {
					activate_plugin( $main_plugin_file );
					$msg = $api->name . ' successfully activated.';
				}
			} else {
				$status = 'failed';
				$msg    = 'There was an error activating ' . $api->name . '.';
			}

			$json = array(
				'status' => $status,
				'msg'    => $msg,
			);

			wp_send_json( $json );
		}




		/*
		* get_plugin_file
		* A method to get the main plugin file.
		*
		*
		* @param  $plugin_slug    String - The slug of the plugin
		* @return $plugin_file
		*
		* @since 1.0
		*/

		public static function get_plugin_file( $plugin_slug ) {
			require_once ABSPATH . '/wp-admin/includes/plugin.php'; // Load plugin lib
			$plugins = get_plugins();

			foreach ( $plugins as $plugin_file => $plugin_info ) {

				// Get the basename of the plugin e.g. [askismet]/askismet.php
				$slug = dirname( plugin_basename( $plugin_file ) );

				if ( $slug ) {
					if ( $slug === $plugin_slug ) {
						return $plugin_file; // If $slug = $plugin_name
					}
				}
			}
			return null;
		}




		/*
		* check_file_extension
		* A helper to check file extension
		*
		*
		* @param $filename    String - The filename of the plugin
		* @return boolean
		*
		* @since 1.0
		*/
		public static function check_file_extension( $filename ) {
			if ( substr( strrchr( $filename, '.' ), 1 ) === 'php' ) {
				// has .php exension
				return true;
			} else {
				// ./wp-content/plugins
				return false;
			}
		}




		/*
		* enqueue_scripts
		* Enqueue admin scripts and scripts localization
		*
		*
		* @since 1.0
		*/
		public function enqueue_scripts() {
			wp_enqueue_script( 'plugin-installer', CNKT_INSTALLER_PATH . 'static/js/installer.js', array( 'jquery' ), IndieWeb_Plugin::$version, true );
			wp_localize_script(
				'plugin-installer',
				'cnkt_installer_localize',
				array(
					'ajax_url'      => admin_url( 'admin-ajax.php' ),
					'admin_nonce'   => wp_create_nonce( 'cnkt_installer_nonce' ),
					'install_now'   => __( 'Are you sure you want to install this plugin?', 'indieweb' ),
					'install_btn'   => __( 'Install Now', 'indieweb' ),
					'activate_btn'  => __( 'Activate', 'indieweb' ),
					'installed_btn' => __( 'Activated', 'indieweb' ),
				)
			);

			wp_enqueue_style( 'plugin-installer', CNKT_INSTALLER_PATH . 'static/css/installer.css', array(), IndieWeb_Plugin::$version );
		}
	}


	// initialize
	$connekt_plugin_installer = new IndieWeb_Plugin_Installer();
	$connekt_plugin_installer->start();
} // End if( class_exists )
