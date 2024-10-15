<?php

/**
 * The file that defines the core plugin class
 *
 * A class definition that includes attributes and functions used across both the
 * public-facing side of the site and the admin area.
 *
 * @link       https://www.activecampaign.com/
 * @since      1.0.0
 *
 * @package    Activecampaign_For_Woocommerce
 * @subpackage Activecampaign_For_Woocommerce/includes
 */

use Activecampaign_For_Woocommerce_Add_Accepts_Marketing_To_Customer_Meta_Command as Add_Accepts_Marketing_To_Customer_Meta;
use Activecampaign_For_Woocommerce_Clear_User_Meta_Command as Clear_User_Meta_Command;
use Activecampaign_For_Woocommerce_Admin as Admin;
use Activecampaign_For_Woocommerce_Cart_Events as Cart_Events;
use Activecampaign_For_Woocommerce_New_Order_Created_Event as New_Order_Created;
use Activecampaign_For_Woocommerce_User_Registered_Event as User_Registered;
use Activecampaign_For_Woocommerce_Create_Or_Update_Connection_Option_Command as Create_Or_Update_Connection_Option_Command;
use Activecampaign_For_Woocommerce_I18n as I18n;
use Activecampaign_For_Woocommerce_Loader as Loader;
use Activecampaign_For_Woocommerce_Public as AC_Public;
use Activecampaign_For_Woocommerce_Set_Connection_Id_Cache_Command as Set_Connection_Id_Cache_Command;
use Activecampaign_For_Woocommerce_Sync_Guest_Abandoned_Cart_Command as Sync_Guest_Abandoned_Cart_Command;
use Activecampaign_For_Woocommerce_Run_Abandonment_Sync_Command as Run_Abandonment_Sync_Command;
use Activecampaign_For_Woocommerce_Logger as Logger;
use Activecampaign_For_Woocommerce_Plugin_Upgrade as Plugin_Upgrade_Command;
use Activecampaign_For_Woocommerce_Historical_Sync_Handler as Historical_Sync;
use Activecampaign_For_Woocommerce_Product_Sync_Job as Product_Sync;
use Activecampaign_For_Woocommerce_New_Order_Sync_Job as New_Order_Sync;
use Activecampaign_For_Woocommerce_Customer_Utilities as Customer_Utilities;
use Activecampaign_For_Woocommerce_Bulksync_Repository as Bulksync_Repository;
use Activecampaign_For_Woocommerce_AC_Contact_Repository as Contact_Repository;
use Activecampaign_For_Woocommerce_Product_Repository as Product_Repository;
use Activecampaign_For_Woocommerce_Admin_WC_Order_Page as Admin_Order_Page;
use Activecampaign_For_Woocommerce_Order_Action_Events as Order_Events;
use Activecampaign_For_Woocommerce_AC_Contact_Batch_Repository as AC_Contact_Batch_Repository;
use Activecampaign_For_Woocommerce_Subscription_Events as Subscription_Events;
use Activecampaign_For_Woocommerce_New_Subscription_Sync_Job as New_Subscription_Sync;
use Activecampaign_For_Woocommerce_Admin_Subscription_Page as Admin_Subscription_Page;
/**
 * The core plugin class.
 *
 * This is used to define internationalization, admin-specific hooks, and
 * public-facing site hooks.
 *
 * Also maintains the unique identifier of this plugin as well as the current
 * version of the plugin.
 *
 * @since      1.0.0
 * @package    Activecampaign_For_Woocommerce
 * @subpackage Activecampaign_For_Woocommerce/includes
 * @author     acteamintegrations <team-integrations@activecampaign.com>
 */
class Activecampaign_For_Woocommerce {
	use Activecampaign_For_Woocommerce_Features_Checker;
	/**
	 * The Admin class that handles all admin-facing code.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      Admin $admin The admin class.
	 */
	private $admin;

	/**
	 * The WC order page meta section for AC info.
	 *
	 * @var Activecampaign_For_Woocommerce_Admin_WC_Order_Page
	 */
	private $admin_order_page;
	/**
	 * The Public class that handles all public-facing code.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      AC_Public $public The public class.
	 */
	private $public;
	/**
	 * The loader that's responsible for maintaining and registering all hooks that power
	 * the plugin.
	 *
	 * @since    1.0.0
	 * @access   protected
	 * @var      Loader $loader Maintains and registers all hooks for the plugin.
	 */
	protected $loader;

	/**
	 * The unique identifier of this plugin.
	 *
	 * @since    1.0.0
	 * @access   protected
	 * @var      string $plugin_name The string used to uniquely identify this plugin.
	 */
	protected $plugin_name;

	/**
	 * The current version of the plugin.
	 *
	 * @since    1.0.0
	 * @access   protected
	 * @var      string $version The current version of the plugin.
	 */
	protected $version;

	/**
	 * Handles all internationalization.
	 *
	 * @var Activecampaign_For_Woocommerce_I18n $i18n The internationalization class.
	 */
	private $i18n;

	/**
	 * Used for triggering cart events.
	 *
	 * @var Cart_Events The cart event class.
	 */
	private $cart_events;

	/**
	 * Used for triggering order finished event.
	 *
	 * @var New_Order_Created The order finished event class.
	 */
	private $new_order_created_event;

	/**
	 * Used for triggering new user registered event.
	 *
	 * @var User_Registered The user registered event class.
	 */
	private $user_registered_event;

	/**
	 * Handles setting the connection id cache.
	 *
	 * @var Set_Connection_Id_Cache_Command The set connection id command class.
	 */
	private $set_connection_id_cache_command;

	/**
	 * Handles creating or updating the connection ID in Hosted.
	 *
	 * @var Create_Or_Update_Connection_Option_Command The create or update connection option command.
	 */
	private $create_or_update_connection_option_command;

	/**
	 * Handles taking from the $_POST object the customers accepts marketing choice and adding
	 * it to the meta of the customer in the DB.
	 *
	 * @var Add_Accepts_Marketing_To_Customer_Meta The add accepts marketing meta to customer class.
	 */
	private $add_accepts_marketing_to_customer_meta_command;

	/**
	 * Handles clearing user meta if certain circumstances are met.
	 *
	 * @var Clear_User_Meta_Command The clear user meta command class.
	 */
	private $clear_user_meta_command;

	/**
	 * Handles sending the guest customer and pending order to AC.
	 *
	 * @var Sync_Guest_Abandoned_Cart_Command The sync guest abandoned cart command class.
	 */
	private $sync_guest_abandoned_cart_command;

	/**
	 * Handles syncing the abandoned carts to AC.
	 *
	 * @var Run_Abandonment_Sync_Command The sync sync runner command class.
	 */
	private $run_abandonment_sync_command;

	/**
	 * Handles plugin upgrade.
	 *
	 * @var Plugin_Upgrade_Command The upgrade command class.
	 */
	private $plugin_upgrade_command;

	/**
	 * Handles historical sync.
	 *
	 * @since 1.5.0
	 * @var Historical_Sync The historical sync class.
	 */
	private $historical_sync;

	/**
	 * Handles product sync.
	 *
	 * @since 1.9.0
	 * @var Product_Sync The product sync class.
	 */
	private $product_sync;

	/**
	 * Handles new order sync.
	 *
	 * @since 1.8.0
	 * @var New_Order_Sync The new sync class.
	 */
	private $new_order_sync;

	/**
	 * Handles new subscription sync.
	 *
	 * @since 3.0.0
	 * @var New_Subscription_Sync The subscription sync class.
	 */
	private $new_subscription_sync;
	/**
	 * Customer utility class.
	 *
	 * @since 1.5.0
	 * @var Customer_Utilities The customer utility class.
	 */
	private $customer_utilities;

	/**
	 * The bulksync repository class.
	 *
	 * @since 1.6.0
	 * @var Activecampaign_For_Woocommerce_Bulksync_Repository
	 */
	private $bulksync_repository;

	/**
	 * @var Activecampaign_For_Woocommerce_Product_Repository
	 */
	private $product_repository;

	/**
	 * @var Activecampaign_For_Woocommerce_Order_Action_Events
	 */
	private $order_events;

	/**
	 * @var Activecampaign_For_Woocommerce_Subscription_Events
	 */
	private $subscription_events;

	/**
	 * @var Activecampaign_For_Woocommerce_Logger
	 */
	private $logger;

	/**
	 * @var Activecampaign_For_Woocommerce_AC_Contact_Batch_Repository
	 */
	private $ac_contact_batch_repository;

	/**
	 * @var Activecampaign_For_Woocommerce_AC_Contact_Repository
	 */
	private $contact_repository;

	/**
	 * Define the core functionality of the plugin.
	 *
	 * Set the plugin name and the plugin version that can be used throughout the plugin.
	 *
	 * @param string                                               $version The current version of the plugin.
	 * @param string                                               $plugin_name The kebab-case name of the plugin.
	 * @param Loader                                               $loader The loader class.
	 * @param Admin                                                $admin The admin class.
	 * @param AC_Public                                            $public The public class.
	 * @param I18n                                                 $i18n The internationalization class.
	 * @param Logger                                               $logger The logger.
	 * @param Cart_Events                                          $cart_events The cart event class.
	 * @param Set_Connection_Id_Cache_Command                      $set_connection_id_cache_command The connection id cache command class.
	 * @param Create_Or_Update_Connection_Option_Command           $c_or_u_co_command The connection option command class.
	 * @param Add_Accepts_Marketing_To_Customer_Meta               $add_am_to_meta_command The accepts marketing command class.
	 * @param Clear_User_Meta_Command                              $clear_user_meta_command The clear user meta command class.
	 * @param Sync_Guest_Abandoned_Cart_Command                    $sync_guest_abandoned_cart_command The sync guest abandoned cart command class.
	 * @param New_Order_Created                                    $new_order_created_event The order finished event class.
	 * @param User_Registered                                      $user_registered_event The user registered event class.
	 * @param Run_Abandonment_Sync_Command                         $run_abandonment_sync_command The scheduled runner to sync abandonments.
	 * @param Plugin_Upgrade_Command                               $plugin_upgrade_command The plugin installation and upgrade commands.
	 * @param Historical_Sync                                      $historical_sync The historical sync commands.
	 * @param AC_Contact_Batch_Repository                          $ac_contact_batch_repository The contact batching sync commands.
	 * @param New_Order_Sync                                       $new_order_sync The new order sync job.
	 * @param Customer_Utilities                                   $customer_utilities The customer utility functions.
	 * @param Activecampaign_For_Woocommerce_Bulksync_Repository   $bulksync_repository The bulksync repository.
	 * @param Activecampaign_For_Woocommerce_AC_Contact_Repository $contact_repository The AC contact repository.
	 *
	 * @since    1.0.0
	 */
	public function __construct(
		$version,
		$plugin_name,
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
		New_Order_Created $new_order_created_event,
		User_Registered $user_registered_event,
		Run_Abandonment_Sync_Command $run_abandonment_sync_command,
		Plugin_Upgrade_Command $plugin_upgrade_command,
		Historical_Sync $historical_sync,
		AC_Contact_Batch_Repository $ac_contact_batch_repository,
		New_Order_Sync $new_order_sync,
		Customer_Utilities $customer_utilities,
		Bulksync_Repository $bulksync_repository,
		Contact_Repository $contact_repository,
		Product_Repository $product_repository,
		Product_Sync $product_sync,
		Admin_Order_Page $admin_order_page,
		Subscription_Events $subscription_events,
		New_Subscription_Sync $new_subscription_sync
	) {
		$this->version                                    = $version;
		$this->plugin_name                                = $plugin_name;
		$this->loader                                     = $loader;
		$this->admin                                      = $admin;
		$this->public                                     = $public;
		$this->i18n                                       = $i18n;
		$this->logger                                     = $logger;
		$this->cart_events                                = $cart_events;
		$this->set_connection_id_cache_command            = $set_connection_id_cache_command;
		$this->create_or_update_connection_option_command = $c_or_u_co_command;
		$this->add_accepts_marketing_to_customer_meta_command = $add_am_to_meta_command;
		$this->clear_user_meta_command                        = $clear_user_meta_command;
		$this->sync_guest_abandoned_cart_command              = $sync_guest_abandoned_cart_command;
		$this->new_order_created_event                        = $new_order_created_event;
		$this->user_registered_event                          = $user_registered_event;
		$this->run_abandonment_sync_command                   = $run_abandonment_sync_command;
		$this->plugin_upgrade_command                         = $plugin_upgrade_command;
		$this->historical_sync                                = $historical_sync;
		$this->ac_contact_batch_repository                    = $ac_contact_batch_repository;
		$this->new_order_sync                                 = $new_order_sync;
		$this->customer_utilities                             = $customer_utilities;
		$this->bulksync_repository                            = $bulksync_repository;
		$this->contact_repository                             = $contact_repository;
		$this->product_sync                                   = $product_sync;
		$this->product_repository                             = $product_repository;
		$this->admin_order_page                               = $admin_order_page;
		$this->order_events                                   = new Order_Events();
		$this->subscription_events                            = new Subscription_Events();
		$this->new_subscription_sync                          = $new_subscription_sync;
	}

	/**
	 * Define the locale for this plugin for internationalization.
	 *
	 * Uses the Activecampaign_For_Woocommerce_I18n class in order to set the domain and to register the hook
	 * with WordPress.
	 *
	 * @throws Exception Thrown when Container definitions are missing.
	 * @since    1.0.0
	 * @access   private
	 */
	private function set_locale() {
		$this->loader->add_action( 'plugins_loaded', $this->i18n, 'load_plugin_textdomain' );
	}

	/**
	 * On plugin update these hooks should run.
	 */
	private function plugin_updates() {
		$installed_version = get_option( 'activecampaign_for_woocommerce_db_version' );
		$db_version        = ACTIVECAMPAIGN_FOR_WOOCOMMERCE_DB_VERSION;

		if ( isset( $installed_version ) && $installed_version === $db_version ) {
			return;
		}

		$this->loader->add_action(
			'upgrader_post_install',
			$this->plugin_upgrade_command,
			'execute',
			1
		);

		$this->loader->add_action(
			'activecampaign_for_woocommerce_verify_tables',
			$this->plugin_upgrade_command,
			'verify_table',
			1
		);

		$this->loader->add_action(
			'upgrader_process_complete',
			$this->plugin_upgrade_command,
			'execute',
			1
		);

		$this->loader->add_action(
			'update_plugin_complete_actions',
			$this->plugin_upgrade_command,
			'execute',
			1
		);

		$this->loader->add_action(
			'plugins_loaded',
			$this->plugin_upgrade_command,
			'execute',
			1
		);
	}

	/**
	 * Register Events to be executed on different actions.
	 *
	 * @throws Exception Thrown when Container definitions are missing.
	 * @since    1.0.0
	 * @access   private
	 */
	private function define_event_hooks() {
		// If we can't get the config stop this function
		if ( ! $this->is_configured() ) {
			return;
		}

		// Cart actions
		$this->loader->add_action(
			'woocommerce_update_cart_action_cart_updated',
			$this->cart_events,
			'cart_updated'
		);

		$this->loader->add_action(
			'woocommerce_add_to_cart',
			$this->cart_events,
			'cart_updated'
		);

		$this->loader->add_action(
			'woocommerce_cart_item_removed',
			$this->cart_events,
			'cart_updated'
		);

		// Order complete hooks
		$this->loader->add_action(
			'woocommerce_checkout_update_order_meta',
			$this->cart_events,
			'cart_emptied'
		);

		$this->loader->add_action(
			'woocommerce_checkout_update_order_meta',
			$this->new_order_created_event,
			'execute_with_order_id',
			20
		);

		$this->loader->add_action(
			'woocommerce_new_order',
			$this->new_order_created_event,
			'ac_woocommerce_new_order',
			20
		);

		$this->loader->add_action(
			'woocommerce_payment_complete',
			$this->new_order_created_event,
			'ac_woocommerce_payment_complete',
			20
		);

		$this->loader->add_action(
			'woocommerce_checkout_order_created',
			$this->new_order_created_event,
			'ac_woocommerce_checkout_order_created',
			20
		);
		// End order complete hooks

		$this->loader->add_action(
			'user_register',
			$this->user_registered_event,
			'trigger'
		);

		$this->loader->add_action(
			'woocommerce_cart_emptied',
			$this->cart_events,
			'cart_emptied'
		);
	}

	/**
	 * Registers commands related to the admin portion of the WordPress site with
	 * action hooks.
	 *
	 * @since 1.2.1
	 * @access private
	 */
	private function define_admin_commands() {
		if ( $this->verify_ac_features( 'historical' ) ) {
			$this->define_historical_sync_commands();
		}
		if ( $this->verify_ac_features( 'product' ) ) {
			$this->define_product_sync();
		}

		/**
		 * By including priority 1, we ensure that the connection id caching occurs
		 * before the create or update command.
		 */
		$this->loader->add_action(
			'activecampaign_for_woocommerce_admin_settings_updated',
			$this->set_connection_id_cache_command,
			'execute',
			1
		);

		$this->loader->add_action(
			'activecampaign_for_woocommerce_admin_settings_updated',
			$this->create_or_update_connection_option_command,
			'execute'
		);

		$this->loader->add_filter(
			'activecampaign_for_woocommerce_admin_settings_updated',
			$this->clear_user_meta_command,
			'execute'
		);

		$this->loader->add_action(
			'activecampaign_for_woocommerce_ready_new_order',
			$this->new_order_created_event,
			'execute'
		);

		$this->loader->add_action(
			'activecampaign_for_woocommerce_download_log_data',
			$this->admin,
			'download_log_data',
			1
		);

		// This is called on trashing an order or a product
		$this->loader->add_action(
			'before_delete_post',
			$this->admin,
			'remove_deleted_order',
			1
		);

		// This is called on trashing an order or a product
		$this->loader->add_action(
			'wp_trash_post',
			$this->admin,
			'remove_deleted_order',
			1
		);

		// This is called on trashing an order or a product
		$this->loader->add_action(
			'wp_trash_post',
			$this->order_events,
			'execute_order_deleted',
			1
		);

		// When a product is restored
		$this->loader->add_action(
			'untrash_post',
			$this->admin,
			'restore_product',
			1
		);

		// When a product is created
		$this->loader->add_action(
			'wp_create_post',
			$this->admin,
			'restore_product',
			1
		);

		// On order update
		$this->loader->add_action(
			'woocommerce_process_shop_order_meta',
			$this->order_events,
			'execute_order_updated',
			90,
			1
		);

		$this->loader->add_action(
			'woocommerce_order_status_changed',
			$this->order_events,
			'execute_order_status_changed',
			10,
			3
		);

		// needed for stripe
		$this->loader->add_action(
			'woocommerce_order_edit_status',
			$this->order_events,
			'execute_order_edit_status',
			20,
			2
		);

		$this->loader->add_action(
			'wc_gateway_stripe_process_response',
			$this->order_events,
			'execute_order_updated_stripe',
			9,
			2
		);

		// On order refund
		$this->loader->add_action(
			'woocommerce_order_refunded',
			$this->order_events,
			'execute_order_updated_refund',
			20,
			2
		);

		if ( ! $this->is_configured() || ! $this->is_connected() ) {
			return;
		}

		$this->loader->add_action(
			ACTIVECAMPAIGN_FOR_WOOCOMMERCE_RUN_NEW_ORDER_SYNC_NAME,
			$this->new_order_sync,
			'execute',
			1,
			2
		);

		$this->loader->add_action(
			'activecampaign_for_woocommerce_admin_sync_single_order_active',
			$this->new_order_sync,
			'execute',
			1,
			2
		);

		$this->loader->add_action(
			'activecampaign_for_woocommerce_admin_sync_single_order_status',
			$this->new_order_sync,
			'execute_from_status',
			1,
			2
		);
	}

	private function define_product_sync() {
		if ( ! $this->is_configured() || ! $this->is_connected() ) {
			return;
		}

		/**
		 * Product sync actions
		 */
		$this->loader->add_action(
			'activecampaign_for_woocommerce_run_product_sync',
			$this->product_sync,
			'execute',
			1,
			1
		);

		$this->loader->add_action(
			'activecampaign_for_woocommerce_run_single_product_sync',
			$this->product_sync,
			'single_product_cofe_sync',
			1,
			1
		);

		$this->loader->add_action(
			'activecampaign_for_woocommerce_run_sync_connection',
			$this->product_sync,
			'run_sync_connection',
			1,
			1
		);

		$this->loader->add_action(
			'activecampaign_for_woocommerce_build_product_sync_schedules',
			$this->product_sync,
			'build_product_sync_schedules',
			1,
			2
		);

		$this->loader->add_action(
			'woocommerce_product_set_stock_status',
			$this->product_sync,
			'save_or_update_product_status',
			12,
			3
		);
	}

	private function define_historical_sync_commands() {
		if ( ! $this->is_configured() || ! $this->is_connected() ) {
			return;
		}

		$this->loader->add_action(
			'activecampaign_for_woocommerce_admin_sync_single_order_historical',
			$this->historical_sync,
			'run_sync',
			5,
			2
		);

		$this->loader->add_action(
			ACTIVECAMPAIGN_FOR_WOOCOMMERCE_RUN_HISTORICAL_RECUR,
			$this->historical_sync,
			'run_sync',
			5,
			2
		);

		$this->loader->add_action(
			ACTIVECAMPAIGN_FOR_WOOCOMMERCE_RUN_HISTORICAL_RECUR,
			$this->historical_sync,
			'run_historical_sync_contacts',
			6,
			2
		);

		$this->loader->add_action(
			'activecampaign_for_woocommerce_prep_historical_data',
			$this->historical_sync,
			'prep_data',
			1,
			2
		);

		$this->loader->add_action(
			'add_meta_boxes',
			$this->admin_order_page,
			'order_edit_meta_box',
			20,
			3
		);

		$this->loader->add_action(
			'activecampaign_for_woocommerce_ready_existing_historical_data',
			$this->historical_sync,
			'ready_existing_data',
			1
		);

		$this->loader->add_action(
			'activecampaign_for_woocommerce_run_historical_sync_contacts',
			$this->historical_sync,
			'prep_historical_sync_contacts',
			3,
			2
		);

		$this->loader->add_action(
			ACTIVECAMPAIGN_FOR_WOOCOMMERCE_RUN_HISTORICAL_SYNC_NAME,
			$this->historical_sync,
			'run_sync',
			1,
			2
		);

		$this->loader->add_action(
			ACTIVECAMPAIGN_FOR_WOOCOMMERCE_RUN_HISTORICAL_SYNC_NAME,
			$this->historical_sync,
			'run_validation',
			1,
			2
		);

		$this->loader->add_action(
			'wp_ajax_activecampaign_for_woocommerce_schedule_bulk_historical_sync',
			$this->admin,
			'schedule_bulk_historical_sync'
		);

		$this->loader->add_action(
			'wp_ajax_activecampaign_for_woocommerce_check_historical_sync_status',
			$this->admin,
			'check_historical_sync_status'
		);

		$this->loader->add_action(
			'wp_ajax_activecampaign_for_woocommerce_cancel_historical_sync',
			$this->admin,
			'stop_historical_sync'
		);

		$this->loader->add_action(
			'wp_ajax_activecampaign_for_woocommerce_pause_historical_sync',
			$this->admin,
			'stop_historical_sync'
		);

		$this->loader->add_action(
			'wp_ajax_activecampaign_for_woocommerce_reset_historical_sync',
			$this->admin,
			'reset_historical_sync'
		);
	}

	private function define_subscription_commands() {
		// Do not run if subscription is disabled or not installed
		if ( function_exists( 'is_plugin_active' ) && ! is_plugin_active( 'woocommerce-subscriptions/woocommerce-subscriptions.php' ) ) {
			return;
		}

		$admin_subscription_page = new Admin_Subscription_Page();

		$this->loader->add_action(
			'add_meta_boxes',
			$admin_subscription_page,
			'subscription_edit_meta_box',
			20,
			3
		);

		$this->loader->add_action(
			'activecampaign_for_woocommerce_admin_sync_single_subscription_active',
			$this->new_subscription_sync,
			'execute',
			1,
			2
		);

		$this->loader->add_action(
			'activecampaign_for_woocommerce_admin_sync_single_subscription_historical',
			$this->new_subscription_sync,
			'execute_one_historical',
			5,
			2
		);

		// This is for new subscriptions. It is working.
		$this->loader->add_action(
			ACTIVECAMPAIGN_FOR_WOOCOMMERCE_RUN_NEW_ORDER_SYNC_NAME,
			$this->new_subscription_sync,
			'execute',
			2,
			2
		);

		$this->loader->add_action(
			'activecampaign_for_woocommerce_update_subscription',
			$this->new_subscription_sync,
			'execute',
			2,
			2
		);

		$this->loader->add_action(
			'woocommerce_subscription_status_updated',
			$this->subscription_events,
			'execute_woocommerce_subscription_status_updated',
			2,
			3
		);

		$this->loader->add_action(
			'activecampaign_for_woocommerce_route_order_update_to_subscription',
			$this->subscription_events,
			'execute_woocommerce_subscription_status_updated',
			2,
			3
		);

		$this->loader->add_action(
			'woocommerce_scheduled_subscription_trial_end',
			$this->subscription_events,
			'execute_woocommerce_scheduled_subscription_trial_end',
			2,
			1
		);

		$this->loader->add_action(
			'woocommerce_checkout_subscription_created',
			$this->subscription_events,
			'execute_woocommerce_checkout_subscription_created',
			2,
			3
		);

		$this->loader->add_action(
			'wp_ajax_activecampaign_for_woocommerce_run_single_subscription_sync',
			$admin_subscription_page,
			'ac_ajax_sync_single_record',
			1
		);

		// If a subscription is incorrectly listed as an order send it to this event
		$this->loader->add_action(
			'activecampaign_for_woocommerce_miscat_order_to_subscription',
			$this->subscription_events,
			'trigger_order_to_subscription',
			2,
			2
		);

		// $this->loader->add_action(
		// 'woocommerce_subscription_payment_complete',
		// $this->subscription_events,
		// 'execute_woocommerce_subscription_payment_complete'
		// );

		// $this->loader->add_action(
		// 'subscriptions_created_for_order',
		// $this->subscription_events,
		// 'execute_subscription_created_for_order'
		// );
	}
	/**
	 * Registers commands related to the public-facing portion of the WordPress site with
	 * action hooks.
	 *
	 * @since 1.2.1
	 * @access private
	 */
	private function define_public_commands() {

		if ( ! $this->is_configured() ) {
			return;
		}

		// Order checkout finished, order created, transition cart to order
		$this->loader->add_action(
			'woocommerce_checkout_create_order',
			$this->cart_events,
			'cart_to_order_transition'
		);

		$this->define_subscription_commands();

		// custom hook for hourly abandoned cart
		$this->loader->add_action(
			'activecampaign_for_woocommerce_cart_updated_recurring_event',
			$this->run_abandonment_sync_command,
			'abandoned_cart_hourly_task'
		);
	}

	/**
	 * Register all of the hooks related to the admin area functionality
	 * of the plugin.
	 *
	 * @throws Exception Thrown when Container definitions are missing.
	 * @since    1.0.0
	 * @access   private
	 */
	private function define_admin_hooks() {
		$this->loader->add_action(
			'admin_enqueue_scripts',
			$this->admin,
			'enqueue_styles_scripts'
		);

		// add menu item to bottom of WooCommerce menu
		$this->loader->add_action(
			'admin_menu',
			$this->admin,
			'add_admin_page',
			10
		);

		$this->loader->add_action(
			'admin_menu',
			$this->admin,
			'add_admin_entitlements',
			11
		);

		$this->loader->add_action(
			'admin_post_' . ACTIVECAMPAIGN_FOR_WOOCOMMERCE_PLUGIN_NAME_SNAKE . '_settings',
			$this->admin,
			'handle_settings_post'
		);

		$this->loader->add_filter(
			'plugin_action_links_' . ACTIVECAMPAIGN_FOR_WOOCOMMERCE_PLUGIN_BASE_NAME,
			$this->admin,
			'add_plugin_settings_link'
		);

		$this->loader->add_action(
			'rest_api_init',
			$this->admin,
			'active_campaign_register_settings_api',
			1
		);

		$disable_notice = 0;
		if ( get_option( 'activecampaign_for_woocommerce_dismiss_error_notice' ) ) {
			$dismiss_setting = json_decode(
				get_option( 'activecampaign_for_woocommerce_dismiss_error_notice' ),
				'array'
			);
			$user_id         = get_current_user_id();

			if ( isset( $dismiss_setting[ $user_id ] ) && 1 === $dismiss_setting[ $user_id ] ) {
				$disable_notice = 1;
			}
		}

		if ( ! $disable_notice ) {
			$this->loader->add_action(
				'admin_notices',
				$this->admin,
				'error_admin_notice'
			);
		}

		$disable_plugin_notice = 0;
		if ( get_option( 'activecampaign_for_woocommerce_dismiss_plugin_notice' ) ) {
			$notice_setting = json_decode(
				get_option( 'activecampaign_for_woocommerce_dismiss_plugin_notice' ),
				'array'
			);
			$user_id        = get_current_user_id();

			if ( isset( $notice_setting[ $user_id ] ) && 1 === $notice_setting[ $user_id ] ) {
				$disable_plugin_notice = 1;
			}
		}

		if ( ! $this->is_configured() ) {
			if ( ! $disable_plugin_notice ) {
				$this->loader->add_filter(
					'admin_notices',
					$this->admin,
					'please_configure_plugin_notice',
					10,
					1
				);
			}
		}

		if ( $this->is_connected() ) {
			$this->loader->add_action(
				'activecampaign_for_woocommerce_run_manual_abandonment_sync',
				$this->run_abandonment_sync_command,
				'abandoned_cart_manual_run'
			);

			$this->loader->add_action(
				'activecampaign_for_woocommerce_run_force_row_abandonment_sync',
				$this->run_abandonment_sync_command,
				'force_sync_row'
			);

			$this->loader->add_action(
				'activecampaign_for_woocommerce_run_manual_abandonment_delete',
				$this->run_abandonment_sync_command,
				'abandoned_cart_manual_delete'
			);
		}
	}

	/**
	 * Defines the admin ajax calls
	 */
	public function define_admin_ajax_hooks() {
		$this->loader->add_action(
			'wp_ajax_api_test',
			$this->admin,
			'handle_api_test'
		);

		$this->loader->add_action(
			'wp_ajax_activecampaign_for_woocommerce_dismiss_error_notice',
			$this->admin,
			'update_dismiss_error_notice_option'
		);

		$this->loader->add_action(
			'wp_ajax_activecampaign_for_woocommerce_load_connection_block',
			$this->admin,
			'load_connection_block'
		);

		$this->loader->add_action(
			'wp_ajax_activecampaign_for_woocommerce_dismiss_plugin_notice',
			$this->admin,
			'update_dismiss_plugin_notice_option'
		);

		$this->loader->add_action(
			'wp_ajax_activecampaign_for_woocommerce_clear_error_log',
			$this->admin,
			'clear_error_logs'
		);

		$this->loader->add_action(
			'wp_ajax_activecampaign_for_woocommerce_clear_all_settings',
			$this->admin,
			'handle_clear_plugin_settings'
		);

		// This is for repair connection button
		$this->loader->add_action(
			'wp_ajax_activecampaign_for_woocommerce_reset_connection_id',
			$this->admin,
			'handle_reset_connection_id'
		);

		$this->loader->add_action(
			'wp_ajax_activecampaign_for_woocommerce_create_connection',
			$this->admin,
			'add_new_connection'
		);

		$this->loader->add_action(
			'wp_ajax_activecampaign_for_woocommerce_update_connection',
			$this->admin,
			'update_existing_connection'
		);

		$this->loader->add_action(
			'wp_ajax_activecampaign_for_woocommerce_select_connection',
			$this->admin,
			'select_connection'
		);

		$this->loader->add_action(
			'wp_ajax_activecampaign_for_woocommerce_delete_connection',
			$this->admin,
			'delete_existing_connection'
		);

		$this->loader->add_action(
			'wp_ajax_activecampaign_for_woocommerce_resync_features',
			$this->admin,
			'clear_entitlements_cache'
		);

		// Anything below this is disabled if the plugin is not configured
		if ( ! $this->is_configured() || ! $this->is_connected() ) {
			return;
		}

		$this->loader->add_action(
			'wp_ajax_activecampaign_for_woocommerce_manual_abandonment_sync',
			$this->admin,
			'handle_abandon_cart_sync'
		);

		$this->loader->add_action(
			'wp_ajax_activecampaign_for_woocommerce_delete_abandoned_cart_row',
			$this->admin,
			'handle_abandon_cart_delete'
		);

		$this->loader->add_action(
			'wp_ajax_activecampaign_for_woocommerce_sync_abandoned_cart_row',
			$this->admin,
			'handle_abandon_cart_force_row_sync'
		);

		$this->loader->add_action(
			'wp_ajax_activecampaign_for_woocommerce_schedule_product_sync',
			$this->admin,
			'run_product_sync'
		);

		$this->loader->add_action(
			'wp_ajax_activecampaign_for_woocommerce_check_product_sync_status',
			$this->admin,
			'check_product_sync_status'
		);

		$this->loader->add_action(
			'wp_ajax_activecampaign_for_woocommerce_cancel_product_sync',
			$this->product_sync,
			'handle_cancel_sync',
			1,
			1
		);

		$this->loader->add_action(
			'wp_ajax_activecampaign_for_woocommerce_reset_product_sync',
			$this->product_sync,
			'handle_reset_sync_status',
			1,
			1
		);

		$this->loader->add_action(
			'wp_ajax_activecampaign_for_woocommerce_run_single_record_sync',
			$this->admin_order_page,
			'ac_ajax_sync_single_record',
			1
		);

		$this->loader->add_action(
			'wp_ajax_activecampaign_for_woocommerce_create_status_mapping',
			$this->admin,
			'handle_status_mapping_actions',
			1
		);

		$this->loader->add_action(
			'wp_ajax_activecampaign_for_woocommerce_delete_status_mapping',
			$this->admin,
			'handle_status_mapping_actions',
			1
		);
	}

	/**
	 * Register all of the hooks related to the public-facing functionality
	 * of the plugin.
	 *
	 * @throws Exception Thrown when Container definitions are missing.
	 * @since    1.0.0
	 * @access   private
	 */
	private function define_public_hooks() {
		// end this function if not configured
		if ( ! $this->is_configured() ) {
			return;
		}

		/**
		 * If the site admin has not yet configured their plugin, bail out before
		 * registering any public commands since they will not work without the
		 * plugin being configured.
		 */

		$ops = $this->admin->get_local_settings();

		$this->loader->add_action(
			'wp_enqueue_scripts',
			$this->public,
			'enqueue_styles_scripts'
		);

		// Verify the checkbox should display
		if (
			$ops['checkbox_display_option']
			&& $ops['optin_checkbox_text']
			&& ! empty( $ops['checkbox_display_option'] )
			&& ! empty( $ops['optin_checkbox_text'] )
		) {
			if ( 'not_visible' !== $ops['checkbox_display_option'] ) {
				$this->loader->add_action(
					'woocommerce_checkout_create_order',
					$this->add_accepts_marketing_to_customer_meta_command,
					'execute',
					90
				);

				// Add the checkbox to the billing form
				$this->loader->add_action(
					'woocommerce_after_checkout_billing_form',
					$this->public,
					'handle_woocommerce_checkout_form',
					5
				);

				// this hook is a fallback method in case we can't find the billing form hook
				$this->loader->add_action(
					'woocommerce_after_checkout_form',
					$this->public,
					'handle_woocommerce_checkout_form',
					5
				);
			}
		} else {
			$this->logger->debug( 'Checkbox actions cannot be run. checkbox_display_option and/or optin_checkbox_text are not defined or not available in your theme.' );
		}

	}

	/**
	 * Defines the public ajax calls
	 */
	public function define_public_ajax_hooks() {
		if ( ! $this->is_configured() ) {
			return;
		}

		$this->loader->add_action(
			'wp_ajax_activecampaign_for_woocommerce_cart_sync_guest',
			$this->sync_guest_abandoned_cart_command,
			'execute'
		);

		$this->loader->add_action(
			'wp_ajax_nopriv_activecampaign_for_woocommerce_cart_sync_guest',
			$this->sync_guest_abandoned_cart_command,
			'execute'
		);
	}

	/**
	 * Run the loader to execute all of the hooks with WordPress.
	 *
	 * Load the dependencies, define the locale, and set the hooks for the admin area and
	 * the public-facing side of the site.
	 *
	 * @throws Exception Thrown when Container definitions are missing.
	 * @since    1.0.0
	 */
	public function run() {
		if ( ! $this->logger ) {
			$this->logger = new Logger();
		}
		$this->set_locale();
		$this->plugin_updates();

		$this->define_event_hooks();

		$this->define_admin_commands();
		$this->define_admin_hooks();
		$this->define_admin_ajax_hooks();

		$this->define_public_commands();
		$this->define_public_hooks();
		$this->define_public_ajax_hooks();

		$this->loader->run();
	}

	/**
	 * The name of the plugin used to uniquely identify it within the context of
	 * WordPress and to define internationalization functionality.
	 *
	 * @return    string    The name of the plugin.
	 * @since     1.0.0
	 */
	public function get_plugin_name() {
		return $this->plugin_name;
	}

	/**
	 * The reference to the class that orchestrates the hooks with the plugin.
	 *
	 * @return    Activecampaign_For_Woocommerce_Loader    Orchestrates the hooks of the plugin.
	 * @since     1.0.0
	 */
	public function get_loader() {
		return $this->loader;
	}

	/**
	 * Retrieve the version number of the plugin.
	 *
	 * @return    string    The version number of the plugin.
	 * @since     1.0.0
	 */
	public function get_version() {
		return $this->version;
	}

	/**
	 * Verify our plugin is configured
	 *
	 * @return bool
	 */
	private function is_configured() {
		$ops = $this->admin->get_local_settings();
		if ( ! $ops ||
			 ! $ops['api_key'] ||
			 ! $ops['api_url']
		) {
			return false;
		}

		return true;
	}

	/**
	 * Verify our plugin has connection_id
	 *
	 * @return bool
	 */
	private function is_connected() {
		if (
			! isset( $this->admin->get_connection_storage()['connection_id'] ) ||
			empty( $this->admin->get_connection_storage()['connection_id'] )
		) {
			return false;
		}
		return true;
	}
}
