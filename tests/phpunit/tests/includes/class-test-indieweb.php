<?php
/**
 * Test Indieweb class.
 *
 * @package Indieweb
 */

/**
 * Test Indieweb class.
 */
class Test_Indieweb extends WP_UnitTestCase {

	/**
	 * Test that the plugin version constant is defined.
	 */
	public function test_version_constant_defined() {
		$this->assertTrue( defined( 'INDIEWEB_VERSION' ) );
		$this->assertNotEmpty( INDIEWEB_VERSION );
	}

	/**
	 * Test that plugin constants are defined.
	 */
	public function test_plugin_constants_defined() {
		$this->assertTrue( defined( 'INDIEWEB_PLUGIN_DIR' ) );
		$this->assertTrue( defined( 'INDIEWEB_PLUGIN_BASENAME' ) );
		$this->assertTrue( defined( 'INDIEWEB_PLUGIN_FILE' ) );
		$this->assertTrue( defined( 'INDIEWEB_PLUGIN_URL' ) );
	}

	/**
	 * Test that the singleton returns an instance.
	 */
	public function test_get_instance() {
		$instance = \Indieweb\Indieweb::get_instance();

		$this->assertInstanceOf( \Indieweb\Indieweb::class, $instance );
	}

	/**
	 * Test that get_instance returns the same instance.
	 */
	public function test_singleton_returns_same_instance() {
		$instance1 = \Indieweb\Indieweb::get_instance();
		$instance2 = \Indieweb\Indieweb::get_instance();

		$this->assertSame( $instance1, $instance2 );
	}

	/**
	 * Test that get_version returns the version constant.
	 */
	public function test_get_version() {
		$instance = \Indieweb\Indieweb::get_instance();

		$this->assertEquals( INDIEWEB_VERSION, $instance->get_version() );
	}

	/**
	 * Test that the version function exists and returns version.
	 */
	public function test_version_function() {
		$this->assertEquals( INDIEWEB_VERSION, \Indieweb\version() );
	}

	/**
	 * Test that hooks are registered after init.
	 */
	public function test_hooks_registered() {
		$instance = \Indieweb\Indieweb::get_instance();
		$instance->init();

		$this->assertNotFalse( has_action( 'wp_enqueue_scripts', array( $instance, 'enqueue_style' ) ) );
		$this->assertNotFalse( has_action( 'admin_enqueue_scripts', array( $instance, 'enqueue_admin_style' ) ) );
		$this->assertNotFalse( has_action( 'admin_menu', array( $instance, 'add_menu_item' ) ) );
		$this->assertNotFalse( has_action( 'admin_init', array( $instance, 'privacy_declaration' ) ) );
	}

	/**
	 * Test that the indieweb_loaded action is fired.
	 */
	public function test_indieweb_loaded_action_fired() {
		$this->assertNotFalse( did_action( 'indieweb_loaded' ) );
	}
}
