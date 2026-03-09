<?php

/**
 * Webhook for receive information from the BulletProof Gateway
 */
if (!defined("WP_USE_THEMES")) {
    define('WP_USE_THEMES', false);
}
if (file_exists(__DIR__ . '/../../../wp-blog-header.php')) {
    include_once __DIR__ . '/../../../wp-blog-header.php';
} else {
    echo "Invalid location of the WordPress wp-config file";
    die();
    exit;
}

defined('ABSPATH') || exit;

include_once plugin_dir_path(__FILE__) . 'includes/class-wc-bulletproof-webhook.php';

$exec_webhook=new Bulletproof_webhook_class();
$exec_webhook->start_webhook_reception();