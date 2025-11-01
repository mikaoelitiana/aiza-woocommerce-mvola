<?php

if (!defined('ABSPATH')) {
    exit;
}

class WC_Gateway_MVola extends WC_Payment_Gateway {

    const API_BASE_URL_SANDBOX = 'https://pre-api.mvola.mg';
    const API_BASE_URL_PRODUCTION = 'https://api.mvola.mg';
    
    public $testmode;
    public $consumer_key;
    public $secret_key;
    public $merchant_number;
    public $callback_url;
    public $partner_name;
    
    public function __construct() {
        $this->id = 'mvola';
        $this->icon = apply_filters('woocommerce_mvola_icon', '');
        $this->has_fields = true;
        $this->method_title = __('MVola Madagascar', 'woocommerce-mvola');
        $this->method_description = __('Accept payments via MVola mobile money', 'woocommerce-mvola');
        $this->supports = array(
            'products'
        );

        $this->init_form_fields();
        $this->init_settings();

        $this->title = $this->get_option('title');
        $this->description = $this->get_option('description');
        $this->enabled = $this->get_option('enabled');
        $this->testmode = 'yes' === $this->get_option('testmode');
        $this->consumer_key = $this->get_option('consumer_key');
        $this->secret_key = $this->get_option('secret_key');
        $this->merchant_number = $this->get_option('merchant_number');
        $this->callback_url = $this->get_option('callback_url');
        $this->partner_name = $this->get_option('partner_name');

        add_action('woocommerce_update_options_payment_gateways_' . $this->id, array($this, 'process_admin_options'));
        add_action('woocommerce_api_wc_gateway_mvola', array($this, 'handle_callback'));
    }

    public function init_form_fields() {
        $this->form_fields = array(
            'partner_name' => array(
                'title' => __('Partner Name', 'woocommerce-mvola'),
                'type' => 'text',
                'description' => __('Name of your business/partner for MVola API header.', 'woocommerce-mvola'),
                'default' => 'WooCommerce',
                'desc_tip' => true,
            ),
            'enabled' => array(
                'title' => __('Enable/Disable', 'woocommerce-mvola'),
                'type' => 'checkbox',
                'label' => __('Enable MVola payment', 'woocommerce-mvola'),
                'default' => 'no'
            ),
            'title' => array(
                'title' => __('Title', 'woocommerce-mvola'),
                'type' => 'text',
                'description' => __('This controls the title which the user sees during checkout.', 'woocommerce-mvola'),
                'default' => __('MVola', 'woocommerce-mvola'),
                'desc_tip' => true,
            ),
            'description' => array(
                'title' => __('Description', 'woocommerce-mvola'),
                'type' => 'textarea',
                'description' => __('This controls the description which the user sees during checkout.', 'woocommerce-mvola'),
                'default' => __('Pay securely using MVola mobile money.', 'woocommerce-mvola'),
            ),
            'testmode' => array(
                'title' => __('Test mode', 'woocommerce-mvola'),
                'type' => 'checkbox',
                'label' => __('Enable Sandbox Mode', 'woocommerce-mvola'),
                'default' => 'yes',
                'description' => __('Use the sandbox environment for testing. No real money will be used.', 'woocommerce-mvola'),
            ),
            'consumer_key' => array(
                'title' => __('Consumer Key', 'woocommerce-mvola'),
                'type' => 'text',
                'description' => __('Get your consumer key from MVola developer portal.', 'woocommerce-mvola'),
                'default' => '',
                'desc_tip' => true,
            ),
            'secret_key' => array(
                'title' => __('Secret Key', 'woocommerce-mvola'),
                'type' => 'password',
                'description' => __('Get your secret key from MVola developer portal.', 'woocommerce-mvola'),
                'default' => '',
                'desc_tip' => true,
            ),
            'merchant_number' => array(
                'title' => __('Merchant Phone Number', 'woocommerce-mvola'),
                'type' => 'text',
                'description' => __('Your MVola merchant account phone number (receiver).', 'woocommerce-mvola'),
                'default' => '',
                'desc_tip' => true,
            ),
            'callback_url' => array(
                'title' => __('Custom Callback URL', 'woocommerce-mvola'),
                'type' => 'text',
                'description' => sprintf(
                    __('Optional: Override the default callback URL. Leave empty to use: %s<br>For local development, use ngrok or similar service to make your callback publicly accessible.', 'woocommerce-mvola'),
                    WC()->api_request_url('wc_gateway_mvola')
                ),
                'default' => '',
                'placeholder' => WC()->api_request_url('wc_gateway_mvola'),
            ),
        );
    }

    public function payment_fields() {
        if ($this->description) {
            echo wpautop(wptexturize($this->description));
        }
        ?>
        <fieldset id="wc-<?php echo esc_attr($this->id); ?>-form" class="wc-payment-form">
            <p class="form-row form-row-wide">
                <label for="mvola-phone-number"><?php echo esc_html__('Your MVola Phone Number', 'woocommerce-mvola'); ?> <span class="required">*</span></label>
                <input id="mvola-phone-number" name="mvola_phone_number" type="tel" class="input-text" placeholder="034 00 000 00" pattern="[0-9\s]+" required />
                <small><?php echo esc_html__('Enter your MVola account phone number', 'woocommerce-mvola'); ?></small>
            </p>
        </fieldset>
        <?php
    }

    public function validate_fields() {
        if (empty($_POST['mvola_phone_number'])) {
            wc_add_notice(__('MVola phone number is required.', 'woocommerce-mvola'), 'error');
            return false;
        }

        $phone = sanitize_text_field($_POST['mvola_phone_number']);
        $phone = preg_replace('/[^0-9]/', '', $phone);
        
        if (strlen($phone) < 10) {
            wc_add_notice(__('Please enter a valid MVola phone number.', 'woocommerce-mvola'), 'error');
            return false;
        }

        return true;
    }

    public function is_available() {
        if (!$this->enabled || 'yes' !== $this->enabled) {
            return false;
        }
        
        $is_available = parent::is_available();
        
        if (!$is_available) {
            return false;
        }

        if ($this->testmode) {
            return true;
        }

        if (empty($this->consumer_key) || empty($this->secret_key) || empty($this->merchant_number)) {
            return false;
        }

        return true;
    }

    private function get_api_base_url() {
        return $this->testmode ? self::API_BASE_URL_SANDBOX : self::API_BASE_URL_PRODUCTION;
    }

    private function get_access_token() {
        $cached_token = get_transient('mvola_access_token_' . ($this->testmode ? 'sandbox' : 'live'));
        if ($cached_token) {
            return $cached_token;
        }

        $credentials = base64_encode($this->consumer_key . ':' . $this->secret_key);

        $response = wp_remote_post($this->get_api_base_url() . '/token', array(
            'headers' => array(
                'Authorization' => 'Basic ' . $credentials,
                'Content-Type' => 'application/x-www-form-urlencoded',
                'Cache-Control' => 'no-cache',
            ),
            'body' => array(
                'grant_type' => 'client_credentials',
                'scope' => 'EXT_INT_MVOLA_SCOPE',
            ),
        ));

        if (is_wp_error($response)) {
            $this->log('Token error: ' . $response->get_error_message());
            return false;
        }

        $body = json_decode(wp_remote_retrieve_body($response), true);
        
        if (isset($body['access_token'])) {
            $token = $body['access_token'];
            $expires_in = isset($body['expires_in']) ? intval($body['expires_in']) - 60 : 3540;
            set_transient('mvola_access_token_' . ($this->testmode ? 'sandbox' : 'live'), $token, $expires_in);
            return $token;
        }

        $this->log('Token response: ' . print_r($body, true));
        return false;
    }

    private function generate_correlation_id() {
        // Use uniqid with more entropy for uniqueness
        return uniqid('wc_mvola_', true);
    }

    public function process_payment($order_id) {
        $order = wc_get_order($order_id);
        $access_token = $this->get_access_token();

        if (!$access_token) {
            wc_add_notice(__('Payment error: Could not authenticate with MVola.', 'woocommerce-mvola'), 'error');
            return array('result' => 'fail');
        }

        $phone = sanitize_text_field($_POST['mvola_phone_number']);
        $phone = preg_replace('/[^0-9]/', '', $phone);
        // Normalize to international format
        if (preg_match('/^26134[0-9]{7}$/', $phone)) {
            // Already correct
        } elseif (preg_match('/^034[0-9]{7}$/', $phone)) {
            $phone = '261' . substr($phone, 1); // Replace 0 with 261
        } elseif (preg_match('/^34[0-9]{7}$/', $phone)) {
            $phone = '261' . $phone;
        } else {
            wc_add_notice(__('Please enter a valid MVola phone number (034xxxxxxx or 26134xxxxxxx).', 'woocommerce-mvola'), 'error');
            return array('result' => 'fail');
        }

        $reference = 'WC-' . $order_id . '-' . wp_generate_password(10, false);
        $callback_url = !empty($this->callback_url) ? $this->callback_url : WC()->api_request_url('wc_gateway_mvola');
        $callback_url = add_query_arg('reference', $reference, $callback_url);

        $transaction_data = array(
            'amount' => (string) $order->get_total(),
            'currency' => 'Ar',
            'descriptionText' => (string) ('Order-' . $order->get_order_number()),
            'requestDate' => (string) gmdate('Y-m-d\TH:i:s.000\Z'),
            'debitParty' => array(
                array(
                    'key' => (string) 'msisdn',
                    'value' => (string) $phone
                )
            ),
            'creditParty' => array(
                array(
                    'key' => (string) 'msisdn',
                    'value' => (string) $this->merchant_number
                )
            ),
            'metadata' => array(
                array(
                    'key' => (string) 'partnerName',
                    'value' => (string) (!empty($this->partner_name) ? $this->partner_name : 'WooCommerce')
                ),

                array(
                    'key' => (string) 'fc',
                    'value' => (string) 'USD'
                ),
                array(
                    'key' => (string) 'amountFc',
                    'value' => (string) '1'
                )
            ),
            'requestingOrganisationTransactionReference' => (string) $reference,
            'originalTransactionReference' => (string) $order->get_order_number()
        );

        $response = wp_remote_post($this->get_api_base_url() . '/mvola/mm/transactions/type/merchantpay/1.0.0/', array(
            'headers' => array(
                'Authorization' => 'Bearer ' . $access_token,
                'Content-Type' => 'application/json',
                'UserLanguage' => 'mg',
                'UserAccountIdentifier' => 'msisdn;' . $this->merchant_number,
                'partnerName' => !empty($this->partner_name) ? $this->partner_name : 'WooCommerce',
                'Cache-Control' => 'no-cache',
                'Version' => '1.0',
                'X-CorrelationID' => $this->generate_correlation_id(),
                'X-Callback-URL' => $callback_url,
            ),
            'body' => json_encode($transaction_data),
            'timeout' => 30,
        ));

        if (is_wp_error($response)) {
            $this->log('Transaction error: ' . $response->get_error_message());
            wc_add_notice(__('Payment error: ', 'woocommerce-mvola') . $response->get_error_message(), 'error');
            return array('result' => 'fail');
        }

        $body = json_decode(wp_remote_retrieve_body($response), true);
        $response_code = wp_remote_retrieve_response_code($response);

        $this->log('Transaction response: ' . print_r($body, true));

        if (($response_code === 201 || $response_code === 202) && isset($body['serverCorrelationId'])) {
            global $wpdb;
            $table_name = $wpdb->prefix . 'wc_mvola_transactions';
            
            $wpdb->insert(
                $table_name,
                array(
                    'order_id' => $order_id,
                    'server_correlation_id' => $body['serverCorrelationId'],
                    'correlation_id' => $headers['X-CorrelationID'],
                    'reference' => $reference,
                    'user_account_identifier' => $phone,
                    'status' => 'pending',
                )
            );

            $order->update_status('pending', __('Awaiting MVola payment confirmation. Please check your phone.', 'woocommerce-mvola'));
            $order->add_order_note(
                sprintf(__('MVola payment initiated. Transaction ID: %s. Customer will receive a push notification on their phone.', 'woocommerce-mvola'), 
                $body['serverCorrelationId'])
            );

            WC()->cart->empty_cart();

            return array(
                'result' => 'success',
                'redirect' => $this->get_return_url($order),
            );
        }

        $error_message = isset($body['message']) ? $body['message'] : __('Could not initiate payment.', 'woocommerce-mvola');
        wc_add_notice(__('Payment error: ', 'woocommerce-mvola') . $error_message, 'error');
        return array('result' => 'fail');
    }

    public function handle_callback() {
        global $wpdb;

        $reference = isset($_GET['reference']) ? sanitize_text_field($_GET['reference']) : '';

        if (empty($reference)) {
            $this->log('Callback: No reference provided');
            status_header(400);
            exit('No reference');
        }

        $table_name = $wpdb->prefix . 'wc_mvola_transactions';
        $transaction = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $table_name WHERE reference = %s",
            $reference
        ));

        if (!$transaction) {
            $this->log('Callback: Transaction not found for reference: ' . $reference);
            status_header(404);
            exit('Transaction not found');
        }

        $order = wc_get_order($transaction->order_id);

        if (!$order) {
            $this->log('Callback: Order not found: ' . $transaction->order_id);
            status_header(404);
            exit('Order not found');
        }

        $access_token = $this->get_access_token();

        if (!$access_token) {
            $this->log('Callback: Could not get access token');
            status_header(500);
            exit('Authentication error');
        }

        // Query correlation_id using server_correlation_id
        $correlation_id = $transaction->correlation_id;
        $status_response = wp_remote_get(
            $this->get_api_base_url() . '/mvola/mm/transactions/type/merchantpay/1.0.0/status/' . $transaction->server_correlation_id,
            array(
                'headers' => array(
                    'Authorization' => 'Bearer ' . $access_token,
                    'UserLanguage' => 'FR',
                    'UserAccountIdentifier' => 'msisdn;' . $transaction->user_account_identifier,
                    'PartnerName' => 'WooCommerce',
                    'Cache-Control' => 'no-cache',
                    'Version' => '1.0',
                    'X-CorrelationID' => $correlation_id,
                ),
                'timeout' => 30,
            )
        );

        if (is_wp_error($status_response)) {
            $this->log('Callback: Status check error: ' . $status_response->get_error_message());
            status_header(500);
            exit('Status check error');
        }

        $status_body = json_decode(wp_remote_retrieve_body($status_response), true);
        $this->log('Callback: Status response: ' . print_r($status_body, true));

        if (isset($status_body['status']) && strtolower($status_body['status']) === 'success') {
            $wpdb->update(
                $table_name,
                array('status' => 'completed'),
                array('reference' => $reference)
            );

            $order->payment_complete($transaction->server_correlation_id);
            $order->add_order_note(
                sprintf(__('MVola payment completed. Transaction ID: %s', 'woocommerce-mvola'), 
                $transaction->server_correlation_id)
            );

            status_header(200);
            exit('Payment confirmed');
        } else {
            $wpdb->update(
                $table_name,
                array('status' => 'failed'),
                array('reference' => $reference)
            );

            $order->update_status('failed', __('MVola payment failed or was cancelled', 'woocommerce-mvola'));
            
            status_header(200);
            exit('Payment failed');
        }
    }

    private function log($message) {
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log('WC_MVola: ' . $message);
        }
    }
}
