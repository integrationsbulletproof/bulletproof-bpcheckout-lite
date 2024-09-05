<?php

if (!function_exists('bulletproof_payment_gateway_plugin_notice')) {
    function bulletproof_payment_gateway_plugin_notice($msg)
    {
        echo '<div class="error"><p>' . $msg . '</p></div>';
    }
}

// Attach extra information to the Thank you page
add_action('woocommerce_thankyou',  'bulletproof_add_content_thankyou');
if (!function_exists('bulletproof_add_content_thankyou')) {

    function bulletproof_display_row_information_thankyou($title, $value)
    {

        if ($value != "") {
            $response = "<tr>";
            $response .= "<td><strong>" . $title . "</strong></td>";
            $response .= "<td>" . $value . "</td>";
            $response .= "</tr>";
            return $response;
        } else {
            return "";
        }
    }

    /**
     * Add extra information received to the WooComemrce Thank you page
     */

    function bulletproof_add_content_thankyou()
    {
        $transactionid = "";
        $subscriptionid = "";
        $last4 = "";
        $cctype = "";
        $gateway = "";
        $auth_approved = "";
        $allowed_cc_types = array("VISA", "MASTER", "AMEX", "DISCOVER", "MAESTRO", "JCB");

        if ((isset($_GET['transactionid'])) && ($_GET['transactionid'] != "")) {
            $transactionid = sanitize_text_field($_GET['transactionid']);
            if (!is_numeric($transactionid)) {
                $transactionid = "";
            }
        }
        if ((isset($_GET['subscriptionid'])) && ($_GET['subscriptionid'] != "")) {
            $subscriptionid = sanitize_text_field($_GET['subscriptionid']);
            if (!is_numeric($subscriptionid)) {
                $subscriptionid = "";
            }
        }
        if ((isset($_GET['last4'])) && ($_GET['last4'] != "")) {
            $last4 = sanitize_text_field($_GET['last4']);
            if (!is_numeric($last4)) {
                $last4 = "";
            }
        }
        if ((isset($_GET['cctype'])) && ($_GET['cctype'] != "")) {
            $cctype = strtoupper(sanitize_text_field($_GET['cctype']));
            // only are allowed specific cc types
            if (!in_array($cctype, $allowed_cc_types)) {
                $cctype = "";
            }
            if ($cctype == "MASTER") {
                $cctype = "MASTER CARD";
            } else {
                if ($cctype == "AMEX") {
                    $cctype = "AMERICAN EXPRESS";
                }
            }
        }
        if ((isset($_GET['gateway'])) && ($_GET['gateway'] != "")) {
            $gateway = strtoupper(sanitize_text_field($_GET['gateway']));
            if (($gateway != "BP") && ($gateway != "PAYFAC") && ($gateway != "PAYFAC_FLEX")) {
                $gateway = "";
            }
        }
        if ((isset($_GET['3ds_approved'])) && ($_GET['3ds_approved'] != "")) {
            $auth_approved = strtolower(sanitize_text_field($_GET['3ds_approved']));
            if (($auth_approved != "yes") && ($auth_approved != "no")) {
                $auth_approved = "";
            }
        }

        if ($gateway == "BP") {
            echo "<section id='bulletproof_information' name='bulletproof_information'>";
            echo "<table class='woocommerce-table' id='table_bulletproof_information' name='table_bulletproof_information'>";
            echo "<tbody>";

            echo bulletproof_display_row_information_thankyou("Transaction ID:", $transactionid);
            echo bulletproof_display_row_information_thankyou("Subscription ID:", $subscriptionid);
            if (($cctype != "") && ($last4 != "")) {
                echo bulletproof_display_row_information_thankyou("Credit Card:", $cctype . " XXXXXXXXXXXX" . $last4);
            }
            if ($auth_approved == "yes") {
                echo bulletproof_display_row_information_thankyou("Credit Card 3DS Authentication:", "This transaction has received 3DS authentication");
            }

            echo "</tbody>";
            echo "</table>";
            echo "</section>";
        }
    }
}

// Handle order status changes
add_action('woocommerce_order_status_changed', 'woo_order_status_change_bpcheckout_lite', 10, 3);
if (!function_exists('woo_order_status_change_bpcheckout_lite')) {
    function woo_order_status_change_bpcheckout_lite($order_id, $old_status, $new_status)
    {
        if ($old_status != "") {
            $old_status = strtolower($old_status);
        }
        if ($new_status != "") {
            $new_status = strtolower($new_status);
        }

        // If the order changes from completed to cancelled or refunded , then will trigger a refund on the gateway
        //bulletproof_lite_gateway_api_refund_error

        if (($order_id != "") && ($old_status == "completed") && (($new_status == "cancelled") || ($new_status == "refunded"))) {
            error_log("Starting refund from the BulletProof Lite Plugin for the Order ID#:" . $order_id);
            // Check if the order was paid using the BulletProof Lite plugin (or the BulletProof plus plugin)
            $order = wc_get_order($order_id);
            //|| ! $order->get_transaction_id()
            if (! $order ||  !is_object($order)) {
                error_log("Invalid Order " . $order_id . " received.");
            } else {
                $payment_method_used = $order->get_meta('_payment_method', true);

                if (($payment_method_used == "bulletproof_bpcheckout_lite") || ($payment_method_used == "bulletproof_bpcheckout")) {
                    $date_completed = $order->get_date_completed();
                    $datefrom = new DateTime($date_completed);
                    $dateto = new DateTime();
                    $days_diff = $datefrom->diff($dateto)->days;
                    if ($days_diff < 30) {
                        $lite_gateway = new Bulletproof_Payment_Gateway_Lite();
                        $response_refund = $lite_gateway->process_refund($order_id, $order->get_total());

                        if (is_wp_error($response_refund)) {

                            $the_msg = "Order " . $order_id . " was not refunded.";

                            $error_detail_on_gateway = "";

                            if (isset($response_refund->errors['bulletproof_refund_api_error'][0])) {
                                $error_detail_on_gateway = (string)$response_refund->errors['bulletproof_refund_api_error'][0];
                                if ($error_detail_on_gateway != "") {
                                    $the_msg .= ". Detail:" . $error_detail_on_gateway;
                                }
                            }
                            error_log($the_msg);
                            if ($error_detail_on_gateway != "") {
                                $order->add_order_note("This order can not be refunded by BulletProof because " . $error_detail_on_gateway);
                                $order->save();
                            }
                            return false;
                        } else {
                            $the_msg = "Order " . $order_id . " was refunded succesfully";
                            error_log($the_msg);
                            try {
                                $current_user = wp_get_current_user();
                            } catch (Exception $ex) {
                                $current_user = "";
                            }
                            $order->update_meta_data('_cancel_by',  $current_user);
                            $order->update_meta_data('_bulletproof_refunded',  true);
                        }
                    } else {
                        error_log("Order " . $order_id . " is older than 30 days and can not be refunded in the Payment Gateway");
                        $order->add_order_note("This order is older than 30 days and can not be refunded from the BulletProof Checkout Plugin, but the status in WooCommerce was changed to Cancelled");
                        $order->save();
                    }
                } else {
                    error_log("Order " . $order_id . " was not refunded by BulletProof because was originally paid with other payment gateway");
                    $order->add_order_note("This order can not be refunded by BulletProof because was paid on another payment gateway");
                    $order->save();
                }
            }
        }
    }
}



/**
 * Function to display API error notices.
 * This function displays any error notices generated during the settings save process.
 */
if (!function_exists('bulletproof_display_error_bulletproof_lite_gateway_api_refund_error')) {
    function bulletproof_display_error_bulletproof_lite_gateway_api_refund_error()
    {
        // Display error notices
        $message = get_transient('bulletproof_lite_gateway_api_refund_error');
        if ($message) {
?>
            <div class="notice notice-error is-dismissible">
                <p><?php esc_html($message); ?></p>
            </div>
<?php
            // Delete the transient to avoid displaying the message again
            delete_transient('bulletproof_lite_gateway_api_refund_error');
        }
    }
}

// Handle incoming messages to the checkout page
add_action('woocommerce_before_checkout_form', 'bulletproof_payment_gateway_checkout_msg');
if (!function_exists('bulletproof_payment_gateway_checkout_msg')) {
    function bulletproof_payment_gateway_checkout_msg()
    {
        if ((isset($_GET['msg'])) && ($_GET['msg'] != "")) {
            if (function_exists("wc_add_notice")) {
                wc_add_notice(__(sanitize_text_field(urldecode($_GET['msg'])), 'bulletproof-checkout-lite'), 'error');
            }
        }
    }
}



if (!function_exists('check_bulletproof_lite_environment')) {
    add_action('admin_init',  'check_bulletproof_lite_environment');
    function check_bulletproof_lite_environment()
    {
        $gateway_settings = get_option('woocommerce_bulletproof_bpcheckout_lite_settings');
        if (isset($gateway_settings['username'])) {
            $username = $gateway_settings['username'];
        } else {
            $username = "";
        }
        if (isset($gateway_settings['password'])) {
            $password = $gateway_settings['password'];
        } else {
            $password = "";
        }
        if (isset($gateway_settings['api_key'])) {
            $security_key = $gateway_settings['api_key'];
        } else {
            $security_key = "";
        }
        if (($username == "") || ($password == "") || ($security_key == "")) {
            // wp_send_json_error( 'Username, password, or API key is empty.' );
            // Add admin notice
            $adminnotice = new WC_Admin_Notices();
            $setting_link = admin_url('admin.php?page=wc-settings&tab=checkout&section=bulletproof_bpcheckout_lite');
            $adminnotice->add_custom_notice("", sprintf(__("BulletProof Checkout Lite is almost ready. To get started, <a href='%s'>set your BulletProof Checkout Lite account keys</a>.", 'wc-nmi'), $setting_link));
            $adminnotice->output_custom_notices();
        }
    }
}
