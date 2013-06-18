<?php
/*
 Plugin Name: IndieWeb
 Plugin URI: https://github.com/pfefferle/wordpress-indieweb-comments
 Description: Adds some IndieWeb functionality to WordPress' comment system
 Author: pfefferle
 Author URI: http://notizblog.org/
 Version: 1.0.0-dev
*/

// webactions

/**
 * reply links with webactions support
 *
 * @link http://indiewebcamp.com/webactions
 */
function indieweb_reply_link( $link, $args, $comment, $post ) {
  $permalink = get_permalink($post->ID);
  
  return "<action do='post reply' with='$permalink'>$link</action>";
}
add_filter('comment_reply_link', 'indieweb_reply_link', null, 4);
