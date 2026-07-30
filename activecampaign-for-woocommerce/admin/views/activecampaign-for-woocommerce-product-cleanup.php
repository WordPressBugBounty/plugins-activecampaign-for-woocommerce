<?php
/**
 * Provide an admin product cleanup view for the plugin.
 *
 * @link       https://www.activecampaign.com/
 *
 * @package    Activecampaign_For_Woocommerce
 * @subpackage Activecampaign_For_Woocommerce/admin/partials
 */
?>
<style>
	.cleanup-run-status{display:none;}
</style>
<div id="activecampaign-for-woocommerce-product-cleanup" class="wrap">
	<div class="notice notice-info">
		<h3>
			<?php esc_html_e( 'Product Cleanup', ACTIVECAMPAIGN_FOR_WOOCOMMERCE_LOCALIZATION_DOMAIN ); ?>
		</h3>
		<p>
			<?php
			esc_html_e(
				'This job fetches all products currently stored in ActiveCampaign and compares them against products and product variations in WooCommerce. Any AC product that no longer exists in WooCommerce, or has been moved to the trash, will be soft-deleted from ActiveCampaign.',
				ACTIVECAMPAIGN_FOR_WOOCOMMERCE_LOCALIZATION_DOMAIN
			);
			?>
		</p>
	</div>
	<h1>
		<?php esc_html_e( 'ActiveCampaign for WooCommerce Product Cleanup', ACTIVECAMPAIGN_FOR_WOOCOMMERCE_LOCALIZATION_DOMAIN ); ?>
	</h1>
	<section>
		<div class="card max-w-none">
			<div>
				<div id="cleanup-start-section">
					<form method="post"
						  action="<?php echo esc_url( $activecampaign_for_woocommerce_product_cleanup_data['page_url'] ); ?>">
						<?php
						wp_nonce_field( 'activecampaign_for_woocommerce_product_cleanup_form', 'activecampaign_for_woocommerce_nonce_field' );
						?>

						<div class="mb-500">
							<?php
							esc_html_e(
								'Cleanup time depends on the number of products stored in AC. You can safely close this page and check back later — the job continues to run in the background.',
								ACTIVECAMPAIGN_FOR_WOOCOMMERCE_LOCALIZATION_DOMAIN
							);
							?>
							<br/>
							<?php
							esc_html_e(
								'Products removed from WooCommerce (deleted or trashed) will be soft-deleted from COFE. No WooCommerce data is modified by this job.',
								ACTIVECAMPAIGN_FOR_WOOCOMMERCE_LOCALIZATION_DOMAIN
							);
							?>
						</div>

						<div class="mb-500">
							<button type="button" id="activecampaign-run-product-cleanup" class="activecampaign-for-woocommerce button button-primary" style="padding: 0 10px">
								<?php esc_html_e( 'Run Cleanup', ACTIVECAMPAIGN_FOR_WOOCOMMERCE_LOCALIZATION_DOMAIN ); ?>
							</button>
							<button id="activecampaign-reset-product-cleanup" class="button" type="button">
								<?php esc_html_e( 'Reset Cleanup Status', ACTIVECAMPAIGN_FOR_WOOCOMMERCE_LOCALIZATION_DOMAIN ); ?>
							</button>
						</div>
					</form>
				</div>
			</div>
		</div>

		<div class="card max-w-none">
			<div class="clear">
				<h3><?php esc_html_e( 'Cleanup Status', ACTIVECAMPAIGN_FOR_WOOCOMMERCE_LOCALIZATION_DOMAIN ); ?></h3>
				<hr/>
				<div id="cleanup-run-section">
					<div id="activecampaign-product-cleanup-run-shortly" class="cleanup-run-status" style="border:1px dashed #2271b1; padding:5px 10px; display:none;">
						<span>-</span>
						<?php
						esc_html_e(
							'product cleanup event(s) scheduled, waiting for cron to run...',
							ACTIVECAMPAIGN_FOR_WOOCOMMERCE_LOCALIZATION_DOMAIN
						);
						?>
					</div>
					<div id="activecampaign-product-cleanup-stop-requested" class="cleanup-run-status" style="display:none;">
						<?php esc_html_e( 'Attempting to stop the process...', ACTIVECAMPAIGN_FOR_WOOCOMMERCE_LOCALIZATION_DOMAIN ); ?>
					</div>
					<div id="activecampaign-run-product-cleanup-wait" class="cleanup-run-status">
						<?php esc_html_e( 'Please wait...', ACTIVECAMPAIGN_FOR_WOOCOMMERCE_LOCALIZATION_DOMAIN ); ?>
					</div>
					<div id="activecampaign-run-product-cleanup-running" class="cleanup-run-status">
						<?php esc_html_e( 'Status: Cleanup Running', ACTIVECAMPAIGN_FOR_WOOCOMMERCE_LOCALIZATION_DOMAIN ); ?>
					</div>
					<div id="activecampaign-run-product-cleanup-reset" class="cleanup-run-status">
						<?php esc_html_e( 'Status: Cleanup Reset', ACTIVECAMPAIGN_FOR_WOOCOMMERCE_LOCALIZATION_DOMAIN ); ?>
					</div>
					<div id="activecampaign-run-product-cleanup-cancelled" class="cleanup-run-status">
						<?php esc_html_e( 'Status: Cleanup Cancelled', ACTIVECAMPAIGN_FOR_WOOCOMMERCE_LOCALIZATION_DOMAIN ); ?>
					</div>
					<div id="activecampaign-run-product-cleanup-started" class="cleanup-run-status last-cleanup-status">
						<?php esc_html_e( 'Latest Cleanup Started:', ACTIVECAMPAIGN_FOR_WOOCOMMERCE_LOCALIZATION_DOMAIN ); ?> <span>placeholder</span>
					</div>
					<div id="activecampaign-run-product-cleanup-finished" class="cleanup-run-status last-cleanup-status">
						<?php esc_html_e( 'Latest Cleanup Finished:', ACTIVECAMPAIGN_FOR_WOOCOMMERCE_LOCALIZATION_DOMAIN ); ?> <span>placeholder</span>
					</div>
					<div id="activecampaign-run-product-cleanup-last-update" class="cleanup-run-status">
						<?php esc_html_e( 'Last Status Update:', ACTIVECAMPAIGN_FOR_WOOCOMMERCE_LOCALIZATION_DOMAIN ); ?> <span>placeholder</span>
					</div>
					<div id="activecampaign-run-product-cleanup-cofe-total" class="cleanup-run-status">
						<?php esc_html_e( 'Products in COFE (total):', ACTIVECAMPAIGN_FOR_WOOCOMMERCE_LOCALIZATION_DOMAIN ); ?> <span>placeholder</span>
					</div>
					<div id="activecampaign-run-product-cleanup-cofe-eligible" class="cleanup-run-status">
						<?php esc_html_e( 'Eligible for cleanup (not already deleted in COFE):', ACTIVECAMPAIGN_FOR_WOOCOMMERCE_LOCALIZATION_DOMAIN ); ?> <span>placeholder</span>
					</div>
					<div id="activecampaign-run-product-cleanup-current-record" class="cleanup-run-status">
						<?php esc_html_e( 'Records Processed:', ACTIVECAMPAIGN_FOR_WOOCOMMERCE_LOCALIZATION_DOMAIN ); ?> <span>placeholder</span>
					</div>
					<div id="activecampaign-run-product-cleanup-deleted" class="cleanup-run-status">
						<?php esc_html_e( 'Products Soft-Deleted:', ACTIVECAMPAIGN_FOR_WOOCOMMERCE_LOCALIZATION_DOMAIN ); ?> <span>placeholder</span>
					</div>
					<div id="activecampaign-run-product-cleanup-fails" class="cleanup-run-status last-cleanup-status">
						<?php esc_html_e( 'Failed records:', ACTIVECAMPAIGN_FOR_WOOCOMMERCE_LOCALIZATION_DOMAIN ); ?> <span>placeholder</span>
					</div>
					<?php if ( ! empty( $activecampaign_for_woocommerce_product_cleanup_data['options']['ac_debug'] ) ) : ?>
						<div>
							<p><?php esc_html_e( 'Debug info:', ACTIVECAMPAIGN_FOR_WOOCOMMERCE_LOCALIZATION_DOMAIN ); ?> <span id="activecampaign-run-product-cleanup-debug">placeholder</span></p>
						</div>
					<?php endif; ?>
				</div>
				<div>
					<button id="activecampaign-cancel-product-cleanup" class="button">
						<?php esc_html_e( 'Cancel Cleanup Process', ACTIVECAMPAIGN_FOR_WOOCOMMERCE_LOCALIZATION_DOMAIN ); ?>
					</button>
				</div>
			</div>
		</div>
	</section>
</div>
