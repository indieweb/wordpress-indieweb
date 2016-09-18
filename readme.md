# IndieWeb #
**Contributors:** [pfefferle](https://profiles.wordpress.org/pfefferle), [dshanske](https://profiles.wordpress.org/dshanske)  
**Donate link:** https://indieweb.org/how-to-sponsor  
**Tags:** indieweb, webmention, webaction, POSSE, indieauth  
**Requires at least:** 4.4  
**Tested up to:** 4.6  
**Stable tag:** 3.0.6  

IndieWeb for WordPress!

## Description ##

The IndieWeb Plugin for Wordpress helps you establish your IndieWeb identity by extending the user profile to provide [rel-me](https://indieweb.org/rel-me) and [h-card](https://indieweb.org/h-card) fields.
It also includes a bundled installer for a core set of IndieWeb-related plugins. It's meant to be a one-stop shop to help WordPress users quickly
and easily join the growing [IndieWeb](http://www.indiewebcamp.com) movement (see below).

Some of these plugins allow you to:

* send and receive comments, likes, reposts, and other kinds of post responses using your own site
* allow comments on others' sites to show up as comments on your posts
* help make IndieWeb comments and mentions look better on your site
* allow support for threaded comments and webmentions
* more easily syndicate your content to other sites to take advantage of network effects and other communities while still owning all of your original content
* link to syndicated versions of a post so that comments on your content in silos like Facebook, Twitter, Instagram, Google+ can come back to your original post as comments there
* allow you to add bookmarklets to easily respond/comment on other sites with one click
* set up a MicroPub Server to use other posting interfaces. (You could potentially use services like Instagram, Foursquare, and others to post to your WordPress site.)
* set up a personal URL shortener
* log into your WordPress site with services like Twitter, GitHub, SMS, or even email using [IndieAuth](https://indieweb.org/indieauth).

### The IndieWeb ###

**The [IndieWeb](https://indieweb.org/) is a people-focused alternative to the ‘corporate web’ that allows you to be the hub of your own web presence.** It's been written about in [Wired](http://www.wired.com/2013/08/indie-web/), [The Atlantic](http://www.theatlantic.com/technology/archive/2014/08/the-new-editors-of-the-internet/378983/), [Slate](http://www.slate.com/blogs/future_tense/2014/04/25/indiewebcamps_create_tools_for_a_new_internet.html), and [Gigaom](https://gigaom.com/2014/09/03/dont-like-facebook-owning-and-controlling-your-content-use-tools-that-support-the-open-web/) amongst others.

### The IndieWeb, like WordPress, feels that your content is yours ###

When you post something on the web, it should belong to you, not a corporation. Too many companies have gone out of business and lost all of their users’ data. By joining the IndieWeb, your content stays yours and in your control.

### The IndieWeb is here to help you be better connected ###

Your articles and status messages can be syndicated to all services, not just one, allowing you to engage with everyone in your social network/social graph. Even replies and likes on other services can come back to your site so they’re all in one place.

Interested in connecting your WordPress site to the [IndieWeb](https://indiewebcamp.com/)? Let us help you get started.

## Frequently Asked Questions ##

### How do I get Started? ###

IndieWeb for WordPress includes a plugin installer program. A Getting Started Guide can be found under IndieWeb.

### Where can I find help? Can I contribute? ###

A group of web developers (including those knowledgeable about WordPress, among many other web technologies) can be found discussing and working on IndieWeb related technologies in the wiki at [IndieWebCamp.com](http://www.indiewebcamp.com) or in the #IndieWeb [IRC on Freenode](https://indiewebcamp.com/IRC). WordPress specific portions of the IndieWeb camp can be found at [WordPress](https://indiewebcamp.com/wordpress), [Getting Started on WordPress](https://indiewebcamp.com/Getting_Started_on_WordPress), [Examples](https://indiewebcamp.com/WordPress/Examples), and other [plugins](https://indiewebcamp.com/WordPress/Plugins).

If you need additional assistance, feel free to reach out to any of the [WordPress Outreach Club](https://indiewebcamp.com/WordPress_Outreach_Club) members via the website, our individual websites, or our social media presences -- we're happy to help!

### Why IndieWeb? ###

Find more information and details for the motivations for joining the IndieWeb at https://indieweb.org/Why

### What about plugin XYZ? ###

If you think we missed a plugin reference, please file an issue on [Github](https://github.com/indieweb/wordpress-indieweb/issues).

### What plugins are included in this package? Can I install them separately? ###

* Webmention (Required) - allows you to send and receive by adding webmention support to WordPress. Mentions show up as comments on your site.
* Semantic Linkbacks (Required) - makes IndieWeb comments and mentions look better on your site.
* Webmention for (Threaded) Comments - Adds support for threaded comments for webmentions.
* Webactions - Adds webaction markups to WordPress elements.
* Post Kinds - Allows you to reply/like/RSVP etc to another site from your own, by adding support for kinds of posts to WordPress.
* Syndication Links - Adds fields to a post to allow manual entry of syndication links as well as automatically from a supported syndication plugin. Fully supports Social, partial support for NextScripts: Social Networks Auto-Poster (aka SNAP).
* MicroPub - A MicroPub Server
* IndieWeb Press-This - Adds IndieWeb markup to the WordPress Press-This bookmarkets to allow you to respond on your site with one-click.
* Hum URL Shortener - A personal URL shortener.
* Indieauth - The plugin lets you login to the WordPress backend via IndieAuth. It uses the URL from the profile page to identify the blog user.

One could certainly download, install, and activate some or all of these plugins separately, but it is much quicker and easier to utilize the interface provided by this IndieWeb plugin to install and activate them. Note that some of these plugins may only be available on GitHub and are not yet on WordPress.org.

## Changelog ##

Project maintained on github at [indieweb/wordpress-indieweb](https://github.com/indieweb/wordpress-indieweb).

### 3.0.6 ###

* Fix bug in single author display
* Add constant INDIEWEB_ADD_HCARD_SUPPORT to disable hcard additions
* Add constant INDIEWEB_ADD_RELME_SUPPORT to disable rel-me widget

### 3.0.5 ###

* Textual improvements

### 3.0.4 ###

* Hidden relme links will be active whenever the relme widget is not active
* Changes in documentation

### 3.0.3 ###
* Bug fix re rel=me on multi-author sites
* Removal of post author option due changes in global config
* Remove use of deprecated functions
* Disable hidden rel=me option due error

### 3.0.2 ###

* WordPress coding style
* Fixed indents

### 3.0.1 ###

* General cleanups
* Update TGM Plugin Activation to 2.6.1
* SVG Icon Support

### 3.0.0 ###

* The plugin now supports establishing your identity on the web with your site.
* Extended User Profile
* includes additional properties to be used to generate h-card
* includes rel-me for the silos supported by IndieAuth plus any arbitrary additional ones
* supports 'hidden' rel-me links or a rel-me widget with SVG icons
* Admin is redesigned with a top-level menu
* Update TGM Plugins Activate to Version 2.6.1


### 2.2.0 ###

* Update to Version 2.5.0 of the TGM Plugins Activation which adds update capability
* Now that Syndication Links has the functionality of WordPress Syndication remove as duplicative

### 2.1.1 ###

* "Post Kinds" is now in the WordPress repository
* "MicroPub" server plugin added
* "Syndication Links" is now in the WordPress repository

### 2.1.0 ###

* added "syndication-links", "indieauth" and "wordpress-syndication plugins
* expanded IndieWeb Page with description of plugins
* second contributor
* fixed some small bugs

### 2.0.0 ###

* plugins are not bundled any more

### 1.1.0 ###

* updated webmention and semantic-linkbacks plugins
* added hum (url shortener)

### 1.0.0 ###

initial release

## Installation ##

1. Upload the `indieweb`-folder to the `/wp-content/plugins/` directory
2. Activate the plugin through the *Plugins* menu in WordPress
3. ...and that's it :)
4. Seriously though, this plugin includes a number of other configurable files as well as services, which need to be set up/configured individually. A good resource for details on setting them up quickly can be found at <a href="https://indieweb.org/Getting_Started_on_WordPress">IndieWeb: Getting Started on WordPress</a>. We also recommend viewing the instruction pages of the individual sub-plugins themselves.
