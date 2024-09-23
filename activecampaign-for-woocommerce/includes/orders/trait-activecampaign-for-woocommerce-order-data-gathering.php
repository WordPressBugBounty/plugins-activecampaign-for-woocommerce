<?php

/**
 * Various order utilities for the Activecampaign_For_Woocommerce plugin.
 *
 * @link       https://www.activecampaign.com/
 * @since      1.5.0
 *
 * @package    Activecampaign_For_Woocommerce
 * @subpackage Activecampaign_For_Woocommerce/includes
 */

use Activecampaign_For_Woocommerce_Logger as Logger;
use Activecampaign_For_Woocommerce_Ecom_Discount as Ecom_Discount;
use Activecampaign_For_Woocommerce_Ecom_Enum_Type as Enumish;

/**
 * The Order Utilities Class.
 *
 * @since      1.5.0
 * @package    Activecampaign_For_Woocommerce
 * @subpackage Activecampaign_For_Woocommerce/includes/orders
 * @author     acteamintegrations <team-integrations@activecampaign.com>
 */
trait Activecampaign_For_Woocommerce_Order_Data_Gathering {
	use Activecampaign_For_Woocommerce_Data_Validation;

	/**
	 * We absolutely need the WC_Order so we need to make every attempt to get it for a valid order.
	 * The order could be anything so we have to make every attempt to get WC_Order from whatever we get from WC.
	 *
	 * @param object|string|array $order The unknown order item passed back from WC.
	 *
	 * @return bool|WC_Order
	 */
	public function get_wc_order( $order ) {
		if ( self::validate_object( $order, 'get_id' ) && ! empty( $order->get_id() ) ) {
			return $order;
		}

		// If it's not a valid WC_Order, try using it as a non WC object.
		try {
			if ( is_object( $order ) ) {
				$wc_order = wc_get_order( $order );

				if ( $this->is_valid_wc_order( $wc_order ) ) {
					return $wc_order;
				}

				$wc_order = wc_get_order( $order->get_id() );

				if ( $this->is_valid_wc_order( $wc_order ) ) {
					return $wc_order;
				}
			}
		} catch ( Throwable $t ) {
			$this->logger->debug(
				'Order Utilities: wc_get_order threw an error on the order object. ',
				[
					'message'     => $t->getMessage(),
					'order class' => get_class( $order ),
				]
			);
		}

		// Try the order as an array
		try {
			if ( is_array( $order ) ) {
				$wc_order = wc_get_order( $order['id'] );

				if ( $this->is_valid_wc_order( $wc_order ) ) {
					return $wc_order;
				}
			}
		} catch ( Throwable $t ) {
			$this->logger->debug(
				'Order Utilities: There was an issue parsing this order as an array.',
				[
					'message' => $t->getMessage(),
				]
			);
		}

		try {
			$wc_order = wc_get_order( $order );

			if ( $this->is_valid_wc_order( $wc_order ) ) {
				return $wc_order;
			}
		} catch ( Throwable $t ) {
			$this->logger->debug(
				'Order Utilities: A final WC_Order object failed to retrieve.',
				[
					'message' => $t->getMessage(),
					'order'   => self::validate_object( $wc_order, 'get_data' ) ? $wc_order->get_data() : null,
				]
			);
		}

		try {
			if ( self::validate_object( $order, 'get_id' ) ) {
				$wc_order = new WC_Order( $order->get_id() );
			} elseif ( isset( $order['id'] ) ) {
				$wc_order = new WC_Order( $order['id'] );
			} else {
				$wc_order = new WC_Order( $order );
			}

			if ( $this->is_valid_wc_order( $wc_order ) ) {
				return $wc_order;
			}
		} catch ( Throwable $t ) {
			$this->logger->debug(
				'Order Utilities: Could not create a new WC_Order from any known data type variation.',
				[
					'message' => $t->getMessage(),
				]
			);
		}

		$this->logger->warning(
			'Order Data Gathering: A WC_Order object could not be retrieved from WooCommerce. This order may be missing or deleted.',
			[
				'order' => $order,
			]
		);

		return false;
	}

	public function get_wc_subscription( $subscription ) {
		if ( self::validate_object( $subscription, 'get_id' ) && ! empty( $subscription->get_id() ) ) {
			return $subscription;
		}

		// If it's not a valid WC_Order, try using it as a non WC object.
		try {
			if ( is_object( $subscription ) ) {
				$wc_subscription = wcs_get_subscription( $subscription );

				if ( $this->is_valid_wc_subscription( $wc_subscription ) ) {
					return $wc_subscription;
				}

				$wc_subscription = wcs_get_subscription( $subscription->get_id() );

				if ( $this->is_valid_wc_subscription( $wc_subscription ) ) {
					return $wc_subscription;
				}
			}
		} catch ( Throwable $t ) {
			$this->logger->debug(
				'Order Utilities: wc_get_subscription threw an error on the order object. ',
				[
					'message'     => $t->getMessage(),
					'order class' => get_class( $subscription ),
				]
			);
		}

		// Try the order as an array
		try {
			if ( is_array( $subscription ) ) {
				$wc_subscription = wcs_get_subscription( $subscription['id'] );

				if ( $this->is_valid_wc_subscription( $wc_subscription ) ) {
					return $wc_subscription;
				}
			}
		} catch ( Throwable $t ) {
			$this->logger->debug(
				'Order Utilities: There was an issue parsing this subscription as an array.',
				[
					'message' => $t->getMessage(),
				]
			);
		}

		try {
			if ( self::validate_object( $subscription, 'get_id' ) ) {
				$wc_subscription = new WC_Subscription( $subscription->get_id() );
			} elseif ( isset( $subscription['id'] ) ) {
				$wc_subscription = new WC_Subscription( $subscription['id'] );
			} else {
				$wc_subscription = new WC_Subscription( $subscription );
			}

			if ( $this->is_valid_wc_subscription( $wc_subscription ) ) {
				return $wc_subscription;
			}
		} catch ( Throwable $t ) {
			$this->logger->debug(
				'Order Utilities: Could not create a new WC_Order from any known data type variation.',
				[
					'message' => $t->getMessage(),
				]
			);
		}

		$this->logger->warning(
			'Order Data Gathering: A WC_Subscription object could not be retrieved from WooCommerce. This order may be missing or deleted.',
			[
				'subscription' => $subscription,
			]
		);

		return false;
	}

	public function is_valid_wc_order( $wc_order ) {
		if (
			self::validate_object( $wc_order, 'get_id' ) &&
			self::validate_object( $wc_order, 'get_data' ) &&
			 ! empty( $wc_order->get_id() )
		) {
			return true;
		}

		return false;
	}

	public function is_valid_wc_subscription( $wc_subscription ) {
		if (
			self::validate_object( $wc_subscription, 'get_id' ) &&
			self::validate_object( $wc_subscription, 'get_data' ) &&
			! empty( $wc_subscription->get_id() )
		) {
			return true;
		}

		return false;
	}


	/**
	 * Gets the coupons from the order.
	 *
	 * @param WC_Order $wc_order The WC order.
	 *
	 * @return array
	 */
	public function get_coupons_from_order( $wc_order ) {
		$ecom_coupons = [];
		$wc_coupons   = $wc_order->get_coupons();

		if ( isset( $wc_coupons ) && count( $wc_coupons ) > 0 ) {
			$ecom_coupons = [];

			foreach ( $wc_coupons as $coupon ) {
				$ecom_coupons[] = $this->get_coupon_data( $coupon );
			}
		}

		return $ecom_coupons;
	}

	/**
	 * Gets the coupon data and creates an object to pass back.
	 *
	 * @param     WC_Order_Item_Coupon $coupon     The WC coupon object.
	 *
	 * @return array The coupon class.
	 */
	private function get_coupon_data( $coupon ) {
		try {
			$object = new Ecom_Discount();
			$object->set_type( new Enumish( 'ORDER' ) ); // order or shipping

			if ( self::validate_object( $coupon, 'get_code' ) ) {
				$object->set_name( $coupon->get_code() ); // string
			} else {
				$object->set_name( 'Unavailable' );
			}

			if ( self::validate_object( $coupon, 'get_discount' ) ) {
				$object->set_discount_amount( $coupon->get_discount() );
			}

			return $object->serialize_to_array();
		} catch ( Throwable $t ) {
			$logger = new Logger();
			$logger->notice(
				'There was an issue retrieving coupon data for an order.',
				[
					'message' => $t->getMessage(),
					'coupon'  => $coupon,
					'trace'   => $logger->clean_trace( $t->getTrace() ),
				]
			);
		}
	}

	/**
	 * Verifies the status of the order for sending to AC
	 *
	 * @param     string $status     The order status.
	 *
	 * @return bool Whether or not the order passes.
	 */
	public function is_failed_order( $status ) {
		if ( ! empty( $status ) ) {
			$failed_statuses = [ 'wc-cancelled', 'wc-failed', 'failed', 'cancelled' ];

			return in_array( $status, $failed_statuses, true );
		}

		return false;
	}

	/**
	 * Verifies the status of the order for sending to AC
	 *
	 * @param     string $status     The order status.
	 *
	 * @return bool Whether or not the order passes.
	 */
	public function verify_order_status( $status ) {
		if ( ! empty( $status ) ) {
			$accepted_statuses = [ 'completed', 'processing', 'wc-completed', 'wc-processing' ];

			return in_array( $status, $accepted_statuses, true );
		}

		return false;
	}

	/**
	 * Get the ActiveCampaign ID.
	 *
	 * @param string|int $order_id The order ID.
	 *
	 * @return mixed|string|null
	 */
	public function get_ac_order_id( $order_id ) {
		// check if we have it in storage ac_order_id
		$ac_order_id = $this->get_ac_orderid_from_wc_order( $order_id );

		if ( ! isset( $ac_order_id ) ) {
			// check ac by externalcheckoutid
			$externalcheckout_id = $this->get_externalcheckoutid_from_table_by_orderid( $order_id );

			if ( ! empty( $externalcheckout_id ) ) {
				$order_ac = $this->order_repository->find_by_externalcheckoutid( $externalcheckout_id );
			}

			if ( ! isset( $order_ac ) || ! self::validate_object( $order_ac, 'get_id' ) || empty( $order_ac->get_id() ) ) {
				// check ac by external order id
				$order_ac = $this->order_repository->find_by_externalid( $order_id );
			}

			$ac_order_id = $order_ac->get_id();
		}

		return $ac_order_id;
	}

	/**
	 * Gets the externalcheckoutid from our unsynced table.
	 *
	 * @param string|int $order_id The order ID.
	 *
	 * @return string|null
	 */
	public function get_externalcheckoutid_from_table_by_orderid( $order_id ) {
		global $wpdb;
		$logger = new Logger();
		try {
			// Get the expired carts from our table
			$ac_externalcheckoutid = $wpdb->get_var(
			// phpcs:disable
				$wpdb->prepare( 'SELECT ac_externalcheckoutid 
					FROM
						`' . $wpdb->prefix . ACTIVECAMPAIGN_FOR_WOOCOMMERCE_TABLE_NAME . '`
					WHERE
						abandoned_date IS NOT NULL
						AND wc_order_id = %s LIMIT 1',
					$order_id
				)
			// phpcs:enable
			);

			if ( $wpdb->last_error ) {
				$logger->error(
					'Abandonment sync: There was an error getting results for abandoned cart records.',
					[
						'wpdb_last_error' => $wpdb->last_error,
					]
				);
			}

			if ( ! empty( $ac_externalcheckoutid ) ) {
				// abandoned carts found
				return $ac_externalcheckoutid;
			}
		} catch ( Throwable $t ) {
			$logger->error(
				'Abandonment Sync: There was an error with preparing or getting abandoned cart results.',
				[
					'message' => $t->getMessage(),
					'trace'   => $logger->clean_trace( $t->getTrace() ),
				]
			);
		}

		return null;
	}

	/**
	 * Gets the AC order ID from the unsynced table.
	 *
	 * @param string|int $order_id The order id.
	 *
	 * @return string|null
	 */
	public function get_ac_orderid_from_wc_order( $order_id ) {
		global $wpdb;
		$logger = new Logger();
		try {
			// Get the expired carts from our table
			$ac_order_id = $wpdb->get_var(
			// phpcs:disable
				$wpdb->prepare( 'SELECT ac_order_id 
					FROM
						`' . $wpdb->prefix . ACTIVECAMPAIGN_FOR_WOOCOMMERCE_TABLE_NAME . '`
					WHERE wc_order_id = %s LIMIT 1',
					$order_id
				)
			// phpcs:enable
			);

			if ( $wpdb->last_error ) {
				$logger->error(
					'Abandonment sync: There was an error getting results for abandoned cart records.',
					[
						'wpdb_last_error' => $wpdb->last_error,
					]
				);
			}

			if ( ! empty( $ac_order_id ) ) {
				// abandoned carts found
				return $ac_order_id;
			}
		} catch ( Throwable $t ) {
			$logger->error(
				'Abandonment Sync: There was an error with preparing or getting abandoned cart results.',
				[
					'message' => $t->getMessage(),
					'trace'   => $logger->clean_trace( $t->getTrace() ),
				]
			);
		}

		return null;
	}


	/**
	 * Builds the order url.
	 *
	 * @param array|WC_Order $order The order object.
	 *
	 * @return string
	 */
	public function build_order_url( $order ) {
		if ( ! empty( $order['id'] ) && ! empty( $order['order_key'] ) ) {
			return get_site_url( null, '/checkout/order-received/' . $order['id'] . '?key=' . $order['order_key'] );
		}

		if ( ! empty( $order['id'] ) ) {
			$wc_order = wc_get_order( $order['id'] );
			return $wc_order->get_checkout_order_received_url();
		}

		return get_site_url();
	}
}
