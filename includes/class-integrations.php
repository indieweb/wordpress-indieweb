<?php

add_action( 'init', array( 'IndieWeb_Integrations', 'init' ) );

/**
 * Third party integrations
 *
 */
class IndieWeb_Integrations {

	public static function init() {
		add_filter( 'pubsubhubbub_feed_urls', array( 'IndieWeb_Integrations', 'add_pubsubhubbub_feeds' ) );
	}

	/**
	 * adds the Microformats (2) feed to PubsubHubBub
	 *
	 * @param array $feeds
	 * @return array
	 */
	public static function add_pubsubhubbub_feeds( $feeds ) {
		$feeds[] = get_post_type_archive_link( 'post' );

		return array_unique( $feeds );
	}
}
