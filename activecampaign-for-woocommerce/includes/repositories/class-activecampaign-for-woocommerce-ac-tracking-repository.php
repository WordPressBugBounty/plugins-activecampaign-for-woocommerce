<?php

/**
 * The file for the Activecampaign_for_Woocommerce_Ac_User_Repository class
 *
 * @link       https://www.activecampaign.com/
 * @since      1.0.0
 *
 * @package    Activecampaign_For_Woocommerce
 * @subpackage Activecampaign_For_Woocommerce/includes/repositories
 */

use Activecampaign_For_Woocommerce_Api_Client as Api_Client;

use Activecampaign_For_Woocommerce_Connection as Connection;
use Activecampaign_For_Woocommerce_Ecom_Model_Interface as Ecom_Model;
use Activecampaign_For_Woocommerce_Interacts_With_Api as Interacts_With_Api;
use Activecampaign_For_Woocommerce_Repository_Interface as Repository;
use Activecampaign_For_Woocommerce_Resource_Not_Found_Exception as Resource_Not_Found;
use Activecampaign_For_Woocommerce_Resource_Unprocessable_Exception as Unprocessable;
use Activecampaign_For_Woocommerce_Logger as Logger;
/**
 * The repository class for Connections
 *
 * @since      1.0.0
 * @package    Activecampaign_For_Woocommerce
 * @subpackage Activecampaign_For_Woocommerce/includes/repositories
 * @author     acteamintegrations <team-integrations@activecampaign.com>
 */
class Activecampaign_For_Woocommerce_Ac_Tracking_Repository implements Repository {
	use Interacts_With_Api;

	const RESOURCE_NAME        = 'siteTracking';
	const RESOURCE_NAME_PLURAL = 'siteTracking';
	const ENDPOINT_NAME        = 'siteTracking';
	const ENDPOINT_NAME_PLURAL = 'siteTracking';

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
		 * @var Connection $connection_model
		 */
		$connection_model = new Connection();

		$this->get_and_set_model_properties_from_api_by_id(
			$this->client,
			$connection_model,
			(string) $id
		);

		return $connection_model;
	}

	/**
	 * Finds a resource by a filtered list response and returns an instantiated model with the resource's data.
	 *
	 * @param string $filter_name The filter name.
	 * @param string $filter_value The filter value.
	 */
	public function find_by_filter( $filter_name, $filter_value ) {
		return $this->get_result_code_from_api(
			$this->client,
			$filter_name,
			$filter_value
		);
	}

	/**
	 * Enables site tracking in AC. Must be a PUT request.
	 *
	 * @return void
	 */
	public function enable_sitetracking() {
		try {
			$enable_tracking = new Activecampaign_For_Woocommerce_Enable_Tracking();
			$enable_tracking->set_enabled( true );

			$this->put_model_properties_in_api(
				$this->client,
				$enable_tracking
			);
		} catch (Throwable $t ) {
			$logger = new Logger();
			$logger->debug(
				'There was an issue enabling site tracking in AC. It may need to be performed manually.',
				array(
					self::ENDPOINT_NAME,
					$t->getMessage(),
				)
			);
		}
	}

	public function create( $model ) {}
	public function update( $model ) {}
}
