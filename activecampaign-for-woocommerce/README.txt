=== ActiveCampaign for WooCommerce ===
Contributors: acteamintegrations, bartboy011
Tags: email marketing, ecommerce, woocommerce, activecampaign, abandoned cart
Requires at least: 7.0
Tested up to: 7.1.3
Stable tag: 3.0.1
Requires PHP: 8.1
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

Autonomous marketing to transform your store. Fuel your customer journeys with personalized experiences across email, SMS, and WhatsApp.

== Description ==

https://youtu.be/wHPrLFXQTgQ

ActiveCampaign is the autonomous marketing platform built to transform how marketers, agencies, and business owners work. Use Active Intelligence to power goal-aware automations and orchestrate personalized experiences across email, SMS, and WhatsApp. Effortlessly integrate with 1000+ apps, uncover deep performance insights, and optimize your workflows so you win every day.

== ActiveCampaign’s Capabilities ==

*  **Autonomous marketing**
  Built on the foundation of marketing automation, fuel your marketing strategy and customer journeys with AI-driven execution, optimization, and insight at every step.
* **AI agents**
  Run entire marketing campaigns through simple prompts, backed by Active Intelligence.
* **Cross-channel marketing**
  Reach prospects and customers wherever they are, with email, SMS, WhatsApp, and more.
* **On-brand, personalized content**
  Creative tools that deliver professional, conversion-ready designs for email and landing pages.
* **CRM**
  Track, manage, and automate your sales process.
* **1000+ apps & integrations**
  Connect ActiveCampaign to your favorite tools.

== WooCommerce + ActiveCampaign ==

Send real-time store data directly to ActiveCampaign
Engage customers with automated abandoned cart and post-purchase emails.
Personalize email, SMS, and WhatsApp messages with purchase data.
Showcase specific product catalogs in your marketing emails.

== Here’s what you’ll need to configure this integration: ==

* WordPress 7.0 or newer, PHP 8.1 or newer
* WooCommerce 10.0 or newer
* The latest version of the ActiveCampaign for WooCommerce plugin

**Learn more: [Connect WooCommerce to ActiveCampaign](https://help.activecampaign.com/hc/en-us/articles/115000652490-Connect-WooCommerce-to-ActiveCampaign)**

== Screenshots ==

1. Active Intelligence Workspace
2. Email Marketing
3. Abandoned Cart and Post-Purchase Automations
4. Segments
5. Active Intelligence
6. AI Brand Kit
7. Cross-channel Marketing

== Installation ==

= WooCommerce Compatibility =
* Tested up to version: 11.2.1
* Minimal version requirement: 10.0
* HPOS Compatible
* WooCommerce Blocks now supported

= Minimum Requirements =
* PHP 8.1 or greater
* WordPress 7.0 or greater (tested up to 7.1.3)
* WooCommerce 10.0 or greater (tested up to 11.2.1)
* Latest release versions of WordPress and WooCommerce are recommended
* MySQL version 5.6 or greater

= Before You Start =
- Our plugin requires you to have the WooCommerce plugin installed and activated in WordPress.
- Your hosting environment must meet the minimum requirements above, including PHP 8.1 or greater. WordPress will not install this version on stores below these minimums; such stores keep running their current 2.11.x version.

= Installation Steps =
1. In your ActiveCampaign account, navigate to Settings.
2. Click the Integrations tab.
3. If your WooCommerce store is already listed here, skip to step 7. Otherwise, continue to step 4.
4. Click the "Add Integration" button.
5. Enter the URL of your WooCommerce site.
6. Follow the connection process that appears in WooCommerce.
7. In your WooCommerce store, install the "ActiveCampaign for WooCommerce" plugin and activate it.
8. Navigate to the plugin settings page (Settings > ActiveCampaign for WooCommerce)
9. Enter your ActiveCampaign API URL and API Key in the provided boxes.
10. Click "Update Settings".

== Changelog ==

= 3.0.1 2026-11-02 =
* Documentation: the plugin listing now states the current minimum requirements (PHP 8.1, WordPress 7.0, WooCommerce 10.0) and shows an upgrade notice before updating

= 3.0.0 2026-11-02 =
* Requires PHP 8.1 or newer, WordPress 7.0 or newer and WooCommerce 10.0 or newer; tested with WordPress 7.1 and WooCommerce 11.2
* Security: all admin actions of the plugin now require the "Manage WooCommerce" capability; the API key is no longer written to log files
* Fix: orders deleted or trashed on stores using High-Performance Order Storage were not removed from the sync table and were retried forever
* Fix: the abandoned-cart checkout id was not saved on orders placed through the block checkout; quantity changes in the block cart are now tracked
* Fix: plugin translations and database upgrades hooked on plugin load never ran; database indexes were not created when upgrading from very old versions
* Fix: timezone and interval calculations in the abandoned cart sync, historical sync delay and connection health check
* Fix: settings could be saved unverified when the connection check failed
* Improvement: PHP 8.1 - 8.4 compatibility (no deprecation notices), updated libraries (PHP-DI 7, PSR Log 3, Brick Money 0.10)
* Improvement: uninstalling removes every plugin option, transient and scheduled action
* Improvement: admin pages show spinners, progress bars and a finished notice for connection test, settings save, historical sync and product sync
* Fix: the marketing consent checkbox is shown on both the Checkout block and the classic checkout regardless of theme; the "checked by default" option is honoured by the Checkout block
* Fix: the product sync no longer loads every variation of a variable product at once; stores with very large variable products could run out of memory and never finish the sync
* Fix: a malformed WP-Cron entry of the recurring sync could stop every scheduled task of the site with a fatal error on PHP 8; the recurring hooks are now cleaned and rebuilt on update and on every settings save
* Fix: the historical sync now includes orders the regular sync never processed and no longer reports a whole batch as failed because of a validation error in one group
* Fix: an empty "Product URL patterns" field no longer disables Browse Abandonment; the default patterns are sent instead

= 2.11.3 2026-10-20 =
* Fix: Order line item prices are no longer rounded to whole currency units; refunded items are synced with the correct unit price

= 2.11.2 2026-10-09 =
* Fix: Prevent out-of-memory crash when syncing orders containing variations from products with very large variation counts

= 2.11.1 2026-09-21 =
* Fix: Prevent site errors caused by conflicts with other plugins

= 2.11.0 2026-07-30 =
* Improvement: Products and product variations are getting deleted from product catalogue
* Improvement: New Product Cleanup job was added to delete old products from product catalogue

= 2.10.5 2026-06-24 =
* Improvement: Prevent checkboxes from being double-rendered on the checkout page

= 2.10.4 2026-06-02 =
* Improvement - added a self-triggering retry mechanism for API requests after a specific date.
* Improvement - added a checkout version check to dynamically render the correct marketing opt-in checkbox (Gutenberg Blocks vs. Legacy).

= 2.10.3 2026-04-15 =
* Improvement - prevent external API calls during maintenance mode/archivization process

= 2.10.2 2026-01-15 =
* Fix - Syncing status changes into ActiveCampaign
* Fix - Syncing `shop_order_placehold` type into ActiveCampaign
* Fix - Getting correct count of records on historical sync page

= 2.10.1 2025-08-07 =
* Fix - Distinction between user ID and customer ID when syncing order
* Fix - Syncing order multiple times causing automations to run twice

= 2.10.0 2025-05-15 =
* Improvement - Accepts marketing support for WooCommerce Blocks added
* Improvement - Abandoned cart now has debug option for show all in admin (limited to 500)

= 2.9.2 2025-04-29 =
* Improvement - Multisite compatible permissions check
* Fix - Orders not always synced to hosted
* Fix - Count error on cron run fixed

= 2.9.1 2025-04-14 =
* Improvement - Abandoned carts that have failed to sync can be reset
* Upkeep - Support page corrections
* Fix - Order sync improvements

= 2.9.0 2025-03-06 =
* Improvement - Order sync scheduling rebuilt
* Improvement - Action Schedule will be preferred with fallback to cron
* Bugfix - Order sync and abandon sync process bugs resolved

= 2.8.7 2025-02-13 =
* Improvement - Admin settings improvements

= 2.8.6 2025-02-12 =
* Bugfix - Fix cron event fatal error during order sync

= 2.8.5 2025-02-10 =
* Improvement - Browse Session Timeouts saved as minutes

= 2.8.4 2025-01-30 =
* Improvement - Recovered orders should track better
* Bugfix - Fixing issues discovered in WC version 9.6.0
* Bugfix - Abandoned carts would sometimes not get picked up
* Bugfix - Metadata relevant to order syncing was not being saved by WC

= 2.8.3 2025-01-16 =
* Feature - Adding slugs to tracking options
* Bugfix - Tracking no longer requires saving settings twice

= 2.8.2 2025-01-13 =
* Bugfix - Resolving missing whitelist class

= 2.8.1 2025-01-09 =
* Feature - Browse Abandonment Settings Page Management
* Bugfix - Issue with synced order in ActiveCampaign not being visible in WooCommerce

= 2.8.0 2024-12-10 =
* Feature - Product sync now has the option to directly pull products from the database
* Enhancement - Product sync will show the number of products available to sync for debugging purposes
* Bugfix - Fatal errors thrown by PHP 8.2 resolved
* Bugfix - Orders from WooCommerce sometimes synced to the wrong contact
* Bugfix - Products missing from product sync with some plugin customizations
* Bugfix - Edge case where some orders would convert to subscriptions and vanish from the store

See CHANGELOG file for all changes

== Upgrade Notice ==

= 3.0.1 =
Requires PHP 8.1, WordPress 7.0 and WooCommerce 10.0 or newer. Stores on older versions will not receive this update and keep running 2.11.x. Settings and connection are preserved on update.

= 3.0.0 =
Requires PHP 8.1, WordPress 7.0 and WooCommerce 10.0 or newer. Stores on older versions will not receive this update and keep running 2.11.x. Settings and connection are preserved on update.
