=== ActiveCampaign for WooCommerce ===
Contributors: acteamintegrations, bartboy011
Tags: marketing, ecommerce, woocommerce, email, activecampaign, abandoned cart
Requires at least: 6.0
Tested up to: 6.8.1
Stable tag: 2.10.2
Requires PHP: 7.4
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

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

* WooCommerce 7.4 (or more recent version)
* The ActiveCampaign for WooCommerce WordPress plugin 2.10.2 .

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
* Tested up to version: 9.8.5
* Minimal version requirement: 7.4.0
* HPOS Compatible
* WooCommerce Blocks now supported

= Minimum Requirements =
* WordPress supported PHP version (PHP 7.4 or greater is recommended)
* Latest release versions of WordPress and WooCommerce are recommended
* MySQL version 5.6 or greater

= Before You Start =
- Our plugin requires you to have the WooCommerce plugin installed and activated in WordPress.
- Your hosting environment should meet WooCommerce's minimum requirements, including PHP 7.0 or greater.

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
