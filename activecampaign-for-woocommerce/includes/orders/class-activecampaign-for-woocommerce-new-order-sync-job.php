<?php

/**
 * Controls the new order sync process.
 * This will only be run by new order execution or cron so make sure all methods are admin only.
 *
 * @link       https://www.activecampaign.com/
 * @since      1.8.0
 *
 * @package    Activecampaign_For_Woocommerce
 * @subpackage Activecampaign_For_Woocommerce/includes
 */

use Activecampaign_For_Woocommerce_Executable_Interface as Executable;
use Activecampaign_For_Woocommerce_Logger as Logger;
use Activecampaign_For_Woocommerce_AC_Contact as AC_Contact;
use Activecampaign_For_Woocommerce_Customer_Utilities as Customer_Utilities;
use Activecampaign_For_Woocommerce_Ecom_Order_Repository as Order_Repository;
use Activecampaign_For_Woocommerce_Cofe_Order_Repository as Cofe_Order_Repository;
use Activecampaign_For_Woocommerce_Ecom_Customer_Repository as Customer_Repository;
use Activecampaign_For_Woocommerce_Cofe_Order_Builder as Cofe_Order_Builder;
use Activecampaign_For_Woocommerce_Synced_Status_Interface as Synced_Status;

/**
 * The Historical_Sync Event Class.
 *
 * @since      1.5.0
 * @package    Activecampaign_For_Woocommerce
 * @subpackage Activecampaign_For_Woocommerce/includes/events
 * @author     acteamintegrations <team-integrations@activecampaign.com>
 */
class Activecampaign_For_Woocommerce_New_Order_Sync_Job implements Executable, Synced_Status {
	use Activecampaign_For_Woocommerce_Data_Validation,
		Activecampaign_For_Woocommerce_Order_Data_Gathering,
		Activecampaign_For_Woocommerce_Contact_Data_Handler,
		Activecampaign_For_Woocommerce_Synced_Status_Handler;

	/**
	 * The custom ActiveCampaign logger
	 *
	 * @var Activecampaign_For_Woocommerce_Logger
	 */
	private $logger;

	/**
	 * The Ecom Order Repo
	 *
	 * @var Activecampaign_For_Woocommerce_Ecom_Order_Repository
	 */
	private $order_repository;

	/**
	 * The Cofe Order Repo
	 *
	 * @var Activecampaign_For_Woocommerce_Cofe_Order_Repository
	 */
	private $cofe_order_repository;

	/**
	 * The order utilities functions.
	 *
	 * @var Activecampaign_For_Woocommerce_Customer_Utilities
	 */
	private $customer_utilities;

	/**
	 * The Ecom Customer Repo
	 *
	 * @var Activecampaign_For_Woocommerce_Ecom_Customer_Repository
	 */
	private $customer_repository;

	/**
	 * The Ecom Connection ID
	 *
	 * @var int
	 */
	private $connection_id;

	/**
	 * Activecampaign_For_Woocommerce_Historical_Sync_Job constructor.
	 *
	 * @param     Activecampaign_For_Woocommerce_Logger|null              $logger     The logger object.
	 * @param     Activecampaign_For_Woocommerce_Customer_Utilities       $customer_utilities     The customer utility class.
	 * @param     Activecampaign_For_Woocommerce_Ecom_Customer_Repository $customer_repository     The customer repository object.
	 * @param     Activecampaign_For_Woocommerce_Ecom_Order_Repository    $order_repository     The order repository object.
	 * @param     Activecampaign_For_Woocommerce_Cofe_Order_Repository    $cofe_order_repository     The cofe order repository object.
	 */
	public function __construct(
		Logger $logger,
		Customer_Utilities $customer_utilities,
		Customer_Repository $customer_repository,
		Order_Repository $order_repository,
		Cofe_Order_Repository $cofe_order_repository
	) {
		if ( ! $logger ) {
			$this->logger = new Logger();
		} else {
			$this->logger = $logger;
		}

		$this->customer_repository   = $customer_repository;
		$this->order_repository      = $order_repository;
		$this->cofe_order_repository = $cofe_order_repository;
		$this->customer_utilities    = $customer_utilities;

		$admin_storage = get_option( ACTIVECAMPAIGN_FOR_WOOCOMMERCE_DB_CONNECTION_STORAGE_NAME );
		if ( ! empty( $admin_storage ) && isset( $admin_storage['connection_id'] ) ) {
			$this->connection_id = $admin_storage['connection_id'];
		}
	}

	/**
	 * Sync any new/live orders.
	 * Triggered from a hook call.
	 *
	 * @param     mixed ...$args The passed args.
	 */
	public function execute( ...$args ) {
		if ( ! $this->logger ) {
			$this->logger = new Logger();
		}

		$unsynced_order_data = null;
		$recovered_orders    = null;

		try {
			if ( isset( $args[0] ) && is_int( $args[0] ) ) {
				$wc_order_id = $args[0];
			}

			if ( isset( $args[0]['wc_order_id'] ) ) {
				$wc_order_id = $args[0]['wc_order_id'];
			}

			if ( isset( $args['wc_order_id'] ) ) {
				$wc_order_id = $args['wc_order_id'];
			}

			if ( isset( $wc_order_id ) ) {
				// We're just syncing one row by order id
				$wc_order = $this->get_wc_order( $wc_order_id );

				if ( false === $wc_order || ! $this->order_has_required_data( $wc_order ) ) {
					$this->mark_order_as_incompatible( $wc_order_id );
					return false;
				}

				$ac_customer_id = $this->get_ac_customer_id( $wc_order->get_billing_email() );
				$ac_order       = $this->single_sync_cofe_data( $wc_order, false, $ac_customer_id );

				if ( ! isset( $ac_order ) || ! $ac_order ) {
					$this->logger->warning(
						'The order may have failed to sync to cofe',
						[
							$ac_order,
						]
					);

					$this->mark_order_as_failed( $wc_order_id );
				} else {
					$ac_id = null;

					if ( self::validate_object( $ac_order, 'get_id' ) ) {
						$ac_id = $ac_order->get_id();
					}

					$this->add_update_notes( $wc_order_id, $ac_id, $wc_order->get_status() );
					$this->mark_single_order_synced( $wc_order_id );
					$this->update_last_order_sync();
				}
			}
		} catch ( Throwable $t ) {
			$this->logger->error(
				'Activecampaign_For_Woocommerce_New_Order_Sync: There was an error processing the order data by wc_order_id.',
				[
					'args'        => $args,
					'message'     => $t->getMessage(),
					'stack_trace' => $t->getTrace(),
				]
			);
			if ( isset( $wc_order_id ) && ! empty( $wc_order_id ) ) {
				$this->mark_order_as_incompatible( $wc_order_id );
			}
		}

		try {
			if ( isset( $args[0]['row_id'] ) ) {
				$row_id = $args[0]['row_id'];
			}

			if ( isset( $args['row_id'] ) ) {
				$row_id = $args['row_id'];
			}

			if ( isset( $row_id ) ) {
				// We're just syncing one row by row id
				$unsynced_order_data = $this->get_unsynced_orders_from_table( $row_id, false );
				$recovered_orders    = $this->get_unsynced_orders_from_table( $row_id, true );
			} else {

				// This is a general sync, get all records
				$now      = date_create( 'NOW' );
				$last_run = get_option( 'activecampaign_for_woocommerce_abandoned_cart_last_run' );

				// Try and keep this event from running too many times.
				// The cron will run every minute but this needs to run every 10 instead.
				if ( false !== $last_run ) {
					$interval         = date_diff( $now, $last_run );
					$interval_minutes = $interval->format( '%i' );
				} else {
					$interval_minutes = 10;
				}

				if ( ! isset( $interval_minutes ) || empty( $interval_minutes ) || $interval_minutes >= 10 ) {
					$unsynced_order_data = $this->get_unsynced_orders_from_table( null, false );
					$recovered_orders    = $this->get_unsynced_orders_from_table( null, true );
				}
			}
		} catch ( Throwable $t ) {
			$this->logger->warning(
				'Activecampaign_For_Woocommerce_New_Order_Sync: There was an error processing the order data.',
				[
					'args'        => $args,
					'message'     => $t->getMessage(),
					'stack_trace' => $t->getTrace(),
				]
			);
			if ( isset( $wc_order_id ) && ! empty( $wc_order_id ) ) {
				$this->mark_order_as_incompatible( $wc_order_id );
			}
		}

		try {
			if (
				(
					is_array( $unsynced_order_data ) && count( $unsynced_order_data ) > 0
				) ||
				(
					is_array( $recovered_orders ) && count( $recovered_orders ) > 0
				)

			) {
				$this->iterate_order_data( $unsynced_order_data, $recovered_orders );
			}
		} catch ( Throwable $t ) {
			$this->logger->warning(
				'Activecampaign_For_Woocommerce_New_Order_Sync: There was an error processing the order data.',
				[
					'message'     => $t->getMessage(),
					'stack_trace' => $t->getTrace(),
				]
			);
		}
	}

	private function mark_single_order_synced( $wc_order_id ) {
		global $wpdb;

		if ( isset( $wc_order_id ) ) {
			$table_data = $this->get_unsynced_orders_from_table( $wc_order_id, false, true );

			if ( ! isset( $table_data[0]->id ) ) {
				$table_data = $this->get_unsynced_orders_from_table( $wc_order_id, true, true );
			}

			if (
				isset( $table_data[0]->id, $table_data[0]->synced_to_ac ) &&
				$table_data[0]->synced_to_ac >= self::STATUS_ABANDONED_CART_AUTO_SYNCED &&
				$table_data[0]->synced_to_ac <= self::STATUS_ABANDONED_CART_NETWORK_FAIL_PERM
			) {
				$wpdb->update(
					$wpdb->prefix . ACTIVECAMPAIGN_FOR_WOOCOMMERCE_TABLE_NAME,
					[ 'synced_to_ac' => self::STATUS_SYNCED ],
					[
						'id' => $table_data[0]->id,
					]
				);
			} elseif ( isset( $table_data[0]->id ) ) {
				$wpdb->update(
					$wpdb->prefix . ACTIVECAMPAIGN_FOR_WOOCOMMERCE_TABLE_NAME,
					[ 'synced_to_ac' => self::STATUS_SYNCED ],
					[
						'id' => $table_data[0]->id,
					]
				);
			}
		}
	}

	public function iterate_order_data( $unsynced_order_data, $recovered_orders ) {
		// Iterate through standard orders
		if ( ! empty( $unsynced_order_data ) && count( $unsynced_order_data ) > 0 ) {
			// Iterate through the unsynced order data
			foreach ( $unsynced_order_data as $prep_order ) {
				$wc_order = $this->get_wc_order( $prep_order->wc_order_id );

				if ( false === $wc_order || ! $this->order_has_required_data( $wc_order ) ) {
					$this->mark_order_as_incompatible( $prep_order->wc_order_id );
					// fail this order but keep going
					continue;
				}

				try {
					if ( isset( $prep_order->ac_customer_id ) && ! empty( $prep_order->ac_customer_id ) ) {
						$ac_customer_id = $prep_order->ac_customer_id;
					} else {
						$ac_customer_id = $this->get_ac_customer_id( $prep_order->customer_email );
					}

					$ac_order = $this->single_sync_cofe_data( $wc_order, false, $ac_customer_id );

					if ( ! isset( $ac_order ) ) {
						$this->mark_order_as_failed( $prep_order->wc_order_id );
					} else {
						$ac_id = null;

						if ( self::validate_object( $ac_order, 'get_id' ) ) {
							$ac_id = $ac_order->get_id();
						}

						$this->add_update_notes( $prep_order->wc_order_id, $ac_id, $wc_order->get_status() );

						$this->mark_single_order_synced( $prep_order->wc_order_id );
					}
				} catch ( Throwable $t ) {
					$this->logger->warning(
						'New Order Sync: This order failed to process via COFE code. It will not be synced.',
						[
							'prep_order'  => $prep_order,
							'message'     => $t->getMessage(),
							'stack_trace' => $this->logger->clean_trace( $t->getTrace() ),
						]
					);
				}

				if ( ! isset( $ac_order ) ) {
					$this->mark_order_as_failed( $prep_order->wc_order_id );
				}
			}
		}

		// Iterate through recovered orders
		if ( ! empty( $recovered_orders ) && count( $recovered_orders ) > 0 ) {
			foreach ( $recovered_orders as $unsynced_recovered_order ) {
				$wc_order = $this->get_wc_order( wc_get_order( $unsynced_recovered_order->wc_order_id ) );

				if ( false === $wc_order || ! $this->order_has_required_data( $wc_order ) ) {
					$this->mark_order_as_incompatible( $unsynced_recovered_order->wc_order_id );
					continue;
				}

				try {
					// RECOVERED
					if ( isset( $unsynced_recovered_order->ac_customer_id ) && ! empty( $unsynced_recovered_order->ac_customer_id ) ) {
						$ac_customer_id = $unsynced_recovered_order->ac_customer_id;
					} else {
						$ac_customer_id = $this->get_ac_customer_id( $unsynced_recovered_order->customer_email );
					}

					$ac_order = $this->single_sync_cofe_data( $wc_order, $unsynced_recovered_order->ac_externalcheckoutid, $ac_customer_id );
				} catch ( Throwable $t ) {
					$this->logger->warning(
						'New Order Sync: This recovered order failed to process via COFE code. It will not be synced.',
						[
							'prep_order'  => $prep_order,
							'message'     => $t->getMessage(),
							'stack_trace' => $this->logger->clean_trace( $t->getTrace() ),
						]
					);
				}

				if ( ! isset( $ac_order ) ) {
					$this->mark_order_as_failed( $unsynced_recovered_order->wc_order_id );
				} else {
					$ac_id = null;

					if ( self::validate_object( $ac_order, 'get_id' ) ) {
						$ac_id = $ac_order->get_id();
					}

					$this->add_update_notes( $unsynced_recovered_order->wc_order_id, $ac_id, $wc_order->get_status() );

					$this->mark_single_order_synced( $unsynced_recovered_order->wc_order_id );
				}
			}
		}

		$this->logger->debug( 'New order sync job finished' );
	}

	/**
	 * Process to sync a single record
	 *
	 * @param WC_Order $wc_order The WC order.
	 * @param string   $externalcheckoutid The external checkout ID passed only if it's recovered order.
	 *
	 * @return false|void
	 */
	public function single_sync_cofe_data( $wc_order, $externalcheckoutid = false, $ac_customer_id = null ) {
		if ( ! isset( $wc_order ) ) {
			return false;
		}

		$ecom_contact = new AC_Contact();

		if ( $ecom_contact->create_ecom_contact_from_order( $wc_order ) ) {
			$ecom_contact->set_connectionid( $this->connection_id );
		}

		$this->sync_contact_to_ac( $ecom_contact );

		$cofe_order_builder = new Cofe_Order_Builder();

		if ( isset( $wc_order ) && self::validate_object( $wc_order, 'get_id' ) && ! empty( $wc_order->get_id() ) ) {
			// Data for cofe order sync
			$ecom_order = $cofe_order_builder->setup_cofe_order_from_table( $wc_order, 1 );

			if ( is_null( $ecom_order ) ) {
				$this->logger->warning( 'Setup COFE order returned null. Something may have gone wrong or the data may not be missing/incompatible with AC.' );
				return false;
			}

			if ( $externalcheckoutid ) {
				$ecom_order->set_externalcheckoutid( $externalcheckoutid );
			}

			$ecom_order->set_accepts_marketing( $wc_order->get_meta( ACTIVECAMPAIGN_FOR_WOOCOMMERCE_ACCEPTS_MARKETING_NAME ) );

			$result = $this->cofe_order_repository->upsert_order( $ecom_order->serialize_to_array() );

			// Change this to return the response from AC if we have a data response
			return $result;
		}
	}

	/**
	 * Check if our order was properly synced to AC then mark result in the table.
	 *
	 * @param     object     $unsynced_order     The stored cart or order object.
	 * @param     Ecom_Order $ac_order The AC order object.
	 */
	private function check_synced_order( $unsynced_order, $ac_order = null ) {
		global $wpdb;

		$ac_customer_id = null;
		$ac_order_id    = null;

		if ( isset( $unsynced_order->wc_order_id, $unsynced_order->customer_email ) && ! empty( $unsynced_order->customer_email ) ) {
			try {
				if ( self::validate_object( $ac_order, 'get_id' ) ) {
					$ac_order_id = $ac_order->get_id();
				} elseif ( isset( $ac_order->id ) ) {
					$ac_order_id = $ac_order->id;
				}
			} catch ( Throwable $t ) {
				$this->logger->info(
					'Check synced order failed to get ID from ac_order',
					[
						'unsynced_order' => $unsynced_order,
						'ac_order'       => $ac_order,
					]
				);
			}

			try {
				// If no AC order id then find one in hosted
				if ( empty( $ac_order_id ) && isset( $unsynced_order->wc_order_id ) ) {
					$ac_order = $this->order_repository->find_by_externalid( $unsynced_order->wc_order_id );

					if ( self::validate_object( $ac_order, 'get_id' ) ) {
						$ac_order_id = $ac_order->get_id();
					}

					if ( self::validate_object( $ac_order, 'get_customerid' ) ) {
						$ac_customer_id = $ac_order->get_customerid();
					}
				}
			} catch ( Throwable $t ) {
				$this->logger->warning(
					'Check synced order encountered an error trying to find record by external ID',
					[
						'unsynced_order' => $unsynced_order,
						'ac_order'       => $ac_order,
					]
				);
			}

			try {
				if ( empty( $ac_customer_id ) && isset( $ac_order->customerid ) && ! empty( $ac_order->customerid ) ) {
					$ac_customer_id = $ac_order->customerid;
				}
			} catch ( Throwable $t ) {
				$this->logger->warning(
					'customerid not set or threw an error',
					[
						'unsynced_order' => $unsynced_order,
						'ac_order'       => $ac_order,
					]
				);
			}

			try {
				if ( empty( $ac_customer_id ) && self::validate_object( $ac_order, 'serialize_to_array' ) ) {
					$ac_order_array = $ac_order->serialize_to_array();
					if ( isset( $ac_order_array['customerid'] ) ) {
						$ac_customer_id = $ac_order_array['customerid'];
					}
				}
			} catch ( Throwable $t ) {
				$this->logger->warning(
					'Check synced order failed to get ID from ac_order',
					[
						'unsynced_order' => $unsynced_order,
						'ac_order'       => $ac_order,
					]
				);
			}

			try {
				if ( ! isset( $ac_customer_id ) ) {
					$ac_customer = $this->customer_repository->find_by_email_and_connection_id( $unsynced_order->customer_email, $this->connection_id );
					if ( self::validate_object( $ac_customer, 'get_id' ) ) {
						$ac_customer_id = $ac_customer->get_id();
					}
				}
			} catch ( Throwable $t ) {
				$this->logger->warning(
					'Check synced order failed to get ID from ac_order',
					[
						'unsynced_order' => $unsynced_order,
						'ac_order'       => $ac_order,
					]
				);
			}

			$data = [ 'synced_to_ac' => self::STATUS_FAIL ];

			if ( isset( $ac_customer_id ) && ! empty( $ac_customer_id ) ) {
				$data['ac_customer_id'] = $ac_customer_id;
			}

			if ( isset( $unsynced_order->customer_id ) && ! empty( $unsynced_order->customer_id ) ) {
				update_metadata( 'user', $unsynced_order->customer_id, 'activecampaign_for_woocommerce_hosted_customer_id', $ac_customer_id );
			}

			if ( ! empty( $ac_order_id ) ) {
				$data['ac_order_id']  = $ac_order_id;
				$data['synced_to_ac'] = self::STATUS_SYNCED;
				$this->add_update_notes( $unsynced_order->wc_order_id, $ac_order_id );
				$this->update_last_order_sync();
			}

			// if order do this
			$wpdb->update(
				$wpdb->prefix . ACTIVECAMPAIGN_FOR_WOOCOMMERCE_TABLE_NAME,
				$data,
				[
					'id' => $unsynced_order->id,
				]
			);

			if ( $wpdb->last_error ) {
				$this->logger->error(
					'Abandonement sync: There was an error updating an abandoned cart record as synced.',
					[
						'wpdb_last_error' => $wpdb->last_error,
					]
				);
			}
		}
	}

	private function update_last_order_sync() {
		$created_date = new DateTime( 'NOW', new DateTimeZone( 'UTC' ) );
		update_option( 'activecampaign_for_woocommerce_last_order_sync', $created_date );
	}

	/**
	 * Add update notes to the order.
	 *
	 * @param string|int  $wc_order_id The WC order ID.
	 * @param string|null $ac_order_id The AC order ID.
	 * @param string|null $sent_status The status we are sending.
	 */
	private function add_update_notes( $wc_order_id, $ac_order_id = null, $sent_status = null ) {
		$note = 'ActiveCampaign for WC synced order';

		if ( isset( $ac_order_id ) && ! empty( $ac_order_id ) ) {
			$note .= '
			(ID: ' . $ac_order_id . ')';
		}

		if ( isset( $sent_status ) && ! empty( $sent_status ) ) {
			$note .= '
			[Status: ' . $sent_status . ']';
		}

		wc_create_order_note( $wc_order_id, $note );
	}

	/**
	 * Get all of the unsynced orders from the table.
	 *
	 * @param     int|null $id     The row id.
	 * @param     bool     $recovered If this is a recovered call.
	 * @param     bool     $is_wc_order_id If this is a WC order id instead of a row id.
	 *
	 * @return array|bool|object|null
	 */
	private function get_unsynced_orders_from_table( $id = null, $recovered = false, $is_wc_order_id = false ) {
		global $wpdb;

		try {
			// Get the expired carts from our table
			if ( null !== $id ) {
				if ( true === $recovered ) {
					$where = 'abandoned_date IS NOT NULL';
				} else {
					$where = 'abandoned_date IS NULL';
				}

				$id_type = 'id';
				if ( $is_wc_order_id ) {
					$id_type = 'wc_order_id';
				}

				$orders = $wpdb->get_results(
				// phpcs:disable
					$wpdb->prepare( 'SELECT id, wc_order_id, ac_externalcheckoutid, customer_email, abandoned_date, ac_customer_id
						FROM
							`' . $wpdb->prefix . ACTIVECAMPAIGN_FOR_WOOCOMMERCE_TABLE_NAME . '`
						WHERE '.$where.'
							AND '.$id_type.' = %d
							LIMIT 1',
						$id

					)
				// phpcs:enable
				);

			} else {
				if ( true === $recovered ) {
					$where = 'AND abandoned_date IS NOT NULL';
				} else {
					$where = 'AND abandoned_date IS NULL';
				}

				$orders = $wpdb->get_results(
				// phpcs:disable
					$wpdb->prepare( 'SELECT id, wc_order_id, ac_externalcheckoutid, customer_email, ac_customer_id
					FROM
						`' . $wpdb->prefix . ACTIVECAMPAIGN_FOR_WOOCOMMERCE_TABLE_NAME . '`
					WHERE
						wc_order_id IS NOT NULL 
						'.$where.'
						AND synced_to_ac = %d
						ORDER BY id ASC
						LIMIT 100',
						0

					)
				// phpcs:enable
				);
			}

			if ( $wpdb->last_error ) {
				$this->logger->error(
					'Abandonment sync: There was an error getting results for abandoned cart records.',
					[
						'wpdb_last_error' => $wpdb->last_error,
					]
				);
			}

			if ( ! empty( $orders ) ) {
				return $orders;
			} else {
				return false;
			}
		} catch ( Throwable $t ) {
			$this->logger->error(
				'Order Sync: There was an error with preparing or getting order results.',
				[
					'message' => $t->getMessage(),
					'trace'   => $this->logger->clean_trace( $t->getTrace() ),
				]
			);
		}
	}

	/**
	 * Gets the customerm id by email from Hosted.
	 *
	 * @param string $email The billing email.
	 *
	 * @return mixed|null
	 */
	private function get_ac_customer_id( $email ) {
		$ac_customer_id = null;
		try {
			if ( isset( $email ) ) {
				$ac_customer = $this->customer_repository->find_by_email_and_connection_id( $email, $this->connection_id );
				if ( self::validate_object( $ac_customer, 'get_id' ) ) {
					$ac_customer_id = $ac_customer->get_id();
				}
			}
		} catch ( Throwable $t ) {
			$this->logger->warning(
				'Check synced order failed to get ID from ac_order',
				[
					'unsynced_order' => $email,
					'ac_customer_id' => $ac_customer_id,
				]
			);
		}

		return $ac_customer_id;
	}
}
