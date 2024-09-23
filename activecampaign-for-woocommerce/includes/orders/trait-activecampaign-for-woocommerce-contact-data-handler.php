<?php

/**
 * Various order utilities for the Activecampaign_For_Woocommerce plugin.
 *
 * @link       https://www.activecampaign.com/
 * @since      1.5.0
 *
 * @package    Activecampaign_For_Woocommerce
 * @subpackage Activecampaign_For_Woocommerce/includes
 */

use Activecampaign_For_Woocommerce_Logger as Logger;
use Activecampaign_For_Woocommerce_AC_Contact as AC_Contact;
use Activecampaign_For_Woocommerce_AC_Contact_Repository as Contact_Repository;
use Activecampaign_For_Woocommerce_Api_Client as Api_Client;

/**
 * The Order Utilities Class.
 *
 * @since      1.5.0
 * @package    Activecampaign_For_Woocommerce
 * @subpackage Activecampaign_For_Woocommerce/includes/orders
 * @author     acteamintegrations <team-integrations@activecampaign.com>
 */
trait Activecampaign_For_Woocommerce_Contact_Data_Handler {
	use Activecampaign_For_Woocommerce_Data_Validation;

	/**
	 * Syncs the contact to ActiveCampaign
	 *
	 * @param AC_Contact $ecom_contact The AC contact.
	 */
	private function sync_contact_to_ac( $ecom_contact ) {
		$logger = new Logger();
		// Sync the contact
		try {
			$settings = get_option( ACTIVECAMPAIGN_FOR_WOOCOMMERCE_DB_SETTINGS_NAME );

			$api_uri = isset( $settings['api_url'] ) ? $settings['api_url'] : null;
			$api_key = isset( $settings['api_key'] ) ? $settings['api_key'] : null;

			$contact_repository = new Contact_Repository( new Api_Client( $api_uri, $api_key, $logger ) );

			// Contact is allowed to fail it not found in AC
			$ac_contact   = $this->get_ac_contact_by_email( $contact_repository, $ecom_contact->get_email() );
			$ecom_contact = $this->compare_data( $ecom_contact, $ac_contact );

			if (
				self::validate_object( $ac_contact, 'get_id' ) &&
				! empty( $ac_contact->get_id() )
			) {
				$ecom_contact->set_id( $ac_contact->get_id() );
				$ac_contact = $contact_repository->update( $ecom_contact );
			} else {
				$ac_contact = $contact_repository->create( $ecom_contact );
			}

			return $ac_contact;
		} catch ( Throwable $t ) {
			$logger->warning(
				'Contact data handler: Could not create contact in AC.',
				[
					'email'   => $ecom_contact->get_email(),
					'message' => $t->getMessage(),
				]
			);
		}
	}

	/**
	 * @param Contact_Repository $contact_repository The contact repo.
	 * @param string             $email The email address.
	 *
	 * @return AC_Contact The contact info stored in AC.
	 */
	private function get_ac_contact_by_email( $contact_repository, $email ) {
		return $contact_repository->find_by_email( $email );
	}

	/**
	 * @param AC_Contact $ecom_contact The WC prepared contact.
	 * @param AC_Contact $ac_contact The returned contact as stored in AC.
	 *
	 * @return AC_Contact
	 */
	private function compare_data( $ecom_contact, $ac_contact ) {
		if ( isset( $ac_contact ) ) {
			$ecom_contact->set_first_name( $this->get_new_value( $ecom_contact->get_first_name(), $ac_contact->get_first_name() ) );
			$ecom_contact->set_last_name( $this->get_new_value( $ecom_contact->get_last_name(), $ac_contact->get_last_name() ) );
			$ecom_contact->set_email( $this->get_new_value( $ecom_contact->get_email(), $ac_contact->get_email() ) );
			$ecom_contact->set_phone( $this->get_new_value( $ecom_contact->get_phone(), $ac_contact->get_phone() ) );
		}

		return $ecom_contact;
	}

	/**
	 * @param mixed $local The value WC has.
	 * @param mixed $remote The value AC has.
	 *
	 * @return mixed The resulting best new value
	 */
	private function get_new_value( $local, $remote ) {
		if ( ! empty( $local ) && $local !== $remote ) {
			return $local;
		} else {
			return $remote;
		}
	}
}
