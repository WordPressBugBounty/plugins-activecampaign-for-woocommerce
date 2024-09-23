<?php

/**
 * Runs the historical sync prep procedures.
 * This just gets records ready for processing but does not perform the processing/sync.
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

/**
 * The Historical_Sync Event Class.
 *
 * @since      1.5.0
 * @package    Activecampaign_For_Woocommerce
 * @subpackage Activecampaign_For_Woocommerce/includes/events
 * @author     acteamintegrations <team-integrations@activecampaign.com>
 */
class Activecampaign_For_Woocommerce_Historical_Sync_Subscriptions implements Executable, Synced_Status {
	use Activecampaign_For_Woocommerce_Historical_Status,
		Activecampaign_For_Woocommerce_Data_Validation,
		Activecampaign_For_Woocommerce_Synced_Status_Handler,
		Activecampaign_For_Woocommerce_Historical_Utilities,
		Activecampaign_For_Woocommerce_Global_Utilities;

	/**
	 * The custom ActiveCampaign logger
	 *
	 * @var Activecampaign_For_Woocommerce_Logger
	 */
	private $logger;

	/**
	 * Execute function.
	 *
	 * @param     mixed ...$args     The arg.
	 *
	 * @return mixed|void
	 */
	public function execute( ...$args ) {
		global $wpdb;

		$this->logger = new Logger();
		$batch_limit  = 20;

		if ( function_exists( 'wcs_get_subscriptions' ) ) {
			// phpcs:disable
			// Get all unsynced orders to exclude from this process. They should sync normally.
			$exclude = $wpdb->get_col(
				'SELECT wc_order_id 
			FROM ' . $wpdb->prefix . ACTIVECAMPAIGN_FOR_WOOCOMMERCE_TABLE_NAME
				. ' WHERE `wc_order_id` IS NOT NULL 
                AND `synced_to_ac` = ' . self::STATUS_SUBSCRIPTION_UNSYNCED
			);
			// phpcs:enable
			$this->clean_bad_data_from_table();
			$wpdb->delete( $wpdb->prefix . ACTIVECAMPAIGN_FOR_WOOCOMMERCE_TABLE_NAME, [ 'synced_to_ac' => self::STATUS_DELETE ] );
			$this->mark_subscriptions_for_prep();
			$current_offset  = 0;
			$orders_id_array = true;
			while ( isset( $orders_id_array ) && null !== $orders_id_array ) {
				try {
					$orders_id_array = $this->get_subscription_ids_by_page( $current_offset, $batch_limit, $exclude );

					if ( ! isset( $orders_id_array[0] ) ) {
						// no orders
						break;
					}

					$this->prep_loop( $wpdb, $orders_id_array );
					$current_offset += $batch_limit;
				} catch ( Throwable $t ) {
					$this->logger->warning(
						'Historical sync prep encountered an error and will skip this page.',
						[
							'page'        => $current_offset,
							'batch_limit' => $batch_limit,
							'exclude'     => $exclude,
							'message'     => $t->getMessage(),
							'trace'       => $t->getTrace(),
							'ac_code'     => 'HSS_74',
						]
					);

					$current_offset++;
					continue;
				}
			}

			$this->queue_prepared_records();
			// Delete bad or deleted records from table

			$this->logger->debug( 'Historical Subscription Sync finished preparing records' );
		}
	}

	/**
	 * Queues all records in prep to pending sync.
	 */
	public function queue_prepared_records() {
		global $wpdb;
		// Move any possible abandoned prep orders to queue
		// phpcs:disable

		$wpdb->query(
		'UPDATE ' . $wpdb->prefix . ACTIVECAMPAIGN_FOR_WOOCOMMERCE_TABLE_NAME .
		' SET synced_to_ac=' . self::STATUS_SUBSCRIPTION_HISTORICAL_SYNC_QUEUE .
		' WHERE synced_to_ac='.self::STATUS_SUBSCRIPTION_HISTORICAL_SYNC_PREP,
		);
		// phpcs:enable
	}

	/**
	 * Performs the loop that prepares records.
	 *
	 * @param WPDB  $wpdb Global WPDB.
	 * @param array $orders_id_array The order id array.
	 */
	private function prep_loop( $wpdb, $orders_id_array ) {
		try {
			$order_id_array_implode = implode( ',', $orders_id_array );

			// phpcs:disable
			$registered_order_ids = $wpdb->get_col(
				'SELECT wc_order_id 
			FROM ' . $wpdb->prefix . ACTIVECAMPAIGN_FOR_WOOCOMMERCE_TABLE_NAME
				. ' WHERE `wc_order_id` IN (' . $order_id_array_implode . ')'
			);
			// phpcs:enable
			unset( $order_id_array_implode );
			$result_int = array_map( 'intval', $registered_order_ids );
			unset( $registered_order_ids ); // save memory
			$unique_order_ids = array_diff( $orders_id_array, $result_int );
			unset( $result_int );
			$data = [];

			if ( count( $unique_order_ids ) >= 1 ) {
				foreach ( $unique_order_ids as $order_id ) {
					// Add data to the table
					$row = $this->build_subscription_data_for_historical_sync( $order_id );
					if ( isset( $row ) ) {
						$data[] = $row;
					}
				}

				unset( $unique_order_ids ); // save memory
				// Bulk insert the prepared order data we didn't previously have
				if ( isset( $data ) && count( $data ) > 0 ) {
					self::wpdb_bulk_insert( $wpdb->prefix . ACTIVECAMPAIGN_FOR_WOOCOMMERCE_TABLE_NAME, $data );
				}
			}
			unset( $data );
		} catch ( Throwable $t ) {
			$logger = new Logger();
			$logger->error(
				'Could not prepare line item data for subscription',
				[
					'message' => $t->getMessage(),
					'trace'   => $t->getTrace(),
					'ac_code' => 'HSS_162',
				]
			);
		}
	}

	/**
	 * Marks existing entries in our table as prepared.
	 */
	public function mark_subscriptions_for_prep() {
		global $wpdb;
		$this->clean_bad_data_from_table();

		$in_str = implode(
			',',
			[
				self::STATUS_SUBSCRIPTION_SYNCED,
				self::STATUS_SUBSCRIPTION_HISTORICAL_SYNC_PREP,
				self::STATUS_SUBSCRIPTION_HISTORICAL_SYNC_FINISH,
				self::STATUS_SUBSCRIPTION_EXPIRED,
				self::STATUS_SUBSCRIPTION_FAILED_BILLING,
				self::STATUS_SUBSCRIPTION_INCOMPATIBLE,
				self::STATUS_SUBSCRIPTION_FAILED_SYNC,
			]
		);

		// Do the subscriptions
		// phpcs:disable
		$wpdb->query(
			'
			UPDATE ' . $wpdb->prefix . ACTIVECAMPAIGN_FOR_WOOCOMMERCE_TABLE_NAME . '
			SET `synced_to_ac` = ' . self::STATUS_SUBSCRIPTION_HISTORICAL_SYNC_PREP . '
			WHERE `wc_order_id` IS NOT NULL AND `synced_to_ac` IN (' . $in_str . ')'
		);
		// phpcs:enable

	}

	/**
	 * Store the order data related to the historical sync
	 *
	 * @param     int $order_id     The WC order ID.
	 */
	private function build_subscription_data_for_historical_sync( $order_id ) {
		try {
			// $externalcheckout_id = get_metadata_raw( 'post', $order_id, 'activecampaign_for_woocommerce_external_checkout_id', true );

			$store_data = [
				'synced_to_ac' => self::STATUS_SUBSCRIPTION_HISTORICAL_SYNC_QUEUE,
				'wc_order_id'  => $order_id,
			];

			return $store_data;

		} catch ( Throwable $t ) {
			$logger = new Logger();
			$logger->warning(
				'There was an issue forming the order data for historical sync.',
				[
					'message' => $t->getMessage(),
					'trace'   => $logger->clean_trace( $t->getTrace() ),
				]
			);

			return null;
		}
	}

	/**
	 * Gets all orders by page filtered by status. Exclude is optional if it's needed to exclude a specific ID or set of IDs.
	 *
	 * @param     int               $offset     The offset.
	 * @param     int               $batch_limit     The limit of results.
	 * @param     array|null        $exclude     The records to exclude.
	 * @param     string|array|null $return_ids  Whether or not to only return IDs.
	 *
	 * @return stdClass|WC_Order[]
	 */
	private function get_subscription_ids_by_page( $offset, $batch_limit, $exclude = null, $return_ids = true ) {
		$logger = new Logger();

		try {
			$data = array(
				'offset'              => 0,
				'return'              => 'ids',
				'subscription_status' => array( 'any' ),
			);
			// subscriptions_per_page = limit
			// paged = page
			// offset = offset
			if ( isset( $offset ) && $offset > 0 ) {
				$data['offset'] = $offset;
			}

			if ( isset( $batch_limit ) && $batch_limit > 0 ) {
				$data['subscriptions_per_page'] = $batch_limit;
			}

			if ( isset( $exclude ) && count( $exclude ) > 0 ) {
				$data['exclude'] = $exclude;
			}

			$return = wcs_get_subscriptions( $data );

			if ( true === $return_ids ) {
				$return_id_array = array();
				foreach ( $return as $key => $order ) {
					$return_id_array[] = $key;
				}

				if ( count( $return_id_array ) >= 1 ) {
					return $return_id_array;
				}
			}

			if ( count( $return ) >= 1 ) {
				return $return;
			}
		} catch ( Throwable $t ) {
			$logger->error(
				'Could not get subscription ids by page for historical sync',
				[
					'message' => $t->getMessage(),
					'trace'   => $t->getTrace(),
					'ac_code' => 'HSS_291',
				]
			);
		}

		return null;
	}
}
