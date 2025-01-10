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
trait Activecampaign_For_Woocommerce_Arg_Data_Gathering {

	/**
	 * Checks both post and get for values. WC seems to pass nonce as GET but fields pass as POST.
	 *
	 * @param     string $field     The field name.
	 *
	 * @return mixed|null Returns field data.
	 */
	public static function get_request_data( $field ) {
		$get_input     = null;
		$post_input    = null;
		$request_input = null;

		try {
			$post_input = filter_input( INPUT_POST, $field, FILTER_UNSAFE_RAW );
			$get_input  = filter_input( INPUT_GET, $field, FILTER_UNSAFE_RAW );

			if ( ! empty( $post_input ) ) {
				return $post_input;
			}

			if ( ! empty( $get_input ) ) {
				return $get_input;
			}
		} catch ( Throwable $t ) {
			$logger = new Activecampaign_For_Woocommerce_Logger();
			$logger->warning(
				'There was an issue getting get or post data for a field',
				array(
					'field_name' => $field,
					'get_input'  => $get_input,
					'post_input' => $post_input,
					'message'    => $t->getMessage(),
					'ac_code'    => 'ADG_48',
				)
			);
		}

		try {
			$request = wp_unslash( $_REQUEST );
			if ( isset( $request[ $field ] ) ) {
				$request_input = $request[ $field ];

				if ( ! empty( $request_input ) ) {
					return $request_input;
				}
			}
		} catch ( Throwable $t ) {
			$logger = new Activecampaign_For_Woocommerce_Logger();
			$logger->warning(
				'There was an issue getting request data for a field',
				array(
					'field_name'    => $field,
					'request_input' => $request_input,
					'message'       => $t->getMessage(),
					'ac_code'       => 'ADG_70',
				)
			);
		}

		try {
			// phpcs:disable
			$request = wp_unslash( $_POST );
			// phpcs:enable

			if ( isset( $request[ $field ] ) ) {
				$request_input = $request[ $field ];

				if ( ! empty( $request_input ) ) {
					return $request_input;
				}
			}
		} catch ( Throwable $t ) {
			$logger = new Activecampaign_For_Woocommerce_Logger();
			$logger->warning(
				'There was an issue getting direct post data for a field',
				array(
					'field_name'    => $field,
					'request_input' => $request_input,
					'message'       => $t->getMessage(),
					'ac_code'       => 'ADG_90',
				)
			);
		}

		return null;
	}

	/**
	 * Gets the product IDs in the format we need.
	 *
	 * @param int  $limit The limit.
	 * @param int  $offset The offset.
	 * @param bool $return_id_only Marker for return IDs only.
	 *
	 * @return array|stdClass
	 */
	public static function get_products_by_offset( $limit, $offset, $return_id_only ) {
		// types standard available 'external', 'grouped', 'simple', 'variable'
		// Do not include groups for now.
		$logger = new Logger();

		try {
			$safe_product_types = self::get_cofe_safe_product_types(); // This may be causing an issue with some 3rd party plugins due to custom product types.

			$data = array(
				'limit'   => (int) $limit,
				'offset'  => (int) $offset,
				'orderby' => 'ID',
				'status'  => 'publish',
				'order'   => 'ASC',
			);

			if ( isset( $safe_product_types ) && ! empty( $safe_product_types ) ) {
				$data['type'] = $safe_product_types;
			}

			if ( $return_id_only ) {
				$data['return'] = 'ids';
			}

			$logger->debug(
				'Getting products by offset',
				array(
					'producttypes'   => $safe_product_types,
					'data'           => $data,
					'return_id_only' => $return_id_only,
				)
			);

			$products = wc_get_products( $data );

			return $products;
		} catch ( Throwable $t ) {
			$logger = new Logger();
			$logger->warning(
				'There was an issue getting products for the product sync',
				array(
					'message'        => $t->getMessage(),
					'return_id_only' => $return_id_only,
				)
			);
		}

		return null;
	}

	/**
	 * Get product data directly from the database bypassing WooCommerce functions.
	 *
	 * @param int|string $limit The limit.
	 * @param int|string $offset The offset.
	 * @param bool       $return_id_only Return only IDs or not.
	 *
	 * @return array
	 */
	public function get_products_direct_by_offset( $limit, $offset, $return_id_only = true ) {
		global $wpdb;

		try {
			$product_ids = $wpdb->get_col(
				$wpdb->prepare(
					"SELECT ID FROM {$wpdb->posts} WHERE post_type = 'product' AND post_status = 'publish' LIMIT %d OFFSET %d",
					array( $limit, $offset )
				)
			);
		} catch ( Throwable $t ) {
			$logger = new Logger();
			$logger->warning(
				'There was an issue getting products for the product sync',
				array(
					'message'        => $t->getMessage(),
					'return_id_only' => $return_id_only,
				)
			);
		}
		try {
			if ($return_id_only ) {
				return $product_ids;
			} else {
				$products = array();

				foreach ($product_ids as $product_id ) {
								$products[] = wc_get_product( $product_id );
				}

				return $products;
			}
		} catch ( Throwable $t ) {
			$logger = new Logger();
			$logger->warning(
				'There was an issue getting products for the product sync',
				array(
					'message'        => $t->getMessage(),
					'return_id_only' => $return_id_only,
				)
			);
		}

		return null;
	}

	/**
	 * Gets all product data directly from the database.
	 *
	 * @return array WC_Products array
	 */
	public function get_all_products_direct() {
		global $wpdb;
		$product_ids = $wpdb->get_col( "SELECT ID FROM {$wpdb->posts} WHERE post_type = 'product' AND post_status = 'publish'" );

		$products = array();
		foreach ($product_ids as $product_id ) {
			$products[] = wc_get_product( $product_id );
		}

		return $products;
	}

	/**
	 * Gets all product IDs directly from the database.
	 *
	 * @return array
	 */
	public function get_all_product_ids_direct() {
		global $wpdb;
		$product_ids = $wpdb->get_col( "SELECT ID FROM {$wpdb->posts} WHERE post_type = 'product' AND post_status = 'publish'" );

		return $product_ids;
	}

	/**
	 * Fetch the safe product types. These are going to blacklist the below items.
	 *
	 * @return int[]|string[]
	 */
	public static function get_cofe_safe_product_types() {
		$product_types           = wc_get_product_types();
		$product_types_blacklist = array( 'grouped', 'draft' );

		// Blacklist certain types that cause conflicts & duplicates
		foreach ( $product_types_blacklist as $product_type ) {
			if ( isset( $product_types[ $product_type ] ) ) {
				unset( $product_types[ $product_type ] ); // Never sync this type
			}
		}

		// WC returns array as type_name: type readable so return only the keys
		return array_keys( $product_types );
	}
}
