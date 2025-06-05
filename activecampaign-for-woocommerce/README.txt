=== ActiveCampaign for WooCommerce ===
Contributors: acteamintegrations, bartboy011
Tags: marketing, ecommerce, woocommerce, email, activecampaign, abandoned cart
Requires at least: 6.0
Tested up to: 6.8.1
Stable tag: 2.10.0
Requires PHP: 7.4
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

The official ActiveCampaign for WooCommerce plugin connects to your store to acquire, convert, and retain customers with marketing automations.

== Description ==

https://youtu.be/wHPrLFXQTgQ

[Setting up the WooCommerce integration in ActiveCampaign in 6 Minutes](https://youtu.be/wHPrLFXQTgQ)

Trusted by thousands of brands, ActiveCampaign is your all-in-one email and marketing automation solution to acquire, convert, and retain customers through email and SMS to drive engagement, increase AOV, and recover lost revenue–[start your free trial today.](https://www.activecampaign.com/pricing)

You need WooCommerce 3.0 or greater and the ActiveCampaign for WooCommerce WordPress plugin 1.2.0 to configure this integration. [Learn more about how to connect your WooCommerce store with ActiveCampaign.](https://www.activecampaign.com/pricing)

= Seamlessly sync WooCommerce store data =
Integrating ActiveCampaign with WooCommerce takes only minutes and is pre-built right out of the box. This allows you to sync all your historical and real-time data, including custom objects like product catalogs and coupon codes, ensuring you stay on top of every interaction buyers have with your brand.

*   Utilize custom objects such as Product Catalog, Recurring Payments, and Coupon codes.
*   Build segments and campaigns, send automated flows, and generate reports without any coding.
*   Trigger automations based on Order Status changes, such as driving completion of pending orders, expressing gratitude to completing shoppers, and identifying issues with failed, returned, canceled, or refunded orders.
*   Manage customer relationships or wholesale business effectively with Marketing CRM.

= Leverage historical and real-time customer, order, and subscription data for advanced segmentation =
Streamline your efforts and drive meaningful marketing automations using all your store data in a single place to optimize customer experiences.

* Segment contacts using a combination of event data (e.g., abandoned cart) and behavior data (e.g., placed orders, refunded orders).
* Build unlimited segment parameters using Customer, Order, and Subscription data (e.g. your loyal customers, last year’s BFCM shoppers, engaged subscribers in the last 90 days, purchased within category).
* Connect WooCommerce with Facebook, Google, Linkedin and more for retargeting and lookalike segmentation.

= Manage and optimize your subscription business =
For businesses with recurring payments, automate tasks, personalize communications, and proactively address customer needs to reduce churn risk:

* Set up email marketing automations to engage customers at pivotal moments in their subscription journey, starting with a welcome email to nurture loyalty.
* Use the Product Catalog feature to create strategic email campaigns that recommend complementary products or enticing upgrades for effective cross-selling and upselling.
* Notify subscribers via email if their payment fails, offering assistance in updating payment information or resolving billing issues.

= Omnichannel marketing with natives Integrations =
Benefit from over 900+ native integrations to streamline your marketing efforts across multiple channels and platforms.

* Targeted ads on Facebook, Google, and other social media platform to encourage customers to return and complete the purchase.
* Sync your marketing message across email, SMS, and social media ads (via Facebook and Google integrations).
* Trigger a series of post-purchase communications and surveys across email, SMS, and social media.


= About ActiveCampaign =
ActiveCampaign’s email and marketing automations platform is chosen by over 150,000 businesses in 170 countries to meaningfully engage with their customers. The platform gives businesses of all sizes access to AI-powered automations that suggest, personalize, and validate your marketing campaigns that combine transactional email, email marketing, marketing automations, and CRM for powerful segmentation and personalization across social, email, messaging, chat, and text. Over 70% of ActiveCampaign’s customers use its 900+ integrations including WordPress, Shopify, Square, Facebook, and Salesforce.

ActiveCampaign scores higher in customer satisfaction than any other solution in Marketing Automation, CRM, and E-Commerce Personalization on G2.com and is the Top Rated Marketing Automation Software on TrustRadius. [Start your free trial today.](https://www.activecampaign.com/pricing)

== Screenshots ==

1. ActiveCampaign for WooCommerce
2. Post-purchase thank you and product suggestion ActiveCampaign for WooCommerce automation workflow
3. WooCommerce store purchase history on an ActiveCampaign contact
4. Accessory upsell after purchase ActiveCampaign automation recipe for WooCommerce stores
5. Ecommerce subscription and welcome ActiveCampaign automation recipe for WooCommerce stores
6. Birthday and anniversary coupon email ActiveCampaign automation recipe for WooCommerce store

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
