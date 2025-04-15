<?php

/**
 * The Ecom Product Factory file.
 *
 * @link       https://www.activecampaign.com/
 * @since      1.0.0
 * @package    Activecampaign_For_Woocommerce
 * @subpackage Activecampaign_For_Woocommerce/
 */

use Activecampaign_For_Woocommerce_Logger as Logger;

trait Activecampaign_For_Woocommerce_Synced_Status_Handler {

	/**
	 * @var mixed The readable status mapping.
	 */
	private $readable_status_mapping = array(
		0  => array(
			'title' => 'Unsynced',
			'help'  => 'This record has never been synced to ActiveCampaign.',
		),
		1  => array(
			'title' => 'Synced',
			'help'  => 'This record has been synced to ActiveCampaign. This is currently its final state.',
		),
		2  => array(
			'title' => 'On Hold',
			'help'  => 'This is an on hold record or on hold order. Skip it for now until an event marks it available to sync again.',
		),
		3  => array(
			'title' => 'Historical Sync Pending',
			'help'  => 'Historical sync is pending for this record. It is in a scheduled state.',
		),
		4  => array(
			'title' => 'Historical Sync Preparation',
			'help'  => 'This record is ready for historical sync preparation. It has been marked to sync but has not yet been scheduled.',
		),
		5  => array(
			'title' => 'Historical Sync Finished',
			'help'  => 'Historical sync has finished this record but the sync may still be running.',
		),
		6  => array(
			'title' => 'Records Incompatible',
			'help'  => 'Historical sync could not collect required data or could not find the customer/order records.',
		),
		7  => array(
			'title' => 'Subscription order',
			'help'  => 'This is a subscription record. It will not get resynced without a direct change.',
		),
		8  => array(
			'title' => 'Refund order, unable to currently sync',
			'help'  => 'One or more items on this record have been refunded. ActiveCampaign is unable to process them currently.',
		),
		9  => array(
			'title' => 'Sync Failed, will not try again',
			'help'  => 'Sync for this record has permanently failed. Please check logs for explanation. This record will not be synced again until marked otherwise.',
		),

		20 => array(
			'title' => 'Abandoned cart unsynced',
			'help'  => 'This abandoned cart has not attempted to sync or may not be ready to be synced.',
		),
		21 => array(
			'title' => 'Abandoned cart synced',
			'help'  => 'This abandoned cart has successfully been synced.',
		),
		22 => array(
			'title' => 'Abandoned cart manually synced',
			'help'  => 'This abandoned cart was synced manually.',
		),
		23 => array(
			'title' => 'Abandoned cart recovered, not synced',
			'help'  => 'This order was abandoned, recovered, but has not been synced.',
		),
		24 => array(
			'title' => 'Abandoned cart recovered, synced',
			'help'  => 'This order was abandoned, recovered, and synced.',
		),
		26 => array(
			'title' => 'Abandoned cart failed sync',
			'help'  => 'This abandoned cart failed to sync. Please check your logs for explanation.',
		),
		27 => array(
			'title' => 'Abandoned cart failed sync twice',
			'help'  => 'This abandoned cart failed to sync twice. Please check your logs for explanation.',
		),
		28 => array(
			'title' => 'Abandoned cart could not reach ActiveCampaign, retry',
			'help'  => 'This abandoned cart failed to sync because ActiveCampaign was unreachable. There may be an issue reaching the API. Please check your logs for explanation.',
		),
		29 => array(
			'title' => 'Abandoned cart could not reach ActiveCampaign permanently',
			'help'  => 'This abandoned cart failed to sync multiple times and will not be synced again. Please check your logs for explanation.',
		),
		30 => array(
			'title' => 'Subscription unsynced',
			'help'  => 'Record is stored but has not yet been synced.',
		),
		31 => array(
			'title' => 'Subscription synced',
			'help'  => 'Record was last synced as a new recurring order.',
		),
		32 => array(
			'title' => 'Subscription pending historical sync',
			'help'  => 'Record is ready to be synced and waiting in queue.',
		),
		33 => array(
			'title' => 'Subscription being prepared for historical sync',
			'help'  => 'Record is waiting for validation and compatibility. It has been marked to sync but has not yet been scheduled.',
		),
		35 => array(
			'title' => 'Subscription historically synced',
			'help'  => 'Record was last synced historically.',
		),
		38 => array(
			'title' => 'Subscription incompatible with ActiveCampaign',
			'help'  => 'This subscription is incompatible with ActiveCampaign. It may be missing or have unusable required data.',
		),
		39 => array(
			'title' => 'Subscription failed sync to ActiveCampaign',
			'help'  => 'This record failed to sync to ActiveCampaign. Please check logs to verify reason.',
		),
		86 => array(
			'title' => 'Record deleted from WooCommerce',
			'help'  => 'This record may have been deleted from WooCommerce. It will be removed from the table.',
		),
	);

	public function get_readable_sync_status( $status_ref ) {
		$status_ref = (int) $status_ref;
		$mappings   = $this->readable_status_mapping;

		foreach ( $mappings as $local_numeric => $readable ) {
			if ( $status_ref === $local_numeric ) {
				return $readable;
			}
		}
	}

	public function get_readable_sync_status_title( $status_ref ) {
		$return = $this->get_readable_sync_status( $status_ref );

		return $return['title'];
	}

	public function get_readable_sync_status_help( $status_ref ) {
		$return = $this->get_readable_sync_status( $status_ref );

		return $return['help'];
	}

	/**
	 * Mark this order as failed in our database table.
	 *
	 * @param int $wc_order_id
	 */
	public function mark_order_as_failed( $wc_order_id ) {
		global $wpdb;
		$data = array( 'synced_to_ac' => self::STATUS_FAIL );
		$wpdb->update(
			$wpdb->prefix . ACTIVECAMPAIGN_FOR_WOOCOMMERCE_TABLE_NAME,
			$data,
			array(
				'wc_order_id' => $wc_order_id,
			)
		);
	}

	/**
	 * Mark this order as incompatible in our database table.
	 *
	 * @param int $wc_order_id
	 */
	public function mark_order_as_incompatible( $wc_order_id ) {
		global $wpdb;
		$data = array( 'synced_to_ac' => self::STATUS_SYNC_INCOMPATIBLE );
		$wpdb->update(
			$wpdb->prefix . ACTIVECAMPAIGN_FOR_WOOCOMMERCE_TABLE_NAME,
			$data,
			array(
				'wc_order_id' => $wc_order_id,
			)
		);
	}

	/**
	 * Mark this order as failed in our database table.
	 *
	 * @param int $wc_order_id
	 */
	public function mark_subscription_as_failed( $wc_order_id ) {
		global $wpdb;
		$data = array( 'synced_to_ac' => self::STATUS_SUBSCRIPTION_FAILED_SYNC );
		$wpdb->update(
			$wpdb->prefix . ACTIVECAMPAIGN_FOR_WOOCOMMERCE_TABLE_NAME,
			$data,
			array(
				'wc_order_id' => $wc_order_id,
			)
		);
		$logger = new Logger();
		$logger->info( 'The following subscription failed to sync', array( $wc_order_id ) );
	}

	/**
	 * Mark this order as incompatible in our database table.
	 *
	 * @param int $wc_order_id
	 */
	public function mark_subscription_as_incompatible( $wc_order_id ) {
		global $wpdb;
		$data = array( 'synced_to_ac' => self::STATUS_SUBSCRIPTION_INCOMPATIBLE );
		$wpdb->update(
			$wpdb->prefix . ACTIVECAMPAIGN_FOR_WOOCOMMERCE_TABLE_NAME,
			$data,
			array(
				'wc_order_id' => $wc_order_id,
			)
		);
	}

	/**
	 * Mark this order as incompatible in our database table for historical sync records.
	 *
	 * @param int $wc_order_id
	 */
	public function mark_order_as_historical_incompatible( $wc_order_id ) {
		global $wpdb;
		$data = array( 'synced_to_ac' => self::STATUS_HISTORICAL_SYNC_INCOMPATIBLE );
		$wpdb->update(
			$wpdb->prefix . ACTIVECAMPAIGN_FOR_WOOCOMMERCE_TABLE_NAME,
			$data,
			array(
				'wc_order_id' => $wc_order_id,
			)
		);
	}

	public function mark_subscription_as_historical_incompatible( $wc_order_id ) {
		global $wpdb;
		$data = array( 'synced_to_ac' => self::STATUS_SUBSCRIPTION_INCOMPATIBLE );
		$wpdb->update(
			$wpdb->prefix . ACTIVECAMPAIGN_FOR_WOOCOMMERCE_TABLE_NAME,
			$data,
			array(
				'wc_order_id' => $wc_order_id,
			)
		);
	}
	/**
	 * Mark this order as unsynced because the overall request failed, it will have to be synced again.
	 *
	 * @param int $wc_order_id
	 */
	public function mark_order_as_pending( $wc_order_id ) {
		global $wpdb;
		$data = array( 'synced_to_ac' => self::STATUS_HISTORICAL_SYNC_QUEUE );
		$wpdb->update(
			$wpdb->prefix . ACTIVECAMPAIGN_FOR_WOOCOMMERCE_TABLE_NAME,
			$data,
			array(
				'wc_order_id' => $wc_order_id,
			)
		);
	}

	private function mark_abandoned_cart_network_failed( $abandoned_cart ) {
		global $wpdb;
		$logger = new Logger();

		$fail_step = self::STATUS_ABANDONED_CART_FAILED_WAIT;

		$wpdb->update(
			$wpdb->prefix . ACTIVECAMPAIGN_FOR_WOOCOMMERCE_TABLE_NAME,
			array(
				'synced_to_ac'   => $fail_step,
				'abandoned_date' => $abandoned_cart->last_access_time,
			),
			array(
				'id' => $abandoned_cart->id,
			)
		);

		if ( $wpdb->last_error ) {
			$logger->error(
				'A database error was encountered attempting to update a record in the' . ACTIVECAMPAIGN_FOR_WOOCOMMERCE_TABLE_NAME . ' table.',
				array(
					'wpdb_last_error'  => $wpdb->last_error,
					'suggested_action' => 'Please check the message for explanation and contact ActiveCampaign support if the issue repeats.',
					'ac_code'          => 'TSSH_239',
					'order_id'         => $abandoned_cart->id,
				)
			);
		}
	}

	private function mark_abandoned_cart_failed( $abandoned_cart ) {
		global $wpdb;
		$logger = new Logger();

		try {
			$fail_step = self::STATUS_ABANDONED_CART_FAILED_WAIT;

			// First fail
			if ( self::STATUS_ABANDONED_CART_UNSYNCED == $abandoned_cart->synced_to_ac ) {
				$fail_step = self::STATUS_ABANDONED_CART_FAILED_WAIT;
			}

			// Second fail
			if ( self::STATUS_ABANDONED_CART_FAILED_WAIT == $abandoned_cart->synced_to_ac ) {
				$fail_step = self::STATUS_ABANDONED_CART_FAILED_2;
			}

			// If it failed three times mark it perm fail
			if ( self::STATUS_ABANDONED_CART_FAILED_2 == $abandoned_cart->synced_to_ac ) {
				$fail_step = self::STATUS_ABANDONED_CART_NETWORK_FAIL_PERM;
			}

			$wpdb->update(
				$wpdb->prefix . ACTIVECAMPAIGN_FOR_WOOCOMMERCE_TABLE_NAME,
				array(
					'synced_to_ac'   => $fail_step,
					'abandoned_date' => $abandoned_cart->last_access_time,
				),
				array(
					'id' => $abandoned_cart->id,
				)
			);

			if ( $wpdb->last_error ) {
				$logger->error(
					'A database error was encountered attempting to update a record in the ' . ACTIVECAMPAIGN_FOR_WOOCOMMERCE_TABLE_NAME . ' table',
					array(
						'wpdb_last_error'  => $wpdb->last_error,
						'suggested_action' => 'Please check the message for explanation and contact ActiveCampaign support if the issue repeats.',
						'order_id'         => $abandoned_cart->id,
						'ac_code'          => 'TSSH_284',
					)
				);
			}
		} catch ( Throwable $t ) {
			$logger->error(
				'An exception was thrown while attempting to mark an abandoned cart as failed.',
				array(
					'message'          => $t->getMessage(),
					'suggested_action' => 'Please check the message for explanation and contact ActiveCampaign support if the issue repeats.',
					'ac_code'          => 'TSSH_295',
					'trace'            => $t->getTrace(),
				)
			);
		}
	}

	private function clean_all_old_historical_syncs() {
		global $wpdb;
		$logger = new Logger();
		try {
			$wipe_time    = 40320;
			$expire_4week = gmdate( 'Y-m-d H:i:s', strtotime( '-' . $wipe_time . ' minutes' ) );

			$wipe_time    = 20160;
			$expire_2week = gmdate( 'Y-m-d H:i:s', strtotime( '-' . $wipe_time . ' minutes' ) );

			$synced_to_ac_implode = implode(
				',',
				array(
					self::STATUS_HISTORICAL_SYNC_FINISH,
					self::STATUS_HISTORICAL_SYNC_INCOMPATIBLE,
					self::STATUS_HISTORICAL_SYNC_PREP,
					self::STATUS_HISTORICAL_SYNC_QUEUE,
					self::STATUS_SYNC_INCOMPATIBLE,
					self::STATUS_SUBSCRIPTION_HISTORICAL_SYNC_FINISH,
					self::STATUS_SUBSCRIPTION_HISTORICAL_SYNC_PREP,
					self::STATUS_SUBSCRIPTION_HISTORICAL_SYNC_QUEUE,
					self::STATUS_SUBSCRIPTION_INCOMPATIBLE,
					self::STATUS_SUBSCRIPTION_EXPIRED,
					self::STATUS_SUBSCRIPTION_FAILED_SYNC,
					self::STATUS_SUBSCRIPTION_FAILED_BILLING,
					self::STATUS_FAIL,
					self::STATUS_SYNCED,
				)
			);

			// phpcs:disable
			$delete_count = $wpdb->query(
				'DELETE FROM ' . $wpdb->prefix . ACTIVECAMPAIGN_FOR_WOOCOMMERCE_TABLE_NAME .
				' WHERE (synced_to_ac IN (' . $synced_to_ac_implode . ') ) OR (order_date < "' . $expire_4week . '" AND synced_to_ac = 0) OR (order_date IS NULL AND abandoned_date IS NULL AND last_access_time IS NULL)'
			);

			// phpcs:enable
			if ( ! empty( $delete_count ) ) {
				$logger->debug( $delete_count . ' old historical sync records deleted.' );

				if ( $wpdb->last_error ) {
					$logger->error(
						'A database error was encountered while attempting to delete old historical sync records.',
						array(
							'wpdb_last_error' => $wpdb->last_error,
							'ac_code'         => 'HCU_118',
						)
					);
				}
			}
		} catch ( Throwable $t ) {
			$logger->error(
				'An exception was encountered while preparing or getting historical sync results.',
				array(
					'message' => $t->getMessage(),
					'ac_code' => 'HCU_133',
					'trace'   => $logger->clean_trace( $t->getTrace() ),
				)
			);
		}
	}
}
