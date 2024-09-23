<?php
use Activecampaign_For_Woocommerce_Logger as Logger;

trait Activecampaign_For_Woocommerce_Historical_Status {


	/**
	 * The initializing status array.
	 *
	 * @since 1.6.0
	 *
	 * @var array
	 */
	private $status = [
		'success_count'                      => 0,
		'batch_limit'                        => 100, // How many records per batch (200 is the API limit)
		'start_time'                         => null, // WP date time
		'last_update'                        => null, // WP date time
		'end_time'                           => null, // WP date time
		'failed_order_id_array'              => [], // Array of failed IDs
		'incompatible_order_id_array'        => [], // Array of incompatible IDs
		'failed_subscription_id_array'       => [], // Array of failed IDs
		'incompatible_subscription_id_array' => [], // Array of incompatible IDs
		'is_running'                         => true,
		'contact_count'                      => 0,
		'contact_failed_count'               => 0,
		'contact_total'                      => 0,
	];

	/**
	 * Initialize the status.
	 */
	private function init_status() {
		if ( get_option( ACTIVECAMPAIGN_FOR_WOOCOMMERCE_HISTORICAL_SYNC_RUNNING_STATUS_NAME ) ) {
			$this->status = json_decode( get_option( ACTIVECAMPAIGN_FOR_WOOCOMMERCE_HISTORICAL_SYNC_RUNNING_STATUS_NAME ), 'array' );
			$this->update_sync_status();
		}

		$this->status['is_running'] = true;
		$this->update_sync_status();
	}

	/**
	 * Updates the sync statuses in the options for the info readout to use on the frontend.
	 * This is how the admin panel is able to tell where we are in the process and to keep record of the sync.
	 *
	 * @param     string $type     Indicates the type of update.
	 */
	private function update_sync_status( $type = '' ) {
		$logger = new Logger();
		try {
			$this->status['last_update'] = time();

			switch ( $type ) {
				case 'cancel':
				case 'finished':
					$this->status['end_time']   = wp_date( 'F d, Y - G:i:s e' );
					$this->status['is_running'] = false;
					update_option( ACTIVECAMPAIGN_FOR_WOOCOMMERCE_HISTORICAL_SYNC_RUNNING_STATUS_NAME, wp_json_encode( $this->status ) );
					$logger->debug(
						'Historical Sync Ended',
						[
							'status' => $this->status,
						]
					);
					break;
				case 'halted':
					$this->status['status_name'] = 'halt';
					$this->status['end_time']    = wp_date( 'F d, Y - G:i:s e' );
					$this->status['is_running']  = false;

					update_option( ACTIVECAMPAIGN_FOR_WOOCOMMERCE_HISTORICAL_SYNC_RUNNING_STATUS_NAME, wp_json_encode( $this->status ) );
					$logger->info(
						'Historical Sync was halted due to an error',
						[
							'status' => $this->status,
						]
					);
					die( 'There was a fatal error encountered and Historical Sync was halted. Please go back to the historical sync page and check your ActiveCampaign logs.' );
				default:
					update_option( ACTIVECAMPAIGN_FOR_WOOCOMMERCE_HISTORICAL_SYNC_RUNNING_STATUS_NAME, wp_json_encode( $this->status ) );
					break;
			}
		} catch ( Throwable $t ) {
			$logger->warning(
				'There was an issue attempting to save historical sync status.',
				[
					'message' => $t->getMessage(),
					'status'  => $this->status,
				]
			);
		}
	}


	/**
	 * Add a failed order to the status.
	 *
	 * @param     object   $order     The order.
	 * @param     WC_Order $wc_order     The WooCommerce order.
	 */
	private function add_failed_order_to_status( $order = null, $wc_order = null ) {
		try {
			if ( self::validate_object( $wc_order, 'get_id' ) && ! empty( $wc_order->get_id() ) ) {
				$this->status['failed_order_id_array'][] = $wc_order->get_id();
			} elseif ( self::validate_object( $order, 'get_id' ) && ! empty( $order->get_id() ) ) {
				$this->status['failed_order_id_array'][] = $order->get_id();
			} else {
				$this->status['failed_order_id_array'][] = $order;
			}
		} catch ( Throwable $t ) {
			$logger = new Logger();
			$logger->error(
				'An exception was thrown by historical sync while attempting to mark an order as failed.',
				[
					'message'          => $t->getMessage(),
					'suggested_action' => 'If this problem repeats please contact ActiveCampaign support.',
					'ac_code'          => 'THS_107',
					'order'            => $order,
				]
			);
		}
	}

	private function add_failed_subscription_to_status( $subscription = null, $wc_subscription = null ) {
		try {
			if ( self::validate_object( $wc_subscription, 'get_id' ) && ! empty( $wc_subscription->get_id() ) ) {
				$this->status['failed_subscription_id_array'][] = $wc_subscription->get_id();
			} elseif ( self::validate_object( $subscription, 'get_id' ) && ! empty( $subscription->get_id() ) ) {
				$this->status['failed_subscription_id_array'][] = $subscription->get_id();
			} else {
				$this->status['failed_subscription_id_array'][] = $subscription;
			}
		} catch ( Throwable $t ) {
			$logger = new Logger();
			$logger->error(
				'An exception was thrown by historical sync while attempting to mark an subscription as failed.',
				[
					'message'          => $t->getMessage(),
					'suggested_action' => 'If this problem repeats please contact ActiveCampaign support.',
					'ac_code'          => 'THS_107',
					'subscription'     => $subscription,
				]
			);
		}
	}

	/**
	 * Add a failed order to the status.
	 *
	 * @param     object   $order     The order.
	 * @param     WC_Order $wc_order     The WooCommerce order.
	 */
	private function add_incompatible_order_to_status( $order = null, $wc_order = null ) {
		try {
			if ( self::validate_object( $wc_order, 'get_id' ) && ! empty( $wc_order->get_id() ) ) {
				$this->status['incompatible_order_id_array'][] = $wc_order->get_id();
			} elseif ( self::validate_object( $order, 'get_id' ) && ! empty( $order->get_id() ) ) {
				$this->status['incompatible_order_id_array'][] = $order->get_id();
			} else {
				$this->status['incompatible_order_id_array'][] = $order;
			}
		} catch ( Throwable $t ) {
			$logger = new Logger();
			$logger->error(
				'An exception was thrown by historical sync while attempting to mark an order as incompatible.',
				[
					'message'          => $t->getMessage(),
					'suggested_action' => 'If this problem repeats please contact ActiveCampaign support.',
					'ac_code'          => 'THS_136',
					'order'            => $order,
				]
			);
		}
	}

	private function add_incompatible_subscription_to_status( $subscription = null, $wc_subscription = null ) {
		try {
			if ( self::validate_object( $wc_subscription, 'get_id' ) && ! empty( $wc_subscription->get_id() ) ) {
				$this->status['incompatible_subscription_id_array'][] = $wc_subscription->get_id();
			} elseif ( self::validate_object( $subscription, 'get_id' ) && ! empty( $subscription->get_id() ) ) {
				$this->status['incompatible_subscription_id_array'][] = $subscription->get_id();
			} else {
				$this->status['incompatible_subscription_id_array'][] = $subscription;
			}
		} catch ( Throwable $t ) {
			$logger = new Logger();
			$logger->error(
				'An exception was thrown by historical sync while attempting to mark an subscription as incompatible.',
				[
					'message'          => $t->getMessage(),
					'suggested_action' => 'If this problem repeats please contact ActiveCampaign support.',
					'ac_code'          => 'THS_136',
					'subscription'     => $subscription,
				]
			);
		}
	}

	/**
	 * Mark this order as failed in our database table.
	 *
	 * @param int $wc_order_id
	 */
	public function mark_order_as_refund( $wc_order_id ) {
		global $wpdb;
		$data = [ 'synced_to_ac' => self::STATUS_REFUND ];
		$wpdb->update(
			$wpdb->prefix . ACTIVECAMPAIGN_FOR_WOOCOMMERCE_TABLE_NAME,
			$data,
			[
				'wc_order_id' => $wc_order_id,
			]
		);
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
