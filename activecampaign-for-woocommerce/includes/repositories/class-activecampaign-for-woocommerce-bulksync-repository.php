<?php

/**
 * The file for the Activecampaign_for_Woocommerce_Ecom_Bulksync_Repository class
 *
 * @link       https://www.activecampaign.com/
 * @since      1.6.0
 *
 * @package    Activecampaign_For_Woocommerce
 * @subpackage Activecampaign_For_Woocommerce/includes/repositories
 */

use Activecampaign_For_Woocommerce_Api_Client as Api_Client;
use Activecampaign_For_Woocommerce_Ecom_Model_Interface as Ecom_Model;
use Activecampaign_For_Woocommerce_Interacts_With_Api as Interacts_With_Api;
use Activecampaign_For_Woocommerce_Repository_Interface as Repository;

/**
 * The repository class for Ecom bulk sync
 *
 * @since      1.6.0
 * @package    Activecampaign_For_Woocommerce
 * @subpackage Activecampaign_For_Woocommerce/includes/repositories
 * @author     acteamintegrations <team-integrations@activecampaign.com>
 */
class Activecampaign_For_Woocommerce_Bulksync_Repository implements Repository {
	use Interacts_With_Api;

	/**
	 * The singular resource name as it maps to the AC API.
	 */
	const RESOURCE_NAME        = 'ecomData';
	const RESOURCE_NAME_PLURAL = 'ecomData';
	const ENDPOINT_NAME        = 'ecomData/bulkSync';
	const ENDPOINT_NAME_PLURAL = 'ecomData/bulkSync';

	/**
	 * The API client.
	 *
	 * @var Api_Client
	 */
	private $client;

	/**
	 * Ecom_Order Repository constructor.
	 *
	 * @param Api_Client $client The api client.
	 */
	public function __construct( Api_Client $client ) {
		$this->client = $client;

		$this->client->configure_client();
	}

	/**
	 * Creates a remote resource and updates the model with the returned data.
	 *
	 * @param Ecom_Model $model The model to be created remotely.
	 *
	 * @return Ecom_Model|bool
	 */
	public function create( Ecom_Model $model ) {
		$response = $this->create_and_set_model_properties_from_api(
			$this->client,
			$model
		);

		if ( ! empty( $model->get_id() ) ) {
			return $model;
		}

		if ( isset( $response ) ) {
			return $response;
		}

		return false;
	}

	/**
	 * Sets the max retries on the client connection.
	 *
	 * @param int $tries The number of retries.
	 */
	public function set_max_retries( $tries ) {
		$this->client->set_max_retries( $tries );
	}

	/**
	 * Finds a model by its id.
	 *
	 * @param     string $id     The id to search with.
	 *
	 * @return void
	 */
	public function find_by_id( $id ) {
		// There is no find method, but it is required.
	}

	/**
	 * Finds a model by its email.
	 *
	 * @param     string $filter_name     The name of the filter to use.
	 * @param     string $filter_value     The value of the filter to use.
	 *
	 * @return void
	 */
	public function find_by_filter( $filter_name, $filter_value ) {
		// There is no find method, but it is required.
	}

	/**
	 * Updates a remote resource from a model.
	 *
	 * @param     Activecampaign_For_Woocommerce_Ecom_Model_Interface $model     The model to update.
	 *
	 * @return void
	 */
	public function update( Activecampaign_For_Woocommerce_Ecom_Model_Interface $model ) {
		// There is no update method, but it is required.
	}
}
