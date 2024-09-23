<?php
/**
 * The file for the trait Activecampaign_For_Woocommerce_Api_Serializable.
 *
 * @link       https://www.activecampaign.com/
 * @since      1.0.0
 *
 * @package    Activecampaign_For_Woocommerce
 * @subpackage Activecampaign_For_Woocommerce/includes/traits
 */

/**
 * Trait Activecampaign_For_Woocommerce_Api_Serializable
 *
 * @link       https://www.activecampaign.com/
 * @since      1.0.0
 *
 * @package    Activecampaign_For_Woocommerce
 * @subpackage Activecampaign_For_Woocommerce/includes/traits
 */
trait Activecampaign_For_Woocommerce_Api_Serializable {
	/**
	 * Returns an array of all values mapped to their AC-specific property names.
	 *
	 * For each mapping set on the $api_mappings static property, the keys are the names of the properties
	 * as we store them locally, and the values are how they're sent/received from the API. We dynamically call
	 * the appropriate getter methods on the instantiated class and assign them to keys on a new array that have
	 * the appropriate name for the API.
	 *
	 * @return array
	 */
	public function serialize_to_array() {
		$mappings = $this->api_mappings;

		return $this->serialize_to_array_from_mapping( $mappings );
	}

	public function serialize_to_array_from_mapping( $mappings ) {

		$array = [];

		// e.g., "order_number" => "orderNumber"
		foreach ( $mappings as $local_name => $remote_name ) {
			// e.g., get_order_number()
			$get_method = "get_$local_name";

			// e.g. ["orderNumber" => $this->get_order_number()];
			if ( $this->$get_method() !== null ) {
				$array[ $remote_name ] = $this->$get_method();
			}
		}

		return $array;
	}

	/**
	 * For each mapping set on the $api_mappings static property, the keys are the names of the properties
	 * as we store them locally, and the values are how they're sent/received from the API. We dynamically call
	 * the appropriate setter methods on the instantiated class and pass into them the values returned from the API.
	 *
	 * @param array $array The array of data that will be mapped to setter methods on the instantiated class.
	 *
	 * @return self
	 */
	public function set_properties_from_serialized_array( array $array ) {
		$mappings = $this->api_mappings;

		// e.g., "order_number" => "orderNumber"
		foreach ( $mappings as $local_name => $remote_name ) {
			if ( isset( $array[ $remote_name ] ) ) {
				// e.g., set_order_number()
				$set_method = "set_$local_name";
				// e.g. $this->set_order_number($array['orderNumber']);
				$this->$set_method( $array[ $remote_name ] );
			}
		}

		return $this;
	}

	/**
	 * Converts a serialized array to json
	 *
	 * @return false|string
	 */
	public function customer_to_json() {
		return wp_json_encode( $this->serialize_to_array() );
	}
}
