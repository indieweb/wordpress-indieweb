<?php
/**
 * Test Plugin_Installer class.
 *
 * @package Indieweb
 */

use Indieweb\Plugin_Installer;

/**
 * Test Plugin_Installer class.
 *
 * WordPress.org is never contacted: `plugins_api()` is short-circuited through
 * the `plugins_api` filter and answers from `$api_responses`. Only made-up
 * plugin slugs are used, so the plugins that happen to be installed in the
 * test environment cannot leak in.
 */
class Test_Plugin_Installer extends WP_UnitTestCase {

	/**
	 * Slug of the throwaway plugin some tests write to the plugins directory.
	 *
	 * @var string
	 */
	const TEST_PLUGIN_SLUG = 'indieweb-test-plugin';

	/**
	 * Slug of a second throwaway plugin, used as a dependency.
	 *
	 * @var string
	 */
	const TEST_DEPENDENCY_SLUG = 'indieweb-test-dependency';

	/**
	 * Nonce action of the install request.
	 *
	 * @var string
	 */
	const NONCE_ACTION = 'indieweb_install_activate_plugin';

	/**
	 * User IDs keyed by role.
	 *
	 * @var int[]
	 */
	protected static $users = array();

	/**
	 * Canned `plugins_api()` responses keyed by slug. Unknown slugs get an error.
	 *
	 * @var array[]
	 */
	private $api_responses = array();

	/**
	 * Slugs `plugins_api()` was asked about, in order.
	 *
	 * @var string[]
	 */
	private $api_calls = array();

	/**
	 * Plugin directories written to the plugins directory, to be removed again.
	 *
	 * @var string[]
	 */
	private $plugin_dirs = array();

	/**
	 * Set up before class.
	 *
	 * @param WP_UnitTest_Factory $factory Factory instance.
	 */
	public static function wpSetUpBeforeClass( WP_UnitTest_Factory $factory ) {
		foreach ( array( 'administrator', 'subscriber' ) as $role ) {
			self::$users[ $role ] = $factory->user->create( array( 'role' => $role ) );
		}
	}

	/**
	 * Set up.
	 */
	public function set_up() {
		parent::set_up();

		add_filter( 'plugins_api', array( $this, 'mock_plugins_api' ), 10, 3 );
		add_filter( 'pre_http_request', array( $this, 'block_http' ) );

		// Keeps the Post Kinds detection away from the API unless a test asks for it.
		add_filter( 'use_block_editor_for_post_type', '__return_false' );
	}

	/**
	 * Tear down.
	 *
	 * Options and caches are reverted by the framework, only the files are not.
	 */
	public function tear_down() {
		foreach ( $this->plugin_dirs as $dir ) {
			$this->rmdir( $dir );
			$this->delete_folders( $dir );
		}

		parent::tear_down();
	}

	/**
	 * Fail any HTTP request a test lets slip through.
	 *
	 * @return WP_Error
	 */
	public function block_http() {
		return new WP_Error( 'http_blocked', 'Tests must not make HTTP requests.' );
	}

	/**
	 * Answer `plugins_api()` from the canned responses.
	 *
	 * @param false|object|array $result The current result.
	 * @param string             $action The API action.
	 * @param object             $args   The request arguments.
	 * @return object|WP_Error The plugin data, or an error for unknown slugs.
	 */
	public function mock_plugins_api( $result, $action, $args ) {
		$this->api_calls[] = $args->slug;

		if ( ! isset( $this->api_responses[ $args->slug ] ) ) {
			return new WP_Error( 'mock_error', 'No such plugin: ' . $args->slug );
		}

		return (object) $this->api_responses[ $args->slug ];
	}

	/**
	 * Build a complete, compatible API response for a made-up plugin.
	 *
	 * @param string $slug      The plugin slug.
	 * @param array  $overrides Fields to override.
	 * @return array The plugin data.
	 */
	private function plugin_data( $slug, array $overrides = array() ) {
		return array_merge(
			array(
				'name'              => 'Plugin ' . $slug,
				'slug'              => $slug,
				'short_description' => 'Description of ' . $slug,
				'requires'          => '6.2',
				'requires_php'      => '7.4',
				'requires_plugins'  => array(),
				'icons'             => array(),
				'version'           => '1.0.0',
				'download_link'     => 'https://downloads.example/' . $slug . '.zip',
				'sections'          => array( 'description' => 'Long text we never want.' ),
			),
			$overrides
		);
	}

	/**
	 * Write a minimal plugin to the plugins directory.
	 *
	 * @param string $slug The plugin slug, also used as the directory name.
	 * @return string The plugin file, relative to the plugins directory.
	 */
	private function create_plugin( $slug ) {
		$dir  = WP_PLUGIN_DIR . '/' . $slug;
		$file = $slug . '/' . $slug . '.php';

		if ( ! is_dir( $dir ) ) {
			mkdir( $dir ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_mkdir
		}

		// phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_file_put_contents
		file_put_contents(
			WP_PLUGIN_DIR . '/' . $file,
			"<?php\n/**\n * Plugin Name: Test {$slug}\n * Version: 1.0.0\n */\n"
		);

		$this->plugin_dirs[] = $dir;
		wp_cache_delete( 'plugins', 'plugins' );

		return $file;
	}

	/**
	 * Log in as a user with the given role.
	 *
	 * @param string $role The role.
	 */
	private function login_as( $role ) {
		wp_set_current_user( self::$users[ $role ] );
	}

	/**
	 * Run the install action the way a plugin card link would.
	 *
	 * @param string|null $slug       The requested slug, null to omit it.
	 * @param bool        $with_nonce Whether to send a valid nonce.
	 */
	private function dispatch_install_action( $slug, $with_nonce = true ) {
		if ( null !== $slug ) {
			$_GET['slug'] = $slug;
		}

		if ( $with_nonce ) {
			$_REQUEST['_wpnonce'] = wp_create_nonce( self::NONCE_ACTION );
		}

		Plugin_Installer::handle_install_activate();
	}

	/**
	 * Render a card and return its markup.
	 *
	 * @param array $plugin_data The plugin data.
	 * @return string The markup.
	 */
	private function render_card( array $plugin_data ) {
		return get_echo( array( Plugin_Installer::class, 'render_plugin_card' ), array( $plugin_data ) );
	}

	/**
	 * Both Post Kinds variants are installable, whichever one is recommended.
	 */
	public function test_installable_slugs_include_both_post_kinds_variants() {
		$slugs = Plugin_Installer::get_installable_slugs();

		$this->assertContains( Plugin_Installer::POST_KINDS_CLASSIC, $slugs );
		$this->assertContains( Plugin_Installer::POST_KINDS_BLOCK, $slugs );
		$this->assertContains( 'webmention', $slugs );
		$this->assertSame( array_unique( $slugs ), $slugs, 'Slugs must be unique.' );
	}

	/**
	 * Plugins added through the filter are recommended and installable.
	 */
	public function test_filter_can_add_recommended_plugins() {
		add_filter(
			'indieweb_recommended_plugins',
			function ( $plugins ) {
				$plugins[] = 'extra-plugin';
				$plugins[] = 'extra-plugin'; // Duplicates are dropped.
				$plugins[] = '';             // And so are empty entries.

				return $plugins;
			}
		);

		$recommended = Plugin_Installer::get_plugin_slugs();

		$this->assertContains( 'extra-plugin', $recommended );
		$this->assertNotContains( '', $recommended );
		$this->assertSame( array_unique( $recommended ), $recommended );
		$this->assertContains( 'extra-plugin', Plugin_Installer::get_installable_slugs() );
	}

	/**
	 * Without the block editor the classic variant is recommended and no API call is made.
	 */
	public function test_post_kinds_defaults_to_classic_without_block_editor() {
		$this->assertFalse( Plugin_Installer::is_block_editor_enabled() );
		$this->assertSame( Plugin_Installer::POST_KINDS_CLASSIC, Plugin_Installer::get_post_kinds_slug() );
		$this->assertSame( array(), $this->api_calls );
	}

	/**
	 * With the block editor the block variant is recommended if the site can run it.
	 */
	public function test_post_kinds_prefers_block_variant_when_compatible() {
		add_filter( 'use_block_editor_for_post_type', '__return_true' );
		$this->api_responses[ Plugin_Installer::POST_KINDS_BLOCK ] = $this->plugin_data( Plugin_Installer::POST_KINDS_BLOCK );

		$this->assertTrue( Plugin_Installer::is_block_editor_enabled() );
		$this->assertSame( Plugin_Installer::POST_KINDS_BLOCK, Plugin_Installer::get_post_kinds_slug() );
		$this->assertContains( Plugin_Installer::POST_KINDS_BLOCK, Plugin_Installer::get_plugin_slugs() );
		$this->assertNotContains( Plugin_Installer::POST_KINDS_CLASSIC, Plugin_Installer::get_plugin_slugs() );
	}

	/**
	 * The block variant is not recommended when its requirements are not met.
	 */
	public function test_post_kinds_falls_back_to_classic_when_block_variant_is_incompatible() {
		add_filter( 'use_block_editor_for_post_type', '__return_true' );
		$this->api_responses[ Plugin_Installer::POST_KINDS_BLOCK ] = $this->plugin_data( Plugin_Installer::POST_KINDS_BLOCK, array( 'requires' => '99.0' ) );

		$this->assertSame( Plugin_Installer::POST_KINDS_CLASSIC, Plugin_Installer::get_post_kinds_slug() );
	}

	/**
	 * A failed lookup of the block variant falls back to the classic one.
	 */
	public function test_post_kinds_falls_back_to_classic_on_api_error() {
		add_filter( 'use_block_editor_for_post_type', '__return_true' );

		$this->assertSame( Plugin_Installer::POST_KINDS_CLASSIC, Plugin_Installer::get_post_kinds_slug() );
		$this->assertSame( array( Plugin_Installer::POST_KINDS_BLOCK ), $this->api_calls );
	}

	/**
	 * A variant that is already on disk wins over the block editor check.
	 */
	public function test_post_kinds_keeps_installed_variant() {
		add_filter( 'use_block_editor_for_post_type', '__return_true' );
		$this->create_plugin( Plugin_Installer::POST_KINDS_BLOCK );

		$this->assertSame( Plugin_Installer::POST_KINDS_BLOCK, Plugin_Installer::get_post_kinds_slug() );
		$this->assertSame( array(), $this->api_calls );
	}

	/**
	 * Only the fields we need are kept, and `requires_plugins` is always an array.
	 */
	public function test_query_plugin_info_keeps_only_the_needed_fields() {
		$this->api_responses['some-plugin'] = $this->plugin_data( 'some-plugin', array( 'requires_plugins' => null ) );

		$plugin_data = Plugin_Installer::query_plugin_info( 'some-plugin' );

		$this->assertSameSets( Plugin_Installer::FIELDS, array_keys( $plugin_data ) );
		$this->assertSame( 'some-plugin', $plugin_data['slug'] );
		$this->assertSame( array(), $plugin_data['requires_plugins'] );
	}

	/**
	 * Plugin data is cached per locale and served from the cache afterwards.
	 */
	public function test_query_plugin_info_caches_per_locale() {
		$this->api_responses['some-plugin'] = $this->plugin_data( 'some-plugin' );

		Plugin_Installer::query_plugin_info( 'some-plugin' );
		Plugin_Installer::query_plugin_info( 'some-plugin' );

		$this->assertSame( array( 'some-plugin' ), $this->api_calls, 'The second call must be served from the cache.' );
		$this->assertArrayHasKey( 'some-plugin', get_transient( Plugin_Installer::TRANSIENT_KEY . '_en_us' ) );

		// Another locale gets its own lookup and its own transient.
		add_filter( 'locale', fn() => 'de_DE' );

		Plugin_Installer::query_plugin_info( 'some-plugin' );

		$this->assertSame( array( 'some-plugin', 'some-plugin' ), $this->api_calls );
		$this->assertArrayHasKey( 'some-plugin', get_transient( Plugin_Installer::TRANSIENT_KEY . '_de_de' ) );
	}

	/**
	 * Failed lookups are cached apart from the plugin data.
	 */
	public function test_query_plugin_info_caches_errors_separately() {
		$error = Plugin_Installer::query_plugin_info( 'broken-plugin' );
		Plugin_Installer::query_plugin_info( 'broken-plugin' );

		$this->assertWPError( $error );
		$this->assertSame( 'api_error', $error->get_error_code() );
		$this->assertStringContainsString( 'No such plugin: broken-plugin', $error->get_error_message() );
		$this->assertSame( array( 'broken-plugin' ), $this->api_calls, 'The error must be served from the cache.' );

		$this->assertFalse( get_transient( Plugin_Installer::TRANSIENT_KEY . '_en_us' ) );
		$this->assertArrayHasKey( 'broken-plugin', get_transient( Plugin_Installer::ERROR_TRANSIENT_KEY . '_en_us' ) );
	}

	/**
	 * A response without a slug is treated as "not found".
	 */
	public function test_query_plugin_info_rejects_responses_without_slug() {
		$this->api_responses['odd-plugin'] = array( 'name' => 'Odd' );

		$error = Plugin_Installer::query_plugin_info( 'odd-plugin' );

		$this->assertWPError( $error );
		$this->assertSame( 'plugin_not_found', $error->get_error_code() );
	}

	/**
	 * Availability reflects the version requirements and the user's capabilities.
	 */
	public function test_availability_checks_requirements_and_capabilities() {
		$this->login_as( 'administrator' );

		$availability = Plugin_Installer::get_plugin_availability( $this->plugin_data( 'new-plugin' ) );

		$this->assertTrue( $availability['compatible_php'] );
		$this->assertTrue( $availability['compatible_wp'] );
		$this->assertFalse( $availability['installed'] );
		$this->assertFalse( $availability['activated'] );
		$this->assertTrue( $availability['can_install'] );
		$this->assertTrue( $availability['can_activate'] );

		$availability = Plugin_Installer::get_plugin_availability(
			$this->plugin_data(
				'new-plugin',
				array(
					'requires'     => '99.0',
					'requires_php' => '99.0',
				)
			)
		);

		$this->assertFalse( $availability['compatible_php'] );
		$this->assertFalse( $availability['compatible_wp'] );

		$this->login_as( 'subscriber' );

		$availability = Plugin_Installer::get_plugin_availability( $this->plugin_data( 'new-plugin' ) );

		$this->assertFalse( $availability['can_install'] );
		$this->assertFalse( $availability['can_activate'] );
	}

	/**
	 * Installed and active plugins are recognised as such.
	 */
	public function test_availability_recognises_installed_and_active_plugins() {
		$this->login_as( 'administrator' );
		$plugin_file = $this->create_plugin( self::TEST_PLUGIN_SLUG );

		$availability = Plugin_Installer::get_plugin_availability( $this->plugin_data( self::TEST_PLUGIN_SLUG ) );

		$this->assertTrue( $availability['installed'] );
		$this->assertFalse( $availability['activated'] );

		activate_plugin( $plugin_file );

		$availability = Plugin_Installer::get_plugin_availability( $this->plugin_data( self::TEST_PLUGIN_SLUG ) );

		$this->assertTrue( $availability['installed'] );
		$this->assertTrue( $availability['activated'] );
	}

	/**
	 * An incompatible dependency blocks the plugin, but does not hide that it is active.
	 */
	public function test_availability_folds_in_dependency_gates_only() {
		$this->login_as( 'administrator' );
		$plugin_file = $this->create_plugin( self::TEST_PLUGIN_SLUG );
		activate_plugin( $plugin_file );

		$this->api_responses['needed-plugin'] = $this->plugin_data( 'needed-plugin', array( 'requires' => '99.0' ) );

		$processed    = array();
		$availability = Plugin_Installer::get_plugin_availability(
			$this->plugin_data( self::TEST_PLUGIN_SLUG, array( 'requires_plugins' => array( 'needed-plugin' ) ) ),
			$processed
		);

		$this->assertFalse( $availability['compatible_wp'] );
		$this->assertTrue( $availability['compatible_php'] );
		$this->assertTrue( $availability['installed'] );
		$this->assertTrue( $availability['activated'] );
		$this->assertSame( array( self::TEST_PLUGIN_SLUG, 'needed-plugin' ), array_keys( $processed ) );
	}

	/**
	 * Circular dependencies do not recurse forever.
	 */
	public function test_availability_survives_circular_dependencies() {
		$this->login_as( 'administrator' );
		$this->api_responses = array(
			'plugin-a' => $this->plugin_data( 'plugin-a', array( 'requires_plugins' => array( 'plugin-b' ) ) ),
			'plugin-b' => $this->plugin_data( 'plugin-b', array( 'requires_plugins' => array( 'plugin-a' ) ) ),
		);

		$processed    = array();
		$availability = Plugin_Installer::get_plugin_availability( Plugin_Installer::query_plugin_info( 'plugin-a' ), $processed );

		$this->assertTrue( $availability['can_install'] );
		$this->assertSame( array( 'plugin-a', 'plugin-b' ), array_keys( $processed ) );
		$this->assertSame( array( 'plugin-a', 'plugin-b' ), $this->api_calls );
	}

	/**
	 * Dependency chains are capped.
	 */
	public function test_install_stops_at_the_dependency_depth_limit() {
		$processed = array();
		$result    = Plugin_Installer::install_and_activate_plugin( 'deep-plugin', $processed, Plugin_Installer::MAX_DEPENDENCY_DEPTH + 1 );

		$this->assertWPError( $result );
		$this->assertSame( 'dependency_depth_exceeded', $result->get_error_code() );
		$this->assertSame( array(), $this->api_calls );
	}

	/**
	 * A plugin that was already handled in this run is skipped.
	 */
	public function test_install_skips_already_processed_plugins() {
		$processed = array( 'seen-plugin' );

		$this->assertNull( Plugin_Installer::install_and_activate_plugin( 'seen-plugin', $processed ) );
		$this->assertSame( array(), $this->api_calls );
	}

	/**
	 * API failures are passed through.
	 */
	public function test_install_returns_api_errors() {
		$result = Plugin_Installer::install_and_activate_plugin( 'broken-plugin' );

		$this->assertWPError( $result );
		$this->assertSame( 'mock_error', $result->get_error_code() );
	}

	/**
	 * Users who may not install plugins are stopped before the upgrader runs.
	 */
	public function test_install_requires_the_install_capability() {
		$this->login_as( 'subscriber' );
		$this->api_responses['new-plugin'] = $this->plugin_data( 'new-plugin' );

		$result = Plugin_Installer::install_and_activate_plugin( 'new-plugin' );

		$this->assertWPError( $result );
		$this->assertSame( 'cannot_install_plugin', $result->get_error_code() );
	}

	/**
	 * Users who may not activate plugins are stopped.
	 */
	public function test_install_requires_the_activate_capability() {
		$this->login_as( 'subscriber' );
		$plugin_file                                   = $this->create_plugin( self::TEST_PLUGIN_SLUG );
		$this->api_responses[ self::TEST_PLUGIN_SLUG ] = $this->plugin_data( self::TEST_PLUGIN_SLUG );

		$result = Plugin_Installer::install_and_activate_plugin( self::TEST_PLUGIN_SLUG );

		$this->assertWPError( $result );
		$this->assertSame( 'cannot_activate_plugin', $result->get_error_code() );
		$this->assertFalse( is_plugin_active( $plugin_file ) );
	}

	/**
	 * A plugin that is on disk is activated, dependencies first.
	 */
	public function test_install_activates_installed_plugins_and_their_dependencies() {
		$this->login_as( 'administrator' );
		$plugin_file     = $this->create_plugin( self::TEST_PLUGIN_SLUG );
		$dependency_file = $this->create_plugin( self::TEST_DEPENDENCY_SLUG );

		$this->api_responses = array(
			self::TEST_PLUGIN_SLUG     => $this->plugin_data( self::TEST_PLUGIN_SLUG, array( 'requires_plugins' => array( self::TEST_DEPENDENCY_SLUG ) ) ),
			self::TEST_DEPENDENCY_SLUG => $this->plugin_data( self::TEST_DEPENDENCY_SLUG ),
		);

		$activated = new MockAction();
		add_action( 'activated_plugin', array( $activated, 'action' ) );

		$this->assertNull( Plugin_Installer::install_and_activate_plugin( self::TEST_PLUGIN_SLUG ) );
		$this->assertTrue( is_plugin_active( $plugin_file ) );
		$this->assertTrue( is_plugin_active( $dependency_file ) );
		$this->assertSame( array( array( $dependency_file ), array( $plugin_file ) ), $activated->get_args() );
	}

	/**
	 * The install action needs a valid nonce.
	 */
	public function test_install_action_requires_a_nonce() {
		$this->login_as( 'administrator' );

		$this->expectException( WPDieException::class );

		$this->dispatch_install_action( 'webmention', false );
	}

	/**
	 * The install action needs a user who may manage plugins.
	 */
	public function test_install_action_requires_plugin_capabilities() {
		$this->login_as( 'subscriber' );

		$this->expectException( WPDieException::class );
		$this->expectExceptionMessage( 'not allowed to manage plugins' );

		$this->dispatch_install_action( 'webmention' );
	}

	/**
	 * The install action needs a slug.
	 */
	public function test_install_action_requires_a_slug() {
		$this->login_as( 'administrator' );

		$this->expectException( WPDieException::class );
		$this->expectExceptionMessage( 'Missing required parameter.' );

		$this->dispatch_install_action( null );
	}

	/**
	 * Only allow-listed plugins can be installed through the action.
	 */
	public function test_install_action_rejects_unknown_plugins() {
		$this->login_as( 'administrator' );

		$this->expectException( WPDieException::class );
		$this->expectExceptionMessage( 'Invalid plugin.' );

		$this->dispatch_install_action( 'akismet' );
	}

	/**
	 * Failures while installing end in an error page with a way back.
	 */
	public function test_install_action_reports_install_errors() {
		$this->login_as( 'administrator' );

		$this->expectException( WPDieException::class );
		$this->expectExceptionMessage( 'No such plugin: webmention' );

		$this->dispatch_install_action( 'webmention' );
	}

	/**
	 * Administrators get an install button that points at the nonced action.
	 */
	public function test_card_offers_install_to_administrators() {
		$this->login_as( 'administrator' );

		$html = $this->render_card(
			$this->plugin_data(
				'new-plugin',
				array(
					'icons' => array(
						'1x'  => 'https://example.org/1x.png',
						'svg' => 'https://example.org/icon.svg',
					),
				)
			)
		);

		$this->assertStringContainsString( 'plugin-card-new-plugin', $html );
		$this->assertStringContainsString( '>Install Now<', $html );
		$this->assertStringContainsString( 'action=indieweb_install_activate_plugin', $html );
		$this->assertStringContainsString( 'slug=new-plugin', $html );
		$this->assertStringContainsString( '_wpnonce=', $html );
		$this->assertStringContainsString( 'https://example.org/icon.svg', $html, 'The vector icon is preferred.' );
		$this->assertStringContainsString( 'open-plugin-details-modal', $html );
		$this->assertStringContainsString( 'More Details', $html );
	}

	/**
	 * An installed plugin gets an activate button, an active one a disabled "Active".
	 */
	public function test_card_reflects_installed_and_active_state() {
		$this->login_as( 'administrator' );
		$plugin_file = $this->create_plugin( self::TEST_PLUGIN_SLUG );

		$html = $this->render_card( $this->plugin_data( self::TEST_PLUGIN_SLUG ) );

		$this->assertStringContainsString( '>Activate<', $html );
		$this->assertStringNotContainsString( 'Install Now', $html );

		activate_plugin( $plugin_file );

		$html = $this->render_card( $this->plugin_data( self::TEST_PLUGIN_SLUG ) );

		$this->assertStringContainsString( '>Active<', $html );
		$this->assertStringContainsString( 'button-disabled', $html );
		$this->assertStringNotContainsString( 'indieweb_install_activate_plugin', $html );
	}

	/**
	 * Incompatible plugins get a disabled button and a notice.
	 */
	public function test_card_disables_incompatible_plugins() {
		$this->login_as( 'administrator' );

		$html = $this->render_card( $this->plugin_data( 'new-plugin', array( 'requires' => '99.0' ) ) );

		$this->assertStringContainsString( 'does not work with your version of WordPress', $html );
		$this->assertStringContainsString( 'button-disabled', $html );
		$this->assertStringNotContainsString( 'indieweb_install_activate_plugin', $html );

		$html = $this->render_card(
			$this->plugin_data(
				'new-plugin',
				array(
					'requires'     => '99.0',
					'requires_php' => '99.0',
				)
			)
		);

		$this->assertStringContainsString( 'does not work with your versions of WordPress and PHP', $html );
	}

	/**
	 * Users without plugin capabilities only get a link to the directory.
	 */
	public function test_card_offers_no_install_to_subscribers() {
		$this->login_as( 'subscriber' );

		$html = $this->render_card( $this->plugin_data( 'new-plugin' ) );

		$this->assertStringContainsString( 'Cannot Install', $html );
		$this->assertStringContainsString( 'https://wordpress.org/plugins/new-plugin/', $html );
		$this->assertStringContainsString( 'Visit plugin site', $html );
		$this->assertStringNotContainsString( 'indieweb_install_activate_plugin', $html );
		$this->assertStringNotContainsString( 'open-plugin-details-modal', $html );
	}

	/**
	 * Markup from the API is stripped before it is shown.
	 */
	public function test_card_strips_markup_from_api_data() {
		$this->login_as( 'administrator' );

		$html = $this->render_card(
			$this->plugin_data(
				'new-plugin',
				array(
					'name'              => 'Evil <script>alert(1)</script> Plugin',
					'short_description' => 'Nice <img src=x onerror=alert(1)> plugin',
				)
			)
		);

		$this->assertStringNotContainsString( '<script>', $html );
		$this->assertStringNotContainsString( 'onerror', $html );
		$this->assertStringContainsString( 'Evil  Plugin', $html );
	}

	/**
	 * The screen lists plugins that could not be looked up and confirms activations.
	 */
	public function test_screen_reports_lookup_errors_and_activations() {
		$this->login_as( 'administrator' );
		$plugin_file = $this->create_plugin( self::TEST_PLUGIN_SLUG );
		activate_plugin( $plugin_file );

		add_filter( 'indieweb_recommended_plugins', fn() => array( self::TEST_PLUGIN_SLUG, 'broken-plugin' ) );
		$this->api_responses[ self::TEST_PLUGIN_SLUG ] = $this->plugin_data( self::TEST_PLUGIN_SLUG, array( 'name' => 'My Test Plugin' ) );
		$_GET['activate']                              = self::TEST_PLUGIN_SLUG;

		$html = get_echo( array( Plugin_Installer::class, 'render_plugins_ui' ) );

		$this->assertStringContainsString( 'My Test Plugin was successfully installed and activated.', $html );
		$this->assertStringContainsString( 'Failed to query the WordPress.org Plugin Directory for the following plugin:', $html );
		$this->assertStringContainsString( '<code>broken-plugin</code>', $html );
		$this->assertStringContainsString( 'plugin-card-' . self::TEST_PLUGIN_SLUG, $html );
		$this->assertStringNotContainsString( 'plugin-card-broken-plugin', $html );
	}

	/**
	 * The activation notice only shows for allow-listed plugins that really are active.
	 */
	public function test_screen_ignores_bogus_activation_notices() {
		$this->login_as( 'administrator' );
		add_filter( 'indieweb_recommended_plugins', fn() => array( 'broken-plugin' ) );
		$_GET['activate'] = 'akismet';

		$html = get_echo( array( Plugin_Installer::class, 'render_plugins_ui' ) );

		$this->assertStringNotContainsString( 'successfully installed and activated', $html );
	}
}
