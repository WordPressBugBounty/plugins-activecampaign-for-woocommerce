<?php

/**
 * The file that defines Request ID service.
 *
 * A class that provides a request ID for the current request.
 *
 * @link       https://www.activecampaign.com/
 * @since      1.2.11
 *
 * @package    Activecampaign_For_Woocommerce
 * @subpackage Activecampaign_For_Woocommerce/includes/services
 */

/**
 * This is used to add a request ID to Woocommerce logs and outbound requests to ActiveCampaign.
 *
 * @since      1.2.11
 * @package    Activecampaign_For_Woocommerce
 * @subpackage Activecampaign_For_Woocommerce/includes
 * @author     acteamintegrations <team-integrations@activecampaign.com>
 */
class Activecampaign_For_Woocommerce_Request_Id_Service {
	/**
	 * The request ID for this request.
	 *
	 * @since  1.2.11
	 * @access private
	 * @var    string
	 */
	private static $request_id;

	/**
	 * Get the request ID for this request.
	 *
	 * @since  1.2.11
	 * @access public
	 * @return string
	 */
	public static function get_request_id() {
		if ( static::$request_id ) {
			return static::$request_id;
		}

		static::$request_id = uniqid( 'ac_woocommerce_', true );
		return static::$request_id;
	}
}
