<?php
/**
 * H-Card User Profile Extensions.
 *
 * @package Indieweb
 */

namespace Indieweb\Hcard;

use Indieweb\Relme\Domain_Icon_Map;
use Indieweb\Relme\Widget as Relme_Widget;

/**
 * Extended Profile for Rel-Me and H-Card.
 */
class User {

	/**
	 * Initialize the H-Card user functionality.
	 */
	public static function init() {
		if ( 1 === (int) \get_option( 'iw_author_url' ) ) {
			\add_filter( 'author_link', array( self::class, 'author_link' ), 10, 2 );
		}
		\add_filter( 'user_contactmethods', array( self::class, 'user_contactmethods' ) );

		\add_action( 'show_user_profile', array( self::class, 'extended_user_profile' ) );
		\add_action( 'edit_user_profile', array( self::class, 'extended_user_profile' ) );
		// Save Extra User Data.
		\add_action( 'personal_options_update', array( self::class, 'save_profile' ), 11 );
		\add_action( 'edit_user_profile_update', array( self::class, 'save_profile' ), 11 );
		\add_filter( 'wp_head', array( self::class, 'pgp' ), 11 );
		\add_action( 'rest_api_init', array( self::class, 'rest_fields' ) );
	}

	/**
	 * Register WordPress widgets.
	 */
	public static function init_widgets() {
		\register_widget( Relme_Widget::class );
	}

	/**
	 * If there is a URL set in the user profile, set author link to that.
	 *
	 * @param string $link      The author link.
	 * @param int    $author_id The author ID.
	 * @return string The modified author link.
	 */
	public static function author_link( $link, $author_id ) {
		if ( \in_the_loop() && ( \is_home() || \is_archive() || \is_singular() ) ) {
			$user_info = \get_userdata( $author_id );
			if ( ! empty( $user_info->user_url ) ) {
				$link = $user_info->user_url;
			}
		}
		return $link;
	}

	/**
	 * List of popular silos and profile URL patterns.
	 *
	 * Focusing on those which are supported by IndieAuth.
	 *
	 * @see https://indieweb.org/indieauth.com
	 * @return array Array of silo configurations.
	 */
	public static function silos() {
		$silos = array(
			'github'    => array(
				'baseurl' => 'https://github.com/%s',
				'display' => \__( 'Github username', 'indieweb' ),
			),
			'twitter'   => array(
				'baseurl' => 'https://twitter.com/%s',
				'display' => \__( 'X/Twitter username (without @)', 'indieweb' ),
			),
			'facebook'  => array(
				'baseurl' => 'https://www.facebook.com/%s',
				'display' => \__( 'Facebook ID', 'indieweb' ),
			),
			'microblog' => array(
				'baseurl' => 'https://micro.blog/%s',
				'display' => \__( 'Micro.blog username', 'indieweb' ),
			),
			'instagram' => array(
				'baseurl' => 'https://www.instagram.com/%s',
				'display' => \__( 'Instagram username', 'indieweb' ),
			),
			'flickr'    => array(
				'baseurl' => 'https://www.flickr.com/people/%s',
				'display' => \__( 'Flickr username', 'indieweb' ),
			),
			'bluesky'   => array(
				'baseurl' => 'https://bsky.app/profile/%s',
				'display' => \__( 'Bluesky Username', 'indieweb' ),
			),

			'reddit'    => array(
				'baseurl' => 'https://reddit.com/user/%s',
				'display' => \__( 'Reddit Username', 'indieweb' ),
			),
			'mastodon'  => array(
				'baseurl' => '%s',
				'display' => \__( 'Mastodon Server (URL)', 'indieweb' ),
			),
		);
		return \apply_filters( 'wp_relme_silos', $silos );
	}


	/**
	 * Additional user fields.
	 *
	 * @param array $profile_fields Current profile fields.
	 * @return array Extended profile fields.
	 */
	public static function user_contactmethods( $profile_fields ) {
		foreach ( self::silos() as $silo => $details ) {
			if ( ! array_key_exists( $silo, $profile_fields ) ) {
				$profile_fields[ $silo ] = $details['display'];
			}
		}

		// Telephone Number and PGP Key are not silos.
		$profile_fields['tel'] = \__( 'Telephone', 'indieweb' );
		$profile_fields['pgp'] = \__( 'PGP Key (URL)', 'indieweb' );
		return $profile_fields;
	}

	/**
	 * Get address fields configuration.
	 *
	 * @return array Address fields.
	 */
	public static function address_fields() {
		$address = array(
			'street_address'   => array(
				'title'       => \__( 'Street Address', 'indieweb' ),
				'description' => \__( 'Street Number and Name', 'indieweb' ),
			),
			'extended_address' => array(
				'title'       => \__( 'Extended Address', 'indieweb' ),
				'description' => \__( 'Apartment/Suite/Room Name/Number if any', 'indieweb' ),
			),
			'locality'         => array(
				'title'       => \__( 'Locality', 'indieweb' ),
				'description' => \__( 'City/State/Village', 'indieweb' ),
			),
			'region'           => array(
				'title'       => \__( 'Region', 'indieweb' ),
				'description' => \__( 'State/County/Province', 'indieweb' ),
			),
			'postal_code'      => array(
				'title'       => \__( 'Postal Code', 'indieweb' ),
				'description' => \__( 'Postal Code, such as Zip Code', 'indieweb' ),
			),
			'country_name'     => array(
				'title'       => \__( 'Country Name', 'indieweb' ),
				'description' => \__( 'Country Name', 'indieweb' ),
			),
		);
		return \apply_filters( 'wp_user_address', $address );
	}

	/**
	 * Get extra profile fields configuration.
	 *
	 * @return array Extra fields.
	 */
	public static function extra_fields() {
		$extras = array(
			'job_title'        => array(
				'title'       => \__( 'Job Title', 'indieweb' ),
				'description' => \__( 'Title or Role', 'indieweb' ),
			),
			'organization'     => array(
				'title'       => \__( 'Organization', 'indieweb' ),
				'description' => \__( 'Affiliated Organization', 'indieweb' ),
			),
			'honorific_prefix' => array(
				'title'       => \__( 'Honorific Prefix', 'indieweb' ),
				'description' => \__( 'e.g. Mrs., Mr. Dr.', 'indieweb' ),
			),
		);
		return \apply_filters( 'wp_user_extrafields', $extras );
	}

	/**
	 * Render extended user profile fields.
	 *
	 * @param \WP_User $user The user object.
	 */
	public static function extended_user_profile( $user ) {
		echo '<h3>' . \esc_html__( 'Address', 'indieweb' ) . '</h3>';
		echo '<p>' . \esc_html__( 'Fill in all fields you wish displayed.', 'indieweb' ) . '</p>';
		echo '<table class="form-table">';
		foreach ( self::address_fields() as $key => $value ) {
			self::extended_profile_text_field( $user, $key, $value['title'], $value['description'] );
		}
		echo '</table>';

		echo '<h3>' . \esc_html__( 'Additional Profile Information', 'indieweb' ) . '</h3>';
		echo '<p>' . \esc_html__( 'Fill in all fields you are wish displayed.', 'indieweb' ) . '</p>';
		echo '<table class="form-table">';
		foreach ( self::extra_fields() as $key => $value ) {
			self::extended_profile_text_field( $user, $key, $value['title'], $value['description'] );
		}
		self::extended_profile_textarea_field( $user, 'relme', \__( 'Other Sites', 'indieweb' ), \__( 'Other profiles without their own field in your user profile (One URL per line)', 'indieweb' ) );
		echo '</table>';
	}

	/**
	 * Render a text field for the extended profile.
	 *
	 * @param \WP_User $user        The user object.
	 * @param string   $key         The field key.
	 * @param string   $title       The field title.
	 * @param string   $description The field description.
	 */
	public static function extended_profile_text_field( $user, $key, $title, $description ) {
		?>
	<tr>
		<th><label for="<?php echo \esc_html( $key ); ?>"><?php echo \esc_html( $title ); ?></label></th>
		<td>
			<input type="text" name="<?php echo \esc_html( $key ); ?>" id="<?php echo \esc_html( $key ); ?>" value="<?php echo \esc_attr( \get_the_author_meta( $key, $user->ID ) ); ?>" class="regular-text" /><br />
			<span class="description"><?php echo \esc_html( $description ); ?></span>
		</td>
	</tr>
		<?php
	}

	/**
	 * Render a textarea field for the extended profile.
	 *
	 * @param \WP_User $user        The user object.
	 * @param string   $key         The field key.
	 * @param string   $title       The field title.
	 * @param string   $description The field description.
	 */
	public static function extended_profile_textarea_field( $user, $key, $title, $description ) {
		$value = \get_the_author_meta( $key, $user->ID );
		if ( is_array( $value ) ) {
			$value = implode( "\n", $value );
		}
		?>
	<tr>
		<th><label for="<?php echo \esc_html( $key ); ?>"><?php echo \esc_html( $title ); ?></label></th>
		<td>
			<textarea name="<?php echo \esc_html( $key ); ?>" id="<?php echo \esc_html( $key ); ?>"><?php echo \esc_attr( $value ); ?></textarea><br />
			<span class="description"><?php echo \esc_html( $description ); ?></span>
		</td>
	</tr>
		<?php
	}

	/**
	 * Register REST API fields.
	 */
	public static function rest_fields() {
		\register_rest_field(
			'user',
			'me',
			array(
				'get_callback' => function ( $user ) {
					$rel_me = self::get_rel_me( $user['id'] );
					return $rel_me ? array_values( $rel_me ) : array();
				},
			)
		);
		\register_rest_field(
			'user',
			'first_name',
			array(
				'get_callback' => function ( $user ) {
					return \get_user_meta( $user['id'], 'first_name', true );
				},
			)
		);
	}

	/**
	 * Save profile data.
	 *
	 * @param int $user_id The user ID.
	 * @return bool|void False if permission denied.
	 */
	public static function save_profile( $user_id ) {
		if ( ! \current_user_can( 'edit_user', $user_id ) ) {
			return false;
		}
		$fields = array_merge( self::extra_fields(), self::address_fields() );
		$p      = array_filter( $_POST ); // phpcs:ignore
		foreach ( $fields as $key => $value ) {
			if ( isset( $p[ $key ] ) ) {
				\update_user_meta( $user_id, $key, \sanitize_text_field( $p[ $key ] ) );
			} else {
				\delete_user_meta( $user_id, $key );
			}
		}
		if ( isset( $_POST['relme'] ) ) { // phpcs:ignore
			$relme = explode( "\n", $_POST['relme'] ); // phpcs:ignore
			if ( ! empty( $relme ) ) {
				\update_user_meta( $user_id, 'relme', self::clean_urls( $relme ) );
			} else {
				\delete_user_meta( $user_id, 'relme' );
			}
		}
		\delete_transient( 'indieweb_mastodon' );
	}

	/**
	 * Filters a single silo URL.
	 *
	 * @param string $url_string A string that is expected to be a silo URL.
	 * @return string|bool The filtered and escaped URL string, or FALSE if invalid.
	 */
	public static function clean_url( $url_string ) {
		$url = trim( $url_string );
		if ( ! filter_var( $url, FILTER_VALIDATE_URL ) ) {
			return false;
		}
		$host = \wp_parse_url( $url, PHP_URL_HOST );
		if ( ! $host ) {
			return false;
		}
		// Rewrite these to https as needed.
		$secure = \apply_filters( 'iwc_rewrite_secure', array( 'facebook.com', 'twitter.com', 'github.com' ) );
		if ( in_array( preg_replace( '/^www\./', '', $host ), $secure, true ) ) {
			$url = preg_replace( '/^http:/i', 'https:', $url );
		}
		$url = \esc_url_raw( $url );
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
		$array = array_map( array( self::class, 'clean_url' ), $urls );
		return array_filter( array_unique( $array ) );
	}

	/**
	 * Returns an array of links from the user profile to be used as rel-me.
	 *
	 * @param int|null $author_id The author ID.
	 * @return array|false Array of rel-me links or false.
	 */
	public static function get_rel_me( $author_id = null ) {
		if ( empty( $author_id ) ) {
			$author_id = \get_the_author_meta( 'ID' );
		}

		if ( empty( $author_id ) || 0 === $author_id ) {
			return false;
		}

		$list = array();

		foreach ( self::silos() as $silo => $details ) {
			$socialmeta = \get_the_author_meta( $silo, $author_id );

			if ( ! empty( $socialmeta ) ) {
				// If it is not a URL.
				if ( ! filter_var( $socialmeta, FILTER_VALIDATE_URL ) ) {
					// If the username has the @ symbol strip it.
					if ( ( 'twitter' === $silo ) && ( preg_match( '/^@?(\w+)$/i', $socialmeta, $matches ) ) ) {
						$socialmeta = trim( $socialmeta, '@' );
					}
					$list[ $silo ] = sprintf( $details['baseurl'], $socialmeta );
					// Pass the URL itself.
				} else {
					$list[ $silo ] = self::clean_url( $socialmeta );
				}
			}
		}

		$relme = \get_the_author_meta( 'relme', $author_id );

		if ( $relme ) {
			if ( ! is_array( $relme ) ) {
				$relme = explode( "\n", $relme );
			}
			$relme = self::clean_urls( $relme );
			foreach ( $relme as $url ) {
				$list[ preg_replace( '/^www\./', '', \wp_parse_url( $url, PHP_URL_HOST ) ) ] = $url;
			}
		}
		return array_unique( $list );
	}

	/**
	 * Prints a formatted list of rel=me to supported silos.
	 *
	 * @param int|null $author_id   The author ID.
	 * @param bool     $include_rel Whether to include rel attribute.
	 */
	public static function rel_me_list( $author_id = null, $include_rel = false ) {
		echo self::get_rel_me_list( $author_id, $include_rel ); // phpcs:ignore
	}

	/**
	 * Returns a formatted list of rel=me to supported silos.
	 *
	 * @param int|null $author_id   The author ID.
	 * @param bool     $include_rel Whether to include rel attribute.
	 * @return string|false The HTML list or false.
	 */
	public static function get_rel_me_list( $author_id = null, $include_rel = false ) {
		$list = self::get_rel_me( $author_id );
		if ( ! $list ) {
			return false;
		}
		$author_name = \get_the_author_meta( 'display_name', $author_id );
		$r           = array();
		foreach ( $list as $silo => $profile_url ) {
			$name       = Domain_Icon_Map::url_to_name( $profile_url );
			$title      = Domain_Icon_Map::get_title( $name );
			$r[ $silo ] = '<a ' . ( $include_rel ? 'rel="me" ' : '' ) . 'class="icon-' .
				$silo . ' url u-url" href="' . \esc_url( $profile_url ) . '" title="' . \esc_attr( $author_name ) . ' @ ' .
				\esc_attr( $title ) . '"><span class="relmename">' . \esc_attr( $silo ) . '</span>' . Domain_Icon_Map::get_icon( $name ) . '</a>';
		}

		$r = "<div class='relme'><ul>\n<li>" . join( "</li>\n<li>", $r ) . "</li>\n</ul></div>";

		return \apply_filters( 'indieweb_rel_me', $r, $author_id, $list ); // phpcs:ignore
	}

	/**
	 * Returns a formatted list of rel=me for the head to supported silos.
	 *
	 * @param int|null $author_id The author ID.
	 * @return string|false The HTML links or false.
	 */
	public static function relme_head_list( $author_id = null ) {
		$list = self::get_rel_me( $author_id );
		if ( ! $list ) {
			return false;
		}
		$r = array();
		foreach ( $list as $silo => $profile_url ) {
			$r[ $silo ] = '<link rel="me" href="' . \esc_url( $profile_url ) . '" />' . PHP_EOL;
		}
		return join( '', $r );
	}

	/**
	 * Get the current author ID based on context.
	 *
	 * @return int|null The author ID or null.
	 */
	public static function get_author() {
		$single_author = \get_option( 'iw_single_author' );
		if ( \is_front_page() && 1 === (int) $single_author ) {
			return \get_option( 'iw_default_author' ); // Set the author ID to default.
		} elseif ( \is_author() ) {
			$author = \get_user_by( 'slug', \get_query_var( 'author_name' ) );
			if ( $author instanceof \WP_User ) {
				return $author->ID;
			} else {
				return $author;
			}
		} else {
			return null;
		}
	}

	/**
	 * Output PGP key link in head.
	 */
	public static function pgp() {
		$author_id = self::get_author();
		if ( ! $author_id ) {
			return;
		}
		$pgp = \get_user_option( 'pgp', $author_id );
		if ( ! empty( $pgp ) ) {
			printf( '<link rel="pgpkey" href="%1$s" />', \esc_url( $pgp ) );
		}
	}

	/**
	 * Output rel-me links in head.
	 */
	public static function relme_head() {
		$author_id = self::get_author();
		if ( ! $author_id ) {
			return;
		}
		echo self::relme_head_list( $author_id ); // phpcs:ignore
	}

	/**
	 * Get default display options for h-card.
	 *
	 * @return array Default display options.
	 */
	public static function get_hcard_display_defaults() {
		$defaults = array(
			'style'         => 'div',
			'container-css' => '',
			'single-css'    => '',
			'avatar_size'   => 96,
			'avatar'        => true,  // Display Avatar.
			'location'      => true,  // Display location elements.
			'notes'         => true,  // Display Bio/Notes.
			'email'         => false, // Display email.
			'me'            => true,  // Display rel-me links inside h-card.
		);
		return \apply_filters( 'hcard_display_defaults', $defaults );
	}

	/**
	 * Looks up, and returns if exists, the full path to a given file in the
	 * /templates subdirectory of the active theme (child, parent).
	 * Defaults to the /templates subdirectory in this plugin.
	 *
	 * @param string $file_name File name, example: h-card.php.
	 * @return string Full path to file.
	 */
	public static function get_template_file( $file_name ) {
		$theme_template_file = \locate_template( 'templates/' . $file_name );
		return $theme_template_file ? $theme_template_file : \dirname( __DIR__, 2 ) . '/templates/' . $file_name;
	}

	/**
	 * Render the h-card for a user.
	 *
	 * @param int|\WP_User $user The user ID or object.
	 * @param array        $args Display arguments.
	 * @return string|false The h-card HTML or false.
	 */
	public static function hcard( $user, $args = array() ) {
		if ( ! $user ) {
			return false;
		}
		$user = new \WP_User( $user );
		if ( ! $user ) {
			return false;
		}

		$args = \wp_parse_args( $args, self::get_hcard_display_defaults() );
		// Variables are used in the included template file (h-card.php).
		// phpcs:disable VariableAnalysis.CodeAnalysis.VariableAnalysis.UnusedVariable
		if ( $args['avatar'] ) {
			$avatar = \get_avatar(
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
		$url   = $user->has_prop( 'user_url' ) ? $user->get( 'user_url' ) : $url = \get_author_posts_url( $user->ID );
		$name  = $user->get( 'display_name' );
		$email = $user->get( 'user_email' );
		// phpcs:enable VariableAnalysis.CodeAnalysis.VariableAnalysis.UnusedVariable
		ob_start();
		include self::get_template_file( 'h-card.php' );
		$return = ob_get_contents();
		ob_end_clean();
		return $return;
	}
}
