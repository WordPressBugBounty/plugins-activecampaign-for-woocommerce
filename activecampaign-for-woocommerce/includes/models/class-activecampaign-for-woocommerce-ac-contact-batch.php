<?php

/**
 * The file for the AC Contact Batch Sync
 *
 * @link       https://www.activecampaign.com/
 * @since      1.6.0
 *
 * @package    Activecampaign_For_Woocommerce
 * @subpackage Activecampaign_For_Woocommerce/includes/models
 */

use Activecampaign_For_Woocommerce_Api_Serializable as Api_Serializable;
use Activecampaign_For_Woocommerce_Ecom_Model_Interface as Ecom_Model;
use Activecampaign_For_Woocommerce_Has_Id as Has_Id;
use Activecampaign_For_Woocommerce_Logger as Logger;

/**
 * The model class for the AC Contact Batch Sync
 *
 * @since      1.6.0
 * @package    Activecampaign_For_Woocommerce
 * @subpackage Activecampaign_For_Woocommerce/includes/models
 * @author     acteamintegrations <team-integrations@activecampaign.com>
 */
class Activecampaign_For_Woocommerce_AC_Contact_Batch implements Ecom_Model, Has_Id {
	use Api_Serializable {
		set_properties_from_serialized_array as set_all_but_products_as_properties_from_serialized_array;
	}

	/**
	 * The mappings for the Api_Serializable trait.
	 *
	 * @var array
	 */
	public $api_mappings = [
		'contacts' => 'contacts',
	];

	/**
	 * The id.
	 * This is not used for bulksync but is required.
	 *
	 * @var string
	 */
	private $id;

	/**
	 * The list of contacts.
	 *
	 * @var string
	 */
	private $contacts;

	/**
	 * Returns the contacts.
	 *
	 * @return mixed
	 */
	public function get_contacts() {
		return $this->contacts;
	}

	/**
	 * Sets the contacts array.
	 *
	 * @param array $contacts The contacts.
	 */
	public function set_contacts( $contacts ) {
		$this->contacts = $contacts;

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
	 * Sets the properties from a serialized array returned from the API.
	 *
	 * Calls the set properties method of the trait used in this class, but first
	 * massages the data due to how ecom order products are returned by the API.
	 *
	 * @param     array $array     The serialized array.
	 *
	 * @return Activecampaign_For_Woocommerce_Ecom_Bulksync
	 */
	public function set_properties_from_serialized_array( array $array ) {
		$logger = new Logger();
		try {
			return $this->set_all_but_products_as_properties_from_serialized_array( $array );
		} catch ( Throwable $t ) {
			$logger->warning(
				'There was an issue setting properties from serialized array on the contact batch',
				[
					'message'          => $t->getMessage(),
					'suggested_action' => 'Please refer to the message for explanation.',
					'passed_array'     => $array,
					'trace'            => $logger->clean_trace( $t->getTrace() ),
					'ac_code'          => 'ACCB_107',
				]
			);
		}
	}

	/**
	 * Converts the order to json.
	 *
	 * @return false|string
	 */
	public function to_json() {
		return wp_json_encode( $this->serialize_to_array() );
	}
}
