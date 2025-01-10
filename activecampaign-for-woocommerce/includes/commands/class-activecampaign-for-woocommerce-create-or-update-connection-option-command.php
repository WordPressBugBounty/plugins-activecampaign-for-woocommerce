<?php

/**
 * The file that defines the Create Or Update Connection Option Command Class.
 *
 * @link       https://www.activecampaign.com/
 * @since      1.0.0
 *
 * @package    Activecampaign_For_Woocommerce
 * @subpackage Activecampaign_For_Woocommerce/includes/commands
 */

use Activecampaign_For_Woocommerce_Admin as Admin;
use Activecampaign_For_Woocommerce_Connection_Option as Connection_Option;
use Activecampaign_For_Woocommerce_Connection_Option_Repository as Repository;

use Activecampaign_For_Woocommerce_Executable_Interface as Executable;
use Activecampaign_For_Woocommerce_Logger as Logger;

/**
 * The Create Or Update Connection Option Command Class.
 *
 * @since      1.0.0
 * @package    Activecampaign_For_Woocommerce
 * @subpackage Activecampaign_For_Woocommerce/includes/commands
 * @author     acteamintegrations <team-integrations@activecampaign.com>
 * @deprecated
 */
class Activecampaign_For_Woocommerce_Create_Or_Update_Connection_Option_Command implements Executable {
	/**
	 * Array of browse abandonment connection options with their WC option name and their corresponding AC value
	 *
	 * @var array
	 */
	private $connection_options_keys = array(
		'abcart_wait'             => 'abandoned_cart.abandon_after_hours',
		'ba_min_page_view_time'   => 'browse_abandonment.minimum_page_view_time',
		'ba_session_timeout'      => 'browse_abandonment.session_timeout',
		'ba_product_url_patterns' => 'browse_abandonment.product_url_patterns',
	);
	/**
	 * The Admin class.
	 *
	 * @var Admin
	 * @since 1.0.0
	 */
	private $admin;

	/**
	 * The Repository class.
	 *
	 * @var Repository
	 * @since 1.0.0
	 */
	private $repository;

	/**
	 * The array of storage values returned from the DB.
	 *
	 * @var array
	 * @since 1.0.0
	 */
	private $storage;

	/**
	 * The array of option values returned from the DB.
	 *
	 * @var array
	 * @since 1.0.0
	 */
	private $settings;

	/**
	 * The connection option array related to Browse Abandonment Settings.
	 *
	 * @var Activecampaign_For_Woocommerce_Connection_Option[] The connection option model.
	 */
	private $connection_options;

	/**
	 * The array connection option ids from AC.
	 *
	 * @var array
	 * @since 1.0.0
	 */
	private $ac_connection_option_ids;

	/**
	 * The logger interface.
	 *
	 * @var Logger The logger interface.
	 */
	private $logger;

	/**
	 * Activecampaign_For_Woocommerce_Create_Or_Update_Connection_Option_Command constructor.
	 *
	 * @throws Exception When the container is missing definitions.
	 * @since 1.0.0
	 *
	 * @param Admin      $admin The Admin singleton instance.
	 * @param Repository $repository The connection option repository singleton.
	 * @param Logger     $logger The logger interface.
	 */
	public function __construct( Admin $admin, Repository $repository, Logger $logger = null ) {
		$this->admin      = $admin;
		$this->repository = $repository;

		if ( ! $this->logger ) {
			$this->logger = new Logger();
		} else {
			$this->logger = $logger;
		}
	}

	// phpcs:disable Generic.CodeAnalysis.UnusedFunctionParameter

	/**
	 * Executes the command.
	 *
	 * Called when the activecampaign_settings_updated action hook fires.
	 * Either updates or creates a connection option via the API.
	 *
	 * @param mixed ...$args An array of arguments that may be passed in from the action/filter called.
	 *
	 * @throws Activecampaign_For_Woocommerce_Resource_Not_Found_Exception When the Connection Option isn't found.
	 * @throws Activecampaign_For_Woocommerce_Resource_Unprocessable_Exception When the Connection Option is
	 *                                                                         unprocessable.
	 * @deprecated
	 * @since 1.0.0
	 */
	public function execute( ...$args ) {
		/**
		 * If we were to set these values in the constructor, they would be null due to this object
		 * being constructed prior the values being saved (the first time they're set).
		 */
		$this->storage                  = $this->admin->get_connection_storage();
		$this->settings                 = $this->admin->get_local_settings();
		$this->ac_connection_option_ids = $this->store_connection_option_ids_from_ac();

		if ( $this->necessary_values_are_missing() ) {
			$this->logger->warning( 'Create or update connection option command: Some or all the following values are missing: connection_id,abcart_wait,ba_min_page_view_time,ba_session_timeout,ba_product_url_patterns' );

			return;
		}

		/**
		 *  Steps of updating connection options to AC
		 *  check if we have an AC id in Storage.
		 *  if they are missing in Storage, lets make an API call to see if we can get them.
		 *  if we find them we need to update storage with each respective AC id.
		 *  Create new array of options to send to AC. If option has an id, we will update with new values.
		 *  if option is missing id then we know at this stage we must send a create option request.
		 */
		if ( $this->connection_options_id_cache_is_missing() ) {
			$this->maybe_find_all_connection_options_by_connection_id();

			if ( $this->connection_options ) {
				$this->update_all_connection_options();

				return;
			}

			$this->update_all_connection_options();
		}
		$this->update_all_connection_options();
	}
	// phpcs:enable

	/**
	 * Checks if values necessary to the command are missing.
	 *
	 * @return bool
	 * @since  1.0.0
	 * @access private
	 * @deprecated
	 */
	private function necessary_values_are_missing() {
		return ! isset( $this->storage['connection_id'] ) || ! isset( $this->settings['abcart_wait'] ) || ! isset( $this->settings['ba_min_page_view_time'] ) || ! isset( $this->settings['ba_session_timeout'] ) || ! isset( $this->settings['ba_product_url_patterns'] );
	}

	/**
	 * Returns whether or not the connection option ids are set for ba settings
	 * in the DB cache or not.
	 *
	 * @return bool
	 * @since  1.0.0
	 * @access private
	 */
	private function connection_options_id_cache_is_missing() {
		return ! isset( $this->storage['abcart_wait_id'] ) || ! isset( $this->storage['ba_min_page_view_time_id'] ) || ! isset( $this->storage['ba_session_timeout_id'] ) || ! isset( $this->storage['ba_product_url_patterns_id'] );
	}

	/**
	 * Call AC to store Option Ids used to determine create or update patterns
	 */
	private function store_connection_option_ids_from_ac() {
		$ac_ids = array();
		try {
			$connection_options = $this->repository->find_all_by_filter(
				'connectionid',
				$this->storage['connection_id']
			);

			foreach ( $connection_options as $connection_option ) {
				array_push( $ac_ids, $connection_option['id'] );
			}
		} catch ( Activecampaign_For_Woocommerce_Resource_Not_Found_Exception $e ) {
			$message     = $e->getMessage();
			$stack_trace = $this->logger->clean_trace( $e->getTrace() );
			$this->logger->warning(
				'Could not find any connection options by connection ID.',
				array(
					'message'     => $message,
					'stack trace' => $stack_trace,
				)
			);
			$ac_ids = array();
		}
		return $ac_ids;
	}
	/**
	 * Attempts to find all the connection options by its connection id
	 */
	private function maybe_find_all_connection_options_by_connection_id() {
		$connection_options_model_array = array();
		$temp_connection_options_keys   = array_values( $this->connection_options_keys );
		try {
			$connection_options = $this->repository->find_all_by_filter(
				'connectionid',
				$this->storage['connection_id']
			);

			foreach ( $connection_options as $connection_option ) {
				$co_model = new Connection_Option();
				$co_model->set_properties_from_serialized_array( $connection_option );

				array_push( $connection_options_model_array, $co_model );
				array_splice( $temp_connection_options_keys, array_search( $connection_option['option'], $temp_connection_options_keys ), 1 );
			}
			/**
			 * After retrieval options could be missing. could be due to for some unforeseen reason record was deleted in AC.
			 * If this array has values that is the case, so we need to create a model for the option we need to create.
			 */
			if ( ! empty( $temp_connection_options_keys ) ) {
				foreach ( $temp_connection_options_keys as $option ) {
					$missing_connection_option = new Connection_Option();
					$option_name               = array_search( $option, $this->connection_options_keys );

					$missing_connection_option->set_option( $option );
					$missing_connection_option->set_connectionid( $this->storage['connection_id'] );
					$missing_connection_option->set_value( $this->settings[ $option_name ] );
					array_push( $connection_options_model_array, $missing_connection_option );
				}
			}

			$this->connection_options = $connection_options_model_array;
		} catch ( Activecampaign_For_Woocommerce_Resource_Not_Found_Exception $e ) {
			$message     = $e->getMessage();
			$stack_trace = $this->logger->clean_trace( $e->getTrace() );
			$this->logger->warning(
				'Could not find any connection options by connection ID.',
				array(
					'message'     => $message,
					'stack trace' => $stack_trace,
				)
			);
			$this->connection_options = null;
		}
	}

	/**
	 * Prepares all options to update/create process. Sets values from settings
	 * This is called after an
	 *
	 * @return Connection_Option[]
	 * @since  1.0.0
	 * @access private
	 */
	private function get_all_connection_options() {
		$connection_options = array();

		if ( $this->connection_options ) {
			$connection_options = $this->connection_options;
		} else {
			foreach ( $this->connection_options_keys as $wc_option => $ac_option ) {
				$connection_option = new Connection_Option();

				$connection_option->set_option( $ac_option );
				$connection_option->set_connectionid( $this->storage['connection_id'] );
				$connection_option->set_value( $this->settings[ $wc_option ] );

				array_push( $connection_options, $connection_option );
			}
		}

		return $connection_options;
	}

	/**
	 * Sends all Connection Option resources to Hosted via the API, then caches the ids
	 * of the option in the DB.
	 *
	 * @since  1.0.0
	 * @access private
	 */
	private function update_all_connection_options() {
		$connection_options = $this->get_all_connection_options();
		foreach ( $connection_options as $connection_option ) {
			$option_name    = array_search( $connection_option->get_option(), $this->connection_options_keys );
			$send_to_create = false;

			if ( ! $connection_option->get_id() ) {
				// The option is missing an id however we have one in storage, so we can use that to make an update call. only if it matches the id we pulled from AC
				if ( isset( $this->storage[ "{$option_name}_id" ] ) && in_array( $this->storage[ "{$option_name}_id" ], $this->ac_connection_option_ids ) ) {
					$connection_option->set_id( $this->storage[ "{$option_name}_id" ] );
				} else {
					$this->logger->info( 'Connection Option is missing. Creating Connection option - ' . $option_name );
					$send_to_create = true;
				}
			}

			$this->update_or_create_option( $connection_option, $send_to_create );
		}
	}

	/**
	 * Updates the cache with the connection option id.
	 *
	 * @param Connection_Option $connection_option The connection option to save in cache.
	 *
	 * @since  1.0.0
	 * @access private
	 */
	private function update_connection_option_id_cache( $connection_option ) {
		$option_name = array_search( $connection_option->get_option(), $this->connection_options_keys );
			$this->admin->update_connection_storage(
				array(
					"{$option_name}_id" => $connection_option->get_id(),
				)
			);

		$this->storage = $this->admin->get_connection_storage();
	}

	/**
	 * Instantiates connection options for each connection option, then creates it via API, then caches their ids
	 * of the option in the DB.
	 *
	 * @since  1.0.0
	 * @access private
	 */
	private function update_or_create_option( $connection_option, $to_create ) {
		if ( $to_create ) {
			try {
				$this->repository->create( $connection_option );

				$this->logger->info( 'Create or update connection option command:  single connection option created -' . $connection_option->get_option() );
			} catch ( Throwable $t ) {
				$this->admin->add_async_processing_notification(
					'Issue saving singular connection option setting. Option not saved for option - ' . $connection_option->get_option(),
					'error'
				);

				$message     = $t->getMessage();
				$stack_trace = $this->logger->clean_trace( $t->getTrace() );
				$this->logger->warning(
					'Create connection option encountered an error',
					array(
						'message'     => $message,
						'stack trace' => $stack_trace,
					)
				);

				return;
			}
		} else {
			try {
				$this->repository->update( $connection_option );

				$this->logger->info( 'Create or update connection option command:  single connection option updated - ' . $connection_option->get_option() );
			} catch ( Throwable $t ) {
				$this->admin->add_async_processing_notification(
					'Issue updating singular connection option setting. Option not saved for option - ' . $connection_option->get_option(),
					'error'
				);

				$message     = $t->getMessage();
				$stack_trace = $this->logger->clean_trace( $t->getTrace() );
				$this->logger->warning(
					'Create connection option encountered an error',
					array(
						'message'     => $message,
						'stack trace' => $stack_trace,
					)
				);

				return;
			}
		}
		$this->update_connection_option_id_cache( $connection_option );
	}
}
