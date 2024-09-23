<?php

/**
 * The Ecom Product Factory file.
 *
 * @link       https://www.activecampaign.com/
 * @since      1.0.0
 * @package    Activecampaign_For_Woocommerce
 * @subpackage Activecampaign_For_Woocommerce/
 */

use Activecampaign_For_Woocommerce_Logger as Logger;

/**
 * The Ecom Product Factory class.
 *
 * @since      1.0.0
 * @package    Activecampaign_For_Woocommerce
 * @subpackage Activecampaign_For_Woocommerce/
 * @author     acteamintegrations <team-integrations@activecampaign.com>
 */
class Activecampaign_For_Woocommerce_Ecom_Product_Factory {
	use Activecampaign_For_Woocommerce_Data_Validation;

	/**
	 * Given an array of cart contents, create an array of products
	 *
	 * @param array $cart_contents The cart contents.
	 *
	 * @return array
	 */
	public function create_products_from_cart_contents( $cart_contents ) {
		try {
			return array_map( [ $this, 'product_from_cart_content' ], $cart_contents );
		} catch ( Throwable $t ) {
			$logger = new Logger();
			$logger->warning(
				'Product factory could not create products from cart contents.',
				[
					'suggested_action' => 'Verify this order has products attached. If this order is not syncing and it does contain products please contact ActiveCampaign support.',
					'message'          => $t->getMessage(),
					'ac_code'          => 'EPF_168',
					'function'         => 'create_products_from_cart_contents',
					'trace'            => $logger->clean_trace( $t->getTrace() ),
				]
			);
		}
	}

	/**
	 * Given a cart content, create a product
	 *
	 * @param array $content The key-value array of a cart content.
	 *
	 * @return Activecampaign_For_Woocommerce_Ecom_Product
	 */
	public function product_from_cart_content( $content ) {
		try {
			if (
				isset( $content['data'] ) && (
				self::validate_object( $content['wc_product'], 'get_id' ) ||
				$content['wc_product'] instanceof WC_Product ||
				$content['wc_product'] instanceof WC_Product_Factory
				)
			) {
				$ecom_product = $this->convert_product_data_to_ecom_product( $content['wc_product'], $content['data'] );
				// $ecom_product = $this->convert_wc_product_to_ecom_product( $content['data'] );
			} else {
				$ecom_product = $this->convert_item_data_to_generic_product( $content );
			}

			if ( isset( $ecom_product ) ) {
				$ecom_product->set_quantity( $content['quantity'] );
				return $ecom_product;
			}
		} catch ( Throwable $t ) {
			$logger = new Logger();
			$logger->warning(
				'Product factory could not create products from cart contents.',
				[
					'suggested_action' => 'Verify this order has products attached. If this order is not syncing and it does contain products please contact ActiveCampaign support.',
					'message'          => $t->getMessage(),
					'ac_code'          => 'EPF_77',
					'function'         => 'product_from_cart_content',
					'trace'            => $logger->clean_trace( $t->getTrace() ),
				]
			);
		}

		return null;
	}

	public function convert_product_data_to_ecom_product( $wc_product, $data ) {
		try {
			if ( isset( $data['id'] ) ) {
				$logger = new Logger();

				$ecom_product = new Activecampaign_For_Woocommerce_Ecom_Product();
				if ( isset( $data['variation_id'] ) ) {
					$ecom_product->set_externalid( $data['variation_id'] );
					$child_product = wc_get_product( $data['variation_id'] );
				} else {
					$ecom_product->set_externalid( $data['product_id'] );
				}

				if ( isset( $data['name'] ) && ! empty( $data['name'] ) ) {
					$ecom_product->set_name( $data['name'] );
				} elseif ( self::validate_object( $child_product, 'get_name' ) ) {
					$ecom_product->set_name( $child_product->get_name() );
				} else {
					$ecom_product->set_name( $wc_product->get_name() );
				}

				if ( self::validate_object( $child_product, 'get_name' ) ) {
					$ecom_product->set_price( $child_product->get_price() > 0 ? $child_product->get_price() * 100 : 0 );
				} else {
					$ecom_product->set_price( $wc_product->get_price() > 0 ? $wc_product->get_price() * 100 : 0 );
				}

				$ecom_product->set_category( $this->get_product_all_categories( $child_product ) );
				$ecom_product->set_image_url( $this->get_product_image_url_from_wc( $child_product ) );
				$ecom_product->set_product_url( $this->get_product_url_from_wc( $child_product ) );
				$ecom_product->set_sku( $this->get_sku( $child_product ) );

				if ( self::validate_object( $child_product, 'get_short_description' ) && ! empty( $child_product->get_short_description() ) ) {
					$description = $child_product->get_short_description();
				} elseif ( self::validate_object( $child_product, 'get_description' ) && ! empty( $child_product->get_description() ) ) {
					$description = $child_product->get_description();
				}

				if ( empty( $description ) && ! empty( $wc_product->get_short_description() ) ) {
					$description = $wc_product->get_short_description();
				} elseif ( empty( $description ) && ! empty( $wc_product->get_description() ) ) {
					$description = $wc_product->get_description();
				}

				if ( empty( $description ) ) {
					$ecom_product->set_description( '' );
				} else {
					$ecom_product->set_description( $this->clean_product_description( $description ) );
				}

				if (
					empty( $ecom_product->get_category() ) ||
					'Unknown' === $ecom_product->get_category()
				) {
					$ecom_product->set_category( $this->get_product_all_categories( $wc_product ) );
				}

				if ( empty( $ecom_product->get_image_url() ) ) {
					$ecom_product->set_image_url( $this->get_product_image_url_from_wc( $wc_product ) );
				}
				if ( empty( $ecom_product->get_product_url() ) ) {
					$ecom_product->set_product_url( $this->get_product_url_from_wc( $wc_product ) );
				}
				if ( empty( $ecom_product->get_sku() ) ) {
					$ecom_product->set_sku( $this->get_sku( $wc_product ) );
				}

				return $ecom_product;
			}
		} catch ( Throwable $t ) {
			$logger = new Logger();
			$logger->warning(
				'There was an exception attempting to convert WC product data to AC product data.',
				[
					'message'          => $t->getMessage(),
					'suggested_action' => 'Please refer to the message for explanation.',
					'ac_code'          => 'EPF_162',
					'trace'            => $logger->clean_trace( $t->getTrace() ),
				]
			);
		}

		return null;
	}

	/**
	 * Given a WC Product, create an Ecom Product.
	 *
	 * @param WC_Product $product The WC Product.
	 *
	 * @return Activecampaign_For_Woocommerce_Ecom_Product|null
	 */
	private function convert_wc_product_to_ecom_product( $product ) {
		try {
			if ( self::validate_object( $product, 'get_id' ) && ! empty( $product->get_id() ) ) {
				$ecom_product = new Activecampaign_For_Woocommerce_Ecom_Product();

				$ecom_product->set_externalid( $product->get_id() );
				$ecom_product->set_name( $product->get_name() );
				$ecom_product->set_price( $product->get_price() > 0 ? $product->get_price() * 100 : 0 );
				$ecom_product->set_category( $this->get_product_all_categories( $product ) );
				$ecom_product->set_image_url( $this->get_product_image_url_from_wc( $product ) );
				$ecom_product->set_product_url( $this->get_product_url_from_wc( $product ) );
				$ecom_product->set_sku( $this->get_sku( $product ) );

				if ( ! empty( $product->get_short_description() ) ) {
					$description = $product->get_short_description();
				} else {
					$description = $product->get_description();
				}

				$ecom_product->set_description( $this->clean_product_description( $description ) );

				return $ecom_product;
			}
		} catch ( Throwable $t ) {
			$logger = new Logger();
			$logger->warning(
				'There was an exception attempting to convert WC product data to AC product data.',
				[
					'suggested_action' => 'Please refer to the message for explanation.',
					'message'          => $t->getMessage(),
					'ac_code'          => 'EPF_207',
					'trace'            => $logger->clean_trace( $t->getTrace() ),
				]
			);
		}

		return null;
	}

	/**
	 * Convert a pre_product object to a generic product.
	 *
	 * @param array $pre_product The product item.
	 *
	 * @return Activecampaign_For_Woocommerce_Ecom_Product
	 */
	private function convert_item_data_to_generic_product( $pre_product ) {
		$logger = new Logger();
		try {
			$ecom_product = new Activecampaign_For_Woocommerce_Ecom_Product();

			if ( $pre_product['variation_id'] ) {
				$ecom_product->set_externalid( $pre_product['variation_id'] );
			} else {
				$ecom_product->set_externalid( $pre_product['product_id'] );
			}

			$ecom_product->set_name( $pre_product['name'] );
			$ecom_product->set_price( $pre_product['total'] > 0 ? $pre_product['total'] * 100 : 0 );
			$ecom_product->set_category( $this->get_product_all_categories( $pre_product['item'] ) );
			$ecom_product->set_image_url( $this->get_product_image_url_from_wc( $pre_product['item'] ) );
			$ecom_product->set_product_url( $this->get_product_url_from_wc( $pre_product['item'] ) );
			$ecom_product->set_sku( $this->get_sku( $pre_product['item'] ) );
			$ecom_product->set_description( '' );

			return $ecom_product;
		} catch ( Throwable $t ) {

			$logger->warning(
				'There was an exception attempting to convert WC product data to generic AC product data.',
				[
					'suggested_action' => 'Please refer to the message for explanation.',
					'message'          => $t->getMessage(),
					'ac_code'          => 'EPF_249',
					'trace'            => $logger->clean_trace( $t->getTrace() ),
				]
			);
		}
	}

	/**
	 * Cleans a description field by removing tags and shortening the number of words to a max amount.
	 *
	 * @param string $description The description.
	 *
	 * @return string
	 */
	public function clean_product_description( $description ) {
		$logger = new Logger();

		try {
			$plain_description = str_replace( array( "\r", "\n", '&nbsp;' ), ' ', $description );
			$plain_description = trim( wp_strip_all_tags( $plain_description, false ) );
			$plain_description = preg_replace( '/\s+/', ' ', $plain_description );
			$wrap_description  = wordwrap( $plain_description, 300 );
			$description_arr   = explode( "\n", $wrap_description );
			if ( isset( $description_arr[0] ) ) {
				$fin_description = $description_arr[0] . '...';
			}
		} catch ( Throwable $t ) {
			$logger->warning(
				'There was an issue cleaning the description field.',
				[
					'message'     => $t->getMessage(),
					'ac_code'     => 'EPF_280',
					'description' => $description,
				]
			);
		}

		if ( ! empty( $fin_description ) ) {
			return $fin_description;
		}

		if ( ! empty( $plain_description ) ) {
			return $plain_description;
		}

		return $description;
	}

	/**
	 * Parse the results of the all of a product's categories and return all as separated list
	 *
	 * @param WC_Product $product The WC Product.
	 *
	 * @return string|null
	 */
	private function get_product_all_categories( $product ) {
		$logger = new Logger();
		if ( self::validate_object( $product, 'get_id' ) && ! empty( $product->get_id() ) ) {
			$terms    = get_the_terms( $product->get_id(), 'product_cat' );
			$cat_list = [];
			try {
				// go through the categories and make a named list
				if ( ! empty( $terms ) && is_array( $terms ) ) {
					foreach ( $terms as $term ) {
						$product_cat_id   = $term->term_id;
						$product_cat_name = $term->name;
						if ( $product_cat_id >= 0 && ! empty( $product_cat_name ) ) {
							$cat_list[] = $product_cat_name;
						} else {
							$logger->warning(
								'A product category attached to this product does not have a valid category and/or name.',
								[
									'product_id' => $product->get_id(),
									'term_id'    => $term->term_id,
									'term_name'  => $term->name,
									'ac_code'    => 'EPF_322',
								]
							);
						}
					}
				}
			} catch ( Throwable $t ) {
				$logger->warning(
					'There was an error getting all product categories.',
					[
						'terms'          => $terms,
						'product_id'     => $product->get_id(),
						'trace'          => $logger->clean_trace( $t->getTrace() ),
						'thrown_message' => $t->getMessage(),
						'ac_code'        => 'EPF_335',
					]
				);
			}

			if ( ! empty( $cat_list ) ) {
				// Convert to a comma separated string
				return implode( ', ', $cat_list );
			}
		}

		return null;
	}

	/**
	 * Get the image URL for a given WC Product.
	 *
	 * @param WC_Product $product The WC Product.
	 * @return string|null
	 */
	private function get_product_image_url_from_wc( $product ) {

		if ( self::validate_object( $product, 'get_id' ) && ! empty( $product->get_id() ) ) {
			try {
				$post         = $product->get_id();
				$thumbnail_id = get_post_thumbnail_id( $post );
				$image_src    = wp_get_attachment_image_src( $thumbnail_id, 'woocommerce_single' );

				if ( ! is_array( $image_src ) ) {
					$thumbnail_url = get_the_post_thumbnail_url( $post );

					if ( empty( $thumbnail_url ) ) {
						$thumbnail_url = wp_get_original_image_url( $thumbnail_id );
					}

					if ( isset( $thumbnail_url ) && ! empty( $thumbnail_url ) ) {
						return $this->check_for_baseurl( $thumbnail_url );
					}

					return wp_get_original_image_url( $product->get_image_id() );
				}
				// The first element is the actual URL
				return $this->check_for_baseurl( $image_src[0] );
			} catch ( Throwable $t ) {
				$logger = new Logger();

				$logger->warning(
					'There was an error getting product image url.',
					[
						'thrown_message' => $t->getMessage(),
						'ac_code'        => 'EPF_386',
						'post'           => isset( $post ) ? $post : null,
						'thumbnail_id'   => isset( $thumbnail_id ) ? $thumbnail_id : null,
						'image_src'      => isset( $image_src ) ? $image_src : null,
						'product_id'     => self::validate_object( $product, 'get_id' ) ? $product->get_id() : null,
						'trace'          => $logger->clean_trace( $t->getTrace() ),
					]
				);
			}
		}

		return '';
	}

	/**
	 * Checks for and returns the corrected base URL for our image.
	 *
	 * @param string $url The passed url.
	 *
	 * @return mixed|string The fixed url.
	 */
	private function check_for_baseurl( $url ) {
		$p_site_url   = wp_parse_url( site_url() );
		$p_passed_url = wp_parse_url( $url );

		if (
			! isset( $p_passed_url['host'], $p_passed_url['scheme'], $p_passed_url['path'] ) ||
			stripos( $p_passed_url['host'], $p_site_url['host'] ) === false ||
			$p_site_url['host'] !== $p_passed_url['host'] ||
			'https' !== $p_passed_url['scheme']
		) {
			return 'https://' . $p_site_url['host'] . $p_passed_url['path'];
		}

		return $url;
	}


	/**
	 * Get the product url for the product
	 *
	 * @param  WC_Product $product The WC Product.
	 * @return false|string|null
	 */
	private function get_product_url_from_wc( $product ) {
		if ( self::validate_object( $product, 'get_id' ) && ! empty( $product->get_id() ) ) {
			try {
				$product_id = $product->get_id();
				$url        = get_permalink( $product_id );

				if ( is_null( $url ) || empty( $url ) ) {
					return '';
				}

				return $url;
			} catch ( Throwable $t ) {
				$logger = new Logger();
				$logger->warning(
					'There was an error getting product URL.',
					[
						'product_id'     => self::validate_object( $product, 'get_id' ) ? $product->get_id() : null,
						'ac_code'        => 'EPF_438',
						'thrown_message' => $t->getMessage(),
						'trace'          => $logger->clean_trace( $t->getTrace() ),
					]
				);
			}
		}

		return '';
	}

	/**
	 * Get the sku for the product
	 *
	 * @param WC_Product $product The WC Product.
	 * @return string|null
	 */
	private function get_sku( $product ) {
		if ( self::validate_object( $product, 'get_sku' ) && ! empty( $product->get_sku() ) ) {
			try {
				$sku = $product->get_sku();

				if ( is_null( $sku ) || empty( $sku ) ) {
					return '';
				}

				return $sku;
			} catch ( Throwable $t ) {
				$logger = new Logger();
				$logger->warning(
					'There was an error getting product sku.',
					[
						'product_id'     => self::validate_object( $product, 'get_id' ) ? $product->get_id() : null,
						'ac_code'        => 'EPF_471',
						'thrown_message' => $t->getMessage(),
						'trace'          => $logger->clean_trace( $t->getTrace() ),
					]
				);
			}
		}
		return '';
	}
}
