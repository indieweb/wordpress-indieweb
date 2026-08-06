<?php
/**
 * Third party integrations for Indieweb.
 *
 * @package Indieweb
 */

namespace Indieweb;

/**
 * Third party integrations.
 */
class Integrations {

	/**
	 * Initialize integrations.
	 */
	public static function init() {
		\add_filter( 'pubsubhubbub_feed_urls', array( self::class, 'add_pubsubhubbub_feeds' ) );
	}

	/**
	 * Adds the Microformats (2) feed to PubsubHubBub.
	 *
	 * @param array $feeds Array of feed URLs.
	 * @return array Modified array of feed URLs.
	 */
	public static function add_pubsubhubbub_feeds( $feeds ) {
		$feeds[] = \get_post_type_archive_link( 'post' );

		return array_unique( $feeds );
	}
}
