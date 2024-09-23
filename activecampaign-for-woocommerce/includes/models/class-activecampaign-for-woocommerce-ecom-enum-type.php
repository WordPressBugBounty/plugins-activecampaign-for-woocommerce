<?php

/**
 * In generating graphql, enums should not have quotes around them. Wrapping a string in this class allows us to
 * remove those quotes when serializing our graphql.
 */
use Activecampaign_For_Woocommerce_Api_Serializable as Api_Serializable;
use Activecampaign_For_Woocommerce_Ecom_Model_Interface as Ecom_Model;
use Activecampaign_For_Woocommerce_Has_Id as Has_Id;
use Activecampaign_For_Woocommerce_Has_Email as Has_Email;
use Activecampaign_For_Woocommerce_Logger as Logger;


class Activecampaign_For_Woocommerce_Ecom_Enum_Type {
	/**
	 * @var string
	 */
	public $val;

	public function __construct( $val ) {
		$this->val = $val;
	}
}
