=== Algolia for WordPress ===
Contributors: algolia, leocolomb, korben
Donate link: https://www.patreon.com/LeoColomb
Tags: search, algolia, relevant search, better search, custom search
Requires at least: 5.2
Tested up to: 5.8
Requires PHP: 7.2
Stable tag: trunk
License: MIT
License URI: https://github.com/korben-info/wp-algolia/blob/master/LICENSE

A (unofficial) WordPress plugin to use Algolia as a search engine.

== Description ==

Algolia is the smartest way to improve search on your site.
The plugin provides relevant search results in milliseconds, ensuring that your users can find your best posts at the speed of thought.
It also comes with native typo-tolerance and is language-agnostic, so that every WordPress user, no matter where they are, can benefit from it.

== Installation ==

= Requirements =

* PHP 7.2+
* WordPress 5.2+

= Manual =

1. Upload the plugin by cloning or copying this repository to the `/wp-content/plugins` directory, or install the plugin through the WordPress plugins screen directly.
2. Activate the plugin through the 'Plugins' screen in WordPress
3. Use the Settings->Algolia screen to configure the plugin

= Automatic =

This plugin can be installed with Composer on a Composer-managed WordPress stack.
```
composer require korben-info/wp-algolia
```

== Frequently Asked Questions ==

= Will Algolia for WordPress work with my theme? =

Yes, Algolia for WordPress will work with any theme.
That said, the Instant Search and Autocomplete features require to be supported by the theme.

== Screenshots ==

1. Algolia for WordPress settings.

== Changelog ==

[Checkout the complete changelog here](https://github.com/korben-info/wp-algolia/releases).

== Upgrade Notice ==

If you upgrade from Search by Algolia, you can just copy and past your settings.
Please note this plugin does not contain public interface change, your theme must include Algolia usage separately.

== Contributors ==

[Léo Colombaro](https://colombaro.fr)
