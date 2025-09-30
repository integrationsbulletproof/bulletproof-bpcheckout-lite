<?php

if (!defined('ABSPATH')) {
    exit;
}

class Bulletproof_webhook_class
{

    /**
     * Utility functions
     */

    public function get_ip()
    {
        if (php_sapi_name() != "cli") {
            if (array_key_exists('HTTP_X_FORWARDED_FOR', $_SERVER)) {

                if (strpos($_SERVER['HTTP_X_FORWARDED_FOR'], ',')) {
                    $ips = explode(',', $_SERVER['HTTP_X_FORWARDED_FOR']);

                    return trim(reset($ips));
                } else {
                    return $_SERVER['HTTP_X_FORWARDED_FOR'];
                }
            } else if (array_key_exists('REMOTE_ADDR', $_SERVER)) {
                return $_SERVER['REMOTE_ADDR'];
            } else if (array_key_exists('HTTP_CLIENT_IP', $_SERVER)) {
                return $_SERVER['HTTP_CLIENT_IP'];
            }
        }
        return '';
    }

    /**
     * Update the order status from Pending Payment to the status after payment
     */

    private function update_order($data, $status_after_order_completed)
    {
        if ((isset($data['order_id'])) && (is_numeric($data['order_id'])) && (isset($data['transaction_id']))) {
            $order = wc_get_order($data['order_id']);
            if ($order) {
                $current_order_status = $order->get_status();
                $payment_method_used = $order->get_meta('_payment_method', true);

                if (($payment_method_used == "bulletproof_bpcheckout_lite") || ($payment_method_used == "bulletproof_bpcheckout")) {
                    if ($order && ($current_order_status === 'pending' || $current_order_status === 'Pending payment' || $current_order_status === 'failed' || $current_order_status === 'cancelled' || $current_order_status === 'Failed' || $current_order_status === 'Canceled')) {
                        if ($status_after_order_completed != "") {
                            $the_msg = 'Status updated by the BulletProof Plugin from ' . $current_order_status . ' to ' . $status_after_order_completed;
                            $translated_msg = did_action('init') ? __($the_msg, 'bulletproof-checkout-lite') : $the_msg;
                            $order->update_status($status_after_order_completed, $translated_msg);
                        }
                    }

                    // Fields to update
                    if ($order && ($current_order_status === 'complete' || $current_order_status === 'completed' || $current_order_status === 'pending' || $current_order_status === 'Pending payment')) {
                        if ((isset($data['subscription_id']) && $data['subscription_id'] != "")) {
                            $subscription_id = $data['subscription_id'];
                        } else {
                            $subscription_id = "";
                        }
                        $first_name = "";
                        $last_name = "";
                        if (isset($data['billing_address'])) {
                            $billing_data = $data['billing_address'];
                            if (isset($billing_data['first_name'])) {
                                $first_name = $billing_data['first_name'];
                            }
                            if (isset($billing_data['last_name'])) {
                                $last_name = $billing_data['last_name'];
                            }
                        }

                        $payment_type = "cc";  // Fixed to Credit Card until release the new payment methods
                        $cctype = "";
                        $first6 = "";
                        $last4 = "";
                        $cavv = "";
                        $eci = "";
                        $cardholder_auth = "";
                        if (isset($data['card'])) {
                            $card_data = $data['card'];
                            if (isset($card_data['cc_type'])) {
                                $cctype = $card_data['cc_type'];
                                if ($cctype != "") $cctype = strtoupper($cctype);
                            }
                            if (isset($card_data['cc_number'])) {
                                $cc_number = $card_data['cc_number'];
                                if ($cc_number != "" && strlen($cc_number) > 10) {
                                    $first6 = substr($cc_number, 0, 6);
                                    $last4 = substr($cc_number, -4);
                                }
                            }
                            if (isset($card_data['cavv'])) {
                                $cavv = $card_data['cavv'];
                            }
                            if (isset($card_data['eci'])) {
                                $eci = $card_data['eci'];
                            }
                            if (isset($card_data['cardholder_auth'])) {
                                $cardholder_auth = $card_data['cardholder_auth'];
                            }
                        }
                        if ((isset($data['processor_id']) && $data['processor_id'] != "")) {
                            $processor_id = $data['processor_id'];
                        } else {
                            $processor_id = "";
                        }

                        // get the status after order completed
                        $gateway_class = new Bulletproof_Payment_Gateway_Lite();
                        $gateway_class->bulletproof_lite_update_order_meta($data['order_id'], $data['transaction_id'], $order, $subscription_id, $first_name, $last_name, $payment_type, $cctype, $first6, $last4, $processor_id, $cardholder_auth, $cavv, $eci);
                    }
                }
            }
        }
    }

    /**
     * Register any sale failure
     */
    private function register_sale_failure($data, $status_after_order_completed)
    {
        if ((isset($data['order_id'])) && (is_numeric($data['order_id'])) && (isset($data['transaction_id']))) {
            $order = wc_get_order($data['order_id']);
            if ($order) {
                $payment_method_used = $order->get_meta('_payment_method', true);

                if (($payment_method_used == "bulletproof_bpcheckout_lite") || ($payment_method_used == "bulletproof_bpcheckout")) {
                    $order->update_meta_data('_bulletproof_gateway_failed_transaction', $data['transaction_id']);
                    if (isset($data['requested_amount'])) {
                        $order->update_meta_data('_bulletproof_gateway_failed_amount', $data['requested_amount']);
                    }
                    if (isset($data['processor_id'])) {
                        $processor = $data['processor_id'];
                    } else {
                        $processor = "";
                    }
                    $cctype = "";
                    $first6 = "";
                    $last4 = "";
                    $cavv = "";
                    $eci = "";
                    $cardholder_auth = "";
                    if (isset($data['card'])) {
                        $card_data = $data['card'];
                        if (isset($card_data['cc_type'])) {
                            $cctype = $card_data['cc_type'];
                            if ($cctype != "") $cctype = strtoupper($cctype);
                        }
                        if (isset($card_data['cc_number'])) {
                            $cc_number = $card_data['cc_number'];
                            if ($cc_number != "" && strlen($cc_number) > 10) {
                                $first6 = substr($cc_number, 0, 6);
                                $last4 = substr($cc_number, -4);
                            }
                        }
                        if (isset($card_data['cavv'])) {
                            $cavv = $card_data['cavv'];
                        }
                        if (isset($card_data['eci'])) {
                            $eci = $card_data['eci'];
                        }
                        if (isset($card_data['cardholder_auth'])) {
                            $cardholder_auth = $card_data['cardholder_auth'];
                        }
                        // CardHolder Authentication
                        if ($eci != "") {
                            $order->update_meta_data('_bulletproof_gateway_failed_eci', $eci);
                        }
                        if ($cavv != "") {
                            $order->update_meta_data('_bulletproof_gateway_failed_cavv', $cavv);
                        }
                        if ($cardholder_auth != "") {
                            $order->update_meta_data('_bulletproof_gateway_failed_cardholder_auth', $cardholder_auth);
                        }
                        // Payment type
                        $order->update_meta_data('_bulletproof_gateway_failed_payment_type', "cc");
                        // CC Brand
                        if ($cctype != "") {
                            $order->update_meta_data('_bulletproof_gateway_failed_cctype', $cctype);
                        }
                        if ($first6 != "") {
                            $order->update_meta_data('_bulletproof_gateway_failed_first6', $first6);
                        }
                        if ($last4 != "") {
                            $order->update_meta_data('_bulletproof_gateway_failed_last4', $last4);
                        }
                        if ($processor != "") {
                            $order->update_meta_data('_bulletproof_gateway_failed_processor', $processor);
                        }
                    }
                    $order->update_meta_data('_bulletproof_gateway_failed_sale_date', current_time('mysql'));
                    $order->save();
                }
            }
        }
    }


    /**
     * Register any refund failure
     */
    private function register_refund_failure($data, $status_after_order_completed)
    {
        if ((isset($data['order_id'])) && (is_numeric($data['order_id']))) {
            $order = wc_get_order($data['order_id']);
            if ($order) {
                // Only if the payment method used was the BulletProof Lite Plugin will update the status
                $payment_method_used = $order->get_meta('_payment_method', true);
                if (($payment_method_used == "bulletproof_bpcheckout_lite") || ($payment_method_used == "bulletproof_bpcheckout")) {
                    if ((isset($data['transaction_id'])) && ($data['transaction_id'] != "")) {
                        $the_msg = 'Refund failure registered by the BulletProof Plugin for transaction ' . $data['transaction_id'];
                        $translated_msg = did_action('init') ? __($the_msg, 'bulletproof-checkout-lite') : $the_msg;
                        $order->add_order_note($translated_msg);
                        // register the last refund failure date
                        $order->update_meta_data('_bulletproof_gateway_failed_refund_date', current_time('mysql'));
                        $order->save();
                    }
                }
            }
        }
    }

    /**
     * Update the order status from Pending Payment, pendingsettlement or completed to refund
     */
    private function refund_order($data, $status_after_order_completed)
    {
        if ((isset($data['order_id'])) && (is_numeric($data['order_id']))) {
            $order = wc_get_order($data['order_id']);
            if ($order) {
                if ($order && ($order->get_status() === 'pending' || $order->get_status() === 'Pending payment') || ($order->get_status() === 'completed') || ($order->get_status() === $status_after_order_completed)) {
                    // Only if the payment method used was the BulletProof Lite Plugin will update the status
                    $payment_method_used = $order->get_meta('_payment_method', true);
                    if (($payment_method_used == "bulletproof_bpcheckout_lite") || ($payment_method_used == "bulletproof_bpcheckout")) {
                        // Check if the order amount matches (full refund)
                        if ((isset($data['requested_amount'])) && ($data['requested_amount'] != "")) {
                            // compares against the order amount
                            if (number_format($data['requested_amount'], 2, '.', '') == number_format($order->get_total(), 2, '.', '')) {
                                $valid_amount = true;
                            } else {
                                if ($data['requested_amount'] < $order->get_total()) {
                                    $valid_amount = false;
                                } else {
                                    $valid_amount = true;
                                }
                            }
                        } else {
                            // If no amount was received, potentially is a full refund
                            $valid_amount = true;
                        }
                        if ($valid_amount) {
                            $the_msg = 'Status updated to refund by the BulletProof Plugin';
                            $translated_msg = did_action('init') ? __($the_msg, 'bulletproof-checkout-lite') : $the_msg;
                            $order->update_status("refunded", $translated_msg);
                        } else {
                            // Partial refund was triggered, just leave a note, but do not update the order status
                            $currency_code = $order->get_currency();
                            $currency_symbol = get_woocommerce_currency_symbol($currency_code);
                            $the_msg = "Partial refund issued by the BulletProof Gateway for " . $currency_symbol . number_format($data['requested_amount'], 2, '.', '');
                            $order->add_order_note($the_msg);
                        }
                        $order->update_meta_data('_bulletproof_gateway_success_refund_date', current_time('mysql'));
                        $order->save();
                    }
                }
            }
        }
    }

    /**
     * Update the order status from Pending Payment, pendingsettlement or completed to cancelled
     */
    private function cancel_order($data, $status_after_order_completed)
    {
        if ((isset($data['order_id'])) && (is_numeric($data['order_id']))) {
            $order = wc_get_order($data['order_id']);
            if ($order && ($order->get_status() === 'pending' || $order->get_status() === 'Pending payment') || ($order->get_status() === 'completed') || ($order->get_status() === $status_after_order_completed)) {
                // Only if the payment method used was the BulletProof Lite Plugin will update the status
                $payment_method_used = $order->get_meta('_payment_method', true);
                if (($payment_method_used == "bulletproof_bpcheckout_lite") || ($payment_method_used == "bulletproof_bpcheckout")) {
                    $the_msg = 'Status updated to cancelled by the BulletProof Plugin';
                    $translated_msg = did_action('init') ? __($the_msg, 'bulletproof-checkout-lite') : $the_msg;
                    $order->update_status("cancelled", $translated_msg);
                    $order->save();
                }
            }
        }
    }

    /**
     * Get header Authorization
     * */
    private function getAuthorizationHeader()
    {
        $headers = null;
        if (isset($_SERVER['Authorization'])) {
            $headers = trim($_SERVER["Authorization"]);
        } else if (isset($_SERVER['HTTP_AUTHORIZATION'])) { //Nginx or fast CGI
            $headers = trim($_SERVER["HTTP_AUTHORIZATION"]);
        } elseif (function_exists('apache_request_headers')) {
            $requestHeaders = apache_request_headers();
            // Server-side fix for bug in old Android versions (a nice side-effect of this fix means we don't care about capitalization for Authorization)
            $requestHeaders = array_combine(array_map('ucwords', array_keys($requestHeaders)), array_values($requestHeaders));
            //print_r($requestHeaders);
            if (isset($requestHeaders['Authorization'])) {
                $headers = trim($requestHeaders['Authorization']);
            }
        }
        return $headers;
    }

    /**
     * Get Header signature: Signature is exempted on development environments
     */
    private function getSignature()
    {
        try {
            $headers = getallheaders();
            if ((isset($headers['Webhook-Signature'])) && ($_SERVER['HTTP_HOST'] != "localhost")) {
                $sigHeader = $headers['Webhook-Signature'];
                if ($_SERVER['HTTP_HOST'] != "localhost") {
                    if (!is_null($sigHeader) && strlen($sigHeader) < 1) {
                        throw new Exception("invalid webhook - signature header missing.");
                    }
                }
                return $sigHeader;
            } else {
                throw new Exception("invalid webhook - signature header missing");
            }
        } catch (Exception $e) {
            if ($_SERVER['HTTP_HOST'] != "localhost") {
                echo json_encode($e->getMessage());
                die();
            } else {
                return "";
            }
        }
    }

    /**
     * Get access token from header
     * */
    private function getBearerToken()
    {
        $headers = self::getAuthorizationHeader();
        // HEADER: Get the access token from the header
        if (!empty($headers)) {
            if (preg_match('/Bearer\s(\S+)/', $headers, $matches)) {
                return $matches[1];
            }
        }
        return null;
    }

    /**
     * End of Utility functions
     */

    /**
     * Valid response IP, this webhook only allow requests coming from the enxt IP address
     */

    public function start_webhook_reception()
    {
        $bulletproof_valid_ip = array("173.231.198.75", "67.222.38.70");
        // The next lines are only for development environments
        $ngrok_host = "";  // Here you can add a ngrok domain for development (the domain needs to be without http nor https)
        if (($_SERVER['HTTP_HOST'] == $ngrok_host) || ($_SERVER['HTTP_HOST'] == "localhost")) {
            // Adds any sandbox IP
            if (isset($_SERVER['HTTP_X-Forwarded-For'])) {
                $sandboxIP = $_SERVER['HTTP_X-Forwarded-For'];
                if ($sandboxIP != "") {
                    array_push($bulletproof_valid_ip, $sandboxIP);
                }
            } else {
                $curr_ip = self::get_ip();
                array_push($bulletproof_valid_ip, $curr_ip);
            }
        }
        $the_ip = self::get_ip();
        if (in_array($the_ip, $bulletproof_valid_ip)) {
            // validate the webhook received
            $gateway_settings = get_option('woocommerce_bulletproof_bpcheckout_lite_settings');
            if (isset($gateway_settings['app_secret'])) {
                $app_secret = $gateway_settings['app_secret'];
            } else {
                $app_secret = "";
            }
            if (isset($gateway_settings['webhook_api_key'])) {
                $webhook_api_key = $gateway_settings['webhook_api_key'];
            } else {
                $webhook_api_key = "";
            }
            if ($app_secret != "") {

                // compares the stored apikey with the received bearer token
                $signature = self::getSignature();
                $token_received = self::getBearerToken();
                $webhookBody = file_get_contents("php://input");
                // Check if is a valid signature
                $signature_calculated = hash_hmac("sha256",  $webhookBody, $app_secret);
                if ($signature == $signature_calculated) {
                    if ((is_string($token_received)) && ($token_received != "")) {
                        // TODO: replace the next comparison with the HMAC decryption of the signature
                        if ($token_received == $webhook_api_key) {

                            // get the body of the request
                            $data_json = json_decode($webhookBody, true);
                            if ((isset($data_json['event_type'])) && (isset($data_json['event_body']))) {
                                $event_type = $data_json['event_type'];
                                $data = $data_json['event_body'];
                                // Status after order completed
                                if (isset($gateway_settings['status_after_order_completed'])) {
                                    $next_status = $gateway_settings['status_after_order_completed'];
                                } else {
                                    $next_status = "";
                                }
                                switch ($event_type) {
                                    case "transaction.sale.success":
                                        self::update_order($data, $next_status);
                                        break;
                                    case "transaction.sale.failure":
                                        self::register_sale_failure($data, $next_status);
                                        break;
                                    case "transaction.void.success":
                                        self::cancel_order($data, $next_status);
                                        break;
                                    case "transaction.refund.success":
                                        self::refund_order($data, $next_status);
                                        break;
                                    case "transaction.refund.failure":
                                        self::register_refund_failure($data, $next_status);
                                        break;
                                    default:
                                        $msg = "Invalid event type received";
                                        header("HTTP/1.0 404 " . $msg, true, 404);
                                        echo json_encode($msg);
                                        die();
                                        break;
                                }
                                echo json_encode("OK");
                                die();
                            } else {
                                $msg = "Invalid data received";
                            }
                        } else {
                            $msg = "Invalid token received";
                        }
                    } else {
                        $msg = "Invalid token requested";
                    }
                } else {
                    if ((isset($_GET['testwebhook'])) && ($_GET['testwebhook'] == "Y")) {
                        // This route is used for validate the availability of the webhook endpoint
                        $msg = "OK";
                    } else {
                        $msg = "Invalid signature";
                    }
                }
            } else {
                $msg = "Missed App Secret on WooCommerce->Payments->BulletProof Checkout Lite Settings";
            }
            header("HTTP/1.0 404 " . $msg, true, 404);
            echo json_encode($msg);
        } else {
            header("HTTP/1.0 404 Not Authorized", true, 404);
            echo json_encode("Not Authorized. IP Address:" . $the_ip);
        }
    }
}
