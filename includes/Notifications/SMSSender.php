<?php
namespace BotsinoManager\Notifications;

defined('ABSPATH') || exit;

use BotsinoManager\Helpers\PhoneNormalizer;

class SMSSender {
    
    protected $api_key;
    protected $username;
    protected $password;
    protected $sender;
    protected $pattern_code;
    
    public function __construct() {
        $this->api_key = get_option('botsino_sms_api_key');
        $this->username = get_option('botsino_sms_username');
        $this->password = get_option('botsino_sms_password');
        $this->sender = get_option('botsino_sms_sender');
        $this->pattern_code = get_option('botsino_pattern_code');
    }
    
    public function send_pattern($phone, $name, $username, $password, $pattern_code = null) {
        if (empty($pattern_code)) {
            $pattern_code = $this->pattern_code;
        }
        
        if (empty($pattern_code)) {
            error_log("Pattern code not configured");
            return false;
        }
        
        $phone = PhoneNormalizer::normalize($phone);
        
        if (!empty($this->api_key)) {
            return $this->send_via_api_key($phone, $name, $username, $password, $pattern_code);
        }
        
        if (empty($this->username) || empty($this->password)) {
            error_log("SMS credentials not configured");
            return false;
        }
        
        $data = [
            "username" => $this->username,
            "password" => $this->password,
            "to" => $phone,
            "from" => $this->sender,
            "pattern_code" => $pattern_code,
            "params" => json_encode([
                "name" => $name,
                "username" => $username,
                "pass" => $password
            ])
        ];
        
        $response = wp_remote_post("https://edge.ippanel.com/v1/v1/sms/pattern/send", [
            'method' => 'POST',
            'body' => json_encode($data),
            'headers' => ['Content-Type' => 'application/json'],
            'timeout' => 15
        ]);
        
        if (is_wp_error($response)) {
            error_log("SMS send failed: " . $response->get_error_message());
            return false;
        }
        
        return wp_remote_retrieve_response_code($response) === 200;
    }
    
    protected function send_via_api_key($phone, $name, $username, $password, $pattern_code) {
        $request_data = [
            "sending_type" => "pattern",
            "from_number" => $this->sender,
            "code" => $pattern_code,
            "recipients" => [$phone],
            "params" => [
                "name" => $name,
                "username" => $username,
                "pass" => $password
            ]
        ];
        
        $response = wp_remote_post('https://edge.ippanel.com/v1/api/send', [
            'method' => 'POST',
            'headers' => [
                'Content-Type' => 'application/json',
                'Authorization' => $this->api_key
            ],
            'body' => json_encode($request_data),
            'timeout' => 15
        ]);
        
        if (is_wp_error($response)) {
            return false;
        }
        
        $status_code = wp_remote_retrieve_response_code($response);
        $body = json_decode(wp_remote_retrieve_body($response), true);
        
        return $status_code === 200 && isset($body['meta']['status']) && $body['meta']['status'] === true;
    }
    
    public function send_reminder($phone, $message) {
        $phone = PhoneNormalizer::normalize($phone);
        
        if (!empty($this->api_key)) {
            $request_data = [
                "sending_type" => "direct",
                "from_number" => $this->sender,
                "recipients" => [$phone],
                "message" => $message
            ];
            
            $response = wp_remote_post('https://edge.ippanel.com/v1/api/send', [
                'method' => 'POST',
                'headers' => [
                    'Content-Type' => 'application/json',
                    'Authorization' => $this->api_key
                ],
                'body' => json_encode($request_data),
                'timeout' => 15
            ]);
            
            return !is_wp_error($response) && wp_remote_retrieve_response_code($response) === 200;
        }
        
        $data = [
            "username" => $this->username,
            "password" => $this->password,
            "to" => $phone,
            "from" => $this->sender,
            "message" => $message
        ];
        
        $response = wp_remote_post("https://edge.ippanel.com/v1/v1/sms/send", [
            'method' => 'POST',
            'body' => json_encode($data),
            'headers' => ['Content-Type' => 'application/json'],
            'timeout' => 15
        ]);
        
        return !is_wp_error($response) && wp_remote_retrieve_response_code($response) === 200;
    }
}
