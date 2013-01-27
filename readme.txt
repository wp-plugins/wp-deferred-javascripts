=== WP Deferred Javascripts ===
Contributors: willybahuaud, confridin
Tags: javascript, optimization, performance, deferring, labjs, asynchronous, speed
Requires at least: 3.0
Tested up to: 3.5.1
Stable tag: 3.5.1
License: GPLv2 or later

Defer the loading of all javascripts added with wp_enqueue_scripts, using LABJS (an asynchronous javascript library).

== Description ==

This plugin defer the loading of all javascripts added by the way of wp_enqueue_scripts, using LABJS. The result is a significant optimization of loading time.

It is compatible with all WordPress javascript functions (localize_script, js in header, in footer ...) and works with all well coded plugins.

If a plugin or a theme is not poperly enqueuing scripts, your site may not work. Check this page : http://codex.wordpress.org/Function_Reference/wp_enqueue_script

== Installation ==

1. Upload the WP Deferred Javascripts plugin to your blog and Activate it.

2. Enjoy ^^

== Changelog ==

= 1.0 =
* First version, with some hooks and functions