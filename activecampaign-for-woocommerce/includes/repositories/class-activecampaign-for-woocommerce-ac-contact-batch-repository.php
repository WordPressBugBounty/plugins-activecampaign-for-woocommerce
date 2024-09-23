<?php

/**
 * The file for the Activecampaign_for_Woocommerce_AC_Contact_Repository class
 *
 * @link       https://www.activecampaign.com/
 * @since      1.0.0
 *
 * @package    Activecampaign_For_Woocommerce
 * @subpackage Activecampaign_For_Woocommerce/includes/repositories
 */

use Activecampaign_For_Woocommerce_Api_Client as Api_Client;
use Activecampaign_For_Woocommerce_AC_Contact as AC_Contact;
use Activecampaign_For_Woocommerce_Ecom_Model_Interface as Ecom_Model;
use Activecampaign_For_Woocommerce_Interacts_With_Api as Interacts_With_Api;
use Activecampaign_For_Woocommerce_Repository_Interface as Repository;
use Activecampaign_For_Woocommerce_Logger as Logger;

/**
 * The repository class for Contacts
 *
 * @since      1.0.0
 * @package    Activecampaign_For_Woocommerce
 * @subpackage Activecampaign_For_Woocommerce/includes/repositories
 * @author     acteamintegrations <team-integrations@activecampaign.com>
 */
class Activecampaign_For_Woocommerce_AC_Contact_Batch_Repository implements Repository {
	use Interacts_With_Api;

	const RESOURCE_NAME        = 'import';
	const RESOURCE_NAME_PLURAL = 'import';
	const ENDPOINT_NAME        = 'import/bulk_import';
	const ENDPOINT_NAME_PLURAL = 'import/bulk_import';

	/**
	 * The API client.
	 *
	 * @var Api_Client
	 */
	private $client;

	/**
	 * Activecampaign_For_Woocommerce_AC_Contact_Repository constructor.
	 *
	 * @param     Api_Client $api_client     The api client.
	 */
	public function __construct( Api_Client $api_client ) {
		$this->client = $api_client;

		$this->client->configure_client();
	}

	/**
	 * Finds a resource by its ID and returns an instantiated model with the resource's data.
	 *
	 * @param     string|int $id     The ID to find the resource by.
	 *
	 * @return Ecom_Model
	 */
	public function find_by_id( $id ) {
		/**
		 * An AC Contact model.
		 *
		 * @var AC_Contact $ac_contact_model
		 */
		$ac_contact_model = new AC_Contact();

		$this->get_and_set_model_properties_from_api_by_id(
			$this->client,
			$ac_contact_model,
			(string) $id
		);

		return $ac_contact_model;
	}

	/**
	 * Finds a resource by a filtered list response and returns an instantiated model with the resource's data.
	 *
	 * @param     string $filter_name     The filter name.
	 * @param     string $filter_value     The filter value.
	 *
	 * @return Ecom_Model
	 */
	public function find_by_filter( $filter_name, $filter_value ) {
		/**
		 * An AC Contact Model.
		 *
		 * @var AC_Contact $ac_contact_model.
		 */
		$ac_contact_model = new AC_Contact();

		$this->get_and_set_model_properties_from_api_by_filter(
			$this->client,
			$ac_contact_model,
			$filter_name,
			$filter_value
		);

		return $ac_contact_model;
	}

	/**
	 * Finds a resource by its email and returns an instantiated model with the resource's data.
	 *
	 * @param     string $email     The email to find the resource by.
	 *
	 * @return Ecom_Model|null
	 */
	public function find_by_email( $email ) {
		if ( ! $email ) {
			return null;
		}

		$logger           = new Logger();
		$ac_contact_model = new AC_Contact();

		$result_array = $this->get_result_set_from_api_by_filter(
			$this->client,
			'email',
			$email
		);

		$result = $result_array;

		if ( empty( $result ) ) {
			$logger = new Logger();
			$logger->debug(
				'ac_contact_repository: Resource not found.',
				[
					'endpoint' => $this->client->get_endpoint(),
					'email'    => $email,
				]
			);
		}

		return $ac_contact_model->set_properties_from_serialized_array( array_values( $result )[0] );
	}

	/**
	 * Creates a remote resource and updates the model with the returned data.
	 *
	 * @param     Ecom_Model $model     The model to be created remotely.
	 *
	 * @return Ecom_Model|array
	 */
	public function create( Ecom_Model $model ) {
		$logger = new Logger();
		$result = $this->create_and_set_model_properties_from_api(
			$this->client,
			$model
		);

		if ( isset( $result ) ) {
			return $result;
		}

	}

	/**
	 * Updates a remote resource and updates the model with the returned data.
	 *
	 * @param     Ecom_Model $model     The model to be updated remotely.
	 *
	 * @return Ecom_Model|array
	 */
	public function update( Ecom_Model $model ) {
		$result = $this->update_and_set_model_properties_from_api(
			$this->client,
			$model
		);

		if ( is_array( $result ) & isset( $result['type'] ) ) {
			return $result;
		}

		return $model;
	}
}
