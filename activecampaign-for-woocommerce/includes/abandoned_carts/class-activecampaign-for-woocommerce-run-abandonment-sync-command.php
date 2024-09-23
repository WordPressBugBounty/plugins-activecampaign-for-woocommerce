<?php

/**
 * The file that runs the abandonment synchronization for abandoned carts.
 *
 * @link       https://www.activecampaign.com/
 * @since      1.3.2
 *
 * @package    Activecampaign_For_Woocommerce
 */

use Activecampaign_For_Woocommerce_Admin as Admin;
use Activecampaign_For_Woocommerce_Ecom_Customer as Ecom_Customer;
use Activecampaign_For_Woocommerce_Ecom_Customer_Repository as Ecom_Customer_Repository;
use Activecampaign_For_Woocommerce_Ecom_Order as Ecom_Order;
use Activecampaign_For_Woocommerce_Ecom_Order_Repository as Ecom_Order_Repository;
use Activecampaign_For_Woocommerce_Logger as Logger;
use Activecampaign_For_Woocommerce_Customer_Utilities as Customer_Utilities;
use Activecampaign_For_Woocommerce_Synced_Status_Interface as Synced_Status;
use AcVendor\Brick\Money\Money;

/**
 * Sync the abandoned carts and their products to ActiveCampaign.
 *
 * @since      1.0.0
 * @package    Activecampaign_For_Woocommerce
 * @subpackage Activecampaign_For_Woocommerce/includes/commands
 * @author     acteamintegrations <team-integrations@activecampaign.com>
 */
class Activecampaign_For_Woocommerce_Run_Abandonment_Sync_Command implements Synced_Status {
	use Activecampaign_For_Woocommerce_Data_Validation,
		Activecampaign_For_Woocommerce_Synced_Status_Handler,
		Activecampaign_For_Woocommerce_Abandoned_Cart_Utilities,
		Activecampaign_For_Woocommerce_Order_Line_Item_Gathering;

	/**
	 * The logger interface.
	 *
	 * @var Logger
	 */
	private $logger;

	/**
	 * The Admin object
	 *
	 * @var Activecampaign_For_Woocommerce_Admin
	 */
	private $admin;

	/**
	 * The Ecom Order Repo
	 *
	 * @var Activecampaign_For_Woocommerce_Ecom_Order_Repository
	 */
	private $order_repository;

	/**
	 * The Ecom Customer Repo
	 *
	 * @var Activecampaign_For_Woocommerce_Ecom_Customer_Repository
	 */
	private $customer_repository;

	/**
	 * Customer utility class.
	 *
	 * @since 1.5.0
	 * @var Customer_Utilities The customer utility class.
	 */
	private $customer_utilities;

	/**
	 * The connection id.
	 *
	 * @since 1.7.0
	 * @var Connection_Id The connection id.
	 */
	private $connection_id;

	/**
	 * The abandoned date.
	 *
	 * @since 2.4.5
	 * @var DateTime The abandoned date.
	 */
	private $abandoned_date;

	/**
	 * The time set in settings for expiration..
	 *
	 * @since 2.4.5
	 * @var String|int The expiration time.
	 */
	private $expire_time;

	/**
	 * Activecampaign_For_Woocommerce_Update_Cart_Command constructor.
	 *
	 * @param     Activecampaign_For_Woocommerce_Admin|null         $admin     The admin object.
	 * @param     Logger                                            $logger     The logger interface.
	 * @param     Ecom_Customer_Repository|null                     $customer_repository     The Ecom Customer Repo.
	 * @param     Ecom_Order_Repository                             $order_repository     The Ecom Order Repo.
	 * @param     Activecampaign_For_Woocommerce_Customer_Utilities $customer_utilities The customer utility class.
	 */
	public function __construct(
		Admin $admin,
		Logger $logger,
		Ecom_Customer_Repository $customer_repository,
		Ecom_Order_Repository $order_repository,
		Customer_Utilities $customer_utilities
	) {
		$this->admin               = $admin;
		$this->customer_repository = $customer_repository;
		$this->order_repository    = $order_repository;
		$this->customer_utilities  = $customer_utilities;

		if ( ! $logger ) {
			$this->logger = new Logger();
		} else {
			$this->logger = $logger;
		}

		$this->validate_connection_id();
	}

	/**
	 * The hourly task that runs via hook.
	 * This initializes via Activecampaign_For_Woocommerce_Save_Abandoned_Cart_Command
	 */
	public function abandoned_cart_hourly_task() {
		if ( ! $this->logger ) {
			$this->logger = new Logger();
		}

		set_transient( 'acforwc_abandoned_task_hook', wp_date( DATE_ATOM ), 604800 );

		$now      = date_create( 'NOW' );
		$last_run = get_option( 'activecampaign_for_woocommerce_abandoned_cart_last_run' );

		if ( false !== $last_run ) {
			$interval         = date_diff( $now, $last_run );
			$interval_minutes = $interval->format( '%i' );
		} else {
			$interval_minutes = 0;
		}

		if ( false === $last_run || 5 <= $interval_minutes ) {
			do_action( 'activecampaign_for_woocommerce_verify_tables' );

			$this->run_abandoned_carts();
		}
	}

	private function run_abandoned_carts() {
		$cart_count = 0;

		// Process second failure records
		$abandoned_carts = $this->get_all_abandoned_carts_from_table( self::STATUS_ABANDONED_CART_FAILED_2 );
		if ( ! empty( $abandoned_carts ) ) {
			$this->process_abandoned_carts_per_record( $abandoned_carts ); // Process this group
			$cart_count += count( $abandoned_carts );
		}

		// Process first failure records
		$abandoned_carts = $this->get_all_abandoned_carts_from_table( self::STATUS_ABANDONED_CART_FAILED_WAIT );
		if ( ! empty( $abandoned_carts ) ) {
			$this->process_abandoned_carts_per_record( $abandoned_carts ); // Process this group
			$cart_count += count( $abandoned_carts );
		}

		// Check for legacy abandoned carts
		$abandoned_carts = $this->get_all_abandoned_carts_from_table_legacy();
		if ( ! empty( $abandoned_carts ) ) {
			$this->process_abandoned_carts_per_record( $abandoned_carts ); // Process this group
			$cart_count += count( $abandoned_carts );
		} else {
			$this->logger->debug_excess( 'Abandoned cart hourly task: No legacy abandoned carts to process...' );
		}

		// Check for abandoned carts new value
		$abandoned_carts = $this->get_all_abandoned_carts_from_table();
		if ( ! empty( $abandoned_carts ) ) {
			$this->process_abandoned_carts_per_record( $abandoned_carts ); // Process this group
			$cart_count += count( $abandoned_carts );
		} else {
			$this->logger->debug_excess( 'Abandoned cart hourly task: No abandoned carts to process...' );
		}

		$this->clean_old_synced_abandoned_carts();
		$this->clean_all_old_abandoned_carts();

		return $cart_count;
	}
	/**
	 * The manual run of the hourly task.
	 */
	public function abandoned_cart_manual_run() {
		if ( ! $this->logger ) {
			$this->logger = new Logger();
		}
		do_action( 'activecampaign_for_woocommerce_verify_tables' );

		// Check for abandoned carts
		$cart_count = $this->run_abandoned_carts();

		if ( isset( $cart_count ) && ! empty( $cart_count ) && $cart_count > 0 ) {
			wp_send_json_success( 'Finished sync of abandoned cart. Processed ' . $cart_count . ' carts.' );
		} else {
			wp_send_json_success( 'No abandoned carts to process.' );
		}

		wp_send_json_error( 'The abandoned cart process did not return a success or fail message. Nothing happened and that is not normal.' );
	}

	/**
	 * Performs a manual delete of a row from the abandoned cart table.
	 *
	 * @param string $row_id The row id.
	 */
	public function abandoned_cart_manual_delete( $row_id ) {
		do_action( 'activecampaign_for_woocommerce_verify_tables' );

		if ( $this->delete_abandoned_cart_by_filter( 'id', $row_id ) ) {
			wp_send_json_success( 'Row deleted.' );
		} else {
			wp_send_json_error( 'There was an issue deleting the row.' );
		}
	}

	/**
	 * Forces the sync of a specific abandoned cart row manually.
	 *
	 * @param     int $id     The abandoned cart row id.
	 */
	public function force_sync_row( $id ) {
		if ( ! $this->logger ) {
			$this->logger = new Logger();
		}

		do_action( 'activecampaign_for_woocommerce_verify_tables' );

		$abandoned_cart = $this->get_abandoned_cart_by_row_id( $id );

		if ( ! empty( $abandoned_cart ) ) {
			if ( $this->process_single_abandoned_cart_record( $abandoned_cart[0] ) ) {
				wp_send_json_success( 'Record synced' );
			} else {
				wp_send_json_error( 'Record failed to sync' );
			}
		} else {
			$this->logger->warning(
				'Activecampaign_For_Woocommerce_Run_Abandonment_Sync_Command [force_sync_row]: No abandoned carts found by id',
				[
					'id'             => $id,
					'abandoned_cart' => $abandoned_cart,
					'ac_code'        => 'RASC_251',
				]
			);
			wp_send_json_error( 'Could not find the abandoned cart row ' . $id . ' in the database' );
		}
	}

	/**
	 * Get all active carts.
	 *
	 * @return mixed Whether or not there are abandoned carts.
	 * @throws Throwable Thrown message.
	 */
	private function get_all_abandoned_carts_from_table( $synced_to_ac = self::STATUS_ABANDONED_CART_UNSYNCED ) {
		global $wpdb;

		// default is 1 hour abandon cart expiration
		$this->expire_time = 1;

		// Get the expire time period from the db
		$activecampaign_for_woocommerce_settings = get_option( ACTIVECAMPAIGN_FOR_WOOCOMMERCE_DB_SETTINGS_NAME );
		$activecampaign_for_woocommerce_settings = stripslashes_deep( $activecampaign_for_woocommerce_settings );

		if ( isset( $activecampaign_for_woocommerce_settings['abcart_wait'] ) && ! empty( $activecampaign_for_woocommerce_settings['abcart_wait'] ) ) {
			$this->expire_time = $activecampaign_for_woocommerce_settings['abcart_wait'];
		}

		$expire_datetime = new DateTime( 'now -' . $this->expire_time . ' hours', new DateTimeZone( 'UTC' ) );

		try {
			// Get the expired carts from our table
			// phpcs:disable
			$abandoned_carts = $wpdb->get_results( '
				SELECT
					id, synced_to_ac, customer_ref_json, cart_ref_json, cart_totals_ref_json, removed_cart_contents_ref_json, activecampaignfwc_order_external_uuid, last_access_time, abandoned_date,
					ADDTIME(last_access_time, "' . $this->expire_time . ':00:00") as calc_abandoned_date
				FROM
					`' . $wpdb->prefix . ACTIVECAMPAIGN_FOR_WOOCOMMERCE_TABLE_NAME . '`
				WHERE
					(
						last_access_time < "' . $expire_datetime->format( 'Y-m-d H:i:s' ) . '"
						OR last_access_time < str_to_date("' . $expire_datetime->format( 'Y-m-d H:i:s' ) . '", "Y-m-d H:i:s") 
					)
					AND order_date IS NULL
					AND synced_to_ac = ' . $synced_to_ac . ';'
			);
			// phpcs:enable

			if ( $wpdb->last_error ) {
				$this->logger->error(
					'A database error was encountered while getting results for abandoned cart records.',
					[
						'wpdb_last_error'  => $wpdb->last_error,
						'wpdb_last_query'  => $wpdb->last_query,
						'suggested_action' => 'Please verify that the query is correct and cron process has read access to the ' . ACTIVECAMPAIGN_FOR_WOOCOMMERCE_TABLE_NAME . ' table',
						'ac_code'          => 'RASC_301',
					]
				);
			}

			$this->clean_old_synced_abandoned_carts();
			$this->clean_all_old_abandoned_carts();

			if ( ! empty( $abandoned_carts ) ) {
				return $abandoned_carts; // abandoned carts found
			} else {
				return false; // no abandoned carts to process
			}
		} catch ( Throwable $t ) {
			$this->logger->error(
				'An error was thrown while preparing or getting abandoned cart results.',
				[
					'message' => $t->getMessage(),
					'ac_code' => 'RASC_320',
					'trace'   => $this->logger->clean_trace( $t->getTrace() ),
				]
			);
		}
	}

	/**
	 * Get all active carts.
	 *
	 * @deprecated This will need to be removed after a few versions as we moved from a common unsynced value of 0 to 20 for abandoned carts specifically.
	 *
	 * @return mixed Whether or not there are abandoned carts.
	 * @throws Throwable Thrown message.
	 */
	private function get_all_abandoned_carts_from_table_legacy() {
		global $wpdb;

		// default is 1 hour abandon cart expiration
		$this->expire_time = 1;

		// Get the expire time period from the db
		$activecampaign_for_woocommerce_settings = get_option( ACTIVECAMPAIGN_FOR_WOOCOMMERCE_DB_SETTINGS_NAME );
		$activecampaign_for_woocommerce_settings = stripslashes_deep( $activecampaign_for_woocommerce_settings );

		if ( isset( $activecampaign_for_woocommerce_settings['abcart_wait'] ) && ! empty( $activecampaign_for_woocommerce_settings['abcart_wait'] ) ) {
			$this->expire_time = $activecampaign_for_woocommerce_settings['abcart_wait'];
		}

		$expire_datetime = new DateTime( 'now -' . $this->expire_time . ' hours', new DateTimeZone( 'UTC' ) );

		try {
			// Get the expired carts from our table
			// phpcs:disable
			$abandoned_carts = $wpdb->get_results(
				'SELECT id, synced_to_ac, customer_ref_json, cart_ref_json, cart_totals_ref_json, removed_cart_contents_ref_json, activecampaignfwc_order_external_uuid, last_access_time, ADDTIME(last_access_time, "' . $this->expire_time . ':00:00") as calc_abandoned_date 
					FROM
						`' . $wpdb->prefix . ACTIVECAMPAIGN_FOR_WOOCOMMERCE_TABLE_NAME . '`
					WHERE
						( last_access_time < "' . $expire_datetime->format( 'Y-m-d H:i:s' ) . '"
						OR last_access_time < str_to_date("' . $expire_datetime->format( 'Y-m-d H:i:s' ) . '", "Y-m-d H:i:s") )
						AND order_date IS NULL
						AND synced_to_ac = 0;'
			);
			// phpcs:enable

			if ( $wpdb->last_error ) {
				$this->logger->notice(
					'Abandonment sync: There was an error getting results for abandoned cart records.',
					[
						'wpdb_last_error' => $wpdb->last_error,
					]
				);
			}

			$this->clean_all_old_abandoned_carts();

			if ( ! empty( $abandoned_carts ) ) {
				// abandoned carts found
				return $abandoned_carts;
			} else {
				// no abandoned carts
				return false;
			}
		} catch ( Throwable $t ) {
			$this->logger->notice(
				'Abandonment Sync: There was an error with preparing or getting abandoned cart results.',
				[
					'message' => $t->getMessage(),
					'trace'   => $this->logger->clean_trace( $t->getTrace() ),
				]
			);
		}
	}

	/**
	 * Validates the connection ID and sets it if it happens to be unset.
	 *
	 * @return bool
	 */
	private function validate_connection_id() {
		$storage = get_option( ACTIVECAMPAIGN_FOR_WOOCOMMERCE_DB_CONNECTION_STORAGE_NAME );

		if (
			(
				! isset( $this->connection_id ) ||
				empty( $this->connection_id )
			) &&
			isset( $storage['connection_id'] ) &&
			! empty( $storage['connection_id'] )
		) {
			$this->connection_id = $storage['connection_id'];
		}

		if ( ! isset( $this->connection_id ) || empty( $this->connection_id ) ) {
			$this->logger->debug_calls(
				'Abandoned cart was not able to establish a valid connection ID',
				[
					'connection_id'       => $this->connection_id,
					'conneciton_settings' => $storage,
				]
			);

			return false;
		}

		return true;
	}

	/**
	 * Process cycle an abandoned carts group of records.
	 *
	 * @param     Array $abandoned_carts     Abandoned carts found in the database.
	 */
	private function process_abandoned_carts_per_record( $abandoned_carts ) {
		// set each cart as though it's the existing active cart
		foreach ( $abandoned_carts as $abc_order ) {
			$this->process_single_abandoned_cart_record( $abc_order ); // for group of records
		}
	}

	/**
	 * Process the abandoned cart record and sync to AC.
	 *
	 * @param object $abc_order The abandoned cart cart order.
	 *
	 * @return bool The response.
	 */
	private function process_single_abandoned_cart_record( $abc_order ) {
		global $wpdb;

		$now = date_create( 'NOW' );
		update_option( 'activecampaign_for_woocommerce_abandoned_cart_last_run', $now );

		// parse the values for each cart
		$synced_to_ac = false;
		$customer     = json_decode( $abc_order->customer_ref_json, false );
		$cart         = json_decode( $abc_order->cart_ref_json, false );
		$cart_totals  = json_decode( $abc_order->cart_totals_ref_json, false );
		// $removed_cart_contents                 = json_decode( $ab_order->removed_cart_contents_ref_json, false );
		$activecampaignfwc_order_external_uuid = $abc_order->activecampaignfwc_order_external_uuid;

		// Get or register our contact
		$customer_ac = $this->find_or_create_ac_customer( $customer );

		// Step 1: Check if we have customer in AC & create or update
		if ( ! isset( $customer_ac ) || empty( $customer_ac ) ) {
			$this->logger->warning(
				'Abandonment sync: Process single abandon cart - Could not find or create customer...',
				[
					'customer id'         => isset( $customer->id ) ? $customer->id : null,
					'customer first name' => isset( $customer->first_name ) ? $customer->first_name : null,
					'customer last name'  => isset( $customer->last_name ) ? $customer->last_name : null,
				]
			);

			$this->mark_abandoned_cart_failed( $abc_order );
			return false;
		}

		try {
			$product_data = $this->build_abandoned_products( $cart );

			if ( ( isset( $product_data['products'] ) ) && count( $product_data['products'] ) > 0 ) {
				$products = $product_data['products'];
			} else {
				$this->logger->warning(
					'No product data found in abandoned cart',
					[
						'cart'         => $abc_order,
						'product_data' => $product_data,
					]
				);
				$this->mark_abandoned_cart_failed( $abc_order );

				return false;
			}

			$item_count_total = 0;
			if ( isset( $product_data['item_count_total'] ) ) {
				$item_count_total = $product_data['item_count_total'];
			}
		} catch ( Throwable $t ) {
			$this->logger->debug(
				'Products could not be built for an abandoned cart.',
				[
					'cart' => $abc_order,
				]
			);

			return false;
		}

		// Step 2: Let's make the abandoned order for AC
		$ecom_order = new Ecom_Order();

		try {
			$externalcheckout_id = $this->generate_externalcheckoutid( $customer->id, $customer->email, $activecampaignfwc_order_external_uuid );

			if ( ! $this->validate_connection_id() ) {
				$this->logger->notice(
					'Abandoned cart could not find valid connection ID or the cart. Please repair your connection to ActiveCampaign.',
					[
						'connection_id' => $this->connection_id,
					]
				);

				$this->mark_abandoned_cart_failed( $abc_order );
				return false;
			}

			$ecom_order->set_customerid( $customer_ac->get_id() );
			$ecom_order->set_connectionid( $this->connection_id );
			$ecom_order->set_externalcheckoutid( $externalcheckout_id );
			$ecom_order->set_source( '1' );
			$ecom_order->set_email( $customer->email );
			$ecom_order->set_currency( get_woocommerce_currency() );
			$ecom_order->set_total_price( $this->convert_money_to_cents( $cart_totals->total ) ); // must be in cents
			$ecom_order->set_tax_amount( $this->convert_money_to_cents( $cart_totals->total_tax ) );
			$ecom_order->set_shipping_amount( $this->convert_money_to_cents( $cart_totals->shipping_total ) );
			$ecom_order->set_order_url( wc_get_cart_url() );
			$ecom_order->set_total_products( $item_count_total );

		} catch ( Throwable $t ) {
			$this->logger->warning(
				'Abandonment Sync: Failed to build ecom order.',
				[
					'exception_message' => $t->getMessage(),
					'exception_trace'   => $this->logger->clean_trace( $t->getTrace() ),
				]
			);
		}

		try {
			// Step 3: Add the products to the order
			if ( ! empty( $products ) && count( $products ) > 0 ) {
				array_walk( $products, [ $ecom_order, 'push_order_product' ] );
			} else {
				$this->logger->warning(
					'Abandonment Sync: Failed to add products to ecom order.',
					[
						'email' => isset( $customer->email ) ? $customer->email : null,
					]
				);
			}
		} catch ( Throwable $t ) {
			$this->logger->warning(
				'Abandonment Sync: Failed to add products to ecom order.',
				[
					'exception_message' => $t->getMessage(),
					'exception_trace'   => $this->logger->clean_trace( $t->getTrace() ),
				]
			);
		}

		try {
			// Try to find the order by it's externalcheckoutid
			$order_ac = $this->order_repository->find_by_externalcheckoutid( $externalcheckout_id );
		} catch ( Throwable $t ) {
			$this->logger->debug(
				'Abandonment Sync: Find order in AC exception. ',
				[
					'exception_message'   => $t->getMessage(),
					'connection_id'       => isset( $this->connection_id ) ? $this->connection_id : null,
					'customer_email'      => isset( $customer->email ) ? $customer->email : null,
					'externalcheckout_id' => $externalcheckout_id,
					'exception_trace'     => $this->logger->clean_trace( $t->getTrace() ),
				]
			);
		}

		// Let's make absolutely sure this is the same record
		if ( isset( $order_ac ) && ! empty( $order_ac->get_id() ) && $externalcheckout_id === $order_ac->get_externalcheckoutid() ) {
			try {

				$ab_datetime = $this->calculate_abandoned_date( $abc_order );

				$ecom_order->set_external_updated_date( $ab_datetime );
				$ecom_order->set_external_created_date( $ab_datetime );
				$ecom_order->set_abandoned_date( $ab_datetime );

				$ecom_order->set_source( 0 );
				$ecom_order->set_id( $order_ac->get_id() );

				$this->logger->debug_excess(
					'Abandonment Sync: This abandoned cart has already been synced to ActiveCampaign and will be updated.',
					[
						'order'                     => self::validate_object( $ecom_order, 'serialize_to_array' ) ? $ecom_order->serialize_to_array() : null,
						'connection_id'             => isset( $this->connection_id ) ? $this->connection_id : null,
						'order externalcheckout_id' => $externalcheckout_id,
						'ac externalcheckout_id'    => self::validate_object( $order_ac, 'get_externalcheckoutid' ) ? $order_ac->get_externalcheckoutid() : null,
						'ac_id'                     => self::validate_object( $order_ac, 'get_id' ) ? $order_ac->get_id() : null,
						'customer_email'            => isset( $customer->email ) ? $customer->email : null,
						'externalcheckout_id'       => $externalcheckout_id,
					]
				);

				$order_ac = $this->order_repository->update( $ecom_order );
			} catch ( Throwable $t ) {
				$this->logger->warning(
					'Abandonment Sync: Order update exception: ',
					[
						'connection_id'       => isset( $this->connection_id ) ? $this->connection_id : null,
						'customer_email'      => isset( $customer->email ) ? $customer->email : null,
						'externalcheckout_id' => $externalcheckout_id,
						'exception_message'   => $t->getMessage(),
						'exception_trace'     => $this->logger->clean_trace( $t->getTrace() ),
					]
				);
			}
		} else {
			try {
				// Order does not exist in AC yet
				// Try to create the new order in AC
				$this->logger->debug(
					'Abandonment Sync: Creating abandoned cart entry in ActiveCampaign: ',
					[
						'order_created' => self::validate_object( $ecom_order, 'serialize_to_array' ) ? wp_json_encode( $ecom_order->serialize_to_array() ) : null,
					]
				);

				$ab_datetime = $this->calculate_abandoned_date( $abc_order );
				$ecom_order->set_abandoned_date( $ab_datetime );
				$ecom_order->set_external_updated_date( $ab_datetime );
				$ecom_order->set_external_created_date( $ab_datetime );

				$order_ac = $this->order_repository->create( $ecom_order );
				$this->logger->debug_calls(
					'Abandoned cart create result',
					[
						'order_ac'         => $order_ac,
						'serialized order' => self::validate_object( $order_ac, 'serialize_to_array' ) ? $order_ac->serialize_to_array() : null,
					]
				);
			} catch ( Throwable $t ) {
				$this->logger->debug(
					'Abandonment Sync: Order creation exception: ',
					[
						'connection_id'       => isset( $this->connection_id ) ? $this->connection_id : null,
						'customer_email'      => isset( $customer->email ) ? $customer->email : null,
						'externalcheckout_id' => $externalcheckout_id,
						'exception_message'   => $t->getMessage(),
						'exception_trace'     => $this->logger->clean_trace( $t->getTrace() ),
					]
				);
			}
		}

		try {
			if (
				isset( $order_ac ) &&
				self::validate_object( $order_ac, 'get_id' ) &&
				! empty( $order_ac->get_id() )
			) {
				$synced_to_ac = true;
			} else {
				if ( ! isset( $order_ac ) || ! self::validate_object( $order_ac, 'get_id' ) || ! empty( $order_ac->get_id() ) ) {
					$this->logger->debug_calls(
						'Abandonment Sync: Abandoned order creation failed: ',
						[
							$order_ac,
						]
					);
				}

				$synced_to_ac = false;
			}
		} catch ( Throwable $t ) {
			$this->logger->debug(
				'Abandonment Sync: Could not read sync ID, record may not have synced to AC: ',
				[
					'connection_id'       => isset( $this->connection_id ) ? $this->connection_id : null,
					'customer_email'      => isset( $customer->email ) ? $customer->email : null,
					'externalcheckout_id' => $externalcheckout_id,
					'exception_message'   => $t->getMessage(),
					'exception_trace'     => $this->logger->clean_trace( $t->getTrace() ),
				]
			);

			$synced_to_ac = false;
		}

		try {
			if ( $synced_to_ac ) {
				// Update the record to show we've synced so we don't sync it again

				$wpdb->update(
					$wpdb->prefix . ACTIVECAMPAIGN_FOR_WOOCOMMERCE_TABLE_NAME,
					[
						'synced_to_ac'   => self::STATUS_ABANDONED_CART_AUTO_SYNCED,
						'abandoned_date' => $this->calculate_abandoned_date( $abc_order ),
						'ac_order_id'    => self::validate_object( $order_ac, 'get_id' ) ? $order_ac->get_id() : null,
						'ac_customer_id' => self::validate_object( $order_ac, 'get_customerid' ) ? $order_ac->get_customerid() : null,
					],
					[
						'id' => $abc_order->id,
					]
				);

				if ( $wpdb->last_error ) {
					$this->logger->notice(
						'A database error was encountered while attempting to update a record in the ' . ACTIVECAMPAIGN_FOR_WOOCOMMERCE_TABLE_NAME . ' table',
						[
							'wpdb_last_error'  => $wpdb->last_error,
							'suggested_action' => 'Please check the message for explanation and contact ActiveCampaign support if the issue repeats.',
							'ac_code'          => 'TSSH_713',
							'order_id'         => $abc_order->id,
						]
					);
				}
			}
		} catch ( Throwable $t ) {
			$this->logger->notice(
				'An exception was encountered while attempting to update a record in the ' . ACTIVECAMPAIGN_FOR_WOOCOMMERCE_TABLE_NAME . ' table',
				[
					'message'             => $t->getMessage(),
					'abandoned_order_id'  => isset( $abc_order->id ) ? $abc_order->id : null,
					'suggested_action'    => 'Please check the message for explanation and contact ActiveCampaign support if the issue repeats.',
					'ac_code'             => 'TSSH_725',
					'externalcheckout_id' => $externalcheckout_id,
					'exception_message'   => $t->getMessage(),
					'exception_trace'     => $this->logger->clean_trace( $t->getTrace() ),
				]
			);
		}

		// This only gets run on a single order
		if ( ! $synced_to_ac ) {

			$this->mark_abandoned_cart_failed( $abc_order );
			return false;
		} else {
			return true;
		}
	}

	/**
	 * Convert money to cents. Different parameters might send different formats.
	 *
	 * @param string|int|bool|float $amount The currency amount.
	 *
	 * @return BigDecimal|int
	 */
	private function convert_money_to_cents( $amount ) {
		if ( empty( $amount ) || is_bool( $amount ) ) {
			return 0;
		}

		$currency = get_woocommerce_currency();
		$dec      = null;
		$cents    = null;

		try {
			$dec = round( $amount, 2 );
		} catch ( Throwable $t ) {
			$this->logger->error(
				'There was an issue rounding the amount to 2 decimals.',
				[
					'message'       => $t->getMessage(),
					'amount_passed' => $amount,
					'stack_trace'   => $this->logger->clean_trace( $t->getTrace() ),
				]
			);
		}

		if ( isset( $dec ) && ! empty( $dec ) ) {
			try {
				// Convert rounded amount to minor "cents" integer
				$cents = Money::of( $dec, $currency )->getMinorAmount()->toInt();
			} catch ( Throwable $t ) {
				$this->logger->error(
					'There was an issue converting rounded amount to cents.',
					[
						'message'       => $t->getMessage(),
						'amount_passed' => $amount,
						'rounded'       => $dec,
						'stack_trace'   => $this->logger->clean_trace( $t->getTrace() ),
					]
				);
			}
		}

		if ( null === $cents ) {
			try {
				// Convert original amount to minor "cents" integer if the previous failed
				$cents = Money::of( $amount, $currency )->getMinorAmount()->toInt();
			} catch ( Throwable $t ) {
				$this->logger->error(
					'There was an issue converting amount to cents.',
					[
						'message'       => $t->getMessage(),
						'amount_passed' => $amount,
						'stack_trace'   => $this->logger->clean_trace( $t->getTrace() ),
					]
				);
			}
		}

		// Cents can never be null, return cents or zero
		if ( null !== $cents ) {
			return $cents;
		}

		return 0;
	}

	private function build_abandoned_products( $cart ) {
		// Get the products set up for the order/cart
		$item_count_total = 0;
		$products         = [];

		foreach ( $cart as $product ) {
			try {
				// One of these two methods will get product_id
				if ( isset( $product->product_id ) ) {
					$product_id = $product->product_id;
				}

				if ( empty( $product_id ) ) {
					$product_id = $product['product_id'];
				}

				$item_count_total += $product->quantity;
				$wc_product        = wc_get_product( $product_id );
				if ( self::validate_object( $wc_product, 'get_data' ) ) {
					$product->data = $wc_product->get_data();
				} else {
					$product->data = null;
				}

				// Create ecom product
				$ecom_product = new Activecampaign_For_Woocommerce_Ecom_Product();
				$ecom_product->set_externalid( $wc_product->get_id() );
				$ecom_product->set_name( $wc_product->get_name() );
				$ecom_product->set_price( $this->convert_money_to_cents( $wc_product->get_price() ) );
				$ecom_product->set_category( $this->get_product_all_categories( $wc_product ) );
				$ecom_product->set_image_url( $this->get_product_image_url_from_wc( $wc_product ) );
				$ecom_product->set_product_url( $this->get_product_url_from_wc( $wc_product ) );
				$ecom_product->set_sku( $this->get_product_sku_from_wc( $wc_product ) );
				$ecom_product->set_quantity( $product->quantity );

				if ( ! empty( $wc_product->get_short_description() ) ) {
					$description = $wc_product->get_short_description();
				} else {
					$description = $wc_product->get_description();
				}

				$ecom_product->set_description( self::clean_description( $description ) );

				$products[] = $ecom_product;
			} catch ( Throwable $t ) {
				$this->logger->error(
					'An exception was thrown while attempting to build the abandoned cart.',
					[
						'exception_message' => $t->getMessage(),
						'suggested_action'  => 'Verify that cart and product data can be accessed by cron.',
						'ac_code'           => 'RASC_793',
						'product_id'        => $product_id,
						'exception_trace'   => $this->logger->clean_trace( $t->getTrace() ),
					]
				);

				return false;
			}
		}

		return [
			'products'         => $products,
			'item_count_total' => $item_count_total,
		];
	}

	/**
	 * Lookup ecom customer record in AC. If it does not exist, create it. This is altered specifically for abandonment.
	 *
	 * @param     WC_Customer $customer     The customer object.
	 *
	 * @return object $customer_ac The customer object from ActiveCampaign.
	 */
	private function find_or_create_ac_customer( $customer ) {
		$customer_ac = null;

		if ( ! $this->validate_connection_id() ) {
			$this->logger->warning(
				'Abandoned cart could not find valid connection ID. Please repair your connection to ActiveCampaign.',
				[
					'connection_id' => $this->connection_id,
					'ac_code'       => 'RASC_825',
				]
			);

			return null;
		}

		try {
			// Try to find the customer in AC
			$customer_ac = $this->customer_repository->find_by_email_and_connection_id( $customer->email, $this->connection_id );
		} catch ( Throwable $t ) {
			$this->logger->debug(
				'Abandonment sync: Find customer exception.',
				[
					'customer_email'      => isset( $customer->email ) ? $customer->email : null,
					'customer_first_name' => isset( $customer->first_name ) ? $customer->first_name : null,
					'customer_last_name'  => isset( $customer->last_name ) ? $customer->last_name : null,
					'connection_id'       => $this->connection_id,
					'exception'           => $t->getMessage(),
					'exception_trace'     => $this->logger->clean_trace( $t->getTrace() ),
				]
			);
		}

		if ( ! $customer_ac ) {
			try {
				// Customer does not exist in AC yet
				// Set up AC customer model
				$new_customer = new Ecom_Customer();

				$new_customer->set_connectionid( $this->connection_id );
				$new_customer->set_email( $customer->email );
				$new_customer->set_first_name( $customer->first_name );
				$new_customer->set_last_name( $customer->last_name );

				// Try to create the new customer in AC
				$this->logger->debug(
					'Abandonment sync: Creating customer in ActiveCampaign: ',
					[
						'serialized customer' => wp_json_encode( $new_customer->serialize_to_array() ),
					]
				);

				if ( ! empty( $new_customer->get_email() ) ) {
					$customer_ac = $this->customer_repository->create( $new_customer );
				} else {
					$this->logger->warning(
						'Abandonment sync: Email missing, cannot create a customer in AC.',
						[
							'email'    => self::validate_object( $new_customer, 'get_email' ) ? $new_customer->get_email() : null,
							'customer' => $customer,
						]
					);
				}
			} catch ( Throwable $t ) {
				$this->logger->warning(
					'Abandonment sync: Abandon customer creation exception.',
					[
						'customer_email'      => isset( $customer->email ) ? $customer->email : null,
						'customer_first_name' => isset( $customer->first_name ) ? $customer->first_name : null,
						'customer_last_name'  => isset( $customer->last_name ) ? $customer->last_name : null,
						'connection_id'       => $this->connection_id,
						'exception_message'   => $t->getMessage(),
						'ac_code'             => 'RASC_884',
						'exception_trace'     => $this->logger->clean_trace( $t->getTrace() ),
					]
				);
			}

			if ( ! $customer_ac ) {
				$this->logger->warning(
					'Abandonment sync: Invalid AC customer.',
					[
						'customer_email'      => isset( $customer->email ) ? $customer->email : null,
						'customer_first_name' => isset( $customer->first_name ) ? $customer->first_name : null,
						'customer_last_name'  => isset( $customer->last_name ) ? $customer->last_name : null,
						'connection_id'       => $this->connection_id,
						'ac_code'             => 'RASC_899',
					]
				);
			}
		}

		return $customer_ac;
	}

	/**
	 * Sets/Gets the abandoned sync date. This is the time when a sync is performed which should be close to the actual abandonment time.
	 *
	 * @param object $cart The abandoned cart object.
	 *
	 * @return string The date formatted.
	 */
	private function calculate_abandoned_date( $cart ) {
		$logger = new Logger();

		if ( isset( $cart->last_access_time ) && ( ! isset( $cart->abandoned_date ) || empty( $cart->abandoned_date ) ) ) {
			try {
				$now = new DateTime( 'now', new DateTimeZone( 'UTC' ) );

				if ( ! isset( $this->expire_time ) || empty( $this->expire_time ) ) {
					$this->expire_time = 1;
				}

				$last_access_date = new DateTime( $cart->last_access_time, new DateTimeZone( 'UTC' ) );
				$expire_datetime  = new DateTime( 'now -' . $this->expire_time . ' hours', new DateTimeZone( 'UTC' ) );
				$ab_date          = new DateTime( $cart->last_access_time . ' + ' . $this->expire_time . ' hours', new DateTimeZone( 'UTC' ) );

				$diff = $expire_datetime->diff( $ab_date, true );
				$i    = 0 + ( $diff->days * 24 );
				$i   += $diff->hours;

				// calc_abandoned_date is calculated by the DB
				if ( ! empty( $cart->calc_abandoned_date ) && empty( $cart->abandoned_date ) ) {
					$c_ab_date = new DateTime( $cart->calc_abandoned_date, new DateTimeZone( 'UTC' ) );
					return $c_ab_date->format( 'Y-m-d H:i:s e' );
				} elseif ( empty( $cart->calc_abandoned_date ) && empty( $cart->abandoned_date ) ) {
					// If the DB somehow fails to calculate the date
					if ( intval( $i ) >= $this->expire_time ) {
						// If the expiration and abandonment difference is more than the expire time
						return $ab_date->format( 'Y-m-d H:i:s e' );
					}
				}

				// If this is manually force synced and not old enough use now as the time
				return $now->format( 'Y-m-d H:i:s e' );
			} catch ( Throwable $t ) {
				$logger->warning(
					'Could not set a date time for abandoned cart.',
					[
						'message' => $t->getMessage(),
						'trace'   => $t->getTrace(),
					]
				);
			}
		}

	}
}
