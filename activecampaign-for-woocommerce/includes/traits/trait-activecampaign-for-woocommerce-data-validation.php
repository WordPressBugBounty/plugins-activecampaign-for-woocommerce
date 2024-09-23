<?php

use Activecampaign_For_Woocommerce_Logger as Logger;

/**
 * The file that defines the Global Utilities.
 *
 * @link       https://www.activecampaign.com/
 * @since      1.x
 *
 * @package    Activecampaign_For_Woocommerce
 */

/**
 * The Utilities Class.
 *
 * @since      1.0.0
 * @package    Activecampaign_For_Woocommerce
 * @subpackage Activecampaign_For_Woocommerce/includes/events
 * @author     acteamintegrations <team-integrations@activecampaign.com>
 */
trait Activecampaign_For_Woocommerce_Data_Validation {
	/**
	 * Validates an object with isset check and method_exists check in one call.
	 *
	 * @param     object $o     The string|object.
	 * @param     string $s     The string for the call.
	 *
	 * @return bool
	 */
	private static function validate_object( $o, $s ) {
		if (
			isset( $o ) &&
			( is_object( $o ) || is_string( $o ) ) &&
			method_exists( $o, $s )
		) {
			return true;
		}

		return false;
	}

	/**
	 * Validates if an email is valid for syncing to ActiveCampaign.
	 *
	 * @param     string $email     The email address.
	 *
	 * @return bool
	 */
	private static function check_valid_email( $email ) {
		$email_valid = false;
		try {
			if ( ! empty( $email ) ) {

				// GET EMAIL PARTS
				$domain = ltrim( stristr( $email, '@' ), '@' ) . '.';
				$user   = stristr( $email, '@', true );

				// VALIDATE EMAIL ADDRESS
				if (
					! empty( $user ) &&
					! empty( $domain ) &&
					is_email( $email ) &&
					filter_var( $email, FILTER_VALIDATE_EMAIL )
				) {
					$email_valid = true;
				}
			}
		} catch ( Throwable $t ) {
			$logger = new Logger();
			$logger->warning(
				'There was an issue validating this email for syncing.',
				[
					'email'   => $email,
					'message' => $t->getMessage(),
					'trace'   => $logger->clean_trace( $t->getTrace() ),
					'ac_code' => 'DVAL_70',
				]
			);
		}
		return $email_valid;
	}

	/**
	 * Verifies that the order object has data to process.
	 *
	 * @param WC_Order $wc_order The WooCommerce order.
	 *
	 * @return bool
	 */
	private function order_has_required_data( $wc_order ) {
		try {
			if (
				self::validate_object( $wc_order, 'get_data' ) &&
				! empty( $wc_order->get_data() ) &&
				self::validate_object( $wc_order, 'get_billing_email' ) &&
				! empty( $wc_order->get_billing_email() )
			) {
				if ( 'trash' === $wc_order->get_status() ) {
					$this->logger->warning(
						'This order is trashed and cannot be synced.',
						[
							'order_status' => self::validate_object( $wc_order, 'get_status' ) ? $wc_order->get_status() : null,
						]
					);

					return false;
				}

				if ( self::check_valid_email( $wc_order->get_billing_email() ) ) {
					return true;
				} else {
					$this->logger->warning(
						'This order does not have a compatible email.',
						[
							'billing_email' => self::validate_object( $wc_order, 'get_billing_email' ) ? $wc_order->get_billing_email() : null,
						]
					);
				}
			} else {
				$this->logger->warning(
					'This order is missing one of the following required data for this order. (id, order number, or billing email)',
					[
						'id'            => self::validate_object( $wc_order, 'get_id' ) ? $wc_order->get_id() : null,
						'order_number'  => self::validate_object( $wc_order, 'get_order_number' ) ? $wc_order->get_order_number() : null,
						'billing_email' => self::validate_object( $wc_order, 'get_billing_email' ) ? $wc_order->get_billing_email() : null,
						'ac_code'       => 'DVAL_110',
					]
				);
			}
		} catch ( Throwable $t ) {
			$this->logger->warning(
				'There was an issue validating the WC order data.',
				[
					'order'       => wp_json_encode( $wc_order ),
					'message'     => $t->getMessage(),
					'stack_trace' => $this->logger->clean_trace( $t->getTrace() ),
					'ac_code'     => 'DVAL_120',
				]
			);
		}

		return false;
	}

	/**
	 * Checks if the order contains a refund.
	 *
	 * @param string|WC_Order|object $order The order object.
	 *
	 * @return bool
	 */
	private function is_refund_order( $order ) {
		try {
			if ( self::validate_object( $order, 'get_item_count_refunded' ) && $order->get_item_count_refunded() > 0 ) {
				// refunds don't work yet
				$this->logger->debug(
					'Historical sync cannot currently sync refund data. This order will be ignored.',
					[
						'order_id'            => self::validate_object( $order, 'get_id' ) ? $order->get_id() : null,
						'item_count_refunded' => self::validate_object( $order, 'get_item_count_refunded' ) ? $order->get_item_count_refunded() : null,
					]
				);

				return true;
			}
		} catch ( Throwable $t ) {
			$this->logger->notice(
				'There was an issue verifying an order as refund.',
				[
					'order'       => wp_json_encode( $order ),
					'message'     => $t->getMessage(),
					'stack_trace' => $this->logger->clean_trace( $t->getTrace() ),
				]
			);

			return true;
		}

		return false;
	}

	/**
	 * Cleans a description field by removing tags and shortening the number of characters.
	 *
	 * @param     string $description     The description.
	 * @param     int    $trim     Character trim length for word wrap. Trimmed by default. Pass 0 to not trim.
	 *
	 * @return string
	 */
	private function clean_description( $description, $trim = 300 ) {
		$logger = new Logger();

		try {
			$plain_description = wp_strip_all_tags( $description, false );
			$plain_description = str_replace( array( "\r", "\n", '&nbsp;' ), ' ', $plain_description );
			$plain_description = trim( $plain_description );
			$plain_description = preg_replace( '/\s+/', ' ', $plain_description );

			if ( $trim > 0 && strlen( $plain_description ) > $trim ) {
				$wrap_description = wordwrap( $plain_description, $trim - 3 );
				$description_arr  = explode( "\n", $wrap_description );
				if ( isset( $description_arr[0] ) ) {
					$fin_description = $description_arr[0];
					if ( isset( $description_arr[1] ) ) {
						$fin_description .= '...';
					}

					if ( ! empty( $fin_description ) ) {
						return $fin_description;
					}
				}
			}
		} catch ( Throwable $t ) {
			$logger->warning(
				'There was an issue cleaning the description field.',
				[
					'message'     => $t->getMessage(),
					'description' => $description,
				]
			);
		}

		if ( ! empty( $plain_description ) ) {
			return $plain_description;
		}

		return $description;
	}

}
