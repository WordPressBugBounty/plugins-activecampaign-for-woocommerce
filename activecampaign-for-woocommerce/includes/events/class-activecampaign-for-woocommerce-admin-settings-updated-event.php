<?php

/**
 * The file that defines the Admin Settings Updated Event Class.
 *
 * @link       https://www.activecampaign.com/
 * @since      1.0.0
 *
 * @package    Activecampaign_For_Woocommerce
 * @subpackage Activecampaign_For_Woocommerce/includes/events
 */

use Activecampaign_For_Woocommerce_Triggerable_Interface as Triggerable;

/**
 * The Admin Settings Updated Event Class.
 *
 * @since      1.0.0
 * @package    Activecampaign_For_Woocommerce
 * @subpackage Activecampaign_For_Woocommerce/includes/events
 * @author     acteamintegrations <team-integrations@activecampaign.com>
 */
class Activecampaign_For_Woocommerce_Admin_Settings_Updated_Event implements Triggerable {
	/**
	 * Called when admin settings are updated.
	 *
	 * @param array ...$args An array of all arguments passed in.
	 *
	 * @since 1.0.0
	 */
	public function trigger( ...$args ) {
		$additional_arguments = isset( $args[0] ) ? $args[0] : [];

		do_action( 'activecampaign_for_woocommerce_admin_settings_updated', $additional_arguments );
		do_action( 'activecampaign_for_woocommerce_run_sync_connection' );
	}
}
