<?php

use Activecampaign_For_Woocommerce_Api_Client_Graphql as Api_Client_Graphql;
use Activecampaign_For_Woocommerce_Interacts_With_Api as Interacts_With_Api;
use Activecampaign_For_Woocommerce_Simple_Graphql_Serializer as GraphqlSerializer;
use Activecampaign_For_Woocommerce_Cofe_Sync_Connection as Cofe_Sync_Connection;
use Activecampaign_For_Woocommerce_Logger as Logger;

/**
 * @package    Activecampaign_For_Woocommerce
 * @subpackage Activecampaign_For_Woocommerce/includes/repositories
 */
class Activecampaign_For_Woocommerce_Cofe_Order_Repository {
	use Interacts_With_Api;

	/**
	 * The API client.
	 *
	 * @var Api_Client_Graphql
	 */
	private $client;

	/**
	 * Ecom_Order Repository constructor.
	 *
	 * @param Api_Client_Graphql $client The api client.
	 */
	public function __construct( Api_Client_Graphql $client ) {
		$this->client = $client;
		// Prod/Staging:
		$this->client->configure_client( null, 'ecom/graphql' );
		$this->client->set_max_retries( 2 );
	}

	/**
	 * Creates a remote resource and updates the model with the returned data.
	 *
	 * @param array $model The model to be created remotely.
	 * @return string object from graphql or null
	 */
	public function upsert_order( $model ) {
		$logger = new Logger();

		try {
			if ( $model ) {
				// GraphqlSerializer::graphql_serialize( 'orders', $model );
				$args = [
					'operation' => 'upsertOrder',
					'first_key' => 'order',
					'return'    => array( 'id' ),
				];

				$response = $this->perform_mutation( $model, $args );

				return $response;
			} else {
				$logger->warning(
					'No valid models were provided to the single record sync for upsert order.',
					[
						'models'  => $model,
						'ac_code' => 'COR_61',
					]
				);

				return null;
			}
		} catch ( Throwable $t ) {
			$split = explode( 'Response:', $t->getMessage() );
			if ( isset( $split[1] ) ) {
				$ob = maybe_unserialize( $split[1] );
			}
			$logger->warning(
				'Order repository failed to send graphql data for upsertOrder. Process must be ended.',
				[
					'message' => $t->getMessage(),
					'code'    => $t->getCode(),
					'trace'   => $logger->clean_trace( $t->getTrace() ),
					'ac_code' => 'COR_78',
				]
			);
			return null;
		}
	}

	public function upsert_subscription( $model ) {
		$logger = new Logger();

		try {
			if ( $model ) {
				// GraphqlSerializer::graphql_serialize( 'orders', $model );
				$args = [
					'operation' => 'bulkUpsertRecurringPayments',
					'first_key' => 'recurringPayments',
					'return'    => array( 'recordId' ),
				];

				$response = $this->perform_mutation( $model, $args );

				return $response;
			} else {
				$logger->warning(
					'No valid models were provided to the single record sync for upsert subscription.',
					[
						'models'  => $model,
						'ac_code' => 'COR_105',
					]
				);

				return null;
			}
		} catch ( Throwable $t ) {
			$split = explode( 'Response:', $t->getMessage() );
			$ob    = null;
			if ( isset( $split[1] ) ) {
				$ob = maybe_unserialize( $split[1] );
			}
			$logger->warning(
				'Order repository failed to send graphql data for upsert subscription. Process must be ended.',
				[
					'message' => $t->getMessage(),
					'object'  => $ob,
					'code'    => $t->getCode(),
					'trace'   => $logger->clean_trace( $t->getTrace() ),
					'ac_code' => 'COR_122',
				]
			);

			return null;
		}
	}

	/**
	 * Creates a remote resource and updates the model with the returned data.
	 *
	 * @param array $models The model to be created remotely.
	 * @return mixed object from graphql or null
	 */
	public function create_bulk( $models, $operation_type = 'orders' ) {
		$logger = new Logger();

		try {
			// GraphqlSerializer::graphql_serialize( 'orders', $models );
			if ( $models ) {
				$args = [
					'operation' => 'bulkUpsertOrdersAsync',
					'first_key' => 'orders',
					'return'    => array(
						'recordId',
					),
				];

				if ( 'subscription' === $operation_type || 'subscriptions' === $operation_type ) {
					$args['operation'] = 'bulkUpsertRecurringPayments';
					$args['first_key'] = 'recurringPayments';
				}

				$response = $this->perform_mutation( $models, $args );

				return $response;
			} else {
				$logger->warning(
					'No valid models were provided to the record bulk sync.',
					[
						'models'  => $models,
						'ac_code' => 'COR_163',
					]
				);

				return null;
			}
		} catch ( Throwable $t ) {
			$logger->warning(
				'The order repository failed processing GraphQL data.',
				[
					'message' => $t->getMessage(),
					'code'    => $t->getCode(),
					'ac_code' => 'COR_195',
					'trace'   => $logger->clean_trace( $t->getTrace() ),
				]
			);

			return $this->split_errors_from_response(
				explode( 'Response: ', $t->getMessage() )
			);
		}
	}

	public function perform_mutation( $models, $args ) {
		$logger = new Logger();
		try {
			$response = $this->client->mutation(
				$args['operation'],
				$args['first_key'],
				$models,
				$args['return']
			);

			return $response;
		} catch ( Throwable $t ) {
			$logger->debug(
				'The mutation has thrown an error.',
				[
					$t->getMessage(),
					$args,
				]
			);

			$logger->warning(
				'The order repository failed processing GraphQL data.',
				[
					'message' => $t->getMessage(),
					'code'    => $t->getCode(),
					'ac_code' => 'COR_195',
					'trace'   => $logger->clean_trace( $t->getTrace() ),
				]
			);

			return $this->split_errors_from_response( explode( 'Response: ', $t->getMessage() ) );
		}
	}

	/**
	 * Splits the errors from the response.
	 *
	 * @param array $split The split out data.
	 *
	 * @return array|void
	 */
	public function split_errors_from_response( $split ) {
		if ( isset( $split[1] ) && ! empty( $split[1] ) ) {
			$dec = json_decode( $split[1] );

			if ( isset( $dec->errors ) ) {
				if (
					isset( $dec->errors[0]->message, $dec->errors[0]->extensions ) &&
					'Validation errors' === $dec->errors[0]->message
				) {
					$data = [
						'type'   => 'validation_error',
						'errors' => $this->collect_error_records( $dec->errors[0]->extensions ),
					];

					return $data;
				}

				if (
					isset( $dec->errors[0]->message, $dec->errors[0]->extensions ) &&
					stristr( $dec->errors[0]->message, 'More than 15000 grammar tokens' )
				) {

					$data = [
						'type'   => 'grammar_tokens',
						'errors' => $this->collect_error_records( $dec->errors[0]->extensions ),
					];

					return $data;
				}
			}
		}
	}

	/**
	 * Parse and gather the error records from the message returned.
	 *
	 * @param array $errors The series of errors.
	 *
	 * @return array|null
	 */
	private function collect_error_records( $errors ) {
		$error_id_array = [];

		foreach ( $errors as $key => $error_text ) {
			if ( isset( $key ) && ! empty( $key ) && isset( $error_text ) && ! empty( $error_text ) ) {
				$error_id_array[] = $this->get_error_record( $key );
			}
		}

		if ( count( $error_id_array ) > 0 ) {
			return $error_id_array;
		}

		return null;
	}

	/**
	 * Gets an error record from the line.
	 *
	 * @param string $line The line to parse.
	 *
	 * @return string|void
	 */
	private function get_error_record( $line ) {
		$groups = explode( '-', $line );

		if (
			isset( $groups[1], $groups[2] ) &&
			! empty( $groups[2] ) &&
			( 'storeOrderId' === $groups[1] || 'storeRecurringPaymentId' === $groups[1] )
		) {
			return $groups[2];
		}
	}

}
