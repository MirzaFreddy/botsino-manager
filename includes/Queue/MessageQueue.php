<?php
namespace BotsinoManager\Queue;

defined('ABSPATH') || exit;

use BotsinoManager\Config\Constants;
use BotsinoManager\Helpers\PhoneNormalizer;

class MessageQueue {
    
    protected $wpdb;
    protected $table_name;
    
    public function __construct() {
        global $wpdb;
        $this->wpdb = $wpdb;
        $this->table_name = $wpdb->prefix . Constants::MESSAGE_QUEUE_TABLE;
    }
    
    public function add($phone, $fullname, $username, $password, $type = 'new') {
        date_default_timezone_set('Asia/Tehran');
        $scheduled_time = date('Y-m-d H:i:s', strtotime('+10 seconds'));
        date_default_timezone_set('UTC');
        
        error_log("Adding to message queue - Phone: {$phone}, Type: {$type}");
        
        wp_cache_delete('current_processing_order', 'botsino');
        wp_cache_delete('last_processed_phone', 'botsino');
        
        $normalized_phone = PhoneNormalizer::normalize($phone);
        
        $current_order_phone = $this->get_current_order_phone();
        if ($current_order_phone && $normalized_phone !== $current_order_phone) {
            error_log("Phone mismatch - correcting from {$normalized_phone} to {$current_order_phone}");
            $normalized_phone = $current_order_phone;
        }
        
        if ($type === 'update' && empty($password)) {
            error_log("Existing user - sending update message without password");
            $message = "🎉 پلن شما با موفقیت به‌روزرسانی شد!\n\n" .
                       "👤 نام کاربری: $username\n" .
                       "🌐 آدرس سایت: https://botsino.ir\n" .
                       "📚 آموزش‌ها: https://botsino.ir/blog_internal";
            
            return $this->send_whatsapp_update_message($phone, $fullname, $message);
        }
        
        if (empty($password)) {
            error_log("Password empty - sending message without password");
            $message = "🎉 حساب Botsino شما آماده است!\n\n" .
                       "👤 نام کاربری: $username\n" .
                       "🔑 رمز عبور: از رمز قبلی خود استفاده کنید\n" .
                       "🌐 آدرس سایت: https://botsino.ir\n" .
                       "📚 آموزش‌ها: https://botsino.ir/blog_internal";
            
            return $this->send_whatsapp_update_message($phone, $fullname, $message);
        }
        
        $result = $this->wpdb->insert($this->table_name, [
            'phone' => PhoneNormalizer::normalize($phone),
            'fullname' => $fullname,
            'username' => $username,
            'password' => $password,
            'message_type' => 'credentials',
            'user_type' => $type,
            'scheduled_at' => $scheduled_time,
            'status' => 'pending',
            'created_at' => current_time('mysql')
        ]);
        
        if ($result) {
            error_log("Message added to queue for: {$phone} - scheduled: {$scheduled_time}");
        } else {
            error_log("Error adding message to queue: " . $this->wpdb->last_error);
        }
        
        return $result;
    }
    
    public function process() {
        $current_time = current_time('mysql');
        
        $messages = $this->wpdb->get_results(
            $this->wpdb->prepare(
                "SELECT * FROM {$this->table_name} 
                 WHERE status = 'pending' 
                 AND attempts < 3 
                 AND (scheduled_at IS NULL OR scheduled_at <= %s)
                 ORDER BY created_at ASC 
                 LIMIT 10",
                $current_time
            ),
            ARRAY_A
        );
        
        if (empty($messages)) return 0;
        
        $processed = 0;
        foreach ($messages as $message) {
            $this->wpdb->update($this->table_name, [
                'status' => 'processing'
            ], ['id' => $message['id']]);
            
            require_once BOTSINO_PLUGIN_DIR . 'includes/Notifications/WhatsAppSender.php';
            $whatsapp = new \BotsinoManager\Notifications\WhatsAppSender();
            $result = $whatsapp->send_credentials(
                $message['phone'],
                $message['fullname'],
                $message['username'],
                $message['password']
            );
            
            if ($result) {
                $this->wpdb->update($this->table_name, [
                    'status' => 'completed',
                    'processed_at' => current_time('mysql')
                ], ['id' => $message['id']]);
                
                error_log("Message sent successfully: {$message['phone']}");
                $processed++;
            } else {
                $new_attempts = $message['attempts'] + 1;
                $next_try = date('Y-m-d H:i:s', strtotime('+2 minutes'));
                
                $this->wpdb->update($this->table_name, [
                    'status' => ($new_attempts >= 3) ? 'failed' : 'pending',
                    'attempts' => $new_attempts,
                    'scheduled_at' => $next_try
                ], ['id' => $message['id']]);
                
                error_log("Error sending message: {$message['phone']} - attempt {$new_attempts}");
            }
            
            sleep(1);
        }
        
        error_log("Message queue processed: {$processed} messages sent");
        return $processed;
    }
    
    public function cleanup() {
        $twenty_four_hours_ago = date('Y-m-d H:i:s', strtotime('-24 hours'));
        
        $deleted = $this->wpdb->query(
            $this->wpdb->prepare(
                "DELETE FROM {$this->table_name} 
                WHERE status = 'sent' 
                AND scheduled_at < %s",
                $twenty_four_hours_ago
            )
        );
        
        if ($deleted) {
            error_log("Cleaned up {$deleted} sent messages from queue (older than 24 hours)");
        }
        
        $one_week_ago = date('Y-m-d H:i:s', strtotime('-1 week'));
        $deleted_failed = $this->wpdb->query(
            $this->wpdb->prepare(
                "DELETE FROM {$this->table_name} 
                WHERE status = 'failed' 
                AND scheduled_at < %s",
                $one_week_ago
            )
        );
        
        if ($deleted_failed) {
            error_log("Cleaned up {$deleted_failed} failed messages from queue (older than 1 week)");
        }
    }
    
    protected function get_current_order_phone() {
        $current_order_id = wp_cache_get('current_processing_order_id', 'botsino');
        
        if ($current_order_id) {
            $order = wc_get_order($current_order_id);
            if ($order) {
                $phone = $order->get_billing_phone();
                $normalized_phone = PhoneNormalizer::normalize($phone);
                error_log("Current order phone from cache: {$normalized_phone}");
                return $normalized_phone;
            }
        }
        
        error_log("No current order found in cache");
        return '';
    }
    
    protected function send_whatsapp_update_message($phone, $fullname, $message) {
        require_once BOTSINO_PLUGIN_DIR . 'includes/Notifications/WhatsAppSender.php';
        $whatsapp = new \BotsinoManager\Notifications\WhatsAppSender();
        return $whatsapp->send_text($phone, $message);
    }
}
