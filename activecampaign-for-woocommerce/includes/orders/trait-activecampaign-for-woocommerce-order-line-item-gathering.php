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
trait Activecampaign_For_Woocommerce_Order_Line_Item_Gathering {

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
	 * Gets and returns the WooCommerce tag names.
	 *
	 * @param string $product_id The product ID.
	 *
	 * @return array|void|null
	 */
	private function get_wc_tag_names( $product_id ) {
		$logger = new Logger();

		try {
			$tags = array();

			$current_tags = get_the_terms( $product_id, 'product_tag' );

			if ( $current_tags && ! is_wp_error( $current_tags ) ) {
				foreach ( $current_tags as $tag ) {
					$tags[] = $tag->name;
				}
			}

			if ( empty( $tags ) ) {
				return null;
			}

			return $tags;
		} catch ( Throwable $t ) {
			$logger->warning(
				'There was an issue retrieving tags',
				[
					'product_id' => $product_id,
					'message'    => $t->getMessage(),
					'trace'      => $t->getTrace(),
				]
			);
		}
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

					$thumbnail_url = wp_get_original_image_url( $product->get_image_id() );
					if ( isset( $thumbnail_url ) && ! empty( $thumbnail_url ) ) {
						return $thumbnail_url;
					}

					return '';
				}

				if ( isset( $image_src[0] ) && ! empty( $image_src[0] ) ) {
					// The first element is the actual URL
					return $this->check_for_baseurl( $image_src[0] );
				} else {
					$thumbnail_url = wp_get_original_image_url( $product->get_image_id() );
					if ( isset( $thumbnail_url ) && ! empty( $thumbnail_url ) ) {
						return $thumbnail_url;
					}
				}
			} catch ( Throwable $t ) {
				$logger = new Logger();

				$logger->warning(
					'There was an error getting product image url.',
					[
						'thrown_message' => $t->getMessage(),
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
	private function get_product_sku_from_wc( $product ) {
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
						'thrown_message' => $t->getMessage(),
						'trace'          => $logger->clean_trace( $t->getTrace() ),
					]
				);
			}
		}
		return '';
	}

	/**
	 * Gets the brands for a product. Currently not used.
	 *
	 * @ref https://woocommerce.com/document/woocommerce-brands/
	 *
	 * @param     int $product_id
	 * @return false|string|WP_Error|WP_Term[]
	 */
	private function get_wc_product_brands( $product_id ) {
		$logger = new Logger();
		if ( isset( $product_id ) ) {
			$brands = get_the_term_list( $product_id, 'product_brand', '"', ', ', '"' );

			if ( isset( $brands->errors ) ) {
				$brands = get_the_term_list( $product_id, 'pa_brand', '"', ', ', '"' );
				// $brands = get_the_terms( $product_id, 'pa_brand' );
			}

			if ( isset( $brands->errors ) ) {
				$brands = get_the_term_list( $product_id, 'berocket_brand', '"', ', ', '"' );
				// $brands = get_the_terms( $product_id, 'berocket_brand' );
			}

			if ( isset( $brands->errors ) ) {
				$brands = get_the_terms( $product_id, 'product_brand' );
			}

			if ( ! empty( $brands ) ) {
				return $brands;
			}
		}

		return null;
	}
}
