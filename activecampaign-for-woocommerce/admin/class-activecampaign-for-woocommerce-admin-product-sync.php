<?php

/**
 * The admin status page specific functionality of the plugin.
 *
 * @link       https://www.activecampaign.com/
 * @since      1.8.0
 *
 * @package    Activecampaign_For_Woocommerce
 * @subpackage Activecampaign_For_Woocommerce/admin
 */

use Activecampaign_For_Woocommerce_Logger as Logger;

/**
 * The admin-specific functionality of the plugin.
 *
 * Defines the plugin name, version, and two examples hooks for how to
 * enqueue the admin-specific stylesheet and JavaScript.
 *
 * @package    Activecampaign_For_Woocommerce
 * @subpackage Activecampaign_For_Woocommerce/admin
 * @author     acteamintegrations <team-integrations@activecampaign.com>
 */
trait Activecampaign_For_Woocommerce_Admin_Product_Sync {

	/**
	 * Logger class.
	 *
	 * @var Activecampaign_For_Woocommerce_Logger The logger class.
	 */
	private $logger;

	/**
	 * Initialize the class and set its properties.
	 */
	public function __construct() {
		$this->logger = new Logger();
	}


	/**
	 * Fetches the product sync page view.
	 *
	 * @since 1.9.0
	 */
	public function fetch_product_sync_page() {
		// This gets ported to the display page through require
		$activecampaign_for_woocommerce_product_sync_data            = $this->get_product_sync_page_data();
		$activecampaign_for_woocommerce_product_sync_data['options'] = $this->get_local_settings();

		wp_enqueue_script( $this->plugin_name . 'product-sync' );

		require_once plugin_dir_path( __FILE__ )
					 . 'views/activecampaign-for-woocommerce-product-sync.php';

	}

	public function get_product_sync_page_data() {
		$data['product_sync_running'] = false;
		try {
			$data['running_sync_status'] = json_decode(
				get_option( ACTIVECAMPAIGN_FOR_WOOCOMMERCE_PRODUCT_SYNC_RUNNING_STATUS_NAME ),
				'array'
			);

			$data['event_status'] = wp_get_scheduled_event( ACTIVECAMPAIGN_FOR_WOOCOMMERCE_RUN_PRODUCT_SYNC_NAME );
			$data['page_url']     = esc_url( admin_url( 'admin.php?page=' . ACTIVECAMPAIGN_FOR_WOOCOMMERCE_PLUGIN_NAME_SNAKE . '_product_sync&activesync=1' ) );
			$data['page_nonce']   = wp_create_nonce( 'activecampaign_for_woocommerce_product_sync_form' );
		} catch ( Throwable $t ) {
			$this->logger->error(
				'There was an issue loading the product sync page data.',
				[
					'message' => $t->getMessage(),
					'data'    => $data,
				]
			);
		}

		return $data;
	}

	/**
	 * Runs product sync in active mode.
	 */
	public function run_product_sync( ...$args ) {
		delete_option( ACTIVECAMPAIGN_FOR_WOOCOMMERCE_PRODUCT_SYNC_STOP_CHECK_NAME );
		do_action( 'activecampaign_for_woocommerce_run_sync_connection' );

		do_action( 'activecampaign_for_woocommerce_build_product_sync_schedules' );
	}

	/**
	 * Checks the status of historical sync and returns the result.
	 *
	 * @since 1.5.0
	 */
	public function get_product_sync_status() {
		try {
			$status          = json_decode( get_option( ACTIVECAMPAIGN_FOR_WOOCOMMERCE_PRODUCT_SYNC_RUNNING_STATUS_NAME ), 'array' );
			$last_sync       = json_decode( get_option( ACTIVECAMPAIGN_FOR_WOOCOMMERCE_PRODUCT_SYNC_LAST_STATUS_NAME ), 'array' );
			$is_cancelled    = get_option( ACTIVECAMPAIGN_FOR_WOOCOMMERCE_PRODUCT_SYNC_STOP_CHECK_NAME );
			$scheduled_event = wp_get_scheduled_event( 'activecampaign_for_woocommerce_run_product_sync' );
			$next_scheduled  = wp_next_scheduled( 'activecampaign_for_woocommerce_run_product_sync' );
			$cron_array      = $this->get_cron_array();
			$jobs_scheduled  = false;
			$job_count       = 0;

			foreach ( $cron_array as $job ) {
				if ( ! empty( $job['activecampaign_for_woocommerce_run_product_sync'] ) ) {
					$jobs_scheduled = true;
					$job_count      = count( $job['activecampaign_for_woocommerce_run_product_sync'] );
				}
			}

			if ( ! $status && ! $last_sync && ! $jobs_scheduled ) {
				$data = (object) array(
					'status'          => $status,
					'last_sync'       => $last_sync,
					'scheduled_event' => $scheduled_event,
					'next_scheduled'  => $next_scheduled,
					'is_cancelled'    => $is_cancelled,
					'is_scheduled'    => false,
				);

				wp_send_json_success( $data );
			}

			// If the sync is scheduled but has not run or not finished running
			if (
				true === $jobs_scheduled ||
				! empty( wp_get_scheduled_event( ACTIVECAMPAIGN_FOR_WOOCOMMERCE_RUN_PRODUCT_SYNC_NAME ) ) ||
				get_option( ACTIVECAMPAIGN_FOR_WOOCOMMERCE_PRODUCT_SYNC_SCHEDULED_STATUS_NAME )
			) {
				$data = (object) array(
					'status'          => $status,
					'last_sync'       => $last_sync,
					'scheduled_event' => $scheduled_event,
					'next_scheduled'  => $next_scheduled,
					'job_count'       => $job_count,
					'is_scheduled'    => $jobs_scheduled,
					'is_cancelled'    => $is_cancelled,
				);

				wp_send_json_success( $data );

			} else {
				$data = (object) array(
					'status'          => $status,
					'last_sync'       => $last_sync,
					'scheduled_event' => $scheduled_event,
					'next_scheduled'  => $next_scheduled,
					'job_count'       => $job_count,
					'is_scheduled'    => $jobs_scheduled,
					'is_cancelled'    => $is_cancelled,
				);

				wp_send_json_success( $data );
			}
		} catch ( Throwable $t ) {
			$logger = new Logger();
			$logger->warning(
				'There was an issue getting the historical sync status',
				array(
					'message'  => $t->getMessage(),
					'function' => 'check_historical_sync_status',
				)
			);
		}
	}

	/**
	 * Get the cron array.
	 *
	 * @return array|false|void
	 */
	private function get_cron_array() {
		$cron = get_option( 'cron' );
		if ( ! is_array( $cron ) ) {
			return array();
		}

		return $cron;
	}

	public function check_product_sync_status() {
		$this->get_product_sync_status();
	}
}
