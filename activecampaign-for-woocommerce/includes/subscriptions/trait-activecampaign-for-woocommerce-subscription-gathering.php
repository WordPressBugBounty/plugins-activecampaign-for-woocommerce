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
use Activecampaign_For_Woocommerce_Subscription_Model as Cofe_Ecom_Order_Subscription;
use Activecampaign_For_Woocommerce_Subscription_Customer_Model as Ecom_Subscription_Customer;
use Activecampaign_For_Woocommerce_User_Meta_Service as User_Meta_Service;

/**
 * The Subscription Utilities Class.
 *
 * @since      1.5.0
 * @package    Activecampaign_For_Woocommerce
 * @subpackage Activecampaign_For_Woocommerce/includes/subscriptions
 * @author     acteamintegrations <team-integrations@activecampaign.com>
 */
trait Activecampaign_For_Woocommerce_Subscription_Data_Gathering {
	use Activecampaign_For_Woocommerce_Data_Validation,
		Activecampaign_For_Woocommerce_Order_Line_Item_Gathering;

	private function init() {
		$admin_storage = get_option( ACTIVECAMPAIGN_FOR_WOOCOMMERCE_DB_CONNECTION_STORAGE_NAME );
		if ( ! empty( $admin_storage ) && isset( $admin_storage['connection_id'] ) ) {
			$this->connection_id = $admin_storage['connection_id'];
		}
	}

	/**
	 * Sets up cofe subscription from table data.
	 *
	 * @param     WC_Subscription $wc_subscription The WC subscription.
	 * @param     int             $source The Source for historical or active.
	 *
	 * @return Activecampaign_For_Woocommerce_Subscription_Model
	 */
	private function setup_cofe_subscription_from_table( WC_Subscription $wc_subscription, $source = 0 ) {
		if ( ! self::validate_object( $wc_subscription, 'get_data' ) || ! self::validate_object( $wc_subscription, 'get_order_number' ) ) {
			return null;
		}
		$this->init();
		try {
			// setup the ecom order

			$logger               = new Logger();
			$wc_subscription_data = $wc_subscription->get_data();
			$ecom_subscription    = new Cofe_Ecom_Order_Subscription();
			$ecom_subscription->set_properties_from_subscription_data( $wc_subscription->get_data() );

			// Data not passed from the order data array must be set another way.
			$ecom_subscription->set_legacy_connectionid( $this->connection_id );
			$ecom_subscription->set_source( $source );
			$ecom_subscription->set_subscription_id( $wc_subscription->get_id() );

			$trial_passed = wcs_trial_has_passed( $wc_subscription );
			if (
				isset( $trial_passed ) &&
				is_bool( $trial_passed ) &&
				false === $trial_passed &&
				null !== $wc_subscription_data['schedule_trial_end']
				) {
				$ecom_subscription->set_is_trial( true );
			}

			$ecom_customer = new Ecom_Subscription_Customer();

			$ecom_customer->set_first_name( $wc_subscription->get_billing_first_name() );
			$ecom_customer->set_last_name( $wc_subscription->get_billing_last_name() );
			$ecom_customer->set_accepts_marketing( $wc_subscription->get_meta( ACTIVECAMPAIGN_FOR_WOOCOMMERCE_ACCEPTS_MARKETING_NAME ) );

			// Sets the customer data object into the subscription
			$serialized_customer = $ecom_customer->serialize_to_array();
			if ( isset( $serialized_customer ) && ! empty( $serialized_customer ) ) {
				$ecom_subscription->set_customer_data( $serialized_customer );
			} else {
				$ecom_subscription->set_customer_data( null );
			}

			if ( in_array( $ecom_subscription->get_wc_customer_id(), [ 0, '0', null ], true ) ) {
				$customer_id = $this->get_customer_id_from_subscription( $wc_subscription );
				$ecom_subscription->set_wc_customer_id( $customer_id );
			}

			// add the data to the order factory
			if ( $wc_subscription->get_user_id() ) {
				// Set if the AC id is set
				$ecom_subscription->set_id( User_Meta_Service::get_current_cart_ac_id( $wc_subscription->get_user_id() ) );
			} elseif ( $wc_subscription->get_customer_id() ) {
				$ecom_subscription->set_id( User_Meta_Service::get_current_cart_ac_id( $wc_subscription->get_customer_id() ) );
			}

			$data            = $wc_subscription->get_data();
			$cofe_line_items = [
				'categories'        => array(),
				'names'             => array(),
				'skus'              => array(),
				'brands'            => array(),
				'tags'              => array(),
				'store_primary_ids' => array(),
			];

			foreach ( $data['line_items'] as $item_id => $item ) {
				$cofe_line_items = $this->populate_line_item_data( $cofe_line_items, $item_id, $item );
			}

			$ecom_subscription->set_line_item_categories( $cofe_line_items['categories'] );
			$ecom_subscription->set_line_item_names( $cofe_line_items['names'] );
			$ecom_subscription->set_line_item_skus( $cofe_line_items['skus'] );
			$ecom_subscription->set_line_item_tags( $cofe_line_items['tags'] );
			$ecom_subscription->set_line_item_store_primary_ids( $cofe_line_items['store_primary_ids'] );

			return $ecom_subscription;
		} catch ( Throwable $t ) {
			$logger = new Logger();
			$logger->error(
				'There was an exception creating the subscription object for AC.',
				[
					'wc_order'         => $wc_subscription->get_id(),
					'message'          => $t->getMessage(),
					'suggested_action' => 'Please check the message for explanation or error and if the issue continues contact ActiveCampaign support.',
					'ac_code'          => 'SG_154',
					'trace'            => $logger->clean_trace( $t->getTrace() ),
				]
			);

			return null;
		}
	}

	/**
	 * Builds the subscription line item groups.
	 *
	 * @param array      $cofe_line_items The line items array.
	 * @param int|string $id The line item ID.
	 * @param object     $item The line item.
	 *
	 * @return array
	 */
	public function populate_line_item_data( $cofe_line_items, $id, $item ) {
		if ( ! self::validate_object( $item, 'get_data' ) ) {
			return null;
		}
		$logger = new Logger();

		try {
			$item_data  = $item->get_data();
			$wc_product = wc_get_product( $item_data['product_id'] );

			if ( isset( $wc_product ) && self::validate_object( $wc_product, 'get_name' ) ) {

				$sku = $wc_product->get_sku();
				if ( isset( $sku ) && ! empty( $sku ) ) {
					array_push( $cofe_line_items['skus'], $sku );
				}

				$name = $wc_product->get_name();
				if ( isset( $name ) && ! empty( $name ) ) {
					array_push( $cofe_line_items['names'], $name );
				}

				$tags = $this->get_wc_tag_names( $item_data['product_id'] );
				if ( isset( $tags ) && ! empty( $tags ) ) {
					foreach ( $tags as $tag ) {
						array_push( $cofe_line_items['tags'], $tag );
					}
				}

				$categories = $this->get_product_all_categories( $wc_product );
				if ( isset( $categories ) && ! empty( $categories ) ) {
					array_push( $cofe_line_items['categories'], $categories );
				}
			} else {
				$logger->warning(
					'Could not populate line item data for this subscription',
					[
						'id'      => $id,
						'item'    => $item,
						'ac_code' => 'SBG_185',
					]
				);
			}
		} catch ( Throwable $t ) {
			$logger->error(
				'Could no populate line item data for subscription',
				[
					'message' => $t->getMessage(),
					'trace'   => $t->getTrace(),
					'ac_code' => 'SBG_200',
				]
			);
		}

		return $cofe_line_items;
	}

	/**
	 * Returns a customer ID if we can find one.
	 *
	 * @param     WC_Subscription|null $subscription The order object.
	 *
	 * @return bool|string
	 */
	private function get_customer_id_from_subscription( $subscription = null ) {
		$logger = new Logger();
		try {
			if ( ! is_null( $subscription ) && self::validate_object( $subscription, 'get_customer_id' ) && ! empty( $subscription->get_customer_id() ) ) {

				return $subscription->get_customer_id();
			}

			if ( ! is_null( $subscription ) && self::validate_object( $subscription, 'get_user_id' ) && ! empty( $subscription->get_user_id() ) ) {

				return $subscription->get_user_id();
			}
		} catch ( Throwable $t ) {
			$logger->debug(
				'Customer ID fetch threw an error on the subscription object.',
				[
					$subscription,
					$t->getMessage(),
					'ac_code' => 'SG_312',
				]
			);
		}

		try {
			global $wpdb;
			$customer_row = $wpdb->get_row(
				$wpdb->prepare(
					'SELECT order_id, customer_id FROM ' . $wpdb->prefix . 'wc_order_stats where order_id = %s LIMIT 1;',
					[ $subscription->get_id() ]
				)
			);

			if ( isset( $customer_row ) && ! empty( $customer_row->customer_id ) ) {
				return $customer_row->customer_id;
			}
		} catch ( Throwable $t ) {
			$logger->warning(
				'Customer fetch threw an error for subscription from the table.',
				[
					'message' => $t->getMessage(),
					'order'   => $subscription,
					'ac_code' => 'SG_335',
				]
			);
		}

		$logger->warning(
			'Could not find a customer ID matching the subscription for COFE builder.',
			[
				'order'         => $subscription,
				'id'            => self::validate_object( $subscription, 'get_id' ) ? $subscription->get_id() : null,
				'order_number'  => self::validate_object( $subscription, 'get_order_number' ) ? $subscription->get_order_number() : null,
				'billing_email' => self::validate_object( $subscription, 'get_billing_email' ) ? $subscription->get_billing_email() : null,
				'ac_code'       => 'SG_346',
			]
		);

		return null;
	}
}
