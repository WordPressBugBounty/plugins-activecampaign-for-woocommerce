<?php

/**
 * The file for the Ecom Order Factory
 *
 * @link       https://www.activecampaign.com/
 * @since      1.0.0
 * @package    Activecampaign_For_Woocommerce
 * @subpackage Activecampaign_For_Woocommerce/
 */

use Activecampaign_For_Woocommerce_Admin as Admin;
use Activecampaign_For_Woocommerce_Ecom_Order as Ecom_Order;
use Activecampaign_For_Woocommerce_Ecom_Product_Factory as Ecom_Product_Factory;
use Activecampaign_For_Woocommerce_User_Meta_Service as User_Meta_Service;
use Activecampaign_For_Woocommerce_Logger as Logger;

/**
 * Ecom Order Factory
 *
 * @since      1.0.0
 * @package    Activecampaign_For_Woocommerce
 * @subpackage Activecampaign_For_Woocommerce/
 * @author     acteamintegrations <team-integrations@activecampaign.com>
 */
class Activecampaign_For_Woocommerce_Ecom_Order_Factory {
	use Activecampaign_For_Woocommerce_Abandoned_Cart_Utilities,
		Activecampaign_For_Woocommerce_Data_Validation;

	/**
	 * Product Factory
	 *
	 * @var Ecom_Product_Factory
	 */
	private $product_factory;

	/**
	 * Admin
	 *
	 * @var Admin
	 */
	private $admin;

	/**
	 * Activecampaign_For_Woocommerce_Ecom_Order_Factory constructor.
	 *
	 * @param Ecom_Product_Factory|null                 $product_factory The Ecom Product Factory.
	 * @param Activecampaign_For_Woocommerce_Admin|null $admin The Admin object.
	 */
	public function __construct( Ecom_Product_Factory $product_factory, Admin $admin ) {
		$this->product_factory = $product_factory;
		$this->admin           = $admin;
	}

	/**
	 * Create an Ecom Order from the given WC Cart and WC Customer
	 *
	 * @param     WC_Cart     $cart     The WC Cart.
	 * @param     WC_Customer $customer     The WC Customer.
	 *
	 * @return Ecom_Order
	 */
	public function from_woocommerce( $cart, $customer ) {
		$order  = new Ecom_Order();
		$logger = new Logger();

		try {
			if ( self::validate_object( $cart, 'get_cart_contents' ) && ! $cart->is_empty() && self::validate_object( $customer, 'get_email' ) ) {
				$date = new DateTime( 'now', new DateTimeZone( 'UTC' ) );

				$order->set_id( $this->get_ac_id() );

				$external_id = $this->generate_externalcheckoutid(
					wc()->session->get_customer_id(),
					$customer->get_email()
				);
				$order->set_externalcheckoutid( $external_id );
				$order->set_source( '1' );
				$order->set_email( $customer->get_email() );
				$order->set_total_price( $this->get_cart_total( $cart ) );
				$order->set_currency( $this->get_woocommerce_currency() );
				$order->set_connectionid( $this->admin->get_connection_storage()['connection_id'] );
				$order->set_customerid( $this->get_ac_customer_id() );
				$order->set_order_date( $date->format( DATE_ATOM ) );
				$order->set_order_url( wc_get_cart_url() );
			}
		} catch ( Throwable $t ) {
			$logger->warning(
				'Order Factory from_woocommerce: There was an error creating the order.',
				[
					'message'       => $t->getMessage(),
					'email'         => self::validate_object( $customer, 'get_email' ) ? $customer->get_email() : null,
					'cart_contents' => self::validate_object( $cart, 'get_cart_contents' ) ? $cart->get_cart_contents() : null,
					'trace'         => $logger->clean_trace( $t->getTrace() ),
				]
			);
		}

		try {
			$products = $this
				->product_factory
				->create_products_from_cart_contents( $cart->get_cart_contents() );

			if ( count( $products ) > 0 ) {
				array_walk( $products, [ $order, 'push_order_product' ] );
			} else {
				$logger->warning(
					'Order Factory: Could not create product from cart contents.',
					[
						'email'         => self::validate_object( $customer, 'get_email' ) ? $customer->get_email() : null,
						'cart_contents' => self::validate_object( $cart, 'get_cart_contents' ) ? $cart->get_cart_contents() : null,
					]
				);
			}
		} catch ( Throwable $t ) {
			$logger->warning(
				'Order Factory: Could not create product from cart contents.',
				[
					'message'       => $t->getMessage(),
					'email'         => self::validate_object( $customer, 'get_email' ) ? $customer->get_email() : null,
					'cart_contents' => self::validate_object( $cart, 'get_cart_contents' ) ? $cart->get_cart_contents() : null,
					'trace'         => $logger->clean_trace( $t->getTrace() ),
				]
			);

			return null;
		}

		return $order;
	}

	/**
	 * Retrieve the ID from the DB for this customer's cart
	 *
	 * @return int
	 */
	private function get_ac_id() {
		return User_Meta_Service::get_current_cart_ac_id( get_current_user_id() );
	}

	/**
	 * Return the externalid from the DB for this customer's cart
	 *
	 * @return int
	 */
	private function get_external_id() {
		return User_Meta_Service::get_current_cart_id( get_current_user_id() );
	}

	/**
	 * Get ID for the customer record
	 *
	 * @return int
	 */
	private function get_ac_customer_id() {
		return User_Meta_Service::get_current_user_ac_customer_id( get_current_user_id() );
	}

	/**
	 * Get the cart's total price in cents,
	 * considering whether the global setting indicates that tax should be included.
	 *
	 * @param WC_Cart $cart The WC Cart.
	 *
	 * @return int
	 */
	private function get_cart_total( $cart ) {
		$totals = new WC_Cart_Totals( $cart );

		return $totals->get_total( 'total', true );
	}

	/**
	 * Wrapper for global method to get the WC currency.
	 *
	 * @return string
	 */
	private function get_woocommerce_currency() {
		return get_woocommerce_currency();
	}
}
