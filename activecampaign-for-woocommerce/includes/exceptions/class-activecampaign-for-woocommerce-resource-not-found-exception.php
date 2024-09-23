<?php

/**
 * The file that defines the ResourceNotFound Exception.
 *
 * @link       https://www.activecampaign.com/
 * @since      1.0.0
 *
 * @package    Activecampaign_For_Woocommerce
 * @subpackage Activecampaign_For_Woocommerce/includes/exceptions
 */

/**
 * The ResourceNotFound Exception class.
 *
 * @since      1.0.0
 * @package    Activecampaign_For_Woocommerce
 * @subpackage Activecampaign_For_Woocommerce/includes/exceptions
 * @author     acteamintegrations <team-integrations@activecampaign.com>
 */
class Activecampaign_For_Woocommerce_Resource_Not_Found_Exception extends Exception {
	/**
	 * Activecampaign_For_Woocommerce_Resource_Not_Found_Exception constructor.
	 *
	 * @param string $message The message for the exception.
	 * @param array  $context The json serialized context array.
	 * @param int    $code The error code.
	 * @param null   $previous The previous exception.
	 */
	public function __construct( $message = '', $context = [], $code = 0, $previous = null ) {
		if ( ! array_key_exists( 'trace', $context ) ) {
			$context['trace'] = $this->getTrace();
		}

		$message .= ' ' . wp_json_encode( $context );
		parent::__construct( $message, $code, $previous );
	}
}
