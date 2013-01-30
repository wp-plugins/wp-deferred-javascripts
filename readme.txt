=== WP Deferred Javascripts ===
Contributors: willybahuaud, Confridin
Tags: javascript, optimization, performance, deferring, labjs, asynchronous, speed
Requires at least: 3.0
Tested up to: 3.5.1
Stable tag: 3.5.1
License: GPLv2 or later

Defer the loading of all javascripts added with wp_enqueue_scripts, using LABJS (an asynchronous javascript library).

== Description ==

This plugin defer the loading of all javascripts added by the way of wp_enqueue_scripts, using LABJS. The result is a significant optimization of loading time.

It is compatible with all WordPress javascript functions (localize_script, js in header, in footer ...) and works with all well coded plugins.

If a plugin or a theme is not poperly enqueuing scripts, your site may not work. Check this page : [Function Reference/wp_enqueue_script on WordPress Codex](http://codex.wordpress.org/Function_Reference/wp_enqueue_script)

LABjs (Loading And Blocking JavaScript) is an open-source (MIT license) project supported by [Getify Solutions](http://getify.com/).

== Installation ==

1. Upload the WP Deferred Javascripts plugin to your blog and Activate it.

2. Enjoy ^^

== Changelog ==

= 1.3 =
* Fixed a major bug : files with dependencies are now waiting the loading of parent files before loading themselves

= 1.2 =
* Data called after wp_head, but linked to a script queued into header are now considered by the plugin

= 1.1 =
* Correction of some minor bugs
* Improve code readability

= 1.0 =
* Initial release