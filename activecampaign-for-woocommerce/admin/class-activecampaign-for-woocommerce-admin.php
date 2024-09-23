<?php

/**
 * The admin-specific functionality of the plugin.
 *
 * @link       https://www.activecampaign.com/
 * @since      1.0.0
 *
 * @package    Activecampaign_For_Woocommerce
 * @subpackage Activecampaign_For_Woocommerce/admin
 */

use Activecampaign_For_Woocommerce_Admin_Settings_Updated_Event as Admin_Settings_Updated;
use Activecampaign_For_Woocommerce_Admin_Settings_Validator as Validator;
use Activecampaign_For_Woocommerce_Logger as Logger;
use Activecampaign_For_Woocommerce_Connection as Connection;
use Activecampaign_For_Woocommerce_Connection_Repository as Connection_Repository;
use Activecampaign_For_Woocommerce_AC_Contact_Repository as Contact_Repository;
use Activecampaign_For_Woocommerce_Synced_Status_Interface as Synced_Status;

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
class Activecampaign_For_Woocommerce_Admin implements Synced_Status {
	use Activecampaign_For_Woocommerce_Admin_Abandoned_Cart,
		Activecampaign_For_Woocommerce_Admin_Historical_Sync,
		Activecampaign_For_Woocommerce_Admin_Status,
		Activecampaign_For_Woocommerce_Admin_Product_Sync,
		Activecampaign_For_Woocommerce_Admin_Connections,
		Activecampaign_For_Woocommerce_Admin_Utilities,
		Activecampaign_For_Woocommerce_Synced_Status_Handler,
		Activecampaign_For_Woocommerce_Features_Checker,
		Activecampaign_For_Woocommerce_Global_Utilities;

	/**
	 * The ID of this plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      string $plugin_name The ID of this plugin.
	 */
	private $plugin_name;

	/**
	 * The version of this plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      string $version The current version of this plugin.
	 */
	private $version;

	/**
	 * The response array that will be returned.
	 *
	 * @var array The array.
	 */
	private $response = array();

	/**
	 * The class that handles validating options changes.
	 *
	 * @var Validator The validator class.
	 */
	private $validator;

	/**
	 * The event class to be triggered after a successful options update.
	 *
	 * @var Activecampaign_For_Woocommerce_Admin_Settings_Updated_Event The event class.
	 */
	private $event;

	/**
	 * The class for connection repository.
	 *
	 * @var Activecampaign_For_Woocommerce_Connection_Repository The connection class.
	 */
	private $connection_repository;

	/**
	 * The class for contact repository.
	 *
	 * @var Activecampaign_For_Woocommerce_AC_Contact_Repository The contact class.
	 */
	private $contact_repository;

	/**
	 * Initialize the class and set its properties.
	 *
	 * @param     string                                               $plugin_name     The name of this plugin.
	 * @param     string                                               $version     The version of this plugin.
	 * @param     Validator                                            $validator     The validator for the admin options.
	 * @param     Admin_Settings_Updated                               $event     The admin settings updated event class.
	 * @param     Activecampaign_For_Woocommerce_Connection_Repository $connection_repository     The connection repository.
	 * @param     Activecampaign_For_Woocommerce_AC_Contact_Repository $contact_repository The contact repository.
	 *
	 * @since    1.0.0
	 */
	public function __construct( $plugin_name, $version, Validator $validator, Admin_Settings_Updated $event, Connection_Repository $connection_repository, Contact_Repository $contact_repository ) {
		$this->plugin_name           = $plugin_name;
		$this->version               = $version;
		$this->validator             = $validator;
		$this->event                 = $event;
		$this->connection_repository = $connection_repository;
		$this->contact_repository    = $contact_repository;
	}

	/**
	 * Register the JavaScript for the admin area.
	 * Loaded via action hook.
	 *
	 * @since    1.0.0
	 */
	public function enqueue_styles_scripts() {
		global $plugin_page;

		if ( isset( $plugin_page ) && ! empty( $plugin_page ) && preg_match( '/^activecampaign_for_woocommerce/i', $plugin_page ) >= 1 ) {
			wp_enqueue_style(
				$this->plugin_name,
				plugin_dir_url( __FILE__ ) . 'css/activecampaign-for-woocommerce-admin.css',
				array(),
				$this->version,
				'all'
			);
		}

		/* Register scripts but do not load them until they are fetched */
		wp_register_script(
			$this->plugin_name . 'settings-page',
			plugin_dir_url( __FILE__ ) . 'scripts/activecampaign-for-woocommerce-settings-page.js',
			array( 'jquery' ),
			$this->version,
			true
		);

		wp_register_script(
			$this->plugin_name . 'settings-page-connection',
			plugin_dir_url( __FILE__ ) . 'scripts/activecampaign-for-woocommerce-connection-settings.js',
			array( 'jquery' ),
			$this->version,
			true
		);

		wp_register_script(
			$this->plugin_name . 'settings-page-status-mapping',
			plugin_dir_url( __FILE__ ) . 'scripts/activecampaign-for-woocommerce-status-mapping.js',
			array( 'jquery' ),
			$this->version,
			true
		);

		wp_register_script(
			$this->plugin_name . 'status-page',
			plugin_dir_url( __FILE__ ) . 'scripts/activecampaign-for-woocommerce-status-page.js',
			array( 'jquery' ),
			$this->version,
			true
		);

		wp_register_script(
			$this->plugin_name . 'abandoned-cart',
			plugin_dir_url( __FILE__ ) . 'scripts/activecampaign-for-woocommerce-abandoned-cart.js',
			array( 'jquery' ),
			$this->version,
			true
		);

		wp_register_script(
			$this->plugin_name . 'historical-sync',
			plugin_dir_url( __FILE__ ) . 'scripts/activecampaign-for-woocommerce-historical-sync.js',
			array( 'jquery' ),
			$this->version,
			true
		);

		wp_register_script(
			$this->plugin_name . 'product-sync',
			plugin_dir_url( __FILE__ ) . 'scripts/activecampaign-for-woocommerce-product-sync.js',
			array( 'jquery' ),
			$this->version,
			true
		);
	}

	/**
	 * Register the page for the admin section, adds to the WooCommerce menu parent
	 *
	 * @since    1.0.0
	 */
	public function add_admin_page() {
		$ac_icon = 'data:image/svg+xml;base64,PD94bWwgdmVyc2lvbj0iMS4wIiBlbmNvZGluZz0iVVRGLTgiPz4KPHN2ZyB3aWR0aD0iMjBweCIgaGVpZ2h0PSIyMHB4IiB2aWV3Qm94PSIwIDAgMjAgMjAiIHZlcnNpb249IjEuMSIgeG1sbnM9Imh0dHA6Ly93d3cudzMub3JnLzIwMDAvc3ZnIiB4bWxuczp4bGluaz0iaHR0cDovL3d3dy53My5vcmcvMTk5OS94bGluayI+CiAgICA8dGl0bGU+R3JvdXA8L3RpdGxlPgogICAgPGcgaWQ9IlBhZ2UtMSIgc3Ryb2tlPSJub25lIiBzdHJva2Utd2lkdGg9IjEiIGZpbGw9Im5vbmUiIGZpbGwtcnVsZT0iZXZlbm9kZCI+CiAgICAgICAgPGcgaWQ9Ikdyb3VwIj4KICAgICAgICAgICAgPHJlY3QgaWQ9IlJlY3RhbmdsZSIgb3BhY2l0eT0iMCIgeD0iMCIgeT0iMCIgd2lkdGg9IjIwIiBoZWlnaHQ9IjIwIj48L3JlY3Q+CiAgICAgICAgICAgIDxwYXRoIGQ9Ik00LjIxNzMxNjUsMS4xMDg2NTE1IEM0LjY4ODE0LDEuMzk4MzkgMTUuMDEwMDUsOC42MDU2NSAxNS4yNjM2LDguODIyOTUgQzE1LjY5ODIsOS4xMTI3IDE1LjkxNTUsOS40NzQ4NSAxNS45MTU1LDkuODM3IEwxNS45MTU1LDEwLjA1NDM1IEMxNS45MTU1LDEwLjM0NDA1IDE1LjgwNjg1LDEwLjgxNDkgMTUuMjk5OCwxMS4xNzcwNSBDMTUuMDgyNSwxMS4zNTgxNSA0LjEwODY2OSwxOSA0LjEwODY2OSwxOSBMNC4xMDg2NjksMTcuMjk3OCBDNC4xMDg2NjksMTYuNzkwNzUgNC4xNDQ4Njg1LDE2LjUzNzIgNC43MjQzNDUsMTYuMTc1MDUgQzUuMTk1MTcsMTUuODg1MyAxMi42NTU5NSwxMC43MDYyNSAxMy42MzM4LDEwLjAxODEgQzEzLjE2Nzk1LDkuNjkwMyAxMS4yNTUzNSw4LjM1OTYgOS4zMjU0LDcuMDE2ODUgQzcuMjA0MzIsNS41NDExMiA1LjA2MjI3LDQuMDUwOCA0Ljc5Njc4NSwzLjg2MTE3IEw0LjcyNDM0NSwzLjgyNDk1IEM0LjY5Njg2NSwzLjgwMjk2NSA0LjY2OTgwNSwzLjc4MTYxIDQuNjQzMjU1LDMuNzYwNjU1IEM0LjMxOTg3NCwzLjUwNTQ0IDQuMDcyNDM4NSwzLjMxMDE2IDQuMDcyNDM4NSwyLjc3NDY1IEw0LjA3MjQzODUsMSBMNC4yMTczMTY1LDEuMTA4NjUxNSBaIE05LjY4NjEsMTAuNDg4OSBDOS4zOTY0LDEwLjcwNjI1IDkuMTA2NjUsMTAuODE0OSA4LjgxNjkyLDEwLjgxNDkgQzguNTYzNCwxMC44MTQ5IDguMzA5ODc1LDEwLjc0MjQ1IDguMDIwMTM1LDEwLjU2MTM1IEM3LjM2ODIyNSwxMC4xMjY3NSA0LjA3MjQ0OCw3Ljg0NTA1IDQuMDM2MjMwNTUsNy44MDg4NSBMNCw3Ljc3MjY1IEw0LDYuNjQ5OSBDNCw2LjM2MDE1IDQuMTQ0ODc4LDYuMTc5MDUgNC4zMjU5NjQ1LDYuMDcwNCBDNC41MDcwNSw1Ljk2MTc2IDQuNzk2OCw1Ljk5Nzk4IDUuMDE0MSw2LjE3OTA1IEM1LjUyMTE0NSw2LjUwNSAxMC4zMzgwNSw5LjgzNyAxMC4zNzQyNSw5Ljg3MzI1IEwxMC40ODI5LDkuOTQ1NjUgTDEwLjM3NDI1LDEwLjAxODEgQzEwLjM3NDI1LDEwLjAxODEgMTAuMDQ4MywxMC4yMzU0IDkuNjg2MSwxMC40ODg5IFoiIGlkPSJTaGFwZSIgZmlsbD0iIzAwNENGRiI+PC9wYXRoPgogICAgICAgIDwvZz4KICAgIDwvZz4KPC9zdmc+';

		add_menu_page(
			'ActiveCampaign for WooCommerce',
			'ActiveCampaign',
			'manage_options',
			ACTIVECAMPAIGN_FOR_WOOCOMMERCE_PLUGIN_NAME_SNAKE,
			array( $this, 'fetch_admin_page' ),
			$ac_icon,
			55
		);

		add_submenu_page(
			ACTIVECAMPAIGN_FOR_WOOCOMMERCE_PLUGIN_NAME_SNAKE,
			'ActiveCampaign for WooCommerce Settings',
			'Settings',
			'manage_options',
			ACTIVECAMPAIGN_FOR_WOOCOMMERCE_PLUGIN_NAME_SNAKE,
			array( $this, 'fetch_admin_page' )
		);

		add_submenu_page(
			ACTIVECAMPAIGN_FOR_WOOCOMMERCE_PLUGIN_NAME_SNAKE,
			'ActiveCampaign for WooCommerce Support',
			'Support',
			'manage_options',
			ACTIVECAMPAIGN_FOR_WOOCOMMERCE_PLUGIN_NAME_SNAKE . '_status',
			array( $this, 'fetch_status_page' )
		);
	}

	public function add_admin_entitlements() {
		if (
			isset(
				$this->get_connection_storage()['connection_id']
			) &&
			$this->is_configured() &&
			! empty( $this->get_connection_storage()['connection_id'] )
		) {
			if ( $this->verify_ac_features( 'abandon' ) ) {
				add_submenu_page(
					ACTIVECAMPAIGN_FOR_WOOCOMMERCE_PLUGIN_NAME_SNAKE,
					'ActiveCampaign for WooCommerce Abandoned Carts',
					'Abandoned Carts',
					'manage_options',
					ACTIVECAMPAIGN_FOR_WOOCOMMERCE_PLUGIN_NAME_SNAKE . '_abandoned_carts',
					array( $this, 'fetch_abandoned_cart_page' )
				);
			}
			if ( $this->verify_ac_features( 'historical' ) ) {
				add_submenu_page(
					ACTIVECAMPAIGN_FOR_WOOCOMMERCE_PLUGIN_NAME_SNAKE,
					'ActiveCampaign for WooCommerce Historical Sync',
					'Historical Sync',
					'manage_options',
					ACTIVECAMPAIGN_FOR_WOOCOMMERCE_PLUGIN_NAME_SNAKE . '_historical_sync',
					array( $this, 'fetch_historical_sync_page' )
				);
			}
			if ( $this->verify_ac_features( 'product' ) ) {
				add_submenu_page(
					ACTIVECAMPAIGN_FOR_WOOCOMMERCE_PLUGIN_NAME_SNAKE,
					'ActiveCampaign for WooCommerce Product Sync',
					'Product Sync',
					'manage_options',
					ACTIVECAMPAIGN_FOR_WOOCOMMERCE_PLUGIN_NAME_SNAKE . '_product_sync',
					array( $this, 'fetch_product_sync_page' )
				);
			}
		}

	}

	public function load_connection_block() {
		if ( $this->get_connection_storage() && $this->is_configured() ) {
			require_once plugin_dir_path( __FILE__ )
						 . 'partials/activecampaign-for-woocommerce-connections.php';
		}
	}

	public function load_status_mapping_block() {
		if ( $this->get_connection_storage() && $this->is_configured() ) {
			require_once plugin_dir_path( __FILE__ )
						 . 'partials/activecampaign-for-woocommerce-status-mapping.php';
		}
	}

	/**
	 * This function adds to our plugin listing on the plugin page a link to our settings page.
	 * Called via filter.
	 *
	 * @param     array $links     The existing links being passed in.
	 *
	 * @return array
	 */
	public function add_plugin_settings_link( $links ) {
		$html_raw = '<a href="%s" aria-label="%s">%s</a>';

		$html = sprintf(
			$html_raw,
			admin_url( 'admin.php?page=' . ACTIVECAMPAIGN_FOR_WOOCOMMERCE_PLUGIN_NAME_SNAKE ),
			esc_attr__(
				'View ActiveCampaign settings',
				ACTIVECAMPAIGN_FOR_WOOCOMMERCE_LOCALIZATION_DOMAIN
			),
			esc_html__( 'Settings', ACTIVECAMPAIGN_FOR_WOOCOMMERCE_LOCALIZATION_DOMAIN )
		);

		$action_links = array(
			$html,
		);

		return array_merge( $action_links, $links );
	}

	/**
	 * Fetch the PHP template file that is used for the admin page
	 *
	 * @since    1.4.9
	 */
	public function please_configure_plugin_notice() {
		global $pagenow;
		global $plugin_page;

		// Verify we're on an admin section
		if (
		'activecampaign_for_woocommerce' !== $plugin_page &&
		current_user_can( 'administrator' ) &&
		(
			'admin.php' === $pagenow
			|| 'plugins.php' === $pagenow
			|| get_current_screen()->in_admin()
		)
		) {
			require_once plugin_dir_path( __FILE__ ) . 'views/activecampaign-for-woocommerce-please-configure-plugin-notice.php';
		}
	}

	/**
	 * Populates an admin notice dismiss in db.
	 * Called via ajax action.
	 */
	public function update_dismiss_plugin_notice_option() {
		$setting                          = json_decode( get_option( 'activecampaign_for_woocommerce_dismiss_plugin_notice' ), 'array' );
		$setting[ get_current_user_id() ] = 1;
		update_option( 'activecampaign_for_woocommerce_dismiss_plugin_notice', wp_json_encode( $setting ) );
	}

	/**
	 * Populates an admin notice.
	 */
	public function error_admin_notice() {
		global $pagenow;

		// Verify we're on an admin section
		if ( 'admin.php' === $pagenow ) {
			$err_count = $this->get_ac_error_count();

			if ( ! empty( $err_count ) ) {
				if ( function_exists( 'wc_admin_url' ) ) {
					$admin_log_url = wc_admin_url(
						'status',
						array(
							'page' => 'wc-status',
							'tab'  => 'logs',
						)
					);
				} else {
					$admin_log_url = admin_url( 'admin.php?page=wc-status&tab=logs' );
				}

				echo '<div id="activecampaign-for-woocommerce-notice-error" class="notice notice-error is-dismissible activecampaign-for-woocommerce-error"><p>' .
				esc_html(
					'The ActiveCampaign for WooCommerce plugin has recorded ' . $err_count . ' ' .
					translate_nooped_plural(
						array(
							'singular' => 'error',
							'plural'   => 'errors',
							'domain'   => ACTIVECAMPAIGN_FOR_WOOCOMMERCE_LOCALIZATION_DOMAIN,
							'context'  => null,
						),
						$err_count
					) .
					 '.'
				) .
					 '<br/><a href="' . esc_url( $admin_log_url ) . '">' . esc_html( 'Please check the ActiveCampaign logs for issues.' ) .
					 '</a></p></div>
						<script type="text/javascript">
						jQuery(document).ready(function($) {
						    $("#activecampaign-for-woocommerce-notice-error").click(function(){
								jQuery.ajax({
						            url: ajaxurl,
							        data: {
												action: "activecampaign_for_woocommerce_dismiss_error_notice"
							        }
						        });
							});
						});
					</script>';
			}
		}
	}

	/**
	 * Gets the AC error count from the database.
	 *
	 * @return int|string|null The error count.
	 */
	public function get_ac_error_count() {
		global $wpdb;
		$err_count = 0;

		try {
			$level = 500;

			// phpcs:disable
			$err_count = $wpdb->get_var(
				$wpdb->prepare(
					'SELECT COUNT(*) FROM ' . $wpdb->prefix . 'woocommerce_log WHERE source = %s OR source = %s AND level = %d',
					[ ACTIVECAMPAIGN_FOR_WOOCOMMERCE_PLUGIN_ERR_KEBAB, ACTIVECAMPAIGN_FOR_WOOCOMMERCE_PLUGIN_NAME_KEBAB, $level ]
				)
			);
			// phpcs:enable
		} catch ( Throwable $t ) {
			$logger = new Logger();
			$logger->warning( 'There was an issue retrieving log information from the woocommerce_log table.' );
		}

		return $err_count;
	}

	/**
	 * Updates the dismiss error notice option in the database.
	 * Called via ajax action.
	 */
	public function update_dismiss_error_notice_option() {
		$setting                          = json_decode( get_option( 'activecampaign_for_woocommerce_dismiss_error_notice' ), 'array' );
		$setting[ get_current_user_id() ] = 1;
		update_option( 'activecampaign_for_woocommerce_dismiss_error_notice', wp_json_encode( $setting ) );
	}

	/**
	 * Clears the error log history.
	 */
	public function clear_error_logs() {
		$logger = new Logger();
		if ( ! $this->validate_request_nonce( 'activecampaign_for_woocommerce_status_form' ) ) {
			wp_send_json_error( 'Clear logs failed. Invalid Nonce.' );
			throw new Error( 'Clear logs failed. Invalid Nonce.' );
		}

		$result = $logger->clear_wc_error_log();
		$count  = $result['count'];
		if ( $result['error'] ) {
			wp_send_json_error( 'Action Failed. Unauthorized access.' );
		} else {
			delete_option( 'activecampaign_for_woocommerce_dismiss_error_notice' );
			wp_send_json_success(
				$count . ' ' .
				translate_nooped_plural(
					array(
						'singular' => 'record',
						'plural'   => 'records',
						'domain'   => ACTIVECAMPAIGN_FOR_WOOCOMMERCE_LOCALIZATION_DOMAIN,
						'context'  => null,
					),
					$count
				) . ' removed from the database.'
			);
		}
	}

	/**
	 * Fetch the PHP template file that is used for the admin abandoned cart page.
	 *
	 * @since    1.0.0
	 */
	public function fetch_admin_page() {
		wp_enqueue_script( $this->plugin_name . 'settings-page' );
		wp_enqueue_script( $this->plugin_name . 'settings-page-connection' );
		wp_enqueue_script( $this->plugin_name . 'settings-page-status-mapping' );

		require_once plugin_dir_path( __FILE__ )
				 . 'views/activecampaign-for-woocommerce-admin-display.php';
	}


	/**
	 * Returns an encoded array of existing notices to be displayed on page-load.
	 *
	 * Once displayed, these notifications are then removed so they don't constantly build up in the
	 * UI.
	 *
	 * @return string
	 */
	public function get_admin_notices() {
		try {
			$storage = $this->get_local_settings();

			$notifications = isset( $storage['notifications'] ) ? $storage['notifications'] : array();

			$this->update_settings(
				array(
					'notifications' => array(),
				)
			);

			return wp_json_encode( $notifications );
		} catch ( Throwable $t ) {
			$logger = new Logger();
			$logger->warning(
				'There was an issue forcing a row sync on an abandoned cart',
				array(
					'message'  => $t->getMessage(),
					'function' => 'handle_abandon_cart_force_row_sync',
				)
			);
		}
	}

	/**
	 * Handles the API Test request from the settings page,
	 * then redirects back to the plugin page
	 */
	public function handle_api_test() {
		if ( ! $this->validate_request_nonce( 'activecampaign_for_woocommerce_settings_form' ) ) {
			wp_send_json_error( $this->get_response(), 403 );
		}

		$new_data     = $this->extract_post_data();
		$current_data = get_option( ACTIVECAMPAIGN_FOR_WOOCOMMERCE_DB_SETTINGS_NAME );

		$errors = $this->validator->validate( $new_data, $current_data, true );

		if ( ! empty( $errors ) ) {
			foreach ( $errors as $error ) {
				$this->push_response_error(
					$this->format_response_message(
						$error,
						'error'
					)
				);
			}
		}

		if ( $this->response_has_errors() ) {
			wp_send_json_error( $this->get_response(), 422 );
		}

		$this->push_response_notice(
			$this->format_response_message( 'API tested successfully!', 'success' )
		);

		wp_send_json_success( $this->get_response() );
	}

	public function check_for_existing_connection() {
		$logger  = new Logger();
		$storage = $this->get_connection_storage();

		if ( ! $this->is_configured() ) {
			return false;
		}

		$cached_connection = get_transient( 'activecampaign_for_woocommerce_connection' );

		if ( false !== $cached_connection ) {
			return $cached_connection;
		}

		if (
			isset( $storage['connection_id'] ) &&
			! empty( $storage['connection_id'] )
		) {
			$connection = get_transient( 'activecampaign_for_woocommerce_connection' );

			if ( ! $connection ) {
				$connection = $this->connection_repository->find_by_id( $storage['connection_id'] );
			}
		}

		if ( ! isset( $connection ) || ! $connection->get_id() ) {
			$connection = $this->connection_repository->find_current(); // If we don't have an active connection ID, check if we have one for the URL
			$logger->debug( 'Find my current connection', [ self::validate_object( $connection, 'serialize_to_array' ) ? $connection->serialize_to_array() : null ] );
		}

		// No valid connection, find current if it exists
		if ( ! isset( $connection ) || ! $connection->get_id() ) {
			// No valid connection check by filter
			// If we don't have an ID now just get the WC service we find
			$connection = $this->connection_repository->find_by_filter( 'service', 'woocommerce' );

			$logger->debug(
				'This is the full connection check output',
				[
					'$storage'         => $storage,
					'site_url'         => get_site_url(),
					'serializetoarray' => self::validate_object( $connection, 'serialize_to_array' ) ? $connection->serialize_to_array() : null,
				]
			);
		}

		// If this is accurate store it
		$valid_site_urls = [
			get_site_url(),
			get_site_url() . '/',
			get_home_url(),
			get_home_url() . '/',
			get_option( 'home' ),
			get_option( 'home' ) . '/',
		];

		if ( isset( $connection ) &&
			 ( in_array( $connection->get_externalid(), $valid_site_urls, false ) ||
			 $storage['external_id'] === $connection->get_externalid() )
		) {
			set_transient( 'activecampaign_for_woocommerce_connection', $connection, 3600 );
			return $connection;
		}

		return false;
	}

	/**
	 * Checks the health of the connection and returns issues or empty.
	 * Called in the view header.
	 *
	 * @return array|bool
	 */
	public function connection_health_check() {
		if ( $this->get_connection_storage() ) {
			$issues   = array(
				'warnings' => [],
				'errors'   => [],
			);
			$now      = date_create( 'NOW' );
			$last_run = get_option( 'activecampaign_for_woocommerce_connection_health_check_last_run' );
			$settings = $this->get_local_settings();
			$storage  = $this->get_connection_storage();
			$logger   = new Logger();

			if ( ! $this->is_configured() ) {
				return false;
			}

			if ( false !== $last_run ) {
				$interval         = date_diff( $now, $last_run );
				$interval_minutes = $interval->format( '%i' );
			} else {
				$interval_minutes = 0;
			}

			if ( false === $last_run || $interval_minutes >= 60 || ! isset( $storage['connection_id'] ) ) {
				update_option( 'activecampaign_for_woocommerce_connection_health_check_last_run', $now );

				if ( empty( $storage ) || ( empty( $settings['api_url'] ) && empty( $settings['api_key'] ) ) ) {
					$issues['errors'][] = 'API URL and/or Key is missing.';
				}

				if ( ! isset( $storage['connection_id'] ) || empty( $storage['connection_id'] ) ) {
					$issues['errors'][] = 'Connection id is missing from settings!';
				} else {
					try {
						$connection = $this->connection_repository->find_by_id( $storage['connection_id'] );

						if ( ! isset( $connection ) || empty( $connection->get_externalid() ) ) {
							$connection = $this->check_for_existing_connection();
							if ( ! $connection ) {
								$issues['errors'][] = 'A valid connection ID for this store could not be found from the stored data.';
							} else {
								$storage = $this->get_connection_storage();
							}
						}
					} catch ( Throwable $t ) {
						$logger->warning(
							'There was an issue trying to validate connection ID.',
							array(
								'message' => $t->getMessage(),
								'trace'   => $logger->clean_trace( $t->getTrace() ),
							)
						);

						$issues['warnings'][] = $t->getMessage();
					}

					try {
						$valid_site_urls = [
							get_site_url(),
							get_site_url() . '/',
							get_home_url(),
							get_home_url() . '/',
							get_option( 'home' ),
							get_option( 'home' ) . '/',
						];

						if (
							isset( $connection ) &&
							self::validate_object( $connection, 'get_id' ) &&
							! empty( $connection->get_id() ) &&
							! in_array( $connection->get_externalid(), $valid_site_urls, false )
						) {
							$issues['warnings'][] = 'The connection URL and your site URL do not match.';

							if ( isset( $storage['external_id'] ) ) {
								$valid_site_urls[] = $storage['external_id'];
							}

							if ( ! in_array( $connection->get_externalid(), $valid_site_urls, false ) ) {
								if ( empty( $connection->get_externalid() ) ) {
									$issues['errors'][] = 'Your stored connection ID could not be found in ActiveCampaign. You will need to fix your connection.';
								} else {
									$issues['warnings'][] = 'This is not ideal but this can be ignored if you are not experiencing issues.';
									$issues['warnings'][] = 'Your site URL is ' . get_site_url() . ' | The stored integration URL in ActiveCampaign matching ID ' . $storage['connection_id'] . ' is ' . $connection->get_externalid();
								}
							}
						} elseif (
							isset( $connection ) &&
							self::validate_object( $connection, 'get_id' ) &&
							! empty( $connection->get_id() ) &&
							in_array( $connection->get_externalid(), $valid_site_urls, false )
						) {
							$this->update_storage_from_connection( $connection );
						}

						if (
							isset( $connection ) &&
							$storage['external_id'] === $connection->get_externalid() &&
							! in_array( $connection->get_externalid(), $valid_site_urls, false )
						) {
							$issues['warnings'][] = 'The connection URL and your site URL do not match. This is not ideal but this can be ignored if you are not experiencing issues.';
							$issues['warnings'][] = 'Your site URL is ' . get_site_url() . ' | The stored integration URL in ActiveCampaign matching ID ' . $storage['connection_id'] . ' is ' . $connection->get_externalid();
						}
					} catch ( Throwable $t ) {
						$logger->warning(
							'The connection to ActiveCampaign was not defined.',
							array(
								'message' => $t->getMessage(),
								'trace'   => $logger->clean_trace( $t->getTrace() ),
							)
						);

						$issues['errors'][] = $t->getMessage();
					}
				}
			}

			if ( count( $issues['errors'] ) > 0 ) {
				$issues['errors'][] = '* ActiveCampaign functionality will be disabled until the connection is repaired.';
				$issues['errors'][] = '* Please update your settings and validate your connection.';
				delete_option( 'activecampaign_for_woocommerce_connection_health_check_last_run' );
				$logger->error(
					'Connection Health Check: Issues Discovered',
					[
						'issues'           => $issues,
						'suggested_action' => 'Please see the issues above and verify your connection is properly selected and set up.',
						'ac_code'          => 'ADMIN_663',
					]
				);
				$storage = $this->get_connection_storage();

				if ( ! isset( $storage['connection_id'] ) || empty( $storage['connection_id'] ) ) {
					$this->clear_cron_syncs();
				}
			}

			return $issues;
		} else {
			return false;
		}
	}

	/**
	 * Handles the form submission for the settings page,
	 * then redirects back to the plugin page.
	 */
	public function handle_settings_post() {
		try {
			if ( ! $this->validate_request_nonce( 'activecampaign_for_woocommerce_settings_form' ) ) {
				wp_send_json_error( $this->get_response(), 403 );
			}

			$post_data = $this->extract_post_data();

			$this->update_settings( $post_data );

			if ( $this->response_has_errors() ) {
				wp_send_json_error( $this->get_response(), 422 );
			}

			// Settings saved, make sure our table is populated.
			do_action( 'activecampaign_for_woocommerce_verify_tables' );

			$this->schedule_cron_syncs();

			$this->push_response_notice(
				$this->format_response_message( 'Settings successfully updated!', 'success' )
			);

			wp_send_json_success( $this->get_response() );
		} catch ( Throwable $t ) {
			$logger = new Logger();
			$logger->warning(
				'There was an issue saving settings.',
				array(
					'message'  => $t->getMessage(),
					'function' => 'handle_settings_post',
				)
			);
		}
	}

	/**
	 * Returns the options values in the DB.
	 *
	 * @return array
	 */
	public function get_local_settings() {
		if ( ACTIVECAMPAIGN_FOR_WOOCOMMERCE_DB_SETTINGS_NAME ) {
			return get_option( ACTIVECAMPAIGN_FOR_WOOCOMMERCE_DB_SETTINGS_NAME );
		} else {
			return get_option( 'activecampaign_for_woocommerce_settings' );
		}
	}

	/**
	 * Updates the settings options in the DB.
	 *
	 * @param     array $data     An array of data that will be serialized into the DB.
	 *
	 * @return array
	 * @throws Exception When the container is missing definitions.
	 */
	public function update_settings( $data ) {
		$logger           = new Logger();
		$current_settings = get_option( ACTIVECAMPAIGN_FOR_WOOCOMMERCE_DB_SETTINGS_NAME );

		$this->validate_options_update( $data, $current_settings );

		if ( $this->response_has_errors() ) {
			return $this->response;
		}

		$api_url_changing = $this->api_url_is_changing( $data, $current_settings );

		if ( $current_settings ) {
			$data = array_merge( $current_settings, $data );
		}

		if ( ! isset( $current_settings['webhooks_deleted'] ) ) {
			// Disable our webhooks
			try {
				global $wpdb;
				$wpdb->query(
					'UPDATE ' . $wpdb->prefix . "wc_webhooks SET `status` = 'disabled' WHERE `name` LIKE '%ActiveCampaign WooCommerce Deep Data%'"
				);
				$data['webhooks_deleted'] = 1;
			} catch ( Throwable $t ) {
				$logger->warning( 'There was an issue disabling ActiveCampaign webhooks.' );
			}
		}

		update_option( ACTIVECAMPAIGN_FOR_WOOCOMMERCE_DB_SETTINGS_NAME, $data );

		$this->event->trigger(
			array(
				'api_url_changed' => $api_url_changing,
			)
		);

		return $this->get_local_settings();
	}

	/**
	 * Returns the storage values in the DB.
	 *
	 * @return array
	 */
	public function get_connection_storage() {
		return get_option( ACTIVECAMPAIGN_FOR_WOOCOMMERCE_DB_CONNECTION_STORAGE_NAME );
	}

	/**
	 * Gets the count of all sync ready orders from WC.
	 *
	 * @param     string $type The type to return. Expects "array".
	 *
	 * @return int|string|array
	 */
	public function get_sync_ready_order_count( $type = 'int' ) {
		if ( 'array' === $type ) {

			$order_totals = array(
				'processing' => wc_orders_count( 'processing', 'shop_order' ),
				'completed'  => wc_orders_count( 'completed', 'shop_order' ),
				'failed'     => wc_orders_count( 'failed', 'shop_order' ),
				'cancelled'  => wc_orders_count( 'cancelled', 'shop_order' ),
				'refunded'   => wc_orders_count( 'refunded', 'shop_order' ),
				'pending'    => wc_orders_count( 'pending', 'shop_order' ),
				'on-hold'    => wc_orders_count( 'on-hold', 'shop_order' ),
			);
		} else {
			$order_totals = wc_orders_count( 'processing', 'shop_order' )
				+ wc_orders_count( 'completed', 'shop_order' )
				+ wc_orders_count( 'failed', 'shop_order' )
				+ wc_orders_count( 'cancelled', 'shop_order' )
				+ wc_orders_count( 'refunded', 'shop_order' )
				+ wc_orders_count( 'pending', 'shop_order' )
				+ wc_orders_count( 'on-hold', 'shop_order' );
		}

		return $order_totals;
	}

	public function update_storage_from_connection( Connection $connection ) {
		if ( isset( $connection ) && self::validate_object( $connection, 'get_id' ) ) {
			$this->update_connection_storage(
				[
					'connection_id' => $connection->get_id(),
					'name'          => $connection->get_name(),
					'external_id'   => $connection->get_externalid(),
					'service'       => $connection->get_service(),
					'link_url'      => $connection->get_link_url(),
					'logo_url'      => $connection->get_logo_url(),
					'is_internal'   => $connection->get_is_internal(),
				]
			);

			delete_transient( 'activecampaign_for_woocommerce_all_connections' );
			delete_transient( 'activecampaign_for_woocommerce_connection' );

			return true;
		}
	}

	/**
	 * Updates the connection storage values in the DB.
	 *
	 * @param     array $data     An array of data that will be serialized into the DB.
	 *
	 * @return bool
	 */
	public function update_connection_storage( $data ) {
		$current_settings = get_option( ACTIVECAMPAIGN_FOR_WOOCOMMERCE_DB_CONNECTION_STORAGE_NAME );

		if ( $current_settings ) {
			$data = array_merge( $current_settings, $data );
		}

		update_option( ACTIVECAMPAIGN_FOR_WOOCOMMERCE_DB_CONNECTION_STORAGE_NAME, $data );

		delete_transient( 'activecampaign_for_woocommerce_all_connections' );
		delete_transient( 'activecampaign_for_woocommerce_connection' );

		return true;
	}

	/**
	 * Allows an event listener/async process to store a notification to be displayed
	 * on the next settings page load.
	 *
	 * @param     string $message     The message to be translated and escaped for display.
	 * @param     string $level     The level of severity of the message.
	 */
	public function add_async_processing_notification( $message, $level = 'info' ) {
		$current_storage = $this->get_local_settings();

		if ( ! isset( $current_storage['notifications'] ) ) {
			$current_storage['notifications'] = array();
		}

		$notifications = $current_storage['notifications'];

		$notifications[] = $this->format_response_message( $message, $level );

		$this->update_settings( [ 'notifications' => $notifications ] );
	}

	/**
	 * Validates the new data for the options table.
	 *
	 * @param     array $new_data     The array of data to be updated.
	 * @param     array $current_data     The existing data for the options.
	 */
	private function validate_options_update( $new_data, $current_data ) {
		$errors = $this->validator->validate( $new_data, $current_data );

		if ( ! empty( $errors ) ) {
			foreach ( $errors as $error ) {
				$this->push_response_error(
					$this->format_response_message(
						$error,
						'error'
					)
				);
			}
		}
	}

	/**
	 * Adds to the array of response errors a new error.
	 *
	 * @param     array $error     The error associative array containing the error message and level.
	 */
	private function push_response_error( $error ) {
		if ( ! isset( $this->response['errors'] ) ) {
			$this->response['errors'] = array();
		}

		$this->response['errors'][] = $error;
	}

	/**
	 * Adds to the array of response notices a new notice.
	 *
	 * @param     array $notice     The notice associative array containing the notice message and level.
	 */
	private function push_response_notice( $notice ) {
		if ( ! isset( $this->response['notices'] ) ) {
			$this->response['notices'] = array();
		}

		$this->response['notices'][] = $notice;
	}

	/**
	 * Returns an array of the response array with notices and errors
	 * merged with the current state of the options array.
	 *
	 * @return array
	 */
	private function get_response() {
		if ( $this->get_local_settings() ) {
			return array_merge( $this->response, $this->get_local_settings() );
		}

		return array_merge( $this->response, array() );
	}

	/**
	 * Checks whether or not the current response contains errors.
	 *
	 * @return bool
	 */
	private function response_has_errors() {
		return isset( $this->response['errors'] ) && count( $this->response['errors'] ) > 0;
	}

	/**
	 * Checks if the API Url setting is changing.
	 *
	 * @param     array $new_data     An array of new data to be saved.
	 * @param     array $current_data     An array of data that's already saved.
	 *
	 * @return bool
	 */
	private function api_url_is_changing( $new_data, $current_data ) {
		return ( isset( $new_data['api_url'] ) && isset( $current_data['api_url'] ) ) && // both are set
			   $new_data['api_url'] !== $current_data['api_url'];                        // and changing
	}

	/**
	 * Registers available WooCommerce route.
	 */
	public function active_campaign_register_settings_api() {
		register_rest_route(
			'wc',
			'/v2/active-campaign-for-woocommerce/register-integration',
			array(
				'methods'             => 'POST',
				'callback'            => array(
					$this,
					'save_active_campaign_settings',
				),
				'permission_callback' => array(
					$this,
					'validate_rest_user',
				),
			)
		);
	}

	/**
	 * Saves our integration connection settings.
	 *
	 * @param     WP_REST_Request $request     The request object.
	 *
	 * @return WP_REST_Response The REST response object.
	 */
	public function save_active_campaign_settings( WP_REST_Request $request ) {
		$logger = new Logger();

		if ( $request->has_param( 'api_url' ) && $request->has_param( 'api_key' ) ) {
			$params  = $request->get_params();
			$options = $this->get_local_settings();

			$defaults = $this->get_default_settings();
			// We need to set the default values so WP doesn't error

			foreach ( $defaults as $key => $default ) {
				if ( ! isset( $options[ $key ] ) ) {
					$params[ $key ] = $default;
				}
			}

			$logger->info( 'Saving integration settings from ActiveCampaign...', array( $params ) );

			$this->update_settings( $params );

			// If settings were saved we should populate our table to enable functionality
			do_action( 'activecampaign_for_woocommerce_verify_tables' );
			$this->schedule_cron_syncs();
			return new WP_REST_Response( 'ActiveCampaign connection settings successfully saved to WordPress.', 201 );
		} else {
			$logger->error(
				'ActiveCampaign was unable to store data via the WooCommerce API.',
				[
					'suggested_action' => 'Please setup your integration URL and key manually by visiting the developer page in your ActiveCampaign Hosted settings and saving it to your settings in the WordPress plugin.',
					'ac_code'          => 'ADMIN_1018',
				]
			);

			return new WP_REST_Response( 'Error: Missing required parameters.', 400 );
		}
	}

	/**
	 * Generate the cron sync scheduled processes
	 */
	public function schedule_cron_syncs() {
		wp_clear_scheduled_hook( ACTIVECAMPAIGN_FOR_WOOCOMMERCE_RUN_NEW_ORDER_SYNC_NAME );
		wp_clear_scheduled_hook( ACTIVECAMPAIGN_FOR_WOOCOMMERCE_RUN_HISTORICAL_RECUR );
		wp_clear_scheduled_hook( 'activecampaign_for_woocommerce_cart_updated_recurring_event' );

		try {
			if (
				isset( $this->get_connection_storage()['connection_id'] ) &&
				$this->is_connected() &&
				$this->is_configured()
			) {
				wp_schedule_event( time() + 10, 'hourly', 'activecampaign_for_woocommerce_cart_updated_recurring_event' );
				wp_schedule_event( time() + 10, 'every_minute', ACTIVECAMPAIGN_FOR_WOOCOMMERCE_RUN_NEW_ORDER_SYNC_NAME );
				wp_schedule_event( time() + 15, 'every_minute', ACTIVECAMPAIGN_FOR_WOOCOMMERCE_RUN_HISTORICAL_RECUR );
			}
		} catch ( Throwable $t ) {
			$logger = new Logger();
			$logger->warning(
				'There was an issue scheduling the cron events from admin settings.',
				array(
					'message' => $t->getMessage(),
				)
			);
		}
	}

	/**
	 * Unschedules cron syncs
	 */
	public function clear_cron_syncs() {
		wp_clear_scheduled_hook( ACTIVECAMPAIGN_FOR_WOOCOMMERCE_RUN_NEW_ORDER_SYNC_NAME );
		wp_clear_scheduled_hook( 'activecampaign_for_woocommerce_cart_updated_recurring_event' );
		wp_clear_scheduled_hook( ACTIVECAMPAIGN_FOR_WOOCOMMERCE_RUN_HISTORICAL_RECUR );
	}

	/**
	 * Callback function to validate the user can save settings
	 *
	 * @return bool|WP_Error The error or true.
	 */
	public function validate_rest_user() {
		if ( ! is_user_logged_in() ) {
			return new WP_Error( 'Unauthorized', __( 'Unauthorized', ACTIVECAMPAIGN_FOR_WOOCOMMERCE_LOCALIZATION_DOMAIN ), array( 'status' => 401 ) );
		} elseif ( ! current_user_can( 'administrator' ) ) {
			return new WP_Error( 'Forbidden', __( 'Forbidden', ACTIVECAMPAIGN_FOR_WOOCOMMERCE_LOCALIZATION_DOMAIN ), array( 'status' => 403 ) );
		} else {
			return true;
		}
	}

	/**
	 * Handles the ajax call for clear plugin settings.
	 */
	public function handle_clear_plugin_settings() {
		$logger = new Logger();

		if ( ! $this->validate_request_nonce( 'activecampaign_for_woocommerce_settings_form' ) ) {
			wp_send_json_error( 'The nonce appears to be invalid.' );
		}

		if ( $this->clear_plugin_settings() ) {
			$logger->info( 'Plugin settings have been manually cleared by the admin. The plugin will not run until new settings are saved.' );
			wp_send_json_success( 'ActiveCampaign for WooCommerce settings have been cleared. NOTICE: The plugin will not run until new settings are saved.' );
		} else {
			wp_send_json_error( 'There was an issue attempting to clear the plugin settings' );
		}
	}

	/**
	 * Attempts to clear the plugin settings.
	 *
	 * @return bool
	 */
	private function clear_plugin_settings() {
		try {
			if ( delete_option( ACTIVECAMPAIGN_FOR_WOOCOMMERCE_DB_SETTINGS_NAME ) && delete_option( ACTIVECAMPAIGN_FOR_WOOCOMMERCE_DB_CONNECTION_STORAGE_NAME ) ) {
				wp_clear_scheduled_hook( 'activecampaign_for_woocommerce_cart_updated_recurring_event' );
				wp_clear_scheduled_hook( ACTIVECAMPAIGN_FOR_WOOCOMMERCE_RUN_NEW_ORDER_SYNC_NAME );
				wp_clear_scheduled_hook( ACTIVECAMPAIGN_FOR_WOOCOMMERCE_RUN_PRODUCT_SYNC_NAME );
				wp_clear_scheduled_hook( ACTIVECAMPAIGN_FOR_WOOCOMMERCE_RUN_HISTORICAL_RECUR );
				wp_clear_scheduled_hook( ACTIVECAMPAIGN_FOR_WOOCOMMERCE_RUN_HISTORICAL_SYNC_NAME );
				wp_clear_scheduled_hook( ACTIVECAMPAIGN_FOR_WOOCOMMERCE_HISTORICAL_SYNC_SCHEDULED_STATUS_NAME );
				wp_clear_scheduled_hook( 'activecampaign_for_woocommerce_prep_historical_data' );
				delete_transient( 'activecampaign_for_woocommerce_all_connections' );
				delete_transient( 'activecampaign_for_woocommerce_connection' );
				delete_option( ACTIVECAMPAIGN_FOR_WOOCOMMERCE_HISTORICAL_SYNC_RUNNING_STATUS_NAME );
				delete_option( ACTIVECAMPAIGN_FOR_WOOCOMMERCE_HISTORICAL_SYNC_SCHEDULED_STATUS_NAME );
				delete_option( ACTIVECAMPAIGN_FOR_WOOCOMMERCE_HISTORICAL_SYNC_LAST_STATUS_NAME );
				delete_option( 'activecampaign_for_woocommerce_connection_health_check_last_run' );
				delete_option( 'activecampaign_for_woocommerce_last_order_sync' );
				delete_option( 'activecampaign_for_woocommerce_abandoned_cart_last_run' );
				delete_option( ACTIVECAMPAIGN_FOR_WOOCOMMERCE_HISTORICAL_SYNC_SCHEDULED_STATUS_NAME );
				delete_option( ACTIVECAMPAIGN_FOR_WOOCOMMERCE_PRODUCT_SYNC_RUNNING_STATUS_NAME );
				delete_option( ACTIVECAMPAIGN_FOR_WOOCOMMERCE_PRODUCT_SYNC_LAST_STATUS_NAME );
				return true;
			} else {
				return false;
			}
		} catch ( Throwable $t ) {
			$logger = new Logger();
			$logger->warning(
				'There was an issue trying to reset the connection ID',
				array(
					'message' => $t->getMessage(),
					'trace'   => $logger->clean_trace( $t->getTrace() ),
				)
			);
			return false;
		}
	}

	/**
	 * Handles the ajax call for reset connection.
	 *
	 * @send string json_success|json_error AJAX return for success or failure.
	 */
	public function handle_reset_connection_id() {
		$logger = new Logger();

		if ( ! $this->validate_request_nonce( 'activecampaign_for_woocommerce_settings_form' ) ) {
			wp_send_json_error( 'The nonce appears to be invalid.' );
		}

		$logger->info(
			'The connection ID has been manually reset. These are the stored options.',
			array(
				'option_values' => get_option( ACTIVECAMPAIGN_FOR_WOOCOMMERCE_DB_CONNECTION_STORAGE_NAME ),
			)
		);

		$connection = $this->check_for_existing_connection();

		if ( isset( $connection ) ) {
			$this->update_storage_from_connection( $connection );
		}

		delete_option( 'activecampaign_for_woocommerce_connection_health_check_last_run' );

		$this->schedule_cron_syncs();
		wp_send_json_success( 'ActiveCampaign connection ID has been updated/repaired.' );
	}

	/**
	 * Outputs flushed PHP actively being processed.
	 *
	 * @param string $output The output string.
	 */
	public function output_echo( $output ) {
		echo '<p>' . esc_html( $output ) . '</p>';
		ob_flush();
		flush();
	}

	/**
	 * Removes a deleted order from our local table.
	 * This can be used for new or historical sync orders.
	 * TODO: Make a check for if this is an order or a product.
	 *
	 * @param     mixed ...$args The passed arguments.
	 */
	public function remove_deleted_order( ...$args ) {
		$logger = new Logger();

		if ( isset( $args[0] ) ) {
			$order_id  = $args[0];
			$post_type = get_post_type( $order_id );

			// If it's not an order just ignore it
			if ( 'shop_order' !== $post_type ) {
				return;
			}

			global $wpdb;
			// phpcs:disable
			$wpdb->delete( $wpdb->prefix . ACTIVECAMPAIGN_FOR_WOOCOMMERCE_TABLE_NAME, [ 'wc_order_id' => $order_id ] );
			// phpcs:enable
		}
	}

	/**
	 * Catches the restore of a product and sets it to sync to COFE.
	 *
	 * @param mixed ...$args The arguments.
	 */
	public function restore_product( ...$args ) {
		$logger = new Logger();

		if ( isset( $args[0] ) ) {
			$product_id = $args[0];
			$post_type  = get_post_type( $product_id );

			if ( 'product' !== $post_type ) {
				return;
			}

			$logger->debug( 'Restore trashed product', [ 'product' => $args ] );
			// Sync the product when restored
			do_action( 'activecampaign_for_woocommerce_run_single_product_sync', $product_id );
		}
	}
	/**
	 * Makes a yes check mark HTML echo.
	 *
	 * @param     string $s The string output.
	 */
	public function output_yes_mark( $s = '' ) {
		echo '
			<mark class="yes">
				<b>&check;</b> [ ' . esc_html( $s ) . ' ]
			</mark>
		';
	}

	/**
	 * Makes an error mark HTML echo.
	 *
	 * @param     string $s The string output.
	 */
	public function output_err_mark( $s = '' ) {
		echo '
			<mark class="error">
				<b>x</b> [ ' . esc_html( $s ) . ' ]
		</mark>';
	}

	/**
	 * Handles the status mapping actions
	 */
	public function handle_status_mapping_actions() {
		$logger = new Logger();

		if ( ! $this->validate_request_nonce( 'activecampaign_for_woocommerce_settings_form' ) ) {
			wp_send_json_error( 'The nonce appears to be invalid.' );
		}

		try {
			$post_data = $this->extract_post_data();
			$settings  = $this->get_local_settings();

			if ( 'create' === $post_data['perform'] ) {
				$settings['status_mapping'][ $post_data['wc_status_key'] ] = $post_data['ac_status_key'];
			}

			if ( 'delete' === $post_data['perform'] ) {
				unset( $settings['status_mapping'][ $post_data['wc_status_key'] ] );
			}

			$this->update_settings( $settings );
		} catch ( Throwable $t ) {
			$logger->warning(
				'Could not save mapping',
				[ $t->getMessage() ]
			);
			wp_send_json_error( 'An error was encountered.' );
		}

		wp_send_json_success( 'Mapping was added.' );
	}

	public function clear_entitlements_cache() {
		delete_transient( 'activecampaign_for_woocommerce_features' );
		wp_send_json_success( 'Entitlement ready for refresh' );
	}
}
