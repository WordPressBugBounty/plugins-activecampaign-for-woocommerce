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
use Activecampaign_For_Woocommerce_Account_Status_Manager as Account_Status_Manager;
use Activecampaign_For_Woocommerce_Api_Client as Api_Client;
use Activecampaign_For_Woocommerce_AC_Contact_Repository as Contact_Repository;
use Activecampaign_For_Woocommerce_Synced_Status_Interface as Synced_Status;
use Activecampaign_For_Woocommerce_Logger as Logger;
use Automattic\WooCommerce\Internal\DataStores\Orders\CustomOrdersTableController;

class Activecampaign_For_Woocommerce_Admin_WC_Order_Page implements Synced_Status {

	/**
	 * Set for 15 minutes after an AC lookup on the order or subscription page failed.
	 */
	public const AC_UNAVAILABLE_TRANSIENT = 'activecampaign_for_woocommerce_order_page_ac_unavailable';

	/**
	 * Cached in place of a contact that does not exist in AC.
	 */
	private const CONTACT_NOT_FOUND = 'not_found';
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
				self::meta_box_title(),
				'Activecampaign_For_Woocommerce_Admin_WC_Order_Page::order_meta_box_display',
				$screen,
				'normal',
				'default',
				self::meta_box_args()
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

		$api_client = self::api_client_or_placeholder();

		if ( ! $api_client ) {
			return;
		}

		$contact_repository                  = new Contact_Repository( $api_client );
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
					$ac_order = self::find_synced_order( $wc_order_id, $api_client );

					if ( false === $ac_order ) {
						self::render_ac_unavailable();
						return;
					}

					$order_ac_id = $ac_order->get_id();
				}

				if (
					! isset( $ac_order ) &&
					isset( $table_data->synced_to_ac ) &&
					in_array( $table_data->synced_to_ac, array( self::STATUS_UNSYNCED, self::STATUS_ABANDONED_CART_UNSYNCED, self::STATUS_ABANDONED_CART_RECOVERED ) )
				) {
					$ac_order = self::find_synced_order( $wc_order_id, $api_client );

					if ( false === $ac_order ) {
						self::render_ac_unavailable();
						return;
					}

					$order_ac_id = $ac_order->get_id();
				}

				if ( ! empty( $order_ac_id ) ) {
					if ( ! isset( $table_data->id ) ) {
						// No row for this order (placed before the plugin was set up, or removed by the
						// cleanup), but AC has it: store it as synced.
						$table_data = (object) array(
							'synced_to_ac'          => self::STATUS_SYNCED,
							'ac_externalcheckoutid' => null,
						);
					}

					$table_data->ac_order_id    = $order_ac_id;
					$table_data->ac_customer_id = $ac_order->get_customerid();
					self::save_table_data( $wc_order, $table_data );

					$table_data = self::get_table_data( $wc_order_id );
				}

				$ac_contact = self::find_contact( $contact_repository, $api_client, $wc_order->get_billing_email(), 300 );

				if ( false === $ac_contact ) {
					self::render_ac_unavailable();
					return;
				}

				if ( isset( $table_data->id ) && ( is_null( $table_data->customer_email ) || ! isset( $table_data->ac_order_id ) ) ) {
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

				if ( ! isset( $table_data->id ) ) {
					// Neither the plugin nor AC knows this order: show it without creating a row, which
					// would queue an old order for sync (and its automations) just by opening it.
					$table_data = self::table_data_from_order( $wc_order );
				}

				if ( isset( $table_data ) ) {
					$activecampaign_for_woocommerce_data                        = ( new Activecampaign_For_Woocommerce_Admin_WC_Order_Page() )->get_order_page_data( $table_data, $ac_contact );
					$activecampaign_for_woocommerce_data['meta_abandoned_date'] = $wc_order->get_meta( 'activecampaign_for_woocommerce_abandoned_date', $wc_order_id );
				}

				require plugin_dir_path( __FILE__ ) . 'partials/activecampaign-for-woocommerce-order-meta.php';
			}
		} catch ( Throwable $t ) {
			$logger->warning( 'There was an issue retrieving or loading the order data for this page.', array($t->getMessage(), $post_or_order_object, 'trace' => $t->getTrace() ) );
		}
	}

	/**
	 * Looks the order up in AC.
	 *
	 * @param int        $wc_order_id The WC order id.
	 * @param Api_Client $api_client The client from api_client_or_placeholder().
	 *
	 * @return object|false The AC order model (empty when not found), false when AC could not be reached.
	 */
	public static function find_synced_order( $wc_order_id, $api_client ) {
		$ac_order = ( new Activecampaign_For_Woocommerce_Ecom_Order_Repository( $api_client ) )->find_by_externalid( $wc_order_id );

		if ( $api_client->last_call_failed() ) {
			self::mark_ac_unavailable( 'ecomOrders' );
			return false;
		}

		return $ac_order;
	}

	/**
	 * Returns the client for the lookups on the order and subscription pages, or prints the
	 * placeholder and returns null when the box should not call AC.
	 *
	 * The page waits for these calls, so the client has a short timeout and no retry.
	 *
	 * @return Api_Client|null
	 */
	public static function api_client_or_placeholder() {
		$settings = get_option( ACTIVECAMPAIGN_FOR_WOOCOMMERCE_DB_SETTINGS_NAME );

		if ( empty( $settings['api_url'] ) || empty( $settings['api_key'] ) ) {
			echo '<p>' . esc_html__( 'ActiveCampaign is not connected.', ACTIVECAMPAIGN_FOR_WOOCOMMERCE_LOCALIZATION_DOMAIN ) . '</p>';
			return null;
		}

		if ( self::is_ac_unavailable() ) {
			self::render_ac_unavailable();
			return null;
		}

		$client = new Api_Client( $settings['api_url'], $settings['api_key'], new Logger() );
		$client->set_max_retries( 0 );
		$client->set_timeout( 10 );

		return $client;
	}

	/**
	 * Whether the ActiveCampaign Data box should be skipped: a lookup failed in the last
	 * 15 minutes, or the integration is paused (account maintenance).
	 *
	 * @return bool
	 */
	public static function is_ac_unavailable() {
		return false !== get_transient( self::AC_UNAVAILABLE_TRANSIENT ) || Account_Status_Manager::is_blocked();
	}

	/**
	 * Skips the AC lookups on the order and subscription pages for 15 minutes, so the pages
	 * don't wait for AC on every load while it is unreachable (DEFECT-43483).
	 *
	 * @param string $endpoint The endpoint that failed.
	 */
	public static function mark_ac_unavailable( $endpoint ) {
		set_transient( self::AC_UNAVAILABLE_TRANSIENT, time(), 15 * MINUTE_IN_SECONDS );

		( new Logger() )->warning(
			'ActiveCampaign could not be reached from the order page. The ActiveCampaign Data box is skipped for 15 minutes.',
			array(
				'endpoint' => $endpoint,
				'ac_code'  => 'WCOP_247',
			)
		);
	}

	/**
	 * Finds the AC contact for an email, cached per email. Contacts that don't exist are cached too.
	 *
	 * @param Contact_Repository $contact_repository The contact repository using $api_client.
	 * @param Api_Client         $api_client The client from api_client_or_placeholder().
	 * @param string             $email The billing email.
	 * @param int                $ttl Cache lifetime in seconds.
	 *
	 * @return object|null|false The contact, null when there is none, false when AC could not be reached.
	 */
	public static function find_contact( $contact_repository, $api_client, $email, $ttl ) {
		if ( empty( $email ) ) {
			return null;
		}

		$cache_key = 'activecampaign_for_woocommerce_contact' . $email;
		$contact   = get_transient( $cache_key );

		if ( empty( $contact ) ) {
			$contact = $contact_repository->find_by_email( $email );

			if ( $api_client->last_call_failed() ) {
				self::mark_ac_unavailable( 'contacts' );
				return false;
			}

			// A missing contact may be created any time (forms, imports), so remember it only briefly.
			if ( $contact ) {
				set_transient( $cache_key, $contact, $ttl );
			} else {
				set_transient( $cache_key, self::CONTACT_NOT_FOUND, min( $ttl, 5 * MINUTE_IN_SECONDS ) );
			}
		}

		return self::CONTACT_NOT_FOUND === $contact ? null : $contact;
	}

	/**
	 * The meta box title, with a badge when AC is unavailable.
	 *
	 * @return string
	 */
	public static function meta_box_title() {
		$title = esc_html__( 'ActiveCampaign Data', ACTIVECAMPAIGN_FOR_WOOCOMMERCE_LOCALIZATION_DOMAIN );

		if ( self::is_ac_unavailable() ) {
			$title .= ' ' . self::unavailable_badge();
		}

		return $title;
	}

	/**
	 * The meta box args: a plain title for Screen Options and the screen reader toggle text.
	 *
	 * @return array
	 */
	public static function meta_box_args() {
		return array( '__widget_basename' => __( 'ActiveCampaign Data', ACTIVECAMPAIGN_FOR_WOOCOMMERCE_LOCALIZATION_DOMAIN ) );
	}

	/**
	 * Prints the placeholder shown instead of the ActiveCampaign Data box content.
	 */
	public static function render_ac_unavailable() {
		$message = Account_Status_Manager::is_blocked()
			? __( 'The ActiveCampaign integration is paused (the account may be in maintenance). Data will load again when the pause ends.', ACTIVECAMPAIGN_FOR_WOOCOMMERCE_LOCALIZATION_DOMAIN )
			: __( 'ActiveCampaign could not be reached. Data will load again in a few minutes. If this persists, contact support.', ACTIVECAMPAIGN_FOR_WOOCOMMERCE_LOCALIZATION_DOMAIN );

		// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- built from escaped parts.
		echo '<p>' . self::unavailable_badge() . '</p><p>' . esc_html( $message ) . '</p>';
	}

	/**
	 * The red "Temporarily unavailable" badge.
	 *
	 * @return string
	 */
	private static function unavailable_badge() {
		return '<span class="activecampaign-for-woocommerce-unavailable-badge" style="display:inline-block;margin-left:8px;padding:0 8px;border-radius:10px;background:#d63638;color:#fff;font-size:12px;font-weight:600;line-height:20px;vertical-align:middle;">'
			. esc_html__( 'Temporarily unavailable', ACTIVECAMPAIGN_FOR_WOOCOMMERCE_LOCALIZATION_DOMAIN )
			. '</span>';
	}

	/**
	 * The fields of a table row, read from the order, for orders without a row. Not saved.
	 *
	 * @param WC_Order $wc_order The order.
	 *
	 * @return object
	 */
	private static function table_data_from_order( $wc_order ) {
		return (object) array(
			'id'                    => null,
			'synced_to_ac'          => self::STATUS_UNSYNCED,
			'ac_order_id'           => null,
			'wc_order_id'           => $wc_order->get_id(),
			'ac_customer_id'        => null,
			'abandoned_date'        => null,
			'customer_email'        => $wc_order->get_billing_email(),
			'ac_externalcheckoutid' => null,
			'customer_first_name'   => $wc_order->get_billing_first_name(),
			'customer_last_name'    => $wc_order->get_billing_last_name(),
		);
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
				} elseif ( false === $wpdb->insert( $wpdb->prefix . ACTIVECAMPAIGN_FOR_WOOCOMMERCE_TABLE_NAME, $store_data ) ) {
					// wpdb doesn't throw: log it, e.g. a duplicate ac_externalcheckoutid from an abandoned cart row.
					$logger->warning(
						'Could not add this order to the ActiveCampaign table.',
						array(
							'wc_order_id'     => $store_data['wc_order_id'],
							'wpdb_last_error' => $wpdb->last_error,
							'ac_code'         => 'WCOP_551',
						)
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
