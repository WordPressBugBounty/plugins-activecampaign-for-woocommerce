<?php

/**
 * The file that defines the Add_Cart_Id_To_Order_Command Class.
 *
 * @link       https://www.activecampaign.com/
 * @since      1.0.0
 *
 * @package    Activecampaign_For_Woocommerce
 * @subpackage Activecampaign_For_Woocommerce/includes/commands
 */

use Activecampaign_For_Woocommerce_Executable_Interface as Executable;
use Activecampaign_For_Woocommerce_User_Meta_Service as User_Meta_Service;
use Activecampaign_For_Woocommerce_Sync_Guest_Abandoned_Cart_Command as Sync_Guest_Abandoned_Cart_Command;
use Activecampaign_For_Woocommerce_Logger as Logger;

/**
 * The Add_Cart_Id_To_Order_Command Class.
 *
 * This command is called when a cart is transitioning to an order, allowing us to
 * take our persistent cart id and add it to the meta table for the order.
 *
 * @since      1.0.0
 * @package    Activecampaign_For_Woocommerce
 * @subpackage Activecampaign_For_Woocommerce/includes/commands
 * @author     acteamintegrations <team-integrations@activecampaign.com>
 */
class Activecampaign_For_Woocommerce_Add_Cart_Id_To_Order_Command implements Executable {
	use Activecampaign_For_Woocommerce_Abandoned_Cart_Utilities;
	/**
	 * Executes the command.
	 *
	 * Checks if the user is logged in. If so, and there's a persistent cart,
	 * saves that cart id to the order meta table.
	 *
	 * @param mixed ...$args An array of arguments that may be passed in from the action/filter called.
	 *
	 * @since 1.0.0
	 * @return WC_Order | bool
	 */
	public function execute( ...$args ) {
		/**
		 * The WooCommerce Order object that's in-progress of being saved.
		 *
		 * @var WC_Order $order
		 */
		try {
			if ( isset( $args[0] ) ) {
				$order = $args[0];

				if ( empty( $order->get_billing_email() ) ) {
					return $order;
				}

				$user_id = get_current_user_id();

				if ( ! $user_id ) {
					// Guest checkout
					$persistant_cart_id_name = $this->generate_externalcheckoutid(
						wc()->session->get_customer_id(),
						$order->get_billing_email()
					);
				} else {
					// Registered user (customer) checkout

					/**
					 * Delete the local cache of Hosted's order/cart ID so it isn't used
					 * erroneously on the next order this user places.
					 */
					User_Meta_Service::delete_current_cart_ac_id( $user_id );

					$cart_id = User_Meta_Service::get_current_cart_id( $user_id );

					if ( $cart_id ) {
						// Registered user (customer) initiated cart and completed checkout
						$persistant_cart_id_name = $cart_id;
					} else {
						// Registered user (customer) only completed checkout (guest initiated cart)

						/**
						 * In this case we have a user ID but no cart ID.
						 * This means a guest placed an order and converted
						 * to a customer during checkout.
						 *
						 * Example session cookie:
						 *
						 * Array
						 * (
						 *   [0] => 4a342d38b872b7ce2ab15d6f420aa80d
						 *   [1] => 1558289976
						 *   [2] => 1558286376
						 *   [3] => 69070d73cd7950bb08352af7f7ee4cc2
						 * )
						 *
						 * The first item is used to generate the externalcheckoutid so
						 * Hosted knows to convert the pending order to completed.
						 */
						$woocommerce_session_cookie = wc()->session->get_session_cookie();

						$woocommerce_session_hash = $woocommerce_session_cookie[0];

						$persistant_cart_id_name = $this->generate_externalcheckoutid(
							$woocommerce_session_hash,
							$order->get_billing_email()
						);
					}
				}

				// This ends up as the externalcheckoutid in Hosted
				$order->update_meta_data(
					ACTIVECAMPAIGN_FOR_WOOCOMMERCE_PERSISTENT_CART_ID_NAME,
					$persistant_cart_id_name
				);

				return $order;
			}
		} catch ( Throwable $t ) {
			$logger = new Logger();
			$logger->warning(
				'There was an issue trying to add cart ID to order.',
				[
					'class'   => 'Activecampaign_For_Woocommerce_Add_Cart_Id_To_Order_Command',
					'message' => $t->getMessage(),
				]
			);
		}
	}
}
