<?php

/**
 * The file for the EcomOrder Model
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
use Activecampaign_For_Woocommerce_Cofe_Ecom_Order_Status as Ecom_Order_Status;
use Activecampaign_For_Woocommerce_Ecom_Enum_Type as Enumish;
use AcVendor\Brick\Math\BigDecimal;

/**
 * The model class for the EcomOrder
 *
 * @since      1.0.0
 * @package    Activecampaign_For_Woocommerce
 * @subpackage Activecampaign_For_Woocommerce/includes/models
 * @author     acteamintegrations <team-integrations@activecampaign.com>
 */
class Activecampaign_For_Woocommerce_Cofe_Ecom_Order implements Ecom_Model, Has_Id, Has_Email {
	use Activecampaign_For_Woocommerce_Data_Validation ,
		Api_Serializable {
		serialize_to_array as serialize_all_but_products_to_array;
		set_properties_from_serialized_array as set_all_but_products_as_properties_from_serialized_array;
	}

	/**
	 * The mappings for the Api_Serializable trait.
	 *
	 * @var array
	 */
	public $api_mappings = [
		'connectionid'           => 'connectionId',
		'legacy_connectionid'    => 'legacyConnectionId', // how is this different from connectionId?
		'ac_status'              => 'normalizedStatus', // add fixed selections (PENDING, COMPLETED, ABANDONED, RECOVERED, WAITING, CANCELLED, REFUNDED, FAILED, RETURNED)
		'wc_status'              => 'storeStatus', // add raw
		'accepts_marketing'      => 'acceptsMarketing',
		'wc_customer_id'         => 'storeCustomerId',
		'currency'               => 'currency',
		'email'                  => 'email',
		'externalid'             => 'storeOrderId',
		'externalcheckoutid'     => 'cartId', // add this retrieval somewhere
		'external_created_date'  => 'storeCreatedDate',
		'external_updated_date'  => 'storeModifiedDate',
		'order_number'           => 'orderNumber',
		'source'                 => 'creationSource',
		'total_price'            => 'finalAmount',
		'order_url'              => 'orderUrl',
		'order_discounts'        => 'discounts', // deprecated? missing?
		'shipping_amount'        => 'shippingAmount',
		'shipping_method'        => 'shippingMethod',
		'discount_amount'        => 'discountsAmount',
		'billing_address'        => 'billingAddress',
		'shipping_address'       => 'shippingAddress',
		'payment_method'         => 'paymentMethod',
		'customer_note'          => 'notes',
		'customer_data_override' => 'customerData',
		'is_subscription'        => 'createdByRecurringPayment',
	];

	// customerLocale ? - can this be gathered maybe get_user_locale( int|WP_User $user )

	/**
	 * The mapping for the discount array.
	 *
	 * @var array The discount mapping array.
	 */
	private $discount_mappings = [
		'name'            => 'name',
		'type'            => 'type',
		'discount_amount' => 'discountAmount',
	];

	/**
	 * The required fields for an ecom order
	 *
	 * @var array
	 */
	private $required_fields = [
		'externalid',
		'connectionid',
		'legacy_connectionid',
		'email',
		'total_price',
		'source',
		'order_number',
		'currency',
		'external_created_date',
	];

	/**
	 * The abandoned date.
	 *
	 * @var string
	 */
	private $abandoned_date;

	/**
	 * The connection id.
	 *
	 * @var string
	 */
	private $connectionid;

	/**
	 * The legacy connection ID.
	 *
	 * @var string
	 */
	private $legacy_connectionid;

	/**
	 * The customer id.
	 *
	 * @var string
	 */
	public $customerid;

	/**
	 * The customer id.
	 *
	 * @var string
	 */
	public $wc_customer_id;

	/**
	 * The currency.
	 *
	 * @var string
	 */
	private $currency;

	/**
	 * The email.
	 *
	 * @var string
	 */
	private $email;

	/**
	 * The external id.
	 *
	 * @var string
	 */
	private $externalid;

	/**
	 * The external checkout id.
	 *
	 * @var string
	 */
	private $externalcheckoutid;

	/**
	 * The external created date.
	 *
	 * @var string
	 */
	private $external_created_date;

	/**
	 * The external updated date.
	 *
	 * @var string
	 */
	private $external_updated_date;

	/**
	 * The id.
	 *
	 * @var string
	 */
	private $id;

	/**
	 * The order number.
	 *
	 * @var string
	 */
	private $order_number;

	/**
	 * An array of Order Products for this order.
	 *
	 * @var Activecampaign_For_Woocommerce_Ecom_Product[]
	 */
	private $order_products = [];

	/**
	 * The source (1 or 0).
	 *
	 * @var Enumish
	 */
	private $source;

	/**
	 * The total price.
	 *
	 * @var string
	 */
	private $total_price;

	/**
	 * The order date.
	 *
	 * @var string
	 */
	private $order_date;

	/**
	 * The order/cart url.
	 *
	 * @var string
	 */
	private $order_url;

	/**
	 * The order discount amount total.
	 *
	 * @var string
	 */
	private $discount_amount;

	/**
	 * The order discounts applied.
	 *
	 * @var array
	 */
	private $order_discounts;

	/**
	 * The order shipping amount.
	 *
	 * @var string
	 */
	private $shipping_amount;

	/**
	 * The order shipping method.
	 *
	 * @var string
	 */
	private $shipping_method;

	/**
	 * The order tax amount.
	 *
	 * @var string
	 */
	private $tax_amount;

	/**
	 * The billing address.
	 *
	 * @var string
	 */
	private $billing_address;

	/**
	 * The shipping address.
	 *
	 * @var string
	 */
	private $shipping_address;

	/**
	 * The local WC status.
	 *
	 * @var string
	 */
	private $wc_status;

	/**
	 * The remote AC status.
	 *
	 * @var string
	 */
	private $ac_status;

	/**
	 * The payment method.
	 *
	 * @var string
	 */
	private $payment_method;

	/**
	 * The customer note.
	 *
	 * @var string
	 */
	private $customer_note;

	/**
	 * The customer accepts marketing value.
	 *
	 * @var string
	 */
	private $accepts_marketing;

	/**
	 * The contact data override.
	 *
	 * @var array
	 */
	private $customer_data_override;

	/**
	 * The subscription flag.
	 *
	 * @var bool
	 */
	private $is_subscription = false;

	/**
	 * Returns the abandoned date.
	 *
	 * @return string
	 */
	public function get_abandoned_date() {
		return $this->abandoned_date;
	}

	/**
	 * Sets the abandoned date.
	 *
	 * @param string $abandoned_date The abandoned date.
	 */
	public function set_abandoned_date( $abandoned_date ) {
		$this->abandoned_date = $abandoned_date;
	}

	/**
	 * Returns the connection id.
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
	 * Returns the legacy connection id.
	 *
	 * @return string
	 */
	public function get_legacy_connectionid() {
		return $this->legacy_connectionid;
	}

	/**
	 * Sets the legacy connection id.
	 *
	 * @param string $legacy_connectionid The legacy connection id.
	 */
	public function set_legacy_connectionid( $legacy_connectionid ) {
		$this->legacy_connectionid = $legacy_connectionid ? (int) $legacy_connectionid : null;
	}

	/**
	 * Returns the WC customer id.
	 *
	 * @return string
	 */
	public function get_wc_customer_id() {
		return $this->wc_customer_id;
	}

	/**
	 * Sets the WC customer id.
	 *
	 * @param string $wc_customer_id The customer id.
	 */
	public function set_wc_customer_id( $wc_customer_id ) {
		$this->wc_customer_id = $wc_customer_id ? (string) $wc_customer_id : null;
	}

	/**
	 * Returns the currency.
	 *
	 * @return string
	 */
	public function get_currency() {
		return $this->currency;
	}

	/**
	 * Sets the currency.
	 *
	 * @param string $currency The currency.
	 */
	public function set_currency( $currency ) {
		$this->currency = $currency;
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
	 * @throws RuntimeException Throws an exception for an invalid email to get a catch.
	 */
	public function set_email( $email ) {
		if ( self::check_valid_email( $email ) ) {
			$this->email = $email;
		} else {
			$this->email = null;
		}
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
		$this->externalid = $externalid ? (string) $externalid : null;
	}

	/**
	 * Returns the external checkout id.
	 *
	 * @return string
	 */
	public function get_externalcheckoutid() {
		return $this->externalcheckoutid;
	}

	/**
	 * Sets the external checkout id.
	 *
	 * @param string $externalcheckoutid The external checkout id.
	 */
	public function set_externalcheckoutid( $externalcheckoutid ) {
		$this->externalcheckoutid = $externalcheckoutid ? (string) $externalcheckoutid : null;
	}

	/**
	 * Returns the external created date.
	 *
	 * @return string
	 */
	public function get_external_created_date() {
		return $this->external_created_date;
	}

	/**
	 * Sets the external created date.
	 *
	 * @param string $external_created_date The external created date.
	 */
	public function set_external_created_date( $external_created_date ) {
		$this->external_created_date = $external_created_date;
	}

	/**
	 * Returns the external updated date.
	 *
	 * @return string
	 */
	public function get_external_updated_date() {
		return $this->external_updated_date;
	}

	/**
	 * Sets the external updated date.
	 *
	 * @param string $external_updated_date The external updated date.
	 */
	public function set_external_updated_date( $external_updated_date ) {
		$this->external_updated_date = $external_updated_date;
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
		$this->id = $id ? (string) $id : null;
	}

	/**
	 * Returns the order number.
	 *
	 * @return string
	 */
	public function get_order_number() {
		return $this->order_number;
	}

	/**
	 * Sets the order number.
	 *
	 * @param string $order_number The order number.
	 */
	public function set_order_number( $order_number ) {
		$this->order_number = $order_number ? (string) $order_number : null;
	}

	/**
	 * Returns the source (sync = 0 or webhook = 1).
	 *
	 * @return Enumish
	 */
	public function get_source() {
		return $this->source;
	}

	/**
	 * Sets the source. Changes it from 1 or 0 to HISTORICAL or REAL_TIME.
	 *
	 * @param Enumish $source The source (1 or 0).
	 */
	public function set_source( $source ) {
		if ( in_array( $source, [ 0, '0' ], false ) ) {
			$this->source = new Enumish( 'HISTORICAL' );
		} else {
			$this->source = new Enumish( 'REAL_TIME' );
		}
	}

	/**
	 * Returns the total price.
	 *
	 * @return string
	 */
	public function get_total_price() {
		return $this->total_price;
	}

	/**
	 * Sets the total price.
	 *
	 * @param string $total_price The total price.
	 */
	public function set_total_price( $total_price ) {
		$this->total_price = $total_price ? BigDecimal::of( $total_price ) : 0;
	}

	/**
	 * Set the order date
	 *
	 * @param string $date_time The order date.
	 */
	public function set_order_date( $date_time ) {
		$this->order_date = $date_time;
	}

	/**
	 * Get the order date
	 *
	 * @return string
	 */
	public function get_order_date() {
		return $this->order_date;
	}

	/**
	 * Set the order url.
	 *
	 * @param string $url The order url.
	 */
	public function set_order_url( $url ) {
		$this->order_url = $url;
	}

	/**
	 * Get the order url.
	 *
	 * @return string
	 */
	public function get_order_url() {
		return $this->order_url;
	}

	/**
	 * Set the order discount total.
	 *
	 * @param string $discount The order total discount.
	 */
	public function set_discount_amount( $discount ) {
		$this->discount_amount = $discount ? BigDecimal::of( $discount ) : 0;
	}

	/**
	 * Get the order total discount.
	 *
	 * @return string
	 */
	public function get_discount_amount() {
		return $this->discount_amount;
	}

	/**
	 * Set the order discount array.
	 *
	 * @param array $discounts The order discount array.
	 */
	public function set_order_discounts( $discounts ) {
		$this->order_discounts = $discounts;
	}

	/**
	 * Get the order discounts.
	 *
	 * @return array The order discount array.
	 */
	public function get_order_discounts() {
		return $this->order_discounts;
	}

	/**
	 * Set the order shipping total.
	 *
	 * @param string $shipping The order total shipping.
	 */
	public function set_shipping_amount( $shipping ) {
		$this->shipping_amount = $shipping ? BigDecimal::of( $shipping ) : 0;
	}

	/**
	 * Get the order total shipping.
	 *
	 * @return string
	 */
	public function get_shipping_amount() {
		return $this->shipping_amount;
	}

	/**
	 * Set the order shipping method.
	 *
	 * @param string $shipping The order shipping method.
	 */
	public function set_shipping_method( $shipping ) {
		$this->shipping_method = $shipping;
	}

	/**
	 * Get the order shipping method.
	 *
	 * @return string
	 */
	public function get_shipping_method() {
		return $this->shipping_method;
	}

	/**
	 * Set the order tax total.
	 *
	 * @param string $tax The order tax total.
	 */
	public function set_tax_amount( $tax ) {
		$this->tax_amount = $tax;
	}

	/**
	 * Get the order tax total.
	 *
	 * @return string
	 */
	public function get_tax_amount() {
		return $this->tax_amount;
	}

	/**
	 * Adds an Ecom product to the array of ecom products.
	 *
	 * @param Activecampaign_For_Woocommerce_Cofe_Ecom_Order_Line_Item $order_product The ecom product to be added.
	 */
	public function push_order_product( Activecampaign_For_Woocommerce_Cofe_Ecom_Order_Line_Item $order_product ) {
		$this->order_products[] = $order_product;
	}

	/**
	 * Returns the array of ecom products.
	 *
	 * @return array
	 */
	public function get_order_products() {
		return $this->order_products;
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
	 * Returns the billing address.
	 *
	 * @return string
	 */
	public function get_billing_address() {
		return $this->billing_address;
	}

	/**
	 * Sets the billing address.
	 *
	 * @param array $billing_address The serialized data for billing address.
	 */
	public function set_billing_address( $billing_address ) {
		$this->billing_address = $billing_address;
	}

	/**
	 * Returns the shipping address.
	 *
	 * @return string
	 */
	public function get_shipping_address() {
		return $this->shipping_address;
	}

	/**
	 * Sets the shipping address.
	 *
	 * @param array $shipping_address The serialized data for shipping address.
	 */
	public function set_shipping_address( $shipping_address ) {
		$this->shipping_address = $shipping_address;
	}

	/**
	 * Returns the WC order status.
	 *
	 * @return string
	 */
	public function get_wc_status() {
		return $this->wc_status;
	}

	/**
	 * Sets the ecommerce status.
	 *
	 * @param string $wc_status The WC status name.
	 */
	public function set_wc_status( $wc_status ) {
		$this->wc_status = $wc_status;

		// Automatically set the AC status from the WC status
		$this->set_ac_status( $wc_status );
	}

	/**
	 * Returns the AC order status.
	 *
	 * @return string
	 */
	public function get_ac_status() {
		return $this->ac_status;
	}

	/**
	 * Sets the AC appropriate status.
	 *
	 * @param string $status The status.
	 */
	public function set_ac_status( $status ) {
		$ecom_status = new Ecom_Order_Status();

		$ecom_status->set_ac_status_from_wc_status( $status );

		if ( ! is_null( $ecom_status->get_status() ) ) {
			$this->ac_status = new Enumish( $ecom_status->get_status() );
		} else {
			$logger = new Logger();
			$logger->warning( "This status [$status] is currently invalid for ActiveCampaign." );
			throw new RuntimeException( 'This order cannot currently be synced due to an unsupported order status.' );
		}
	}

	/**
	 * Returns the payment method.
	 *
	 * @return string
	 */
	public function get_payment_method() {
		return $this->payment_method;
	}

	/**
	 * Sets the payment method.
	 *
	 * @param string $payment_method The payment method.
	 */
	public function set_payment_method( $payment_method ) {
		$this->payment_method = $payment_method;
	}

	/**
	 * Returns the customer note.
	 *
	 * @return string
	 */
	public function get_customer_note() {
		return $this->customer_note;
	}

	/**
	 * Sets the customer note if available.
	 *
	 * @param string $customer_note The customer notes from the order.
	 */
	public function set_customer_note( $customer_note ) {
		$this->customer_note = $customer_note;
	}

	/**
	 * Returns the contact data override.
	 *
	 * @return array
	 */
	public function get_customer_data_override() {
		return $this->customer_data_override;
	}


	/**
	 * The customer information override to tell COFE exactly what to set on a contact.
	 * Data must look like customerData: {firstName: "Timothy" lastName: "The Chef" phone: "1110002222" company: "My Place"}
	 *
	 * @param array $data The serialized data.
	 */
	public function set_customer_data_override( $data ) {
		if ( isset( $data ) && ! empty( $data ) ) {
			$this->customer_data_override = $data;
		} else {
			$this->customer_data_override = null;
		}
	}

	/**
	 * Sets the bool for a subscription.
	 *
	 * @param bool $is_subscription The subscription bool.
	 */
	public function set_is_subscription( bool $is_subscription ) {
		$this->is_subscription = $is_subscription;
	}

	/**
	 * Returns the customer note.
	 *
	 * @return string
	 */
	public function get_is_subscription() {
		return $this->is_subscription;
	}
	/**
	 * Sets the properties from a serialized array returned from the API.
	 *
	 * Calls the set properties method of the trait used in this class, but first
	 * massages the data due to how ecom order products are returned by the API.
	 *
	 * @param array $array The serialized array.
	 */
	public function set_properties_from_serialized_array( array $array ) {
		$logger = new Logger();
		try {
			$this->set_all_but_products_as_properties_from_serialized_array( $array );
			if ( isset( $array['lineItems'] ) ) {
				foreach ( $array['lineItems'] as $product ) {
					if ( ! isset( $product['orderid'] ) ) {
						$product['orderid'] = $this->get_id();
					}

					$order_product = new Activecampaign_For_Woocommerce_Ecom_Product();

					if ( isset( $product ) ) {
						$order_product->set_properties_from_serialized_array( $product );
						$this->push_order_product( $order_product );
					}
				}
			}

			if ( isset( $array['orderDiscounts'] ) ) {
				foreach ( $array['orderDiscounts'] as $order_discounts ) {
					$mappings = $this->discount_mappings;

					// e.g., "order_number" => "orderNumber"
					foreach ( $mappings as $local_name => $remote_name ) {
						if ( isset( $array[ $remote_name ] ) ) {
							// e.g., set_order_number()
							$set_method = "set_$local_name";
							// e.g. $this->set_order_number($array['orderNumber']);
							$this->$set_method( $array[ $remote_name ] );
						}
					}
				}
			}
		} catch ( Throwable $t ) {
			$logger->warning(
				'There was an issue setting properties from serialized array for the cofe ecom order',
				[
					'message'          => $t->getMessage(),
					'suggested_action' => 'Please refer to the message for explanation.',
					'passed_array'     => $array,
					'ac_code'          => 'CEO_886',
					'trace'            => $logger->clean_trace( $t->getTrace() ),
				]
			);
		}
	}

	/**
	 * Sets all the properties directly available in order data.
	 *
	 * @param     array $order_data The order data from WC.
	 */
	public function set_properties_from_order_data( $order_data ) {
		if ( ! isset( $order_data['id'] ) ) {
			throw new RuntimeException( 'Order data is not available on order id ' . $order_data );
		}

		$logger = new Logger();

		if ( isset( $order_data['currency'] ) ) {
			$this->set_currency( $order_data['currency'] );
		}
		if ( isset( $order_data['number'] ) ) {
			$this->set_order_number( $order_data['number'] );
		}
		if ( isset( $order_data['id'] ) ) {
			$this->set_externalid( $order_data['id'] );
		}
		if ( isset( $order_data['ID'] ) ) {
			$this->set_externalid( $order_data['ID'] );
		}
		if ( isset( $order_data['discount_total'] ) ) {
			$this->set_discount_amount( $order_data['discount_total'] );
		}
		if ( isset( $order_data['shipping_total'] ) ) {
			$this->set_shipping_amount( $order_data['shipping_total'] );
		}
		if ( isset( $order_data['total'] ) ) {
			$this->set_total_price( $order_data['total'] );
		}
		if ( isset( $order_data['customer_id'] ) ) {
			// this is ok
			$this->set_wc_customer_id( $order_data['customer_id'] );
		}

		if ( isset( $order_data['billing']['email'] ) ) {
			$this->set_email( $order_data['billing']['email'] );
		}

		if ( isset( $order_data['payment_method'] ) ) {
			$this->set_payment_method( $order_data['payment_method'] );
		}

		if ( isset( $order_data['customer_note'] ) ) {
			$this->set_customer_note( $order_data['customer_note'] );
		}

		if ( isset( $order_data['shipping_method'] ) ) {
			$this->set_shipping_method( $order_data['shipping_method'] );
		}

		if ( isset( $order_data['created_via'] ) && 'subscription' === $order_data['created_via'] ) {
			$this->set_is_subscription( true );
		}

		$this->set_accepts_marketing( $this->get_order_metadata( $order_data, 'activecampaign_for_woocommerce_accepts_marketing' ) );

		if ( isset( $order_data['date_created'] ) ) {
			$created_date = new DateTime( $order_data['date_created'], new DateTimeZone( 'UTC' ) );
			$this->set_order_date( $created_date->format( DATE_ATOM ) );
			$this->set_external_created_date( $created_date->format( DATE_ATOM ) );
		}

		if ( isset( $order_data['date_modified'] ) ) {
			$modified_date = new DateTime( $order_data['date_modified'], new DateTimeZone( 'UTC' ) );
			$this->set_external_updated_date( $modified_date->format( DATE_ATOM ) );
		}

		$billing_address = new Activecampaign_For_Woocommerce_Ecom_Address();
		if ( isset( $order_data['billing'] ) ) {
			$billing_address->set_properties_from_order_data( $order_data['billing'] );
			$serialized_billing = $billing_address->serialize_to_array();

			if ( isset( $serialized_billing ) && ! empty( $serialized_billing ) ) {
				$this->set_billing_address( $serialized_billing );
			} else {
				$this->set_billing_address( null );
			}
		} else {
			$this->set_billing_address( null );
		}

		$shipping_address = new Activecampaign_For_Woocommerce_Ecom_Address();
		if ( isset( $order_data['shipping'] ) ) {
			$shipping_address->set_properties_from_order_data( $order_data['shipping'] );

			$serialized_shipping = $shipping_address->serialize_to_array();
			if ( isset( $serialized_shipping ) && ! empty( $serialized_shipping ) ) {
				$this->set_shipping_address( $serialized_shipping );
			} else {
				$this->set_shipping_address( null );
			}
		} else {
			$this->set_shipping_address( null );
		}

		if (
			count( array_diff( $order_data['shipping'], $order_data['billing'] ) ) > 0
		) {
			$override = $billing_address->get_override_properties();
			if ( ! empty( $override ) ) {
				$this->set_customer_data_override( $override );
			}
		}

		// Sets both status types
		if ( isset( $order_data['status'] ) ) {
			$this->set_wc_status( $order_data['status'] );
		}
	}

	/**
	 * @param array  $data The WC order data.
	 * @param string $field_name The defined field name.
	 *
	 * @return mixed|null
	 */
	private function get_order_metadata( $data, $field_name ) {
		if ( isset( $data['meta_data'] ) ) {
			foreach ( $data['meta_data'] as $meta_data ) {
				if ( is_array( $meta_data ) && $field_name === $meta_data['key'] ) {
					return $meta_data['value'];
				}

				if ( is_object( $meta_data ) && $field_name === $meta_data->key ) {
					return $meta_data->value;
				}
			}
		}

		return null;
	}

	/**
	 * Serializes the model to an associative array.
	 *
	 * Calls the serialize method of the trait used in this class, but first
	 * massages the data due to how ecom order products are expected by the API.
	 *
	 * @return array
	 */
	public function serialize_to_array() {
		try {
			$array = $this->serialize_all_but_products_to_array();

			$order_products = [];

			foreach ( $this->order_products as $order_product ) {
				$order_products[] = $order_product->serialize_to_array();
			}

			$array['lineItems'] = $order_products;

			return $array;
		} catch ( Throwable $t ) {
			$logger = new Logger();
			$logger->warning(
				'Activecampaign_For_Woocommerce_Ecom_Order: The serialize_to_array function encountered an issue. A valid order object may not exist.',
				[
					'message' => $t->getMessage(),
					'trace'   => $logger->clean_trace( $t->getTrace() ),
				]
			);

			return null;
		}
	}

	/**
	 * Validate the required fields and return a bool
	 *
	 * @return bool
	 */
	public function validate_model() {
		$logger   = new Logger();
		$bad_data = [];

		try {
			// One of these is required
			if ( empty( $this->externalcheckoutid ) && empty( $this->externalid ) ) {
				$logger->warning(
					'Activecampaign_For_Woocommerce_Ecom_Order: No external ID has been set. This is required.',
					[
						'email'        => $this->email,
						'order_number' => $this->order_number,

					]
				);
			}

			// One of the two connection fields needs to be set
			if (
				! isset( $this->legacy_connectionid ) &&
				! isset( $this->connectionid ) &&
				empty( $this->legacy_connectionid ) &&
				empty( $this->connectionid )
			) {
				$bad_data['legacy_connectionid'] = $this->legacy_connectionid;
				$bad_data['connectionid']        = $this->connectionid;
			}

			foreach ( $this->required_fields as $field ) {
				if (
					'source' === $field &&
					(
						is_int( $this->source ) &&
						1 !== $this->source &&
						0 !== $this->source
					) &&
					(
							new Enumish( 'HISTORICAL' ) !== $this->source &&
							new Enumish( 'REAL_TIME' ) !== $this->source &&
							'HISTORICAL' !== $this->source &&
							'REAL_TIME' !== $this->source
						)
				) {
					$bad_data['source'] = $this->source;
				}

				if ( 'total_price' === $field && ! isset( $this->total_price ) ) {
					$bad_data['total_price'] = $this->total_price;
				}

				if (
					'total_price' !== $field &&
					'source' !== $field &&
					'legacy_connectionid' === $field &&
					'connectionid' === $field &&
					empty( $this->{$field} )
				) {
					$bad_data[ $field ] = $this->{$field};
				}
			}
			if ( count( $bad_data ) > 0 ) {
				$logger->error(
					'The following required fields may be missing from the data order data.',
					[
						'suggested_action'    => 'If you would like this record synced, please verify the data for the order exists.',
						'ac_code'             => 'CEOM_1113',
						'email'               => $this->email,
						'order_number'        => $this->order_number,
						'missing or bad data' => $bad_data,
					]
				);

				return false;
			}
		} catch ( Throwable $t ) {
			$logger->warning(
				'Activecampaign_For_Woocommerce_Ecom_Order: There was an error validating the ecom order model.',
				[
					'message' => $t->getMessage(),
					'trace'   => $logger->clean_trace( $t->getTrace() ),
				]
			);
		}

		return true;
	}

	/**
	 * Converts the order to json.
	 *
	 * @return false|string
	 */
	public function order_to_json() {
		return wp_json_encode( $this->serialize_to_array() );
	}

	/**
	 * @param ?WC_DateTime $wc_date_field
	 * @return string|null
	 */
	private static function date_format( $wc_date_field ): ?string {
		return null !== $wc_date_field ? $wc_date_field->__toString() : null;
	}
}
