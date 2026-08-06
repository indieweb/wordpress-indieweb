<?php
/**
 * Test Hcard User class.
 *
 * @package Indieweb
 */

/**
 * Test Hcard User class.
 */
class Test_Hcard_User extends WP_UnitTestCase {

	/**
	 * Test user ID.
	 *
	 * @var int
	 */
	protected static $user_id;

	/**
	 * Set up before class.
	 *
	 * @param WP_UnitTest_Factory $factory Factory instance.
	 */
	public static function wpSetUpBeforeClass( WP_UnitTest_Factory $factory ) {
		self::$user_id = $factory->user->create(
			array(
				'role'         => 'administrator',
				'display_name' => 'Test User',
				'user_url'     => 'https://example.com',
			)
		);
	}

	/**
	 * Test that silos returns an array.
	 */
	public function test_silos_returns_array() {
		$silos = \Indieweb\Hcard\User::silos();

		$this->assertIsArray( $silos );
		$this->assertNotEmpty( $silos );
	}

	/**
	 * Test that silos contains expected keys.
	 */
	public function test_silos_contains_expected_keys() {
		$silos = \Indieweb\Hcard\User::silos();

		$this->assertArrayHasKey( 'github', $silos );
		$this->assertArrayHasKey( 'twitter', $silos );
		$this->assertArrayHasKey( 'mastodon', $silos );
		$this->assertArrayHasKey( 'bluesky', $silos );
	}

	/**
	 * Test that silos have required structure.
	 */
	public function test_silos_have_required_structure() {
		$silos = \Indieweb\Hcard\User::silos();

		foreach ( $silos as $silo => $details ) {
			$this->assertArrayHasKey( 'baseurl', $details, "Silo {$silo} should have baseurl" );
			$this->assertArrayHasKey( 'display', $details, "Silo {$silo} should have display" );
		}
	}

	/**
	 * Test address fields returns an array.
	 */
	public function test_address_fields_returns_array() {
		$fields = \Indieweb\Hcard\User::address_fields();

		$this->assertIsArray( $fields );
		$this->assertNotEmpty( $fields );
	}

	/**
	 * Test address fields contains expected keys.
	 */
	public function test_address_fields_contains_expected_keys() {
		$fields = \Indieweb\Hcard\User::address_fields();

		$this->assertArrayHasKey( 'street_address', $fields );
		$this->assertArrayHasKey( 'locality', $fields );
		$this->assertArrayHasKey( 'region', $fields );
		$this->assertArrayHasKey( 'postal_code', $fields );
		$this->assertArrayHasKey( 'country_name', $fields );
	}

	/**
	 * Test extra fields returns an array.
	 */
	public function test_extra_fields_returns_array() {
		$fields = \Indieweb\Hcard\User::extra_fields();

		$this->assertIsArray( $fields );
		$this->assertNotEmpty( $fields );
	}

	/**
	 * Test extra fields contains expected keys.
	 */
	public function test_extra_fields_contains_expected_keys() {
		$fields = \Indieweb\Hcard\User::extra_fields();

		$this->assertArrayHasKey( 'job_title', $fields );
		$this->assertArrayHasKey( 'organization', $fields );
		$this->assertArrayHasKey( 'honorific_prefix', $fields );
	}

	/**
	 * Test clean_url with valid URL.
	 */
	public function test_clean_url_with_valid_url() {
		$url    = 'https://example.com/path';
		$result = \Indieweb\Hcard\User::clean_url( $url );

		$this->assertEquals( $url, $result );
	}

	/**
	 * Test clean_url with invalid URL.
	 */
	public function test_clean_url_with_invalid_url() {
		$result = \Indieweb\Hcard\User::clean_url( 'not-a-url' );

		$this->assertFalse( $result );
	}

	/**
	 * Test clean_url upgrades http to https for known domains.
	 */
	public function test_clean_url_upgrades_http_to_https() {
		$result = \Indieweb\Hcard\User::clean_url( 'http://github.com/user' );

		$this->assertStringStartsWith( 'https://', $result );
	}

	/**
	 * Test clean_urls filters array of URLs.
	 */
	public function test_clean_urls_filters_array() {
		$urls   = array(
			'https://example.com',
			'not-a-url',
			'https://example.org',
			'',
		);
		$result = \Indieweb\Hcard\User::clean_urls( $urls );

		$this->assertCount( 2, $result );
		$this->assertContains( 'https://example.com', $result );
		$this->assertContains( 'https://example.org', $result );
	}

	/**
	 * Test get_hcard_display_defaults returns array.
	 */
	public function test_get_hcard_display_defaults() {
		$defaults = \Indieweb\Hcard\User::get_hcard_display_defaults();

		$this->assertIsArray( $defaults );
		$this->assertArrayHasKey( 'avatar', $defaults );
		$this->assertArrayHasKey( 'location', $defaults );
		$this->assertArrayHasKey( 'notes', $defaults );
		$this->assertArrayHasKey( 'email', $defaults );
		$this->assertArrayHasKey( 'me', $defaults );
	}

	/**
	 * Test hcard returns false for invalid user.
	 */
	public function test_hcard_returns_false_for_invalid_user() {
		$result = \Indieweb\Hcard\User::hcard( null );

		$this->assertFalse( $result );
	}

	/**
	 * Test hcard returns string for valid user.
	 */
	public function test_hcard_returns_string_for_valid_user() {
		$result = \Indieweb\Hcard\User::hcard( self::$user_id );

		$this->assertIsString( $result );
		$this->assertStringContainsString( 'h-card', $result );
	}
}
