<?php
/**
 * The account status page specific functionality of the plugin.
 *
 * @link       https://www.activecampaign.com/
 * @since      2.1.0
 *
 * @package    Activecampaign_For_Woocommerce
 * @subpackage Activecampaign_For_Woocommerce/admin
 * @author     acteamintegrations <team-integrations@activecampaign.com>
 */

/**
 * Class Activecampaign_For_Woocommerce_Account_Status_Manager
 *
 * The account-specific functionality of the plugin.
 */
class Activecampaign_For_Woocommerce_Account_Status_Manager {

	/**
	 * To indicate if there is some issue with error status code 503 from AC.
	 * If so, block request from plugin to prevent redundant API calls.
	 * Users can remove this blockade manually from WP admin plugin page.
	 *
	 * @return bool Whether outgoing requests are currently blocked.
	 */
	public static function is_blocked() {
		return get_option( ACTIVECAMPAIGN_FOR_WOOCOMMERCE_ACCOUNT_ACTIVE_STATUS, 'true' ) === 'false';
	}

	/**
	 * Block account (only in plugin - like we assert that this account might be temporary unavailable due to 503 http error code).
	 */
	public static function block_account() {
		update_option( ACTIVECAMPAIGN_FOR_WOOCOMMERCE_ACCOUNT_ACTIVE_STATUS, 'false' );
	}

	/**
	 * Remove blockade manually from admin panel.
	 */
	public static function unblock_account() {
		update_option( ACTIVECAMPAIGN_FOR_WOOCOMMERCE_ACCOUNT_ACTIVE_STATUS, 'true' );
	}

	/**
	 * Get the user-facing error message displayed when the integration is blocked.
	 *
	 * @return string
	 */
	public static function get_error_user_friendly_message() {
		return __( 'Your integration has been automatically blocked due to multiple failed connection attempts (Error 503). Please check your logs for details or contact support.', 'activecampaign' );
	}

	/**
	 * Get the debug log message recorded when the integration is blocked.
	 *
	 * @param string $endpoint The endpoint that returned the 503 response.
	 * @return string
	 */
	public static function get_debug_error_log_message( $endpoint = 'unknown' ) {
		return sprintf(
			'[%s] ACCOUNT BLOCKED: Endpoint %s returned 503. Outgoing requests have been blocked.',
			gmdate( 'Y-m-d H:i:s' ),
			$endpoint
		);
	}
}
