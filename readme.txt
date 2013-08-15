=== Plugin Name ===
Contributors: redeyedmonster
Donate link: 
Tags: database, table, update, insert, add, delete, edit, MySQL, phpMyAdmin, data, editor, widget, dashboard
Requires at least: 3.0.0
Tested up to: 3.6.0
Stable tag: trunk
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

A WordPress plugin/Dashboard Widget that allows you to connect to any database and edit the contents.

== Description ==

This plugin is a dashboard widget to allow you to connect to any MySQL database of your choice (as long as your hosting allows) and search, edit, add and delete records in an easy to use interface.  Ideal if you have built a site referencing another database and you want to allow other editors/administators of the site to alter, update, delete, add and correct entries.  

== Installation ==

* To install, download the .zip file, extract the contents and place the edit-any-table folder in your plugin directory (typically \\your-site\wp-content\plugins\)

* Once installed go to the Dashboard->Plugins page of your WordPress site and activate the plugin.

* Now go to the Dashboard->Settings->Edit Any Table page and enter the required details. 
* First you will need to enter the host (often localhost), the name of the database you wish to connect to and a valid user name and password. 
* Under Admin Settings you can choose to allow Administrators only to have access to the widget or Editors as well. Note: If neither of these boxes are ticked the widget will not display in your WordPress Dashboard. 
* Display Settings allows you to select the maximum number of columns to display for returned searches. Edit Any Table displays best in a single column dashboard configuration and I find five columns is a comfortable fit but adjust to suit.
* Also here you can set a friendly name for the database you are connecting to (less confusing for your users)

For full deatails and user guide visit [RedEyedMonster Edit-Any-Table](http://redeyedmonster.co.uk/edit-any-table/)

== Frequently Asked Questions ==

= Why can't I see the widget in the dashboard? =

Probably because you have not selected 'Editor' or 'Administrator' in the settings. Go to Settings->Edit Any Table and correct this.

= There are no tables in the drop down list, why? =

You have entered invalid database information. Check the settings.

== Screenshots ==

1. Administration screen
2. Search for or add a record
3. Edit or delete a record

== Changelog ==

= 1.2.3 =
* Fuzzy search added.  You can now select to search string fields by part word or phrase.

= 1.1.3 =
* Bug introduced by WordPress 3.5 (prepare statement now always requires 2 parameters) FIXED

= 1.1.2 =
* Instructions link added to widget

= 1.1.1 =
* Plugin homepage address changed

= 1.1.0 =
* Simplified layout
* Settings link added to main plugin page
* Option to set a friendly database name in dashboard widget

= 1.0.0 =
* First release

== Upgrade Notice ==

= 1.1.1 =
Plugin homepage address changed

= 1.1.0 =
Widget appearance and ease of use updated

= 1.0.0 =
If you don't install this you won't have the plugin :)