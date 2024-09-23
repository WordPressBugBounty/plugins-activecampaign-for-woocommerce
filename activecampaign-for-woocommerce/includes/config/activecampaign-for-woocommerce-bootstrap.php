<?php
/**
 * The plugin configuration file.
 *
 * This file is read by WordPress to generate the plugin information in the plugin
 * admin area. This file also includes all of the dependencies used by the plugin,
 * registers the activation and deactivation functions, and defines a function
 * that starts the plugin.
 *
 * @link              https://www.activecampaign.com/
 * @since             1.0.0
 * @package           Activecampaign_For_Woocommerce
 */

/**
 * Composer Autoload.
 */
require_once __DIR__ . '/../../ac_vendor/autoload.php';

/**
 * Define all our constants.
 */
require_once __DIR__ . '/activecampaign-for-woocommerce-global-constants.php';

/**
 * Bootstraps our dependency container.
 *
 * @see   includes/config/activecampaign-for-woocommerce-class-factories.php
 * @since 1.0.0
 *
 * @return AcVendor\DI\Container
 */
function activecampaign_for_woocommerce_build_container() {

	$builder = new AcVendor\DI\ContainerBuilder();
	$builder->addDefinitions(
		__DIR__ . '/activecampaign-for-woocommerce-class-factories.php'
	);

	static $container = null;

	if ( ! $container ) {
		$container = $builder->build();
	}

	return $container;
}
