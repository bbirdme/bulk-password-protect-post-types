=== Bulk Password Protect Post Types ===
Contributors: mckilem
Tags: password, protect, post types, custom post types, private
Requires at least: 6.0
Tested up to: 7.1
Requires PHP: 7.4
Stable tag: 1.1.0
License: GPLv3 or later
License URI: http://www.gnu.org/licenses/gpl-3.0.html

Password-protect all posts of the post types you choose, with one password.

== Description ==

**Having a problem or a question?** Please write to the [support forum](https://wordpress.org/support/plugin/bulk-password-protect-post-types/) first. I read it and will try to help you before you leave a review.

Bulk Password Protect Post Types lets you choose one or more post types and set one password for every post within them. A visitor sees WordPress's own password form instead of the text, on the page, in search results, in feeds and in the REST API.

= Features =

* Lists every post type of the site, custom post types included.
* One password for all posts of the chosen types, set in one place.
* Uses WordPress's own password protection, so the form and the cookie behave as they do for a single protected post.
* Posts you add later to a chosen type are protected from the first moment.

= Good to know =

* The password is saved in the site's database as plain text, as WordPress saves the password of a single post. Do not use one you use elsewhere.
* The password of this setting replaces the password a post had of its own, for the post types you chose. Without a password in the setting, nothing is changed.
* Nothing is written to the posts themselves. Remove a post type from the setting, or deactivate the plugin, and its posts are open again.

== Installation ==

1. Upload the plugin files to the `/wp-content/plugins/bulk-password-protect-post-types` directory, or install the plugin through the WordPress plugins screen directly.
2. Activate the plugin through the 'Plugins' screen in WordPress.
3. Click Settings, or go to Settings → Bulk Password Protect.
4. Choose one or more post types and a password, and save.

== Frequently Asked Questions ==

= Does it change my posts? =

No. The password is applied when a post is loaded, and it is never saved to the post. Deactivate the plugin and the posts are open again.

= Can I use a different password for each post type? =

Not yet. All chosen post types share one password.

= Why does the post type list show names like "wp_block"? =

It lists every post type WordPress knows, with its internal name. Choose the ones your visitors see, usually "post", "page" and your own custom post types.

== Screenshots ==

1. The settings page.

== Changelog ==

= 1.1.0 =
* Fixed: the settings page ended in a fatal error on PHP 8.
* Fixed: a chosen post type with an empty password removed the passwords posts had of their own. Now nothing changes until a password is set.
* Security: the chosen post types and the password are sanitized when saved, and everything on the settings page is escaped.
* The text domain is now `bulk-password-protect-post-types`, the plugin's slug, so wordpress.org translations load.
* The Settings link on the Plugins screen follows the site's admin address.
* Requires WordPress 6.0 and PHP 7.4. Tested with WordPress 7.1.
* Added automated tests.

= 1.0 =
* First stable version

== Upgrade Notice ==

= 1.1.0 =
Fixes a fatal error on the settings page with PHP 8. Your settings are kept.
