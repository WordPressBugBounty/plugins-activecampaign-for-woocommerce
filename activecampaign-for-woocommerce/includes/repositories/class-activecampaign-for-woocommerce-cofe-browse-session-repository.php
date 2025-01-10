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
class Activecampaign_For_Woocommerce_Cofe_Browse_Session_Repository {
	use Interacts_With_Api;

	/**
	 * The API client.
	 *
	 * @var Api_Client_Graphql
	 */
	private $client;

	/**
	 * Browse_Session Repository constructor.
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
	 * Send a add to cart event to a Browse Session to keep it active
	 *
	 * @param string $email email for the user triggering add to cart event.
	 */
	public function browse_session_cart_add( $email ) {
		$logger = new Logger();

		try {
			$storage  = get_option( ACTIVECAMPAIGN_FOR_WOOCOMMERCE_DB_CONNECTION_STORAGE_NAME );
			$response = $this->client->mutation_browse_add_cart(
				'browseSessionCartAdd',
				(int) $storage['connection_id'],
				$email
			);
		} catch ( Throwable $t ) {
			$logger->warning(
				'Browse_Session repository failed to send graphql data. Process must be ended.',
				array(
					'message' => $t->getMessage(),
					'code'    => $t->getCode(),
					'trace'   => $t->getTrace(),
				)
			);
			return false;
		}
	}
}
