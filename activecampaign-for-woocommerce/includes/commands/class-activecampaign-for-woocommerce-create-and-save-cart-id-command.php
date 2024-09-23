<?php

/**
 * The file that defines the Create_And_Save_Cart_Id_Command Class.
 *
 * @link       https://www.activecampaign.com/
 * @since      1.0.0
 *
 * @package    Activecampaign_For_Woocommerce
 * @subpackage Activecampaign_For_Woocommerce/includes/commands
 */

use Activecampaign_For_Woocommerce_Executable_Interface as Executable;
use Activecampaign_For_Woocommerce_Logger as Logger;
use Activecampaign_For_Woocommerce_User_Meta_Service as User_Meta_Service;

/**
 * The Create_And_Save_Cart_Id_Command Class.
 *
 * @since      1.0.0
 * @package    Activecampaign_For_Woocommerce
 * @subpackage Activecampaign_For_Woocommerce/includes/commands
 * @author     acteamintegrations <team-integrations@activecampaign.com>
 */
class Activecampaign_For_Woocommerce_Create_And_Save_Cart_Id_Command implements Executable {
	/**
	 * The Logger interface.
	 *
	 * @var Logger
	 */
	private $logger;

	/**
	 * Activecampaign_For_Woocommerce_Create_And_Save_Cart_Id_Command constructor.
	 *
	 * @param Logger $logger The Logger interface.
	 */
	public function __construct( Logger $logger = null ) {
		if ( ! $this->logger ) {
			$this->logger = new Logger();
		} else {
			$this->logger = $logger;
		}
	}

	// phpcs:disable Generic.CodeAnalysis.UnusedFunctionParameter

	/**
	 * Executes the command.
	 *
	 * Checks if the user is logged in and if the cart already has an ID. If there's a user,
	 * and no cart ID, creates one.
	 *
	 * @param mixed ...$args An array of arguments that may be passed in from the action/filter called.
	 *
	 * @since 1.0.0
	 */
	public function execute( ...$args ) {
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
				$this->logger->debug_excess( 'Create and save cart id: cart already exists' );

				return;
			}

			User_Meta_Service::set_current_cart_id( $user_id );
		} catch ( Throwable $t ) {
			$this->logger->warning(
				'There was an issue trying to add additional info to user meta.',
				[
					'class'   => 'Activecampaign_For_Woocommerce_Create_And_Save_Cart_Id_Command',
					'message' => $t->getMessage(),
				]
			);
		}
	}
	// phpcs:enable
}
