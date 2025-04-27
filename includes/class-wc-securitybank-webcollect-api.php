<?php
if (!defined('ABSPATH')) {
    exit;
}

class WC_SecurityBank_WebCollect_API {
    const API_BASE_URL = 'https://pay.securitybankcollect.com/api';

    /**
     * Create checkout session
     */
    public static function create_session($publishable_key, $secret_key, $data) {
        $url = self::API_BASE_URL . '/v2/sessions';

        $args = array(
            'headers' => array(
                'Authorization' => 'Basic ' . base64_encode($publishable_key . ':' . $secret_key),
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
            throw new Exception($response->get_error_message());
        }

        $response_code = wp_remote_retrieve_response_code($response);
        $response_body = json_decode(wp_remote_retrieve_body($response), true);

        self::log_response($response_code, $response_body);

        if ($response_code !== 200) {
            $error_message = $response_body['error']['message'] ?? 'API request failed';
            self::log_error('API Error: ' . $error_message);
            throw new Exception($error_message);
        }

        return $response_body;
    }

    /**
     * Create customer
     */
    public static function create_customer($publishable_key, $secret_key, $email, $name) {
        $url = self::API_BASE_URL . '/v2/customers';

        $data = array(
            'email' => $email,
            'description' => $name,
            'metadata' => array()
        );

        $args = array(
            'headers' => array(
                'Authorization' => 'Basic ' . base64_encode($publishable_key . ':' . $secret_key),
                'Content-Type' => 'application/json'
            ),
            'body' => json_encode($data),
            'timeout' => 30
        );

        self::log_request('POST', $url, $data);

        $response = wp_remote_post($url, $args);

        if (is_wp_error($response)) {
            self::log_error('Request failed: ' . $response->get_error_message());
            throw new Exception($response->get_error_message());
        }

        $response_code = wp_remote_retrieve_response_code($response);
        $response_body = json_decode(wp_remote_retrieve_body($response), true);

        self::log_response($response_code, $response_body);

        if ($response_code !== 200) {
            $error_message = $response_body['error']['message'] ?? 'Customer creation failed';
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
                       array('source' => 'securitybank-api'));
    }

    /**
     * Log API responses
     */
    private static function log_response($code, $body) {
        $logger = wc_get_logger();
        $logger->debug("API Response: $code\n" . json_encode($body, JSON_PRETTY_PRINT), 
                       array('source' => 'securitybank-api'));

        if ($code !== 200) {
            $logger->error("API Error: $code\n" . json_encode($body, JSON_PRETTY_PRINT), 
                           array('source' => 'securitybank-api'));
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
