<?php
/**
 * Icons Class.
 *
 * @package Indieweb
 */

namespace Indieweb;

use Indieweb\Relme\Domain_Icon_Map;

/**
 * Registers the bundled icons with the WordPress Icons API.
 *
 * The icons are registered as the "indieweb" icon collection, which makes
 * them available in the editor's Icon Library and via `wp_get_icon()`. On
 * WordPress versions without the Icons API the compatibility shims in
 * `includes/compat.php` provide the same functions.
 */
class Icons {

	/**
	 * The icon collection slug.
	 */
	const COLLECTION = 'indieweb';

	/**
	 * Pattern an unqualified icon name has to match, mirrors WP_Icons_Registry.
	 */
	const NAME_PATTERN = '/^[a-z0-9](?:[a-z0-9_-]*[a-z0-9])?$/';

	/**
	 * Whether the icon collection has been registered.
	 *
	 * @var boolean
	 */
	private static $collection_registered = false;

	/**
	 * Icon names that have already been registered.
	 *
	 * @var array
	 */
	private static $registered_names = array();

	/**
	 * Register the bundled icons with the Icons API.
	 *
	 * Uses the icon directories of the Domain_Icon_Map, so icons added via
	 * the `indieweb_icon_file_dirs` filter are registered as well. Icons that
	 * have already been registered are skipped, so the method can run again
	 * to pick up directories that were added later.
	 */
	public static function register_icons() {
		if ( ! self::$collection_registered ) {
			self::$collection_registered = true;

			\wp_register_icon_collection(
				self::COLLECTION,
				array(
					'label'       => \__( 'IndieWeb', 'indieweb' ),
					'description' => \__( 'Icons for social networks and IndieWeb services.', 'indieweb' ),
				)
			);
		}

		foreach ( self::get_bundled_icons() as $slug => $file ) {
			$icon_name = self::COLLECTION . '/' . $slug;
			if ( isset( self::$registered_names[ $icon_name ] ) ) {
				continue;
			}
			self::$registered_names[ $icon_name ] = true;

			$label = \function_exists( 'simpleicons_iw_get_names' ) ? Domain_Icon_Map::get_title( $slug ) : $slug;

			\wp_register_icon(
				$icon_name,
				array(
					'label'     => $label,
					'file_path' => $file,
				)
			);
		}
	}

	/**
	 * Collect the SVG files from the icon directories.
	 *
	 * The directories are searched in order and the first match wins, so a
	 * directory added to the front of the list can replace a bundled icon.
	 *
	 * @return array Map of icon slug to SVG file path.
	 */
	private static function get_bundled_icons() {
		$icons = array();

		foreach ( Domain_Icon_Map::get_icon_file_dirs() as $dir ) {
			$files = \glob( \trailingslashit( $dir ) . '*.svg' );
			if ( ! $files ) {
				continue;
			}

			foreach ( $files as $file ) {
				$slug = \strtolower( \basename( $file, '.svg' ) );
				if ( isset( $icons[ $slug ] ) || ! \preg_match( self::NAME_PATTERN, $slug ) ) {
					continue;
				}
				$icons[ $slug ] = $file;
			}
		}

		return $icons;
	}
}
