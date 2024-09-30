<?php

/**
 * Plugin Name: BulletProof Checkout Lite
 * Plugin URI: https://www.bulletproof-checkout.com/
 * Description: Receive Credit Card payments using the Lite version of the BulletProof Gateway.
 * Version: 1.0.8
 * Author: BulletProof Checkout <support@bulletproof-checkout.com>
 * Author URI: https://www.bulletproof-checkout.com/
 * License: GPLv3
 * License URI: https://www.gnu.org/licenses/gpl-3.0.html
 * Text Domain: bulletproof-checkout-lite
 * WC requires at least: 5.0
 * WC tested up to: 9.3.2
 * Tested up to: 6.6.2
 * Requires PHP: 7.4
 * Requires Plugins: woocommerce
 */

if (!defined('ABSPATH')) {
	exit;
}
// Define constants for API base URL, gateway identifiers, and response format.
// Please, do not change the constants
// Live Endpoint
if (!defined('BULLETPROOF_CHECKOUT_API_BASE_URL')) define('BULLETPROOF_CHECKOUT_API_BASE_URL', 'https://bulletproofcheckout.net/API/endpoints/directpost/');
// Sandbox Endpoint - Transactions on sandbox are not registered in the portal
if (!defined('BULLETPROOF_CHECKOUT_API_BASE_URL_SANDBOX')) define('BULLETPROOF_CHECKOUT_API_BASE_URL_SANDBOX', 'https://bulletproofcheckout.net/APIsandbox/endpoints/directpost/');


if (!defined('BULLETPROOF_CHECKOUT_GATEWAY')) define('BULLETPROOF_CHECKOUT_GATEWAY', 'BP');
if (!defined('BULLETPROOF_CHECKOUT_FORMAT')) define('BULLETPROOF_CHECKOUT_FORMAT', 'raw');
if (!defined('BULLETPROOF_BPCHECKOUT_GATEWAY')) define('BULLETPROOF_BPCHECKOUT_GATEWAY', 'BPCHECKOUT');
// If the Official Mobile App will be used then will need to disable BULLETPROOF_CHECKOUT_ADDORDERLISTCOLUMNS
// In the Official Mobile App BulletProof does not support Authorize and Capture later
if (!defined('BULLETPROOF_CHECKOUT_ADDORDERLISTCOLUMNS')) define('BULLETPROOF_CHECKOUT_ADDORDERLISTCOLUMNS', false);
// Some hosting providers auto-enabled JetPack SSO whih is buggy with the Official Mobile App
if (!defined('BULLETPROOF_CHECKOUT_DISABLEJETPACKSSO')) define('BULLETPROOF_CHECKOUT_DISABLEJETPACKSSO', false);


/**
 * Check if WooCommerce is active and include the necessary files.
 */
if (!function_exists('bulletproof_payment_integration')) {
	/**
	 * Plugin initialization: Checks if WooCommerce is active and includes necessary files.
	 */
	add_action('plugins_loaded', 'bulletproof_payment_integration');

	function bulletproof_payment_integration()
	{
		/**
		 * Filter the active plugins to check if WooCommerce is active.
		 *
		 * @since 1.0.0
		 * @param array $active_plugins An array of active plugin filenames.
		 * @return bool Whether WooCommerce is active or not.
		 */
		if (in_array('woocommerce/woocommerce.php', apply_filters('active_plugins', get_option('active_plugins')))) {
			// Include the BulletProof Payment Gateway Lite class file.

			include_once plugin_dir_path(__FILE__) . 'includes/class-wc-bulletproof-payment-gateway-lite.php';
			include_once plugin_dir_path(__FILE__) . 'includes/class-wc-bulletproof-shop-orders.php';
			include_once plugin_dir_path(__FILE__) . 'includes/common.php';

		} else {
			// Display an admin notice if WooCommerce is not active.
			add_action('admin_notices', 'bulletproof_payment_gateway_plugin_notice_not_activated');
		}
	}
}

// declares compatibility with HPOS (High Performance Orders)

add_action('before_woocommerce_init', function(){
    if ( class_exists( \Automattic\WooCommerce\Utilities\FeaturesUtil::class ) ) {
        \Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility( 'custom_order_tables', __FILE__, true );
    }
});


// Register activation hook to flush rewrite rules on plugin activation.
register_activation_hook(__FILE__, 'bulletproof_flush_rewrite_rules_on_activation');

/**
 * Flush rewrite rules on plugin activation.
 */
if (!function_exists('bulletproof_flush_rewrite_rules_on_activation')) {
	function bulletproof_flush_rewrite_rules_on_activation()
	{

		// Flush WordPress rewrite rules.
		flush_rewrite_rules();
	}
}


// Hook into WooCommerce payment gateways to add BulletProof Payment Gateway class.
add_filter('woocommerce_payment_gateways', 'bulletproof_add_gateway_class');

/**
 * Add BulletProof Payment Gateway class to WooCommerce payment gateways.
 *
 * @param array $gateways
 * @return array
 */
if (!function_exists('bulletproof_add_gateway_class')) {
	function bulletproof_add_gateway_class($gateways)
	{
		$gateways[] = 'Bulletproof_Payment_Gateway_Lite';
		return $gateways;
	}
}

/**
 * Display an admin notice if WooCommerce is not active.
 */
if (!function_exists('bulletproof_payment_gateway_plugin_notice_not_activated')) {
	function bulletproof_payment_gateway_plugin_notice_not_activated()
	{
		bulletproof_payment_gateway_plugin_notice("Please activate WooCommerce to use Custom WooCommerce Plugin.");
	}
}

add_filter('plugin_action_links_' . plugin_basename(__FILE__), 'bulletproof_gateway_lite_2024_visitweb');
if (!function_exists('bulletproof_gateway_lite_2024_visitweb')) {
	function bulletproof_gateway_lite_2024_visitweb($settings)
	{
		// Create the link.
		$settings_link = '<a href="https://bulletproof-checkout.com">Visit Site</a>';
		// Adds the link to the end of the array.
		array_push(
			$settings,
			$settings_link
		);

		return $settings;
	}
}

		

