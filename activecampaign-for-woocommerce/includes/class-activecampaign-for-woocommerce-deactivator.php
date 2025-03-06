<?php

/**
 * Fired during plugin deactivation
 *
 * @link       https://www.activecampaign.com/
 * @since      1.0.0
 *
 * @package    Activecampaign_For_Woocommerce
 * @subpackage Activecampaign_For_Woocommerce/includes
 */

use Activecampaign_For_Woocommerce_Logger as Logger;
use Activecampaign_For_Woocommerce_Scheduler_Handler as AC_Scheduler;

/**
 * Fired during plugin deactivation.
 *
 * This class defines all code necessary to run during the plugin's deactivation.
 *
 * @since      1.0.0
 * @package    Activecampaign_For_Woocommerce
 * @subpackage Activecampaign_For_Woocommerce/includes
 * @author     acteamintegrations <team-integrations@activecampaign.com>
 */
class Activecampaign_For_Woocommerce_Deactivator {

	/**
	 * The custom ActiveCampaign logger
	 *
	 * @var Activecampaign_For_Woocommerce_Logger
	 */
	private $logger;

	/**
	 * Deactivation script.
	 *
	 * @since    1.0.0
	 */
	public function deactivate() {
		$this->logger = new Logger();
		// Should we clean the table out on deactivation?
		$this->logger->info( 'Deactivation running...' );
		AC_Scheduler::remove_all_events();
		$this->logger->info( 'ActiveCampaign for WooCommerce Deactivated.' );
	}
}
