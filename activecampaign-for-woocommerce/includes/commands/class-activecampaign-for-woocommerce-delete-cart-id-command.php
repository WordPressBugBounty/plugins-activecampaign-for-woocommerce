<?php

/**
 * The file that defines the Delete_Cart_Id_Command Class.
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
 * The Delete_Cart_Id_Command Class.
 *
 * @since      1.0.0
 * @package    Activecampaign_For_Woocommerce
 * @subpackage Activecampaign_For_Woocommerce/includes/commands
 * @author     acteamintegrations <team-integrations@activecampaign.com>
 */
class Activecampaign_For_Woocommerce_Delete_Cart_Id_Command implements Executable {
	// phpcs:disable Generic.CodeAnalysis.UnusedFunctionParameter
	/**
	 * Executes the command.
	 *
	 * Checks if the user is logged in. If so, deletes their current cart id meta key.
	 *
	 * @param mixed ...$args An array of arguments that may be passed in from the action/filter called.
	 *
	 * @since 1.0.0
	 */
	public function execute( ...$args ) {
		$user_id = get_current_user_id();

		if ( ! $user_id ) {
			return;
		}

		User_Meta_Service::delete_current_cart_id( $user_id );
	}
	// phpcs:enable
}
