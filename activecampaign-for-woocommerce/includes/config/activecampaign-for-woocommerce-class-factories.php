<?php

/**
 * The file that contains definitions for the Dependencies Container.
 *
 * The definitions laid out here are used by the Dependencies Container to fetch
 * the appropriate value/class/etc. If the value of the definition is callable,
 * the container will return the returned value from the callable.
 *
 * @link       https://www.activecampaign.com/
 * @since      1.0.0
 *
 * @package    Activecampaign_For_Woocommerce
 * @subpackage Activecampaign_For_Woocommerce/includes/config
 */

use AcVendor\Psr\Container\ContainerInterface;
use AcVendor\DI\factory;
use Activecampaign_For_Woocommerce_AC_Contact_Repository as Contact_Repository;
use Activecampaign_For_Woocommerce_Add_Accepts_Marketing_To_Customer_Meta_Command as Add_Accepts_Marketing_To_Customer_Meta;
use Activecampaign_For_Woocommerce_Admin as Admin;
use Activecampaign_For_Woocommerce_Api_Client as Api_Client;
use Activecampaign_For_Woocommerce_Cart_Events as Cart_Events;
use Activecampaign_For_Woocommerce_Clear_User_Meta_Command as Clear_User_Meta_Command;
use Activecampaign_For_Woocommerce_Connection_Option_Repository as Connection_Option_Repository;
use Activecampaign_For_Woocommerce_Connection_Repository as Connection_Repository;
use Activecampaign_For_Woocommerce_Create_Or_Update_Connection_Option_Command as Create_Or_Update_Connection_Option_Command;
use Activecampaign_For_Woocommerce_Ecom_Customer_Repository as Ecom_Customer_Repository;
use Activecampaign_For_Woocommerce_Ecom_Order_Repository as Ecom_Order_Repository;
use Activecampaign_For_Woocommerce_Cofe_Order_Repository as Cofe_Order_Repository;
use Activecampaign_For_Woocommerce_I18n as I18n;
use Activecampaign_For_Woocommerce_Loader as Loader;
use Activecampaign_For_Woocommerce_Logger as Logger;
use Activecampaign_For_Woocommerce_Customer_Utilities as Customer_Utilities;
use Activecampaign_For_Woocommerce_Plugin_Upgrade as Plugin_Upgrade_Command;
use Activecampaign_For_Woocommerce_Public as AC_Public;
use Activecampaign_For_Woocommerce_Run_Abandonment_Sync_Command as Run_Abandonment_Sync_Command;
use Activecampaign_For_Woocommerce_Set_Connection_Id_Cache_Command as Set_Connection_Id_Cache_Command;
use Activecampaign_For_Woocommerce_Sync_Guest_Abandoned_Cart_Command as Sync_Guest_Abandoned_Cart_Command;
use Activecampaign_For_Woocommerce_New_Order_Created_Event as Order_Finished;
use Activecampaign_For_Woocommerce_User_Registered_Event as User_Registered;
use Activecampaign_For_Woocommerce_Historical_Sync_Handler as Historical_Sync;
use Activecampaign_For_Woocommerce_Product_Sync_Job as Product_Sync;
use Activecampaign_For_Woocommerce_New_Order_Sync_Job as New_Order_Sync;
use Activecampaign_For_Woocommerce_Bulksync_Repository as Bulksync_Repository;
use Activecampaign_For_Woocommerce_AC_Contact_Batch_Repository as AC_Contact_Batch_Repository;
use Activecampaign_For_Woocommerce_Api_Client_Graphql as Graphql_Api_Client;
use Activecampaign_For_Woocommerce_Product_Repository as Cofe_Product_Repository;
use Activecampaign_For_Woocommerce_Admin_WC_Order_Page as Admin_Order_Page;
use Activecampaign_For_Woocommerce_New_Subscription_Sync_Job as New_Subscription_Sync;
use Activecampaign_For_Woocommerce_Subscription_Events as Subscription_Events;

return array(
	Activecampaign_For_Woocommerce::class             => function (
		Loader $loader,
		Admin $admin,
		AC_Public $public,
		I18n $i18n,
		Logger $logger,
		Cart_Events $cart_events,
		Set_Connection_Id_Cache_Command $set_connection_id_cache_command,
		Create_Or_Update_Connection_Option_Command $c_or_u_co_command,
		Add_Accepts_Marketing_To_Customer_Meta $add_am_to_meta_command,
		Clear_User_Meta_Command $clear_user_meta_command,
		Sync_Guest_Abandoned_Cart_Command $sync_guest_abandoned_cart_command,
		Order_Finished $order_finished_event,
		User_Registered $user_registered_event,
		Run_Abandonment_Sync_Command $run_abandonment_sync_command,
		Plugin_Upgrade_Command $plugin_upgrade_command,
		Historical_Sync $historical_sync,
		AC_Contact_Batch_Repository $ac_contact_batch_repository,
		New_Order_Sync $new_order_sync,
		Customer_Utilities $customer_utilities,
		Bulksync_Repository $bulksync_repository,
		Contact_Repository $contact_repository,
		Cofe_Product_Repository $cofe_product_repository,
		Product_Sync $product_sync_job,
		Admin_Order_Page $admin_order_page,
		Subscription_Events $subscription_events,
		New_Subscription_Sync $new_subscription_sync
	) {
		$version = defined( 'ACTIVECAMPAIGN_FOR_WOOCOMMERCE_VERSION' ) ?
			ACTIVECAMPAIGN_FOR_WOOCOMMERCE_VERSION :
			'1.0.0';

		$plugin_name = defined( 'ACTIVECAMPAIGN_FOR_WOOCOMMERCE_PLUGIN_NAME_KEBAB' ) ?
			ACTIVECAMPAIGN_FOR_WOOCOMMERCE_PLUGIN_NAME_KEBAB :
			'activecampaign-for-woocommerce';

		return new Activecampaign_For_Woocommerce(
			$version,
			$plugin_name,
			$loader,
			$admin,
			$public,
			$i18n,
			$logger,
			$cart_events,
			$set_connection_id_cache_command,
			$c_or_u_co_command,
			$add_am_to_meta_command,
			$clear_user_meta_command,
			$sync_guest_abandoned_cart_command,
			$order_finished_event,
			$user_registered_event,
			$run_abandonment_sync_command,
			$plugin_upgrade_command,
			$historical_sync,
			$ac_contact_batch_repository,
			$new_order_sync,
			$customer_utilities,
			$bulksync_repository,
			$contact_repository,
			$cofe_product_repository,
			$product_sync_job,
			$admin_order_page,
			$subscription_events,
			$new_subscription_sync
		);
	},

	Add_Accepts_Marketing_To_Customer_Meta::class     => static function ( Logger $logger ) {
		return new Add_Accepts_Marketing_To_Customer_Meta( $logger );
	},

	Admin::class                                      => static function ( ContainerInterface $c, Connection_Repository $connection_repository, Api_Client $api_client ) {
		$version = defined( 'ACTIVECAMPAIGN_FOR_WOOCOMMERCE_VERSION' ) ?
			ACTIVECAMPAIGN_FOR_WOOCOMMERCE_VERSION :
			'1.0.0';

		$plugin_name = defined( 'ACTIVECAMPAIGN_FOR_WOOCOMMERCE_PLUGIN_NAME_KEBAB' ) ?
			ACTIVECAMPAIGN_FOR_WOOCOMMERCE_PLUGIN_NAME_KEBAB :
			'activecampaign-for-woocommerce';

		$validator = $c->get( Activecampaign_For_Woocommerce_Admin_Settings_Validator::class );
		$contact_repository = new Contact_Repository( $api_client );
		$event = new Activecampaign_For_Woocommerce_Admin_Settings_Updated_Event();

		return new Admin( $plugin_name, $version, $validator, $event, $connection_repository, $contact_repository );
	},

	Api_Client::class                                 => static function ( Logger $logger ) {
		$settings = get_option( ACTIVECAMPAIGN_FOR_WOOCOMMERCE_DB_SETTINGS_NAME );

		$api_uri = isset( $settings['api_url'] ) ? $settings['api_url'] : null;
		$api_key = isset( $settings['api_key'] ) ? $settings['api_key'] : null;

		return new Api_Client( $api_uri, $api_key, $logger );
	},

	Graphql_Api_Client::class                         => static function ( Logger $logger ) {
		$settings = get_option( ACTIVECAMPAIGN_FOR_WOOCOMMERCE_DB_SETTINGS_NAME );

		$api_uri = isset( $settings['api_url'] ) ? $settings['api_url'] : null;
		$api_key = isset( $settings['api_key'] ) ? $settings['api_key'] : null;

		return new Graphql_Api_Client( $api_uri, $api_key, $logger );
	},

	AC_Public::class                                  => static function ( Admin $admin ) {
		$version = defined( 'ACTIVECAMPAIGN_FOR_WOOCOMMERCE_VERSION' ) ? ACTIVECAMPAIGN_FOR_WOOCOMMERCE_VERSION : '1.0.0';

		$plugin_name = defined( 'ACTIVECAMPAIGN_FOR_WOOCOMMERCE_PLUGIN_NAME_KEBAB' ) ? ACTIVECAMPAIGN_FOR_WOOCOMMERCE_PLUGIN_NAME_KEBAB : 'activecampaign-for-woocommerce';

		return new AC_Public( $plugin_name, $version, $admin );
	},


	Create_Or_Update_Connection_Option_Command::class => static function (
		Admin $admin,
		Connection_Option_Repository $repository,
		Logger $logger
	) {
		return new Create_Or_Update_Connection_Option_Command( $admin, $repository, null, $logger );
	},

	Set_Connection_Id_Cache_Command::class            => static function (
		Admin $admin,
		Connection_Repository $connection_repository,
		Logger $logger
	) {
		return new Set_Connection_Id_Cache_Command( $admin, $connection_repository, $logger );
	},

	Sync_Guest_Abandoned_Cart_Command::class          => static function (
		Admin $admin,
		Ecom_Customer_Repository $customer_repository
	) {
		return new Sync_Guest_Abandoned_Cart_Command(
			null,
			null,
			null,
			$admin,
			$customer_repository
		);
	},

	Activecampaign_For_Woocommerce_Save_Abandoned_Cart_Command::class => static function (
		Logger $logger
	) {
		return new Activecampaign_For_Woocommerce_Save_Abandoned_Cart_Command( $logger );
	},

	Historical_Sync::class                            => static function (
		Logger $logger,
		AC_Contact_Batch_Repository $ac_contact_batch_repository,
		Cofe_Order_Repository $cofe_order_repository,
		Bulksync_Repository $bulksync_repository
	) {
		return new Historical_Sync( $logger, $ac_contact_batch_repository, $cofe_order_repository, $bulksync_repository );
	},

	Product_Sync::class                               => static function (
		Logger $logger,
		Cofe_Product_Repository $cofe_product_repository
	) {
		return new Product_Sync( $logger, $cofe_product_repository );
	},

	Cofe_Product_Repository::class                    => static function (
		Graphql_Api_Client $client
	) {
		return new Cofe_Product_Repository( $client );
	},
	New_Order_Sync::class                             => static function (
		Logger $logger,
		Customer_Utilities $customer_utilities,
		Ecom_Customer_Repository $customer_repository,
		Ecom_Order_Repository $order_repository,
		Cofe_Order_Repository $cofe_order_repository
	) {
		return new New_Order_Sync( $logger, $customer_utilities, $customer_repository, $order_repository, $cofe_order_repository );
	},
	Plugin_Upgrade_Command::class                     => static function (
		Logger $logger,
		Ecom_Order_Repository $order_repository
	) {
		return new Plugin_Upgrade_Command( $logger, $order_repository );
	},
	Logger::class                                     => static function () {
		return new Logger();
	},
);
