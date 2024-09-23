<?php

/**
 * The features available
 *
 * @link       https://www.activecampaign.com/
 * @since      1.0.0
 *
 * @package    Activecampaign_For_Woocommerce
 * @subpackage Activecampaign_For_Woocommerce/admin
 */

use Activecampaign_For_Woocommerce_Logger as Logger;
use Activecampaign_For_Woocommerce_Ac_Features_Repository as Ac_Features_Repository;
use Activecampaign_For_Woocommerce_Api_Client as Api_Client;

/**
 * The admin-specific functionality of the plugin.
 *
 * Defines the plugin name, version, and two examples hooks for how to
 * enqueue the admin-specific stylesheet and JavaScript.
 *
 * @package    Activecampaign_For_Woocommerce
 * @subpackage Activecampaign_For_Woocommerce/admin
 * @author     acteamintegrations <team-integrations@activecampaign.com>
 */

trait Activecampaign_For_Woocommerce_Features_Checker {

	/**
	 * Features array.
	 *
	 * @var array
	 */
	private $features;

	/**
	 * Gets the feature validation
	 *
	 * @param string $feature
	 *
	 * @return bool
	 */
	public function verify_ac_features( $feature ) {
		$logger   = new Logger();
		$features = array(
			'historical' => 'ecom-historical-sync',
			'abandon'    => 'ecom-core',
			'activesync' => 'ecom-core',
			'recurring'  => 'ecom-recurring-payments',
			'product'    => 'ecom-product-catalog',
			'ecommerce'  => 'ecommerce',
		);

		// core features
		if ( true !== in_array( $feature, array_keys( $features ), true ) ) {
			$logger->warning(
				'The feature being checked for feature may not be correct. Please contact ActiveCampaign as this may be a bug.',
				[
					'in_feature_list' => in_array( $feature, $features, true ),
					'feature_name'    => $feature,
					'features_list'   => $features,
				]
			);

			return false;
		}

		// Check for plan level first
		if ( ! isset( $this->features ) ) {
			$this->check_ac_plan();
		}

		if ( false === $this->features ) {
			$logger->warning( 'You may not have the plan level necessary to access all features in the ActiveCampaign plugin. Please verify your ActiveCampaign account.' );
			return false;
		}

		// If the plan is legacy or has the feature
		if (
			in_array( 'ecommerce', $this->features ) ||
			in_array( $features[ $feature ], $this->features )
		) {
			return true;
		}

		return false;
	}

	/**
	 * Check for the plan level using cache first then verify from AC.
	 */
	public function check_ac_plan() {
		if ( empty( $this->features ) ) {
			$this->features = get_transient( 'activecampaign_for_woocommerce_features' );
		}

		if ( ! $this->features ) {
			$this->get_and_set_ecom_features_from_ac();
			$this->cache_ac_features();
		}
	}

	/**
	 * Retrieves the plan level from AC.
	 */
	private function get_and_set_ecom_features_from_ac() {
		$logger   = new Logger();
		$settings = get_option( ACTIVECAMPAIGN_FOR_WOOCOMMERCE_DB_SETTINGS_NAME );
		$api_uri  = isset( $settings['api_url'] ) ? $settings['api_url'] : null;
		$api_key  = isset( $settings['api_key'] ) ? $settings['api_key'] : null;

		$ac_features_repo = new Ac_Features_Repository( new Api_Client( $api_uri, $api_key, $logger ) );

		// grab it from AC
		$account_features = $ac_features_repo->find_all_current();

		// Parse for a valid one
		$feature_list = $account_features->get_valid_entitlements();

		if ( isset( $feature_list ) && null !== $feature_list && ! empty( $feature_list ) ) {
			// Set the plan name global
			$this->features = $feature_list;
			$this->cache_ac_features();
		}
	}

	/**
	 * Saves the plan level to the local cache
	 */
	private function cache_ac_features() {
		if ( ! $this->features ) {
			$this->features = false;
		}

		set_transient( 'activecampaign_for_woocommerce_features', $this->features, 43200 );
	}
}
