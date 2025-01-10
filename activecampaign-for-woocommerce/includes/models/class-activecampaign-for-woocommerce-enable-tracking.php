<?php

/**
 * The file for the Tracking enablement Model.
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
class Activecampaign_For_Woocommerce_Enable_Tracking implements Ecom_Model {
	use Api_Serializable;
	use Activecampaign_For_Woocommerce_Data_Validation;

	/**
	 * The API mappings for the API_Serializable trait.
	 *
	 * @var array
	 */
	public $api_mappings = array(
		'enabled' => 'enabled',
	);

	/**
	 * The enabled boolean.
	 *
	 * @var bool
	 */
	private $enabled;

	public function __construct() {
		// Start completely empty
		$this->enabled = true;
	}

	/**
	 * Returns the name.
	 *
	 * @return string
	 */
	public function get_enabled() {
		return $this->enabled;
	}

	/**
	 * Sets the enabled tag.
	 *
	 * @param     bool $enabled     The enabled tag.
	 */
	public function set_enabled( $enabled ) {
		$this->enabled = $enabled;
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
