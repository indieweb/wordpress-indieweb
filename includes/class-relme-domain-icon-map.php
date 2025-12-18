<?php
/**
 * Maps domain names to icons from the provided SVG fontset.
 *
 * @package IndieWeb
 */

/**
 * Rel-Me Domain Icon Map class.
 */
class Rel_Me_Domain_Icon_Map {

	/**
	 * Common and custom domain to icon mappings.
	 *
	 * @var array
	 */
	private static $map = array(
		'twitter.com'         => 'twitter',
		'blogspot.com'        => 'blogger',
		'facebook.com'        => 'facebook',
		'swarmapp.com'        => 'swarm',
		'instagram.com'       => 'instagram',
		'play.google.com'     => 'googleplay',
		'plus.google.com'     => 'googleplus',
		'podcasts.google.com' => 'googlepodcasts',
		'podcasts.apple.com'  => 'applepodcasts',
		'indieweb.xyz'        => 'info',
		'getpocket.com'       => 'pocket',
		'flip.it'             => 'flipboard',
		'micro.blog'          => 'microdotblog',
		'wordpress.org'       => 'wordpress',
		'wordpress.com'       => 'wordpress',
		'itunes.apple.com'    => 'applemusic',
		'reading.am'          => 'book',
		'astral.ninja'        => 'nostr',
		'nos.social'          => 'nostr',
		'iris.to'             => 'nostr',
		'snort.social'        => 'nostr',
		'app.coracle.social'  => 'nostr',
		'primal.net'          => 'nostr',
		'habla.news'          => 'nostr',
		'nostr.band'          => 'nostr',
		'bsky.app'            => 'bluesky',
		'bsky.social'         => 'bluesky',

	);

	/**
	 * Try to get the correct icon for the majority of sites.
	 *
	 * @param string $string The domain string to split.
	 * @return string The extracted domain part.
	 */
	public static function split_domain( $string ) {
		$explode = explode( '.', $string );
		if ( 2 === count( $explode ) ) {
			return $explode[0];
		}
		if ( 3 === count( $explode ) ) {
			return $explode[1];
		}
		return $string;
	}

	/**
	 * Return the filename of an icon based on name if the file exists.
	 *
	 * @param string $name The icon name.
	 * @return string|null The icon file path or null if not found.
	 */
	public static function get_icon_filename( $name ) {
		$svg = sprintf( '%1$s/static/svg/%2$s.svg', plugin_dir_path( __DIR__ ), $name );
		if ( file_exists( $svg ) ) {
			return $svg;
		}
		return null;
	}

	/**
	 * Return the retrieved SVG based on name.
	 *
	 * @param string $name The icon name.
	 * @return string|null The SVG content or null if not found.
	 */
	public static function get_icon_svg( $name ) {
		$file = self::get_icon_filename( $name );
		if ( $file ) {
			$icon = file_get_contents( $file ); // phpcs:ignore
			if ( $icon ) {
				return $icon;
			}
		}
		return null;
	}

	/**
	 * Get the icon HTML markup.
	 *
	 * @param string $name The icon name.
	 * @return string The icon HTML or the name if not found.
	 */
	public static function get_icon( $name ) {
		$icon  = self::get_icon_svg( $name );
		$title = self::get_title( $name );
		if ( $icon ) {
			return sprintf( '<span class="relme-icon svg-%1$s" aria-hidden="true" aria-label="%2$s" title="%2$s" >%3$s</span>', esc_attr( $name ), esc_attr( $title ), $icon );
		}
		return $name;
	}

	/**
	 * Get the title for an icon.
	 *
	 * @param string $name The icon name.
	 * @return string The icon title.
	 */
	public static function get_title( $name ) {
		$strings = simpleicons_iw_get_names();
		if ( isset( $strings[ $name ] ) ) {
			return $strings[ $name ];
		}
		return $name;
	}

	/**
	 * Get the Mastodon URL from user meta.
	 *
	 * @return string|false The Mastodon domain or false.
	 */
	public static function mastodon_url() {
		$mastodon = get_transient( 'indieweb_mastodon' );
		if ( false !== $mastodon ) {
			return $mastodon;
		}
		$args    = array(
			'number'      => 1,
			'count_total' => false,
			'meta_query'  => array(
				array(
					'key'     => 'mastodon',
					'compare' => 'EXISTS',
				),
			),
		);
		$query   = new WP_User_Query( $args );
		$results = $query->get_results();
		if ( empty( $results ) ) {
			$value = false;
		} else {
			$user  = $results[0];
			$value = get_user_meta( $user->ID, 'mastodon', true );
			if ( ! empty( $value ) && is_string( $value ) ) {
				$value = wp_parse_url( $value, PHP_URL_HOST );
			}
		}
		set_transient( 'indieweb_mastodon', $value );
	}

	/**
	 * Convert a URL to an icon name.
	 *
	 * @param string $url The URL to convert.
	 * @return string The icon name.
	 */
	public static function url_to_name( $url ) {
		$scheme = wp_parse_url( $url, PHP_URL_SCHEME );
		// The default if not an http link is to return notice.
		$return = 'notice';
		if ( ( 'http' === $scheme ) || ( 'https' === $scheme ) ) {
			$return = 'website'; // Default for web links.
			$url    = strtolower( $url );
			$domain = wp_parse_url( $url, PHP_URL_HOST );

			$domain = str_replace( 'www.', '', $domain ); // Always remove www.

			// If the domain is already on the pre-loaded list then use that.
			if ( array_key_exists( $domain, self::$map ) ) {
				$return = self::$map[ $domain ];
			} elseif ( self::mastodon_url() === $domain ) {
				$return = 'mastodon';
			} else {
				// Remove extra info and try to map it to an icon.
				$strip = self::split_domain( $domain );
				if ( self::get_icon_filename( $strip ) ) {
					$return = $strip;
				} elseif ( self::get_icon_filename( str_replace( '.', '-dot-', $domain ) ) ) {
					$return = str_replace( '.', '-dot-', $domain );
				} elseif ( self::get_icon_filename( str_replace( '.', '', $domain ) ) ) {
					$return = str_replace( '.', '', $domain );
				} elseif ( false !== stripos( $domain, 'wordpress' ) ) { // phpcs:ignore WordPress.WP.CapitalPDangit
					// Anything with WordPress in the name that is not matched return WordPress icon.
					$return = 'wordpress'; // phpcs:ignore WordPress.WP.CapitalPDangit
				} elseif ( false !== stripos( $domain, 'read' ) ) {
					// Anything with read in the name that is not matched return a book.
					$return = 'book';
				} elseif ( false !== stripos( $domain, 'news' ) ) {
					// Anything with news in the name that is not matched return the summary icon.
					$return = 'summary';
				} else {
					// Some domains have the word app in them check for matches with that.
					$strip = str_replace( 'app', '', $strip );
					if ( self::get_icon_filename( $strip ) ) {
						$return = $strip;
					}
				}
			}
		}
		if ( 'sms' === $scheme ) {
			return 'phone';
		}
		if ( 'mailto' === $scheme ) {
			return 'mail';
		}
		if ( 'gtalk' === $scheme ) {
			return 'googlehangouts';
		}
		// Save the determined mapping into the map so that it will not have to look again on the same page load.
		self::$map[ $domain ] = $return;
		$return               = apply_filters( 'indieweb_links_url_to_name', $return, $url );
		return $return;
	}
}
