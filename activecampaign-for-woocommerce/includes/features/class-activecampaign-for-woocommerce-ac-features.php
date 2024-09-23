<?php

/**
 * The file for the Account Model for getting features
 *
 * @link       https://www.activecampaign.com/
 * @since      1.0.0
 * api/v1/accounts/{account_id}
 *
 * @package    Activecampaign_For_Woocommerce
 * @subpackage Activecampaign_For_Woocommerce/includes/models
 */

use Activecampaign_For_Woocommerce_Api_Serializable as Api_Serializable;
use Activecampaign_For_Woocommerce_Ecom_Model_Interface as Ecom_Model;
use Activecampaign_For_Woocommerce_Has_Id as Has_Id;
use Activecampaign_For_Woocommerce_Logger as Logger;

/**
 * The model class for the Connection Model
 *
 * @since      1.0.0
 * @package    Activecampaign_For_Woocommerce
 * @subpackage Activecampaign_For_Woocommerce/includes/models
 * @author     acteamintegrations <team-integrations@activecampaign.com>
 */
class Activecampaign_For_Woocommerce_Ac_Features implements Ecom_Model {
	use Api_Serializable;

	/**
	 * The API mappings for the API_Serializable trait.
	 * service is always woocommerce
	 * externalid is the name of the store
	 * name is the store name
	 *
	 * @var array
	 */
	public $api_mappings = [
		'entitlements' => 'entitlements', // the site entitlements
		'code'         => 'code',
		'id'           => 'id',
	];

	/**
	 * The valid profile names used in AC.
	 *
	 * @var string[]
	 */
	private $valid_entitlements = [
		'ecommerce', // this is legacy
		'ecom-core',
		'ecom-historical-sync',
		'ecom-product-catalog',
		'ecom-recurring-payments',
	];

	/**
	 * The id.
	 *
	 * @var string
	 */
	private $id;

	/**
	 * The entitlement list.
	 *
	 * @var array
	 */
	private $entitlements;

	/**
	 * Returns the features.
	 *
	 * @return array
	 */
	public function get_entitlements() {
		return $this->entitlements;
	}

	/**
	 * Sets all the features.
	 *
	 * @param     array $entitlements     The features.
	 */
	public function set_entitlements( $entitlements ) {
		$this->entitlements = $entitlements;
	}

	public function get_code() {}

	public function set_code( $entitlements ) {}

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
	 * @param     string $id     The id.
	 */
	public function set_id( $id ) {
		$this->id = $id;
	}

	/**
	 * Gets only the valid features for WC.
	 *
	 * @return array|null
	 */
	public function get_valid_entitlements() {
		$entitlement_list = [];

		if ( isset( $this->entitlements ) ) {
			foreach ( $this->entitlements as $ent ) {
				if ( isset( $ent['code'] ) && in_array( $ent['code'], $this->valid_entitlements, true ) ) {
					$entitlement_list[] = $ent['code'];
				}
			}
		}

		if ( ! empty( $entitlement_list ) && count( $entitlement_list ) > 0 ) {
			return $entitlement_list;
		} else {
			return null;
		}
	}

	/**
	 * Sets the connection from a serialized array.
	 *
	 * @param     array $array     The connection array.
	 */
	public function set_properties_from_serialized_array( array $array ) {
		$mappings = $this->api_mappings;

		foreach ( $mappings as $local_name => $remote_name ) {
			if ( isset( $array[ $remote_name ] ) ) {
				// e.g., set_order_number()
				$set_method = "set_$local_name";
				// e.g. $this->set_order_number($array['orderNumber']);
				$this->$set_method( $array[ $remote_name ] );
			}
		}

	}
}
