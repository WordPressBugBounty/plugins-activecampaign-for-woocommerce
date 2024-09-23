<?php

	/**
	 * Provide an abandoned cart plugin view.
	 *
	 * This file is used to markup the admin-facing aspects of the plugin.
	 *
	 * @link       https://www.activecampaign.com/
	 * @since      1.3.7
	 *
	 * @package    Activecampaign_For_Woocommerce
	 * @subpackage Activecampaign_For_Woocommerce/admin/partials
	 */

$activecampaign_for_woocommerce_limit   = 40;
$activecampaign_for_woocommerce_request = wp_unslash( $_REQUEST );
$activecampaign_for_woocommerce_get     = wp_unslash( $_GET );
if (
	isset( $activecampaign_for_woocommerce_request['activecampaign_for_woocommerce_abandoned_cart_nonce_field'], $activecampaign_for_woocommerce_get['offset'] ) &&
	wp_verify_nonce( $activecampaign_for_woocommerce_request['activecampaign_for_woocommerce_abandoned_cart_nonce_field'], 'activecampaign_for_woocommerce_abandoned_form' )
) {
	$activecampaign_for_woocommerce_offset = $activecampaign_for_woocommerce_get['offset'];
} else {
	$activecampaign_for_woocommerce_offset = 0;
}

$activecampaign_for_woocommerce_expire_time = 1;
if ( isset( $activecampaign_for_woocommerce_settings['abcart_wait'] ) && ! empty( $activecampaign_for_woocommerce_settings['abcart_wait'] ) ) {
	$activecampaign_for_woocommerce_expire_time = $activecampaign_for_woocommerce_settings['abcart_wait'];
}

$activecampaign_for_woocommerce_expire_datetime = new DateTime( 'now -' . $activecampaign_for_woocommerce_expire_time . ' hours', new DateTimeZone( 'UTC' ) );

$activecampaign_for_woocommerce_abandoned_carts = $this->get_abandoned_carts( $activecampaign_for_woocommerce_offset );
$activecampaign_for_woocommerce_total           = 0;

$activecampaign_for_woocommerce_pages = 0;
if ( count( $activecampaign_for_woocommerce_abandoned_carts ) > 0 ) {
	$activecampaign_for_woocommerce_total = $this->get_total_abandoned_carts();
	$activecampaign_for_woocommerce_pages = ceil( $activecampaign_for_woocommerce_total / $activecampaign_for_woocommerce_limit );
}
$activecampaign_for_woocommerce_now      = date_create( 'NOW' );
$activecampaign_for_woocommerce_last_run = get_option( 'activecampaign_for_woocommerce_abandoned_cart_last_run' );
if ( $activecampaign_for_woocommerce_last_run ) {
	$activecampaign_for_woocommerce_interval         = date_diff( $activecampaign_for_woocommerce_now, $activecampaign_for_woocommerce_last_run );
	$activecampaign_for_woocommerce_interval_minutes = $activecampaign_for_woocommerce_interval->format( '%i' );
} else {
	$activecampaign_for_woocommerce_interval_minutes = null;
}
$activecampaign_for_woocommerce_page_nonce = wp_create_nonce( 'activecampaign_for_woocommerce_abandoned_form' );

function activecampaign_for_woocommerce_convert_date_to_local( $datetime ) {
	return wp_date( 'Y-m-d H:i:s e', strtotime( $datetime ) );
}
/**
 * Parses an array from abandoned cart to display decently.
 *
 * @param array $activecampaign_for_woocommerce_array_data The array from abandoned carts.
 *
 * @return string
 */
function activecampaign_for_woocommerce_parse_array( $activecampaign_for_woocommerce_array_data ) {
	try {
		if ( is_array( $activecampaign_for_woocommerce_array_data ) ) {
			return implode(
				",\r\n",
				array_map(
					static function ( $k, $v ) {
						if ( is_array( $v ) ) {
							return "$k:" . activecampaign_for_woocommerce_parse_array( $v );
						}

						if ( ! empty( $v ) ) {
							return "$k: $v";
						} else {
							return "$k: [EMPTY]";
						}
					},
					array_keys( $activecampaign_for_woocommerce_array_data ),
					array_values( $activecampaign_for_woocommerce_array_data )
				)
			);
		} else {
			return null;
		}
	} catch ( Throwable $t ) {
		return $t->getMessage();
	}
}
?>
<?php settings_errors(); ?>
<div id="activecampaign-for-woocommerce-abandoned-cart">
	<?php
		require plugin_dir_path( __FILE__ ) . '../partials/activecampaign-for-woocommerce-header.php';
	?>
	<div>
		<?php
			esc_html_e( 'All abandoned cart entries will appear here. Records for customers who have finished their order will be removed from the list.', ACTIVECAMPAIGN_FOR_WOOCOMMERCE_LOCALIZATION_DOMAIN );
		?>
	</div>
	<section>
		<div class="card">
			<div>
				<div class="columnbox">
					<button id="activecampaign-run-abandoned-cart" class="button
					<?php
					if ( ! $activecampaign_for_woocommerce_total ) :
						?>
						disabled<?php endif; ?>">Sync Abandoned Carts</button>
					<div id="activecampaign-run-abandoned-cart-status"></div>
				</div>
			</div>
			<div class="clear">
				<h3>
					Last sync time:
					<?php if ( isset( $activecampaign_for_woocommerce_interval_minutes ) ) : ?>
						<?php echo esc_html( $activecampaign_for_woocommerce_interval_minutes ); ?> minutes ago
					<?php else : ?>
						unknown
					<?php endif; ?>
				</h3>
				<h3>
					Total Abandoned Carts
				</h3>
				<p>
					Total Unsynced: 
					<?php
					echo esc_html( $this->get_total_abandoned_carts_unsynced() );
					?>
				</p>
				<p>
					Total Abandoned Carts: <?php echo esc_html( $activecampaign_for_woocommerce_total ); ?>
				</p>
			</div>
		</div>
	</section>
	<section>
		<div class="col-container">
			<?php if ( $activecampaign_for_woocommerce_total ) : ?>
				<div class="pagination">
					Page:
					<?php for ( $activecampaign_for_woocommerce_c = 1; $activecampaign_for_woocommerce_c <= $activecampaign_for_woocommerce_pages; $activecampaign_for_woocommerce_c++ ) : ?>
						<?php if ( $activecampaign_for_woocommerce_c === $activecampaign_for_woocommerce_offset + 1 ) : ?>
							<?php echo esc_html( $activecampaign_for_woocommerce_c ); ?>
						<?php else : ?>
							<a href="
							<?php
							echo esc_html(
								add_query_arg(
									[
										'offset' => $activecampaign_for_woocommerce_c - 1,
										'activecampaign_for_woocommerce_abandoned_cart_nonce_field' => $activecampaign_for_woocommerce_page_nonce,
									],
									wc_get_current_admin_url()
								)
							);

							?>
							"><?php echo esc_html( $activecampaign_for_woocommerce_c ); ?></a>
						<?php endif; ?>
					<?php endfor; ?>
				</div>
			<?php endif; ?>
			<form method="POST" id="activecampaign-for-woocommerce-form">
				<?php
				wp_nonce_field( 'activecampaign_for_woocommerce_abandoned_form', 'activecampaign_for_woocommerce_settings_nonce_field' );
				?>
				<table class="wc_status_table widefat status_activecampaign" cellspacing="0">
					<thead>
					<tr>
						<td>
							<?php
							esc_html_e( 'Customer', ACTIVECAMPAIGN_FOR_WOOCOMMERCE_LOCALIZATION_DOMAIN );
							?>
						</td>
						<td>
							Order Type
						</td>
						<td>
							<?php
							esc_html_e( 'Synced Latest Data To ActiveCampaign', ACTIVECAMPAIGN_FOR_WOOCOMMERCE_LOCALIZATION_DOMAIN );
							?>
						</td>

						<td>
							<?php
							esc_html_e( 'External Checkout ID', ACTIVECAMPAIGN_FOR_WOOCOMMERCE_LOCALIZATION_DOMAIN );
							?>
						</td>
						<td>
							<?php
							esc_html_e( 'Last Access Time', ACTIVECAMPAIGN_FOR_WOOCOMMERCE_LOCALIZATION_DOMAIN );
							?>
						</td>
						<td>
							<?php
							esc_html_e( 'Actions', ACTIVECAMPAIGN_FOR_WOOCOMMERCE_LOCALIZATION_DOMAIN );
							?>
						</td>
					</tr>
					</thead>
					<tbody>
					<?php if ( $activecampaign_for_woocommerce_total ) : ?>
						<?php foreach ( $activecampaign_for_woocommerce_abandoned_carts as $activecampaign_for_woocommerce_key => $activecampaign_for_woocommerce_ab_cart ) : ?>
							<?php if ( isset( $activecampaign_for_woocommerce_ab_cart->id ) ) : ?>
								<tr rowid="<?php echo esc_html( $activecampaign_for_woocommerce_ab_cart->id ); ?>">
									<td>
										<div>
											<?php echo esc_html( $activecampaign_for_woocommerce_ab_cart->customer_first_name ); ?>
											<?php echo esc_html( $activecampaign_for_woocommerce_ab_cart->customer_last_name ); ?>
										</div>
										<div>
										<?php echo esc_html( $activecampaign_for_woocommerce_ab_cart->customer_email ); ?>
										</div>
									</td>
									<td>
										<?php
										if (
												! empty( $activecampaign_for_woocommerce_ab_cart->order_date ) &&
												! empty( $activecampaign_for_woocommerce_ab_cart->abandoned_date ) &&
												in_array( $activecampaign_for_woocommerce_ab_cart->synced_to_ac, [ 23, '23', 1, '1' ], true )
										) :
											?>
											<?php esc_html_e( 'Recovered:', ACTIVECAMPAIGN_FOR_WOOCOMMERCE_LOCALIZATION_DOMAIN ); ?> <?php echo esc_html( activecampaign_for_woocommerce_convert_date_to_local( $activecampaign_for_woocommerce_ab_cart->order_date ) ); ?>
										<?php endif; ?>
										<?php if ( ! empty( $activecampaign_for_woocommerce_ab_cart->order_date ) && empty( $activecampaign_for_woocommerce_ab_cart->abandoned_date ) ) : ?>
											Ordered: <?php echo esc_html( activecampaign_for_woocommerce_convert_date_to_local( $activecampaign_for_woocommerce_ab_cart->order_date ) ); ?>
										<?php endif; ?>

										<?php
										if (
												(
												empty( $activecampaign_for_woocommerce_ab_cart->order_date ) &&
											   ! empty( $activecampaign_for_woocommerce_ab_cart->abandoned_date )
											 ) ||
											in_array(
												$activecampaign_for_woocommerce_ab_cart->synced_to_ac,
												[
													21,
													'21',
													22,
													'22',
												],
												true
											) ) :
											?>
											Synced<br/>Abandoned On: <?php echo esc_html( activecampaign_for_woocommerce_convert_date_to_local( $activecampaign_for_woocommerce_ab_cart->abandoned_date ) ); ?>
										<?php endif; ?>

										<?php
										if (
												( empty( $activecampaign_for_woocommerce_ab_cart->order_date ) && empty( $activecampaign_for_woocommerce_ab_cart->abandoned_date ) ) ||
												in_array( $activecampaign_for_woocommerce_ab_cart->synced_to_ac, [ 0, 20, '0', '20' ], true )
										) :
											?>
											<?php if ( isset( $activecampaign_for_woocommerce_ab_cart->ready_state ) && in_array( $activecampaign_for_woocommerce_ab_cart->ready_state, [ '1', 1 ], true ) ) : ?>
												Abandoned Cart Ready to Sync
											<?php else : ?>
												Active Cart
											<?php endif; ?>
										<?php endif; ?>

										<?php if ( in_array( $activecampaign_for_woocommerce_ab_cart->synced_to_ac, [ 25, '25' ], true ) ) : ?>
											Timeout/Network failure (try again)
										<?php endif; ?>

										<?php if ( in_array( $activecampaign_for_woocommerce_ab_cart->synced_to_ac, [ 26, '26' ], true ) ) : ?>
											Failed to sync 1 time (will try again)
										<?php endif; ?>

										<?php if ( in_array( $activecampaign_for_woocommerce_ab_cart->synced_to_ac, [ 27, '27' ], true ) ) : ?>
											Failed to sync 2 times (will try again)
										<?php endif; ?>

										<?php if ( in_array( $activecampaign_for_woocommerce_ab_cart->synced_to_ac, [ 29, '29' ], true ) ) : ?>
											Sync failed permanently
										<?php endif; ?>
									</td>
									<td>
										<?php
										if ( in_array( $activecampaign_for_woocommerce_ab_cart->synced_to_ac, [ 1, 21, 22, 23, '1', '21', '22', '23' ], true ) ) {
											esc_html_e( 'Yes', ACTIVECAMPAIGN_FOR_WOOCOMMERCE_LOCALIZATION_DOMAIN );
										} else {
											esc_html_e( 'No', ACTIVECAMPAIGN_FOR_WOOCOMMERCE_LOCALIZATION_DOMAIN );
										}
										?>
									</td>
									<td>
										<div>
										<?php
											echo esc_html( md5( $activecampaign_for_woocommerce_ab_cart->customer_id . $activecampaign_for_woocommerce_ab_cart->customer_email . $activecampaign_for_woocommerce_ab_cart->activecampaignfwc_order_external_uuid ) );
										?>
										</div>
									</td>
									<td>
										<?php echo esc_html( $activecampaign_for_woocommerce_ab_cart->last_access_time ); ?> UTC
										<br/>
										<?php echo esc_html( activecampaign_for_woocommerce_convert_date_to_local( $activecampaign_for_woocommerce_ab_cart->last_access_time ) ); ?>
									</td>
									<td>
										<div
												class="activecampaign-modal-abandoned-cart button"
												ref="<?php echo esc_html( 'abcartmodal_' . $activecampaign_for_woocommerce_key ); ?>"
												data="
												<?php
												if ( isset( $activecampaign_for_woocommerce_ab_cart->cart_ref_json ) ) {
													try {
														 echo esc_html( maybe_unserialize( $activecampaign_for_woocommerce_ab_cart->cart_ref_json ) );
													} catch ( Throwable $t ) {
														// do nothing
													}
												}
												?>
												"
										>
											<?php
											esc_html_e( 'More Info', ACTIVECAMPAIGN_FOR_WOOCOMMERCE_LOCALIZATION_DOMAIN );
											?>
										</div>
										<div class="activecampaign-more-data">
											<h2>
												<?php
												echo esc_html_e( 'Customer details', ACTIVECAMPAIGN_FOR_WOOCOMMERCE_LOCALIZATION_DOMAIN );
												?>
											</h2>
											<?php
											echo esc_html( $activecampaign_for_woocommerce_ab_cart->customer_id );
											?>
											<div>
												<?php echo esc_html( $activecampaign_for_woocommerce_ab_cart->customer_first_name ); ?>
												<?php echo esc_html( $activecampaign_for_woocommerce_ab_cart->customer_last_name ); ?>
											</div>
											<div>
												<?php echo esc_html( $activecampaign_for_woocommerce_ab_cart->customer_email ); ?>
											</div>
											<?php
											$activecampaign_for_woocommerce_array_data = json_decode( $activecampaign_for_woocommerce_ab_cart->customer_ref_json, true );
											if ( is_array( $activecampaign_for_woocommerce_array_data ) ) {
												 echo nl2br( esc_html( activecampaign_for_woocommerce_parse_array( $activecampaign_for_woocommerce_array_data ) ) );
											}
											?>
											<hr/>
											<h2>Cart details</h2>
											<?php
											$activecampaign_for_woocommerce_array_data = json_decode( $activecampaign_for_woocommerce_ab_cart->cart_ref_json, true );
											if ( is_array( $activecampaign_for_woocommerce_array_data ) ) {
												 echo nl2br( esc_html( activecampaign_for_woocommerce_parse_array( array_values( $activecampaign_for_woocommerce_array_data )[0] ) ) );
											}
											?>
										</div>
										<button class="activecampaign-sync-abandoned-cart button">
											<?php
											echo esc_html_e( 'Sync', ACTIVECAMPAIGN_FOR_WOOCOMMERCE_LOCALIZATION_DOMAIN );
											?>
										</button>
										<button class="activecampaign-delete-abandoned-cart button">
											<?php
											echo esc_html_e( 'Delete', ACTIVECAMPAIGN_FOR_WOOCOMMERCE_LOCALIZATION_DOMAIN );
											?>
										</button>
									</td>
								</tr>
							<?php endif; ?>
						<?php endforeach; ?>
						<?php else : ?>
							<tr>
								<td>
									<?php
									echo esc_html_e( 'No abandoned carts recorded.', ACTIVECAMPAIGN_FOR_WOOCOMMERCE_LOCALIZATION_DOMAIN );
									?>
								</td>
							</tr>
						<?php endif; ?>
					</tbody>
				</table>
			</form>
		</div>
	</section>
	<div id="abcartmodal" class="abandoned-cart-detail-container">
		<div class="abandoned-cart-detail-modal">
			<div class="abandoned-cart-details-close">
				<div class="button"><?php esc_html_e( 'X', ACTIVECAMPAIGN_FOR_WOOCOMMERCE_LOCALIZATION_DOMAIN ); ?></div>
			</div>
			<div class="abandoned-cart-details">
			</div>
		</div>
	</div>
</div>

