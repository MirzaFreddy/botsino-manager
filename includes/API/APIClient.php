<?php
namespace BotsinoManager\API;

defined('ABSPATH') || exit;

class APIClient {
    
    protected $api_key;
    protected $api_url;
    
    public function __construct() {
        $this->api_key = get_option('botsino_api_key');
        $this->api_url = get_option('botsino_api_url');
    }
    
    public function get_user($email) {
        $url = $this->build_url(['email' => $email]);
        
        $response = wp_remote_get($url, [
            'timeout' => 60,
            'sslverify' => false
        ]);
        
        if (is_wp_error($response)) {
            error_log("Error getting user data: " . $response->get_error_message());
            return false;
        }
        
        $body = wp_remote_retrieve_body($response);
        $data = json_decode($body, true);
        
        if (isset($data['status']) && $data['status'] === 'success' && !empty($data['data'])) {
            foreach ($data['data'] as $user) {
                if (strtolower($user['email']) === strtolower($email)) {
                    return $user;
                }
            }
        }
        
        error_log("User not found: " . $email);
        return false;
    }
    
    public function get_user_by_phone($phone) {
        // نرمال‌سازی شماره
        require_once BOTSINO_PLUGIN_DIR . 'includes/Helpers/PhoneNormalizer.php';
        $normalized_phone = \BotsinoManager\Helpers\PhoneNormalizer::normalize($phone);
        
        // دریافت تمام کاربران
        $users = $this->get_users();
        
        if (empty($users)) {
            return false;
        }
        
        // جستجو در لیست کاربران
        foreach ($users as $user) {
            if (isset($user['username'])) {
                $user_phone = \BotsinoManager\Helpers\PhoneNormalizer::normalize($user['username']);
                if ($user_phone === $normalized_phone) {
                    error_log("User found by phone: " . $phone);
                    return $user;
                }
            }
        }
        
        error_log("User not found by phone: " . $phone);
        return false;
    }
    
    public function create_user($user_data) {
        $url = $this->build_url();
        
        error_log("Creating Botsino user: " . json_encode($user_data, JSON_UNESCAPED_UNICODE));
        
        return $this->request($url, $user_data, 'POST');
    }
    
    public function update_user($email, $user_data) {
        $url = $this->build_url(['user' => $email]);
        
        error_log("Updating Botsino user: " . json_encode($user_data, JSON_UNESCAPED_UNICODE));
        
        return $this->request($url, $user_data, 'PUT');
    }
    
    public function delete_user($email) {
        $url = $this->build_url(['user' => $email]);
        
        error_log("Deleting Botsino user: " . $email);
        
        $args = [
            'method' => 'DELETE',
            'headers' => ['Content-Type' => 'application/json'],
            'timeout' => 30
        ];
        
        $response = wp_remote_request($url, $args);
        
        if (is_wp_error($response)) {
            error_log("Delete Error: " . $response->get_error_message());
            return ['success' => false, 'response' => $response->get_error_message()];
        }
        
        $status_code = wp_remote_retrieve_response_code($response);
        $body = wp_remote_retrieve_body($response);
        
        error_log("Delete Response: Status {$status_code}, Body: {$body}");
        
        return [
            'success' => ($status_code >= 200 && $status_code < 300),
            'response' => $body
        ];
    }
    
    public function get_users($filters = []) {
        $params = array_merge(['status' => 'all', 'per_page' => 100], $filters);
        $url = $this->build_url($params);
        
        $response = wp_remote_get($url, ['timeout' => 30]);
        
        if (is_wp_error($response)) {
            error_log("Error fetching users: " . $response->get_error_message());
            return [];
        }
        
        $body = json_decode(wp_remote_retrieve_body($response), true);
        
        if (isset($body['status']) && $body['status'] === 'success' && !empty($body['data'])) {
            return $body['data'];
        }
        
        return [];
    }
    
    protected function request($url, $data, $method = 'POST') {
        $args = [
            'method' => $method,
            'body' => json_encode($data),
            'headers' => ['Content-Type' => 'application/json'],
            'timeout' => 60
        ];
        
        $response = wp_remote_request($url, $args);
        
        if (is_wp_error($response)) {
            error_log("API Error: " . $response->get_error_message());
            return [
                'success' => false,
                'response' => $response->get_error_message()
            ];
        }
        
        $status_code = wp_remote_retrieve_response_code($response);
        $body = wp_remote_retrieve_body($response);
        
        error_log("API Response ({$status_code}): {$body}");
        
        return [
            'success' => $status_code === 200,
            'response' => $body,
            'status_code' => $status_code
        ];
    }
    
    protected function build_url($params = []) {
        $params['api_key'] = $this->api_key;
        return add_query_arg($params, $this->api_url);
    }
}
