<?php

/**
 * When a new subscription is created this event class is triggered.
 *
 * @link       https://www.activecampaign.com/
 * @since      1.0.0
 *
 * @package    Activecampaign_For_Woocommerce
 * @subpackage Activecampaign_For_Woocommerce/includes/events
 */

use Activecampaign_For_Woocommerce_Logger as Logger;
use Activecampaign_For_Woocommerce_Synced_Status_Interface as Synced_Status;

/**
 * The Subscription Event Class.
 *
 * @since      1.0.0
 * @package    Activecampaign_For_Woocommerce
 * @subpackage Activecampaign_For_Woocommerce/includes/events
 * @author     acteamintegrations <team-integrations@activecampaign.com>
 */
class Activecampaign_For_Woocommerce_Subscription_Events implements Synced_Status {
	use Activecampaign_For_Woocommerce_Data_Validation,
		Activecampaign_For_Woocommerce_Synced_Status_Handler;

	/**
	 * A new subscription is created from checkout.
	 * This should have actions to sync a subscription to COFE.
	 *
	 * @param     WC_Subscription $subscription
	 * @param     WC_Order        $wc_order
	 * @param     WC_Cart         $recurring_cart
	 */
	public function execute_woocommerce_checkout_subscription_created( WC_Subscription $subscription, WC_Order $wc_order, WC_Cart $recurring_cart ) {
		global $wpdb;

		$logger = new Logger();

		$order_id = $subscription->get_id(); // This is actually the ID for a subscription but it's handled as an order.
		// phpcs:disable
		$stored_row =$wpdb->get_row(
			$wpdb->prepare(
				'SELECT id, wc_order_id, synced_to_ac FROM ' . $wpdb->prefix . ACTIVECAMPAIGN_FOR_WOOCOMMERCE_TABLE_NAME . ' 
					WHERE wc_order_id = %d LIMIT 1',
				[ $order_id ]
			),
			'ARRAY_A'
		);
		// phpcs:enable

		if ( isset( $stored_row['id'] ) && ! empty( $stored_row['id'] ) ) {
			$stored_id                  = $stored_row['id'];
			$stored_row['synced_to_ac'] = self::STATUS_SUBSCRIPTION_UNSYNCED;

			$wpdb->update(
				$wpdb->prefix . ACTIVECAMPAIGN_FOR_WOOCOMMERCE_TABLE_NAME,
				$stored_row,
				[
					'id' => $stored_id,
				]
			);
		}
	}

	/**
	 * Subscriptions were created for the order. This may be needed if an order is created through API.
	 *
	 * @param     WC_Order $wc_order
	 */
	// public function execute_subscription_created_for_order( WC_Order $wc_order ) {
	// $logger = new Logger();
	// if ( isset( $wc_order ) && self::validate_object( $wc_order, 'get_id' ) ) {
	// $logger->dev( 'Triggered the execute_subscription_created_for_order action. Look into removal of this. Is there any case where we need this?', [ $wc_order->get_id() ] );
	// } else {
	// $logger->dev( 'Triggered the execute_subscription_created_for_order action but it carries no order. Look into removal of this. Is there any case where we need this?' );
	// }
	// }

	/**
	 * Any update to the subscription status happens.
	 *
	 * Happens on suspended action
	 * Happens on reactivate
	 * Something in renewal billing causes a status update of on hold
	 *
	 * @param     WC_Subscription $wc_subscription The subscription object.
	 * @param     string          $new_status The new status.
	 * @param     string          $old_status The old status.
	 */
	public function execute_woocommerce_subscription_status_updated( WC_Subscription $wc_subscription, $new_status = null, $old_status = null ) {
		$logger = new Logger();
		global $wpdb;
		$wc_subscription = $this->get_wc_subscription_object( $wc_subscription );

		if ( false === $wc_subscription || is_null( $wc_subscription ) ) {
			return;
		}

		try {
			$subscription_id = $wc_subscription->get_id();

			$logger->debug(
				'Subscription updated triggered and order set',
				[
					'subscription_id' => $subscription_id,
				]
			);

			$order_id = $wc_subscription->get_id(); // This is actually the ID for a subscription but it's handled as an order.
			// phpcs:disable
			$stored_row = $wpdb->get_row(
				$wpdb->prepare(
					'SELECT id, wc_order_id, synced_to_ac FROM ' . $wpdb->prefix . ACTIVECAMPAIGN_FOR_WOOCOMMERCE_TABLE_NAME . ' 
					WHERE wc_order_id = %d LIMIT 1',
					[ $order_id ]
				),
				'ARRAY_A'
			);
			// phpcs:enable

			if ( isset( $subscription_id ) && null !== $subscription_id && ! empty( $subscription_id ) ) {
				if ( ! wp_get_scheduled_event( ACTIVECAMPAIGN_FOR_WOOCOMMERCE_RUN_NEW_ORDER_SYNC_NAME, [ 'wc_order_id' => $subscription_id ] ) ) {
					wp_schedule_single_event(
						time() + 30,
						'activecampaign_for_woocommerce_update_subscription',
						[ 'wc_order_id' => $subscription_id ]
					);
				}
			}
		} catch ( Throwable $t ) {
			$logger->warning(
				'There was an error thrown while trying to run the subscription update hook.',
				[
					'message' => $t->getMessage(),
					'ac_code' => 'SE_146',
				]
			);
		}
	}

	/**
	 * The trial has ended.
	 *
	 * @param string $subscription_id The subscription ID.
	 */
	public function execute_woocommerce_scheduled_subscription_trial_end( $subscription_id ) {
		$logger = new Logger();
		global $wpdb;
		$wc_subscription = $this->get_wc_subscription_object( $subscription_id );

		if ( false === $wc_subscription || is_null( $wc_subscription ) ) {
			return;
		}

		$logger->debug(
			'Subscription trial end triggered and order set',
			[
				'subscription_id' => $subscription_id,
			]
		);

		try {
			$order_id = $wc_subscription->get_id(); // This is actually the ID for a subscription but it's handled as an order.
			// phpcs:disable
			$stored_row = $wpdb->get_row(
				$wpdb->prepare(
					'SELECT id, wc_order_id, synced_to_ac FROM ' . $wpdb->prefix . ACTIVECAMPAIGN_FOR_WOOCOMMERCE_TABLE_NAME . ' 
					WHERE wc_order_id = %d LIMIT 1',
					[ $order_id ]
				),
				'ARRAY_A'
			);
			// phpcs:enable

			if ( isset( $subscription_id ) && null !== $subscription_id && ! empty( $subscription_id ) ) {
				if ( ! wp_get_scheduled_event( ACTIVECAMPAIGN_FOR_WOOCOMMERCE_RUN_NEW_ORDER_SYNC_NAME, [ 'wc_order_id' => $subscription_id ] ) ) {
					wp_schedule_single_event(
						time() + 30,
						'activecampaign_for_woocommerce_update_subscription',
						[ 'wc_order_id' => $subscription_id ]
					);
				}
			}
		} catch ( Throwable $t ) {
			$logger->warning(
				'There was an error thrown while trying to run the subscription trial end hook.',
				[
					'message' => $t->getMessage(),
					'ac_code' => 'SE_224',
				]
			);
		}
	}



	/**
	 * TODO: Action for moving an order record to a subscription record
	 *
	 * @param string $subscription_id
	 */
	public function trigger_order_to_subscription( $subscription_id ) {
		$wc_subscription = $this->get_wc_subscription_object( $subscription_id );
		$this->execute_woocommerce_subscription_status_updated( $wc_subscription );
	}

	private function get_wc_subscription_object( $subscription ) {
		if ( isset( $subscription ) && ! empty( $subscription ) ) {
			$wc_subscription = wcs_get_subscription( $subscription );

			if ( ! empty( $wc_subscription->get_id() ) ) {
				return $wc_subscription;
			}
		}

		return false;
	}
}
