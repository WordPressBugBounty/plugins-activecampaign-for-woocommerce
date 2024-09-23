<?php

	/**
	 * Status display
	 *
	 * @package    Activecampaign_For_Woocommerce
	 */

	global $wpdb;

?>

<style>
	.border td{
		border-top:1px solid #ccc;
		border-bottom:1px solid #ccc;
	}
	#log-viewer-container{
		border: 1px solid #c3c4c7;
		box-shadow: 0 1px 1px rgba(0,0,0,.04);
	}
	#log-viewer-select{
		padding: 10px;
		background: white;
		border-bottom: 1px solid #c3c4c7;
	}
	#log-viewer{
		padding: 10px;
		background: white;
		overflow: auto;
		max-height: 500px;
	}
</style>
<div id="activecampaign_status" label="
	<?php

		esc_html_e( 'Status', ACTIVECAMPAIGN_FOR_WOOCOMMERCE_LOCALIZATION_DOMAIN );
	?>
	">
	<?php
		require plugin_dir_path( __FILE__ ) . '../partials/activecampaign-for-woocommerce-header.php';
	?>
	<div id="activecampaign_status_copy_button">
		<span id="copyStatus"></span>
		<button id="copyButton" class="activecampaign-for-woocommerce button secondary">Copy to clipboard</button>
	</div>
	<table class="wc_status_table widefat status_activecampaign_checklist" cellspacing="0">
		<thead>
		<tr>
			<th colspan="3" data-export-label="Theme">
				<h2><?php esc_html_e( 'ActiveCampaign Checklist', ACTIVECAMPAIGN_FOR_WOOCOMMERCE_LOCALIZATION_DOMAIN ); ?></h2>
			</th>
		</tr>
		</thead>
		<tbody>
		<tr>
			<td>
				<?php
				esc_html_e( 'ActiveCampaign for WooCommerce plugin up to date: ', ACTIVECAMPAIGN_FOR_WOOCOMMERCE_LOCALIZATION_DOMAIN );
				?>
			</td>
			<td>
				<?php if ( isset( $activecampaign_for_woocommerce_status_data['plugin_data']->update ) && ! empty( $activecampaign_for_woocommerce_status_data['plugin_data']->update->new_version ) && ACTIVECAMPAIGN_FOR_WOOCOMMERCE_VERSION !== $activecampaign_for_woocommerce_status_data['plugin_data']->update->new_version ) : ?>
					<span class="notice error">
						<?php echo esc_html_e( 'Your plugin is outdated. Please update to version ', ACTIVECAMPAIGN_FOR_WOOCOMMERCE_LOCALIZATION_DOMAIN ); ?>
						<?php echo esc_html( $activecampaign_for_woocommerce_status_data['plugin_data']->update->new_version ); ?>
					</span>
				<?php else : ?>
					<?php $this->output_yes_mark( esc_html( ACTIVECAMPAIGN_FOR_WOOCOMMERCE_VERSION ) ); ?>
				<?php endif; ?>
			</td>
		</tr>
		<tr>
			<td>Plugin configured:</td>
			<td>
				<?php if ( isset( $this->get_options()['api_key'], $this->get_options()['api_url'] ) ) : ?>
					<?php $this->output_yes_mark( $this->get_options()['api_url'] . '|' . $this->get_options()['api_key'] ); ?>
				<?php else : ?>
					<mark class="error">
						<?php if ( ! isset( $this->get_options()['api_url'] ) ) : ?>
							API URL missing <br/>
						<?php endif; ?>
						<?php if ( ! isset( $this->get_options()['api_key'] ) ) : ?>
							API Key missing
						<?php endif; ?>
					</mark>
				<?php endif; ?>
			</td>
		</tr>
		<tr>
			<td>Plugin connected</td>
			<td>
				<?php if ( false !== $this->connection_health_check() ) : ?>
					<?php $this->output_yes_mark( 'Connected' ); ?>
				<?php endif; ?>
			</td>
		</tr>
		<tr>
			<td>
				<?php
				esc_html_e( 'ActiveCampaign connection managed in: ', ACTIVECAMPAIGN_FOR_WOOCOMMERCE_LOCALIZATION_DOMAIN );
				?>
			</td>
			<td>
				<?php if ( 0 === $this->get_storage()['is_internal'] || '0' === $this->get_storage()['is_internal'] ) : ?>
					<?php $this->output_yes_mark( 'Third Party managed in WooCommerce' ); ?>
				<?php elseif ( 1 === $this->get_storage()['is_internal'] || '1' === $this->get_storage()['is_internal'] ) : ?>
					<?php $this->output_yes_mark( 'Internal Integration managed in Hosted' ); ?>
				<?php else : ?>
					<?php $this->output_yes_mark( 'Third Party managed in WooCommerce' ); ?>
				<?php endif; ?>
			</td>
		</tr>
		<tr>
			<td>
				<?php
				esc_html_e( 'ActiveCampaign connection ID is set: ', ACTIVECAMPAIGN_FOR_WOOCOMMERCE_LOCALIZATION_DOMAIN );
				?>
			</td>
			<td>
				<?php if ( empty( $this->get_storage() ) || ! $this->get_storage() || ! isset( $this->get_storage()['connection_id'] ) ) : ?>
					<?php esc_html_e( 'Error: No connection ID found in settings! ', ACTIVECAMPAIGN_FOR_WOOCOMMERCE_LOCALIZATION_DOMAIN ); ?>
				<?php else : ?>
					<?php $this->output_yes_mark( esc_html( $this->get_storage()['connection_id'] ) ); ?>
				<?php endif; ?>
			</td>
		</tr>
		<tr>
			<td>AC Database Verison</td>
			<td>
				<?php if ( ACTIVECAMPAIGN_FOR_WOOCOMMERCE_DB_VERSION === get_option( 'activecampaign_for_woocommerce_db_version' ) ) : ?>
					<?php $this->output_yes_mark( esc_html( get_option( 'activecampaign_for_woocommerce_db_version' ) ) ); ?>
				<?php else : ?>
					<mark class="error"><?php echo esc_html( get_option( 'activecampaign_for_woocommerce_db_version' ) ); ?></mark>
				<?php endif; ?>
			</td>
		</tr>
		<tr>
			<td>
				ActiveCampaign Table Exists?
			</td>
			<td>
				<?php if ( true === $activecampaign_for_woocommerce_status_data['table_exists'] ) : ?>
					<?php $this->output_yes_mark( esc_html( ACTIVECAMPAIGN_FOR_WOOCOMMERCE_TABLE_NAME ) ); ?>
				<?php endif; ?>
			</td>
		</tr>
		<tr><td colspan="2"><hr/></td></tr>

		<?php if ( isset( $activecampaign_for_woocommerce_status_data['wc_database'] ) ) : ?>
		<tr>
			<td data-export-label="Database Prefix"><?php esc_html_e( 'Database prefix', ACTIVECAMPAIGN_FOR_WOOCOMMERCE_LOCALIZATION_DOMAIN ); ?></td>
			<td>
				<?php
				if ( strlen( $activecampaign_for_woocommerce_status_data['wc_database']['database_prefix'] ) > 20 ) {
					/* Translators: %1$s: Database prefix, %2$s: Docs link. */
					echo '<mark class="error"><span class="dashicons dashicons-warning"></span> ' . sprintf( esc_html__( '%1$s - We recommend using a prefix with less than 20 characters. See: %2$s', ACTIVECAMPAIGN_FOR_WOOCOMMERCE_LOCALIZATION_DOMAIN ), esc_html( $activecampaign_for_woocommerce_status_data['wc_database']['database_prefix'] ), '<a href="https://docs.woocommerce.com/document/completed-order-email-doesnt-contain-download-links/#section-2" target="_blank">' . esc_html__( 'How to update your database table prefix', ACTIVECAMPAIGN_FOR_WOOCOMMERCE_LOCALIZATION_DOMAIN ) . '</a>' ) . '</mark>';
				} else {
					$this->output_yes_mark( esc_html( $activecampaign_for_woocommerce_status_data['wc_database']['database_prefix'] ) );
				}
				?>
			</td>
		</tr>
		<?php endif; ?>

		<?php if ( isset( $activecampaign_for_woocommerce_status_data['wc_environment'] ) ) : ?>
			<tr>
				<td><?php esc_html_e( 'WooCommerce version meets requirements', ACTIVECAMPAIGN_FOR_WOOCOMMERCE_LOCALIZATION_DOMAIN ); ?>
					:
				</td>
				<td>
					<?php if ( $activecampaign_for_woocommerce_status_data['wc_environment']['version'] > 7 ) : ?>
						<?php $this->output_yes_mark( esc_html( $activecampaign_for_woocommerce_status_data['wc_environment']['version'] ) ); ?>
					<?php else : ?>
						<mark class="error"><span class="dashicons dashicons-warning"></span> Version is below tested compatibility requirements.</mark>
					Version: <?php echo esc_html( $activecampaign_for_woocommerce_status_data['wc_environment']['version'] ); ?>
					<?php endif; ?>
				</td>
			</tr>

			<tr>
				<td data-export-label="MySQL Version"><?php esc_html_e( 'MySQL version', ACTIVECAMPAIGN_FOR_WOOCOMMERCE_LOCALIZATION_DOMAIN ); ?>
					:
				</td>
				<td>
					<?php
					if ( version_compare( $activecampaign_for_woocommerce_status_data['wc_environment']['mysql_version'], '5.6', '<' ) && ! strstr( $activecampaign_for_woocommerce_status_data['wc_environment']['mysql_version_string'], 'MariaDB' ) ) {
						/* Translators: %1$s: MySQL version, %2$s: Recommended MySQL version. */
						$this->output_err_mark( sprintf( esc_html__( '%1$s - We recommend a minimum MySQL version of 5.6. See: %2$s', ACTIVECAMPAIGN_FOR_WOOCOMMERCE_LOCALIZATION_DOMAIN ), esc_html( $activecampaign_for_woocommerce_status_data['wc_environment']['mysql_version_string'] ), '<a href="https://wordpress.org/about/requirements/" target="_blank">' . esc_html__( 'WordPress requirements', ACTIVECAMPAIGN_FOR_WOOCOMMERCE_LOCALIZATION_DOMAIN ) . '</a>' ) );
					} else {
						$this->output_yes_mark( esc_html( $activecampaign_for_woocommerce_status_data['wc_environment']['mysql_version_string'] ) );
					}
					?>
				</td>
			</tr>

			<tr>
				<td><?php esc_html_e( 'WordPress version meets requirements', ACTIVECAMPAIGN_FOR_WOOCOMMERCE_LOCALIZATION_DOMAIN ); ?>
					:
				</td>
				<td>
					<?php
					if ( isset( $activecampaign_for_woocommerce_status_data['wp_version']['newer'] ) && true === $activecampaign_for_woocommerce_status_data['wp_version']['newer'] ) {
						/* Translators: %1$s: Current version, %2$s: New version */
						echo '<mark class="error"><span class="dashicons dashicons-warning"></span> ' . sprintf( esc_html__( '%1$s - There is a newer version of WordPress available (%2$s)', ACTIVECAMPAIGN_FOR_WOOCOMMERCE_LOCALIZATION_DOMAIN ), esc_html( $activecampaign_for_woocommerce_status_data['wc_environment']['wp_version'] ), esc_html( $activecampaign_for_woocommerce_latest_version ) ) . '</mark>';
					}
					?>
					<?php if ( true === $activecampaign_for_woocommerce_status_data['wp_version']['meets'] ) : ?>
						<?php $this->output_yes_mark( esc_html( $activecampaign_for_woocommerce_status_data['wp_version']['number'] ) ); ?>
					<?php else : ?>
						<mark class="error"><?php echo esc_html( $activecampaign_for_woocommerce_status_data['wp_version']['number'] ); ?> <?php esc_html_e( 'This version of WordPress may not be supported.', ACTIVECAMPAIGN_FOR_WOOCOMMERCE_LOCALIZATION_DOMAIN ); ?></mark>
					<?php endif; ?>
				</td>
			</tr>
			<tr>
				<td data-export-label="PHP Version"><?php esc_html_e( 'PHP version', ACTIVECAMPAIGN_FOR_WOOCOMMERCE_LOCALIZATION_DOMAIN ); ?>
					:
				</td>
				<td>
					<?php
					if ( $activecampaign_for_woocommerce_status_data['php_version']['php_supported'] ) {
						$this->output_yes_mark( esc_html( $activecampaign_for_woocommerce_status_data['wc_environment']['php_version'] ) );
					} else {
						$activecampaign_for_woocommerce_update_link = ' <a href="https://docs.woocommerce.com/document/how-to-update-your-php-version/" target="_blank">' . esc_html__( 'How to update your PHP version', ACTIVECAMPAIGN_FOR_WOOCOMMERCE_LOCALIZATION_DOMAIN ) . '</a>';

						$activecampaign_for_woocommerce_notice = '<span class="dashicons dashicons-warning"></span> ' . __( 'ActiveCampaign will run under this version of PHP, however, some features are not compatible and may not be supported. We recommend using PHP version 7.4+ and below 8.1 due to compatibility.', ACTIVECAMPAIGN_FOR_WOOCOMMERCE_LOCALIZATION_DOMAIN ) . $activecampaign_for_woocommerce_update_link;

						echo '<mark class="recommendation">' . esc_html( $activecampaign_for_woocommerce_status_data['php_version']['local_php'] ) . ' - ' . wp_kses_post( $activecampaign_for_woocommerce_notice ) . '</mark>';
					}
					?>
				</td>
			</tr>
			<tr>
				<td data-export-label="WP Memory Limit"><?php esc_html_e( 'WordPress memory limit', ACTIVECAMPAIGN_FOR_WOOCOMMERCE_LOCALIZATION_DOMAIN ); ?>
					:
				</td>
				<td>
					<?php
					if ( $activecampaign_for_woocommerce_status_data['wc_environment']['wp_memory_limit'] < 67108864 ) {
						/* Translators: %1$s: Memory limit, %2$s: Docs link. */
						echo '<mark class="error"><span class="dashicons dashicons-warning"></span> ' . sprintf( esc_html__( '%1$s - We recommend setting memory to at least 64MB. See: %2$s', ACTIVECAMPAIGN_FOR_WOOCOMMERCE_LOCALIZATION_DOMAIN ), esc_html( size_format( $activecampaign_for_woocommerce_status_data['wc_environment']['wp_memory_limit'] ) ), '<a href="https://wordpress.org/support/article/editing-wp-config-php/#increasing-memory-allocated-to-php" target="_blank">' . esc_html__( 'Increasing memory allocated to PHP', ACTIVECAMPAIGN_FOR_WOOCOMMERCE_LOCALIZATION_DOMAIN ) . '</a>' ) . '</mark>';
					} else {
						$this->output_yes_mark( esc_html( size_format( $activecampaign_for_woocommerce_status_data['wc_environment']['wp_memory_limit'] ) ) );
					}
					?>
				</td>
			</tr>

			<tr>
				<td>Disk Space</td>
				<td>
					<?php if ( isset( $activecampaign_for_woocommerce_status_data['disk_space']['readable'], $activecampaign_for_woocommerce_status_data['disk_space']['percent'] ) && ! empty( $activecampaign_for_woocommerce_status_data['disk_space']['readable'] ) && $activecampaign_for_woocommerce_status_data['disk_space']['percent'] < 95 ) : ?>
						<?php $this->output_yes_mark( $activecampaign_for_woocommerce_status_data['disk_space']['readable'] ); ?>
					<?php else : ?>
						<?php $this->output_err_mark( $activecampaign_for_woocommerce_status_data['disk_space']['readable'] ); ?>
					<?php endif; ?>
				</td>
			</tr>
		<?php else : ?>
			<tr><td colspan="2">WooCommerce Environment data could not be loaded.</td></tr>
		<?php endif; ?>
		<tr><td colspan="2"><hr/></td></tr>
		<tr>
			<td><?php esc_html_e( 'Abandoned Cart Cron scheduled', ACTIVECAMPAIGN_FOR_WOOCOMMERCE_LOCALIZATION_DOMAIN ); ?></td>
			<td>
				<?php if ( ! $activecampaign_for_woocommerce_status_data['abandoned_schedule']['error'] ) : ?>
					<?php $this->output_yes_mark( 'Next scheduled: ' . esc_html( $activecampaign_for_woocommerce_status_data['abandoned_schedule']['timestamp'] ) ); ?>
				<?php else : ?>
					<mark class="error"><?php esc_html_e( 'Warning! Abandoned cron may not be scheduled.', ACTIVECAMPAIGN_FOR_WOOCOMMERCE_LOCALIZATION_DOMAIN ); ?></mark>
				<?php endif; ?>
			</td>
		</tr>
		<tr>
			<td><?php esc_html_e( 'Order sync Cron scheduled', ACTIVECAMPAIGN_FOR_WOOCOMMERCE_LOCALIZATION_DOMAIN ); ?></td>
			<td>
				<?php if ( ! $activecampaign_for_woocommerce_status_data['new_order_schedule']['error'] ) : ?>
					<?php $this->output_yes_mark( 'Next scheduled: ' . esc_html( $activecampaign_for_woocommerce_status_data['new_order_schedule']['timestamp'] ) ); ?>
				<?php else : ?>
					<mark class="error"><?php esc_html_e( 'Warning! Order sync cron may not be scheduled.', ACTIVECAMPAIGN_FOR_WOOCOMMERCE_LOCALIZATION_DOMAIN ); ?></mark>
				<?php endif; ?>
			</td>
		</tr>
		<tr>
			<td><?php esc_html_e( 'Historical sync Cron scheduled', ACTIVECAMPAIGN_FOR_WOOCOMMERCE_LOCALIZATION_DOMAIN ); ?></td>
			<td>
				<?php if ( ! $activecampaign_for_woocommerce_status_data['historical_order_schedule']['error'] ) : ?>
					<?php $this->output_yes_mark( 'Next scheduled: ' . esc_html( $activecampaign_for_woocommerce_status_data['historical_order_schedule']['timestamp'] ) ); ?>
				<?php else : ?>
					<mark class="error"><?php esc_html_e( 'Warning! Historical sync cron may not be scheduled.', ACTIVECAMPAIGN_FOR_WOOCOMMERCE_LOCALIZATION_DOMAIN ); ?></mark>
				<?php endif; ?>
			</td>
		</tr>

		<tr>
			<td>Permalink Set Properly:</td>
			<td>
				<?php if ( isset( $activecampaign_for_woocommerce_status_data['permalink_structure'] ) && ! empty( $activecampaign_for_woocommerce_status_data['permalink_structure'] ) ) : ?>
					<?php $this->output_yes_mark( $activecampaign_for_woocommerce_status_data['permalink_structure'] ); ?>
				<?php else : ?>
					<?php $this->output_err_mark( 'Invalid selection' ); ?>
				<?php endif; ?>
			</td>
		</tr>
		<tr>
			<td>
				Reported issues in error list?
			</td>
			<td>
				<?php if ( $activecampaign_for_woocommerce_status_data['recent_log_errors'] ) : ?>
					<mark class="error"><?php echo esc_html( $activecampaign_for_woocommerce_status_data['log_errors_count'] ); ?> errors recorded, please check logs for recent errors.</mark>
				<?php else : ?>
					<?php $this->output_yes_mark( 'No issues found' ); ?>
				<?php endif; ?>
			</td>
		</tr>
		</tbody>
	</table>
	<table class="wc_status_table widefat status_activecampaign" cellspacing="0">
		<thead>
		<tr>
			<th colspan="3" data-export-label="Theme">
				<h2><?php esc_html_e( 'Advanced Status', ACTIVECAMPAIGN_FOR_WOOCOMMERCE_LOCALIZATION_DOMAIN ); ?></h2>
			</th>
		</tr>
		</thead>
		<tbody>
		<tr>
			<td style="min-width:150px;">
				System Post Types:
			</td>
			<td>
			<?php echo esc_html( implode( ', ', get_post_types() ) ); ?>
			</td>
		</tr>
		<tr>
			<td>
				Hook Checks<br/>
				<i>(Last known run time in past 7 days)</i>
			</td>
			<td>
				Cart updated: <?php echo esc_html( get_transient( 'acforwc_cart_updated_hook' ) ); ?><br/>
				Cart to Order transition: <?php echo esc_html( get_transient( 'acforwc_cart_to_order_transition_hook' ) ); ?><br/>
				Order created: <?php echo esc_html( get_transient( 'acforwc_order_created_hook' ) ); ?><br/>
				Order updated: <?php echo esc_html( get_transient( 'acforwc_order_updated_hook' ) ); ?><br/>
				Order deleted: <?php echo esc_html( get_transient( 'acforwc_order_deleted_hook' ) ); ?><br/>
				Abandoned cart task: <?php echo esc_html( get_transient( 'acforwc_abandoned_task_hook' ) ); ?><br/>
			</td>
		</tr>
		</tbody>
	</table>
	<hr />
	<table class="wc_status_table widefat status_activecampaign" cellspacing="0">
		<thead>
		<tr>
			<th colspan="3" data-export-label="Theme">
				<h2><?php esc_html_e( 'ActiveCampaign Order Status', ACTIVECAMPAIGN_FOR_WOOCOMMERCE_LOCALIZATION_DOMAIN ); ?></h2>
			</th>
		</tr>
		</thead>
		<tbody>
		<tr class="border">
			<td><?php esc_html_e( 'Last time order sync ran:', ACTIVECAMPAIGN_FOR_WOOCOMMERCE_LOCALIZATION_DOMAIN ); ?></td>
			<td>
				<?php if ( isset( $activecampaign_for_woocommerce_status_data['last_order_interval_minutes'] ) ) : ?>
					<?php if ( $activecampaign_for_woocommerce_status_data['last_order_interval_minutes'] >= 0 && $activecampaign_for_woocommerce_status_data['last_order_interval_minutes'] <= 1440 ) : ?>
						<?php $this->output_yes_mark( $activecampaign_for_woocommerce_status_data['last_order_interval_minutes'] . ' minutes ago' ); ?>
					<?php else : ?>
						<mark class="error">Could not confirm the last order sync</mark>
					<?php endif; ?>
					<br/>
				<?php else : ?>
					<mark class="error">Could not confirm the last order sync</mark>
				<?php endif; ?>
			</td>
		</tr>
		<tr>
			<td>
				<?php esc_html_e( 'Last abandoned sync attempt:', ACTIVECAMPAIGN_FOR_WOOCOMMERCE_LOCALIZATION_DOMAIN ); ?>
			</td>
			<td>
				<?php if ( isset( $activecampaign_for_woocommerce_status_data['abandoned_interval_minutes'] ) && $activecampaign_for_woocommerce_status_data['abandoned_interval_minutes'] >= 0 ) : ?>
					<?php echo esc_html( $activecampaign_for_woocommerce_status_data['abandoned_interval_minutes'] ); ?> <?php esc_html_e( 'minutes ago', ACTIVECAMPAIGN_FOR_WOOCOMMERCE_LOCALIZATION_DOMAIN ); ?>
					<br/>
				<?php endif; ?>
			</td>
		</tr>
		<tr>
			<td>
				Recent syncing results:
			</td>
			<td>
				<?php foreach ( $activecampaign_for_woocommerce_status_data['synced_results'] as $activecampaign_for_woocommerce_synced_result ) : ?>
					<?php
					switch ( $activecampaign_for_woocommerce_synced_result->synced_to_ac ) {
						case 0:
							echo 'Unsynced Recent Orders: ';
							break;
						case 1:
							echo 'Recently Synced Orders: ';
							break;
						case 2:
							echo 'Orders On Hold: ';
							break;
						case 3:
							echo 'Pending for Historical Sync: ';
							break;
						case 4:
							echo 'Ready for Historical Sync: ';
							break;
						case 5:
							echo 'Finished Historical Sync: ';
							break;
						case 6:
							echo 'Incompatible Records: ';
							break;
						case 7:
							echo 'Subscription Records: ';
							break;
						case 8:
							echo 'Refund Records: ';
							break;
						case 9:
							echo 'Failed Records: ';
							break;
					}
					?>
					<?php echo esc_html( $activecampaign_for_woocommerce_synced_result->count ); ?>
					<br/>
				<?php endforeach; ?>

				<?php foreach ( $activecampaign_for_woocommerce_status_data['abandoned_results'] as $activecampaign_for_woocommerce_abandoned_result ) : ?>
					<?php
					switch ( $activecampaign_for_woocommerce_abandoned_result->synced_to_ac ) {
						case 0:
							echo 'Current Unsynced Abandoned Carts: ';
							break;
						case 1:
							echo 'Synced Abandoned Carts: ';
							break;
						case 8:
						case 9:
							echo 'Failed Records: ';
							break;
						case 20:
							echo 'Unsynced abandoned carts: ';
							break;
						case 21:
						case 22:
						case 23:
							echo 'Recently Synced abandoned carts: ';
							break;
						case 26:
						case 27:
						case 28:
							echo 'Abandoned carts failed - retry: ';
							break;
						case 29:
							echo 'Abandoned carts permanently failed: ';
							break;
					}
					?>
					<?php echo esc_html( $activecampaign_for_woocommerce_abandoned_result->count ); ?>
					<small>(within the past 2 weeks)</small>
					<br/>

				<?php endforeach; ?>
			</td>
		</tr>
		</tbody>
	</table>
	<hr />
	<table class="wc_status_table widefat status_activecampaign_errors" cellspacing="0">
		<div class="status-split">
			<a href="#log-viewer-container">See the ActiveCampaign for WooCommerce logs for more info</a>
			<span id="activecampaign-for-woocommerce-clear-error-log">
				<?php
				wp_nonce_field( 'activecampaign_for_woocommerce_status_form', 'activecampaign_for_woocommerce_settings_nonce_field' );
				?>
				<?php if ( $activecampaign_for_woocommerce_status_data['recent_log_errors'] ) : ?>
					<span class="button-secondary" href="#" title="Clear Log Errors">Clear Log Errors</span>
				<?php else : ?>
					<span class="button-secondary button-disabled" href="#" title="Clear Log Errors">Clear Log Errors</span>
				<?php endif; ?>
			</span>
		</div>
		<div id="activecampaign-for-woocommerce-clear-error-log-result"></div>
		<thead>
		<tr>
			<td style="min-width:200px;">
				<?php echo esc_html( $activecampaign_for_woocommerce_status_data['log_errors_count'] ); ?> ActiveCampaign for WooCommerce errors recorded
			</td>
			<td>
				Timestamp
			</td>
			<td>
				Context
			</td>
		</tr>
		</thead>
		<tbody>
		<?php if ( $activecampaign_for_woocommerce_status_data['recent_log_errors'] ) : ?>
			<?php foreach ( $activecampaign_for_woocommerce_status_data['recent_log_errors'] as $activecampaign_for_woocommerce_err ) : ?>
				<tr>
					<td>
						<div class="td-container">
							<?php echo esc_html( $activecampaign_for_woocommerce_err->message ); ?>
						</div>
					</td>
					<td style="min-width:150px">
						<?php if ( is_null( $activecampaign_for_woocommerce_err->timestamp ) ) : ?>
							<div class="td-container no-context">
								<?php echo esc_html( 'No context available' ); ?>
							</div>
						<?php else : ?>
							<div class="td-container">
								<?php echo esc_html( wp_json_encode( maybe_unserialize( $activecampaign_for_woocommerce_err->timestamp ) ) ); ?>
							</div>
						<?php endif; ?>
					</td>
					<td>
						<?php if ( is_null( $activecampaign_for_woocommerce_err->context ) ) : ?>
							<div class="td-container no-context">
								<?php echo esc_html( 'No context available' ); ?>
							</div>
						<?php else : ?>
							<div class="td-container">
								<?php echo esc_html( wp_json_encode( maybe_unserialize( $activecampaign_for_woocommerce_err->context ) ) ); ?>
							</div>
						<?php endif; ?>
					</td>
				</tr>
			<?php endforeach; ?>
		<?php else : ?>
			<tr>
				<td>
					There are no errors at this time.
				</td>
			</tr>
		<?php endif; ?>
		</tbody>
	</table>
	<table class="wc_status_table widefat status_wordpress_env" cellspacing="0" id="status">
		<thead>
		<tr>
			<th colspan="3" data-export-label="Other Store Details">
				<h2><?php esc_html_e( 'Other Store Details', ACTIVECAMPAIGN_FOR_WOOCOMMERCE_LOCALIZATION_DOMAIN ); ?></h2>
			</th>
		</tr>
		</thead>
		<tbody>
		<tr>
			<td data-export-label="WordPress address (URL)"><?php esc_html_e( 'WordPress address (URL)', ACTIVECAMPAIGN_FOR_WOOCOMMERCE_LOCALIZATION_DOMAIN ); ?>
				:
			</td>
			<td><?php echo esc_html( $activecampaign_for_woocommerce_status_data['wc_environment']['site_url'] ); ?></td>
		</tr>
		<tr>
			<td data-export-label="Site address (URL)"><?php esc_html_e( 'Site address (URL)', ACTIVECAMPAIGN_FOR_WOOCOMMERCE_LOCALIZATION_DOMAIN ); ?>
				:
			</td>
			<td><?php echo esc_html( $activecampaign_for_woocommerce_status_data['wc_environment']['home_url'] ); ?></td>
		</tr>
		<tr>
			<td data-export-label="WP Multisite"><?php esc_html_e( 'WordPress multisite', ACTIVECAMPAIGN_FOR_WOOCOMMERCE_LOCALIZATION_DOMAIN ); ?>
				:
			</td>
			<td><?php echo ( $activecampaign_for_woocommerce_status_data['wc_environment']['wp_multisite'] ) ? '<span class="dashicons dashicons-yes"></span>' : '&ndash;'; ?></td>
		</tr>
		<tr>
			<td data-export-label="WP Debug Mode"><?php esc_html_e( 'WordPress debug mode', ACTIVECAMPAIGN_FOR_WOOCOMMERCE_LOCALIZATION_DOMAIN ); ?>
				:
			</td>
			<td>
				<?php if ( $activecampaign_for_woocommerce_status_data['wc_environment']['wp_debug_mode'] ) : ?>
					<mark class="no">Debug enabled</mark>
				<?php else : ?>
					<mark class="yes">&ndash;</mark>
				<?php endif; ?>
			</td>
		</tr>
		<tr>
			<td data-export-label="WP Cron"><?php esc_html_e( 'WordPress cron', ACTIVECAMPAIGN_FOR_WOOCOMMERCE_LOCALIZATION_DOMAIN ); ?>
				:
			</td>
			<td>
				<?php if ( $activecampaign_for_woocommerce_status_data['wc_environment']['wp_cron'] ) : ?>
					WP_CRON enabled
				<?php else : ?>
					&ndash;
				<?php endif; ?>
			</td>
		</tr>
		<tr>
			<td data-export-label="Language"><?php esc_html_e( 'Language', ACTIVECAMPAIGN_FOR_WOOCOMMERCE_LOCALIZATION_DOMAIN ); ?>
				:
			</td>
			<td><?php echo esc_html( $activecampaign_for_woocommerce_status_data['wc_environment']['language'] ); ?></td>
		</tr>
		<tr>
			<td data-export-label="Name"><?php esc_html_e( 'Theme Name [version]', ACTIVECAMPAIGN_FOR_WOOCOMMERCE_LOCALIZATION_DOMAIN ); ?>
				:
			</td>
			<td>
				<?php echo esc_html( $activecampaign_for_woocommerce_status_data['wc_theme']['name'] ); ?>
				<?php
				if ( isset( $activecampaign_for_woocommerce_status_data['wc_theme']['version'] ) ) {
					echo '[' . esc_html( $activecampaign_for_woocommerce_status_data['wc_theme']['version'] ) . ']';
				}
				?>
			</td>
		</tr>
		<?php if ( function_exists( 'ini_get' ) ) : ?>
			<tr>
				<td data-export-label="PHP Post Max Size"><?php esc_html_e( 'PHP post max size', ACTIVECAMPAIGN_FOR_WOOCOMMERCE_LOCALIZATION_DOMAIN ); ?>
					:
				</td>
				<td><?php echo esc_html( size_format( $activecampaign_for_woocommerce_status_data['wc_environment']['php_post_max_size'] ) ); ?></td>
			</tr>
			<tr>
				<td data-export-label="PHP Time Limit"><?php esc_html_e( 'PHP time limit', ACTIVECAMPAIGN_FOR_WOOCOMMERCE_LOCALIZATION_DOMAIN ); ?>
					:
				</td>
				<td><?php echo esc_html( $activecampaign_for_woocommerce_status_data['wc_environment']['php_max_execution_time'] ); ?></td>
			</tr>
			<tr>
				<td data-export-label="PHP Max Input Vars"><?php esc_html_e( 'PHP max input vars', ACTIVECAMPAIGN_FOR_WOOCOMMERCE_LOCALIZATION_DOMAIN ); ?>
					:
				</td>
				<td><?php echo esc_html( $activecampaign_for_woocommerce_status_data['wc_environment']['php_max_input_vars'] ); ?></td>
			</tr>
		<?php endif; ?>
		<?php if ( ! empty( $activecampaign_for_woocommerce_status_data['wc_database']['database_size'] ) && ! empty( $activecampaign_for_woocommerce_status_data['wc_database']['database_tables'] ) ) : ?>
			<tr>
				<td><?php esc_html_e( 'Total Database Size', ACTIVECAMPAIGN_FOR_WOOCOMMERCE_LOCALIZATION_DOMAIN ); ?></td>
				<td><?php printf( '%.2fMB', esc_html( $activecampaign_for_woocommerce_status_data['wc_database']['database_size']['data'] + $activecampaign_for_woocommerce_status_data['wc_database']['database_size']['index'] ) ); ?></td>
			</tr>

			<tr>
				<td><?php esc_html_e( 'Database Data Size', ACTIVECAMPAIGN_FOR_WOOCOMMERCE_LOCALIZATION_DOMAIN ); ?></td>
				<td><?php printf( '%.2fMB', esc_html( $activecampaign_for_woocommerce_status_data['wc_database']['database_size']['data'] ) ); ?></td>
			</tr>

			<tr>
				<td><?php esc_html_e( 'Database Index Size', ACTIVECAMPAIGN_FOR_WOOCOMMERCE_LOCALIZATION_DOMAIN ); ?></td>
				<td><?php printf( '%.2fMB', esc_html( $activecampaign_for_woocommerce_status_data['wc_database']['database_size']['index'] ) ); ?></td>
			</tr>
		<?php endif; ?>
		</tbody>
	</table>
	<hr/>
	<div id="log-viewer-container" class="wc_status_table widefat status_wordpress_env">
		<?php if ( isset( $activecampaign_for_woocommerce_status_data['viewed_log'] ) ) : ?>
			<div id="log-viewer-select">
				<div class="alignleft">
					<h2>
						<?php echo esc_html( $activecampaign_for_woocommerce_status_data['viewed_log'] ); ?>
					</h2>
				</div>
				<div class="alignright">
					<form action="<?php echo esc_url( admin_url( 'admin.php?page=activecampaign_for_woocommerce_status#log-viewer-container' ) ); ?>" method="post">
						<?php
						wp_nonce_field( 'activecampaign_for_woocommerce_settings_form', 'activecampaign_for_woocommerce_settings_nonce_field' );
						?>
						<select class="wc-enhanced-select" name="log_file">
							<?php foreach ( $activecampaign_for_woocommerce_status_data['logs'] as $activecampaign_for_woocommerce_log_key => $activecampaign_for_woocommerce_log_file ) : ?>
								<?php
								 $activecampaign_for_woocommerce_timestamp = filemtime( $activecampaign_for_woocommerce_status_data['logdir'] . $activecampaign_for_woocommerce_log_file );
								$activecampaign_for_woocommerce_date       = sprintf(
									/* translators: 1: last access date 2: last access time 3: last access timezone abbreviation */
									__( '%1$s at %2$s %3$s', ACTIVECAMPAIGN_FOR_WOOCOMMERCE_LOCALIZATION_DOMAIN ),
									wp_date( wc_date_format(), $activecampaign_for_woocommerce_timestamp ),
									wp_date( wc_time_format(), $activecampaign_for_woocommerce_timestamp ),
									wp_date( 'T', $activecampaign_for_woocommerce_timestamp )
								);
								?>
								<option value="<?php echo esc_attr( $activecampaign_for_woocommerce_log_file ); ?>" <?php selected( sanitize_title( $activecampaign_for_woocommerce_status_data['viewed_log'] ), $activecampaign_for_woocommerce_log_key ); ?>><?php echo esc_html( $activecampaign_for_woocommerce_log_file ); ?> (<?php echo esc_html( $activecampaign_for_woocommerce_date ); ?>)</option>
							<?php endforeach; ?>
						</select>
						<button type="submit" class="button" value="<?php esc_attr_e( 'View', ACTIVECAMPAIGN_FOR_WOOCOMMERCE_LOCALIZATION_DOMAIN ); ?>"><?php esc_html_e( 'View', ACTIVECAMPAIGN_FOR_WOOCOMMERCE_LOCALIZATION_DOMAIN ); ?></button>
						<button type="submit" class="button" name="save" value="<?php esc_attr_e( 'Save', ACTIVECAMPAIGN_FOR_WOOCOMMERCE_LOCALIZATION_DOMAIN ); ?>"><?php esc_html_e( 'Save', ACTIVECAMPAIGN_FOR_WOOCOMMERCE_LOCALIZATION_DOMAIN ); ?></button>
					</form>
				</div>
				<div class="clear"></div>
			</div>
			<div id="log-viewer">
					<?php if ( isset( $activecampaign_for_woocommerce_status_data['viewed_log_full_path'] ) ) : ?>
						<?php
						try {
							?>
							<?php if ( ! empty( $activecampaign_for_woocommerce_status_data['viewed_log_show_log'] ) ) : ?>
								<pre><?php echo esc_html( $activecampaign_for_woocommerce_status_data['viewed_log_show_log'] ); ?></pre>
							<?php else : ?>
								Could not get file contents.
							<?php endif; ?>
							<?php
						} catch ( Throwable $t ) {
							esc_html_e( 'Unable to get contents of log file. Path may be incorrect or user may be limited.', ACTIVECAMPAIGN_FOR_WOOCOMMERCE_LOCALIZATION_DOMAIN );
						}
						?>
				<?php else : ?>
					Log file could not be loaded.
				<?php endif; ?>
			</div>
		<?php else : ?>
			<div id="log-viewer">
				No ActiveCampaign for WooCommerce or Fatal Log files could be found.
			</div>
		<?php endif; ?>
	</div>
</div>
