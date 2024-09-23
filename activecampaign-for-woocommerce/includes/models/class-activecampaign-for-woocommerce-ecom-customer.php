<?php

/**
 * The file for the EcomCustomer Model
 *
 * @link       https://www.activecampaign.com/
 * @since      1.0.0
 *
 * @package    Activecampaign_For_Woocommerce
 * @subpackage Activecampaign_For_Woocommerce/includes/models
 */

use Activecampaign_For_Woocommerce_Api_Serializable as Api_Serializable;
use Activecampaign_For_Woocommerce_Ecom_Model_Interface as Ecom_Model;
use Activecampaign_For_Woocommerce_Has_Id as Has_Id;
use Activecampaign_For_Woocommerce_Has_Email as Has_Email;
use Activecampaign_For_Woocommerce_Logger as Logger;

/**
 * The model class for the Ecom Customer
 *
 * @since      1.0.0
 * @package    Activecampaign_For_Woocommerce
 * @subpackage Activecampaign_For_Woocommerce/includes/models
 * @author     acteamintegrations <team-integrations@activecampaign.com>
 */
class Activecampaign_For_Woocommerce_Ecom_Customer implements Ecom_Model, Has_Id, Has_Email {
	use Api_Serializable,
		Activecampaign_For_Woocommerce_Data_Validation;

	/**
	 * The API mappings for the API_Serializable trait.
	 *
	 * @var array
	 */
	public $api_mappings = [
		'connectionid'      => 'connectionid',
		'externalid'        => 'externalid',
		'email'             => 'email',
		'id'                => 'id',
		'first_name'        => 'first_name',
		'last_name'         => 'last_name',
		'accepts_marketing' => 'acceptsMarketing',
	];

	/**
	 * The connection id.
	 *
	 * @var string
	 */
	private $connectionid;

	/**
	 * The external id.
	 *
	 * @var string
	 */
	private $externalid;

	/**
	 * The email address.
	 *
	 * @var string
	 */
	private $email;

	/**
	 * The id.
	 *
	 * @var string
	 */
	private $id;

	/**
	 * The customer's first name.
	 *
	 * @var string
	 */
	private $first_name;

	/**
	 * The customer's last name.
	 *
	 * @var string
	 */
	private $last_name;

	/**
	 * The customer's marketing checkbox choice.
	 *
	 * @var string
	 */
	private $accepts_marketing;

	/**
	 * Returns a connection id.
	 *
	 * @return string
	 */
	public function get_connectionid() {
		return $this->connectionid;
	}

	/**
	 * Sets the connection id.
	 *
	 * @param string $connectionid The connection id.
	 */
	public function set_connectionid( $connectionid ) {
		$this->connectionid = $connectionid;
	}

	/**
	 * Returns the external id.
	 *
	 * @return string
	 */
	public function get_externalid() {
		return $this->externalid;
	}

	/**
	 * Sets the external id.
	 *
	 * @param string $externalid The external id.
	 */
	public function set_externalid( $externalid ) {
		$this->externalid = $externalid;
	}

	/**
	 * Returns the email.
	 *
	 * @return string
	 */
	public function get_email() {
		return $this->email;
	}

	/**
	 * Sets the email.
	 *
	 * @param string $email The email.
	 * @throws RuntimeException Throws an exception if the email is invalid.
	 */
	public function set_email( $email ) {
		if ( self::check_valid_email( $email ) ) {
			$this->email = $email;
		} else {
			$this->email = null;
		}
	}

	/**
	 * Returns the id.
	 *
	 * @return string
	 */
	public function get_id() {
		return $this->id;
	}

	/**
	 * Sets the id.
	 *
	 * @param string $id The id.
	 */
	public function set_id( $id ) {
		$this->id = $id;
	}

	/**
	 * Returns the first_name.
	 *
	 * @return string
	 */
	public function get_first_name() {
		return $this->first_name;
	}

	/**
	 * Sets the first_name.
	 *
	 * @param string $first_name The first_name.
	 */
	public function set_first_name( $first_name ) {
		$this->first_name = $first_name;
	}

	/**
	 * Returns the last_name.
	 *
	 * @return string
	 */
	public function get_last_name() {
		return $this->last_name;
	}

	/**
	 * Sets the last_name.
	 *
	 * @param string $last_name The last_name.
	 */
	public function set_last_name( $last_name ) {
		$this->last_name = $last_name;
	}

	/**
	 * Returns the accepts_marketing.
	 *
	 * @return string
	 */
	public function get_accepts_marketing() {
		return $this->accepts_marketing;
	}

	/**
	 * Sets the accepts_marketing.
	 *
	 * @param string $accepts_marketing The accepts marketing checkbox value.
	 */
	public function set_accepts_marketing( $accepts_marketing ) {
		$this->accepts_marketing = $accepts_marketing;
	}

	/**
	 * Generates the customer object from the order.
	 *
	 * @param     WC_Order $order The WC Order.
	 *
	 * @return bool
	 */
	public function create_ecom_customer_from_order( $order ) {
		$logger = new Logger();
		if ( isset( $order ) && self::validate_object( $order, 'get_id' ) && ! empty( $order->get_id() ) ) {
			if ( self::validate_object( $order, 'get_customer_id' ) && $order->get_customer_id() ) {
				try {
					// Use the customer information
					$customer = new WC_Customer( $order->get_customer_id(), false );

					if ( self::validate_object( $customer, 'get_id' ) && ! empty( $customer->get_id() ) ) {
						$this->externalid = $customer->get_id();
					}

					if ( self::validate_object( $customer, 'get_email' ) && ! empty( $customer->get_email() ) ) {
						$this->email = $customer->get_email();
					} elseif ( self::validate_object( $customer, 'get_billing_email' ) && ! empty( $customer->get_billing_email() ) ) {
						$this->email = $customer->get_billing_email();
					}

					if ( self::validate_object( $customer, 'get_first_name' ) && ! empty( $customer->get_first_name() ) ) {
						$this->first_name = $customer->get_first_name();
					} elseif ( self::validate_object( $customer, 'get_billing_first_name' ) && ! empty( $customer->get_billing_first_name() ) ) {
						$this->first_name = $customer->get_billing_first_name();
					}

					if ( self::validate_object( $customer, 'get_last_name' ) && ! empty( $customer->get_last_name() ) ) {
						$this->last_name = $customer->get_last_name();
					} elseif ( self::validate_object( $customer, 'get_billing_last_name' ) && ! empty( $customer->get_billing_last_name() ) ) {
						$this->last_name = $customer->get_billing_last_name();
					}
				} catch ( Throwable $t ) {
					$logger->warning(
						'Could not establish WC_Customer object from the order',
						[
							'message' => $t->getMessage(),
							'ac_code' => 'ECM_263',
							'trace'   => $t->getTrace(),
						]
					);
				}
			}

			try {
				if ( ( ! isset( $this->first_name ) || empty( $this->first_name ) ) && self::validate_object( $order, 'get_billing_first_name' ) && ! empty( $order->get_billing_first_name() ) ) {
					$this->first_name = $order->get_billing_first_name();
				}

				if ( ( ! isset( $this->last_name ) || empty( $this->last_name ) ) && self::validate_object( $order, 'get_billing_last_name' ) && ! empty( $order->get_billing_last_name() ) ) {
					$this->last_name = $order->get_billing_last_name();
				}

				if ( ( ! isset( $this->email ) || empty( $this->email ) ) && self::validate_object( $order, 'get_billing_email' ) && ! empty( $order->get_billing_email() ) ) {
					$this->email = $order->get_billing_email();
				}
			} catch ( Throwable $t ) {
				$logger->warning(
					'Could not get user data from order.',
					[
						'customer_email' => self::validate_object( $order, 'get_billing_email' ) ? $order->get_billing_email() : null,
						'message'        => $t->getMessage(),
						'ac_code'        => 'ECM_290',
						'trace'          => $logger->clean_trace( $t->getTrace() ),
					]
				);
			}

			if (
				! isset( $customer ) ||
				! self::validate_object( $order, 'get_customer_id' ) ||
				! self::validate_object( $customer, 'get_email' ) ||
				! $order->get_customer_id() ||
				! $customer->get_email()
			) {
				try {
					// This customer doesn't have a customer or user dataset, set externalid to zero
					$this->externalid = 0;
					if ( self::validate_object( $order, 'get_billing_email' ) ) {
						$this->email      = $order->get_billing_email();
						$this->first_name = $order->get_billing_first_name();
						$this->last_name  = $order->get_billing_last_name();
					}
				} catch ( Throwable $t ) {
					$logger->warning(
						'There was a problem preparing customer data for a record.',
						[
							'customer_email' => self::validate_object( $order, 'get_billing_email' ) ? $order->get_billing_email() : null,
							'message'        => $t->getMessage(),
							'ac_code'        => 'ECM_317',
							'trace'          => $logger->clean_trace( $t->getTrace() ),
						]
					);

					return false;
				}
			}
		} else {
			$logger->warning(
				'Could not create an ecom customer from this order because the order may not exist.',
				[
					'suggested_action' => 'Verify this order exists, otherwise you can ignore this issue.',
					'order'            => $order,
					'ac_code'          => 'ECM_331',
				]
			);
		}
	}
}
