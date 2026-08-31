<?php
/**
 * Compatibility shims for the Icons API.
 *
 * WordPress 7.1 introduced the Icons API. This file provides the functions
 * on older versions, backed by a minimal registry, so icons can be
 * registered and rendered the same way on every supported WordPress
 * version. The registry behaves like the core one: icons are registered
 * under a collection, the content of file based icons is loaded lazily and
 * the markup is sanitized the same way as in core.
 *
 * @package Indieweb
 */

if ( ! function_exists( 'wp_get_icon' ) ) :

	/**
	 * Return the icon registry that backs the shims.
	 *
	 * @access private
	 *
	 * @return array The registry with a `collections` and an `icons` list.
	 */
	function &_indieweb_icons_registry() {
		static $registry = array(
			'collections' => array(),
			'icons'       => array(),
		);

		return $registry;
	}

	/**
	 * Sanitize the icon SVG content, mirrors WP_Icons_Registry.
	 *
	 * @access private
	 *
	 * @param string $content The icon SVG content to sanitize.
	 * @return string The sanitized icon SVG content.
	 */
	function _indieweb_sanitize_icon_content( $content ) {
		$allowed_tags = array(
			'svg'     => array(
				'class'       => true,
				'xmlns'       => true,
				'width'       => true,
				'height'      => true,
				'viewbox'     => true,
				'aria-hidden' => true,
				'role'        => true,
				'focusable'   => true,
			),
			'path'    => array(
				'fill'      => true,
				'fill-rule' => true,
				'd'         => true,
				'transform' => true,
			),
			'polygon' => array(
				'fill'      => true,
				'fill-rule' => true,
				'points'    => true,
				'transform' => true,
				'focusable' => true,
			),
		);

		return wp_kses( $content, $allowed_tags );
	}

	/**
	 * Get the sanitized content of a registered icon.
	 *
	 * The content of icons registered with a `file_path` is loaded lazily.
	 *
	 * @access private
	 *
	 * @param string $name The namespaced icon name.
	 * @return string|null The SVG content or null if not found.
	 */
	function _indieweb_get_icon_content( $name ) {
		$registry = &_indieweb_icons_registry();

		if ( ! is_string( $name ) || ! isset( $registry['icons'][ $name ] ) ) {
			return null;
		}

		if ( ! isset( $registry['icons'][ $name ]['content'] ) ) {
			$file = $registry['icons'][ $name ]['file_path'];
			if ( ! is_string( $file ) || '.svg' !== substr( $file, -4 ) || ! is_readable( $file ) ) {
				return null;
			}

			$content = _indieweb_sanitize_icon_content( file_get_contents( $file ) ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents -- Reading a local SVG file.
			if ( empty( $content ) ) {
				return null;
			}

			$registry['icons'][ $name ]['content'] = $content;
		}

		return $registry['icons'][ $name ]['content'];
	}

	/**
	 * Apply size, class and label arguments without the HTML API.
	 *
	 * Best effort fallback for WordPress before 6.2: the attributes are
	 * added right after the opening `svg` tag, so they win over duplicates.
	 *
	 * @access private
	 *
	 * @param string $svg  The SVG markup.
	 * @param array  $args Parsed icon arguments.
	 * @return string The decorated SVG markup.
	 */
	function _indieweb_decorate_icon_svg_legacy( $svg, $args ) {
		$attributes = '';

		if ( is_numeric( $args['size'] ) ) {
			$size        = absint( $args['size'] );
			$attributes .= sprintf( ' width="%1$d" height="%1$d"', $size );
		}

		if ( ! empty( $args['class'] ) ) {
			$attributes .= sprintf( ' class="%s"', esc_attr( $args['class'] ) );
		}

		if ( ! empty( $args['label'] ) ) {
			$attributes .= sprintf( ' role="img" aria-label="%s"', esc_attr( $args['label'] ) );
		} else {
			$attributes .= ' aria-hidden="true" focusable="false"';
		}

		return preg_replace( '/<svg\b/', '<svg' . $attributes, $svg, 1 );
	}

	if ( ! function_exists( 'wp_register_icon_collection' ) ) {
		/**
		 * Register a new icon collection.
		 *
		 * @see https://developer.wordpress.org/reference/functions/wp_register_icon_collection/
		 *
		 * @param string $slug Icon collection slug.
		 * @param array  $args Arguments for registering an icon collection.
		 * @return boolean True if the icon collection was registered successfully, else false.
		 */
		function wp_register_icon_collection( $slug, $args ) {
			$registry = &_indieweb_icons_registry();

			if ( ! is_string( $slug ) || ! preg_match( '/^[a-z0-9](?:[a-z0-9_-]*[a-z0-9])?$/', $slug ) || isset( $registry['collections'][ $slug ] ) ) {
				return false;
			}

			if ( ! is_array( $args ) || empty( $args['label'] ) || ! is_string( $args['label'] ) ) {
				return false;
			}

			$registry['collections'][ $slug ] = array_merge(
				array( 'description' => '' ),
				$args,
				array( 'slug' => $slug )
			);

			return true;
		}
	}

	if ( ! function_exists( 'wp_unregister_icon_collection' ) ) {
		/**
		 * Unregister an icon collection.
		 *
		 * Any icons registered under the given collection are also unregistered.
		 *
		 * @see https://developer.wordpress.org/reference/functions/wp_unregister_icon_collection/
		 *
		 * @param string $slug Icon collection slug.
		 * @return boolean True if the icon collection was unregistered successfully, else false.
		 */
		function wp_unregister_icon_collection( $slug ) {
			$registry = &_indieweb_icons_registry();

			if ( ! is_string( $slug ) || ! isset( $registry['collections'][ $slug ] ) ) {
				return false;
			}

			foreach ( array_keys( $registry['icons'] ) as $icon_name ) {
				if ( 0 === strpos( $icon_name, $slug . '/' ) ) {
					unset( $registry['icons'][ $icon_name ] );
				}
			}

			unset( $registry['collections'][ $slug ] );

			return true;
		}
	}

	if ( ! function_exists( 'wp_register_icon' ) ) {
		/**
		 * Register a new icon.
		 *
		 * @see https://developer.wordpress.org/reference/functions/wp_register_icon/
		 *
		 * @param string $icon_name Namespaced icon name in the form "collection/icon-name".
		 * @param array  $args      List of properties for the icon (`label` and either `content` or `file_path`).
		 * @return boolean True if the icon was registered successfully, else false.
		 */
		function wp_register_icon( $icon_name, $args ) {
			$registry = &_indieweb_icons_registry();

			if ( ! is_string( $icon_name ) || false === strpos( $icon_name, '/' ) ) {
				return false;
			}

			list( $collection, $name ) = explode( '/', $icon_name, 2 );

			if ( ! preg_match( '/^[a-z0-9](?:[a-z0-9_-]*[a-z0-9])?$/', $name ) ) {
				return false;
			}

			if ( ! isset( $registry['collections'][ $collection ] ) || isset( $registry['icons'][ $icon_name ] ) ) {
				return false;
			}

			if ( ! is_array( $args ) || empty( $args['label'] ) || ! is_string( $args['label'] ) ) {
				return false;
			}

			// Icons have to provide either `content` or `file_path`, but not both.
			if ( isset( $args['content'] ) === isset( $args['file_path'] ) ) {
				return false;
			}

			if ( isset( $args['content'] ) ) {
				$content = _indieweb_sanitize_icon_content( $args['content'] );
				if ( empty( $content ) ) {
					return false;
				}
				$args['content'] = $content;
			}

			$registry['icons'][ $icon_name ] = array_merge(
				$args,
				array(
					'name'       => $icon_name,
					'collection' => $collection,
				)
			);

			return true;
		}
	}

	if ( ! function_exists( 'wp_unregister_icon' ) ) {
		/**
		 * Unregister an icon.
		 *
		 * @see https://developer.wordpress.org/reference/functions/wp_unregister_icon/
		 *
		 * @param string $icon_name Namespaced icon name in the form "collection/icon-name".
		 * @return boolean True if the icon was unregistered successfully, else false.
		 */
		function wp_unregister_icon( $icon_name ) {
			$registry = &_indieweb_icons_registry();

			if ( ! is_string( $icon_name ) || ! isset( $registry['icons'][ $icon_name ] ) ) {
				return false;
			}

			unset( $registry['icons'][ $icon_name ] );

			return true;
		}
	}

	/**
	 * Return the SVG markup for a registered icon.
	 *
	 * @see https://developer.wordpress.org/reference/functions/wp_get_icon/
	 *
	 * @param string $name The namespaced icon name (e.g. 'indieweb/mastodon').
	 * @param array  $args Optional. Arguments for the icon (`size`, `class` and `label`).
	 * @return string SVG markup for the icon, or empty string if not found.
	 */
	function wp_get_icon( $name, $args = array() ) {
		$svg = _indieweb_get_icon_content( $name );
		if ( empty( $svg ) ) {
			return '';
		}

		$args = wp_parse_args(
			$args,
			array(
				'size'  => 24,
				'class' => '',
				'label' => '',
			)
		);

		if ( ! class_exists( 'WP_HTML_Tag_Processor' ) ) {
			return _indieweb_decorate_icon_svg_legacy( $svg, $args );
		}

		$processor = new WP_HTML_Tag_Processor( $svg );
		if ( ! $processor->next_tag( 'svg' ) ) {
			return '';
		}

		if ( is_numeric( $args['size'] ) ) {
			$size = absint( $args['size'] );
			$processor->set_attribute( 'width', (string) $size );
			$processor->set_attribute( 'height', (string) $size );
		}

		if ( ! empty( $args['class'] ) ) {
			foreach ( preg_split( '/\s+/', $args['class'], -1, PREG_SPLIT_NO_EMPTY ) as $class_name ) {
				$processor->add_class( $class_name );
			}
		}

		if ( ! empty( $args['label'] ) ) {
			$processor->set_attribute( 'role', 'img' );
			$processor->set_attribute( 'aria-label', $args['label'] );
			$processor->remove_attribute( 'aria-hidden' );
			$processor->remove_attribute( 'focusable' );
		} else {
			$processor->set_attribute( 'aria-hidden', 'true' );
			$processor->set_attribute( 'focusable', 'false' );
			$processor->remove_attribute( 'role' );
			$processor->remove_attribute( 'aria-label' );
		}

		return $processor->get_updated_html();
	}

endif;
