<?php

/**
 * Fired when the plugin is uninstalled.
 *
 * @link       https://www.activecampaign.com/
 * @since      1.0.0
 *
 * @package    Activecampaign_For_Woocommerce
 */

use Activecampaign_For_Woocommerce_Uninstall_Plugin_Command as Uninstall_Plugin;

// If uninstall not called from WordPress, then exit.
if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

require_once __DIR__ . '/includes/config/activecampaign-for-woocommerce-bootstrap.php';

/**
 * Uninstalls the plugin.
 */
function activecampaign_for_woocommerce_uninstall() {
	$uninstall_command = new Uninstall_Plugin();

	$uninstall_command->execute();
}

activecampaign_for_woocommerce_uninstall();
