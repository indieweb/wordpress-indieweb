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
	 * Directory used to test additional icon directories.
	 *
	 * @var string
	 */
	private $icon_dir;

	/**
	 * Create a directory with a custom icon.
	 */
	public function set_up() {
		parent::set_up();

		$this->icon_dir = get_temp_dir() . 'indieweb-test-icons/';
		wp_mkdir_p( $this->icon_dir );
		file_put_contents( $this->icon_dir . 'testicon.svg', '<svg><title>Test Icon</title></svg>' ); // phpcs:ignore
	}

	/**
	 * Remove the custom icon directory.
	 */
	public function tear_down() {
		if ( file_exists( $this->icon_dir . 'testicon.svg' ) ) {
			wp_delete_file( $this->icon_dir . 'testicon.svg' );
		}
		if ( is_dir( $this->icon_dir ) ) {
			rmdir( $this->icon_dir ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_rmdir -- Removing a directory created by the test.
		}

		parent::tear_down();
	}

	/**
	 * Add the test directory to the icon directories.
	 */
	private function add_icon_dir() {
		add_filter(
			'indieweb_icon_file_dirs',
			function ( $dirs ) {
				$dirs[] = $this->icon_dir;
				return $dirs;
			}
		);
	}

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

	/**
	 * Test get_icon_file_dirs contains the bundled icon directory.
	 */
	public function test_get_icon_file_dirs_contains_bundled_dir() {
		$dirs = \Indieweb\Relme\Domain_Icon_Map::get_icon_file_dirs();

		$this->assertNotEmpty( $dirs );
		$this->assertStringEndsWith( 'static/svg/', $dirs[0] );
		$this->assertDirectoryExists( $dirs[0] );
	}

	/**
	 * Test get_icon_filename finds an icon in an additional directory.
	 */
	public function test_get_icon_filename_finds_icon_in_added_dir() {
		$this->add_icon_dir();

		$result = \Indieweb\Relme\Domain_Icon_Map::get_icon_filename( 'testicon' );

		$this->assertEquals( $this->icon_dir . 'testicon.svg', $result );
	}

	/**
	 * Test get_icon_filename does not find the icon without the added directory.
	 */
	public function test_get_icon_filename_without_added_dir() {
		$result = \Indieweb\Relme\Domain_Icon_Map::get_icon_filename( 'testicon' );

		$this->assertNull( $result );
	}

	/**
	 * Test get_icon_svg reads an icon from an additional directory.
	 */
	public function test_get_icon_svg_from_added_dir() {
		$this->add_icon_dir();

		$result = \Indieweb\Relme\Domain_Icon_Map::get_icon_svg( 'testicon' );

		$this->assertEquals( '<svg><title>Test Icon</title></svg>', $result );
	}

	/**
	 * Test url_to_name uses an icon from an additional directory.
	 */
	public function test_url_to_name_uses_added_dir() {
		add_filter(
			'indieweb_icon_file_dirs',
			function ( $dirs ) {
				$dirs[] = $this->icon_dir;
				return $dirs;
			}
		);

		$result = \Indieweb\Relme\Domain_Icon_Map::url_to_name( 'https://testicon.example/profile' );

		$this->assertEquals( 'testicon', $result );
	}

	/**
	 * Test get_icon_filename rejects a name that could leave the directory.
	 */
	public function test_get_icon_filename_rejects_traversal() {
		$result = \Indieweb\Relme\Domain_Icon_Map::get_icon_filename( '../../../wp-config' );

		$this->assertNull( $result );
	}

	/**
	 * Test pre_indieweb_icon_svg short circuits the file lookup.
	 */
	public function test_pre_indieweb_icon_svg_filter() {
		add_filter(
			'pre_indieweb_icon_svg',
			function ( $icon, $name ) {
				if ( 'filtericon' === $name ) {
					return '<svg id="filtered"></svg>';
				}
				return $icon;
			},
			10,
			2
		);

		$this->assertEquals( '<svg id="filtered"></svg>', \Indieweb\Relme\Domain_Icon_Map::get_icon_svg( 'filtericon' ) );
		$this->assertStringContainsString( '<svg id="filtered"></svg>', \Indieweb\Relme\Domain_Icon_Map::get_icon( 'filtericon' ) );
	}

	/**
	 * Test get_title filter is applied.
	 */
	public function test_get_title_filter() {
		add_filter(
			'indieweb_icon_title',
			function ( $title, $name ) {
				if ( 'testicon' === $name ) {
					return 'Test Icon';
				}
				return $title;
			},
			10,
			2
		);

		$this->assertEquals( 'Test Icon', \Indieweb\Relme\Domain_Icon_Map::get_title( 'testicon' ) );
	}
}
