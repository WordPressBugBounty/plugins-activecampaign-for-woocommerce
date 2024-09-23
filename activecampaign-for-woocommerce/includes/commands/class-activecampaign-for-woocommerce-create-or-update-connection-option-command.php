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
	 * The connection option.
	 *
	 * @var Activecampaign_For_Woocommerce_Connection_Option The connection option model.
	 */
	private $connection_option;

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
	 * @param Admin             $admin The Admin singleton instance.
	 * @param Repository        $repository The connection option repository singleton.
	 * @param Connection_Option $connection_option The connection option model to optionally use.
	 * @param Logger            $logger The logger interface.
	 */
	public function __construct( Admin $admin, Repository $repository, Connection_Option $connection_option = null, Logger $logger = null ) {
		$this->admin             = $admin;
		$this->repository        = $repository;
		$this->connection_option = $connection_option;

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
		$this->storage  = $this->admin->get_connection_storage();
		$this->settings = $this->admin->get_local_settings();

		if ( $this->necessary_values_are_missing() ) {
			$this->logger->warning( 'Create or update connection option command: connection_id or abcart_wait values are missing.' );

			return;
		}

		if ( $this->connection_option_id_cache_is_missing() ) {

			$this->maybe_find_connection_option_by_connection_id();

			if ( $this->connection_option ) {
				$this->update_connection_option_id_cache( $this->connection_option->get_id() );

				$this->update_connection_option();

				return;
			}

			$this->logger->debug( 'Create or update connection option command: connection option not found.' );

			$this->create_connection_option();

			return;
		}

		$this->update_connection_option();
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
		return ! isset( $this->storage['connection_id'] ) || ! isset( $this->settings['abcart_wait'] );
	}

	/**
	 * Instantiates the Connection Option and returns it.
	 *
	 * @return Connection_Option
	 * @since  1.0.0
	 * @access private
	 * @deprecated
	 */
	private function get_connection_option() {
		if ( $this->connection_option ) {
			$connection_option = $this->connection_option;
		} else {
			$connection_option = new Connection_Option();
		}

		$connection_option->set_option( 'abandoned_cart.abandon_after_hours' );
		$connection_option->set_connectionid( $this->storage['connection_id'] );
		$connection_option->set_value( $this->settings['abcart_wait'] );

		return $connection_option;
	}

	/**
	 * Returns whether or not the connection option id is set
	 * in the DB cache or not.
	 *
	 * @return bool
	 * @since  1.0.0
	 * @access private
	 * @deprecated
	 */
	private function connection_option_id_cache_is_missing() {
		return ! isset( $this->storage['connection_option_id'] );
	}

	/**
	 * Instantiates a connection option, then creates it via API, then caches the id
	 * of the option in the DB.
	 *
	 * @since  1.0.0
	 * @access private
	 * @deprecated
	 */
	private function create_connection_option() {
		$connection_option = $this->get_connection_option();

		try {
			$this->repository->create( $connection_option );

			$this->logger->info( 'Create or update connection option command: connection option created.' );
		} catch ( Throwable $t ) {
			$this->admin->add_async_processing_notification(
				'There was a problem updating the Abandoned Cart time, please try saving your settings again.',
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

		$this->connection_option = $connection_option;

		$this->update_connection_option_id_cache( $this->connection_option->get_id() );
	}

	/**
	 * Attempts to find the connection option by its connection id before creating.
	 *
	 * @deprecated
	 */
	private function maybe_find_connection_option_by_connection_id() {
		try {
			$this->connection_option = $this->repository->find_by_filter(
				'connectionid',
				$this->storage['connection_id']
			);
		} catch ( Activecampaign_For_Woocommerce_Resource_Not_Found_Exception $e ) {
			$message     = $e->getMessage();
			$stack_trace = $this->logger->clean_trace( $e->getTrace() );
			$this->logger->warning(
				'Could not find the connection option ID by connection ID.',
				array(
					'message'     => $message,
					'stack trace' => $stack_trace,
				)
			);

			$this->connection_option = null;
		}
	}

	/**
	 * Sends a Connection Option resource to Hosted via the API, then caches the id
	 * of the option in the DB.
	 *
	 * If the first PUT fails with a Resource Not Found exception, the assumption is that the cache
	 * has become out of date and we need to attempt to re-find the connection option from the connection
	 * id. Once we've done that, we'll attempt to update it again. If it _still_ fails, then the assumption is
	 * that the connection option has somehow been deleted in Hosted. At this point, we create a new one instead.
	 *
	 * @param int $attempts How many attempts have we made so far. Prevents an infinite loop.
	 *
	 * @throws Exception WHen the container is missing definitions.
	 * @throws Activecampaign_For_Woocommerce_Resource_Not_Found_Exception When a 404 is returned.
	 * @throws Activecampaign_For_Woocommerce_Resource_Unprocessable_Exception When the connection option is
	 *                                                                         unprocessable.
	 * @since  1.0.0
	 * @access private
	 * @deprecated
	 */
	private function update_connection_option( $attempts = 0 ) {
		$connection_option = $this->get_connection_option();

		// Don't override an existing ID.
		if ( $this->storage['connection_id'] === $connection_option->get_connectionid() &&
			 $this->storage['connection_option_id'] !== $connection_option->get_value()
		) {
			$connection_option->set_id( $this->storage['connection_option_id'] );
		}

		if ( ! $connection_option->get_id() ) {
			$connection_option->set_id( $this->storage['connection_option_id'] );
		}

		try {
			$this->repository->update( $connection_option );
		} catch ( Activecampaign_For_Woocommerce_Resource_Not_Found_Exception $e ) {
			if ( $attempts > 1 ) {
				$this->create_connection_option();
			}

			$this->maybe_find_connection_option_by_connection_id();

			$this->update_connection_option( ++ $attempts );
		} catch ( Throwable $t ) {
			/**
			 * We have seen issues for a few users of this plugin where either the create or update call throws
			 * an exception, which ends up breaking their store. This try/catch is a stop-gap measure for now.
			 */

			$message     = $t->getMessage();
			$stack_trace = $this->logger->clean_trace( $t->getTrace() );
			$this->logger->error(
				'There was an error attempting to update the connection option with ActiveCampaign.',
				array(
					'message'     => $message,
					'stack trace' => $stack_trace,
				)
			);

			return;
		}

		$this->update_connection_option_id_cache( $connection_option->get_id() );
	}

	/**
	 * Updates the cache with the connection option id.
	 *
	 * @param string $id The id.
	 *
	 * @since  1.0.0
	 * @access private
	 * @deprecated
	 */
	private function update_connection_option_id_cache( $id ) {
		$this->admin->update_connection_storage(
			array(
				'connection_option_id' => $id,
			)
		);

		$this->storage = $this->admin->get_connection_storage();
	}
}
