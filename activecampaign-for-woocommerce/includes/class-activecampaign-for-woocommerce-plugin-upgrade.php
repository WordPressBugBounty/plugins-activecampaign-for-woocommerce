<?php
/*eslint no-unused-vars: ["error", { "varsIgnorePattern": "[^...]" }]*/

/**
 * The file that defines the Plugin_Upgrade_Command Class.
 *
 * @link       https://www.activecampaign.com/
 * @since      1.0.0
 *
 * @package    Activecampaign_For_Woocommerce
 * @subpackage Activecampaign_For_Woocommerce/includes/commands
 */

use Activecampaign_For_Woocommerce_Executable_Interface as Executable;
use Activecampaign_For_Woocommerce_Logger as Logger;
use Activecampaign_For_Woocommerce_Ecom_Order_Repository as Order_Repository;

/**
 * The Uninstall_Plugin_Command Class.
 *
 * This command is called when uninstalling the plugin and handled erasing all plugin-specific data.
 *
 * @since      1.0.0
 * @package    Activecampaign_For_Woocommerce
 * @subpackage Activecampaign_For_Woocommerce/includes/commands
 * @author     Joshua Bartlett <jbartlett@activecampaign.com>
 */
class Activecampaign_For_Woocommerce_Plugin_Upgrade implements Executable {
	use Activecampaign_For_Woocommerce_Abandoned_Cart_Utilities;
	/**
	 * The custom ActiveCampaign logger
	 *
	 * @var Activecampaign_For_Woocommerce_Logger
	 */
	private $logger;

	/**
	 * The expected db version
	 *
	 * @var string
	 */
	private $db_version;

	/**
	 * The Ecom Order Repo
	 *
	 * @var Activecampaign_For_Woocommerce_Ecom_Order_Repository
	 */
	private $order_repository;

	/**
	 * Activecampaign_For_Woocommerce_Plugin_Upgrade_Command constructor.
	 *
	 * @param     Activecampaign_For_Woocommerce_Logger|null           $logger The logger.
	 * @param     Activecampaign_For_Woocommerce_Ecom_Order_Repository $order_repository The ecom order repository.
	 */
	public function __construct(
		Logger $logger = null,
		Order_Repository $order_repository
	) {
		if ( ! $logger ) {
			$this->logger = new Logger();
		} else {
			$this->logger = $logger;
		}

		$this->order_repository = $order_repository;
	}

	// phpcs:disable Generic.CodeAnalysis.UnusedFunctionParameter
	/**
	 * Executes the command.
	 *
	 * Checks for any upgrades that need to happen when the plugin is updated
	 *
	 * @param     mixed ...$args     An array of arguments that may be passed in from the action/filter called.
	 *
	 * @since 1.0.0
	 */
	public function execute( ...$args ) {
		$this->logger      = new Logger();
		$installed_version = get_option( 'activecampaign_for_woocommerce_db_version' );

		$this->logger->debug(
			'Plugin Upgrade Check...',
			[
				'Your AC Table Version'   => $installed_version,
				'Plugin AC Table Version' => $this->get_plugin_db_version(),
			]
		);

		if ( ! $installed_version ) {
			$this->logger->notice( 'Plugin Upgrade: We need to add the ActiveCampaign table.' );
			$this->install_table();
		} elseif ( $installed_version !== $this->get_plugin_db_version() ) {
			$this->logger->notice(
				'Plugin Upgrade: It looks like your installed version needs a table upgrade.',
				[
					'Your AC Table Version'   => $installed_version,
					'Plugin AC Table Version' => $this->get_plugin_db_version(),
				]
			);
			$this->upgrade_table();
		} elseif ( $installed_version === $this->get_plugin_db_version() ) {
			// $this->logger->debug( 'Plugin Upgrade Command: Plugin db is up to date.' );
			return;
		} else {
			$this->logger->notice( 'Plugin Upgrade: Plugin is unsure what to do with the upgrade.' );
		}
	}
	// phpcs:enable

	/**
	 * Validates the table is installed.
	 */
	public function verify_table() {
		$this->logger = new Logger();
		$table_exists = false;
		global $wpdb;

		try {
			$table_name = $wpdb->prefix . ACTIVECAMPAIGN_FOR_WOOCOMMERCE_TABLE_NAME;

			if ( $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $table_name ) ) === $table_name ) {
				$table_exists = true;
			} else {
				$this->logger->info( 'Plugin Upgrade Command: Could not find the ' . ACTIVECAMPAIGN_FOR_WOOCOMMERCE_TABLE_NAME . ' table.' );

				// Verify if the table just wasn't renamed properly
				$this->rename_table();
				$this->install_table();

				if ( $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $table_name ) ) === $table_name ) {
					$table_exists = true;
				} else {
					$this->logger->error(
						'Plugin Upgrade: There was an exception in creating or finding the ActiveCampaign table! ActiveCampaign will not properly function without this table.',
						[
							'suggested_action' => 'Please verify the ' . ACTIVECAMPAIGN_FOR_WOOCOMMERCE_TABLE_NAME . ' table was created and that this user has table creation permission.',
							'ac_code'          => 'PUCM_138',
						]
					);
				}
			}
		} catch ( Throwable $t ) {
			$this->logger->warning(
				'Plugin Upgrade: There was an exception in table verification...',
				[
					'message' => $t->getMessage(),
					'trace'   => $this->logger->clean_trace( $t->getTrace() ),
				]
			);

			$table_exists = false;
		}

		if ( $table_exists ) {
			$installed_version = get_option( 'activecampaign_for_woocommerce_db_version' );

			if ( $installed_version !== $this->get_plugin_db_version() ) {
				$this->logger->notice( 'Plugin Upgrade Verify Table: It looks like your installed version needs a table upgrade.' );
				$this->upgrade_table();
				$table_exists = true;
			}
		}

		return $table_exists;
	}

	/**
	 * Adds our table to the WordPress install.
	 */
	private function install_table() {
		$this->logger->info( 'Plugin Upgrade Install Table: Install the activecampaign_for_woocommerce_abandoned_cart table...' );
		global $wpdb;
		try {
			$table_name      = $wpdb->prefix . ACTIVECAMPAIGN_FOR_WOOCOMMERCE_TABLE_NAME;
			$charset_collate = $wpdb->get_charset_collate();

			$sql = "CREATE TABLE $table_name (
			`id` INT NOT NULL AUTO_INCREMENT,
			`synced_to_ac` TINYINT NOT NULL DEFAULT 0,
			`customer_id` VARCHAR(45) NULL,
			`customer_email` VARCHAR(255),
			`customer_first_name` VARCHAR(255),
			`customer_last_name` VARCHAR(255),
			`last_access_time` DATETIME,
			`order_date` DATETIME NULL,
			`user_ref_json` MEDIUMTEXT,
			`customer_ref_json` LONGTEXT,
			`cart_ref_json` LONGTEXT,
			`cart_totals_ref_json` MEDIUMTEXT,
			`removed_cart_contents_ref_json` LONGTEXT,
			`activecampaignfwc_order_external_uuid` VARCHAR(255),
			`ac_externalcheckoutid` VARCHAR(155) NULL,
			`wc_order_id` INT NULL,
			`ac_order_id` VARCHAR(45) NULL,
			`abandoned_date` DATETIME NULL,
			`ac_customer_id` VARCHAR(45) NULL,
			PRIMARY KEY (`id`),
			INDEX `synced_to_ac_last_access_time` (`last_access_time` ASC, `synced_to_ac` ASC),
			UNIQUE INDEX `wc_order_id_UNIQUE` (`wc_order_id` ASC),
			UNIQUE INDEX `ac_externalcheckoutid_UNIQUE` (`ac_externalcheckoutid` ASC),
			INDEX `synced_to_ac` (`synced_to_ac` ASC),
			INDEX `wc_order_id_ac_externalcheckoutid` (`wc_order_id` ASC, `ac_externalcheckoutid` ASC)) $charset_collate;";

			require_once ABSPATH . 'wp-admin/includes/upgrade.php';
			dbDelta( $sql );

			// Add the db version
			add_option( 'activecampaign_for_woocommerce_db_version', $this->get_plugin_db_version() );
			$this->logger->info( 'Plugin Upgrade Command: Table installation finished!' );
			$this->disable_old_webhooks();
		} catch ( Throwable $t ) {
			$this->logger->error(
				'Plugin Upgrade install table: There was an exception creating the abandoned cart table. Please verify the plugin can create this table.',
				[
					'message' => $t->getMessage(),
					'trace'   => $this->logger->clean_trace( $t->getTrace() ),
				]
			);
		}
	}

	/**
	 * Upgrades the table with new changes.
	 * The dbDelta handles checking if this upgrade needs to take place.
	 */
	private function upgrade_table() {
		global $wpdb;

		// Rename table before attempting upgrades
		$this->rename_table();

		$table_name = $wpdb->prefix . ACTIVECAMPAIGN_FOR_WOOCOMMERCE_TABLE_NAME;

		// v1.1.0 //
		try {
			$sql = "CREATE TABLE $table_name (
				`order_date` DATETIME NULL AFTER `last_access_time` ,
				`ac_externalcheckoutid` VARCHAR(155) NULL AFTER `activecampaignfwc_order_external_uuid`,
				`wc_order_id` INT NULL AFTER `ac_externalcheckoutid`,
				`ac_order_id` VARCHAR(45) NULL AFTER `wc_order_id`,
				`abandoned_date` DATETIME NULL AFTER `ac_order_id`,
				`ac_customer_id` VARCHAR(45) NULL AFTER `abandoned_date`,
				UNIQUE INDEX `ac_externalcheckoutid_UNIQUE` (`ac_externalcheckoutid` ASC)
				)
			";

			require_once ABSPATH . 'wp-admin/includes/upgrade.php';
			dbDelta( $sql );

			// phpcs:disable

			if (
				$wpdb->get_var( 'SELECT * FROM INFORMATION_SCHEMA.STATISTICS WHERE TABLE_NAME = "'.$table_name.'" AND INDEX_NAME = "customer_id_UNIQUE" ' )
			){
				$wpdb->query( 'DROP INDEX `customer_id_UNIQUE` ON '.$table_name);
				$this->check_for_error( $wpdb );
			}

			// If everything went well this shouldn't error
			$wpdb->get_results( 'SELECT order_date, ac_order_id, ac_externalcheckoutid, wc_order_id, abandoned_date, ac_customer_id FROM ' . $table_name . ' LIMIT 1' );
			$this->check_for_error( $wpdb );
			// phpcs:enable

		} catch ( Throwable $t ) {
			$this->logger->error(
				'There was an issue upgrading the table for v1.1.0.',
				[
					'message' => $t->getMessage(),
					'trace'   => $this->logger->clean_trace( $t->getTrace() ),
				]
			);
			$this->check_for_error( $wpdb );
		}

		// v1.1.1 //
		try {
			if ( $wpdb->last_error ) {
				$this->logger->notice(
					'Update db check failed for some reason...',
					[
						'wpdb_last_error'   => $wpdb->last_error,
						'update to version' => $this->get_plugin_db_version(),
					]
				);
			} else {
				// Add the db version
				update_option( 'activecampaign_for_woocommerce_db_version', $this->get_plugin_db_version() );
				$this->update_abandoned_carts();
			}
		} catch ( Throwable $t ) {
			$this->logger->error(
				'There was an error upgrading the table for v1.1.1.',
				[
					'message' => $t->getMessage(),
					'trace'   => $this->logger->clean_trace( $t->getTrace() ),
				]
			);
			$this->check_for_error( $wpdb );
		}

		// v1.1.2 //
		try {
			$sql = "ALTER TABLE $table_name ADD UNIQUE INDEX `wc_order_id_UNIQUE` (`wc_order_id` ASC)";

			require_once ABSPATH . 'wp-admin/includes/upgrade.php';
			dbDelta( $sql );
			$this->check_for_error( $wpdb );
		} catch ( Throwable $t ) {
			$this->logger->error(
				'There was an error upgrading the table for v1.1.2.',
				[
					'message' => $t->getMessage(),
					'trace'   => $this->logger->clean_trace( $t->getTrace() ),
				]
			);
			$this->check_for_error( $wpdb );
		}

		// v1.1.3 //
		try {
			$sql = "ALTER TABLE $table_name ADD INDEX `synced_to_ac` (`synced_to_ac` ASC)";

			require_once ABSPATH . 'wp-admin/includes/upgrade.php';
			dbDelta( $sql );
			$this->check_for_error( $wpdb );

			$sql = "ALTER TABLE $table_name ADD INDEX `wc_order_id_ac_externalcheckoutid` (`wc_order_id` ASC, `ac_externalcheckoutid` ASC)";

			require_once ABSPATH . 'wp-admin/includes/upgrade.php';
			dbDelta( $sql );
			$this->check_for_error( $wpdb );
		} catch ( Throwable $t ) {
			$this->logger->error(
				'There was an error upgrading the table for v1.1.3.',
				[
					'message' => $t->getMessage(),
					'trace'   => $this->logger->clean_trace( $t->getTrace() ),
				]
			);
			$this->check_for_error( $wpdb );
		}

		$this->logger->info( 'Plugin Upgrade Command: Table upgrade finished!' );
		$this->disable_old_webhooks();
	}

	/**
	 * Checks for an error in the last query performed using wpdb.
	 *
	 * @param WPDB $wpdb Global WP db command.
	 */
	private function check_for_error( $wpdb ) {
		if ( $wpdb->last_error ) {
			$this->logger->warning(
				'Issue encountered on the table upgrade...',
				[
					'message' => $wpdb->last_error,
				]
			);
		}
	}

	/**
	 * Rename table if using the old name
	 *
	 * @version 1.1.1
	 */
	private function rename_table() {
		global $wpdb;
		$old_table_name = $wpdb->prefix . 'activecampaign_for_woocommerce_abandoned_cart';
		// phpcs:disable
		if ( $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $old_table_name ) ) === $old_table_name ) {
			$new_table_name = $wpdb->prefix . ACTIVECAMPAIGN_FOR_WOOCOMMERCE_TABLE_NAME;
			$wpdb->query( 'ALTER TABLE ' . $old_table_name . ' RENAME TO `' . $new_table_name . '`' );

			try {
				if ( $wpdb->last_error ) {
					$this->logger->warning(
						'Table could not be renamed...',
						[
							'wpdb_last_error'   => $wpdb->last_error,
							'old_table_name' => $old_table_name,
							'new_table_name'=>$new_table_name,
						]
					);
				} else {
					$this->logger->info( 'Plugin Upgrade Command: Table rename finished!' );
				}
			} catch ( Throwable $t ) {
				$this->logger->warning(
					'There was an issue checking the table update.',
					[
						'message' => $t->getMessage(),
						'trace'   => $this->logger->clean_trace( $t->getTrace() ),
					]
				);
			}
		}
		// phpcs:enable
	}

	/**
	 * Gets the current db version of our plugin.
	 *
	 * @return string The expected db version number.
	 */
	private function get_plugin_db_version() {
		if ( ! isset( $this->db_version ) ) {
			$this->db_version = ACTIVECAMPAIGN_FOR_WOOCOMMERCE_DB_VERSION;
		}

		return $this->db_version;
	}

	/**
	 * The update adds needed fields to each row and requires that an abandoned cart have that filled with the last_access_time
	 */
	private function update_abandoned_carts() {
		global $wpdb;
		$table_name = $wpdb->prefix . ACTIVECAMPAIGN_FOR_WOOCOMMERCE_TABLE_NAME;

		if ( $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $table_name ) ) === $table_name ) {
			$synced_abandoned_carts = $wpdb->get_results(
			// phpcs:disable
				$wpdb->prepare( 'SELECT id, customer_id, customer_email, last_access_time, activecampaignfwc_order_external_uuid 
						FROM
							`' . $wpdb->prefix . ACTIVECAMPAIGN_FOR_WOOCOMMERCE_TABLE_NAME . '`
						WHERE
							synced_to_ac = %s
							AND wc_order_id IS NULL
							AND order_date IS NULL
							AND abandoned_date IS NULL
							AND ac_externalcheckoutid IS NULL',
					1
				)
			// phpcs:enable
			);

			foreach ( $synced_abandoned_carts as $abc_order ) {
				$externalcheckout_id = $this->generate_externalcheckoutid( $abc_order->customer_id, $abc_order->customer_email, $abc_order->activecampaignfwc_order_external_uuid );

				$data['abandoned_date']        = $abc_order->last_access_time;
				$data['ac_externalcheckoutid'] = $externalcheckout_id;

				$wpdb->update(
					$wpdb->prefix . ACTIVECAMPAIGN_FOR_WOOCOMMERCE_TABLE_NAME,
					$data,
					[
						'id' => $abc_order->id,
					]
				);
			}
		}
	}

	/**
	 * Disables our old webhooks on upgrade.
	 */
	private function disable_old_webhooks() {
		try {
			global $wpdb;
			$wpdb->query(
				'UPDATE ' . $wpdb->prefix . "wc_webhooks SET `status` = 'disabled' WHERE `name` LIKE '%ActiveCampaign WooCommerce Deep Data%'"
			);
			$this->logger->notice( 'ActiveCampaign is using COFE service going forward. Deepdata webhooks have been disabled.' );
		} catch ( Throwable $t ) {
			$this->logger->warning( 'There was an issue disabling ActiveCampaign webhooks.' );
		}
	}
}
