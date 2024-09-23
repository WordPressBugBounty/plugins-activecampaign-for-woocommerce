<?php

/**
 * Handles the historical sync process.
 * This will only be run by admin or cron so make sure all methods are admin only.
 *
 * @link       https://www.activecampaign.com/
 * @since      1.5.0
 *
 * @package    Activecampaign_For_Woocommerce
 * @subpackage Activecampaign_For_Woocommerce/includes
 */

 use Activecampaign_For_Woocommerce_Historical_Sync_Prep as Historical_Prep;
 use Activecampaign_For_Woocommerce_Historical_Sync_Runner_Cofe as Historical_Runner_Cofe;
 use Activecampaign_For_Woocommerce_Historical_Sync_Contacts as Historical_Contacts;
 use Activecampaign_For_Woocommerce_Logger as Logger;
 use Activecampaign_For_Woocommerce_Bulksync_Repository as Bulksync_Repository;
 use Activecampaign_For_Woocommerce_AC_Contact_Batch_Repository as AC_Contact_Batch_Repository;
 use Activecampaign_For_Woocommerce_Cofe_Order_Repository as Cofe_Order_Repository;

class Activecampaign_For_Woocommerce_Historical_Sync_Handler {
	use Activecampaign_For_Woocommerce_Order_Data_Gathering;

	/**
	 * The custom ActiveCampaign logger
	 *
	 * @var Logger
	 */
	private $logger;

	/**
	 * The AC contact batch repository.
	 *
	 * @var AC_Contact_Batch_Repository AC_Batch_Repository.
	 */
	private $contact_batch_repository;

	/**
	 * The bulksync repository object.
	 *
	 * @since 1.6.0
	 *
	 * @var Bulksync_Repository
	 */
	private $bulksync_repository;

	/**
	 * The COFE Order Repo
	 *
	 * @var Cofe_Order_Repository
	 */
	private $cofe_order_repository;

	/**
	 * constructor.
	 *
	 * @param     Logger|null                 $logger     The logger object.
	 * @param     AC_Contact_Batch_Repository $contact_batch_repository     The repository for contact batching.
	 * @param     Cofe_Order_Repository       $cofe_order_repository     The order repository object.
	 * @param     Bulksync_Repository         $bulksync_repository     The bulksync repository object.
	 */
	public function __construct(
		Logger $logger,
		AC_Contact_Batch_Repository $contact_batch_repository,
		Cofe_Order_Repository $cofe_order_repository,
		Bulksync_Repository $bulksync_repository
	) {
		if ( ! $logger ) {
			$this->logger = new Logger();
		} else {
			$this->logger = $logger;
		}

		$this->contact_batch_repository = $contact_batch_repository;
		$this->cofe_order_repository    = $cofe_order_repository;
		$this->bulksync_repository      = $bulksync_repository;
	}

	/**
	 * Runs the process to set existing records to ready for sync prep.
	 */
	public function ready_existing_data() {
		$historical_prep = new Historical_Prep();
		$historical_prep->mark_orders_for_prep();
	}

	/**
	 * Runs the preparation of data.
	 *
	 * @param mixed ...$args The passed arguments.
	 */
	public function prep_data( ...$args ) {
		delete_option( ACTIVECAMPAIGN_FOR_WOOCOMMERCE_HISTORICAL_SYNC_STOP_CHECK_NAME );
		$this->update_sync_running_status( 'orders', 'preparing' );
		$historical_prep = new Historical_Prep();
		try {
			$historical_prep->execute( $args );
		} catch ( Throwable $t ) {
			$logger = new Logger();
			$logger->notice(
				'An error was thrown in historical sync data prep',
				[
					'message' => $t->getMessage(),
					'trace'   => $t->getTrace(),
				]
			);
		}
		$historical_prep->queue_prepared_records();
		$this->update_sync_running_status( 'orders', 'queued/ready' );
	}

	/**
	 * Runs historical sync.
	 *
	 * @param mixed ...$args The passed arguments.
	 */
	public function run_sync( ...$args ) {
		// Stop check
		$stop_check = get_option( ACTIVECAMPAIGN_FOR_WOOCOMMERCE_HISTORICAL_SYNC_STOP_CHECK_NAME );

		if ( ! empty( $stop_check ) ) {
			// Stop found, do not run
			$this->update_sync_running_status( 'orders', 'stopped' );
		}

		$historical_prep = new Historical_Prep();
		$historical_prep->queue_prepared_records();

		try {
			$historical_runner_cofe = new Historical_Runner_Cofe(
				$this->logger,
				$this->cofe_order_repository
			);

			if ( isset( $args[0]['wc_order_id'] ) ) {
				$historical_runner_cofe->execute_one( $args[0]['wc_order_id'] );
			}

			if ( ! empty( $stop_check ) ) {
				return;
			}

			$st = $historical_runner_cofe->execute( $args );

			if ( ! isset( $st ) || ! $st ) {
				return;
			}
		} catch ( Throwable $t ) {
			$logger = new Logger();
			$logger->notice(
				'An error was thrown in historical sync sync process',
				[
					'message' => $t->getMessage(),
					'trace'   => $t->getTrace(),
				]
			);
		}

		$this->update_sync_running_status( 'orders', 'syncing' );
	}

	public function prep_historical_sync_contacts() {
		set_transient( 'activecampaign_for_woocommerce_hs_contacts', 0, 3600 );
	}

	/**
	 * Runs historical sync for contacts.
	 *
	 * @param mixed ...$args The passed arguments.
	 */
	public function run_historical_sync_contacts( ...$args ) {
		if ( false !== get_transient( 'activecampaign_for_woocommerce_hs_contacts' ) && null !== get_transient( 'activecampaign_for_woocommerce_hs_contacts' ) ) {
			$this->update_sync_running_status( 'contacts', 'syncing' );

			$historical_contacts = new Historical_Contacts(
				$this->logger,
				$this->contact_batch_repository
			);

			$historical_contacts->execute( $args );
		}

		// $this->update_sync_running_status( 'contacts', 'finished' );
	}

	/**
	 * Updates the sync running status for the info page. Saves as an array option.
	 *
	 * @param string $type Which status type.
	 * @param string $status The status.
	 */
	private function update_sync_running_status( $type, $status ) {
		$run_sync          = get_option( ACTIVECAMPAIGN_FOR_WOOCOMMERCE_HISTORICAL_SYNC_SCHEDULED_STATUS_NAME );
		$run_sync[ $type ] = $status;
		update_option(
			ACTIVECAMPAIGN_FOR_WOOCOMMERCE_HISTORICAL_SYNC_SCHEDULED_STATUS_NAME,
			$run_sync
		);
	}
}
