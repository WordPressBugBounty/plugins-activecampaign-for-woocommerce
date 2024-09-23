<?php

/**
 * The file that defines the User Registered Event Class.
 *
 * @link       https://www.activecampaign.com/
 * @since      1.0.0
 *
 * @package    Activecampaign_For_Woocommerce
 * @subpackage Activecampaign_For_Woocommerce/includes/events
 */

use Activecampaign_For_Woocommerce_Admin as Admin;
use Activecampaign_For_Woocommerce_Logger as Logger;
use Activecampaign_For_Woocommerce_Triggerable_Interface as Triggerable;
use Activecampaign_For_Woocommerce_Ecom_Customer_Repository as Ecom_Customer_Repository;
use Activecampaign_For_Woocommerce_Ecom_Customer as Ecom_Customer;

/**
 * The User Registered Event Class.
 *
 * @since      1.0.0
 * @package    Activecampaign_For_Woocommerce
 * @subpackage Activecampaign_For_Woocommerce/includes/events
 * @author     acteamintegrations <team-integrations@activecampaign.com>
 */
class Activecampaign_For_Woocommerce_User_Registered_Event implements Triggerable {

	/**
	 * The custom ActiveCampaign logger
	 *
	 * @var Activecampaign_For_Woocommerce_Logger
	 */
	private $customer_email;

	/**
	 * The resulting existing or newly created AC ecom customer
	 *
	 * @var Ecom_Model
	 */
	private $customer_ac;

	/**
	 * The customer first name
	 *
	 * @var string
	 */
	private $customer_first_name;

	/**
	 * The customer last name
	 *
	 * @var string
	 */
	private $customer_last_name;

	/**
	 * The Ecom Customer Repo
	 *
	 * @var Activecampaign_For_Woocommerce_Ecom_Customer_Repository
	 */
	public $customer_repository;

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
	 * @param     Ecom_Customer_Repository|null             $customer_repository     The Ecom Customer Repository.
	 */
	public function __construct(
		Admin $admin,
		Logger $logger = null,
		Ecom_Customer_Repository $customer_repository
	) {
		$this->admin               = $admin;
		$this->logger              = $logger;
		$this->customer_repository = $customer_repository;
	}

	/**
	 * Called when a user is registered.
	 *
	 * @param     array ...$args     An array of all arguments passed in.
	 *
	 * @since 1.0.0
	 */
	public function trigger( ...$args ) {
		if ( ! $this->logger ) {
			$this->logger = new Logger();
		}

		// Step 1: Verify user
		if ( isset( $args[0] ) ) {
			$user_data = get_userdata( $args[0] );

			if ( isset( $user_data->user_email ) ) {
				$this->customer_email      = $user_data->user_email;
				$this->customer_first_name = $user_data->first_name;
				$this->customer_last_name  = $user_data->last_name;

				// Step 2: Create or find AC contact
				if ( $this->setup_woocommerce_customer() ) {
					$this->logger->debug( 'Registered event: New contact synced to ActiveCampaign.' );

					// Step 3: Add a meta to user to show the synced time
					$meta_key = 'activecampaign_for_woocommerce_contact_synced';
					update_user_meta( $user_data->ID, $meta_key, true );

					$meta_key = 'activecampaign_for_woocommerce_contact_last_synced';
					update_user_meta( $user_data->ID, $meta_key, gmdate( 'Y-m-d H:i:s' ) );

				} else {
					$meta_key = 'activecampaign_for_woocommerce_contact_synced';
					update_user_meta( $user_data->ID, $meta_key, false );
					$this->logger->warning( 'Registered event: Could not save contact to ActiveCampaign.' );
				}
			}
		}
	}

	/**
	 * Sets up and sends the customer for AC
	 */
	private function setup_woocommerce_customer() {
		// find_or_create_ac_customer
		$this->customer_ac = null;
		if ( isset( $this->admin ) && isset( $this->admin->get_connection_storage()['connection_id'] ) ) {
			$connection_id = $this->admin->get_connection_storage()['connection_id'];
			try {
				// Try to find the customer in AC
				$this->customer_ac = $this->customer_repository->find_by_email_and_connection_id( $this->customer_email, $connection_id );

				return true;
			} catch ( Throwable $t ) {
				// Set up AC customer model for a new customer
				try {
					$new_customer = new Ecom_Customer();
					$new_customer->set_email( $this->customer_email );
					$new_customer->set_connectionid( $connection_id );
					$new_customer->set_first_name( $this->customer_first_name );
					$new_customer->set_last_name( $this->customer_last_name );
				} catch ( Throwable $t ) {
					$this->logger->debug( 'Registered event: New customer creation exception ', [ 'message' => $t->getMessage() ] );

					return false;
				}

				try {
					// Try to create the new customer in AC
					$this->logger->debug( 'Registered event: Creating customer in ActiveCampaign. ', [ 'new_customer_data' => wp_json_encode( $new_customer->serialize_to_array() ) ] );

					$this->customer_ac = $this->customer_repository->create( $new_customer );

					return true;
				} catch ( Throwable $t ) {
					$this->logger->debug( 'Registered event: guest customer creation exception ', [ 'message' => $t->getMessage() ] );

					return false;
				}
			} catch ( Throwable $t ) {
				$this->logger->debug( 'Registered event: Guest find customer exception ', [ 'message' => $t->getMessage() ] );

				return false;
			}
		} else {
			$this->logger->warning( 'Registered event: Could not retrieve the connection id ' );

			return false;
		}
	}

}
