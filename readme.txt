=== WP Deferred Javascripts ===
Contributors: willybahuaud, Confridin
Tags: javascript, optimization, performance, deferring, labjs, asynchronous, speed
Requires at least: 3.0
Tested up to: 3.5.1
Stable tag: 1.5.6
License: GPLv2 or later

Defer the loading of all javascripts added with wp_enqueue_scripts, using LABJS (an asynchronous javascript library).

== Description ==

This plugin defer the loading of all javascripts added by the way of wp_enqueue_scripts, using LABJS. The result is a significant optimization of loading time.

It is compatible with all WordPress javascript functions (localize_script, js in header, in footer ...) and works with all well coded plugins.

If a plugin or a theme is not poperly enqueuing scripts, your site may not work. Check this page : [Function Reference/wp_enqueue_script on WordPress Codex](http://codex.wordpress.org/Function_Reference/wp_enqueue_script)

LABjs (Loading And Blocking JavaScript) is an open-source (MIT license) project supported by [Getify Solutions](http://getify.com/).

We performed a range of tests to determine the potential benefit of loading time. On [wabeo](http://wabeo.fr) we executed [webwait](http://webwait.com/) (150 calls by test). Result is this plugin could **improve your loading time by 25%** !! 
More information in the [Screenshots section](http://wordpress.org/extend/plugins/wp-deferred-javascripts/screenshots/).

You can find [more information about WP defered Javascripts](http://www.seomix.fr/wp-deferred-javascript/) and [technical information about asynchronous scripts](http://wabeo.fr/blog/wordpress-javascripts-asynchrones/) on authors blogs.

== Installation ==

= Default =

1. Upload the WP Deferred Javascripts plugin to your blog and Activate it.
2. Enjoy ^^

= Exclude Scripts =

Release 1.5.6 provide a new filter hook named *do_not_defer* which take an array of script's handle as argument.
Theses script will not be deferred.

= Defer inline scripts execution =

New release 1.5.6 provide another filter hook named *wdjs_before_end_lab* which take a string of javascript code as argument.
Code will be call add the end of LabJS enqueue, calling one function or more.

For exemple : 
`
<?php 
function load_my_js_code_after_loading() {
    return '.wait(function(){alert 'loaded';anInlineFunction();})';
}
add_filter( 'wdjs_before_end_lab', 'load_my_js_code_after_loading' );
`
Adding this will execute *anInlineFunction()* and print *'Loaded'* after all my scripts have been loaded :-)

Using other plugins hook, you can wrap JS inline code into a function, and call this function with *load_my_js_code_after_loading*...

== Screenshots ==

1. Average load time of **1.91** seconds **without WP Deferred Javascripts activated** and scripts loaded in the header
2. Average load time of **1.99** seconds **without WP Deferred Javascripts activated** and scripts queued in the footer
3. Average load time of **1.56** seconds **with WP Deferred Javascripts activated** and scripts queued in the header
4. Average load time of **1.54** seconds **with WP Deferred Javascripts activated** and scripts queued in the footer

== Changelog ==

= 1.5.6 =
* Add a release argument to scripts's URL, to solve problems with hard caching methods
* Add a Filter Hook namned "do_not_defer", to exlude scripts from defer process
* Add a Filter Hook namned "wdjs_before_end_lab", to add callback functions to labJS, improving compatibility with some plugins...

= 1.5.5 =
* Solve a problem when uri script contain "&amp;"
* Solve a bug while waiting dependencies

= 1.5.4 =
* Prevent bug when scripts dependencies are not enqueud

= 1.5.3 =
* Prevent a minor bug for footer enqueue script

= 1.5.2 =
* Fixed a minor bug : bad priority while emptying $wp_scripts

= 1.5.1 =
* Fixed a minor bug : plugin active was on login and register pages

= 1.5 =
* Fixed a major bug : plugin active only in front end

= 1.4 =
* Fixed a minor bug : some javascripts enqueued with very high priority were ignored - filter scripts are now hooked on wp_print_scripts

= 1.3 =
* Fixed a major bug : files with dependencies are now waiting the loading of parent files before loading themselves

= 1.2 =
* Data called after wp_head, but linked to a script queued into header are now considered by the plugin

= 1.1 =
* Correction of some minor bugs
* Improve code readability

= 1.0 =
* Initial release