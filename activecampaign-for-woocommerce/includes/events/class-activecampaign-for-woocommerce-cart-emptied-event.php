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

use Activecampaign_For_Woocommerce_Triggerable_Interface as Triggerable;
use Activecampaign_For_Woocommerce_Admin as Admin;
use Activecampaign_For_Woocommerce_Ecom_Customer_Repository as Ecom_Customer_Repository;
use Activecampaign_For_Woocommerce_Ecom_Order_Factory as Ecom_Order_Factory;
use Activecampaign_For_Woocommerce_Ecom_Order_Repository as Ecom_Order_Repository;
use Activecampaign_For_Woocommerce_Logger as Logger;
use Activecampaign_For_Woocommerce_Ecom_Product_Factory as Ecom_Product_Factory;
use AcVendor\GuzzleHttp\Exception\GuzzleException;

/**
 * The Cart_Emptied Event Class.
 *
 * @since      1.0.0
 * @package    Activecampaign_For_Woocommerce
 * @subpackage Activecampaign_For_Woocommerce/includes/events
 * @author     acteamintegrations <team-integrations@activecampaign.com>
 */
class Activecampaign_For_Woocommerce_Cart_Emptied_Event implements Triggerable {

	/**
	 * The WC Cart
	 *
	 * @var WC_Cart
	 */
	public $cart;

	/**
	 * The WC Customer
	 *
	 * @var WC_Customer
	 */
	public $customer;

	/**
	 * The Ecom Order Factory
	 *
	 * @var Ecom_Order_Factory
	 */
	public $factory;

	/**
	 * The Ecom Order Repo
	 *
	 * @var Activecampaign_For_Woocommerce_Ecom_Order_Repository
	 */
	public $order_repository;

	/**
	 * The Ecom Customer Repo
	 *
	 * @var Activecampaign_For_Woocommerce_Ecom_Customer_Repository
	 */
	public $customer_repository;

	/**
	 * The Admin object
	 *
	 * @var Activecampaign_For_Woocommerce_Admin
	 */
	private $admin;

	/**
	 * The guest customer email address
	 *
	 * @var string
	 */
	private $customer_email;

	/**
	 * The guest customer first name
	 *
	 * @var string
	 */
	private $customer_first_name;

	/**
	 * The guest customer last name
	 *
	 * @var string
	 */
	private $customer_last_name;

	/**
	 * The WooCommerce customer object
	 *
	 * @var WC_Customer
	 */
	private $customer_woo;

	/**
	 * The resulting existing or newly created AC ecom customer
	 *
	 * @var Ecom_Model
	 */
	private $customer_ac;

	/**
	 * Hash of the WooCommerce session ID plus the guest customer email.
	 * Used to identify an order as being created in a pending state.
	 *
	 * @var string
	 */
	private $external_checkout_id;

	/**
	 * The native ecom order object used to
	 * create or update an order in AC
	 *
	 * @var Activecampaign_For_Woocommerce_Ecom_Order
	 */
	private $ecom_order;

	/**
	 * The resulting existing or newly created AC ecom order
	 *
	 * @var Activecampaign_For_Woocommerce_Ecom_Model_Interface
	 */
	private $order_ac;

	/**
	 * Whether or not the AC order exists.
	 * Used to determine whether or not
	 * we want to update the AC order (PUT request).
	 *
	 * @var boolean
	 */
	private $order_ac_exists = false;

	/**
	 * The custom ActiveCampaign logger
	 *
	 * @var Activecampaign_For_Woocommerce_Logger
	 */
	private $logger;

	/**
	 * The WooCommerce session
	 *
	 * @var WC_Session|null
	 */
	private $wc_session;

	/**
	 * The Ecom Product Factory
	 *
	 * @var Ecom_Product_Factory
	 */
	private $product_factory;

	/**
	 * Activecampaign_For_Woocommerce_Cart_Emptied_Event constructor.
	 *
	 * @param     Activecampaign_For_Woocommerce_Admin|null              $admin     The Admin object.
	 * @param     Activecampaign_For_Woocommerce_Ecom_Order_Factory|null $factory     The ecom order factory.
	 * @param     Ecom_Order_Repository|null                             $order_repository     The Ecom Order Repository.
	 * @param     Ecom_Product_Factory|null                              $product_factory     The Ecom Product Factory.
	 * @param     Ecom_Customer_Repository|null                          $customer_repository     The Ecom Customer Repository.
	 * @param     Logger|null                                            $logger     The Logger.
	 */
	public function __construct(
		Admin $admin,
		Ecom_Order_Factory $factory,
		Ecom_Order_Repository $order_repository,
		Ecom_Product_Factory $product_factory,
		Ecom_Customer_Repository $customer_repository,
		Logger $logger = null
	) {
		$this->admin               = $admin;
		$this->factory             = $factory;
		$this->order_repository    = $order_repository;
		$this->product_factory     = $product_factory;
		$this->customer_repository = $customer_repository;
		$this->logger              = $logger;
	}


	// phpcs:disable Generic.CodeAnalysis.UnusedFunctionParameter

	/**
	 * Called when WooCommerce Cart is emptied.
	 *
	 * @param     array ...$args     An array of all arguments passed in.
	 *
	 * @since 1.0.0
	 */
	public function trigger( ...$args ) {
		do_action( 'activecampaign_for_woocommerce_cart_emptied' );
	}
	// phpcs:enable

}
