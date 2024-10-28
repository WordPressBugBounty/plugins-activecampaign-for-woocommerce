<?php

/**
 * The file for all order based event handling.
 *
 * @link       https://www.activecampaign.com/
 *
 * @package    Activecampaign_For_Woocommerce
 * @subpackage Activecampaign_For_Woocommerce/includes/events
 */

use Activecampaign_For_Woocommerce_Logger as Logger;
use Activecampaign_For_Woocommerce_Ecom_Order_Repository as Order_Repository;
use Activecampaign_For_Woocommerce_Api_Client as Api_Client;

/**
 * The Order_Finished Event Class.
 *
 * @since      1.0.0
 * @package    Activecampaign_For_Woocommerce
 * @subpackage Activecampaign_For_Woocommerce/includes/events
 * @author     acteamintegrations <team-integrations@activecampaign.com>
 */
class Activecampaign_For_Woocommerce_Order_Action_Events {
	use Activecampaign_For_Woocommerce_Order_Data_Gathering;

	/**
	 * Verify this is necessary and used.
	 *
	 * @param object|string|WC_Order $order Args passed, could be an order or a string.
	 */
	public function execute_order_created( $order ) {
		$logger = new Logger();

		if ( isset( $order ) && self::validate_object( $order, 'get_id' ) ) {
			$order_id = $order->get_id();
		} else {
			$order_id = $order;
		}

		if ( isset( $order_id ) && ! empty( $order_id ) ) {

			$post_type = get_post_type( $order_id );

			set_transient( 'acforwc_order_created_hook', wp_date( DATE_ATOM ), 604800 );

			if ( isset( $order_id ) && null !== $order_id && ! empty( $order_id ) ) {
				if ( ! wp_get_scheduled_event( 'activecampaign_for_woocommerce_ready_new_order', [ 'order_id' => $order_id ] ) ) {
					wp_schedule_single_event(
						time() + 30,
						'activecampaign_for_woocommerce_ready_new_order',
						[ 'order_id' => $order_id ]
					);
				}

				$logger->debug(
					'Order created triggered and order set',
					[
						'post_type' => $post_type,
						'order_id'  => $order_id,
					]
				);
			}
		} else {
			$logger->warning(
				'The new order does not appear to be valid for sync to AC.',
				[
					'order_id' => $order_id,
				]
			);
		}

		return $order;
	}

	/**
	 * An order update is always processed backend so it will not interrupt customer process.
	 * Due to that, we should always process immediately and not on a cron to keep data from going stale
	 * or from losing the data due to quick status changes.
	 * FYI New orders do not go through this process.
	 *
	 * @param string|int $order_id The order id as passed from the hook.
	 */
	public function execute_order_updated( $order_id ) {
		$logger = new Logger();

		if ( isset( $order_id ) && ! empty( $order_id ) ) {
			$logger->debug_excess(
				'Order update triggered',
				[
					'order' => $order_id,
				]
			);

			$post_type = get_post_type( $order_id );

			// If it's a subscription, route through sub update
			if ( 'shop_subscription' === $post_type ) {
				$wc_subscription = $this->get_wc_subscription( $order_id );

				if ( $this->check_update_validity( $wc_subscription ) ) {
					do_action( 'activecampaign_for_woocommerce_route_order_update_to_subscription', [ $wc_subscription ] );
				}

				return;
			}

			// If it's not an order do nothing, this could be anything
			if ( 'shop_order' !== $post_type ) {
				return;
			}

			$wc_order = $this->get_wc_order( $order_id );

			// Check if order is valid
			if ( self::validate_object( $wc_order, 'get_data' ) && $this->check_update_validity( $wc_order ) ) {
				set_transient( 'acforwc_order_updated_hook', wp_date( DATE_ATOM ), 604800 );

				if ( ! wp_get_scheduled_event(
					'activecampaign_for_woocommerce_admin_sync_single_order_active',
					[
						'wc_order_id' => $order_id,
						'status'      => $wc_order->get_status(),
					]
				) &&
					! wp_get_scheduled_event(
						'activecampaign_for_woocommerce_admin_sync_single_order_status',
						[
							'wc_order_id' => $order_id,
							'status'      => $wc_order->get_status(),
						]
					) ) {
					wp_schedule_single_event(
						time() + 10,
						'activecampaign_for_woocommerce_admin_sync_single_order_active',
						[
							'wc_order_id' => $order_id,
							'status'      => $wc_order->get_status(),
						]
					);
				}
			} else {
				$logger->warning(
					'The updated order does not appear to be valid for sync to AC.',
					[
						'order_id' => $order_id,
					]
				);
			}
		}
	}

	/**
	 * Order status updates are processed through this function.
	 *
	 * @param int|string $order_id The order id.
	 * @param string     $from_status The status the order changed from.
	 * @param string     $to_status The status the order is changing to.
	 */
	public function execute_order_status_changed( $order_id, $from_status, $to_status ) {
		$logger = new Logger();

		if ( isset( $order_id ) && ! empty( $order_id ) ) {
			$logger->debug_excess(
				'Order status update triggered',
				[
					'order'        => $order_id,
					'order_status' => $from_status,
					'new_status'   => $to_status,
				]
			);

			$post_type = get_post_type( $order_id );

			// If it's a subscription, route through subscription update.
			if ( 'shop_subscription' === $post_type ) {
				$wc_subscription = $this->get_wc_subscription( $order_id );

				do_action( 'activecampaign_for_woocommerce_route_order_update_to_subscription', [ $wc_subscription ] );
				return;
			}

			// If it's not an order do nothing, this could be anything
			if ( 'shop_order' !== $post_type ) {
				$logger->debug_excess(
					'Order status update was triggered but the post is not a shop order type.',
					[
						'order_id'   => $order_id,
						'post_type'  => $post_type,
						'new_status' => $to_status,
					]
				);

				return;
			}

			$wc_order = $this->get_wc_order( $order_id );

			// Check if order is valid
			if ( self::validate_object( $wc_order, 'get_data' ) ) {
				set_transient( 'acforwc_order_updated_hook', wp_date( DATE_ATOM ), 604800 );

				if ( ! wp_get_scheduled_event(
					'activecampaign_for_woocommerce_admin_sync_single_order_status',
					[
						'wc_order_id' => $order_id,
						'status'      => $to_status,
					]
				) ) {
					wp_schedule_single_event(
						time() + 10,
						'activecampaign_for_woocommerce_admin_sync_single_order_status',
						[
							'wc_order_id' => $order_id,
							'status'      => $to_status,
						]
					);
				}
			}
		} else {
			$logger->warning(
				'The updated order does not appear to be valid for sync to AC.',
				[
					'order_id' => $order_id,
				]
			);
		}
	}

	public function execute_order_edit_status( $order_id, $new_status ) {
		$wc_order = $this->get_wc_order( $order_id );

		if ( self::validate_object( $wc_order, 'get_data' ) ) {
			set_transient( 'acforwc_order_updated_hook', wp_date( DATE_ATOM ), 604800 );

			if ( ! wp_get_scheduled_event(
				'activecampaign_for_woocommerce_admin_sync_single_order_status',
				[
					'wc_order_id' => $order_id,
					'event_type'  => 'status_' . $new_status,
				]
			) ) {
				wp_schedule_single_event(
					time() + 10,
					'activecampaign_for_woocommerce_admin_sync_single_order_status',
					[
						'wc_order_id' => $order_id,
						'status'      => $new_status,
					]
				);
			}
		}
	}

	/**
	 * The process used for an order refund. Technically this is the same as an order update.
	 * We pass this through a different function to note it in debug until we have relevant handling.
	 *
	 * @param string|int $order_id The order id.
	 * @param string|int $refund_id Refund id is passed and required but not used here.
	 */
	public function execute_order_updated_refund( $order_id, $refund_id ) {
		$logger = new Logger();

		try {
			$logger->debug_excess(
				'Refund order update triggered',
				[
					'order_id' => $order_id,
				]
			);

			$this->execute_order_updated( $order_id );
		} catch ( Throwable $t ) {
			$logger->warning(
				'There was an issue updating the order from a refund trigger.',
				[
					'order_id' => $order_id,
					'message'  => $t->getMessage(),
				]
			);
		}
	}

	/**
	 * Stripe documentation is unhelpful but we need to process its updates. We do not use the response but it must be
	 * included in the function args.
	 *
	 * @param object|string   $sripe_response Stripe response, unused.
	 * @param string|WC_Order $order Could be order object or order id, stripe does not say.
	 */
	public function execute_order_updated_stripe( $sripe_response, $order ) {
		$logger = new Logger();

		try {
			if ( isset( $sripe_response ) && isset( $order ) ) {
				$wc_order = $this->get_wc_order( $order ); // Be sure we have the WC Order
				$order_id = $wc_order->get_id();
				if ( isset( $order_id ) && ! empty( $order_id ) ) {
					$logger->debug_excess(
						'Stripe verified order update triggered',
						[
							'order_id' => $order_id,
						]
					);

					$this->execute_order_updated( $order_id );
				}
			}
		} catch ( Throwable $t ) {
			$logger->warning(
				'There was an issue updating the order from stripe.',
				[
					'order'   => $order,
					'message' => $t->getMessage(),
				]
			);
		}
	}

	/**
	 * Execute AC steps for an order deleted event. Triggered by action.
	 *
	 * @param string|int $order_id The arguments.
	 */
	public function execute_order_deleted( $order_id ) {
		$logger = new Logger();
		try {
			if ( isset( $order_id ) && ! empty( $order_id ) ) {
				$post_type = get_post_type( $order_id );

				// If it's not an order just ignore it, this could be anything
				if ( 'shop_order' !== $post_type ) {
					return;
				}

				set_transient( 'acforwc_order_deleted_hook', wp_date( DATE_ATOM ), 604800 );

				$logger->debug(
					'Order delete triggered',
					[
						'order' => $order_id,
					]
				);
				// Delete an order for deepdata
				$settings = get_option( ACTIVECAMPAIGN_FOR_WOOCOMMERCE_DB_SETTINGS_NAME );

				$api_uri = isset( $settings['api_url'] ) ? $settings['api_url'] : null;
				$api_key = isset( $settings['api_key'] ) ? $settings['api_key'] : null;

				$order_repository = new Order_Repository( new Api_Client( $api_uri, $api_key, $logger ) );

				// Try to find the order in AC
				$ac_order = $order_repository->find_by_externalid( $order_id );
				if ( isset( $ac_order ) && self::validate_object( $ac_order, 'get_id' ) ) {
					$ac_order_id = $ac_order->get_id();

					// If the order exists delete it from AC
					$order_repository->delete( $ac_order_id );

					// Delete a local order for COFE
					$this->delete_order_from_local_table( $order_id );
				}
			}
		} catch ( Throwable $t ) {
			$logger->warning(
				'There was an issue deleting the order from AC.',
				[
					'order_id' => $order_id,
					'message'  => $t->getMessage(),
				]
			);
		}
	}

	/**
	 * Removes a WC deleted order from the AC table.
	 * This can be used for new or historical sync orders.
	 *
	 * @param     mixed ...$args The passed arguments.
	 */
	public function delete_order_from_local_table( ...$args ) {
		if ( isset( $args[0] ) ) {
			$order_id = $args[0];

			// Find the post type in
			$post_type = get_post_type( $order_id );

			// Make sure it's a shop order
			if ( 'shop_order' !== $post_type ) {
				return;
			}

			global $wpdb;
			$wpdb->delete( $wpdb->prefix . ACTIVECAMPAIGN_FOR_WOOCOMMERCE_TABLE_NAME, [ 'wc_order_id' => $order_id ] );
		}
	}

	/**
	 * Check to make sure we are not double syncing the same data.
	 * True means that it is valid for update.
	 *
	 * @param WC_Subscription|WC_Order $wc_order The order or subscription. Both carry these functions.
	 *
	 * @return bool
	 */
	private function check_update_validity( $wc_order ) {
		$logger = new Logger();
		$last_synced      = $wc_order->get_meta( 'ac_order_last_synced_time' );
		$last_status      = $wc_order->get_meta( 'ac_last_synced_status' );
		$ac_datahash      = $wc_order->get_meta( 'ac_datahash' );
		try {
			$current_datahash = md5( wp_json_encode( $wc_order->get_data() ) );
		} catch ( Throwable $t ) {
			$logger->warning(
				'There was an issue attempting to create a datahash for the update validity on the order update.',
				[
					'order_id' => $wc_order->get_id(),
				]
			);
			// If this fails return true. Better to double sync than miss it entirely.
		}

		try {
			if ( ! empty( $last_synced ) && time() - intval( $last_synced ) < 120 ) {
				$last_synced_too_soon = true;
			} else {
				$last_synced_too_soon = false;
			}
		} catch ( Throwable $t ) {
			$logger->warning(
				'There was an issue attempting to validate the order update.',
				[
					'order_id' => $wc_order->get_id(),
				]
			);
			// If this fails return true. Better to double sync than miss it entirely.
		}

		try {
			if (
				isset( $last_status ) &&
				$last_status === $wc_order->get_status() &&
				$last_synced_too_soon &&
				$ac_datahash === $current_datahash
			) {
				return false;
			}
		} catch ( Throwable $t ) {

			$logger->warning(
				'There was an issue attempting to validate the order update.',
				[
					'order_id' => $wc_order->get_id(),
				]
			);
			// If this fails return true. Better to double sync than miss it entirely.
		}

		return true;
	}
}
