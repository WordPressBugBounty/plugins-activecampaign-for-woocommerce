<?php

/**
 * The file that defines the Executable Command Interface.
 *
 * @link       https://www.activecampaign.com/
 * @since      1.1.0
 *
 * @package    Activecampaign_For_Woocommerce
 * @subpackage Activecampaign_For_Woocommerce/includes/commands
 */

use Activecampaign_For_Woocommerce_Admin as Admin;
use Activecampaign_For_Woocommerce_Ecom_Customer_Repository as Ecom_Customer_Repository;
use Activecampaign_For_Woocommerce_Logger as Logger;
use Activecampaign_For_Woocommerce_Save_Abandoned_Cart_Command as Abandoned_Cart;

/**
 * Handles sending the guest customer and pending order to AC.
 * When the email input field on the checkout page is changed,
 * an Ajax request will run the execute method.
 *
 * @since      1.1.0
 * @package    Activecampaign_For_Woocommerce
 * @subpackage Activecampaign_For_Woocommerce/includes/commands
 * @author     acteamintegrations <team-integrations@activecampaign.com>
 */
class Activecampaign_For_Woocommerce_Sync_Guest_Abandoned_Cart_Command implements Activecampaign_For_Woocommerce_Executable_Interface {
	use Activecampaign_For_Woocommerce_Abandoned_Cart_Utilities,
		Activecampaign_For_Woocommerce_Data_Validation,
		Activecampaign_For_Woocommerce_Admin_Utilities;

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
	 * Hash of the WooCommerce session ID plus the guest customer email.
	 * Used to identify an order as being created in a pending state.
	 *
	 * @var string
	 */
	private $external_checkout_id;

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
	 * Activecampaign_For_Woocommerce_Sync_Guest_Abandoned_Cart_Command constructor.
	 *
	 * @param     WC_Cart|null                              $cart     The WC Cart.
	 * @param     WC_Customer|null                          $customer     The WC Customer.
	 * @param     WC_Session|null                           $wc_session     The WC Session.
	 * @param     Activecampaign_For_Woocommerce_Admin|null $admin     The admin object.
	 * @param     Ecom_Customer_Repository|null             $customer_repository     The Ecom Customer Repo.
	 * @param     Logger                                    $logger     The ActiveCampaign WooCommerce logger.
	 */
	public function __construct(
		WC_Cart $cart = null,
		WC_Customer $customer = null,
		WC_Session $wc_session = null,
		Admin $admin,
		Ecom_Customer_Repository $customer_repository,
		Logger $logger = null
	) {
		$this->cart                = $cart;
		$this->customer            = $customer;
		$this->wc_session          = $wc_session;
		$this->admin               = $admin;
		$this->customer_repository = $customer_repository;
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
		try {

			if ( ! $this->cart ) {
				$this->cart = wc()->cart;
			}
			if ( ! $this->customer ) {
				$this->customer = wc()->customer;
			}
			if ( ! $this->wc_session ) {
				$this->wc_session = wc()->session;
			}
			if ( ! $this->logger ) {
				$this->logger = new Logger();
			}
		} catch ( Throwable $t ) {
			$this->logger->debug(
				'Could not initialize the guest cart functions. Session, cart, or customer may be throwing an error.',
				[
					'message'  => $t->getMessage(),
					'customer' => wc()->customer,
					'cart'     => wc()->cart,
					'session'  => wc()->session,
				]
			);
		}
	}

	// phpcs:disable Generic.CodeAnalysis.UnusedFunctionParameter

	/**
	 * Execute this command.
	 *
	 * @param     mixed ...$args     The array of parameters passed.
	 */
	public function execute( ...$args ) {
		$this->init();

		if (
			! $this->validate_request() ||
			! $this->setup_woocommerce_customer() ||
			! $this->setup_woocommerce_cart()
		) {
			wp_send_json_error( 'AC Abandoned cart could not store the request.' );
		}

		try {
			$abandoned_cart = new Abandoned_Cart();

			if ( ! $abandoned_cart->init_data(
				[
					'customer_email'      => $this->customer_email,
					'customer_first_name' => $this->customer_first_name,
					'customer_last_name'  => $this->customer_last_name,
				]
			) ) {
				wp_send_json_error( 'Could not initialize abandoned cart data.' );
			}
		} catch ( Throwable $t ) {
			$this->logger->warning(
				'Sync Guest Abandoned Cart: Some POST information was missing from the AJAX call.',
				[
					'message' => $t->getMessage(),
					'trace'   => $this->logger->clean_trace( $t->getTrace() ),
				]
			);
			wp_send_json_error( 'Guest abandoned cart was missing data and could not store.' );
		}

		wp_send_json_success( 'AC Abandoned cart stored' );
	}
	// phpcs:enable

	/**
	 * Validate that the request has all necessary data
	 *
	 * @return bool Whether or not this job was successful
	 */
	private function validate_request() {
		if ( is_user_logged_in() ) {
			$this->logger->debug(
				'Abandon cart guest sync: User is logged in, cannot perform guest sync',
				[
					'is_user_logged_in' => is_user_logged_in(),
					'current_user'      => wp_get_current_user(),
				]
			);

			wp_send_json_error( 'AC Abandoned cart could not validate request.' );
			return false;
		}

		if ( ! wp_verify_nonce( self::get_request_data( 'nonce' ), 'sync_guest_abandoned_cart_nonce' ) ) {
			$this->logger->debug( 'Abandon cart guest sync: Could not verify sync_guest_abandoned_cart_nonce' );
			wp_send_json_error( 'Invalid nonce for abandoned cart.' );
			return false;
		}

		$this->customer_email = sanitize_email( self::get_request_data( 'email' ) );

		if ( empty( $this->customer_email ) ) {
			$this->logger->debug(
				'Abandon cart guest sync: Invalid customer email',
				[
					'email'         => $this->customer_email,
					'request email' => self::get_request_data( 'email' ),
				]
			);

			wp_send_json_error( 'AC Abandoned cart experienced an issue retrieving the customer email.' );
			return false;
		}

		$this->customer_first_name = self::get_request_data( 'first_name' );
		if ( empty( $this->customer_first_name ) ) {
			// this is allowed to be empty
			$this->customer_first_name = '';
		}

		$this->customer_last_name = self::get_request_data( 'last_name' );
		if ( empty( $this->customer_last_name ) ) {
			// this is allowed to be empty
			$this->customer_last_name = '';
		}

		return true;
	}

	/**
	 * Set up the WooCommerce customer object
	 * with the customer's email address
	 *
	 * @return bool Whether or not this job was successful
	 */
	private function setup_woocommerce_customer() {
		// Obtain WooCommerce customer model
		$this->customer_woo = $this->customer;

		if ( ! self::validate_object( $this->customer_woo, 'get_email' ) ) {
			$this->logger->debug( 'Abandon cart guest sync: customer_woo not an instance of WC_Customer' );
			wp_send_json_error( 'AC Abandoned cart could not set up a WooCommerce customer object for saving an abandoned cart.' );
			return false;
		}

		$this->customer_woo->set_email( $this->customer_email );
		return true;
	}

	/**
	 * Set up the WooCommerce cart object with the checkout ID
	 *
	 * @return bool Whether or not this job was successful
	 */
	private function setup_woocommerce_cart() {
		if ( ! self::validate_object( $this->cart, 'get_cart' ) || $this->cart->is_empty() ) {
			$this->logger->debug( 'Abandon cart guest sync: cart not an instance of WC_Cart' );
			wp_send_json_error( 'AC Abandoned cart experienced an issue setting up a cart or the cart is empty.' );
			return false;
		}

		$this->external_checkout_id = $this->generate_externalcheckoutid( $this->wc_session->get_customer_id(), $this->customer_email );

		return true;
	}

	/**
	 * Set the logger (for testing)
	 *
	 * @param     Activecampaign_For_Woocommerce_Logger $logger     The logger.
	 */
	public function setLogger( $logger ) {
		$this->logger = $logger;
	}

	/**
	 * Set the session (for testing)
	 *
	 * @param     WC_Session|null $wc_session     The session.
	 */
	public function setWcSession( $wc_session ) {
		$this->wc_session = $wc_session;
	}
}
