<?php

/**
 * The file that defines the Abandoned Cart Functions.
 *
 * @link       https://www.activecampaign.com/
 * @since      1.x
 *
 * @package    Activecampaign_For_Woocommerce
 * @subpackage Activecampaign_For_Woocommerce/includes/abandoned_carts
 */

use Activecampaign_For_Woocommerce_Customer_Utilities as Customer_Utilities;
use Activecampaign_For_Woocommerce_Logger as Logger;

/**
 * The Order_Finished Event Class.
 *
 * @since      1.0.0
 * @package    Activecampaign_For_Woocommerce
 * @subpackage Activecampaign_For_Woocommerce/includes/events
 * @author     acteamintegrations <team-integrations@activecampaign.com>
 */
trait Activecampaign_For_Woocommerce_Abandoned_Cart_Utilities {

	/**
	 * Get an abandoned cart by row id.
	 *
	 * @param int $id The row id.
	 *
	 * @return array|bool|object|null
	 */
	public function get_abandoned_cart_by_row_id( $id ) {
		global $wpdb;
		$logger = new Logger();
		try {
			// Get the expired carts from our table
			$abandoned_cart = $wpdb->get_results(
			// phpcs:disable
				$wpdb->prepare( 'SELECT id, synced_to_ac, last_access_time, customer_ref_json, cart_ref_json, cart_totals_ref_json, removed_cart_contents_ref_json, activecampaignfwc_order_external_uuid, abandoned_date 
					FROM
						`' . $wpdb->prefix . ACTIVECAMPAIGN_FOR_WOOCOMMERCE_TABLE_NAME . '`
					WHERE
						id = %s
						AND order_date IS NULL',
					$id
				)
			// phpcs:enable
			);

			$activecampaign_for_woocommerce_settings = get_option( ACTIVECAMPAIGN_FOR_WOOCOMMERCE_DB_SETTINGS_NAME );
			$activecampaign_for_woocommerce_settings = stripslashes_deep( $activecampaign_for_woocommerce_settings );

			if ( isset( $activecampaign_for_woocommerce_settings['abcart_wait'] ) && ! empty( $activecampaign_for_woocommerce_settings['abcart_wait'] ) ) {
				$this->expire_time = $activecampaign_for_woocommerce_settings['abcart_wait'];
			}

			if ( $wpdb->last_error ) {
				$logger->warning(
					'Abandonment sync: There was an error getting results for abandoned cart records from the database.',
					[
						'wpdb_last_error' => $wpdb->last_error,
					]
				);
			}

			if ( ! empty( $abandoned_cart ) ) {
				// abandoned carts found
				return $abandoned_cart;
			} else {
				// no abandoned carts
				return false;
			}
		} catch ( Throwable $t ) {
			$logger->warning(
				'Abandonment Sync: There was an error with preparing or getting abandoned cart results. This record may have been fulfilled as an order.',
				[
					'message' => $t->getMessage(),
					'trace'   => $logger->clean_trace( $t->getTrace() ),
				]
			);
		}
	}

	/**
	 * Checks for an existing order using our metadata on orders
	 *
	 * @param string $externalcheckout_id The external checkoutid.
	 *
	 * @return string|bool
	 */
	public function find_existing_wc_order( $externalcheckout_id ) {
		global $wpdb;

		if ( ! empty( $externalcheckout_id ) ) {
			$wc_post_id = $wpdb->get_var(
			// phpcs:disable
				$wpdb->prepare(
					'SELECT post_id
					FROM
						`' . $wpdb->prefix . 'postmeta`
					WHERE
						(meta_key = %s OR meta_key = %s OR meta_key = %s) AND meta_value = %s;',
					'activecampaign_for_woocommerce_externalcheckoutid',
					'activecampaign_for_woocommerce_external_checkout_id',
					'activecampaign_for_woocommerce_persistent_cart_id',
					$externalcheckout_id
				)
			// phpcs:enable
			);
			if ( ! empty( $wc_post_id ) ) {
				return $wc_post_id;
			}
		}

		return false;
	}

	/**
	 * Delete an abandoned cart by order object.
	 *
	 * @param     WC_Order|null $order The order object.
	 *
	 * @return bool
	 */
	public function delete_abandoned_cart_by_order( $order = null ) {
		$customer_util = new Customer_Utilities();
		$logger        = new Logger();
		if ( isset( $order ) && ! empty( $order ) ) {
			$customer_id = $customer_util->get_customer_id( $order );
		}

		if ( empty( $customer_id ) ) {
			$logger->warning(
				'Abandoned Cart: Could not delete the abandoned cart from the database by order. No valid order passed or no customer ID provided.',
				[
					'passed data' => $order,
					'customer_id' => $customer_id,
				]
			);

			return false;
		}

		$this->delete_abandoned_cart_by_filter( 'customer_id', $customer_id );
		return true;
	}

	/**
	 * Delete an abandoned cart by customer.
	 *
	 * @return bool
	 */
	public function delete_abandoned_cart_by_customer() {
		$customer_util = new Customer_Utilities();
		$customer_id   = $customer_util->get_customer_id();
		$logger        = new Logger();

		if ( empty( $customer_id ) ) {
			$logger->warning(
				'Abandoned Cart: Could not delete the abandoned cart from the database by customer. No valid order passed or no customer ID found.',
				[
					'customer_id' => $customer_id,
				]
			);

			return false;
		}

		$this->delete_abandoned_cart_by_filter( 'customer_id', $customer_id );
		return true;
	}

	/**
	 * Deletes an abandoned cart record based on a filter value pair.
	 *
	 * @param string $filter_name The filter column name to use.
	 * @param string $filter_value The data passed to perform the deletion.
	 *
	 * @return bool
	 */
	public function delete_abandoned_cart_by_filter( $filter_name, $filter_value ) {
		global $wpdb;
		$logger = new Logger();

		if (
			! isset( $filter_name, $filter_value ) ||
			empty( $filter_name ) ||
			empty( $filter_value )
		) {
			$logger->warning(
				'Abandoned Cart: Deletion name or value was not set.',
				[
					$filter_name => $filter_value,
				]
			);

			return false;
		}

		try {
			$table_name = $wpdb->prefix . ACTIVECAMPAIGN_FOR_WOOCOMMERCE_TABLE_NAME;

			$wpdb->delete(
				$table_name,
				[
					$filter_name => $filter_value,
				]
			);

			if ( ! empty( $wpdb->last_error ) ) {
				$logger->warning(
					'Abandoned cart: There was an error removing the abandoned cart record.',
					[
						$filter_name      => $filter_value,
						'wpdb_last_error' => $wpdb->last_error,
					]
				);

				return false;
			}

			return true;

		} catch ( Throwable $t ) {
			$logger->warning(
				'Abandoned cart: could not delete the abandoned cart entry.',
				[
					'message'  => $t->getMessage(),
					'session'  => isset( wc()->session ) && self::validate_object( wc()->session, 'get_session_data' ) ? wc()->session->get_session_data() : null,
					'customer' => isset( wc()->customer ) && self::validate_object( wc()->customer, 'get_data' ) ? wc()->customer->get_data() : null,
					'trace'    => $logger->clean_trace( $t->getTrace() ),
				]
			);

			return false;
		}
	}

	/**
	 * This schedules the recurring event and verifies it's still set up
	 */
	public function schedule_recurring_abandon_cart_task() {
		// If not scheduled, set up our recurring event
		$logger = new Logger();

		try {
			if ( ! wp_next_scheduled( 'activecampaign_for_woocommerce_cart_updated_recurring_event' ) ) {
				wp_schedule_event( time() + 10, 'hourly', 'activecampaign_for_woocommerce_cart_updated_recurring_event' );
			} else {
				if ( function_exists( 'wp_get_scheduled_event' ) ) {
					$logger->debug_excess(
						'Recurring cron already scheduled',
						[
							'time_now' => time(),
							'myevent'  => wp_get_scheduled_event( 'activecampaign_for_woocommerce_cart_updated_recurring_event' ),
						]
					);
				}
			}
		} catch ( Throwable $t ) {
			$logger->debug(
				'There was an issue scheduling the abandoned cart event.',
				[
					'message' => $t->getMessage(),
				]
			);
		}
	}

	/**
	 * Send the table data to the database
	 *
	 * @param   Array       $data The data.
	 * @param     null|string $stored_id The stored id of the customer.
	 */
	public function store_abandoned_cart_data( $data, $stored_id = null ) {
		global $wpdb;
		$logger = new Logger();
		try {
			if ( ! is_null( $stored_id ) && ! empty( $stored_id ) ) {
				$wpdb->update(
					$wpdb->prefix . ACTIVECAMPAIGN_FOR_WOOCOMMERCE_TABLE_NAME,
					$data,
					[
						'id' => $stored_id,
					]
				);

			} else {
				$wpdb->insert(
					$wpdb->prefix . ACTIVECAMPAIGN_FOR_WOOCOMMERCE_TABLE_NAME,
					$data
				);

				$stored_id = null;

				if ( ! empty( $wpdb->insert_id ) ) {
					$stored_id = $wpdb->insert_id;
				}
			}

			if ( $wpdb->last_error ) {
				$logger->warning(
					'Abandoned cart: There was an error creating/updating an abandoned cart record.',
					[
						'wpdb_last_error' => $wpdb->last_error,
						'data'            => $data,
						'stored_id'       => $stored_id,
					]
				);
			}

			return $stored_id;
		} catch ( Throwable $t ) {
			$logger->warning(
				'Abandoned cart: There was an error attempting to save this abandoned cart to the database.',
				[
					'message'       => $t->getMessage(),
					'stored_id (if set this was an update)' => $stored_id,
					'customer_data' => $data,
					'trace'         => $logger->clean_trace( $t->getTrace() ),
				]
			);
		}
	}

	/**
	 * Generate the externalcheckoutid hash which
	 * is used to tie together pending and complete
	 * orders in Hosted (so we don't create duplicate orders).
	 * This has been modified to accurately work with woo commerce not independently
	 * tracking cart session vs order session
	 *
	 * @param     string $customer_id     The unique WooCommerce cart session ID.
	 * @param     string $customer_email     The guest customer's email address.
	 * @param     string $order_external_uuid The UUID value.
	 *
	 * @return string The hash used as the externalcheckoutid value
	 */
	public function generate_externalcheckoutid( $customer_id, $customer_email, $order_external_uuid = null ) {
		// Get the custom session if it exists
		if ( is_null( $order_external_uuid ) ) {
			$order_external_uuid = $this->get_or_generate_uuid();
		}

		// Generate the hash we'll use and return it
		return md5( $customer_id . $customer_email . $order_external_uuid );
	}

	/**
	 * Get the UUID from the session cart or generate a UUID for the customer abandoned cart.
	 *
	 * @return string
	 */
	public function get_or_generate_uuid() {
		if (
			isset( wc()->session ) &&
			self::validate_object( wc()->session, 'get' ) &&
			wc()->session->get( 'activecampaignfwc_order_external_uuid' )
		) {
			$uuid = wc()->session->get( 'activecampaignfwc_order_external_uuid' );
		} else {
			$uuid = uniqid( '', true );

			if ( isset( wc()->session ) ) {
				wc()->session->set( 'activecampaignfwc_order_external_uuid', $uuid );
			}
		}

		return $uuid;
	}

	/**
	 * Resets our UUID and cart ID on customer session
	 */
	public function cleanup_session_activecampaignfwc_order_external_uuid() {
		$logger = new Logger();
		if ( isset( wc()->session ) && $this->get_or_generate_uuid() ) {
			wc()->session->set( 'activecampaignfwc_order_external_uuid', '' );
			wc()->session->set( 'activecampaign_abandoned_cart_id', '' );

			$logger->debug(
				'Reset the activecampaignfwc_order_external_uuid & activecampaign_abandoned_cart_id on cart',
				[
					'activecampaignfwc_order_external_uuid' => wc()->session->get( 'activecampaignfwc_order_external_uuid' ),
					'activecampaign_abandoned_cart_id' => wc()->session->get( 'activecampaign_abandoned_cart_id', '' ),
				]
			);
		}
	}

	/**
	 * Cleans up any synced records older than 4 weeks that are not orders. Send the number otherwise it will by default delete just synced records.
	 */
	private function clean_all_old_abandoned_carts() {
		global $wpdb;
		try {
			// wipe time is anything 4 weeks old
			$wipe_time = 40320;

			// Get the outdated carts from our table
			$expire_datetime      = gmdate( 'Y-m-d H:i:s', strtotime( '-' . $wipe_time . ' minutes' ) );
			$synced_to_ac_implode = implode(
				',',
				[
					self::STATUS_ABANDONED_CART_AUTO_SYNCED,
					self::STATUS_ABANDONED_CART_AUTO_SYNCED,
					self::STATUS_ABANDONED_CART_MANUAL_SYNCED,
					self::STATUS_ABANDONED_CART_RECOVERED,
					self::STATUS_ABANDONED_CART_FAILED_WAIT,
					self::STATUS_ABANDONED_CART_FAILED_2,
					self::STATUS_ABANDONED_CART_NETWORK_FAIL_RETRY,
					self::STATUS_ABANDONED_CART_NETWORK_FAIL_PERM,
				]
			);

			// phpcs:disable
			$delete_count = $wpdb->query(
				'DELETE FROM ' . $wpdb->prefix . ACTIVECAMPAIGN_FOR_WOOCOMMERCE_TABLE_NAME .
				' WHERE (last_access_time < "' . $expire_datetime . '" AND synced_to_ac IN (' . $synced_to_ac_implode . ') ) OR (last_access_time < "' . $expire_datetime . '" AND synced_to_ac = 1 AND order_date IS NULL)'
			);

			// phpcs:enable
			if ( ! empty( $delete_count ) ) {
				$this->logger->debug( $delete_count . ' old abandoned cart records deleted.' );

				if ( $wpdb->last_error ) {
					$this->logger->error(
						'A database error was encountered while attempting to delete old abandoned cart records.',
						[
							'wpdb_last_error' => $wpdb->last_error,
							'ac_code'         => 'RASC_952',
						]
					);
				}
			}
		} catch ( Throwable $t ) {
			$this->logger->error(
				'An exception was encountered while preparing or getting abandoned cart results.',
				[
					'message' => $t->getMessage(),
					'ac_code' => 'RASC_962',
					'trace'   => $this->logger->clean_trace( $t->getTrace() ),
				]
			);
		}
	}

	/**
	 * Cleans up any synced records older than 2 weeks that are not orders. Send the number otherwise it will by default delete just synced records.
	 */
	private function clean_old_synced_abandoned_carts() {
		global $wpdb;
		try {
			// wipe time is anything 2 weeks old
			$wipe_time = 20160;

			// Get the outdated carts from our table
			$expire_datetime = gmdate( 'Y-m-d H:i:s', strtotime( '-' . $wipe_time . ' minutes' ) );

			$synced_to_ac_implode = implode( ',', [ self::STATUS_ABANDONED_CART_MANUAL_SYNCED, self::STATUS_ABANDONED_CART_AUTO_SYNCED ] );
			// phpcs:disable
			$delete_count = $wpdb->query(
				'DELETE FROM ' . $wpdb->prefix . ACTIVECAMPAIGN_FOR_WOOCOMMERCE_TABLE_NAME .
				' WHERE (last_access_time < "' . $expire_datetime . '" AND synced_to_ac IN (' . $synced_to_ac_implode . ') ) OR (last_access_time < "' . $expire_datetime . '" AND synced_to_ac = 1 AND order_date IS NULL)'
			);

			// phpcs:enable
			if ( ! empty( $delete_count ) ) {
				$this->logger->debug( $delete_count . ' old abandoned cart records deleted.' );

				if ( $wpdb->last_error ) {
					$this->logger->warning(
						'Abandonment sync: There was an error deleting old abandoned cart records.',
						[
							'wpdb_last_error' => $wpdb->last_error,
							'ac_code'         => 'RASC_997',
						]
					);
				}
			}
		} catch ( Throwable $t ) {
			$this->logger->error(
				'An exception was thrown while preparing or getting abandoned cart results.',
				[
					'message' => $t->getMessage(),
					'ac_code' => 'RASC_1007',
					'trace'   => $this->logger->clean_trace( $t->getTrace() ),
				]
			);
		}
	}

	/**
	 * Parse the results of the all of a product's categories and return all as separated list
	 *
	 * @param WC_Product $product The WC Product.
	 *
	 * @return string|null
	 */
	public function get_product_category( $product ) {
		$logger = new Logger();
		try {
			if ( self::validate_object( $product, 'get_id' ) ) {
				$terms = get_the_terms( $product->get_id(), 'product_cat' );
			}
		} catch ( Throwable $t ) {
			$logger->warning(
				'Could not get the terms/categories for a product.',
				[
					'message' => $t->getMessage(),
					'product' => $product,
				]
			);
		}

		$cat_list = [];
		try {
			// go through the categories and make a named list
			if ( ! empty( $terms ) && is_array( $terms ) ) {
				foreach ( $terms as $term ) {
					$product_cat_id   = $term->term_id;
					$product_cat_name = $term->name;
					if ( $product_cat_id >= 0 && ! empty( $product_cat_name ) ) {
						$cat_list[] = $product_cat_name;
					} else {
						$logger->warning(
							'A product category attached to this product does not have a valid category and/or name.',
							[
								'product_id' => self::validate_object( $product, 'get_id' ) ? $product->get_id() : null,
								'term_id'    => $term->term_id,
								'term_name'  => $term->name,
							]
						);
					}
				}
			}
		} catch ( Throwable $t ) {
			$logger->warning(
				'There was an error getting all product categories.',
				[
					'terms'          => $terms,
					'product_id'     => self::validate_object( $product, 'get_id' ) ? $product->get_id() : null,
					'trace'          => $logger->clean_trace( $t->getTrace() ),
					'thrown_message' => $t->getMessage(),
				]
			);
		}

		if ( ! empty( $cat_list ) ) {
			// Convert to a comma separated string
			return implode( ', ', $cat_list );
		}

		return null;
	}


}
