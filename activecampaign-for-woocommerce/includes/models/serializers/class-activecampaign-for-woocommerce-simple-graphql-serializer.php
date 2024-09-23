<?php

use Activecampaign_For_Woocommerce_Ecom_Enum_Type as Enumish;
use Activecampaign_For_Woocommerce_Logger as Logger;
use AcVendor\Brick\Math\BigDecimal;

class Activecampaign_For_Woocommerce_Simple_Graphql_Serializer {

	/**
	 * @param string      $first_key
	 * @param array|mixed $objects
	 *
	 * @return string
	 */
	public static function graphql_serialize( $first_key, $objects ): string {
		if ( is_array( $objects ) ) {
			$str = '';
			if ( isset( $objects[0] ) && count( $objects[0] ) > 1 ) {
				foreach ( $objects as $obj ) {
					$str .= self::graphql_serialize_single( $obj );
				}
			} else {
				$str .= self::graphql_serialize_single( $objects );
				return "$first_key:{$str}";
			}

			return "$first_key:[$str]";
		} else {
			$str = self::graphql_serialize_single( $objects );

			return "$first_key:$str";
		}
	}

	private static function graphql_serialize_single( $arr ): string {
		$str                  = '';
		$first                = true;
		$is_associative_array = false;
		$logger               = new Logger();

		foreach ( $arr as $key => $value ) {
			if ( ! $is_associative_array && ! is_int( $key ) ) {
				$is_associative_array = true;
			}

			if ( ! is_null( $value ) ) {
				if ( ! $first ) {
					$str .= ' ';
				}
				$first = false;

				// If we have a key that is an integer, then we're in a list (e.g. ["a","b","c"], so don't print the key
				if ( ! is_int( $key ) ) {
					$str .= "$key:";
				}

				try {
					// On the value...
					if ( is_array( $value ) ) {
						$str .= self::graphql_serialize_single( $value );
					} elseif ( is_bool( $value ) ) {
						$str .= ( $value ? 'true' : 'false' );
					} elseif ( $value instanceof BigDecimal ) {
						$str .= $value;
					} elseif ( is_int( $value ) ) {
						$str .= $value;
					} elseif ( $value instanceof Enumish ) {
						// Enums don't use quotes in Graphql
						$str .= $value->val;
					} else {
						// Using json_encode to handle escaping properly
						$str .= wp_json_encode( $value );
					}
				} catch ( Throwable $t ) {
					$logger->error(
						'GraphQL Serializer encountered an exception while processing a data single.',
						[
							'message'          => $t->getMessage(),
							'suggested_action' => 'If this problem repeats please contact ActiveCampaign support.',
							'ac_code'          => 'SGQLS_75',
							'key'              => $key,
							'value'            => $value,
							'trace'            => $t->getTrace(),
						]
					);
				}
			}
		}

		if ( $is_associative_array ) {
			return '{' . $str . '}';
		} else {
			return "[$str]";
		}
	}
}
