<?php
/**
 * The admin only order page functionality of the plugin.
 *
 * @link       https://www.activecampaign.com/
 * @since      1.0.0
 *
 * @package    Activecampaign_For_Woocommerce
 * @subpackage Activecampaign_For_Woocommerce/admin
 */
use Activecampaign_For_Woocommerce_Api_Client as Api_Client;
use Activecampaign_For_Woocommerce_AC_Contact_Repository as Contact_Repository;
use Activecampaign_For_Woocommerce_Synced_Status_Interface as Synced_Status;
use Activecampaign_For_Woocommerce_Logger as Logger;
use Automattic\WooCommerce\Internal\DataStores\Orders\CustomOrdersTableController;

class Activecampaign_For_Woocommerce_Admin_WC_Order_Page implements Synced_Status {
	use Activecampaign_For_Woocommerce_Synced_Status_Handler;
	use Activecampaign_For_Woocommerce_Order_Data_Gathering;
	use Activecampaign_For_Woocommerce_Admin_Utilities;

	/**
	 * Populates the order page with our info.
	 *
	 * @param WC_Order $order The WC order.
	 */
	public function order_edit_meta_box( $order ) {
		if ( ! Activecampaign_For_Woocommerce_Utilities::valid_permission( 'sync_data' ) ) {
			// Current user doesn't have permission for this so just return.
			return;
		}

		try {
			$screen = wc_get_container()->get( CustomOrdersTableController::class )->custom_orders_table_usage_is_enabled()
			? wc_get_page_screen_id( 'shop-order' )
			: 'shop_order';

			add_meta_box(
				'woocommerce-order-activecampaign',
				__( 'ActiveCampaign Data', 'activecampaign' ),
				'Activecampaign_For_Woocommerce_Admin_WC_Order_Page::order_meta_box_display',
				$screen,
				'normal',
				'default'
			);
		} catch ( Throwable $t ) {
			$logger = new Logger();
			$logger->debug(
				'There was an issue retrieving subscription details for the subscription page.',
				array(
					'order_id' => method_exists( $order, 'get_id' ) ? $order->get_id() : null,
				)
			);
		}
	}

	/**
	 * The display content for the meta box.
	 *
	 * @param object $post_or_order_object The post or order depending on HPOS or old version.
	 */
	public static function order_meta_box_display( $post_or_order_object ) {
		global $wpdb;
		$logger = new Logger();

		$settings = get_option( ACTIVECAMPAIGN_FOR_WOOCOMMERCE_DB_SETTINGS_NAME );

		$api_uri = isset( $settings['api_url'] ) ? $settings['api_url'] : null;
		$api_key = isset( $settings['api_key'] ) ? $settings['api_key'] : null;

		$contact_repository                  = new Contact_Repository( new Api_Client( $api_uri, $api_key, $logger ) );
		$activecampaign_for_woocommerce_data = array();

		try {
			$wc_order = ( $post_or_order_object instanceof WP_Post ) ? wc_get_order( $post_or_order_object->ID ) : $post_or_order_object;

			if ( isset( $wc_order->ID ) ) {
				$wc_order_id = $wc_order->ID;
			} else {
				$wc_order_id = $wc_order->get_id();
			}

			$wc_order = wc_get_order( $wc_order_id );

			if ( self::validate_object( $wc_order, 'get_id' ) ) {
				$table_data = self::get_table_data( $wc_order_id );

				if ( ! isset( $table_data, $table_data->id, $table_data->ac_order_id ) ) {
					$ac_order    = ( new Activecampaign_For_Woocommerce_Admin_WC_Order_Page() )->check_for_synced_order( $wc_order_id );
					$order_ac_id = $ac_order->get_id();
				}

				if (
					! isset( $ac_order ) &&
					isset( $table_data->synced_to_ac ) &&
					in_array( $table_data->synced_to_ac, array( self::STATUS_UNSYNCED, self::STATUS_ABANDONED_CART_UNSYNCED, self::STATUS_ABANDONED_CART_RECOVERED ) )
				) {
					$ac_order    = ( new Activecampaign_For_Woocommerce_Admin_WC_Order_Page() )->check_for_synced_order( $wc_order_id );
					$order_ac_id = $ac_order->get_id();
				}

				if (
					isset( $ac_order ) &&
					self::validate_object( $ac_order, 'get_id' )
				) {
					$table_data->ac_order_id    = $ac_order->get_id();
					$table_data->ac_customer_id = $ac_order->get_customerid();
					self::save_table_data( $wc_order, $table_data );
				}

				if ( ! isset( $table_data, $table_data->id ) ) {
					if ( isset( $order_ac_id ) && ! empty( $order_ac_id ) ) {
						self::save_table_data( $wc_order, null, 1 );
					}

					$table_data = self::get_table_data( $wc_order_id );
				}

				$ac_contact = get_transient( 'activecampaign_for_woocommerce_contact' . $wc_order->get_billing_email() );

				if ( ! isset( $ac_contact ) || false === $ac_contact || '' === $ac_contact ) {
					$ac_contact = $contact_repository->find_by_email( $wc_order->get_billing_email() );
					set_transient( 'activecampaign_for_woocommerce_contact' . $wc_order->get_billing_email(), $ac_contact, 300 );
				}

				if ( isset( $table_data ) && is_null( $table_data->customer_email ) || ! isset( $table_data->ac_order_id ) ) {
					self::save_table_data( $wc_order, $table_data );

					$table_data = self::get_table_data( $wc_order_id );
				}

				if (
					! empty( $order_ac_id ) &&
					isset( $table_data->synced_to_ac ) &&
					in_array( $table_data->synced_to_ac, array( 0, '0' ) )
				) {
					self::save_table_data( $wc_order, $table_data );
				}

				if ( isset( $table_data ) ) {
					$activecampaign_for_woocommerce_data                        = ( new Activecampaign_For_Woocommerce_Admin_WC_Order_Page() )->get_order_page_data( $table_data, $ac_contact );
					$activecampaign_for_woocommerce_data['meta_abandoned_date'] = $wc_order->get_meta( 'activecampaign_for_woocommerce_abandoned_date', $wc_order_id );
				}

				require_once plugin_dir_path( __FILE__ ) . 'partials/activecampaign-for-woocommerce-order-meta.php';
			}
		} catch ( Throwable $t ) {
			$logger->warning( 'There was an issue retrieving or loading the order data for this page.', array($t->getMessage(), $post_or_order_object, 'trace' => $t->getTrace() ) );
		}
	}

	public function check_for_synced_order( $wc_order_id ) {
		$settings = get_option( ACTIVECAMPAIGN_FOR_WOOCOMMERCE_DB_SETTINGS_NAME );

		$api_uri          = isset( $settings['api_url'] ) ? $settings['api_url'] : null;
		$api_key          = isset( $settings['api_key'] ) ? $settings['api_key'] : null;
		$logger           = new Logger();
		$order_repository = new Activecampaign_For_Woocommerce_Ecom_Order_Repository( new Api_Client( $api_uri, $api_key, $logger ) );

		$order_ac = $order_repository->find_by_externalid( $wc_order_id );
		return $order_ac;
	}

	private static function get_table_data( $wc_order_id ) {
		global $wpdb;

		return $wpdb->get_row(
		// phpcs:disable
			$wpdb->prepare( 'SELECT id, synced_to_ac, ac_order_id, wc_order_id, ac_customer_id, abandoned_date, customer_email, ac_externalcheckoutid, customer_first_name, customer_last_name
					FROM
					`' . $wpdb->prefix . ACTIVECAMPAIGN_FOR_WOOCOMMERCE_TABLE_NAME . '`
					WHERE wc_order_id = %d
					LIMIT 1',
				$wc_order_id
			)
		// phpcs:enable
		);
	}

	/**
	 * Gets the order page data.
	 *
	 * @param object $table_data The table data from our AC table.
	 * @param object $ac_contact The AC contact.
	 *
	 * @return array
	 */
	private function get_order_page_data( $table_data, $ac_contact ) {
		$activecampaign_for_woocommerce_data['hosted_contact_url'] = '';
		$activecampaign_for_woocommerce_data['contact_id']         = '';
		$activecampaign_for_woocommerce_settings                   = get_option( ACTIVECAMPAIGN_FOR_WOOCOMMERCE_DB_SETTINGS_NAME );

		if ( isset( $activecampaign_for_woocommerce_settings['ac_debug'] ) ) {
			$activecampaign_for_woocommerce_data['debug'] = $activecampaign_for_woocommerce_settings['ac_debug'];
		} else {
			$activecampaign_for_woocommerce_data['debug'] = 0;
		}

		if ( isset( $ac_contact ) && false !== $ac_contact && method_exists( $ac_contact, 'get_id' ) ) {
			$activecampaign_for_woocommerce_data['contact_id']    = $ac_contact->get_id();
			$activecampaign_for_woocommerce_data['contact_array'] = $ac_contact->serialize_to_array();
			$ac_hosted_api                                        = explode( '.', wp_parse_url( $activecampaign_for_woocommerce_settings['api_url'], PHP_URL_HOST ) );

			if ( isset( $ac_hosted_api[0] ) ) {
				$activecampaign_for_woocommerce_data['hosted_contact_url'] = 'https://' . $ac_hosted_api[0] . '.activehosted.com/app/contacts/' . $ac_contact->get_id();
			}
		}

		$activecampaign_for_woocommerce_data['wc_order_id']           = $table_data->wc_order_id;
		$activecampaign_for_woocommerce_data['ac_customer_id']        = $table_data->ac_customer_id;
		$activecampaign_for_woocommerce_data['abandoned_date']        = $table_data->abandoned_date;
		$activecampaign_for_woocommerce_data['ac_order_id']           = $table_data->ac_order_id;
		$activecampaign_for_woocommerce_data['customer_first_name']   = $table_data->customer_first_name;
		$activecampaign_for_woocommerce_data['customer_last_name']    = $table_data->customer_last_name;
		$activecampaign_for_woocommerce_data['customer_email']        = $table_data->customer_email;
		$activecampaign_for_woocommerce_data['ac_externalcheckoutid'] = $table_data->ac_externalcheckoutid;
		$activecampaign_for_woocommerce_data['synced_to_ac']          = $table_data->synced_to_ac;
		$activecampaign_for_woocommerce_data['synced_to_ac_readable'] = $this->get_readable_sync_status( $table_data->synced_to_ac );

		wp_register_script(
			ACTIVECAMPAIGN_FOR_WOOCOMMERCE_PLUGIN_NAME_KEBAB . 'wc-order-page',
			plugin_dir_url( __FILE__ ) . 'scripts/activecampaign-for-woocommerce-wc-order-page.js',
			array( 'jquery' ),
			ACTIVECAMPAIGN_FOR_WOOCOMMERCE_VERSION,
			true
		);
		wp_enqueue_script( ACTIVECAMPAIGN_FOR_WOOCOMMERCE_PLUGIN_NAME_KEBAB . 'wc-order-page' );
		return $activecampaign_for_woocommerce_data;
	}

	/**
	 * Runs through a hook ac_ajax_sync_single_record.
	 *
	 * @return void
	 */
	public function ac_ajax_sync_single_record() {
		$nonce = self::get_request_data( 'activecampaign_for_woocommerce_settings_nonce_field' );
		$valid = wp_verify_nonce( $nonce, 'activecampaign_for_woocommerce_order_form' );

		if ( $valid ) {
			$order_id = self::get_request_data( 'wc_order_id' );
			$type     = self::get_request_data( 'sync_type' );

			if ( isset( $order_id ) && ! empty( $order_id ) ) {
				$data = array(
					'wc_order_id' => $order_id,
				);

				if ( 'new' === $type ) {
					$data['type'] = 'new';
					do_action( 'activecampaign_for_woocommerce_admin_sync_single_order_active', $data );
				} elseif ( 'historical' === $type ) {
					$data['type'] = 'historical';
					do_action( 'activecampaign_for_woocommerce_admin_sync_single_order_historical', $data );
				}

				wp_send_json_success( array( $order_id ) );
			}

			wp_send_json_error( $order_id );
		}
	}

	private static function save_table_data( $wc_order, $table_data = false, $status_override = null ) {
		$logger = new Logger();

		try {
			$data = $wc_order->get_data();
		} catch ( Throwable $t ) {
			$logger->warning(
				'Plugin encountered an error trying to save table data.',
				array(
					'message' => $t->getMessage(),
					'trace'   => $logger->clean_trace( $t->getTrace() ),
				)
			);
		}

		try {
			if ( isset( $data['id'] ) ) {
				if ( function_exists( 'wcs_order_contains_renewal' ) && wcs_order_contains_renewal( $wc_order ) && empty( $table_data ) ) {
					$externalcheckoutid = null;
				} elseif ( $table_data->ac_externalcheckoutid ) {
					$externalcheckoutid = $table_data->ac_externalcheckoutid;
				} else {
					$externalcheckoutid = get_metadata_raw( 'post', $data['id'], 'activecampaign_for_woocommerce_external_checkout_id', true );
				}
			}
		} catch ( Throwable $t ) {
				$logger->warning(
					'There was an issue retrieving externalcheckoutid',
					array(
						'message' => $t->getMessage(),
						'trace'   => $logger->clean_trace( $t->getTrace() ),
					)
				);
		}

		try {
			if ( isset( $data['id'] ) ) {
				$dt = new DateTime( $data['date_created'], new DateTimeZone( 'UTC' ) );

				$store_data = array(
					'customer_id'                    => $data['customer_id'],
					'customer_email'                 => $data['billing']['email'],
					'customer_first_name'            => $data['billing']['first_name'],
					'customer_last_name'             => $data['billing']['last_name'],
					'wc_order_id'                    => $data['id'],
					'order_date'                     => $dt->format( 'Y-m-d H:i:s' ),
					'activecampaignfwc_order_external_uuid' => null,
					'ac_externalcheckoutid'          => isset( $externalcheckoutid ) ? $externalcheckoutid : null,
					'customer_ref_json'              => null,
					'user_ref_json'                  => null,
					'cart_ref_json'                  => null,
					'cart_totals_ref_json'           => null,
					'removed_cart_contents_ref_json' => null,
				);

				if ( isset( $table_data->synced_to_ac ) ) {
					$store_data['synced_to_ac'] = $table_data->synced_to_ac;
				}

				if ( isset( $table_data->ac_order_id ) ) {
					$store_data['ac_order_id'] = $table_data->ac_order_id;
				}

				if ( isset( $table_data->ac_customer_id ) ) {
					$store_data['ac_customer_id'] = $table_data->ac_customer_id;
				}

				if ( isset( $status_override ) && ! empty( $status_override ) ) {
					$store_data['synced_to_ac'] = $status_override;
				}

				global $wpdb;
				if ( isset( $table_data->id ) && ! empty( $table_data->id ) ) {
					$wpdb->update(
						$wpdb->prefix . ACTIVECAMPAIGN_FOR_WOOCOMMERCE_TABLE_NAME,
						$store_data,
						array(
							'id' => $table_data->id,
						)
					);
				} else {
					$wpdb->insert(
						$wpdb->prefix . ACTIVECAMPAIGN_FOR_WOOCOMMERCE_TABLE_NAME,
						$store_data
					);
				}
			}
		} catch ( Throwable $t ) {
			$logger->error(
				'There was an issue saving order data to the ActiveCampaign table.',
				array(
					'message'          => $t->getMessage(),
					'suggested_action' => 'Verify that the ' . ACTIVECAMPAIGN_FOR_WOOCOMMERCE_TABLE_NAME . ' exists and is both writable and readable.',
					'trace'            => $logger->clean_trace( $t->getTrace() ),
					'ac_code'          => 'WCOP_243',
				)
			);
		}
	}
}
