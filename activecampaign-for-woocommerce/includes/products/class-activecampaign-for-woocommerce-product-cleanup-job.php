<?php

/**
 * Controls the product cleanup process.
 *
 * Reconciles the products stored in COFE against the WooCommerce catalog.
 * Any COFE product whose WooCommerce counterpart is missing (or trashed) is
 * soft-deleted from COFE. Only cron/admin should invoke this job.
 *
 * @link       https://www.activecampaign.com/
 *
 * @package    Activecampaign_For_Woocommerce
 * @subpackage Activecampaign_For_Woocommerce/includes/products
 */

use Activecampaign_For_Woocommerce_Product_Repository as Cofe_Product_Repository;
use Activecampaign_For_Woocommerce_Cofe_Connection_Repository as Cofe_Connection_Repository;
use Activecampaign_For_Woocommerce_Executable_Interface as Executable;
use Activecampaign_For_Woocommerce_Logger as Logger;

/**
 * The Product_Cleanup Job Class.
 *
 * @package    Activecampaign_For_Woocommerce
 * @subpackage Activecampaign_For_Woocommerce/includes/products
 * @author     acteamintegrations <team-integrations@activecampaign.com>
 */
class Activecampaign_For_Woocommerce_Product_Cleanup_Job implements Executable {
	use Activecampaign_For_Woocommerce_Data_Validation;

	/**
	 * Maximum products COFE will accept in a single `bulkUpsertProducts` call.
	 */
	private const BATCH_LIMIT = 99;

	/**
	 * Ceiling on cursor pages when scanning COFE. Guards against a broken
	 * API that never yields an empty `nextCursor` from tying up a worker.
	 * At 10k products per page, this covers 10M records — well beyond any
	 * realistic catalog.
	 */
	private const MAX_PAGES = 1000;

	/**
	 * The custom ActiveCampaign logger.
	 *
	 * @var Activecampaign_For_Woocommerce_Logger
	 */
	private $logger;

	/**
	 * COFE product repository.
	 *
	 * @var Activecampaign_For_Woocommerce_Product_Repository
	 */
	private $cofe_product_repository;

	/**
	 * COFE connection repository — resolves the legacy → COFE connection ID.
	 *
	 * @var Activecampaign_For_Woocommerce_Cofe_Connection_Repository
	 */
	private $cofe_connection_repository;

	/**
	 * Activecampaign_For_Woocommerce_Product_Cleanup_Job constructor.
	 *
	 * @param Logger                     $logger The logger.
	 * @param Cofe_Product_Repository    $cofe_product_repository COFE product repo.
	 * @param Cofe_Connection_Repository $cofe_connection_repository COFE connection repo.
	 */
	public function __construct(
		Logger $logger,
		Cofe_Product_Repository $cofe_product_repository,
		Cofe_Connection_Repository $cofe_connection_repository
	) {
		$this->logger                     = $logger;
		$this->cofe_product_repository    = $cofe_product_repository;
		$this->cofe_connection_repository = $cofe_connection_repository;
	}

	/**
	 * Cron callback — processes a single cleanup batch.
	 *
	 * Fired by the `activecampaign_for_woocommerce_run_product_cleanup` action
	 * that {@see schedule_background_product_cleanup()} queues via wp-cron.
	 *
	 * @param mixed ...$args
	 *
	 * @return void
	 */
	public function execute( ...$args ) {
		if ( $this->check_for_stop() ) {
			return;
		}

		$data     = isset( $args[0] ) && is_array( $args[0] ) ? $args[0] : array();
		$products = isset( $data['products'] ) && is_array( $data['products'] ) ? $data['products'] : array();

		if ( empty( $products ) ) {
			$this->finalize_run_if_done();
			return;
		}

		$cofe_connection_id = $this->cofe_connection_repository->find_current_id();
		if ( empty( $cofe_connection_id ) ) {
			$this->logger->warning( 'Product cleanup batch skipped — could not resolve COFE connection ID.' );
			$this->finalize_run_if_done();
			return;
		}

		$missing = $this->find_missing_in_woocommerce( $products );

		$deleted_this_batch = 0;
		$failed_this_batch  = array();

		if ( ! empty( $missing ) ) {
			$payload = array_map(
				static function ( $product ) use ( $cofe_connection_id ) {
					$row = array(
						'connectionId'   => $cofe_connection_id,
						'storePrimaryId' => (string) $product['storePrimaryId'],
						'isDeleted'      => true,
					);

					if ( ! empty( $product['storeBaseProductId'] ) ) {
						$row['storeBaseProductId'] = (string) $product['storeBaseProductId'];
					}

					return $row;
				},
				array_values( $missing )
			);

			$response = $this->cofe_product_repository->create_bulk( $payload );

			if ( $response ) {
				$deleted_this_batch = count( $missing );
			} else {
				foreach ( $missing as $product ) {
					$failed_this_batch[] = isset( $product['storePrimaryId'] ) ? $product['storePrimaryId'] : ( $product['id'] ?? 'unknown' );
				}
				$this->logger->warning(
					'Bulk soft-delete failed for a cleanup batch.',
					array( 'product_count' => count( $missing ) )
				);
			}
		}

		$this->update_running_status(
			count( $products ),
			$deleted_this_batch,
			$failed_this_batch
		);

		$this->finalize_run_if_done();
	}

	/**
	 * Builds the queue of cleanup batches based on how many products COFE holds.
	 *
	 * Paginates through COFE with `searchProductPage(limit: PAGE_SIZE)`, chunks
	 * each page into {@see BATCH_LIMIT}-sized slices, and schedules one wp-cron
	 * event per slice — carrying the exact product IDs to reconcile.
	 *
	 * Fired by `activecampaign_for_woocommerce_build_product_cleanup_schedules`
	 * (dispatched from {@see Activecampaign_For_Woocommerce_Admin_Product_Cleanup::run_product_cleanup()}).
	 *
	 * @return void
	 */
	public function build_product_cleanup_schedules() {
		if ( wp_next_scheduled( ACTIVECAMPAIGN_FOR_WOOCOMMERCE_RUN_PRODUCT_CLEANUP_NAME ) ) {
			$this->logger->info( 'Product cleanup batches already queued; skipping duplicate schedule build.' );
			return;
		}

		wp_raise_memory_limit( 'admin' );
		set_time_limit( 0 );

		delete_option( ACTIVECAMPAIGN_FOR_WOOCOMMERCE_PRODUCT_CLEANUP_RUNNING_STATUS_NAME );
		delete_option( ACTIVECAMPAIGN_FOR_WOOCOMMERCE_PRODUCT_CLEANUP_LAST_STATUS_NAME );
		delete_option( ACTIVECAMPAIGN_FOR_WOOCOMMERCE_PRODUCT_CLEANUP_SCHEDULED_STATUS_NAME );

		$page_size         = 10000;
		$cursor            = null;
		$cofe_total        = 0;
		$cofe_eligible     = 0;
		$scheduled_batches = 0;

		$cofe_connection_id = $this->cofe_connection_repository->find_current_id();

		if ( empty( $cofe_connection_id ) ) {
			$this->logger->warning( 'Product cleanup could not resolve a COFE connection ID for this store.' );
			return;
		}

		$page_index = 0;

		do {
			$page     = $this->cofe_product_repository->search_product_page( $cofe_connection_id, $page_size, $cursor );
			$products = isset( $page['products'] ) ? $page['products'] : array();
			$cursor   = isset( $page['nextCursor'] ) ? $page['nextCursor'] : null;

			$cofe_total += count( $products );

			$eligible = array_values(
				array_filter(
					$products,
					static function ( $product ) {
						return empty( $product['isDeleted'] );
					}
				)
			);

			if ( ! empty( $eligible ) ) {
				$cofe_eligible += count( $eligible );

				foreach ( array_chunk( $eligible, self::BATCH_LIMIT ) as $slice ) {
					$this->schedule_background_product_cleanup( $slice );
					++$scheduled_batches;
				}
			}

			if ( ++$page_index >= self::MAX_PAGES ) {
				$this->logger->warning(
					'Product cleanup pagination hit the safety ceiling; aborting further COFE scans.',
					array(
						'max_pages'  => self::MAX_PAGES,
						'cofe_total' => $cofe_total,
					)
				);
				break;
			}
		} while ( ! empty( $cursor ) );

		update_option(
			ACTIVECAMPAIGN_FOR_WOOCOMMERCE_PRODUCT_CLEANUP_RUNNING_STATUS_NAME,
			wp_json_encode(
				array(
					'status_name'       => 'product_cleanup',
					'start_time'        => wp_date( 'F d, Y - G:i:s e' ),
					'last_update'       => wp_date( 'F d, Y - G:i:s e' ),
					'cofe_total'        => $cofe_total,
					'cofe_eligible'     => $cofe_eligible,
					'current_record'    => 0,
					'deleted_count'     => 0,
					'failed_id_array'   => array(),
					'scheduled_batches' => $scheduled_batches,
					'is_running'        => $scheduled_batches > 0,
				)
			)
		);

		if ( $scheduled_batches > 0 ) {
			update_option( ACTIVECAMPAIGN_FOR_WOOCOMMERCE_PRODUCT_CLEANUP_SCHEDULED_STATUS_NAME, true );
		}

		$this->logger->info(
			'Product cleanup schedules built.',
			array(
				'cofe_total'        => $cofe_total,
				'scheduled_batches' => $scheduled_batches,
			)
		);
	}

	/**
	 * Schedules a single background cleanup batch via wp-cron.
	 *
	 * The batch of COFE product records is embedded directly in the cron args
	 * so {@see execute()} receives the exact IDs it needs to reconcile.
	 *
	 * @param array $products The batch of COFE product records to reconcile.
	 *
	 * @return void
	 */
	public function schedule_background_product_cleanup( $products ) {
		if ( empty( $products ) ) {
			return;
		}

		$data = array(
			array(
				'products' => $products,
			),
		);

		try {
			if ( empty( wp_next_scheduled( ACTIVECAMPAIGN_FOR_WOOCOMMERCE_RUN_PRODUCT_CLEANUP_NAME, $data ) ) ) {
				wp_schedule_single_event(
					time() + 10,
					ACTIVECAMPAIGN_FOR_WOOCOMMERCE_RUN_PRODUCT_CLEANUP_NAME,
					$data
				);
			}
		} catch ( Throwable $t ) {
			$this->logger->error(
				'There was an issue scheduling a product cleanup batch.',
				array(
					'message'       => $t->getMessage(),
					'product_count' => count( $products ),
					'function'      => 'schedule_background_product_cleanup',
				)
			);
		}
	}

	/**
	 * Returns the subset of a COFE batch that is missing (or trashed) in WooCommerce.
	 *
	 * A COFE product is treated as missing when the WooCommerce post identified by
	 * its `storePrimaryId` either doesn't exist or has been moved to the trash.
	 *
	 * @param array $cofe_products The batch pulled from COFE.
	 *
	 * @return array COFE products that should be soft-deleted.
	 */
	private function find_missing_in_woocommerce( array $cofe_products ): array {
		global $wpdb;

		$ids = array_values( array_filter( array_map( 'intval', array_column( $cofe_products, 'storePrimaryId' ) ) ) );
		if ( empty( $ids ) ) {
			return array();
		}

		$placeholders = implode( ',', array_fill( 0, count( $ids ), '%d' ) );

		// phpcs:disable WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- $placeholders contains only '%d' tokens, each bound to an int-cast ID below.
		$live_ids = $wpdb->get_col(
			$wpdb->prepare(
				"SELECT ID FROM {$wpdb->posts} WHERE ID IN ($placeholders) AND post_status != 'trash' AND post_type IN ('product', 'product_variation')",
				$ids
			)
		);
		// phpcs:enable

		$live_set = array_flip( $live_ids );

		$missing = array();
		foreach ( $cofe_products as $product ) {
			if ( empty( $product['storePrimaryId'] ) ) {
				continue;
			}
			if ( ! isset( $live_set[ (string) $product['storePrimaryId'] ] ) ) {
				$missing[] = $product;
			}
		}

		return $missing;
	}

	/**
	 * Merges the outcome of this batch into the persisted running status.
	 *
	 * @param int   $processed Number of products this batch inspected.
	 * @param int   $deleted   Number of products soft-deleted in this batch.
	 * @param array $failed    IDs that failed to soft-delete in this batch.
	 */
	private function update_running_status( int $processed, int $deleted, array $failed ): void {
		$status = json_decode( get_option( ACTIVECAMPAIGN_FOR_WOOCOMMERCE_PRODUCT_CLEANUP_RUNNING_STATUS_NAME ), true );
		if ( ! is_array( $status ) ) {
			$status = array();
		}

		$status['status_name']     = 'product_cleanup';
		$status['current_record']  = ( isset( $status['current_record'] ) ? (int) $status['current_record'] : 0 ) + $processed;
		$status['deleted_count']   = ( isset( $status['deleted_count'] ) ? (int) $status['deleted_count'] : 0 ) + $deleted;
		$status['failed_id_array'] = array_merge(
			isset( $status['failed_id_array'] ) && is_array( $status['failed_id_array'] ) ? $status['failed_id_array'] : array(),
			$failed
		);
		$status['last_update']     = wp_date( 'F d, Y - G:i:s e' );
		$status['is_running']      = true;

		update_option(
			ACTIVECAMPAIGN_FOR_WOOCOMMERCE_PRODUCT_CLEANUP_RUNNING_STATUS_NAME,
			wp_json_encode( $status )
		);
	}

	/**
	 * If no more `run_product_cleanup` cron events are queued, promote the
	 * running status to the "last run" slot and clear the scheduled flag.
	 */
	private function finalize_run_if_done(): void {
		if ( ! empty( wp_next_scheduled( ACTIVECAMPAIGN_FOR_WOOCOMMERCE_RUN_PRODUCT_CLEANUP_NAME ) ) ) {
			return;
		}

		$status = json_decode( get_option( ACTIVECAMPAIGN_FOR_WOOCOMMERCE_PRODUCT_CLEANUP_RUNNING_STATUS_NAME ), true );
		if ( ! is_array( $status ) ) {
			$status = array();
		}

		$status['end_time']    = wp_date( 'F d, Y - G:i:s e' );
		$status['is_running']  = false;
		$status['is_finished'] = true;

		update_option(
			ACTIVECAMPAIGN_FOR_WOOCOMMERCE_PRODUCT_CLEANUP_LAST_STATUS_NAME,
			wp_json_encode( $status )
		);
		delete_option( ACTIVECAMPAIGN_FOR_WOOCOMMERCE_PRODUCT_CLEANUP_RUNNING_STATUS_NAME );
		delete_option( ACTIVECAMPAIGN_FOR_WOOCOMMERCE_PRODUCT_CLEANUP_SCHEDULED_STATUS_NAME );

		$this->logger->info(
			'Product cleanup run finished.',
			array(
				'deleted_count'   => isset( $status['deleted_count'] ) ? $status['deleted_count'] : 0,
				'current_record'  => isset( $status['current_record'] ) ? $status['current_record'] : 0,
				'failed_id_array' => isset( $status['failed_id_array'] ) ? $status['failed_id_array'] : array(),
			)
		);
	}

	/**
	 * AJAX handler — flags the running job to stop at the next boundary.
	 */
	public function handle_cancel_cleanup(): void {
		if ( ! check_ajax_referer(
			'activecampaign_for_woocommerce_product_cleanup_form',
			'activecampaign_for_woocommerce_settings_nonce_field',
			false
		) ) {
			wp_send_json_error( 'The nonce appears to be invalid.' );
			return;
		}

		update_option( ACTIVECAMPAIGN_FOR_WOOCOMMERCE_PRODUCT_CLEANUP_STOP_CHECK_NAME, 'cancel' );
		wp_send_json_success( 'Product cleanup stop request processed.' );
	}

	/**
	 * AJAX handler — clears all cleanup state so the UI can start fresh.
	 */
	public function handle_reset_cleanup_status(): void {
		if ( ! check_ajax_referer(
			'activecampaign_for_woocommerce_product_cleanup_form',
			'activecampaign_for_woocommerce_settings_nonce_field',
			false
		) ) {
			wp_send_json_error( 'The nonce appears to be invalid.' );
			return;
		}

		delete_option( ACTIVECAMPAIGN_FOR_WOOCOMMERCE_PRODUCT_CLEANUP_RUNNING_STATUS_NAME );
		delete_option( ACTIVECAMPAIGN_FOR_WOOCOMMERCE_PRODUCT_CLEANUP_SCHEDULED_STATUS_NAME );
		delete_option( ACTIVECAMPAIGN_FOR_WOOCOMMERCE_PRODUCT_CLEANUP_STOP_CHECK_NAME );

		update_option(
			ACTIVECAMPAIGN_FOR_WOOCOMMERCE_PRODUCT_CLEANUP_LAST_STATUS_NAME,
			wp_json_encode(
				array(
					'status_name' => 'reset',
					'end_time'    => wp_date( 'F d, Y - G:i:s e' ),
					'is_running'  => false,
					'is_finished' => true,
				)
			)
		);

		wp_clear_scheduled_hook( ACTIVECAMPAIGN_FOR_WOOCOMMERCE_RUN_PRODUCT_CLEANUP_NAME );

		wp_send_json_success( 'Product cleanup data reset.' );
	}

	/**
	 * Returns true when the user has requested a stop via {@see handle_cancel_cleanup()}.
	 */
	private function check_for_stop(): bool {
		$stop_type = get_option( ACTIVECAMPAIGN_FOR_WOOCOMMERCE_PRODUCT_CLEANUP_STOP_CHECK_NAME );

		if ( empty( $stop_type ) ) {
			return false;
		}

		$this->logger->alert(
			'Product cleanup stop request found: cancelled.',
			array( 'stop_type' => $stop_type )
		);

		wp_clear_scheduled_hook( ACTIVECAMPAIGN_FOR_WOOCOMMERCE_RUN_PRODUCT_CLEANUP_NAME );

		$status = json_decode( get_option( ACTIVECAMPAIGN_FOR_WOOCOMMERCE_PRODUCT_CLEANUP_RUNNING_STATUS_NAME ), true );
		if ( ! is_array( $status ) ) {
			$status = array();
		}
		$status['status_name'] = 'cancelled';
		$status['end_time']    = wp_date( 'F d, Y - G:i:s e' );
		$status['is_running']  = false;
		$status['is_finished'] = true;
		update_option( ACTIVECAMPAIGN_FOR_WOOCOMMERCE_PRODUCT_CLEANUP_LAST_STATUS_NAME, wp_json_encode( $status ) );
		delete_option( ACTIVECAMPAIGN_FOR_WOOCOMMERCE_PRODUCT_CLEANUP_RUNNING_STATUS_NAME );
		delete_option( ACTIVECAMPAIGN_FOR_WOOCOMMERCE_PRODUCT_CLEANUP_SCHEDULED_STATUS_NAME );

		return true;
	}
}
