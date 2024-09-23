<?php

/**
 * Controls the product sync process.
 * This will only be run by admin or cron so make sure all methods are admin only.
 *
 * @link       https://www.activecampaign.com/
 * @since      1.9.0
 *
 * @package    Activecampaign_For_Woocommerce
 * @subpackage Activecampaign_For_Woocommerce/includes
 */

use Activecampaign_For_Woocommerce_Product_Repository as Cofe_Product_Repository;
use Activecampaign_For_Woocommerce_Ecom_Cofe_Product_Serializer as Ecom_Cofe_Product;
use Activecampaign_For_Woocommerce_Executable_Interface as Executable;
use Activecampaign_For_Woocommerce_Logger as Logger;
use Activecampaign_For_Woocommerce_Sync_Status as Sync_Status;
use AcVendor\Brick\Math\BigDecimal;

/**
 * The Product_Sync Event Class.
 *
 * @since      1.9.0
 * @package    Activecampaign_For_Woocommerce
 * @subpackage Activecampaign_For_Woocommerce/includes/products
 * @author     acteamintegrations <team-integrations@activecampaign.com>
 */
class Activecampaign_For_Woocommerce_Product_Sync_Job implements Executable {
	use Activecampaign_For_Woocommerce_Data_Validation;
	/**
	 * The custom ActiveCampaign logger
	 *
	 * @var Activecampaign_For_Woocommerce_Logger
	 */
	private $logger;

	/**
	 * Repo.
	 *
	 * @var Activecampaign_For_Woocommerce_Product_Repository
	 */
	private $cofe_product_repository;

	/**
	 * Start record.
	 *
	 * @var int
	 */
	private $start_record;

	/**
	 * Batch limit.
	 *
	 * @var int
	 */
	private $batch_limit;
	/**
	 * Activecampaign_For_Woocommerce_Product_Sync_Job constructor.
	 *
	 * @param Activecampaign_For_Woocommerce_Logger|null $logger The logger object.
	 * @param Cofe_Product_Repository                    $cofe_product_repository Repo.
	 */
	public function __construct(
		Logger $logger,
		Cofe_Product_Repository $cofe_product_repository
	) {
		$this->logger                  = $logger;
		$this->cofe_product_repository = $cofe_product_repository;
		$this->start_record            = 0;
		$this->batch_limit             = 20;
	}

	/**
	 *
	 * @param mixed ...$args args.
	 * @return void
	 */
    // phpcs:disable
    public function execute( ...$args ) {
    // phpcs:enable
		$status = $this->add_args_to_status( $args );

		if ( $this->check_for_stop( $status ) ) {
			return;
		}

		// Remove the scheduled status because our process is no longer scheduled & now running.
		delete_option( ACTIVECAMPAIGN_FOR_WOOCOMMERCE_PRODUCT_SYNC_SCHEDULED_STATUS_NAME );
		delete_option( ACTIVECAMPAIGN_FOR_WOOCOMMERCE_PRODUCT_SYNC_LAST_STATUS_NAME );
		delete_option( ACTIVECAMPAIGN_FOR_WOOCOMMERCE_PRODUCT_SYNC_SCHEDULED_STATUS_NAME );

		// Set the init sync status.
		$this->logger->debug(
			'Product sync is starting with the following settings:',
			array(
				'status'       => $status,
				'args'         => $args,
				'batch_limit'  => $this->batch_limit,
				'start_record' => $this->start_record,
			)
		);

		$this->run_product_sync_process( $status );
	}


	/**
	 * On save of a product or update of stock this will get triggered.
	 *
	 * @param int        $product_id
	 * @param mixed      $product_stock_status
	 * @param WC_Product $product
	 */
	public function save_or_update_product_status( $product_id, $product_stock_status, $product ) {
		$logger = new Logger();

		if ( ! isset( $product_id, $product_stock_status ) ) {
			return;
		}

		$updating_product_id = 'update_product_' . $product_id;
		if ( false === get_transient( $updating_product_id ) ) {
			try {
				$admin_storage = get_option( ACTIVECAMPAIGN_FOR_WOOCOMMERCE_DB_CONNECTION_STORAGE_NAME );
				if ( ! empty( $admin_storage ) && isset( $admin_storage['connection_id'] ) ) {
					$connection_id = $admin_storage['connection_id'];
				}

				if ( ! self::validate_object( $product, 'get_data' ) ) {
					$product = wc_get_product( $product_id );
				}

				$status    = $this->get_status();
				$cofe_data = array();
				$logger->debug(
					'Product status changed, update COFE product status.',
					[
						'product_id'           => $product_id,
						'product_stock_status' => $product_stock_status,
					]
				);
			} catch ( Throwable $t ) {
				$this->logger->warning(
					'There was an issue setting up product data for update product sync.',
					[
						'message' => $t->getMessage(),
						'trace'   => $t->getTrace(),
						'func'    => 'save_or_update_product',
					]
				);
			}

			// Sync the connection before doing any COFE stuff
			do_action( 'activecampaign_for_woocommerce_run_sync_connection' );

			try {
				if ( isset( $connection_id ) ) {
					$this->add_product_data( $status, $product, $connection_id, $cofe_data );
				} else {
					$logger->warning(
						'Save or update product could not retrieve connection id.',
						[
							'product_id' => $product_id,
						]
					);

					return;
				}
			} catch ( Throwable $t ) {
				$this->logger->debug(
					'There was an issue adding product data',
					[
						'message'       => $t->getMessage(),
						'product'       => $product,
						'connection_id' => $connection_id,
					]
				);
			}

			if ( method_exists( $product, 'get_children' ) ) {
				try {
					$children = $product->get_children();

					if ( ! empty( $children ) ) {
						foreach ( $children as $child_id ) {
							$child = wc_get_product( $child_id );
							$this->add_product_data( $status, $child, $connection_id, $cofe_data, $product );
						}
					}
				} catch ( Throwable $t ) {
					$this->logger->warning(
						'There was an issue getting child product data.',
						[
							'product' => $product,
							'message' => $t->getMessage(),
							'trace'   => $t->getTrace(),
						]
					);

					$this->add_failed_product_to_status( $status, $product );
				}
			}

			if ( $cofe_data > 0 ) {
				try {
					$response = $this->cofe_product_repository->create_bulk( $cofe_data );

					if ( ! $response ) {
						$this->logger->warning(
							'Create bulk product data returned false. Check error logs.',
							[
								'found_in'  => 'product_cofe_sync',
								'response'  => $response,
								'cofe_data' => $cofe_data,
							]
						);
					}
				} catch ( Throwable $t ) {
					$this->logger->error(
						'There was a fatal error creating a bulk cofe call.',
						[
							'message' => $t->getMessage(),
							'trace'   => $t->getTrace(),
						]
					);
				}
			}
			set_transient( $updating_product_id, $product_id, 5 ); // change 2 seconds if not enough
		}
	}

	/**
	 * Gets the product IDs in the format we need.
	 *
	 * @param int  $limit The limit.
	 * @param int  $offset The offset.
	 * @param bool $return_id_only Marker for return IDs only.
	 *
	 * @return array|stdClass
	 */
	private function get_products_by_offset( $limit, $offset, $return_id_only ) {
		// types available 'external', 'grouped', 'simple', 'variable'
		// Do not include groups for now.
		try {
			$safe_product_types = $this->get_cofe_safe_product_types();

			$data = [
				'limit'   => (int) $limit,
				'offset'  => (int) $offset,
				'type'    => $safe_product_types,
				'orderby' => 'none',
				'order'   => 'ASC',
			];

			if ( $return_id_only ) {
				$data['return'] = 'ids';
			}

			$this->logger->debug(
				'Getting products by offset',
				[
					'producttypes'   => $safe_product_types,
					'data'           => $data,
					'return_id_only' => $return_id_only,
				]
			);

			$products = wc_get_products( $data );

			return $products;
		} catch ( Throwable $t ) {
			$this->logger->warning(
				'There was an issue getting products for the product sync',
				[
					'message'        => $t->getMessage(),
					'request data'   => $data,
					'return_id_only' => $return_id_only,
				]
			);
		}
		return null;
	}

	/**
	 * Build the product sync schedules based on number of products.
	 *
	 * @param mixed ...$args The args.
	 */
	public function build_product_sync_schedules( ...$args ) {
		delete_option( ACTIVECAMPAIGN_FOR_WOOCOMMERCE_RUN_PRODUCT_SYNC_NAME );
		delete_option( ACTIVECAMPAIGN_FOR_WOOCOMMERCE_PRODUCT_SYNC_RUNNING_STATUS_NAME );

		$status = $this->add_args_to_status( $args );

		$offset = 0;

		$wc_product_ids = $this->get_products_by_offset( $this->batch_limit, $offset, true );

		while ( $wc_product_ids ) {
			$this->schedule_background_product_sync( $offset, $this->batch_limit );

			$offset += count( $wc_product_ids );

			$wc_product_ids = $this->get_products_by_offset( $this->batch_limit, $offset, true );
		}

	}

	/**
	 * Runs the sync connection functionality.
	 * Called via action.
	 */
	public function run_sync_connection() {
		$sync = $this->cofe_product_repository->sync_connection();

		if ( $sync ) {
			$this->logger->debug_calls(
				'Sync connection initiated.',
				[
					$sync,
				]
			);
		}
	}

	/**
	 * Adds passed arguments to the status.
	 *
	 * @param     array|false $args
	 *
	 * @return Activecampaign_For_Woocommerce_Sync_Status
	 */
	private function add_args_to_status( $args = false ) {
		$status = $this->get_status();

		if ( is_array( $args ) ) {
			if ( isset( $args[0] ) ) {
				$data = $args[0];
			} else {
				$data = $args;
			}
		} else {
			$data = $args;
		}

		if ( is_object( $data ) ) {
			if ( ! empty( $data->batch_limit ) ) {
				$this->batch_limit = $args[0]->batch_limit;
			}
			if ( ! empty( $data->start_record ) || 0 === $data->start_record || '0' === $data->start_record ) {
				$this->start_record = $args[0]->start_record;
			}
		}

		if ( is_array( $data ) ) {
			if ( ! empty( $data['batch_limit'] ) ) {
				$this->batch_limit = $data['batch_limit'];
			}
			if ( ! empty( $data['start_record'] ) || 0 === $data['start_record'] || '0' === $data['start_record'] ) {
				$this->start_record = $data['start_record'];
			}
		}

		$_post = wp_unslash( $_POST );

		if (
			isset( $_post['activecampaign_for_woocommerce_settings_nonce_field'] )
			&& wp_verify_nonce( $_post['activecampaign_for_woocommerce_settings_nonce_field'], 'activecampaign_for_woocommerce_product_sync_form' )
		) {
			if ( isset( $_post['batchLimit'] ) && ! empty( $_post['batchLimit'] ) ) {
				$this->batch_limit = $_post['batchLimit'];
			}
			if ( isset( $_post['startRecord'] ) && ! empty( $_post['startRecord'] ) ) {
				$this->start_record = $_post['startRecord'];
			}
		}

		$status->start_record = $this->start_record;
		$status->batch_limit  = $this->batch_limit;

		Sync_Status::update_status( ACTIVECAMPAIGN_FOR_WOOCOMMERCE_PRODUCT_SYNC_RUNNING_STATUS_NAME, $status );

		return $status;
	}
	/**
	 * Schedules a bulk product sync to run as a background job.
	 *
	 * @param int $offset The record to start on.
	 * @param int $batch_limit The limit for the batch.
	 *
	 * @since
	 */
	public function schedule_background_product_sync( $offset, $batch_limit ) {
		$logger = new Logger();
		$data   = [
			[
				'start_record' => (int) $offset,
				'batch_limit'  => (int) $batch_limit,
			],
		];
		try {
			if (
				empty(
					wp_next_scheduled( ACTIVECAMPAIGN_FOR_WOOCOMMERCE_RUN_PRODUCT_SYNC_NAME, $data )
				)
			) {
				wp_schedule_single_event(
					time() + 10,
					ACTIVECAMPAIGN_FOR_WOOCOMMERCE_RUN_PRODUCT_SYNC_NAME,
					$data
				);
			}

			$logger->debug(
				'Schedule next product sync',
				[
					'current_time' => time(),
					'batch_limit'  => $batch_limit,
					'start_record' => $offset,
					'schedule'     => wp_get_scheduled_event(
						ACTIVECAMPAIGN_FOR_WOOCOMMERCE_RUN_PRODUCT_SYNC_NAME,
						$data
					),
				]
			);

			update_option( ACTIVECAMPAIGN_FOR_WOOCOMMERCE_PRODUCT_SYNC_SCHEDULED_STATUS_NAME, true );
		} catch ( Throwable $t ) {
			$logger->error(
				'There was an issue scheduling continued bulk product sync. Product sync may hang.',
				[
					'message'  => $t->getMessage(),
					'trace'    => $t->getTrace(),
					'function' => 'schedule_background_product_sync',
				]
			);
		}
	}

	/**
	 * Syncs a single product from ID.
	 *
	 * @param string|int $product_id The product ID.
	 */
	public function single_product_cofe_sync( $product_id ) {
		$logger = new Logger();
		if ( ! isset( $product_id ) ) {
			$this->logger->notice(
				'Product ID is empty, return.'
			);

			return;
		}

		$product = wc_get_product( $product_id );

		try {
			if ( isset( $product ) ) {
				$this->product_cofe_sync( $this->add_args_to_status( false ), [ $product ] );
			}
		} catch ( Throwable $t ) {
			$logger->debug(
				'There was an issue syncing single product data',
				[
					'message'    => $t->getMessage(),
					'product_id' => $product_id,
				]
			);
		}
	}

	/**
	 * The Product Sync.
	 *
	 * @param Activecampaign_For_Woocommerce_Sync_Status $status Status.
	 * @return int
	 */
	public function product_cofe_sync( Sync_Status $status, $products_list = null ) {
		/**
		  * We are grabbing offset and limit from $this because $this gets populated by the job scheduler, which is
		  * more accurate than $status (which is populated from the db).
		 */
		if ( ! isset( $products_list ) || empty( $products_list ) ) {
			$offset = $this->start_record;
			$limit  = $this->batch_limit;

			$wc_products = $this->get_products_by_offset( $limit, $offset, false );
		} else {
			$wc_products = $products_list;
		}

		/** Products. @var WC_Product[] $products */
		$cofe_data     = array();
		$admin_storage = get_option( ACTIVECAMPAIGN_FOR_WOOCOMMERCE_DB_CONNECTION_STORAGE_NAME );

		if ( ! empty( $admin_storage ) && isset( $admin_storage['connection_id'] ) ) {
			$connection_id = $admin_storage['connection_id'];
		}

		foreach ( $wc_products as $product ) {
			if ( ! $product ) {
				$this->logger->notice(
					'Product ID is empty, skipping.',
					[
						'product' => $product,
					]
				);

				continue;
			}

			try {
				$this->add_product_data( $status, $product, $connection_id, $cofe_data );
			} catch ( Throwable $t ) {
				$this->logger->debug(
					'There was an issue adding product data',
					[
						'message'       => $t->getMessage(),
						'product'       => $product,
						'connection_id' => $connection_id,
					]
				);
			}

			if ( method_exists( $product, 'get_children' ) ) {
				try {
					$children = $product->get_children();

					if ( ! empty( $children ) ) {
						foreach ( $children as $child_id ) {
							$child = wc_get_product( $child_id );
							$this->add_product_data( $status, $child, $connection_id, $cofe_data, $product );
						}
					}
				} catch ( Throwable $t ) {
					$this->logger->notice(
						'There was an issue getting child product data.',
						[
							'message' => $t->getMessage(),
							'trace'   => $t->getTrace(),
						]
					);

					$this->add_failed_product_to_status( $status, $product );
				}
			}
		}

		if ( $cofe_data > 0 ) {
			try {
				$response = $this->cofe_product_repository->create_bulk( $cofe_data );

				if ( ! $response ) {
					$this->logger->warning(
						'Create bulk product data returned false. Check error logs.',
						[
							'found_in'  => 'product_cofe_sync',
							'response'  => $response,
							'cofe_data' => $cofe_data,
						]
					);

					Sync_Status::halt( $status );
				}
			} catch ( Throwable $t ) {
				$this->logger->error(
					'There was a fatal error creating a bulk cofe call.',
					[
						'message' => $t->getMessage(),
						'trace'   => $t->getTrace(),
					]
				);
			}
		}

		return count( $wc_products );
	}

	/**
	 * This runs our sync process after being initialized by the execute command.
	 *
	 * @param Sync_Status $status Status.
	 *
	 * @since 1.9.0
	 */
	private function run_product_sync_process( Sync_Status $status ) {
		$product_count = null;

		try {
			$product_count = $this->product_cofe_sync( $status );
		} catch ( Throwable $t ) {
			$this->logger->warning(
				'Product sync failed sync for Offset:',
				[
					'message' => $t->getMessage(),
					'offset'  => $this->start_record,
					'limit'   => $this->batch_limit,
					'trace'   => $t->getTrace(),
				]
			);
		}

		$status->current_record += $product_count;
		Sync_Status::finish( $status );
		$this->logger->info(
			'Product Sync Group Ended',
			array(
				'status'        => $status,
				'product_count' => $product_count,
			)
		);
	}

	/**
	 * Add Product Data.
	 *
	 * @param Activecampaign_For_Woocommerce_Sync_Status $status status.
	 * @param WC_Product | WC_Product_Variation          $product Product.
	 * @param int                                        $connection_id connection ID.
	 * @param array                                      $product_data Produt Data.
	 * @param null                                       $parent Parent.
	 */
	public function add_product_data( Sync_Status $status, WC_Product $product, $connection_id, array &$product_data, $parent = null ) {
		global $activecampaign_for_woocommerce_product_sync_status;
		try {
			$p_data = Ecom_Cofe_Product::product_array_for_cofe( $product, $connection_id, $parent );
			if ( ! is_null( $p_data ) ) {
				$product_data[] = $p_data;
			}
		} catch ( Throwable $t ) {
			$this->add_failed_product_to_status( $status, $product );

			$activecampaign_for_woocommerce_product_sync_status[] = $t->getMessage();
			$this->logger->warning(
				'There was an issue creating a COFE product',
				array(
					'message' => $t->getMessage(),
					'trace'   => $t->getTrace(),
				)
			);
		}

		// Bulk API should only handle pages of 50, so do API call and reset array when we get to that size.
		// This was changed from const MAX_BULK_PRODUCT_PAGE_SIZE to static 50.
		if ( count( $product_data ) === 50 ) {
			try {
				$response = $this->cofe_product_repository->create_bulk( $product_data );
				if ( ! $response ) {
					$this->logger->warning(
						'Create bulk product data returned false. Check error logs.',
						[
							'found_in'     => 'add_product_data',
							'response'     => $response,
							'product_data' => $product_data,
						]
					);
					Sync_Status::halt( $status );
				}
			} catch ( RuntimeException $e ) {
				global $activecampaign_for_woocommerce_product_sync_status;
				$activecampaign_for_woocommerce_product_sync_status[] = $e->getMessage();
				$this->logger->warning(
					'Error with bulk product create call',
					array(
						'message' => $e->getMessage(),
						'trace'   => $e->getTrace(),
					)
				);
				$this->add_failed_product_to_status( $status, $product );
			} catch ( Throwable $t ) {
				$this->logger->warning(
					'Error with bulk product create call',
					[
						'message' => $t->getMessage(),
						'trace'   => $t->getTrace(),
					]
				);
				$this->add_failed_product_to_status( $status, $product );
			}
			$this->logger->info( 'Wrote product data to ActiveCampaign: ' . wp_json_encode( $product_data ) );
			$product_data = array();
		}
	}

	/**
	 * Add a failed order to the status.
	 *
	 * @param Activecampaign_For_Woocommerce_Sync_Status $status
	 * @param WC_Product|null                            $product
	 */
	private function add_failed_product_to_status( Sync_Status $status, ?WC_Product $product = null ) {
		if ( $product && self::validate_object( $product, 'get_id' ) && ! empty( $product->get_id() ) ) {
			$status->failed_order_id_array[] = $product->get_id();
		} else {
			$status->failed_order_id_array[] = 'Unknown WC Product';
		}
	}

	/**
	 * Checks for a stop condition.
	 *
	 * @param Sync_Status $status
	 * @return bool
	 */
	private function check_for_stop( Sync_Status $status ): bool {
		global $wpdb;
		// phpcs:disable
		$sync_stop_type = $wpdb->get_var( 'SELECT option_value from ' . $wpdb->prefix . 'options WHERE option_name = "' . ACTIVECAMPAIGN_FOR_WOOCOMMERCE_PRODUCT_SYNC_STOP_CHECK_NAME . '"' );
		// phpcs:enable

		if ( empty( $sync_stop_type ) ) {
			return false;
		}

		$this->logger->alert(
			'Product Sync Stop Request Found: Cancelled.',
			array(
				'stop_type' => $sync_stop_type,
			)
		);

		switch ( $sync_stop_type ) {
			case 'cancel':
				Sync_Status::cancel( $status );
				break;
			default:
				$this->logger->warning(
					'Product sync stop status found but did not match a type. There may be a bug. Sync will continue.',
					array(
						'status'    => $status,
						'stop_type' => $sync_stop_type,
					)
				);

				return false;
		}

		return true;
	}

	/**
	 * @return Sync_Status
	 */
	private function get_status(): Sync_Status {
		// If from a paused state, use the stored status.
		if ( get_option( ACTIVECAMPAIGN_FOR_WOOCOMMERCE_PRODUCT_SYNC_RUNNING_STATUS_NAME ) ) {
			$sync_status_option = get_option( ACTIVECAMPAIGN_FOR_WOOCOMMERCE_PRODUCT_SYNC_RUNNING_STATUS_NAME );
			$status             = json_decode( $sync_status_option, true );

			$status = Sync_Status::from_map( $status );

			$status->is_running = true;
			Sync_Status::update_status( ACTIVECAMPAIGN_FOR_WOOCOMMERCE_PRODUCT_SYNC_RUNNING_STATUS_NAME, $status );
		} else {
			$status = new Sync_Status();
		}

		// set the start time.
		if ( ! isset( $status->start_time ) ) {
			$status->start_time = wp_date( 'F d, Y - G:i:s e' );
		}

		$status->status_name = 'products';

		return $status;
	}

	/**
	 * Notes an option that cancels all scheduled product syncs
	 */
	public function handle_cancel_sync() {
		update_option( ACTIVECAMPAIGN_FOR_WOOCOMMERCE_PRODUCT_SYNC_STOP_CHECK_NAME, 'cancel' );

		wp_send_json_success( 'Product sync stop request processed.' );
	}

	/**
	 * Resets the sync status by deleting the options.
	 */
	public function handle_reset_sync_status() {
		$status = $this->get_status();

		delete_option( ACTIVECAMPAIGN_FOR_WOOCOMMERCE_PRODUCT_SYNC_STOP_CHECK_NAME );
		delete_option( ACTIVECAMPAIGN_FOR_WOOCOMMERCE_PRODUCT_SYNC_SCHEDULED_STATUS_NAME );
		delete_option( ACTIVECAMPAIGN_FOR_WOOCOMMERCE_RUN_PRODUCT_SYNC_NAME );
		Sync_Status::reset( $status );
		wp_send_json_success( 'Product sync data reset.' );
	}

	/**
	 * Gets the safe COFE product types. This is a blacklist to remove any types that cause duplicates, conflicts, or issues with sync.
	 *
	 * @return int[]|string[]
	 */
	private function get_cofe_safe_product_types() {
		$product_types = wc_get_product_types();

		// Blacklist certain types that cause conflicts & duplicates
		if ( isset( $product_types['grouped'] ) ) {
			unset( $product_types['grouped'] );
		}

		if ( isset( $product_types['draft'] ) ) {
			unset( $product_types['draft'] );
		}

		// WC returns array as type_name: type readable so return only the keys
		return array_keys( $product_types );
	}

	public function execute_product_deleted( $product_id ) {
		$logger = new Logger();

		try {
			if ( isset( $product_id ) && ! empty( $product_id ) ) {
				$post_type = get_post_type( $product_id );

				// If it's not an order just ignore it, this could be anything
				if ( 'product' !== $post_type ) {
					return;
				}

				$logger->debug(
					'Product delete triggered',
					[
						'product' => $product_id,
					]
				);

				// Try to find the order in AC
				$ac_order = $this->cofe_product_repository->find_by_externalid( $product_id );

				if ( isset( $ac_order ) && self::validate_object( $ac_order, 'get_id' ) ) {
					$ac_order_id = $ac_order->get_id();

					// If the order exists delete it from AC
					$this->cofe_product_repository->delete( $ac_order_id );

					// Delete a local order for COFE
					$this->delete_order_from_local_table( $product_id );
				}
			}
		} catch ( Throwable $t ) {
			$logger->warning(
				'There was an issue deleting the order from AC.',
				[
					'order_id' => $product_id,
					'message'  => $t->getMessage(),
				]
			);
		}
	}
}
