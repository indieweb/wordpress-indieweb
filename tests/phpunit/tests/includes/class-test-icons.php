<?php
/**
 * Test Icons class and the Icons API compatibility shims.
 *
 * @package Indieweb
 */

use Indieweb\Icons;

/**
 * Test Icons class and the Icons API compatibility shims.
 *
 * The shim tests only run when the shims are active, so they are skipped
 * on WordPress 7.1 or later where core provides the Icons API.
 */
class Test_Icons extends WP_UnitTestCase {

	/**
	 * Skip the test if core provides the Icons API natively.
	 */
	private function skip_without_shims() {
		if ( ! function_exists( '_indieweb_icons_registry' ) ) {
			$this->markTestSkipped( 'WordPress provides the Icons API natively.' );
		}
	}

	/**
	 * Register a collection with a unique slug.
	 *
	 * @return string The collection slug.
	 */
	private function register_test_collection() {
		$slug = 'testcol-' . uniqid();
		wp_register_icon_collection( $slug, array( 'label' => 'Test Collection' ) );
		return $slug;
	}

	/**
	 * Test that the bundled icons are registered on init.
	 */
	public function test_bundled_icon_is_registered_on_init() {
		$icon = wp_get_icon( 'indieweb/mastodon' );

		$this->assertStringContainsString( '<svg', $icon );
		$this->assertStringContainsString( '<path', $icon );
	}

	/**
	 * Test that wp_get_icon returns an empty string for unknown icons.
	 */
	public function test_wp_get_icon_unknown_icon_returns_empty_string() {
		$this->assertSame( '', wp_get_icon( 'indieweb/this-icon-does-not-exist' ) );
	}

	/**
	 * Test that calling register_icons a second time does not re-register icons.
	 */
	public function test_register_icons_is_idempotent() {
		Icons::register_icons();

		$this->assertStringContainsString( '<svg', wp_get_icon( 'indieweb/mastodon' ) );
	}

	/**
	 * Test that get_icon_svg uses the registered icon.
	 */
	public function test_get_icon_svg_uses_registered_icon() {
		$svg = \Indieweb\Relme\Domain_Icon_Map::get_icon_svg( 'mastodon' );

		$this->assertStringContainsString( '<svg', $svg );
		$this->assertStringContainsString( '<path', $svg );
	}

	/**
	 * Test registering and retrieving an icon via the shims.
	 */
	public function test_shim_register_and_get_icon() {
		$this->skip_without_shims();
		$collection = $this->register_test_collection();

		$registered = wp_register_icon(
			$collection . '/star',
			array(
				'label'   => 'Star',
				'content' => '<svg viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path d="M0 0h24v24H0z"/></svg>',
			)
		);

		$this->assertTrue( $registered );

		$icon = wp_get_icon( $collection . '/star' );

		$this->assertStringContainsString( '<path d="M0 0h24v24H0z"', $icon );
		$this->assertStringContainsString( 'width="24"', $icon );
		$this->assertStringContainsString( 'height="24"', $icon );
		$this->assertStringContainsString( 'aria-hidden="true"', $icon );
		$this->assertStringContainsString( 'focusable="false"', $icon );
	}

	/**
	 * Test the size argument of the wp_get_icon shim.
	 */
	public function test_shim_get_icon_custom_size() {
		$this->skip_without_shims();
		$collection = $this->register_test_collection();
		wp_register_icon(
			$collection . '/star',
			array(
				'label'   => 'Star',
				'content' => '<svg viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path d="M0 0h24v24H0z"/></svg>',
			)
		);

		$icon = wp_get_icon( $collection . '/star', array( 'size' => 32 ) );

		$this->assertStringContainsString( 'width="32"', $icon );
		$this->assertStringContainsString( 'height="32"', $icon );
	}

	/**
	 * Test that a null size keeps the intrinsic dimensions.
	 */
	public function test_shim_get_icon_null_size() {
		$this->skip_without_shims();
		$collection = $this->register_test_collection();
		wp_register_icon(
			$collection . '/star',
			array(
				'label'   => 'Star',
				'content' => '<svg viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path d="M0 0h24v24H0z"/></svg>',
			)
		);

		$icon = wp_get_icon( $collection . '/star', array( 'size' => null ) );

		$this->assertStringNotContainsString( 'width=', $icon );
		$this->assertStringNotContainsString( 'height=', $icon );
	}

	/**
	 * Test the class argument of the wp_get_icon shim.
	 */
	public function test_shim_get_icon_class() {
		$this->skip_without_shims();
		$collection = $this->register_test_collection();
		wp_register_icon(
			$collection . '/star',
			array(
				'label'   => 'Star',
				'content' => '<svg viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path d="M0 0h24v24H0z"/></svg>',
			)
		);

		$icon = wp_get_icon( $collection . '/star', array( 'class' => 'foo bar' ) );

		$this->assertStringContainsString( 'foo', $icon );
		$this->assertStringContainsString( 'bar', $icon );
	}

	/**
	 * Test the label argument of the wp_get_icon shim.
	 */
	public function test_shim_get_icon_label() {
		$this->skip_without_shims();
		$collection = $this->register_test_collection();
		wp_register_icon(
			$collection . '/star',
			array(
				'label'   => 'Star',
				'content' => '<svg viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path d="M0 0h24v24H0z"/></svg>',
			)
		);

		$icon = wp_get_icon( $collection . '/star', array( 'label' => 'A star' ) );

		$this->assertStringContainsString( 'role="img"', $icon );
		$this->assertStringContainsString( 'aria-label="A star"', $icon );
		$this->assertStringNotContainsString( 'aria-hidden', $icon );
	}

	/**
	 * Test that the shim sanitizes icon content.
	 */
	public function test_shim_register_icon_sanitizes_content() {
		$this->skip_without_shims();
		$collection = $this->register_test_collection();
		wp_register_icon(
			$collection . '/star',
			array(
				'label'   => 'Star',
				'content' => '<svg viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><title>Star</title><script>alert(1)</script><path d="M0 0h24v24H0z"/></svg>',
			)
		);

		$icon = wp_get_icon( $collection . '/star' );

		$this->assertStringNotContainsString( '<script', $icon );
		$this->assertStringNotContainsString( '<title', $icon );
		$this->assertStringContainsString( '<path', $icon );
	}

	/**
	 * Test that the shim loads icon content from a file.
	 */
	public function test_shim_register_icon_with_file_path() {
		$this->skip_without_shims();
		$collection = $this->register_test_collection();

		$registered = wp_register_icon(
			$collection . '/mastodon',
			array(
				'label'     => 'Mastodon',
				'file_path' => INDIEWEB_PLUGIN_DIR . 'static/svg/mastodon.svg',
			)
		);

		$this->assertTrue( $registered );

		$icon = wp_get_icon( $collection . '/mastodon' );

		$this->assertStringContainsString( '<svg', $icon );
		$this->assertStringContainsString( '<path', $icon );
	}

	/**
	 * Test that icons cannot be registered for an unknown collection.
	 */
	public function test_shim_register_icon_requires_collection() {
		$this->skip_without_shims();

		$result = wp_register_icon(
			'unregistered-collection/star',
			array(
				'label'   => 'Star',
				'content' => '<svg><path d="M0 0h24v24H0z"/></svg>',
			)
		);

		$this->assertFalse( $result );
	}

	/**
	 * Test that an icon cannot be registered twice.
	 */
	public function test_shim_register_icon_duplicate_returns_false() {
		$this->skip_without_shims();
		$collection = $this->register_test_collection();
		$args       = array(
			'label'   => 'Star',
			'content' => '<svg><path d="M0 0h24v24H0z"/></svg>',
		);

		$this->assertTrue( wp_register_icon( $collection . '/star', $args ) );
		$this->assertFalse( wp_register_icon( $collection . '/star', $args ) );
	}

	/**
	 * Test that invalid icon names are rejected by the shim.
	 */
	public function test_shim_register_icon_invalid_names() {
		$this->skip_without_shims();
		$collection = $this->register_test_collection();
		$args       = array(
			'label'   => 'Star',
			'content' => '<svg><path d="M0 0h24v24H0z"/></svg>',
		);

		$this->assertFalse( wp_register_icon( 'not-namespaced', $args ) );
		$this->assertFalse( wp_register_icon( $collection . '/UpperCase', $args ) );
		$this->assertFalse( wp_register_icon( $collection . '/-invalid', $args ) );
	}

	/**
	 * Test that icons without content and file path are rejected.
	 */
	public function test_shim_register_icon_requires_content_or_file() {
		$this->skip_without_shims();
		$collection = $this->register_test_collection();

		$this->assertFalse( wp_register_icon( $collection . '/star', array( 'label' => 'Star' ) ) );
	}

	/**
	 * Test unregistering an icon.
	 */
	public function test_shim_unregister_icon() {
		$this->skip_without_shims();
		$collection = $this->register_test_collection();
		wp_register_icon(
			$collection . '/star',
			array(
				'label'   => 'Star',
				'content' => '<svg><path d="M0 0h24v24H0z"/></svg>',
			)
		);

		$this->assertTrue( wp_unregister_icon( $collection . '/star' ) );
		$this->assertSame( '', wp_get_icon( $collection . '/star' ) );
		$this->assertFalse( wp_unregister_icon( $collection . '/star' ) );
	}

	/**
	 * Test that unregistering a collection also removes its icons.
	 */
	public function test_shim_unregister_collection_removes_icons() {
		$this->skip_without_shims();
		$collection = $this->register_test_collection();
		wp_register_icon(
			$collection . '/star',
			array(
				'label'   => 'Star',
				'content' => '<svg><path d="M0 0h24v24H0z"/></svg>',
			)
		);

		$this->assertTrue( wp_unregister_icon_collection( $collection ) );
		$this->assertSame( '', wp_get_icon( $collection . '/star' ) );
	}

	/**
	 * Test the legacy SVG decoration used on WordPress before 6.2.
	 */
	public function test_legacy_decoration_defaults() {
		$this->skip_without_shims();

		$icon = _indieweb_decorate_icon_svg_legacy(
			'<svg viewBox="0 0 24 24"><path d="M0 0h24v24H0z"/></svg>',
			array(
				'size'  => 24,
				'class' => '',
				'label' => '',
			)
		);

		$this->assertStringContainsString( '<svg width="24" height="24" aria-hidden="true" focusable="false"', $icon );
		$this->assertStringContainsString( '<path d="M0 0h24v24H0z"', $icon );
	}

	/**
	 * Test the legacy SVG decoration with a label and custom class.
	 */
	public function test_legacy_decoration_label_and_class() {
		$this->skip_without_shims();

		$icon = _indieweb_decorate_icon_svg_legacy(
			'<svg viewBox="0 0 24 24"><path d="M0 0h24v24H0z"/></svg>',
			array(
				'size'  => null,
				'class' => 'foo bar',
				'label' => 'A star',
			)
		);

		$this->assertStringContainsString( 'class="foo bar"', $icon );
		$this->assertStringContainsString( 'role="img" aria-label="A star"', $icon );
		$this->assertStringNotContainsString( 'width=', $icon );
		$this->assertStringNotContainsString( 'aria-hidden', $icon );
	}
}
