<?php
/**
 * The file for the trait Activecampaign_For_Woocommerce_Interacts_With_Api.
 *
 * @link       https://www.activecampaign.com/
 * @since      1.0.0
 *
 * @package    Activecampaign_For_Woocommerce
 * @subpackage Activecampaign_For_Woocommerce/includes/traits
 */

use AcVendor\GuzzleHttp\Exception\GuzzleException;
use AcVendor\GuzzleHttp\Exception\BadResponseException;
use AcVendor\Psr\Http\Message\StreamInterface;

use Activecampaign_For_Woocommerce_Logger as Logger;

/**
 * Trait Activecampaign_For_Woocommerce_Interacts_With_Api
 *
 * @link       https://www.activecampaign.com/
 * @since      1.0.0
 *
 * @package    Activecampaign_For_Woocommerce
 * @subpackage Activecampaign_For_Woocommerce/includes/traits
 */
trait Activecampaign_For_Woocommerce_Interacts_With_Api {
	use Activecampaign_For_Woocommerce_Data_Validation;

	/**
	 * Retrieves a resource via the API and uses the value to update a model.
	 *
	 * @param Activecampaign_For_Woocommerce_Api_Client           $client The Client class.
	 * @param Activecampaign_For_Woocommerce_Ecom_Model_Interface $model The model class.
	 * @param string                                              $id The id to find.
	 * @param callable                                            $response_massager A callable to alter the response body.
	 */
	private function get_and_set_model_properties_from_api_by_id(
		Activecampaign_For_Woocommerce_Api_Client $client,
		Activecampaign_For_Woocommerce_Ecom_Model_Interface $model,
		$id,
		callable $response_massager = null
	) {
		$logger = new Logger();

		try {
			$result = $client
				->get( self::ENDPOINT_NAME_PLURAL, (string) $id )
				->execute();
		} catch ( Throwable $t ) {
			$logger->debug_excess(
				'The resource was not found in Hosted. This may not be an error. Check response_message to confirm.',
				[
					'resource_name'    => self::RESOURCE_NAME,
					'endpoint_name'    => self::ENDPOINT_NAME,
					'found_by'         => 'id',
					'value'            => $id,
					'response_message' => $t->getMessage(),
					'status_code'      => $t->getResponse() ? $t->getResponse()->getStatusCode() : '',
				]
			);
		}

		if ( isset( $result ) ) {
			try {

				if ( ! is_object( $result ) || ! self::validate_object( $result, 'getBody' ) ) {
					$logger->debug(
						'Result of get and set model from get_and_set_model_properties_from_api_by_id',
						[
							'result'        => $result,
							'resource_name' => self::RESOURCE_NAME,
							'endpoint_name' => self::ENDPOINT_NAME,
						]
					);
				} else {
					$resource_array = json_decode( $result->getBody(), true );

					if ( $response_massager ) {
						$resource_array = $response_massager( $resource_array );
					}

					if ( isset( $resource_array ) && is_array( $resource_array ) ) {
						$resource = $resource_array[ self::RESOURCE_NAME ];
						$model->set_properties_from_serialized_array( $resource );
					} else {
						$logger->error(
							'Resource returned is invalid or empty. ActiveCampaign may be unreachable.',
							[
								'result'           => $result,
								'suggested_action' => 'Please wait and if this message persists contact ActiveCampaign for support.',
								'ac_code'          => 'IWAPI_88',
								'resource_name'    => self::RESOURCE_NAME,
								'result_body'      => self::validate_object( $result, 'getBody' ) ? $result->getBody() : null,
							]
						);
					}
				}
			} catch ( Throwable $t ) {
				$logger->warning(
					'Activecampaign_For_Woocommerce_Interacts_With_Api: Resource thrown error.',
					[
						'result'  => $result,
						'ac_code' => 'IWAPI_100',
					]
				);
			}
		} else {
			$logger->error(
				'There was an issue contacting ActiveCampaign, request returned an empty or null result. ActiveCampaign may not be reachable by this Host.',
				[
					'suggested_action' => 'Please verify with your hosting provider that the ActiveCampaign API is reachable. Please contact ActiveCampaign support if necessary.',
					'ac_code'          => 'IWAPI_109',
					'client'           => $client,
					'function'         => 'get_and_set_model_properties_from_api_by_id',
					'resource_name'    => self::RESOURCE_NAME,
					'endpoint_name'    => self::ENDPOINT_NAME,
					'found_by'         => 'id',
					'value'            => $id,
				]
			);
		}
	}

	/**
	 * Retrieves a resource via the API by an email address and uses the value to update a model.
	 *
	 * @param Activecampaign_For_Woocommerce_Api_Client           $client The Api client class.
	 * @param Activecampaign_For_Woocommerce_Ecom_Model_Interface $model The model class.
	 * @param string                                              $email The email address.
	 * @param callable                                            $response_massager A callable to alter the response body.
	 */
	private function get_and_set_model_properties_from_api_by_email(
		Activecampaign_For_Woocommerce_Api_Client $client,
		Activecampaign_For_Woocommerce_Ecom_Model_Interface $model,
		$email,
		callable $response_massager = null
	) {
		$this->get_and_set_model_properties_from_api_by_filter(
			$client,
			$model,
			'email',
			$email,
			$response_massager
		);
	}

	/**
	 * Retrieves a resource via the API by an external id and uses the value to update a model.
	 *
	 * @param Activecampaign_For_Woocommerce_Api_Client           $client The Api client class.
	 * @param Activecampaign_For_Woocommerce_Ecom_Model_Interface $model The model class.
	 * @param string                                              $externalid The externalid.
	 * @param callable                                            $response_massager A callable to alter the response body.
	 */
	private function get_and_set_model_properties_from_api_by_externalid(
		Activecampaign_For_Woocommerce_Api_Client $client,
		Activecampaign_For_Woocommerce_Ecom_Model_Interface $model,
		$externalid,
		callable $response_massager = null
	) {
		$this->get_and_set_model_properties_from_api_by_filter(
			$client,
			$model,
			'externalid',
			$externalid,
			$response_massager
		);
	}

	/**
	 * Retrieves a resource via the API with a filter and uses the value to update a model.
	 *
	 * @param Activecampaign_For_Woocommerce_Api_Client           $client The Api client class.
	 * @param Activecampaign_For_Woocommerce_Ecom_Model_Interface $model The model class.
	 * @param string                                              $filter_name The name of the filter.
	 * @param string                                              $filter_value The value of the filter.
	 * @param callable                                            $response_massager A callable to alter the response body.
	 */
	private function get_and_set_model_properties_from_api_by_filter(
		Activecampaign_For_Woocommerce_Api_Client $client,
		Activecampaign_For_Woocommerce_Ecom_Model_Interface $model,
		$filter_name,
		$filter_value,
		callable $response_massager = null
	) {
		$resource = $this->get_result_set_from_api_by_filter( $client, $filter_name, $filter_value, $response_massager );
		$logger   = new Logger();

		if ( is_array( $resource ) && isset( $resource[0] ) ) {
			try {
				$model->set_properties_from_serialized_array( $resource[0] );
			} catch ( Throwable $t ) {
				$logger->warning(
					'Activecampaign_For_Woocommerce_Interacts_With_Api: There was an issue parsing the resource from serialized array.',
					[
						'message'      => $t->getMessage(),
						'endpoint'     => $client->get_endpoint(),
						'client_body'  => self::validate_object( $client, 'getBody' ) ? $client->get_body() : null,
						'filter_name'  => $filter_name,
						'filter_value' => $filter_value,
						'resource'     => $resource,
					]
				);
			}
			return;
		}

		try {
			$logger->debug(
				'Activecampaign_For_Woocommerce_Interacts_With_Api: Resource not found in result.',
				[
					'endpoint'     => $client->get_endpoint(),
					'resource'     => $resource,
					'filter_name'  => $filter_name,
					'filter_value' => $filter_value,
				]
			);

			if ( isset( $resource ) ) {
				$model->set_properties_from_serialized_array( $resource );
			}
		} catch ( Throwable $t ) {
			$logger->warning(
				'Activecampaign_For_Woocommerce_Interacts_With_Api: Resource threw an error.',
				[
					'message'      => $t->getMessage(),
					'endpoint'     => $client->get_endpoint(),
					'client_body'  => self::validate_object( $client, 'getBody' ) ? $client->get_body() : null,
					'filter_name'  => $filter_name,
					'filter_value' => $filter_value,
					'resource'     => $resource,
				]
			);
		}
	}

	/**
	 * Retrieves a resource via the API with a filter. May return multiple rows.
	 *
	 * @param Activecampaign_For_Woocommerce_Api_Client $client The Api client class.
	 * @param string                                    $filter_name The name of the filter.
	 * @param string                                    $filter_value The value of the filter.
	 * @param callable|null                             $response_massager A callable to alter the response body.
	 *
	 * @return array
	 */
	private function get_result_set_from_api_by_filter(
		Activecampaign_For_Woocommerce_Api_Client $client,
		$filter_name,
		$filter_value,
		callable $response_massager = null
	) {
		$client->set_filters( [] );
		$client->with_body( '' );
		$logger = new Logger();
		$result = $client
			->get( self::ENDPOINT_NAME_PLURAL )
			->with_filter( $filter_name, $filter_value )
			->execute();

		if ( $result ) {
			try {
				if ( ! is_object( $result ) || ! self::validate_object( $result, 'getBody' ) ) {
					$logger->debug(
						'Result from API may not have a body. Could be an error.',
						[
							'result'        => $result,
							'filter_name'   => $filter_name,
							'filter_value'  => $filter_value,
							'client_body'   => self::validate_object( $client, 'getBody' ) ? $client->get_body() : null,
							'resource_name' => self::RESOURCE_NAME,
							'endpoint_name' => self::ENDPOINT_NAME,
						]
					);
				} else {
					$resources_array = json_decode( $result->getBody(), true );
					if ( isset( $resources_array[ self::RESOURCE_NAME_PLURAL ] ) ) {
						if ( count( $resources_array[ self::RESOURCE_NAME_PLURAL ] ) < 1 ) {
							$logger->debug_excess(
								'Activecampaign_For_Woocommerce_Interacts_With_Api: The resource was not found. This may not be an error.',
								[
									'resource_name' => self::RESOURCE_NAME,
									'endpoint_name' => self::ENDPOINT_NAME,
									'filter_name'   => $filter_name,
									'filter_value'  => $filter_value,
									'client_body'   => self::validate_object( $client, 'getBody' ) ? $client->get_body() : null,
									'response'      => $result->getBody() instanceof StreamInterface
										? $result->getBody()->getContents()
										: null,
									'code'          => 404,
								]
							);
						}

						if ( $response_massager ) {
							$resources_array = $response_massager( $resources_array );
						}

						return $resources_array[ self::RESOURCE_NAME_PLURAL ];
					}
				}
			} catch ( Throwable $t ) {
				$logger->debug(
					'Activecampaign_For_Woocommerce_Interacts_With_Api: Resource thrown error.',
					[
						'result'       => $result,
						'filter_name'  => $filter_name,
						'filter_value' => $filter_value,
						'client_body'  => self::validate_object( $client, 'getBody' ) ? $client->get_body() : null,
						'code'         => $t->getCode(),
					]
				);
			}
		}
	}

	/**
	 * Serializes a model and creates a remote resource via the API.
	 *
	 * @param Activecampaign_For_Woocommerce_Api_Client           $client The API Client class.
	 * @param Activecampaign_For_Woocommerce_Ecom_Model_Interface $model The model class.
	 * @param callable                                            $response_massager A callable to alter the response body.
	 */
	private function create_and_set_model_properties_from_api(
		Activecampaign_For_Woocommerce_Api_Client $client,
		Activecampaign_For_Woocommerce_Ecom_Model_Interface $model,
		callable $response_massager = null
	) {
		$client->set_filters( [] );
		$logger = new Logger();

		$resource = $model->serialize_to_array();

		if ( 'import' === self::RESOURCE_NAME ) {
			$body = $resource;
		} else {
			$body = [
				self::RESOURCE_NAME => $resource,
			];
		}

		$body_as_string = wp_json_encode( $body );

		try {
			$result = $client
				->post( self::ENDPOINT_NAME_PLURAL )
				->with_body( $body_as_string )
				->execute();
		} catch ( AcVendor\GuzzleHttp\Exception\ClientException $e ) {
			$logger->warning(
				'Activecampaign_For_Woocommerce_Interacts_With_Api: The resource was unprocessable. [ACGE]',
				[
					'message'       => $e->getMessage(),
					'resource_name' => self::RESOURCE_NAME,
					'endpoint_name' => self::ENDPOINT_NAME,
					'client_body'   => self::validate_object( $client, 'getBody' ) ? $client->get_body() : null,
					'context'       => $body_as_string,
					'response'      => self::validate_object( $e->getResponse(), 'getBody' )
						? $e->getResponse()->getBody()->getContents()
						: '',
					// Make sure the clean trace ends up in the logs
					'trace'         => $logger->clean_trace( $e->getTrace() ),
					'status_code'   => self::validate_object( $e->getResponse(), 'getStatusCode' ) ? $e->getResponse()->getStatusCode() : '',
				]
			);
		} catch ( Throwable $t ) {
			$logger->warning(
				'Activecampaign_For_Woocommerce_Interacts_With_Api: The resource was unprocessable. [Throwable]',
				[
					'message'       => $t->getMessage(),
					'resource_name' => self::RESOURCE_NAME,
					'endpoint_name' => self::ENDPOINT_NAME,
					'client_body'   => self::validate_object( $client, 'getBody' ) ? $client->get_body() : null,
					'context'       => $body_as_string,
					'response'      => self::validate_object( $t->getResponse(), 'getBody' )
						? $t->getResponse()->getBody()->getContents()
						: '',
					// Make sure the clean trace ends up in the logs
					'trace'         => $logger->clean_trace( $t->getTrace() ),
					'status_code'   => self::validate_object( $t->getResponse(), 'getStatusCode' ) ? $t->getResponse()->getStatusCode() : '',
				]
			);
		}

		if ( isset( $result ) && self::validate_object( $result, 'getBody' ) ) {
			try {
				$resource_array = json_decode( $result->getBody(), true );

				if ( $response_massager ) {
					$resource_array = $response_massager( $resource_array );
				}

				if ( isset( $resource_array[ self::RESOURCE_NAME ] ) ) {
					$resource = $resource_array[ self::RESOURCE_NAME ];
					$model->set_properties_from_serialized_array( $resource );
				}

				return $result;
			} catch ( Throwable $t ) {
				$logger = new Logger();
				$logger->warning(
					'Activecampaign_For_Woocommerce_Interacts_With_Api: Resource error thrown.',
					[
						'message'     => $t->getMessage(),
						'client_body' => self::validate_object( $client, 'getBody' ) ? $client->get_body() : null,
						'result'      => $result,
						'trace'       => $logger->clean_trace( $t->getTrace() ),
					]
				);
			}
		}

		if ( isset( $result['type'] ) && ( 'error' === $result['type'] || 'timeout' === $result['type'] ) ) {
			$logger = new Logger();
			$logger->error(
				'ActiveCampaign returned an error response. Please check the result below for explanation.',
				[
					'resource_name' => self::RESOURCE_NAME,
					'endpoint_name' => self::ENDPOINT_NAME,
					'ac_code'       => 'IWAPI_416',
					'client_body'   => self::validate_object( $client, 'getBody' ) ? $client->get_body() : null,
					'result'        => $result,
				]
			);

			return $result;
		}

		if ( isset( $result ) && is_array( $result ) && isset( $result['type'] ) && ( 'success' === $result['type'] ) ) {
			return true;
		}

		return false;
	}

	/**
	 * Serializes a model and updates a remote resource via the API.
	 *
	 * @param Activecampaign_For_Woocommerce_Api_Client           $client The API Client class.
	 * @param Activecampaign_For_Woocommerce_Ecom_Model_Interface $model The model class.
	 * @param callable                                            $response_massager A callable to alter the response body.
	 */
	private function update_and_set_model_properties_from_api(
		Activecampaign_For_Woocommerce_Api_Client $client,
		Activecampaign_For_Woocommerce_Ecom_Model_Interface $model,
		callable $response_massager = null
	) {
		$client->set_filters( [] );

		$resource = $model->serialize_to_array();

		$body = [
			self::RESOURCE_NAME => $resource,
		];

		$body_as_string = wp_json_encode( $body );
		$logger         = new Logger();
		try {
			$logger->debug_calls(
				'Sent request to hosted',
				array(
					'resource_name'  => self::RESOURCE_NAME,
					'endpoint_name'  => self::ENDPOINT_NAME,
					'body_as_string' => $body_as_string,
				)
			);

			$result = $client
				->put( self::RESOURCE_NAME_PLURAL, $model->get_id() )
				->with_body( $body_as_string )
				->execute();

			return $result;
		} catch ( AcVendor\GuzzleHttp\Exception\ClientException $e ) {
			if ( $e->getCode() === 404 ) {
				$logger->debug_excess(
					'Activecampaign_For_Woocommerce_Interacts_With_Api: The resource was not found. This is likely not an error.',
					[
						'message'       => $e->getMessage(),
						'resource_name' => self::RESOURCE_NAME,
						'endpoint_name' => self::ENDPOINT_NAME,
						'found_by'      => 'id',
						'value'         => $model->get_id(),
						'response'      => $e->getResponse()
							? $e->getResponse()->getBody()->getContents()
							: '',
						// Make sure the trace ends up in the logs
						'trace'         => $logger->clean_trace( $e->getTrace() ),
						'status_code'   => $e->getResponse() ? $e->getResponse()->getStatusCode() : '',
					]
				);
			}

			if ( $e->getCode() === 403 ) {
				$logger->error(
					'ActiveCampaign returned a 403 error. This action may be forbidden.',
					[
						'suggested_action' => 'Please verify your API credentials are correct. If they are please try repairing your connection or re-saving your settings in the plugin.',
						'ac_code'          => 'IWAPI_496',
						'message'          => $e->getMessage(),
						'resource_name'    => self::RESOURCE_NAME,
						'endpoint_name'    => self::ENDPOINT_NAME,
						'found_by'         => 'id',
						'value'            => $model->get_id(),
						'response'         => $e->getResponse()
							? $e->getResponse()->getBody()->getContents()
							: '',
						// Make sure the trace ends up in the logs
						'trace'            => $logger->clean_trace( $e->getTrace() ),
						'status_code'      => $e->getResponse() ? $e->getResponse()->getStatusCode() : '',
					]
				);
			}

			$logger->warning(
				'Activecampaign_For_Woocommerce_Interacts_With_Api: The resource was unprocessable.',
				[
					'message'       => $e->getMessage(),
					'resource_name' => self::RESOURCE_NAME,
					'endpoint_name' => self::ENDPOINT_NAME,
					'context'       => $body_as_string,
					'response'      => $e->getResponse()
						? $e->getResponse()->getBody()->getContents()
						: '',
					'status_code'   => $e->getResponse() ? $e->getResponse()->getStatusCode() : '',
				]
			);
		}

		if ( isset( $result ) && null !== $result && self::validate_object( $result, 'getBody' ) ) {
			try {
				$resource_array = json_decode( $result->getBody(), true );

				if ( $response_massager ) {
					$resource_array = $response_massager( $resource_array );
				}

				$resource = $resource_array[ self::RESOURCE_NAME ];
				$model->set_properties_from_serialized_array( $resource );
			} catch ( Throwable $t ) {
				$logger = new Logger();
				$logger->debug(
					'Activecampaign_For_Woocommerce_Interacts_With_Api: Failed to set properties from serialized array.',
					[
						'result' => $result,
					]
				);
			}
		}

	}

	/**
	 * Retrieves a resource via the API and uses the value to update a model.
	 *
	 * @param     Activecampaign_For_Woocommerce_Api_Client $client     The Client class.
	 * @param     string                                    $id     The id to find.
	 * @param     callable|null                             $response_massager     A callable to alter the response body.
	 *
	 * @return mixed
	 */
	private function delete_model_from_api_by_id(
		Activecampaign_For_Woocommerce_Api_Client $client,
		$id,
		callable $response_massager = null
	) {
		$logger = new Logger();

		try {
			$result = $client
				->delete( self::ENDPOINT_NAME_PLURAL, (string) $id )
				->execute();
		} catch ( Throwable $t ) {
			$logger->debug_calls(
				'The delete method threw a critical error.',
				[
					'resource_name'    => self::RESOURCE_NAME,
					'endpoint_name'    => self::ENDPOINT_NAME,
					'value'            => $id,
					'response_message' => $t->getMessage(),
					'status_code'      => $t->getTrace(),
				]
			);
		}

		if ( isset( $result ) ) {
			try {
				return $result;
			} catch ( Throwable $t ) {
				$logger->warning(
					'Activecampaign_For_Woocommerce_Interacts_With_Api: Resource thrown error.',
					[
						'result' => $result,
					]
				);
			}
		} else {
			$logger->error(
				'There was an issue contacting hosted with delete method, request returned an empty or null result.',
				[
					'suggested_action' => 'You may not have access rights to delete via the ActiveCampaign API.',
					'ac_code'          => 'IWAPI_599',
					'client'           => $client,
					'function'         => 'get_and_set_model_properties_from_api_by_id',
					'resource_name'    => self::RESOURCE_NAME,
					'endpoint_name'    => self::ENDPOINT_NAME,
					'value'            => $id,
				]
			);
		}
	}

	private function get_and_set_model_properties_from_api(
		Activecampaign_For_Woocommerce_Api_Client $client,
		Activecampaign_For_Woocommerce_Ecom_Model_Interface $model,
		callable $response_massager = null
	) {
		$logger = new Logger();
		$result = 'zero';

		try {
			$result = $client
				->get( self::ENDPOINT_NAME_PLURAL )
				->execute();
		} catch ( Throwable $t ) {
			$logger->debug_excess(
				'The resource was not found in Hosted. This may not be an error. Check response_message to confirm.',
				[
					'resource_name'    => self::RESOURCE_NAME,
					'endpoint_name'    => self::ENDPOINT_NAME,
					'found_by'         => 'gets all',
					'response_message' => $t->getMessage(),
					'ac_code'          => 'IWAPI_630',
					// 'status_code'      => $t->getResponse() ? $t->getResponse()->getStatusCode() : '',
				]
			);
		}

		if ( isset( $result ) ) {
			if ( ! is_object( $result ) || ! self::validate_object( $result, 'getBody' ) ) {
				$logger->debug(
					'Result of get and set model from get_and_set_model_properties_from_api_by_id',
					[
						'result'        => $result,
						'resource_name' => self::RESOURCE_NAME,
						'endpoint_name' => self::ENDPOINT_NAME,
						'ac_code'       => 'IWAPI_643',
					]
				);
			} else {
				try {
					if ( $result->getBody() !== null && is_string( $result->getBody() ) && ! empty( $result->getBody() ) ) {
						$resource_array = json_decode( $result->getBody(), true );
					} elseif ( is_string( $result->getBody()->__toString() ) ) {
						$resource_array = json_decode( $result->getBody()->__toString(), true );
					}

					if ( $response_massager ) {
						$resource_array = $response_massager( $resource_array );
					}

					if ( isset( $resource_array ) && is_array( $resource_array ) ) {
						$model->set_properties_from_serialized_array( $resource_array );
					} else {
						$logger->error(
							'Resource returned is invalid or empty. ActiveCampaign may be unreachable.',
							[
								'result'           => $result,
								'suggested_action' => 'Please wait and if this message persists contact ActiveCampaign for support.',
								'ac_code'          => 'IWAPI_665',
								'resource_name'    => self::RESOURCE_NAME,
								'result_body'      => self::validate_object( $result, 'getBody' ) ? $result->getBody() : null,
							]
						);
					}
				} catch ( Throwable $t ) {
					$logger->warning(
						'Activecampaign_For_Woocommerce_Interacts_With_Api: Resource thrown error.',
						[
							'result'  => $result,
							'message' => $t->getMessage(),
							'ac_code' => 'IWAPI_677',
						]
					);
				}
			}
		} else {
			$logger->error(
				'There was an issue contacting ActiveCampaign, request returned an empty or null result. ActiveCampaign may not be reachable by this Host.',
				[
					'suggested_action' => 'Please verify with your hosting provider that the ActiveCampaign API is reachable. Please contact ActiveCampaign support if necessary.',
					'ac_code'          => 'IWAPI_686',
					'client'           => $client,
					'function'         => 'get_and_set_model_properties_from_api_by_id',
					'resource_name'    => self::RESOURCE_NAME,
					'endpoint_name'    => self::ENDPOINT_NAME,
					'found_by'         => 'all',
				]
			);
		}
	}
}
