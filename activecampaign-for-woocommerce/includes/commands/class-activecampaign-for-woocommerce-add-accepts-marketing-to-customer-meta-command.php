<?php

/**
 * The file that defines the Add_Accepts_Marketing_To_Customer_Meta_Command Class.
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
 * The Add_Accepts_Marketing_To_Customer_Meta_Command Class.
 *
 * This command is called when a cart is transitioning to an order, allowing us to
 * take our persistent cart id and add it to the meta table for the order.
 *
 * @since      1.0.0
 * @package    Activecampaign_For_Woocommerce
 * @subpackage Activecampaign_For_Woocommerce/includes/commands
 * @author     acteamintegrations <team-integrations@activecampaign.com>
 */
class Activecampaign_For_Woocommerce_Add_Accepts_Marketing_To_Customer_Meta_Command implements Executable {
	use Activecampaign_For_Woocommerce_Admin_Utilities;

	/**
	 * The Logger interface.
	 *
	 * @var Logger
	 */
	private $logger;

	/**
	 * Activecampaign_For_Woocommerce_Add_Accepts_Marketing_To_Customer_Meta_Command constructor.
	 *
	 * @param Logger $logger The Logger interface.
	 */
	public function __construct( Logger $logger ) {
		if ( ! $logger ) {
			$this->logger = new Logger();
		} else {
			$this->logger = $logger;
		}
	}

	/**
	 * Executes the command.
	 *
	 * Checks if the user is logged in. If so, and there's a persistent cart,
	 * saves that cart id to the order meta table.
	 *
	 * If not, saves the accepts marketing value in the order meta table, so it can
	 * still be saved on the eventual customer that will be created for the order.
	 *
	 * @param mixed ...$args An array of arguments that may be passed in from the action/filter called.
	 *
	 * @return WC_Order
	 */
	public function execute( ...$args ) {
		if ( ! $this->logger ) {
			$this->logger = new Logger();
		}
		/**
		 * An instance of the WC_Order class being passed through the filter.
		 *
		 * @var WC_Order $order
		 */
		if ( ! isset( $args[0] ) ) {
			$this->logger->notice( 'Accepts Marketing: Valid order not passed in args.' );
			return;
		}

		$order = $args[0];

		if ( ! $this->nonce_is_valid() ) {
			$this->logger->debug(
				'Accepts Marketing: Invalid WooCommerce checkout nonce. The accepts marketing data may not have come from the WC checkout.',
				[
					'order'        => $order->get_id(),
					'order_number' => $order->get_order_number(),
				]
			);
		}

		$accepts_marketing = $this->extract_accepts_marketing_value();

		$id = $order->get_customer_id();

		// If a customer is logged in, set the value of accepts marketing on the user meta-data.
		// For guest checkouts set it on the order meta-data.
		if ( ! $id ) {
			$this->logger->debug( 'Accepts Marketing: No ID for customer. Setting accepts marketing on the order.' );

			$this->update_order_accepts_marketing( $order, $accepts_marketing );

			$this->logger->debug(
				'Updated order with accepts marketing meta data: ',
				[
					'accepts_marketing' => $order->get_meta( ACTIVECAMPAIGN_FOR_WOOCOMMERCE_ACCEPTS_MARKETING_NAME ),
				]
			);
		} else {
			$this->logger->debug(
				"Accepts Marketing: ID found for customer: $id. Setting accepts marketing on the customer record.",
				[
					'customer_id'       => $id,
					'order_id'          => $order->get_id(),
					'accepts_marketing' => $accepts_marketing,
				]
			);

			$this->update_order_accepts_marketing( $order, $accepts_marketing );
			$this->update_user_accepts_marketing( $id, $accepts_marketing );
		}

		return $order;
	}

	/**
	 * Validates that the nonce for the request is valid.
	 *
	 * @return bool
	 */
	private function nonce_is_valid() {
		// see: https://github.com/woocommerce/woocommerce/blob/master/includes/class-wc-checkout.php#L1076
		$checkout_nonce = self::get_request_data( 'woocommerce-process-checkout-nonce' );
		$wp_nonce       = self::get_request_data( '_wpnonce' );
		$nonce_value    = wc_get_var( $checkout_nonce, wc_get_var( $wp_nonce, '' ) );
		$valid          = (bool) wp_verify_nonce( $nonce_value, 'woocommerce-process_checkout' );

		if ( ! $valid ) {
			$this->logger->debug(
				'Accepts Marketing: Invalid nonce. There may be an issue storing order values or orders may have been created through the API.',
				[
					'checkout_nonce' => $checkout_nonce,
					'nonce_value'    => $nonce_value,
				]
			);
		}

		return $valid;
	}

	/**
	 * Extracts the value of the accepts marketing checkbox.
	 *
	 * @return int
	 */
	private function extract_accepts_marketing_value() {
		$accepts_marketing = self::get_request_data( 'activecampaign_for_woocommerce_accepts_marketing' );
		if ( isset( $accepts_marketing ) && ( '1' === $accepts_marketing || 1 === $accepts_marketing ) ) {
			return 1;
		}

		return 0;
	}

	/**
	 * Updates the user meta for the customer with their preference of marketing.
	 *
	 * Additionally, triggers the customer updated action, which will fire off a
	 * customer updated webhook.
	 *
	 * @param int $id The id of the user to be updated.
	 * @param int $accepts_marketing The value of the meta field to be updated.
	 */
	private function update_user_accepts_marketing( $id, $accepts_marketing ) {
		User_Meta_Service::set_user_accepts_marketing( $id, $accepts_marketing );

		// phpcs:disable
		// The linter definitions don't like that we're invoking another plugin's actions
		do_action( 'woocommerce_update_customer', $id );
		// phpcs:enable
	}

	/**
	 * Update the order's metadata with the accepts marketing value so it is included in the webhook
	 *
	 * @param WC_Order $order The order.
	 * @param int      $accepts_marketing Value of the checkbox.
	 */
	private function update_order_accepts_marketing( $order, $accepts_marketing ) {
		$order->update_meta_data(
			ACTIVECAMPAIGN_FOR_WOOCOMMERCE_ACCEPTS_MARKETING_NAME,
			$accepts_marketing
		);
	}
}
