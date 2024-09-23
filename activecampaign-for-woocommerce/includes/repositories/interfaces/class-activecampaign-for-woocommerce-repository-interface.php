<?php

/**
 * The file for the Repository Interface class
 *
 * @link       https://www.activecampaign.com/
 * @since      1.0.0
 *
 * @package    Activecampaign_For_Woocommerce
 * @subpackage Activecampaign_For_Woocommerce/includes/repositories/interfaces
 */

/**
 * The interface class for all repositories.
 *
 * @since      1.0.0
 * @package    Activecampaign_For_Woocommerce
 * @subpackage Activecampaign_For_Woocommerce/includes/repositories/interfaces
 * @author     acteamintegrations <team-integrations@activecampaign.com>
 */
interface Activecampaign_For_Woocommerce_Repository_Interface {
	/**
	 * Finds a model by its id.
	 *
	 * @param string $id The id to search with.
	 *
	 * @return Activecampaign_For_Woocommerce_Ecom_Model_Interface
	 */
	public function find_by_id( $id );

	/**
	 * Finds a model by its email.
	 *
	 * @param string $filter_name The name of the filter to use.
	 * @param string $filter_value The value of the filter to use.
	 *
	 * @return Activecampaign_For_Woocommerce_Ecom_Model_Interface
	 */
	public function find_by_filter( $filter_name, $filter_value );

	/**
	 * Creates a remote resource from a model.
	 *
	 * @param Activecampaign_For_Woocommerce_Ecom_Model_Interface $model The model to create.
	 *
	 * @return Activecampaign_For_Woocommerce_Ecom_Model_Interface
	 */
	public function create( Activecampaign_For_Woocommerce_Ecom_Model_Interface $model );

	/**
	 * Updates a remote resource from a model.
	 *
	 * @param Activecampaign_For_Woocommerce_Ecom_Model_Interface $model The model to update.
	 *
	 * @return Activecampaign_For_Woocommerce_Ecom_Model_Interface
	 */
	public function update( Activecampaign_For_Woocommerce_Ecom_Model_Interface $model );
}
