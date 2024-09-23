<?php

use Activecampaign_For_Woocommerce_Ecom_Enum_Type as Enumish;
use Activecampaign_For_Woocommerce_Has_Id as Has_Id;
use AcVendor\Brick\Math\BigDecimal;
use Activecampaign_For_Woocommerce_Logger as Logger;

/**
 * The file for the EcomProduct Cofe Model
 *
 * @link       https://www.activecampaign.com/
 * @since      1.0.0
 *
 * @package    Activecampaign_For_Woocommerce
 * @subpackage Activecampaign_For_Woocommerce/includes/models
 */

/**
 * The model class for the EcomProduct
 *
 * @since      1.0.0
 * @package    Activecampaign_For_Woocommerce
 * @subpackage Activecampaign_For_Woocommerce/includes/models
 * @author     acteamintegrations <team-integrations@activecampaign.com>
 */
class Activecampaign_For_Woocommerce_Ecom_Cofe_Product_Serializer {
	use Activecampaign_For_Woocommerce_Data_Validation;

	/**
	 * @param WC_Product | WC_Product_Variation $product
	 * @param ?int                              $connection_id
	 * @param WC_Product?                       $parent
	 * @return array
	 */
	public static function product_array_for_cofe( $product, ?int $connection_id, $parent = null ): ?array {
		$logger       = new Logger();
		$cofe_product = self::base_product_fields( ! is_null( $parent ) ? $parent : $product );

		if ( $cofe_product && self::validate_object( $product, 'get_data' ) ) {
			$cofe_product['legacyConnectionId'] = $connection_id;
			try {
				$tags = get_the_terms( $product->get_id(), 'product_tag' );

				if ( $tags ) {
					$tags = array_map(
						function ( $it ) {
							if ( $it->slug ) {
								return $it->slug;
							} elseif ( $it->name ) {
								return $it->name;
							} else {
								return null;
							}
						},
						$tags
					);
				}

				$cofe_product['tags'] = $tags ? $tags : null;

				$categories = get_the_terms( $product->get_id(), 'product_cat' );

				if ( $categories ) {
					$categories = array_map(
						function ( $it ) {
							if ( $it->term_id ) {
								return (string) $it->term_id;
							} else {
								return null;
							}
						},
						$categories
					);
				}

				if ( false === $categories ) {
					$categories = null;
				}

				$cofe_product['categories']     = $categories;
				$cofe_product['dimensionsUnit'] = get_option( 'woocommerce_dimension_unit' ) === 'cm' ? new Enumish( 'METRIC' ) : new Enumish( 'IMPERIAL' );

				$cofe_product['storePrimaryId']           = self::int_as_string( $product->get_id() );
				$cofe_product['variantSku']               = $product->get_sku();
				$cofe_product['variantName']              = $product->get_name();
				$cofe_product['variantDescription']       = self::get_product_description( $product );
				$cofe_product['variantPriceCurrency']     = get_woocommerce_currency();
				$cofe_product['variantPriceAmount']       = self::big_decimal_price( $product->get_price() );
				$cofe_product['variantStoreCreatedDate']  = self::date_format( $product->get_date_created() );
				$cofe_product['variantStoreModifiedDate'] = self::date_format( $product->get_date_modified() );
				$cofe_product['variantImages']            = self::images( $product );
				$cofe_product['variantUrl']               = $product->get_permalink();
				$cofe_product['variantUrlSlug']           = $product->get_slug();
				$cofe_product['variantWeight']            = self::big_decimal_weight( $product->get_weight() );
				$dimensions                               = self::dimensions( $product );
				if ( ! empty( $dimensions ) ) {
					$cofe_product['variantDimensions'] = $dimensions;
				}

				$cofe_product['type']                = $product->get_type();
				$cofe_product['status']              = $product->get_status();
				$cofe_product['numberOfSales']       = self::int_field( $product->get_total_sales() );
				$cofe_product['isVirtual']           = $product->get_virtual();
				$cofe_product['isDownloadable']      = $product->get_downloadable();
				$cofe_product['isVisible']           = self::convert_to_bool( $product->is_visible() );
				$cofe_product['isOnSale']            = $product->is_on_sale();
				$cofe_product['isBackordersAllowed'] = $product->backorders_allowed();
				if ( $product->is_in_stock() !== null ) {
					if ( $product->is_in_stock() ) {
						$cofe_product['stockStatus'] = new Enumish( 'IN_STOCK' );
					} else {
						$cofe_product['stockStatus'] = new Enumish( 'OUT_OF_STOCK' );
					}

					if ( $product->is_on_backorder() ) {
						$cofe_product['stockStatus'] = new Enumish( 'BACKORDER' );
					}
				}
				$cofe_product['averageRatings'] = self::int_as_string( $product->get_average_rating() );
				$cofe_product['ratingCount']    = self::int_field( $product->get_rating_count() );
				$cofe_product['attributes']     = self::map_field( $product->get_attributes() );

				return $cofe_product;
			} catch ( Throwable $t ) {
				$logger->error(
					'Failed to build the COFE product array for the product sync.',
					[
						'message'          => $t->getMessage(),
						'suggested_action' => 'Check the message for the error and verify the order data. If the issue persists please contact ActiveCampaign.',
						'ac_code'          => 'ECPS_127',
						'cofe_product'     => $cofe_product,
						'trace'            => $t->getTrace(),
					]
				);
			}
		}

		return null;
	}

	/**
	 * @param ?WC_DateTime $wc_date_field
	 * @return string|null
	 */
	private static function date_format( $wc_date_field ): ?string {
		return null !== $wc_date_field ? $wc_date_field->__toString() : null;
	}

	/**
	 * @param ?int $int_field
	 * @return string
	 */
	private static function int_as_string( ?int $int_field ): ?string {
		if ( null === $int_field ) {
			return null;
		} else {
			return strval( $int_field );
		}
	}

	/**
	 * Converts various conditions to a boolean.
	 *
	 * @param srting $field The word.
	 *
	 * @return bool
	 */
	private static function convert_to_bool( $field ) {
		if ( in_array( $field, [ 'yes', 'YES', 'visible', 'search', 'catalog', true, 1, '1' ] ) ) {
			return true;
		}

		return false;
	}

	/**
	 * @param WC_Product $product
	 * @return array
	 */
	public static function dimensions( $product ): ?array {
		if ( ! $product instanceof WC_Product ) {
			return null;
		}
		$dimensions = array();
		if ( is_numeric( $product->get_length() ) ) {
			$dimensions['length'] = BigDecimal::of( $product->get_length() );
		}
		if ( is_numeric( $product->get_width() ) ) {
			$dimensions['width'] = BigDecimal::of( $product->get_width() );
		}
		if ( is_numeric( $product->get_height() ) ) {
			$dimensions['height'] = BigDecimal::of( $product->get_height() );
		}
		return $dimensions;
	}

	/**
	 * @param string $weight
	 * @return BigDecimal|null
	 */
	public static function big_decimal_weight( string $weight ): ?BigDecimal {
		if ( is_numeric( $weight ) ) {
			return BigDecimal::of( $weight );
		} else {
			return null;
		}
	}

	/**
	 * @param string|int $field
	 * @return BigDecimal|null
	 */
	public static function big_decimal_price( $field ): ?BigDecimal {
		if ( is_numeric( $field ) ) {
			return BigDecimal::of( $field );
		} else {
			return BigDecimal::of( 0 );
		}
	}

	/**
	 * @param WC_Product $product
	 * @return array
	 */
	public static function images( $product ): array {
		$result = array();
		$images = array();
		$logger = new Logger();

		// Get the thumbnail and main images
		try {
			if ( self::validate_object( $product, 'get_data' ) ) {
				$product_data = $product->get_data();

				if ( isset( $product_data['image_id'] ) ) {
					$images[] = $product_data['image_id'];
				}

				if ( isset( $product_data['gallery_image_ids'] ) ) {
					foreach ( $product_data['gallery_image_ids'] as $gallery_id ) {
						if ( isset( $gallery_id ) ) {
							$images[] = $gallery_id;
						}
					}
				}
			}

			if ( count( $images ) > 0 ) {
				foreach ( $images as $image_id ) {
					$image_data = wp_get_attachment_image_src( $image_id, 'full' );
					if ( $image_data ) {
						$result[] = array(
							'url'    => $image_data[0],
							'width'  => $image_data[1] ? $image_data[1] : null,
							'height' => $image_data[2] ? $image_data[2] : null,
						);
					}
				}
			}
		} catch ( Throwable $t ) {
			$logger->warning(
				'There was an issue gathering product images for sync.',
				[
					'message' => $t->getMessage(),
					'product' => self::validate_object( $product, 'get_data' ) ? $product->get_data() : null,
				]
			);
		}

		return $result;
	}

	/**
	 * @param ?WC_Product $base_product
	 * @return array
	 */
	public static function base_product_fields( $base_product ): array {
		$logger = new Logger();
		if ( ! $base_product ) {
			$logger->debug( 'No base product provided.' );
			return array();
		}
		try {
			$cofe_product                                 = array();
			$cofe_product['storeBaseProductId']           = self::int_as_string( $base_product->get_parent_id() ? $base_product->get_parent_id() : $base_product->get_id() );
			$cofe_product['baseProductName']              = $base_product->get_name();
			$cofe_product['baseProductDescription']       = self::get_product_description( $base_product );
			$cofe_product['baseProductStoreCreatedDate']  = self::date_format( $base_product->get_date_created() );
			$cofe_product['baseProductStoreModifiedDate'] = self::date_format( $base_product->get_date_modified() );

			$cofe_product['baseProductImages']  = self::images( $base_product );
			$cofe_product['baseProductUrl']     = $base_product->get_permalink();
			$cofe_product['baseProductUrlSlug'] = $base_product->get_slug();

			$cofe_product['baseProductWeight'] = self::big_decimal_weight( $base_product->get_weight() );
			$dimensions                        = self::dimensions( $base_product );
			if ( ! empty( $dimensions ) ) {
				$cofe_product['baseProductDimensions'] = $dimensions;
			}

			return $cofe_product;
		} catch ( Throwable $t ) {
			$logger->error(
				'Failed to build the base COFE product fields for the product sync.',
				[
					'message'          => $t->getMessage(),
					'suggested_action' => 'Check the message for the error and verify the order data. If the issue persists please contact ActiveCampaign.',
					'ac_code'          => 'ECPS_290',
					'cofe_product'     => $cofe_product,
					'found_in'         => 'base_product_fields',
				]
			);

			return array();
		}
	}

	private static function int_field( $stringy ) {
		if ( isset( $stringy ) && $stringy >= 2147483647 ) {
			// Int max cap
			$int_val = 2147483647;
			$logger  = new Logger();
			$logger->debug_excess(
				'Int was too big for cofe product serializer and must be capped',
				[
					'num_passed' => $stringy,
				]
			);
		} else {
			$int_val = intval( $stringy );
		}

		if ( 0 !== $int_val ) {
			return $int_val;
		} elseif ( '0' === $stringy || 0.0 === $stringy ) {
			return 0;
		} else {
			return null;
		}
	}

	/**
	 * Needed to normalize arrays so that they are not nested.
	 *
	 * @param ?array $field
	 * @return array
	 */
	private static function map_field( ?array $field ) : ?array {
		$result = array();
		foreach ( $field as $k => $v ) {
			// GraphQL cannot process any other characters as keys, so replace them
			$k = preg_replace( '/[^A-Za-z0-9_]+/', '__', $k );

			if ( is_array( $v ) ) {
				if ( ! empty( $v ) ) {
					$result[ $k ] = wp_json_encode( $v );
				}
			} elseif ( $v instanceof WC_Product_Attribute ) {
				// For some reason, $v was WC_Product_Attribute, it rendered in a weird way. Not setting anything when $v is WC_Product_Attribute
				$result[ $k ] = null;
			} else {
				$result[ $k ] = $v;
			}
		}
		if ( empty( $result ) ) {
			return null;
		} else {
			return $result;
		}
	}

	/**
	 * Gets the correct product description based on settings.
	 *
	 * @param WC_Product $product The product object.
	 *
	 * @return string
	 */
	private static function get_product_description( $product ) {
		// default options
		$use_short_description   = false;
		$fallback_to_description = true;

		$current_settings = get_option( ACTIVECAMPAIGN_FOR_WOOCOMMERCE_DB_SETTINGS_NAME );
		if ( isset( $current_settings['ac_desc_select'] ) ) {
			// Use full description only
			if ( 0 === $current_settings['ac_desc_select'] || '0' === $current_settings['ac_desc_select'] ) {
				$use_short_description   = false;
				$fallback_to_description = true;
			}
			// Use short description only
			if ( 1 === $current_settings['ac_desc_select'] || '1' === $current_settings['ac_desc_select'] ) {
				$use_short_description   = true;
				$fallback_to_description = false;
			}
			// Use short description, fall back to full description
			if ( 2 === $current_settings['ac_desc_select'] || '2' === $current_settings['ac_desc_select'] ) {
				$use_short_description   = true;
				$fallback_to_description = true;
			}
		}

		if ( $use_short_description ) {
			$description = $product->get_short_description();
		} else {
			$description = $product->get_description();
		}

		if ( empty( $description ) && $fallback_to_description ) {
			$description = $product->get_description();
		}

		if ( ! empty( $description ) ) {
			return ( new Activecampaign_For_Woocommerce_Ecom_Cofe_Product_Serializer() )->clean_description( $description, 0 );
		} else {
			return '';
		}
	}
}
