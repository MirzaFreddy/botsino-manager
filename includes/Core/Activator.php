<?php
namespace BotsinoManager\Core;

defined('ABSPATH') || exit;

/**
 * Plugin Activation Handler
 */
class Activator {
    
    public static function activate() {
        date_default_timezone_set('Asia/Tehran');
        
        require_once BOTSINO_PLUGIN_DIR . 'includes/Database/DatabaseManager.php';
        $db = new \BotsinoManager\Database\DatabaseManager();
        $db->create_tables();
        
        self::schedule_cron_events();
        self::set_default_options();
        self::enable_logging();
    }
    
    private static function schedule_cron_events() {
        if (!wp_next_scheduled('botsino_process_queue')) {
            wp_schedule_event(time(), 'every_minute', 'botsino_process_queue');
        }
        
        if (!wp_next_scheduled('botsino_process_message_queue')) {
            wp_schedule_event(time(), 'every_minute_messages', 'botsino_process_message_queue');
        }
        
        if (!wp_next_scheduled('botsino_daily_expiration_check')) {
            wp_schedule_event(time(), 'daily', 'botsino_daily_expiration_check');
        }
    }
    
    private static function set_default_options() {
        $defaults = [
            'botsino_api_key' => '',
            'botsino_api_url' => 'https://botsino.ir/admin_api/users',
            'botsino_sms_provider' => 'sms',
            'botsino_sms_api_key' => '',
            'botsino_sms_sender' => '',
            'botsino_pattern_code' => '',
            'botsino_sms_username' => '',
            'botsino_sms_password' => '',
            'botsino_access_token' => '',
            'botsino_whatsapp_instance_id' => '',
            'botsino_free_plan_product_id' => '',
            'botsino_admin_phone' => '',
            'botsino_admin_pattern_code' => '',
            'botsino_update_pattern_code' => '',
            'botsino_global_renewal_coupon' => ''
        ];
        
        foreach ($defaults as $key => $value) {
            add_option($key, $value);
        }
    }
    
    private static function enable_logging() {
        if (!defined('WP_DEBUG')) {
            define('WP_DEBUG', true);
        }
        if (!defined('WP_DEBUG_LOG')) {
            define('WP_DEBUG_LOG', true);
        }
        if (!defined('WP_DEBUG_DISPLAY')) {
            define('WP_DEBUG_DISPLAY', false);
        }
        @ini_set('log_errors', 'On');
        @ini_set('error_log', ABSPATH . 'wp-content/debug.log');
    }
}
