<?php
if (!defined('ABSPATH')) {
    exit;
}

class WC_SecurityBank_WebCollect_API {
    const API_BASE_URL = 'https://pay.securitybankcollect.com/api';
    const API_VERSION = 'v2'; // Add version as a constant

    /**
     * Create checkout session
     */
    public static function create_session($secret_key, $data) {
        $url = self::API_BASE_URL . '/' . self::API_VERSION . '/sessions';

        $args = array(
            'headers' => array(
                'Authorization' => 'Basic ' . base64_encode($secret_key . ':'),
                'Content-Type' => 'application/json',
                'Accept' => 'application/json'
            ),
            'body' => json_encode($data),
            'timeout' => 30
        );

        self::log_request('POST', $url, $data);

        $response = wp_remote_post($url, $args);

        if (is_wp_error($response)) {
            self::log_error('Request failed: ' . $response->get_error_message());
            throw new Exception(__('An error occurred while processing your request. Please try again.', 'wc-securitybank-webcollect'));
        }

        $response_code = wp_remote_retrieve_response_code($response);
        $response_body = json_decode(wp_remote_retrieve_body($response), true);

        self::log_response($response_code, $response_body);

        if ($response_code !== 200 && $response_code !== 201) {
            $error_message = $response_body['error']['message'] ?? __('API request failed', 'wc-securitybank-webcollect');
            self::log_error('API Error: ' . $error_message);
            throw new Exception($error_message);
        }

        return $response_body;
    }

    /**
     * Create customer
     */
    public static function create_customer($secret_key, $email, $name) {
        $url = self::API_BASE_URL . '/' . self::API_VERSION . '/customers';

        // Ensure description is not empty
        if (empty($name)) {
            throw new Exception(__('Description is required to create a customer.', 'wc-securitybank-webcollect'));
        }

        $data = array(
            'email' => $email,
            'description' => $name,  // Use name as description
            'metadata' => array()
        );

        $args = array(
            'headers' => array(
                'Authorization' => 'Basic ' . base64_encode($secret_key . ':'),
                'Content-Type' => 'application/json'
            ),
            'body' => json_encode($data),
            'timeout' => 30
        );

        self::log_request('POST', $url, $data);

        $response = wp_remote_post($url, $args);

        if (is_wp_error($response)) {
            self::log_error('Request failed: ' . $response->get_error_message());
            throw new Exception(__('An error occurred while creating the customer. Please try again.', 'wc-securitybank-webcollect'));
        }

        $response_code = wp_remote_retrieve_response_code($response);
        $response_body = json_decode(wp_remote_retrieve_body($response), true);

        self::log_response($response_code, $response_body);

        if ($response_code !== 200) {
            $error_message = $response_body['error']['message'] ?? __('Customer creation failed', 'wc-securitybank-webcollect');
            self::log_error('API Error: ' . $error_message);
            throw new Exception($error_message);
        }

        return $response_body['id'] ?? null;
    }

    /**
     * Log API requests
     */
    private static function log_request($method, $url, $data) {
        $logger = wc_get_logger();
        $logger->debug("API Request: $method $url\n" . json_encode($data, JSON_PRETTY_PRINT),
                       array('source' => 'securitybank-webcollect'));
    }

    /**
     * Log API responses
     */
    private static function log_response($code, $body) {
        $logger = wc_get_logger();
        $logger->debug("API Response: $code\n" . json_encode($body, JSON_PRETTY_PRINT),
                       array('source' => 'securitybank-webcollect'));

        if ($code !== 200) {
            $logger->error("API Error: $code\n" . json_encode($body, JSON_PRETTY_PRINT),
                           array('source' => 'securitybank-webcollect'));
        }
    }

    /**
     * Log errors
     */
    private static function log_error($message) {
        $logger = wc_get_logger();
        $logger->error($message, array('source' => 'securitybank-api'));
    }
}
