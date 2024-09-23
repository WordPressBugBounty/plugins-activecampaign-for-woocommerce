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
class Activecampaign_For_Woocommerce_Product_Repository {

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

	}

	/**
	 * Creates a remote resource and updates the model with the returned data.
	 *
	 * @param array $models The model to be created remotely.
	 */
	public function create_bulk( $models ) {
		$logger = new Logger();

		global $activecampaign_for_woocommerce_product_sync_status;
		try {
			GraphqlSerializer::graphql_serialize( 'products', $models );

			if ( $models ) {
				$response = $this->client->mutation(
					'bulkUpsertProducts',
					'products',
					$models,
					array(
						'id',
						'storePrimaryId',
					)
				);

				$activecampaign_for_woocommerce_product_sync_status[] = 'Synced Products: ' . implode(
					', ',
					array_map(
						function ( $prod ) {
							return $prod['storePrimaryId'];
						},
						$models
					)
				);

				return $response;
			} else {
				$logger->warning(
					'No valid models were provided to the product bulk sync.',
					[
						'models' => $models,
					]
				);

				return false;
			}
		} catch ( Throwable $t ) {
			$logger->warning(
				'Product repository failed to send graphql data. Process must be ended.',
				[
					'message' => $t->getMessage(),
					'code'    => $t->getCode(),
					'trace'   => $t->getTrace(),
				]
			);
			return false;
		}

		return false;
	}

	/**
	 * @return false|string
	 */
	public function sync_connection() {
		$logger = new Logger();
		try {
			$storage  = get_option( ACTIVECAMPAIGN_FOR_WOOCOMMERCE_DB_CONNECTION_STORAGE_NAME );
			$response = $this->client->sync_mutation(
				'syncLegacyConnection',
				'woocommerce',
				$storage['external_id'],
				[
					'id',
				]
			);

			return $response;
		} catch ( Throwable $t ) {
			$logger->warning(
				'There was an issue trying to sync the COFE connection',
				[
					'message' => $t->getMessage(),
					'trace'   => $t->getTrace(),
				]
			);

			return false;
		}
	}
}
