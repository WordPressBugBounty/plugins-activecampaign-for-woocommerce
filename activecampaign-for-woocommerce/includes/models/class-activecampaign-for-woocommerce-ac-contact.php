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
 * The model class for the Ecom Contact
 *
 * @since      1.0.0
 * @package    Activecampaign_For_Woocommerce
 * @subpackage Activecampaign_For_Woocommerce/includes/models
 * @author     acteamintegrations <team-integrations@activecampaign.com>
 */
class Activecampaign_For_Woocommerce_AC_Contact implements Ecom_Model, Has_Id, Has_Email {
	use Api_Serializable;
	use Activecampaign_For_Woocommerce_Data_Validation;

	/**
	 * The API mappings for the API_Serializable trait.
	 *
	 * @var array
	 */
	public $api_mappings = array(
		'connectionid' => 'connectionid',
		'externalid'   => 'externalid',
		'email'        => 'email',
		'id'           => 'id',
		'first_name'   => 'firstName',
		'first_name_c' => 'first_name',
		'last_name'    => 'lastName',
		'last_name_c'  => 'last_name',
		'phone'        => 'phone',
		'tags'         => 'tags',
	);

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
	 * The customer's first name camel case.
	 *
	 * @var string
	 */
	private $first_name_c;

	/**
	 * The customer's last name camel case.
	 *
	 * @var string
	 */
	private $last_name_c;

	/**
	 * The customer's phone number.
	 *
	 * @var string
	 */
	private $phone;

	/**
	 * The tags for the customer.
	 *
	 * @var array
	 */
	private $tags;

	public function __construct() {

		// Start completely empty
		$this->id         = null;
		$this->externalid = '';
		$this->email      = '';
		$this->first_name = '';
		$this->last_name  = '';
		$this->phone      = '';
	}

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
	 * @param     string $connectionid     The connection id.
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
	 * @param     string $externalid     The external id.
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
	 * @param     string $email     The email.
	 * @throws RuntimeException Throws a runtime exception because a bad email will end a sync process.
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
	 * @param     string $id     The id.
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
	 * @param     string $first_name     The first_name.
	 */
	public function set_first_name( $first_name ) {
		$this->first_name = $first_name;
	}

	/**
	 * Returns the first_name_c.
	 *
	 * @return string
	 */
	public function get_first_name_c() {
		return $this->first_name_c;
	}

	/**
	 * Sets the first_name_c.
	 *
	 * @param     string $first_name     The first_name.
	 */
	public function set_first_name_c( $first_name ) {
		$this->first_name_c = $first_name;
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
	 * @param     string $last_name     The last_name.
	 */
	public function set_last_name( $last_name ) {
		$this->last_name = $last_name;
	}

	/**
	 * Returns the last_name_c.
	 *
	 * @return string
	 */
	public function get_last_name_c() {
		return $this->last_name;
	}

	/**
	 * Sets the last_name_c.
	 *
	 * @param     string $last_name     The last_name.
	 */
	public function set_last_name_c( $last_name ) {
		$this->last_name_c = $last_name;
	}

	/**
	 * Returns the phone number.
	 *
	 * @return string
	 */
	public function get_phone() {
		return $this->phone;
	}

	/**
	 * Sets the phone.
	 *
	 * @param     string $phone     The phone number.
	 */
	public function set_phone( $phone ) {
		$this->phone = $phone;
	}

	/**
	 * Sets the tag on the tags array.
	 *
	 * @param     string $tag     The tag to add to the array.
	 */
	public function set_tag( $tag ) {
		if ( isset( $this->tags ) && ! empty( $this->tags ) && ! in_array( $tag, $this->tags, true ) ) {
			$this->tags[] = $tag;
		} else {
			$this->tags = array( $tag );
		}
	}

	public function get_tags() {
		return $this->tags;
	}

	/**
	 * Creates the contact model from an order object.
	 *
	 * @param     WC_Order $wc_order_or_subscription The WC Order or Subscription.
	 *
	 * @return bool
	 */
	public function create_ecom_contact_from_order( $wc_order_or_subscription ) {
		$logger = new Logger();
		if ( isset( $wc_order_or_subscription ) && self::validate_object( $wc_order_or_subscription, 'get_id' ) && $wc_order_or_subscription->get_id() ) {
			$wc_customer = null;
			if ( $wc_order_or_subscription->get_customer_id() ) {
				try {
					// Use the customer information
					$wc_customer = new WC_Customer( $wc_order_or_subscription->get_customer_id(), false );

					if ( $wc_customer->get_id() ) {
						// Note this is for WC customer ID specifically.
						$this->externalid = $wc_customer->get_id();
					}

					if ( $wc_customer->get_email() ) {
						$this->email = $wc_customer->get_email();
					} elseif ( $wc_customer->get_billing_email() ) {
						$this->email = $wc_customer->get_billing_email();
					}

					if ( $wc_customer->get_first_name() ) {
						$this->first_name = $wc_customer->get_first_name();
					} elseif ( $wc_customer->get_billing_first_name() ) {
						$this->first_name = $wc_customer->get_billing_first_name();
					}

					if ( $wc_customer->get_last_name() ) {
						$this->last_name = $wc_customer->get_last_name();
					} elseif ( $wc_customer->get_billing_last_name() ) {
						$this->last_name = $wc_customer->get_billing_last_name();
					}

					if ( $wc_customer->get_billing_phone() ) {
						$this->phone = $wc_customer->get_billing_phone();
					}

					return true;
				} catch ( Throwable $t ) {
					$logger->notice(
						'AC Contact: Could not establish WC_Customer object',
						array(
							'message' => $t->getMessage(),
						)
					);
				}
			}

			if ( ! $wc_customer || ! $wc_order_or_subscription->get_customer_id() || ! $wc_customer->get_email() ) {
				try {
					$this->externalid = 0;
					if ( self::validate_object( $wc_order_or_subscription, 'get_billing_email' ) ) {
						$this->email      = $wc_order_or_subscription->get_billing_email();
						$this->first_name = $wc_order_or_subscription->get_billing_first_name();
						$this->last_name  = $wc_order_or_subscription->get_billing_last_name();
						$this->phone      = $wc_order_or_subscription->get_billing_phone();

						return true;
					}
				} catch ( Throwable $t ) {
					$logger->warning(
						'AC Contact: There was a problem preparing data for a record.',
						array(
							'customer_email' => self::validate_object( $wc_order_or_subscription, 'get_billing_email' ) ? $wc_order_or_subscription->get_billing_email() : null,
							'message'        => $t->getMessage(),
						)
					);

				}
			}
		}
		return false;
	}
}
