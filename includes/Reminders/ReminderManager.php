<?php
namespace BotsinoManager\Reminders;

defined('ABSPATH') || exit;

use BotsinoManager\Config\Constants;
use BotsinoManager\Notifications\SMSSender;
use BotsinoManager\Notifications\WhatsAppSender;

class ReminderManager {
    
    protected $wpdb;
    protected $reminders_table;
    protected $expirations_table;
    protected $coupon_generator;
    
    public function __construct() {
        global $wpdb;
        $this->wpdb = $wpdb;
        $this->reminders_table = $wpdb->prefix . Constants::REMINDERS_TABLE;
        $this->expirations_table = $wpdb->prefix . Constants::EXPIRATIONS_TABLE;
        $this->coupon_generator = new CouponGenerator();
    }
    
    public function check_expirations() {
        date_default_timezone_set('Asia/Tehran');
        
        $today = date('Y-m-d');
        error_log("Starting expiration check for Tehran date: $today");
        
        $active_reminders = $this->wpdb->get_results(
            "SELECT * FROM {$this->reminders_table} WHERE active = 1"
        );
        
        if (empty($active_reminders)) {
            error_log("No active reminders to process");
            return;
        }
        
        foreach ($active_reminders as $reminder) {
            $this->process_reminder($reminder, $today);
        }
        
        error_log("Expiration check completed successfully");
    }
    
    protected function process_reminder($reminder, $today) {
        $target_date = date('Y-m-d', strtotime("+{$reminder->days_before} days"));
        $column_name = "last_reminder_{$reminder->days_before}";
        
        $column_exists = $this->wpdb->get_var($this->wpdb->prepare(
            "SHOW COLUMNS FROM {$this->expirations_table} LIKE %s",
            $column_name
        ));
        
        if (!$column_exists) {
            error_log("Column $column_name does not exist. Skipping reminder {$reminder->id}");
            return;
        }
        
        error_log("Processing reminder #{$reminder->id} ({$reminder->days_before} days before) for expiration date: $target_date");
        
        $query = $this->wpdb->prepare(
            "SELECT * FROM {$this->expirations_table} 
            WHERE expiration_date = %s 
            AND plan_type = %s
            AND `{$column_name}` = 0",
            $target_date,
            $reminder->plan_type
        );
        
        $users = $this->wpdb->get_results($query);
        
        if (empty($users)) {
            error_log("No users found for reminder #{$reminder->id} with expiration date $target_date");
            return;
        }
        
        error_log("Users to process for reminder #{$reminder->id}: " . count($users));
        
        foreach ($users as $user) {
            $this->send_reminder_to_user($user, $reminder, $column_name);
        }
    }
    
    protected function send_reminder_to_user($user, $reminder, $column_name) {
        error_log("Processing user: {$user->user_email}");
        
        $coupon_code_display = '';
        $plan_name = ($user->plan_type === 'free') ? 'رایگان' : 'پولی';
        $product_ids = $reminder->product_ids ?? '';
        
        if ($reminder->coupon_type === 'auto') {
            $auto_settings = $reminder->auto_settings;
            $generated_coupon = $this->coupon_generator->generate_auto($auto_settings, $user->user_email, $product_ids);
            if ($generated_coupon) {
                $coupon_code_display = $generated_coupon;
            }
        } elseif ($reminder->coupon_type === 'fixed') {
            $coupon_to_use = $reminder->coupon_code;
            if (empty($coupon_to_use)) {
                $coupon_to_use = get_option('botsino_global_renewal_coupon');
            }
            if (!empty($coupon_to_use)) {
                $this->coupon_generator->create_renewal($coupon_to_use, $user->user_email, $product_ids);
                $coupon_code_display = $coupon_to_use;
            }
        }
        
        $product_names_list = '';
        if (!empty($product_ids)) {
            $ids = explode(',', $product_ids);
            $product_names = [];
            
            foreach ($ids as $id) {
                $product = wc_get_product($id);
                if ($product) {
                    $product_names[] = $product->get_name();
                }
            }
            
            $product_names_list = implode('، ', $product_names);
        }
        
        $message = str_replace(
            ['{name}', '{expiration_date}', '{coupon_code}', '{site_url}', '{plan_name}', '{products_list}'],
            [
                $user->fullname,
                date('d-m-Y', strtotime($user->expiration_date)),
                $coupon_code_display,
                site_url(),
                $plan_name,
                $product_names_list
            ],
            $reminder->message_text
        );
        
        $result = false;
        if ($reminder->message_type === 'sms') {
            error_log("Sending SMS reminder to {$user->phone}");
            $sms = new SMSSender();
            $result = $sms->send_reminder($user->phone, $message);
        } else {
            error_log("Sending WhatsApp reminder to {$user->phone}");
            $whatsapp = new WhatsAppSender();
            $result = $whatsapp->send_reminder($user->phone, $message);
        }
        
        if ($result) {
            error_log("Reminder sent successfully");
            
            $update_result = $this->wpdb->update(
                $this->expirations_table,
                [$column_name => 1],
                ['id' => $user->id]
            );
            
            if ($update_result !== false) {
                error_log("Reminder status updated for user");
            } else {
                error_log("Error updating reminder status: " . $this->wpdb->last_error);
            }
            
            $this->log_message(
                $user->phone,
                $reminder->message_type,
                $message,
                'success',
                "Reminder {$reminder->days_before} days before expiration | Coupon: $coupon_code_display"
            );
        } else {
            error_log("Error sending reminder");
            $this->log_message(
                $user->phone,
                $reminder->message_type,
                $message,
                'failed',
                "Error sending reminder {$reminder->days_before} days before expiration"
            );
        }
    }
    
    protected function log_message($phone, $type, $content, $status, $notes = '') {
        $logs_table = $this->wpdb->prefix . Constants::MESSAGE_LOGS_TABLE;
        
        $this->wpdb->insert($logs_table, [
            'phone' => $phone,
            'type' => $type,
            'content' => $content,
            'status' => $status,
            'notes' => $notes,
            'created_at' => current_time('mysql')
        ]);
    }
}
