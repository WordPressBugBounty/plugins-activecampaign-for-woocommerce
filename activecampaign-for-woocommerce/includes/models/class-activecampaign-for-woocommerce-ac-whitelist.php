<?php

/**
 * The file for the Whitelisting Model
 *
 * @link       https://www.activecampaign.com/
 * @since      1.0.0
 *
 * @package    Activecampaign_For_Woocommerce
 * @subpackage Activecampaign_For_Woocommerce/includes/models
 */

use Activecampaign_For_Woocommerce_Api_Serializable as Api_Serializable;
use Activecampaign_For_Woocommerce_Ecom_Model_Interface as Ecom_Model;

/**
 * The model class for the Ecom Contact
 *
 * @since      1.0.0
 * @package    Activecampaign_For_Woocommerce
 * @subpackage Activecampaign_For_Woocommerce/includes/models
 * @author     acteamintegrations <team-integrations@activecampaign.com>
 */
class Activecampaign_For_Woocommerce_AC_Whitelist implements Ecom_Model {
	use Api_Serializable;
	use Activecampaign_For_Woocommerce_Data_Validation;

	/**
	 * The API mappings for the API_Serializable trait.
	 *
	 * @var array
	 */
	public $api_mappings = array(
		'name' => 'name',
	);

	/**
	 * The name string.
	 *
	 * @var string
	 */
	private $name;

	public function __construct() {
		// Start completely empty
		$this->name = '';
	}

	/**
	 * Returns the name.
	 *
	 * @return string
	 */
	public function get_name() {
		return $this->name;
	}

	/**
	 * Sets the name.
	 *
	 * @param     string $name     The name.
	 */
	public function set_name( $name ) {
		$this->name = $name;
	}

	/**
	 * unused
	 *
	 * @return void
	 */
	public function get_id() {
		// There is no get ID but this is required to be here.
	}
}
