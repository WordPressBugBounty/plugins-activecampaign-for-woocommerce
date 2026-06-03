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

use Activecampaign_For_Woocommerce_Logger as Logger;

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
		$status           = get_option( ACTIVECAMPAIGN_FOR_WOOCOMMERCE_ACCOUNT_ACTIVE_STATUS, 'true' );
		$delay            = (int) get_option( ACTIVECAMPAIGN_FOR_WOOCOMMERCE_ACCOUNT_INACTIVE_TIME_DELAY, 0 );
		$account_blocked_at = (int) get_option( ACTIVECAMPAIGN_FOR_WOOCOMMERCE_ACCOUNT_BLOCKED_TIMESTAMP, 0 );

		$unblock_at = $account_blocked_at + $delay;

		return ( 'false' === $status && $unblock_at > time() );
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
		$blocked_at = (int) get_option( ACTIVECAMPAIGN_FOR_WOOCOMMERCE_ACCOUNT_BLOCKED_TIMESTAMP, 0 );
		$delay      = (int) get_option( ACTIVECAMPAIGN_FOR_WOOCOMMERCE_ACCOUNT_INACTIVE_TIME_DELAY, 0 );

		if ( $blocked_at > 0 && $delay > 0 ) {
			$blocked_until_timestamp = $blocked_at + $delay;

			if ( time() >= $blocked_until_timestamp ) {
				return '';
			}

			$readable_date = wp_date( 'Y-m-d H:i:s', $blocked_until_timestamp );

			return sprintf(
			/* translators: %s: The date and time for admin view. */
				__( 'Your integration has been blocked until %s.', 'activecampaign' ),
				'<strong>' . $readable_date . '</strong>'
			);
		}

		return __( 'Integration is currently inactive. Please check your credentials.', 'activecampaign' );
	}

	/**
	 * Get the debug log message recorded when the integration is blocked.
	 *
	 * @param string $endpoint The endpoint that returned the 503 response.
	 *
	 * @return string
	 */
	public static function get_debug_error_log_message( $endpoint = 'unknown' ) {
		return sprintf(
			'[%s] ACCOUNT BLOCKED: Endpoint %s returned 503. Outgoing requests have been blocked.',
			gmdate( 'Y-m-d H:i:s' ),
			$endpoint
		);
	}

	/**
	 * This method should set a delay for processing any external request from plugin
	 * This will be applied when AC API returns 503 status code with Retry-After header (in seconds)
	 *
	 * @return void
	 */
	public static function block_account_timestamp(): void {
		update_option( ACTIVECAMPAIGN_FOR_WOOCOMMERCE_ACCOUNT_BLOCKED_TIMESTAMP, time() );
	}

	/**
	 * This method should set a delay for processing any external request from plugin
	 * This will be applied when AC API returns 503 status code with Retry-After header (in seconds)
	 *
	 * @param int $value
	 *
	 * @return void
	 */
	public static function block_account_until( $value ): void {
		self::block_account();
		self::block_account_timestamp();
		update_option( ACTIVECAMPAIGN_FOR_WOOCOMMERCE_ACCOUNT_INACTIVE_TIME_DELAY, $value );
	}

	/**
	 * Setup manually scheduler for this particular job
	 *
	 * @return void
	 */
	public static function setup_scheduler_task_for_unblock_account() {

		$args      = [];
		$delay     = get_option( ACTIVECAMPAIGN_FOR_WOOCOMMERCE_ACCOUNT_INACTIVE_TIME_DELAY, 0 );
		$delay_from = get_option( ACTIVECAMPAIGN_FOR_WOOCOMMERCE_ACCOUNT_BLOCKED_TIMESTAMP, 0 );
		$run_at     = $delay_from + $delay;
		if ( function_exists( 'as_schedule_single_action' ) && did_action( 'action_scheduler_init' ) ) {
			as_schedule_single_action( $run_at, 'activecampaign_for_woocommerce_auto_unblock_account', $args, 'activecampaign_for_woocommerce' );
		} else {
			add_action(
				'action_scheduler_init',
				function () use ( $run_at, $args ) {
					as_schedule_single_action( $run_at, 'activecampaign_for_woocommerce_auto_unblock_account', $args, 'activecampaign_for_woocommerce' );
				}
			);
		}

		$logger        = new Logger();
		$run_at_readable = wp_date( 'Y-m-d H:i:s', $run_at );
		$logger->info( "Scheduler job setup to automatically unblock account after $run_at_readable delay." );
	}

	/**
	 * Call when scheduler job is executed and integration should be unblocked
	 * or user unblock integration manually via button
	 *
	 * @return void
	 */
	public static function clean() {
		self::unblock_account();
		delete_option( ACTIVECAMPAIGN_FOR_WOOCOMMERCE_ACCOUNT_INACTIVE_TIME_DELAY );
		delete_option( ACTIVECAMPAIGN_FOR_WOOCOMMERCE_ACCOUNT_BLOCKED_TIMESTAMP );
	}
}
