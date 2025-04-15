<?php

/**
 * Various scheduler utilities.
 * Call actions using static calls.
 *
 * @link       https://www.activecampaign.com/
 * @since      1.5.0
 *
 * @package    Activecampaign_For_Woocommerce
 * @subpackage Activecampaign_For_Woocommerce/includes
 */

use Activecampaign_For_Woocommerce_Logger as Logger;

/**
 * all events
 * wp_clear_scheduled_hook( 'activecampaign_for_woocommerce_cart_updated_recurring_event' );
 * wp_clear_scheduled_hook( ACTIVECAMPAIGN_FOR_WOOCOMMERCE_RUN_NEW_ORDER_SYNC_NAME );
 * wp_clear_scheduled_hook( ACTIVECAMPAIGN_FOR_WOOCOMMERCE_RUN_PRODUCT_SYNC_NAME );
 * wp_clear_scheduled_hook( ACTIVECAMPAIGN_FOR_WOOCOMMERCE_RUN_HISTORICAL_RECUR );
 * wp_clear_scheduled_hook( ACTIVECAMPAIGN_FOR_WOOCOMMERCE_RUN_HISTORICAL_SYNC_NAME );
 * wp_clear_scheduled_hook( ACTIVECAMPAIGN_FOR_WOOCOMMERCE_HISTORICAL_SYNC_SCHEDULED_STATUS_NAME );
 * wp_clear_scheduled_hook( 'activecampaign_for_woocommerce_prep_historical_data' );
 *
 * // wp_unschedule_event() - Unschedules a previously scheduled event.
 * // wp_clear_scheduled_hook() - Unschedules all events attached to the hook with the specified arguments.
 * // wp_unschedule_hook - Unschedules all events attached to the hook.
 * // wp_get_scheduled_event - Retrieves a scheduled event.
 * // wp_schedule_single_event - Schedules an event to run only once. - (Use wp_next_scheduled() to prevent duplicate events.)
 * // wp_schedule_event = Schedules a recurring event.
 * // wp_reschedule_event - Reschedules a recurring event.
 * // wp_next_scheduled - Retrieves the next timestamp for an event.
 */

class Activecampaign_For_Woocommerce_Scheduler_Handler {

	/**
	 * The offset used typically for all events. Sometimes they will not run if they are in the past.
	 *
	 * @var int
	 */
	private $timestamp_offset = 30;

	/**
	 * The group for event schedules.
	 *
	 * @var string
	 */
	private $group = 'activecampaign_for_woocommerce';
	/**
	 * Recurring order sync.
	 */
	public const RECURRING_ORDER_SYNC = array( // recurring
		'name'        => 'Recurring Order Sync',
		'hook'        => 'activecampaign_for_woocommerce_run_order_sync',
		'description' => 'Recurring background sync for orders that may have failed to sync or not been picked up.',
		'frequency'   => 'ten_minute',
		'interval'    => 600,
	);

	/**
	 * Recurring historical sync that runs the historical process. Any prepared and ready items are synced with this.
	 */
	public const RECURRING_HISTORICAL_SYNC = array( // recurring
		'name'        => 'Historical Sync',
		'hook'        => ACTIVECAMPAIGN_FOR_WOOCOMMERCE_RUN_HISTORICAL_RECUR, // activecampaign_for_woocommerce_run_historical_sync_recurring_event
		'description' => 'Recurring sync for historical sync process.',
		'frequency'   => 'every_minute',
		'interval'    => 60,
	);


	/**
	 * Runs through carts and syncs abandoned carts that meet criteria.
	 */
	public const RECURRING_ABANDONED_SYNC = array( // recurring
		'name'        => 'Abandoned Cart Sync',
		'hook'        => 'activecampaign_for_woocommerce_cart_updated_recurring_event',
		'description' => 'Recurring sync for abandoned carts.',
		'frequency'   => 'every_minute',
		'interval'    => 60,
	);
	/**
	 * Repeat event for background sync of orders that may have been missed.
	 */
	public const SYNC_ONE_ORDER_ACTIVE = array(
		'name'        => 'Sync Order as New',
		'hook'        => 'activecampaign_for_woocommerce_admin_sync_single_order_active',
		'description' => 'Sync an existing order as a new order using the manual sync process.',
		'frequency'   => 'once',
	);

	/**
	 * Syncs one order record as new.
	 */
	public const SYNC_ONE_NEW_ORDER = array(
		'name'        => 'Sync New Order',
		'hook'        => 'activecampaign_for_woocommerce_ready_new_order',
		'description' => 'When a new order is created this event will run.',
		'frequency'   => 'once',
	);

	/**
	 * Run the product sync process.
	 */
	public const PRODUCT_SYNC = array(
		'name'        => 'Product Sync',
		'hook'        => ACTIVECAMPAIGN_FOR_WOOCOMMERCE_RUN_PRODUCT_SYNC_NAME,
		'description' => 'Runs product sync.',
		'frequency'   => 'once',
	);


	/**
	 * Makes sure all records are accounted for in the table.
	 */
	public const PREP_HISTORICAL_SYNC = array(
		'name'        => 'Prep Historical Sync',
		'hook'        => 'activecampaign_for_woocommerce_prep_historical_data',
		'description' => 'Runs historical sync order prep before the historical sync process runs.',
		'frequency'   => 'once',
	);

	/**
	 * Syncs a new subscription.
	 */
	public const SYNC_ONE_SUBSCRIPTION_ORDER = array(
		'name'        => 'Subscription Created',
		'hook'        => ACTIVECAMPAIGN_FOR_WOOCOMMERCE_RUN_NEW_ORDER_SYNC_NAME, // activecampaign_for_woocommerce_run_order_sync or activecampaign_for_woocommerce_update_subscription?
		'description' => 'Runs sync on a single subscription order record',
		'frequency'   => 'once',
	);

	/**
	 * Syncs one subscription when the record is updated.
	 */
	public const SYNC_UPDATE_ONE_SUBSCRIPTION = array(
		'name'        => 'Subscription Updated',
		'hook'        => 'activecampaign_for_woocommerce_update_subscription',
		'description' => 'Sync a subscription with an update of data.',
		'frequency'   => 'once',
	);

	/**
	 * Runs for historical sync of contacts.
	 */
	public const HISTORICAL_SYNC_CONTACTS = array(
		'name'        => 'Contact Historical Sync',
		'hook'        => 'activecampaign_for_woocommerce_run_historical_sync_contacts',
		'description' => 'Historical sync for all known contacts.',
		'frequency'   => 'once',
	);

	/**
	 * Order action event
	 */
	public const ADMIN_SYNC_SINGLE_ORDER = array(
		'name'        => 'Sync Updated Order',
		'hook'        => 'activecampaign_for_woocommerce_admin_sync_single_order_status',
		'description' => 'Sync an order when the status is updated.',
		'frequency'   => 'once',
	);

	/**
	 * List all accessible event constants here.
	 */
	private const ALL_EVENT_HOOKS = array(
		self::RECURRING_ABANDONED_SYNC,
		self::RECURRING_ORDER_SYNC,
		self::RECURRING_HISTORICAL_SYNC,
		self::PRODUCT_SYNC,
		self::PREP_HISTORICAL_SYNC,
		self::ADMIN_SYNC_SINGLE_ORDER,
		self::HISTORICAL_SYNC_CONTACTS,
		self::SYNC_UPDATE_ONE_SUBSCRIPTION,
		self::SYNC_ONE_ORDER_ACTIVE,
		self::SYNC_ONE_NEW_ORDER,
	);

	/**
	 * Get the event schedule.
	 *
	 * @param array $event The event constant.
	 * @param array $args Any args to be passed.
	 *
	 * @return bool|int|object
	 */
	public static function get_schedule( $event, $args = array() ) {
		return ( new Activecampaign_For_Woocommerce_Scheduler_Handler() )->get_event_or_timestamp( $event, $args );
	}

	/**
	 * Checks if an event is already scheduled.
	 *
	 * @param array $event The event constant.
	 * @param array $args Any args to be passed.
	 *
	 * @return bool
	 */
	public static function is_scheduled( $event, $args = array() ) {
		if (function_exists( 'as_has_scheduled_action' ) ) {
			return as_has_scheduled_action( $event['hook'], $args );
		}

		$event = ( new Activecampaign_For_Woocommerce_Scheduler_Handler() )->get_event_or_timestamp( $event, $args );

		if ( ! empty( $event ) ) {
			return true;
		}

		return false;
	}

	/**
	 * Schedules an event using AC standard event constants.
	 *
	 * @param array $event The event constant.
	 * @param array $args Any args to be passed.
	 * @param bool  $recurring Is this event recurring.
	 * @param bool  $reschedule Should we reschedule the event.
	 */
	public static function schedule_ac_event( $event, $args = array(), $recurring = false, $reschedule = false ) {
		( new Activecampaign_For_Woocommerce_Scheduler_Handler() )->schedule_event( $event, $args, $recurring, $reschedule );
	}

	/**
	 * Schedules all events in the recurring list.
	 * If we add a new event put it here.
	 *
	 * @param bool $force_reschedule Should it force rescheduling.
	 *
	 * @return bool
	 */
	public static function schedule_all_recurring_events( $force_reschedule ) {
		$logger         = new Logger();
		$schedule_error = false;
		$handler        = new Activecampaign_For_Woocommerce_Scheduler_Handler();
		if (
			empty( $handler->schedule_event( self::RECURRING_HISTORICAL_SYNC, array(), true, $force_reschedule ) )
		) {
			$schedule_error = true;
		}

		if (
			empty( $handler->schedule_event( self::RECURRING_ORDER_SYNC, array(), true, $force_reschedule ) )
		) {
			$schedule_error = true;
		}

		if (
			empty( $handler->schedule_event( self::RECURRING_ABANDONED_SYNC, array(), true, $force_reschedule ) )
		) {
			$schedule_error = true;
		}
		// TODO: check that they are all scheduled, if not return an error for each.

		if ($schedule_error ) {
			$logger->info( 'One or more events could not be scheduled.' );
			return false;
		}
		return true;
	}

	/**
	 * Remove all of our events.
	 */
	public static function remove_all_events() {
		$logger  = new Logger();
		$handler = new Activecampaign_For_Woocommerce_Scheduler_Handler();

		foreach (self::ALL_EVENT_HOOKS as $event ) {
			$result = $handler->clear_single_event( $event, array(), true );
			$logger->debug(
				'Clearing scheduled event ' . $event['name'],
				[
					$result,
				]
			);

			try {
				$logger->debug( 'Clearing repeat scheduled event ' . $event['name'] );
				if ( function_exists( 'wp_unschedule_event' ) ) {
					wp_unschedule_event( time() + $handler->timestamp_offset, $event['frequency'], $event['hook'] );
				}
				if ( function_exists( 'wp_next_scheduled' ) && ! wp_next_scheduled( $event['hook'] ) ) {
					$logger->debug( 'Removed scheduled event ' . $event['hook'] );
				}
				if ( function_exists( 'wp_get_scheduled_event' ) ) {
					wp_get_scheduled_event( $event['hook'] );
				}
				if ( function_exists( 'wp_clear_scheduled_hook' ) ) {
					wp_clear_scheduled_hook( $event['hook'], array(), true );
				}
			} catch (Throwable $t ) {
				$logger->error(
					'There was an issue removing a scheduled event',
					[
						'event'             => $event['name'],
						'event_description' => $event['description'],
						'message'           => $t->getMessage(),
					]
				);
			}
		}
	}

	/**
	 * Schedule one of the predefined events.
	 * Uses WP Cron events.
	 *
	 * @param array $event The event static array above.
	 * @param array $args Any passable args.
	 * @param bool  $recurring If this event should be recurring.
	 * @param bool  $reschedule If we should force reschedule the event.
	 *
	 * @return bool|int|WP_Error|null $scheduled_event Returns something either null or an event ID.
	 */
	private function schedule_event( $event, $args, $recurring, $reschedule ) {
		$start_time      = time() + $this->timestamp_offset;
		$logger          = new Logger();
		$scheduled_event = null;
		if ($this->debug_mode() ) {
			$event['interval']  = 30;
			$event['frequency'] = 'every_minute';
		}
		// Use action scheduler if we can

		if ( $recurring ) {
			// Try Action Scheduler
			if (function_exists( 'as_schedule_recurring_action' ) ) { // Working!
				// TODO: If reschedule true do we need to remove old? Does this replace?
				$scheduled_event = as_next_scheduled_action( $event['hook'], $args, $this->group );

				if ( empty( $scheduled_event ) ) {
					$scheduled_event = as_schedule_recurring_action( $start_time, $event['interval'], $event['hook'], $args, $this->group );
				}
			}
			if (empty( $scheduled_event ) ) {
				// use WP Cron
				if ( $reschedule ) {
					$scheduled_event = wp_reschedule_event( $start_time, $event['frequency'], $event['hook'], $args, true );
				} else {
					$scheduled_event = wp_schedule_event( $start_time, $event['frequency'], $event['hook'], $args, true );
				}
			}
		} else {
			// Try Action Scheduler single action
			if ( function_exists( 'as_schedule_single_action' ) ) {
				$scheduled_event = as_next_scheduled_action( $event['hook'], $args, $this->group );

				if ( empty( $scheduled_event ) ) {
					$scheduled_event = as_schedule_single_action( $start_time, $event['hook'], $args, $this->group );
				}
			}

			if ( empty( $scheduled_event ) ) {
				// Use WP cron single action
				$scheduled_event = wp_schedule_single_event(
					$start_time,
					$event['hook'],
					$args
				);
			}
		}

		$logger->debug(
			'Schedule event',
			[
				'event'      => $event,
				'event_id'   => $scheduled_event,
				'start_time' => $start_time,
				'args'       => $args,
			]
		);

		return $scheduled_event;
	}

	/**
	 * Check if an event is scheduled and return the schedule or timestamp.
	 * Uses Action Scheduler if possible, back up to WP Cron events.
	 * NOTE: as_get_scheduled_actions will not work as expected. Only returns IDs.
	 * NOTE: wc_get_scheduled_actions was deprecated by WC, do not use.
	 *
	 * @param array $event The event constant.
	 * @param array $args Any args to be passed.
	 *
	 * @return object|int|bool // Returns event array, timestamp, or false
	 */
	private function get_event_or_timestamp( $event, $args = array() ) {
		$logger = new Logger();

		try {
			if ( function_exists( 'as_next_scheduled_action' ) ) { // not working?
				$next_timestamp = as_next_scheduled_action( $event['hook'], $args );

				if ( $next_timestamp ) {
					return $next_timestamp;
				}
			}
		} catch ( Throwable $t ) {
			$logger->warning(
				'There was an issue checking the scheduled event using as_next_scheduled_action.',
				array(
					'event'   => $event['name'],
					'message' => $t->getMessage(),
					'trace'   => $t->getTrace(),
					'ac_code' => 'SCHED_380',
				)
			);
		}

		// If we don't have action scheduler do cron functions
		try {
			if ( function_exists( 'wp_get_scheduled_event' ) ) {
				// returns array
				if ( ! empty( $args ) ) {
					$schedule = wp_get_scheduled_event( $event['hook'], $args );
				} else {
					$schedule = wp_get_scheduled_event( $event['hook'] );
				}
			}

			if ( $schedule ) {
				return $schedule;
			}
		} catch ( Throwable $t ) {
			$logger->warning(
				'There was an issue checking the scheduled event using wp_get_scheduled_event.',
				array(
					'event'   => $event['name'],
					'message' => $t->getMessage(),
					'trace'   => $t->getTrace(),
					'ac_code' => 'SCHED_422',
				)
			);
		}

		try {
			if ( function_exists( 'wp_next_scheduled' ) ) {
				if ( ! empty( $args ) ) {
					$next_timestamp = wp_next_scheduled( $event['hook'], $args );
				} else {
					$next_timestamp = wp_next_scheduled( $event['hook'] );
				}

				if ($next_timestamp ) {
					return $next_timestamp;
				}
			}
		} catch ( Throwable $t ) {
			$logger->warning(
				'There was an issue checking the scheduled event using wp_next_scheduled.',
				array(
					'event'   => $event['name'],
					'message' => $t->getMessage(),
					'trace'   => $t->getTrace(),
					'ac_code' => 'SCHED_446',
				)
			);
		}

		return false;
	}


	/**
	 * Clears a single event.
	 * Uses WP Cron events.
	 *
	 * @param array $event The event constant.
	 * @param array $args  Any args to be passed.
	 * @param bool  $clear_all If we should clear all events or just this specific one.
	 *
	 * @return bool
	 */
	private function clear_single_event( $event, $args = null, $clear_all = false ) {
		if ( $clear_all ) {
			if ( function_exists( 'as_unschedule_all_actions' ) ) {
				as_unschedule_all_actions( $event['hook'], $args, $this->group );
			}

			if ( function_exists( 'wp_unschedule_hook' ) ) {
				wp_unschedule_hook( $event['hook'] );
			}

			if ( ! $this->get_event_or_timestamp( $event, $args ) ) {
				return true;
			}
		}

		if ( function_exists( 'as_unschedule_action' ) ) {
			if ( empty( $args ) ) {
				as_unschedule_action( $event['hook'] );
			}
			if ( ! empty( $args ) ) {
				as_unschedule_action( $event['hook'], $args );
				as_unschedule_action( $event['hook'], $args, $this->group );
			}

			if ( ! $this->get_event_or_timestamp( $event, $args ) ) {
				return true;
			}
		}

		if ( ! empty( $args ) ) {
			if (function_exists( 'wp_clear_scheduled_hook' ) ) {
				wp_clear_scheduled_hook( $event['hook'], $args );
			}
		} else {
			if (function_exists( 'wp_clear_scheduled_hook' ) ) {
				wp_clear_scheduled_hook( $event['hook'] );
			}
		}

		if ( ! $this->get_event_or_timestamp( $event, $args ) ) {
			return true;
		}

		return false;
	}

	private function debug_mode() {
		if (defined( 'ACFWC_DEBUG' ) ) {
			if (
				ACFWC_DEBUG !== null &&
				in_array( ACFWC_DEBUG, array(true, 1, '1') )
			) {
				return true;
			}
		}

		return false;
	}
}
