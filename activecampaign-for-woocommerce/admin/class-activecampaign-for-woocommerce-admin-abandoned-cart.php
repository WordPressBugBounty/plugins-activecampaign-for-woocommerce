<?php

/**
 * The admin abandoned cart page specific functionality of the plugin.
 *
 * @link       https://www.activecampaign.com/
 * @since      1.8.0
 *
 * @package    Activecampaign_For_Woocommerce
 * @subpackage Activecampaign_For_Woocommerce/admin
 */

use Activecampaign_For_Woocommerce_Logger as Logger;

/**
 * The admin-specific functionality of the plugin.
 *
 * Defines the plugin name, version, and two examples hooks for how to
 * enqueue the admin-specific stylesheet and JavaScript.
 *
 * @package    Activecampaign_For_Woocommerce
 * @subpackage Activecampaign_For_Woocommerce/admin/historical_sync
 * @author     acteamintegrations <team-integrations@activecampaign.com>
 */
trait Activecampaign_For_Woocommerce_Admin_Abandoned_Cart {
	use Activecampaign_For_Woocommerce_Admin_Utilities;

	/**
	 * Fetch the PHP template file that is used for the admin abandoned cart page.
	 *
	 * @since    1.3.7
	 */
	public function fetch_abandoned_cart_page() {
		wp_enqueue_script( $this->plugin_name . 'abandoned-cart' );
		require_once plugin_dir_path( __FILE__ )
					 . 'views/activecampaign-for-woocommerce-abandoned-cart-display.php';
	}

	/**
	 * Gets the abandoned carts from our table.
	 *
	 * @param     int $page The page number.
	 *
	 * @return array|object|null
	 */
	public function get_abandoned_carts( $page = 0 ) {
		$logger = new Logger();
		try {
			global $wpdb;

			do_action( 'activecampaign_for_woocommerce_verify_tables' );

			$expire_time                             = 1;
			$limit                                   = 40;
			$offset                                  = $page * $limit;
			$activecampaign_for_woocommerce_settings = get_option( ACTIVECAMPAIGN_FOR_WOOCOMMERCE_DB_SETTINGS_NAME );

			if ( isset( $activecampaign_for_woocommerce_settings['abcart_wait'] ) && ! empty( $activecampaign_for_woocommerce_settings['abcart_wait'] ) ) {
				$expire_time = $activecampaign_for_woocommerce_settings['abcart_wait'];
			}

			$expire_datetime = new DateTime( 'now -' . $expire_time . ' hours', new DateTimeZone( 'UTC' ) );

			$result = $wpdb->get_results(
			// phpcs:disable
				$wpdb->prepare(
					'SELECT 
				       id, 
				       order_date,
				       abandoned_date, 
				       synced_to_ac,
				       customer_id,
				       customer_email, 
				       customer_first_name, 
				       customer_last_name, 
				       last_access_time,
				       activecampaignfwc_order_external_uuid, 
				       cart_ref_json,
				       customer_ref_json,
				       ( last_access_time < "' . $expire_datetime->format( 'Y-m-d H:i:s' ) . '" OR last_access_time < str_to_date("' . $expire_datetime->format( 'Y-m-d H:i:s' ) . '", "Y-m-d H:i:s") ) AS ready_state
	                FROM `' . $wpdb->prefix . ACTIVECAMPAIGN_FOR_WOOCOMMERCE_TABLE_NAME . '` 
	                WHERE
	                (order_date IS NULL OR abandoned_date IS NOT NULL) AND 
	                last_access_time IS NOT NULL AND 
	                (
	                    (
	                        synced_to_ac >= 20 AND synced_to_ac <= 29
	                    ) 
	                    OR 
	                    (
	                        synced_to_ac = 1 OR synced_to_ac = 0
                        )
                    )
	                LIMIT %d,%d',
					[ $offset, $limit ]
				), OBJECT
			// phpcs:enable
			);

			if ( $wpdb->last_error ) {
				$logger->warning(
					'Save abandoned cart command: There was an error selecting the id for a customer abandoned cart record.',
					[
						'wpdb_last_error' => $wpdb->last_error,
						'result'          => $result,
					]
				);
			}
			return $result;
		} catch ( Throwable $t ) {
			$logger->warning(
				'There was an issue getting abandoned carts',
				[
					'message'  => $t->getMessage(),
					'function' => 'get_abandoned_carts',
				]
			);
		}
	}
	/**
	 * Get the abandoned carts total.
	 *
	 * @return string|null
	 */
	public function get_total_abandoned_carts() {
		global $wpdb;
		// phpcs:disable

		do_action('activecampaign_for_woocommerce_verify_tables');

		return $wpdb->get_var(
			'SELECT COUNT(id) FROM `' . $wpdb->prefix . ACTIVECAMPAIGN_FOR_WOOCOMMERCE_TABLE_NAME . '` 
			WHERE (order_date IS NULL OR abandoned_date IS NOT NULL) AND last_access_time IS NOT NULL AND ((synced_to_ac >= 20 AND synced_to_ac <= 29) OR synced_to_ac = 1 OR synced_to_ac = 0)
			LIMIT 1000'
		);
		// phpcs:enable
	}

	/**
	 * Get the unsynced abandoned cart total.
	 *
	 * @return string|null
	 */
	public function get_total_abandoned_carts_unsynced() {
		global $wpdb;

		// phpcs:disable
		return $wpdb->get_var(
			'SELECT COUNT(id) FROM `' . $wpdb->prefix . ACTIVECAMPAIGN_FOR_WOOCOMMERCE_TABLE_NAME . '`
			 WHERE (synced_to_ac = 20 OR synced_to_ac = 0) AND order_date IS NULL AND last_access_time IS NOT NULL
			  LIMIT 1000'
		);
		// phpcs:enable
	}

	/**
	 * Triggers abandoned cart sync action
	 */
	public function handle_abandon_cart_sync() {
		do_action( 'activecampaign_for_woocommerce_run_manual_abandonment_sync' );
	}

	/**
	 * Handles the abandoned cart delete function and triggers the manual delete
	 */
	public function handle_abandon_cart_delete() {
		$logger = new Logger();
		try {
			if ( ! $this->validate_request_nonce( 'activecampaign_for_woocommerce_abandoned_form' ) ) {
				wp_send_json_error( 'The nonce appears to be invalid.' );
			}

			$row_id = self::get_request_data( 'rowId' );
			if ( isset( $row_id ) ) {
				do_action( 'activecampaign_for_woocommerce_run_manual_abandonment_delete', $row_id );
			} else {
				// phpcs:disable
				$logger->warning(
					'Invalid request, rowId missing from the delete abandoned cart call:',
					[
						'request' => $_REQUEST,
						'post'    => $_POST,
						'get'     => $_GET,
					]
				);
				// phpcs:enable
				wp_send_json_error( 'No row ID defined.' );
			}
		} catch ( Throwable $t ) {
			$logger->warning(
				'There was an issue deleting an abandoned cart',
				[
					'message'  => $t->getMessage(),
					'function' => 'handle_abandon_cart_delete',
				]
			);
		}
	}

	/**
	 * Handles the abandoned cart sync function and triggers the manual forced sync
	 */
	public function handle_abandon_cart_force_row_sync() {
		$logger = new Logger();
		try {
			if ( ! $this->validate_request_nonce( 'activecampaign_for_woocommerce_abandoned_form' ) ) {
				wp_send_json_error( 'The nonce appears to be invalid.' );
			}

			$row_id = self::get_request_data( 'rowId' );
			if ( isset( $row_id ) ) {
				do_action( 'activecampaign_for_woocommerce_run_force_row_abandonment_sync', $row_id );
			} else {
				// phpcs:disable
				$logger->warning(
					'Invalid request, rowId missing from the force row sync call:',
					[
						'request' => $_REQUEST,
						'post'    => $_POST,
						'get'     => $_GET,
					]
				);
				// phpcs:enable
				wp_send_json_error( 'The request appears to be invalid. The rowId is missing from the request.' );
			}
		} catch ( Throwable $t ) {
			$logger->warning(
				'There was an issue forcing a row sync on an abandoned cart',
				[
					'message'  => $t->getMessage(),
					'function' => 'handle_abandon_cart_force_row_sync',
					'trace'    => $t->getTrace(),
				]
			);
			wp_send_json_error( 'There was an issue processing this request. Check logs for details.' );
		}
	}

}
