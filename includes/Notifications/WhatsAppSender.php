<?php
namespace BotsinoManager\Notifications;

defined('ABSPATH') || exit;

use BotsinoManager\Helpers\PhoneNormalizer;

class WhatsAppSender {
    
    protected $access_token;
    protected $instance_id;
    protected $api_url = 'https://botsino.ir/api/send';
    
    public function __construct() {
        $this->access_token = get_option('botsino_access_token');
        $this->instance_id = get_option('botsino_whatsapp_instance_id');
    }
    
    public function send_instant_confirmation($phone, $name) {
        if (empty($this->access_token) || empty($this->instance_id)) {
            error_log("WhatsApp settings incomplete");
            return false;
        }
        
        $whatsapp_number = PhoneNormalizer::normalize($phone);
        if (empty($whatsapp_number)) {
            error_log("Invalid phone number");
            return false;
        }
        
        $message = "سلام {$name} عزیز 👋\n" .
                   "✅ درخواست شما ثبت شد\n\n" .
                   "⏳ تا حداکثر 30 دقیقه آینده اطلاعات ورود به واتساپ شما ارسال خواهد شد.\n\n" .
                   "🙏 از صبر شما متشکریم\n\n" .
                   "ربات واتساپی اولترابات\n\n" .
                   "Ultrabot.ir";
        
        return $this->send_text($whatsapp_number, $message);
    }
    
    public function send_credentials($phone, $name, $username, $password) {
        if (empty($this->access_token) || empty($this->instance_id)) {
            error_log("WhatsApp settings incomplete");
            return false;
        }
        
        $whatsapp_number = PhoneNormalizer::normalize($phone);
        if (empty($whatsapp_number)) {
            error_log("Invalid phone number");
            return false;
        }
        
        if (empty($password)) {
            $single_message = "🎉 حساب Botsino شما به‌روزرسانی شد!\n\n" .
                            "🔐 اطلاعات ورود:\n" .
                            "🌐 آدرس سایت: https://botsino.ir\n" .
                            "📚 آموزش‌ها: https://botsino.ir/blog_internal\n\n" .
                            "👤 نام کاربری: {$username}\n\n" .
                            "✅ از رمز عبور قبلی خود استفاده کنید";
            
            return $this->send_text($whatsapp_number, $single_message);
        }
        
        $guide_message = "🎉 حساب Botsino شما آماده است!\n\n" .
                       "🔐 اطلاعات ورود:\n" .
                       "🌐 آدرس سایت: https://botsino.ir\n" .
                       "📚 آموزش‌ها: https://botsino.ir/blog_internal\n\n" .
                       "👤 نام کاربری: {$username}\n\n" .
                       "رمز عبور در پیام بعدی ارسال خواهد شد...";
        
        $first_sent = $this->send_text($whatsapp_number, $guide_message);
        
        if (!$first_sent) {
            error_log("First message failed");
            return false;
        }
        
        sleep(5);
        
        return $this->send_text($whatsapp_number, $password);
    }
    
    public function send_reminder($phone, $message) {
        if (empty($this->access_token) || empty($this->instance_id)) {
            error_log("WhatsApp settings incomplete for reminder");
            return false;
        }
        
        $phone = PhoneNormalizer::normalize($phone);
        if (!$phone) {
            error_log("Invalid phone number for reminder: $phone");
            return false;
        }
        
        return $this->send_text($phone, $message);
    }
    
    public function send_text($phone, $message, $max_chunk_length = 900, $delay_between_chunks = 3) {
        $message = (string)$message;
        $chunks = mb_str_split($message, $max_chunk_length, "UTF-8");
        $success = true;
        
        foreach ($chunks as $i => $chunk) {
            $chunk = (string)$chunk;
            $data = [
                "number" => $phone,
                "type" => "text",
                "message" => $chunk,
                "instance_id" => $this->instance_id,
                "access_token" => $this->access_token
            ];
            
            $response = wp_remote_post($this->api_url, [
                'method' => 'POST',
                'body' => json_encode($data, JSON_UNESCAPED_UNICODE),
                'headers' => ['Content-Type' => 'application/json'],
                'timeout' => 30
            ]);
            
            error_log("Sending chunk " . ($i+1) . " → Response: " . wp_remote_retrieve_body($response));
            
            if (is_wp_error($response)) {
                error_log("Error sending chunk " . ($i+1) . ": " . $response->get_error_message());
                $success = false;
                break;
            }
            
            $status_code = wp_remote_retrieve_response_code($response);
            if ($status_code !== 200) {
                $body = wp_remote_retrieve_body($response);
                error_log("Error sending chunk " . ($i+1) . ". Status: {$status_code}, Response: {$body}");
                $success = false;
                break;
            }
            
            sleep($delay_between_chunks);
        }
        
        return $success;
    }
}
