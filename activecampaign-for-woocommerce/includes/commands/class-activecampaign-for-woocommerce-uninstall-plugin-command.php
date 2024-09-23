<?php

/**
 * The file that defines the Uninstall_Plugin_Command Class.
 *
 * @link       https://www.activecampaign.com/
 * @since      1.0.0
 *
 * @package    Activecampaign_For_Woocommerce
 * @subpackage Activecampaign_For_Woocommerce/includes/commands
 */

use Activecampaign_For_Woocommerce_Executable_Interface as Executable;
use Activecampaign_For_Woocommerce_User_Meta_Service as User_Meta_Service;
use Activecampaign_For_Woocommerce_Logger as Logger;

/**
 * The Uninstall_Plugin_Command Class.
 *
 * This command is called when uninstalling the plugin and handled erasing all plugin-specific data.
 *
 * @since      1.0.0
 * @package    Activecampaign_For_Woocommerce
 * @subpackage Activecampaign_For_Woocommerce/includes/commands
 * @author     Joshua Bartlett <jbartlett@activecampaign.com>
 */
class Activecampaign_For_Woocommerce_Uninstall_Plugin_Command implements Executable {
	// phpcs:disable Generic.CodeAnalysis.UnusedFunctionParameter
	/**
	 * Executes the command.
	 *
	 * Erases all plugin specific data from the database.
	 *
	 * @param     mixed ...$args     An array of arguments that may be passed in from the action/filter called.
	 *
	 * @since 1.0.0
	 */
	public function execute( ...$args ) {
		$logger = new Logger();
		global $wpdb;

		delete_option( ACTIVECAMPAIGN_FOR_WOOCOMMERCE_DB_SETTINGS_NAME );
		delete_option( ACTIVECAMPAIGN_FOR_WOOCOMMERCE_DB_CONNECTION_STORAGE_NAME );
		delete_option( 'activecampaign_for_woocommerce_dismiss_error_notice' );
		delete_option( 'activecampaign_for_woocommerce_dismiss_plugin_notice' );
		delete_option( 'activecampaign_for_woocommerce_abandoned_cart_last_run' );
		delete_option( 'activecampaign_for_woocommerce_connection_health_check_last_run' );
		delete_option( 'activecampaign_for_woocommerce_dismiss_error_notice' );
		delete_option( 'activecampaign_for_woocommerce_historical_sync_running_status' );
		delete_option( 'activecampaign_for_woocommerce_historical_sync_scheduled_status' );
		delete_option( 'activecampaign_for_woocommerce_historical_sync_stop' );
		delete_option( 'activecampaign_for_woocommerce_last_order_sync' );
		delete_option( 'activecampaign_for_woocommerce_product_sync_last_status' );

		try {
			User_Meta_Service::delete_all_user_meta();
		} catch ( Throwable $t ) {
			$logger->warning(
				'There was an issue removing the ActiveCampaign user meta data.',
				[
					'action to take' => 'Manually delete the data if necessary.',
					'message'        => $t->getMessage(),
				]
			);
		}

		try {
			// Remove the DB version for our plugin because we're removing our table
			add_option( 'activecampaign_for_woocommerce_db_version', null );
			delete_option( 'activecampaign_for_woocommerce_db_version' );
		} catch ( Throwable $t ) {
			$logger->warning(
				'There was an issue removing the ActiveCampaign DB version value.',
				[
					'action to take' => 'Manually delete the field if you plan on reinstalling this plugin.',
					'message'        => $t->getMessage(),
				]
			);
		}

		try {
			// phpcs:disable
			$wpdb->query( 'DROP TABLE IF EXISTS ' . $wpdb->prefix . ACTIVECAMPAIGN_FOR_WOOCOMMERCE_TABLE_NAME );
			// phpcs:enable
		} catch ( Throwable $t ) {
			$logger->error(
				'There was an issue removing the ' . ACTIVECAMPAIGN_FOR_WOOCOMMERCE_TABLE_NAME . ' table from your database. This process may not have permission.',
				[
					'action to take' => 'Manually delete the ' . $wpdb->prefix . ACTIVECAMPAIGN_FOR_WOOCOMMERCE_TABLE_NAME . ' table if necessary.',
					'message'        => $t->getMessage(),
					'ac_code'        => 'UPC_59',
				]
			);
		}

		try {
			// phpcs:disable
			$wpdb->query( 'DROP TABLE IF EXISTS ' . $wpdb->prefix . 'activecampaign_for_woocommerce_abandoned_cart' );
			// phpcs:enable
		} catch ( Throwable $t ) {
			$logger->error(
				'There was an issue removing the old activecampaign_for_woocommerce_abandoned_cart table from your database. This process may not have permission.',
				[
					'action to take' => 'Manually delete the ' . $wpdb->prefix . ACTIVECAMPAIGN_FOR_WOOCOMMERCE_TABLE_NAME . ' table if necessary.',
					'message'        => $t->getMessage(),
				]
			);
		}
	}
	// phpcs:enable
}
