<?php

/**
 * The file for the Ecom Bulk Sync Model
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
 * The model class for the Ecom Bulk Sync
 *
 * @since      1.6.0
 * @package    Activecampaign_For_Woocommerce
 * @subpackage Activecampaign_For_Woocommerce/includes/models
 * @author     acteamintegrations <team-integrations@activecampaign.com>
 */
class Activecampaign_For_Woocommerce_Ecom_Bulksync implements Ecom_Model, Has_Id {
	use Api_Serializable {
		set_properties_from_serialized_array as set_all_but_products_as_properties_from_serialized_array;
	}

	/**
	 * The mappings for the Api_Serializable trait.
	 *
	 * @var array
	 */
	public $api_mappings = [
		'service'    => 'service',
		'externalid' => 'externalid',
		'customers'  => 'customers',
	];

	/**
	 * The id.
	 * This is not used for bulksync but is required.
	 *
	 * @var string
	 */
	private $id;

	/**
	 * This is the site URL registered in hosted.
	 *
	 * @var string
	 */
	private $externalid;

	/**
	 * The service. In our case this should always be "woocommerce".
	 *
	 * @var string
	 */
	private $service;

	/**
	 * The list of customers.
	 *
	 * @var string
	 */
	private $customers;

	/**
	 * Returns the customers.
	 *
	 * @return mixed
	 */
	public function get_customers() {
		return $this->customers;
	}

	/**
	 * Sets the customers array.
	 *
	 * @param array $customers The customers.
	 */
	public function set_customers( $customers ) {
		$this->customers = $customers;

	}

	/**
	 * Returns the service.
	 *
	 * @return string
	 */
	public function get_service() {
		return $this->service;
	}

	/**
	 * Sets the service.
	 *
	 * @param string $service The service.
	 */
	public function set_service( $service ) {
		$this->service = $service;
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
	 * Returns the id.
	 *
	 * @return string
	 */
	public function get_externalid() {
		return $this->externalid;
	}

	/**
	 * Sets the externalid.
	 *
	 * @param string $externalid The externalid.
	 */
	public function set_externalid( $externalid ) {
		$this->externalid = $externalid;
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
				'There was an issue setting properties from serialized array on the ecom bulksync',
				[
					'message'          => $t->getMessage(),
					'suggested_action' => 'Please refer to the message for explanation.',
					'passed_array'     => $array,
					'trace'            => $logger->clean_trace( $t->getTrace() ),
					'ac_code'          => 'ECBS_159',
				]
			);
		}
	}

	/**
	 * Converts the order to json.
	 *
	 * @return false|string
	 */
	public function order_to_json() {
		return wp_json_encode( $this->serialize_to_array() );
	}
}
