<?php
/**
 * Plugin Name: BulletProof Checkout Lite
 * Description: Receive Credit Card payments using the BulletProof Gateway API
 * Version: 1.0
 * Author: BulletProof Checkout.com  
 * Text Domain: bulletproof-payment-gateway-lite
 */

// Define constants for API base URL, gateway identifiers, and response format.
define('BULLETPROOF_CHECKOUT_API_BASE_URL', 'https://bulletproofcheckout.net/API/endpoints/directpost/');
define('BULLETPROOF_CHECKOUT_GATEWAY', 'BP');
define('BULLETPROOF_CHECKOUT_FORMAT', 'raw');
define('BULLETPROOF_BPCHECKOUT_GATEWAY', 'BPCHECKOUT');

// Utility function for printing variables with pre tags for better readability.
if (!function_exists('pr')) {
	function pr( $val = '', $val2 = false ) {
		echo '<pre>';
		print_r($val);
		echo '</pre>';
		if ($val2) {
			esc_html( $val2 );
			die;
		}
	}
}

/**
 * Plugin initialization: Checks if WooCommerce is active and includes necessary files.
 */
add_action('plugins_loaded', 'bulletproof_payment_integration');

/**
 * Check if WooCommerce is active and include the necessary files.
 */
function bulletproof_payment_integration() {
	/**
	 * Filter the active plugins to check if WooCommerce is active.
	 *
	 * @param array $active_plugins An array of active plugin filenames.
	 * @return bool Whether WooCommerce is active or not.
   */
	if (in_array('woocommerce/woocommerce.php', apply_filters('active_plugins', get_option('active_plugins')))) {
		// Include the BulletProof Payment Gateway class file.
		include_once plugin_dir_path(__FILE__) . 'includes/class-wc-bulletproof-payment-gateway-lite.php';
		include_once plugin_dir_path(__FILE__) . 'includes/class-wc-bulletproof-shop-orders.php';
	} else {
		// Display an admin notice if WooCommerce is not active.
		add_action('admin_notices', 'bulletproof_payment_gateway_plugin_notice');
	}
}

// Register activation hook to flush rewrite rules on plugin activation.
register_activation_hook(__FILE__, 'flush_rewrite_rules_on_activation');

/**
 * Flush rewrite rules on plugin activation.
 */
function flush_rewrite_rules_on_activation() {

	// Flush WordPress rewrite rules.
	flush_rewrite_rules();
}


// Deactivation Hook
function bulletproof_deactivate_plugin() {
}

// Register deactivation hooks

register_deactivation_hook(__FILE__, 'bulletproof_deactivate_plugin');


// Hook into WooCommerce payment gateways to add BulletProof Payment Gateway class.
add_filter('woocommerce_payment_gateways', 'bulletproof_add_gateway_class');

/**
 * Add BulletProof Payment Gateway class to WooCommerce payment gateways.
 *
 * @param array $gateways
 * @return array
 */
function bulletproof_add_gateway_class( $gateways ) {
	$gateways[] = 'WC_Bulletproof_Payment_Gateway_Lite';
	return $gateways;
}

/**
 * Display an admin notice if WooCommerce is not active.
 */
function bulletproof_payment_gateway_plugin_notice() {
	echo '<div class="error"><p>Please activate WooCommerce to use Custom WooCommerce Plugin.</p></div>';
}
