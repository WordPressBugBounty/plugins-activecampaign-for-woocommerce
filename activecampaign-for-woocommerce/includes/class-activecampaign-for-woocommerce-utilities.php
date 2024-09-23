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
class Activecampaign_For_Woocommerce_Utilities {
	/**
	 * Checks both post and get for values. WC seems to pass nonce as GET but fields pass as POST.
	 *
	 * @param     string $field     The field name.
	 *
	 * @return mixed|null Returns field data.
	 */
	public static function get_request_data( $field ) {
		$get_input     = null;
		$post_input    = null;
		$request_input = null;

		try {
			$post_input = filter_input( INPUT_POST, $field, FILTER_SANITIZE_STRING );
			$get_input  = filter_input( INPUT_GET, $field, FILTER_SANITIZE_STRING );

			if ( ! empty( $post_input ) ) {
				return $post_input;
			}

			if ( ! empty( $get_input ) ) {
				return $get_input;
			}
		} catch ( Throwable $t ) {
			$logger = new Activecampaign_For_Woocommerce_Logger();
			$logger->warning(
				'There was an issue getting filter input post data for a field',
				[
					'field'      => $field,
					'get_input'  => $get_input,
					'post_input' => $post_input,
					'message'    => $t->getMessage(),
					'ac_code'    => 'UTIL_40',
				]
			);
		}

		try {
			$request = wp_unslash( $_REQUEST );
			if ( isset( $request[ $field ] ) ) {
				$request_input = $request[ $field ];

				if ( ! empty( $request_input ) ) {
					return $request_input;
				}
			}
		} catch ( Throwable $t ) {
			$logger = new Activecampaign_For_Woocommerce_Logger();
			$logger->warning(
				'There was an issue getting request data for a field',
				[
					'field'         => $field,
					'request_input' => $request_input,
					'message'       => $t->getMessage(),
					'ac_code'       => 'UTIL_68',
				]
			);
		}

		try {
			// phpcs:disable
			$request = wp_unslash( $_POST );
			// phpcs:enable
			if ( isset( $request[ $field ] ) ) {
				$request_input = $request[ $field ];

				if ( ! empty( $request_input ) ) {
					return $request_input;
				}
			}
		} catch ( Throwable $t ) {
			$logger = new Activecampaign_For_Woocommerce_Logger();
			$logger->warning(
				'There was an issue getting direct post data for a field',
				[
					'field'         => $field,
					'request_input' => $request_input,
					'message'       => $t->getMessage(),
					'ac_code'       => 'UTIL_90',
				]
			);
		}

		return null;

	}

	/**
	 * Validates an object with isset check and method_exists check in one call.
	 *
	 * @param     object $o     The string|object.
	 * @param     string $s     The string for the call.
	 *
	 * @return bool
	 */
	public static function validate_object( $o, $s ) {
		if (
			isset( $o ) &&
			( is_object( $o ) || is_string( $o ) ) &&
			method_exists( $o, $s )
		) {
			return true;
		}

		return false;
	}

	/**
	 * Validates if an email is valid for syncing to ActiveCampaign.
	 *
	 * @param     string $email     The email address.
	 *
	 * @return bool
	 */
	public static function check_valid_email( $email ) {
		$email_valid = false;
		try {
			if ( ! empty( $email ) ) {

				// GET EMAIL PARTS
				$domain = ltrim( stristr( $email, '@' ), '@' ) . '.';
				$user   = stristr( $email, '@', true );

				// VALIDATE EMAIL ADDRESS
				if (
					! empty( $user ) &&
					! empty( $domain ) &&
					is_email( $email ) &&
					filter_var( $email, FILTER_VALIDATE_EMAIL )
				) {
					$email_valid = true;
				}
			}
		} catch ( Throwable $t ) {
			$logger = new Logger();
			$logger->warning(
				'AC Check valid email encountered an error',
				[
					'message' => $t->getMessage(),
					'trace'   => $logger->clean_trace( $t->getTrace() ),
				]
			);
		}
		return $email_valid;
	}

	/**
	 * Allows WPDB to do a bulk insert.
	 *
	 * @param     string $table     The table name.
	 * @param     array  $rows     The rows of data to insert.
	 *
	 * @return bool|int|null
	 */
	public static function wpdb_bulk_insert( $table, $rows ) {
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
	public static function wpdb_update_in( $table, $data, $where, $format = null, $where_format = null ) {

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
