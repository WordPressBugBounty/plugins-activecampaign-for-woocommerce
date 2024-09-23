<?php

/**
 * The file for the Connection Option Model
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
 * The model class for the Connection Option Model
 *
 * @since      1.0.0
 * @package    Activecampaign_For_Woocommerce
 * @subpackage Activecampaign_For_Woocommerce/includes/models
 * @author     acteamintegrations <team-integrations@activecampaign.com>
 */
class Activecampaign_For_Woocommerce_Connection_Option implements Ecom_Model, Has_Id {
	use Api_Serializable;

	/**
	 * The API mappings for the API_Serializable trait.
	 *
	 * @var array
	 */
	public $api_mappings = [
		'connectionid' => 'connectionid',
		'id'           => 'id',
		'option'       => 'option',
		'value'        => 'value',
	];

	/**
	 * The connectionid.
	 *
	 * @var string
	 */
	private $connectionid;

	/**
	 * The id.
	 *
	 * @var string
	 */
	private $id;

	/**
	 * The option.
	 *
	 * @var string
	 */
	private $option;

	/**
	 * The value.
	 *
	 * @var string
	 */
	private $value;

	/**
	 * Returns the connectionid.
	 *
	 * @return string
	 */
	public function get_connectionid() {
		return $this->connectionid;
	}

	/**
	 * Sets the connectionid.
	 *
	 * @param string $connectionid The connectionid.
	 */
	public function set_connectionid( $connectionid ) {
		$this->connectionid = $connectionid;
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
	 * Returns the option.
	 *
	 * @return string
	 */
	public function get_option() {
		return $this->option;
	}

	/**
	 * Sets the option.
	 *
	 * @param string $option The option.
	 */
	public function set_option( $option ) {
		$this->option = $option;
	}

	/**
	 * Returns the value.
	 *
	 * @return string
	 */
	public function get_value() {
		return $this->value;
	}

	/**
	 * Sets the value.
	 *
	 * @param string $value The value.
	 */
	public function set_value( $value ) {
		$this->value = $value;
	}
}
