<div class="wrap">

	<h1><?php esc_html_e( 'IndieWebify your WordPress-Blog', 'indieweb' ); ?></h1>

	<h2><?php esc_html_e( 'Getting Started', 'indieweb' ); ?></h2>

	<p>
	<?php
	esc_html_e(
		'The IndieWeb Plugin can help you establish your identity online, as well as recommending
			other plugins to support additional Indieweb features.', 'indieweb'
	);
?>
</p>

	<p><?php _e( 'Once you have these activated, you can setup <a href="https://www.brid.gy" target="_blank">Bridgy</a> to connect your blog to responses from sites such as Facebook and Twitter too, allowing for a seamless experience.', 'indieweb' ); ?></p>

	<h2><?php esc_html_e( 'Plugins', 'indieweb' ); ?></h2>

	<p><?php _e( 'For more information on these plugins, visit the <a href="https://indieweb.org/wordpress" target="_blank">WordPress page</a> on the IndieWeb wiki', 'indieweb' ); ?></p>

	<ul>
		<li>
			<h3><?php esc_html_e( 'Send and receive responses with your site', 'indieweb' ); ?></h3>
			<p><?php esc_html_e( 'Send and receive comments, likes, reposts, and other kinds of post responses using your own site!', 'indieweb' ); ?></p>
			<ol>
				<li><?php _e( '<strong>Webmention</strong> <em>(Required)</em> - allows you to send and receive by adding webmention support to WordPress. Mentions show up as comments on your site.', 'indieweb' ); ?></li>
				<li><?php _e( '<strong>Semantic Linkbacks</strong> <em>(Required)</em> - makes IndieWeb comments and mentions look better on your site.', 'indieweb' ); ?></li>
				<li><?php _e( '<strong>Post Kinds</strong> - Allows you to reply/like/RSVP etc to another site from your own, by adding support for kinds of posts to WordPress.', 'indieweb' ); ?></li>
			</ol>
		</li>

		<li>
			<h3><?php esc_html_e( 'Syndicate your content to other sites', 'indieweb' ); ?></h3>
			<p><?php esc_html_e( 'Linking to the syndicated version of a post allows for bridgy to be able to discover the post as well as for end users to comment on those sites as a way of replying.', 'indieweb' ); ?></p>
		<ol>
			<li><?php _e( '<strong>Bridgy Publish</strong> - Adds a user interface for using Bridgy to publish to other sites', 'indieweb' ); ?></li>
			<li><?php _e( '<strong>Syndication Links</strong> - Adds fields to a post to allow manual entry of syndication links as well as automatically from a supported syndication plugin. Fully supports <a href="https://github.com/crowdfavorite/wp-social" target="_blank">Social</a>, partial support for <a href="https://wordpress.org/plugins/social-networks-auto-poster-facebook-twitter-g/" target="_blank">NextScripts: Social Networks Auto-Poster (aka SNAP)</a>.', 'indieweb' ); ?></li>
	</ol>
		</li>

		<li>
			<h3><?php esc_html_e( 'Other', 'indieweb' ); ?></h3>
			<ol>
				<li><?php _e( '<strong>MicroPub</strong> - A MicroPub Server', 'indieweb' ); ?></li>
				<li><?php _e( '<strong>Indieauth</strong> - The plugin lets you login to the WordPress backend via IndieAuth. It uses the URL from the profile page to identify the blog user.', 'indieweb' ); ?></li>
			</ol>
		</li>
	</ul>

	<p><a href="<?php echo admin_url( 'admin.php?page=indieweb-installer' ); ?>" class="button button-primary"><?php esc_html_e( 'Install Plugins', 'indieweb' ); ?></a></p>

	<h2><?php esc_html_e( 'Themes', 'indieweb' ); ?></h2>

	<p><?php _e( 'Some WordPress themes are compatible with microformats. The IndieWeb uses microformats2, the latest version, to mark up sites so that they can be interpreted by other sites when retrieved. Most parsers will fall back onto the older format if available.', 'indieweb' ); ?></p>

	<p><?php _e( 'Formatting your site so other sites can consume the information allows for the communications IndieWeb sites support. For example, a class of u-like-of added to a link to a site you liked to indicates that relationship.', 'indieweb' ); ?></p>

	<p>
	<?php
	_e(
		'Currently, <a href="https://wordpress.org/themes/sempress/"
			target="_blank">SemPress</a> is the only theme in the WordPress repository that is fully
			microformats2 compliant. <a href="http://wordpress.org/themes/independent-publisher/"
			target="_blank">Independent Publisher</a> has been updated to include basic microformats2 and
			webmention display support. In practice, most themes will work relatively well out of the box, though there can be some minor display issues.', 'indieweb'
	);
?>
</p>

	<h2><?php esc_html_e( 'What is the IndieWeb?', 'indieweb' ); ?></h2>

	<p><?php _e( '<strong>Own your data.</strong> Create and publish content on your own site, and only optionally syndicate to third-party silos.', 'indieweb' ); ?></p>
	<p>
	<?php
	_e(
		'This is the basis of the <strong>IndieWeb</strong>. For more, see <a
			href="https://indieweb.org/principles" target="_blank">principles</a> and <a
			href="https://indieweb.org/why" target="_blank">why</a>.', 'indieweb'
	);
?>
</p>

	<p><?php _e( 'For even more information, please visit the <a href="https://indieweb.org/" target="_blank"><em>IndieWeb</em> wiki</a>.', 'indieweb' ); ?></p>

</div>				  
