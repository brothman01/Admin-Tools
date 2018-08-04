# WP Monitor #
**Contributors:** [brothman01](https://profiles.wordpress.org/brothman01)  
**Donate link:** https://www.wp-monitor.net  
**Tags:** [productivity](https://wordpress.org/themes/tags/productivity/), [monitor](https://wordpress.org/themes/tags/monitor/), [updates](https://wordpress.org/themes/tags/updates/), [php](https://wordpress.org/themes/tags/php/), [variables](https://wordpress.org/themes/tags/variables/), [admin](https://wordpress.org/themes/tags/admin/), [WP Monitor](https://wordpress.org/themes/tags/WP Monitor/)  
**Requires at least:** 4.6  
**Tested up to:** 4.9  
**Stable tag:** 1.1.3  
**License:** GPLv2 or later  
**License URI:** https://www.gnu.org/licenses/gpl-2.0.html  

A simple WordPress plugin that puts important information on the dashboard.

[![Build Status](https://travis-ci.org/brothman01/wp-monitor.svg?branch=master)](https://travis-ci.org/brothman01/wp-monitor) [![devDependencies Status](https://david-dm.org/brothman01/wp-monitor/master/dev-status.svg)](https://david-dm.org/brothman01/wp-monitor/master?type=dev) [![License](https://img.shields.io/badge/license-GPL--2.0-brightgreen.svg)](https://github.com/brothman01/wp-monitor/blob/master/license.txt) [![PHP >= 5.2](https://img.shields.io/badge/php-%3E=%205.2-8892bf.svg)](https://secure.php.net/supported-versions.php) [![WordPress >= 4.4](https://img.shields.io/badge/wordpress-%3E=%204.4-blue.svg)](https://wordpress.org/download/release-archive/)  

## Description ##

A simple WordPress plugin that puts update, important site and server information in one convenient, easy-to-find place on the dashboard.  Monitor one or multiple sites on the go, this plugin makes your job as a site administrator easier by eliminating the need to look through the site and server for the information that WP Monitor collects and puts all together right on the dashboard.  Print a report that tells you exactly how your site is doing in seconds on one or multiple sites making administration easy.

The display is on the dashboard and the settings page is a sub-item in the 'Tools' menu.  This plugin is great for productivity and easy site monitoring because of how much it speeds up the workflow of any site admin.

Visit https://www.wp-monitor.net for more information on the plugin, add-ons and a knowledge base!

WP Monitor Information:

* Plugin Updates
* Theme Updates
* WordPress Core Updates
* PHP Version inspection and instructions
* SSL Data
* Many Important Website Variables
* User Login Data
* How many Total Updates are needed
* Final website grade based on the site info gathered in the previous steps


Special Thanks to Evan Herman

## Installation ##

1. Download the zip file containing the plugin
2. unzip the zip file into your plugin directory
 OR
 install the plugin via the plugin repository.

 == Screenshots ==

 1. Screenshot of the WP Monitor dashboard display.

## Frequently Asked Questions ##

### Where is the display? ###

On the dashboard

### Where is the settings page? ###

The settings page is a sub-item in the 'Tools' menu.

### What Does the Plugin gauge show? ###

The Plugin gauge fills up to show how many of the total plugins installed on the site have updates out of the total number of plugins installed on the site.  With some subtraction, the number of up-to-date plugins installed on the site is also available:
total plugins - plugins that need updates = up-to-date plugins.

### What Does the Theme gauge show? ###

The Theme gauge fills up to show how many of the total themes installed on the site have updates out of the total number of themes installed on the site.  With some subtraction, the number of up-to-date themes installed on the site is also available:
total themes - themes that need updates = up-to-date themes.

### What does the PHP gauge show? ###

The PHP section shows the things:
1. The current version: The version of PHP running on the server hosting the website.
2. The indicator (red/green circle) - This shows at a glance whether the version of PHP run by the server hosting the website is supported.
3. The 'Supported Until' field: This field shows when the version of PHP currently running on the server hosting the website is supported until as stated by the official PHP website.

### Why Upgrade Running Version of PHP? ###

There are several reasons to upgrade your version of PHP:

1. The PHP Group' (the official managers of PHP) support each version of PHP they release for a certain amount of time, so older versions of PHP are less likely to be supported than newer ones.  In a supported version, if a security flaw is found then it is fixed by The PHP Group, whereas if a security flaw is found in an older unsupported version of PHP, nothing happens and hackers have the freedom to exploit that flaw.

2. PHP 7 and newer versions of PHP have higher performance than the older versions.  Whether you are talking about the ability to execute more requests, the less memory used or the platform independent instructions, PHP 7+ just performs better.

3. Higher load capacity.  PHP 7+ allows hosts to serve more clients with the same hardware.  Changes to phpng as well as the new JIT compiler allow PHP to be on par with Facebook HHVM for load capacity.

### Why use SSL? ###

This is important because the information you send on the Internet is passed from computer to computer to get to the destination server. Any computer in between you and the server can see your credit card numbers, usernames and passwords, and other sensitive information if it is not encrypted with an SSL certificate.  Using SSL will encrypt the data that is sent so that prying eyes between your user and your server cannot get the information.

### What do the variables in the variables table mean? ###

There is a detailed description of each value at http://wp-monitor.net/2017/03/30/what-does-that-value-mean/.

## Changelog ##

### 1.0.0 ###
A Plugin is born

### 1.0.1 ###
Made monitor easier to read with breakdowns and indicators

### 1.0.2 ###
Changed order of variables in the variable table

### 1.0.3 ###
- Added support for email addon and database addon
- changed final grade indicator into a gauge

### 1.0.4 ###
added new variables to the cariable table
