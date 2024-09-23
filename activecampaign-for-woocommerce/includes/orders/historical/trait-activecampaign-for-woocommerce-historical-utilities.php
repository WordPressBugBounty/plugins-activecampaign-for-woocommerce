<?php

trait Activecampaign_For_Woocommerce_Historical_Utilities {
	/**
	 * Cleans bad data from our table. Bad data is any row that has all empty values for IDs we depend on.
	 */
	private function clean_bad_data_from_table() {
		global $wpdb;
		$table = $wpdb->prefix . ACTIVECAMPAIGN_FOR_WOOCOMMERCE_TABLE_NAME;
		// Zero is not a valid order id, erase it. Null orders should not be here if they are not abandoned.
		// phpcs:disable
		$wpdb->query(
			'DELETE FROM ' . $table . ' WHERE wc_order_id = 0 AND wc_order_id = NULL AND (ac_externalcheckoutid = NULL)'
		);
		// phpcs:enable
	}
}
