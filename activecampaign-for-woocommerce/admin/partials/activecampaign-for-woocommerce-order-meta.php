<?php
/**
 * Provide an admin metadata section for the WooCommerce order detail page.
 *
 * @link       https://www.activecampaign.com/
 * @since      1.7.x
 *
 * @package    Activecampaign_For_Woocommerce
 * @subpackage Activecampaign_For_Woocommerce/admin/partials
 */
function activecampaign_for_woocommerce_convert_date_to_local( $datetime ) {
	return wp_date( 'Y-m-d H:i:s', strtotime( $datetime ) ) . ' ' . wp_timezone_string();
}
?>

<style>
	.sync-button{
		position: relative;
	}
	.button.sync-button.lds-dual-ring{
		color: #ccc;
	}
	.lds-dual-ring {
		display: inline-block;
		/*width: 40px;*/
		/*height: 40px;*/
		max-height: 300px;
	}
	.lds-dual-ring:after {
		content: " ";
		display: block;
		width: 24px;
		height: 24px;
		margin: 0px;
		border-radius: 50%;
		border: 2px solid #fff;
		border-color: #2271b1 transparent #2271b1 transparent;
		animation: lds-dual-ring 1.2s linear infinite;
		position: absolute;
		top:0;
		left:40%;
	}
	@keyframes lds-dual-ring {
		0% {
			transform: rotate(0deg);
		}
		100% {
			transform: rotate(360deg);
		}
	}
</style>
	<div class="activecampaign-order-meta">
		<?php
		wp_nonce_field( 'activecampaign_for_woocommerce_order_form', 'activecampaign_for_woocommerce_settings_nonce_field' );
		?>
		<div style="display: grid;grid-template-columns: repeat(2, 1fr);grid-template-rows: repeat(1, 1fr);grid-column-gap: 10px;grid-row-gap: 0px;">
			<div style="grid-area: 1 / 1 / 2 / 2;">
				<div>
					<?php if ( ! empty( $activecampaign_for_woocommerce_data['ac_order_id'] ) ) : ?>
						<p>
							<?php esc_html_e( 'ActiveCampaign Order ID:', ACTIVECAMPAIGN_FOR_WOOCOMMERCE_LOCALIZATION_DOMAIN ); ?>
							<?php echo esc_html( $activecampaign_for_woocommerce_data['ac_order_id'] ); ?>
						</p>
					<?php endif; ?>

					<?php if ( ! empty( $activecampaign_for_woocommerce_data['abandoned_date'] ) ) : ?>
						<p>
							<?php esc_html_e( 'This is a recovered order. Abandoned on: ', ACTIVECAMPAIGN_FOR_WOOCOMMERCE_LOCALIZATION_DOMAIN ); ?>
							<?php echo esc_html( activecampaign_for_woocommerce_convert_date_to_local( $activecampaign_for_woocommerce_data['abandoned_date'] ) ); ?>
						</p>
					<?php endif; ?>

					<?php if ( isset( $activecampaign_for_woocommerce_data['synced_to_ac_readable'] ) ) : ?>
						<p>
							<?php esc_html_e( 'Synced to ActiveCampaign status: ', ACTIVECAMPAIGN_FOR_WOOCOMMERCE_LOCALIZATION_DOMAIN ); ?>
							<?php echo esc_html( $activecampaign_for_woocommerce_data['synced_to_ac_readable']['title'] ); ?>
							(<i><?php echo esc_html( $activecampaign_for_woocommerce_data['synced_to_ac_readable']['help'] ); ?></i>)
						</p>
					<?php endif; ?>
				</div>
				<div>

					<?php if ( ! empty( $activecampaign_for_woocommerce_data['hosted_contact_url'] ) ) : ?>
						<p><a href="<?php echo esc_html( $activecampaign_for_woocommerce_data['hosted_contact_url'] ); ?>"><?php esc_html_e( 'Open contact in ActiveCampaign', ACTIVECAMPAIGN_FOR_WOOCOMMERCE_LOCALIZATION_DOMAIN ); ?></a></p>
					<?php endif; ?>
					<?php if ( isset( $activecampaign_for_woocommerce_data['contact_array']['email'] ) ) : ?>
						<div>Email: <?php echo esc_html( $activecampaign_for_woocommerce_data['contact_array']['email'] ); ?></div>
						<div>Name: <?php echo esc_html( $activecampaign_for_woocommerce_data['contact_array']['firstName'] ); ?> <?php echo esc_html( $activecampaign_for_woocommerce_data['contact_array']['lastName'] ); ?></div>
						<div>Phone: <?php echo esc_html( $activecampaign_for_woocommerce_data['contact_array']['phone'] ); ?></div>
					<?php else : ?>
						<div>No record returned from ActiveCampaign. This order may not be synced.</div>
					<?php endif; ?>
				</div>
			</div>
			<div style="grid-area: 1 / 2 / 2 / 2;border:1px solid #ccc; padding:10px">
				<?php if ( isset( $activecampaign_for_woocommerce_data['wc_order_id'] ) ) : ?>
					<h3>Immediate Syncing for order <?php echo esc_html( $activecampaign_for_woocommerce_data['wc_order_id'] ); ?></h3>
					<p>If you need this order synced immediately you can run either option.
					In most cases we suggest syncing an order as a Historical Order so that your data is updated in ActiveCampaign without running automations.</p>
					<hr style="margin: 20px 0;"/>
					<div>
						<p>Use this button to sync this order as a historical order. [Automations will <b>NOT</b> be run on this sync]</p>

						<button id="activecampaign-sync-historical-order" class="sync-button button disabled" ref="<?php echo esc_html( $activecampaign_for_woocommerce_data['wc_order_id'] ); ?>">
							<span class="text">Sync as Historical Order</span>
						</button>
					</div>
					<hr style="margin: 20px 0;"/>
					<div>
						<p>Use this button to immediately sync this order as a new order. [Automations <b>WILL</b> run on this sync]</p>
						<button id="activecampaign-sync-new-order" class="sync-button button disabled" ref="<?php echo esc_html( $activecampaign_for_woocommerce_data['wc_order_id'] ); ?>">
							<span class="text">Sync as New Order</span>
						</button>
					</div>
				<?php endif; ?>
			</div>
		</div>

		<?php if ( $activecampaign_for_woocommerce_data['debug'] ) : ?>
			<div style="margin-top:20px;border-top:1px solid #ccc;">
				<h4><?php esc_html_e( 'ActiveCampaign Debug info', ACTIVECAMPAIGN_FOR_WOOCOMMERCE_LOCALIZATION_DOMAIN ); ?></h4>
				<?php if ( ! empty( $activecampaign_for_woocommerce_data['ac_customer_id'] ) ) : ?>
					<p>
						<?php esc_html_e( 'ActiveCampaign Customer ID: ', ACTIVECAMPAIGN_FOR_WOOCOMMERCE_LOCALIZATION_DOMAIN ); ?>
						<?php echo esc_html( $activecampaign_for_woocommerce_data['ac_customer_id'] ); ?>
					</p>
				<?php endif; ?>
				<?php if ( ! empty( $activecampaign_for_woocommerce_data['customer_first_name'] ) && ! empty( $activecampaign_for_woocommerce_data['customer_last_name'] ) ) : ?>
					<p>
						<?php esc_html_e( 'Recorded Customer Name: ', ACTIVECAMPAIGN_FOR_WOOCOMMERCE_LOCALIZATION_DOMAIN ); ?>
						<?php echo esc_html( $activecampaign_for_woocommerce_data['customer_first_name'] ); ?>
						<?php echo esc_html( $activecampaign_for_woocommerce_data['customer_last_name'] ); ?>
					</p>
				<?php endif; ?>
				<?php if ( ! empty( $activecampaign_for_woocommerce_data['customer_email'] ) ) : ?>
					<p>
						<?php esc_html_e( 'Recorded Customer Email: ', ACTIVECAMPAIGN_FOR_WOOCOMMERCE_LOCALIZATION_DOMAIN ); ?>
						<?php echo esc_html( $activecampaign_for_woocommerce_data['customer_email'] ); ?>
					</p>
				<?php endif; ?>
				<?php if ( ! empty( $activecampaign_for_woocommerce_data['ac_customer_id'] ) && ! empty( $activecampaign_for_woocommerce_data['contact_id'] ) ) : ?>
					<p>
						<?php esc_html_e( 'ActiveCampaign Contact ID: ', ACTIVECAMPAIGN_FOR_WOOCOMMERCE_LOCALIZATION_DOMAIN ); ?>
						<?php echo esc_html( $activecampaign_for_woocommerce_data['contact_id'] ); ?>
					</p>
				<?php endif; ?>
				<?php if ( ! empty( $activecampaign_for_woocommerce_data['ac_externalcheckoutid'] ) ) : ?>
					<p>
						<?php esc_html_e( 'External Checkout ID: ', ACTIVECAMPAIGN_FOR_WOOCOMMERCE_LOCALIZATION_DOMAIN ); ?>
						<?php echo esc_html( $activecampaign_for_woocommerce_data['ac_externalcheckoutid'] ); ?>
					</p>
				<?php endif; ?>
				<?php if ( isset( $activecampaign_for_woocommerce_data['synced_to_ac'] ) ) : ?>
					<p>
						<?php esc_html_e( 'Synced to ActiveCampaign raw status: ', ACTIVECAMPAIGN_FOR_WOOCOMMERCE_LOCALIZATION_DOMAIN ); ?>
						<?php echo esc_html( $activecampaign_for_woocommerce_data['synced_to_ac'] ); ?>
					</p>
				<?php endif; ?>
			</div>
		<?php endif; ?>
	</div>
