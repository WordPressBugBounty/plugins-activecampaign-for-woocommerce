<?php

/**
 * Notice for admin
 *
 * @package    Activecampaign_For_Woocommerce
 */

?>
<div id="activecampaign-for-woocommerce-notice-info" class="notice notice-success activecampaign-for-woocommerce-info">
	<svg xmlns="http://www.w3.org/2000/svg" width="30" height="30" xmlns:v="https://vecta.io/nano"
		 class="activecampaign-for-woocommerce-info-icon">
		<circle fill="#004cff" cx="15" cy="15" r="15"/>
		<path d="M20.089 14.966l-8.777 5.829c-.407.271-.61.712-.61 1.152v1.457l10.641-6.981c.474-.339.779-.881.779-1.457a1.73 1.73 0 0 0-.779-1.457L10.702 6.596v1.355c0 .475.237.915.61 1.152l8.777 5.863zm-5.287.475c.474.305 1.085.305 1.559 0l.745-.508-5.558-3.762c-.339-.237-.847 0-.847.44v1.118l2.881 1.932 1.22.779z"
			  fill="#fff"/>
	</svg>
	<div class="activecampaign-for-woocommerce-info-content">
		<h2>
			<?php esc_html_e( 'Welcome to ActiveCampaign for WooCommerce', ACTIVECAMPAIGN_FOR_WOOCOMMERCE_LOCALIZATION_DOMAIN ); ?>
		</h2>
		<p>
			<?php esc_html_e( 'Complete your setup by connecting the WooCommerce integration in ActiveCampaign.', ACTIVECAMPAIGN_FOR_WOOCOMMERCE_LOCALIZATION_DOMAIN ); ?>
		</p>
		<a href="https://www.activecampaign.com/apps/woocommerce" target="_blank" rel="noopener noreferrer"
		   class="activecampaign-for-woocommerce button"><span><?php esc_html_e( 'Complete setup in ActiveCampaign', ACTIVECAMPAIGN_FOR_WOOCOMMERCE_LOCALIZATION_DOMAIN ); ?></span>
			<svg class="is-styled css-ws9hmn" height="16px" width="16px" role="img" viewBox="0 0 16 16"
				 xmlns="http://www.w3.org/2000/svg">
				<path clip-rule="evenodd"
					  d="M5 0H0V16H16V11H14V14H2V2H5V0ZM8.99995 2H12.5857L6.29285 8.29289L7.70706 9.70711L14 3.41421V7H16V0H8.99995V2Z"
					  fill-rule="evenodd"></path>
			</svg>
		</a>
		<button type="button" class="notice-dismiss"><span class="screen-reader-text">Dismiss this notice.</span>
		</button>
	</div>
</div>
<script type="text/javascript">
	jQuery(document).ready(function ($) {
		$("#activecampaign-for-woocommerce-notice-info .notice-dismiss").click(function (e) {
			e.preventDefault();
			$("#activecampaign-for-woocommerce-notice-info").hide();
			$.ajax({
				url: ajaxurl,
				data: {
					action: "activecampaign_for_woocommerce_dismiss_plugin_notice"
				}
			});
		});
	});
</script>
