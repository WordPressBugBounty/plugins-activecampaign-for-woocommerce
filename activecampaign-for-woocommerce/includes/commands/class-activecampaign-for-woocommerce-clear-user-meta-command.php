<?php

/**
 * The file that defines the Clear_User_Meta_Command Class.
 *
 * @link       https://www.activecampaign.com/
 * @since      1.0.0
 *
 * @package    Activecampaign_For_Woocommerce
 * @subpackage Activecampaign_For_Woocommerce/includes/commands
 */

use Activecampaign_For_Woocommerce_Executable_Interface as Executable;
use Activecampaign_For_Woocommerce_User_Meta_Service as User_Meta_Service;

/**
 * The Clear_User_Meta_Command Class.
 *
 * This command is called when admin settings are updated. If the API Url is changed,
 * we clear all user meta.
 *
 * @since      1.0.0
 * @package    Activecampaign_For_Woocommerce
 * @subpackage Activecampaign_For_Woocommerce/includes/commands
 * @author     acteamintegrations <team-integrations@activecampaign.com>
 */
class Activecampaign_For_Woocommerce_Clear_User_Meta_Command implements Executable {
	/**
	 * Executes the command.
	 *
	 * @param mixed ...$args An array of arguments that may be passed in from the action/filter called.
	 *
	 * @since 1.0.0
	 * @return WC_Order
	 */
	public function execute( ...$args ) {
		if ( isset( $args[0] ) ) {
			$additional_information = $args[0];
		} else {
			$additional_information = [];
		}

		if (
			isset( $additional_information['api_url_changed'] ) &&
			$additional_information['api_url_changed']
		) {
			User_Meta_Service::delete_all_user_meta();
		}

		return $additional_information;
	}
}
