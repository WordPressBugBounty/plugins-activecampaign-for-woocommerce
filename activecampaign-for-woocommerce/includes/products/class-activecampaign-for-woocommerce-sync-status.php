<?php
use Activecampaign_For_Woocommerce_Logger as Logger;

/**
 * www.activecampaign.com/
 *
 * @since      1.0.0
 *
 * @package    Activecampaign_For_Woocommerce
 * @subpackage Activecampaign_For_Woocommerce/includes/models
 */

class Activecampaign_For_Woocommerce_Sync_Status {


	public function __construct(
		$current_record = 0,
		$start_record = 0,
		$success_count = 0,
		$batch_limit = 20,
		$start_time = null,
		$last_update = null,
		$end_time = null,
		$failed_id_array = array(),
		$is_running = true,
		$status_name = 'none',
		$is_halted = false,
		$is_cancelled = false
	) {
		$this->start_record    = $start_record; // Required
		$this->current_record  = $current_record;
		$this->success_count   = $success_count;
		$this->batch_limit     = $batch_limit; // Required
		$this->start_time      = $start_time; // Required
		$this->last_update     = $last_update; // Required
		$this->end_time        = $end_time; // Required
		$this->failed_id_array = $failed_id_array; // Required
		$this->is_running      = $is_running; // May be useless, replace with next scheduled check
		$this->status_name     = $status_name; // Required
		$this->is_halted       = $is_halted; // Required
		$this->is_cancelled    = $is_cancelled; // Required
	}

	public static function from_map( $data ): Activecampaign_For_Woocommerce_Sync_Status {
		return new Activecampaign_For_Woocommerce_Sync_Status(
			$data['start_record'],
			$data['current_record'],
			$data['success_count'],
			$data['batch_limit'],
			$data['start_time'],
			$data['last_update'],
			$data['end_time'],
			$data['failed_order_id_array'],
			$data['is_running'],
			$data['status_name']
		);
	}

	/**
	 * Based on count not record number
	 *
	 * @var int
	 */
	public $current_record;

	/**
	 * The starting record offset
	 *
	 * @var int
	 */
	public $start_record;

	/**
	 * @var int|mixed
	 */
	public $success_count;

	/**
	 * How many records per batch (200 is the API limit)
	 *
	 * @var int
	 */
	public $batch_limit;


	/**
	 * WP Date time
	 *
	 * @var mixed|null
	 */
	public $start_time;

	/**
	 * WP date time
	 *
	 * @var mixed | null
	 */
	public $last_update;

	/**
	 * WP date time
	 *
	 * @var mixed|null
	 */
	public $end_time;

	/**
	 * If the sync is paused
	 *
	 * @var boolean
	 */
	public $is_paused;

	/**
	 * Running status
	 *
	 * @var boolean
	 */
	public $is_running;

	/**
	 * Array of failed IDs
	 *
	 * @var array
	 */
	public $failed_id_array;

	/**
	 * @var boolean
	 */
	public $is_finished;

	/**
	 * @var boolean
	 */
	public $is_halted;

	/**
	 * @var string
	 */
	public $status_name;

	/**
	 * If the sync is cancelled
	 *
	 * @var boolean
	 */
	public $is_cancelled;

	/**
	 * @var array
	 */
	public $failed_order_id_array;


	/**
	 * @param Activecampaign_For_Woocommerce_Sync_Status $status
	 * @return void
	 */
	public static function cancel( Activecampaign_For_Woocommerce_Sync_Status $status ) {
		$status->is_cancelled = true;
		$status->is_paused    = true;
		$status->is_running   = false;
		$status->is_finished  = true;
		$status->status_name  = 'cancel';

		self::update_status( ACTIVECAMPAIGN_FOR_WOOCOMMERCE_PRODUCT_SYNC_LAST_STATUS_NAME, $status );
		delete_option( ACTIVECAMPAIGN_FOR_WOOCOMMERCE_PRODUCT_SYNC_RUNNING_STATUS_NAME );
	}

	/**
	 * @param Activecampaign_For_Woocommerce_Sync_Status $status
	 * @return void
	 */
	public static function halt( Activecampaign_For_Woocommerce_Sync_Status $status ) {
		$status->is_running  = false;
		$status->is_finished = true;
		$status->is_halted   = true;
		$status->end_time    = wp_date( 'F d, Y - G:i:s e' );
		delete_option( ACTIVECAMPAIGN_FOR_WOOCOMMERCE_PRODUCT_SYNC_RUNNING_STATUS_NAME );
		self::update_status( ACTIVECAMPAIGN_FOR_WOOCOMMERCE_PRODUCT_SYNC_LAST_STATUS_NAME, $status );
		die( 'Halt the process. Major fault encountered.' );
	}

	/**
	 * @param Activecampaign_For_Woocommerce_Sync_Status $status
	 * @return void
	 */
	public static function finish( Activecampaign_For_Woocommerce_Sync_Status $status ) {
		$status->is_running  = false;
		$status->is_finished = true;
		$status->end_time    = wp_date( 'F d, Y - G:i:s e' );
		delete_option( ACTIVECAMPAIGN_FOR_WOOCOMMERCE_PRODUCT_SYNC_RUNNING_STATUS_NAME );
		self::update_status( ACTIVECAMPAIGN_FOR_WOOCOMMERCE_PRODUCT_SYNC_LAST_STATUS_NAME, $status );
	}

	/**
	 * @param Activecampaign_For_Woocommerce_Sync_Status $status
	 * @return void
	 */
	public static function reset( Activecampaign_For_Woocommerce_Sync_Status $status ) {
		$status->is_running  = false;
		$status->is_finished = false;
		$status->is_halted   = false;
		$status->end_time    = 'N/A';
		$status->status_name = 'reset';
		delete_option( ACTIVECAMPAIGN_FOR_WOOCOMMERCE_PRODUCT_SYNC_RUNNING_STATUS_NAME );
		self::update_status( ACTIVECAMPAIGN_FOR_WOOCOMMERCE_PRODUCT_SYNC_LAST_STATUS_NAME, $status );
		die( 'Reset the sync status.' );
	}

	/**
	 * Updates the sync statuses in the options for the info readout to use on the frontend.
	 * This is how the admin panel is able to tell where we are in the process and to keep record of the sync.
	 *
	 * @param string                                     $name
	 * @param Activecampaign_For_Woocommerce_Sync_Status $status
	 * @return void
	 */
	public static function update_status( string $name, Activecampaign_For_Woocommerce_Sync_Status $status ) {
		$status->last_update = time();

		try {
			update_option( $name, wp_json_encode( $status ) );
		} catch ( Throwable $t ) {
			$logger = new Logger();
			$logger->notice(
				'There was an issue attempting to save historical sync status.',
				array(
					'message' => $t->getMessage(),
					'status'  => $status,
				)
			);
		}
	}

}
