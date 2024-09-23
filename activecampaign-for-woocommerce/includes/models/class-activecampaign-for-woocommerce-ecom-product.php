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

/**
 * The model class for the EcomProduct
 *
 * @since      1.0.0
 * @package    Activecampaign_For_Woocommerce
 * @subpackage Activecampaign_For_Woocommerce/includes/models
 * @author     acteamintegrations <team-integrations@activecampaign.com>
 */
class Activecampaign_For_Woocommerce_Ecom_Product {
	use Api_Serializable;

	/**
	 * The API Mappings for the API Serializable trait.
	 *
	 * @var array
	 */
	public $api_mappings = [
		'category'    => 'category',
		'externalid'  => 'externalid',
		'name'        => 'name',
		'price'       => 'price',
		'quantity'    => 'quantity',
		'orderid'     => 'orderid',
		'product_url' => 'productUrl',
		'sku'         => 'sku',
		'image_url'   => 'imageUrl',
		'description' => 'description',
		'id'          => 'id',
	];

	/**
	 * The category.
	 *
	 * @var string
	 */
	private $category;

	/**
	 * The external id.
	 *
	 * @var string
	 */
	private $externalid;

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
	 * The order id.
	 *
	 * @var string
	 */
	private $orderid;

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
	 * The description.
	 *
	 * @var string
	 */
	private $description;

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
	 * Returns the external id.
	 *
	 * @return string
	 */
	public function get_externalid() {
		return $this->externalid;
	}

	/**
	 * Sets the external id.
	 *
	 * @param string $externalid The external id.
	 */
	public function set_externalid( $externalid ) {
		$this->externalid = $externalid;
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
		$this->quantity = $quantity;
	}

	/**
	 * Returns the order id.
	 *
	 * @return string
	 */
	public function get_orderid() {
		return $this->orderid;
	}

	/**
	 * Sets the order id.
	 *
	 * @param string $orderid The order id.
	 */
	public function set_orderid( $orderid ) {
		$this->orderid = $orderid;
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
	 * Returns the description.
	 *
	 * @return string
	 */
	public function get_description() {
		return $this->description;
	}

	/**
	 * Sets the description.
	 *
	 * @param string $description The description.
	 */
	public function set_description( $description ) {
		$this->description = $description;
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
		$this->id = $id;
	}
}
