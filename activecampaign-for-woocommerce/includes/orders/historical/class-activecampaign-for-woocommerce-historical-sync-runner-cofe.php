<?php

/**
 * Controls the historical sync process.
 * This will only be run by admin or cron so make sure all methods are admin only.
 *
 * @link       https://www.activecampaign.com/
 * @since      1.5.0
 *
 * @package    Activecampaign_For_Woocommerce
 * @subpackage Activecampaign_For_Woocommerce/includes
 */

use Activecampaign_For_Woocommerce_Logger as Logger;
use Activecampaign_For_Woocommerce_Executable_Interface as Executable;
use Activecampaign_For_Woocommerce_Synced_Status_Interface as Synced_Status;
use Activecampaign_For_Woocommerce_Cofe_Order_Builder as Cofe_Order_Builder;
use Activecampaign_For_Woocommerce_Cofe_Order_Repository as Cofe_Order_Repository;

/**
 * The Historical_Sync Event Class.
 *
 * @since      1.5.0
 * @package    Activecampaign_For_Woocommerce
 * @subpackage Activecampaign_For_Woocommerce/includes/events
 * @author     acteamintegrations <team-integrations@activecampaign.com>
 */
class Activecampaign_For_Woocommerce_Historical_Sync_Runner_Cofe implements Executable, Synced_Status {
	use Activecampaign_For_Woocommerce_Historical_Status,
		Activecampaign_For_Woocommerce_Data_Validation,
		Activecampaign_For_Woocommerce_Synced_Status_Handler,
		Activecampaign_For_Woocommerce_Order_Data_Gathering,
		Activecampaign_For_Woocommerce_Historical_Utilities,
		Activecampaign_For_Woocommerce_Subscription_Data_Gathering,
		Activecampaign_For_Woocommerce_Global_Utilities;

	/**
	 * The custom ActiveCampaign logger
	 *
	 * @var Logger
	 */
	private $logger;

	/**
	 * The settings for this run process.
	 *
	 * @since 2.0.0
	 *
	 * @var array
	 */
	private $process_settings = [
		'start_order_id' => 0,
		'end_order_id'   => 0,
		'batch_limit'    => 30,
		'batch_runs'     => 10,
	];

	/**
	 * The COFE Order Repo.
	 *
	 * @var Cofe_Order_Repository
	 */
	private $cofe_order_repository;

	/**
	 * The COFE order builder.
	 *
	 * @var Cofe_Order_Builder
	 */
	private $cofe_order_builder;

	/**
	 * The limit for word count. This is similar to token limit.
	 * We use this to reduce the likeliness grammar token limit will get triggered once sent to graphql.
	 *
	 * @var int
	 */
	private $word_count_limit = 12000;

	/**
	 * constructor.
	 *
	 * @param     Activecampaign_For_Woocommerce_Logger $logger     The logger object.
	 * @param     Cofe_Order_Repository                 $cofe_order_repository     The order repository object.
	 */
	public function __construct(
		Logger $logger,
		Cofe_Order_Repository $cofe_order_repository
	) {
		if ( ! $logger ) {
			$this->logger = new Logger();
		} else {
			$this->logger = $logger;
		}

		$this->cofe_order_repository = $cofe_order_repository;
		$this->cofe_order_builder    = new Cofe_Order_Builder();
	}

	/**
	 * Execute function.
	 *
	 * @param     mixed ...$args     The arg.
	 *
	 * @return mixed|void
	 */
	public function execute( ...$args ) {
		if ( ! $this->logger ) {
			$this->logger = new Logger();
		}

		$now       = date_create( 'NOW' );
		$delay_run = get_option( 'activecampaign_for_woocommerce_historical_sync_delay' );

		// Delay run is for cases where the AC system is not available
		if ( false !== $delay_run ) {
			$interval         = date_diff( $now, $delay_run );
			$interval_minutes = $interval->format( '%i' );
			if ( $interval_minutes >= 5 ) {
				$this->logger->info( 'Continue sync. A wait delay was set on historical sync due to a connection issue with ActiveCampaign.' );
				delete_option( 'activecampaign_for_woocommerce_historical_sync_delay' );
			} else {
				return false;
			}
		}

		// If from a paused state, use the stored status
		$this->init_status();

		$stored_settings = get_option( ACTIVECAMPAIGN_FOR_WOOCOMMERCE_DB_SETTINGS_NAME );

		// NOTE: This is temporarily ignored until COFE looks stable
		$this->process_settings['batch_limit'] = 30;

		if ( isset( $stored_settings['sync_batch_limit'] ) ) {
			$this->process_settings['batch_limit'] = $stored_settings['sync_batch_limit'];
		}

		// Override the COFE limit for launch

		if ( isset( $stored_settings['sync_batch_runs'] ) ) {
			$this->process_settings['batch_runs'] = $stored_settings['sync_batch_runs'];
		}

		// set the start time
		if ( ! isset( $this->status['start_time'] ) ) {
			$this->status['start_time'] = wp_date( 'F d, Y - G:i:s e' );
		}

		$this->update_sync_status();

		$run_count = 0;

		while ( $run_count < $this->process_settings['batch_runs'] ) {
			$stop_check = get_option( ACTIVECAMPAIGN_FOR_WOOCOMMERCE_HISTORICAL_SYNC_STOP_CHECK_NAME );
			if ( ! empty( $stop_check ) ) {
				// Stop found, do not run
				return false;
			}

			$this->status['run_count'] = $run_count;
			$this->update_sync_status();

			if ( ! $this->run_historical_sync() ) {
				// No records in queue to historical sync.
				return false;
			} else {
				$run_count ++;
			}
		}

		$this->update_sync_status();
		$this->logger->debug(
			'Historical sync finished sync group.',
			[
				'run_count'   => $run_count,
				'last_update' => $this->status['last_update'],
				'batch_runs'  => $this->process_settings['batch_runs'],
				'batch_limit' => $this->process_settings['batch_limit'],
				'start_time'  => $this->status['start_time'],
			]
		);
	}

	/**
	 * Executes historical sync on one record.
	 *
	 * @param int|string $wc_order_id The WooCommerce order ID.
	 */
	public function execute_one( $wc_order_id ) {
		if ( ! $this->logger ) {
			$this->logger = new Logger();
		}

		if ( isset( $wc_order_id ) ) {
			$data          = [ $wc_order_id ];
			$sync_response = $this->bulk_sync_data( $data );
			global $wpdb;
			// If bulk sync does not return a failure
			if ( true === $sync_response ) {
				$wpdb->update(
					( $wpdb->prefix . ACTIVECAMPAIGN_FOR_WOOCOMMERCE_TABLE_NAME ), // table
					array( 'synced_to_ac' => self::STATUS_SYNCED ), // data
					array( 'wc_order_id' => $wc_order_id ), // where
					array( '%d' ), // format
					'%d' // where format
				);
			} else {
				$wpdb->update(
					( $wpdb->prefix . ACTIVECAMPAIGN_FOR_WOOCOMMERCE_TABLE_NAME ), // table
					array( 'synced_to_ac' => self::STATUS_FAIL ), // data
					array( 'wc_order_id' => $wc_order_id ), // where
					array( '%d' ), // format
					'%d' // where format
				);
			}
		}
	}

	/**
	 * The new method to run the historical sync via the DB
	 */
	private function run_historical_sync() {
		if ( ! isset( $this->status['batch_limit'] ) ) {
			$this->init_status();
		}

		$this->run_historical_sync_subscriptions();

		// Orders
		$ac_prep_orders = $this->get_historical_sync_orders();
		$sync_order_ids = [];

		try {
			if ( isset( $ac_prep_orders ) && ! empty( $ac_prep_orders ) ) {
				$scheduled_status           = get_option( ACTIVECAMPAIGN_FOR_WOOCOMMERCE_HISTORICAL_SYNC_SCHEDULED_STATUS_NAME );
				$scheduled_status['orders'] = 'syncing';
				update_option(
					ACTIVECAMPAIGN_FOR_WOOCOMMERCE_HISTORICAL_SYNC_SCHEDULED_STATUS_NAME,
					$scheduled_status
				);

				foreach ( $ac_prep_orders as $prep_orders ) {
					$sync_order_ids[] = $prep_orders->wc_order_id;
				}
			} else {
				// No orders to sync
				return false;
			}
		} catch ( Throwable $t ) {
			$this->logger->debug(
				'Historical sync encountered a problem setting up the order array.',
				[
					'message' => $t->getMessage(),
					'ac_code' => 'HSRC_228',
					'trace'   => $t->getTrace(),
				]
			);

			return false;
		}

		global $wpdb;

		if ( isset( $sync_order_ids ) ) {
			try {
				$sync_response = $this->bulk_sync_data( $sync_order_ids );
			} catch ( Throwable $t ) {
				$this->logger->debug(
					'Historical sync encountered an issue setting up bulk sync',
					[
						'message' => $t->getMessage(),
						'trace'   => $t->getTrace(),
						'ac_code' => 'HSRC_262',
					]
				);
			}

			try {
				// If bulk sync does not return a failure
				if ( isset( $sync_response ) && true === $sync_response && count( $sync_order_ids ) > 0 ) {
					self::wpdb_update_in(
						( $wpdb->prefix . ACTIVECAMPAIGN_FOR_WOOCOMMERCE_TABLE_NAME ), // table
						array( 'synced_to_ac' => self::STATUS_SYNCED ), // data
						array( 'wc_order_id' => $sync_order_ids ), // where
						array( '%d' ), // format
						'%d' // where format
					);
				}
			} catch ( Throwable $t ) {
				$this->logger->debug(
					'Historical sync encountered an issue setting up bulk sync',
					[
						'message' => $t->getMessage(),
						'trace'   => $t->getTrace(),
						'ac_code' => 'HSRC_284',
					]
				);
			}

			try {
				if ( isset( $this->status['failed_order_id_array'] ) && count( $this->status['failed_order_id_array'] ) > 0 ) {
					// Set the failed order statuses first
					self::wpdb_update_in(
						( $wpdb->prefix . ACTIVECAMPAIGN_FOR_WOOCOMMERCE_TABLE_NAME ), // table
						array( 'synced_to_ac' => self::STATUS_FAIL ), // data
						array( 'wc_order_id' => $this->status['failed_order_id_array'] ), // where
						array( '%d' ), // format
						'%d' // where format
					);

					$this->status['failed_order_id_array'] = array_unique( $this->status['failed_order_id_array'] );
				}
			} catch ( Throwable $t ) {
				$this->logger->debug(
					'Historical sync encountered an issue updating the table with failed values.',
					[
						'message' => $t->getMessage(),
						'trace'   => $t->getTrace(),
						'ac_code' => 'HSRC_308',
					]
				);
			}

			try {
				if ( isset( $this->status['incompatible_order_id_array'] ) && is_array( $this->status['incompatible_order_id_array'] ) ) {
					$this->status['incompatible_order_id_array'] = array_unique( $this->status['incompatible_order_id_array'] );
				} else {
					$this->status['incompatible_order_id_array'] = [];
				}
			} catch ( Throwable $t ) {
				$this->logger->warning(
					'Historical sync could not store incompatible_order_id_array.',
					[
						'message' => $t->getMessage(),
						'ac_code' => 'HSRC_324',
						'trace'   => $t->getTrace(),
					]
				);
			}

			return true;
		}
	}

	/**
	 * This is the sync process using bulk sync.
	 * It will accept an array of order IDs or order objects.
	 *
	 * @param     array $order_ids     An array of orders or order IDs.
	 *
	 * @return bool
	 * @since 1.6.0
	 */
	private function bulk_sync_data( &$order_ids ) {
		$cofe_order_group_count = 0;
		$cofe_order_group_split = [];
		$word_count             = 0;

		foreach ( $order_ids as $k => $order_id ) {
			try {
				if ( ! isset( $order_id ) ) {
					$this->logger->warning(
						'Historical Sync: This order record is not set. A bad var was passed to bulk sync.',
						[
							'order_id' => isset( $order_id ) ? $order_id : null,
							'ac_code'  => 'HSRC_354',
						]
					);
					unset( $order_ids[ $k ] );
					continue;
				}

				$wc_order = $this->get_wc_order( $order_id );

				if ( ! $this->order_has_required_data( $wc_order ) ) {
					$this->logger->warning(
						'This order does not have any data and will not be synced.',
						[
							'order_id' => isset( $order_id ) ? $order_id : null,
							'wc_order' => isset( $wc_order ) ? $wc_order : null,
							'ac_code'  => 'HSRC_369',
						]
					);
					$this->add_incompatible_order_to_status( $order_id, $wc_order );
					$this->mark_order_as_historical_incompatible( $order_id );
					unset( $order_ids[ $k ] );
					continue;
				}

				// Check if the item is an order object. If not assume it's an ID and get the order.
				$cofe_ecom_order = $this->cofe_order_builder->setup_cofe_order_from_table( $wc_order, 0 );

				if ( is_null( $cofe_ecom_order ) ) {
					$this->logger->debug( 'Ecom order builder returned null. Something may have gone wrong with the sync.' );
					$this->add_incompatible_order_to_status( $order_id );
					$this->mark_order_as_historical_incompatible( $order_id );
					unset( $order_ids[ $k ] );
					continue;
				}

				$externalcheckout_id = get_metadata_raw( 'post', $order_id, 'activecampaign_for_woocommerce_external_checkout_id', true );

				if ( isset( $externalcheckout_id ) && ! empty( $externalcheckout_id ) ) {
					$cofe_ecom_order->set_externalcheckoutid( $externalcheckout_id );
				}
				$serialized_cofe_order = $cofe_ecom_order->serialize_to_array();
				$word_count           += str_word_count( wp_json_encode( $serialized_cofe_order ) );

				// This is the limit for tokens in graphql
				// If the word count passes our limit we must create a new group and reset word count
				if ( $word_count >= $this->word_count_limit ) {
					$cofe_order_group_count++;
					$word_count = str_word_count( wp_json_encode( $serialized_cofe_order ) );
				}

				$cofe_order_group_split[ $cofe_order_group_count ][] = $serialized_cofe_order;
			} catch ( Throwable $t ) {
				$this->logger->warning(
					'Historical sync order failed to serialize and may not sync.',
					[
						'message'  => $t->getMessage(),
						'order_id' => $order_id,
						'ac_code'  => 'HSRC_402',
						'trace'    => $t->getTrace(),
					]
				);
			}
		}

		foreach ( $cofe_order_group_split as $group ) {
			if ( ! empty( $group ) ) {
				$r = $this->run_bulk_sync_mutation( $group, $order_ids );
			}

			if ( isset( $r ) && 'grammar_tokens' === $r ) {
				$chunks = array_chunk( $group, count( $group ) / 3, false );

				foreach ( $chunks as $chunk ) {
					if ( ! empty( $chunk ) ) {
						$this->run_bulk_sync_mutation( $chunk, $order_ids );
					}
				}
			}
		}

		return true;
	}

	/**
	 * Runs the bulk sync mutation group.
	 *
	 * @param array|object $cofe_order_group The group of orders.
	 * @param array        $order_ids The group of order IDs.
	 *
	 * @return false|mixed|string
	 */
	private function run_bulk_sync_mutation( $cofe_order_group, $order_ids ) {
		if ( is_array( $cofe_order_group ) && count( $cofe_order_group ) > 0 ) {
			// Performs the call
			$response = $this->cofe_order_repository->create_bulk( $cofe_order_group );
		} else {
			return false;
		}

		if ( is_array( $response ) && isset( $response['type'] ) ) {
			if ( 'validation_error' === $response['type'] ) {
				foreach ( $order_ids as $k => $order ) {
					if ( in_array( $order, $response['errors'], true ) ) {
						$this->add_failed_order_to_status( $order );
						$this->mark_order_as_failed( $order );
						unset( $order_ids[ $k ] );
					}
					// Any item left in the order list will move on to be put back.
				}

				if ( count( $order_ids ) > 0 ) {
					global $wpdb;
					// Update all of the orders that did not fail with a pending status
					// These will be put back in the queue
					self::wpdb_update_in(
						( $wpdb->prefix . ACTIVECAMPAIGN_FOR_WOOCOMMERCE_TABLE_NAME ), // table
						array( 'synced_to_ac' => self::STATUS_HISTORICAL_SYNC_PREP ), // data
						array( 'wc_order_id' => $order_ids ), // where
						array( '%d' ), // format
						'%d' // where format
					);

					unset( $order_ids );
				}

				return false;
			}

			if ( 'grammar_tokens' === $response['type'] ) {
				return 'grammar_tokens';
			}

			if ( 'error' === $response['type'] ) {
				$this->logger->error(
					'COFE returned a bad response and cannot be reached or cannot process the request at this time. Historical sync will be stopped for 5 minutes.',
					[
						'suggested_action' => 'If this problem repeats please contact support.',
						'ac_code'          => 'HSRC_450',
						'response'         => $response,
					]
				);

				if ( in_array( $response['code'], [ 500, 503, 504, 404 ], false ) ) {
					$now = date_create( 'NOW' );
					update_option( 'activecampaign_for_woocommerce_historical_sync_delay', $now );
					throw new RuntimeException( 'Error encountered attempting to sync to AC. Historical sync will wait a moment before trying again.' );
				}
			}

			if ( 'timeout' === $response['type'] ) {
				$this->logger->error(
					'The ActiveCampaign COFE service could not be reached due to a timeout.',
					[
						'suggested_action' => 'Please check with your host for issues with your server sending data to ActiveCampaign.',
						'ac_code'          => 'HSRC_466',
						'response'         => $response,
					]
				);

				// Call timed out. Records should remain at current status.
				return false;
			}
		}

		if ( is_null( $response ) ) {
			if ( isset( $this->status['failed_order_id_array'] ) ) {
				// The call failed, mark the orders as failed
				$this->status['failed_order_id_array'] = array_merge( $this->status['failed_order_id_array'], $order_ids );
			} else {
				$this->status['failed_order_id_array'] = $order_ids;
			}

			$this->status['failed_order_id_array'] = array_unique( $this->status['failed_order_id_array'] );

			return false;
		}

		$data = maybe_unserialize( json_decode( $response, false ) );

		return $data;
	}

	/**
	 * Get the historical sync orders.
	 *
	 * @return array|object|null
	 */
	private function get_historical_sync_orders() {
		global $wpdb;
		// phpcs:disable

		$results = $wpdb->get_results(
		$wpdb->prepare(
			'SELECT id, wc_order_id, ac_order_id, ac_customer_id, customer_email
				FROM `' . $wpdb->prefix . ACTIVECAMPAIGN_FOR_WOOCOMMERCE_TABLE_NAME . '` 
				WHERE synced_to_ac = %d AND wc_order_id >= %d LIMIT %d;',
			[ self::STATUS_HISTORICAL_SYNC_QUEUE, 0, $this->process_settings['batch_limit'] ]
		)
		);

		// phpcs:enable
		return $results;
	}

	/**
	 * Run the historical sync on subscriptions.
	 *
	 * @return bool|void
	 */
	private function run_historical_sync_subscriptions() {

		$ac_prep_subscriptions = $this->get_historical_sync_subscriptions();
		$sync_subscription_ids = [];

		try {
			if ( isset( $ac_prep_subscriptions ) && ! empty( $ac_prep_subscriptions ) ) {
				$scheduled_status           = get_option( ACTIVECAMPAIGN_FOR_WOOCOMMERCE_HISTORICAL_SYNC_SCHEDULED_STATUS_NAME );
				$scheduled_status['orders'] = 'syncing';
				update_option(
					ACTIVECAMPAIGN_FOR_WOOCOMMERCE_HISTORICAL_SYNC_SCHEDULED_STATUS_NAME,
					$scheduled_status
				);

				foreach ( $ac_prep_subscriptions as $prep_subscriptions ) {
					$sync_subscription_ids[] = $prep_subscriptions->wc_order_id;
				}
			} else {
				// No orders to sync
				return false;
			}
		} catch ( Throwable $t ) {
			$this->logger->debug(
				'Historical sync encountered a problem setting up the subscription array.',
				[
					'message' => $t->getMessage(),
					'ac_code' => 'HSRC_240',
					'trace'   => $t->getTrace(),
				]
			);

			return false;
		}

		global $wpdb;

		if ( isset( $sync_subscription_ids ) ) {
			try {
				$sync_response = $this->bulk_sync_subscription_data( $sync_subscription_ids );
			} catch ( Throwable $t ) {
				$this->logger->debug(
					'Historical sync encountered an issue setting up subscription bulk sync',
					[
						'sync_subscription_ids' => $sync_subscription_ids,
						'message'               => $t->getMessage(),
						'trace'                 => $t->getTrace(),
						'ac_code'               => 'HSRC_' . __LINE__,
					]
				);
			}

			try {
				// If bulk sync does not return a failure
				if ( isset( $sync_response ) && true === $sync_response && count( $sync_subscription_ids ) > 0 ) {
					self::wpdb_update_in(
						( $wpdb->prefix . ACTIVECAMPAIGN_FOR_WOOCOMMERCE_TABLE_NAME ), // table
						array( 'synced_to_ac' => self::STATUS_SUBSCRIPTION_HISTORICAL_SYNC_FINISH ), // data
						array( 'wc_order_id' => $sync_subscription_ids ), // where
						array( '%d' ), // format
						'%d' // where format
					);
				}
			} catch ( Throwable $t ) {
				$this->logger->debug(
					'Historical sync encountered an issue setting up subscription bulk sync',
					[
						'message' => $t->getMessage(),
						'trace'   => $t->getTrace(),
						'ac_code' => 'HSRC_284',
					]
				);
			}

			try {
				if ( isset( $this->status['failed_subscription_id_array'] ) && count( $this->status['failed_subscription_id_array'] ) > 0 ) {
					// Set the failed order statuses first
					self::wpdb_update_in(
						( $wpdb->prefix . ACTIVECAMPAIGN_FOR_WOOCOMMERCE_TABLE_NAME ), // table
						array( 'synced_to_ac' => self::STATUS_SUBSCRIPTION_FAILED_SYNC ), // data
						array( 'wc_order_id' => $this->status['failed_subscription_id_array'] ), // where
						array( '%d' ), // format
						'%d' // where format
					);

					$this->status['failed_subscription_id_array'] = array_unique( $this->status['failed_subscription_id_array'] );
				}
			} catch ( Throwable $t ) {
				$this->logger->debug(
					'Historical sync encountered an issue updating the table with failed values.',
					[
						'message' => $t->getMessage(),
						'trace'   => $t->getTrace(),
						'ac_code' => 'HSRC_308',
					]
				);
			}

			try {
				if ( isset( $this->status['incompatible_subscription_id_array'] ) && is_array( $this->status['incompatible_subscription_id_array'] ) ) {
					$this->status['incompatible_subscription_id_array'] = array_unique( $this->status['incompatible_subscription_id_array'] );
				} else {
					$this->status['incompatible_subscription_id_array'] = [];
				}
			} catch ( Throwable $t ) {
				$this->logger->warning(
					'Historical sync could not store incompatible_subscription_id_array.',
					[
						'message' => $t->getMessage(),
						'ac_code' => 'HSRC_324',
						'trace'   => $t->getTrace(),
					]
				);
			}

			return true;
		}
	}

	/**
	 * This is the sync process using bulk sync.
	 * It will accept an array of order IDs or order objects.
	 *
	 * @param     array $subscription_ids     An array of orders or order IDs.
	 *
	 * @return bool
	 * @since 1.6.0
	 */
	private function bulk_sync_subscription_data( &$subscription_ids ) {
		$success_count           = 0;
		$cofe_subscription_group = [];

		foreach ( $subscription_ids as $k => $order_id ) {
			try {
				if ( ! isset( $order_id ) ) {
					$this->logger->warning(
						'Historical Sync: This order record is not set. A bad var was passed to bulk sync.',
						[
							'order_id' => isset( $order_id ) ? $order_id : null,
							'ac_code'  => 'HSRC_354',
						]
					);
					unset( $subscription_ids[ $k ] );
					continue;
				}

				$wc_subscription = $this->get_wc_subscription( $order_id );

				if ( ! $this->order_has_required_data( $wc_subscription ) ) {
					$this->logger->warning(
						'This order does not have any data and will not be synced.',
						[
							'order_id' => isset( $order_id ) ? $order_id : null,
							'wc_order' => isset( $wc_subscription ) ? $wc_subscription : null,
							'ac_code'  => 'HSRC_689',
						]
					);
					$this->add_incompatible_subscription_to_status( $order_id, $wc_subscription );
					$this->mark_subscription_as_historical_incompatible( $order_id );
					unset( $subscription_ids[ $k ] );
					continue;
				}

				// Check if the item is an order object. If not assume it's an ID and get the order.
				$cofe_ecom_subscription = $this->setup_cofe_subscription_from_table( $wc_subscription, 0 );

				if ( is_null( $cofe_ecom_subscription ) ) {
					$this->logger->debug( 'Ecom subscription builder returned null. Something may have gone wrong with the sync.' );
					$this->add_incompatible_subscription_to_status( $order_id );
					$this->mark_subscription_as_historical_incompatible( $order_id );
					unset( $subscription_ids[ $k ] );
					continue;
				}

				$cofe_subscription_group[] = $cofe_ecom_subscription->serialize_to_array();
			} catch ( Throwable $t ) {
				$this->logger->warning(
					'Historical sync order failed to serialize and may not sync.',
					[
						'message'  => $t->getMessage(),
						'order_id' => $order_id,
						'ac_code'  => 'HSRC_402',
						'trace'    => $t->getTrace(),
					]
				);
			}
		}

		if ( is_array( $cofe_subscription_group ) && count( $cofe_subscription_group ) > 0 ) {
			// Performs the call
			$response = $this->cofe_order_repository->create_bulk( $cofe_subscription_group, 'subscriptions' );
		} else {
			return false;
		}

		if ( is_array( $response ) && isset( $response['type'] ) ) {
			if ( 'validation_error' === $response['type'] ) {
				foreach ( $subscription_ids as $k => $order ) {
					if ( in_array( $order, $response['errors'], true ) ) {
						$this->add_failed_subscription_to_status( $order );
						$this->mark_subscription_as_failed( $order );
						unset( $subscription_ids[ $k ] );
					}
					// Any item left in the order list will move on to be put back.
				}

				if ( count( $subscription_ids ) > 0 ) {
					global $wpdb;
					// Update all of the orders that did not fail with a queue status
					// These will be put back in the queue
					self::wpdb_update_in(
						( $wpdb->prefix . ACTIVECAMPAIGN_FOR_WOOCOMMERCE_TABLE_NAME ), // table
						array( 'synced_to_ac' => self::STATUS_SUBSCRIPTION_HISTORICAL_SYNC_QUEUE ), // data
						array( 'wc_order_id' => $subscription_ids ), // where
						array( '%d' ), // format
						'%d' // where format
					);

					unset( $subscription_ids );
				}

				return false;
			}

			if ( 'error' === $response['type'] ) {
				$this->logger->error(
					'COFE returned a bad response and cannot be reached or cannot process the request at this time. Historical sync will be stopped for 5 minutes.',
					[
						'suggested_action' => 'If this problem repeats please contact support.',
						'ac_code'          => 'HSRC_450',
						'response'         => $response,
					]
				);

				if ( in_array( $response['code'], [ 500, 503, 504, 404 ], false ) ) {
					$now = date_create( 'NOW' );
					update_option( 'activecampaign_for_woocommerce_historical_sync_delay', $now );
					throw new RuntimeException( 'Error encountered attempting to sync to AC. Historical sync will wait a moment before trying again.' );
				}
			}

			if ( 'timeout' === $response['type'] ) {
				$this->logger->error(
					'The ActiveCampaign COFE service could not be reached due to a timeout.',
					[
						'suggested_action' => 'Please check with your host for issues with your server sending data to ActiveCampaign.',
						'ac_code'          => 'HSRC_466',
						'response'         => $response,
					]
				);

				// Call timed out. Records should remain at current status.
				return false;
			}
		}

		if ( is_null( $response ) ) {
			if ( isset( $this->status['failed_subscription_id_array'] ) ) {
				// The call failed, mark the orders as failed
				$this->status['failed_subscription_id_array'] = array_merge( $this->status['failed_subscription_id_array'], $subscription_ids );
			} else {
				$this->status['failed_subscription_id_array'] = $subscription_ids;
			}

			$this->status['failed_subscription_id_array'] = array_unique( $this->status['failed_subscription_id_array'] );

			return false;
		}

		$data = maybe_unserialize( json_decode( $response, false ) );

		// each id is returned in order
		if ( isset( $response, $data->data->bulkUpsertOrders ) && ! is_null( $response ) && count( $data->data->bulkUpsertOrders ) > 0 ) {
			foreach ( $data->data->bulkUpsertOrders as $k => $r_id ) {
				// $orders[$k];

				/**
				 *
				 * missing fields
				 * empty fields
				 * no line items
				 * no address info
				 */
				$this->status['success_count'] += $success_count;
			}
		}

		return true;
	}

	/**
	 * Get the historical sync subscriptions.
	 *
	 * @return array|object|null
	 */
	private function get_historical_sync_subscriptions() {
		global $wpdb;
		// phpcs:disable

		$results = $wpdb->get_results(
			$wpdb->prepare(
				'SELECT id, wc_order_id, ac_order_id, ac_customer_id, customer_email
				FROM `' . $wpdb->prefix . ACTIVECAMPAIGN_FOR_WOOCOMMERCE_TABLE_NAME . '` 
				WHERE synced_to_ac = %d AND wc_order_id >= %d LIMIT %d;',
				[ self::STATUS_SUBSCRIPTION_HISTORICAL_SYNC_QUEUE, 0, $this->process_settings['batch_limit'] ]
			)
		);

		// phpcs:enable
		return $results;
	}

}
