<?php
namespace BotsinoManager\Admin;

defined('ABSPATH') || exit;

class SettingsPage {
    
    public function save_settings() {
        if (!isset($_POST['botsino_settings_submit'])) {
            return;
        }
        
        if (!current_user_can('manage_options')) {
            return;
        }
        
        check_admin_referer('botsino_settings_nonce');
        
        $settings = [
            'botsino_api_key',
            'botsino_api_url',
            'botsino_sms_provider',
            'botsino_sms_api_key',
            'botsino_sms_sender',
            'botsino_pattern_code',
            'botsino_sms_username',
            'botsino_sms_password',
            'botsino_access_token',
            'botsino_whatsapp_instance_id',
            'botsino_free_plan_product_id',
            'botsino_admin_phone',
            'botsino_admin_pattern_code',
            'botsino_update_pattern_code',
            'botsino_global_renewal_coupon'
        ];
        
        foreach ($settings as $setting) {
            if (isset($_POST[$setting])) {
                update_option($setting, sanitize_text_field($_POST[$setting]));
            }
        }
        
        add_settings_error('botsino_messages', 'botsino_message', 'تنظیمات ذخیره شد', 'updated');
    }
    
    public function render() {
        if (!current_user_can('manage_options')) {
            wp_die('دسترسی غیرمجاز');
        }
        
        require_once BOTSINO_PLUGIN_DIR . 'includes/Admin/Views/SettingsView.php';
        $view = new \BotsinoManager\Admin\Views\SettingsView();
        $view->render();
    }
}
