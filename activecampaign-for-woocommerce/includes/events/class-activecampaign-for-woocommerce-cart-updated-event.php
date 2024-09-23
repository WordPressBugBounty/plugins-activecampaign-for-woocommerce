<?php

/**
 * The file that defines the Cart Updated Event Class.
 *
 * @link       https://www.activecampaign.com/
 * @since      1.0.0
 *
 * @package    Activecampaign_For_Woocommerce
 * @subpackage Activecampaign_For_Woocommerce/includes/events
 */

use Activecampaign_For_Woocommerce_Logger as Logger;
use Activecampaign_For_Woocommerce_Triggerable_Interface as Triggerable;

/**
 * The Cart Updated Event Class.
 *
 * @since      1.0.0
 * @package    Activecampaign_For_Woocommerce
 * @subpackage Activecampaign_For_Woocommerce/includes/events
 * @author     acteamintegrations <team-integrations@activecampaign.com>
 */
class Activecampaign_For_Woocommerce_Cart_Updated_Event implements Triggerable {
	// phpcs:disable Generic.CodeAnalysis.UnusedFunctionParameter
	/**
	 * Called when a cart is updated.
	 *
	 * @param     array ...$args     An array of all arguments passed in.
	 *
	 * @since 1.0.0
	 */
	public function trigger( ...$args ) {
		do_action( 'activecampaign_for_woocommerce_cart_updated' );
	}
	// phpcs:enable
}
