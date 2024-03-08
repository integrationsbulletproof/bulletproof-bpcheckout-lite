<?php
if (!defined('ABSPATH')) {
    exit;
}

// Include WooCommerce Payment Gateway class.
class WC_Bulletproof_Payment_Gateway_Lite extends WC_Payment_Gateway
{

    /**
     * Constructor function to initialize the payment gateway settings.
     */
    public function __construct()
    {

        // Define basic information about the payment gateway.
        $this->id = 'bulletproof_bpcheckout_lite';
        $this->method_title = 'Bulletproof Payment Gateway Lite';
        $this->title = 'Bulletproof Gateway Lite';
        $this->has_fields = false;
        $this->method_description = 'BulletProof payment gateway lite for WooCommerce';
        /**
         * Filter the icon for the Bulletproof Payment Gateway Lite.
         *
         * @since 1.0.0
         * @param string $icon The icon HTML code.
        */
        $this->icon = apply_filters('bulletproof_payment_gateway_lite_icon', '');

        // Initialize form fields and settings.
        $this->init_form_fields();
        $this->init_settings();
        $this->title = $this->get_option('title');
        $this->description = $this->get_option('description');
        $this->supports = array(
            'products',
        );
        $this->enabled = $this->get_option('enabled');
        $this->testmode = 'yes' === $this->get_option('testmode');
        $this->api_key = $this->get_option('api_key');
        $this->enable_vault = $this->get_option('save_payment_info');

        // Process admin options when saving payment gateway settings
        add_action('woocommerce_update_options_payment_gateways_' . $this->id, array( $this, 'process_admin_options' ));

        // Enqueue payment scripts on frontend
        add_action('wp_enqueue_scripts', array( $this, 'bulletproof_payment_scripts' ));

        // Handle BulletProof payment endpoint
        add_action('init', array( $this, 'bulletproof_payment_endpoint' ));

        // Handle BulletProof payment response
        add_action('wp', array( $this, 'bulletproof_payment_response_handler' ));

        // Validate credentials when saving payment gateway settings
        add_action( 'woocommerce_update_options_payment_gateways_' . $this->id, array($this,'bulletproof_validate_payment_gateway_credentials' ));
    }

    /**
    * Define form fields for WooCommerce settings.
    */
    public function init_form_fields()
    {
        // Definition of form fields for the WooCommerce settings.
        $this->form_fields = array(
            'enabled' => array(
                'title'       => __('Enable/Disable', 'bulletproof-payment-gateway-lite'),
                'label'       => 'Enable BulletProof Gateway Lite',
                'type'        => 'checkbox',
                'description' => '',
                'default'     => 'no',
            ),
            'title' => array(
                'title'       => __('Title', 'bulletproof-payment-gateway-lite'),
                'type'        => 'text',
                'description' => 'This controls the title which the user sees during checkout.',
                'default'     => 'Credit Card',
                'desc_tip'    => true,
            ),
            'description' => array(
                'title'       => __('Description', 'bulletproof-payment-gateway-lite'),
                'type'        => 'textarea',
                'description' => 'This controls the description which the user sees during checkout.',
                'default'     => 'Pay with your credit card via our super-cool payment gateway.',
            ),
            'testmode' => array(
                'title'       => __('Test mode', 'bulletproof-payment-gateway-lite'),
                'label'       => 'Enable Test Mode',
                'type'        => 'checkbox',
                'description' => 'Place the payment gateway in test mode using test API keys.',
                'default'     => 'yes',
                'desc_tip'    => true,
            ),
            'username'    => array(
                'title'       => __('Username', 'bulletproof-payment-gateway-lite'),
                'type'        => 'text',
                'description' => __('This is the API username generated within the BulletProof Checkout.', 'bulletproof-payment-getway-lite'),
                'default'     => '',
            ),
            'password'    => array(
                'title'       => __('Password', 'bulletproof-payment-gateway-lite'),
                'type'        => 'text',
                'description' => __('This is the API user password generated within the BulletProof checkout.', 'bulletproof-payment-gateway-lite'),
                'default'     => '',
            ),
            'salemethod'  => array(
                'title'       => __('Sale Method', 'bulletproof-payment-gateway-lite'),
                'type'        => 'select',
                'description' => __('Select which sale method to use. Authorize Only will authorize the customers card for the purchase amount only.  Authorize &amp; Capture will authorize the customer\'s card and collect funds.', 'bulletproof-payment-gateway'),
                'options'     => array(
                    'sale' => 'Authorize &amp; Capture',
                    'auth' => 'Authorize Only',
                ),
                'default'     => 'Authorize &amp; Capture',
            ),
            'api_key' => array(
                'title'       => __('API Keys', 'bulletproof-payment-gateway-lite'),
                'type'        => 'text',
            ),
            'save_payment_info' => array(
                'title'       => __('Enable Customer Vault', 'bulletproof-payment-gateway-lite'),
                'type'        => 'select',
                'label'       => __('Enable Customer Vault', 'bulletproof-payment-gateway-lite'),
                'description' => __('Select to enable or disable the customer vault.', 'bulletproof-payment-gateway-lite'),
                'options'     => array(
                    'yes' => 'Yes',
                    'no' => 'No',
                ),
                'default'     => 'No',
            ),

        );
        // Get API credentials.
        $username = $this->get_option('username');
        $password = $this->get_option('password');
        $security_key = $this->get_option('api_key');
        $processors = $this->get_bulletproof_processors($username, $password, $security_key);
        // Initialize an array to store processor IDs.
        $processors_list = array();

        // Check if processors are available and build the list.
        if (!empty($processors) && !isset($processors->error)) {
            
            foreach ($processors as $key => $processor) {
                $processors_list[$processor->{'processor-id'}->{'0'}] = $processor->{'processor-id'}->{'0'};
            }
        }
        $empty_option = array( '' => __('Select a processor', 'bulletproof-payment-gateway-lite') );
        if (!empty($processors_list)) {
            $processor_field = array(
                'title' => __('Processor', 'bulletproof-payment-gateway-lite'),
                'type' => 'select',
                'options' => $empty_option + $processors_list,
                'description' => __('Select an option from the dropdown.', 'woocommerce'),
            );
            $this->form_fields['processor'] = $processor_field;
        }
    }

    /**
     * Function to display error notices.
     * This function displays any error notices generated during the settings save process.
    */


    public function display_bulletproof_payment_gateway_credentials_error(){
        // Display error notices
       if ($message = get_transient('custom_gateway_api_error')) {
        ?>
            <div class="notice notice-error is-dismissible">
                <p><?php echo $message; ?></p>
            </div>
            <?php
            // Delete the transient to avoid displaying the message again
            delete_transient('custom_gateway_api_error');
        }
    }


    /**
     * Function to validate API credentials when saving settings.
     * This function retrieves the API username and password from the settings form,
     * performs validation, and adds an error notice if the credentials are not valid.
    */

    public function bulletproof_validate_payment_gateway_credentials(){
        // Check requires params in post
        
        if(!empty($this->get_post(esc_attr('woocommerce_'.$this->id) . '_username')) && !empty($this->get_post(esc_attr('woocommerce_'.$this->id) . '_password')) && !empty($this->get_post(esc_attr('woocommerce_'.$this->id) . '_api_key'))){
            
            // Perform credential validation
            $api_response = $this->get_bulletproof_processors($this->get_post(esc_attr('woocommerce_'.$this->id) . '_username'), $this->get_post(esc_attr('woocommerce_'.$this->id) . '_password'), $this->get_post(esc_attr('woocommerce_'.$this->id) . '_api_key'));
            // If credentials are not valid, add an error notice
            if(!empty($api_response) && isset($api_response->error)){
                set_transient('custom_gateway_api_error', __('Invalid API credentials. Please check your API Key username and password.', 'bulletproof-payment-gateway-lite'));
                
                add_action('admin_notices', array($this, 'display_bulletproof_payment_gateway_credentials_error'));
                return;
            }
        }
    }

    
    /**
    * Handler for processing payment responses.
    */
    public function bulletproof_payment_response_handler()
    {
        // Check if the order status has already been updated.
        $status_updated = false;
        $order_id = isset($_GET['orderid']) ? intval($_GET['orderid']) : 0;
        $transaction_id = isset($_GET['transactionid']) ? intval($_GET['transactionid']) : 0;
        // Code for processing payment responses based on query parameters.
        if (!empty($_GET['3ds_approved']) && !empty($order_id) && !empty($transaction_id)) {
            $order = new WC_Order($order_id);
            $this->bulletproof_update_order_meta($order_id, $transaction_id);

            if ($this->get_option('salemethod') == 'auth') {
                $username = $this->get_option('username');
                $password = $this->get_option('password');
                $security_key = $this->get_option('api_key');

                $request_args = array(
                    'headers' => array(
                        'accept' => 'application/json',
                    ),
                    'body' => '',
                );

                $api_url = BULLETPROOF_CHECKOUT_API_BASE_URL . 'capture_payment.php?user=' . urlencode($username) .
                '&pass=' . urlencode($password) .
                '&security_key=' . urlencode($security_key) .
                '&transactionid=' . urlencode($transaction_id);

                $response = $this->bulletproof_capture_payment_api($api_url, $request_args, 'Capture Payment');
                parse_str($response['data'], $responseArray);
                if (isset($responseArray['response']) && 1 == $responseArray['response']) {
                    $order->payment_complete();

                 // Update status only if not already updated.
                    if (!$status_updated) {
                        $order->update_status('completed');
                        $status_updated = true; // Set the flag to true after updating status.
                    }

                    $order->save();
                }
            } else {
                $order->payment_complete();
                // Update status only if not already updated.
                if (!$status_updated) {
                    $order->update_status('completed');
                    $status_updated = true; // Set the flag to true after updating status.
                }

                $order->save();
            }
            WC()->cart->empty_cart();
            wp_safe_redirect($this->get_return_url($order));
        } elseif (!empty($_GET['denial']) || !empty($_GET['token'])) {
            wc_add_notice(__('Transaction Failed', 'bulletproof-payment-gateway-lite'), $notice_type = 'error');
            wp_safe_redirect(wc_get_checkout_url());
        }
    }

    /**
    * Register custom endpoint for BulletProof payment processing.
    */
    public function bulletproof_payment_endpoint()
    {
        add_rewrite_endpoint('bulletproof-payment-processing', EP_ROOT | EP_PAGES);
    }

    /**
    * Function to make API requests for capturing payment.
    *
    * @param string $api_url
    * @param array $request_args
    * @param string $endpoint
    * @return array|mixed|object
    */
    public function bulletproof_capture_payment_api($api_url, $request_args, $endpoint)
    {
        // API request logic for capturing payment.
        $response = wp_remote_post($api_url, $request_args);

        if (is_wp_error($response)) {
            error_log($endpoint . 'API request failed: ' . $response->get_error_message());
        } else {
            $body = wp_remote_retrieve_body($response);
            $decoded_response = json_decode($body, true);
            return $decoded_response;
        }
    }

    /**
    * Enqueue payment-related styles.
    */
    public function bulletproof_payment_scripts()
    {
        wp_enqueue_style('payment-styles', plugins_url('../assets/css/style.css', __FILE__), array(), '1.0');
    }

    /**
    * Utility function to retrieve POST data.
    *
    * @param string $name
    * @return mixed|null
    */
    protected function get_post($name)
    {
        // Retrieve POST data.
        if (isset($_POST[ $name ])) {
            return sanitize_text_field($_POST[ $name ]);
        }
        return null;
    }

    /**
    * Function to display additional payment fields during checkout.
    */
    public function payment_fields()
    {
        // Display additional payment fields during checkout.
        if ($this->description) {
            if ($this->testmode) {
                $this->description .= ' TEST MODE ENABLED. In test mode, you can use the test card numbers.';
                $this->description  = trim($this->description);
            }

            echo wp_kses_post($this->description);
        }

        echo '<fieldset id="wc-' . esc_attr($this->id) . '-cc-form" class="wc-credit-card-form wc-payment-form" style="background:transparent;">';

        /**
         * Fires at the start of the credit card form for a specific payment gateway.
         *
         * This hook allows you to add content or modify the credit card form
         * for a specific payment gateway.
         *
         * @since 1.0.0
         *
         * @param string $gateway_id ID of the payment gateway.
        */
        do_action('woocommerce_credit_card_form_start', $this->id);

        ?>
        <div class="form-row form-row-wide">
            <label for="<?php echo esc_attr($this->id); ?>-card-number"><?php echo esc_html__('Card Number', 'bulletproof-payment-gateway-lite'); ?> <span class="required">*</span></label>
            <input type="text" class="input-text" pattern="[0-9]*" id="<?php echo esc_attr($this->id); ?>-card-number" name="<?php echo esc_attr($this->id); ?>_card_number" />
        </div>
        <div class="form-row form-row-wide">
            <label for="<?php echo esc_attr($this->id); ?>-card-expiry"><?php esc_html__('Expiration Date', 'bulletproof-payment-gateway-lite'); ?> <span class="required">*</span></label>
            <?php
            $current_month = gmdate('m');
            $months = array();
            for ($i = $current_month; $i <= 12; $i++) {
                $month_value = str_pad($i, 2, '0', STR_PAD_LEFT);
                $months[$month_value] = $month_value;
            }
            ?>
            <div class="select-row">
                <div class="select-col-half">
                    <select id="<?php echo esc_attr($this->id); ?>-card-expiry-month" name="<?php echo esc_attr($this->id); ?>_card_expiry_month" class="bp-card-expiry">
                        <option value=""></option>
                        <?php

                        foreach ($months as $month_value => $month_label) {
                            echo "<option value='" . esc_attr($month_value) . "'>" . esc_html($month_label) . '</option>';
                        }
                        ?>
                    </select>
                </div>
                <div class="select-2-col-half">
                    <select id="<?php echo esc_attr($this->id); ?>-card-expiry-year" name="<?php echo esc_attr($this->id); ?>_card_expiry_year" class="bp-card-expiry">
                        <option value=""></option>      
                        <?php
                        $current_year = gmdate('Y');
                        $end_year = $current_year + 10;

                        for ($year = $current_year; $year <= $end_year; $year++) {
                            echo "<option value='" . esc_attr(substr($year, -2)) . "'>" . esc_html($year) . '</option>';
                        }
                        ?>
                    </select>
                </div>
            </div>
        </div>
        <div class="form-row form-row-wide">
            <label for="<?php echo esc_attr($this->id); ?>-card-cvc"><?php echo esc_html(__('CVV', 'bulletproof-payment-gateway-lite')); ?> <span class="required">*</span></label>
            <input type="text" class="input-text" pattern="^\d{3,4}$" minlength="3" maxlength="4" id="<?php echo esc_attr($this->id); ?>-card-cvc" name="<?php echo esc_attr($this->id); ?>_card_cvc" />
        </div>
        <!-- Add the hidden nonce field -->
        <?php
        
        echo '<input type="hidden" name="bulletproof_payment_nonce" value="' . esc_attr(wp_create_nonce('bulletproof_payment_nonce')) . '" />';
        /**
         * Action hook to indicate the end of the credit card form.
         *
         * @hook woocommerce_credit_card_form_end
         * @since 1.0.0
         *
         * @param string $gateway_id The ID of the payment gateway.
        */
        do_action('woocommerce_credit_card_form_end', $this->id);
        if ('yes' == $this->enable_vault) {
            ?>
            <div style="clear: both;"></div>
            <p>
                <label for="save_payment_info"><?php echo esc_html__('Save payment information to my account', 'bulletproof-payment-gateway-lite'); ?></label>
                <input type="checkbox" class="input-checkbox" id="save_payment_info" name="save_payment_info" />

            </p>
            <?php
        }
        echo '<div class="clear"></div></fieldset>';
    }

    /**
    * Processes a refund for an order.
    *
    * @param int    $order_id The ID of the WooCommerce order.
    * @param float|null $amount The refund amount.
    * @param string $reason The reason for the refund.
    * @return bool|WP_Error True on success, WP_Error on failure.
    */
    public function process_refund($order_id, $amount = null, $reason = '')
    {
        // Get the WooCommerce order.
        $order = wc_get_order($order_id);
        // Check if the order is valid.
        if (!$order || !is_object($order)) {
              return new WP_Error('invalid_order', 'Invalid order.');
        }
        // Get API credentials and transaction ID.
        $username = $this->get_option('username');
        $password = $this->get_option('password');
        $security_key = $this->get_option('api_key');
        $transaction_id = get_post_meta($order_id, '_payment_gateway_tx_received', true);

        // Prepare request arguments.
        $request_args = array(
        'headers' => array(
            'accept' => 'application/json',
        ),
        'body' => '',
        );

        // Build the API URL with parameters.
        $api_url = BULLETPROOF_CHECKOUT_API_BASE_URL . 'refund.php?user=' . urlencode($username) .
        '&pass=' . urlencode($password) .
        '&security_key=' . urlencode($security_key) .
        '&transactionid=' . urlencode($transaction_id);

        // Make the refund API call.
        $response = $this->bulletproof_capture_payment_api($api_url, $request_args, 'Refund');
        error_log(print_r($response, true));

        // $order->update_status('refunded');
        // $order->add_order_note('Refunded via Custom Gateway.');

        return true;
    }

    /**
    * Gets a list of Bulletproof processors.
    *
    * @return array The list of processors.
    */
    public function get_bulletproof_processors($username, $password, $security_key)
    {
        // Set the API URL for retrieving processors.
        $api_url = BULLETPROOF_CHECKOUT_API_BASE_URL . 'processors.php';

        // Check if required API credentials are available.
        if (empty($username) && empty($password) && empty($security_key)) {
            return;
        }

        // Prepare request arguments.
        $request_args = array(
        'headers' => array(
            'Accept' => 'application/json',
        ),
        'body' => array(
            'user' => $username,
            'pass' => $password,
            'security_key' => $security_key,
        ),
        );

        // Make the processors API call.
        $response = wp_remote_post($api_url, $request_args);

        // Check if the API request was successful.
        if (!is_wp_error($response)) {
            $body = wp_remote_retrieve_body($response);
            $processors = json_decode($body);
        } else {
             // Log an error message if the API request fails.
            $error_message = is_wp_error($response) ? $response->get_error_message() : 'Unknown error occurred';
            error_log('Processors API request failed: ' . $response->get_error_message());
        }

        

        // Return the list of processors.
        return $processors;
    }

    /**
    * Validates payment fields before processing a payment.
    *
    * @return bool True if validation passes, false otherwise.
    */
    public function validate_fields()
    {
        // Get card details from post data.
        $card_number           = $this->get_post(esc_attr($this->id) . '_card_number');
        $card_cvv              = $this->get_post(esc_attr($this->id) . '_card_cvc');
        $card_expiration_month = $this->get_post(esc_attr($this->id) . '_card_expiry_month');
        $card_expiration_year  = $this->get_post(esc_attr($this->id) . '_card_expiry_year');

        // Validate card number.
        if (empty($card_number) || ! ctype_digit($card_number) || !preg_match('/^\d{16}$/', $card_number)) {
            wc_add_notice(__('Card number is invalid.', 'bulletproof-payment-gateway-lite'), $notice_type = 'error');
            return false;
        }

        // Validate card security code.
        if (! ctype_digit($card_cvv)) {
            wc_add_notice(__('Card security code is invalid (only digits are allowed).', 'bulletproof-payment-gateway-lite'), $notice_type = 'error');
            return false;
        }

        // Validate card security code length.
        if (!preg_match('/^\d{3,4}$/', $card_cvv)) {
            wc_add_notice(__('Card security code is invalid (wrong length).', 'bulletproof-payment-gateway-lite'), $notice_type = 'error');
            return false;
        }

        // Get the current year.
        $current_year = gmdate('y');

        // Validate card expiration date.
        if (! ctype_digit($card_expiration_month) || ! ctype_digit($card_expiration_year) ||
        $card_expiration_month > 12 || $card_expiration_month < 1 || $card_expiration_year < $current_year || $card_expiration_year > $current_year + 10) {
            wc_add_notice(__('Card expiration date is invalid', 'bulletproof-payment-gateway-lite'), $notice_type = 'error');
            return false;
        }

        // Remove spaces and hyphens from card number.
        $card_number = str_replace(array( ' ', '-' ), '', $card_number);

        // Validation passed.
        return true;
    }

    /**
    * Processes a payment for an order.
    *
    * @param int $order_id The ID of the WooCommerce order.
    * @return array An array with 'result' and 'redirect' keys.
    */
    public function process_payment($order_id)
    {
        if (! isset($_POST['bulletproof_payment_nonce']) || ! wp_verify_nonce(sanitize_text_field($_POST['bulletproof_payment_nonce']), 'bulletproof_payment_nonce')) {
            // Nonce verification failed, handle error
            return;
        }
        // Create a new instance of the WooCommerce order.
        $order = new WC_Order($order_id);

        // Set the API URL for sale authorization.
        $sale_auth_api_url = BULLETPROOF_CHECKOUT_API_BASE_URL;

        // Get sale authorization parameters.
        $sale_auth_params = $this->bulletproof_checkout_api_params($order, $order_id);

        // Make the sale authorization API call.
        $sale_auth_response = $this->bulletproof_checkout_api($sale_auth_api_url, $sale_auth_params, 'POST');

        // Check if the sale authorization was successful.
        if (isset($sale_auth_response->token) && !empty($sale_auth_response->token)) {
            // Build the validation API URL.
            $validate_api_url = BULLETPROOF_CHECKOUT_API_BASE_URL . 'validate.php?token=' . $sale_auth_response->token;

            // Return success with redirection URL.
            return array(
            'result' => 'success',
            'redirect' => $validate_api_url,
            );
        } elseif (isset($sale_auth_response->error) && !empty($sale_auth_response->error)) {
            // Check if there is an error in the sale authorization response.
            // Display an error notice and return an empty array.
            wc_add_notice(__($sale_auth_response->error, 'bulletproof-payment-gateway-lite'), $notice_type = 'error');
            return array();
        }
    }

    /**
    * Updates order meta data after a successful transaction.
    *
    * @param int    $order_id The ID of the WooCommerce order.
    * @param string $transaction_id The transaction ID.
    */
    public function bulletproof_update_order_meta($order_id, $transaction_id)
    {
        // Get the current date and time.
        $order_date = gmdate('Y-m-d H:i:s');

        // Determine the gateway environment.
        $gateway_environment = $this->testmode ? 'sandbox' : 'live';

        // Update various order meta data.
        update_post_meta($order_id, '_bulletproof_gateway_action_type_sale', $order_date);
        update_post_meta($order_id, '_payment_gateway_tx_received_prewebhook', $transaction_id);
        update_post_meta($order_id, '_subscriptionId_prewebhook', '');
        update_post_meta($order_id, '_payment_gateway_tx_received', $transaction_id);
        update_post_meta($order_id, '_payment_gateway_subscriptionId_received', '');
        update_post_meta($order_id, 'bulletproof_bpcheckout_gateway', BULLETPROOF_BPCHECKOUT_GATEWAY);
        update_post_meta($order_id, '_bulletproof_bpcheckout_gateway', BULLETPROOF_BPCHECKOUT_GATEWAY);
        update_post_meta($order_id, 'bulletproof_bpcheckout_gateway_environment', $gateway_environment);
        update_post_meta($order_id, '_bulletproof_bpcheckout_gateway_environment', $gateway_environment);
        $unix_format_date = strtotime(gmdate('Y-m-d H:i:s'));
        update_post_meta($order_id, '_date_completed', $unix_format_date);
        update_post_meta($order_id, '_date_paid', $unix_format_date);
        update_post_meta($order_id, '_paid_date', gmdate('Y-m-d H:i:s'));
        update_post_meta($order_id, '_completed_date', gmdate('Y-m-d H:i:s'));
        $random_naunce_key = $this->generateRandomString();
        update_post_meta($order_id, '_random_naunce_key', $random_naunce_key);
    }

    /**
    * Generates sale authorization parameters for the BulletProof API.
    *
    * @param WC_Order $order The WooCommerce order object.
    * @param int      $order_id The ID of the WooCommerce order.
    * @return array An array of parameters for sale authorization.
    */
    public function bulletproof_checkout_api_params($order, $order_id)
    {
        // Initialize an array to store item product codes.
        $item_product_code = array();

        // Loop through order items to get product IDs.
        foreach ($order->get_items() as $item_id => $item_data) {
            $item_product_code[] = 'item_product_code_' . $item_data->get_product_id();
        }

        // Combine item product codes into a comma-separated string.
        $item_product_code = implode(',', $item_product_code);

        // Get user details.
        $user = new WP_User($order->get_user_id());

        // Retrieve API credentials.
        $username = $this->get_option('username');
        $password = $this->get_option('password');
        $security_key = $this->get_option('api_key');

        // Check if required API credentials are available.
        if (empty($security_key) || empty($username) || empty($password)) {
            wc_add_notice('API key, username, and password are required.', 'error');
            return;
        }

        // Determine if payment info should be saved to the vault.
        $vault = $this->get_post('save_payment_info') ? 'Y' : 'N';

        // Get sale method, processor, and card details.
        $sale_method = $this->get_option('salemethod');
        $processor = !empty($this->get_option('processor')) ? $this->get_option('processor') : '';
        $ccnum = $this->get_post(esc_attr($this->id) . '_card_number');
        $card_cvv = $this->get_post(esc_attr($this->id) . '_card_cvc');
        $card_expiration_month = $this->get_post(esc_attr($this->id) . '_card_expiry_month');
        $card_expiration_year  = $this->get_post(esc_attr($this->id) . '_card_expiry_year');
        $ccexp = $card_expiration_month . '' . $card_expiration_year;

        // Build an array of sale authorization parameters.
        $sale_auth_params = array(
            'sale_auth_only' => $sale_method,
            'gateway' => BULLETPROOF_CHECKOUT_GATEWAY,
            'security_key' => $security_key,
            'user' => $username,
            'pass' => $password,
            'ccnumber' => $ccnum,
            'ccexp' => $ccexp,
            'cvv' => $card_cvv,
            'amount' => $order->get_total(),
            'orderid' => $order_id,
            'format' => BULLETPROOF_CHECKOUT_FORMAT,
            'approval_url' => home_url('/bulletproof-payment-processing'),
            'denial_url' => home_url('/bulletproof-payment-processing'),
            'vault' => $vault,
            'customer-id' => $order->get_user_id(),
            'processor-id' => $processor,
            'billing_address_first_name' => $order->get_billing_first_name(),
            'billing_address_last_name' => $order->get_billing_last_name(),
            'billing_address_address1' => $order->get_billing_address_1(),
            'billing_address_address2' => $order->get_billing_address_2(),
            'billing_address_city' => $order->get_billing_city(),
            'billing_address_state' => $order->get_billing_state(),
            'billing_address_zip' => $order->get_billing_postcode(),
            'billing_address_country' => $order->get_billing_country(),
            'billing_address_phone' => $order->get_billing_phone(),
            'billing_address_email' => $order->get_billing_email(),
            'item_product_code_#' => $item_product_code,
        );
        return $sale_auth_params;
    }

    /**
    * Calls the BulletProof API.
    *
    * @param string $api_url The API URL.
    * @param array  $params The request parameters.
    * @param string $method The request method (GET or POST).
    * @return mixed|void The API response.
    */
    public function bulletproof_checkout_api($api_url, $params, $method = 'POST')
    {
        // Make the API call using wp_remote_post.
        $response = wp_remote_post(
            $api_url,
            array(
                'body'    => $params,
                'headers' => array(
                    'accept' => 'application/json',
                )
            )
        );

        // Check for WP_Error.
        if (is_wp_error($response)) {
            // Log an error message if the API request fails.
            error_log('API request failed: ' . $response->get_error_message());
        } else {
            // Decode the JSON response.
            $body = json_decode(wp_remote_retrieve_body($response));
            // Return the decoded response.
            return $body;
        }
    }

    /**
    * Generates a random string of the specified length.
    *
    * @param int $length The length of the random string.
    * @return string The generated random string.
    */
    public function generateRandomString($length = 10)
    {
        // Define characters for the random string.
        $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';

        // Get the length of the character set.
        $charactersLength = strlen($characters);

        // Initialize an empty string.
        $randomString = '';

        // Loop to generate random string.
        for ($i = 0; $i < $length; $i++) {
            $randomString .= $characters[rand(0, $charactersLength - 1)];
        }

        // Return the generated random string.
        return $randomString;
    }
}


// Instantiate the Bulletproof Payment Gateway Lite class.
new WC_Bulletproof_Payment_Gateway_Lite();
