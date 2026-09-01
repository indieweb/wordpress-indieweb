<?php
/**
 * Indieweb Plugin Installer.
 *
 * Renders the recommended plugins using core's own plugin card markup and
 * install/activate helpers, modelled after the WordPress Performance Lab
 * plugin.
 *
 * @link    https://github.com/WordPress/performance/blob/trunk/plugins/performance-lab/includes/admin/plugins.php
 * @package Indieweb
 */

namespace Indieweb;

use WP_Error;
use Plugin_Upgrader;
use WP_Ajax_Upgrader_Skin;

/**
 * Plugin Installer class for Indieweb.
 */
class Plugin_Installer {

	/**
	 * Slug of the Extensions admin screen.
	 *
	 * @var string
	 */
	const PAGE_SLUG = 'indieweb-installer';

	/**
	 * Transient key the WordPress.org plugin data is cached under.
	 *
	 * @var string
	 */
	const TRANSIENT_KEY = 'indieweb_plugins_info';

	/**
	 * Transient key failed WordPress.org lookups are cached under.
	 *
	 * Kept apart from the plugin data so one broken slug cannot shorten the
	 * lifetime of every successful lookup.
	 *
	 * @var string
	 */
	const ERROR_TRANSIENT_KEY = 'indieweb_plugins_errors';

	/**
	 * Slug of the classic (meta box based) Post Kinds plugin.
	 *
	 * @var string
	 */
	const POST_KINDS_CLASSIC = 'indieweb-post-kinds';

	/**
	 * Slug of the block editor based Post Kinds plugin.
	 *
	 * @var string
	 */
	const POST_KINDS_BLOCK = 'post-kinds-for-indieweb-in-block-themes';

	/**
	 * How deep to follow `requires_plugins` when installing.
	 *
	 * @var int
	 */
	const MAX_DEPENDENCY_DEPTH = 2;

	/**
	 * The recommended plugins, in display order.
	 *
	 * The Post Kinds entry is a placeholder. get_plugin_slugs() swaps in
	 * whichever of the two variants fits the site.
	 *
	 * @var string[]
	 */
	const PLUGINS = array(
		'webmention',
		'micropub',
		self::POST_KINDS_CLASSIC,
		'syndication-links',
		'indieauth',
		'simple-location',
		'pubsubhubbub',
		'indieblocks',
	);

	/**
	 * Plugin fields requested from the WordPress.org API and kept in the cache.
	 *
	 * @var string[]
	 */
	const FIELDS = array(
		'name',
		'slug',
		'short_description',
		'requires',
		'requires_php',
		'requires_plugins',
		'icons',
		'version', // Needed by install_plugin_install_status().
	);

	/**
	 * In-request copy of the transients, keyed by transient name.
	 *
	 * @var array<string, array>
	 */
	private static $cache = array();

	/**
	 * Register the hooks.
	 */
	public static function init() {
		\add_action( 'admin_action_indieweb_install_activate_plugin', array( self::class, 'handle_install_activate' ) );
	}

	/**
	 * Set up the installer screen.
	 *
	 * Hooked to `load-{$hook_suffix}` so the assets are only loaded on the
	 * Extensions screen.
	 */
	public static function load_page() {
		\add_action( 'admin_enqueue_scripts', array( self::class, 'enqueue_scripts' ) );
	}

	/**
	 * Enqueue the core assets needed for the plugin cards.
	 */
	public static function enqueue_scripts() {
		// These assets are needed for the "Learn more" popover.
		\add_thickbox();
		\wp_enqueue_script( 'plugin-install' );
	}

	/**
	 * Get every plugin slug this screen is willing to install.
	 *
	 * This is the allow list for install requests. It holds both Post Kinds
	 * variants regardless of which one is currently recommended, plus anything
	 * `indieweb_recommended_plugins` added, because a card that renders an
	 * install button has to be installable.
	 *
	 * @return string[] List of plugin slugs.
	 */
	public static function get_installable_slugs() {
		$slugs   = self::PLUGINS;
		$slugs[] = self::POST_KINDS_BLOCK;

		return \array_values( \array_unique( \array_merge( $slugs, self::get_plugin_slugs() ) ) );
	}

	/**
	 * Get the slugs of the recommended plugins.
	 *
	 * The list is dynamic: the Post Kinds entry depends on the site, see
	 * get_post_kinds_slug().
	 *
	 * @return string[] List of plugin slugs.
	 */
	public static function get_plugin_slugs() {
		static $slugs = null;

		if ( null !== $slugs ) {
			return $slugs;
		}

		$recommended = array();

		foreach ( self::PLUGINS as $slug ) {
			$recommended[] = self::POST_KINDS_CLASSIC === $slug ? self::get_post_kinds_slug() : $slug;
		}

		/**
		 * Filters the list of plugins recommended on the Extensions screen.
		 *
		 * @param string[] $recommended List of WordPress.org plugin slugs.
		 */
		$recommended = \apply_filters( 'indieweb_recommended_plugins', $recommended );

		$slugs = \array_values( \array_unique( \array_filter( $recommended ) ) );

		return $slugs;
	}

	/**
	 * Decide which of the two Post Kinds plugins to recommend.
	 *
	 * The block editor variant is only recommended if the site can actually run
	 * it: the block editor has to be available and the plugin's WordPress and
	 * PHP requirements have to be met. An active variant always wins, and a
	 * variant that is merely installed wins over the version check, so we never
	 * nudge anyone off what they already run.
	 *
	 * @return string The plugin slug to recommend.
	 */
	public static function get_post_kinds_slug() {
		static $slug = null;

		if ( null !== $slug ) {
			return $slug;
		}

		$variants = array( self::POST_KINDS_CLASSIC, self::POST_KINDS_BLOCK );

		// Whichever variant is running is the one to show.
		foreach ( $variants as $variant ) {
			if ( self::is_plugin_active_by_slug( $variant ) ) {
				$slug = $variant;

				return $slug;
			}
		}

		// Otherwise stick with whatever is already on disk.
		foreach ( $variants as $variant ) {
			if ( ! empty( self::get_installed_plugin_file( $variant ) ) ) {
				$slug = $variant;

				return $slug;
			}
		}

		$slug = self::POST_KINDS_CLASSIC;

		if ( ! self::is_block_editor_enabled() ) {
			return $slug;
		}

		$plugin_data = self::query_plugin_info( self::POST_KINDS_BLOCK );
		if ( \is_wp_error( $plugin_data ) ) {
			return $slug;
		}

		$availability = self::get_plugin_availability( $plugin_data );
		if ( $availability['compatible_wp'] && $availability['compatible_php'] ) {
			$slug = self::POST_KINDS_BLOCK;
		}

		return $slug;
	}

	/**
	 * Whether posts on this site are edited with the block editor.
	 *
	 * `use_block_editor_for_post_type()` is the gate core itself uses, and the
	 * one the Classic Editor plugin, Disable Gutenberg and friends filter, so a
	 * single check covers all of them.
	 *
	 * ClassicPress is asked about separately. Its version of the function is a
	 * stub that returns false, so the answer would be the same, but calling it
	 * runs `WP_Compat::using_block_function()`, which walks the backtrace and
	 * records us in the `plugins_using_blocks` option. On a site running blocks
	 * compatibility level 2 that puts a "uses block-related functions and may
	 * have issues" warning next to this plugin on the Plugins screen.
	 *
	 * @return bool True if the block editor is used for posts.
	 */
	public static function is_block_editor_enabled() {
		if ( \function_exists( 'classicpress_version' ) ) {
			return false;
		}

		// In wp-includes since WordPress 6.1, so loaded on every version we support.
		if ( ! \function_exists( 'use_block_editor_for_post_type' ) ) {
			return false;
		}

		return (bool) \use_block_editor_for_post_type( 'post' );
	}

	/**
	 * Get the main plugin file of an installed plugin, by slug.
	 *
	 * @param string $plugin_slug The WordPress.org plugin slug.
	 * @return string|null The plugin file, relative to the plugins directory, or null if not installed.
	 */
	private static function get_installed_plugin_file( $plugin_slug ) {
		require_once ABSPATH . 'wp-admin/includes/plugin.php';

		$plugins = \get_plugins( '/' . $plugin_slug );

		if ( 0 === \count( $plugins ) ) {
			return null;
		}

		$plugin_files = \array_keys( $plugins );

		return $plugin_slug . '/' . $plugin_files[0];
	}

	/**
	 * Whether an installed plugin is active, by slug.
	 *
	 * @param string $plugin_slug The WordPress.org plugin slug.
	 * @return bool True if the plugin is installed and active.
	 */
	private static function is_plugin_active_by_slug( $plugin_slug ) {
		$plugin_file = self::get_installed_plugin_file( $plugin_slug );

		return ! empty( $plugin_file ) && \is_plugin_active( $plugin_file );
	}

	/**
	 * Build the locale specific transient key.
	 *
	 * `plugins_api()` defaults its `locale` argument to `get_user_locale()`, so
	 * the names and descriptions it returns are translated for whoever asked.
	 * Caching those under a shared key would serve one admin's language to
	 * everybody else.
	 *
	 * @param string $key The transient key.
	 * @return string The key for the current user's locale.
	 */
	private static function get_transient_key( $key ) {
		return $key . '_' . \strtolower( \get_user_locale() );
	}

	/**
	 * Read a transient, keeping an in-request copy.
	 *
	 * @param string $key The transient key.
	 * @return array The cached array, empty if there is nothing cached.
	 */
	private static function get_cache( $key ) {
		$key = self::get_transient_key( $key );

		if ( ! isset( self::$cache[ $key ] ) ) {
			$value = \get_transient( $key );

			self::$cache[ $key ] = \is_array( $value ) ? $value : array();
		}

		return self::$cache[ $key ];
	}

	/**
	 * Write a transient and update the in-request copy.
	 *
	 * @param string $key        The transient key.
	 * @param array  $value      The value to cache.
	 * @param int    $expiration Time until expiration, in seconds.
	 */
	private static function set_cache( $key, $value, $expiration ) {
		$key = self::get_transient_key( $key );

		self::$cache[ $key ] = $value;

		\set_transient( $key, $value, $expiration );
	}

	/**
	 * Get plugin info for the given plugin slug from WordPress.org.
	 *
	 * The results for all recommended plugins share a single transient, so a
	 * screen render costs at most one API request per plugin per hour instead
	 * of one on every page load.
	 *
	 * @param string $plugin_slug The WordPress.org plugin slug.
	 * @return array|WP_Error Array of plugin data or WP_Error on failure.
	 */
	public static function query_plugin_info( $plugin_slug ) {
		$plugins = self::get_cache( self::TRANSIENT_KEY );

		if ( isset( $plugins[ $plugin_slug ] ) ) {
			return $plugins[ $plugin_slug ];
		}

		$errors = self::get_cache( self::ERROR_TRANSIENT_KEY );

		if ( isset( $errors[ $plugin_slug ] ) ) {
			return $errors[ $plugin_slug ];
		}

		require_once ABSPATH . 'wp-admin/includes/plugin-install.php';

		$response = \plugins_api(
			'plugin_information',
			array(
				'slug'   => $plugin_slug,
				'fields' => \array_merge(
					\array_fill_keys( self::FIELDS, true ),
					array( 'sections' => false ) // Omit the bulk of the response which we don't need.
				),
			)
		);

		$error = null;

		if ( \is_wp_error( $response ) ) {
			$error = new WP_Error(
				'api_error',
				\sprintf(
					/* translators: %s: API error message */
					\__( 'Failed to retrieve plugin data from the WordPress.org API: %s', 'indieweb' ),
					$response->get_error_message()
				)
			);
		} elseif ( ! \is_object( $response ) || ! isset( $response->slug ) ) {
			$error = new WP_Error(
				'plugin_not_found',
				\__( 'Plugin not found in the API response.', 'indieweb' )
			);
		}

		if ( $error instanceof WP_Error ) {
			$errors[ $plugin_slug ] = $error;

			self::set_cache( self::ERROR_TRANSIENT_KEY, $errors, MINUTE_IN_SECONDS );

			return $error;
		}

		$plugin_data = \wp_array_slice_assoc( (array) $response, self::FIELDS );

		// Not every plugin declares dependencies, but the rest of the code expects the key.
		if ( ! isset( $plugin_data['requires_plugins'] ) || ! \is_array( $plugin_data['requires_plugins'] ) ) {
			$plugin_data['requires_plugins'] = array();
		}

		$plugins[ $plugin_slug ] = $plugin_data;

		self::set_cache( self::TRANSIENT_KEY, $plugins, HOUR_IN_SECONDS );

		return $plugin_data;
	}

	/**
	 * Check whether a given plugin can be installed and activated.
	 *
	 * @param array $plugin_data                     Plugin data from the WordPress.org API.
	 * @param array $processed_plugin_availabilities Availabilities already processed, reused across cards.
	 * @return array {
	 *     @type bool $compatible_php Whether the PHP requirement is met.
	 *     @type bool $compatible_wp  Whether the WordPress requirement is met.
	 *     @type bool $can_install    Whether the plugin is installed or can be installed.
	 *     @type bool $can_activate   Whether the plugin is active or can be activated.
	 *     @type bool $installed      Whether the plugin is installed.
	 *     @type bool $activated      Whether the plugin is active.
	 * }
	 */
	public static function get_plugin_availability( $plugin_data, &$processed_plugin_availabilities = array() ) {
		if ( \array_key_exists( $plugin_data['slug'], $processed_plugin_availabilities ) ) {
			// Prevent infinite recursion by returning the previously computed value.
			return $processed_plugin_availabilities[ $plugin_data['slug'] ];
		}

		require_once ABSPATH . 'wp-admin/includes/plugin.php';
		require_once ABSPATH . 'wp-admin/includes/plugin-install.php';

		$availability = array(
			'compatible_php' => (
				empty( $plugin_data['requires_php'] ) ||
				\is_php_version_compatible( $plugin_data['requires_php'] )
			),
			'compatible_wp'  => (
				empty( $plugin_data['requires'] ) ||
				\is_wp_version_compatible( $plugin_data['requires'] )
			),
		);

		$plugin_status = \install_plugin_install_status( $plugin_data );
		$plugin_file   = false !== $plugin_status['file'] ? $plugin_status['file'] : self::get_installed_plugin_file( $plugin_data['slug'] );

		// Core can report "install" for a plugin that is already on disk, so check the directory too.
		$availability['installed'] = ( 'install' !== $plugin_status['status'] || ! empty( $plugin_file ) );
		$availability['activated'] = ! empty( $plugin_file ) && \is_plugin_active( $plugin_file );

		// The plugin is already installed or the user can install plugins.
		$availability['can_install'] = (
			$availability['installed'] ||
			\current_user_can( 'install_plugins' )
		);

		// The plugin is activated or the user can activate plugins.
		$availability['can_activate'] = (
			$availability['activated'] ||
			(
				! empty( $plugin_file ) // When not empty, the plugin is installed.
					? \current_user_can( 'activate_plugin', $plugin_file )
					: \current_user_can( 'activate_plugins' )
			)
		);

		// Store pending availability before recursing.
		$processed_plugin_availabilities[ $plugin_data['slug'] ] = $availability;

		foreach ( $plugin_data['requires_plugins'] as $requires_plugin ) {
			$dependency_plugin_data = self::query_plugin_info( $requires_plugin );
			if ( \is_wp_error( $dependency_plugin_data ) ) {
				continue;
			}

			$dependency_availability = self::get_plugin_availability( $dependency_plugin_data, $processed_plugin_availabilities );

			/*
			 * Only the gates fold in. `installed` and `activated` describe this plugin, and
			 * folding a missing dependency into them would render an active plugin as
			 * "Install Now".
			 */
			foreach ( array( 'compatible_php', 'compatible_wp', 'can_install', 'can_activate' ) as $key ) {
				$availability[ $key ] = $availability[ $key ] && $dependency_availability[ $key ];
			}
		}

		$processed_plugin_availabilities[ $plugin_data['slug'] ] = $availability;

		return $availability;
	}

	/**
	 * Install and activate a plugin by its slug.
	 *
	 * Dependencies are installed and activated as well. Their slugs come from the
	 * requested plugin's own WordPress.org metadata rather than from our allow
	 * list, so the depth is capped: we install what an allow-listed plugin asks
	 * for, not a chain of arbitrary length.
	 *
	 * @param string $plugin_slug       Plugin slug.
	 * @param array  $processed_plugins Slugs for plugins which have already been processed. Only used by recursive calls.
	 * @param int    $depth             Current recursion depth. Only used by recursive calls.
	 * @return WP_Error|null WP_Error on failure, null on success.
	 */
	public static function install_and_activate_plugin( $plugin_slug, &$processed_plugins = array(), $depth = 0 ) {
		if ( \in_array( $plugin_slug, $processed_plugins, true ) ) {
			// Prevent infinite recursion from a possible circular dependency.
			return null;
		}

		if ( $depth > self::MAX_DEPENDENCY_DEPTH ) {
			return new WP_Error(
				'dependency_depth_exceeded',
				\__( 'This plugin declares too many nested dependencies to install automatically.', 'indieweb' )
			);
		}

		$processed_plugins[] = $plugin_slug;

		require_once ABSPATH . 'wp-admin/includes/plugin.php';
		require_once ABSPATH . 'wp-admin/includes/plugin-install.php';
		require_once ABSPATH . 'wp-admin/includes/class-wp-upgrader.php';
		require_once ABSPATH . 'wp-admin/includes/class-wp-ajax-upgrader-skin.php';
		require_once ABSPATH . 'wp-admin/includes/class-plugin-upgrader.php';

		// Get the freshest data, including the most recent download_link, as opposed to what query_plugin_info() caches.
		$plugin_data = \plugins_api(
			'plugin_information',
			array(
				'slug'   => $plugin_slug,
				'fields' => array(
					'download_link'    => true,
					'requires_plugins' => true,
					'sections'         => false, // Omit the bulk of the response which we don't need.
				),
			)
		);

		if ( \is_wp_error( $plugin_data ) ) {
			return $plugin_data;
		}

		$plugin_data = (array) $plugin_data;

		if ( ! isset( $plugin_data['requires_plugins'] ) || ! \is_array( $plugin_data['requires_plugins'] ) ) {
			$plugin_data['requires_plugins'] = array();
		}

		// Install and activate plugin dependencies first.
		foreach ( $plugin_data['requires_plugins'] as $requires_plugin_slug ) {
			$result = self::install_and_activate_plugin( $requires_plugin_slug, $processed_plugins, $depth + 1 );
			if ( \is_wp_error( $result ) ) {
				return $result;
			}
		}

		// Install the plugin.
		$plugin_status = \install_plugin_install_status( $plugin_data );
		$plugin_file   = $plugin_status['file'];

		/*
		 * Core reports "install" for a plugin whose folder is already there when its header
		 * version trails wordpress.org and the update transient does not list it, e.g. a
		 * locally patched copy. Running the upgrader on that folder just fails with
		 * "Destination folder already exists", so activate what is on disk instead.
		 */
		if ( 'install' === $plugin_status['status'] ) {
			$installed_file = self::get_installed_plugin_file( $plugin_slug );

			if ( ! empty( $installed_file ) ) {
				$plugin_status['status'] = 'installed';
				$plugin_file             = $installed_file;
			}
		}

		if ( 'install' === $plugin_status['status'] ) {
			if ( ! \current_user_can( 'install_plugins' ) ) {
				return new WP_Error( 'cannot_install_plugin', \__( 'Sorry, you are not allowed to install plugins on this site.', 'default' ) );
			}

			$skin     = new WP_Ajax_Upgrader_Skin( array( 'api' => $plugin_data ) );
			$upgrader = new Plugin_Upgrader( $skin );
			$result   = $upgrader->install( $plugin_data['download_link'] );

			if ( \is_wp_error( $result ) ) {
				return $result;
			} elseif ( \is_wp_error( $skin->result ) ) {
				return $skin->result;
			} elseif ( $skin->get_errors()->has_errors() ) {
				return $skin->get_errors();
			}

			// Resolves the main file from what was actually unpacked, which can differ from the slug.
			$plugin_file = $upgrader->plugin_info();

			if ( ! $plugin_file ) {
				return new WP_Error(
					'plugin_not_found',
					\__( 'Plugin not found among installed plugins.', 'indieweb' )
				);
			}
		}

		// Activate the plugin.
		if ( ! \is_plugin_active( $plugin_file ) ) {
			if ( ! \current_user_can( 'activate_plugin', $plugin_file ) ) {
				return new WP_Error( 'cannot_activate_plugin', \__( 'Sorry, you are not allowed to activate this plugin.', 'default' ) );
			}

			$result = \activate_plugin( $plugin_file );
			if ( \is_wp_error( $result ) ) {
				return $result;
			}
		}

		return null;
	}

	/**
	 * Handle the install/activate request from a plugin card.
	 */
	public static function handle_install_activate() {
		\check_admin_referer( 'indieweb_install_activate_plugin' );

		/*
		 * Unlike the settings screens this runs on a bare `admin.php` request, which has no
		 * capability gate of its own. The individual capabilities are checked again per plugin
		 * in install_and_activate_plugin().
		 */
		if ( ! \current_user_can( 'install_plugins' ) && ! \current_user_can( 'activate_plugins' ) ) {
			self::die_with_back_link( \esc_html__( 'Sorry, you are not allowed to manage plugins for this site.', 'default' ), 403 );
		}

		if ( ! isset( $_GET['slug'] ) ) {
			self::die_with_back_link( \esc_html__( 'Missing required parameter.', 'indieweb' ), 400 );
		}

		$plugin_slug = \sanitize_key( \wp_unslash( $_GET['slug'] ) );

		if ( ! \in_array( $plugin_slug, self::get_installable_slugs(), true ) ) {
			self::die_with_back_link( \esc_html__( 'Invalid plugin.', 'indieweb' ), 400 );
		}

		// Install and activate the plugin and its dependencies.
		$result = self::install_and_activate_plugin( $plugin_slug );
		if ( \is_wp_error( $result ) ) {
			self::die_with_back_link( \wp_kses_post( $result->get_error_message() ) );
		}

		\wp_safe_redirect(
			\add_query_arg(
				array(
					'page'     => self::PAGE_SLUG,
					'activate' => $plugin_slug,
				),
				\admin_url( 'admin.php' )
			)
		);
		exit;
	}

	/**
	 * Bail out of the install request with a way back to the Extensions screen.
	 *
	 * The install runs as a full page navigation, so without a link back the only
	 * way out of a failure is the browser's back button.
	 *
	 * @param string $message  The already escaped error message.
	 * @param int    $response The HTTP status code to send.
	 */
	private static function die_with_back_link( $message, $response = 500 ) {
		\wp_die(
			$message, // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Escaped by the caller.
			\esc_html__( 'IndieWeb Plugin Installer', 'indieweb' ),
			array(
				'back_link' => true,
				'response'  => \absint( $response ),
			)
		);
	}

	/**
	 * Render the installer screen.
	 */
	public static function render_plugins_ui() {
		require_once ABSPATH . 'wp-admin/includes/plugin.php';
		require_once ABSPATH . 'wp-admin/includes/plugin-install.php';

		$plugins = array();
		$errors  = array();

		foreach ( self::get_plugin_slugs() as $plugin_slug ) {
			$plugin_data = self::query_plugin_info( $plugin_slug );

			if ( \is_wp_error( $plugin_data ) ) {
				$errors[ $plugin_slug ] = $plugin_data;
			} else {
				$plugins[ $plugin_slug ] = $plugin_data;
			}
		}

		// Shared across every card, so dependencies are only resolved once.
		$availabilities = array();
		?>
		<div class="wrap plugin-install-php">
			<h1><?php \esc_html_e( 'IndieWeb Plugin Installer', 'indieweb' ); ?></h1>
			<p><?php \esc_html_e( 'The below plugins are recommended to enable additional IndieWeb functionality.', 'indieweb' ); ?></p>

			<?php
			self::render_activation_notice( $plugins );
			self::render_error_notice( $errors );

			if ( \count( $plugins ) > 0 ) :
				?>
				<div class="wp-list-table widefat plugin-install">
					<h2 class="screen-reader-text"><?php \esc_html_e( 'Plugins list', 'default' ); ?></h2>
					<div id="the-list">
						<?php
						foreach ( $plugins as $plugin_data ) {
							self::render_plugin_card( $plugin_data, $availabilities );
						}
						?>
					</div>
				</div>
				<div class="clear"></div>
				<?php
			endif;

			if ( \current_user_can( 'activate_plugins' ) ) :
				?>
				<p>
					<?php
					echo \wp_kses(
						\sprintf(
							/* translators: %s: URL to the plugins screen */
							\__( 'IndieWeb features are installed as plugins. To update or remove them, <a href="%s">manage them on the plugins screen</a>.', 'indieweb' ),
							\esc_url( \admin_url( 'plugins.php' ) )
						),
						array( 'a' => array( 'href' => true ) )
					);
					?>
				</p>
				<?php
			endif;
			?>
		</div>
		<?php
	}

	/**
	 * Render the notice shown after a plugin was installed and activated.
	 *
	 * @param array $plugins Plugin data for the recommended plugins, keyed by slug.
	 */
	private static function render_activation_notice( $plugins ) {
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Read-only notice, the action itself is nonce checked.
		if ( ! isset( $_GET['activate'] ) ) {
			return;
		}

		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Read-only notice, the action itself is nonce checked.
		$plugin_slug = \sanitize_key( \wp_unslash( $_GET['activate'] ) );

		// Only report success for something we offer and that really is running now.
		if ( ! \in_array( $plugin_slug, self::get_installable_slugs(), true ) || ! self::is_plugin_active_by_slug( $plugin_slug ) ) {
			return;
		}

		$name = isset( $plugins[ $plugin_slug ]['name'] ) ? $plugins[ $plugin_slug ]['name'] : $plugin_slug;
		?>
		<div class="notice notice-success is-dismissible">
			<p>
				<?php
				/* translators: %s: Plugin name */
				\printf( \esc_html__( '%s was successfully installed and activated.', 'indieweb' ), \esc_html( $name ) );
				?>
			</p>
		</div>
		<?php
	}

	/**
	 * Render a notice for plugins that could not be looked up.
	 *
	 * @param WP_Error[] $errors Errors keyed by plugin slug.
	 */
	private static function render_error_notice( $errors ) {
		if ( 0 === \count( $errors ) ) {
			return;
		}
		?>
		<div class="notice notice-error">
			<p>
				<?php
				echo \esc_html(
					\_n(
						'Failed to query the WordPress.org Plugin Directory for the following plugin:',
						'Failed to query the WordPress.org Plugin Directory for the following plugins:',
						\count( $errors ),
						'indieweb'
					)
				);
				?>
			</p>
			<ul>
				<?php foreach ( $errors as $plugin_slug => $error ) : ?>
					<li>
						<a target="_blank" href="<?php echo \esc_url( self::get_plugin_directory_url( $plugin_slug ) ); ?>">
							<code><?php echo \esc_html( $plugin_slug ); ?></code>
						</a>
						<?php echo \wp_kses( $error->get_error_message(), array( 'a' => array( 'href' => true ) ) ); ?>
					</li>
				<?php endforeach; ?>
			</ul>
			<p><?php \esc_html_e( 'Please consider installing and activating these plugins manually.', 'indieweb' ); ?></p>
		</div>
		<?php
	}

	/**
	 * Render an individual plugin card.
	 *
	 * Adapted from `WP_Plugin_Install_List_Table::display_rows()` in core.
	 *
	 * @see \WP_Plugin_Install_List_Table::display_rows()
	 *
	 * @param array $plugin_data    Plugin data from the WordPress.org API.
	 * @param array $availabilities Availabilities already processed, reused across cards.
	 */
	public static function render_plugin_card( $plugin_data, &$availabilities = array() ) {
		$name        = \wp_strip_all_tags( $plugin_data['name'] );
		$description = \wp_strip_all_tags( $plugin_data['short_description'] );

		/** This filter is documented in wp-admin/includes/class-wp-plugin-install-list-table.php */
		// phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound -- Intentionally applying a core filter.
		$description = \apply_filters( 'plugin_install_description', $description, $plugin_data );

		$availability = self::get_plugin_availability( $plugin_data, $availabilities );
		$action_links = array();

		if ( $availability['activated'] ) {
			$action_links[] = \sprintf(
				'<button type="button" class="button button-disabled" disabled="disabled">%s</button>',
				\esc_html( \_x( 'Active', 'plugin', 'default' ) )
			);
		} elseif (
			$availability['compatible_php'] &&
			$availability['compatible_wp'] &&
			$availability['can_install'] &&
			$availability['can_activate']
		) {
			$action_links[] = \sprintf(
				'<a class="button" href="%s">%s</a>',
				\esc_url( self::get_install_url( $plugin_data['slug'] ) ),
				\esc_html( $availability['installed'] ? \_x( 'Activate', 'plugin', 'default' ) : \_x( 'Install Now', 'plugin', 'default' ) )
			);
		} else {
			$action_links[] = \sprintf(
				'<button type="button" class="button button-disabled" disabled="disabled">%s</button>',
				// Core has no plugin-context "Cannot Install", so that one stays in our own text domain.
				\esc_html( $availability['can_install'] ? \_x( 'Cannot Activate', 'plugin', 'default' ) : \__( 'Cannot Install', 'indieweb' ) )
			);
		}

		if ( \current_user_can( 'install_plugins' ) ) {
			$title_link_attr = ' class="thickbox open-plugin-details-modal"';
			$details_link    = \add_query_arg(
				array(
					'tab'       => 'plugin-information',
					'plugin'    => $plugin_data['slug'],
					'TB_iframe' => 'true',
					'width'     => 600,
					'height'    => 550,
				),
				\admin_url( 'plugin-install.php' )
			);

			$action_links[] = \sprintf(
				'<a href="%s" class="thickbox open-plugin-details-modal" aria-label="%s" data-title="%s">%s</a>',
				\esc_url( $details_link ),
				/* translators: %s: Plugin name */
				\esc_attr( \sprintf( \__( 'More information about %s', 'default' ), $name ) ),
				\esc_attr( $name ),
				\esc_html__( 'More Details', 'default' )
			);
		} else {
			$title_link_attr = ' target="_blank"';
			$details_link    = self::get_plugin_directory_url( $plugin_data['slug'] );

			$action_links[] = \sprintf(
				'<a href="%s" aria-label="%s" target="_blank">%s</a>',
				\esc_url( $details_link ),
				/* translators: %s: Plugin name */
				\esc_attr( \sprintf( \__( 'Visit plugin site for %s', 'default' ), $name ) ),
				\esc_html__( 'Visit plugin site', 'default' )
			);
		}
		?>
		<div class="plugin-card plugin-card-<?php echo \sanitize_html_class( $plugin_data['slug'] ); ?>">
			<?php self::render_compatibility_notice( $availability ); ?>
			<div class="plugin-card-top">
				<?php
				$icon = self::get_plugin_icon( $plugin_data );

				if ( '' !== $icon ) :
					?>
					<a href="<?php echo \esc_url( $details_link ); ?>"<?php echo $title_link_attr; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Hardcoded attribute string. ?>>
						<img src="<?php echo \esc_url( $icon ); ?>" class="plugin-icon" alt="" />
					</a>
				<?php endif; ?>
				<div class="name column-name">
					<h3>
						<a href="<?php echo \esc_url( $details_link ); ?>"<?php echo $title_link_attr; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Hardcoded attribute string. ?>>
							<?php echo \esc_html( $name ); ?>
						</a>
					</h3>
				</div>
				<div class="action-links">
					<ul class="plugin-action-buttons">
						<?php foreach ( $action_links as $action_link ) : ?>
							<li><?php echo \wp_kses_post( $action_link ); ?></li>
						<?php endforeach; ?>
					</ul>
				</div>
				<div class="desc column-description">
					<p><?php echo \wp_kses_post( $description ); ?></p>
				</div>
			</div>
		</div>
		<?php
	}

	/**
	 * Render the "does not work with your version of…" notice for a card.
	 *
	 * @param array $availability Availability as returned by get_plugin_availability().
	 */
	private static function render_compatibility_notice( $availability ) {
		$compatible_php = $availability['compatible_php'];
		$compatible_wp  = $availability['compatible_wp'];

		if ( $compatible_php && $compatible_wp ) {
			return;
		}

		if ( ! $compatible_php && ! $compatible_wp ) {
			$message = \__( 'This plugin does not work with your versions of WordPress and PHP.', 'default' );
		} elseif ( ! $compatible_wp ) {
			$message = \__( 'This plugin does not work with your version of WordPress.', 'default' );
		} else {
			$message = \__( 'This plugin does not work with your version of PHP.', 'default' );
		}

		$can_update_core = ! $compatible_wp && \current_user_can( 'update_core' );
		$can_update_php  = ! $compatible_php && \current_user_can( 'update_php' );
		?>
		<div class="notice inline notice-error notice-alt">
			<p>
				<?php
				echo \esc_html( $message );

				if ( $can_update_core ) {
					echo \wp_kses_post(
						\sprintf(
							/* translators: %s: URL to WordPress Updates screen. */
							' ' . \__( '<a href="%s">Please update WordPress</a>.', 'default' ),
							\esc_url( \self_admin_url( 'update-core.php' ) )
						)
					);
				}

				if ( $can_update_php ) {
					echo \wp_kses_post(
						\sprintf(
							/* translators: %s: URL to Update PHP page. */
							' ' . \__( '<a href="%s">Learn more about updating PHP</a>.', 'default' ),
							\esc_url( \wp_get_update_php_url() )
						)
					);
				}
				?>
			</p>
			<?php
			if ( $can_update_php ) {
				\wp_update_php_annotation( '<p><em>', '</em></p>' );
			}
			?>
		</div>
		<?php
	}

	/**
	 * Get the nonced URL that installs and activates a plugin.
	 *
	 * @param string $plugin_slug The WordPress.org plugin slug.
	 * @return string The install URL.
	 */
	private static function get_install_url( $plugin_slug ) {
		static $base = null;

		if ( null === $base ) {
			$base = \wp_nonce_url(
				\add_query_arg( 'action', 'indieweb_install_activate_plugin', \admin_url( 'admin.php' ) ),
				'indieweb_install_activate_plugin'
			);
		}

		return \add_query_arg( 'slug', $plugin_slug, $base );
	}

	/**
	 * Get the WordPress.org plugin directory URL for a plugin.
	 *
	 * @param string $plugin_slug The WordPress.org plugin slug.
	 * @return string The plugin directory URL.
	 */
	private static function get_plugin_directory_url( $plugin_slug ) {
		return \trailingslashit( \__( 'https://wordpress.org/plugins/', 'default' ) . $plugin_slug );
	}

	/**
	 * Get the icon URL for a plugin, preferring the vector version.
	 *
	 * @param array $plugin_data Plugin data from the WordPress.org API.
	 * @return string The icon URL, or an empty string if the plugin has none.
	 */
	private static function get_plugin_icon( $plugin_data ) {
		if ( ! isset( $plugin_data['icons'] ) || ! \is_array( $plugin_data['icons'] ) ) {
			return '';
		}

		foreach ( array( 'svg', '2x', '1x', 'default' ) as $size ) {
			if ( ! empty( $plugin_data['icons'][ $size ] ) ) {
				return $plugin_data['icons'][ $size ];
			}
		}

		return '';
	}
}
