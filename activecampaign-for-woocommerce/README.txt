=== ActiveCampaign for WooCommerce ===
Contributors: acteamintegrations, bartboy011
Tags: marketing, ecommerce, woocommerce, email, activecampaign, abandoned cart
Requires at least: 6.0
Tested up to: 6.6.2
Stable tag: 2.7.10
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
* Tested up to version: 9.3.3
* Minimal version requirement: 7.4.0
* HPOS Compatible

= Minimum Requirements =
* Wordpress supported PHP version (PHP 7.4 or greater is recommended)
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

= 2.7.10 2024-10-28 =
* Bugfix - Solving various issues with order update
* Bugfix - Abandoned cart created date error resolved

= 2.7.9 2024-10-16 =
* Bugfix - Issue with our order action event has been resolved

= 2.7.8 2024-10-15 =
* Bugfix - WooCommerce hook for stripe added to the order sync
* Bugfix - Order status changes should not get lost if done quickly
* Bugfix - Added debug display items for product sync
* Bugfix - Fixed product sync issue related to gathering records due to WC updates

= 2.7.7 2024-09-11 =
* Enhancement - WooCommerce checkout blocks supported for abandoned cart
* Fix - Order pages no longer cause errors in the AC block
* Fix - Various issues due to WooCommerce changes

= 2.7.6 2024-07-30 =
* Enhancement - Orders through Stripe will trigger the order updated hook
* Fix - WooCommerce Order with Stripe payment not updating correct status
* Fix - Product sync throws error on isVisible field

= 2.7.5 2024-07-19 =
* Fix - Grammar tokens issue resolved
* Fix - Fetch parent category if variation has none set or is "uncategorized"

= 2.7.4 2024-06-26 =
* Enhancement - New product sync option in settings for product description selection between full or short description

= 2.7.3 2024-06-17 =
* Update - WooCommerce 9.0.0 compatibility updates
* Tweak - Better error handling for bad records sent to ActiveCampaign
* Fix - WCS not always returning all records in historical sync

= 2.7.2 2024-06-10 =
* Some small bug fixes
* Resolved bug with historical sync subscriptions halting

= 2.7.1 2024-06-03 =
* Bug fixes for subscription statuses
* Adding logging and minor fixes to abandoned carts

= 2.7.0 2024-05-21 =
* Compatible with WP 6.5.3 & WC 8.9.0
* Adds AC account feature retrieval
* Corrects bugs with some status types missing in historical sync
* Fixing a null error in PHP 8.1
* Corrects an error when subscriptions is not installed
* Fix for subscription orders being synced incorrectly

See CHANGELOG file for all changes
