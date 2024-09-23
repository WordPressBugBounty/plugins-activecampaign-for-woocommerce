<?php

/**
 * The file that defines the main ActiveCampaign API Client.
 *
 * A class definition that includes attributes and functions used across both the
 * public-facing side of the site and the admin area.
 *
 * @link       https://www.activecampaign.com/
 * @since      1.0.0
 *
 * @package    Activecampaign_For_Woocommerce
 * @subpackage Activecampaign_For_Woocommerce/includes/api-client
 */

use AcVendor\GuzzleHttp\Client;
use AcVendor\GuzzleHttp\Exception\GuzzleException;
use AcVendor\Psr\Http\Message\ResponseInterface;
use Activecampaign_For_Woocommerce_Logger as Logger;
use Activecampaign_For_Woocommerce_Request_Id_Service as RequestIdService;

/**
 * The main API Client class.
 *
 * This is used to fetch/send data from and to ActiveCampaign.
 *
 * @since      1.0.0
 * @package    Activecampaign_For_Woocommerce
 * @subpackage Activecampaign_For_Woocommerce/includes/api-client
 * @author     acteamintegrations <team-integrations@activecampaign.com>
 *
 * @method self get( string $endpoint, string | int $id = null )
 * @method self put( string $endpoint, string | int $id = null )
 * @method self post( string $endpoint, string | int $id = null )
 * @method self delete( string $endpoint, string | int $id = null )
 */
class Activecampaign_For_Woocommerce_Api_Client {
	use Activecampaign_For_Woocommerce_Global_Utilities,
		Activecampaign_For_Woocommerce_Data_Validation;

	/**
	 * The API Uri to make requests against.
	 *
	 * @var string
	 * @since      1.0.0
	 */
	private $api_uri;

	/**
	 * The API Key to include as a header value in all requests.
	 *
	 * @var string
	 * @since      1.0.0
	 */
	private $api_key;

	/**
	 * The HTTP Client that will handle making the requests.
	 *
	 * @var Client
	 * @since      1.0.0
	 */
	private $client;

	/**
	 * The method for the request, e.g. 'GET'.
	 *
	 * @var string
	 * @since      1.0.0
	 */
	private $method;

	/**
	 * The endpoint for the request formatted, e.g. 'ecomOrder/1'
	 *
	 * @var string
	 * @since      1.0.0
	 */
	private $endpoint;

	/**
	 * The endpoint for the request in origin, e.g. 'ecomOrder/1'
	 *
	 * @var string
	 * @since      1.0.0
	 */
	private $origin_endpoint;

	/**
	 * The JSON-formatted string to include as the body of the request.
	 *
	 * @var string
	 * @since      1.0.0
	 */
	private $body;

	/**
	 * The filters to add to the endpoint.
	 *
	 * @var array
	 * @since      1.0.0
	 */
	private $filters = array();

	/**
	 * Whether or not we've already configured the client.
	 *
	 * @var bool
	 * @since      1.0.0
	 */
	private $configured = false;

	/**
	 * The count for the number of retries attempted.
	 *
	 * @var int
	 * @since      1.6.10
	 */
	private $retry_count = 0;

	/**
	 * The number of retries allowed.
	 *
	 * @var bool
	 * @since      1.6.10
	 */
	private $retries_allowed = 1;

	/**
	 * A list of methods that can be magic-called.
	 *
	 * @since      1.0.0
	 */
	const ACCEPTED_METHODS = array(
		'get',
		'put',
		'post',
		'delete',
	);
	/**
	 * Whether or not to log the response.
	 *
	 * @since 1.2.11
	 * @var   bool
	 */
	private $ac_debug;
	/**
	 * The WC Logger
	 *
	 * @since 1.2.11
	 * @var   Logger|null
	 */
	protected $logger;

	/**
	 * The slash clearing whitelist. Any URLs to keep a slash.
	 *
	 * @var array
	 */
	private $slash_whitelist = array(
		'ecomData/bulkSync',
		'import/bulk_import',
		'ecom/graphql',
		'account/entitlements',
	);

	/**
	 * Activecampaign_For_Woocommerce_Api_Client constructor.
	 *
	 * @param string|null $api_uri The API Uri for the client to use.
	 * @param string|null $api_key The API Key for the client to use.
	 * @param Logger|null $logger  The logger.
	 * @param null        $debug   Whether debugging is turned on.
	 *
	 * @since      1.0.0
	 */
	public function __construct( $api_uri = null, $api_key = null, Logger $logger = null, $debug = null ) {
		$this->api_uri = $api_uri;
		$this->api_key = $api_key;

		if ( ! isset( $this->logger ) ) {
			$this->logger = new Logger();
		} else {
			$this->logger = $logger;
		}
		$settings = get_option( ACTIVECAMPAIGN_FOR_WOOCOMMERCE_DB_SETTINGS_NAME );
		$api_uri  = isset( $settings['api_url'] ) ? $settings['api_url'] : null;
		$api_key  = isset( $settings['api_key'] ) ? $settings['api_key'] : null;

		if ( null === $debug ) {
			$settings       = get_option( ACTIVECAMPAIGN_FOR_WOOCOMMERCE_DB_SETTINGS_NAME );
			$this->ac_debug = isset( $settings['ac_debug'] ) ? '1' === $settings['ac_debug'] : false;
		} else {
			$this->ac_debug = $debug;
		}
	}

	/**
	 * Configure the HTTP Client that will make the requests.
	 *
	 * If an instantiated Guzzle Client class is passed in as an argument,
	 * it will be used. Otherwise, a new one will be instantiated and given
	 * default arguments.
	 *
	 * @param Client|null $client The HTTP Client that will be used.
	 *
	 * @since      1.0.0
	 */
	public function configure_client( Client $client = null, $extra_path = '' ) {
		if ( $client ) {
			$this->client = $client;

			$this->configured = true;

			return;
		}

		/**
		 * Prevent mal-configuring this object in the event the DB values are not yet set.
		 */
		if (
			! $this->api_uri ||
			! $this->api_key
		) {
			return;
		}

		$this->client = new Client(
			array(
				// LOCAL (because locally, you can't access cofe under {hosted_url}/api/3/ecom
				// Still looking for a more elegant solution for this.
				// 'base_uri' => 'http://host.docker.internal:14001/graphql',
				// REAL CODE:
				'base_uri'        => $this->get_api_uri_with_v3_path() . $extra_path,
				'timeout'         => 30,
				'allow_redirects' => false,
				'http_errors'     => false,
				'headers'         => array(
					'Api-Token'         => $this->get_api_key(),
					'X-Request-Id'      => RequestIdService::get_request_id(),
					'wc-plugin-version' => ACTIVECAMPAIGN_FOR_WOOCOMMERCE_VERSION,
				),
			)
		);

		$this->configured = true;
	}

	/**
	 * Returns the api uri with the api v3 path appended.
	 *
	 * @return string
	 * @since      1.0.0
	 */
	public function get_api_uri_with_v3_path() {
		return $this->api_uri . '/api/3/';
	}

	/**
	 * Returns the api uri.
	 *
	 * @return string
	 * @since      1.0.0
	 */
	public function get_api_uri() {
		return $this->api_uri;
	}

	/**
	 * Sets the api uri.
	 *
	 * @param string $api_uri The API Uri to use.
	 *
	 * @since      1.0.0
	 */
	public function set_api_uri( $api_uri ) {
		$this->api_uri = $api_uri;
	}

	/**
	 * Returns the api key.
	 *
	 * @return string
	 * @since      1.0.0
	 */
	public function get_api_key() {
		return $this->api_key;
	}

	/**
	 * Sets the api key.
	 *
	 * @param string $api_key The API Key to use.
	 *
	 * @since      1.0.0
	 */
	public function set_api_key( $api_key ) {
		$this->api_key = $api_key;
	}

	/**
	 * Returns the instantiated Guzzle client.
	 *
	 * @return Client
	 * @since      1.0.0
	 */
	public function get_client() {
		return $this->client;
	}

	/**
	 * Sets the max number of retries, zero disables
	 * Disabled by default.
	 *
	 * @param int $r The quantity of retries allowed.
	 */
	public function set_max_retries( $r ) {
		$this->retries_allowed = $r;
	}

	/**
	 * This magic-method allows us to call get(), put(), and post() on the client without individually
	 * defining them.
	 *
	 * @param string $name      The name of the method called, eg, get() => 'get'.
	 * @param array  $arguments An array containing all arguments passed in the method call.
	 *
	 * @return self
	 * @throws InvalidArgumentException When the method called is not in the list of accepted methods.
	 * @since      1.0.0
	 */
	public function __call( $name, $arguments ) {
		if ( ! in_array( $name, self::ACCEPTED_METHODS, true ) ) {
			throw new InvalidArgumentException( "The method $name is not an acceptable request method." );
		}

		/**
		 * The first time the API key and url are saved to the DB, we run into an issue where this object
		 * is constructed before the values are saved. This leads to fatal exceptions due to a malformed
		 * url. Here, we refresh the values from the DB and now configure the client if we had to return
		 * early in the initial configuration due to the missing values.
		 */
		if ( ! $this->configured ) {
			$this->refresh_api_values();

			$this->configure_client();
		}

		$this->method          = $this->format_request_method( $name );
		$this->origin_endpoint = $arguments;
		$this->endpoint        = $this->format_endpoint( $arguments );

		return $this;
	}

	/**
	 * Returns the endpoint for this request.
	 *
	 * @return string The endpoint.
	 * @since      1.0.0
	 */
	public function get_endpoint() {
		return $this->endpoint;
	}

	/**
	 * Returns the method for this request.
	 *
	 * @return string The method.
	 * @since      1.0.0
	 */
	public function get_method() {
		return $this->method;
	}

	/**
	 * Sets a JSON-formatted string as the body for the request.
	 *
	 * If called multiple times, the original body will be overwritten.
	 *
	 * @param string $body The JSON-formatted string to include in the request.
	 *
	 * @return self
	 * @since      1.0.0
	 */
	public function with_body( $body ) {
		$this->body = $body;

		return $this;
	}

	/**
	 * Returns the JSON-formatted body for this request.
	 *
	 * @return string The JSON-formatted body.
	 * @since      1.0.0
	 */
	public function get_body() {
		return $this->body;
	}

	/**
	 * Pushes a new key/value pair of a filter to the filters array.
	 *
	 * E.g.:
	 *
	 * $filter_name = 'email', $filter_value = 'example@example.com'
	 *
	 * Becomes
	 *
	 * ["email" => "example@example.com"]
	 *
	 * @param string $filter_name  The name of the filter to add.
	 * @param string $filter_value The value of the filter to add.
	 *
	 * @return self
	 * @since      1.0.0
	 */
	public function with_filter( $filter_name, $filter_value ) {
		$this->filters[ $filter_name ] = rawurlencode( $filter_value );

		return $this;
	}

	/**
	 * Returns the current array of filters.
	 *
	 * @return array The filters array.
	 * @since      1.0.0
	 */
	public function get_filters() {
		return $this->filters;
	}

	/**
	 * Sets the filters.
	 *
	 * @param array $filters The filters array.
	 * @return void
	 */
	public function set_filters( $filters ) {
		$this->filters = $filters;
	}

	/**
	 * Executes the request.
	 *
	 * First creates a filtered endpoint, and then passes that endpoint to the Guzzle
	 * Client. This client then handles making the actual request.
	 *
	 * @return array|bool|ResponseInterface
	 * @throws GuzzleException Thrown when a non-200/300 response is received.
	 * @since      1.0.0
	 */
	public function execute( $headers = null ) {
		$endpoint = $this->construct_endpoint_with_filters();
		$response = null;

		if ( ! $this->configured ) {
			$this->refresh_api_values();

			$this->configure_client();
		}

		try {
			$options = array();
			if ( $headers ) {
				$options['headers'] = $headers;
			}

			if ( $this->body ) {
				$options['body'] = $this->body;
			}

			$response = $this->client->request( $this->method, $endpoint, $options );

			if ( $response instanceof ResponseInterface && $this->ac_debug && $this->logger ) {
				$this->logger->debug_calls(
					'Received response',
					array(
						'endpoint'             => $this->endpoint,
						'method'               => $this->method,
						'response_status_code' => self::validate_object( $response, 'getStatusCode' ) ? $response->getStatusCode() : null,
						'response_headers'     => self::validate_object( $response, 'getHeaders' ) ? $response->getHeaders() : null,
						'response_body'        => self::validate_object( $response, 'getBody' ) ? $response->getBody() : null,
						'response_contents'    => self::validate_object( $response, 'getBody' ) ? $response->getBody()->getContents() : null,
						'response_string'      => self::validate_object( $response, 'getBody' ) ? $response->getBody()->__toString() : null,
					)
				);
			}
		} catch ( GuzzleException $e ) {
			$message = $e->getMessage();

			if (
				404 === $e->getCode()
				&& strpos( $message, 'No Result found' ) !== false
			) {
				$this->logger->debug_calls(
					'Hosted lookup returned zero results. This is probably not an error.',
					array(
						'response' => $message,
					)
				);

				return 'No results found.';
			}

			$stack_trace = $this->logger->clean_trace( $e->getTrace() );

			if ( isset( $e ) && 422 === $e->getCode() ) {
				if ( strpos( $message, 'duplicate' ) !== false ) {
					$this->logger->debug(
						'Duplicate record found',
						array(
							'message'  => $message,
							'endpoint' => $this->endpoint,
						)
					);
				} else {
					$this->logger->debug(
						'The API returned an error [api_e0]',
						array(
							'message'         => $message,
							'endpoint'        => $this->endpoint,
							'origin_endpoint' => $this->origin_endpoint,
							'code'            => $e->getCode(),
							'stack trace'     => $stack_trace,
						)
					);

					return [
						'type'             => 'error',
						'code'             => $e->getCode(),
						'response_message' => $e->getMessage(),
						'message'          => 'The API returned an error. This may be a duplicate record or data that could not be processed. Please check logs.',
					];
				}
			} else {

				if ( false !== strpos( $message, 'Connection timed out' ) || false !== strpos( $message, 'cURL error 28' ) ) {
					if ( $this->retries_allowed > $this->retry_count ) {
						$this->logger->notice(
							'The connection to Hosted timed out. Waiting 10 seconds to try again.',
							array(
								'message'         => $message,
								'endpoint'        => $this->endpoint,
								'origin_endpoint' => $this->origin_endpoint,
								'retries_allowed' => $this->retries_allowed,
								'retry_count'     => $this->retry_count,
								'stack_trace'     => $this->logger->clean_trace( $e->getTrace() ),
							)
						);

						$this->retry_count ++;
						sleep( 10 );
						return $this->execute();
					} else {
						$this->logger->warning(
							'The connection to ActiveCampaign timed out ' . $this->retry_count . ' times. Aborting the call and continuing on.',
							array(
								'message'         => $message,
								'endpoint'        => $this->endpoint,
								'origin_endpoint' => $this->origin_endpoint,
								'retries_allowed' => $this->retries_allowed,
								'retry_count'     => $this->retry_count,
								'stack trace'     => $this->logger->clean_trace( $e->getTrace() ),
							)
						);

						return array(
							'type'    => 'timeout',
							'message' => 'The connection to ActiveCampaign timed out ' . $this->retry_count . ' times. Aborting the call and continuing on.',
						);
					}
				} else {
					if (
						(
							'ecomData/bulkSync' === $this->endpoint ||
							'ecomData\/bulkSync' === $this->endpoint
						) &&
						(
							400 === $e->getCode() ||
							'400' === $e->getCode()
						)
					) {
						$this->logger->warning(
							'The API returned an error but it may be false [api_eb]',
							array(
								'message'         => $message,
								'endpoint'        => $this->endpoint,
								'origin_endpoint' => $this->origin_endpoint,
								'call_body'       => $this->body,
								'response_code'   => $e->getCode(),
								'stack_trace'     => $stack_trace,
							)
						);

						return [
							'type'    => 'error',
							'message' => $message,
							'code'    => $e->getCode(),
						];
					} elseif ( in_array( $e->getCode(), array( 500, 503, 520, 521, 525, 526, 590 ), true ) ) {
						$full_response = '';
						if (
							self::validate_object( $e, 'getResponse' ) &&
							self::validate_object( $e->getResponse(), 'getBody' ) &&
							self::validate_object( $e->getResponse()->getBody(), 'getContents' )
						) {
							$full_response = $e->getResponse()->getBody()->getContents();
						}

						$this->logger->error(
							'The ActiveCampaign API returned a server level error [api_e5]',
							array(
								'endpoint'          => $this->endpoint,
								'origin_endpoint'   => $this->origin_endpoint,
								'method'            => $this->method,
								'response_code'     => $e->getCode(),
								'message'           => $message,
								'get_response_body' => $full_response,
								'response_body'     => self::validate_object( $response, 'getBody' ) ? $response->getBody() : null,
								'stack_trace'       => $stack_trace,
							)
						);

						return array(
							'type'    => 'error',
							'message' => $full_response,
							'code'    => $e->getCode(),
						);
					} else {
						$this->logger->error(
							'The ActiveCampaign API returned an error indicating there may be an issue with the data sent.',
							array(
								'suggested_action' => 'Please address the errors stated in the logs and if this problem repeats please contact ActiveCampaign support.',
								'endpoint'         => $this->endpoint,
								'origin_endpoint'  => $this->origin_endpoint,
								'method'           => $this->method,
								'response_code'    => $e->getCode(),
								'message'          => $message,
								'ac_code'          => 'API_621',
								'response_body'    => self::validate_object( $response, 'getBody' ) ? $response->getBody() : null,
								'stack_trace'      => $stack_trace,
							)
						);

						return [
							'type'    => 'error',
							'message' => $message,
							'code'    => $e->getCode(),
						];
					}
				}

				return false;
			}
		} catch ( Throwable $t ) {
			$message     = $t->getMessage();
			$stack_trace = $t->getTrace();

			if ( isset( $e ) && 422 === $t->getCode() ) {
				if ( strpos( $message, 'duplicate' ) !== false ) {
					// Don't waste log space with stack traces if it's just a duplicate
					$this->logger->debug(
						'Duplicate record found [api_e2]',
						array(
							'message'     => $message,
							'stack trace' => $stack_trace,
						)
					);
				} else {
					$this->logger->debug(
						'The ActiveCampaign API returned an error [api_e3]',
						array(
							'message'     => $message,
							'stack trace' => $stack_trace,
						)
					);

					return false;
				}
			} else {
				$this->logger->error(
					'An error was encountered attempting to send or read data when communicating with the ActiveCampaign API.',
					array(
						'message'          => $message,
						'suggested_action' => 'Please review the message provided in this error. If it does not indicate the cause of the issue and this is a reoccuring error please reach out to support.',
						'ac_code'          => 'API_663',
						'stack trace'      => $stack_trace,
					)
				);

				return false;
			}
		}

		return $response;
	}

	/**
	 * Creates an endpoint string with the filters appended.
	 *
	 * E.g.:
	 *
	 * ["email" => "example@example.com"]
	 *
	 * Becomes
	 *
	 * ?filters[email]=example@example.com
	 *
	 * @return string
	 * @since      1.0.0
	 */
	public function construct_endpoint_with_filters() {
		$endpoint = $this->endpoint;

		if ( $this->filters && count( $this->filters ) > 0 ) {
			$endpoint .= '?';

			foreach ( $this->filters as $filter => $value ) {
				$endpoint .= "filters[$filter]=$value&";
			}
		}

		/**
		 * If the last character of the string is '&', then set $endpoint
		 * to be $endpoint minus the last character
		 */
		if ( substr( $endpoint, - 1 ) === '&' ) {
			$endpoint = substr( $endpoint, 0, strlen( $endpoint ) - 1 );
		}

		return $endpoint;
	}

	/**
	 * Refreshes the values from the DB.
	 *
	 * This method is called typically only the first time the API settings are saved.
	 * The first time they're saved, this object is instantiated prior to the values being
	 * saved to the DB, so the Container constructs the object improperly. By refreshing the
	 * values, we fix this issue.
	 *
	 * @since      1.0.0
	 */
	private function refresh_api_values() {
		if ( $this->is_configured() ) {
			$settings      = get_option( ACTIVECAMPAIGN_FOR_WOOCOMMERCE_DB_SETTINGS_NAME );
			$this->api_uri = $settings['api_url'];
			$this->api_key = $settings['api_key'];
		}
	}

	/**
	 * Formats the request method called into a usable HTTP Request type.
	 *
	 * Eg, get() => 'get' => 'GET'.
	 *
	 * @param string $method The method name called.
	 *
	 * @return string
	 * @since      1.0.0
	 */
	private function format_request_method( $method ) {
		return strtoupper( $method );
	}

	/**
	 * Formats the endpoint to ensure the client works appropriately.
	 *
	 * We set a default Uri in the Guzzle Client. If and endpoint were
	 * passed in with a preceding slash, the Client would not use the
	 * default Uri and instead ONLY use the endpoint. This method removes the
	 * preceding slash to prevent this from happening.
	 *
	 * If your call is being stripped of slashes add it to the list here.
	 *
	 * @param array $args The endpoint to format.
	 *
	 * @return string
	 * @since      1.0.0
	 */
	private function format_endpoint( $args ) {
		$endpoint = $args[0];

		$id = count( $args ) > 1 ? (string) $args[1] : null;

		// Do not strip slashes for these endpoints
		if ( ! in_array( $endpoint, $this->slash_whitelist, true ) ) {
			$endpoint = str_replace( '/', '', $endpoint );
		}

		if ( $id ) {
			$endpoint .= "/$id";
		}

		return $endpoint;
	}
}
