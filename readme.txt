=== Enterprise Shipping for Pitney Bowes ===
Contributors: rermis
Tags: woocommerce, shipping, live rates, pitney bowes, label
License: GPLv3 or later
License URI: http://www.gnu.org/licenses/gpl-3.0.html
Requires at least: 4.6
Tested up to: 6.6
Stable tag: 5.0.11

A streamlined US shipping solution for WooCommerce and Pitney Bowes.

== Description ==
A streamlined US shipping solution for WooCommerce and Pitney Bowes.

## Features
&#9745;  **Print US Domestic Labels** by USPS, PB, FedEx or UPS

&#9745; **Live Customizable Rates** in cart

&#9745; **Print Labels** directly from WooCommerce orders

&#9745; **WooCommerce Shipment Tracking** Compatible

&#9745; **Advanced Options** for US address validation, insurance and special services

&#9745; **Delivery Notifications** for customers and admins, plus support for SMS

&#9745; **Pitney Bowes business account** required to purchase labels


== Installation ==
1. Upload the plugin files to the `/wp-content/plugins/pb-shipping` directory, or install the plugin through the WordPress plugins screen directly.
2. Activate the plugin through the \'Plugins\' screen in WordPress
3. Go to the WooCommerce > Settings page for setup
4. Visit the WooCommerce > Orders page to begin shipping orders


== Changelog ==
= 5.0.11 = * Detect out of order acceptance scans. Improve predictive box sizes.
= 5.0.9 = * Added delivery estimate to tracking meta.
= 5.0.8 = * Compatibility with WP 6.6, WC 9.1
= 5.0.7 = * Compatibility with WC 8.9, Added sanitization to setting tabs.
= 5.0.6 = * Report interface refinements.
= 5.0.5 = * Caching and report refinements.
= 5.0.3 = * Minor setup improvements. Revised fetch logic.
= 5.0.1 = * Minor aesthetic improvements.
= 5.0.0 = * Restructured account setup. Improved instructions.
= 4.0.2 = * Minor improvements and readme updates.
= 4.0.1 = * Report improvements. Notification bug fixes.
= 4.0.0 = * Redesigned setup interface. Expanded notification options. Optional default to origin behavior.
= 3.3.4 = * Shipping reports improvements.
= 3.3.3 = * Minor bug fixes. Compatibility with WC 8.5.
= 3.3.2 = * Shipping reports improvements. Bug fix: weight calculation.
= 3.3.0 = * Update for WC unit measure conversions. Compatibility with WC 8.4 and WC HPOS.
= 3.2.16 = * Updates to available methods in live rate setup.
= 3.2.15 = * Compatibility with WP 6.4, WC 8.2.
= 3.2.14 = * Bug fixes to live cart rate and cart caching. Improvements to packing weight est.
= 3.2.12 = * Add menu shortcut to WooCommerce submenu. Update readme.
= 3.2.11 = * Compatibility improvements with MySQL <8.0
= 3.2.9 = * Shipping method setup refinements. Add button for setup when no US-zoned method exists.
= 3.2.8 = * Compatibility with WC 8.0. Notify admin of shipping exceptions in transit. Do not update last ship_date on purchase of return label. Allow ship_date meta update from reports. Define USPS rate acronyms on rollover.
= 3.2.6 = * Compatibility with WC 7.8.
= 3.2.5 = * Bug fixes to tracking fetch inside reports. Improvements to queue color coding.
= 3.2.4 = * Improvements to delivery date support in queue.
= 3.2.3 = * Minor bug fixes to setup.
= 3.2.2 = * Formula support using cart live rate. Bug fixes to completed email formatting.
= 3.2.1 = * Induction zip caching. Minor bug fixes.
= 3.2.0 = * Introduced tracking notifications for reships. Bug fix for table prefix in ship queue.
= 3.1.22 = * Compatibility with WP 6.2, WC 7.6.
= 3.1.20 = * Bug fix for PHP strict mode.
= 3.1.19 = * Report improvements. Default to manifest. Improve search interface.
= 3.1.18 = * History search improvements and bug fixes to pagination. Fix potential bugs in sms process.
= 3.1.16 = * Improved label history reports. Added tracking number search.
= 3.1.15 = * Improved storage efficiency of address validation cache.
= 3.1.14 = * Potential fix for wp-db deprecation bug for WP<6.1.1.
= 3.1.12 = * Minor bug fixes.
= 3.1.11 = * Allow printing of packing slip without label purchase.
= 3.1.8 = * Differentiate between display of retail and contract rates. Skip retail display when lower rate avail for identical service.
= 3.1.5 = * Ship date logging, input and caching.
= 3.1.4 = * Minor updates. Optional variation view for queue. WC tested up to: 7.0.
= 3.1.3 = * Added packing slip note and optional logo url. Bug fix for duplicate meta. WC tested up to: 6.8.
= 3.1.1 = * Improved caching of options by association. Use customer selected method for predicative selections.
= 3.1.0 = * Compatibility with WP 6.0. Various bug fixes for cart rate quotes, queue, and reporting.
= 3.0.23 = * Compatibility with WC 6.5. Minor improvements to queue and dim logic.
= 3.0.22 = * Update to use shipping company field, instead of billing co, if specified. Compatibility with WC 6.3.
= 3.0.21 = * Compatibility with WP 5.9, WC 6.2.
= 3.0.19 = * WC tested up to: 6.1. Minor improvements to ship notifications.
= 3.0.17 = * Opt out endpoint. Minor bug fixes.
= 3.0.16 = * Bug fixes to sms delivery status. Minor improvements to queue.
= 3.0.14 = * Bug fixes to queue and product list.
= 3.0.11 = * 2021 Holiday corrections. 
= 3.0.10 = * WC tested up to: 5.9. Other minor improvements to product ship list. 
= 3.0.9 = * DelCon default service for FCM & PM. Fix product qty bug after partial refund/return.
= 3.0.8 = * Compatibility with WC 5.8.
= 3.0.7 = * Remove transient on fetch setting update.
= 3.0.6 = * Add timing for fetch setting. Resubscribe to notifications feature.
= 3.0.5 = * Delete report bug fixes. Email queue optimization.
= 3.0.3 = * Email and sms delivery bug fixes. Email unsubscribe link. Email queue optimization.
= 3.0.2 = * Delivery notification functionality. Cron jobs and sms queue. Various minor bug fixes.


== Frequently Asked Questions ==

= Where can I get support? =
Reach out to us anytime for [additional support](https://richardlerma.com/#contact).