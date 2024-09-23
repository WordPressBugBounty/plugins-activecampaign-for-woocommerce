<?php

/**
 * The file for the COFE service subscription model.
 *
 * @link       https://www.activecampaign.com/
 * @since      1.0.0
 *
 * @package    Activecampaign_For_Woocommerce
 * @subpackage Activecampaign_For_Woocommerce/includes/subscriptions/models
 */

use Activecampaign_For_Woocommerce_Api_Serializable as Api_Serializable;
use Activecampaign_For_Woocommerce_Ecom_Model_Interface as Ecom_Model;
use Activecampaign_For_Woocommerce_Has_Id as Has_Id;
use Activecampaign_For_Woocommerce_Has_Email as Has_Email;
use Activecampaign_For_Woocommerce_Logger as Logger;
use Activecampaign_For_Woocommerce_Cofe_Subscription_Status as Ecom_Subscription_Status;
use Activecampaign_For_Woocommerce_Ecom_Enum_Type as Enumish;
use AcVendor\Brick\Math\BigDecimal;

/**
 * The model class for the RecurringPayments model.
 *
 * @since      1.0.0
 * @package    Activecampaign_For_Woocommerce
 * @subpackage Activecampaign_For_Woocommerce/includes/subscriptions/models
 * @author     acteamintegrations <team-integrations@activecampaign.com>
 */
class Activecampaign_For_Woocommerce_Subscription_Model implements Ecom_Model, Has_Id, Has_Email {
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
		'legacy_connectionid'         => 'legacyConnectionId', // how is this different from connectionId?
		'subscription_id'             => 'storeRecurringPaymentId',
		'email'                       => 'email',
		'ac_status'                   => 'normalizedStatus', // add fixed selections (PENDING, COMPLETED, ABANDONED, RECOVERED, WAITING, CANCELLED, REFUNDED, FAILED, RETURNED)
		'wc_status'                   => 'storeStatus', // add raw
		'billing_period'              => 'billingInterval', // enum
		'billing_interval_count'      => 'billingIntervalCount', // int
		'total_price'                 => 'paymentAmount', // double // Different than the total price but how? 20.00,
		'discount_total'              => 'discountAmount', // double
		'total_tax'                   => 'taxAmount', // double
		'shipping_amount'             => 'shippingAmount',
		'last_payment_date_gmt'       => 'lastPaymentDate', // tstamp
		'start_date_gmt'              => 'startDate', // tstamp also anchorDate?
		'next_payment_date_gmt'       => 'nextPaymentDate', // tstamp
		'parent_id'                   => 'originOrderId', // string // relating to a COFE orderId
		'date_created_gmt'            => 'storeCreatedDate', // tstamp
		'date_modified_gmt'           => 'storeModifiedDate', // tstamp
		'cancelled_date_gmt'          => 'cancelledDate', // tstamp
		'is_trial'                    => 'isTrial',
		'currency'                    => 'currency',
		'customer_note'               => 'notes',
		'billing_address'             => 'billingAddress',
		'shipping_address'            => 'shippingAddress',
		'customer_data'               => 'customerData', // net new fields after here
		'wc_customer_id'              => 'storeCustomerId',
		'line_item_names'             => 'lineItemNames',
		'line_item_categories'        => 'lineItemCategories',
		'line_item_skus'              => 'lineItemSkus',
		'line_item_brands'            => 'lineItemBrands',
		'line_item_tags'              => 'lineItemTags',
		'line_item_store_primary_ids' => 'lineItemStorePrimaryIds',
		'source'                      => 'suppressAutomations',
	];

	/**
	 * The required fields for an ecom subscription
	 *
	 * @var array
	 */
	private $required_fields = [
		'legacy_connectionid',
		'email',
		'total_price',
		'source',
	];

	/**
	 * The subscription ID set by WC.
	 *
	 * @var string
	 */
	private $subscription_id;

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
	public $wc_customer_id;

	/**
	 * The currency in the store.
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
	 * The id.
	 *
	 * @var string
	 */
	private $id;

	/**
	 * The source (1 or 0).
	 *
	 * @var bool
	 */
	private $source;

	/**
	 * The total price.
	 *
	 * @var string
	 */
	private $total_price;

	/**
	 * The subscription shipping amount.
	 *
	 * @var string
	 */
	private $shipping_amount;

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
	 * The customer note.
	 *
	 * @var string
	 */
	private $customer_note;

	/**
	 * The contact data override.
	 *
	 * @var array
	 */
	private $customer_data;

	/**
	 * The line item categories.
	 *
	 * @var array
	 */
	private $line_item_categories = array();

	/**
	 * The line item names.
	 *
	 * @var array
	 */
	private $line_item_names = array();

	/**
	 * The line item skus.
	 *
	 * @var array
	 */
	private $line_item_skus = array();

	/**
	 * The line item brands.
	 * Currently unused in the WC plugin.
	 *
	 * @var array
	 */
	private $line_item_brands = array();

	/**
	 * The line item tags.
	 *
	 * @var array
	 */
	private $line_item_tags = array();

	/**
	 * The line item store primary IDs.
	 * This may not be used for woocommerce.
	 *
	 * @var array
	 */
	private $line_item_store_primary_ids = array();

	/**
	 * The billing period.
	 *
	 * @var string
	 */
	private $billing_period;

	/**
	 * @var string
	 */
	private $billing_interval_count; // valid, done

	/**
	 * @var string
	 */
	private $discount_total; // valid, done
	/**
	 * @var string
	 */
	private $total_tax; // valid, done
	/**
	 * @var string
	 */
	private $last_payment_date_gmt;
	/**
	 * @var string
	 */
	private $start_date_gmt;
	/**
	 * @var string
	 */
	private $next_payment_date_gmt;
	/**
	 * @var string
	 */
	private $date_modified_gmt;
	/**
	 * @var string
	 */
	private $date_created_gmt;
	/**
	 * @var string
	 */
	private $cancelled_date_gmt;
	/**
	 * @var string
	 */
	private $parent_id; // valid, done
	/**
	 * @var string
	 */
	private $subscription_period;
	/**
	 * @var string
	 */
	private $subscription_interval;
	/**
	 * @var string
	 */
	private $subscription_length;
	/**
	 * @var string
	 */
	private $trial_length;
	/**
	 * @var string
	 */
	private $trial_period; // trial_period
	/**
	 * @var string
	 */
	private $trial_end_date_gmt;

	/**
	 * Flag for if it is a trial or not.
	 *
	 * @var bool
	 */
	private $is_trial;

	/**
	 * Gets the billing period.
	 *
	 * @return mixed
	 */
	public function get_billing_period() {
		return $this->billing_period;
	}


	/**
	 * How often the customer pays.
	 * Enum of: DAILY, WEEKLY, MONTHLY, YEARLY
	 *
	 * @param enum $billing_period
	 */
	public function set_billing_period( $billing_period ) {
		if ( in_array( $billing_period, [ 'day', 'daily' ], false ) ) {
			$this->billing_period = new Enumish( 'DAILY' );
			return;
		}
		if ( in_array( $billing_period, [ 'week', 'weekly' ], false ) ) {
			$this->billing_period = new Enumish( 'WEEKLY' );
			return;
		}
		if ( in_array( $billing_period, [ 'month', 'monthly' ], false ) ) {
			$this->billing_period = new Enumish( 'MONTHLY' );
			return;
		}
		if ( in_array( $billing_period, [ 'year', 'yearly' ], false ) ) {
			$this->billing_period = new Enumish( 'YEARLY' );
			return;
		}

		$this->billing_period = null;
	}


	public function get_billing_interval_count() {
		return $this->billing_interval_count;
	}

	public function set_billing_interval_count( $billing_interval ) {
		$this->billing_interval_count = (int) $billing_interval;
	}

	public function get_discount_total() {
		return $this->discount_total;
	}
	public function set_discount_total( $discount_total ) {
		$this->discount_total = $discount_total;
	}

	public function get_total_tax() {
		return $this->total_tax;
	}
	public function set_total_tax( $total_tax ) {
		$this->total_tax = $total_tax;
	}

	/**
	 * Get the last payment date for GMT.
	 *
	 * @return string
	 */
	public function get_last_payment_date_gmt() {
		return $this->last_payment_date_gmt;
	}
	public function set_last_payment_date_gmt( $last_payment_date_gmt ) {
		$this->last_payment_date_gmt = $last_payment_date_gmt;
	}

	/**
	 * Get the start date for GMT.
	 *
	 * @return string
	 */
	public function get_start_date_gmt() {
		return $this->start_date_gmt;
	}

	public function set_start_date_gmt( $start_date_gmt ) {
		$this->start_date_gmt = $start_date_gmt;
	}

	/**
	 * Get the next payment date for GMT.
	 * (if known)
	 *
	 * @return string
	 */
	public function get_next_payment_date_gmt() {
		return $this->next_payment_date_gmt;
	}

	/**
	 * Set the cancelled date for GMT.
	 *
	 * @param string $next_payment_date_gmt
	 */
	public function set_next_payment_date_gmt( $next_payment_date_gmt ) {
		$this->next_payment_date_gmt = $next_payment_date_gmt;
	}

	/**
	 * Get the cancelled date for GMT.
	 *
	 * @return string
	 */
	public function get_cancelled_date_gmt() {
		return $this->cancelled_date_gmt;
	}

	/**
	 * Set the cancelled date for GMT.
	 *
	 * @param string $cancelled_date_gmt
	 */
	public function set_cancelled_date_gmt( $cancelled_date_gmt ) {
		$this->cancelled_date_gmt = $cancelled_date_gmt;
	}


	public function get_parent_id() {
		return $this->parent_id;
	}
	public function set_parent_id( $parent_id ) {
		$this->parent_id = (string) $parent_id;
	}

	/**
	 * Get the created date for GMT.
	 *
	 * @return string
	 */
	public function get_date_created_gmt() {
		return $this->date_created_gmt;
	}

	/**
	 * Set the created date for GMT.
	 *
	 * @param string $date_created_gmt
	 */
	public function set_date_created_gmt( $date_created_gmt ) {
		$this->date_created_gmt = $date_created_gmt;
	}


	/**
	 * Get the modified date for GMT.
	 *
	 * @return string
	 */
	public function get_date_modified_gmt() {
		return $this->date_modified_gmt;
	}

	/**
	 * Set the modified date for GMT.
	 *
	 * @param string $date_modified_gmt
	 */
	public function set_date_modified_gmt( $date_modified_gmt ) {
		$this->date_modified_gmt = $date_modified_gmt;
	}


	/**
	 * Set the line item categories.
	 *
	 * @param array $lines
	 */
	public function set_line_item_categories( $lines ) {
		$this->line_item_categories = $lines;
	}

	/**
	 * Add a line item category.
	 *
	 * @param string $line
	 */
	public function add_line_item_category( $line ) {
		$this->set_line_item_categories( array_push( $this->line_item_categories, $line ) );
	}

	/**
	 * Get the line item categories.
	 *
	 * @return array
	 */
	public function get_line_item_categories() {
		return $this->line_item_categories;
	}

	/**
	 * Set the line item names.
	 *
	 * @param array $lines
	 */
	public function set_line_item_names( $lines ) {
		$this->line_item_names = $lines;
	}

	/**
	 * Add a line item name.
	 */
	public function add_line_item_name( $line ) {
		$this->set_line_item_names( array_push( $this->line_item_names, $line ) );
	}

	/**
	 * Get the line item names.
	 *
	 * @return array
	 */
	public function get_line_item_names() {
		return $this->line_item_names;
	}

	/**
	 * Sets the line item skus.
	 *
	 * @param array $lines
	 */
	public function set_line_item_skus( $lines ) {
		$this->line_item_skus = $lines;
	}

	/**
	 * Add a line item sku.
	 *
	 * @param string $line
	 */
	public function add_line_item_skus( $line ) {
		$this->set_line_item_skus( array_push( $this->line_item_skus, $line ) );
	}

	/**
	 * Get the line item skus.
	 *
	 * @return array
	 */
	public function get_line_item_skus() {
		return $this->line_item_skus;
	}

	/**
	 * Set the line item brands.
	 *
	 * @param array $lines
	 */
	public function set_line_item_brands( $lines ) {
		$this->line_item_brands = $lines;
	}

	/**
	 * Add a line item brand.
	 *
	 * @param string $line
	 */
	public function add_line_item_brands( $line ) {
		$this->set_line_item_brands( array_push( $this->line_item_brands, $line ) );
	}

	/**
	 * Get the line item brands.
	 *
	 * @return array
	 */
	public function get_line_item_brands() {
		return $this->line_item_brands;
	}

	/**
	 * @param array $lines
	 */
	public function set_line_item_tags( $lines ) {
		$this->line_item_tags = $lines;
	}

	/**
	 * Add a line item tag.
	 */
	public function add_line_item_tags( $line ) {
		$this->set_line_item_tags( array_push( $this->line_item_tags, $line ) );
	}

	/**
	 * Get the line item tags.
	 *
	 * @return array
	 */
	public function get_line_item_tags() {
		return $this->line_item_tags;
	}

	/**
	 * Set the line item store primary ids.
	 *
	 * @param array $lines
	 */
	public function set_line_item_store_primary_ids( $lines ) {
		$this->line_item_store_primary_ids = $lines;
	}

	/**
	 * Add a line item store primary id.
	 */
	public function add_line_item_store_primary_ids( $line ) {
		$this->set_line_item_store_primary_ids( array_push( $this->line_item_store_primary_ids, $line ) );
	}

	/**
	 * Get the line item store primary ids.
	 *
	 * @return array
	 */
	public function get_line_item_store_primary_ids() {
		return $this->line_item_store_primary_ids;
	}
	// customerLocale ? - can this be gathered maybe get_user_locale( int|WP_User $user )

	/**
	 * Returns the subscription id.
	 *
	 * @return string
	 */
	public function get_subscription_id() {
		return $this->subscription_id;
	}

	/**
	 * Sets the subscription id.
	 *
	 * @param string $subscription_id The subscription id value.
	 */
	public function set_subscription_id( $subscription_id ) {
		$this->subscription_id = (string) $subscription_id;
	}

	/**
	 * @return mixed
	 */
	public function get_subscription_period() {
		return $this->subscription_period;
	}

	/**
	 * @param string $subscription_period
	 */
	public function set_subscription_period( $subscription_period ) {
		$this->subscription_period = $subscription_period;
	}

	/**
	 * @return mixed
	 */
	public function get_subscription_interval() {
		return $this->subscription_interval;
	}

	/**
	 * @param string $subscription_interval
	 */
	public function set_subscription_interval( $subscription_interval ) {
		$this->subscription_interval = $subscription_interval;
	}

	/**
	 * @return mixed
	 */
	public function get_subscription_length() {
		return $this->subscription_length;
	}

	/**
	 * @param string $subscription_length
	 */
	public function set_subscription_length( $subscription_length ) {
		$this->subscription_length = $subscription_length;
	}

	/**
	 * @return mixed
	 */
	public function get_trial_length() {
		return $this->trial_length;
	}

	/**
	 * @param string $trial_length
	 */
	public function set_trial_length( $trial_length ) {
		$this->trial_length = $trial_length;
	}

	/**
	 * @return mixed
	 */
	public function get_trial_period() {
		return $this->trial_period;
	}

	/**
	 * @param string $trial_period
	 */
	public function set_trial_period( $trial_period ) {
		$this->trial_period = $trial_period;
	}

	/**
	 * @return mixed
	 */
	public function get_trial_end_date_gmt() {
		return $this->trial_end_date_gmt;
	}

	/**
	 * @param string $trial_end_date_gmt
	 */
	public function set_trial_end_date_gmt( $trial_end_date_gmt ) {
		$this->trial_end_date_gmt = $trial_end_date_gmt;
	}

	/**
	 * Returns the abandoned date.
	 *
	 * @return bool
	 */
	public function get_is_trial() {
		return $this->is_trial;
	}

	/**
	 * Returns the abandoned date.
	 *
	 * @param bool $is_trial
	 */
	public function set_is_trial( $is_trial ) {
		$this->is_trial = $is_trial;
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
	 * Returns the source (sync = 0 or webhook = 1).
	 *
	 * @return bool
	 */
	public function get_source() {
		return $this->source;
	}

	/**
	 * Sets the source. Changes it from 1 or 0 to HISTORICAL or REAL_TIME.
	 *
	 * @param bool $source The source (1 or 0).
	 */
	public function set_source( $source ) {
		if ( in_array( $source, [ 0, '0' ], false ) ) {
			$this->source = true;
		} else {
			$this->source = false;
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
	 * Set the subscription shipping total.
	 *
	 * @param string $shipping The subscription total shipping.
	 */
	public function set_shipping_amount( $shipping ) {
		$this->shipping_amount = $shipping ? BigDecimal::of( $shipping ) : 0;
	}

	/**
	 * Get the subscription total shipping.
	 *
	 * @return string
	 */
	public function get_shipping_amount() {
		return $this->shipping_amount;
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
	 * Returns the WC subscription status.
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
	 * Returns the AC subscription status.
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
		$ecom_status = new Ecom_Subscription_Status();

		$ecom_status->set_ac_status_from_wc_status( $status );

		if ( ! is_null( $ecom_status->get_status() ) ) {
			$this->ac_status = new Enumish( $ecom_status->get_status() );
		} else {
			$logger = new Logger();
			$logger->warning( "This subscription status [$status] is currently invalid for ActiveCampaign." );
			throw new RuntimeException( 'This subscription cannot currently be synced due to an unsupported normalized subscription status.' );
		}
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
	 * @param string $customer_note The customer notes from the subscription.
	 */
	public function set_customer_note( $customer_note ) {
		$this->customer_note = $customer_note;
	}

	/**
	 * Returns the contact data override.
	 *
	 * @return array
	 */
	public function get_customer_data() {
		return $this->customer_data;
	}


	/**
	 * The customer information override to tell COFE exactly what to set on a contact.
	 * Data must look like customerData: {firstName: "Timothy" lastName: "The Chef" phone: "1110002222" company: "My Place"}
	 *
	 * @param array $data The serialized data.
	 */
	public function set_customer_data( $data ) {
		if ( isset( $data ) && ! empty( $data ) ) {
			$this->customer_data = $data;
		} else {
			$this->customer_data = null;
		}
	}

	/**
	 * Sets the properties from a serialized array returned from the API.
	 *
	 * Calls the set properties method of the trait used in this class, but first
	 * massages the data due to how ecom subscription products are returned by the API.
	 *
	 * @param array $array The serialized array.
	 */
	public function set_properties_from_serialized_array( array $array ) {
		$logger = new Logger();
		try {
			$this->set_all_but_products_as_properties_from_serialized_array( $array );
		} catch ( Throwable $t ) {
			$logger->warning(
				'There was an issue setting properties from serialized array for the cofe ecom subscription',
				[
					'message'          => $t->getMessage(),
					'suggested_action' => 'Please refer to the message for explanation.',
					'passed_array'     => $array,
					'ac_code'          => 'WSM_1060',
					'trace'            => $logger->clean_trace( $t->getTrace() ),
				]
			);
		}
	}

	/**
	 * Sets all the properties directly available in subscription data.
	 *
	 * @param     array $order_data The subscription subscription data from WC.
	 */
	public function set_properties_from_subscription_data( $order_data ) {
		if ( ! isset( $order_data['id'] ) ) {
			throw new RuntimeException( 'Subscription data is not available on subscription id ' . $order_data );
		}

		$logger = new Logger();

		try {
			if ( isset( $order_data['currency'] ) ) {
				$this->set_currency( $order_data['currency'] );
			}
			if ( isset( $order_data['parent_id'] ) ) {
				$this->set_parent_id( $order_data['parent_id'] );
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

			if ( isset( $order_data['customer_note'] ) ) {
				$this->set_customer_note( $order_data['customer_note'] );
			}

			if ( isset( $order_data['date_created'] ) ) {
				$this->set_date_created_gmt( $this->date_to_gmt( $order_data['date_created'] ) );
			}

			if ( isset( $order_data['date_modified'] ) ) {
				$this->set_date_modified_gmt( $this->date_to_gmt( $order_data['date_modified'] ) );
			}

			if ( isset( $order_data['schedule_next_payment'] ) ) {
				$this->set_next_payment_date_gmt( $this->date_to_gmt( $order_data['schedule_next_payment'] ) );
			}

			if ( isset( $order_data['billing_period'] ) ) {
				$this->set_billing_period( $order_data['billing_period'] );
				$this->set_subscription_period( $order_data['billing_period'] );
			}

			if ( isset( $order_data['billing_interval'] ) ) {
				$this->set_billing_interval_count( $order_data['billing_interval'] );
			}

			if ( isset( $order_data['schedule_trial_end'] ) ) {
				$this->set_trial_end_date_gmt( $this->date_to_gmt( $order_data['schedule_trial_end'] ) );
			}
			if ( isset( $order_data['schedule_start'] ) ) {
				$this->set_start_date_gmt( $this->date_to_gmt( $order_data['schedule_start'] ) );
			}
			if ( isset( $order_data['schedule_cancelled'] ) ) {
				$this->set_cancelled_date_gmt( $this->date_to_gmt( $order_data['schedule_cancelled'] ) );
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

			// Sets both status types
			if ( isset( $order_data['status'] ) ) {
				$this->set_wc_status( $order_data['status'] );
			}
		} catch ( Throwable $t ) {
			$logger->error(
				'Could not set all properties from subscription data.',
				[
					'message' => $t->getMessage(),
					'trace'   => $t->getTrace(),
					'ac_code' => 'WSM_1175',
				]
			);
		}
	}

	private function date_to_gmt( $date_field ) {
		if ( isset( $date_field ) && ! empty( $date_field ) ) {
			$modified_date = new DateTime( $date_field, new DateTimeZone( 'UTC' ) );
			$date_gmt      = $modified_date->format( DATE_ATOM );
			return $date_gmt;
		}

		return null;
	}
	/**
	 * @param array  $data The WC subscription data.
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
	 * massages the data due to how ecom subscription products are expected by the API.
	 *
	 * @return array
	 */
	public function serialize_to_array() {
		try {
			$array = $this->serialize_all_but_products_to_array();

			return $array;
		} catch ( Throwable $t ) {
			$logger = new Logger();
			$logger->warning(
				'The serialize_to_array function encountered an issue. A valid subscription object may not exist.',
				[
					'message' => $t->getMessage(),
					'trace'   => $logger->clean_trace( $t->getTrace() ),
					'ac_code' => 'WSM_1232',
				]
			);

			return null;
		}
	}

	/**
	 * Converts the subscription to json.
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
