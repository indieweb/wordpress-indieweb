<?php

add_action( 'init', array( 'HCard_User', 'init' ) );
add_action( 'widgets_init', array( 'HCard_User', 'init_widgets' ) );


// Extended Profile for Rel-Me and H-Card
class HCard_User {

	public static function init() {
		if ( 1 === (int) get_option( 'iw_author_url' ) ) {
			add_filter( 'author_link', array( 'HCard_User', 'author_link' ), 10, 3 );
		}
		add_filter( 'user_contactmethods', array( 'HCard_User', 'user_contactmethods' ) );

		add_action( 'show_user_profile', array( 'HCard_User', 'extended_user_profile' ) );
		add_action( 'edit_user_profile', array( 'HCard_User', 'extended_user_profile' ) );
		// Save Extra User Data
		add_action( 'personal_options_update', array( 'HCard_User', 'save_profile' ), 11 );
		add_action( 'edit_user_profile_update', array( 'HCard_User', 'save_profile' ), 11 );
	}

	/**
	 * register WordPress widgets
	 */
	public static function init_widgets() {
		register_widget( 'RelMe_Widget' );
	}

	/**
	 * If there is a URL set in the user profile, set author link to that
	 */
	public static function author_link( $link, $author_id, $nicename ) {
		$user_info = get_userdata( $author_id );
		if ( ! empty( $user_info->user_url ) ) {
				$link = $user_info->user_url;
		}
		return $link;
	}

	/**
	 *
	 * list of popular silos and profile url patterns
	 * Focusing on those which are supported by indieauth
	 * https://indieweb.org/indieauth.com
	 */
	public static function silos() {
		$silos = array(
			'tel' => array(
				'baseurl' => 'sms:%s',
				'display' => __( 'Telephone', 'indieweb' ),
			),
			'github' => array(
				'baseurl' => 'https://github.com/%s',
				'display' => __( 'Github username', 'indieweb' ),
			),
			'googleplus' => array(
				'baseurl' => 'https://plus.google.com/%s',
				'display' => __( 'Google+ userID (not username)', 'indieweb' ),
			),
			'twitter' => array(
				'baseurl' => 'https://twitter.com/%s',
				'display' => __( 'Twitter username (without @)', 'indieweb' ),
			),
			'facebook' => array(
				'baseurl' => 'https://www.facebook.com/%s',
				'display' => __( 'Facebook ID', 'indieweb' ),
			),
			'lastfm' => array(
				'baseurl' => 'https://last.fm/user/%s',
				'display' => __( 'Last.fm username', 'indieweb' ),
			),
			'instagram' => array(
				'baseurl' => 'https://www.instagram.com/%s',
				'display' => __( 'Instagram username', 'indieweb' ),
			),
			'flickr' => array(
				'baseurl' => 'https://www.flickr.com/people/%s',
				'display' => __( 'Flickr username', 'indieweb' ),
			),
		);
		return apply_filters( 'wp_relme_silos', $silos );
	}


	/**
	 * additional user fields
	 *
	 * @param array $profile_fields Current profile fields
	 *
	 * @return array $profile_fields extended
	 */
	public static function user_contactmethods( $profile_fields ) {
		foreach ( self::silos() as $silo => $details ) {
			if ( ! array_key_exists( $silo, $profile_fields ) ) {
				$profile_fields[ $silo ] = $details['display'];
			}
		}
		return $profile_fields;
	}

	public static function address_fields() {
		$address = array(
			'street_address' => array(
				'title' => __( 'Street Address', 'indieweb' ),
				'description' => __( 'Street Number and Name', 'indieweb' ),
			),
			'extended_address' => array(
				'title' => __( 'Extended Address', 'indieweb' ),
				'description' => __( 'Apartment/Suite/Room Name/Number if any', 'indieweb' ),
			),
			'locality' => array(
				'title' => __( 'Locality', 'indieweb' ),
				'description' => __( 'City/State/Village', 'indieweb' ),
			),
			'region' => array(
				'title' => __( 'Region', 'indieweb' ),
				'description' => __( 'State/County/Province', 'indieweb' ),
			),
			'postal_code' => array(
				'title' => __( 'Postal Code', 'indieweb' ),
				'description' => __( 'Postal Code, such as Zip Code', 'indieweb' ),
			),
			'country_name' => array(
				'title' => __( 'Country Name', 'indieweb' ),
				'description' => __( 'Country Name', 'indieweb' ),
			),
		);
		return apply_filters( 'wp_user_address', $address );
	}

	public static function extra_fields() {
		$extras = array(
			'job_title' => array(
				'title' => __( 'Job Title', 'indieweb' ),
				'description' => __( 'Title or Role', 'indieweb' ),
			),
			'organization' => array(
				'title' => __( 'Organization', 'indieweb' ),
				'description' => __( 'Affiliated Organization', 'indieweb' ),
			),
			'honorific_prefix' => array(
				'title' => __( 'Honorific Prefix', 'indieweb' ),
				'description' => __( 'e.g. Mrs., Mr. Dr.', 'indieweb' ),
			),
		);
		return apply_filters( 'wp_user_extrafields', $extras );
	}

	public static function extended_user_profile( $user ) {
		echo '<h3>' . esc_html__( 'Address', 'indieweb' ) . '</h3>';
		echo '<p>' . esc_html__( 'Fill in all fields you are wish displayed.', 'indieweb' ) . '</p>';
		echo '<table class="form-table">';
		foreach ( self::address_fields() as $key => $value ) {
			self::extended_profile_text_field( $user, $key, $value['title'], $value['description'] );
		}
		echo '</table>';

		echo '<h3>' . esc_html__( 'Additional Profile Information', 'indieweb' ) . '</h3>';
		echo '<p>' . esc_html__( 'Fill in all fields you are wish displayed.', 'indieweb' ) . '</p>';
		echo '<table class="form-table">';
		foreach ( self::extra_fields() as $key => $value ) {
			self::extended_profile_text_field( $user, $key, $value['title'], $value['description'] );
		}
		self::extended_profile_textarea_field( $user, 'relme', __( 'Other Sites', 'indieweb' ), __( 'Sites not listed in the profile to add to rel-me (One URL per line)', 'indieweb' ) );
		echo '</table>';
	}

	public static function extended_profile_text_field( $user, $key, $title, $description ) {
	?>
	<tr>
		<th><label for="<?php echo esc_html( $key ); ?>"><?php echo esc_html( $title ); ?></label></th>

		<td>
			<input type="text" name="<?php echo esc_html( $key ); ?>" id="<?php echo esc_html( $key ); ?>" value="<?php echo esc_attr( get_the_author_meta( $key, $user->ID ) ); ?>" class="regular-text" /><br />
			<span class="description"><?php echo esc_html( $description );?></span>
		</td>
	</tr>
	<?php
	}

	public static function extended_profile_textarea_field( $user, $key, $title, $description ) {
		$value = get_the_author_meta( $key, $user->ID );
		if ( is_array( $value ) ) {
			$value = implode( "\n", $value );
		}
	?>
	<tr>
		<th><label for="<?php echo esc_html( $key ); ?>"><?php echo esc_html( $title ); ?></label></th>

		<td>
			<textarea name="<?php echo esc_html( $key ); ?>" id="<?php echo esc_html( $key ); ?>"><?php echo esc_attr( $value ); ?></textarea><br />
			<span class="description"><?php echo esc_html( $description ); ?></span>
		</td>
	</tr>
	<?php
	}


	public static function save_profile( $user_id ) {
		if ( ! current_user_can( 'edit_user', $user_id ) ) {
			return false;
		}
		$fields = array_merge( self::extra_fields(), self::address_fields() );
		$p = array_filter( $_POST );
		foreach ( $fields as $key => $value ) {
			if ( isset( $p[ $key ] ) ) {
				update_user_meta( $user_id, $key, sanitize_text_field( $p[ $key ] ) );
			} else {
				delete_user_meta( $user_id, $key );
			}
		}
		if ( isset( $_POST['relme'] ) ) {
			$relme = explode( "\n", $_POST['relme'] );
			if ( ! empty( $relme ) ) {
				update_user_meta( $user_id, 'relme', self::clean_urls( $relme ) );
			} else {
				delete_user_meta( $user_id, 'relme' );
			}
		}
	}

	/**
	 * Filters a single silo URL.
	 *
	 * @param string $string A string that is expected to be a silo URL.
	 * @return string|bool The filtered and escaped URL string, or FALSE if invalid.
	 * @used-by clean_urls
	 */
	public static function clean_url( $string ) {
		$url = trim( $string );
		if ( ! filter_var( $url, FILTER_VALIDATE_URL ) ) {
			return false;
		}
		// Rewrite these to https as needed
		$secure = apply_filters( 'iwc_rewrite_secure', array( 'facebook.com', 'twitter.com' ) );
		if ( in_array( self::extract_domain_name( $url ), $secure, true ) ) {
			$url = preg_replace( '/^http:/i', 'https:', $url );
		}
		$url = esc_url_raw( $url );
		return $url;
	}

	/**
	 * Filters incoming URLs.
	 *
	 * @param array $urls An array of URLs to filter.
	 *
	 * @return array A filtered array of unique URLs.
	 *
	 * @uses clean_url
	 */
	public static function clean_urls( $urls ) {
		$array = array_map( array( 'HCard_User', 'clean_url' ), $urls );
		return array_filter( array_unique( $array ) );
	}

	/**
	 * Returns the Domain Name out of a URL.
	 *
	 * @param string $url URL.
	 *
	 * @return string domain name
	 */
	public static function extract_domain_name( $url ) {
		$host = parse_url( $url, PHP_URL_HOST );
		$host = preg_replace( '/^www\./', '', $host );
		return $host;
	}

	/**
	 * Return a marked up SVG icon..
	 *
	 * @param string $domain domain.
	 *
	 * @return string svg icon
	 */
	public static function get_icon( $domain ) {
		// Supported icons.
		$icons = array(
			'default'			=> 'share',
			'amazon.com'		=> 'amazon',
			'behance.net'		=> 'behance',
			'blogspot.com'		=> 'blogger',
			'codepen.io'		=> 'codepen',
			'dribbble.com'		=> 'dribbble',
			'dropbox.com'		=> 'dropbox',
			'eventbrite.com'	=> 'eventbrite',
			'facebook.com'		=> 'facebook',
			'flickr.com'		=> 'flickr',
			// feed
			'foursquare.com'	=> 'foursquare',
			'ghost.org'			=> 'ghost',
			'plus.google.com'	=> 'google-plus',
			'github.com'		=> 'github',
			'instagram.com'		=> 'instagram',
			'linkedin.com'		=> 'linkedin',
			'mailto:'			=> 'mail',
			'medium.com'		=> 'medium',
			'path.com'			=> 'path',
			'pinterest.com'		=> 'pinterest',
			'getpocket.com'		=> 'pocket',
			'polldaddy.com'		=> 'polldaddy',
			'reddit.com'		=> 'reddit',
			'squarespace.com'	=> 'squarespace',
			'skype.com'			=> 'skype',
			'skype:'			=> 'skype',
			// share
			'soundcloud.com'	=> 'cloud',
			'spotify.com'		=> 'spotify',
			'stumbleupon.com'	=> 'stumbleupon',
			'telegram.org'		=> 'telegram',
			'tumblr.com'		=> 'tumblr',
			'twitch.tv'			=> 'twitch',
			'twitter.com'		=> 'twitter',
			'vimeo.com'			=> 'vimeo',
			'whatsapp.com'		=> 'whatsapp',
			'wordpress.org'		=> 'wordpress',
			'wordpress.com'		=> 'wordpress',
			'youtube.com'		=> 'youtube',
		);

		// Substitute another domain to sprite map
		$icons = apply_filters( 'indieweb_domain_icons', $icons );
		$icon = $icons['default'];

		if ( array_key_exists( $domain, $icons ) ) {
			$icon = $icons[ $domain ];
		}

		// Substitute another svg sprite file
		$sprite = apply_filters( 'indieweb_icon_sprite', plugins_url( 'static/img/social-logos.svg', dirname( __FILE__ ) ), $domain );

		return '<svg class="svg-icon svg-' . $icon . '" aria-hidden="true"><use xlink:href="' . $sprite . '#' . $icon . '"></use><svg>';
	}

	/**
	 * returns an array of links from the user profile to be used as rel-me
	 */
	public static function get_rel_me( $author_id = null ) {
		if ( empty( $author_id ) ) {
			$author_id = get_the_author_meta( 'ID' );
		}

		if ( empty( $author_id ) || 0 === $author_id ) {
			return false;
		}

		$list = array();

		foreach ( self::silos() as $silo => $details ) {
			$socialmeta = get_the_author_meta( $silo, $author_id );

			if ( ! empty( $socialmeta ) ) {
				// If it is not a URL
				if ( ! filter_var( $socialmeta, FILTER_VALIDATE_URL ) ) {
					// If the username has the @ symbol strip it
					if ( ( 'twitter' === $silo ) && ( preg_match( '/^@?(\w+)$/i', $socialmeta, $matches ) ) ) {
						$socialmeta = trim( $socialmeta, '@' );
					}
					$list[ $silo ] = sprintf( $details['baseurl'], $socialmeta );
				} // Pass the URL itself
				else {
					$list[ $silo ] = self::clean_url( $socialmeta );
				}
			}
		}

		$relme = get_the_author_meta( 'relme', $author_id );

		if ( $relme ) {
			if ( ! is_array( $relme ) ) {
				$relme = explode( "\n", $relme );
			}
			$relme = self::clean_urls( $relme );
			foreach ( $relme as $url ) {
				$list[ self::extract_domain_name( $url ) ] = $url;
			}
		}
		return array_unique( $list );
	}

	/**
	 * prints a formatted <ul> list of rel=me to supported silos
	 */
	public static function rel_me_list( $author_id = null, $include_rel = false ) {
		$list = self::get_rel_me( $author_id );
		if ( ! $list ) {
			return false;
		}
		$author_name = get_the_author_meta( 'display_name' , $author_id );
		$r = array();
		foreach ( $list as $silo => $profile_url ) {
			$r[ $silo ] = '<a ' . ( $include_rel ? 'rel="me" ' : '' ) . 'class="icon-' . $silo . ' url
				u-url" href="' . esc_url( $profile_url ) . '" title="' . esc_attr( $author_name ) . ' @ ' .
				esc_attr( $silo ) . '"><span class="relmename">' . esc_attr( $silo ) . '</span>' . self::get_icon( self::extract_domain_name( $profile_url ) ) . '</a>';
		}

		$r = "<div class='relme'><ul>\n<li>" . join( "</li>\n<li>", $r ) . "</li>\n</ul></div>";

		echo apply_filters( 'indieweb_rel_me', $r, $author_id, $list );
	}

	/**
	 * prints a formatted list of rel=me for the head to supported silos
	 */
	public static function relme_head_list( $author_id = null ) {
		$list = self::get_rel_me( $author_id );
		if ( ! $list ) {
			return false;
		}
		$author_name = get_the_author_meta( 'display_name' , $author_id );
		$r = array();
		foreach ( $list as $silo => $profile_url ) {
			$r[ $silo ] = '<link rel="me" href="' . esc_url( $profile_url ) . '" />' . PHP_EOL;
		}
		return join( '', $r );
	}

	/**
	 *
	 */
	public static function relme_head() {
		global $authordata;
		$single_author = get_option( 'iw_single_author' );
		$author_id = get_option( 'iw_default_author' ); // Set the author ID to default
		if ( is_front_page() && 1 === (int) $single_author ) {
			echo self::relme_head_list( $author_id );
			return;
		}
		if ( is_author() ) {
			global $authordata;
			$author_id = $authordata->ID;
			echo self::relme_head_list( $author_id );
		}
	}


	public static function get_hcard_display_defaults() {
		// $display = self::get_hcard_display_option();
		$defaults = array(
			'style' => 'div',
			'container-css' => '',
			'single-css' => '',
			'avatar_size' => 96,
		);
		return apply_filters( 'hcard_display_defaults', $defaults );
	}


	public static function hcard( $user, $args = array() ) {
		if ( ! $user ) {
			return false;
		}
		$user = new WP_User( $user );
		if ( ! $user ) {
			return false;
		}
		$r = wp_parse_args( $args, self::get_hcard_display_defaults() );
		$avatar = get_avatar(
			$user,
			$r['avatar_size'],
			'default',
			'',
			array(
				'class' => array( 'u-photo', 'hcard-photo' ),
			)
		);
		$url = $user->has_prop( 'user_url' ) ? $user->get( 'user_url' ) : $url = get_author_posts_url( $user->ID );
		$name = $user->get( 'display_name' );

		$return = '<div class="hcard-display h-card vcard p-author">';
		$return .= '<div class="hcard-header">';
		$return .= '<a class="u-url url fn" href="' . $url . '" rel="author">';
		if ( ! $avatar ) {
			$return .= '<p class="hcard-name p-name n">' . $name . '</h2></a>';
		} else {
			$return .= $avatar . '</a>';
			$return .= '<p class="hcard-name p-name n">' . $name . '</h2>';
		}
		$return .= '</div>';
		$return .= '<div class="hcard-body">';
		$return .= '<ul class="hcard-properties">';
		$return .= '<li class="h-adr adr">';
		if ( $user->has_prop( 'locality' ) ) {
			$return .= '<span class="p-locality locality">' . $user->get( 'locality' ) . '</span>, ';
		}
		if ( $user->has_prop( 'region' ) ) {
			$return .= '<span class="p-region region">' . $user->get( 'region' ) . '</span> ';
		}
		if ( $user->has_prop( 'country-name' ) ) {
			$return .= '<span class="p-country-name country-name">' . $user->get( 'country-name' ) . '</span>';
		}
		$return .= '</li>';
		if ( $user->has_prop( 'tel' ) && $user->get( 'tel' ) ) {
			$return .= '<li><a class="p-tel tel" href="tel:' . $user->get( 'tel' ) . '">' . $user->get( 'tel' ) . '</a></li>';
		}
		$return .= '</ul>';
		$return .= '<p class="p-note note">' . $user->get( 'description' ) . '</p>';
		$return .= '</div>';
		return $return;
	}



} // End Class
