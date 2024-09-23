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
use Activecampaign_For_Woocommerce_Historical_Sync_Subscriptions as Subscription_Historical_Sync;
/**
 * The Historical_Sync Event Class.
 *
 * @since      1.5.0
 * @package    Activecampaign_For_Woocommerce
 * @subpackage Activecampaign_For_Woocommerce/includes/events
 * @author     acteamintegrations <team-integrations@activecampaign.com>
 */
class Activecampaign_For_Woocommerce_Historical_Sync_Prep implements Executable, Synced_Status {
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
		$this->logger->debug( 'Preparing historical sync records.' );
		$batch_limit = 30;
		$this->clean_bad_data_from_table();

		$subscription_historical_sync = new Subscription_Historical_Sync();
		$subscription_historical_sync->execute();

		// phpcs:disable
		// Get all unsynced orders to exclude from this process. They should sync normally.
		$exclude = $wpdb->get_col(
			'SELECT wc_order_id 
			FROM ' . $wpdb->prefix . ACTIVECAMPAIGN_FOR_WOOCOMMERCE_TABLE_NAME
								   . ' WHERE `wc_order_id` IS NOT NULL 
                AND `synced_to_ac` = ' . self::STATUS_UNSYNCED
		);
		// phpcs:enable

		$current_page = 0;

		$first_set_pagination = $this->get_order_ids_by_page( $current_page, $batch_limit, $exclude, null, true );

		if ( isset( $first_set_pagination->total, $first_set_pagination->max_num_pages ) ) {
			if ( ! isset( $first_set_pagination->orders ) ||
				 $first_set_pagination->total < 1 ||
				 count( $first_set_pagination->orders ) < 1
			) {
				$this->logger->debug( 'WooCommerce returned no orders for historical sync to prepare.', [ $first_set_pagination->orders ] );
				return false;
			}

			$total_records   = $first_set_pagination->total;
			$max_pages       = $first_set_pagination->max_num_pages + 1;
			$orders_id_array = $first_set_pagination->orders;
			$this->logger->debug(
				'WooCommerce returned these totals for pagination:',
				[
					'total_records' => $total_records,
					'total_pages'   => $max_pages,
				]
			);
			unset( $first_set_pagination );
		} else {
			$max_pages = 1;
		}

		if ( isset( $orders_id_array[0] ) ) {
			$this->prep_loop( $wpdb, $orders_id_array );
		}
		$current_page ++;
		unset( $orders_id_array );

		while ( $current_page <= $max_pages ) {
			try {
				// $start_memory    = memory_get_usage();
				$orders_id_array = $this->get_order_ids_by_page( $current_page, $batch_limit, $exclude );

				if ( ! isset( $orders_id_array[0] ) ) {
					// no orders
					break;
				}
				$this->prep_loop( $wpdb, $orders_id_array );
				$current_page ++;
			} catch ( Throwable $t ) {
				$this->logger->warning(
					'Historical sync prep encountered an error and will skip this page.',
					[
						'page'        => $current_page,
						'batch_limit' => $batch_limit,
						'exclude'     => $exclude,
						'message'     => $t->getMessage(),
						'trace'       => $t->getTrace(),
					]
				);
				$current_page ++;
			}
		}

		$wpdb->delete( $wpdb->prefix . ACTIVECAMPAIGN_FOR_WOOCOMMERCE_TABLE_NAME, [ 'synced_to_ac' => self::STATUS_DELETE ] );
		$this->logger->debug( 'Historical Sync finished preparing records' );

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
			' SET synced_to_ac=' . self::STATUS_HISTORICAL_SYNC_QUEUE .
			' WHERE synced_to_ac='.self::STATUS_HISTORICAL_SYNC_PREP,
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
				$row = $this->build_order_data_for_historical_sync( $order_id );
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
	}

	/**
	 * Marks existing entries in our table as prepared.
	 */
	public function mark_orders_for_prep() {
		global $wpdb;
		$this->clean_bad_data_from_table();
		$in_str = implode(
			',',
			[
				self::STATUS_UNSYNCED,
				self::STATUS_ON_HOLD,
				self::STATUS_REFUND,
			]
		);

		// phpcs:disable
		// Mark all orders in the table already as ready for prep so we aren't making unnecessary calls for data we already have
		$wpdb->query(
			'
			UPDATE ' . $wpdb->prefix . ACTIVECAMPAIGN_FOR_WOOCOMMERCE_TABLE_NAME . '
			SET `synced_to_ac` = ' . self::STATUS_HISTORICAL_SYNC_PREP . '
			WHERE `wc_order_id` IS NOT NULL
			AND `synced_to_ac` NOT IN (' . $in_str . ')
			AND `synced_to_ac` < 20
			'
		);
		// phpcs:enable
	}

	/**
	 * Store the order data related to the historical sync
	 *
	 * @param     int $order_id     The WC order ID.
	 */
	private function build_order_data_for_historical_sync( $order_id ) {
		try {
			// $externalcheckout_id = get_metadata_raw( 'post', $order_id, 'activecampaign_for_woocommerce_external_checkout_id', true );

			$store_data = [
				'synced_to_ac' => self::STATUS_HISTORICAL_SYNC_PREP,
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
	 * @param     string|array|null $status     The status we want to check for. This is an override and by default we do processing and completed.
	 *
	 * @return stdClass|WC_Order[]
	 */
	private function get_order_ids_by_page( $offset, $batch_limit = 30, $exclude = null, $status = null, $get_pagination = false ) {
		// limits and paged can be added
		$data = array(
			'orderby' => 'id',
			'order'   => 'ASC',
			'return'  => 'ids',
		);

		if ( $get_pagination ) {
			$data['paginate'] = true;
		}

		if ( isset( $offset ) && $offset > 0 ) {
			$data['page'] = $offset;
		}

		if ( isset( $batch_limit ) && $batch_limit > 0 ) {
			$data['limit'] = $batch_limit;
		}

		if ( isset( $exclude ) && count( $exclude ) > 0 ) {
			$data['exclude'] = $exclude;
		}

		return wc_get_orders( $data );
	}
}
