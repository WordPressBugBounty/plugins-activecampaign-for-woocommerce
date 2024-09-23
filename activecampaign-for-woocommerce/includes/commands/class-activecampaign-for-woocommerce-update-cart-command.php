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

use Activecampaign_For_Woocommerce_Admin as Admin;
use Activecampaign_For_Woocommerce_Ecom_Customer_Repository as Ecom_Customer_Repository;
use Activecampaign_For_Woocommerce_Ecom_Order_Factory as Ecom_Order_Factory;
use Activecampaign_For_Woocommerce_Ecom_Order_Repository as Ecom_Order_Repository;
use Activecampaign_For_Woocommerce_Logger as Logger;
use Activecampaign_For_Woocommerce_User_Meta_Service as User_Meta_Service;
use Activecampaign_For_Woocommerce_Save_Abandoned_Cart_Command as Abandoned_Cart;

/**
 * Send the cart and its products to ActiveCampaign for the given customer.
 *
 * @since      1.0.0
 * @package    Activecampaign_For_Woocommerce
 * @subpackage Activecampaign_For_Woocommerce/includes/commands
 * @author     acteamintegrations <team-integrations@activecampaign.com>
 */
class Activecampaign_For_Woocommerce_Update_Cart_Command implements Activecampaign_For_Woocommerce_Executable_Interface {
	use Activecampaign_For_Woocommerce_Data_Validation;

	/**
	 * The WC Cart
	 *
	 * @var WC_Cart
	 */
	private $cart;
	/**
	 * The WC Customer
	 *
	 * @var WC_Customer
	 */
	private $customer;
	/**
	 * The Ecom Order Factory
	 *
	 * @var Ecom_Order_Factory
	 */
	private $factory;
	/**
	 * The Ecom Order Repo
	 *
	 * @var Activecampaign_For_Woocommerce_Ecom_Order_Repository
	 */
	private $order_repository;
	/**
	 * The Ecom Customer Repo
	 *
	 * @var Activecampaign_For_Woocommerce_Ecom_Customer_Repository
	 */
	private $customer_repository;

	/**
	 * The Admin object
	 *
	 * @var Activecampaign_For_Woocommerce_Admin
	 */
	private $admin;

	/**
	 * The logger interface.
	 *
	 * @var Logger
	 */
	private $logger;

	/**
	 * The resulting existing or newly created AC ecom customer
	 *
	 * @var Ecom_Model
	 */
	private $customer_ac;

	/**
	 * The connection id stored in admin
	 *
	 * @var string
	 */
	private $connection_id;

	/**
	 * Activecampaign_For_Woocommerce_Update_Cart_Command constructor.
	 *
	 * @param     WC_Cart|null                              $cart     The WC Cart.
	 * @param     WC_Customer|null                          $customer     The WC Customer.
	 * @param     Activecampaign_For_Woocommerce_Admin|null $admin     The admin object.
	 * @param     Ecom_Order_Factory                        $factory     The Ecom Order Factory.
	 * @param     Ecom_Order_Repository                     $order_repository     The Ecom Order Repo.
	 * @param     Ecom_Customer_Repository|null             $customer_repository     The Ecom Customer Repo.
	 * @param     Logger                                    $logger     The logger interface.
	 */
	public function __construct(
		WC_Cart $cart = null,
		WC_Customer $customer = null,
		Admin $admin,
		Ecom_Order_Factory $factory,
		Ecom_Order_Repository $order_repository,
		Ecom_Customer_Repository $customer_repository,
		Logger $logger = null
	) {
		$this->cart                = $cart;
		$this->customer            = $customer;
		$this->factory             = $factory;
		$this->order_repository    = $order_repository;
		$this->customer_repository = $customer_repository;
		$this->admin               = $admin;

		if ( ! $logger ) {
			$this->logger = new Logger();
		} else {
			$this->logger = $logger;
		}
	}

	/**
	 * Initialize injections that are still null
	 */
	public function init() {
		// calling wc in the constructor causes an exception, since the object is not ready yet
		if ( ! $this->cart ) {
			$this->cart = wc()->cart;
		}

		if ( ! $this->customer ) {
			$this->customer = wc()->customer;
		}
	}
	// phpcs:disable Generic.CodeAnalysis.UnusedFunctionParameter

	/**
	 * {@inheritDoc}
	 *
	 * @param     mixed ...$args     The array of parameters passed.
	 *
	 * @return bool
	 */
	public function execute( ...$args ) {
		$this->init();
		$this->create_and_save_cart_id();

		// If the customer has no available email, there is nothing to do
		try {
			if (
				( ! self::validate_object( $this->customer, 'get_billing_email' ) || empty( $this->customer->get_billing_email() ) ) &&
				( ! self::validate_object( $this->customer, 'get_email' ) || empty( $this->customer->get_email() ) )
			) {
				$this->logger->debug_excess(
					'Update Cart Command: Customer not logged in or email unknown. Do nothing.',
					[
						'customer email' => self::validate_object( $this->customer, 'get_email' ) ? $this->customer->get_email() : null,
					]
				);

				return false;
			}
		} catch ( Throwable $t ) {
			$this->logger->warning(
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

			$this->logger->notice(
				'Update Cart: Could not process abandoned cart.',
				[
					'message'     => $t->getMessage(),
					'stack_trace' => $this->logger->clean_trace( $t->getTrace() ),
					'ac_code'     => 'UCC_207',
				]
			);

			return false;
		}

		return true;
	}

	// phpcs:enable

	private function create_and_save_cart_id() {
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
				$this->logger->debug( 'Create and save cart id: cart already exists' );

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

	/**
	 * Returns the WC customer
	 *
	 * @return WC_Customer
	 */
	public function get_customer() {
		return $this->customer;
	}

	/**
	 * Sets the WC customer
	 *
	 * @param     WC_Customer $wc_customer     the WooCommerce customer.
	 */
	public function set_customer( $wc_customer ) {
		$this->customer = $wc_customer;
	}

	/**
	 * Returns the customer repository
	 *
	 * @return Activecampaign_For_Woocommerce_Ecom_Customer_Repository
	 */
	public function get_customer_repository() {
		return $this->customer_repository;
	}

	/**
	 * Sets the customer repository
	 *
	 * @param     Activecampaign_For_Woocommerce_Ecom_Customer_Repository $repository     the Ecom_Customer_Repository.
	 */
	public function set_customer_repository( $repository ) {
		$this->customer_repository = $repository;
	}

	/**
	 * Returns the order repository
	 *
	 * @return Activecampaign_For_Woocommerce_Ecom_Order_Repository
	 */
	public function get_order_repository() {
		return $this->order_repository;
	}

	/**
	 * Sets the order repository
	 *
	 * @param     Activecampaign_For_Woocommerce_Ecom_Order_Repository $repository     the Ecom_Order_Repository.
	 */
	public function set_order_repository( $repository ) {
		$this->order_repository = $repository;
	}

	/**
	 * Returns the Ecom_Customer
	 *
	 * @return Activecampaign_For_Woocommerce_Ecom_Customer
	 */
	public function get_customer_ac() {
		return $this->customer_ac;
	}

	/**
	 * Sets the Ecom_Customer
	 *
	 * @param     Activecampaign_For_Woocommerce_Ecom_Customer $customer_ac     the Ecom_Customer.
	 */
	public function set_customer_ac( $customer_ac ) {
		$this->customer_ac = $customer_ac;
	}

	/**
	 * Returns the Ecom_Order_Factory
	 *
	 * @return Activecampaign_For_Woocommerce_Ecom_Order_Factory
	 */
	public function get_order_factory() {
		return $this->factory;
	}

	/**
	 * Sets the Ecom_Order_Factory
	 *
	 * @param     Activecampaign_For_Woocommerce_Ecom_Order_Factory $factory     the Ecom_Order_Factory.
	 */
	public function set_order_factory( $factory ) {
		$this->factory = $factory;
	}

	/**
	 * Returns the WC_Cart
	 *
	 * @return WC_Cart
	 */
	public function get_cart() {
		return $this->cart;
	}

	/**
	 * Sets the WC_Cart
	 *
	 * @param     WC_Cart $cart     the WooCommerce Cart.
	 */
	public function set_cart( $cart ) {
		$this->cart = $cart;
	}
}
