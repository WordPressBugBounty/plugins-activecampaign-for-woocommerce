<?php

/**
 * The file that saves the abandoned carts.
 *
 * @link       https://www.activecampaign.com/
 * @since      1.3.2
 *
 * @package    Activecampaign_For_Woocommerce
 */

use Activecampaign_For_Woocommerce_Logger as Logger;
use Activecampaign_For_Woocommerce_User_Meta_Service as User_Meta_Service;
use Activecampaign_For_Woocommerce_Synced_Status_Interface as Synced_Status;

/**
 * Save the cart to a table to keep the record in case it gets abandoned
 *
 * @since      1.0.0
 * @package    Activecampaign_For_Woocommerce
 * @subpackage Activecampaign_For_Woocommerce/includes/commands
 * @author     acteamintegrations <team-integrations@activecampaign.com>
 */
class Activecampaign_For_Woocommerce_Save_Abandoned_Cart_Command implements Synced_Status {
	use Activecampaign_For_Woocommerce_Data_Validation,
		Activecampaign_For_Woocommerce_Synced_Status_Handler,
		Activecampaign_For_Woocommerce_Abandoned_Cart_Utilities;

	/**
	 * The custom ActiveCampaign logger
	 *
	 * @var Activecampaign_For_Woocommerce_Logger
	 */
	private $logger;

	/**
	 * Array of data passed from ajax
	 *
	 * @var Array passed_data
	 */
	private $passed_data;

	/**
	 * Activecampaign_For_Woocommerce_Update_Cart_Command constructor.
	 *
	 * @param     Logger $logger     The logger interface.
	 */
	public function __construct(
		Logger $logger = null
	) {
		if ( ! $logger ) {
			$this->logger = new Logger();
		} else {
			$this->logger = $logger;
		}
	}

	/**
	 * Store the last activity time for the current user.
	 * This is the initialization event which triggers on any cart change.
	 */
	public function init() {
		if ( ! $this->logger ) {
			$this->logger = new Logger();
		}

		// Store the cart
		try {
			$this->prep_abandoned_cart_data();
		} catch ( Throwable $e ) {
			$this->logger->debug( 'Could not prep abandoned cart data' );
		}
		// Schedule single event for a logged in user if there's a cart
		$this->schedule_recurring_abandon_cart_task();
	}

	/**
	 * Store the last activity time for the current user.
	 * This is the initialization event which triggers on any cart change.
	 *
	 * @param     array $data     Data passed from ajax to override the name and email fields.
	 *
	 * @return bool
	 */
	public function init_data( $data ) {
		if ( ! $this->logger ) {
			$this->logger = new Logger();
		}

		// Schedule single event for a logged in user if there's a cart
		if ( ! empty( $data ) ) {
			$this->passed_data = $data;
			try {
				$this->prep_abandoned_cart_data();
			} catch ( Throwable $e ) {
				$this->logger->debug( 'Could not prep abandoned cart data' );
			}
			$this->schedule_recurring_abandon_cart_task();

			return true;
		}

		return false;
	}

	/**
	 * Builds the customer data we need for abandoned cart.
	 *
	 * @return array|string
	 */
	private function build_customer_data() {
		try {
			// Get current customer
			if ( ! empty( wc()->customer->get_id() ) && ! empty( wc()->customer->get_email() ) ) {
				$customer_data               = wc()->customer->get_data();
				$customer_data['id']         = wc()->customer->get_id(); // This is a user id if registered or a UUID if guest
				$customer_data['email']      = wc()->customer->get_email();
				$customer_data['first_name'] = wc()->customer->get_first_name();
				$customer_data['last_name']  = wc()->customer->get_last_name();
			} else {
				// We don't have a real WC customer, get the session customer
				$customer_data = wc()->session->get( 'customer' );

				// Make sure we've set the id
				$customer_data['id'] = wc()->session->get_customer_id();

				// If we have guest data passed in, replace with that
				if ( ! empty( $this->passed_data ) ) {
					$customer_data['email']      = $this->passed_data['customer_email'];
					$customer_data['first_name'] = $this->passed_data['customer_first_name'];
					$customer_data['last_name']  = $this->passed_data['customer_last_name'];
				}

				if ( ! empty( $customer_data['email'] ) ) {
					// Set the customer data for billing
					$customer_data['billing_email'] = $customer_data['email'];
				}

				if ( ! empty( $customer_data['first_name'] ) ) {
					$customer_data['billing_first_name'] = $customer_data['first_name'];
				}

				if ( ! empty( $customer_data['last_name'] ) ) {
					$customer_data['billing_last_name'] = $customer_data['last_name'];
				}
			}

			return $customer_data;
		} catch ( Throwable $t ) {
			$this->logger->error(
				'Abandoned sync: Encountered an error on gathering customer and/or session data for the abandonment sync',
				[
					'message' => $t->getMessage(),
					'trace'   => $this->logger->clean_trace( $t->getTrace() ),
				]
			);
		}
	}

	/**
	 * This adds the cart info to our table.
	 *
	 * @throws Throwable Message.
	 */
	private function prep_abandoned_cart_data() {
		$dt           = new DateTime( 'now', new DateTimeZone( 'UTC' ) );
		$current_user = wp_get_current_user();

		$customer_data = $this->build_customer_data();

		// If the cart is emptied remove the abandoned cart entry and end the function
		if ( wc()->cart->is_empty() ) {
			$this->delete_abandoned_cart_by_filter( 'customer_id', $customer_data['id'] );
			$this->logger->debug(
				'This cart is empty and cannot be saved.',
				[
					wc()->cart->get_cart(),
				]
			);
			return;
		}

		// Get the cart
		$cart                  = wc()->cart->get_cart();
		$removed_cart_contents = wc()->cart->removed_cart_contents;
		$cart_totals           = null;

		try {
			// Calculate the latest totals so the cart totals are accurate
			wc()->cart->calculate_totals();
			$cart_totals = wc()->cart->get_totals();
		} catch ( Throwable $t ) {
			$this->logger->warning(
				'Abandoned sync: Encountered an error on gathering cart totals for the abandonment sync',
				[
					'message' => $t->getMessage(),
					'trace'   => $this->logger->clean_trace( $t->getTrace() ),
				]
			);
		}

		// If we have a customer, do the stuff
		if ( ! empty( $customer_data['email'] ) ) {
			// Step 1 verify we added a table
			do_action( 'activecampaign_for_woocommerce_verify_tables' );

			global $wpdb;

			try {
				$stored_id = null;
				if ( ! empty( $customer_data['id'] ) ) {

					$abandoned_row_id                      = wc()->session->get( 'activecampaign_abandoned_cart_id' );
					$activecampaignfwc_order_external_uuid = wc()->session->get( 'activecampaignfwc_order_external_uuid' );

					if ( ! empty( $abandoned_row_id ) ) {
						$stored_id = $wpdb->get_var(
						// phpcs:disable
							$wpdb->prepare(
								'
							SELECT id FROM ' . $wpdb->prefix . ACTIVECAMPAIGN_FOR_WOOCOMMERCE_TABLE_NAME . ' 
							WHERE id = %d
							',
								$abandoned_row_id
							)
						// phpcs:enable
						);
					} elseif ( ! empty( $activecampaignfwc_order_external_uuid ) ) {
						$stored_id = $wpdb->get_var(
						// phpcs:disable
							$wpdb->prepare(
								'
							SELECT id FROM ' . $wpdb->prefix . ACTIVECAMPAIGN_FOR_WOOCOMMERCE_TABLE_NAME . ' 
							WHERE activecampaignfwc_order_external_uuid = %s
							',
								$activecampaignfwc_order_external_uuid
							)
						// phpcs:enable
						);
					}

					if ( $wpdb->last_error ) {
						$this->logger->warning(
							'Save abandoned cart command: There was an error selecting the id for a customer abandoned cart record.',
							[
								'wpdb_last_error' => $wpdb->last_error,
								'customer_id'     => $customer_data['id'],
							]
						);
					}
				}
			} catch ( Throwable $t ) {
				$this->logger->warning(
					'Save abandoned cart command: There was an error attempting to save this abandoned cart',
					[
						'message'       => $t->getMessage(),
						'customer_data' => $customer_data,
						'trace'         => $this->logger->clean_trace( $t->getTrace() ),
					]
				);
			}

			try {
				// clean user_pass from user
				unset( $current_user->user_pass );
			} catch ( Throwable $t ) {
				$this->logger->warning(
					'Save abandoned cart command: There was an error checking and clearing the abandoned cart',
					[
						'message'       => $t->getMessage(),
						'customer_data' => $customer_data,
						'trace'         => $this->logger->clean_trace( $t->getTrace() ),
					]
				);
			}

			try {
				$store_data = [
					'customer_id'                    => $customer_data['id'],
					'customer_email'                 => $customer_data['email'],
					'customer_first_name'            => $customer_data['first_name'],
					'customer_last_name'             => $customer_data['last_name'],
					'last_access_time'               => $dt->format( 'Y-m-d H:i:s e' ),
					'customer_ref_json'              => wp_json_encode( $customer_data, JSON_UNESCAPED_UNICODE ),
					'user_ref_json'                  => wp_json_encode( $current_user, JSON_UNESCAPED_UNICODE ),
					'cart_ref_json'                  => wp_json_encode( $cart, JSON_UNESCAPED_UNICODE ),
					'cart_totals_ref_json'           => wp_json_encode( $cart_totals, JSON_UNESCAPED_UNICODE ),
					'removed_cart_contents_ref_json' => wp_json_encode( $removed_cart_contents, JSON_UNESCAPED_UNICODE ),
				];

				if ( isset( $customer_data['id'] ) && User_Meta_Service::get_current_user_ac_customer_id( $customer_data['id'] ) ) {
					$store_data['ac_customer_id'] = User_Meta_Service::get_current_user_ac_customer_id( $customer_data['id'] );
				}

				$current_hash = wc()->cart->get_cart_hash();
				$saved_hash   = wc()->session->get( 'activecampaign_abandoned_cart_hash' );

				if ( empty( $saved_hash ) || $current_hash !== $saved_hash ) {
					$store_data['synced_to_ac'] = self::STATUS_ABANDONED_CART_UNSYNCED;
					wc()->session->set( 'activecampaign_abandoned_cart_hash', wc()->cart->get_cart_hash() );
				}

				if ( ! empty( $stored_id ) ) {
					// Updating existing record
					$this->store_abandoned_cart_data( $store_data, $stored_id );
				} else {
					// Storing a new record
					$store_data['activecampaignfwc_order_external_uuid'] = $this->get_or_generate_uuid();
					$store_data['ac_externalcheckoutid']                 = $this->generate_externalcheckoutid( $customer_data['id'], $customer_data['email'], $store_data['activecampaignfwc_order_external_uuid'] );

					$stored_id = $this->store_abandoned_cart_data( $store_data );
				}

				wc()->session->set( 'activecampaign_abandoned_cart_id', $stored_id );
			} catch ( Throwable $t ) {
				$this->logger->warning(
					'Save abandoned cart command: There was an error attempting to save this abandoned cart',
					[
						'message'       => $t->getMessage(),
						'customer_data' => $customer_data,
						'trace'         => $this->logger->clean_trace( $t->getTrace() ),
					]
				);
			}
		}
	}
}
