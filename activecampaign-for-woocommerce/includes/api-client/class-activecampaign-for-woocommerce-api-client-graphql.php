<?php

/**
 * The file that defines a simple client for use with graphql.
 *
 * @link       https://www.activecampaign.com/
 *
 * @package    Activecampaign_For_Woocommerce
 * @subpackage Activecampaign_For_Woocommerce/includes/api-client
 */
class Activecampaign_For_Woocommerce_Api_Client_Graphql extends Activecampaign_For_Woocommerce_Api_Client {


	/**
	 * Serializes the data, creates the mutation, and sends the request to Graphql.
	 *
	 * @param string                                                                                            $operation operation.
	 * @param string|null                                                                                       $first_key firstKey.
	 * @param Activecampaign_For_Woocommerce_Api_Serializable|Activecampaign_For_Woocommerce_Api_Serializable[] $body_objects objects in body.
	 * @param array                                                                                             $response_fields fields you want in response.
	 *
	 * @return string
	 */
	public function mutation( $operation, $first_key, $body_objects, $response_fields = array() ) {
		try {
			$params = Activecampaign_For_Woocommerce_Simple_Graphql_Serializer::graphql_serialize( $first_key, $body_objects );
		} catch ( Throwable $t ) {
			$this->logger->error(
				'There was an issue serializing the data for graphql.',
				array(
					'first_key'    => $first_key,
					'body_objects' => $body_objects,
					'message'      => $t->getMessage(),
					'trace'        => $t->getTrace(),
					'ac_code'      => 'APIGQL_26',
				)
			);
		}

		try {
			$body_objects = 'mutation{' . $operation . '(' . $params . '){' . implode( ' ', $response_fields ) . '}}';
		} catch ( Throwable $t ) {
			$this->logger->error(
				'Failed to form Graphql mutation. No response.',
				array(
					'message'         => $t->getMessage(),
					'response_fields' => $response_fields,
					'operation'       => $operation,
					'params'          => $params,
					'trace'           => $t->getTrace(),
					'ac_code'         => 'APIGQL_42',
				)
			);
		}

		$this->logger->debug_calls( 'Body objects sent to AC', array( 'trimmed' => trim( $body_objects, 250 ) ) );

		$response = $this->send( $body_objects );

		if (
			is_array( $response ) &&
			200 !== $response['code'] &&
			201 !== $response['code']
		) {

			$this->logger->error(
				'Failed Graphql call. No response.',
				array(
					'body'    => $body_objects,
					'message' => isset( $response['message'] ) ? $response['message'] : null,
					'code'    => isset( $response['code'] ) ? $response['code'] : null,
					'ac_code' => 'APIGQL_85',
				)
			);

			throw new RuntimeException( 'Failed Graphql call. Error response: ' . $response['code'] . '|' . $response['message'] );
		}

		if ( method_exists( $response, 'getBody' ) ) {
			$this->logger->debug_calls( "Made graphQL API call to $operation response: " . $response->getStatusCode() . $response->getReasonPhrase() );

			$response_body  = $response->getBody();
			$response_array = json_decode( $response_body, true );

			if ( isset( $response_array['errors'] ) && count( $response_array['errors'] ) > 0 ) {
				global $activecampaign_for_woocommerce_product_sync_status;
				$activecampaign_for_woocommerce_product_sync_status[] = "Body: $body_objects";
				$this->logger->error(
					'Graphql returned errors:',
					array(
						'body'           => $body_objects,
						'response_array' => $response_array,
						'response_body'  => $response_body,
						'ac_code'        => 'APIGQL_110',
					)
				);

				throw new RuntimeException( "Failed Graphql call. Response: $response_body" );
			}

			return (string) $response_body;
		}

		return $response;
	}

	/**
	 *
	 * @param string $operation operation.
	 * @param string $legacy_connection_id integration name.
	 * @param string $email unique identifier, usually the URL set up on the connection for woocommerce.
	 *
	 * @return string
	 */
	public function mutation_browse_add_cart( $operation, $legacy_connection_id, $email ) {
		$body_objects = 'mutation{' . $operation . '(legacyConnectionId:' . $legacy_connection_id . ' email:"' . $email . '")}';

		$this->logger->debug_calls( 'Browse Session Add to Cart Event: Body Objects', array( $body_objects ) );

		$response = $this->send( $body_objects );

		if (
			is_array( $response ) &&
			200 !== $response['code'] &&
			201 !== $response['code']
		) {

			$this->logger->error(
				'Failed Graphql call. No response.',
				array(
					'body'    => $body_objects,
					'message' => isset( $response['message'] ) ? $response['message'] : null,
					'code'    => isset( $response['code'] ) ? $response['code'] : null,
					'ac_code' => 'APIGQL_170',
				)
			);

			throw new RuntimeException( 'Failed Graphql call. Error response. ' );
		}

		if ( method_exists( $response, 'getBody' ) ) {
			$this->logger->debug_calls(
				"Made graphQL API call to $operation response: ",
				array(
					'code'   => $response->getStatusCode(),
					'reason' => $response->getReasonPhrase(),
				)
			);

			$response_body  = $response->getBody();
			$response_array = json_decode( $response_body, true );

			if ( isset( $response_array['errors'] ) && count( $response_array['errors'] ) > 0 ) {
				global $activecampaign_for_woocommerce_product_sync_status;
				$activecampaign_for_woocommerce_product_sync_status[] = "Body: $body_objects";
				$this->logger->error(
					'Graphql returned errors:',
					array(
						'response_array' => $response_array,
						'response_body'  => $response_body,
						'ac_code'        => 'APIGQL_195',
					)
				);

				throw new RuntimeException( "Failed Graphql call. Response: $response_body" );
			}

			return (string) $response_body;
		}

		return $response;
	}
	/**
	 *
	 * @param string $operation operation.
	 * @param string $integration_name integration name.
	 * @param string $connection_unique_identifier unique identifier, usually the URL set up on the connection for woocommerce.
	 * @param array  $response_fields fields you want in response.
	 *
	 * @return string
	 */
	public function sync_mutation( $operation, $integration_name, $connection_unique_identifier, $response_fields = array() ) {
		$body_objects = 'mutation{' . $operation . '(integrationName:"' . $integration_name . '" connectionUniqueIdentifier:"' . $connection_unique_identifier . '"){' . implode( ' ', $response_fields ) . '}}';

		$this->logger->debug_calls( 'Body objects', array( $body_objects ) );

		$response = $this->send( $body_objects );

		if (
			is_array( $response ) &&
			200 !== $response['code'] &&
			201 !== $response['code']
		) {

			$this->logger->error(
				'Failed Graphql call. No response.',
				array(
					'body'    => $body_objects,
					'message' => isset( $response['message'] ) ? $response['message'] : null,
					'code'    => isset( $response['code'] ) ? $response['code'] : null,
					'ac_code' => 'APIGQL_170',
				)
			);

			throw new RuntimeException( 'Failed Graphql call. Error response. ' );
		}

		if ( method_exists( $response, 'getBody' ) ) {
			$this->logger->debug_calls(
				"Made graphQL API call to $operation response: ",
				array(
					'code'   => $response->getStatusCode(),
					'reason' => $response->getReasonPhrase(),
				)
			);

			$response_body  = $response->getBody();
			$response_array = json_decode( $response_body, true );

			if ( isset( $response_array['errors'] ) && count( $response_array['errors'] ) > 0 ) {
				global $activecampaign_for_woocommerce_product_sync_status;
				$activecampaign_for_woocommerce_product_sync_status[] = "Body: $body_objects";
				$this->logger->error(
					'Graphql returned errors:',
					array(
						'response_array' => $response_array,
						'response_body'  => $response_body,
						'ac_code'        => 'APIGQL_195',
					)
				);

				throw new RuntimeException( "Failed Graphql call. Response: $response_body" );
			}

			return (string) $response_body;
		}

		return $response;
	}

	public function operation( $operation, $body, $response_fields = array() ) {
		$body = '{' . $operation . '(' . $body . ')}{' . explode( ' ', $response_fields ) . '}}';
		$this->post( '' )->with_body( $body );
	}

	/**
	 * @param ?string $body_objects
	 * @return \AcVendor\Psr\Http\Message\ResponseInterface|array|bool|string|string[]
	 */
	public function send( ?string $body_objects ) {
		if ( $body_objects ) {
			$response = $this->post( '' )->with_body( wp_json_encode( array( 'query' => $body_objects ) ) )->execute(
				array(
					'content-type'      => 'application/json',
					'wc-plugin-version' => ACTIVECAMPAIGN_FOR_WOOCOMMERCE_VERSION,
				)
			);
		}

		if ( ! isset( $response ) || empty( $response ) ) {
			$this->logger->error(
				'Failed Graphql call. No response.',
				array(
					'body' => $body_objects,
				)
			);

			throw new RuntimeException( 'Failed Graphql call. No response.' );
		}
		return $response;
	}
}
