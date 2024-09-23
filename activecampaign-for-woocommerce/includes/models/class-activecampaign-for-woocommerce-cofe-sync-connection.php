<?php

/**
 * The file for the Connection Model
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

/**
 * The model class for the Connection Model
 *
 * @since      1.0.0
 * @package    Activecampaign_For_Woocommerce
 * @subpackage Activecampaign_For_Woocommerce/includes/models
 * @author     acteamintegrations <team-integrations@activecampaign.com>
 */
class Activecampaign_For_Woocommerce_Cofe_Sync_Connection implements Ecom_Model, Has_Id {
	use Api_Serializable;

	/**
	 * The API mappings for the API_Serializable trait.
	 *
	 * @var array
	 */
	public $api_mappings = [
		'integration_name'             => 'integrationName',
		'connection_unique_identifier' => 'connectionUniqueIdentifier',
	];

	/**
	 * The integrationName.
	 *
	 * @var string|null
	 */
	private $integration_name;

	/**
	 * The id.
	 *
	 * @var string|null
	 */
	private $id;

	/**
	 * The connectionUniqueIdentifier.
	 *
	 * @var string|null
	 */
	private $connection_unique_identifier;

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
	 * Returns the name.
	 *
	 * @return string
	 */
	public function get_integration_name() {
		return $this->integration_name;
	}

	/**
	 * Sets the name.
	 *
	 * @param string $name The name.
	 */
	public function set_integration_name( $name ) {
		$this->integration_name = $name;
	}

	/**
	 * Returns the service.
	 *
	 * @return string
	 */
	public function get_connection_unique_identifier() {
		return $this->connection_unique_identifier;
	}

	/**
	 * Sets the service.
	 *
	 * @param string $service The service.
	 */
	public function set_connection_unique_identifier( $service ) {
		$this->connection_unique_identifier = $service;
	}
}
