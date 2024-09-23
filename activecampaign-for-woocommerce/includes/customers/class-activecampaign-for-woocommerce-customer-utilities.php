<?php

/**
 * Various customer utilities for the Activecampaign_For_Woocommerce plugin.
 *
 * @link       https://www.activecampaign.com/
 * @since      1.5.0
 *
 * @package    Activecampaign_For_Woocommerce
 * @subpackage Activecampaign_For_Woocommerce/includes/customers
 */

use Activecampaign_For_Woocommerce_Ecom_Customer as Ecom_Customer;
use Activecampaign_For_Woocommerce_Ecom_Order as Ecom_Order;
use Activecampaign_For_Woocommerce_Logger as Logger;
use Activecampaign_For_Woocommerce_User_Meta_Service as User_Meta_Service;

/**
 * The Customer Utilities Class.
 *
 * @since      1.5.0
 * @package    Activecampaign_For_Woocommerce
 * @subpackage Activecampaign_For_Woocommerce/includes/customers
 * @author     acteamintegrations <team-integrations@activecampaign.com>
 */
class Activecampaign_For_Woocommerce_Customer_Utilities {
	use Activecampaign_For_Woocommerce_Data_Validation;

	/**
	 * The custom ActiveCampaign logger
	 *
	 * @var Activecampaign_For_Woocommerce_Logger
	 */
	private $logger;

	/**
	 * Activecampaign_For_Woocommerce_Customer_Utilities constructor.
	 *
	 * @param     Logger|null $logger     The Logger.
	 */
	public function __construct(
		Logger $logger = null
	) {
		if ( ! $logger ) {
			$this->logger = new Logger();
		} else {
			$this->logger = $logger;
		}
	}

	/**
	 * Add the customer info to the order object.
	 *
	 * @param     WC_Order   $order The WC order.
	 * @param     Ecom_Order $ecom_order The AC order.
	 * @param     bool       $is_admin Is the process called by admin (Session & Customer not available).
	 *
	 * @return Ecom_Order|null
	 */
	public function add_customer_to_order( $order, $ecom_order, $is_admin = false ) {
		if (
			self::validate_object( $order, 'get_user_id' ) &&
			self::validate_object( $ecom_order, 'set_id' ) &&
			$order->get_user_id()
		) {
			try {
				// Set if the AC id is set
				$ecom_order->set_id( User_Meta_Service::get_current_cart_ac_id( $order->get_user_id() ) );
				if ( get_user_meta( $order->get_user_id(), 'activecampaign_for_woocommerce_ac_customer_id' ) ) {
					// if it's an AC customer already stored in hosted
					$ac_customerid = get_user_meta( $order->get_user_id(), 'activecampaign_for_woocommerce_ac_customer_id' );
					$ecom_order->set_customerid( $ac_customerid );
				}
			} catch ( Throwable $t ) {
				$this->logger->error(
					'Activecampaign_For_Woocommerce_Customer_Utilities: There was an error adding customer to the order.',
					[
						'message'     => $t->getMessage(),
						'stack_trace' => $t->getTrace(),
					]
				);
			}
		} elseif ( self::validate_object( $order, 'get_customer_id' ) && $order->get_customer_id() ) {
			$ecom_order->set_id( User_Meta_Service::get_current_cart_ac_id( $order->get_customer_id() ) );

			if ( get_user_meta( $order->get_customer_id(), 'activecampaign_for_woocommerce_ac_customer_id' ) ) {
				$ac_customerid = get_user_meta( $order->get_customer_id(), 'activecampaign_for_woocommerce_ac_customer_id' );
				$ecom_order->set_customerid( $ac_customerid );
			}
		}

		try {
			if (
				! $is_admin &&
				wc()->customer && self::validate_object( wc()->customer, 'get_email' ) &&
				wc()->customer->get_email()
			) {
					// Set the email address from customer
					$ecom_order->set_email( wc()->customer->get_email() );

			} elseif (
				self::validate_object( $order, 'get_user_id' ) &&
				get_user_meta( $order->get_user_id(), 'email' )
			) {

					$email = get_user_meta( $order->get_user_id(), 'email' );
					// Set the email address from user
					$ecom_order->set_email( $email );

			} elseif (
				self::validate_object( $order, 'get_billing_email' ) &&
				$order->get_billing_email()
			) {

					// Set the email address from order
					$ecom_order->set_email( $order->get_billing_email() );
			}

			return $ecom_order;
		} catch ( Throwable $t ) {
			$this->logger->error(
				'Activecampaign_For_Woocommerce_Customer_Utilities: There was an error adding customer to the order.',
				[
					'message'     => $t->getMessage(),
					'stack_trace' => $t->getTrace(),
					'ac_code'     => 'CU_122',
				]
			);

			return null;
		}
	}

	/**
	 * Returns a customer ID if we can find one.
	 *
	 * @param     WC_Order|null $order The order object.
	 *
	 * @return bool|string
	 */
	public function get_customer_id( $order = null ) {
		if ( is_null( $order ) && self::validate_object( wc()->session, 'get_customer_id' ) && ! empty( wc()->session->get_customer_id() ) ) {
			return wc()->session->get_customer_id();
		}

		if ( ! is_null( $order ) && self::validate_object( $order, 'get_customer_id' ) && ! empty( $order->get_customer_id() ) ) {
			return $order->get_customer_id();
		}

		if ( isset( wc()->customer ) && self::validate_object( wc()->customer, 'get_id' ) && ! empty( wc()->customer->get_id() ) ) {
			return wc()->customer->get_id();
		}

		if ( isset( wc()->session ) && self::validate_object( wc()->session, 'get_customer_id' ) && ! empty( wc()->session->get_customer_id() ) ) {
			return wc()->session->get_customer_id();
		}

		if ( ! is_null( $order ) && self::validate_object( $order, 'get_user_id' ) && ! empty( $order->get_user_id() ) ) {
			return $order->get_user_id();
		}

		$this->logger->debug(
			'Customer Utilities: Could not find a customer ID.',
			[
				'order'         => $order,
				'id'            => self::validate_object( $order, 'get_id' ) ? $order->get_id() : null,
				'order_number'  => self::validate_object( $order, 'get_order_number' ) ? $order->get_order_number() : null,
				'billing_email' => self::validate_object( $order, 'get_billing_email' ) ? $order->get_billing_email() : null,
				'ac_code'       => 'CU_162',
			]
		);

		return false;
	}

	/**
	 * Updates the last synced date on a record.
	 *
	 * @param int $customer_id The customer ID from the order.
	 *
	 * @throws Exception Does not stop.
	 */
	public function update_last_synced( $customer_id ) {
		$date = new DateTime( 'now', new DateTimeZone( 'UTC' ) );
		try {
			update_user_meta( $customer_id, 'activecampaign_for_woocommerce_last_synced', $date->format( 'Y-m-d H:i:s e' ) );
		} catch ( Throwable $t ) {
			$this->logger->warning(
				'Activecampaign_For_Woocommerce_Order_Finished_Event: Could not create datetime or save sync metadata to the customer',
				[
					'message'  => $t->getMessage(),
					'date'     => $date->format( 'Y-m-d H:i:s e' ),
					'order_id' => $customer_id,
					'trace'    => $this->logger->clean_trace( $t->getTrace() ),
					'ac_code'  => 'CU_187',
				]
			);
		}
	}

	/**
	 * Stores the ActiveCampaign ID for the customer record
	 *
	 * @param string $customer_id The WC customer ID.
	 * @param string $ac_id The Hosted record ID.
	 */
	public function store_ac_id( $customer_id, $ac_id ) {
		update_user_meta( $customer_id, 'activecampaign_for_woocommerce_hosted_customer_id', $ac_id );
	}

	/**
	 * Get a customer ID from the order.
	 *
	 * @param WC_Order $order The order object.
	 *
	 * @return mixed
	 */
	public function get_ac_customer_id_from_order( $order ) {
		if ( self::validate_object( $order, 'get_customer_id' ) ) {
			$customer_id = $order->get_customer_id();
		} elseif ( isset( $order['customer_id'] ) ) {
			$customer_id = $order['customer_id'];
		}

		if ( isset( $customer_id ) && get_user_meta( $customer_id, 'activecampaign_for_woocommerce_ac_customer_id' ) ) {
			// if it's an AC customer already stored in hosted
			$ac_customerid = get_user_meta( $order['customer_id'], 'activecampaign_for_woocommerce_ac_customer_id' );
			return $ac_customerid;
		}
	}

	/**
	 * Builds a customer from the user_id using stored meta
	 *
	 * @param     int $user_id     The user id.
	 *
	 * @return object
	 */
	public function build_customer_from_user_meta( $user_id ) {
		try {
			$this->customer = new Ecom_Customer();
			$this->customer->set_connectionid( $this->connection_id );
			$this->customer->set_email( $this->customer_email );
			$this->customer->set_first_name( get_user_meta( $user_id, 'first_name', true ) );
			$this->customer->set_first_name_c( get_user_meta( $user_id, 'first_name', true ) );
			$this->customer->set_last_name( get_user_meta( $user_id, 'last_name', true ) );
			$this->customer->set_last_name_c( get_user_meta( $user_id, 'last_name', true ) );
			$this->customer->set_accepts_marketing( $this->accepts_marketing );
		} catch ( Throwable $t ) {
			$this->logger->error(
				'There was an exception building a customer from user data.',
				[
					'message' => $t->getMessage(),
					'trace'   => $this->logger->clean_trace( $t->getTrace() ),
				]
			);

			return false;
		}

		return $this->customer;
	}

	/**
	 * Builds the customer data we need for abandoned cart.
	 *
	 * @param     array|null $passed_data The data passed.
	 *
	 * @return array|string
	 */
	public function build_customer_data( $passed_data = null ) {
		try {
			// Get current customer
			if (
				isset( wc()->customer ) &&
				! is_null( wc()->customer ) &&
				self::validate_object( wc()->customer, 'get_id' ) &&
				! empty( wc()->customer->get_id() ) &&
				! empty( wc()->customer->get_email() )
			) {
				$customer_data               = wc()->customer->get_data();
				$customer_data['id']         = wc()->customer->get_id(); // This is a user id if registered or a UUID if guest
				$customer_data['email']      = wc()->customer->get_email();
				$customer_data['first_name'] = wc()->customer->get_first_name();
				$customer_data['last_name']  = wc()->customer->get_last_name();
			} else {
				if (
					isset( wc()->session ) &&
					! is_null( wc()->session ) &&
					self::validate_object( wc()->session, 'get_customer_id' )
				) {
					// We don't have a real WC customer, get the session customer
					$customer_data = wc()->session->get( 'customer' );

					// Make sure we've set the id
					$customer_data['id'] = wc()->session->get_customer_id();
				}
			}

			// If we have guest data passed in, replace with that
			if ( ! empty( $passed_data ) ) {
				if ( isset( $passed_data['billing']['email'] ) ) {
					$customer_data['email'] = $passed_data['billing']['email'];
				}
				if ( isset( $passed_data['billing']['last_name'] ) ) {
					$customer_data['last_name'] = $passed_data['billing']['last_name'];
				}
				if ( isset( $passed_data['billing']['first_name'] ) ) {
					$customer_data['first_name'] = $passed_data['billing']['first_name'];
				}

				if ( isset( $passed_data['customer_email'] ) ) {
					$customer_data['email'] = $passed_data['customer_email'];
				}
				if ( isset( $passed_data['customer_first_name'] ) ) {
					$customer_data['first_name'] = $passed_data['customer_first_name'];
				}
				if ( isset( $passed_data['customer_last_name'] ) ) {
					$customer_data['last_name'] = $passed_data['customer_last_name'];
				}
				if ( isset( $passed_data['accepts_marketing'] ) ) {
					$customer_data['accepts_marketing'] = $passed_data['accepts_marketing'];
				}
			}

			if ( ! empty( $customer_data['email'] ) ) {
				// Set the customer data for billing
				$customer_data['billing_email'] = $customer_data['email'];
			}

			if ( ! empty( $customer_data['first_name'] ) ) {
				$customer_data['billing_first_name'] = $customer_data['first_name'];
			}

			if ( ! empty( $customer_data['last_name'] ) ) {
				$customer_data['billing_last_name'] = $customer_data['last_name'];
			}

			return $customer_data;
		} catch ( Throwable $t ) {
			$this->logger->error(
				'Abandoned sync: Encountered an error on gathering customer and/or session data for the abandonment sync',
				[
					'message' => $t->getMessage(),
					'trace'   => $this->logger->clean_trace( $t->getTrace() ),
				]
			);
		}
	}
}
