<?php
/**
 * Provide an admin historical sync view for the plugin
 *
 * @link       https://www.activecampaign.com/
 * @since      1.5.0
 *
 * @package    Activecampaign_For_Woocommerce
 * @subpackage Activecampaign_For_Woocommerce/admin/partials
 */

$activecampaign_for_woocommerce_options           = $this->get_options();
$activecampaign_for_woocommerce_page_url          = esc_url( admin_url( 'admin.php?page=' . ACTIVECAMPAIGN_FOR_WOOCOMMERCE_PLUGIN_NAME_SNAKE . '_historical_sync&activesync=1' ) );
$activecampaign_for_woocommerce_status_data       = $this->get_next_historical_sync();
$activecampaign_for_woocommerce_has_subscriptions = $this->get_has_subscriptions();
?>
<style>
	table {
		background-color: #EEEEEE;
		width: 100%;
		text-align: left;
		border-collapse: collapse;
	}
	table td, table th {
		border: 1px solid #AAAAAA;
		padding: 3px 2px;
	}
	table tbody td {
		font-size: 13px;
	}
	table tr:nth-child(even) {
		background: #FFFFFF;
	}
	table thead {
		background: #2271B1;
		border-bottom: 2px solid #444444;
	}
	table thead th {
		font-size: 15px;
		font-weight: bold;
		color: #FFFFFF;
		border-left: 1px solid #444444;
	}
	table thead th:first-child {
		border-left: none;
	}

	table tfoot td {
		font-size: 14px;
	}
	table tfoot .links {
		text-align: right;
	}
	table tfoot .links a{
		display: inline-block;
		background: #1C6EA4;
		color: #FFFFFF;
		padding: 2px 8px;
		border-radius: 5px;
	}
	table .prep{
		color:#769de2;
	}
	table .queue{
		color:#5e9d9f;
	}
	table .verify{
		color:#7e9c81;
	}
	table .synced{
		color:#0a870a;
	}
	table .failed{
		color: #be1212;
	}
	/* Tooltip container */
	table .tooltip {
		position: relative;
		display: inline-block;
		border-bottom: 1px dotted black; /* If you want dots under the hoverable text */
	}

	/* Tooltip text */
	table .tooltip .tooltiptext {
		visibility: hidden;
		width: 120px;
		background-color: black;
		color: #fff;
		text-align: center;
		padding: 5px 15px;
		border-radius: 6px;

		/* Position the tooltip text - see examples below! */
		position: absolute;
		z-index: 1;
	}

	/* Show the tooltip text when you mouse over the tooltip container */
	table .tooltip:hover .tooltiptext {
		visibility: visible;
	}
	.activecampaign-status-header{
		display: none;
	}
	#activecampaign-run-historical-sync-current-record-status{overflow-wrap: break-word;}
</style>
<div id="activecampaign-for-woocommerce-historical-sync" class="wrap">
	<h1>
		<?php
		esc_html_e( 'ActiveCampaign for WooCommerce Historical Sync', ACTIVECAMPAIGN_FOR_WOOCOMMERCE_LOCALIZATION_DOMAIN );
		?>
	</h1>
	<section>
		<div class="card">
			<div>
				<div id="sync-start-section">
					<div>
						<form method="post" action="<?php echo esc_html( $activecampaign_for_woocommerce_page_url ); ?>">
							<?php
							wp_nonce_field( 'activecampaign_for_woocommerce_historical_sync_form', 'activecampaign_for_woocommerce_nonce_field' );
							?>
							<input type="hidden" name="action" value="activecampaign_for_woocommerce_run_historical_sync_active" />

							<button type="button" id="activecampaign-run-historical-sync" class="activecampaign-for-woocommerce button button-primary" style="padding: 0 10px">
								<?php esc_html_e( 'Start Historical Sync', ACTIVECAMPAIGN_FOR_WOOCOMMERCE_LOCALIZATION_DOMAIN ); ?>
							</button>

							<button id="activecampaign-reset-historical-sync" class="button" type="button" >
								<?php esc_html_e( 'Reset Sync Status', ACTIVECAMPAIGN_FOR_WOOCOMMERCE_LOCALIZATION_DOMAIN ); ?>
							</button>
							<div>
								<div>
									<input type="checkbox" value="1" class="checkbox" name="activecampaign-historical-sync-contacts" id="activecampaign-historical-sync-contacts" />
									<label for="activecampaign-historical-sync-contacts">Sync All Contacts</label>
								</div>
							</div>
						</form>
					</div>
				</div>
			</div>
		</div>
		<div class="card">
			<p>Historical sync will continue running every minute as long as there are orders in the queue.</p>
			<p>If you would like to stop sync from running or pause the process you can halt it with this button.</p>
			<p>(Once the process is stopped you can continue at any time or start from the beginning by clicking the start button)</p>
			<div>
				<button id="activecampaign-cancel-historical-sync" class="button" type="button">
				<?php esc_html_e( 'Halt/Stop Historical Sync', ACTIVECAMPAIGN_FOR_WOOCOMMERCE_LOCALIZATION_DOMAIN ); ?>
				</button>
				<button id="activecampaign-continue-historical-sync" class="button" type="button">
					<?php esc_html_e( 'Continue Historical Sync', ACTIVECAMPAIGN_FOR_WOOCOMMERCE_LOCALIZATION_DOMAIN ); ?>
				</button>
			</div>
		</div>
		<div class="card">
			<div class="clear">
				<h3 id="activecampaign-sync-running-header" style="">
					<?php esc_html_e( 'Sync Status', ACTIVECAMPAIGN_FOR_WOOCOMMERCE_LOCALIZATION_DOMAIN ); ?>
				</h3>
				<hr />
				<h4 id="activecampaign-historical-sync-order-status" class="activecampaign-status-header" style="">
					<?php esc_html_e( 'Order processing status:', ACTIVECAMPAIGN_FOR_WOOCOMMERCE_LOCALIZATION_DOMAIN ); ?>
					<span class="data"></span>
				</h4>
				<h4 id="activecampaign-historical-sync-contact-status" class="activecampaign-status-header" style="">
					<?php esc_html_e( 'Contact processing status:', ACTIVECAMPAIGN_FOR_WOOCOMMERCE_LOCALIZATION_DOMAIN ); ?>
					<span class="data"></span>
				</h4>
				<h4 id="activecampaign-historical-sync-stop-requested" class="activecampaign-status-header" style="">
					<?php esc_html_e( 'Stop requested.', ACTIVECAMPAIGN_FOR_WOOCOMMERCE_LOCALIZATION_DOMAIN ); ?>
					<span class="data"></span>
				</h4>
				<h4 id="activecampaign-historical-sync-stuck" class="activecampaign-status-header" style="">
					<?php esc_html_e( 'Historical sync may be stuck or running very slow.', ACTIVECAMPAIGN_FOR_WOOCOMMERCE_LOCALIZATION_DOMAIN ); ?>
				</h4>
				<hr/>
				<div id="activecampaign-sync-run-section">
					<div id="activecampaign-run-historical-sync-current-record">
						<h2>Sync counts</h2>
						<table style="width: 100%;">
							<tr>
								<th>Data Type</th>
								<th>
									<div class="tooltip">Total Available
										<span class="tooltiptext">Total records available with a compatible status.</span>
									</div>
								</th>
								<th class="prep">
									<div class="tooltip">
										Preparing Data
										<span class="tooltiptext">Records currently gathering data in preparation for sync.</span>
									</div>
								</th>
								<th class="queue">
									<div class="tooltip">
										Sync Queue
										<span class="tooltiptext">These records are queued and ready to sync.</span>
									</div>
								</th>
								<th class="synced">
									<div class="tooltip">
										Synced
										<span class="tooltiptext">Records successfully synced to ActiveCampaign</span>
									</div>
									</th>
								<th class="incompatible">
									<div class="tooltip">
										Incompatible
										<span class="tooltiptext">Records may be missing required fields and/or WooCommerce returned null data for an order/contact.</span>
									</div>
									</th>
								<th class="failed">
									<div class="tooltip">
										Failed Sync
										<span class="tooltiptext">Records failed to sync to ActiveCampaign.</span>
									</div>
									</th>
							</tr>
							<tr id="activecampaign-data-contacts">
								<td>Contacts</td>
								<td><span id="activecampaign-historical-sync-contacts-count"></span></td>
								<td>N/A</td>
								<td><span id="activecampaign-historical-sync-contacts-queue">-</span></td>
								<td><span id="activecampaign-run-historical-sync-contact-record-num"></span></td>
								<td>N/A</td>
								<td><span id="activecampaign-run-historical-sync-contact-failed-num"></span></td>
							</tr>
							<?php if ( $activecampaign_for_woocommerce_has_subscriptions ) : ?>
								<tr id="activecampaign-data-subscriptions">
									<td>Subscriptions</td>
									<td><span id="activecampaign-run-historical-sync-sub-total-count"></span></td>
									<td><span id="activecampaign-run-historical-sync-sub-prepared-count"></span></td>
									<td><span id="activecampaign-run-historical-sync-sub-pending-count"></span></td>
									<td><span id="activecampaign-run-historical-sync-sub-synced-count"></span></td>
									<td><span id="activecampaign-run-historical-sync-sub-incompatible-count"></span></td>
									<td><span id="activecampaign-run-historical-sync-sub-error-count"></span></td>
								</tr>
							<?php endif; ?>
							<tr id="activecampaign-data-orders">
								<td>Orders</td>
								<td><span id="activecampaign-run-historical-sync-total-count"></span></td>
								<td><span id="activecampaign-run-historical-sync-prepared-count"></span></td>
								<td><span id="activecampaign-run-historical-sync-pending-count"></span></td>
								<td><span id="activecampaign-run-historical-sync-synced-count"></span></td>
								<td><span id="activecampaign-run-historical-sync-incompatible-count"></span></td>
								<td><span id="activecampaign-run-historical-sync-error-count"></span></td>
							</tr>
						</table>
						<br/>
						<td><?php esc_html_e( 'Next scheduled historical sync cron', ACTIVECAMPAIGN_FOR_WOOCOMMERCE_LOCALIZATION_DOMAIN ); ?></td>
						<td>
							<?php if ( ! $activecampaign_for_woocommerce_status_data['historical_order_schedule']['error'] ) : ?>
								<?php echo esc_html( $activecampaign_for_woocommerce_status_data['historical_order_schedule']['timestamp'] ); ?>
							<?php else : ?>
								<mark class="error"><?php esc_html_e( 'Warning! Historical sync cron may not be scheduled.', ACTIVECAMPAIGN_FOR_WOOCOMMERCE_LOCALIZATION_DOMAIN ); ?></mark>
							<?php endif; ?>
						</td>
						<?php if ( $activecampaign_for_woocommerce_options['ac_debug'] ) : ?>
							<hr/>
							<div>
								<p>Debug info: <div id="activecampaign-run-historical-sync-current-record-status" style="width:100%;overflow:auto;"></div></p>
							</div>
						<?php endif; ?>
					</div>
				
				</div>
			</div>
		</div>
	</section>
