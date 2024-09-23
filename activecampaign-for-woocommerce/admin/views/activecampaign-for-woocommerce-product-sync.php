<?php

/**
 * Provide an admin product sync view for the plugin
 *
 * @link       https://www.activecampaign.com/
 * @since      1.9.0
 *
 * @package    Activecampaign_For_Woocommerce
 * @subpackage Activecampaign_For_Woocommerce/admin/partials
 */
?>
<style>
	.sync-run-status{display:none;}
</style>
<div id="activecampaign-for-woocommerce-product-sync" class="wrap">
	<div class="notice notice-info">
		<h3>
			Introducing Product Catalog sync!
		</h3>
		<p>
			Sync your product data to power the campaign product block like never before. If you have connected manually through the ActiveCampaign for WooCommerce plugin, you may not see categories sync, but know we are actively working to resolve this!
		</p>
	</div>
	<h1>
		<?php
		esc_html_e( 'ActiveCampaign for WooCommerce Product Sync', ACTIVECAMPAIGN_FOR_WOOCOMMERCE_LOCALIZATION_DOMAIN );
		?>
	</h1>
	<section>
		<div class="card max-w-none">
			<div>
				<div id="sync-start-section">
					<div>
						<form method="post"
							  action="<?php echo esc_html( $activecampaign_for_woocommerce_product_sync_data['page_url'] ); ?>">
							<?php
							wp_nonce_field( 'activecampaign_for_woocommerce_product_sync_form', 'activecampaign_for_woocommerce_nonce_field' );
							?>

							<div class="mb-500">
								Product data sync times vary based on the amount of product you're syncing in. you could check back later when sync is completed
							</div>

							<div class="mb-500">
								<button type="button" id="activecampaign-run-product-sync" class="activecampaign-for-woocommerce button button-primary" style="padding: 0 10px">
									<?php
									esc_html_e(
										'Resync Products',
										ACTIVECAMPAIGN_FOR_WOOCOMMERCE_LOCALIZATION_DOMAIN
									);
									?>
								</button>
								<button id="activecampaign-reset-product-sync" class="button" type="activecampaign-for-woocommerce button">
									<?php
									esc_html_e(
										'Reset Sync Status',
										ACTIVECAMPAIGN_FOR_WOOCOMMERCE_LOCALIZATION_DOMAIN
									);
									?>
								</button>
							</div>
							<div>
								<div>
									<label>Batch Limit: </label>
									<select name="activecampaign-product-sync-limit"
											id="activecampaign-product-sync-limit">
										<option value=5>1</option>
										<option value=5>5</option>
										<option value=10>10</option>
										<option selected="selected" value=20>20</option>
									</select>
								</div>
							</div>
						</form>
					</div>
				</div>
			</div>
		</div>
		<div class="card max-w-none">
			<div class="clear">
				<h3>Sync Status</h3>
				<hr/>
				<div id="sync-run-section">
					<div id="activecampaign-product-sync-run-shortly" class="sync-run-status" style="border:1px dashed #2271b1; padding:5px 10px; display:none;">
						<span></span>
						<?php
						esc_html_e(
							'product sync event(s) scheduled, waiting for cron to run...',
							ACTIVECAMPAIGN_FOR_WOOCOMMERCE_LOCALIZATION_DOMAIN
						);
						?>
					</div>
					<div id="activecampaign-product-sync-stop-requested" class="sync-run-status" style="display:none;">
						<?php
						esc_html_e(
							'Attempting to stop the process...',
							ACTIVECAMPAIGN_FOR_WOOCOMMERCE_LOCALIZATION_DOMAIN
						);
						?>
					</div>
					<div id="activecampaign-run-product-wait" class="sync-run-status">
						Please wait...
					</div>
					<div id="activecampaign-run-product-sync-running" class="sync-run-status">
						Status: Sync Running
					</div>
					<div id="activecampaign-run-product-sync-reset" class="sync-run-status">
						Status: Sync Reset
					</div>
					<div id="activecampaign-run-product-sync-cancelled" class="sync-run-status">
						Status: Sync Cancelled
					</div>
					<div id="activecampaign-run-product-sync-sync-started" class="sync-run-status last-sync-status">
						Latest Sync Started: <span>placeholder</span>
					</div>
					<div id="activecampaign-run-product-sync-finished" class="sync-run-status last-sync-status">
						Latest Sync Finished: <span>placeholder</span>
					</div>
					<div id="activecampaign-run-product-sync-last-update" class="sync-run-status">
						Last Status Update: <span>placeholder</span>
					</div>
					<div id="activecampaign-run-product-sync-current-record" class="sync-run-status">
						Records Processed: <span>placeholder</span>
					</div>
					<div id="activecampaign-run-product-sync-start-record" class="sync-run-status">
						Group Being Processed: <span>placeholder</span>
					</div>
					<div id="activecampaign-run-product-sync-fails" class="sync-run-status last-sync-status">
						Failed records: <span>placeholder</span>
					</div>
					<?php if ( $activecampaign_for_woocommerce_product_sync_data['options']['ac_debug'] ) : ?>
						<div>
							<p>Debug info: <span id="activecampaign-run-product-sync-debug">placeholder</span>
							</p>
						</div>
					<?php endif; ?>
					</div>
					<div>
						<button id="activecampaign-cancel-product-sync" class="button">
							<?php
							esc_html_e(
								'Cancel Sync Process',
								ACTIVECAMPAIGN_FOR_WOOCOMMERCE_LOCALIZATION_DOMAIN
							);
							?>
						</button>
					</div>
				</div>

			</div>
		</div>
	</section>
</div>
