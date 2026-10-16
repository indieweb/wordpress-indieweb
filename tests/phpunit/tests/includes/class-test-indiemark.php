<?php
/**
 * IndieMark assessment and rendering tests.
 *
 * @package Indieweb
 */

/**
 * Test the shared block and shortcode display.
 */
class Test_IndieMark extends WP_UnitTestCase {
	/**
	 * Prevent real HTTP calls in detection tests.
	 */
	public function set_up() {
		parent::set_up();
		add_filter(
			'pre_http_request',
			static function () {
				return new WP_Error( 'test_http_disabled' );
			}
		);
	}
	/**
	 * Both entry points are registered.
	 */
	public function test_registration() {
		$this->assertTrue( shortcode_exists( 'indieweb_indiemark' ) );
		$this->assertTrue( WP_Block_Type_Registry::get_instance()->is_registered( 'indieweb/indiemark' ) );
	}

	/**
	 * Local settings and published content affect detection.
	 */
	public function test_detection() {
		update_option( 'blog_public', '0' );
		$features = \Indieweb\IndieMark::detect();
		$this->assertFalse( $features['indexing'][2] );
		$this->factory->post->create_many( 2, array( 'post_status' => 'publish' ) );
		$features = \Indieweb\IndieMark::detect();
		$this->assertTrue( $features['posts'][2] );
		update_option( 'blog_public', '1' );
	}

	/**
	 * Domains are assumed to belong to the owner, while local addresses are excluded.
	 */
	public function test_domain_detection() {
		$url      = '';
		$home_url = static function () use ( &$url ) {
			return $url;
		};
		add_filter( 'home_url', $home_url );
		foreach ( array( 'https://example.com', 'https://blog.example.com', 'https://example.com:8443/blog', 'https://myblog.wordpress.com' ) as $url ) {
			$this->assertTrue( \Indieweb\IndieMark::detect()['domain'][2], $url );
		}
		foreach ( array( 'http://localhost:8890', 'http://blog.localhost', 'http://127.0.0.1', 'http://[::1]', 'http://wordpress' ) as $url ) {
			$this->assertFalse( \Indieweb\IndieMark::detect()['domain'][2], $url );
		}
		remove_filter( 'home_url', $home_url );
	}

	/**
	 * Network-active IndieAuth contributes to automatic detection.
	 */
	public function test_network_plugin_evidence() {
		$previous = get_site_option( 'active_sitewide_plugins', array() );
		update_site_option( 'active_sitewide_plugins', array( 'indieauth/indieauth.php' => time() ) );
		$features = \Indieweb\IndieMark::detect();
		update_site_option( 'active_sitewide_plugins', $previous );
		$this->assertTrue( $features['authentication'][2] );
		$this->assertStringContainsString( 'IndieAuth', $features['authentication'][3] );
	}

	/**
	 * Automatic levels require all earlier checks and exclusions take priority.
	 */
	public function test_automatic_levels_and_overrides() {
		update_option( 'home', 'http://localhost' );
		$keys = implode( ',', array_keys( \Indieweb\IndieMark::detect() ) );
		$this->assertStringContainsString( 'IndieMark Level 0', do_shortcode( '[indieweb_indiemark]' ) );
		$this->assertStringContainsString( 'IndieMark Level 5', do_shortcode( '[indieweb_indiemark confirm="' . $keys . '"]' ) );
		$this->assertStringContainsString( 'IndieMark Level 0', do_shortcode( '[indieweb_indiemark confirm="' . $keys . '" exclude="authentication"]' ) );
	}

	/**
	 * Manual levels and compact rendering work through both entry points.
	 */
	public function test_shared_rendering() {
		update_option( 'home', 'http://localhost' );
		$shortcode = do_shortcode( '[indieweb_indiemark level="4" details="false"]' );
		$block     = render_block(
			array(
				'blockName'    => 'indieweb/indiemark',
				'attrs'        => array(
					'level'   => '4',
					'details' => false,
				),
				'innerBlocks'  => array(),
				'innerHTML'    => '',
				'innerContent' => array(),
			)
		);
		$this->assertStringContainsString( 'IndieMark Level 4', $shortcode );
		$this->assertStringContainsString( 'IndieMark Level 4', $block );
		$this->assertStringNotContainsString( '<ul>', $shortcode );
		$this->assertStringNotContainsString( '<ul>', $block );
		$this->assertStringNotContainsString( '<a ', $block );
		$this->assertStringNotContainsString( '<button', $block );
		$this->assertStringContainsString( 'IndieMark Level 0', do_shortcode( '[indieweb_indiemark level="99"]' ) );
		$this->assertStringNotContainsString( '<ul>', do_shortcode( '[indieweb_indiemark]' ) );
		$this->assertStringNotContainsString( 'Self-reported', $shortcode );
		$this->assertStringContainsString( '<ul>', do_shortcode( '[indieweb_indiemark details="true"]' ) );
		$this->assertStringStartsWith( '<div class="wp-block-indieweb-indiemark">', $shortcode );
	}

	/**
	 * Detect rendered h-card markup and the configured site icon together.
	 */
	public function test_homepage_hcard_and_icon() {
		$body     = '<div class="profile h-card"><span class="p-name">Alice</span></div>';
		$requests = 0;
		$mock     = static function () use ( &$body, &$requests ) {
			++$requests;
			return array(
				'response' => array( 'code' => 200 ),
				'body'     => $body,
			);
		};
		add_filter( 'pre_http_request', $mock );
		$attachment = $this->factory->attachment->create_upload_object( DIR_TESTDATA . '/images/canola.jpg' );
		update_option( 'site_icon', $attachment );
		\Indieweb\IndieMark::refresh();
		$this->assertTrue( \Indieweb\IndieMark::detect()['hcard'][2] );
		$this->assertTrue( \Indieweb\IndieMark::detect()['hcard'][2] );
		$this->assertSame( 1, $requests );
		$body = '<div class="h-cardish">No h-card</div><script>"<div class=\"h-card\">"</script>';
		\Indieweb\IndieMark::refresh();
		$this->assertFalse( \Indieweb\IndieMark::detect()['hcard'][2] );
		delete_option( 'site_icon' );
		$this->assertFalse( \Indieweb\IndieMark::detect()['hcard'][2] );
		remove_filter( 'pre_http_request', $mock );
	}

	/**
	 * Installed capabilities calculate the mark without any manual confirmations.
	 */
	public function test_automatic_plugin_detection() {
		$home = static function () {
			return 'https://example.com/';
		};
		$http = static function () {
			return array(
				'response' => array( 'code' => 200 ),
				'body'     => '<div class="h-card"></div>',
			);
		};
		add_filter( 'home_url', $home );
		add_filter( 'pre_http_request', $http );
		$this->factory->post->create_many( 2, array( 'post_status' => 'publish' ) );
		update_option( 'site_icon', $this->factory->attachment->create_upload_object( DIR_TESTDATA . '/images/canola.jpg' ) );
		\Indieweb\IndieMark::refresh();
		update_option( 'blog_public', '1' );
		update_option( 'default_comment_status', 'open' );
		$plugins = array( 'indieauth/indieauth.php', 'microformats2/microformats2.php', 'post-kinds-for-indieweb-in-block-themes/plugin.php', 'syndication-links/syndication-links.php', 'pubsubhubbub/pubsubhubbub.php', 'webmention/webmention.php' );
		update_option( 'active_plugins', $plugins );
		$this->assertStringContainsString( 'IndieMark Level 4', do_shortcode( '[indieweb_indiemark]' ) );
		update_option( 'webmention_support_post_types', array( 'page' ) );
		$this->assertStringContainsString( 'IndieMark Level 2', do_shortcode( '[indieweb_indiemark]' ) );
		update_option( 'blog_public', '0' );
		$this->assertStringContainsString( 'IndieMark Level 0', do_shortcode( '[indieweb_indiemark]' ) );
		remove_filter( 'home_url', $home );
		remove_filter( 'pre_http_request', $http );
	}

	/**
	 * A theme providing h-entry can be detected without a microformats plugin.
	 */
	public function test_rendered_post_markup_detection() {
		$this->factory->post->create_many( 2, array( 'post_status' => 'publish' ) );
		$http = static function () {
			return array(
				'response' => array( 'code' => 200 ),
				'body'     => '<article class="post h-entry"></article>',
			);
		};
		add_filter( 'pre_http_request', $http );
		\Indieweb\IndieMark::refresh();
		$this->assertTrue( \Indieweb\IndieMark::detect()['markup'][2] );
		remove_filter( 'pre_http_request', $http );
	}

	/**
	 * Plugins and themes can declare microformats2 support through WordPress.
	 */
	public function test_microformats_theme_support() {
		add_theme_support( 'microformats2' );
		$this->assertTrue( \Indieweb\IndieMark::detect()['markup'][2] );
		remove_theme_support( 'microformats2' );
		$this->assertFalse( \Indieweb\IndieMark::detect()['markup'][2] );
	}

	/**
	 * Standard articles plus a supported post format provide multiple content types.
	 */
	public function test_post_formats_detection() {
		remove_theme_support( 'post-formats' );
		$this->assertFalse( \Indieweb\IndieMark::detect()['post_types'][2] );
		add_theme_support( 'post-formats', array( 'status', 'image' ) );
		$this->assertTrue( \Indieweb\IndieMark::detect()['post_types'][2] );
		remove_theme_support( 'post-formats' );
		$this->assertFalse( \Indieweb\IndieMark::detect()['post_types'][2] );
	}

	/**
	 * SemPress, Autonomie, and their child themes supply microformats.
	 */
	public function test_known_microformats_themes() {
		$previous = get_option( 'template' );
		foreach ( array( 'sempress', 'autonomie' ) as $theme ) {
			update_option( 'template', $theme );
			$this->assertTrue( \Indieweb\IndieMark::detect()['markup'][2] );
		}
		update_option( 'template', $previous );
	}

	/**
	 * IndieBlocks detection respects its enabled modules and changes schedule a markup refresh.
	 */
	public function test_indieblocks_settings_and_cache() {
		update_option( 'active_plugins', array( 'indieblocks/indieblocks.php' ) );
		add_theme_support( 'add_block_template_part_support' );
		update_option( 'indieblocks_settings', array() );
		$features = \Indieweb\IndieMark::detect();
		$this->assertFalse( $features['markup'][2] );
		$this->assertFalse( $features['post_types'][2] );
		$this->assertFalse( $features['webmentions'][2] );
		$this->assertNotFalse( wp_next_scheduled( 'indieweb_indiemark_refresh' ) );
		wp_clear_scheduled_hook( 'indieweb_indiemark_refresh' );
		update_option(
			'indieblocks_settings',
			array(
				'add_mf2'               => true,
				'enable_notes'          => true,
				'webmention'            => true,
				'webmention_post_types' => array( 'post' ),
			)
		);
		$this->assertNotFalse( wp_next_scheduled( 'indieweb_indiemark_refresh' ) );
		$features = \Indieweb\IndieMark::detect();
		$this->assertTrue( $features['markup'][2] );
		$this->assertTrue( $features['post_types'][2] );
		$this->assertTrue( $features['webmentions'][2] );
		wp_clear_scheduled_hook( 'indieweb_indiemark_refresh' );
		$this->factory->post->create( array( 'post_status' => 'draft' ) );
		$this->assertFalse( wp_next_scheduled( 'indieweb_indiemark_refresh' ) );
		$this->factory->post->create();
		$this->assertNotFalse( wp_next_scheduled( 'indieweb_indiemark_refresh' ) );
		remove_theme_support( 'add_block_template_part_support' );
	}
}
