<?php

/**
 * The Ecom Synced Status Interface.
 *
 * @link       https://www.activecampaign.com/
 * @since      1.0.0
 * @package    Activecampaign_For_Woocommerce
 * @subpackage Activecampaign_For_Woocommerce/
 */

/**
 * The Ecom Product Factory class.
 *
 * @since      1.0.0
 * @package    Activecampaign_For_Woocommerce
 * @subpackage Activecampaign_For_Woocommerce/
 * @author     acteamintegrations <team-integrations@activecampaign.com>
 */
interface Activecampaign_For_Woocommerce_Synced_Status_Interface {

	/**
	 * Order status - Unsynced
	 */
	public const STATUS_UNSYNCED = 0;

	/**
	 * Order status - Synced
	 */
	public const STATUS_SYNCED = 1;

	/**
	 * Order status - On hold order
	 */
	public const STATUS_ON_HOLD = 2;

	/**
	 * Order status - Pending historical sync, in a scheduled state
	 */
	public const STATUS_HISTORICAL_SYNC_QUEUE = 3;

	/**
	 * Order status - Prepare for historical sync, gather records
	 */
	public const STATUS_HISTORICAL_SYNC_PREP = 4;

	/**
	 * Order status - Sync finished, ready to validate
	 */
	public const STATUS_HISTORICAL_SYNC_FINISH = 5;

	/**
	 * Order status - Sync finished, ready to validate
	 */
	public const STATUS_HISTORICAL_SYNC_INCOMPATIBLE = 6;

	/**
	 * Order status - Subscription order. Marked on parent order.
	 */
	public const STATUS_SYNC_INCOMPATIBLE = 7;

	/**
	 * Order status - Refund order
	 */
	public const STATUS_REFUND = 8;

	/**
	 * Order status - Failed to sync (error returned from AC)
	 */
	public const STATUS_FAIL = 9;

	/**
	 * Abandoned cart status - cart unsynced
	 */
	public const STATUS_ABANDONED_CART_UNSYNCED           = 20;
	public const STATUS_ABANDONED_CART_AUTO_SYNCED        = 21;
	public const STATUS_ABANDONED_CART_MANUAL_SYNCED      = 22;
	public const STATUS_ABANDONED_CART_RECOVERED          = 23;
	public const STATUS_ABANDONED_CART_FAILED_WAIT        = 26;
	public const STATUS_ABANDONED_CART_FAILED_2           = 27;
	public const STATUS_ABANDONED_CART_NETWORK_FAIL_RETRY = 28;
	public const STATUS_ABANDONED_CART_NETWORK_FAIL_PERM  = 29;

	/**
	 * Subscription status - Ready to sync as a new record.
	 */
	public const STATUS_SUBSCRIPTION_UNSYNCED = 30;
	/**
	 * Subscription status - Standard sync is finished.
	 */
	public const STATUS_SUBSCRIPTION_SYNCED = 31;

	/**
	 * Subscription status - Pending historical sync, in a scheduled state.
	 */
	public const STATUS_SUBSCRIPTION_HISTORICAL_SYNC_QUEUE = 32;

	/**
	 * Subscription status - Prepare for historical sync.
	 */
	public const STATUS_SUBSCRIPTION_HISTORICAL_SYNC_PREP = 33;

	public const STATUS_SUBSCRIPTION_HISTORICAL_SYNC_FINISH = 35;
	public const STATUS_SUBSCRIPTION_EXPIRED                = 36; // needed?
	public const STATUS_SUBSCRIPTION_FAILED_BILLING         = 37; // Failed billing // needed?
	public const STATUS_SUBSCRIPTION_INCOMPATIBLE           = 38;
	public const STATUS_SUBSCRIPTION_FAILED_SYNC            = 39;

	/**
	 * Order status - Delete record
	 */
	public const STATUS_DELETE = 86;

	/**
	 * Returns the readable version of the status when provided the number.
	 *
	 * @param     int $status The status.
	 *
	 * @return mixed
	 */
	public function get_readable_sync_status( int $status);
}
