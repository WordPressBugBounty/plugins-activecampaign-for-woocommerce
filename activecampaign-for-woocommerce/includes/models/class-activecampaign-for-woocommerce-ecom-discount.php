<?php

/**
 * The file for the Ecom Customer Address Model
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

/**
 * The model class for the Ecom Customer Address
 *
 * @since      1.0.0
 * @package    Activecampaign_For_Woocommerce
 * @subpackage Activecampaign_For_Woocommerce/includes/models
 * @author     acteamintegrations <team-integrations@activecampaign.com>
 */
class Activecampaign_For_Woocommerce_Ecom_Discount {
	use Api_Serializable;

	/**
	 * The API mappings for the API_Serializable trait.
	 *
	 * @var array
	 */
	public $api_mappings = [
		'name'            => 'name',
		'type'            => 'type',
		'discount_amount' => 'discountAmount',
	];

	/**
	 * The type of discount.
	 *
	 * @var string
	 */
	private $type;

	/**
	 * The name of the discount
	 *
	 * @var string
	 */
	private $name;

	/**
	 * The discount amount
	 *
	 * @var string
	 */
	private $discount_amount;

	/**
	 * Returns the type.
	 *
	 * @return string
	 */
	public function get_type() {
		return $this->type;
	}

	/**
	 * Sets the email.
	 *
	 * @param Ecom_Discount $type The type.
	 * @throws RuntimeException Throws an exception if the email is invalid.
	 */
	public function set_type( $type ) {
		if ( isset( $type ) ) {
			$this->type = $type;
		} else {
			$this->type = 'ORDER';
		}
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
		$this->name = (string) $name;
	}

	/**
	 * Returns the discount amount.
	 *
	 * @return string
	 */
	public function get_discount_amount() {
		return $this->discount_amount;
	}

	/**
	 * Sets the discount amount.
	 *
	 * @param BigDemical $discount_amount The discount amount.
	 */
	public function set_discount_amount( $discount_amount ) {
		$this->discount_amount = $discount_amount ? BigDecimal::of( $discount_amount ) : 0;
	}
}
