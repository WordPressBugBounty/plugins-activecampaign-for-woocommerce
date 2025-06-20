<?php

/**
 * When a new order is created this event class is triggered.
 *
 * @link       https://www.activecampaign.com/
 * @since      1.0.0
 *
 * @package    Activecampaign_For_Woocommerce
 * @subpackage Activecampaign_For_Woocommerce/includes/events
 */

use Activecampaign_For_Woocommerce_Admin as Admin;
use Activecampaign_For_Woocommerce_Ecom_Order_Factory as Ecom_Order_Factory;
use Activecampaign_For_Woocommerce_Logger as Logger;
use Activecampaign_For_Woocommerce_Customer_Utilities as Customer_Utilities;
use Activecampaign_For_Woocommerce_Synced_Status_Interface as Synced_Status;
use Activecampaign_For_Woocommerce_Scheduler_Handler as AC_Scheduler;
/**
 * The Order_Finished Event Class.
 *
 * @since      1.0.0
 * @package    Activecampaign_For_Woocommerce
 * @subpackage Activecampaign_For_Woocommerce/includes/events
 * @author     acteamintegrations <team-integrations@activecampaign.com>
 */
class Activecampaign_For_Woocommerce_New_Order_Created_Event {
	use Activecampaign_For_Woocommerce_Abandoned_Cart_Utilities;
	use Activecampaign_For_Woocommerce_Data_Validation;

	/**
	 * The WC Customer
	 *
	 * @var WC_Customer
	 */
	public $customer;

	/**
	 * The Ecom Order Factory
	 *
	 * @var Ecom_Order_Factory
	 */
	public $factory;

	/**
	 * The Ecom Order Repo
	 *
	 * @var Activecampaign_For_Woocommerce_Ecom_Order_Repository
	 */
	public $order_repository;

	/**
	 * The Ecom Customer Repo
	 *
	 * @var Activecampaign_For_Woocommerce_Ecom_Customer_Repository
	 */
	public $customer_repository;

	/**
	 * The Admin object
	 *
	 * @var Activecampaign_For_Woocommerce_Admin
	 */
	private $admin;

	/**
	 * The custom ActiveCampaign logger
	 *
	 * @var Activecampaign_For_Woocommerce_Logger
	 */
	private $logger;

	/**
	 * Activecampaign_For_Woocommerce_Cart_Emptied_Event constructor.
	 *
	 * @param     Activecampaign_For_Woocommerce_Admin|null $admin     The Admin object.
	 * @param     Logger|null                               $logger     The Logger.
	 */
	public function __construct(
		Admin $admin,
		Logger $logger
	) {
		$this->admin  = $admin;
		$this->logger = $logger;
	}

	/**
	 * Execute process for finished order. If we don't know what will be passed this should be used.
	 * Hook 'woocommerce_payment_complete', $this->get_id(), $transaction_id
	 *
	 * @param int|string $order_id The order id.
	 * @param int        $transaction_id The transaction id.
	 */
	public function ac_woocommerce_payment_complete( $order_id, $transaction_id ) {
		if ( empty( $order_id ) ) {
			return;
		}

		$wc_order = wc_get_order( $order_id );

		if ( isset( $wc_order ) && self::validate_object( $wc_order, 'get_id' ) ) {
			$this->logger->debug(
				'New order payment complete triggered',
				array(
					'order_id'       => $order_id,
					'transaction_id' => $transaction_id,
				)
			);

			$this->checkout_completed( $order_id, $wc_order );
		} else {
			$this->logger->error(
				'A new order was created but the record could not be processed or validated. Processing for this new order cannot be performed.',
				array(
					'suggested_action' => 'Check if the order has been created and properly triggered WooCommerce order hooks. If this is an API order or your store uses a third party payment process then this may not process through this method.',
					'ac_code'          => 'NOCE_125',
					'wc_order'         => $wc_order,
					$order_id,
				)
			);
		}
	}

	/**
	 * Hook 'woocommerce_new_order', $order->get_id(), $order
	 *
	 * @param string|int $order_id Order id.
	 * @param WC_Order   $wc_order Order object.
	 *
	 * @return void
	 */
	public function ac_woocommerce_new_order( $order_id, $wc_order ) {
		if ( empty( $order_id ) || is_null( $wc_order ) ) {
			return;
		}

		if ( ! self::validate_object( $wc_order, 'get_id' ) || empty( $wc_order->get_id() ) ) {
			$wc_order = wc_get_order( $order_id );
		}

		if ( isset( $wc_order ) && self::validate_object( $wc_order, 'get_id' ) ) {
			$this->logger->debug(
				'New order created triggered',
				array(
					'order_id' => $order_id,
				)
			);

			$this->checkout_completed( $order_id, $wc_order );
		} else {
			$this->logger->error(
				'A new order was created but the record could not be processed or validated. Processing for this new order cannot be performed.',
				array(
					'suggested_action' => 'Check if the order has been created and properly triggered WooCommerce order hooks. If this is an API order or your store uses a third party payment process then this may not process through this method.',
					'ac_code'          => 'NOCE_164',
					'wc_order'         => $wc_order,
					'order_id'         => $order_id,
				)
			);
		}
	}

	/**
	 * Execute using an order object.
	 * Hook woocommerce_checkout_order_created', $order
	 *
	 * @param WC_Order $order The WC Order object.
	 */
	public function ac_woocommerce_checkout_order_created( $order ) {
		if ( is_object( $order ) && self::validate_object( $order, 'get_id' ) ) {
			$order_id = $order->get_id();
			$this->checkout_completed( $order_id, $order );
		} else {
			$this->logger->error(
				'A new order was created but the record could not be processed or validated. Processing for this new order cannot be performed.',
				array(
					'suggested_action' => 'Check if the order has been created. If this is an API order or your store uses a third party payment process then this may not process through this method.',
					'ac_code'          => 'NOCE_140',
					$order,
				)
			);

			return;
		}
	}

	/**
	 * This may not work as expected anymore due to WC changes.
	 *
	 * @param string|int $order_id The order ID.
	 * @param WC_Order   $data The data.
	 */
	public function ac_woocommerce_checkout_update_order_meta( $order_id, $data ) {
		$this->logger->dev(
			'do meta',
			[
				$order_id, $data,
			]
		);
		if ( empty( $order_id ) ) {
			return;
		}

		$wc_order = wc_get_order( $order_id );

		if ( isset( $wc_order ) && self::validate_object( $wc_order, 'get_id' ) ) {
			$this->logger->debug(
				'New order meta update event triggered',
				array(
					'order_id' => $order_id,
					'data'     => $data,
				)
			);

			$this->checkout_completed( $order_id, $wc_order );
		} else {
			$this->logger->error(
				'A new order was created but the record could not be processed or validated. Processing for this new order cannot be performed.',
				array(
					'suggested_action' => 'Check if the order has been created and properly triggered WooCommerce order hooks. If this is an API order or your store uses a third party payment process then this may not process through this method.',
					'ac_code'          => 'NOCE_226',
					'wc_order'         => $wc_order,
					$order_id,
				)
			);
		}
	}

	/**
	 * Checks if the order is a subscription.
	 *
	 * @param     string   $order_id
	 * @param     WC_Order $wc_order
	 */
	public function check_for_subscription( $order_id, WC_Order $wc_order ) {
		if ( function_exists( 'wcs_get_subscriptions_for_order' ) ) {

			if ( isset( $order_id ) ) {
				$wc_order      = wc_get_order( $order_id );
				$subscriptions = wcs_get_subscriptions_for_order( $order_id );

				if ( isset( $subscription ) ) {
					foreach ( $subscriptions as $subscription ) {
						$this->logger->debug(
							'1 each subscription on the order hopefully processed right.',
							array(
								'subscription' => $subscription,
								'data'         => $subscription->get_data(),
							)
						);
					}
				}
			}
			if ( isset( $wc_order ) ) {
				$subscriptions = wcs_get_subscriptions_for_order( $wc_order );

				if ( isset( $subscription ) ) {
					foreach ( $subscriptions as $subscription ) {
						$this->logger->debug(
							'2 each subscription on the order hopefully processed right.',
							array(
								'subscription' => $subscription,
								'data'         => $subscription->get_data(),
							)
						);
					}
				}
			}
		}
	}

	/**
	 * Check if the order is a subscription renewal.
	 *
	 * @param     WC_Order $wc_order
	 *
	 * @return bool
	 */
	public function is_renewal( WC_Order $wc_order ) {
		if ( function_exists( 'wcs_order_contains_renewal' ) && wcs_order_contains_renewal( $wc_order ) ) {
			return true;
		}
		return false;
	}
	/**
	 * Called when an order checkout is completed so that we can process and send data to Hosted.
	 * Directly called via hook action.
	 *
	 * @param     int      $order_id       The order ID.
	 * @param      WC_Order $wc_order The WC order object.
	 */
	private function checkout_completed( $order_id, $wc_order ) {
		if ( ! $this->logger ) {
			$this->logger = new Logger();
		}

		global $wpdb;

		try {
			$customer_utilities = new Customer_Utilities();

			$customer_data = $customer_utilities->build_customer_data( $wc_order->get_data() );

			if ( ! isset( $customer_data['id'] ) || empty( $customer_data['id'] ) ) {
				$found_customer_id = $customer_utilities->get_wc_customer_id( $wc_order );
				if ( isset( $found_customer_id ) && ! empty( $found_customer_id ) ) {
					$customer_data['id'] = $customer_utilities->get_wc_customer_id( $wc_order );
				}
			}

			if ( empty( $customer_data['id'] ) ) {
				$customer_data['id'] = null;
			}

			$cart_uuid = $this->get_or_generate_uuid();

			if ( isset( $customer_data['email'] ) ) {
				$externalcheckoutid = $this->generate_externalcheckoutid( $customer_data['id'], $customer_data['email'], $cart_uuid );
			} else {
				return;
			}
			if ( ! self::validate_object( $wc_order, 'get_id' ) ) {
				$wc_order = wc_get_order( $order_id );
			}

			if ( $this->is_renewal( $wc_order ) ) {
				// A renewal may not have set the external checkout ID so make sure it's set.
				$wc_order->update_meta_data(
					ACTIVECAMPAIGN_FOR_WOOCOMMERCE_PERSISTENT_CART_ID_NAME,
					$externalcheckoutid
				);
			}
			// phpcs:disable
			$stored_row = $wpdb->get_row(
				$wpdb->prepare(
					'SELECT id, wc_order_id, abandoned_date FROM ' . $wpdb->prefix . ACTIVECAMPAIGN_FOR_WOOCOMMERCE_TABLE_NAME . ' 
					WHERE wc_order_id = %d LIMIT 1',
					[ $order_id ]
				)
			);

			if ( isset( $stored_row->wc_order_id ) && ! empty( $stored_row->wc_order_id ) ) {
				$this->schedule_sync_job( $order_id );
				return;
			}

			$stored_row = $wpdb->get_row(
				$wpdb->prepare(
					'SELECT id, wc_order_id, abandoned_date FROM ' . $wpdb->prefix . ACTIVECAMPAIGN_FOR_WOOCOMMERCE_TABLE_NAME . ' 
					WHERE ac_externalcheckoutid = %s OR activecampaignfwc_order_external_uuid = %s LIMIT 1',
					[ $externalcheckoutid, $cart_uuid ]
				)
			);
			// phpcs:enable

			if ( isset( $stored_row->wc_order_id ) && ! empty( $stored_row->wc_order_id ) ) {
				// If we've saved the order we do not need to save again to stop redundant events
				$this->schedule_sync_job( $order_id );
				return;
			}

			if ( isset( $stored_row->id ) && ! empty( $stored_row->id ) ) {
				$stored_id = $stored_row->id;
			}

			$was_abandoned = false;
			if ( null !== $stored_row->abandoned_date ) {
				$was_abandoned = true;
			}
			if (
				isset( wc()->session ) &&
				! is_null( wc()->session ) &&
				WC()->session->has_session() &&
				self::validate_object( wc()->session, 'get' )
			) {
				$abandoned_cart_id = wc()->session->get( 'activecampaign_abandoned_cart_id' );
			}

			if ( isset( $stored_id ) && empty( $stored_id ) ) {
				if ( isset( $abandoned_cart_id ) && ! empty( $abandoned_cart_id ) ) {
					$stored_id = $wpdb->get_var(
					// phpcs:disable
						$wpdb->prepare(
							'SELECT id FROM ' . $wpdb->prefix . ACTIVECAMPAIGN_FOR_WOOCOMMERCE_TABLE_NAME . ' 
								WHERE id = %d 
								OR ac_externalcheckoutid = %s 
								OR activecampaignfwc_order_external_uuid = %s',
							[ $abandoned_cart_id, $externalcheckoutid, $cart_uuid ]
						)
					// phpcs:enable
					);
				} else {
					$stored_id = $wpdb->get_var(
					// phpcs:disable
						$wpdb->prepare(
							'SELECT id FROM ' . $wpdb->prefix . ACTIVECAMPAIGN_FOR_WOOCOMMERCE_TABLE_NAME . ' 
								WHERE ac_externalcheckoutid = %s 
								OR activecampaignfwc_order_external_uuid = %s',
							[ $externalcheckoutid, $cart_uuid ]
						)
					// phpcs:enable
					);
				}
			}
		} catch ( Throwable $t ) {
			$this->logger->error(
				'An exception was thrown while attempting to retrieve data from the ActiveCampaign table.',
				array(
					'message'            => $t->getMessage(),
					'suggested_action'   => 'Verify that the ' . ACTIVECAMPAIGN_FOR_WOOCOMMERCE_TABLE_NAME . ' exists and is both writable and readable.',
					'ac_code'            => 'NOCE_244',
					'stored_id'          => isset( $stored_id ) ? $stored_id : null,
					'abandoned_cart_id'  => isset( $abandoned_cart_id ) ? $abandoned_cart_id : null,
					'externalcheckoutid' => isset( $externalcheckoutid ) ? $externalcheckoutid : null,
					'trace'              => $this->logger->clean_trace( $t->getTrace() ),
				)
			);
		}

		try {
			$dt = new DateTime( $wc_order->get_date_created(), new DateTimeZone( 'UTC' ) );
			$dt->format( 'Y-m-d H:i:s' );

			if ( ! isset( $customer_data['id'], $customer_data['email'] ) ) {
				return;
			}

			$store_data = array(
				'synced_to_ac'                          => 0,
				'customer_id'                           => $customer_data['id'],
				'customer_email'                        => $customer_data['email'],
				'customer_first_name'                   => $customer_data['first_name'],
				'customer_last_name'                    => $customer_data['last_name'],
				'wc_order_id'                           => $order_id,
				'order_date'                            => $dt->format( 'Y-m-d H:i:s' ),
				'activecampaignfwc_order_external_uuid' => $cart_uuid,
				'ac_externalcheckoutid'                 => $externalcheckoutid,
				'customer_ref_json'                     => null,
				'user_ref_json'                         => null,
				'cart_ref_json'                         => null,
				'cart_totals_ref_json'                  => null,
				'removed_cart_contents_ref_json'        => null,
			);
		} catch ( Throwable $t ) {
			$this->logger->error(
				'During checkout completion an exception was thrown while attempting to form the order data.',
				array(
					'message'           => $t->getMessage(),
					'suggested_action'  => 'Check the message for explanation of the error and contact ActiveCampaign support if necessary.',
					'ac_code'           => 'NOCE_283',
					'abandoned_cart_id' => isset( $abandoned_cart_id ) ? $abandoned_cart_id : null,
					'stored_id'         => isset( $stored_id ) ? $stored_id : null,
					'trace'             => $this->logger->clean_trace( $t->getTrace() ),
				)
			);

			$store_data = null;
		}

		$this->cleanup_session_activecampaignfwc_order_external_uuid();

		if ( ! isset( $store_data ) ) {
			$this->logger->warning(
				'New order store data is missing. This should not happen. Something went wrong.',
				array(
					'order_id' => $order_id,
				)
			);
			return;
		}

		if ( $was_abandoned ) {
			// Abandoned cart item mark as recovered in synced_to_ac
			$store_data['synced_to_ac'] = Synced_Status::STATUS_ABANDONED_CART_RECOVERED;
		}

		try {
			if ( ! empty( $abandoned_cart_id ) && ! empty( $stored_row->abandoned_date ) ) {
				// Abandoned cart item mark as recovered in synced_to_ac
				$store_data['synced_to_ac'] = Synced_Status::STATUS_ABANDONED_CART_RECOVERED;
				$wpdb->update(
					$wpdb->prefix . ACTIVECAMPAIGN_FOR_WOOCOMMERCE_TABLE_NAME,
					$store_data,
					array(
						'id' => $abandoned_cart_id,
					)
				);
			} elseif ( isset( $stored_id ) && ! empty( $stored_id ) ) {
				if ( $was_abandoned ) {
					// Abandoned cart item mark as recovered in synced_to_ac
					$store_data['synced_to_ac'] = Synced_Status::STATUS_ABANDONED_CART_RECOVERED;
				}
				$wpdb->update(
					$wpdb->prefix . ACTIVECAMPAIGN_FOR_WOOCOMMERCE_TABLE_NAME,
					$store_data,
					array(
						'id' => $stored_id,
					)
				);

				$this->schedule_sync_job( $order_id );
			} else {

				if ( ! isset( $stored_id ) ) {
					$stored_id = null;
				}

				$stored_id = $wpdb->insert(
					$wpdb->prefix . ACTIVECAMPAIGN_FOR_WOOCOMMERCE_TABLE_NAME,
					$store_data
				);
				$this->schedule_sync_job( $order_id );
			}

			if ( $wpdb->last_error ) {
				$this->logger->error(
					'WordPress encountered an error when creating/updating an abandoned cart record. This order may not properly transition from an abandoned cart to an order. This may be falsely reported as an abandoned cart or result in a duplicate record in ActiveCampaign.',
					array(
						'wpdb_last_error'  => isset( $wpdb->last_error ) ? $wpdb->last_error : null,
						'stored_id'        => isset( $stored_id ) ? $stored_id : null,
						'suggested_action' => 'Please verify there is read/write access on the ACTIVECAMPAIGN_FOR_WOOCOMMERCE_TABLE_NAME table and contact support if issues persist.',
						'ac_code'          => 'NOCE_326',
					)
				);
			}
		} catch ( Throwable $t ) {
			$this->logger->error(
				'An exception was thrown while attempting to save order data from a finished order checkout.',
				array(
					'message'           => $t->getMessage(),
					'suggested_action'  => 'Check the message for error details and contact ActiveCampaign support if the issue persists.',
					'ac_code'           => 'NOCE_337',
					'abandoned_cart_id' => isset( $stored_id ) ? $stored_id : null,
					'trace'             => $this->logger->clean_trace( $t->getTrace() ),
				)
			);
		}

		$this->schedule_recurring_order_sync_task();
	}


	/**
	 * This schedules the recurring event and verifies it's still set up
	 */
	private function schedule_recurring_order_sync_task() {
		// If not scheduled, set up our recurring event
		$logger = new Logger();

		try {
			// wp_clear_scheduled_hook('activecampaign_for_woocommerce_cart_updated_recurring_event');
			if ( ! AC_Scheduler::is_scheduled( AC_Scheduler::RECURRING_ORDER_SYNC ) ) {
				AC_Scheduler::schedule_ac_event( AC_Scheduler::RECURRING_ORDER_SYNC, array(), true, false );
			} elseif ( function_exists( 'wp_get_scheduled_event' ) ) {
				$logger->debug_excess(
					'Recurring order sync already scheduled',
					array(
						'time_now' => time(),
						'myevent'  => AC_Scheduler::get_schedule( AC_Scheduler::RECURRING_ORDER_SYNC ),
					)
				);

			}
		} catch ( Throwable $t ) {
			$logger->debug(
				'There was a thrown issue scheduling the recurring order sync event.',
				array(
					'message' => $t->getMessage(),
				)
			);
		}
	}

	/**
	 * Schedules the sync job.
	 *
	 * @param int $order_id The order id.
	 */
	private function schedule_sync_job( $order_id ) {
		try {
			if ( ! AC_Scheduler::is_scheduled( AC_Scheduler::SYNC_ONE_ORDER_ACTIVE, array( 'wc_order_id' => $order_id, 'event' => 'onetime' ) ) ) {
				AC_Scheduler::schedule_ac_event(
					AC_Scheduler::SYNC_ONE_ORDER_ACTIVE,
					array(
						'wc_order_id' => $order_id,
						'event'       => 'onetime',
					),
					false,
					false
				);
			}

			$this->logger->debug(
				'Schedule finished order for immediate sync.',
				array(
					'wc_order_id'         => $order_id,
					'current_time'        => time(),
					'scheduled_time'      => time() + 40,
					'schedule validation' => AC_Scheduler::get_schedule( AC_Scheduler::SYNC_ONE_ORDER_ACTIVE, array( 'wc_order_id' => $order_id, 'event' => 'onetime' ) ),
				)
			);
		} catch ( Throwable $t ) {
			$logger = new Logger();
			$logger->warning(
				'There was a thrown error scheduling the immediate sync event.',
				array(
					'message' => $t->getMessage(),
					'ac_code' => 'NOCE_418',
				)
			);
		}

		$this->schedule_recurring_order_sync_task();
	}
}
