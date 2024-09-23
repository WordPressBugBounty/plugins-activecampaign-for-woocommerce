<?php

/**
 * The file that defines the Executable Command Interface.
 *
 * @link       https://www.activecampaign.com/
 * @since      1.0.0
 *
 * @package    Activecampaign_For_Woocommerce
 * @subpackage Activecampaign_For_Woocommerce/includes/commands
 */

/**
 * The Executable Command Interface.
 *
 * @since      1.0.0
 * @package    Activecampaign_For_Woocommerce
 * @subpackage Activecampaign_For_Woocommerce/includes/commands
 * @author     acteamintegrations <team-integrations@activecampaign.com>
 */
interface Activecampaign_For_Woocommerce_Triggerable_Interface {
	/**
	 * The method called during events.
	 *
	 * @param array ...$args An array of all arguments passed in.
	 *
	 * @since 1.0.0
	 */
	public function trigger( ...$args );
}
