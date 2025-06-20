<?php
/**
 * The public-facing functionality of the plugin.
 *
 * @link       https://www.activecampaign.com/
 * @since      1.0.0
 *
 * @package    Activecampaign_For_Woocommerce
 * @subpackage Activecampaign_For_Woocommerce/public
 */

use Activecampaign_For_Woocommerce_Admin as Admin;
use Activecampaign_For_Woocommerce_User_Meta_Service as User_Meta_Service;
use Activecampaign_For_Woocommerce_Logger as Logger;
use Automattic\WooCommerce\Blocks\Package;
use Automattic\WooCommerce\Blocks\Domain\Services\CheckoutFields;

/**
 * The public-facing functionality of the plugin.
 *
 * Defines the plugin name, version, and two examples hooks for how to
 * enqueue the public-facing stylesheet and JavaScript.
 *
 * @package    Activecampaign_For_Woocommerce
 * @subpackage Activecampaign_For_Woocommerce/public
 * @author     acteamintegrations <team-integrations@activecampaign.com>
 */
class Activecampaign_For_Woocommerce_Public {

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
	 * An instance of the Admin class to handle communicating with the options table.
	 *
	 * @var    Admin
	 * @since  1.0.0
	 * @access private
	 */
	private $admin;

	/**
	 * The custom ActiveCampaign logger
	 *
	 * @var Activecampaign_For_Woocommerce_Logger
	 */
	private $logger;

	/**
	 * Marker for if the checkbox was successfully populated on the page
	 *
	 * @var bool $checkbox_populated
	 */
	private $checkbox_populated;

	/**
	 * Initialize the class and set its properties.
	 *
	 * @param     string $plugin_name     The name of the plugin.
	 * @param     string $version     The version of this plugin.
	 * @param     Admin  $admin     An instantiated admin class to optionally use.
	 * @param     Logger $logger     The ActiveCampaign WooCommerce logger.
	 *
	 * @since 1.0.0
	 */
	public function __construct( $plugin_name, $version, Admin $admin, Logger $logger = null ) {
		$this->plugin_name = $plugin_name;
		$this->version     = $version;
		$this->admin       = $admin;
		$this->logger      = $logger;
	}

	/**
	 * Initialize injections that are still null
	 */
	public function init() {
		if ( ! $this->logger ) {
			$this->logger = new Logger();
		}

		if ( ! $this->checkbox_populated ) {
			$this->checkbox_populated = false;
		}
	}

	/**
	 * Register the CSS & JavaScript for the public-facing side of the site.
	 *
	 * @since    1.0.0
	 */
	public function enqueue_styles_scripts() {
		if ( $this->better_is_checkout() ) {
			$this->init();

			wp_enqueue_style(
				$this->plugin_name,
				plugin_dir_url( __FILE__ ) . 'css/activecampaign-for-woocommerce-public.css',
				array(),
				$this->version,
				'all'
			);

			$custom_email_field = 'billing_email';

			try {
				$options = $this->admin->get_local_settings();
				if ( isset( $options['custom_email_field'] ) ) {
					$setting = $options['custom_email_field'];

					/**
					 * There are three settings available, but only this one results in not displaying the checkbox at all.
					 */
					if ( ! empty( $setting ) && ! is_null( $setting ) ) {
						$custom_email_field = $setting;
					}
				} else {
					$this->logger->error(
						'The value for custom_email_field not found in database. This should not happen and may mean the setting has not been saved to the database.',
						array(
							'suggested_action' => 'Go to the plugin settings page and save the opt-in setting again. If the issue persists please contact support.',
							'ac_code'          => 'PUB_129',
						)
					);
				}
			} catch ( Throwable $t ) {
				$this->logger->debug(
					'Activecampaign_For_Woocommerce_Public: There was an issue reading the custom email field.',
					array(
						'message' => $t->getMessage(),
					)
				);
			}

			wp_register_script(
				$this->plugin_name,
				plugin_dir_url( __FILE__ ) . 'js/activecampaign-for-woocommerce-public.js?custom_email_field=' . $custom_email_field,
				array( 'jquery' ),
				$this->version,
				true
			);

			$sync_guest_abandoned_cart_nonce = wp_create_nonce( 'sync_guest_abandoned_cart_nonce' );

			wp_localize_script(
				$this->plugin_name,
				'public_vars',
				array(
					'ajaxurl' => admin_url( "admin-ajax.php?nonce=$sync_guest_abandoned_cart_nonce" ),
				)
			);

			wp_enqueue_script( $this->plugin_name );
		}

		try {
			$options = $this->admin->get_local_settings();

			if (
				isset( $options['browse_tracking'] ) &&
				in_array( $options['browse_tracking'], array( 1, '1' ), true ) &&
				isset( $options['tracking_id'] )
			) {
				$this->activecampaign_frontend_sitetracking_scripts( $options['tracking_id'] );
			}

			if (
				isset( $options['browse_tracking'] ) &&
				in_array( $options['browse_tracking'], array( 3, '3' ), true ) &&
				isset( $options['tracking_id'] )
			) {
				$this->activecampaign_frontend_sitetracking_scripts( $options['tracking_id'], true );
			}
		} catch ( Throwable $t ) {
			$this->logger->warning(
				'Activecampaign_For_Woocommerce_Public: There was an issue with enabling site tracking.',
				array(
					'message' => $t->getMessage(),
					'ac_code' => 'PUB_180',
				)
			);
		}
	}

	/**
	 * Handles the display of accepts marketing block for WooCommerce blocks.
	 * When using the blocks method it will run the hook to trigger this and return woocommerce_register_additional_checkout_field.
	 * handle_woocommerce_checkout_form is now legacy.
	 *
	 * @return CheckoutFields|void
	 */
	public function handle_woocommerce_blocks_checkout_form( $checkout ) {
		$this->init();

		try {
			$options                                   = $this->admin->get_local_settings();
			$activecampaign_for_woocommerce_is_checked = $this->accepts_marketing_checkbox_is_checked();
			$activecampaign_for_woocommerce_accepts_marketing_label = esc_html( $this->label_for_accepts_marketing_checkbox() );
			$location          = 'contact';
			$hidden_validation = false;

			if ( isset( $options['accepts_marketing_checkbox_location_option'] ) && ! empty( $options['accepts_marketing_checkbox_location_option'] ) ) {
				// contact or address are only valid options
				// TODO: Later iteration should allow admin to select which location and use it here.
				$location = $options['accepts_marketing_checkbox_location_option'];
			}

			if ( isset( $options['checkbox_display_option'] ) && 'not_visible' === $options['checkbox_display_option'] ) {
				$hidden_validation = [
					'type'       => 'object',
					'properties' => [
						'cart' => [
							'properties' => [
								'accepts_marketing_show' => [
									'const' => false,
								],
							],
						],
					],
				];
			}

			$data_custom = 'unchecked';
			if ( true === $activecampaign_for_woocommerce_is_checked ) {
				$data_custom = 'checked';
			}

			return woocommerce_register_additional_checkout_field(
				array(
					'id'         => ACTIVECAMPAIGN_FOR_WOOCOMMERCE_PLUGIN_NAME_SNAKE . '/accepts_marketing',
					'label'      => $activecampaign_for_woocommerce_accepts_marketing_label,
					'location'   => $location,
					'priority'   => 99,
					'required'   => false,
					'hidden'     => $hidden_validation,
					'type'       => 'checkbox',
					'attributes' => array(
						'data-custom' => $data_custom,
					),
				)
			);
		} catch (Throwable $t ) {
			$this->logger->warning( 'Accepts marketing encountered an error while attempting to register the checkout field', [$t->getMessage()] );
		}
	}

	/**
	 * If a user is logged in, adds an accepts marketing checkbox to the checkout form.
	 * This is now legacy checkout. handle_woocommerce_blocks_checkout_form handles blocks.
	 *
	 * Called as part of the WooCommerce action hooks when the checkout form is being built. The
	 * owner of the site is able to customize on which hook this method should be called.
	 */
	public function handle_woocommerce_checkout_form() {
		$this->init();

		if ( ! $this->checkbox_populated ) {
			if ( $this->admin->get_local_settings() ) {
				$options = $this->admin->get_local_settings();
			} else {
				$options = get_option( 'activecampaign_for_woocommerce_settings' );
			}

			if ( isset( $options['checkbox_display_option'] ) ) {
				$setting = $options['checkbox_display_option'];

				/**
				 * There are three settings available, but only this one results in not displaying the checkbox at all.
				 */
				if ( 'not_visible' === $setting ) {
					$this->logger->debug( 'checkbox_display_option is set to not_visible. We will not display it.' );

					return;
				}
			} else {
				$this->logger->error(
					'The value for checkbox_display_option not found in database. This should not happen and may mean the setting has not been saved to the database.',
					array(
						'suggested_action' => 'Go to the plugin settings page and save the opt-in setting again. If the issue persists please contact support.',
						'ac_code'          => 'PUB_193',
					)
				);
			}

			$activecampaign_for_woocommerce_is_checked              = $this->accepts_marketing_checkbox_is_checked();
			$activecampaign_for_woocommerce_accepts_marketing_label = esc_html( $this->label_for_accepts_marketing_checkbox() );

			// Label html must be built before the function
			$label = '<label for="activecampaign_for_woocommerce_accepts_marketing" class="woocommerce-form__label woocommerce-form__label-for-checkbox inline"><span>' . $activecampaign_for_woocommerce_accepts_marketing_label . '</span></label>';

			woocommerce_form_field(
				'activecampaign_for_woocommerce_accepts_marketing',
				array(
					'type'     => 'checkbox',
					'class'    => array( 'woocommerce-form__input', 'woocommerce-form__input-checkbox', 'input-checkbox' ),
					'label'    => $label,
					'required' => false,
				),
				$activecampaign_for_woocommerce_is_checked
			);

			$this->checkbox_populated = true;
		}
	}

	/**
	 * Returns a boolean to control whether or not the accepts marketing checkbox should be shown as checked.
	 *
	 * This determination is based on whether the user has previously accepted marketing and
	 * whether or not the site owner has set the checkbox to be checked by default.
	 *
	 * @return bool
	 */
	public function accepts_marketing_checkbox_is_checked() {
		if ( $this->current_user_has_accepted_marketing() ) {
			return true;
		}

		$checked = true;
		$options = $this->admin->get_local_settings();
		if ( isset( $options['checkbox_display_option'] ) ) {
			$setting = $options['checkbox_display_option'];

			/**
			 * There are three settings, but only this one results in a checked box by default.
			 */
			$checked = 'visible_checked_by_default' === $setting;
		} else {
			$this->logger->error(
				'The value for checkbox_display_option not found in database. This should not happen and may mean the setting has not been saved to the database.',
				array(
					'suggested_action' => 'Go to the plugin settings page and save the opt-in setting again. If the issue persists please contact support.',
					'ac_code'          => 'PUB_246',
				)
			);
		}

		return $checked;
	}

	/**
	 * Returns whether or not the current user has already accepted marketing.
	 *
	 * @return boolean
	 */
	public function current_user_has_accepted_marketing() {
		if ( ! is_user_logged_in() ) {
			return false;
		}

		return User_Meta_Service::get_current_user_accepts_marketing();
	}

	/**
	 * Returns the label to be added to the accepts marketing checkbox.
	 *
	 * The user can either set this string in their settings, or the
	 * default will be returned.
	 *
	 * @return string
	 */
	public function label_for_accepts_marketing_checkbox() {
		$options = $this->admin->get_local_settings();
		if ( isset( $options['optin_checkbox_text'] ) ) {
			return $options['optin_checkbox_text'];
		} else {
			return esc_attr__(
				'Keep me up to date on news and exclusive offers.',
				ACTIVECAMPAIGN_FOR_WOOCOMMERCE_LOCALIZATION_DOMAIN
			);
		}
	}

	/**
	 * Better method for checking if we are on the checkout page.
	 *
	 * @return bool
	 */
	private function better_is_checkout() {
		try {
			if ( function_exists( 'is_checkout' ) && is_checkout() ) {
				return true;
			}

			$checkout_path = wp_parse_url( wc_get_checkout_url(), PHP_URL_PATH );
			if ( isset( $_SERVER['HTTP_HOST'], $_SERVER['REQUEST_URI'] ) ) {
				$host_name = esc_url_raw( wp_unslash( $_SERVER['HTTP_HOST'] ) . wp_unslash( $_SERVER['REQUEST_URI'] ) );
			} else {
				$host_name = '';
			}

			$current_url_path = wp_parse_url( "http://$host_name", PHP_URL_PATH );

			if (
				(
					null !== $checkout_path &&
					null !== $current_url_path &&
					trailingslashit( $checkout_path ) === trailingslashit( $current_url_path )
				) ||
				is_page( 'checkout' )
			) {

				return true;
			}
		} catch ( Throwable $t ) {

			$this->logger->warning(
				'There may be an issue checking for the checkout page',
				array(
					'message' => $t->getMessage(),
					'ac_code' => 'PUB_350',
				)
			);
		}

		return false;
	}

	/**
	 * Hook to load the site tracking script. Can be called from any place on the site.
	 * Call `activecampaign_for_woocommerce_load_sitetracking` to load this hook.
	 *
	 * @action activecampaign_for_woocommerce_load_sitetracking
	 */
	public function activecampaign_load_sitetracking() {
		$this->init();

		try {
			$options = $this->admin->get_local_settings();

			if (
				isset( $options['browse_tracking'] ) &&
				in_array( $options['browse_tracking'], array( 1, '1', 2, '2' ), true ) &&
				isset( $options['tracking_id'] )
			) {
				$this->activecampaign_frontend_sitetracking_scripts( $options['tracking_id'] );
			}

			if (
				isset( $options['browse_tracking'] ) &&
				in_array( $options['browse_tracking'], array( 3, '3' ), true ) &&
				isset( $options['tracking_id'] )
			) {
				$this->activecampaign_frontend_sitetracking_scripts( $options['tracking_id'], true );
			}
		} catch ( Throwable $t ) {
			$this->logger->warning(
				'Activecampaign_For_Woocommerce_Public: There was an issue with loading site tracking.',
				array(
					'message' => $t->getMessage(),
					'ac_code' => 'PUB_380',
				)
			);
		}
	}

	/**
	 * Loads in the site tracking script.
	 *
	 * @param string $tracking_id the tracking ID to use.
	 */
	public function activecampaign_frontend_sitetracking_scripts( $tracking_id, $use_staging_url = false ) {
		$ac_forms_settings = get_option( 'settings_activecampaign' ); // This is the setting from the other AC plugin

		if (
			isset( $ac_forms_settings['activecampaign_site_tracking_default'], $ac_forms_settings['site_tracking'] ) &&
			in_array( $ac_forms_settings['site_tracking'], array( '1', 1 ), true )
		) {
			// Don't perform this stuff, the other plugin will do it
			return;
		}

		wp_register_script(
			'activecampaign-for-woocommerce-site-tracking',
			plugins_url( '/js/site_tracking.js', __FILE__ ),
			array( 'jquery' ),
			$this->version,
			true
		);

		unset( $ac_forms_settings['api_url'] );
		unset( $ac_forms_settings['api_key'] );
		$current_user          = wp_get_current_user();
		$user_email            = '';
		$site_tracking_staging = 0;

		if ( isset( $current_user->data->user_email ) ) {
			$user_email = $current_user->data->user_email;
		}

		// any data we need to access in JavaScript.
		if ( empty( $tracking_id ) || ! empty( $ac_forms_settings['tracking_actid'] ) ) {
			$tracking_id = $ac_forms_settings['tracking_actid'];
		}

		if ( true === $use_staging_url ) {
			$site_tracking_staging = 1;
		}

		$data = array(
			'ac_settings' => array(
				'tracking_actid'        => $tracking_id,
				'site_tracking_default' => 1,
				'site_tracking'         => 1,
				'site_tracking_staging' => $site_tracking_staging,
			),
			'user_email'  => $user_email,
		);

		if ( isset( $ac_forms_settings['activecampaign_site_tracking_default'] ) ) {
			$data['ac_settings']['site_tracking_default'] = (int) $ac_forms_settings['activecampaign_site_tracking_default'];
		}

		wp_localize_script( 'activecampaign-for-woocommerce-site-tracking', 'php_data', $data );
		wp_enqueue_script( 'activecampaign-for-woocommerce-site-tracking' );
	}
}
