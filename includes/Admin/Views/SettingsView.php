<?php
namespace BotsinoManager\Admin\Views;

defined('ABSPATH') || exit;

class SettingsView {
    
    public function render() {
        if (!current_user_can('manage_options')) {
            wp_die('دسترسی غیرمجاز');
        }
        
        $this->render_page();
        $this->add_scripts();
    }
    
    protected function render_page() {
        $api_key = get_option('botsino_api_key');
        $api_url = get_option('botsino_api_url');
        $sms_provider = get_option('botsino_sms_provider');
        $sms_api_key = get_option('botsino_sms_api_key');
        $sms_sender = get_option('botsino_sms_sender');
        $pattern_code = get_option('botsino_pattern_code');
        $sms_username = get_option('botsino_sms_username');
        $sms_password = get_option('botsino_sms_password');
        $access_token = get_option('botsino_access_token');
        $whatsapp_instance_id = get_option('botsino_whatsapp_instance_id');
        $update_pattern_code = get_option('botsino_update_pattern_code');
        $free_plan_product_id = get_option('botsino_free_plan_product_id');
        $admin_phone = get_option('botsino_admin_phone');
        $admin_pattern_code = get_option('botsino_admin_pattern_code');
        $global_renewal_coupon = get_option('botsino_global_renewal_coupon');
        
        echo '<div class="wrap">';
        echo '<h1>⚙️ تنظیمات Botsino</h1>';
        
        settings_errors('botsino_messages');
        
        echo '<form method="post" action="">';
        wp_nonce_field('botsino_settings_nonce');
        
        echo '<table class="form-table">';
        
        // تنظیمات عمومی
        echo '<tr><th colspan="2"><h2>🌐 تنظیمات عمومی</h2></th></tr>';
        
        echo '<tr>
                <th scope="row"><label for="botsino_global_renewal_coupon">کوپن تخفیف جهانی تمدید</label></th>
                <td>
                    <input type="text" id="botsino_global_renewal_coupon" name="botsino_global_renewal_coupon" value="' . esc_attr($global_renewal_coupon) . '" class="regular-text">
                    <p class="description">کوپن پیش‌فرض برای یادآوری‌های تمدید (اگر در یادآوری مشخص نشده باشد از این استفاده می‌شود)</p>
                </td>
              </tr>';
        
        echo '<tr>
                <th scope="row"><label for="botsino_api_key">کلید API Botsino</label></th>
                <td><input type="text" id="botsino_api_key" name="botsino_api_key" value="' . esc_attr($api_key) . '" class="regular-text"></td>
              </tr>';
        
        echo '<tr>
                <th scope="row"><label for="botsino_api_url">آدرس API Botsino</label></th>
                <td><input type="text" id="botsino_api_url" name="botsino_api_url" value="' . esc_attr($api_url) . '" class="regular-text"></td>
              </tr>';
        
        // تنظیمات ارسال پیام
        echo '<tr><th colspan="2"><h2>📱 تنظیمات ارسال پیام</h2></th></tr>';
        
        echo '<tr>
                <th scope="row"><label for="botsino_sms_provider">روش ارسال</label></th>
                <td>
                    <select id="botsino_sms_provider" name="botsino_sms_provider">
                        <option value="sms" ' . selected($sms_provider, 'sms', false) . '>پیامک</option>
                        <option value="whatsapp" ' . selected($sms_provider, 'whatsapp', false) . '>واتساپ</option>
                    </select>
                </td>
              </tr>';
        
        // تنظیمات SMS
        echo '<tr class="sms-settings">
                <th scope="row"><label for="botsino_sms_api_key">کلید API سرویس پیامک</label></th>
                <td><input type="text" id="botsino_sms_api_key" name="botsino_sms_api_key" value="' . esc_attr($sms_api_key) . '" class="regular-text"></td>
              </tr>';
        
        echo '<tr class="sms-settings">
                <th scope="row"><label for="botsino_sms_sender">شماره ارسال‌کننده پیامک</label></th>
                <td><input type="text" id="botsino_sms_sender" name="botsino_sms_sender" value="' . esc_attr($sms_sender) . '" class="regular-text"></td>
              </tr>';
        
        echo '<tr class="sms-settings">
                <th scope="row"><label for="botsino_pattern_code">کد پترن پیامک</label></th>
                <td><input type="text" id="botsino_pattern_code" name="botsino_pattern_code" value="' . esc_attr($pattern_code) . '" class="regular-text"></td>
              </tr>';
        
        echo '<tr class="sms-settings">
                <th scope="row"><label for="botsino_update_pattern_code">کد پترن پیامک (به‌روزرسانی پلن)</label></th>
                <td>
                    <input type="text" id="botsino_update_pattern_code" name="botsino_update_pattern_code" value="' . esc_attr($update_pattern_code) . '" class="regular-text">
                    <p class="description">الگوی مخصوص اطلاع رسانی به‌روزرسانی پلن کاربران</p>
                </td>
              </tr>';
        
        echo '<tr class="sms-settings">
                <th scope="row"><label for="botsino_sms_username">نام کاربری سرویس پیامک</label></th>
                <td><input type="text" id="botsino_sms_username" name="botsino_sms_username" value="' . esc_attr($sms_username) . '" class="regular-text"></td>
              </tr>';
        
        echo '<tr class="sms-settings">
                <th scope="row"><label for="botsino_sms_password">رمز عبور سرویس پیامک</label></th>
                <td><input type="password" id="botsino_sms_password" name="botsino_sms_password" value="' . esc_attr($sms_password) . '" class="regular-text"></td>
              </tr>';
        
        // تنظیمات WhatsApp
        echo '<tr class="whatsapp-settings">
                <th scope="row"><label for="botsino_access_token">توکن دسترسی واتساپ</label></th>
                <td><input type="text" id="botsino_access_token" name="botsino_access_token" value="' . esc_attr($access_token) . '" class="regular-text"></td>
              </tr>';
        
        echo '<tr class="whatsapp-settings">
                <th scope="row"><label for="botsino_whatsapp_instance_id">شناسه نمونه واتساپ</label></th>
                <td><input type="text" id="botsino_whatsapp_instance_id" name="botsino_whatsapp_instance_id" value="' . esc_attr($whatsapp_instance_id) . '" class="regular-text"></td>
              </tr>';
        
        // تنظیمات پلن رایگان
        echo '<tr><th colspan="2"><h2>🎁 تنظیمات پلن رایگان</h2></th></tr>';
        
        echo '<tr>
                <th scope="row"><label for="botsino_free_plan_product_id">شناسه محصول پلن رایگان</label></th>
                <td><input type="number" id="botsino_free_plan_product_id" name="botsino_free_plan_product_id" value="' . esc_attr($free_plan_product_id) . '" class="regular-text"></td>
              </tr>';
        
        // تنظیمات پیامک مدیر
        echo '<tr><th colspan="2"><h2>👤 تنظیمات پیامک مدیر</h2></th></tr>';
        
        echo '<tr>
                <th scope="row"><label for="botsino_admin_phone">شماره مدیر</label></th>
                <td><input type="text" id="botsino_admin_phone" name="botsino_admin_phone" value="' . esc_attr($admin_phone) . '" class="regular-text" placeholder="09123456789"></td>
              </tr>';
        
        echo '<tr>
                <th scope="row"><label for="botsino_admin_pattern_code">کد پترن پیامک مدیر</label></th>
                <td><input type="text" id="botsino_admin_pattern_code" name="botsino_admin_pattern_code" value="' . esc_attr($admin_pattern_code) . '" class="regular-text"></td>
              </tr>';
        
        echo '</table>';
        
        submit_button('ذخیره تغییرات', 'primary', 'botsino_settings_submit');
        echo '</form>';
        
        echo '</div>';
    }
    
    protected function add_scripts() {
        echo '<script>
        jQuery(document).ready(function($) {
            function toggleSettings() {
                if ($("#botsino_sms_provider").val() === "sms") {
                    $(".sms-settings").show();
                    $(".whatsapp-settings").hide();
                } else {
                    $(".sms-settings").hide();
                    $(".whatsapp-settings").show();
                }
            }
            
            toggleSettings();
            
            $("#botsino_sms_provider").change(function() {
                toggleSettings();
            });
        });
        </script>';
    }
}
