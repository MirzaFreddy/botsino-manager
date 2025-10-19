<?php
namespace BotsinoManager\Queue;

defined('ABSPATH') || exit;

use BotsinoManager\Config\Constants;
use BotsinoManager\Users\UserManager;
use BotsinoManager\Helpers\PhoneNormalizer;
use BotsinoManager\Helpers\DateHelper;

class QueueManager {
    
    protected $wpdb;
    protected $table_name;
    protected $user_manager;
    protected $message_queue;
    
    public function __construct() {
        global $wpdb;
        $this->wpdb = $wpdb;
        $this->table_name = $wpdb->prefix . Constants::QUEUE_TABLE;
        $this->user_manager = new UserManager();
        $this->message_queue = new MessageQueue();
    }
    
    public function add_order_to_queue($order_id) {
        wp_cache_set('current_processing_order_id', $order_id, 'botsino', 300);
        
        $order = wc_get_order($order_id);
        error_log("Processing order #{$order_id} for Botsino integration");
        
        $processed_by_ultrabot = $order->get_meta('_botsino_processed_by_ultrabot');
        $processing_by_ultrabot = $order->get_meta('_processing_by_ultrabot');
        
        if ($processed_by_ultrabot === 'yes' || $processing_by_ultrabot === 'yes') {
            error_log("Skipping - Already processed by Ultrabot");
            return;
        }
        
        $order->update_meta_data('_processing_by_botsino', 'yes');
        $order->save();
        
        if (!$order) {
            error_log("Order not found");
            return;
        }
        
        $email = $order->get_billing_email();
        $phone = $order->get_billing_phone();
        $fullname = trim($order->get_billing_first_name() . ' ' . $order->get_billing_last_name());
        
        $plan_id = $this->extract_plan_from_order($order);
        
        if ($plan_id === 1) {
            error_log("Free plan order - skipping as it's created via popup form");
            return;
        }
        
        if ($plan_id === 0) {
            error_log("No valid plan found");
            return;
        }
        
        $apply_mode = $order->get_meta('_apply_mode') ?: 'add';
        $prev_days = (int)$order->get_meta('_prev_days') ?: 0;
        
        $plan_duration = Constants::SKU_DURATION_MAPPING['plan' . $plan_id] ?? 12;
        $expiration_date = DateHelper::calculateExpiration($email, $plan_duration, $apply_mode, $prev_days);
        
        $result = $this->user_manager->create(
            $email,
            $phone,
            $plan_id,
            $fullname,
            2,
            $expiration_date->format('Y-m-d H:i:s'),
            true
        );
        
        if ($result['success']) {
            error_log("User #{$email} created successfully - plan: {$plan_id} - phone: {$phone}");
            
            $order->update_meta_data('_botsino_plan_id', $plan_id);
            $order->update_meta_data('_botsino_expiration', $expiration_date->format('Y-m-d H:i:s'));
            $order->save();
            
            if (function_exists('send_user_notification')) {
                $notification_result = send_user_notification(
                    $phone,
                    $fullname,
                    $result['username'],
                    $result['password']
                );
                
                if ($notification_result) {
                    error_log("Notification sent successfully to: {$phone}");
                }
            }
        } else {
            error_log("Failed to create user: " . $result['error']);
            
            $this->wpdb->insert($this->table_name, [
                'order_id' => $order_id,
                'user_data' => serialize([
                    'email' => $email,
                    'phone' => $phone,
                    'fullname' => $fullname,
                    'plan_id' => $plan_id,
                    'apply_mode' => $apply_mode,
                    'prev_days' => $prev_days
                ]),
                'attempts' => 0,
                'next_attempt' => date('Y-m-d H:i:s', strtotime('+30 minutes')),
                'status' => 'pending',
                'created_at' => current_time('mysql')
            ]);
            
            error_log("Order #{$order_id} added to Botsino queue");
        }
    }
    
    public function process() {
        $current_time_tehran = date('Y-m-d H:i:s');
        
        $this->message_queue->process();
        
        $items = $this->wpdb->get_results(
            $this->wpdb->prepare(
                "SELECT * FROM {$this->table_name} 
                WHERE status IN ('pending','failed') 
                AND attempts < %d 
                AND (next_attempt IS NULL OR next_attempt <= %s)
                ORDER BY id ASC 
                LIMIT 20",
                Constants::MAX_ATTEMPTS,
                $current_time_tehran
            ),
            ARRAY_A
        );
        
        if (empty($items)) return 0;
        
        $processed = 0;
        foreach ($items as $item) {
            $this->wpdb->update($this->table_name, [
                'status' => 'processing',
                'last_attempt' => current_time('mysql')
            ], ['id' => $item['id']]);
            
            $user_data = unserialize($item['user_data']);
            $attempts = $item['attempts'] + 1;
            
            // بررسی وجود کاربر
            require_once BOTSINO_PLUGIN_DIR . 'includes/API/APIClient.php';
            $api_client = new \BotsinoManager\API\APIClient();
            $current_user = $api_client->get_user($user_data['email']);
            
            if ($current_user) {
                $this->message_queue->add(
                    $user_data['phone'],
                    $user_data['fullname'],
                    $current_user['username'],
                    $current_user['password'] ?? '',
                    'existing'
                );
                
                $this->wpdb->update($this->table_name, [
                    'status' => 'completed',
                    'attempts' => $attempts,
                    'processed_at' => current_time('mysql')
                ], ['id' => $item['id']]);
                continue;
            }
            
            $result = $this->user_manager->create(
                $user_data['email'],
                $user_data['phone'],
                $user_data['plan_id'],
                $user_data['fullname'],
                2,
                null,
                true
            );
            
            if ($result['success']) {
                $this->wpdb->update($this->table_name, [
                    'status' => 'completed',
                    'attempts' => $attempts,
                    'processed_at' => current_time('mysql'),
                    'response' => $result['response']
                ], ['id' => $item['id']]);
                
                $processed++;
            } else {
                $this->wpdb->update($this->table_name, [
                    'status' => 'failed',
                    'attempts' => $attempts,
                    'next_attempt' => date('Y-m-d H:i:s', strtotime('+30 seconds')),
                    'response' => $result['response']
                ], ['id' => $item['id']]);
            }
        }
        
        error_log("Botsino queue processed: {$processed} items succeeded");
        return $processed;
    }
    
    protected function extract_plan_from_order($order) {
        $plan_id = 0;
        
        foreach ($order->get_items() as $item) {
            $product = $item->get_product();
            if (!$product) continue;
            
            $sku = $product->get_sku();
            if ($sku && preg_match('/^plan(\d+)$/i', $sku, $matches)) {
                $plan_id = (int)$matches[1];
                break;
            }
        }
        
        return $plan_id;
    }
    
    public function cleanup() {
        $twenty_four_hours_ago = date('Y-m-d H:i:s', strtotime('-24 hours'));
        
        $deleted = $this->wpdb->query(
            $this->wpdb->prepare(
                "DELETE FROM {$this->table_name} 
                WHERE status = 'completed' 
                AND processed_at < %s",
                $twenty_four_hours_ago
            )
        );
        
        if ($deleted) {
            error_log("Cleaned up {$deleted} completed queue items (older than 24 hours)");
        }
    }
}
