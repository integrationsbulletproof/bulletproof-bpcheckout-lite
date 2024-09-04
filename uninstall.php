<?php

/**
 * 
 * This file runs when the plugin in uninstalled (deleted).
 * This will not run when the plugin is deactivated.
 * Any required cleanup script will be here
 *
 */

// If plugin is not being uninstalled, exit (do nothing)
if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

// Actions once the plugin is being uninstalled.
$result=\delete_option("woocommerce_bulletproof_bpcheckout_lite_settings");
$result=\delete_option("woocommerce_bulletproof_bpcheckout_settings_lite_processors");

