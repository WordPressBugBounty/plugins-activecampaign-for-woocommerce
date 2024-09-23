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
use Activecampaign_For_Woocommerce_Logger as Logger;

/**
 * The model class for the Connection Model
 *
 * @since      1.0.0
 * @package    Activecampaign_For_Woocommerce
 * @subpackage Activecampaign_For_Woocommerce/includes/models
 * @author     acteamintegrations <team-integrations@activecampaign.com>
 */
class Activecampaign_For_Woocommerce_Connection implements Ecom_Model, Has_Id {
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
		'externalid'  => 'externalid', // the site URL
		'id'          => 'id', // set by Hosted
		'name'        => 'name', // the site title
		'service'     => 'service', // always woocommerce
		'logo_url'    => 'logoUrl', // the site logo
		'link_url'    => 'linkUrl', // the shop URL
		'is_internal' => 'isInternal',
	];

	/**
	 * The externalid.
	 *
	 * @var string
	 */
	private $externalid;

	/**
	 * The id.
	 *
	 * @var string
	 */
	private $id;

	/**
	 * The name.
	 *
	 * @var string
	 */
	private $name;

	/**
	 * The service.
	 *
	 * @var string
	 */
	private $service;

	/**
	 * The logo URL.
	 *
	 * @var string
	 */
	private $logo_url;

	/**
	 * The link URL.
	 *
	 * @var string
	 */
	private $link_url;

	/**
	 * The marker for this connection is internal.
	 *
	 * @var string
	 */
	private $is_internal;

	/**
	 * Returns the externalid.
	 *
	 * @return string
	 */
	public function get_externalid() {
		return $this->externalid;
	}

	/**
	 * Sets the externalid.
	 *
	 * @param string $externalid The externalid.
	 */
	public function set_externalid( $externalid ) {
		$this->externalid = $externalid;
	}

	/**
	 * Gets the is internal marker.
	 *
	 * @return bool|int
	 */
	public function get_is_internal() {
		return $this->is_internal;
	}

	/**
	 * Sets the internal marker.
	 *
	 * @param bool|int $is_internal The maker.
	 */
	public function set_is_internal( $is_internal ) {
		if ( isset( $is_internal ) && ! empty( $is_internal ) ) {
			$this->is_internal = $is_internal;
		} else {
			$this->is_internal = 0;
		}
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
	 * @param string $name The name.
	 */
	public function set_name( $name ) {
		$this->name = $name;
	}

	/**
	 * Returns the service.
	 *
	 * @return string
	 */
	public function get_service() {
		return $this->service;
	}

	/**
	 * Sets the service.
	 *
	 * @param string $service The service.
	 */
	public function set_service( $service ) {
		$this->service = $service;
	}

	/**
	 * Gets the logo URL.
	 *
	 * @return string
	 */
	public function get_logo_url() {
		return $this->logo_url;
	}

	/**
	 * Sets the logo URL.
	 *
	 * @param string $logo_url The logo URL.
	 */
	public function set_logo_url( $logo_url ) {
		$this->logo_url = $logo_url;
	}

	/**
	 * Gets the link URL.
	 *
	 * @return string
	 */
	public function get_link_url() {
		return $this->link_url;
	}

	/**
	 * Sets the link URL.
	 *
	 * @param string $link_url The URL.
	 */
	public function set_link_url( $link_url ) {
		$this->link_url = $link_url;
	}

	/**
	 * Sets the connection from a serialized array.
	 *
	 * @param array $array The connection array.
	 */
	public function set_connection_from_serialized_array( array $array ) {
		$connection = new self();
		$mappings   = $this->api_mappings;

		foreach ( $mappings as $local_name => $remote_name ) {
			if ( isset( $array[ $remote_name ] ) ) {
				// e.g., set_order_number()
				$set_method = "set_$local_name";
				// e.g. $this->set_order_number($array['orderNumber']);
				$connection->$set_method( $array[ $remote_name ] );
			}
		}

		return $connection;
	}
}
