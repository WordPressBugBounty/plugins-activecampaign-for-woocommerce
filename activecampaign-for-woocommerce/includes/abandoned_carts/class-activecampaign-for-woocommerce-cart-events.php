<?php

/**
 * The file that defines the Cart_Emptied Event Class.
 *
 * @link       https://www.activecampaign.com/
 * @since      1.0.0
 *
 * @package    Activecampaign_For_Woocommerce
 * @subpackage Activecampaign_For_Woocommerce/includes/events
 */

use Activecampaign_For_Woocommerce_Logger as Logger;
use Activecampaign_For_Woocommerce_User_Meta_Service as User_Meta_Service;
use Activecampaign_For_Woocommerce_Save_Abandoned_Cart_Command as Abandoned_Cart;

class Activecampaign_For_Woocommerce_Cart_Events {
	use Activecampaign_For_Woocommerce_Data_Validation,
		Activecampaign_For_Woocommerce_Abandoned_Cart_Utilities;

	public function cart_emptied( ...$args ) {
		$user_id = get_current_user_id();

		if ( ! $user_id ) {
			return;
		}

		User_Meta_Service::delete_current_cart_id( $user_id );
	}

	public function cart_updated( ...$args ) {
		$logger = new Logger();
		$this->create_and_save_cart_id();

		$wc_customer = wc()->customer;

		try {
			set_transient( 'acforwc_cart_updated_hook', wp_date( DATE_ATOM ), 604800 );

			if (
				( ! self::validate_object( $wc_customer, 'get_billing_email' ) || empty( $wc_customer->get_billing_email() ) ) &&
				( ! self::validate_object( $wc_customer, 'get_email' ) || empty( $wc_customer->get_email() ) )
			) {
				$logger->debug_excess(
					'Update Cart Command: Customer not logged in or email unknown. Do nothing.',
					[
						'customer email' => self::validate_object( $wc_customer, 'get_email' ) ? $wc_customer->get_email() : null,
					]
				);

				return false;
			}
		} catch ( Throwable $t ) {
			$logger->warning(
				'Update Cart Command: There was an issue creating a customer or reading order, continuing.',
				[
					'message' => $t->getMessage(),
					'ac_code' => 'UCC_168',
				]
			);
		}

		// If we already have an AC ID, then this is an update. Otherwise, it's a create.
		try {
			$abandoned_cart = new Abandoned_Cart();
			$abandoned_cart->init();
		} catch ( Throwable $t ) {
			/**
			 * We have seen issues for a few users of this plugin where either the create or update call throws
			 * an exception, which ends up breaking their store. This try/catch is a stop-gap measure for now.
			 */

			$logger->notice(
				'Update Cart: Could not process abandoned cart.',
				[
					'message'     => $t->getMessage(),
					'stack_trace' => $logger->clean_trace( $t->getTrace() ),
					'ac_code'     => 'UCC_207',
				]
			);

			return false;
		}
	}

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
	public function cart_to_order_transition( ...$args ) {
		/**
		 * The WooCommerce Order object that's in-progress of being saved.
		 *
		 * @var WC_Order $order
		 */
		$logger = new Logger();

		try {
			set_transient( 'acforwc_cart_to_order_transition_hook', wp_date( DATE_ATOM ), 604800 );

			if ( isset( $args[0] ) ) {
				$order = $args[0];

				if ( $order->get_billing_email() === null || empty( $order->get_billing_email() ) ) {
					$logger->warning(
						'Cart to order transition was not able to run due to a missing email.',
						[
							'args'    => $args,
							'ac_code' => 'CE_118',
						]
					);
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
				$order->add_meta_data(
					ACTIVECAMPAIGN_FOR_WOOCOMMERCE_PERSISTENT_CART_ID_NAME,
					$persistant_cart_id_name,
					true
				);
				// $order->update_meta_data(
				// ACTIVECAMPAIGN_FOR_WOOCOMMERCE_PERSISTENT_CART_ID_NAME,
				// $persistant_cart_id_name
				// );

				return $order;
			} else {
				$logger->warning(
					'Cart to order transition is missing order information and cannot transition the cart properly.',
					[
						'args'    => $args,
						'ac_code' => 'CE_195',
					]
				);
			}
		} catch ( Throwable $t ) {
			$logger->warning(
				'There was an issue trying to add cart ID to order. External checkout ID may be missing.',
				[
					'class'   => 'Activecampaign_For_Woocommerce_Add_Cart_Id_To_Order_Command',
					'message' => $t->getMessage(),
					'ac_code' => 'CE_202',
				]
			);
		}
	}

	private function create_and_save_cart_id() {
		$logger = new Logger();
		try {
			$user_id = get_current_user_id();

			if ( ! $user_id ) {
				// Create and save cart id: No current user id
				return;
			}

			$current_cart_id = User_Meta_Service::get_current_cart_id( $user_id );

			/**
			 * The function get_user_meta will return an empty string if the key is not set.
			 * If there's an existing cart id, return early.
			 */
			if ( '' !== $current_cart_id ) {
				$logger->debug( 'Create and save cart id: cart already exists' );

				return;
			}

			User_Meta_Service::set_current_cart_id( $user_id, $current_cart_id );
		} catch ( Throwable $t ) {
			$logger->warning(
				'There was an issue trying to add additional info to user meta.',
				[
					'class'   => 'Activecampaign_For_Woocommerce_Create_And_Save_Cart_Id_Command',
					'message' => $t->getMessage(),
					'ac_code' => 'CE_238',
				]
			);
		}
	}
}
