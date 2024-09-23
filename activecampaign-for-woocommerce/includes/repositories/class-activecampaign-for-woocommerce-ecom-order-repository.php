<?php

/**
 * The file for the Activecampaign_for_Woocommerce_Ecom_Order_Repository class
 *
 * @link       https://www.activecampaign.com/
 * @since      1.0.0
 *
 * @package    Activecampaign_For_Woocommerce
 * @subpackage Activecampaign_For_Woocommerce/includes/repositories
 */

use Activecampaign_For_Woocommerce_Api_Client as Api_Client;

use Activecampaign_For_Woocommerce_Ecom_Order as Ecom_Order;
use Activecampaign_For_Woocommerce_Ecom_Model_Interface as Ecom_Model;
use Activecampaign_For_Woocommerce_Interacts_With_Api as Interacts_With_Api;
use Activecampaign_For_Woocommerce_Repository_Interface as Repository;

/**
 * The repository class for Ecom Orders
 *
 * @since      1.0.0
 * @package    Activecampaign_For_Woocommerce
 * @subpackage Activecampaign_For_Woocommerce/includes/repositories
 * @author     acteamintegrations <team-integrations@activecampaign.com>
 */
class Activecampaign_For_Woocommerce_Ecom_Order_Repository implements Repository {
	use Interacts_With_Api;

	/**
	 * The singular resource name as it maps to the AC API.
	 */
	const RESOURCE_NAME = 'ecomOrder';

	/**
	 * The plural resource name as it maps to the AC API.
	 */
	const RESOURCE_NAME_PLURAL = 'ecomOrders';
	const ENDPOINT_NAME        = 'ecomOrder';
	const ENDPOINT_NAME_PLURAL = 'ecomOrders';
	/**
	 * The API client.
	 *
	 * @var Api_Client
	 */
	private $client;

	/**
	 * Ecom_Order Repository constructor.
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
	 */
	public function find_by_id( $id ) {
		/**
		 * An Ecom Order Model.
		 *
		 * @var Ecom_Order $ecom_order_model
		 */
		$ecom_order_model = new Ecom_Order();

		$this->get_and_set_model_properties_from_api_by_id(
			$this->client,
			$ecom_order_model,
			(string) $id,
			[ $this, 'find_and_insert_ecom_order_products_from_response' ]
		);

		return $ecom_order_model;
	}

	/**
	 * Finds a resource by a filtered list response and returns an instantiated model with the resource's data.
	 *
	 * @param string $filter_name  The filter name.
	 * @param string $filter_value The filter value.
	 *
	 * @return Ecom_Model
	 */
	public function find_by_filter( $filter_name, $filter_value ) {
		/**
		 * An Ecom Order Model.
		 *
		 * @var Ecom_Order $ecom_order_model
		 */
		$ecom_order_model = new Ecom_Order();

		$this->get_and_set_model_properties_from_api_by_filter(
			$this->client,
			$ecom_order_model,
			$filter_name,
			$filter_value,
			[ $this, 'find_and_insert_ecom_order_products_from_response' ]
		);

		return $ecom_order_model;
	}

	/**
	 * Finds a resource by its email and returns an instantiated model with the resource's data.
	 *
	 * @param string|int $email The email to find the resource by.
	 *
	 * @return Ecom_Model
	 */
	public function find_by_email( $email ) {
		return $this->find_by_filter( 'email', $email );
	}

	/**
	 * Finds a resource by its external id and returns an instantiated model with the resource's data.
	 *
	 * @param string|int $externalid The external id to find the resource by.
	 *
	 * @return Ecom_Model
	 */
	public function find_by_externalid( $externalid ) {
		return $this->find_by_filter( 'externalid', $externalid );
	}

	/**
	 * Finds a resource by its external checkout id and returns an instantiated model with the resource's data.
	 *
	 * @param string|int $externalcheckoutid The external checkout id to find the resource by.
	 *
	 * @return Ecom_Model
	 */
	public function find_by_externalcheckoutid( $externalcheckoutid ) {
		return $this->find_by_filter( 'externalcheckoutid', $externalcheckoutid );
	}

	/**
	 * Creates a remote resource and updates the model with the returned data.
	 *
	 * @param Ecom_Model $model The model to be created remotely.
	 *
	 * @return Ecom_Model|array
	 */
	public function create( Ecom_Model $model ) {
		$result = $this->create_and_set_model_properties_from_api(
			$this->client,
			$model,
			[ $this, 'find_and_insert_ecom_order_products_from_response' ]
		);

		if ( is_array( $result ) && isset( $result['type'] ) && ( 'error' === $result['type'] || 'timeout' === $result['type'] ) ) {
			return $result;
		}

		return $model;
	}

	/**
	 * Updates a remote resource and updates the model with the returned data.
	 *
	 * @param Ecom_Model $model The model to be updated remotely.
	 *
	 * @return Ecom_Model
	 */
	public function update( Ecom_Model $model ) {
		$this->update_and_set_model_properties_from_api(
			$this->client,
			$model,
			[ $this, 'find_and_insert_ecom_order_products_from_response' ]
		);

		return $model;
	}

	/**
	 * Updates a remote resource and updates the model with the returned data.
	 *
	 * @param string|int $id The AC record id.
	 *
	 * @return string|int $id
	 */
	public function delete( $id ) {
		$this->delete_model_from_api_by_id(
			$this->client,
			$id,
			[ $this, 'return_result_from_response' ]
		);

		return $id;
	}

	/**
	 * This may not be necessary.
	 *
	 * @param mixed $response
	 *
	 * @return mixed
	 */
	public function return_result_from_response( $response ) {
		return $response;
	}

	/**
	 * Massages the returned resource array from the API and adds ecom products to their parent orders.
	 *
	 * Serves as a middleware on the response. Our API returns EcomOrders and EcomOrderProducts separate from
	 * each other, but wants them sent in together. This function massages the returned resources into a more
	 * expected response.
	 *
	 * @param array $response The response array.
	 *
	 * @return array
	 */
	public function find_and_insert_ecom_order_products_from_response( $response ) {
		$is_order_list = isset( $response['ecomOrders'] );

		// create our array of orders
		$orders = $is_order_list ? $response['ecomOrders'] : [ $response['ecomOrder'] ];

		// create our array of products
		$products = isset( $response['ecomOrderProducts'] ) ? $response['ecomOrderProducts'] : [];
		uasort( $products, [ $this, 'product_array_sort' ] );

		// map through all orders, setting them in-place
		$orders = array_map(
			function ( $order ) use ( $products ) {
				if ( isset( $order['orderProducts'] ) && is_array( $order['orderProducts'] ) ) {
					// map through all order product ids, replacing them with their associated product object
					$order['orderProducts'] = array_map(
						function ( $product_id ) use ( $products ) {
							return $this->binary_product_search( $product_id, $products );
						},
						$order['orderProducts']
					);
				}
				return $order;
			},
			$orders
		);

		if ( $is_order_list ) {
			$response['ecomOrders'] = $orders;
		} else {
			$response['ecomOrder'] = $orders[0];
		}

		return $response;
	}

	/**
	 * Performs a binary search on the product array passed in to reduce the size of the search.
	 *
	 * Some orders may include a very large data set of order products. By performing a binary search
	 * we can reduce the total search time as the complexity grows logarithmically.
	 *
	 * @param string|int $target_id The id of the product we're looking for.
	 * @param array      $products  The array of products.
	 *
	 * @return array|null
	 */
	private function binary_product_search( $target_id, array $products ) {
		if ( count( $products ) === 0 ) {
			return null;
		}

		if ( count( $products ) === 1 ) {
			return $products[0];
		}

		$low  = 0;
		$high = count( $products ) - 1;

		while ( $low <= $high ) {
			$middle = (int) floor( ( $low + $high ) / 2 );

			$middle_product = $products[ $middle ];

			if ( $middle_product['id'] === $target_id ) {
				return $middle_product;
			}

			if ( $target_id < $middle_product['id'] ) {
				$high = $middle - 1;
			} else {
				$low = $middle + 1;
			}
		}

		return null;
	}

	/**
	 * Returns a simple sort value for the PHP function uasort.
	 *
	 * @param array $a The first product for comparison.
	 * @param array $b The second product for comparison.
	 *
	 * @return int
	 */
	private function product_array_sort( array $a, array $b ) {
		if ( $a['id'] === $b['id'] ) {
			return 0;
		}

		return ( $a['id'] < $b['id'] ) ? - 1 : 1;
	}
}
