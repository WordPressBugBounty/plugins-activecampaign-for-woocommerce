<?php
/**
 * Provide an admin section for the connections block.
 *
 * @link       https://www.activecampaign.com/
 * @since      1.7.x
 *
 * @package    Activecampaign_For_Woocommerce
 * @subpackage Activecampaign_For_Woocommerce/admin/partials
 */
use Activecampaign_For_Woocommerce_Cofe_Ecom_Order_Status as Ecom_Order_Status;

try {
	$activecampaign_for_woocommerce_options        = $this->get_ac_settings();
	$activecampaign_for_woocommerce_settings       = $this->get_local_settings();
	$activecampaign_for_woocommerce_saved_mappings = null;

	if ( isset( $activecampaign_for_woocommerce_settings['status_mapping'] ) ) {
		$activecampaign_for_woocommerce_saved_mappings = $activecampaign_for_woocommerce_settings['status_mapping'];
	}

	$activecampaign_for_woocommerce_all_connections   = $this->get_all_connections();
	$activecampaign_for_woocommerce_connection_id     = 'UNKNOWN';
	$activecampaign_for_woocommerce_ecom_order_status = new Ecom_Order_Status();
	$activecampaign_for_woocommerce_wc_status_list    = wc_get_order_statuses();

	// We automatically map all of these
	$activecampaign_for_woocommerce_default_wc = [
		'pending',
		'on-hold',
		'processing',
		'completed',
		'failed',
		'refunded',
		'cancelled',
		'wc-pending',
		'wc-on-hold',
		'wc-processing',
		'wc-completed',
		'wc-failed',
		'wc-refunded',
		'wc-cancelled',
		'wc-paused',
		'pending-cancel',
		'wc-pending-cancel',
		'wc-active',
		'active',
		'wc-expired',
		'expired',
		'paused',
	];

	$activecampaign_for_woocommerce_ac_status_list = $activecampaign_for_woocommerce_ecom_order_status->get_all_ac_statuses();

	foreach ( $activecampaign_for_woocommerce_wc_status_list as $activecampaign_for_woocommerce_wc_status_key => $activecampaign_for_woocommerce_wc_status ) {
		if ( in_array( $activecampaign_for_woocommerce_wc_status_key, $activecampaign_for_woocommerce_default_wc, true ) ) {
			unset( $activecampaign_for_woocommerce_wc_status_list[ $activecampaign_for_woocommerce_wc_status_key ] );
		}
	}
} catch ( Throwable $t ) {
	$activecampaign_for_woocommerce_logger = new Activecampaign_For_Woocommerce_Logger();
	$activecampaign_for_woocommerce_logger->warning(
		'AC Admin could not render status mapping. There may be an issue with order status mapping.',
		[
			'message' => $t->getMessage(),
			'trace'   => $t->getTrace(),
		]
	);
}
?>
<section>
	<h2>
		<?php esc_html_e( 'Custom Order Status Mapping', ACTIVECAMPAIGN_FOR_WOOCOMMERCE_LOCALIZATION_DOMAIN ); ?>
	</h2>
	<table class="wp-list-table widefat striped table-view-list">
		<thead>
		<tr>
			<th>WooCommerce order status will map to ></th>
			<th>ActiveCampaign status</th>
			<th>Actions</th>
		</tr>
		</thead>
		<tr>
			<td>
				<?php if ( isset( $activecampaign_for_woocommerce_wc_status_list ) && is_array( $activecampaign_for_woocommerce_wc_status_list ) ) : ?>
				<select id="wc_status_key">
					<?php foreach ( $activecampaign_for_woocommerce_wc_status_list as $activecampaign_for_woocommerce_status_key => $activecampaign_for_woocommerce_status_name ) : ?>
						<?php echo '<option value="' . esc_html( $activecampaign_for_woocommerce_status_key ) . '">' . esc_html( $activecampaign_for_woocommerce_status_name ) . ' (' . esc_html( $activecampaign_for_woocommerce_status_key ) . ')</option>'; ?>
					<?php endforeach; ?>
				</select>
				<?php else : ?>
					Unable to retrieve status list from WooCommerce.
				<?php endif; ?>
			</td>
			<td>
				<?php if ( isset( $activecampaign_for_woocommerce_ac_status_list ) && is_array( $activecampaign_for_woocommerce_ac_status_list ) ) : ?>
				<select id="ac_status_key" class="activecampaign-for-woocommerce">
					<?php foreach ( array_unique( $activecampaign_for_woocommerce_ac_status_list ) as $activecampaign_for_woocommerce_ac_status_key => $activecampaign_for_woocommerce_ac_status_match ) : ?>
						<?php echo '<option value="' . esc_html( $activecampaign_for_woocommerce_ac_status_match ) . '">' . esc_html( $activecampaign_for_woocommerce_ac_status_match ) . '</option>'; ?>
					<?php endforeach; ?>
				</select>
				<?php else : ?>
					Unable to retrieve stored AC status list from settings.
				<?php endif; ?>
			</td>
			<td>
				<button id="activecampaign-create-mapping-button" class="activecampaign-for-woocommerce button secondary">Add/Update</button>
			</td>
		</tr>
		<?php if ( isset( $activecampaign_for_woocommerce_saved_mappings ) && count( $activecampaign_for_woocommerce_saved_mappings ) > 0 ) : ?>
			<?php foreach ( $activecampaign_for_woocommerce_saved_mappings as $activecampaign_for_woocommerce_map_key => $activecampaign_for_woocommerce_mapping ) : ?>
				<tr>
					<td><?php echo esc_html( $activecampaign_for_woocommerce_map_key ); ?></td>
					<td><?php echo esc_html( $activecampaign_for_woocommerce_mapping ); ?></td>
					<td><button class="activecampaign-delete-mapping-button activecampaign-for-woocommerce button secondary" key="<?php echo esc_html( $activecampaign_for_woocommerce_map_key ); ?>">Delete</button></td>
				</tr>
			<?php endforeach; ?>
		<?php else : ?>
			<tr>
				<td>
					No mappings set
				</td>
			</tr>
		<?php endif; ?>
	</table>
</section>
