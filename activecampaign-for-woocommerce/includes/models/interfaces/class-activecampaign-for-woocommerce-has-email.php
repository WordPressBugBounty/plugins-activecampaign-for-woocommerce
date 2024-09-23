<?php

/**
 * The file for the Has Email Interface.
 *
 * @link       https://www.activecampaign.com/
 * @since      1.0.0
 *
 * @package    Activecampaign_For_Woocommerce
 * @subpackage Activecampaign_For_Woocommerce/includes/models/interfaces
 */

/**
 * The model class for the Has Email Interface.
 *
 * @since      1.0.0
 * @package    Activecampaign_For_Woocommerce
 * @subpackage Activecampaign_For_Woocommerce/includes/models/interfaces
 * @author     acteamintegrations <team-integrations@activecampaign.com>
 */
interface Activecampaign_For_Woocommerce_Has_Email {
	/**
	 * Returns the email.
	 *
	 * @return string
	 */
	public function get_email();
}
