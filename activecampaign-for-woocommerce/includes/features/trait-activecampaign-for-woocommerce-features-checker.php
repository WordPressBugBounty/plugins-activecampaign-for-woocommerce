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
	 * @var array|null
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
			'historical'     => 'ecom-historical-sync',
			'abandon'        => 'ecom-core',
			'activesync'     => 'ecom-core',
			'recurring'      => 'ecom-recurring-payments',
			'product'        => 'ecom-product-catalog',
			'ecommerce'      => 'ecommerce',
			'browsetracking' => 'ecom-browse-abandonment',
		);

		// core features
		if ( true !== in_array( $feature, array_keys( $features ), true ) ) {
			$logger->warning(
				'The feature being checked for feature may not be correct. Please contact ActiveCampaign as this may be a bug.',
				array(
					'in_feature_list' => in_array( $feature, $features, true ),
					'feature_name'    => $feature,
					'features_list'   => $features,
				)
			);

			return false;
		}

		// Check for plan level first
		if ( ! isset( $this->features ) ) {
			$this->check_ac_plan();
		}

		if ( empty( $this->features ) ) {
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
	 *
	 * The features transient holds either the last successful answer from AC (12 hours)
	 * or, after a failed or still running call, the last known features (15 minutes).
	 * This keeps every request from calling AC again while AC is unreachable (DEFECT-43483).
	 */
	public function check_ac_plan() {
		$cached = get_transient( 'activecampaign_for_woocommerce_features' );

		// An array, also an empty one, is a stored answer. Anything else means we have to ask AC,
		// including the false that older versions cached after a failed call.
		$last_known = get_option( 'activecampaign_for_woocommerce_features_last_known', false );

		if ( is_array( $cached ) ) {
			$this->features = $cached;

			// Sites upgraded from older versions only have the transient: keep it as the fallback.
			if ( false === $last_known && ! empty( $cached ) ) {
				update_option( 'activecampaign_for_woocommerce_features_last_known', $cached );
			}

			return;
		}

		$last_known     = is_array( $last_known ) ? $last_known : array();
		$this->features = $last_known;

		// Saved before the call: when AC is down, or this request gets killed while waiting,
		// other requests use the last known features instead of calling AC again.
		// Without last known features (fresh connection) other requests keep asking AC instead
		// of running without features until this call returns.
		if ( ! empty( $last_known ) ) {
			set_transient( 'activecampaign_for_woocommerce_features', $last_known, 15 * MINUTE_IN_SECONDS );
		}

		$feature_list = $this->get_ecom_features_from_ac();

		if ( null === $feature_list ) {
			set_transient( 'activecampaign_for_woocommerce_features', $last_known, 15 * MINUTE_IN_SECONDS );
			$logger = new Logger();
			$logger->warning(
				'Could not retrieve the ActiveCampaign entitlements. Using the last known features and retrying in 15 minutes.',
				array(
					'last_known_features' => $last_known,
					'ac_code'             => 'FC_132',
				)
			);

			return;
		}

		$this->features = $feature_list;
		set_transient( 'activecampaign_for_woocommerce_features', $feature_list, 12 * HOUR_IN_SECONDS );
		update_option( 'activecampaign_for_woocommerce_features_last_known', $feature_list );
	}

	/**
	 * Retrieves the plan level from AC.
	 *
	 * @return array|null The valid feature codes (possibly empty), or null when AC did not answer.
	 */
	protected function get_ecom_features_from_ac() {
		$logger   = new Logger();
		$settings = get_option( ACTIVECAMPAIGN_FOR_WOOCOMMERCE_DB_SETTINGS_NAME );
		$api_uri  = isset( $settings['api_url'] ) ? $settings['api_url'] : null;
		$api_key  = isset( $settings['api_key'] ) ? $settings['api_key'] : null;

		$client = new Api_Client( $api_uri, $api_key, $logger );
		// No 10 second sleep and second attempt on a timeout: this call runs while a page renders.
		$client->set_max_retries( 0 );

		try {
			$account_features = ( new Ac_Features_Repository( $client ) )->find_all_current();
		} catch ( Throwable $t ) {
			$logger->warning(
				'There was an error retrieving the ActiveCampaign entitlements.',
				array(
					'message' => $t->getMessage(),
					'ac_code' => 'FC_166',
				)
			);

			return null;
		}

		// Without an entitlements list in the response (timeout, 5xx page, error body) AC did not answer.
		if ( ! is_array( $account_features->get_entitlements() ) ) {
			return null;
		}

		$feature_list = $account_features->get_valid_entitlements();

		return is_array( $feature_list ) ? $feature_list : array();
	}
}
