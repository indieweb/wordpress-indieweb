<?php
/**
 * Test Relme Domain_Icon_Map class.
 *
 * @package Indieweb
 */

/**
 * Test Relme Domain_Icon_Map class.
 */
class Test_Relme_Domain_Icon_Map extends WP_UnitTestCase {

	/**
	 * Test split_domain with two-part domain.
	 */
	public function test_split_domain_two_parts() {
		$result = \Indieweb\Relme\Domain_Icon_Map::split_domain( 'example.com' );

		$this->assertEquals( 'example', $result );
	}

	/**
	 * Test split_domain with three-part domain.
	 */
	public function test_split_domain_three_parts() {
		$result = \Indieweb\Relme\Domain_Icon_Map::split_domain( 'www.example.com' );

		$this->assertEquals( 'example', $result );
	}

	/**
	 * Test split_domain with single part.
	 */
	public function test_split_domain_single_part() {
		$result = \Indieweb\Relme\Domain_Icon_Map::split_domain( 'localhost' );

		$this->assertEquals( 'localhost', $result );
	}

	/**
	 * Test url_to_name with known domain.
	 */
	public function test_url_to_name_known_domain() {
		$result = \Indieweb\Relme\Domain_Icon_Map::url_to_name( 'https://github.com/user' );

		$this->assertEquals( 'github', $result );
	}

	/**
	 * Test url_to_name with Twitter.
	 */
	public function test_url_to_name_twitter() {
		$result = \Indieweb\Relme\Domain_Icon_Map::url_to_name( 'https://twitter.com/username' );

		$this->assertEquals( 'twitter', $result );
	}

	/**
	 * Test url_to_name with Bluesky.
	 */
	public function test_url_to_name_bluesky() {
		$result = \Indieweb\Relme\Domain_Icon_Map::url_to_name( 'https://bsky.app/profile/user.bsky.social' );

		$this->assertEquals( 'bluesky', $result );
	}

	/**
	 * Test url_to_name with mailto scheme.
	 */
	public function test_url_to_name_mailto() {
		$result = \Indieweb\Relme\Domain_Icon_Map::url_to_name( 'mailto:test@example.com' );

		$this->assertEquals( 'mail', $result );
	}

	/**
	 * Test url_to_name with sms scheme.
	 */
	public function test_url_to_name_sms() {
		$result = \Indieweb\Relme\Domain_Icon_Map::url_to_name( 'sms:+1234567890' );

		$this->assertEquals( 'phone', $result );
	}

	/**
	 * Test url_to_name with unknown domain returns website.
	 */
	public function test_url_to_name_unknown_domain() {
		$result = \Indieweb\Relme\Domain_Icon_Map::url_to_name( 'https://unknowndomain12345.com' );

		$this->assertEquals( 'website', $result );
	}

	/**
	 * Test url_to_name with non-http scheme returns notice.
	 */
	public function test_url_to_name_non_http() {
		$result = \Indieweb\Relme\Domain_Icon_Map::url_to_name( 'ftp://example.com' );

		$this->assertEquals( 'notice', $result );
	}

	/**
	 * Test get_title returns name when not found.
	 */
	public function test_get_title_returns_name_when_not_found() {
		$result = \Indieweb\Relme\Domain_Icon_Map::get_title( 'unknownicon12345' );

		$this->assertEquals( 'unknownicon12345', $result );
	}

	/**
	 * Test get_icon_filename returns null for non-existent icon.
	 */
	public function test_get_icon_filename_returns_null_for_nonexistent() {
		$result = \Indieweb\Relme\Domain_Icon_Map::get_icon_filename( 'nonexistenticon12345' );

		$this->assertNull( $result );
	}

	/**
	 * Test get_icon returns name for non-existent icon.
	 */
	public function test_get_icon_returns_name_for_nonexistent() {
		$result = \Indieweb\Relme\Domain_Icon_Map::get_icon( 'nonexistenticon12345' );

		$this->assertEquals( 'nonexistenticon12345', $result );
	}

	/**
	 * Test url_to_name filter is applied.
	 */
	public function test_url_to_name_filter() {
		add_filter(
			'indieweb_links_url_to_name',
			function ( $name, $url ) {
				if ( strpos( $url, 'custom.example.com' ) !== false ) {
					return 'custom';
				}
				return $name;
			},
			10,
			2
		);

		$result = \Indieweb\Relme\Domain_Icon_Map::url_to_name( 'https://custom.example.com' );

		$this->assertEquals( 'custom', $result );
	}
}
