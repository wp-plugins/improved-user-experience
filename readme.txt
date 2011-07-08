=== Improved User Experience ===
Contributors: aaroncampbell
Donate link: https://www.paypal.com/cgi-bin/webscr?cmd=_s-xclick&hosted_button_id=10147595
Tags: user, login, lost password
Requires at least: 2.6
Tested up to: 3.2
Stable tag: 1.1.2

This plugin allows users to log in with an E-Mail address and shortens the password recovery process. Requires PHP5.

== Description ==

This plugin allows users to log in with an E-Mail address and shortens the password recovery process to:

* User clicks "forgot password"
* User enters their E-Mail address and submits.
* User gets and E-Mail with a link
* User follows the link and is logged in, taken to their profile page, and prompted to change their password

<em>Requires PHP5.</em>

== Installation ==

1. Verify that you have PHP5, which is required for this plugin.
1. Upload the whole `improved-user-experience` directory to the `/wp-content/plugins/` directory
1. Activate the plugin through the 'Plugins' menu in WordPress

== Frequently Asked Questions ==

= Why change the password reset process? =

I personally like the default password reset process in WordPress, but I have a lot of clients that think it's too many steps.  This method is faster, easier, and still secure.

== Changelog ==

= 1.1.2 =
* Fix issue with some sites not getting the user logged in to reset their password

= 1.1.1 =
* Updated to work with the new reset password process from WordPress 3.1
* Upgraded to use the new <a href="http://xavisys.com/xavisys-wordpress-plugin-framework/">Xavisys WordPress Plugin Framework</a>

= 1.1.0 =
* Upgraded to use the new <a href="http://xavisys.com/xavisys-wordpress-plugin-framework/">Xavisys WordPress Plugin Framework</a>

= 1.0.0 =
* Released to wordpress.org repository
