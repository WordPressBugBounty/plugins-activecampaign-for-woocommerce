<?php

/**
 * The file that defines the Set_Connection_Id_Cache_Command Class.
 *
 * @link       https://www.activecampaign.com/
 * @since      1.0.0
 *
 * @package    Activecampaign_For_Woocommerce
 * @subpackage Activecampaign_For_Woocommerce/includes/commands
 */

use Activecampaign_For_Woocommerce_Admin as Admin;
use Activecampaign_For_Woocommerce_Connection as Connection;
use Activecampaign_For_Woocommerce_Connection_Repository as Repository;

use Activecampaign_For_Woocommerce_Executable_Interface as Executable;
use Activecampaign_For_Woocommerce_Resource_Not_Found_Exception as Resource_Not_Found;
use Activecampaign_For_Woocommerce_Logger as Logger;

/**
 * The Set Connection Id Cache Command Class.
 *
 * @since      1.0.0
 * @package    Activecampaign_For_Woocommerce
 * @subpackage Activecampaign_For_Woocommerce/includes/commands
 * @author     acteamintegrations <team-integrations@activecampaign.com>
 */
class Activecampaign_For_Woocommerce_Set_Connection_Id_Cache_Command implements Executable {
	/**
	 * The repository.
	 *
	 * @var Repository
	 * @since 1.0.0
	 */
	private $repository;

	/**
	 * The Admin class.
	 *
	 * @var Admin
	 * @since 1.0.0
	 */
	private $admin;

	/**
	 * The logger interface.
	 *
	 * @var Logger
	 */
	private $logger;

	/**
	 * Activecampaign_For_Woocommerce_Set_Connection_Id_Cache_Command constructor.
	 *
	 * @param Admin       $admin The admin singleton instance.
	 * @param Repository  $repository The connection repository instance.
	 * @param Logger|null $logger The Logger interface.
	 *
	 * @since 1.0.0
	 */
	public function __construct( Admin $admin, Repository $repository, Logger $logger = null ) {
		$this->repository = $repository;
		$this->admin      = $admin;
		if ( ! $logger ) {
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
	 * Retrieves the id by API and then adds it to the DB.
	 *
	 * @param mixed ...$args An array of arguments that may be passed in from the action/filter called.
	 *
	 * @since 1.0.0
	 */
	public function execute( ...$args ) {

		/**
		 * The Connection model.
		 *
		 * @var Connection $connection
		 * @since 1.0.0
		 */
		 $connection = $this->get_connection();

		if ( ! $connection ) {
			return;
		}

		 $id = $connection->get_id();

		$this->admin->update_connection_storage(
			[
				'connection_id' => $id,
				'name'          => $connection->get_name(),
				'external_id'   => $connection->get_externalid(),
				'service'       => $connection->get_service(),
				'link_url'      => $connection->get_link_url(),
				'logo_url'      => $connection->get_logo_url(),
				'is_internal'   => $connection->get_is_internal(),
			]
		);

		 $this->admin->update_storage_from_connection( $connection );
	}
	// phpcs:enable

	/**
	 * Returns the connection model from the API.
	 *
	 * @return Connection|null
	 * @access private
	 * @since  1.0.0
	 */
	private function get_connection() {
		$connection = null;

		try {
			/**
			 * The Connection model.
			 *
			 * @var Connection $connection
			 * @since 1.0.0
			 */

			$connection = get_transient( 'activecampaign_for_woocommerce_connection' );

			if ( ! $connection ) {
				$connection = $this->repository->find_current();
			}
		} catch ( Resource_Not_Found $e ) {

			$this->admin->add_async_processing_notification(
				$this->formatted_error_text(),
				'warning'
			);

			$message     = $e->getMessage();
			$stack_trace = $this->logger->clean_trace( $e->getTrace() );
			$this->logger->notice(
				'Attempting to get connection for ActiveCampaign encountered an issue.',
				[
					'message'     => $message,
					'stack trace' => $stack_trace,
				]
			);
		}

		return $connection;
	}

	/**
	 * Returns a formatted error string with link to the ActiveCampaign account of the user.
	 *
	 * @return string
	 */
	private function formatted_error_text() {
		$api_url = $this->admin->get_local_settings()['api_url'];

		$new_url = str_replace( 'api-us1', 'activehosted', $api_url );

		$template = 'ACTION NEEDED: Please connect your WooCommerce store in your ActiveCampaign account first.' .
					" <a href='%s/app/integrations?openIntegrationInfoModal=true&selectedService=woocommerce' target='_blank'>" .
					'Click here to go to your ActiveCampaign account.</a>';

		$error_text = sprintf( $template, $new_url );

		return $error_text;
	}
}
