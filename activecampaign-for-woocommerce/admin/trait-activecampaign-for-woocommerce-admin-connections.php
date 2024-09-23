<?php

/**
 * The admin status page specific functionality of the plugin.
 *
 * @link       https://www.activecampaign.com/
 * @since      1.8.0
 *
 * @package    Activecampaign_For_Woocommerce
 * @subpackage Activecampaign_For_Woocommerce/admin
 */

use Activecampaign_For_Woocommerce_Logger as Logger;
use Activecampaign_For_Woocommerce_Connection as Connection;

/**
 * The admin-specific functionality of the plugin.
 *
 * Defines the plugin name, version, and two examples hooks for how to
 * enqueue the admin-specific stylesheet and JavaScript.
 *
 * @package    Activecampaign_For_Woocommerce
 * @subpackage Activecampaign_For_Woocommerce/admin
 * @author     acteamintegrations <team-integrations@activecampaign.com>
 */
trait Activecampaign_For_Woocommerce_Admin_Connections {
	use Activecampaign_For_Woocommerce_Data_Validation;
	/**
	 * Initialize the class and set its properties.
	 */
	public function __construct() {}

	/**
	 * Gets all WooCommerce connections.
	 */
	public function get_all_connections() {
		$connections = get_transient( 'activecampaign_for_woocommerce_all_connections' );

		if ( false === $connections ) {
			$connections = $this->connection_repository->find_all_by_filter( 'service', 'woocommerce' );
			set_transient( 'activecampaign_for_woocommerce_all_connections', $connections, 3600 );
		}

		return $connections;
	}

	/**
	 * Ajax call. Update an existing connection.
	 */
	public function update_existing_connection() {
		// This is to update an existing connection from the connection list
		if ( ! $this->validate_request_nonce( 'activecampaign_for_woocommerce_settings_form' ) ) {
			wp_send_json_error( 'The nonce appears to be invalid.' );
		}

		delete_transient( 'activecampaign_for_woocommerce_all_connections' );

		// $post_data = wp_unslash( $_POST );
		$post_data = $this->extract_post_data();

		if ( isset( $post_data['connection_id'] ) && ! empty( $post_data['connection_id'] ) ) {

			$connection = $this->get_connection_from_existing( $post_data['connection_id'], $post_data['connection_external_id'] ); // update_existing_connection

			if ( $connection->get_id() === $post_data['connection_id'] ) {

				$connection->set_externalid( $post_data['connection_external_id'] );
				$connection->set_name( $post_data['connection_integration_name'] );
				$connection->set_link_url( $post_data['connection_integration_link'] );

				$connection = $this->connection_repository->update( $connection );

				if ( self::validate_object( $connection, 'serialize_to_array' ) ) {
					delete_transient( 'activecampaign_for_woocommerce_all_connections' );
					wp_send_json_success( $connection->serialize_to_array() );
				} elseif ( isset( $connection['type'] ) && 'error' === $connection['type'] ) {
					wp_send_json_error(
						[
							'type'    => 'error',
							'message' => 'There was an error updating the connection. Please check your logs for details. (' . $connection['message'] . ')',
						]
					);
				}
			} else {
				$existing_connection = $this->get_connection_from_existing( $post_data['connection_external_id'] );

				if ( ! $existing_connection ) {
					wp_send_json_error( $existing_connection );
				}
			}
		}
	}

	/**
	 * Gets the current connection from the existing list of connections.
	 */
	public function get_connection_from_existing( $connection_id = null, $connection_external_id = null ) {
		$connections = $this->get_all_connections(); // get_connection_from_existing
		foreach ( $connections as $co ) {
			if ( $co->get_id() && $co->get_id() === $connection_id ) {
				return $co;
			}

			if ( $co->get_externalid() && $co->get_externalid() === $connection_external_id ) {
				return $co;
			}
		}

		return false;
	}

	/**
	 * Ajax call. Add a new connection and select it.
	 */
	public function add_new_connection() {
		$logger = new Logger();

		if ( ! $this->validate_request_nonce( 'activecampaign_for_woocommerce_settings_form' ) ) {
			wp_send_json_error( 'The nonce appears to be invalid.' );
		}

		delete_transient( 'activecampaign_for_woocommerce_all_connections' );

		$post_data = $this->extract_post_data();
		$settings  = $this->get_local_settings();

		if (
			! empty( $settings['api_url'] ) &&
			! empty( $settings['api_key'] )
		) {
			$existing_connection = false;
			delete_transient( 'activecampaign_for_woocommerce_all_connections' );

			$connections = $this->get_all_connections(); // add new connection call

			foreach ( $connections as $co ) {
				if ( $co->get_externalid() && $co->get_externalid() === $post_data['connection_external_id'] ) {
					$existing_connection = true;
					$connection          = $co;
					break;
				}
			}

			if ( ! $existing_connection ) {
				$logger->notice( 'Existing connection does not exist in hosted, make a new one' );
				// We don't have a connection, let's find an existing one

				// If no connection add a new one
				$new_connection = $this->set_connection_from_data( null, $post_data );

				$connection = $this->connection_repository->create( $new_connection );
				$logger->alert(
					'create new connection',
					[
						$connection->serialize_to_array(),
					]
				);
			} elseif ( isset( $connection ) && $connection->get_externalid() ) {
				wp_send_json_error(
					[
						'type'    => 'error',
						'message' => 'This URL already exists. Only one connection is allowed per unique Site URL. Please refresh the page or update the existing connection from the connection list.',
					]
				);

			}

			if ( isset( $connection ) && $connection->get_id() && $connection->get_externalid() ) {
				delete_option( 'activecampaign_for_woocommerce_connection_health_check_last_run' );
				$this->update_storage_from_connection( $connection );
				do_action( 'activecampaign_for_woocommerce_run_sync_connection' );
				wp_send_json_success( 'Connection saved: ' . wp_json_encode( $connection->serialize_to_array() ) );
			} else {
				wp_send_json_error( 'Connection could not be created.' );
			}
		} else {
			wp_send_json_error( 'No API configuration found in settings.' );
		}
	}

	/**
	 * Ajax call. Select an existing connection.
	 */
	public function select_connection() {
		$logger = new Logger();

		if ( ! $this->validate_request_nonce( 'activecampaign_for_woocommerce_settings_form' ) ) {
			wp_send_json_error( 'The nonce appears to be invalid.' );
		}

		delete_transient( 'activecampaign_for_woocommerce_all_connections' );

		$post_data = $this->extract_post_data();
		$settings  = $this->get_local_settings();

		if (
			! empty( $settings['api_url'] ) &&
			! empty( $settings['api_key'] ) &&
			isset( $post_data['connection_external_id'] )
		) {

			$connection = $this->set_connection_from_data( null, $post_data );

			if ( isset( $connection ) && $connection->get_id() && $connection->get_externalid() ) {
				delete_option( 'activecampaign_for_woocommerce_connection_health_check_last_run' );
				$this->update_storage_from_connection( $connection );

				wp_send_json_success( 'Connection saved.' );
			}
		}
		wp_send_json_error( 'Something went wrong with setting the connection.' );
	}

	/**
	 * Ajax call. Delete an existing connection.
	 * This is suspended until we have a better concept of how connections should be deleted.
	 *
	 * @suspended
	 */
	public function delete_existing_connection() {
		$logger = new Logger();

		if ( ! $this->validate_request_nonce( 'activecampaign_for_woocommerce_settings_form' ) ) {
			wp_send_json_error( 'The nonce appears to be invalid.' );
		}
		delete_transient( 'activecampaign_for_woocommerce_all_connections' );
		$post_data = $this->extract_post_data();

		$connection = $this->connection_repository->find_by_id( $post_data['connection_id'] );

		if ( isset( $connection ) && $post_data['connection_id'] === $connection->get_id() ) {
			$result = $this->connection_repository->delete( $connection->get_id() );

			// There is an error happening here
			if ( is_array( $result ) && isset( $result['type'] ) && 'error' === $result['type'] ) {
				wp_send_json_error(
					[
						'type'    => 'error',
						'message' => 'There was an error deleting the connection. Please check your logs for details.',
					]
				);
			} else {
				wp_send_json_success( 'Connection deleted' );
			}
		}
	}

	/**
	 * Sets up a connection from passed in data.
	 *
	 * @param null|Activecampaign_For_Woocommerce_Connection $connection The connection or a null field.
	 * @param array                                          $connection_data The connection data.
	 *
	 * @return Activecampaign_For_Woocommerce_Connection
	 */
	private function set_connection_from_data( $connection, $connection_data ) {
		if ( ! isset( $connection ) ) {
			$connection = new Connection();
		}

		delete_transient( 'activecampaign_for_woocommerce_all_connections' );

		try {
			$connection->set_service( 'woocommerce' );

			if ( isset( $connection_data['connection_id'] ) && ! empty( $connection_data['connection_id'] ) ) {
				$connection->set_id( $connection_data['connection_id'] );
			}

			if ( isset( $connection_data['connection_external_id'] ) && ! empty( $connection_data['connection_external_id'] ) ) {
				$connection->set_externalid( $connection_data['connection_external_id'] );
			} else {
				$connection->set_externalid( get_site_url() );
			}

			if ( isset( $connection_data['connection_integration_name'] ) && ! empty( $connection_data['connection_integration_name'] ) ) {
				$connection->set_name( $connection_data['connection_integration_name'] );
			} else {
				$connection->set_name( get_option( 'blogname' ) );
			}

			if ( isset( $connection_data['connection_link_url'] ) && ! empty( $connection_data['connection_link_url'] ) ) {
				$connection->set_link_url( $connection_data['connection_link_url'] );
			} else {
				$connection->set_link_url( get_home_url() );
			}

			if ( isset( $connection_data['connection_integration_link'] ) && ! empty( $connection_data['connection_integration_link'] ) ) {
				$connection->set_link_url( $connection_data['connection_integration_link'] );
			} else {
				$connection->set_link_url( get_home_url() );
			}

			if ( isset( $connection_data['connection_logo_url'] ) && ! empty( $connection_data['connection_logo_url'] ) ) {
				$connection->set_logo_url( $connection_data['connection_logo_url'] );
			} else {
				$connection->set_logo_url( '/app/images/woocommerce-logo.png' );
			}

			return $connection;
		} catch ( Throwable $t ) {
			$logger = new Logger();
			$logger->error(
				'The connection could not be set from the provided field data.',
				[
					'message'          => $t->getMessage(),
					'suggested_action' => 'Please address the errors stated in the logs and try again. If this problem repeats please contact ActiveCampaign support.',
					'ac_code'          => 'ADCO_293',
				]
			);
		}
	}

}
