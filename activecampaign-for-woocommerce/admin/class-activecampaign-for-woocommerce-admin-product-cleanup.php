<?php

/**
 * The admin product cleanup functionality of the plugin.
 *
 * @link       https://www.activecampaign.com/
 *
 * @package    Activecampaign_For_Woocommerce
 * @subpackage Activecampaign_For_Woocommerce/admin
 */

use Activecampaign_For_Woocommerce_Logger as Logger;

/**
 * Product cleanup trait.
 *
 * Provides the admin page and background job responsible for reconciling
 * products stored in AC against WooCommerce and soft-deleting any AC
 * product that no longer exists (or has been trashed) in WooCommerce.
 *
 * @package    Activecampaign_For_Woocommerce
 * @subpackage Activecampaign_For_Woocommerce/admin
 * @author     acteamintegrations <team-integrations@activecampaign.com>
 */
trait Activecampaign_For_Woocommerce_Admin_Product_Cleanup {

	/**
	 * Fetches the product cleanup page view.
	 */
	public function fetch_product_cleanup_page() {
		$activecampaign_for_woocommerce_product_cleanup_data            = $this->get_product_cleanup_page_data();
		$activecampaign_for_woocommerce_product_cleanup_data['options'] = $this->get_local_settings();

		wp_enqueue_script( $this->plugin_name . 'product-cleanup' );

		require_once plugin_dir_path( __FILE__ )
			. 'views/activecampaign-for-woocommerce-product-cleanup.php';
	}

	/**
	 * Gathers the data required to render the product cleanup page.
	 *
	 * @return array
	 */
	public function get_product_cleanup_page_data() {
		$data                            = array();
		$data['product_cleanup_running'] = false;

		try {
			$data['running_cleanup_status'] = json_decode(
				get_option( ACTIVECAMPAIGN_FOR_WOOCOMMERCE_PRODUCT_CLEANUP_RUNNING_STATUS_NAME ),
				true
			);

			$data['last_cleanup_status'] = json_decode(
				get_option( ACTIVECAMPAIGN_FOR_WOOCOMMERCE_PRODUCT_CLEANUP_LAST_STATUS_NAME ),
				true
			);

			$data['is_cancelled']   = get_option( ACTIVECAMPAIGN_FOR_WOOCOMMERCE_PRODUCT_CLEANUP_STOP_CHECK_NAME );
			$data['event_status']   = wp_get_scheduled_event( ACTIVECAMPAIGN_FOR_WOOCOMMERCE_RUN_PRODUCT_CLEANUP_NAME );
			$data['next_scheduled'] = wp_next_scheduled( ACTIVECAMPAIGN_FOR_WOOCOMMERCE_RUN_PRODUCT_CLEANUP_NAME );
			$data['page_url']       = esc_url( admin_url( 'admin.php?page=' . ACTIVECAMPAIGN_FOR_WOOCOMMERCE_PLUGIN_NAME_SNAKE . '_product_cleanup' ) );
			$data['page_nonce']     = wp_create_nonce( 'activecampaign_for_woocommerce_product_cleanup_form' );
		} catch ( Throwable $t ) {
			$logger = new Logger();
			$logger->error(
				'There was an issue loading the product cleanup page data.',
				array(
					'message' => $t->getMessage(),
					'data'    => $data,
				)
			);
		}

		return $data;
	}

	/**
	 * Kicks off the product cleanup job.
	 *
	 * Clears any lingering "stop" flag from a previous run, then dispatches the
	 * hooks that verify the AC connection and schedule the cleanup batches.
	 *
	 * @param mixed ...$args
	 */
	public function run_product_cleanup( ...$args ) {
		if ( ! $this->validate_request_nonce( 'activecampaign_for_woocommerce_product_cleanup_form' ) ) {
			wp_send_json_error( 'The nonce appears to be invalid.' );
			return;
		}

		delete_option( ACTIVECAMPAIGN_FOR_WOOCOMMERCE_PRODUCT_CLEANUP_STOP_CHECK_NAME );
		do_action( 'activecampaign_for_woocommerce_run_sync_connection' );
		do_action( 'activecampaign_for_woocommerce_build_product_cleanup_schedules' );
		wp_send_json_success( 'Product cleanup scheduled.' );
	}

	/**
	 * Returns the current status of the product cleanup job (AJAX endpoint).
	 *
	 * Terminates with wp_send_json_success() carrying the payload the cleanup
	 * page's JS polls for.
	 */
	public function get_product_cleanup_status() {
		try {
			$status          = json_decode( get_option( ACTIVECAMPAIGN_FOR_WOOCOMMERCE_PRODUCT_CLEANUP_RUNNING_STATUS_NAME ), true );
			$last_run        = json_decode( get_option( ACTIVECAMPAIGN_FOR_WOOCOMMERCE_PRODUCT_CLEANUP_LAST_STATUS_NAME ), true );
			$is_cancelled    = get_option( ACTIVECAMPAIGN_FOR_WOOCOMMERCE_PRODUCT_CLEANUP_STOP_CHECK_NAME );
			$scheduled_event = wp_get_scheduled_event( ACTIVECAMPAIGN_FOR_WOOCOMMERCE_RUN_PRODUCT_CLEANUP_NAME );
			$next_scheduled  = wp_next_scheduled( ACTIVECAMPAIGN_FOR_WOOCOMMERCE_RUN_PRODUCT_CLEANUP_NAME );
			$cron_array      = $this->get_cron_array();
			$jobs_scheduled  = false;
			$job_count       = 0;

			foreach ( $cron_array as $job ) {
				if ( ! empty( $job[ ACTIVECAMPAIGN_FOR_WOOCOMMERCE_RUN_PRODUCT_CLEANUP_NAME ] ) ) {
					$jobs_scheduled = true;
					$job_count      = count( $job[ ACTIVECAMPAIGN_FOR_WOOCOMMERCE_RUN_PRODUCT_CLEANUP_NAME ] );
				}
			}

			$is_scheduled = $jobs_scheduled
				|| ! empty( $scheduled_event )
				|| (bool) get_option( ACTIVECAMPAIGN_FOR_WOOCOMMERCE_PRODUCT_CLEANUP_SCHEDULED_STATUS_NAME );

			wp_send_json_success(
				(object) array(
					'status'          => $status,
					'last_run'        => $last_run,
					'scheduled_event' => $scheduled_event,
					'next_scheduled'  => $next_scheduled,
					'job_count'       => $job_count,
					'is_scheduled'    => $is_scheduled,
					'is_cancelled'    => $is_cancelled,
				)
			);
		} catch ( Throwable $t ) {
			$logger = new Logger();
			$logger->warning(
				'There was an issue getting the product cleanup status.',
				array(
					'message'  => $t->getMessage(),
					'function' => 'get_product_cleanup_status',
				)
			);
			wp_send_json_error( 'There was an issue getting the product cleanup status.' );
		}
	}

	/**
	 * AJAX status check wrapper for the cleanup job.
	 */
	public function check_product_cleanup_status() {
		$this->get_product_cleanup_status();
	}
}
