<?php
namespace BotsinoManager\Admin\Views;

defined('ABSPATH') || exit;

class RemindersPage {
    
    public function render() {
        if (!current_user_can('manage_options')) {
            wp_die('دسترسی غیرمجاز');
        }
        
        global $wpdb;
        $table_name = $wpdb->prefix . 'botsino_reminders';
        
        $this->handle_actions($wpdb, $table_name);
        
        echo '<div class="wrap">';
        echo '<h1><span class="dashicons dashicons-clock"></span> مدیریت یادآوری تمدید اشتراک</h1>';
        
        $this->render_add_form($table_name);
        $this->render_reminders_list($wpdb, $table_name);
        $this->render_manual_trigger();
        
        echo '</div>';
        
        $this->add_scripts();
    }
    
    protected function handle_actions($wpdb, $table_name) {
        if (isset($_POST['add_reminder'])) {
            check_admin_referer('botsino_reminders_nonce');
            $this->save_reminder($wpdb, $table_name);
        }
        
        if (isset($_GET['delete'])) {
            $wpdb->delete($table_name, ['id' => (int)$_GET['delete']]);
            echo '<div class="notice notice-success"><p>یادآوری حذف شد</p></div>';
        }
        
        if (isset($_GET['toggle'])) {
            $id = (int)$_GET['toggle'];
            $current = $wpdb->get_var($wpdb->prepare("SELECT active FROM $table_name WHERE id = %d", $id));
            $wpdb->update($table_name, ['active' => 1 - $current], ['id' => $id]);
            echo '<div class="notice notice-success"><p>وضعیت به‌روزرسانی شد</p></div>';
        }
        
        if (isset($_POST['run_manual_reminder'])) {
            check_admin_referer('botsino_manual_reminder_nonce');
            $this->run_manual_reminder();
        }
    }
    
    protected function save_reminder($wpdb, $table_name) {
        $coupon_type = sanitize_text_field($_POST['coupon_type']);
        $coupon_code = '';
        $auto_settings = '';
        
        if ($coupon_type === 'fixed') {
            $coupon_code = sanitize_text_field($_POST['coupon_code']);
        } elseif ($coupon_type === 'auto') {
            $auto_settings = json_encode([
                'prefix' => sanitize_text_field($_POST['auto_prefix']),
                'length' => (int)$_POST['auto_length'],
                'discount' => (int)$_POST['auto_discount'],
                'expiry' => (int)$_POST['auto_expiry']
            ]);
        }
        
        $product_ids = isset($_POST['product_ids']) ? implode(',', array_map('intval', $_POST['product_ids'])) : '';
        
        $wpdb->insert($table_name, [
            'plan_type' => sanitize_text_field($_POST['plan_type']),
            'days_before' => (int)$_POST['days_before'],
            'message_type' => sanitize_text_field($_POST['message_type']),
            'message_text' => sanitize_textarea_field($_POST['message_text']),
            'coupon_type' => $coupon_type,
            'coupon_code' => $coupon_code,
            'product_ids' => $product_ids,
            'auto_settings' => $auto_settings,
            'active' => isset($_POST['active']) ? 1 : 0
        ]);
    }
    
    protected function render_add_form($table_name) {
        echo '<form method="post" class="botsino-reminder-form">';
        wp_nonce_field('botsino_reminders_nonce');
        echo '<h2>افزودن یادآوری جدید</h2>';
        echo '<table class="form-table">';
        
        echo '<tr><th>نوع اشتراک</th><td><select name="plan_type" required>';
        echo '<option value="free">رایگان</option>';
        echo '<option value="paid">پولی</option>';
        echo '</select></td></tr>';
        
        echo '<tr><th>روز قبل از انقضا</th><td><input type="number" name="days_before" min="0" required></td></tr>';
        
        echo '<tr><th>نوع پیام</th><td><select name="message_type" required>';
        echo '<option value="sms">پیامک</option>';
        echo '<option value="whatsapp">واتساپ</option>';
        echo '</select></td></tr>';
        
        echo '<tr><th>متن پیام</th><td>';
        echo '<textarea name="message_text" rows="5" class="large-text" required></textarea>';
        echo '<p class="description">متغیرها: {name}, {expiration_date}, {coupon_code}, {site_url}</p>';
        echo '</td></tr>';
        
        echo '<tr><th>نوع کوپن</th><td><select name="coupon_type" id="coupon_type">';
        echo '<option value="none">بدون کوپن</option>';
        echo '<option value="fixed">کوپن ثابت</option>';
        echo '<option value="auto">تولید خودکار</option>';
        echo '</select></td></tr>';
        
        echo '<tr id="fixed_coupon_row" style="display:none"><th>کد تخفیف ثابت</th><td><input type="text" name="coupon_code" class="regular-text"></td></tr>';
        
        echo '<tr id="auto_coupon_row" style="display:none"><th>تنظیمات کوپن خودکار</th><td>';
        echo '<div><label>پیشوند: <input type="text" name="auto_prefix" value="RENEW-"></label></div>';
        echo '<div><label>طول: <input type="number" name="auto_length" min="6" max="12" value="8"></label></div>';
        echo '<div><label>تخفیف(%): <input type="number" name="auto_discount" min="1" max="100" value="10"></label></div>';
        echo '<div><label>انقضا(روز): <input type="number" name="auto_expiry" min="1" max="365" value="30"></label></div>';
        echo '</td></tr>';
        
        echo '<tr><th>فعال</th><td><input type="checkbox" name="active" checked></td></tr>';
        echo '</table>';
        
        submit_button('ذخیره یادآوری', 'primary', 'add_reminder');
        echo '</form>';
    }
    
    protected function render_reminders_list($wpdb, $table_name) {
        $reminders = $wpdb->get_results("SELECT * FROM $table_name ORDER BY plan_type, days_before DESC");
        
        echo '<h2>یادآوری‌های تنظیم شده</h2>';
        
        if (empty($reminders)) {
            echo '<p>هیچ یادآوری تنظیم نشده است.</p>';
            return;
        }
        
        echo '<table class="wp-list-table widefat fixed striped">';
        echo '<thead><tr><th>نوع</th><th>روز قبل</th><th>پیام</th><th>کوپن</th><th>وضعیت</th><th>عملیات</th></tr></thead>';
        echo '<tbody>';
        
        foreach ($reminders as $r) {
            echo '<tr>';
            echo '<td>' . ($r->plan_type === 'free' ? 'رایگان' : 'پولی') . '</td>';
            echo '<td>' . $r->days_before . '</td>';
            echo '<td>' . esc_html(mb_substr($r->message_text, 0, 50)) . '...</td>';
            echo '<td>' . ($r->coupon_type === 'none' ? 'ندارد' : ($r->coupon_type === 'fixed' ? 'ثابت' : 'خودکار')) . '</td>';
            echo '<td>' . ($r->active ? '<span style="color:green">فعال</span>' : '<span style="color:red">غیرفعال</span>') . '</td>';
            echo '<td>';
            echo '<a href="?page=botsino-reminders&toggle=' . $r->id . '">' . ($r->active ? 'غیرفعال' : 'فعال') . '</a> | ';
            echo '<a href="?page=botsino-reminders&delete=' . $r->id . '" onclick="return confirm(\'مطمئنید؟\')">حذف</a>';
            echo '</td>';
            echo '</tr>';
        }
        
        echo '</tbody></table>';
    }
    
    protected function render_manual_trigger() {
        echo '<hr style="margin: 30px 0;">';
        echo '<h2>اجرای دستی سیستم یادآوری</h2>';
        echo '<form method="post">';
        wp_nonce_field('botsino_manual_reminder_nonce');
        echo '<p>با کلیک بر روی دکمه زیر، سیستم یادآوری به صورت دستی اجرا خواهد شد.</p>';
        submit_button('اجرای دستی یادآوری‌ها', 'primary', 'run_manual_reminder');
        echo '</form>';
    }
    
    protected function run_manual_reminder() {
        require_once BOTSINO_PLUGIN_DIR . 'includes/Reminders/ReminderManager.php';
        $manager = new \BotsinoManager\Reminders\ReminderManager();
        $manager->check_expirations();
        echo '<div class="notice notice-success"><p>سیستم یادآوری اجرا شد. نتایج در لاگ ثبت گردید.</p></div>';
    }
    
    protected function add_scripts() {
        echo '<script>
        jQuery(document).ready(function($) {
            function toggleCouponFields() {
                var type = $("#coupon_type").val();
                $("#fixed_coupon_row, #auto_coupon_row").hide();
                if (type === "fixed") $("#fixed_coupon_row").show();
                if (type === "auto") $("#auto_coupon_row").show();
            }
            toggleCouponFields();
            $("#coupon_type").change(toggleCouponFields);
        });
        </script>';
        
        echo '<style>
        .botsino-reminder-form { background: #fff; padding: 20px; margin: 20px 0; border: 1px solid #ccd0d4; }
        .form-table input[type="text"], .form-table select, .form-table textarea { width: 100%; max-width: 500px; }
        </style>';
    }
}
