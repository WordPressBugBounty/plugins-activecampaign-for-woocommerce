<?php

/**
 * The User Meta Service, providing access to meta user data from the DB.
 *
 * @link       https://www.activecampaign.com/
 * @since      1.0.0
 * @package    Activecampaign_For_Woocommerce
 * @subpackage Activecampaign_For_Woocommerce/includes/service
 */

/**
 * The User Meta Service Class
 *
 * @since      1.0.0
 * @package    Activecampaign_For_Woocommerce
 * @subpackage Activecampaign_For_Woocommerce/
 * @author     acteamintegrations <team-integrations@activecampaign.com>
 */
class Activecampaign_For_Woocommerce_User_Meta_Service {
	const ACTIVECAMPAIGN_CUSTOMER_ID = 'activecampaign_for_woocommerce_ac_customer_id';
	const ACTIVECAMPAIGN_CART_ID     = 'activecampaign_for_woocommerce_ac_cart_id';

	/**
	 * Returns the current cart id for the user from the user meta table.
	 *
	 * @param int $user_id The User ID.
	 *
	 * @return string
	 * @access public
	 * @since  1.0.0
	 */
	public static function get_current_cart_id( $user_id ) {
		return get_user_meta(
			$user_id,
			ACTIVECAMPAIGN_FOR_WOOCOMMERCE_PERSISTENT_CART_ID_NAME,
			true
		);
	}

	/**
	 * Sets on the user's meta table the cart ID to be used by Hosted.
	 *
	 * @access public
	 * @since  1.0.0
	 *
	 * @param int $user_id The User ID.
	 */
	public static function set_current_cart_id( $user_id, $current_cart_id = null ) {
		if ( isset( $current_cart_id ) && ! empty( $current_cart_id ) ) {
			add_user_meta(
				$user_id,
				ACTIVECAMPAIGN_FOR_WOOCOMMERCE_PERSISTENT_CART_ID_NAME,
				static::generate_random_cart_id(),
				true
			);
		} else {
			update_user_meta(
				$user_id,
				ACTIVECAMPAIGN_FOR_WOOCOMMERCE_PERSISTENT_CART_ID_NAME,
				static::generate_random_cart_id(),
				$current_cart_id
			);
		}
	}

	/**
	 * Generates and returns a unique id.
	 *
	 * @return string
	 * @access public
	 * @since  1.0.0
	 */
	public static function generate_random_cart_id() {
		return uniqid( '', true );
	}

	/**
	 * Deletes the current cart id.
	 *
	 * @access public
	 * @since  1.0.0
	 *
	 * @param int $user_id The User ID.
	 */
	public static function delete_current_cart_id( $user_id ) {
		delete_user_meta(
			$user_id,
			ACTIVECAMPAIGN_FOR_WOOCOMMERCE_PERSISTENT_CART_ID_NAME
		);
	}

	/**
	 * Returns the current cart's ActiveCampaign id for the user from the user meta table.
	 *
	 * @param int $user_id The User ID.
	 *
	 * @return string
	 * @access public
	 * @since  1.0.0
	 */
	public static function get_current_cart_ac_id( $user_id ) {
		return get_user_meta(
			$user_id,
			static::ACTIVECAMPAIGN_CART_ID,
			true
		);
	}

	/**
	 * Sets on the user's meta table the cart's ActiveCampaign ID to be used by Hosted.
	 *
	 * @access public
	 * @since  1.0.0
	 *
	 * @param int $user_id    The user ID.
	 * @param int $ac_cart_id The ActiveCampaign cart ID.
	 */
	public static function set_current_cart_ac_id( $user_id, $ac_cart_id ) {
		update_user_meta(
			$user_id,
			static::ACTIVECAMPAIGN_CART_ID,
			$ac_cart_id,
			true
		);
	}

	/**
	 * Deletes the current cart's ActiveCampaign id.
	 *
	 * @access public
	 * @since  1.0.0
	 *
	 * @param int $user_id The User ID.
	 */
	public static function delete_current_cart_ac_id( $user_id ) {
		delete_user_meta(
			$user_id,
			static::ACTIVECAMPAIGN_CART_ID
		);
	}

	/**
	 * Get the ID of the ActiveCampaign customer record for the given user ID.
	 *
	 * @param int $user_id The user ID.
	 *
	 * @return int
	 */
	public static function get_current_user_ac_customer_id( $user_id ) {
		return get_user_meta(
			$user_id,
			static::ACTIVECAMPAIGN_CUSTOMER_ID,
			true
		);
	}

	/**
	 * Set the ID of the ActiveCampaign customer record for the given user ID.
	 *
	 * @param int $woo_user_id The Woocommerce user ID.
	 * @param int $ac_user_id  The ActiveCampaign user ID.
	 *
	 * @return false|int
	 */
	public static function set_current_user_ac_customer_id( $woo_user_id, $ac_user_id ) {
		return update_user_meta(
			$woo_user_id,
			static::ACTIVECAMPAIGN_CUSTOMER_ID,
			$ac_user_id,
			true
		);
	}

	/**
	 * Returns whether or not the current user has accepted marketing.
	 *
	 * @return boolean
	 */
	public static function get_current_user_accepts_marketing() {
		return static::get_user_accepts_marketing( get_current_user_id() );
	}

	/**
	 * Returns whether or not the specified user has accepted marketing.
	 *
	 * @param int|string $user_id The id of the user.
	 *
	 * @return bool
	 */
	public static function get_user_accepts_marketing( $user_id ) {
		return (bool) get_user_meta(
			$user_id,
			ACTIVECAMPAIGN_FOR_WOOCOMMERCE_ACCEPTS_MARKETING_NAME,
			true
		);
	}

	/**
	 * Sets the accepts marketing value for the current user.
	 *
	 * @return boolean
	 *
	 * @param string|bool|int $value The new value for the accepts marketing setting. Will be converted to 1 or 0.
	 */
	public static function set_current_user_accepts_marketing( $value ) {
		return static::set_user_accepts_marketing( get_current_user_id(), $value );
	}

	/**
	 * Sets the accepts marketing value for the specified user.
	 *
	 * @return boolean
	 *
	 * @param  int             $user_id The user id.
	 * @param  string|bool|int $value   The new value for the accepts marketing setting. Will be converted to 1 or 0.
	 */
	public static function set_user_accepts_marketing( $user_id, $value ) {
		/**
		 * WordPress converts the bool false into an empty string on DB save.
		 */
		$set_value = (bool) $value ? 1 : 0;

		return update_user_meta(
			$user_id,
			ACTIVECAMPAIGN_FOR_WOOCOMMERCE_ACCEPTS_MARKETING_NAME,
			$set_value
		);
	}

	/**
	 * Deletes the accepts marketing meta value from all users.
	 */
	public static function delete_all_user_accepts_marketing() {
		delete_metadata(
			'user',
			0, // this doesn't actually matter in this call
			ACTIVECAMPAIGN_FOR_WOOCOMMERCE_ACCEPTS_MARKETING_NAME,
			'', // this also doesn't actually matter in this call
			true
		);
	}

	/**
	 * Deletes the ac customer id meta value from all users.
	 */
	public static function delete_all_user_ac_customer_id() {
		delete_metadata(
			'user',
			0, // this doesn't actually matter in this call
			static::ACTIVECAMPAIGN_CUSTOMER_ID,
			'', // this also doesn't actually matter in this call
			true
		);
	}

	/**
	 * Deletes the ac cart id meta value from all users.
	 */
	public static function delete_all_user_ac_cart_id() {
		delete_metadata(
			'user',
			0, // this doesn't actually matter in this call
			static::ACTIVECAMPAIGN_CART_ID,
			'', // this also doesn't actually matter in this call
			true
		);
	}

	/**
	 * Deletes all user meta values that our plugin adds.
	 */
	public static function delete_all_user_meta() {
		static::delete_all_user_ac_cart_id();
		static::delete_all_user_ac_customer_id();
		static::delete_all_user_accepts_marketing();
	}
}
