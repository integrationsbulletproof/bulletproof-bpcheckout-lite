<?php
if (!defined('ABSPATH')) {
    exit;
}

// Include WooCommerce Bulletproof Shop Orders class.
class WC_Bulletproof_Shop_Orders {

    /**
     * Constructor function to initialize the shop orders settings.
     */
    public function __construct() {
        add_action( 'admin_enqueue_scripts', array( $this, 'bulletproof_admin_enqueue_custom_scripts' ));
        add_action( 'wp_enqueue_scripts', array( $this, 'bulletproof_frontend_enqueue_scripts' ));

        add_filter( 'manage_woocommerce_page_wc-orders_columns', array( $this, 'bulletproof_checkout_capture_column_header' ), 10, 1);
        add_filter( 'manage_edit-shop_order_columns', array( $this, 'bulletproof_checkout_capture_column_header' ), 10, 1);

        add_action( 'manage_shop_order_posts_custom_column', array( $this, 'bulletproof_checkout_capture_column_content_old' ), 10, 2);

        add_action( 'manage_woocommerce_page_wc-orders_custom_column', array( $this, 'bulletproof_checkout_capture_column_content' ), 10, 2);

        add_action( 'wp_ajax_capture_order_payment', array( $this, 'bulletproof_capture_order_payment_callback' ));
    }

    public function bulletproof_frontend_enqueue_scripts() {
        wp_enqueue_script( 'frontend-script', plugins_url('../assets/js/frontend.js', __FILE__), array( 'jquery' ), '1.0', true );
    }

    public function bulletproof_admin_enqueue_custom_scripts() {
        wp_enqueue_script( 'admin-custom-script', plugins_url('../assets/js/admin-custom-script.js', __FILE__), array( 'jquery' ), '1.0', true );
        wp_localize_script( 'admin-custom-script', 'custom_script_vars', array(
            'ajax_url' => admin_url( 'admin-ajax.php' ),
            'nonce' => wp_create_nonce( 'order-payment-capture' ),
        ) );
    }

    //Add column header
    public function bulletproof_checkout_capture_column_header( $columns ) {

        $columns['payment_capture_column'] = __( 'Features', 'bulletproof-payment-gateway-lite' );
        return $columns;
    }

    public function bulletproof_checkout_capture_column_content( $column, $order ) {

        if ( 'payment_capture_column' === $column ) {

            $transaction_id = get_post_meta($order->get_id(), '_payment_gateway_tx_received', true);
            if ( $order && $order->get_status() === 'on-hold' && $transaction_id ) {

                echo '<button class="button payment_capture_btn" data-order-id="' . esc_attr( $order->get_id() ) . '">Capture</button>';
            }

        }
    }

    public function bulletproof_checkout_capture_column_content_old( $column, $order_id ) {

        if ( 'payment_capture_column' === $column ) {
            $order = wc_get_order($order_id);
            $transaction_id = get_post_meta($order_id, '_payment_gateway_tx_received', true);
            if ( $order && $order->get_status() === 'on-hold' && $transaction_id ) {

                echo '<button class="button payment_capture_btn" data-order-id="' . esc_attr( $order_id ) . '">Capture</button>';
            }

        }
    }

    public function bulletproof_capture_order_payment_callback() {
        // Verify the nonce
        check_ajax_referer( 'order-payment-capture', 'nonce' );
        // Get the order ID from the AJAX request
        $order_id = isset( $_POST['order_id'] ) ? intval( $_POST['order_id'] ) : 0;
        
        if ( $order_id ) {
            $order = new WC_Order($order_id);
            $gateway_settings = get_option('woocommerce_bulletproof_bpcheckout_lite_settings');
            $username = $gateway_settings['username'];
            $password = $gateway_settings['password'];
            $security_key = $gateway_settings['api_key'];
            if (empty($username) || empty($password) || empty($security_key)) {
                wp_send_json_error( 'Username, password, or API key is empty.' );
            }
            $request_args = array(
                'headers' => array(
                    'accept' => 'application/json',
                ),
                'body' => '',
            );
            $transaction_id = get_post_meta($order_id, '_payment_gateway_tx_received', true);
            $api_url = BULLETPROOF_CHECKOUT_API_BASE_URL . 'capture_payment.php?user=' . urlencode($username) .
            '&pass=' . urlencode($password) .
            '&security_key=' . urlencode($security_key) .
            '&transactionid=' . urlencode($transaction_id);
            $data = array();
            $response = $this->bulletproof_capture_payment_api($api_url, $request_args);
            
            if(isset($response['data']) && !empty($response['data'])){
                parse_str($response['data'], $responseArray);
                if (isset($responseArray['response']) && 1 == $responseArray['response']) {
                    $order->payment_complete();
                    $order->update_status('completed');       
                    $order->save();
                    wc_maybe_reduce_stock_levels( $order_id );
                    $data['success'] = true;
                } else {
                    $data['success'] = false;
                }
            }else{
                $data['success'] = false;
                $data['message'] = $response['error'];
            }
            
            wp_send_json($data);
            die;
        }
    }

    /**
    * Function to make API requests for capturing payment.
    *
    * @param string $api_url
    * @param array $request_args
    * @return array|mixed|object
    */
    public function bulletproof_capture_payment_api( $api_url, $request_args ) {
        // API request logic for capture payment.
        $response = wp_remote_post($api_url, $request_args);

        if (is_wp_error($response)) {
            error_log('Capture payment API request failed: ' . $response->get_error_message());
            return $response->get_error_message();
        } else {
            $body = wp_remote_retrieve_body($response);
            $decoded_response = json_decode($body, true);
            return $decoded_response;
        }
    }
}
new WC_Bulletproof_Shop_Orders();
