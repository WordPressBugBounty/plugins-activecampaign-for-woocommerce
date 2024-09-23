<?php

/**
 * The file for the Ecom Customer Model
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
use Activecampaign_For_Woocommerce_Logger as Logger;

/**
 * The model class for the Ecom Customer
 *
 * @since      1.0.0
 * @package    Activecampaign_For_Woocommerce
 * @subpackage Activecampaign_For_Woocommerce/includes/models
 * @author     acteamintegrations <team-integrations@activecampaign.com>
 */
class Activecampaign_For_Woocommerce_Subscription_Customer_Model implements Ecom_Model, Has_Id {
	use Api_Serializable,
		Activecampaign_For_Woocommerce_Data_Validation;

	/**
	 * The API mappings for the API_Serializable trait.
	 *
	 * @var array
	 */
	public $api_mappings = [
		'first_name'        => 'firstName',
		'last_name'         => 'lastName',
		'accepts_marketing' => 'acceptsMarketing',
		'company'           => 'company',
		'phone'             => 'phone',
	];

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
	 * The customer's company.
	 *
	 * @var string
	 */
	private $company;

	/**
	 * The customer's phone number.
	 *
	 * @var string
	 */
	private $phone;

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
		$this->accepts_marketing = $accepts_marketing ? (bool) $accepts_marketing : false;
	}

	/**
	 * Returns the phone.
	 *
	 * @return string
	 */
	public function get_phone() {
		return $this->phone;
	}

	/**
	 * Sets the phone.
	 *
	 * @param string $phone The last_name.
	 */
	public function set_phone( $phone ) {
		$this->phone = $phone;
	}

	/**
	 * Returns the company.
	 *
	 * @return string
	 */
	public function get_company() {
		return $this->company;
	}

	/**
	 * Sets the company.
	 *
	 * @param string $company The company.
	 */
	public function set_company( $company ) {
		$this->company = $company;
	}

	/**
	 * Generates the customer object from the subscription.
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
						'Could not establish WC_Customer object from the subscription',
						[
							'message' => $t->getMessage(),
							'ac_code' => 'SCM_228',
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
			} catch ( Throwable $t ) {
				$logger->warning(
					'Could not get user data from subscription.',
					[
						'message' => $t->getMessage(),
						'ac_code' => 'SCM_248',
						'trace'   => $logger->clean_trace( $t->getTrace() ),
					]
				);
			}

			if (
				! isset( $customer ) ||
				! self::validate_object( $order, 'get_customer_id' ) ||
				! $order->get_customer_id()
			) {
				try {
					// This customer doesn't have a customer or user dataset, set externalid to zero
					$this->externalid = 0;
					if ( self::validate_object( $order, 'get_billing_first_name' ) ) {
						$this->first_name = $order->get_billing_first_name();
						$this->last_name  = $order->get_billing_last_name();
					}
				} catch ( Throwable $t ) {
					$logger->warning(
						'There was a problem preparing customer data for a record.',
						[
							'message' => $t->getMessage(),
							'ac_code' => 'SCM_271',
							'trace'   => $logger->clean_trace( $t->getTrace() ),
						]
					);

					return false;
				}
			}
		} else {
			$logger->warning(
				'Could not create an ecom customer from this subscription because the subscription may not exist.',
				[
					'suggested_action' => 'Verify this subscription exists, otherwise you can ignore this issue.',
					'order'            => $order,
					'ac_code'          => 'SCM_285',
				]
			);
		}
	}
}
