<?php

/**
 * The file for the Ecom Customer Address Model
 *
 * @link       https://www.activecampaign.com/
 * @since      1.0.0
 *
 * @package    Activecampaign_For_Woocommerce
 * @subpackage Activecampaign_For_Woocommerce/includes/models
 */

use Activecampaign_For_Woocommerce_Api_Serializable as Api_Serializable;

/**
 * The model class for the Ecom Customer Address
 *
 * @since      1.0.0
 * @package    Activecampaign_For_Woocommerce
 * @subpackage Activecampaign_For_Woocommerce/includes/models
 * @author     acteamintegrations <team-integrations@activecampaign.com>
 */
class Activecampaign_For_Woocommerce_Ecom_Address {
	use Api_Serializable,
		Activecampaign_For_Woocommerce_Data_Validation;

	/**
	 * The API mappings for the API_Serializable trait.
	 *
	 * @var array
	 */
	public $api_mappings = [
		'first_name' => 'firstName',
		'last_name'  => 'lastName',
		'address_1'  => 'address1',
		'address_2'  => 'address2',
		'city'       => 'city',
		'state'      => 'province',
		'country'    => 'country',
		'postcode'   => 'postal',
		'phone'      => 'phone',
		'company'    => 'company',
	];

	/**
	 * The order data mapping.
	 *
	 * @var array
	 */
	public $order_data_mappings = [
		'first_name' => 'first_name',
		'last_name'  => 'last_name',
		'company'    => 'company',
		'address_1'  => 'address_1',
		'address_2'  => 'address_2',
		'city'       => 'city',
		'state'      => 'state',
		'postcode'   => 'postcode',
		'country'    => 'country',
		'email'      => 'email',
		'phone'      => 'phone',
	];

	/**
	 * The contact override mappings to convert address to override.
	 *
	 * @var array
	 */
	public $customer_override_mappings = [
		'first_name' => 'firstName',
		'last_name'  => 'lastName',
		'company'    => 'company',
		'phone'      => 'phone',
	];

	/**
	 * The email address.
	 *
	 * @var string
	 */
	private $email;

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
	 * The customer's address field.
	 *
	 * @var string
	 */
	private $address_1;

	/**
	 * The customer's second line address field.
	 *
	 * @var string
	 */
	private $address_2;

	/**
	 * The customer's city field.
	 *
	 * @var string
	 */
	private $city;

	/**
	 * The customer's state field.
	 *
	 * @var string
	 */
	private $state;

	/**
	 * The customer's country field.
	 *
	 * @var string
	 */
	private $country;

	/**
	 * The customer's postal code field.
	 *
	 * @var string
	 */
	private $postcode;

	/**
	 * The customer's phone number field.
	 *
	 * @var string
	 */
	private $phone;

	/**
	 * The customer's company field.
	 *
	 * @var string
	 */
	private $company;

	public function set_properties_from_order_data( $address_array ) {
		$mappings = $this->order_data_mappings;

		foreach ( $mappings as $local_name => $remote_name ) {
			if ( isset( $address_array[ $remote_name ] ) && ! empty( $address_array[ $remote_name ] ) ) {
				// e.g., set_order_number()
				$set_method = "set_$local_name";
				// e.g. $this->set_order_number($array['orderNumber']);
				$this->$set_method( $address_array[ $remote_name ] );
			}
		}
	}

	public function get_override_properties() {
		$mappings = $this->customer_override_mappings;

		return $this->serialize_to_array_from_mapping( $mappings );
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
		$this->first_name = $first_name ? $first_name : null;
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
		$this->last_name = $last_name ? $last_name : null;
	}

	/**
	 * Returns the address_1.
	 *
	 * @return string
	 */
	public function get_address_1() {
		return $this->address_1;
	}

	/**
	 * Sets the address_1.
	 *
	 * @param string $address_1 The address_1.
	 */
	public function set_address_1( $address_1 ) {
		$this->address_1 = $address_1;
	}

	/**
	 * Returns the address_2.
	 *
	 * @return string
	 */
	public function get_address_2() {
		return $this->address_2;
	}

	/**
	 * Sets the address_1.
	 *
	 * @param string $address_2 The address_2.
	 */
	public function set_address_2( $address_2 ) {
		$this->address_2 = $address_2;
	}

	/**
	 * Returns the city.
	 *
	 * @return string
	 */
	public function get_city() {
		return $this->city;
	}

	/**
	 * Sets the city.
	 *
	 * @param string $city The city.
	 */
	public function set_city( $city ) {
		$this->city = $city;
	}

	/**
	 * Returns the state.
	 *
	 * @return string
	 */
	public function get_state() {
		return $this->state;
	}

	/**
	 * Sets the state.
	 *
	 * @param string $state The state.
	 */
	public function set_state( $state ) {
		$this->state = $state;
	}

	/**
	 * Returns the country.
	 *
	 * @return string
	 */
	public function get_country() {
		return $this->country;
	}

	/**
	 * Sets the country.
	 *
	 * @param string $country The country.
	 */
	public function set_country( $country ) {
		$this->country = $country;
	}

	/**
	 * Returns the postcode.
	 *
	 * @return string
	 */
	public function get_postcode() {
		return $this->postcode;
	}

	/**
	 * Sets the postcode.
	 *
	 * @param string $postcode The postcode.
	 */
	public function set_postcode( $postcode ) {
		$this->postcode = $postcode;
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
	 * @param string $phone The phone.
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
}
