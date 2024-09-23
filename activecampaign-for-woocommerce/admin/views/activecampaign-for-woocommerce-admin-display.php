<?php

/**
 * Provide a admin area view for the plugin
 *
 * This file is used to markup the admin-facing aspects of the plugin.
 *
 * @link       https://www.activecampaign.com/
 * @since      1.5.0
 *
 * @package    Activecampaign_For_Woocommerce
 * @subpackage Activecampaign_For_Woocommerce/admin/partials
 */

$activecampaign_for_woocommerce_options = $this->get_options();
$activecampaign_for_woocommerce_storage = $this->get_storage();

// Default values
$activecampaign_for_woocommerce_api_url                       = '';
$activecampaign_for_woocommerce_connection_created_in         = 'UNKNOWN';
$activecampaign_for_woocommerce_configured                    = false;
$activecampaign_for_woocommerce_external_id                   = site_url();
$activecampaign_for_woocommerce_integration_name              = get_option( 'blogname' );
$activecampaign_for_woocommerce_integration_logo_url          = '';
$activecampaign_for_woocommerce_integration_link_url          = get_home_url();
$activecampaign_for_woocommerce_connection_id                 = 'UNKNOWN';
$activecampaign_for_woocommerce_api_key                       = '';
$activecampaign_for_woocommerce_sync_batch_runs               = 10;
$activecampaign_for_woocommerce_sync_batch_limit              = 50;
$activecampaign_for_woocommerce_debug                         = '0';
$activecampaign_for_woocommerce_debug_calls                   = '0';
$activecampaign_for_woocommerce_email_option                  = '0';
$activecampaign_for_woocommerce_abcart_wait                   = '1';
$activecampaign_for_woocommerce_optin_checkbox_text           = esc_html__( 'Keep me up to date on news and exclusive offers', ACTIVECAMPAIGN_FOR_WOOCOMMERCE_LOCALIZATION_DOMAIN );
$activecampaign_for_woocommerce_optin_checkbox_display_option = 'visible_checked_by_default';
$activecampaign_for_woocommerce_custom_email_field            = esc_html__( 'billing_email', ACTIVECAMPAIGN_FOR_WOOCOMMERCE_LOCALIZATION_DOMAIN );
$activecampaign_for_woocommerce_debug_excess                  = 0;
$activecampaign_for_woocommerce_desc_select                   = '0';

if ( is_array( $activecampaign_for_woocommerce_options ) ) {
	if ( isset( $activecampaign_for_woocommerce_options['api_url'], $activecampaign_for_woocommerce_options['api_key'] ) ) {
		$activecampaign_for_woocommerce_configured = true;
	}
}

if ( is_array( $activecampaign_for_woocommerce_storage ) ) {
	$activecampaign_for_woocommerce_connection = $this->check_for_existing_connection();

	if ( ! $activecampaign_for_woocommerce_connection ) {
		$activecampaign_for_woocommerce_connection_exists = false;
	} else {
		$activecampaign_for_woocommerce_connection_exists = true;
	}

	if ( isset( $activecampaign_for_woocommerce_storage['external_id'] ) ) {
		$activecampaign_for_woocommerce_external_id = $activecampaign_for_woocommerce_storage['external_id'];
	}

	$activecampaign_for_woocommerce_connection_created_in = 'WooCommerce';

	if ( isset( $activecampaign_for_woocommerce_storage['is_internal'] ) ) {
		if ( 1 === $activecampaign_for_woocommerce_storage['is_internal'] || '1' === $activecampaign_for_woocommerce_storage['is_internal'] ) {
			$activecampaign_for_woocommerce_connection_created_in = 'ActiveCampaign';
		}
	}

	if ( isset( $activecampaign_for_woocommerce_storage['name'] ) && ! empty( $activecampaign_for_woocommerce_storage['name'] ) ) {
		$activecampaign_for_woocommerce_integration_name = $activecampaign_for_woocommerce_storage['name'];
	}

	if ( isset( $activecampaign_for_woocommerce_storage['logo_url'] ) && ! empty( $activecampaign_for_woocommerce_storage['logo_url'] ) ) {
		if ( '/app/images/woocommerce-logo.png' === $activecampaign_for_woocommerce_storage['logo_url'] ) {
			$activecampaign_for_woocommerce_integration_logo_url = '';
		} else {
			$activecampaign_for_woocommerce_integration_logo_url = $activecampaign_for_woocommerce_storage['logo_url'];
		}
	}

	if ( isset( $activecampaign_for_woocommerce_storage['link_url'] ) ) {
		$activecampaign_for_woocommerce_integration_link_url = $activecampaign_for_woocommerce_storage['link_url'];
	}

	if ( isset( $activecampaign_for_woocommerce_storage['connection_id'] ) ) {
		$activecampaign_for_woocommerce_connection_id = $activecampaign_for_woocommerce_storage['connection_id'];
	}

	$activecampaign_for_woocommerce_settings = get_option( ACTIVECAMPAIGN_FOR_WOOCOMMERCE_DB_SETTINGS_NAME );
	$activecampaign_for_woocommerce_settings = stripslashes_deep( $activecampaign_for_woocommerce_settings );

	if ( isset( $activecampaign_for_woocommerce_settings['api_url'] ) ) {
		$activecampaign_for_woocommerce_api_url = $activecampaign_for_woocommerce_settings['api_url'];
	}

	$activecampaign_for_woocommerce_api_url = esc_html( sanitize_text_field( $activecampaign_for_woocommerce_api_url ) );

	if ( isset( $activecampaign_for_woocommerce_settings['sync_batch_runs'] ) ) {
		$activecampaign_for_woocommerce_sync_batch_runs = $activecampaign_for_woocommerce_settings['sync_batch_runs'];
	}
	$activecampaign_for_woocommerce_sync_batch_runs = esc_html( sanitize_text_field( $activecampaign_for_woocommerce_sync_batch_runs ) );

	if ( isset( $activecampaign_for_woocommerce_settings['sync_batch_limit'] ) ) {
		$activecampaign_for_woocommerce_sync_batch_limit = $activecampaign_for_woocommerce_settings['sync_batch_limit'];
	}
	$activecampaign_for_woocommerce_sync_batch_limit = esc_html( sanitize_text_field( $activecampaign_for_woocommerce_sync_batch_limit ) );

	if ( isset( $activecampaign_for_woocommerce_settings['api_key'] ) ) {
		$activecampaign_for_woocommerce_api_key = $activecampaign_for_woocommerce_settings['api_key'];
	}
	$activecampaign_for_woocommerce_api_key = esc_html( sanitize_text_field( $activecampaign_for_woocommerce_api_key ) );

	if ( isset( $activecampaign_for_woocommerce_settings['ac_debug'] ) ) {
		$activecampaign_for_woocommerce_debug = $activecampaign_for_woocommerce_settings['ac_debug'];
	}
	$activecampaign_for_woocommerce_debug = esc_html( sanitize_text_field( $activecampaign_for_woocommerce_debug ) );

	if ( isset( $activecampaign_for_woocommerce_settings['ac_debug_calls'] ) ) {
		$activecampaign_for_woocommerce_debug_calls = $activecampaign_for_woocommerce_settings['ac_debug_calls'];
	}
	$activecampaign_for_woocommerce_debug_calls = esc_html( sanitize_text_field( $activecampaign_for_woocommerce_debug_calls ) );

	if ( isset( $activecampaign_for_woocommerce_settings['ac_debug_excess'] ) ) {
		$activecampaign_for_woocommerce_debug_excess = $activecampaign_for_woocommerce_settings['ac_debug_excess'];
	}
	$activecampaign_for_woocommerce_debug_excess = esc_html( sanitize_text_field( $activecampaign_for_woocommerce_debug_excess ) );

	if ( isset( $activecampaign_for_woocommerce_settings['ac_emailoption'] ) ) {
		$activecampaign_for_woocommerce_email_option = $activecampaign_for_woocommerce_settings['ac_emailoption'];
	}

	if ( isset( $activecampaign_for_woocommerce_settings['abcart_wait'] ) ) {
		$activecampaign_for_woocommerce_abcart_wait = $activecampaign_for_woocommerce_settings['abcart_wait'];
	}
	$activecampaign_for_woocommerce_abcart_wait = esc_html( sanitize_text_field( $activecampaign_for_woocommerce_abcart_wait ) );

	if ( isset( $activecampaign_for_woocommerce_settings['optin_checkbox_text'] ) && is_string( $activecampaign_for_woocommerce_settings['optin_checkbox_text'] ) ) {
		$activecampaign_for_woocommerce_optin_checkbox_text = esc_html( sanitize_text_field( $activecampaign_for_woocommerce_settings['optin_checkbox_text'] ) );
	}

	if ( isset( $activecampaign_for_woocommerce_settings['ac_desc_select'] ) ) {
		$activecampaign_for_woocommerce_desc_select = $activecampaign_for_woocommerce_settings['ac_desc_select'];
	}

	if ( isset( $activecampaign_for_woocommerce_settings['checkbox_display_option'] ) ) {
		$activecampaign_for_woocommerce_optin_checkbox_display_option = $activecampaign_for_woocommerce_settings['checkbox_display_option'];
	}
	$activecampaign_for_woocommerce_optin_checkbox_display_option = esc_html( sanitize_text_field( $activecampaign_for_woocommerce_optin_checkbox_display_option ) );

	if ( isset( $activecampaign_for_woocommerce_settings['custom_email_field'] ) ) {
		$activecampaign_for_woocommerce_custom_email_field = $activecampaign_for_woocommerce_settings['custom_email_field'];
	}
	$activecampaign_for_woocommerce_custom_email_field = esc_html( sanitize_text_field( $activecampaign_for_woocommerce_custom_email_field ) );
}

$activecampaign_for_woocommerce_ab_cart_options = array(
	'1'  => esc_html__( '1 hour (recommended)', ACTIVECAMPAIGN_FOR_WOOCOMMERCE_LOCALIZATION_DOMAIN ),
	'6'  => esc_html__( '6 hours', ACTIVECAMPAIGN_FOR_WOOCOMMERCE_LOCALIZATION_DOMAIN ),
	'10' => esc_html__( '10 hours', ACTIVECAMPAIGN_FOR_WOOCOMMERCE_LOCALIZATION_DOMAIN ),
	'24' => esc_html__( '24 hours', ACTIVECAMPAIGN_FOR_WOOCOMMERCE_LOCALIZATION_DOMAIN ),
);

$activecampaign_for_woocommerce_ac_debug_options = array(
	// value  // label
	'1' => esc_html__( 'On', ACTIVECAMPAIGN_FOR_WOOCOMMERCE_LOCALIZATION_DOMAIN ),
	'0' => esc_html__( 'Off', ACTIVECAMPAIGN_FOR_WOOCOMMERCE_LOCALIZATION_DOMAIN ),
);

$activecampaign_for_woocommerce_checkbox_display_options = array(
	// value                          // label
	'visible_checked_by_default'   => esc_html__(
		'Visible, checked by default',
		ACTIVECAMPAIGN_FOR_WOOCOMMERCE_LOCALIZATION_DOMAIN
	),
	'visible_unchecked_by_default' => esc_html__(
		'Visible, unchecked by default',
		ACTIVECAMPAIGN_FOR_WOOCOMMERCE_LOCALIZATION_DOMAIN
	),
	'not_visible'                  => esc_html__(
		'Not visible',
		ACTIVECAMPAIGN_FOR_WOOCOMMERCE_LOCALIZATION_DOMAIN
	),
);
?>
<?php settings_errors(); ?>
<div id="activecampaign-for-woocommerce-app">
	<?php
	require plugin_dir_path( __FILE__ ) . '../partials/activecampaign-for-woocommerce-header.php';
	?>
	<?php if ( ! $activecampaign_for_woocommerce_configured ) : ?>
		<section class="no-connection">
			<h2><?php esc_html_e( 'After a few easy steps, you can automate your entire customer lifecycle.', ACTIVECAMPAIGN_FOR_WOOCOMMERCE_LOCALIZATION_DOMAIN ); ?></h2>
			<p><?php esc_html_e( 'You need to log in to ActiveCampaign and connect the WooCommerce integration within settings to complete your setup.', ACTIVECAMPAIGN_FOR_WOOCOMMERCE_LOCALIZATION_DOMAIN ); ?></p>
			<div class="no-connection-content">
				<svg xmlns="http://www.w3.org/2000/svg" class="icon" width="128" height="128" fill="none"
					 xmlns:v="https://vecta.io/nano">
					<style>
						<![CDATA[.B {fill-rule: evenodd}.C {stroke: #356ae6}  .D{stroke-width: 3}.E {fill: #c1d1f7}]]>
					</style>
					<g fill="#fff" stroke-width="2" class="C">
						<rect x="17" y="10" width="94" height="117" rx="3"/>
						<rect x="23" y="14" width="82" height="107" rx="3"/>
					</g>
					<mask id="A" fill="#fff">
						<path d="M71.826 5H93a2 2 0 0 1 2 2v11a2 2 0 0 1-2 2H35a2 2 0 0 1-2-2V7a2 2 0 0 1 2-2h21.174c.677-2.867 3.252-5 6.326-5h3c3.074 0 5.649 2.133 6.326 5z"
							  class="B"/>
					</mask>
					<path d="M71.826 5H93a2 2 0 0 1 2 2v11a2 2 0 0 1-2 2H35a2 2 0 0 1-2-2V7a2 2 0 0 1 2-2h21.174c.677-2.867 3.252-5 6.326-5h3c3.074 0 5.649 2.133 6.326 5z"
						  fill="#e3ebfc" class="B"/>
					<g fill="#356ae6">
						<path d="M71.826 5l-1.947.46.364 1.54h1.583V5zM56.174 5v2h1.583l.364-1.54L56.174 5zm15.652 2H93V3H71.826v4zM93 7h4a4 4 0 0 0-4-4v4zm0 0v11h4V7h-4zm0 11v4a4 4 0 0 0 4-4h-4zm0 0H35v4h58v-4zm-58 0h0-4a4 4 0 0 0 4 4v-4zm0 0V7h-4v11h4zm0-11h0V3a4 4 0 0 0-4 4h4zm0 0h21.174V3H35v4zm23.12-1.54C58.589 3.475 60.375 2 62.5 2v-4c-4.022 0-7.387 2.791-8.272 6.54l3.893.92zM62.5 2h3v-4h-3v4zm3 0c2.125 0 3.911 1.475 4.38 3.46l3.893-.92C72.887.791 69.522-2 65.5-2v4z"
							  mask="url(#A)"/>
						<circle cx="64" cy="7" r="2"/>
					</g>
					<path d="M34.333 54l4.407 4.333 10.593-10.667" class="C D"/>
					<path d="M58 50h30v2H58v-2zm0 5h17v2H58v-2zm36 0H77v2h17v-2z" class="B E"/>
					<path d="M34.333 78l4.407 4.333 10.593-10.667" class="C D"/>
					<path d="M72 74H58v2h14v-2zm22 0H74v2h20v-2zm-36 5h22v2H58v-2zm33 0h-9v2h9v-2z" class="B E"/>
					<path d="M34.333 102l4.407 4.333 10.593-10.666" class="C D"/>
					<path d="M58 98h20v2H58v-2zm22 0h5v2h-5v-2zm9 5H58v2h31v-2zm-8-74H47v3h34v-3zm-7 6H54v2h20v-2z"
						  class="B E"/>
				</svg>
				<div>
					<ul class="circle-numbered-checklist">
						<li>
							<span>1</span><?php esc_html_e( 'Connect the WooCommerce integration in ActiveCampaign', ACTIVECAMPAIGN_FOR_WOOCOMMERCE_LOCALIZATION_DOMAIN ); ?>
						</li>
						<li>
							<span>2</span><?php esc_html_e( 'Activate your abandoned cart', ACTIVECAMPAIGN_FOR_WOOCOMMERCE_LOCALIZATION_DOMAIN ); ?>
						</li>
						<li>
							<span>3</span><?php esc_html_e( 'Activate a cross-sell automation', ACTIVECAMPAIGN_FOR_WOOCOMMERCE_LOCALIZATION_DOMAIN ); ?>
						</li>
					</ul>
					<a href="https://www.activecampaign.com/apps/woocommerce" target="_blank" rel="noopener noreferrer"
					   class="activecampaign-for-woocommerce button"><span><?php esc_html_e( 'Complete setup in ActiveCampaign', ACTIVECAMPAIGN_FOR_WOOCOMMERCE_LOCALIZATION_DOMAIN ); ?></span>
						<svg class="is-styled css-ws9hmn" height="16px" width="16px" role="img" viewBox="0 0 16 16"
							 xmlns="http://www.w3.org/2000/svg">
							<path clip-rule="evenodd"
								  d="M5 0H0V16H16V11H14V14H2V2H5V0ZM8.99995 2H12.5857L6.29285 8.29289L7.70706 9.70711L14 3.41421V7H16V0H8.99995V2Z"
								  fill-rule="evenodd"></path>
						</svg>
					</a>
					<div>
					<div id="activecampaign-manual-mode-container">
						or, <span id="activecampaign-manual-mode">manually configure the API</span>
					</div>
					</div>
				</div>
			</div>
			<section id="manualsetup" style="display:none">
				<form method="POST" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>"
					  id="activecampaign-for-woocommerce-options-form">
					<input type="hidden" name="action" value="activecampaign_for_woocommerce_settings">
					<?php
					wp_nonce_field( 'activecampaign_for_woocommerce_settings_form', 'activecampaign_for_woocommerce_settings_nonce_field' );
					?>
					<h2>
						<?php esc_html_e( 'API Credentials', ACTIVECAMPAIGN_FOR_WOOCOMMERCE_LOCALIZATION_DOMAIN ); ?>
					</h2>
					<p>
						<?php esc_html_e( 'To find your ActiveCampaign API URL and API Key, log into your account and navigate to Settings &gt; Developer &gt; API Access.', ACTIVECAMPAIGN_FOR_WOOCOMMERCE_LOCALIZATION_DOMAIN ); ?>
					</p>
					<div>
						<label for="api_url">
							<?php esc_html_e( 'API URL:', ACTIVECAMPAIGN_FOR_WOOCOMMERCE_LOCALIZATION_DOMAIN ); ?>
						</label>
						<input type="text" name="api_url" id="api_url"
							   value="<?php echo esc_html( $activecampaign_for_woocommerce_api_url ); ?>">
					</div>
					<div>
						<label for="api_key"><?php esc_html_e( 'API key:', ACTIVECAMPAIGN_FOR_WOOCOMMERCE_LOCALIZATION_DOMAIN ); ?></label>
						<input type="text" name="api_key" id="api_key"
							   value="<?php echo esc_html( $activecampaign_for_woocommerce_api_key ); ?>">
					</div>
					<input type="hidden" name="optin_checkbox_text" value="<?php echo esc_html( $activecampaign_for_woocommerce_optin_checkbox_text ); ?>">
					<input type="hidden" name="checkbox_display_option" value="<?php echo esc_html( key( $activecampaign_for_woocommerce_checkbox_display_options ) ); ?>">
					<input type="hidden" name="abcart_wait" value="<?php echo esc_html( key( $activecampaign_for_woocommerce_ab_cart_options ) ); ?>">
					<input type="hidden" id="ac_debug" name="ac_debug" value="0">
					<input type="hidden" id="ac_debug_calls" name="ac_debug_calls" value="0">
					<input type="hidden" id="ac_debug_excess" name="ac_debug_excess" value="0">
					<input type="hidden" name="custom_email_field" id="custom_email_field" value="billing_email">
					<input type="hidden" id="sync_batch_runs" name="sync_batch_runs" value="<?php echo esc_html( $activecampaign_for_woocommerce_sync_batch_runs ); ?>">
					<input type="hidden" id="sync_batch_limit" name="sync_batch_limit" value="<?php echo esc_html( $activecampaign_for_woocommerce_sync_batch_limit ); ?>">

					<section class="mt-0">
						<hr/>
						<button class="activecampaign-for-woocommerce button button-primary">
							<?php esc_html_e( 'Update settings', ACTIVECAMPAIGN_FOR_WOOCOMMERCE_LOCALIZATION_DOMAIN ); ?>
						</button>
					</section>
				</form>
			</section>
			<p>
				<?php
				printf(
				/* translators: link in text */
					esc_html__( '%1$s %2$s to learn more about how ecommerce stores are earning revenue with ActiveCampaign.', ACTIVECAMPAIGN_FOR_WOOCOMMERCE_LOCALIZATION_DOMAIN ),
					esc_html__( 'Visit our', ACTIVECAMPAIGN_FOR_WOOCOMMERCE_LOCALIZATION_DOMAIN ),
					sprintf(
						'<a href="%s" target="_blank" rel="noopener noreferrer">%s</a>',
						esc_url( 'https://www.activecampaign.com/learn' ),
						esc_html__( 'education center', ACTIVECAMPAIGN_FOR_WOOCOMMERCE_LOCALIZATION_DOMAIN )
					)
				);
				?>
			</p>
		</section>
	<?php else : ?>
		<form method="POST" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>"
			  id="activecampaign-for-woocommerce-options-form">
			<input type="hidden" name="action" value="activecampaign_for_woocommerce_settings">
			<?php
			wp_nonce_field( 'activecampaign_for_woocommerce_settings_form', 'activecampaign_for_woocommerce_settings_nonce_field' );
			?>
			<section class="bg-white border-solid border-slate-200 p-800">
				<input type="checkbox" id="ac-config" class="hidden-accordion" checked/>
				<label for="ac-config"
					   class="accordion-title">
					<span class="accordion-icon mr-200">
					<svg width="16" height="16" viewBox="0 0 16 16" fill="none" xmlns="http://www.w3.org/2000/svg">
						<path fill-rule="evenodd" clip-rule="evenodd" d="M7.99993 10.5857L14.2928 4.29282L15.707 5.70703L7.99993 13.4141L0.292818 5.70703L1.70703 4.29282L7.99993 10.5857Z" fill="#1F2129"/>
					</svg>
					</span><?php esc_html_e( 'ActiveCampaign Configurations', ACTIVECAMPAIGN_FOR_WOOCOMMERCE_LOCALIZATION_DOMAIN ); ?>
				</label>
					<div id="activecampaign_store" class="accordion-content">
						<div>
							<div>
								<?php
								wp_nonce_field( 'activecampaign_for_woocommerce_settings_form', 'activecampaign_for_woocommerce_settings_nonce_field' );
								?>
								<section id="activecampaign_connection_list">
									<div>
										<div id="activecampaign_connection_modal" class="hidden">
											<div class="modal-content">
												<div class="notice notice-success inline" style="display:none;">
													Connection Status
												</div>
												<input type="hidden" id="connection_id" name="connection_id" value="">
												<label>Site URL <small>(Your WordPress Address URL: <?php echo esc_html( site_url() ); ?>)</small></label>
												<input type="text" id="connection_external_id" name="connection_external_id" value="">
												<label>Integration Name <small>(Used to identify your stores in ActiveCampaign. By default this is your Site Title.)</small></label>
												<input type="text" id="connection_integration_name" name="connection_integration_name" value="">
												<label>Store URL <small>(Your main store page: <?php echo esc_html( get_permalink( wc_get_page_id( 'shop' ) ) ); ?>)</small></label>
												<input type="text" id="connection_integration_link" name="connection_integration_link" value="">
												<input type="hidden" id="connection_integration_logo" name="connection_integration_logo" placeholder="Using default WooCommerce logo" value="">
												<div class="activecampaign-block-inputs">
													<a href="#" id="activecampaign-send-update-connection-button"
													   data-value="<?php echo esc_url( admin_url( 'admin-ajax.php' ) ); ?>"
													   class="activecampaign-for-woocommerce button secondary" style="display:none;">
									<span>
										<?php esc_html_e( 'Update connection', ACTIVECAMPAIGN_FOR_WOOCOMMERCE_LOCALIZATION_DOMAIN ); ?>
									</span>
													</a>
													<a href="#" id="activecampaign-send-create-connection-button"
													   data-value="<?php echo esc_url( admin_url( 'admin-ajax.php' ) ); ?>"
													   class="activecampaign-for-woocommerce button secondary" style="display:none;">
									<span>
										<?php esc_html_e( 'Create new connection', ACTIVECAMPAIGN_FOR_WOOCOMMERCE_LOCALIZATION_DOMAIN ); ?>
									</span>
													</a>
													<a href="#" id="activecampaign-cancel-connection-button"
													   data-value="<?php echo esc_url( admin_url( 'admin-ajax.php' ) ); ?>"
													   class="activecampaign-for-woocommerce button secondary">
									<span>
										Cancel
									</span>
													</a>
												</div>
											</div>
										</div>
										<div>
											<h2 style="float:left;">WooCommerce Connections</h2>
											<div style="float:right">
												<a href="#" id="activecampaign-new-connection-button"
												   data-value="<?php echo esc_url( admin_url( 'admin-ajax.php' ) ); ?>"
												   class="activecampaign-for-woocommerce">
						<span>
							<?php esc_html_e( 'Create a new connection', ACTIVECAMPAIGN_FOR_WOOCOMMERCE_LOCALIZATION_DOMAIN ); ?>
						</span>
												</a>
											</div>
											<table class="wp-list-table widefat striped table-view-list comments">
												<thead>
												<tr>
													<th scope="col" >
														Connection ID
													</th>
													<th scope="col">
														Site URL
													</th>
													<th scope="col">
														Integration Name
													</th>
													<th scope="col">
														Store URL
													</th>
													<th scope="col">
														Options
													</th>
													<th scope="col">
														Status
													</th>
												</tr>
												</thead>

												<tbody id="the-connection-list" data-wp-lists="list:connection">
												<tr><td colspan="6">Loading...</td></tr>
												</tbody>

												<tbody id="the-extra-comment-list" data-wp-lists="list:comment" style="display: none;">
												<tr class="no-items"><td class="colspanchange" colspan="5">No comments found.</td></tr>	</tbody>
											</table>
										</div>
									</div>
								</section>
							</div>
							<?php if ( $this->verify_ac_features( 'abandon' ) ) : ?>
								<h2>
									<?php esc_html_e( 'Abandoned Cart', ACTIVECAMPAIGN_FOR_WOOCOMMERCE_LOCALIZATION_DOMAIN ); ?>
								</h2>
								<p>
									<?php esc_html_e( 'How long should the store will wait before considering a cart abandoned to send to ActiveCampaign?', ACTIVECAMPAIGN_FOR_WOOCOMMERCE_LOCALIZATION_DOMAIN ); ?>
								</p>
								<p>
									<?php esc_html_e( 'For example a 1 hour setting would wait until 1 hour after the last activity on the cart and then queue the cart for abandoned cart sync to ActiveCampaign. This relies on a cron job that runs hourly. It may be longer before an abandoned cart goes from ready to synced depending on your cron frequency.', ACTIVECAMPAIGN_FOR_WOOCOMMERCE_LOCALIZATION_DOMAIN ); ?>
								</p>
								<label>
									<?php esc_html_e( 'Select wait time:', ACTIVECAMPAIGN_FOR_WOOCOMMERCE_LOCALIZATION_DOMAIN ); ?>
								</label>
								<?php foreach ( $activecampaign_for_woocommerce_ab_cart_options as $activecampaign_for_woocommerce_ab_cart_options_value => $activecampaign_for_woocommerce_ab_cart_options_option ) : ?>
									<label class="radio">
										<input type="radio"
											   id="abcart_wait<?php echo esc_html( $activecampaign_for_woocommerce_ab_cart_options_value ); ?>"
											   name="abcart_wait"
											   value="<?php echo esc_html( $activecampaign_for_woocommerce_ab_cart_options_value ); ?>"
											<?php
											if ( (string) $activecampaign_for_woocommerce_ab_cart_options_value === $activecampaign_for_woocommerce_abcart_wait ) {
												echo 'checked';
											}
											?>
										>
										<?php echo esc_html( $activecampaign_for_woocommerce_ab_cart_options_option ); ?>
									</label>
								<?php endforeach; ?>
							<?php endif; ?>
						</div>
						<hr/>
						<div>
							<h2>
								<?php esc_html_e( 'Opt-in Checkbox', ACTIVECAMPAIGN_FOR_WOOCOMMERCE_LOCALIZATION_DOMAIN ); ?>
							</h2>
							<p>
								<?php esc_html_e( 'Configure what text should appear next to the opt-in checkbox, and whether that checkbox should be visible and checked by default.', ACTIVECAMPAIGN_FOR_WOOCOMMERCE_LOCALIZATION_DOMAIN ); ?>
							</p>
							<div>
								<label for="optin_checkbox_text">
									<?php esc_html_e( 'Checkbox text:', ACTIVECAMPAIGN_FOR_WOOCOMMERCE_LOCALIZATION_DOMAIN ); ?>
								</label>
								<input type="text" name="optin_checkbox_text" id="optin_checkbox_text"
									   value="<?php echo esc_html( $activecampaign_for_woocommerce_optin_checkbox_text ); ?>">
							</div>
							<h3>
								<?php esc_html_e( 'Checkbox display options:', ACTIVECAMPAIGN_FOR_WOOCOMMERCE_LOCALIZATION_DOMAIN ); ?>
							</h3>
							<?php foreach ( $activecampaign_for_woocommerce_checkbox_display_options as $activecampaign_for_woocommerce_checkbox_display_options_value => $activecampaign_for_woocommerce_checkbox_display_options_option ) : ?>
								<label class="radio"
									   for="checkbox_display_option_<?php echo esc_html( $activecampaign_for_woocommerce_checkbox_display_options_value ); ?>">
									<input type="radio"
										   id="checkbox_display_option_<?php echo esc_html( $activecampaign_for_woocommerce_checkbox_display_options_value ); ?>"
										   name="checkbox_display_option"
										   value="<?php echo esc_html( $activecampaign_for_woocommerce_checkbox_display_options_value ); ?>"
										<?php
										if ( $activecampaign_for_woocommerce_checkbox_display_options_value === $activecampaign_for_woocommerce_optin_checkbox_display_option ) {
											echo esc_html( 'checked' );
										}
										?>
									>
									<?php echo esc_html( $activecampaign_for_woocommerce_checkbox_display_options_option ); ?>
								</label>
							<?php endforeach; ?>
						</div>
						<hr/>
						<?php if ( $this->verify_ac_features( 'abandon' ) ) : ?>
							<div>
								<h2>
									<?php esc_html_e( 'Historical Sync Options', ACTIVECAMPAIGN_FOR_WOOCOMMERCE_LOCALIZATION_DOMAIN ); ?>
								</h2>
								<div>
									Change these settings according to your hosting capabilities. Lower numbers will take longer but consume less resources.
								</div>
								<div>
									<label>Runs Per Batch: <span class="help"> (ex: Every time the sync runs 10 batch groups of 50 will run)</span></label>
									<input type="number" name="sync_batch_runs" id="sync_batch_runs" min="1" max="40"
										   value="<?php echo esc_html( $activecampaign_for_woocommerce_sync_batch_runs ); ?>">
								</div>
								<div>
									<label>Bulk Sync Batch Limit: <span class="help">(num of records synced to ActiveCampaign at a time)</span></label>
									<input type="number" name="sync_batch_limit" id="sync_batch_limit" min="1" max="50"
										   value="<?php echo esc_html( $activecampaign_for_woocommerce_sync_batch_limit ); ?>"> Max 50
								</div>
							</div>
						<?php endif; ?>
						<hr/>
						<?php if ( $this->verify_ac_features( 'product' ) ) : ?>
							<div>
								<h2>
									<?php esc_html_e( 'Product Sync Options', ACTIVECAMPAIGN_FOR_WOOCOMMERCE_LOCALIZATION_DOMAIN ); ?>
								</h2>
								<label>
									<?php esc_html_e( 'Product Description:', ACTIVECAMPAIGN_FOR_WOOCOMMERCE_LOCALIZATION_DOMAIN ); ?>
								</label>
								<label class="radio">
									<input type="radio" id="ac_desc_select0" name="ac_desc_select" value="0"
										<?php
										if ( '0' === $activecampaign_for_woocommerce_desc_select ) {
											echo 'checked';
										}
										?>
									> Use full description only
									<div style="padding-left: 23px;font-style:italic;">
										My product descriptions can be assumed to be readable in a small email content block
									</div>
								</label>
								<label class="radio">
									<input type="radio" id="ac_desc_select1" name="ac_desc_select" value="1"
										<?php
										if ( '1' === $activecampaign_for_woocommerce_desc_select ) {
											echo 'checked';
										}
										?>
									> Use short description only

									<div style="padding-left: 23px;font-style:italic;">
										(Will sync with description empty if short description is not included)<br/>
										My full length descriptions are too long for a small email content block, I need the short description
									</div>
								</label>
								<label class="radio">
									<input type="radio" id="ac_desc_select2" name="ac_desc_select" value="2"
										<?php
										if ( '2' === $activecampaign_for_woocommerce_desc_select ) {
											echo 'checked';
										}
										?>
									> Use short description but fall back to full description. <small>[Suggested]</small>
									<div style="padding-left: 23px;font-style:italic;">
										(If the short description is not included it will fall back to the full description.)<br/>
										I prefer the short description, but don’t want anything showing up empty
									</div>
								</label>
							</div>
						<?php endif; ?>
					</div>
			</section>

			<section id="activecampaign_connection" class="advanced bg-white border-solid border-slate-200 p-800"
					 label="<?php esc_html_e( 'Connection', ACTIVECAMPAIGN_FOR_WOOCOMMERCE_LOCALIZATION_DOMAIN ); ?>">
				<input type="checkbox" id="advanced" class="hidden-accordion"/>
				<label for="advanced"
					   class="accordion-title">
					<span class="accordion-icon mr-200">
					<svg width="16" height="16" viewBox="0 0 16 16" fill="none" xmlns="http://www.w3.org/2000/svg">
						<path fill-rule="evenodd" clip-rule="evenodd" d="M7.99993 10.5857L14.2928 4.29282L15.707 5.70703L7.99993 13.4141L0.292818 5.70703L1.70703 4.29282L7.99993 10.5857Z" fill="#1F2129"/>
					</svg>
					</span><?php esc_html_e( 'Advanced Settings', ACTIVECAMPAIGN_FOR_WOOCOMMERCE_LOCALIZATION_DOMAIN ); ?>
				</label>
				<div class="accordion-content">
					<h2>
						<?php esc_html_e( 'API Credentials', ACTIVECAMPAIGN_FOR_WOOCOMMERCE_LOCALIZATION_DOMAIN ); ?>
					</h2>
					<p>
						<?php esc_html_e( 'To find your ActiveCampaign API URL and API Key, log into your account and navigate to Settings &gt; Developer &gt; API Access.', ACTIVECAMPAIGN_FOR_WOOCOMMERCE_LOCALIZATION_DOMAIN ); ?>
					</p>
					<div>
						<label for="api_url">
							<?php esc_html_e( 'API URL:', ACTIVECAMPAIGN_FOR_WOOCOMMERCE_LOCALIZATION_DOMAIN ); ?>
						</label>
						<input type="text" name="api_url" id="api_url"
							   value="<?php echo esc_html( $activecampaign_for_woocommerce_api_url ); ?>">
					</div>
					<div>
						<label for="api_key"><?php esc_html_e( 'API key:', ACTIVECAMPAIGN_FOR_WOOCOMMERCE_LOCALIZATION_DOMAIN ); ?></label>
						<input type="text" name="api_key" id="api_key"
							   value="<?php echo esc_html( $activecampaign_for_woocommerce_api_key ); ?>">
					</div>
					<a href="#" id="activecampaign-update-api-button"
					   data-value="<?php echo esc_url( admin_url( 'admin-ajax.php' ) ); ?>"
					   class="activecampaign-for-woocommerce button secondary"><span><?php esc_html_e( 'Test connection', ACTIVECAMPAIGN_FOR_WOOCOMMERCE_LOCALIZATION_DOMAIN ); ?></span></a>
					<hr/>
					<?php $this->load_status_mapping_block(); ?>
					<hr/>
					<div>
						<label>
							<?php esc_html_e( 'Activate debugging:', ACTIVECAMPAIGN_FOR_WOOCOMMERCE_LOCALIZATION_DOMAIN ); ?>
						</label>
						<label class="radio">
							<input type="radio" id="ac_debug0" name="ac_debug" value="0"
								<?php
								if ( '0' === $activecampaign_for_woocommerce_debug ) {
									echo 'checked';
								}
								?>
							> Off
						</label>
						<label class="radio">
							<input type="radio" id="ac_debug1" name="ac_debug" value="1"
								<?php
								if ( '1' === $activecampaign_for_woocommerce_debug ) {
									echo 'checked';
								}
								?>
							> On
						</label>
					</div>
					<div>
						<label>
							<?php esc_html_e( 'Activate AC call debugging:', ACTIVECAMPAIGN_FOR_WOOCOMMERCE_LOCALIZATION_DOMAIN ); ?>
						</label>
						<label class="radio">
							<input type="radio" id="ac_debug0" name="ac_debug_calls" value="0"
								<?php
								if ( '0' === $activecampaign_for_woocommerce_debug_calls ) {
									echo 'checked';
								}
								?>
							> Off
						</label>
						<label class="radio">
							<input type="radio" id="ac_debug1" name="ac_debug_calls" value="1"
								<?php
								if ( '1' === $activecampaign_for_woocommerce_debug_calls ) {
									echo 'checked';
								}
								?>
							> On
						</label>
					</div>
					<div>
						<label>
							<?php esc_html_e( 'Activate AC excessive call debugging:', ACTIVECAMPAIGN_FOR_WOOCOMMERCE_LOCALIZATION_DOMAIN ); ?>
							<?php esc_html_e( '(Only check for debugging cron or other excess repeat messaging. Not recommended to keep enabled.)', ACTIVECAMPAIGN_FOR_WOOCOMMERCE_LOCALIZATION_DOMAIN ); ?>
						</label>
						<label class="radio">
							<input type="radio" id="ac_debuge0" name="ac_debug_excess" value="0"
								<?php
								if ( '0' === $activecampaign_for_woocommerce_debug_excess ) {
									echo 'checked';
								}
								?>
							> Off
						</label>
						<label class="radio">
							<input type="radio" id="ac_debuge1" name="ac_debug_excess" value="1"
								<?php
								if ( '1' === $activecampaign_for_woocommerce_debug_excess ) {
									echo 'checked';
								}
								?>
							> On
						</label>
					</div>
					<div>
						<label for="custom_email_field">
							<?php esc_html_e( 'Custom email field:', ACTIVECAMPAIGN_FOR_WOOCOMMERCE_LOCALIZATION_DOMAIN ); ?>
						</label>
						<label class="radio">
							<input type="radio"
								   id="ac_emailoption0"
								   name="ac_emailoption"
								   value="0"
								<?php
								if ( '0' === $activecampaign_for_woocommerce_email_option ) {
									echo 'checked';
								}
								?>
							>
							<?php esc_html_e( 'Default (billing_email)', ACTIVECAMPAIGN_FOR_WOOCOMMERCE_LOCALIZATION_DOMAIN ); ?>
						</label>
						<label class="radio">
							<input type="radio"
								   id="ac_emailoption1"
								   name="ac_emailoption"
								   value="1"
								<?php
								if ( '1' === $activecampaign_for_woocommerce_email_option ) {
									echo 'checked';
								}
								?>
							>
							<?php esc_html_e( 'Customize', ACTIVECAMPAIGN_FOR_WOOCOMMERCE_LOCALIZATION_DOMAIN ); ?>
						</label>
						<div id="custom-email-option-set">
							<input type="text" name="custom_email_field" id="custom_email_field"
								   value="<?php echo esc_html( $activecampaign_for_woocommerce_custom_email_field ); ?>"
								   placeholder="billing_email">
							<p><?php esc_html_e( 'Default: billing_email (expects ID as input, do not include #)', ACTIVECAMPAIGN_FOR_WOOCOMMERCE_LOCALIZATION_DOMAIN ); ?></p>
							<p><?php esc_html_e( 'Warning: Advanced users only. Do not set this unless you are having issues with the abandoned cart not triggering on your email field.', ACTIVECAMPAIGN_FOR_WOOCOMMERCE_LOCALIZATION_DOMAIN ); ?></p>
							<p><?php esc_html_e( 'If you have a forced registration or a custom theme for checkout you can change which field we bind on here.', ACTIVECAMPAIGN_FOR_WOOCOMMERCE_LOCALIZATION_DOMAIN ); ?></p>
						</div>
					</div>
				</div>
			</section>

			<section class="bg-white border-solid border-slate-200 p-800">
				<input type="checkbox" id="troubleshooting" class="hidden-accordion"/>
				<label for="troubleshooting"
					   class="accordion-title">
					<span class="accordion-icon mr-200">
					<svg width="16" height="16" viewBox="0 0 16 16" fill="none" xmlns="http://www.w3.org/2000/svg">
						<path fill-rule="evenodd" clip-rule="evenodd" d="M7.99993 10.5857L14.2928 4.29282L15.707 5.70703L7.99993 13.4141L0.292818 5.70703L1.70703 4.29282L7.99993 10.5857Z" fill="#1F2129"/>
					</svg>
					</span><?php esc_html_e( 'Troubleshooting', ACTIVECAMPAIGN_FOR_WOOCOMMERCE_LOCALIZATION_DOMAIN ); ?>
				</label>
				<div class="accordion-content">
					<div class="col-1">
						<div class="card">
							<h2>
								<?php esc_html_e( 'Reset ActiveCampaign Account Features', ACTIVECAMPAIGN_FOR_WOOCOMMERCE_LOCALIZATION_DOMAIN ); ?>
							</h2>
							<p>
								<?php esc_html_e( 'If you are missing any menu items or cannot access historical sync, product sync you may want to resync account features.', ACTIVECAMPAIGN_FOR_WOOCOMMERCE_LOCALIZATION_DOMAIN ); ?>
							</p>
							<div>
								<div id="activecampaign-run-resync-plugin-features" class="button">
									Resync Features
								</div>
							</div>
						</div>
					</div>
				</div>
				<div class="accordion-content">
					<div class="col-1">
						<div class="card">
							<h2>
								<?php esc_html_e( 'Reset Plugin Configuration', ACTIVECAMPAIGN_FOR_WOOCOMMERCE_LOCALIZATION_DOMAIN ); ?>
							</h2>
							<p>
								<?php esc_html_e( 'If you would like to clear all configurations stored for the ActiveCampaign for WooCommerce but retain data you can use this reset method. Please reach out to support before trying this option.', ACTIVECAMPAIGN_FOR_WOOCOMMERCE_LOCALIZATION_DOMAIN ); ?>
							</p>
							<p>
								<i>Resets the plugin without erasing abandoned carts, logs, or tables.</i>
							</p>
							<div>
								<div id="activecampaign-run-clear-plugin-settings" class="button">
									Clear All Settings
								</div>
								<div id="activecampaign-run-clear-plugin-settings-status"></div>
							</div>
						</div>
						<div class="card">
							<h2>
								<?php esc_html_e( 'Repair Connection ID', ACTIVECAMPAIGN_FOR_WOOCOMMERCE_LOCALIZATION_DOMAIN ); ?>
							</h2>
							<?php if ( isset( $activecampaign_for_woocommerce_storage ) && ! empty( $activecampaign_for_woocommerce_storage ) ) : ?>
								<p>
									<?php
									esc_html_e( 'ActiveCampaign connection ID: ', ACTIVECAMPAIGN_FOR_WOOCOMMERCE_LOCALIZATION_DOMAIN );
									?>

									<?php if ( ! isset( $activecampaign_for_woocommerce_connection_id ) ) : ?>
										<?php esc_html_e( 'Error: No connection ID found in settings! ', ACTIVECAMPAIGN_FOR_WOOCOMMERCE_LOCALIZATION_DOMAIN ); ?>
									<?php else : ?>
										<?php echo esc_html( $activecampaign_for_woocommerce_connection_id ); ?>
									<?php endif; ?>
								</p>

								<?php if ( isset( $activecampaign_for_woocommerce_external_id ) ) : ?>
									<p>
										<?php esc_html_e( 'ActiveCampaign external ID: ', ACTIVECAMPAIGN_FOR_WOOCOMMERCE_LOCALIZATION_DOMAIN ); ?>
										<?php echo esc_html( $activecampaign_for_woocommerce_external_id ); ?>
									</p>
								<?php endif; ?>

								<?php if ( isset( $activecampaign_for_woocommerce_integration_name ) ) : ?>
									<p>
										<?php esc_html_e( 'Connection name: ', ACTIVECAMPAIGN_FOR_WOOCOMMERCE_LOCALIZATION_DOMAIN ); ?>
										<?php echo esc_html( $activecampaign_for_woocommerce_integration_name ); ?>
									</p>
								<?php endif; ?>
								<hr/>
								<div>
									<?php esc_html_e( ' If the connection external ID does not match one of these you may experience issues. ', ACTIVECAMPAIGN_FOR_WOOCOMMERCE_LOCALIZATION_DOMAIN ); ?>
								</div>
								<div>
									Site URL: <?php echo esc_html( get_site_url() ); ?>
								</div>
								<div>
									Home URL: <?php echo esc_html( get_home_url() ); ?>
								</div>
							<?php else : ?>
								No connection!
							<?php endif; ?>
							<hr/>
							<p>
								<?php esc_html_e( 'This button should only be used if the health check is reporting an error and you are facing issues with orders not properly sending to ActiveCampaign. Please reach out to support before trying this option.', ACTIVECAMPAIGN_FOR_WOOCOMMERCE_LOCALIZATION_DOMAIN ); ?>
							</p>
							<div>
								<div id="activecampaign-run-fix-connection" class="button">
									Repair Connection IDs
								</div>
								<div id="activecampaign-run-fix-connection-status"></div>
							</div>
						</div>
					</div>
				</div>
			</section>
		</form>
	<?php endif; ?>
</div>
