<?php
namespace BotsinoManager\Users;

defined('ABSPATH') || exit;

use BotsinoManager\API\APIClient;
use BotsinoManager\Helpers\PhoneNormalizer;
use BotsinoManager\Config\Constants;

class UserManager {
    
    protected $api;
    protected $expiration_manager;
    
    public function __construct() {
        $this->api = new APIClient();
        $this->expiration_manager = new ExpirationManager();
    }
    
    public function create($email, $phone, $plan_id, $fullname, $status = 2, $expiration_date = null, $skip_instant_message = false) {
        require_once BOTSINO_PLUGIN_DIR . 'includes/Helpers/Logger.php';
        
        // بررسی کاربر تکراری بر اساس شماره موبایل (مهم‌تر از ایمیل)
        $existing_user = $this->api->get_user_by_phone($phone);
        
        if ($existing_user) {
            \BotsinoManager\Helpers\Logger::warning(
                'user_creation',
                'شماره موبایل قبلاً استفاده شده است',
                ['phone' => $phone, 'email' => $email, 'existing_email' => $existing_user['email'] ?? 'N/A']
            );
            
            // ارسال پیام به کاربر که قبلاً با این شماره ثبت‌نام کرده
            $this->send_duplicate_user_message($phone, $fullname);
            
            return [
                'success' => false,
                'error' => 'duplicate_phone',
                'message' => 'این شماره موبایل قبلاً برای دریافت تست رایگان استفاده شده است',
                'response' => 'Phone number already exists'
            ];
        }
        
        \BotsinoManager\Helpers\Logger::info(
            'user_creation',
            'شروع ساخت کاربر جدید',
            ['email' => $email, 'phone' => $phone, 'plan_id' => $plan_id]
        );
        
        // ارسال پیام تایید فقط برای کاربران جدید
        if (!$skip_instant_message) {
            $this->send_instant_confirmation($phone, $fullname);
        }
        
        $api_plan_id = Constants::REVERSE_PLAN_MAPPING[$plan_id] ?? 2;
        $phone = PhoneNormalizer::normalize($phone);
        
        $username = $phone;
        $password = wp_generate_password(12, false);
        
        $duration = Constants::PLAN_DURATIONS[$api_plan_id] ?? 12;
        
        if ($expiration_date instanceof \DateTime) {
            $expiration_date = $expiration_date->format('Y-m-d');
        }
        
        if (!$expiration_date) {
            $expiration_date = ($plan_id == 1) 
                ? date('Y-m-d', strtotime('+7 days')) 
                : date('Y-m-d', strtotime("+{$duration} months"));
        }
        
        $data = [
            "username" => $username,
            "fullname" => $fullname,
            "email" => $email,
            "whatsapp" => $phone,
            "password" => $password,
            "plan_id" => $api_plan_id,
            "expired_date" => $expiration_date,
            "status" => $status,
            "is_admin" => 0,
            "timezone" => "Asia/Tehran"
        ];
        
        $result = $this->api->create_user($data);
        
        if ($result['success']) {
            \BotsinoManager\Helpers\Logger::success(
                'user_creation',
                'کاربر با موفقیت ساخته شد',
                ['email' => $email, 'username' => $username, 'plan_id' => $plan_id]
            );
            
            // ارسال فوری پیام اطلاعات ورود (بدون صف)
            $this->send_credentials_immediately($phone, $fullname, $username, $password);
            
            $this->expiration_manager->update($email, $phone, $fullname, $plan_id, $expiration_date);
            
            return [
                'success' => true,
                'username' => $username,
                'password' => $password,
                'response' => $result['response'] ?? 'User created successfully'
            ];
        }
        
        \BotsinoManager\Helpers\Logger::error(
            'user_creation',
            'خطا در ساخت کاربر',
            ['email' => $email, 'error' => $result['response']]
        );
        
        return [
            'success' => false,
            'error' => $result['response'],
            'response' => $result['response']
        ];
    }
    
    public function update($email, $phone, $plan_id, $fullname, $current_user, $expiration_date = null) {
        $phone = PhoneNormalizer::normalize($phone);
        
        if ($expiration_date instanceof \DateTime) {
            $expiration_date = $expiration_date->format('Y-m-d');
        }
        
        if (!$expiration_date) {
            $duration = Constants::PLAN_DURATIONS[$plan_id] ?? 12;
            $current_expiration = $current_user['expired_date'] ?? null;
            $current_timestamp = 0;
            
            if ($current_expiration) {
                if (is_string($current_expiration)) {
                    $current_timestamp = strtotime($current_expiration);
                } elseif (is_numeric($current_expiration)) {
                    $current_timestamp = (int)$current_expiration;
                }
            }
            
            if ($current_timestamp > time()) {
                $expiration_date = date('Y-m-d', strtotime("+{$duration} months", $current_timestamp));
            } else {
                $expiration_date = date('Y-m-d', strtotime("+{$duration} months"));
            }
            
            error_log("Calculated expiration: {$duration} months - new date: {$expiration_date}");
        }
        
        $api_plan_id = Constants::REVERSE_PLAN_MAPPING[$plan_id] ?? 2;
        
        $data = [
            "id" => $current_user['id'],
            "fullname" => $fullname,
            "email" => $email,
            "whatsapp" => $phone,
            "plan_id" => $api_plan_id,
            "expired_date" => $expiration_date,
            "status" => $current_user['status'] ?? 2,
            "is_admin" => $current_user['is_admin'] ?? 0,
            "timezone" => $current_user['timezone'] ?? "Asia/Tehran"
        ];
        
        $result = $this->api->update_user($email, $data);
        
        if ($result['success']) {
            error_log("User updated successfully");
            
            $this->expiration_manager->update($email, $phone, $fullname, $plan_id, $expiration_date);
            
            return [
                'success' => true,
                'username' => $current_user['username'],
                'is_update' => true
            ];
        }
        
        error_log("Error updating user: " . $result['response']);
        return [
            'success' => false,
            'error' => $result['response']
        ];
    }
    
    protected function send_instant_confirmation($phone, $name) {
        require_once BOTSINO_PLUGIN_DIR . 'includes/Notifications/WhatsAppSender.php';
        $whatsapp = new \BotsinoManager\Notifications\WhatsAppSender();
        return $whatsapp->send_instant_confirmation($phone, $name);
    }
    
    protected function send_credentials_immediately($phone, $fullname, $username, $password) {
        // تاخیر 3 ثانیه برای اطمینان از ارسال پیام اول
        sleep(3);
        
        require_once BOTSINO_PLUGIN_DIR . 'includes/Notifications/WhatsAppSender.php';
        require_once BOTSINO_PLUGIN_DIR . 'includes/Helpers/Logger.php';
        
        $whatsapp = new \BotsinoManager\Notifications\WhatsAppSender();
        
        \BotsinoManager\Helpers\Logger::info(
            'message_send',
            'ارسال فوری اطلاعات ورود',
            ['phone' => $phone, 'username' => $username]
        );
        
        $result = $whatsapp->send_credentials($phone, $fullname, $username, $password);
        
        if ($result) {
            \BotsinoManager\Helpers\Logger::success(
                'message_send',
                'پیام اطلاعات ورود با موفقیت ارسال شد',
                ['phone' => $phone]
            );
        } else {
            \BotsinoManager\Helpers\Logger::error(
                'message_send',
                'خطا در ارسال پیام اطلاعات ورود',
                ['phone' => $phone]
            );
        }
        
        return $result;
    }
    
    protected function send_duplicate_user_message($phone, $fullname) {
        require_once BOTSINO_PLUGIN_DIR . 'includes/Notifications/WhatsAppSender.php';
        require_once BOTSINO_PLUGIN_DIR . 'includes/Helpers/Logger.php';
        
        $whatsapp = new \BotsinoManager\Notifications\WhatsAppSender();
        
        $message = "سلام " . $fullname . " عزیز 👋\n\n";
        $message .= "❌ شما قبلاً با این شماره موبایل از تست رایگان استفاده کرده‌اید.\n\n";
        $message .= "💡 هر شماره موبایل فقط یک بار می‌تواند از تست رایگان استفاده کند.\n\n";
        $message .= "🔐 اگر رمز عبور خود را فراموش کرده‌اید، لطفاً با پشتیبانی تماس بگیرید.\n\n";
        $message .= "📞 پشتیبانی: @ultrabot_support\n\n";
        $message .= "🌐 سایت: https://app.ultrabot.ir";
        
        \BotsinoManager\Helpers\Logger::warning(
            'message_send',
            'ارسال پیام کاربر تکراری (شماره موبایل تکراری)',
            ['phone' => $phone, 'fullname' => $fullname]
        );
        
        return $whatsapp->send_text($phone, $message);
    }
}
