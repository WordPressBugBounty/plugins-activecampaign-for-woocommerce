<?php

/**
 * The file for the EcomProduct Model
 *
 * @link       https://www.activecampaign.com/
 * @since      1.0.0
 *
 * @package    Activecampaign_For_Woocommerce
 * @subpackage Activecampaign_For_Woocommerce/includes/models
 */

use Activecampaign_For_Woocommerce_Api_Serializable as Api_Serializable;
use Activecampaign_For_Woocommerce_Logger as Logger;
use AcVendor\Brick\Math\BigDecimal;
use AcVendor\Brick\Math\RoundingMode;

/**
 * The model class for the EcomProduct
 *
 * @since      1.0.0
 * @package    Activecampaign_For_Woocommerce
 * @subpackage Activecampaign_For_Woocommerce/includes/models
 * @author     acteamintegrations <team-integrations@activecampaign.com>
 */
class Activecampaign_For_Woocommerce_Cofe_Ecom_Order_Line_Item {
	use Api_Serializable;

	/**
	 * The API Mappings for the API Serializable trait.
	 *
	 * @var array
	 */
	public $api_mappings = [
		'category'       => 'category',
		'order_id'       => 'storeOrderId', // is this id?
		'name'           => 'name', // same
		'price'          => 'priceAmount',
		'quantity'       => 'quantity', // same
		'product_url'    => 'productUrl', // permalink
		'sku'            => 'sku',
		'image_url'      => 'imageUrl',
		'id'             => 'id',
		'line_item_id'   => 'storeLineItemId',
		'product_id'     => 'productStorePrimaryId',
		'average_rating' => 'averageRating', // Bigdecimal
		'is_on_sale'     => 'isOnSale', // bool
		'brand'          => 'brand',
		'tags'           => 'tags',

	];

	// normalizedOrderStatus

	// fulfillmentStatus - partial, unshipped, shipped - do we know this?
	// shippingStatus
	/**
	 * The category.
	 *
	 * @var string
	 */
	private $category;

	/**
	 * The name.
	 *
	 * @var string
	 */
	private $name;

	/**
	 * Tne price.
	 *
	 * @var string
	 */
	private $price;

	/**
	 * The quantity.
	 *
	 * @var string
	 */
	private $quantity;

	/**
	 * The product url.
	 *
	 * @var string
	 */
	private $product_url;

	/**
	 * The image URL.
	 *
	 * @var string
	 */
	private $image_url;

	/**
	 * The id.
	 *
	 * @var string
	 */
	private $id;

	/**
	 * The sku.
	 *
	 * @var string
	 */
	private $sku;

	/**
	 * The line item id.
	 *
	 * @var string
	 */
	private $line_item_id;

	/**
	 * The product ID.
	 *
	 * @var string
	 */
	private $product_id;

	/**
	 * The order ID.
	 *
	 * @var string
	 */
	private $order_id;

	/**
	 * The rating if available.
	 *
	 * @var bigDecimal
	 */
	private $average_rating;

	/**
	 * If the product is on sale.
	 *
	 * @var bool
	 */
	private $is_on_sale = false;

	/**
	 * The brand if available.
	 *
	 * @var string
	 */
	private $brand;

	/**
	 * The tags in a serialized array.
	 *
	 * @var string
	 */
	private $tags;

	/**
	 * Returns the category.
	 *
	 * @return string
	 */
	public function get_category() {
		return $this->category;
	}

	/**
	 * Sets the category.
	 *
	 * @param string $category The category.
	 */
	public function set_category( $category ) {
		$this->category = $category;
	}

	/**
	 * Returns the name.
	 *
	 * @return string
	 */
	public function get_name() {
		return $this->name;
	}

	/**
	 * Sets the name.
	 *
	 * @param string $name The name.
	 */
	public function set_name( $name ) {
		$this->name = $name;
	}

	/**
	 * Returns the price.
	 *
	 * @return string
	 */
	public function get_price() {
		return $this->price;
	}

	/**
	 * Sets the price.
	 *
	 * @param string $price The price.
	 */
	public function set_price( $price ) {
		$this->price = $price;
	}

	/**
	 * Returns the quantity.
	 *
	 * @return string
	 */
	public function get_quantity() {
		return $this->quantity;
	}

	/**
	 * Sets the quantity.
	 *
	 * @param string $quantity The quantity.
	 */
	public function set_quantity( $quantity ) {
		$this->quantity = $quantity ? (int) $quantity : 0;
	}

	/**
	 * Returns the product url.
	 *
	 * @return string
	 */
	public function get_product_url() {
		return $this->product_url;
	}

	/**
	 * Sets the product url.
	 *
	 * @param string $product_url The product url.
	 */
	public function set_product_url( $product_url ) {
		$this->product_url = $product_url;
	}

	/**
	 * Sets the product sku.
	 *
	 * @param string $sku The product sku.
	 */
	public function set_sku( $sku ) {
		$this->sku = $sku;
	}

	/**
	 * Sets the product sku.
	 *
	 * @return string
	 */
	public function get_sku() {
		return $this->sku;
	}

	/**
	 * Returns the image url.
	 *
	 * @return string
	 */
	public function get_image_url() {
		return $this->image_url;
	}

	/**
	 * Sets the image url.
	 *
	 * @param string $image_url The image url.
	 */
	public function set_image_url( $image_url ) {
		$this->image_url = $image_url;
	}

	/**
	 * Gets the average rating.
	 *
	 * @return BigDecimal
	 */
	public function get_average_rating() {
		return $this->average_rating;
	}

	/**
	 * Sets the average rating as BigDecimal.
	 *
	 * @param mixed $average_rating
	 */
	public function set_average_rating( $average_rating ) {
		$this->average_rating = $average_rating ? BigDecimal::of( $average_rating ) : 0;
	}

	/**
	 * Returns the on sale marker.
	 *
	 * @return bool
	 */
	public function get_is_on_sale() {
		return $this->is_on_sale;
	}

	/**
	 * Sets the on sale marker.
	 *
	 * @param bool $is_on_sale
	 */
	public function set_is_on_sale( $is_on_sale ) {
		// Make sure it's a bool
		$this->is_on_sale = $is_on_sale ? (bool) $is_on_sale : false;
	}

	/**
	 * Returns the brand.
	 *
	 * @return string
	 */
	public function get_brand() {
		return $this->brand;
	}

	/**
	 * Sets the brand.
	 *
	 * @param string $brand
	 */
	public function set_brand( $brand ) {
		$this->brand = maybe_serialize( $brand );
	}

	/**
	 * Returns the tags.
	 *
	 * @return string
	 */
	public function get_tags() {
		return $this->tags;
	}

	/**
	 * Sets the tags.
	 *
	 * @param string $tags
	 */
	public function set_tags( $tags ) {
		if ( isset( $tags ) && ! empty( $tags ) ) {
			$this->tags = $tags;
		} else {
			$this->tags = null;
		}

	}

	/**
	 * Returns the id.
	 *
	 * @return string
	 */
	public function get_id() {
		return $this->id;
	}

	/**
	 * Sets the id.
	 *
	 * @param string $id The id.
	 */
	public function set_id( $id ) {
		$this->id = $id ? (string) $id : null;
	}

	public function get_line_item_id() {
		return $this->line_item_id;
	}

	public function set_line_item_id( $id ) {
		$this->line_item_id = $id ? (string) $id : null;
	}

	public function get_product_id() {
		return $this->product_id;
	}

	public function set_product_id( $id ) {
		$this->product_id = $id ? (string) $id : null;
	}

	public function get_order_id() {
		return $this->order_id;
	}

	public function set_order_id( $id ) {
		$this->order_id = $id ? (string) $id : null;
	}

	/**
	 * Sets the properties using the passed product->get_data product data.
	 * Processes either parent or child data.
	 *
	 * @param array $data The product data array.
	 */
	public function set_properties_from_product_data( $data ) {
		$logger = new Logger();

		if ( ! isset( $data['id'] ) ) {
			throw new RuntimeException( 'Product data is not available ' . $data );
		}

		if ( isset( $data['id'] ) ) {
			$this->set_line_item_id( $data['id'] );

			if ( isset( $data['variation_id'] ) && ! empty( $data['variation_id'] ) ) {
				$this->set_product_id( $data['variation_id'] );
			} elseif ( isset( $data['product_id'] ) ) {
				$this->set_product_id( $data['product_id'] );
			}

			if ( isset( $data['name'] ) ) {
				$this->set_name( $data['name'] );
			}

			if ( isset( $data['order_id'] ) ) {
				$this->set_order_id( $data['order_id'] );
			}

			if ( isset( $data['is_on_sale'] ) ) {
				$this->set_is_on_sale( $data['is_on_sale'] );
			}

			if ( isset( $data['average_rating'] ) ) {
				$this->set_average_rating( $data['average_rating'] );
			}

			if ( isset( $data['subtotal'] ) ) {
				// This should be the total per 1 item, not the total overall

				if ( $data['subtotal'] > 0 && $data['quantity'] > 1 ) {
					$price = BigDecimal::of( $data['subtotal'] )->dividedBy( $data['quantity'], 0, RoundingMode::UP );
				} else {
					$price = BigDecimal::of( $data['subtotal'] );
				}
				$this->set_price( $data['total'] > 0 ? $price : 0 );
			} elseif ( isset( $data['total'] ) ) {
				// This should be the total per 1 item, not the total overall

				if ( $data['total'] > 0 && $data['quantity'] > 1 ) {
					$price = BigDecimal::of( $data['total'] )->dividedBy( $data['quantity'], 0, RoundingMode::UP );
				} else {
					$price = BigDecimal::of( $data['total'] );
				}
				$this->set_price( $data['total'] > 0 ? $price : 0 );
			} else {
				$logger->warning(
					'Price is missing for this line item',
					[
						$data,
					]
				);
			}

			if ( isset( $data['quantity'] ) ) {
				$this->set_quantity( $data['quantity'] );
			} else {
				$logger->warning(
					'Quantity is missing for this line item',
					[
						$data,
					]
				);
			}
		}
	}
}
