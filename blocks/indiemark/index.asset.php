<?php
// phpcs:ignoreFile WordPress.Files.FileName.NotHyphenatedLowercase -- WordPress block asset convention.
/**
 * IndieMark editor dependencies.
 *
 * @package Indieweb
 */

return array(
	'dependencies' => array( 'wp-blocks', 'wp-element', 'wp-block-editor', 'wp-components', 'wp-i18n', 'wp-server-side-render' ),
	'version'      => (string) filemtime( __DIR__ . '/index.js' ),
);
