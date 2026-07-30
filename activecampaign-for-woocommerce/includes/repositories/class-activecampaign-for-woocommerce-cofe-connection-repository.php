<?php

use Activecampaign_For_Woocommerce_Api_Client_Graphql as Api_Client_Graphql;
use Activecampaign_For_Woocommerce_Interacts_With_Api as Interacts_With_Api;
use Activecampaign_For_Woocommerce_Logger as Logger;

/**
 * Repository for connection lookups against COFE (GraphQL).
 *
 * @package    Activecampaign_For_Woocommerce
 * @subpackage Activecampaign_For_Woocommerce/includes/repositories
 */
class Activecampaign_For_Woocommerce_Cofe_Connection_Repository {

	use Interacts_With_Api;

	/**
	 * The GraphQL API client.
	 *
	 * @var Api_Client_Graphql
	 */
	private $client;

	/**
	 * Constructor.
	 *
	 * @param Api_Client_Graphql $client The GraphQL API client.
	 */
	public function __construct( Api_Client_Graphql $client ) {
		$this->client = $client;
		$this->client->configure_client( null, 'ecom/graphql' );
	}

	/**
	 * Resolves the COFE connection ID for a given legacy connection ID.
	 *
	 * @param int $legacy_connection_id The `connection_id` value from local WP storage.
	 *
	 * @return string|null COFE connection ID (`con-<accountId>-<legacyConnectionId>`), or null when not found.
	 */
	public function find_id_by_legacy_id( $legacy_connection_id ) {
		$logger = new Logger();

		try {
			$query = 'query{searchConnection(filter:{legacyConnectionId:{filterOperator:EQ value:' . (int) $legacy_connection_id . '}}){id}}';

			$response = $this->client->send( $query );

			if ( ! is_object( $response ) || ! method_exists( $response, 'getBody' ) ) {
				$logger->warning( 'There was a problem retrieving body from GraphQL response.' );

				return null;
			}

			$decoded = json_decode( (string) $response->getBody(), true );

			if ( isset( $decoded['errors'] ) && count( $decoded['errors'] ) > 0 ) {
				$logger->error(
					'searchConnection returned errors.',
					array(
						'errors' => $decoded['errors'],
						'query'  => $query,
					)
				);
				return null;
			}

			$connections = isset( $decoded['data']['searchConnection'] ) ? $decoded['data']['searchConnection'] : array();

			if ( isset( $connections['id'] ) ) {
				return $connections['id'];
			}

			if ( is_array( $connections ) && isset( $connections[0]['id'] ) ) {
				return $connections[0]['id'];
			}
		} catch ( Throwable $t ) {
			$logger->warning(
				'There was an issue resolving the COFE connection ID.',
				array(
					'message'              => $t->getMessage(),
					'legacy_connection_id' => $legacy_connection_id,
				)
			);
		}

		return null;
	}

	/**
	 * Resolves the COFE connection ID from local WP storage.
	 *
	 * Convenience wrapper around {@see find_id_by_legacy_id()} that reads the
	 * legacy connection ID from the plugin's storage option.
	 *
	 * @return string|null
	 */
	public function find_current_id() {
		$storage = get_option( ACTIVECAMPAIGN_FOR_WOOCOMMERCE_DB_CONNECTION_STORAGE_NAME );

		if ( empty( $storage['connection_id'] ) ) {
			return null;
		}

		return $this->find_id_by_legacy_id( $storage['connection_id'] );
	}
}
