<div class="wrap indieweb-getting-started">

	<h1><?php esc_html_e( 'IndieWebify your WordPress-Blog', 'indieweb' ); ?></h1>

	<div class="indieweb-flex-container">
		<div class="indieweb-flex getting-started">
			<h2><?php esc_html_e( 'Getting Started', 'indieweb' ); ?></h2>

			<p>
			<?php
			esc_html_e(
				'The IndieWeb Plugin can help you establish your identity online, as well as recommending other plugins to support additional IndieWeb features.', 'indieweb'
			);
			?>
			</p>
			<ol>
				<li><?php _e( 'Complete your user profile, adding in connections to other websites. This allows you to connect your identity on those sites to your website.', 'indieweb' ); ?></li>
				<li><?php _e( 'Under Options on the IndieWeb menu in the sidebar, set whether this is a single author or multi-author site', 'indieweb' ); ?></li>
				<li><?php _e( 'Links to your various sites will now appear on your site, allowing you to use Indieauth and other services. If you want them visible to site visitors, you can add the rel-me widget to your site.', 'indieweb' ); ?></li>
			</ol>
		</div>
		<div class="indieweb-flex plugins">
			<h2><?php esc_html_e( 'Plugins', 'indieweb' ); ?></h2>

			<p><?php _e( 'After you have established your identity, you can now log into the <a href="https://indieweb.org">IndieWeb wiki</a> and add a user page as a way of introducing yourself to the community. The wiki is also a great source of IndieWeb information', 'indieweb' ); ?></p>

			<ol>
				<li><?php _e( 'Install and activate the Webmentions and Semantic Linkbacks plugins. These will allow you to receive responses such as replies, likes, etc from other IndieWeb sites. You can configure it in the Discussion Settings', 'indieweb' ); ?></li>
				<li><?php _e( 'Install and activate the Micropub and IndieAuth plugins. The Micropub plugin will allow you to publish to your website using Micropub and the IndieAuth plugin adds an IndieAuth endpoint to allow it to authenticate to your site. Without it, the Micropub plugin will use a third-party IndieAuth server to authenticate to your site.', 'indieweb' ); ?></li>
				<li><?php _e( 'You can now install the Syndication Links plugin. It will add its settings options under IndieWeb and allow you to connect your blog to responses from sites such as Flickr, Twitter and Github.', 'indieweb' ); ?></li>
				<li><?php _e( 'Browse the Extensions page for a list of other recommended IndieWeb plugins that can expand your functionality.', 'indieweb' ); ?></li>
			</ol>

			<p><a href="<?php echo admin_url( 'admin.php?page=indieweb-installer' ); ?>" class="button button-primary"><?php esc_html_e( 'Install Plugins', 'indieweb' ); ?></a></p>
		</div>

		<div class="indieweb-flex themes">
			<h2><?php esc_html_e( 'Themes', 'indieweb' ); ?></h2>

			<p><?php _e( 'The IndieWeb uses <a href="https://indieweb.org/microformats">microformats</a> 2 to mark up sites so that they can be interpreted by other sites when retrieved.', 'indieweb' ); ?></p>
			<p><?php _e( 'The Microformats 2 Plugin attempts to add microformats to a theme that does not support them. This is not a replacement for a correctly configured theme and does not work with all themes.', 'indieweb' ); ?></p>
			<p><?php _e( 'Formatting your site so other sites can consume the information allows for the communications IndieWeb sites support. Most tools will attempt to use means other than Microformats2 if they are not available, but will lack detail.', 'indieweb' ); ?></p>

			<p>
				<?php
				_e(
					'Currently, <a href="https://wordpress.org/themes/sempress/"
					target="_blank">SemPress</a> is the only theme in the WordPress repository that is fully
					microformats2 compliant. <a href="http://wordpress.org/themes/independent-publisher/"
					target="_blank">Independent Publisher</a> has been updated to include basic microformats2 and
					webmention display support. In practice, most themes will work relatively well out of the box, though there can be some minor display issues. If you are interested in receiving help in converting your theme to support Microformats 2, the community will be <a href="https://indieweb.org/discuss">happy to assist</a>.', 'indieweb'
				);
				?>
			</p>
		</div>

		<div class="indieweb-flex what-is">
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
			<p><?php _e( 'For assistance and to chat with community members, please visit <a href="https://indieweb.org/discuss" target="_blank">this page</a> for more details.', 'indieweb' ); ?></p>
		</div>
	</div>
</div>
