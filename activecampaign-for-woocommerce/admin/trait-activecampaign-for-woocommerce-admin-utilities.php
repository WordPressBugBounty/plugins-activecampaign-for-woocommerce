<?php

/**
 * The admin status page specific functionality of the plugin.
 *
 * @link       https://www.activecampaign.com/
 * @since      1.8.0
 *
 * @package    Activecampaign_For_Woocommerce
 * @subpackage Activecampaign_For_Woocommerce/admin
 */

use Activecampaign_For_Woocommerce_Logger as Logger;


/**
 * The admin-specific functionality of the plugin.
 *
 * Defines the plugin name, version, and two examples hooks for how to
 * enqueue the admin-specific stylesheet and JavaScript.
 *
 * @package    Activecampaign_For_Woocommerce
 * @subpackage Activecampaign_For_Woocommerce/admin
 * @author     acteamintegrations <team-integrations@activecampaign.com>
 */
trait Activecampaign_For_Woocommerce_Admin_Utilities {
	use Activecampaign_For_Woocommerce_Data_Validation,
		Activecampaign_For_Woocommerce_Arg_Data_Gathering;
	/**
	 * Initialize the class and set its properties.
	 */
	public function __construct() {}

	/**
	 * Validates the request nonce for any form to ensure valid requests by passing the form action name.
	 *
	 * @param string $action_name The action name.
	 *
	 * @return bool
	 */
	private function validate_request_nonce( $action_name ) {
		/**
		 * Validate the nonce created for this specific form action.
		 * The nonce input is generated in the template using the wp_nonce_field().
		 */

		$logger = new Logger();
		$valid  = false;

		try {
			$nonce = self::get_request_data( 'activecampaign_for_woocommerce_settings_nonce_field' );
			$valid = wp_verify_nonce( $nonce, $action_name );
		} catch ( Throwable $t ) {
			$logger->notice(
				'There was an issue validating the request nonce.',
				array(
					'message'          => $t->getMessage(),
					'suggested_action' => 'Check the message and action name for more details.',
					'action_name'      => $action_name,
					'ac_code'          => 'ADUTIL_54',
				)
			);
		}

		if ( ! $valid ) {
			try {
				$logger->warning(
					'Invalid nonce:',
					array(
						'action_name' => $action_name,
						'request'     => $_REQUEST,
						'get'         => $_GET,
						'post'        => $_POST,
						'ac_code'     => 'ADUTIL_66',
					)
				);
			} catch ( Throwable $t ) {
				$logger->warning(
					'A request type was not allowed to log.',
					array(
						'message' => $t->getMessage(),
					)
				);
			}

			$this->push_response_error(
				$this->format_response_message( 'Form nonce is invalid.', 'error' )
			);
		}

		return $valid;
	}

	/**
	 * Extracts from the $_POST superglobal an array of sanitized data.
	 *
	 * Before sanitizing the data, certain key/value pairs from the array are
	 * removed. This is because CSRF values are currently in the POST body
	 * and we do not want to persist them to the DB.
	 *
	 * @return array
	 */
	private function extract_post_data() {
		$request = wp_unslash( $_REQUEST );
		if ( isset( $request['activecampaign_for_woocommerce_settings_nonce_field'] ) && wp_verify_nonce( $request['activecampaign_for_woocommerce_settings_nonce_field'], 'activecampaign_for_woocommerce_settings_form' ) ) {
			$post_data = wp_unslash( $_POST );

			/**
			 * Unset all the form fields that don't need to be persisted in the DB.
			 */
			unset( $post_data['action'] );
			unset( $post_data['activecampaign_for_woocommerce_settings_nonce_field'] );
			unset( $post_data['_wp_http_referer'] );

			/**
			 * Map through all values sent in and sanitize them.
			 */
			$post_data = array_map(
				function ( $entry ) {
					return sanitize_text_field( $entry );
				},
				$post_data
			);

			return $post_data;
		}
	}

	/**
	 * Translates and sanitizes error/notice messages into an associative array.
	 *
	 * This will be returned as part of a response to be displayed as a notice in the
	 * admin section of the site.
	 *
	 * @param     string $message     The message that will be translated and returned.
	 * @param     string $level     The notice level (e.g. info, success...).
	 *
	 * @return array
	 */
	private function format_response_message( $message, $level = 'info' ) {
		// phpcs:disable
		return [
			'level'   => sanitize_text_field( $level ),
			'message' => esc_html__(
				$message,
				ACTIVECAMPAIGN_FOR_WOOCOMMERCE_LOCALIZATION_DOMAIN
			),
		];
		// phpcs:enable
	}

	public function get_default_settings() {
		return $this->set_default_settings( null );
	}

	private function set_default_settings( $current_options = null ) {
		$logger = new Logger();

		if ( is_array( $current_options ) ) {
			$options_to_be_saved = $current_options;
		} else {
			$options_to_be_saved = [];
		}

		try {

			if ( ! isset( $current_options['abcart_wait'] ) ) {
				$options_to_be_saved['abcart_wait'] = '1';
			}

			if ( ! isset( $current_options['optin_checkbox_text'] ) ) {
				$options_to_be_saved['optin_checkbox_text'] = 'Keep me up to date on news and exclusive offers';
			}

			if ( ! isset( $current_options['sync_batch_runs'] ) ) {
				$options_to_be_saved['sync_batch_runs'] = 10;
			}

			if ( ! isset( $current_options['sync_batch_limit'] ) ) {
				$options_to_be_saved['sync_batch_limit'] = 50;
			}

			if ( ! isset( $current_options[ ACTIVECAMPAIGN_FOR_WOOCOMMERCE_PRODUCT_SYNC_ENABLED_NAME ] ) ) {
				$options_to_be_saved[ ACTIVECAMPAIGN_FOR_WOOCOMMERCE_PRODUCT_SYNC_ENABLED_NAME ] = 1;
			}

			if ( ! isset( $current_options['custom_email_field'] ) ) {
				$options_to_be_saved['custom_email_field'] = 'billing_email';
			}

			if ( ! isset( $current_options['ac_emailoption'] ) ) {
				$options_to_be_saved['ac_emailoption'] = '0';
			}
			if ( ! isset( $current_options['checkbox_display_option'] ) ) {
				$options_to_be_saved['checkbox_display_option'] = 'visible_checked_by_default';
			}

			if ( ! isset( $current_options['ac_debug'] ) ) {
				$options_to_be_saved['ac_debug']        = '0';
				$options_to_be_saved['ac_debug_calls']  = '0';
				$options_to_be_saved['ac_debug_excess'] = '0';
			}
		} catch ( Throwable $t ) {
			$logger->error(
				'The plugin activation process encountered an exception.',
				[
					'message' => $t->getMessage(),
					'ac_code' => 'ACT_73',
				]
			);
		}
		return $options_to_be_saved;
	}

}
