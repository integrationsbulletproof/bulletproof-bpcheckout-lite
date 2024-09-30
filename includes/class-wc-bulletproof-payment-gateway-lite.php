<?php
if (!defined('ABSPATH')) {
	exit;
}

// Include WooCommerce Payment Gateway class.
class Bulletproof_Payment_Gateway_Lite extends WC_Payment_Gateway
{

	// Gateway Variables
	public $testmode = false;
	public $enable_vault = "";
	public $api_key = "";
	public $method_title = "Bulletproof Payment Gateway Lite";
	public $title = "Bulletproof Gateway Lite";
	public $has_fields = false;
	public $id = "bulletproof_bpcheckout_lite";
	public $method_description = 'BulletProof payment gateway lite for WooCommerce';
	public $description = "";
	public $processor = "";
	public $supports = array(
		'products',
		'refunds'
	);
	public $allowed_card_types =array('visa','mastercard','amex','discover'); // other options jcb , diners-club
	/**
	 * Constructor function to initialize the payment gateway settings.
	 */
	public function __construct()
	{

		
		// Define basic information about the payment gateway.
		//$this->id = 'bulletproof_bpcheckout_lite';
		//$this->method_title = 'Bulletproof Payment Gateway Lite';
		//$this->title = 'Bulletproof Gateway Lite';
		//$this->has_fields = false;
		//$this->method_description = 'BulletProof payment gateway lite for WooCommerce';
		/**
		 * Filter the icon for the Bulletproof Payment Gateway Lite.
		 *
		 * @since 1.0.0
		 * @param string $icon The icon HTML code.
		 */
		$this->icon = apply_filters('bulletproof_payment_gateway_lite_icon', '');

		// Initialize form fields and settings.
		$this->bulletproof_init_form_fields();
		$this->init_settings();
		$this->title = $this->get_option('title');
		$this->description = $this->get_option('description');
		$this->enabled = $this->get_option('enabled');
		$this->testmode = 'yes' === $this->get_option('testmode');
		$this->api_key = $this->get_option('api_key');
		$this->enable_vault = $this->get_option('save_payment_info');
		$this->processor = $this->get_option('processor');
		$this->supports = array('products', 'refunds');

		// Process admin options when saving payment gateway settings
		add_action('woocommerce_update_options_payment_gateways_' . $this->id, array($this, 'process_admin_options'));

		// Enqueue payment scripts on frontend
		add_action('wp_enqueue_scripts', array($this, 'bulletproof_payment_scripts'));

		// Handle BulletProof payment endpoint
		add_action('init', array($this, 'bulletproof_payment_endpoint'));

		// Handle BulletProof payment response
		add_action('wp', array($this, 'bulletproof_payment_response_handler'));

		// Validate credentials when saving payment gateway settings
		add_action('woocommerce_update_options_payment_gateways_' . $this->id, array($this, 'bulletproof_validate_payment_gateway_credentials'));
	}



	/**
	 * Define form fields for WooCommerce settings.
	 */

	public function bulletproof_init_form_fields()
	{

		$statuses = wc_get_order_statuses();
		// create an array of statuses to be displayed
		$array_active_statuses = array();
		foreach ($statuses as $status_key => $status_value) {
			if (($status_key != 'wc-cancelled') && ($status_key != 'wc-refunded') && ($status_key != 'wc-failed')) {
				$array_active_statuses[$status_key] = $status_value;
			}
		}
		$array_active_statuses['bp_donotchange'] = 'Do not change the status';
		// Definition of form fields for the WooCommerce settings.
		$this->form_fields = array(
			'enabled' => array(
				'title'       => __('Enable/Disable', 'bulletproof-checkout-lite'),
				'label'       => 'Enable BulletProof Gateway Lite',
				'type'        => 'checkbox',
				'description' => '',
				'default'     => 'no',
			),
			'title' => array(
				'title'       => __('Title', 'bulletproof-checkout-lite'),
				'type'        => 'text',
				'description' => 'This controls the title which the user sees during checkout.',
				'default'     => 'Credit Card',
				'desc_tip'    => true,
			),
			'description' => array(
				'title'       => __('Description', 'bulletproof-checkout-lite'),
				'type'        => 'textarea',
				'description' => 'This controls the description which the user sees during checkout.',
				'default'     => 'Pay with your credit card via the BulletProof Checkout Payment Gateway.',
			),
			'testmode' => array(
				'title'       => __('Test mode', 'bulletproof-checkout-lite'),
				'label'       => 'Enable Test Mode',
				'type'        => 'checkbox',
				'description' => 'Place the payment gateway in test mode using test API keys.',
				'default'     => 'no',
				'desc_tip'    => true,
			),
			'username'    => array(
				'title'       => __('Username', 'bulletproof-checkout-lite'),
				'type'        => 'text',
				'description' => __('This is the API username generated within the BulletProof Checkout.', 'bulletproof-checkout-lite'),
				'default'     => '',
			),
			'password'    => array(
				'title'       => __('Password', 'bulletproof-checkout-lite'),
				'type'        => 'password',
				'description' => __('This is the API user password generated within the BulletProof Checkout.', 'bulletproof-checkout-lite'),
				'default'     => '',
			),
			'api_key' => array(
				'title'       => __('API Key', 'bulletproof-checkout-lite'),
				'type'        => 'text',
				'description' => __('This is the API key generated within the BulletProof Checkout.', 'bulletproof-checkout-lite'),
			),
			'app_secret' => array(
				'title'       => __('App Secret', 'bulletproof-checkout-lite'),
				'type'        => 'password',
				'description' => __('This is the App Secret generated within the BulletProof Checkout.', 'bulletproof-checkout-lite'),
			),
			'salemethod'  => array(
				'title'       => __('Sale Method', 'bulletproof-checkout-lite'),
				'type'        => 'select',
				'description' => __('Select which sale method to use. Authorize Only will authorize the customers card for the purchase amount only.  Authorize &amp; Capture will authorize the customer\'s card and collect funds.', 'bulletproof-checkout-lite'),
				'options'     => array(
					'sale' => 'Authorize &amp; Capture',
					'auth' => 'Authorize Only',
				),
				'default'     => 'sale',
			),
			'status_after_order_completed'  => array(
				'title'       => __('Order Status After Payment', 'bulletproof-checkout-lite'),
				'type'        => 'select',
				'description' => __('Select the WooCommerce Order Status you want to appear after receiving payment. Select \'Do not change the status\' if you do not want to assign a status after an order has been received.', 'bulletproof-checkout-lite'),
				'options'     => $array_active_statuses,
				'default'     => 'wc-completed',
			),
			'save_payment_info' => array(
				'title'       => __('Enable Customer Vault', 'bulletproof-checkout-lite'),
				'type'        => 'select',
				'label'       => __('Enable Customer Vault', 'bulletproof-checkout-lite'),
				'description' => __('Select to enable or disable the customer vault.', 'bulletproof-checkout-lite'),
				'options'     => array(
					'no' => 'No',
					'yes' => 'Yes',
				),
				'default'     => 'no',
			),


		);
		// Get API credentials.
		$username = $this->get_option('username');
		$password = $this->get_option('password');
		$security_key = $this->get_option('api_key');
		$processor_stored = $this->get_option('processor');
		$processors_full_list_stored = \get_option('woocommerce_bulletproof_bpcheckout_settings_lite_processors');

		$search_for_processors = false;
		if ($processors_full_list_stored != "") {
			$processors = $processors_full_list_stored;
		} else {
			$processors = $this->bulletproof_get_processors($username, $password, $security_key);
			$search_for_processors = true;
		}

		// Initialize an array to store processor IDs.
		$processors_list = array();

		// Check if processors are available and build the list.
		if ($processors != "") {
			foreach ($processors as $key => $processor) {
				// If at least a single processor is found then will store the procesor list in the WC options table
				if ($search_for_processors) {
					\update_option('woocommerce_bulletproof_bpcheckout_settings_lite_processors', $processors);
					$search_for_processors = false;
				}
				if (isset($processor->{'processor-id'}->{'0'})) {

					$processors_list[$processor->{'processor-id'}->{'0'}] = $processor->{'processor-id'}->{'0'};
				}
			}
		}
		$empty_option = array('' => __('Select a processor', 'bulletproof-checkout-lite'));
		if (!empty($processors_list)) {
			$processor_field = array(
				'title' => __('Processor', 'bulletproof-checkout-lite'),
				'type' => 'select',
				'options' => $empty_option + $processors_list,
				'default'     => $processor_stored,
				'description' => __('Select an option from the dropdown.', 'bulletproof-checkout-lite'),
			);
			$this->form_fields['processor'] = $processor_field;
		}
	}

	// Add nonce field to the settings form
	public function admin_options()
	{
?>
		<h2><?php echo esc_html($this->method_title); ?></h2>
		<table class="form-table">
			<?php $this->generate_settings_html(); ?>
		</table>
		<input type="hidden" name="bulletproof_gateway_nonce" value="<?php echo wp_create_nonce('bulletproof_gateway_nonce'); ?>" />
		<?php
	}

	/**
	 * Function to display error notices.
	 * This function displays any error notices generated during the settings save process.
	 */


	public function bulletproof_display_payment_gateway_credentials_error()
	{
		// Display error notices
		$message = get_transient('bulletproof_custom_gateway_api_error');
		if ($message) {
		?>
			<div class="notice notice-error is-dismissible">
				<p><?php esc_html($message); ?></p>
			</div>
		<?php
			// Delete the transient to avoid displaying the message again
			delete_transient('bulletproof_custom_gateway_api_error');
		}
	}




	
	/**
	 * get_icon function.
	 *
	 * @access public
	 * @return string
	 */
	public function get_icon() {
		$icon = '';
        if ( in_array( 'visa', $this->allowed_card_types ) ) {
            $icon .= '<img style="margin-left: 0.3em" src="' . WC_HTTPS::force_https_url( WC()->plugin_url() . '/assets/images/icons/credit-cards/visa.svg' ) . '" alt="Visa" width="32" />';
        }
        if ( in_array( 'mastercard', $this->allowed_card_types ) ) {
            $icon .= '<img style="margin-left: 0.3em" src="' . WC_HTTPS::force_https_url( WC()->plugin_url() . '/assets/images/icons/credit-cards/mastercard.svg' ) . '" alt="Mastercard" width="32" />';
        }
        if ( in_array( 'amex', $this->allowed_card_types ) ) {
            $icon .= '<img style="margin-left: 0.3em" src="' . WC_HTTPS::force_https_url( WC()->plugin_url() . '/assets/images/icons/credit-cards/amex.svg' ) . '" alt="Amex" width="32" />';
        }
        if ( in_array( 'discover', $this->allowed_card_types ) ) {
            $icon .= '<img style="margin-left: 0.3em" src="' . WC_HTTPS::force_https_url( WC()->plugin_url() . '/assets/images/icons/credit-cards/discover.svg' ) . '" alt="Discover" width="32" />';
        }
        if ( in_array( 'jcb', $this->allowed_card_types ) ) {
            $icon .= '<img style="margin-left: 0.3em" src="' . WC_HTTPS::force_https_url( WC()->plugin_url() . '/assets/images/icons/credit-cards/jcb.svg' ) . '" alt="JCB" width="32" />';
        }
        if ( in_array( 'diners-club', $this->allowed_card_types ) ) {
            $icon .= '<img style="margin-left: 0.3em" src="' . WC_HTTPS::force_https_url( WC()->plugin_url() . '/assets/images/icons/credit-cards/diners.svg' ) . '" alt="Diners Club" width="32" />';
        }

        return apply_filters( 'woocommerce_gateway_icon', $icon, $this->id );
	}

	/**
	 * Function to validate API credentials when saving settings.
	 * This function retrieves the API username and password from the settings form,
	 * performs validation, and adds an error notice if the credentials are not valid.
	 */

	public function bulletproof_validate_payment_gateway_credentials()
	{
		// Check requires params in post

		if (!empty($this->bulletproof_get_post(esc_attr('woocommerce_' . $this->id) . '_username')) && !empty($this->bulletproof_get_post(esc_attr('woocommerce_' . $this->id) . '_password')) && !empty($this->bulletproof_get_post(esc_attr('woocommerce_' . $this->id) . '_api_key'))) {

			// Perform credential validation
			$api_response = $this->bulletproof_get_processors($this->bulletproof_get_post(esc_attr('woocommerce_' . $this->id) . '_username'), $this->bulletproof_get_post(esc_attr('woocommerce_' . $this->id) . '_password'), $this->bulletproof_get_post(esc_attr('woocommerce_' . $this->id) . '_api_key'));
			// If credentials are not valid, add an error notice
			if ($api_response == "") {
				set_transient('bulletproof_custom_gateway_api_error', __('Invalid API credentials. Please check your API Key username and password.', 'bulletproof-checkout-lite'));

				add_action('admin_notices', array($this, 'bulletproof_display_payment_gateway_credentials_error'));
				return;
			}
		}
	}

	public function bulletproof_display_notice($message, $message_type)
	{
		if (!wc_has_notice(__($message, 'bulletproof-checkout-lite'), $message_type)) {
			wc_add_notice(__($message, 'bulletproof-checkout-lite'), $message_type);
		}
	}


	/**
	 * Handler for processing payment responses.
	 */

	public function bulletproof_payment_response_handler()
	{
		// Check if the order status has already been updated.
		$status_updated = false;
		// Nonce verification is not applicable for this payment response, as it is coming from payment processor.
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$order_id = isset($_GET['orderid']) ? intval($_GET['orderid']) : 0;
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$transaction_id = isset($_GET['transactionid']) ? intval($_GET['transactionid']) : 0;
		// Code for processing payment responses based on query parameters.
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		if (!empty($_GET['3ds_approved']) && !empty($order_id) && !empty($transaction_id)) {
			$sale_method_found = $this->get_option('salemethod');
			$order = new WC_Order($order_id);


			$this->bulletproof_update_order_meta($order_id, $transaction_id, $order);
			if ($sale_method_found == 'sale') {
				$order->payment_complete();
				$status_after_payment_completed = $this->get_option('status_after_order_completed');
				if ($status_after_payment_completed == "") $status_after_payment_completed = "completed";
				if ($status_after_payment_completed != "bp_donotchange") {
					$order->update_status($status_after_payment_completed, __('Status after payment received updated by the BulletProof Plugin. ', 'bulletproof-checkout-lite'));
				}
				wc_maybe_reduce_stock_levels($order_id);
			} else {
				$order->update_status('wc-on-hold');
			}

			$order->set_transaction_id($transaction_id);
			$order->update_meta_data('_bulletproof_gateway_action_type', $sale_method_found);
			$order->save();

			WC()->cart->empty_cart();
			// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		} elseif (!empty($_GET['denial']) || !empty($_GET['token'])) {
			self::bulletproof_display_notice('Transaction Failed', 'error');
			if ($order_id != "") {
				$order = new WC_Order($order_id);
				$order->update_status('wc-failed');
			}
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
	 * Function to make API requests for refund payment.
	 *
	 * @param string $api_url
	 * @param array $request_args
	 * @return array|mixed|object
	 */


	public function bulletproof_refund_payment_api($api_url, $request_args)
	{
		// API request logic for refund.
		$response = wp_remote_post($api_url, $request_args);

		if (is_wp_error($response)) {
			error_log('Refund API request failed: ' . $response->get_error_message());
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

	protected function bulletproof_get_post($name)
	{
		// Retrieve POST data.

		if (isset($_POST['bulletproof_gateway_nonce']) && wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['bulletproof_gateway_nonce'])), 'bulletproof_gateway_nonce')) {
			// Nonce is verified, process form data
			if (isset($_POST[$name])) {
				return sanitize_text_field($_POST[$name]);
			}
		} else {

			// Nonce verification failed, handle the error or log it
			wp_die('Security check failed');
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
		if ((strtolower($this->get_option('enabled')) == "yes")) {
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
				<label for="<?php echo esc_attr($this->id); ?>-card-number"><?php echo esc_html__('Card Number', 'bulletproof-checkout-lite'); ?> <span class="required">*</span></label>
				<input type="text" class="input-text" pattern="[0-9]*" id="<?php echo esc_attr($this->id); ?>-card-number" name="<?php echo esc_attr($this->id); ?>_card_number" minlength="14" maxlength="19" inputmode="numeric" autocorrect="no" autocapitalize="no" spellcheck="no" placeholder="" autocomplete="off" onkeydown="bulletproof_validate_ccnumber('<?php echo esc_attr($this->id); ?>-card-number');" onkeyup="bulletproof_validate_ccnumber('<?php echo esc_attr($this->id); ?>-card-number');" />
				<div id="ccnumber-error" style="color: red; display: none;">Please enter a valid Credit Card number</div>

			</div>
			<div class="form-row form-row-wide card-expiry-cvv">

				<?php
				$months = array();
				for ($i = 1; $i <= 12; $i++) {
					$month_value = str_pad($i, 2, '0', STR_PAD_LEFT);
					$months[$month_value] = $month_value;
				}
				?>
				<div class="date-year-section">
					<div class="select-row">
						<div class="select-col-half w-33">
							<label for="<?php echo esc_attr($this->id); ?>-card-expiry"><?php echo esc_html__('Month', 'bulletproof-checkout-lite'); ?> <span class="required">*</span></label>
							<select id="<?php echo esc_attr($this->id); ?>-card-expiry-month" name="<?php echo esc_attr($this->id); ?>_card_expiry_month" class="bp-card-expiry">
								<option value=""></option>
								<?php

								foreach ($months as $month_value => $month_label) {
									echo "<option value='" . esc_attr($month_value) . "'>" . esc_html($month_label) . '</option>';
								}
								?>
							</select>
						</div>
						<div class="select-2-col-half w-33">
							<label for="<?php echo esc_attr($this->id); ?>-card-expiry"><?php echo esc_html__('Year', 'bulletproof-checkout-lite'); ?> <span class="required">*</span></label>
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
						<div class="form-row form-row-wide w-33">
							<label for="<?php echo esc_attr($this->id); ?>-card-cvc"><?php echo esc_html(__('CVV', 'bulletproof-checkout-lite')); ?> <span class="required">*</span></label>
							<input type="text" class="input-text bulletproof-card-cvv" pattern="\d{3,4}" minlength="3" maxlength="4" id="<?php echo esc_attr($this->id); ?>-card-cvc" name="<?php echo esc_attr($this->id); ?>_card_cvc" inputmode="numeric" autocorrect="no" autocapitalize="no" spellcheck="no" placeholder="" autocomplete="off" />
						</div>
					</div>
				</div>
			</div>
			<div id="cvv-error" style="color: red; display: none;">Please enter a valid 3 or 4 digit number</div>
			<!-- Add the hidden nonce field -->
			<?php

			echo '<input type="hidden" name="bulletproof_gateway_nonce" value="' . esc_attr(wp_create_nonce('bulletproof_gateway_nonce')) . '" />';
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
				<p class="save-payment-checkbox">
					<input type="checkbox" class="input-checkbox" id="save_payment_info" name="save_payment_info" />
					<label for="save_payment_info"><?php echo esc_html__('Save payment information to my account', 'bulletproof-checkout-lite'); ?></label>


				</p>
<?php
			}
			echo '<div class="clear"></div></fieldset>';
		} else {
			echo "<div id='bp-lite-no-available' style='color: red; '>Currently, this payment gateway is not available. Please contact the Merchant.</div>";
		}
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
		error_log('Starting refund Order id#: ' . $order_id . ' . Amount to be refunded:' . $amount);

		// Get the WooCommerce order.
		$order = wc_get_order($order_id);
		// Check if the order is valid.
		//|| ! $order->get_transaction_id()
		if (! $order ||  !is_object($order) || $amount <= 0) {
			return new WP_Error('invalid_order', 'Invalid order.');
		}
		// Get API credentials and transaction ID.
		$username = $this->get_option('username');
		$password = $this->get_option('password');
		$security_key = $this->get_option('api_key');
		$transaction_id = $order->get_meta('_payment_gateway_tx_received', true);
		// Prepare request arguments.
		$request_args = array(
			'headers' => array(
				'accept' => 'application/json',
			),
			'body' => '',
		);

		if ((strtolower($this->get_option('enabled')) == "yes")) {
			if ($transaction_id != "") {
				// Locate the API endpoint to be used
				$base_api_url = BULLETPROOF_CHECKOUT_API_BASE_URL;
				try {
					if ((strtolower($this->get_option('testmode')) == "no") || ($this->get_option('testmode') == "")) {
						$base_api_url = BULLETPROOF_CHECKOUT_API_BASE_URL;
					} else {
						if (strtolower($this->get_option('testmode')) == "yes") {
							$base_api_url = BULLETPROOF_CHECKOUT_API_BASE_URL_SANDBOX;
						}
					}
				} catch (Exception $e) {
					$base_api_url = BULLETPROOF_CHECKOUT_API_BASE_URL;
				}

				// Build the API URL with parameters.
				$api_url = $base_api_url . 'refund.php?user=' . urlencode($username) .
					'&pass=' . urlencode($password) .
					'&security_key=' . urlencode($security_key) .
					'&transactionid=' . urlencode($transaction_id);
				// adds support for partial refunds
				if ($amount != "") {
					$api_url .= '&amount=' . $amount;
				}

				// Make the refund API call.
				$response = $this->bulletproof_refund_payment_api($api_url, $request_args);
				error_log(print_r($response, true));

				if ((isset($response['error'])) && ($response['error'] != "")) {
					error_log(print_r($response['error'], true));
					return new WP_Error('bulletproof_refund_api_error', $response['error']);
				} else {
					// $order->update_status('refunded');
					// $order->add_order_note('Refunded via BulletProof Checkout.');
					return true;
				}
			} else {
				$the_msg = "Refund was not made due to missed transaction id";
				error_log(print_r($the_msg, true));
				return new WP_Error('bulletproof_no_transaction_id', $the_msg);
			}
		} else {
			$the_msg = "Refund endpoint not available due to the Payment Gateway is disabled";
			error_log(print_r($the_msg, true));
			return new WP_Error('bulletproof_disabled', $the_msg);
		}
	}


	/**
	 * Gets a list of Bulletproof processors.
	 *
	 * @return array The list of processors.
	 */

	public function bulletproof_get_processors($username, $password, $security_key)
	{
		// Locate the API endpoint to be used
		$base_api_url = "";
		try {
			if ((strtolower($this->get_option('testmode')) == "no") || ($this->get_option('testmode') == "")) {
				$base_api_url = BULLETPROOF_CHECKOUT_API_BASE_URL;
			} else {
				$base_api_url = BULLETPROOF_CHECKOUT_API_BASE_URL_SANDBOX;
			}
		} catch (Exception $e) {
			$base_api_url = BULLETPROOF_CHECKOUT_API_BASE_URL;
		}
		// Set the API URL for retrieving processors.
		$api_url = $base_api_url . 'processors.php';

		// Check if required API credentials are available.
		if (empty($username) || empty($password) || empty($security_key)) {
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
		$processors = '';
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
		if ((strtolower($this->get_option('enabled')) == "yes")) {
			// Get card details from post data.
			$card_number           = $this->bulletproof_get_post(esc_attr($this->id) . '_card_number');
			$card_cvv              = $this->bulletproof_get_post(esc_attr($this->id) . '_card_cvc');
			$card_expiration_month = $this->bulletproof_get_post(esc_attr($this->id) . '_card_expiry_month');
			$card_expiration_year  = $this->bulletproof_get_post(esc_attr($this->id) . '_card_expiry_year');

			// Validate card number.
			if (empty($card_number) || !ctype_digit($card_number) || !preg_match('/^\d{14,19}$/', $card_number)) {
				self::bulletproof_display_notice('Card number is invalid.', 'error');
				return false;
			}

			// Validate card security code.
			if (!ctype_digit($card_cvv)) {
				self::bulletproof_display_notice('Card security code is invalid (only digits are allowed).', 'error');
				return false;
			}

			// Validate card security code length.
			if (!preg_match('/^\d{3,4}$/', $card_cvv)) {
				self::bulletproof_display_notice('Card security code is invalid (wrong length).', 'error');
				return false;
			}

			// Get the current year.
			$current_year = gmdate('y');

			// Validate card expiration date.
			if (
				!ctype_digit($card_expiration_month) || !ctype_digit($card_expiration_year) ||
				$card_expiration_month > 12 || $card_expiration_month < 1 || $card_expiration_year < $current_year || $card_expiration_year > $current_year + 10
			) {
				self::bulletproof_display_notice('Card expiration date is invalid', 'error');
				return false;
			}

			// Remove spaces and hyphens from card number.
			$card_number = str_replace(array(' ', '-'), '', $card_number);

			// Validation passed.
			return true;
		} else {
			self::bulletproof_display_notice('Currently, this payment gateway is not available. Please contact the merchant', 'error');
			return false;
		}
	}


	/**
	 * Processes a payment for an order.
	 *
	 * @param int $order_id The ID of the WooCommerce order.
	 * @return array An array with 'result' and 'redirect' keys.
	 */

	public function process_payment($order_id)
	{
		if (!isset($_POST['bulletproof_gateway_nonce']) || !wp_verify_nonce(sanitize_text_field($_POST['bulletproof_gateway_nonce']), 'bulletproof_gateway_nonce')) {
			// Nonce verification failed, handle error
			return;
		}

		// check if the plugin is enabled
		if ((strtolower($this->get_option('enabled')) == "yes")) {

			// Locate the API endpoint to be used
			$base_api_url = "";
			try {
				if ((strtolower($this->get_option('testmode')) == "no") || ($this->get_option('testmode') == "")) {
					$base_api_url = BULLETPROOF_CHECKOUT_API_BASE_URL;
				} else {
					$base_api_url = BULLETPROOF_CHECKOUT_API_BASE_URL_SANDBOX;
				}
			} catch (Exception $e) {
				$base_api_url = BULLETPROOF_CHECKOUT_API_BASE_URL;
			}

			// Create a new instance of the WooCommerce order.
			$order = new WC_Order($order_id);

			// Set the API URL for sale authorization.
			$sale_auth_api_url = $base_api_url . "?rndx=" . time();

			// Get sale authorization parameters.
			$sale_auth_params = $this->bulletproof_checkout_api_params($order, $order_id);

			// Make the sale authorization API call.
			$sale_auth_response = $this->bulletproof_checkout_api($sale_auth_api_url, $sale_auth_params, 'POST');

			// Check if the sale authorization was successful.
			if ($sale_auth_response != "" && $sale_auth_response != "NULL") {
				if (isset($sale_auth_response->token) && !empty($sale_auth_response->token)) {

					// Build the validation API URL.
					$validate_api_url = $base_api_url . 'validate.php?token=' . $sale_auth_response->token;

					// Return success with redirection URL.
					return array(
						'result' => 'success',
						'redirect' => $validate_api_url,
					);
				} elseif (isset($sale_auth_response->error) && !empty($sale_auth_response->error)) {
					// Check if there is an error in the sale authorization response.

					// Adding translators comment
					/* translators: %s: Error message from the response */
					$template = __('Error: %s', 'bulletproof-checkout-lite');

					// Use printf to display the message (disable the comments if you want to display extra details)
					//printf(
					/* translators: %s: Error message from the response */
					//	esc_html($template),
					//	esc_html($sale_auth_response->error)
					//);

					// Use sprintf to capture the formatted message
					$formatted_message = sprintf(
						/* translators: %s: Error message from the response */
						esc_html($template),
						esc_html($sale_auth_response->error)
					);

					// Display an error notice and return an empty array.
					self::bulletproof_display_notice($formatted_message, 'error');
					$order->update_status('wc-failed');
					return array();
				} else {
					//var_dump($sale_auth_response);
					$error_invalid_response = "Invalid response received from the gateway, please try in some minutes or Contact the Merchant";
					$template = __('Error: %s', 'bulletproof-checkout-lite');
					// Use sprintf to capture the formatted message
					$formatted_message = sprintf(
						/* translators: %s: Error message from the response */
						esc_html($template),
						esc_html($error_invalid_response)
					);
					$order->update_status('wc-failed');

					self::bulletproof_display_notice($formatted_message, 'error');
				}
			} else {


				// Adding translators comment
				/* translators: %s: Error message from the response */
				$template = __('Error: %s', 'bulletproof-checkout-lite');
				$error_no_response = "No response received from the Payment Gateway, please Contact the Merchant or try again in some minutes.";

				// Use sprintf to capture the formatted message
				$formatted_message = sprintf(
					/* translators: %s: Error message from the response */
					esc_html($template),
					esc_html($error_no_response)
				);

				// Display an error notice and return an empty array.
				self::bulletproof_display_notice($formatted_message, 'error');
				$order->update_status('wc-failed');
				return array();
			}
		} else {
			// Adding translators comment
			/* translators: %s: Error message from the response */
			$template = __('Error: %s', 'bulletproof-checkout-lite');
			$error_no_response = "the BulletProof Payment Gateway is disabled, please Contact the Merchant or try again in some minutes.";

			// Use sprintf to capture the formatted message
			$formatted_message = sprintf(
				/* translators: %s: Error message from the response */
				esc_html($template),
				esc_html($error_no_response)
			);

			// Display an error notice and return an empty array.
			self::bulletproof_display_notice($formatted_message, 'error');

			return array();
		}
	}


	/**
	 * Updates order meta data after a successful transaction.
	 *
	 * @param int    $order_id The ID of the WooCommerce order.
	 * @param string $transaction_id The transaction ID.
	 */

	public function bulletproof_update_order_meta($order_id, $transaction_id, $order = "")
	{
		// Get the current date and time.
		$order_date = gmdate('Y-m-d H:i:s');

		// Determine the gateway environment.
		$gateway_environment = $this->testmode ? 'sandbox' : 'live';
		$unix_format_date = strtotime(gmdate('Y-m-d H:i:s'));
		$random_naunce_key = $this->bulletproof_generate_random_string();

		if ($order == "") {
			$order = wc_get_order($order_id);
		}
		// Update various order meta data.

		$order->update_meta_data('_bulletproof_gateway_action_type_sale', $order_date);
		$order->update_meta_data('_payment_gateway_tx_received_prewebhook', $transaction_id);
		$order->update_meta_data('_subscriptionId_prewebhook', "");
		$order->update_meta_data('_payment_gateway_tx_received', $transaction_id);
		$order->update_meta_data('_payment_gateway_subscriptionId_received', "");
		$order->update_meta_data('bulletproof_bpcheckout_gateway', BULLETPROOF_BPCHECKOUT_GATEWAY);
		$order->update_meta_data('_bulletproof_bpcheckout_gateway', BULLETPROOF_BPCHECKOUT_GATEWAY);
		$order->update_meta_data('bulletproof_bpcheckout_gateway_environment', $gateway_environment);
		$order->update_meta_data('_bulletproof_bpcheckout_gateway_environment', $gateway_environment);
		$order->update_meta_data('_date_completed', $unix_format_date);
		$order->update_meta_data('_date_paid', $unix_format_date);
		$order->update_meta_data('_paid_date', gmdate('Y-m-d H:i:s'));
		$order->update_meta_data('_completed_date', gmdate('Y-m-d H:i:s'));
		$order->update_meta_data('_random_naunce_key', $random_naunce_key);
		$order->save();
	}

	// String operations functions
	public static function left($str, $length)
	{
		return substr($str, 0, $length);
	}

	public static function right($str, $length)
	{
		if ($str != "") {
			return substr($str, -$length);
		} else {
			return "";
		}
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
		//$item_product_code = array();
		$item_array = array();
		// Loop through order items to get product IDs.
		// Build the array for the line items to be sent to the Gateway
		$line_item_counter = 0;
		foreach ($order->get_items() as $item_id => $item_data) {
			$line_item_counter++;

			$current_product_id = $item_data->get_product_id();
			$current_product = \wc_get_product($current_product_id);
			$price = $current_product->get_price();
			// in case of product meta handled by third party plugins
			if ($price == 0) {
				if ($item_data->get_quantity() > 0) {
					$price = $item_data->get_total() / $item_data->get_quantity();
				}
			}
			$single_item = array(
				"item_product_code_" . $line_item_counter => (string)$current_product_id,
				"item_description_" . $line_item_counter => $item_data->get_name(),
				"item_quantity_" . $line_item_counter => $item_data->get_quantity(),
				"item_unit_cost_" . $line_item_counter => number_format($price, 2, '.', ''),
				"item_total_amount_" . $line_item_counter => number_format($item_data->get_total(), 2, '.', ''),
				"item_tax_amount_" . $line_item_counter => number_format($item_data->get_subtotal_tax(), 2, '.', '')
			);
			$item_array = array_merge($item_array, $single_item);
		}


		// Check the order fees looking for Surcharge Fees or Technology Fees
		$fees = $order->get_fees();

		$surcharge_amount = 0;
		foreach ($fees as $key => $fee) {
			if ((isset($fee['name'])) && (isset($fee['total'])) && ($fee['name'] != "") && ($fee['total'] != "")) {
				if ((strtolower($fee['name']) == "technology fees") || (strtolower($fee['name']) == "technology fee") || (strtolower($fee['name']) == "surcharge")) {
					$surcharge_amount = $surcharge_amount + $fee['total'];
				}
			}
		}

		// Get user details.
		$user = new WP_User($order->get_user_id());

		// Retrieve API credentials.
		$username = $this->get_option('username');
		$password = $this->get_option('password');
		$security_key = $this->get_option('api_key');

		// Check if required API credentials are available.
		if (empty($security_key) || empty($username) || empty($password)) {
			self::bulletproof_display_notice("API key, username, and password are required.", 'error');
			return;
		}

		// Determine if payment info should be saved to the vault.
		$vault = $this->bulletproof_get_post('save_payment_info') ? 'Y' : 'N';

		// Get sale method, processor, and card details.
		$sale_method = $this->get_option('salemethod');
		$test_mode = $this->get_option('testmode');
		$processor = !empty($this->get_option('processor')) ? $this->get_option('processor') : '';
		$ccnum = $this->bulletproof_get_post(esc_attr($this->id) . '_card_number');
		$card_cvv = $this->bulletproof_get_post(esc_attr($this->id) . '_card_cvc');
		$card_expiration_month = $this->bulletproof_get_post(esc_attr($this->id) . '_card_expiry_month');
		$card_expiration_year  = $this->bulletproof_get_post(esc_attr($this->id) . '_card_expiry_year');
		$ccexp = $card_expiration_month . '' . $card_expiration_year;

		// Validates the Card Expiration Month
		if (!is_numeric($card_expiration_month)) {
			self::bulletproof_display_notice("Invalid Card Expiration Month.", 'error');
			return;
		} else {
			if (($card_expiration_month > 12) || ($card_expiration_month < 1)) {
				self::bulletproof_display_notice("Invalid Card Expiration Month", 'error');
				return;
			}
		}
		// Validates the Card Expiration Year
		if (!is_numeric($card_expiration_year)) {
			self::bulletproof_display_notice("Invalid Card Expiration Year.", 'error');
			return;
		}
		// Validates CVV lenght
		if ((strlen($card_cvv) > 4) || (strlen($card_cvv) < 3)) {
			self::bulletproof_display_notice("Invalid CVV.", 'error');
			return;
		}
		// Validates the Sale method
		if ($sale_method != "") {
			$sale_method = trim(strtoupper($sale_method));
			if (!in_array($sale_method, array("SALE", "AUTH", "VALIDATE"))) {
				self::bulletproof_display_notice("Invalid Sale Method.", 'error');
				return;
			}
		} else {
			self::bulletproof_display_notice("Invalid Sale Method", 'error');
			return;
		}

		$the_amount = $order->get_total();
		$the_country = $order->get_billing_country();
		$the_country_shipping = $order->get_shipping_country();
		try {
			$the_state = $order->get_billing_state();
		} catch (Exception $e) {
			$the_state = "";
		}

		try {
			$the_state_shipping = $order->get_shipping_state();
		} catch (Exception $e) {
			$the_state_shipping = "";
		}


		// patch for remove value format XX-AA in some countries
		if (($the_state != '') && ($the_country != '')) {
			if (strpos($the_state, $the_country . "-")>=0) {
				$the_state = str_replace($the_country . "-", "", $the_state);
			}
		}
		if (($the_state_shipping != '') && ($the_country_shipping != '')) {
			if (strpos($the_state_shipping, $the_country_shipping . "-") >=0) {
				$the_state_shipping = str_replace($the_country_shipping . "-", "", $the_state_shipping);
			}
		}
		// check if state is longer than 3 characters
		if (strlen($the_state) > 3) {
			$the_state = $this->left($the_state, 3);
		}
		if (strlen($the_state_shipping) > 3) {
			$the_state_shipping = $this->left($the_state_shipping, 3);
		}

		// Build an array of sale authorization parameters.
		// The parameter fix_iso_codes will ignore states (which are not on ISO format)
		$sale_auth_params = array(
			'sale_auth_only' => $sale_method,
			'gateway' => BULLETPROOF_CHECKOUT_GATEWAY,
			'security_key' => $security_key,
			'user' => $username,
			'pass' => $password,
			'ccnumber' => $ccnum,
			'ccexp' => $ccexp,
			'cvv' => $card_cvv,
			'amount' => number_format($the_amount, 2, '.', ''),
			'tax' => number_format($order->get_total_tax(), 2, '.', ''),
			'shipping' => number_format($order->get_shipping_total(), 2, '.', ''),
			'orderid' => $order_id,
			'format' => BULLETPROOF_CHECKOUT_FORMAT,
			'approval_url' => $this->get_return_url($order),
			'denial_url' => wc_get_checkout_url(),
			'vault' => $vault,
			'customer-id' => $order->get_user_id(),
			'processor-id' => $processor,
			'test_mode' => $test_mode,
			'billing_address_first_name' => $order->get_billing_first_name(),
			'billing_address_last_name' => $order->get_billing_last_name(),
			'billing_address_address1' => $order->get_billing_address_1(),
			'billing_address_address2' => $order->get_billing_address_2(),
			'billing_address_city' => $order->get_billing_city(),
			'billing_address_state' => $the_state,
			'billing_address_zip' => $order->get_billing_postcode(),
			'billing_address_country' => $the_country,
			'billing_address_phone' => $order->get_billing_phone(),
			'billing_address_email' => $order->get_billing_email(),
			'shipping_address_first_name' => $order->get_shipping_first_name(),
			'shipping_address_last_name' => $order->get_shipping_last_name(),
			'shipping_address_address1' => $order->get_shipping_address_1(),
			'shipping_address_address2' => $order->get_shipping_address_2(),
			'shipping_address_city' => $order->get_shipping_city(),
			'shipping_address_state' => $the_state_shipping,
			'shipping_address_zip' => $order->get_shipping_postcode(),
			'shipping_address_country' => $the_country_shipping,
			'source_override' => 'WOOCOMMERCELITE',
			'fix_iso_codes'=> 'true'
		);


		
		
		// Adds line item information
		$sale_auth_params = array_merge($sale_auth_params, $item_array);
		if ($surcharge_amount > 0) {
			$surcharge_array = array("surcharge_amount" => $surcharge_amount);
			$sale_auth_params = array_merge($sale_auth_params, $surcharge_array);
		}
		// Add any Merchant defined field, merchant defined fields available are #10,11 and 12

		// Stores any extra fee in the Merchant defined field 10
		$fees = $order->get_fees();
		$fees_total = 0;
		$fees_titles = "";
		foreach ($fees as $key => $fee) {
			if ((isset($fee['name'])) && (isset($fee['total'])) && ($fee['name'] != "") && ($fee['total'] != "")) {
				if ((strtolower($fee['name']) == "technology fees") || (strtolower($fee['name']) == "technology fee") || (strtolower($fee['name']) == "surcharge")) {
					$surcharge_amount = $surcharge_amount + $fee['total'];
				} else {
					// any other fee will be sum
					if ($fees_titles != "") $fees_titles .= ",";
					$fees_titles .= urlencode($fee['name']);
					$fees_total = $fees_total + $fee['total'];
				}
			}
		}
		// If some fees are present, then will register those extra fees as part of a merchant defined fields
		if ($fees_total > 0) {
			$sale_auth_params = array_merge($sale_auth_params, array("merchant_defined_field_10" => "Extra fees:" . $fees_titles . "=" . $fees_total));
		}

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
				),
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

	public function bulletproof_generate_random_string($length = 10)
	{
		// Define characters for the random string.
		$characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';

		// Get the length of the character set.
		$charactersLength = strlen($characters);

		// Initialize an empty string.
		$randomString = '';

		// Loop to generate random string.
		for ($i = 0; $i < $length; $i++) {
			$randomString .= $characters[wp_rand(0, $charactersLength - 1)];
		}

		// Return the generated random string.
		return $randomString;
	}
}


// Instantiate the Bulletproof Payment Gateway Lite class.
new Bulletproof_Payment_Gateway_Lite();
