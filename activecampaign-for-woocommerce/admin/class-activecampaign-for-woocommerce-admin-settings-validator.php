<?php

/**
 * Handles validating changes to the admin settings for this plugin.
 *
 * @link       https://www.activecampaign.com/
 * @since      1.0.0
 *
 * @package    Activecampaign_For_Woocommerce
 * @subpackage Activecampaign_For_Woocommerce/admin
 */

use Activecampaign_For_Woocommerce_Api_Client as Client;
use Activecampaign_For_Woocommerce_Connection_Repository as Connection_Repository;
use Activecampaign_For_Woocommerce_Resource_Not_Found_Exception as Resource_Not_Found;
use Activecampaign_For_Woocommerce_Logger as Logger;
use AcVendor\GuzzleHttp\Exception\ClientException;
use AcVendor\GuzzleHttp\Exception\GuzzleException;

/**
 * Handles validating changes to the admin settings for this plugin.
 *
 * @package    Activecampaign_For_Woocommerce
 * @subpackage Activecampaign_For_Woocommerce/admin
 * @author     acteamintegrations <team-integrations@activecampaign.com>
 */
class Activecampaign_For_Woocommerce_Admin_Settings_Validator {
	/**
	 * Client to ActiveCampaign API.
	 *
	 * @var Activecampaign_For_Woocommerce_Api_Client The Api Client.
	 */
	private $client;

	/**
	 * Access to connections via ActiveCampaign API.
	 *
	 * @var Activecampaign_For_Woocommerce_Connection_Repository
	 */
	private $connection_repository;

	/**
	 * An array that will errors placed into it to be returned from the validation.
	 *
	 * @var array The errors array.
	 */
	private $errors;

	/**
	 * Our PSR-3 compliant logger.
	 *
	 * @var Logger
	 */
	private $logger;

	/**
	 * Activecampaign_For_Woocommerce_Admin_Settings_Validator constructor.
	 *
	 * @param     Activecampaign_For_Woocommerce_Api_Client $client     The API Client.
	 * @param     Connection_Repository                     $connection_repository     The repository for connections.
	 * @param     Activecampaign_For_Woocommerce_Logger     $logger The logger class.
	 */
	public function __construct( Client $client, Connection_Repository $connection_repository, Logger $logger ) {
		$this->client                = $client;
		$this->connection_repository = $connection_repository;
		$this->logger                = $logger;
	}

	/**
	 * Validates the new data for the options table.
	 *
	 * @param array $new_data     The array of data to be updated.
	 * @param array $current_data The existing data for the options.
	 * @param bool  $api_only     Optional, only validating API.
	 *
	 * @return array
	 */
	public function validate( $new_data, $current_data, $api_only = false ) {
		$this->errors = [];
		try {
			if ( ! $api_only ) {

				$this->validate_accepts_marketing_checkbox_text( $new_data, $current_data );

				$this->validate_api_key( $new_data, $current_data );

				$this->validate_api_url( $new_data, $current_data );

				$this->validate_ab_cart_wait_time( $new_data, $current_data );

			}

			if ( empty( $this->errors ) ) {
				$this->validate_changing_api_details( $new_data, $current_data );
			}

			return $this->errors;
		} catch ( Throwable $t ) {
			$this->logger->error(
				'Admin Settings: There was an issue validating the ActiveCampaign developer API.',
				[
					'message'          => $t->getMessage(),
					'suggested_action' => 'Please verify your API key on the ActiveCampaign developer page.',
					'new data'         => $new_data,
					'current_data'     => $current_data,
					'ac_code'          => 'ASV_99',
				]
			);
		}
	}

	/**
	 * Validates the accepts marketing checkbox text.
	 *
	 * @param array $new_data     The array of new data.
	 * @param array $current_data The array of existing data.
	 */
	private function validate_accepts_marketing_checkbox_text( $new_data, $current_data ) {
		/**
		 * If the Optin Checkbox Text isn't set in the DB nor the request, or the user is trying to set it
		 * to an empty string.
		 */
		if (
			( ! isset( $new_data['optin_checkbox_text'] ) && ! isset( $current_data['optin_checkbox_text'] ) ) ||
			( isset( $new_data['optin_checkbox_text'] ) && '' === $new_data['optin_checkbox_text'] )
		) {
			$this->errors[] = 'Oops! Looks like you didn&#39;t provide any checkbox text.';
		}
	}

	/**
	 * Validates the api key text.
	 *
	 * @param array $new_data     The array of new data.
	 * @param array $current_data The array of existing data.
	 */
	private function validate_api_key( $new_data, $current_data ) {
		/**
		 * If the API Key isn't set in the DB nor the request, or the user is trying to set it
		 * to an empty string.
		 */
		if (
			( ! isset( $new_data['api_key'] ) && ! isset( $current_data['api_key'] ) ) ||
			( isset( $new_data['api_key'] ) && '' === $new_data['api_key'] )
		) {
			$this->errors[] = 'The API Key is required and cannot be an empty string.';
		}
	}

	/**
	 * Validates the api url text.
	 *
	 * @param array $new_data     The array of new data.
	 * @param array $current_data The array of existing data.
	 */
	private function validate_api_url( $new_data, $current_data ) {
		/**
		 * If the API URL isn't set in the DB nor the request, or the user is trying to set it
		 * to an empty string.
		 */
		if (
			( ! isset( $new_data['api_url'] ) && ! isset( $current_data['api_url'] ) ) ||
			( isset( $new_data['api_url'] ) && '' === $new_data['api_url'] )
		) {
			$this->errors[] = 'The API URL is required and cannot be an empty string.';
		}
	}

	/**
	 * Validates the ab cart wait time.
	 *
	 * @param array $new_data     The array of new data.
	 * @param array $current_data The array of existing data.
	 */
	private function validate_ab_cart_wait_time( $new_data, $current_data ) {
		/**
		 * If the Abandoned Cart Wait Time isn't set in the DB nor the request, or the user is trying to set it
		 * to an empty string.
		 */
		if (
			( ! isset( $new_data['abcart_wait'] ) && ! isset( $current_data['abcart_wait'] ) ) ||
			( isset( $new_data['abcart_wait'] ) && '' === $new_data['abcart_wait'] )
		) {
			$this->errors[] = 'The Abandoned Cart Wait Time is required and cannot be an empty string.';
		}
	}

	/**
	 * Validate that the site URL matches the externalid.
	 *
	 * @param array $response_data Decoded guzzle response data.
	 */
	private function validate_externalid_matches_site_url( $response_data ) {
		foreach ( $response_data['connections'] as $key => $value ) {
			if ( get_site_url() === $response_data['connections'][ $key ]['externalid'] ) {
				return;
			}
		}
		$this->errors[] = 'ACTION NEEDED: Please connect your WooCommerce store in your ActiveCampaign account first.';
	}

	/**
	 * Validates that the changing api credentials still work.
	 *
	 * @param array $new_data     The array of new data.
	 * @param array $current_data The array of existing data.
	 */
	private function validate_changing_api_details( $new_data, $current_data ) {
		$api_url_set_first_time = isset( $new_data['api_url'] ) && ! isset( $current_data['api_url'] );
		$api_key_set_first_time = isset( $new_data['api_key'] ) && ! isset( $current_data['api_key'] );

		$api_url_changing = ( isset( $new_data['api_url'] ) && isset( $current_data['api_url'] ) ) && // both are set
							$new_data['api_url'] !== $current_data['api_url'];                        // and changing

		$api_key_changing = ( isset( $new_data['api_key'] ) && isset( $current_data['api_key'] ) ) && // both are set
							$new_data['api_key'] !== $current_data['api_key'];                        // and changing

		$both_api_key_and_url_set = isset( $new_data['api_url'] ) && isset( $new_data['api_key'] );

		/**
		 * If the API Url/Key is being set for the first time, or is changing.
		 */
		if (
			(
				$api_url_set_first_time ||
				$api_key_set_first_time ||
				$api_url_changing ||
				$api_key_changing
			) &&
			$both_api_key_and_url_set
		) {
			$old_api_key = $this->client->get_api_key();
			$old_api_uri = $this->client->get_api_uri();

			try {
				// Temporarily set new credentials
				$this->client->set_api_key( $new_data['api_key'] );
				$this->client->set_api_uri( $new_data['api_url'] );
				$this->client->configure_client();

				// Get existing connections
				$this->connection_repository->find_current();
			} catch ( Resource_Not_Found $e ) {
				$this->log->error( (string) $e );
				$this->errors[] = 'ACTION NEEDED: Please connect your WooCommerce store in your ActiveCampaign account first.';
			} catch ( ClientException $e ) {
				$this->log->error( (string) $e );
				$this->errors[] = 'Either the API Url or API Key is invalid.';
			} catch ( GuzzleException $e ) {
				$this->log->error( (string) $e );
				$this->errors[] = 'Something went wrong while authenticating, please try again.';
			} catch ( Exception $e ) {
				$this->log->error( (string) $e );
				$this->errors[] = 'Something went wrong while authenticating, please try again.';
			} catch ( Throwable $t ) {
				$this->log->error( (string) $t );
				$this->errors[] = 'Something went wrong while authenticating, please try again.';
			} finally {
				// Restore old credentials
				$this->client->set_api_key( $old_api_key );
				$this->client->set_api_uri( $old_api_uri );
				$this->client->configure_client();
			}
		}
	}
}
