=== GW Database Backup ===
Contributors: Rajib Hossain, Team Getweb, Inc
Tags: backup, database-backup, db-backup
Requires at least: 4.4
Tested up to: 5.4
Requires PHP: 5.6
Stable tag: 1.0.0
License: GPLv2 or later

GW Database Backup speeds up wp site by combining & minify JS, CSS, lazy-load images, and leverage browser caching & gzip compression for apache server.

== Description ==

Using GW Database Backup not so difficult. It can add gzip compression code to your .htaccess file. You need to enable gzip compression & browser caching under compressing assets tab in the setting page. GW Database Backup can minify & combine CSS & JS files under the head those have been enqueued by your theme & other plugins. Same thing will be apply for the image lazy-loading.

== Installation ==

Just install from your WordPress "Plugins > Add New" screen and all will be done automatically. Manual installation:

1. Upload the zip file and unzip it in the `/wp-content/plugins/` directory
1. Activate the plugin through the 'Plugins' menu in WordPress
1. Go to `Settings > GW Database Backup` and enable the options that you need.

== Frequently Asked Questions ==

= How the plugin does work? =

It concatenates all scripts and styles under the header that have been enqueued by your theme & plugins, minifies and compresses them. This plugin is very lightweight.

= Why is jquery.js not optimized =

Starting from AO 2.1 WordPress core's jquery.js is not optimized for the simple reason a lot of popular plugins inject inline JS that is not aggregated either (due to possible cache size issues with unique code in inline JS) which relies on jquery being available, so excluding jquery.js ensures that most sites will work out of the box. If you want optimize jquery as well, you can remove it from the JS optimization exclusion-list (you might have to enable "also aggregate inline JS" as well or switch to "force JS in head").

= My site looks broken after enabling combine & minify CSS ! =

Sometimes some external google-fonts css files can create the problem, you just need to disable for that option.

= Can I still use other plugins like Autoptimize, WP-Optimize or WP-Rocket? =

At the moment first of 2020 , the answer is no.

= My Google Pagespeed Scored barely improved after using GW Database Backup=

GW Database Backup is not a simple fix for google-page-speed; it optimizes the real speed while browsing your site.

= How does CDN work? =
GW Database Backup doesn't compile or optimize any CDN (those are external from your own site) CSS & JS files.


= How can I force the aggregated files to be static CSS or JS instead of PHP? =

If your webserver is properly configured to handle compression (gzip or deflate) and cache expiry (expires and cache-control with sufficient cacheability), you don't need GW Database Backup enabling gzip or browser caching, just keep them disabled.

= I am getting some problems after activating this plugin? =
Just de-activate it, all of it's options will be completely removed and will work as previously worked.