<?php
/**
 * IndieMark block and shortcode.
 *
 * @package Indieweb
 */

namespace Indieweb;

/**
 * Detect IndieMark capabilities and display the resulting mark.
 */
class IndieMark {
	/**
	 * Register both entry points.
	 */
	public static function register() {
		\wp_register_style( 'indieweb-indiemark', INDIEWEB_PLUGIN_URL . 'blocks/indiemark/style.css', array(), (string) filemtime( INDIEWEB_PLUGIN_DIR . '/blocks/indiemark/style.css' ) );
		\register_block_type( INDIEWEB_PLUGIN_DIR . '/blocks/indiemark', array( 'render_callback' => array( self::class, 'render' ) ) );
		\wp_set_script_translations( 'indieweb-indiemark-editor-script', 'indieweb' );
		\add_shortcode( 'indieweb_indiemark', array( self::class, 'shortcode' ) );
		\add_action( 'indieweb_indiemark_refresh', array( self::class, 'refresh' ) );
		\add_action( 'transition_post_status', array( self::class, 'post_changed' ), 10, 3 );
		\add_action( 'switch_theme', array( self::class, 'schedule_refresh' ) );
		\add_action( 'updated_option', array( self::class, 'option_changed' ) );
		\add_action( 'added_option', array( self::class, 'option_changed' ) );
		\add_action( 'deleted_option', array( self::class, 'option_changed' ) );
	}

	/**
	 * Check rendered markup in the background, so rendering never waits on HTTP requests.
	 */
	public static function schedule_refresh() {
		if ( ! \wp_next_scheduled( 'indieweb_indiemark_refresh' ) ) {
			\wp_schedule_single_event( time(), 'indieweb_indiemark_refresh' );
		}
	}

	/**
	 * Store the rendered markup checks.
	 */
	public static function refresh() {
		\set_transient(
			'indieweb_indiemark_markup',
			array(
				'hcard'  => self::homepage_has_hcard(),
				'hentry' => self::posts_have_hentry(),
			),
			DAY_IN_SECONDS
		);
	}

	/**
	 * Refresh markup checks when published content changes.
	 *
	 * @param string   $new_status New post status.
	 * @param string   $old_status Old post status.
	 * @param \WP_Post $post       Post object.
	 */
	public static function post_changed( $new_status, $old_status, $post ) {
		if ( ( 'publish' === $new_status || 'publish' === $old_status ) && \is_post_type_viewable( $post->post_type ) ) {
			self::schedule_refresh();
		}
	}

	/**
	 * Refresh markup checks when a relevant option changes.
	 *
	 * @param string $option Changed option name.
	 */
	public static function option_changed( $option ) {
		if ( in_array( $option, array( 'active_plugins', 'home', 'template', 'stylesheet', 'site_icon', 'blog_public', 'default_comment_status', 'permalink_structure', 'indieblocks_settings', 'webmention_support_post_types', 'uf2_author', 'uf2_media' ), true ) || 0 === strpos( $option, 'theme_mods_' ) || 0 === strpos( $option, 'iw_' ) ) {
			self::schedule_refresh();
		}
	}

	/**
	 * Detect capabilities from active plugins, site settings, and stored markup checks.
	 *
	 * @return array Feature definitions and evidence.
	 */
	public static function detect() {
		$plugins     = array_merge( (array) \get_option( 'active_plugins', array() ), array_keys( (array) \get_site_option( 'active_sitewide_plugins', array() ) ) );
		$active      = static function ( $slug ) use ( $plugins ) {
			foreach ( $plugins as $plugin ) {
				if ( 0 === strpos( $plugin, $slug . '/' ) ) {
					return true;
				}
			}
			return false;
		};
		$posts       = \wp_count_posts( 'post' );
		$indieblocks = $active( 'indieblocks' );
		$options     = $indieblocks ? (array) \get_option( 'indieblocks_settings', array() ) : array();
		$block_theme = \wp_is_block_theme() || \current_theme_supports( 'add_block_template_part_support' );
		$formats     = \get_theme_support( 'post-formats' );
		$rendered    = \get_transient( 'indieweb_indiemark_markup' );
		if ( ! is_array( $rendered ) ) {
			self::schedule_refresh();
			$rendered = array(
				'hcard'  => null,
				'hentry' => false,
			);
		}
		$public      = '1' === (string) \get_option( 'blog_public' );
		$host        = strtolower( rtrim( (string) \wp_parse_url( \home_url(), PHP_URL_HOST ), '.' ) );
		$domain      = false !== strpos( $host, '.' ) && 'localhost' !== substr( $host, -9 ) && ! filter_var( trim( $host, '[]' ), FILTER_VALIDATE_IP );
		$hcard       = \has_site_icon() ? $rendered['hcard'] : false;
		$kind_plugin = $active( Plugin_Installer::POST_KINDS_CLASSIC ) || $active( Plugin_Installer::POST_KINDS_BLOCK );
		$kinds       = ! empty( $formats[0] ) || $kind_plugin || ( $indieblocks && ( ! empty( $options['enable_notes'] ) || ! empty( $options['post_types'] ) ) );
		$replies     = $kind_plugin || ( $indieblocks && ( ! isset( $options['enable_blocks'] ) || ! empty( $options['enable_blocks'] ) ) );
		$syndication = $active( 'syndication-links' );
		$webmention  = ( $active( 'webmention' ) && in_array( 'post', (array) \get_option( 'webmention_support_post_types', array( 'post', 'page' ) ), true ) ) || ( $indieblocks && ! empty( $options['webmention'] ) && in_array( 'post', (array) ( $options['webmention_post_types'] ?? array( 'post' ) ), true ) );
		$markup      = \current_theme_supports( 'microformats2' ) || in_array( \get_template(), array( 'sempress', 'autonomie' ), true ) || $active( 'microformats2' ) || $active( 'microformats-2' ) || $active( 'wp-uf2' ) || $active( 'wordpress-uf2' ) || ( $indieblocks && $block_theme && ! empty( $options['add_mf2'] ) ) || $rendered['hentry'];
		$events      = false;
		if ( \taxonomy_exists( 'kind' ) ) {
			$event  = \get_term_by( 'slug', 'event', 'kind' );
			$events = $event && $event->count > 0;
		}
		$features = array(
			'domain'          => array( 0, \__( 'Personal domain', 'indieweb' ), $domain, \__( 'Detected from the site address.', 'indieweb' ) ),
			'authentication'  => array( 1, \__( 'Web sign-in', 'indieweb' ), $active( 'indieauth' ), \__( 'Detected from the IndieAuth plugin.', 'indieweb' ) ),
			'posts'           => array( 1, \__( 'Two published posts', 'indieweb' ), (int) $posts->publish >= 2, \__( 'Detected from published posts.', 'indieweb' ) ),
			'markup'          => array( 1, \__( 'h-entry markup', 'indieweb' ), $markup, \__( 'Detected from theme support, Microformats2, IndieBlocks settings, or published post markup.', 'indieweb' ) ),
			'indexing'        => array( 1, \__( 'Search visibility', 'indieweb' ), $public, \__( 'Detected from WordPress search visibility settings.', 'indieweb' ) ),
			'hcard'           => array( 2, \__( 'h-card and site icon', 'indieweb' ), $hcard, \__( 'Detected from homepage markup and the site icon setting.', 'indieweb' ) ),
			'post_types'      => array( 2, \__( 'Multiple content types', 'indieweb' ), $kinds, \__( 'Detected from WordPress post formats, Post Kinds, or IndieBlocks.', 'indieweb' ) ),
			'syndication'     => array( 2, \__( 'Syndication', 'indieweb' ), $syndication, \__( 'Detected from Syndication Links.', 'indieweb' ) ),
			'posting_ui'      => array( 2, \__( 'Syndication interface', 'indieweb' ), $syndication, \__( 'Provided by Syndication Links.', 'indieweb' ) ),
			'navigation'      => array( 2, \__( 'Post navigation', 'indieweb' ), true, \__( 'Provided by WordPress.', 'indieweb' ) ),
			'search'          => array( 2, \__( 'Search', 'indieweb' ), true, \__( 'Provided by WordPress.', 'indieweb' ) ),
			'aggregation'     => array( 2, \__( 'Links and embeds', 'indieweb' ), true, \__( 'Provided by WordPress.', 'indieweb' ) ),
			'replies'         => array( 3, \__( 'Reply posts', 'indieweb' ), $replies, \__( 'Provided by Post Kinds or IndieBlocks.', 'indieweb' ) ),
			'webmentions'     => array( 3, \__( 'Send Webmentions', 'indieweb' ), $webmention, \__( 'Detected from Webmention and its supported post types.', 'indieweb' ) ),
			'push'            => array( 3, \__( 'Push subscriptions', 'indieweb' ), $active( 'pubsubhubbub' ), \__( 'Detected from PubSubHubbub.', 'indieweb' ) ),
			'responses'       => array( 4, \__( 'Likes, reposts, and RSVPs', 'indieweb' ), $replies && $webmention, \__( 'Detected from post kind and Webmention support.', 'indieweb' ) ),
			'comments'        => array( 4, \__( 'Receive responses', 'indieweb' ), $webmention && 'open' === \get_option( 'default_comment_status' ), \__( 'Detected from Webmention and comment settings.', 'indieweb' ) ),
			'reply_context'   => array( 4, \__( 'Reply context', 'indieweb' ), $replies, \__( 'Provided by Post Kinds or IndieBlocks.', 'indieweb' ) ),
			'events'          => array( 5, \__( 'Event posts', 'indieweb' ), (bool) $events, \__( 'Detected from published event posts.', 'indieweb' ) ),
			'comment_updates' => array( 5, \__( 'Response updates', 'indieweb' ), $webmention, \__( 'Provided by Webmention.', 'indieweb' ) ),
		);

		/**
		 * Extend detection for custom integrations.
		 *
		 * Each feature is an array of level, label, boolean/null status, and evidence.
		 *
		 * @param array $features Detected features.
		 */
		return \apply_filters( 'indieweb_indiemark_features', $features );
	}

	/**
	 * Check rendered homepage markup.
	 *
	 * @return bool|null Whether an h-card was found, or null if unavailable.
	 */
	public static function homepage_has_hcard() {
		return self::url_has_class( \home_url( '/' ), 'h-card' );
	}

	/**
	 * Detect post microformats when no known provider is installed.
	 *
	 * @return bool Whether two published posts use h-entry markup.
	 */
	public static function posts_have_hentry() {
		$posts = \get_posts(
			array(
				'numberposts' => 2,
				'post_status' => 'publish',
			)
		);
		if ( count( $posts ) < 2 ) {
			return false;
		}
		foreach ( $posts as $post ) {
			if ( true !== self::url_has_class( \get_permalink( $post ), 'h-entry' ) ) {
				return false;
			}
		}
		return true;
	}

	/**
	 * Detect a microformat class in rendered HTML.
	 *
	 * @param string $url Page URL.
	 * @param string $class_name Microformat class.
	 * @return bool|null Detected status, or null on request failure.
	 */
	public static function url_has_class( $url, $class_name ) {
		$response = \wp_remote_get(
			$url,
			array(
				'timeout'     => 3,
				'redirection' => 3,
			)
		);
		$found    = null;
		if ( ! \is_wp_error( $response ) && 200 === \wp_remote_retrieve_response_code( $response ) ) {
			$html  = new \WP_HTML_Tag_Processor( \wp_remote_retrieve_body( $response ) );
			$found = $html->next_tag( array( 'class_name' => $class_name ) );
		}
		return $found;
	}

	/**
	 * Render the shortcode using the block renderer.
	 *
	 * @param array|string $attributes Shortcode attributes.
	 * @return string HTML.
	 */
	public static function shortcode( $attributes ) {
		$attributes = \shortcode_atts(
			array(
				'level'   => 'auto',
				'details' => 'false',
				'confirm' => '',
				'exclude' => '',
			),
			$attributes,
			'indieweb_indiemark'
		);
		$overrides  = array();
		foreach ( array(
			'confirm' => 'yes',
			'exclude' => 'no',
		) as $key => $value ) {
			foreach ( explode( ',', $attributes[ $key ] ) as $feature ) {
				$overrides[ trim( $feature ) ] = $value;
			}
		}
		$attributes['overrides'] = $overrides;
		$attributes['details']   = ! in_array( strtolower( $attributes['details'] ), array( 'false', '0', 'no' ), true );
		return self::render( $attributes );
	}

	/**
	 * Render current detection and per-instance overrides.
	 *
	 * @param array          $attributes Block or shortcode attributes.
	 * @param string         $content    Block content.
	 * @param \WP_Block|null $block      Block instance, null for the shortcode.
	 * @return string HTML.
	 */
	public static function render( $attributes = array(), $content = '', $block = null ) {
		$attributes = \wp_parse_args(
			$attributes,
			array(
				'level'     => 'auto',
				'details'   => false,
				'overrides' => array(),
			)
		);
		$manual     = in_array( (string) $attributes['level'], array( '0', '1', '2', '3', '4', '5' ), true );
		$features   = $manual && ! $attributes['details'] ? array() : self::detect();
		$overrides  = is_array( $attributes['overrides'] ) ? $attributes['overrides'] : array();
		foreach ( $features as $key => &$feature ) {
			if ( isset( $overrides[ $key ] ) && in_array( $overrides[ $key ], array( 'yes', 'no' ), true ) ) {
				$feature[2] = 'yes' === $overrides[ $key ];
				$feature[3] = \__( 'Manually set by the site owner.', 'indieweb' );
			}
		}
		unset( $feature );

		$level = 0;
		if ( $manual ) {
			$level = (int) $attributes['level'];
		} else {
			for ( $candidate = 0; $candidate <= 5; ++$candidate ) {
				foreach ( $features as $feature ) {
					if ( $feature[0] <= $candidate && true !== $feature[2] ) {
						break 2;
					}
				}
				$level = $candidate;
			}
		}

		// The shortcode also needs the shared stylesheet.
		\wp_enqueue_style( 'indieweb-indiemark' );
		$title = sprintf(
			/* translators: %d: IndieMark level. */
			\__( 'IndieMark Level %d', 'indieweb' ),
			$level
		);
		$class = 'wp-block-indieweb-indiemark' . ( $attributes['details'] ? ' has-details' : '' );
		$html  = '<div ' . ( $block instanceof \WP_Block ? \get_block_wrapper_attributes( array( 'class' => $class ) ) : 'class="' . \esc_attr( $class ) . '"' ) . '>';
		$html .= '<p class="indieweb-indiemark-title"><span class="indieweb-indiemark-seal" aria-hidden="true">' . \esc_html( (string) $level ) . '</span><span>' . \esc_html( $title ) . '</span></p>';
		if ( $attributes['details'] ) {
			$html .= '<ul>';
			foreach ( $features as $feature ) {
				$status = null === $feature[2] ? \__( 'Unconfirmed', 'indieweb' ) : ( $feature[2] ? \__( 'Met', 'indieweb' ) : \__( 'Not met', 'indieweb' ) );
				$html  .= '<li><strong>' . \esc_html( $feature[1] ) . '</strong>: ' . \esc_html( $status ) . '<small>' . \esc_html( $feature[3] ) . '</small></li>';
			}
			$html .= '</ul>';
		}
		return $html . '</div>';
	}
}
