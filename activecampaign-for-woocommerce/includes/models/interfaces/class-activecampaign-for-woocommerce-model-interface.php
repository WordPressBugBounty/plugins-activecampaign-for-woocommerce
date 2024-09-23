<?php

/**
 * The file for the Ecom Model Interface.
 *
 * @link       https://www.activecampaign.com/
 * @since      1.0.0
 *
 * @package    Activecampaign_For_Woocommerce
 * @subpackage Activecampaign_For_Woocommerce/includes/models
 */

/**
 * The model class for the Ecom Model Interface.
 *
 * @since      1.0.0
 * @package    Activecampaign_For_Woocommerce
 * @subpackage Activecampaign_For_Woocommerce/includes/models/interfaces
 * @author     acteamintegrations <team-integrations@activecampaign.com>
 */
interface Activecampaign_For_Woocommerce_Ecom_Model_Interface {
	/**
	 * Serializes the model to an array.
	 *
	 * @return array
	 */
	public function serialize_to_array();

	/**
	 * Sets properties on the model from a serialized array.
	 *
	 * @param array $array The array of serialized data to set properties from.
	 */
	public function set_properties_from_serialized_array( array $array );

	/**
	 * Gets the ID for the model.
	 *
	 * @return mixed The id for the model.
	 */
	public function get_id();

}
