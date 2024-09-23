<?php

/**
 * Controls the historical sync process for contacts.
 * This will only be run by admin or cron so make sure all methods are admin only.
 *
 * @link       https://www.activecampaign.com/
 * @since      1.5.0
 *
 * @package    Activecampaign_For_Woocommerce
 * @subpackage Activecampaign_For_Woocommerce/includes
 */

use Activecampaign_For_Woocommerce_AC_Contact as AC_Contact;
use Activecampaign_For_Woocommerce_Logger as Logger;
use Activecampaign_For_Woocommerce_Executable_Interface as Executable;
use Activecampaign_For_Woocommerce_AC_Contact_Batch as AC_Contact_Batch;
use Activecampaign_For_Woocommerce_AC_Contact_Batch_Repository as AC_Contact_Batch_Repository;

/**
 * The Historical_Sync Event Class.
 *
 * @since      1.5.0
 * @package    Activecampaign_For_Woocommerce
 * @subpackage Activecampaign_For_Woocommerce/includes/events
 * @author     acteamintegrations <team-integrations@activecampaign.com>
 */
class Activecampaign_For_Woocommerce_Historical_Sync_Contacts implements Executable {
	use Activecampaign_For_Woocommerce_Historical_Status,
		Activecampaign_For_Woocommerce_Data_Validation;

	/**
	 * @var Activecampaign_For_Woocommerce_Logger
	 */
	private $logger;

	/**
	 * @var Activecampaign_For_Woocommerce_AC_Contact_Batch_Repository
	 */
	private $contact_batch_repository;

	public function __construct( Logger $logger, AC_Contact_Batch_Repository $contact_batch_repository ) {
		$this->logger                   = new Logger();
		$this->contact_batch_repository = $contact_batch_repository;
	}


	/**
	 * Run historical sync on contacts.
	 *
	 * @param     mixed ...$args Passed arguments.
	 */
	public function execute( ...$args ) {
		global $wpdb;

		$this->init_status();

		// set the start time
		if ( ! isset( $this->status['contact_start_time'] ) ) {
			$this->status['contact_start_time'] = wp_date( 'F d, Y - G:i:s e' );
		}

		$this->status['contact_total'] = $wpdb->get_var( 'SELECT count(email) FROM ' . $wpdb->prefix . 'wc_customer_lookup WHERE email != "";' );
		$this->status['contact_queue'] = $this->status['contact_total'] - $this->status['contact_count'] - $this->status['contact_failed_count'];
		$this->update_sync_status();

		$c               = 0;
		$limit           = 200;
		$synced_contacts = 0;
		$last_record     = 0;
		$start           = get_transient( 'activecampaign_for_woocommerce_hs_contacts' );

		$this->logger->debug( 'Contact historical sync started.', [ 'start transient' => $start ] );

		if ( null !== $start && false !== $start ) {
			$last_record     = $start;
			$synced_contacts = $this->status['contact_count'];
		}

		// phpcs:disable

		while ( $wc_customers = $wpdb->get_results(
			$wpdb->prepare(
				'SELECT first_name, last_name, email, user_id, customer_id FROM ' . $wpdb->prefix . 'wc_customer_lookup WHERE email != "" AND customer_id > %d ORDER BY customer_id ASC LIMIT %d, %d;',
				[ $start, $c, $limit ]
			)
		) ) {
			// phpcs:enable
			$bulk_contacts = [];
			foreach ( $wc_customers as $wc_customer ) {
				try {
					$ac_contact = new AC_Contact();
					if ( isset( $wc_customer->email ) ) {
						if ( self::check_valid_email( $wc_customer->email ) ) {
							$ac_contact->set_email( $wc_customer->email );
						} else {
							unset( $ac_contact );
							continue;
						}

						$ac_contact->set_first_name( $wc_customer->first_name );
						$ac_contact->set_first_name_c( $wc_customer->first_name );
						$ac_contact->set_last_name( $wc_customer->last_name );
						$ac_contact->set_last_name_c( $wc_customer->last_name );
						$ac_contact->set_phone( $this->find_wc_phone_number( $wc_customer ) );
						$ac_contact->set_tag( 'woocommerce-customer' );
					}

					if ( empty( $wc_customer->first_name ) ) {
						$ac_contact->set_first_name( get_user_meta( $wc_customer->customer_id, 'first_name', true ) );
						$ac_contact->set_first_name_c( get_user_meta( $wc_customer->customer_id, 'first_name', true ) );
					}

					if ( empty( $wc_customer->last_name ) ) {
						$ac_contact->set_last_name( get_user_meta( $wc_customer->customer_id, 'last_name', true ) );
						$ac_contact->set_last_name_c( get_user_meta( $wc_customer->customer_id, 'last_name', true ) );
					}

					if ( $ac_contact->get_email() ) {
						$bulk_contacts[] = $ac_contact->serialize_to_array();
						$synced_contacts ++;
					}
				} catch ( Throwable $t ) {
					$this->logger->warning(
						'A contact failed validation for historical sync contacts. This record will be skipped.',
						[
							'message' => $t->getMessage(),
							'ac_code' => 'hsc_127',
						]
					);
				}

				$last_record = $wc_customer->customer_id;
			}

			// Sync the contact batch
			try {
				$ac_contact_batch = new AC_Contact_Batch();
				$ac_contact_batch->set_contacts( $bulk_contacts );
				$batch = $ac_contact_batch->to_json();

				$response = $this->contact_batch_repository->create( $ac_contact_batch );

				$this->logger->debug(
					'Processing the batch customer object...',
					[
						'batch'    => $batch,
						'response' => $response,
					]
				);

				if ( is_array( $response ) && isset( $response['type'] ) && 'error' === $response['type'] ) {
					$synced_contacts                      -= count( $bulk_contacts );
					$this->status['contact_failed_count'] += count( $bulk_contacts );
				}

				$this->status['contact_count'] = $synced_contacts;
				$c                            += $limit;
				$this->status['contact_queue'] = $this->status['contact_total'] - $this->status['contact_count'] - $this->status['contact_failed_count'];
				$this->update_sync_status();

			} catch ( Throwable $t ) {
				$synced_contacts -= count( $bulk_contacts );
				$this->logger->debug(
					'Historical sync problem encountered',
					[
						'message' => $t->getMessage(),
						'trace'   => $t->getTrace(),
					]
				);
			}

			set_transient( 'activecampaign_for_woocommerce_hs_contacts', $last_record, 3600 );

			if ( $c >= 2000 ) {
				break;
			}
		}

		if ( 0 === $c ) {
			$this->update_sync_running_status( 'contacts', 'finished' );
			delete_transient( 'activecampaign_for_woocommerce_hs_contacts' );
		} elseif ( isset( $last_record ) && ! empty( $last_record ) ) {
			return;
		}

		$this->logger->debug(
			'Contacts synced',
			[
				'count' => $c,
			]
		);
	}

	private function schedule_next( $start ) {
		wp_schedule_single_event(
			time() + 50,
			'activecampaign_for_woocommerce_run_historical_sync_contacts',
			[ 'start' => $start ]
		);
	}

	/**
	 * Finds the phone number through few methods.
	 *
	 * @param     object $wc_customer     The WooCommerce customer.
	 *
	 * @return string|null The phone number returned.
	 */
	private function find_wc_phone_number( $wc_customer ) {
		global $wpdb;
		try {
			if ( self::validate_object( $wc_customer, 'user_id' ) && ! empty( $wc_customer->user_id ) ) {
				$phone = $wpdb->get_var(
					$wpdb->prepare(
						'SELECT meta_value FROM ' . $wpdb->prefix . 'usermeta where meta_key = %s AND user_id = %d;',
						[ 'billing_phone', $wc_customer->user_id ]
					)
				);

				if ( isset( $phone ) && ! empty( $phone ) ) {
					return $phone;
				}
			}
		} catch ( Throwable $t ) {
			$this->logger->warning(
				'Historical Sync: There was an error trying to find phone number from usermeta.',
				[
					'message' => $t->getMessage(),
					'trace'   => $this->logger->clean_trace( ( $t->getTrace() ) ),
				]
			);
		}

		try {
			if ( ! empty( $wc_customer->customer_id ) ) {
				$order_id = $wpdb->get_var(
					$wpdb->prepare(
						'SELECT order_id FROM ' . $wpdb->prefix . 'wc_order_stats where customer_id = %d ORDER BY order_id ASC LIMIT 1;',
						[ $wc_customer->customer_id ]
					)
				);

				if ( isset( $order_id ) ) {
					$wc_order = wc_get_order( $order_id );

					if ( self::validate_object( $wc_order, 'get_billing_phone' ) ) {
						$phone = $wc_order->get_billing_phone();
					}

					if ( isset( $phone ) && ! empty( $phone ) ) {
						return $phone;
					}
				}
			}
		} catch ( Throwable $t ) {
			$this->logger->warning(
				'Historical Sync: There was an error trying to find phone number from order stats.',
				[
					'message' => $t->getMessage(),
					'trace'   => $this->logger->clean_trace( ( $t->getTrace() ) ),
				]
			);
		}

		return '';
	}
}
