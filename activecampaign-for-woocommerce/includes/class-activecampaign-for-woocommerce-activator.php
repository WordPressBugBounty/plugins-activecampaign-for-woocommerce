<?php

/**
 * Fired during plugin activation
 *
 * @link       https://www.activecampaign.com/
 * @since      1.0.0
 *
 * @package    Activecampaign_For_Woocommerce
 * @subpackage Activecampaign_For_Woocommerce/includes
 */

use Activecampaign_For_Woocommerce_Logger as Logger;

/**
 * Fired during plugin activation.
 *
 * This class defines all code necessary to run during the plugin's activation.
 *
 * @since      1.0.0
 * @package    Activecampaign_For_Woocommerce
 * @subpackage Activecampaign_For_Woocommerce/includes
 * @author     acteamintegrations <team-integrations@activecampaign.com>
 */
class Activecampaign_For_Woocommerce_Activator {
	use Activecampaign_For_Woocommerce_Admin_Utilities;

	/**
	 * Used for querying the DB.
	 *
	 * @var Activecampaign_For_Woocommerce_Admin The admin instance.
	 */
	private $admin;

	/**
	 * Activecampaign_For_Woocommerce_Activator constructor.
	 *
	 * @param     Activecampaign_For_Woocommerce_Admin $admin     The admin instance.
	 */
	public function __construct( Activecampaign_For_Woocommerce_Admin $admin ) {
		$this->admin = $admin;
	}

	/**
	 * Sets default values for settings in the DB.
	 *
	 * @since    1.0.0
	 */
	public function activate() {
		$logger = new Logger();
		$logger->info( 'Activation running...' );

		try {
			$this->admin->update_connection_storage( $this->set_default_settings( $this->admin->get_local_settings() ) );
		} catch ( Throwable $t ) {
			$logger->error(
				'The plugin activation process encountered an exception.',
				[
					'message' => $t->getMessage(),
					'ac_code' => 'ACT_73',
				]
			);
		}

		// Perform the table steps
		do_action( 'activecampaign_for_woocommerce_verify_tables' );

		$logger->info( 'Finished ActiveCampaign for WooCommerce plugin activation.' );
	}
}
