<?php

/**
 * The file for the EcomOrder AC Status Model
 *
 * @link       https://www.activecampaign.com/
 * @since      1.0.0
 *
 * @package    Activecampaign_For_Woocommerce
 * @subpackage Activecampaign_For_Woocommerce/includes/models
 */

/**
 * The model class for the Ecom Order Status
 *
 * @since      1.0.0
 * @package    Activecampaign_For_Woocommerce
 * @subpackage Activecampaign_For_Woocommerce/includes/models
 * @author     acteamintegrations <team-integrations@activecampaign.com>
 */
class Activecampaign_For_Woocommerce_Cofe_Ecom_Order_Status {
	use Activecampaign_For_Woocommerce_Global_Utilities;

	/**
	 * The mappings for the Api_Serializable trait.
	 *
	 * @var array
	 */
	public $status_mappings = [
		'pending'                 => 'PENDING_PAYMENT',
		'wc-pending'              => 'PENDING_PAYMENT',
		'on-hold'                 => 'WAITING',
		'wc-on-hold'              => 'WAITING',
		'processing'              => 'COMPLETED',
		'wc-processing'           => 'COMPLETED',
		'completed'               => 'COMPLETED',
		'wc-completed'            => 'COMPLETED',
		'failed'                  => 'FAILED',
		'wc-failed'               => 'FAILED',
		'refunded'                => 'REFUNDED',
		'wc-refunded'             => 'REFUNDED',
		'pending-cancel'          => 'CANCELLED',
		'wc-pending-cancel'       => 'CANCELLED',
		'cancelled'               => 'CANCELLED',
		'wc-cancelled'            => 'CANCELLED',
		'wc-active'               => 'ACTIVE',
		'active'                  => 'ACTIVE',
		'wc-expired'              => 'EXPIRED',
		'expired'                 => 'EXPIRED',
		'paused'                  => 'WAITING',
		'wc-paused'               => 'WAITING',
		'requires_payment_method' => 'PENDING_PAYMENT',
		'requires_confirmation'   => 'PENDING_PAYMENT',
		'requires_action'         => 'PENDING_PAYMENT',
		'requires_capture'        => 'PENDING_PAYMENT',
	];

	/**
	 * @var string $status
	 */
	private $status;

	/**
	 * Returns the status.
	 *
	 * @return mixed
	 */
	public function get_status() {
		return $this->status;
	}

	/**
	 * Sets the status.
	 *
	 * @param string $status The status.
	 */
	private function set_status( $status ) {
		$this->status = $status;
	}

	public function get_all_ac_statuses() {
		return $this->status_mappings;
	}

	/**
	 * Sets the status from the raw WC status.
	 *
	 * @param string $status The raw WC status.
	 */
	public function set_ac_status_from_wc_status( $status ) {
		// map the wc status to ac status or return an error
		$this->check_for_custom_mappings();

		$mappings = $this->status_mappings;

		// e.g., "order_number" => "orderNumber"
		foreach ( $mappings as $local_name => $remote_name ) {
			if ( $status === $local_name ) {
				// e.g. $this->set_order_number($array['orderNumber']);
				$this->set_status( $remote_name );
				return;
			}
		}
	}

	/**
	 * Inject mappings from custom entry in admin to our standard status mappings.
	 */
	private function check_for_custom_mappings() {
		$activecampaign_for_woocommerce_settings = $this->get_ac_settings();
		if ( isset( $activecampaign_for_woocommerce_settings['status_mapping'] ) ) {
			$saved_mappings = $activecampaign_for_woocommerce_settings['status_mapping'];
			foreach ( $saved_mappings as $wc_key => $ac_key ) {
				$this->status_mappings[ $wc_key ] = $ac_key;

				// Add the clean version as well
				if ( 'wc-' === substr( $wc_key, 0, 3 ) ) {
					$this->status_mappings[ substr( $wc_key, 3 ) ] = $ac_key;
				}
			}
		}
	}
}
