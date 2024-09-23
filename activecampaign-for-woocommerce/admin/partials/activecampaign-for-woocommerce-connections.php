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
$activecampaign_for_woocommerce_options = $this->get_options();
$activecampaign_for_woocommerce_storage = $this->get_storage();

$activecampaign_for_woocommerce_all_connections = $this->get_all_connections();
$activecampaign_for_woocommerce_connection_id   = 'UNKNOWN';
if ( isset( $activecampaign_for_woocommerce_storage['connection_id'] ) ) {
	$activecampaign_for_woocommerce_connection_id = $activecampaign_for_woocommerce_storage['connection_id'];
}

?>
<?php if ( count( $activecampaign_for_woocommerce_all_connections ) > 0 ) : ?>
	<?php foreach ( $activecampaign_for_woocommerce_all_connections as $activecampaign_for_woocommerce_connection ) : ?>
		<tr id="connection-list-<?php echo esc_html( $activecampaign_for_woocommerce_connection->get_id() ); ?>" class="even thread-even depth-1" data-connection="<?php echo esc_html( wp_json_encode( $activecampaign_for_woocommerce_connection->serialize_to_array() ) ); ?>">
			<?php if ( $activecampaign_for_woocommerce_connection_id === $activecampaign_for_woocommerce_connection->get_id() ) : ?>
			<td class="connection-id column-id selected" data-colname="Id">
				<?php else : ?>
			<td class="connection-id column-id" data-colname="Id">
				<?php endif; ?>
				<b>Connection ID: <?php echo esc_html( $activecampaign_for_woocommerce_connection->get_id() ); ?></b>
			</td>
			<td>
				<?php echo esc_html( $activecampaign_for_woocommerce_connection->get_externalid() ); ?>
			</td>
			<td>
				<?php echo esc_html( $activecampaign_for_woocommerce_connection->get_name() ); ?>
			</td>
			<td><?php echo esc_html( $activecampaign_for_woocommerce_connection->get_link_url() ); ?></td>
			<td class="options">
				<?php if ( $activecampaign_for_woocommerce_connection_id !== $activecampaign_for_woocommerce_connection->get_id() ) : ?>

					<a href="#"
					   data-value="<?php echo esc_url( admin_url( 'admin-ajax.php' ) ); ?>"
					   class="activecampaign-for-woocommerce-select-connection-button button secondary">
						Select
					</a>
				<?php endif; ?>
				<a href="#"
				   data-value="<?php echo esc_url( admin_url( 'admin-ajax.php' ) ); ?>"
				   class="activecampaign-for-woocommerce-edit-connection-button button secondary">
					Edit
				</a>
<!--				<a href="#"-->
<!--				   data-value="--><?php // echo esc_url( admin_url( 'admin-ajax.php' ) ); ?><!--"-->
<!--				   class="activecampaign-for-woocommerce-delete-connection-button button secondary">-->
<!--					Delete-->
<!--				</a>-->
			</td>
			<td class="status">
				<span class="status-name tooltip"></span>
			</td>
		</tr>
	<?php endforeach; ?>
<?php else : ?>
	<tr><td colspan="4">No connections found. Please try connecting through your ActiveCampaign Integrations page. If you're having trouble connecting from Activecampaign you can try creating a manual connection from the link above.</td></tr>
<?php endif; ?>
