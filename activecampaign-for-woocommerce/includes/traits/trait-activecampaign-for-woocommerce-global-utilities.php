<?php

use Activecampaign_For_Woocommerce_Logger as Logger;

/**
 * The file that defines the Global Utilities.
 *
 * @link       https://www.activecampaign.com/
 * @since      1.x
 *
 * @package    Activecampaign_For_Woocommerce
 */

/**
 * The Utilities Class.
 *
 * @since      1.0.0
 * @package    Activecampaign_For_Woocommerce
 * @subpackage Activecampaign_For_Woocommerce/includes/events
 * @author     acteamintegrations <team-integrations@activecampaign.com>
 */
trait Activecampaign_For_Woocommerce_Global_Utilities {

	/**
	 * Verify our plugin is configured
	 *
	 * @return bool
	 */
	private function is_configured() {
		$ops = $this->get_local_settings();
		if ( ! $ops ||
			 ! $ops['api_key'] ||
			 ! $ops['api_url']
		) {
			return false;
		}

		return true;
	}

	/**
	 * Returns the options values in the DB.
	 * Also known as get settings.
	 *
	 * @deprecated use get_ac_settings() instead.
	 * @return array
	 */
	private function get_options() {
		if ( ACTIVECAMPAIGN_FOR_WOOCOMMERCE_DB_SETTINGS_NAME ) {
			return get_option( ACTIVECAMPAIGN_FOR_WOOCOMMERCE_DB_SETTINGS_NAME );
		} else {
			return get_option( 'activecampaign_for_woocommerce_settings' );
		}
	}

	/**
	 * Returns the options values in the DB.
	 * Also known as get settings.
	 *
	 * @return array
	 */
	private function get_ac_settings() {
		if ( ACTIVECAMPAIGN_FOR_WOOCOMMERCE_DB_SETTINGS_NAME ) {
			return get_option( ACTIVECAMPAIGN_FOR_WOOCOMMERCE_DB_SETTINGS_NAME );
		} else {
			return get_option( 'activecampaign_for_woocommerce_settings' );
		}
	}

	/**
	 * Returns the storage values in the DB.
	 *
	 * @return array
	 */
	private function get_storage() {
		if ( ACTIVECAMPAIGN_FOR_WOOCOMMERCE_DB_CONNECTION_STORAGE_NAME ) {
			return get_option( ACTIVECAMPAIGN_FOR_WOOCOMMERCE_DB_CONNECTION_STORAGE_NAME );
		} else {
			return get_option( 'activecampaign_for_woocommerce_storage' );
		}
	}

	/**
	 * Verify our plugin has connection_id
	 *
	 * @return bool
	 */
	private function is_connected() {
		if (
			! isset( $this->get_connection_storage()['connection_id'] ) ||
			empty( $this->get_connection_storage()['connection_id'] )
		) {
			return false;
		}
		return true;
	}

	/**
	 * Allows WPDB to do a bulk insert.
	 *
	 * @param     string $table     The table name.
	 * @param     array  $rows     The rows of data to insert.
	 *
	 * @return bool|int|null
	 */
	private function wpdb_bulk_insert( $table, $rows ) {
		global $wpdb;
		$logger = new Logger();
		try {
			// Extract column list from first row of data
			$columns = array_keys( $rows[0] );
			asort( $columns );
			$column_list = '`' . implode( '`, `', $columns ) . '`';
			unset( $columns ); // save mem

			// Start building SQL, initialise data and placeholder arrays
			$sql = "REPLACE INTO `$table` ($column_list) VALUES\n";
			unset( $column_list ); // save mem

			$placeholders = array();
			$data         = array();

			// Build placeholders for each row, and add values to data array
			foreach ( $rows as $row ) {
				ksort( $row );
				$row_placeholders = array();

				foreach ( $row as $value ) {
					$data[]             = esc_sql( $value );
					$row_placeholders[] = is_numeric( $value ) ? '%d' : '%s';
				}

				$placeholders[] = '(' . implode( ', ', $row_placeholders ) . ')';
			}

			unset( $rows );

			// Stitch all rows together
			$sql .= implode( ",\n", $placeholders );
			unset( $placeholders );
			// phpcs:disable
			$query = $wpdb->query( $wpdb->prepare( $sql, $data ) );
			// phpcs:enable
			if ( isset( $wpdb->last_error ) && ! empty( $wpdb->last_error ) ) {
				$logger->warning(
					'There was an error inserting records into the table',
					[
						'error' => $wpdb->last_error,
						'func'  => 'wpdb_bulk_insert',
					]
				);
			}

			return $query;
		} catch ( Throwable $t ) {
			$logger->debug(
				'There was an issue with bulk DB insert',
				[
					'message' => $t->getMessage(),
					'trace'   => $logger->clean_trace( $t->getTrace() ),
				]
			);

			return null;
		}
	}


	/**
	 * Allows for WPDB to run an update in query.
	 *
	 * @param     string      $table     The table name.
	 * @param     array       $data     The data to set example: array( 'synced_to_ac' => 3 ).
	 * @param     array       $where     The where in array example: array( 'wc_order_id' => $exclude ).
	 * @param     array|null  $format     The format example: array( '%d' ).
	 * @param     string|null $where_format     The where format example: '%d'.
	 *
	 * @return bool|int
	 */
	private function wpdb_update_in( $table, $data, $where, $format = null, $where_format = null ) {

		global $wpdb;
		try {
			$table = esc_sql( $table );

			if ( ! is_string( $table ) ) {
				return false;
			}

			$i       = 0;
			$q       = 'UPDATE ' . $table . ' SET ';
			$format  = array_values( (array) $format );
			$escaped = array();

			foreach ( (array) $data as $key => $value ) {
				if ( isset( $format[ $i ] ) && in_array( $format[ $i ], array( '%s', '%d' ), true ) ) {
					$f = $format[ $i ];
				} else {
					$f = '%s';
				}

				// phpcs:disable
				$escaped[] = esc_sql( $key ) . ' = ' . $wpdb->prepare( $f, $value );
				// phpcs:enable

				$i ++;
			}

			$q         .= implode( ', ', $escaped );
			$where      = (array) $where;
			$where_keys = array_keys( $where );
			$where_val  = (array) array_shift( $where );
			$q         .= ' WHERE ' . esc_sql( array_shift( $where_keys ) ) . ' IN (';

			if ( ! in_array( $where_format, array( '%s', '%d' ), true ) ) {
				$where_format = '%s';
			}

			$escaped = array();

			foreach ( $where_val as $val ) {
				// phpcs:disable
				$escaped[] = $wpdb->prepare( $where_format, $val );
				// phpcs:enable
			}

			$q .= implode( ', ', $escaped ) . ')';
		} catch ( Throwable $t ) {
			$logger = new Logger();
			$logger->debug( 'wpdb_update_in problem', [ $t->getMessage() ] );
		}

		try {
			// phpcs:disable
			$result = $wpdb->query( $q );
			// phpcs:enable

			return $result;
		} catch ( Throwable $t ) {
			$logger = new Logger();
			$logger->debug( 'wpdb_update_in problem', [ $t->getMessage() ] );

			return null;
		}
	}
}
