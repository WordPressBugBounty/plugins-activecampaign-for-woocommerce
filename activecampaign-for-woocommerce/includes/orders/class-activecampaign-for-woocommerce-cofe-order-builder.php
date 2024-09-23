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

use Activecampaign_For_Woocommerce_Ecom_Product_Factory as Ecom_Product_Factory;
use Activecampaign_For_Woocommerce_User_Meta_Service as User_Meta_Service;
use Activecampaign_For_Woocommerce_Logger as Logger;
use Activecampaign_For_Woocommerce_Ecom_Customer_Repository as Customer_Repository;
use Activecampaign_For_Woocommerce_AC_Contact_Repository as Contact_Repository;
use Activecampaign_For_Woocommerce_Ecom_Order_Repository as Order_Repository;
use Activecampaign_For_Woocommerce_Cofe_Ecom_Order as Cofe_Ecom_Order;
use Activecampaign_For_Woocommerce_Cofe_Ecom_Order_Line_Item as Line_Item;
use AcVendor\Brick\Math\BigDecimal;
use AcVendor\Brick\Math\RoundingMode;

/**
 * The Order Utilities Class.
 *
 * @since      1.5.0
 * @package    Activecampaign_For_Woocommerce
 * @subpackage Activecampaign_For_Woocommerce/includes/orders
 * @author     acteamintegrations <team-integrations@activecampaign.com>
 */
class Activecampaign_For_Woocommerce_Cofe_Order_Builder {
	use Activecampaign_For_Woocommerce_Order_Data_Gathering,
		Activecampaign_For_Woocommerce_Order_Line_Item_Gathering;

	/**
	 * The custom ActiveCampaign logger
	 *
	 * @var Activecampaign_For_Woocommerce_Logger
	 */
	private $logger;

	/**
	 * The custom ActiveCampaign product factory.
	 *
	 * @var Activecampaign_For_Woocommerce_Ecom_Product_Factory
	 */
	private $product_factory;

	/**
	 * The ActiveCampaign connection ID.
	 *
	 * @var connection_id
	 */
	private $connection_id;

	/**
	 * The customer repository.
	 *
	 * @var customer_repository
	 */
	private $customer_repository;

	/**
	 * The contact repository.
	 *
	 * @var contact_repository
	 */
	private $contact_repository;

	/**
	 * The order repository.
	 *
	 * @var order_repository
	 */
	private $order_repository;

	/**
	 * Initialize function.
	 */
	private function init() {
		$admin_storage = get_option( ACTIVECAMPAIGN_FOR_WOOCOMMERCE_DB_CONNECTION_STORAGE_NAME );
		if ( ! empty( $admin_storage ) && isset( $admin_storage['connection_id'] ) ) {
			$this->connection_id = $admin_storage['connection_id'];
		}

		if ( ! $this->product_factory ) {
			$this->product_factory = new Ecom_Product_Factory();
		}
	}

	/**
	 * Sets up the COFE order from the table info.
	 *
	 * @param WC_Order|string|int $wc_order The WC order or ID.
	 * @param     int                 $source The source.
	 *
	 * @return Activecampaign_For_Woocommerce_Cofe_Ecom_Order|null
	 */
	public function setup_cofe_order_from_table( $wc_order, $source = 0 ) {
		if ( ! self::validate_object( $wc_order, 'get_data' ) || ! self::validate_object( $wc_order, 'get_order_number' ) ) {
			return null;
		}

		// Setup the woocommerce cart
		$this->init();

		try {
			// setup the ecom order

			$logger = new Logger();

			$wc_order_data = $wc_order->get_data();

			if (
				function_exists( 'wcs_is_subscription' ) && wcs_is_subscription( $wc_order->get_id() )
			) {
				$logger->warning(
					'This record was improperly triggered by WooCommerce as an order but is a subscription. It will be processed as a subscription instead.',
					[
						'order_id' => $wc_order->get_id(),
					]
				);

				$wc_subscription = wcs_get_subscription( $wc_order->get_id() );

				if ( ! empty( $wc_subscription->get_id() ) ) {
					$subscription_id = $wc_subscription->get_id();
					do_action( 'activecampaign_for_woocommerce_miscat_order_to_subscription', [ $subscription_id ] );

					return null;
				}
			}

			// Normal order, do the standard method
			$ecom_order = new Cofe_Ecom_Order();
			$ecom_order->set_properties_from_order_data( $wc_order_data );
		} catch ( Throwable $t ) {
			$logger = new Logger();
			$logger->debug(
				'Cofe order builder: There was an error setting the order properties, the order data may not be viable to sync to AC:',
				[
					'wc_order id' => self::validate_object( $wc_order, 'get_id' ) ? $wc_order->get_id() : null,
					'message'     => $t->getMessage(),
					'trace'       => $logger->clean_trace( $t->getTrace() ),
				]
			);

			return null;
		}

		try {
			if ( ! isset( $ecom_order ) || ! self::validate_object( $ecom_order, 'get_email' ) || empty( $ecom_order->get_email() ) ) {
				// If we don't have an email, no matter what this cannot sync
				return null;
			}

			// Data not passed from the order data array must be set another way.
			$ecom_order->set_legacy_connectionid( $this->connection_id );
			$ecom_order->set_source( $source );
			$ecom_order->set_order_url( $wc_order->get_edit_order_url() );
			$ecom_order->set_shipping_method( $wc_order->get_shipping_method() );
			$ecom_order->set_order_discounts( $this->get_coupons_from_order( $wc_order ) );

			if ( in_array( $ecom_order->get_wc_customer_id(), [ 0, '0', null ], true ) ) {
				$customer_id = $this->get_customer_id_from_order( $wc_order );
				$ecom_order->set_wc_customer_id( $customer_id );
			}

			// add the data to the order factory
			if ( $wc_order->get_user_id() ) {
				// Set if the AC id is set
				$ecom_order->set_id( User_Meta_Service::get_current_cart_ac_id( $wc_order->get_user_id() ) );
			} elseif ( $wc_order->get_customer_id() ) {
				$ecom_order->set_id( User_Meta_Service::get_current_cart_ac_id( $wc_order->get_customer_id() ) );
			}

			// Get partial refunds
			$order_refunds = $wc_order->get_refunds();

			$refund_total        = 0;
			$partial_refund_list = [];
			foreach ( $order_refunds as $refund ) {
				$refund_total += $refund->get_total();

				// Loop through the order refund line items
				foreach ( $refund->get_items() as $item_id => $item ) {
					$refund_line_item_id                         = $item->get_meta( '_refunded_item_id' );
					$partial_refund_list[ $refund_line_item_id ] = $item->get_data();
					$partial_refund_list[ $refund_line_item_id ]['refund_line_item_id'] = $refund_line_item_id;
				}
			}

			$total_price = BigDecimal::of( $ecom_order->get_total_price() )->plus( $refund_total );
			$ecom_order->set_total_price( $total_price );

			$line_items = $wc_order_data['line_items'];

			foreach ( $line_items as $item_id => $item ) {
				$refund        = $partial_refund_list[ $item_id ] ?? null;
				$build_product = $this->build_line_item( $item_id, $item, $refund );

				if ( isset( $build_product ) ) {
					$ecom_order->push_order_product( $build_product );
				}
			}

			if ( ! $ecom_order->validate_model() ) {
				$logger->debug( 'This model is invalid.' );
				return null;
			}

			return $ecom_order;
		} catch ( Throwable $t ) {
			$logger = new Logger();
			$logger->error(
				'There was an exception creating the order object for AC.',
				[
					'wc_order'         => $wc_order->get_id(),
					'message'          => $t->getMessage(),
					'suggested_action' => 'Please check the message for explanation or error and if the issue continues contact ActiveCampaign support.',
					'ac_code'          => 'COB_219',
					'trace'            => $logger->clean_trace( $t->getTrace() ),
				]
			);

			return null;
		}
	}

	/**
	 * Builds a line item.
	 *
	 * @param int|string $id The line item ID.
	 * @param WC_Product $item The line item.
	 *
	 * @return Activecampaign_For_Woocommerce_Cofe_Ecom_Order_Line_Item
	 */
	public function build_line_item( $id, $item, $refund = null ) {
		if ( ! self::validate_object( $item, 'get_data' ) ) {
			return null;
		}

		$item_data      = $item->get_data();
		$wc_product     = wc_get_product( $item_data['product_id'] );
		$cofe_line_item = new Line_Item();
		$logger         = new Logger();

		if (
			isset( $item_data['variation_id'] ) &&
			! empty( $item_data['variation_id'] )
		) {
			$child_product = wc_get_product( $item_data['variation_id'] );
			if ( self::validate_object( $child_product, 'get_data' ) ) {
				$child_data = $child_product->get_data();

				// For variations, these fields are in the item_data not the child product, so add them in
				$child_data['variation_id'] = $item_data['variation_id'];
				$child_data['quantity']     = $item_data['quantity'];

				if ( ! isset( $child_data['total'] ) && isset( $item_data['subtotal'] ) ) {
					$child_data['subtotal'] = $item_data['subtotal'];
				}

				if ( ! isset( $child_data['total'] ) && isset( $item_data['total'] ) ) {
					$child_data['total'] = $item_data['total'];
				}

				if ( ! isset( $child_data['average_rating'] ) && isset( $item_data['average_rating'] ) ) {
					$child_data['average_rating'] = $item_data['average_rating'];
				}

				// Sets all the properties from the data array
				$cofe_line_item->set_properties_from_product_data( $child_data );

				// Add fields that don't get set
				$cofe_line_item->set_category( $this->get_product_all_categories( $child_product ) );
				$cofe_line_item->set_image_url( $this->get_product_image_url_from_wc( $child_product ) );
				$cofe_line_item->set_product_url( $this->get_product_url_from_wc( $child_product ) );
				$cofe_line_item->set_sku( $this->get_product_sku_from_wc( $child_product ) );
				$cofe_line_item->set_tags( $this->get_wc_tag_names( $child_data['variation_id'] ) );
			}
		} else {
			$cofe_line_item->set_properties_from_product_data( $item_data );
		}

		if (
			empty( $cofe_line_item->get_category() ) ||
			'Unknown' === $cofe_line_item->get_category() ||
			'Uncategorized' === $cofe_line_item->get_category()
		) {
			$cofe_line_item->set_category( $this->get_product_all_categories( $wc_product ) );
		}

		if ( empty( $cofe_line_item->get_image_url() ) ) {
			$cofe_line_item->set_image_url( $this->get_product_image_url_from_wc( $wc_product ) );
		}

		if ( empty( $cofe_line_item->get_product_url() ) ) {
			$cofe_line_item->set_product_url( $this->get_product_url_from_wc( $wc_product ) );
		}

		if ( empty( $cofe_line_item->get_sku() ) ) {
			$cofe_line_item->set_sku( $this->get_product_sku_from_wc( $wc_product ) );
		}

		if ( empty( $cofe_line_item->get_tags() ) ) {
			$cofe_line_item->set_tags( $this->get_wc_tag_names( $item_data['product_id'] ) );
		}

		if ( self::validate_object( $wc_product, 'get_average_rating' ) && empty( $cofe_line_item->get_average_rating() ) ) {
			$cofe_line_item->set_average_rating( $wc_product->get_average_rating() );
		}

		if ( self::validate_object( $wc_product, 'is_on_sale' ) && empty( $cofe_line_item->get_is_on_sale() ) ) {
			$cofe_line_item->set_is_on_sale( $wc_product->is_on_sale() );
		}

		if ( isset( $refund['subtotal'] ) && $refund['refund_line_item_id'] == $id ) {
			// Refunds are negative quantities so add the totals
			$cofe_line_item->set_price( (int) $cofe_line_item->get_price() + (int) $refund['subtotal'] );
		} elseif ( isset( $refund['total'] ) && $refund['refund_line_item_id'] == $id ) {
			// Refunds are negative quantities so add the totals
			$cofe_line_item->set_price( (int) $cofe_line_item->get_price() + (int) $refund['total'] );
		}

		if ( isset( $refund['quantity'] ) && $refund['refund_line_item_id'] == $id ) {
			// Refunds are negative quantities so add the totals
			$cofe_line_item->set_quantity( (int) $cofe_line_item->get_quantity() + (int) $refund['quantity'] );
		}

		return $cofe_line_item;
	}

	/**
	 * Returns a customer ID if we can find one.
	 *
	 * @param     WC_Order|null $order The order object.
	 *
	 * @return bool|string
	 */
	public function get_customer_id_from_order( $order = null ) {
		$logger = new Logger();
		try {
			if ( ! is_null( $order ) && self::validate_object( $order, 'get_customer_id' ) && ! empty( $order->get_customer_id() ) ) {

				return $order->get_customer_id();
			}

			if ( ! is_null( $order ) && self::validate_object( $order, 'get_user_id' ) && ! empty( $order->get_user_id() ) ) {

				return $order->get_user_id();
			}
		} catch ( Throwable $t ) {
			$logger->debug(
				'Customer ID fetch threw an error on the order object.',
				[
					$order,
					$t->getMessage(),
					'ac_code' => 'COB_312',
				]
			);
		}

		try {
			global $wpdb;
			$customer_row = $wpdb->get_row(
				$wpdb->prepare(
					'SELECT order_id, customer_id FROM ' . $wpdb->prefix . 'wc_order_stats where order_id = %s LIMIT 1;',
					[ $order->get_id() ]
				)
			);

			if ( isset( $customer_row ) && ! empty( $customer_row->customer_id ) ) {
				return $customer_row->customer_id;
			}
		} catch ( Throwable $t ) {
			$logger->warning(
				'Customer fetch threw an error for order from the table.',
				[
					'message' => $t->getMessage(),
					'order'   => $order,
					'ac_code' => 'COB_335',
				]
			);
		}

		$logger->warning(
			'Could not find a customer ID matching the order for COFE builder.',
			[
				'order'         => $order,
				'id'            => self::validate_object( $order, 'get_id' ) ? $order->get_id() : null,
				'order_number'  => self::validate_object( $order, 'get_order_number' ) ? $order->get_order_number() : null,
				'billing_email' => self::validate_object( $order, 'get_billing_email' ) ? $order->get_billing_email() : null,
				'ac_code'       => 'COB_346',
			]
		);

		return null;
	}
}
