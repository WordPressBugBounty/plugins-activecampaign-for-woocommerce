<?php

/**
 * The file for the Activecampaign_for_Woocommerce_Whitelist_Repository class
 *
 * Usage example
 * $whitelist_repo->create( $whitelist_repo->build_parsed_whitelist_item(site_url()) );
 *
 * @link       https://www.activecampaign.com/
 * @since      1.0.0
 *
 * @package    Activecampaign_For_Woocommerce
 * @subpackage Activecampaign_For_Woocommerce/includes/repositories
 */

use Activecampaign_For_Woocommerce_Api_Client as Api_Client;

use Activecampaign_For_Woocommerce_AC_Whitelist as AC_Whitelist;
use Activecampaign_For_Woocommerce_Ecom_Model_Interface as Ecom_Model;
use Activecampaign_For_Woocommerce_Interacts_With_Api as Interacts_With_Api;
use Activecampaign_For_Woocommerce_Repository_Interface as Repository;
use Activecampaign_For_Woocommerce_Resource_Not_Found_Exception as Resource_Not_Found;
use Activecampaign_For_Woocommerce_Resource_Unprocessable_Exception as Unprocessable;
use Activecampaign_For_Woocommerce_Logger as Logger;
/**
 * The repository class for Whitelisting URL
 *
 * @since      1.0.0
 * @package    Activecampaign_For_Woocommerce
 * @subpackage Activecampaign_For_Woocommerce/includes/repositories
 * @author     acteamintegrations <team-integrations@activecampaign.com>
 */
class Activecampaign_For_Woocommerce_Whitelist_Repository {
	use Interacts_With_Api;

	/**
	 * The singular resource name as it maps to the AC API.
	 */
	const RESOURCE_NAME = 'siteTrackingDomain';

	// "siteTrackingDomain": {
	// "name": "example.com"
	// }
	/**
	 * The plural resource name as it maps to the AC API.
	 */
	const RESOURCE_NAME_PLURAL = 'siteTrackingDomains';
	const ENDPOINT_NAME        = 'siteTrackingDomain';
	const ENDPOINT_NAME_PLURAL = 'siteTrackingDomains';
	/**
	 * The API client.
	 *
	 * @var Api_Client
	 */
	private $client;

	/**
	 * Whitelist Repository constructor.
	 *
	 * @param Api_Client $client The api client.
	 */
	public function __construct( Api_Client $client ) {
		$this->client = $client;

		$this->client->configure_client();
	}

	public function list_all() {
		/**
		 * A Whitelist Model.
		 *
		 * @var AC_Whitelist $whitelist_model
		 */

		$response = $this->get_result_set_from_api_by_filter(
			$this->client,
			'',
			''
		);

		$whitelist = array();
		if ( isset( $response[0] ) ) {
			foreach ( $response as $r ) {
				$whitelist_model = new AC_Whitelist();
				$whitelist[]     = $whitelist_model->set_properties_from_serialized_array( $r )->get_name();
			}
		}

		return $whitelist;
	}

	/**
	 * Creates a remote resource and updates the model with the returned data.
	 *
	 * @param Ecom_Model $model The model to be created remotely.
	 *
	 * @return Ecom_Model
	 * @throws Unprocessable Thrown when the whitelist could not be processed due to bad data.
	 */
	public function create( Ecom_Model $model ) {
		$result = $this->create_and_set_model_properties_from_api(
			$this->client,
			$model
		);

		$logger = new Logger();

		try {
			if ( 422 === $result->getStatusCode() ) {
				$resource_array = json_decode( $result->getBody()->__toString(), true );

				if ( isset( $resource_array['errors'] ) ) {
					$error_list = array();
					foreach ( $resource_array['errors'] as $err_obj ) {
						$err_obj['name'] = $model->get_name();
						$error_list[]    = $err_obj;
					}

					$logger->debug(
						'There was an issue creating this Whitelist entry in ActiveCampaign. Your tracking may not work for the site entered. You can also add this manually on your ActiveCampaign SiteTracking page.',
						array(
							$error_list,
						)
					);

					return $error_list;
				}

				return;
			}
		} catch (Throwable $t ) {
			$logger->error(
				'An error occurred attempting to create a whitelist entry.',
				array(
					'message' => $t->getMessage(),
				)
			);
		}

		return $model;
	}

	/**
	 * Parses a URL string and returns the needed structure.
	 *
	 * @param string $url The URL to parse.
	 *
	 * @return Activecampaign_For_Woocommerce_AC_Whitelist
	 */
	public function parse_single_url_return_whitelist_model( $url ) {
		$parsed = wp_parse_url( $url ); // URL cannot contain protocol

		if (isset( $parsed['host'] ) && isset( $parsed['path'] ) ) {
			return $this->build_whitelist_model_for_name( $parsed['host'] . $parsed['path'] );
		} elseif ($parsed['host'] ) {
				return $this->build_whitelist_model_for_name( $parsed['host'] );
		} else {
			return $this->build_whitelist_model_for_name( $url );
		}
	}

	/**
	 * Builds a whitelist model from name.
	 *
	 * @param string $name The site name.
	 *
	 * @return Activecampaign_For_Woocommerce_AC_Whitelist
	 */
	public function build_whitelist_model_for_name( $name ) {
		$whitelist_model = new AC_Whitelist();
		$whitelist_model->set_name( $name );

		return $whitelist_model;
	}

	/**
	 * Parse a URL array and only return the unique host names.
	 *
	 * @param array $urls The url list.
	 *
	 * @return array
	 */
	public function parse_url_array_return_unique_hosts( $urls ) {
		$parsed_list = array();
		foreach ( $urls as $url ) {
			$parsed = wp_parse_url( $url );

			if ( isset( $parsed['host'] ) ) {
				$parsed_list[] = $parsed['host'];
			}
		}
		return array_unique( $parsed_list );
	}
}
