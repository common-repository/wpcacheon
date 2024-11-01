=== WPCacheOn - WordPress Caching plugin ===
Contributors: jeffreycooper
Donate link: https://www.patreon.com/bePatron?u=8652264
Tags: cache, caching, gzip, gtmetrix, minify
Requires at least: 4.6
Tested up to: 6.6.2
Requires PHP: 5.4
Stable tag: 2.1.0
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

A simple and lightweight caching plugin for WordPress that boosts website loading time and enhances performance scores on GTMetrix and Pingdom.

== Description ==
Simple and lightweight cache plugin for WordPress that will also enhance your website loading time and increase the scores at GTMetrix, Pingdom and other performance measuring tools.

= Features =
* Extremely efficient and fast disk cache engine, even greater results with SSD based servers
* Very convenient displaying of the cache size in the admin dashboard
* Automated and manual clearing of the cache
* Native support for Easy Digital Downloads
* HTML, CSS and JavaScript minification
* Deactivate caching for specific page
* Native support for WooCommerce
* Purge the cache for specific page
* WordPress Multisite support
* Custom Post Type support
* New minification options
* CSS and JavaScript inline
* [PreCache](https://wpcacheon.io/cache-and-precache-in-wordpress/) mechanism
* PHP 8.3 compatible
* HTTP/2 Focused
* Expiry Directive
* Secure cache
[](http://coderisk.com/wp/plugin/wpcacheon/RIPS-gnhtYa129X)

= How does the caching work? =
This plugin requires explicitly minimal setup time and no configuration at all. No coding, no configuration - install and activate, that's all! Your website is now optimized and loading faster!

The WPCacheOn plugin has the ability to create two cached files. First is in plain HTML and the second file is gzipped (gzip level 9). Those static files are used to deliver content faster to your website visitors without any database lookups or gzipping because the files are already precompressed.

Stay tuned for new features!

= Website =
* [https://wpcacheon.io/](https://wpcacheon.io/) - official website of the plugin

= Follow us on the social medias =

* [Facebook](https://www.facebook.com/wpcacheon)
* [Twitter](https://twitter.com/wpcacheon)

= Minimum System Requirements =
* PHP >=5.4
* WordPress >=4.1

= Recommended System Requirements =
* PHP = 8.3
* WordPress = 6.6.2

= Maintainer =
* [Jeffrey Cooper](https://profiles.wordpress.org/jeffreycooper/) - 1987coopjeff@gmail.com


= Credits =
* Inspired by the motivation for faster websites!

== Screenshots ==

1. Convenient lookup of the current cache size in your dashboard
2. The new WPCacheOn settings page
3. WordPress cache plugins comparison

== Frequently Asked Questions ==

= What is WPCacheOn? =

WPCacheOn speed-up page loading and improve website score in services like Pingdom and GTmetrix. WPCacheOn will remove any query strings from static resources like CSS & JavaScript files, enable GZIP compression (compress text, html, javascript, css, xml and others), add Vary: Accept-Encoding header and set expires caching (leverage browser caching). With our PreCache™ technology your website will be cached after the plugin is installed. This means that once WPCacheOn is installed your website already become faster with lower loading time and better results at GTMetrix and Pingdom.

= Are there specific requirements for WPCacheOn to work? =

The GZIP compression should be enabled in your web-server, no matter if it is Apache, LiteSpeed, NginX or another. If GZIP is not enabled you can ask your web hosting provider to enable it.

The .htaccess file in your WordPress instance root folder must have write permissions such as 644.

= What to do if I get 500 Internal Server Error after plugin activation? =

If you get "500 – Internal Server Error" after the plugin is activated, follow the steps below to easily return your website to working state:

(1) Login to your hosting account via FTP or File Manager. If you are not sure how to perform that, you can ask your hosting provider.
(2) Go to the WordPress installation folder also known as the root directory of your website.
(3) Rename the .htaccess file to .htaccess.deactivated. Then rename the .htaccess.wco file to .htaccess.

WPCacheOn is using many .htaccess rules related to different web server modules for optimizing the website performance. If some of the web server modules are not installed this will result in Internal Server Error. This is the most common and easy fix to this issue.

If this don’t resolve the issue, you can contact us or your web hosting provider for more in depth investigation.

== Changelog ==
= Release 2.1.0, 26 September 2024 =
* Tested and confirmed compatibility with WordPress 6.6.2
* Tested and confirmed compatibility with PHP 8.3
* Improved autoload
* Improved PreCache functionality
* Improved plugin codebase
* Showing current PHP version instead of date and time
* Fixed minor memory leak upon plugin deactivation
* Added option to trigger PreCache manually
* Added count for cached pages and posts
* Added option to control compression
* Other minor various improvements over the User Experience (UX)
* Performed complete security audit of the plugin
* Improved plugin debug logs in separate file

= Release 2.0.2, 09 December 2020 =
* Tested and confirmed compatibility with WordPress 5.6

= Release 2.0.1, 15 May 2020 =
* User Experience (UX) improvements
* Fixed an issue with server zlib.output_compression (strange characters issue)
* Added upgrade notice, this will provide information what is new in the latest version of WPCacheOn
* Added upgrade link from the WPCacheOn settings page to the WordPress Updates page, when new version is available

= Release 2.0.0, 20 April 2020 =
* Tested and confirmed compatibility with WordPress 5.4
* Tested and confirmed compatibility with PHP 7.4
* Complete code rewrite and optimization
* Additional stability adjustments for caching rules
* Improvements over the [PreCache](https://wpcacheon.io/cache-and-precache-in-wordpress/) mechanism
* Improved security of the cached files
* Completely [rembranded WPCacheOn caching plugin](https://wpcacheon.io/wpcacheon-complete-rebranding/)
* New option - Cache Minification. [Now you can control what to be minified or deactivate the minification completely](https://wpcacheon.io/documentation/).
* Resolved issue - [Problems when both Autoptimize plugin and WPCacheOn plugin are activated](https://wordpress.org/support/topic/problems-when-both-autoptimize-plugin-and-wpcacheon-plugin-are-activated/)
* Resolved issue - [Content of .htaccess removed](https://wordpress.org/support/topic/content-of-htaccess-removed/)

= Release 1.2.7, 30 Nov 2019 =
* Improvements over the [PreCache](https://wpcacheon.io/cache-and-precache-in-wordpress/) system
* .htaccess optimizations
* Small code improvements and optimizations
* Check our [Birthday Week pages for additional WordPress optimizations](https://wpcacheon.io/wordpress-week-of-speed-at-wpcacheon/).

= Release 1.2.6, 14 Nov 2019 =
* Implemented in-house build [PreCache](https://wpcacheon.io/cache-and-precache-in-wordpress/) technique
* Fix issue: [https://wordpress.org/support/topic/content-of-htaccess-removed/](https://wordpress.org/support/topic/content-of-htaccess-removed/)
* Tested and confirmed compatibility with WordPress 5.3
* Minor improvements over the the admin dashboard and overall improvements of the UX

= Release 1.2.5, 10 Oct 2019 =
* Brand new admin dashboard icon
* Applied new technique where we ensure that all of the .htaccess optimization rules will be always applied
* More optimized .htaccess rules and preparation for WebP support
* File structure improvement
* Added new notification upon new version of WPCacheOn plugin
* Added support for localization - now you can translate WPCacheOn plugin to your native language
* Fixed an issue with the core React.JS
* Fixed an issue with adding gzip for JPG image files

= Release 1.2.4, 05 May 2019 =
* Tested and confirmed compatibility with WordPress 5.2

= Release 1.2.3, 13 April 2019 =
* Improved plugin upgrade process
* Fixed an issue with the jpg browser leverage cache
* Overall code optimizations for better internal work of the plugin

= Release 1.2.0, 24 March 2019 =
* Fix issue: [https://wordpress.org/support/topic/problem-with-the-autoptimize-plugin/](https://wordpress.org/support/topic/problem-with-the-autoptimize-plugin/)
* Code glossary and optimizations
* Update of the .htaccess rules for even better performance at [GTMetrix](https://wpcacheon.io/how-to-measure-website-loading-speed/), Pingdom and Google PageSpeed Insights
* Investigated and fixed the [issue with the strange characters](https://wpcacheon.io/strange-symbols-on-your-website-explained-and-resolved-with-wpcacheon/)
* Tested compatibility with WordPress 5.1.1

= Release 1.1.6, 19 February 2019 =
* Initial release at WordPress plugin directory
* Tested compatibility with WordPress 5.1

= Release 1.1.5, 13 December 2018 =
* Tested and confirmed compatibility with WordPress 5.0
* Tested and confirmed compatibility with WordPress 4.9.9
* Fast flush of cache from plugins menu
* Small bug fixes

= Release 1.1.0, 7 December 2018 =
* Tested and confirmed compatibility with PHP 7.3
* Improvements and stability

= Release 1.0.5, 22 October 2018 =
* Updated the WPCacheOn presence in the admin dashboard
* Improved the WPCacheOn code structure

= Release 1.0.0, 16 August 2018 =
* WPCacheOn open beta tests release

= Release 0.1.0, 25 September 2017 =
* WPCacheOn first beta version release

== Upgrade Notice ==
= 2.0.1 =
User Experience (UX) improvements
Fixed an issue with server zlib.output_compression (strange characters issue)
Added upgrade notice, this will provide information what is new in the latest version of WPCacheOn
Added upgrade link from the WPCacheOn settings page to the WordPress Updates page, when new version is available