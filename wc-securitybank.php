<?php
/**
 * Plugin Name: Security Bank WebCollect for WooCommerce
 * Plugin URI: https://securitybankcollect.com/
 * Description: Accept Visa, Mastercard, and popular E-Wallet payments via Security Bank WebCollect.
 * Version: 1.0.3
 * Author: Ramer Ian Dela Pena
 * Author URI: https://ramerian.me
 * License: GPL2
 */

defined('ABSPATH') || exit;

// Make sure WooCommerce is active
if (!in_array('woocommerce/woocommerce.php', apply_filters('active_plugins', get_option('active_plugins')))) {
    return;
}

// Include API class
require_once plugin_dir_path(__FILE__) . 'includes/class-wc-securitybank-webcollect-api.php';

/**
 * Add the gateway to WC Available Gateways
 */
function securitybank_add_to_gateways($gateways) {
    $gateways[] = 'WC_SecurityBank_WebCollect';
    return $gateways;
}
add_filter('woocommerce_payment_gateways', 'securitybank_add_to_gateways');

/**
 * Adds plugin page links
 */
function securitybank_plugin_links($links) {
    $plugin_links = array(
        '<a href="' . admin_url('admin.php?page=wc-settings&tab=checkout&section=securitybank_webcollect') . '">' . __('Configure', 'wc-securitybank-webcollect') . '</a>'
    );
    return array_merge($plugin_links, $links);
}
add_filter('plugin_action_links_' . plugin_basename(__FILE__), 'securitybank_plugin_links');

/**
 * Security Bank WebCollect Payment Gateway
 */
add_action('plugins_loaded', 'securitybank_webcollect_init', 11);

function securitybank_webcollect_init() {
    class WC_SecurityBank_WebCollect extends WC_Payment_Gateway {
        
        /**
         * Class properties
         */
        public $testmode;
        public $secret_key;
        public $publishable_key;
        public $enabled;
        public $title;
        public $description;
        public $icon;
        public $has_fields;
        public $method_title;
        public $method_description;
        
        /**
         * Constructor for the gateway
         */
        public function __construct() {
            $this->id = 'securitybank_webcollect';
            $this->icon = apply_filters('woocommerce_securitybank_icon', '');
            $this->has_fields = false;
            $this->method_title = __('Security Bank WebCollect', 'wc-securitybank-webcollect');
            $this->method_description = __('Accept payments via Security Bank WebCollect - Visa, Mastercard, GCash, PayMaya, and more.', 'wc-securitybank-webcollect');
            
            $this->init_form_fields();
            $this->init_settings();
            
            // Load settings
            $this->title = $this->get_option('title');
            $this->description = $this->get_option('description');
            $this->enabled = $this->get_option('enabled');
            $this->testmode = 'yes' === $this->get_option('testmode');
            
            // Load keys based on mode
            $this->secret_key = $this->testmode 
                ? $this->get_option('test_secret_key') 
                : $this->get_option('live_secret_key');
                
            $this->publishable_key = $this->testmode
                ? $this->get_option('test_publishable_key')
                : $this->get_option('live_publishable_key');
            
            // Actions
            add_action('woocommerce_update_options_payment_gateways_' . $this->id, array($this, 'process_admin_options'));
            add_action('woocommerce_api_securitybank_webcollect_webhook', array($this, 'handle_webhook'));
        }
        
        /**
         * Initialize Gateway Settings Form Fields
         */
        public function init_form_fields() {
            $this->form_fields = array(
                'enabled' => array(
                    'title' => __('Enable/Disable', 'wc-securitybank-webcollect'),
                    'type' => 'checkbox',
                    'label' => __('Enable Security Bank WebCollect', 'wc-securitybank-webcollect'),
                    'default' => 'yes'
                ),
                'title' => array(
                    'title' => __('Title', 'wc-securitybank-webcollect'),
                    'type' => 'text',
                    'description' => __('This controls the title which the user sees during checkout.', 'wc-securitybank-webcollect'),
                    'default' => __('Credit/Debit Card & E-Wallets', 'wc-securitybank-webcollect'),
                    'desc_tip' => true,
                ),
                'description' => array(
                    'title' => __('Description', 'wc-securitybank-webcollect'),
                    'type' => 'textarea',
                    'description' => __('This controls the description which the user sees during checkout.', 'wc-securitybank-webcollect'),
                    'default' => __('Pay securely via Credit/Debit Card, GCash, PayMaya, and other payment methods.', 'wc-securitybank-webcollect'),
                    'desc_tip' => true,
                ),
                'testmode' => array(
                    'title' => __('Test mode', 'wc-securitybank-webcollect'),
                    'type' => 'checkbox',
                    'label' => __('Enable Test Mode', 'wc-securitybank-webcollect'),
                    'default' => 'yes',
                    'description' => __('Place the payment gateway in test mode using test API keys.', 'wc-securitybank-webcollect'),
                ),
                'test_publishable_key' => array(
                    'title' => __('Test Publishable Key', 'wc-securitybank-webcollect'),
                    'type' => 'text',
                    'description' => __('Used for client-side operations (pk_test_...).', 'wc-securitybank-webcollect'),
                    'default' => '',
                    'placeholder' => 'pk_test_xxxxxxxxxx'
                ),
                'test_secret_key' => array(
                    'title' => __('Test Secret Key', 'wc-securitybank-webcollect'),
                    'type' => 'password',
                    'description' => __('Used for server-side operations (sk_test_...).', 'wc-securitybank-webcollect'),
                    'default' => '',
                    'placeholder' => 'sk_test_xxxxxxxxxx'
                ),
                'live_publishable_key' => array(
                    'title' => __('Live Publishable Key', 'wc-securitybank-webcollect'),
                    'type' => 'text',
                    'description' => __('Used for client-side operations (pk_live_...).', 'wc-securitybank-webcollect'),
                    'default' => '',
                    'placeholder' => 'pk_live_xxxxxxxxxx'
                ),
                'live_secret_key' => array(
                    'title' => __('Live Secret Key', 'wc-securitybank-webcollect'),
                    'type' => 'password',
                    'description' => __('Used for server-side operations (sk_live_...).', 'wc-securitybank-webcollect'),
                    'default' => '',
                    'placeholder' => 'sk_live_xxxxxxxxxx'
                ),
                'payment_methods' => array(
                    'title' => __('Payment Methods', 'wc-securitybank-webcollect'),
                    'type' => 'multiselect',
                    'description' => __('Select which payment methods to enable', 'wc-securitybank-webcollect'),
                    'options' => array(
                        'card' => __('Credit/Debit Cards', 'wc-securitybank-webcollect'),
                        'bpi' => __('BPI Online Banking', 'wc-securitybank-webcollect'),
                        'gcash' => __('GCash', 'wc-securitybank-webcollect'),
                        'paymaya' => __('PayMaya', 'wc-securitybank-webcollect'),
                        'unionbank' => __('UnionBank Online', 'wc-securitybank-webcollect')
                    ),
                    'default' => array('card', 'gcash', 'paymaya')
                ),
                'webhook_url' => array(
                    'title' => __('Webhook URL', 'wc-securitybank-webcollect'),
                    'type' => 'text',
                    'description' => __('Set this URL in your Security Bank WebCollect dashboard.', 'wc-securitybank-webcollect'),
                    'default' => add_query_arg('wc-api', 'securitybank_webcollect_webhook', home_url('/')),
                    'custom_attributes' => array('readonly' => 'readonly')
                )
            );
        }
        
        /**
         * Process the payment and return the result
         */
        public function process_payment($order_id) {
            $order = wc_get_order($order_id);
            
            try {
                $session = $this->create_checkout_session($order);
                
                if (isset($session['url'])) {
                    return array(
                        'result' => 'success',
                        'redirect' => $session['url']
                    );
                }
                throw new Exception(__('Payment gateway error: Could not create checkout session', 'wc-securitybank-webcollect'));
            } catch (Exception $e) {
                $this->log_error($e->getMessage());
                wc_add_notice($e->getMessage(), 'error');
                return false;
            }
        }
        
        private function validate_session_data($data) {
            // Check required fields
            $required = ['currency', 'payment_method_types', 'line_items', 'mode'];
            foreach ($required as $field) {
                if (!isset($data[$field]) || empty($data[$field])) {
                    throw new Exception("Missing required field: $field");
                }
            }
        
            // Validate currency format
            if ($data['currency'] !== 'php') {
                throw new Exception("Invalid currency. Must be 'php'");
            }
        
            // Validate line items
            foreach ($data['line_items'] as $item) {
                if (!isset($item['amount']) || !is_numeric($item['amount'])) {
                    throw new Exception("Invalid line item amount");
                }
                if ($item['amount'] < 100) {
                    throw new Exception("Minimum amount per item is ₱1.00");
                }
            }
        
            return true;
        }
        
        /**
         * Create checkout session with Security Bank WebCollect
         */
        private function create_checkout_session($order) {
            $customer_id = $this->get_customer_id($order);
            
            // Prepare line items
            $line_items = array();
            foreach ($order->get_items() as $item) {
                $product = $item->get_product();
                $image_id = $product->get_image_id();
                
                $line_items[] = array(
                    'name' => $item->get_name(),
                    'amount' => (int) round($product->get_price() * 100), // Amount in cents
                    'quantity' => $item->get_quantity(),
                );
            }
            
            // Add shipping if needed
            if ($order->get_shipping_total() > 0) {
                $line_items[] = array(
                    'name' => __('Shipping', 'wc-securitybank-webcollect'),
                    'amount' => (int) round($order->get_shipping_total() * 100), // Amount in cents
                    'quantity' => 1
                );
            }
        
            $data = array(
                'currency' => 'php', // Currency specified here
                'payment_method_types' => $this->get_option('payment_methods', array('card')),
                'line_items' => $line_items,
                'phone_number_collection' => true,
                'mode' => 'payment',
                'success_url' => $this->get_return_url($order),
                'cancel_url' => $order->get_cancel_order_url(),
                'client_reference_id' => 'wc-order-' . $order->get_id(),
                'metadata' => array(
                    'order_id' => $order->get_id(),
                    'customer_email' => $order->get_billing_email(),
                    'customer_name' => $order->get_billing_first_name() . ' ' . $order->get_billing_last_name()
                )
            );
        
            if ($customer_id) {
                $data['customer'] = $customer_id;
            }
        
            return WC_SecurityBank_WebCollect_API::create_session(
                $this->publishable_key,  // Updated to include publishable key
                $this->secret_key, 
                $data
            );
        }
        
        /**
         * Get or create Security Bank customer ID
         */
        private function get_customer_id($order) {
            $user_id = $order->get_user_id();
            if (!$user_id) return null;

            $customer_id = get_user_meta($user_id, '_securitybank_customer_id', true);
            if ($customer_id) return $customer_id;

            try {
                $customer_id = WC_SecurityBank_WebCollect_API::create_customer(
                    $this->publishable_key, // Updated to include publishable key
                    $this->secret_key,
                    $order->get_billing_email(),
                    $order->get_billing_first_name() . ' ' . $order->get_billing_last_name()
                );
                
                if ($customer_id) {
                    update_user_meta($user_id, '_securitybank_customer_id', $customer_id);
                    return $customer_id;
                }
            } catch (Exception $e) {
                $this->log_error('Customer creation failed: ' . $e->getMessage());
            }
            return null;
        }
        
        /**
         * Handle webhook notifications
         */
        public function handle_webhook() {
            $payload = file_get_contents('php://input');
            $event = json_decode($payload, true);

            if (json_last_error() !== JSON_ERROR_NONE) {
                status_header(400);
                exit;
            }

            try {
                if (isset($event['type']) && $event['type'] === 'payment_succeeded') {
                    $order_id = $event['data']['metadata']['order_id'] ?? 0;
                    $order = wc_get_order($order_id);
                    
                    if ($order && $order->get_status() === 'pending') {
                        $order->payment_complete();
                        $order->add_order_note(__('Payment completed via Security Bank WebCollect', 'wc-securitybank-webcollect'));
                    }
                }
            } catch (Exception $e) {
                $this->log_error('Webhook error: ' . $e->getMessage());
            }

            status_header(200);
            exit;
        }
        
        /**
         * Log errors
         */
        private function log_error($message) {
            $logger = wc_get_logger();
            $logger->error($message, array('source' => 'securitybank-webcollect'));
        }
    }
}
