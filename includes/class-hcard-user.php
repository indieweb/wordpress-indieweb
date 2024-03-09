<?php

add_action( 'init', array( 'HCard_User', 'init' ) );
add_action( 'widgets_init', array( 'HCard_User', 'init_widgets' ) );

// Extended Profile for Rel-Me and H-Card
class HCard_User {

	public static function init() {
		include_once 'simple-icons.php';
		if ( 1 === (int) get_option( 'iw_author_url' ) ) {
			add_filter( 'author_link', array( 'HCard_User', 'author_link' ), 10, 3 );
		}
		add_filter( 'user_contactmethods', array( 'HCard_User', 'user_contactmethods' ) );

		add_action( 'show_user_profile', array( 'HCard_User', 'extended_user_profile' ) );
		add_action( 'edit_user_profile', array( 'HCard_User', 'extended_user_profile' ) );
		// Save Extra User Data
		add_action( 'personal_options_update', array( 'HCard_User', 'save_profile' ), 11 );
		add_action( 'edit_user_profile_update', array( 'HCard_User', 'save_profile' ), 11 );
		add_filter( 'wp_head', array( 'HCard_User', 'pgp' ), 11 );
		add_action( 'rest_api_init', array( 'HCard_User', 'rest_fields' ) );
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
		if ( in_the_loop() && ( is_home() || is_archive() || is_singular() ) ) {
			$user_info = get_userdata( $author_id );
			if ( ! empty( $user_info->user_url ) ) {
				$link = $user_info->user_url;
			}
		}
		return $link;
	}

	/**
	 * list of popular silos and profile url patterns
	 * Focusing on those which are supported by indieauth
	 * https://indieweb.org/indieauth.com
	 */
	public static function silos() {
		$silos = array(
			'github'    => array(
				'baseurl' => 'https://github.com/%s',
				'display' => __( 'Github username', 'indieweb' ),
			),
			'twitter'   => array(
				'baseurl' => 'https://twitter.com/%s',
				'display' => __( 'X/Twitter username (without @)', 'indieweb' ),
			),
			'facebook'  => array(
				'baseurl' => 'https://www.facebook.com/%s',
				'display' => __( 'Facebook ID', 'indieweb' ),
			),
			'microblog' => array(
				'baseurl' => 'https://micro.blog/%s',
				'display' => __( 'Micro.blog username', 'indieweb' ),
			),
			'instagram' => array(
				'baseurl' => 'https://www.instagram.com/%s',
				'display' => __( 'Instagram username', 'indieweb' ),
			),
			'flickr'    => array(
				'baseurl' => 'https://www.flickr.com/people/%s',
				'display' => __( 'Flickr username', 'indieweb' ),
			),
			'bluesky'   => array(
				'baseurl' => 'https://bsky.app/profile/%s',
				'display' => __( 'Bluesky Username', 'indieweb' ),
			),

			'reddit'    => array(
				'baseurl' => 'https://reddit.com/user/%s',
				'display' => __( 'Reddit Username', 'indieweb' ),
			),
			'mastodon'  => array(
				'baseurl' => '%s',
				'display' => __( 'Mastodon Server (URL)', 'indieweb' ),
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

		// Telephone Number and PGP Key are not silos
		$profile_fields['tel'] = __( 'Telephone', 'indieweb' );
		$profile_fields['pgp'] = __( 'PGP Key (URL)', 'indieweb' );
		return $profile_fields;
	}

	public static function address_fields() {
		$address = array(
			'street_address'   => array(
				'title'       => __( 'Street Address', 'indieweb' ),
				'description' => __( 'Street Number and Name', 'indieweb' ),
			),
			'extended_address' => array(
				'title'       => __( 'Extended Address', 'indieweb' ),
				'description' => __( 'Apartment/Suite/Room Name/Number if any', 'indieweb' ),
			),
			'locality'         => array(
				'title'       => __( 'Locality', 'indieweb' ),
				'description' => __( 'City/State/Village', 'indieweb' ),
			),
			'region'           => array(
				'title'       => __( 'Region', 'indieweb' ),
				'description' => __( 'State/County/Province', 'indieweb' ),
			),
			'postal_code'      => array(
				'title'       => __( 'Postal Code', 'indieweb' ),
				'description' => __( 'Postal Code, such as Zip Code', 'indieweb' ),
			),
			'country_name'     => array(
				'title'       => __( 'Country Name', 'indieweb' ),
				'description' => __( 'Country Name', 'indieweb' ),
			),
		);
		return apply_filters( 'wp_user_address', $address );
	}

	public static function extra_fields() {
		$extras = array(
			'job_title'        => array(
				'title'       => __( 'Job Title', 'indieweb' ),
				'description' => __( 'Title or Role', 'indieweb' ),
			),
			'organization'     => array(
				'title'       => __( 'Organization', 'indieweb' ),
				'description' => __( 'Affiliated Organization', 'indieweb' ),
			),
			'honorific_prefix' => array(
				'title'       => __( 'Honorific Prefix', 'indieweb' ),
				'description' => __( 'e.g. Mrs., Mr. Dr.', 'indieweb' ),
			),
		);
		return apply_filters( 'wp_user_extrafields', $extras );
	}

	public static function extended_user_profile( $user ) {
		echo '<h3>' . esc_html__( 'Address', 'indieweb' ) . '</h3>';
		echo '<p>' . esc_html__( 'Fill in all fields you wish displayed.', 'indieweb' ) . '</p>';
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
		self::extended_profile_textarea_field( $user, 'relme', __( 'Other Sites', 'indieweb' ), __( 'Other profiles without their own field in your user profile (One URL per line)', 'indieweb' ) );
		echo '</table>';
	}

	public static function extended_profile_text_field( $user, $key, $title, $description ) {
		?>
	<tr>
		<th><label for="<?php echo esc_html( $key ); ?>"><?php echo esc_html( $title ); ?></label></th>
		<td>
			<input type="text" name="<?php echo esc_html( $key ); ?>" id="<?php echo esc_html( $key ); ?>" value="<?php echo esc_attr( get_the_author_meta( $key, $user->ID ) ); ?>" class="regular-text" /><br />
			<span class="description"><?php echo esc_html( $description ); ?></span>
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

	public static function rest_fields() {
		register_rest_field(
			'user',
			'me',
			array(
				'get_callback' => function ( $user, $attr, $request, $object_type ) {
					return array_values( self::get_rel_me( $user['id'] ) );
				},
			)
		);
		register_rest_field(
			'user',
			'first_name',
			array(
				'get_callback' => function ( $user, $attr, $request, $object_type ) {
					return get_user_meta( $user['id'], 'first_name' );
				},
			)
		);
	}

	public static function save_profile( $user_id ) {
		if ( ! current_user_can( 'edit_user', $user_id ) ) {
			return false;
		}
		$fields = array_merge( self::extra_fields(), self::address_fields() );
		$p      = array_filter( $_POST ); // phpcs:ignore
		foreach ( $fields as $key => $value ) {
			if ( isset( $p[ $key ] ) ) {
				update_user_meta( $user_id, $key, sanitize_text_field( $p[ $key ] ) );
			} else {
				delete_user_meta( $user_id, $key );
			}
		}
		if ( isset( $_POST['relme'] ) ) { // phpcs:ignore
			$relme = explode( "\n", $_POST['relme'] ); // phpcs:ignore
			if ( ! empty( $relme ) ) {
				update_user_meta( $user_id, 'relme', self::clean_urls( $relme ) );
			} else {
				delete_user_meta( $user_id, 'relme' );
			}
		}
		delete_transient( 'indieweb_mastodon' );
	}

	/**
	 * Filters a single silo URL.
	 *
	 * @param   string $string A string that is expected to be a silo URL.
	 * @return  string|bool The filtered and escaped URL string, or FALSE if invalid.
	 * @used-by clean_urls
	 */
	public static function clean_url( $string ) {
		$url = trim( $string );
		if ( ! filter_var( $url, FILTER_VALIDATE_URL ) ) {
			return false;
		}
		$host = wp_parse_url( $url, PHP_URL_HOST );
		if ( ! $host ) {
			return false;
		}
		// Rewrite these to https as needed
		$secure = apply_filters( 'iwc_rewrite_secure', array( 'facebook.com', 'twitter.com', 'github.com' ) );
		if ( in_array( preg_replace( '/^www\./', '', $host ), $secure, true ) ) {
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
					// Pass the URL itself
				} else {
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
				$list[ preg_replace( '/^www\./', '', wp_parse_url( $url, PHP_URL_HOST ) ) ] = $url;
			}
		}
		return array_unique( $list );
	}

	/**
	 * returns a formatted <ul> list of rel=me to supported silos
	 */
	public static function rel_me_list( $author_id = null, $include_rel = false ) {
		echo self::get_rel_me_list( $author_id, $include_rel ); // phpcs:ignore
	}

	/**
	 * returns a formatted <ul> list of rel=me to supported silos
	 */
	public static function get_rel_me_list( $author_id = null, $include_rel = false ) {
		$list = self::get_rel_me( $author_id );
		if ( ! $list ) {
			return false;
		}
		$author_name = get_the_author_meta( 'display_name', $author_id );
		$r           = array();
		foreach ( $list as $silo => $profile_url ) {
			$name       = Rel_Me_Domain_Icon_Map::url_to_name( $profile_url );
			$title      = Rel_Me_Domain_Icon_Map::get_title( $name );
			$r[ $silo ] = '<a ' . ( $include_rel ? 'rel="me" ' : '' ) . 'class="icon-' .
				$silo . ' url u-url" href="' . esc_url( $profile_url ) . '" title="' . esc_attr( $author_name ) . ' @ ' .
				esc_attr( $title ) . '"><span class="relmename">' . esc_attr( $silo ) . '</span>' . Rel_Me_Domain_Icon_Map::get_icon( $name ) . '</a>';
		}

		$r = "<div class='relme'><ul>\n<li>" . join( "</li>\n<li>", $r ) . "</li>\n</ul></div>";

		return apply_filters( 'indieweb_rel_me', $r, $author_id, $list ); // phpcs:ignore
	}

	/**
	 * prints a formatted list of rel=me for the head to supported silos
	 */
	public static function relme_head_list( $author_id = null ) {
		$list = self::get_rel_me( $author_id );
		if ( ! $list ) {
			return false;
		}
		$author_name = get_the_author_meta( 'display_name', $author_id );
		$r           = array();
		foreach ( $list as $silo => $profile_url ) {
			$r[ $silo ] = '<link rel="me" href="' . esc_url( $profile_url ) . '" />' . PHP_EOL;
		}
		return join( '', $r );
	}

	public static function get_author() {
		$single_author = get_option( 'iw_single_author' );
		if ( is_front_page() && 1 === (int) $single_author ) {
			return get_option( 'iw_default_author' ); // Set the author ID to default
		} elseif ( is_author() ) {
			$author = get_user_by( 'slug', get_query_var( 'author_name' ) );
			if ( $author instanceof WP_User ) {
				return $author->ID;
			} else {
				return $author;
			}
		} else {
			return null;
		}
	}

	public static function pgp() {
		$author_id = self::get_author();
		if ( ! $author_id ) {
			return;
		}
		$pgp = get_user_option( 'pgp', $author_id );
		if ( ! empty( $pgp ) ) {
			printf( '<link rel="pgpkey" href="%1$s" />',  $pgp ); // phpcs:ignore
		}
	}

	/**
	 *
	 */
	public static function relme_head() {
		$author_id = self::get_author();
		if ( ! $author_id ) {
			return;
		}
		echo self::relme_head_list( $author_id ); // phpcs:ignore
	}

	public static function get_hcard_display_defaults() {
		$defaults = array(
			'style'         => 'div',
			'container-css' => '',
			'single-css'    => '',
			'avatar_size'   => 96,
			'avatar'        => true, // Display Avatar
			'location'      => true, // Display location elements
			'notes'         => true, // Display Bio/Notes
			'email'         => false,  // Display email
			'me'            => true, // Display rel-me links inside h-card
		);
		return apply_filters( 'hcard_display_defaults', $defaults );
	}

	/**
	 * Looks up, and returns if exists, the full path to a given file in the
	 * /templates subdirectory of the active theme (child, parent).
	 * Defaults to the /templates subdirectory in this plugin.
	 *
	 * @param string $file_name   File name, example: h-card.php
	 * @return string             Full path to file
	 */
	public static function get_template_file( $file_name ) {
		$theme_template_file = locate_template( 'templates/' . $file_name );
		return $theme_template_file ? $theme_template_file : __DIR__ . '/../templates/' . $file_name;
	}

	public static function hcard( $user, $args = array() ) {
		if ( ! $user ) {
			return false;
		}
		$user = new WP_User( $user );
		if ( ! $user ) {
			return false;
		}

		$args = wp_parse_args( $args, self::get_hcard_display_defaults() );
		if ( $args['avatar'] ) {
			$avatar = get_avatar(
				$user,
				$args['avatar_size'],
				'default',
				'',
				array(
					'class' => array( 'u-photo', 'hcard-photo' ),
				)
			);
		} else {
			$avatar = '';
		}
		$url   = $user->has_prop( 'user_url' ) ? $user->get( 'user_url' ) : $url = get_author_posts_url( $user->ID );
		$name  = $user->get( 'display_name' );
		$email = $user->get( 'user_email' );
		ob_start();
		include self::get_template_file( 'h-card.php' );
		$return = ob_get_contents();
		ob_end_clean();
		return $return;
	}
}
