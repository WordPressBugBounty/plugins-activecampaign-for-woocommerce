<?php

/**
 * The file for the Activecampaign_for_Woocommerce_Connection_Repository class
 *
 * @link       https://www.activecampaign.com/
 * @since      1.0.0
 *
 * @package    Activecampaign_For_Woocommerce
 * @subpackage Activecampaign_For_Woocommerce/includes/repositories
 */

use Activecampaign_For_Woocommerce_Api_Client as Api_Client;
use Activecampaign_For_Woocommerce_Ecom_Model_Interface as Ecom_Model;
use Activecampaign_For_Woocommerce_Interacts_With_Api as Interacts_With_Api;
use Activecampaign_For_Woocommerce_Repository_Interface as Repository;
use Activecampaign_For_Woocommerce_Resource_Not_Found_Exception as Resource_Not_Found;
use Activecampaign_For_Woocommerce_Resource_Unprocessable_Exception as Unprocessable;
use Activecampaign_For_Woocommerce_Logger as Logger;
use Activecampaign_For_Woocommerce_Ac_Features as Ac_Entitlements_Model;

/**
 * The repository class for Connections
 *
 * @since      1.0.0
 * @package    Activecampaign_For_Woocommerce
 * @subpackage Activecampaign_For_Woocommerce/includes/repositories
 * @author     acteamintegrations <team-integrations@activecampaign.com>
 *
 * Example:
 * $ac_entitlements_repo = new Ac_Entitlements_Repository( new Api_Client( $api_uri, $api_key, $logger ) );
 * $account = $ac_entitlements_repo->find_all_current();
 */
class Activecampaign_For_Woocommerce_Ac_Features_Repository implements Repository {
	use Interacts_With_Api;

	/**
	 * The singular resource name as it maps to the AC API.
	 */
	const RESOURCE_NAME = 'account/entitlements';

	/**
	 * The plural resource name as it maps to the AC API.
	 */
	const RESOURCE_NAME_PLURAL = 'account/entitlements';
	const ENDPOINT_NAME        = 'account/entitlements';
	const ENDPOINT_NAME_PLURAL = 'account/entitlements';
	/**
	 * The API client.
	 *
	 * @var Api_Client
	 */
	private $client;

	/**
	 * Connection Repository constructor.
	 *
	 * @param Api_Client $client The api client.
	 */
	public function __construct( Api_Client $client ) {
		$this->client = $client;

		$this->client->configure_client();
	}

	/**
	 * Finds a resource by its ID and returns an instantiated model with the resource's data.
	 *
	 * @param string|int $id The ID to find the resource by.
	 *
	 * @return Ecom_Model
	 * @throws Resource_Not_Found Thrown when the connection could not be found.
	 */
	public function find_by_id( $id ) {
		/**
		 * A Connection Model.
		 *
		 * @var Ac_Entitlements_Model $ac_entitlements_model
		 */
		$ac_entitlements_model = new Ac_Entitlements_Model();

		$this->get_and_set_model_properties_from_api_by_id(
			$this->client,
			$ac_entitlements_model,
			(string) $id
		);

		return $ac_entitlements_model;
	}

	public function find_all_current() {
		/**
		 * A Connection Model.
		 *
		 * @var Ac_Entitlements_Model $ac_entitlements_model
		 */

		$ac_entitlements_model = new Ac_Entitlements_Model();

		$this->get_and_set_model_properties_from_api(
			$this->client,
			$ac_entitlements_model
		);

		return $ac_entitlements_model;
	}

	public function find_by_filter( $filter_name, $filter_string ) {
		return 'bork';
	}

	public function find_by_code( $code ) {
		/**
		 * A Connection Model.
		 *
		 * @var Ac_Entitlements_Model $ac_entitlements_model
		 */
		$ac_entitlements_model = new Ac_Entitlements_Model();

		$this->get_and_set_model_properties_from_api_by_id(
			$this->client,
			$ac_entitlements_model,
			(string) $code
		);

		return $ac_entitlements_model;
	}

	/**
	 * Creates a remote resource and updates the model with the returned data.
	 *
	 * @param Ecom_Model $model The model to be created remotely.
	 *
	 * @return Ecom_Model
	 * @throws Unprocessable Thrown when the connection could not be processed due to bad data.
	 */
	public function create( Ecom_Model $model ) {
		return null;

	}

	/**
	 * Updates a remote resource and updates the model with the returned data.
	 *
	 * @param Ecom_Model $model The model to be updated remotely.
	 *
	 * @return Ecom_Model|array
	 * @throws Resource_Not_Found Thrown when the connection could not be found.
	 * @throws Unprocessable Thrown when the connection could not be processed due to bad data.
	 */
	public function update( Ecom_Model $model ) {
		return null;
	}

	/**
	 * Updates a remote resource and updates the model with the returned data.
	 *
	 * @param Ecom_Model|array $id The id to be deleted.
	 *
	 * @return Ecom_Model
	 * @throws Resource_Not_Found Thrown when the connection could not be found.
	 * @throws Unprocessable Thrown when the connection could not be processed due to bad data.
	 */
	public function delete( $id ) {
		return null;
	}
}
