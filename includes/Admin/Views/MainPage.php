<?php
namespace BotsinoManager\Admin\Views;

defined('ABSPATH') || exit;

class MainPage {
    
    public function render() {
        if (!current_user_can('manage_options')) {
            wp_die('دسترسی غیرمجاز');
        }
        
        $this->handle_actions();
        $this->render_page();
        $this->add_styles();
    }
    
    protected function render_page() {
        echo '<div class="wrap">';
        echo '<h1><span class="dashicons dashicons-businessperson"></span> مدیریت یکپارچه‌سازی Botsino</h1>';
        
        $tabs = [
            'dashboard' => 'داشبورد',
            'create-user' => 'ساخت کاربر',
            'user-list' => 'لیست کاربران',
            'queue' => 'صف کاربران',
            'logs' => 'لاگ‌ها'
        ];
        
        $current_tab = isset($_GET['tab']) ? sanitize_key($_GET['tab']) : 'dashboard';
        
        echo '<h2 class="nav-tab-wrapper">';
        foreach ($tabs as $tab => $name) {
            $class = ($tab == $current_tab) ? ' nav-tab-active' : '';
            echo '<a class="nav-tab' . $class . '" href="?page=botsino-manager&tab=' . $tab . '">' . $name . '</a>';
        }
        echo '</h2>';
        
        echo '<div class="botsino-admin-content">';
        $method = 'render_' . str_replace('-', '_', $current_tab) . '_tab';
        if (method_exists($this, $method)) {
            $this->$method();
        }
        echo '</div>';
        
        echo '</div>';
    }
    
    protected function handle_actions() {
        if (isset($_POST['botsino_action'])) {
            $action = sanitize_text_field($_POST['botsino_action']);
            if (method_exists($this, 'action_' . $action)) {
                $this->{'action_' . $action}();
            }
        }
        
        if (isset($_GET['action'])) {
            $tab = sanitize_key($_GET['tab'] ?? '');
            $action = sanitize_key($_GET['action']);
            
            if ($tab == 'queue') {
                $this->handle_queue_actions();
            } elseif ($tab == 'user-list' && $action == 'delete_user') {
                $this->handle_delete_user();
            } elseif ($tab == 'logs' && $action == 'clear_logs') {
                $this->handle_clear_logs();
            }
        }
    }
    
    protected function handle_clear_logs() {
        require_once BOTSINO_PLUGIN_DIR . 'includes/Helpers/Logger.php';
        \BotsinoManager\Helpers\Logger::clear_all();
        $this->show_notice(true, 'تمام لاگ‌ها پاک شدند');
    }
    
    protected function action_create_user() {
        require_once BOTSINO_PLUGIN_DIR . 'includes/Users/UserManager.php';
        $user_manager = new \BotsinoManager\Users\UserManager();
        
        $result = $user_manager->create(
            sanitize_email($_POST['user_email']),
            sanitize_text_field($_POST['user_phone']),
            (int)$_POST['plan_id'],
            sanitize_text_field($_POST['fullname']),
            (int)$_POST['status']
        );
        
        $this->show_notice($result['success'], $result['success'] ? 'کاربر با موفقیت ایجاد شد' : 'خطا در ایجاد کاربر');
    }
    
    protected function action_clear_logs() {
        $log_file = ABSPATH . 'wp-content/debug.log';
        if (file_exists($log_file)) {
            file_put_contents($log_file, '');
            $this->show_notice(true, 'فایل لاگ پاک شد');
        }
    }
    
    protected function handle_delete_user() {
        if (!isset($_GET['email'])) {
            $this->show_notice(false, 'ایمیل کاربر مشخص نشده است');
            return;
        }
        
        $email = sanitize_email($_GET['email']);
        
        require_once BOTSINO_PLUGIN_DIR . 'includes/API/APIClient.php';
        $api = new \BotsinoManager\API\APIClient();
        $result = $api->delete_user($email);
        
        require_once BOTSINO_PLUGIN_DIR . 'includes/Helpers/Logger.php';
        if ($result['success']) {
            \BotsinoManager\Helpers\Logger::success(
                'user_management',
                'کاربر حذف شد',
                ['email' => $email]
            );
            $this->show_notice(true, 'کاربر با موفقیت حذف شد');
        } else {
            \BotsinoManager\Helpers\Logger::error(
                'user_management',
                'خطا در حذف کاربر',
                ['email' => $email, 'error' => $result['response']]
            );
            $this->show_notice(false, 'خطا در حذف کاربر: ' . $result['response']);
        }
    }
    
    protected function action_bulk_delete_users() {
        if (!isset($_POST['user_emails']) || !is_array($_POST['user_emails'])) {
            $this->show_notice(false, 'هیچ کاربری انتخاب نشده است');
            return;
        }
        
        check_admin_referer('botsino_bulk_action');
        
        require_once BOTSINO_PLUGIN_DIR . 'includes/API/APIClient.php';
        $api = new \BotsinoManager\API\APIClient();
        
        $success_count = 0;
        $fail_count = 0;
        
        foreach ($_POST['user_emails'] as $email) {
            $email = sanitize_email($email);
            $result = $api->delete_user($email);
            
            if ($result['success']) {
                $success_count++;
            } else {
                $fail_count++;
            }
            
            // کمی تاخیر برای جلوگیری از فشار به API
            usleep(200000); // 0.2 ثانیه
        }
        
        require_once BOTSINO_PLUGIN_DIR . 'includes/Helpers/Logger.php';
        \BotsinoManager\Helpers\Logger::info(
            'user_management',
            'حذف دسته‌ای کاربران',
            ['success' => $success_count, 'failed' => $fail_count]
        );
        
        $message = "✅ {$success_count} کاربر حذف شد";
        if ($fail_count > 0) {
            $message .= " | ❌ {$fail_count} کاربر با خطا مواجه شد";
        }
        
        $this->show_notice($fail_count == 0, $message);
    }
    
    protected function handle_queue_actions() {
        global $wpdb;
        $action = sanitize_key($_GET['action'] ?? '');
        $id = intval($_GET['id'] ?? 0);
        $queue_table = $wpdb->prefix . 'botsino_queue';
        $message_table = $wpdb->prefix . 'botsino_message_queue';
        
        switch ($action) {
            case 'delete':
                $wpdb->delete($queue_table, ['id' => $id]);
                $this->show_notice(true, 'آیتم حذف شد');
                break;
                
            case 'retry':
                $wpdb->update($queue_table, ['status' => 'pending', 'attempts' => 0], ['id' => $id]);
                $this->show_notice(true, 'آیتم برای تلاش مجدد آماده شد');
                break;
                
            case 'process':
                require_once BOTSINO_PLUGIN_DIR . 'includes/Queue/QueueManager.php';
                $processed = (new \BotsinoManager\Queue\QueueManager())->process();
                $this->show_notice(true, "پردازش شد: $processed");
                break;
                
            case 'process_messages':
                require_once BOTSINO_PLUGIN_DIR . 'includes/Queue/MessageQueue.php';
                $processed = (new \BotsinoManager\Queue\MessageQueue())->process();
                $this->show_notice(true, "پیام‌های پردازش شده: $processed");
                break;
                
            case 'resend':
                $this->resend_credentials($id);
                break;
                
            case 'bulk_delete':
                $this->bulk_delete_queue();
                break;
        }
    }
    
    protected function resend_credentials($queue_id) {
        global $wpdb;
        $queue_table = $wpdb->prefix . 'botsino_queue';
        
        $item = $wpdb->get_row($wpdb->prepare("SELECT * FROM $queue_table WHERE id = %d", $queue_id), ARRAY_A);
        
        if (!$item) {
            $this->show_notice(false, 'آیتم یافت نشد');
            return;
        }
        
        $user_data = maybe_unserialize($item['user_data']);
        $email = $user_data['email'] ?? '';
        $phone = $user_data['phone'] ?? '';
        $fullname = $user_data['fullname'] ?? '';
        
        if (!$email || !$phone) {
            $this->show_notice(false, 'اطلاعات ناقص است');
            return;
        }
        
        // دریافت اطلاعات کاربر از API
        require_once BOTSINO_PLUGIN_DIR . 'includes/API/APIClient.php';
        $api = new \BotsinoManager\API\APIClient();
        $user = $api->get_user($email);
        
        if (!$user) {
            $this->show_notice(false, 'کاربر در سیستم یافت نشد');
            return;
        }
        
        // ارسال مجدد اطلاعات
        require_once BOTSINO_PLUGIN_DIR . 'includes/Notifications/WhatsAppSender.php';
        $whatsapp = new \BotsinoManager\Notifications\WhatsAppSender();
        
        $result = $whatsapp->send_credentials(
            $phone,
            $fullname,
            $user['username'] ?? '',
            $user['password'] ?? ''
        );
        
        if ($result) {
            $this->show_notice(true, 'اطلاعات ورود مجدداً ارسال شد');
        } else {
            $this->show_notice(false, 'خطا در ارسال پیام');
        }
    }
    
    protected function bulk_delete_queue() {
        if (!isset($_POST['queue_ids']) || !is_array($_POST['queue_ids'])) {
            $this->show_notice(false, 'هیچ موردی انتخاب نشده است');
            return;
        }
        
        check_admin_referer('botsino_bulk_action');
        
        global $wpdb;
        $queue_table = $wpdb->prefix . 'botsino_queue';
        $ids = array_map('intval', $_POST['queue_ids']);
        
        if (empty($ids)) {
            $this->show_notice(false, 'هیچ موردی انتخاب نشده است');
            return;
        }
        
        $placeholders = implode(',', array_fill(0, count($ids), '%d'));
        $deleted = $wpdb->query($wpdb->prepare("DELETE FROM $queue_table WHERE id IN ($placeholders)", $ids));
        
        $this->show_notice(true, "$deleted مورد حذف شد");
    }
    
    protected function show_notice($success, $message) {
        $type = $success ? 'success' : 'error';
        echo '<div class="notice notice-' . $type . '"><p>' . esc_html($message) . '</p></div>';
    }
    
    protected function render_dashboard_tab() {
        echo '<div class="botsino-dashboard">';
        echo '<h3>داشبورد مدیریت</h3>';
        echo '<p>به پنل مدیریت Botsino خوش آمدید. از منوی بالا گزینه مورد نظر را انتخاب کنید.</p>';
        echo '</div>';
    }
    
    protected function render_create_user_tab() {
        require_once BOTSINO_PLUGIN_DIR . 'includes/Config/Constants.php';
        
        echo '<div class="botsino-form">';
        echo '<h3>ساخت کاربر جدید</h3>';
        echo '<form method="post">';
        echo '<input type="hidden" name="botsino_action" value="create_user">';
        echo '<table class="form-table">';
        echo '<tr><th>ایمیل</th><td><input type="email" name="user_email" required class="regular-text"></td></tr>';
        echo '<tr><th>شماره تلفن</th><td><input type="tel" name="user_phone" required class="regular-text" placeholder="09123456789"></td></tr>';
        echo '<tr><th>نام کامل</th><td><input type="text" name="fullname" required class="regular-text"></td></tr>';
        echo '<tr><th>پلن</th><td><select name="plan_id" required>';
        foreach (\BotsinoManager\Config\Constants::PLANS as $id => $name) {
            echo '<option value="' . $id . '">' . $name . '</option>';
        }
        echo '</select></td></tr>';
        echo '<tr><th>وضعیت</th><td><select name="status" required>';
        echo '<option value="2" selected>فعال</option>';
        echo '<option value="1">غیرفعال</option>';
        echo '<option value="0">مسدودشده</option>';
        echo '</select></td></tr>';
        echo '</table>';
        submit_button('ساخت کاربر');
        echo '</form></div>';
    }
    
    protected function render_user_list_tab() {
        require_once BOTSINO_PLUGIN_DIR . 'includes/API/APIClient.php';
        
        $api = new \BotsinoManager\API\APIClient();
        $users = $api->get_users();
        
        echo '<div class="botsino-user-list">';
        echo '<h3>👥 لیست کاربران</h3>';
        
        if (empty($users)) {
            echo '<p>هیچ کاربری یافت نشد.</p></div>';
            return;
        }
        
        // دکمه‌های عملیات
        echo '<div style="margin-bottom: 15px;">';
        echo '<button type="button" id="delete-selected-users" class="button button-secondary">🗑️ حذف موارد انتخابی</button> ';
        echo '<span id="selected-count" style="margin-right:10px; font-weight:bold;"></span>';
        echo '</div>';
        
        echo '<form id="users-form" method="post">';
        echo '<input type="hidden" name="botsino_action" value="bulk_delete_users">';
        wp_nonce_field('botsino_bulk_action');
        
        echo '<table class="wp-list-table widefat fixed striped">';
        echo '<thead><tr>';
        echo '<th style="width:30px"><input type="checkbox" id="select-all-users"></th>';
        echo '<th>نام کاربری</th><th>ایمیل</th><th>نام</th><th>پلن</th><th>تاریخ انقضا</th><th>وضعیت</th><th>عملیات</th>';
        echo '</tr></thead><tbody>';
        
        foreach ($users as $user) {
            $status_badge = '';
            $status_value = $user['status'] ?? 0;
            
            switch($status_value) {
                case 1:
                case '1':
                    $status_badge = '<span class="status-badge status-completed">✅ فعال</span>';
                    break;
                case 2:
                case '2':
                    $status_badge = '<span class="status-badge status-pending">⏳ در انتظار</span>';
                    break;
                case 0:
                case '0':
                    $status_badge = '<span class="status-badge status-failed">❌ غیرفعال</span>';
                    break;
                default:
                    $status_badge = '<span class="status-badge">' . esc_html($status_value) . '</span>';
            }
            
            $email = $user['email'] ?? '---';
            $expired_date = $user['expired_date'] ?? '---';
            
            echo '<tr>';
            echo '<td><input type="checkbox" name="user_emails[]" value="' . esc_attr($email) . '" class="user-checkbox"></td>';
            echo '<td><strong>' . esc_html($user['username'] ?? '---') . '</strong></td>';
            echo '<td>' . esc_html($email) . '</td>';
            echo '<td>' . esc_html($user['fullname'] ?? '---') . '</td>';
            echo '<td>' . esc_html($user['plan'] ?? '---') . '</td>';
            echo '<td>' . esc_html($expired_date) . '</td>';
            echo '<td>' . $status_badge . '</td>';
            echo '<td style="white-space: nowrap;">';
            echo '<a href="?page=botsino-manager&tab=user-list&action=delete_user&email=' . urlencode($email) . '" class="button button-small button-link-delete" onclick="return confirm(\'مطمئنید که می‌خواهید این کاربر را حذف کنید؟\')" title="حذف">🗑️</a>';
            echo '</td>';
            echo '</tr>';
        }
        
        echo '</tbody></table>';
        echo '</form>';
        
        // JavaScript برای انتخاب همه و شمارش
        echo '<script>
        jQuery(document).ready(function($) {
            $("#select-all-users").on("change", function() {
                $(".user-checkbox").prop("checked", $(this).prop("checked"));
                updateSelectedCount();
            });
            
            $(".user-checkbox").on("change", function() {
                updateSelectedCount();
            });
            
            function updateSelectedCount() {
                var count = $(".user-checkbox:checked").length;
                if (count > 0) {
                    $("#selected-count").text(count + " کاربر انتخاب شده");
                } else {
                    $("#selected-count").text("");
                }
            }
            
            $("#delete-selected-users").on("click", function() {
                var count = $(".user-checkbox:checked").length;
                if (count === 0) {
                    alert("لطفاً حداقل یک کاربر را انتخاب کنید");
                    return;
                }
                if (confirm("آیا مطمئنید که می‌خواهید " + count + " کاربر را حذف کنید؟")) {
                    $("#users-form").submit();
                }
            });
        });
        </script>';
        
        echo '</div>';
    }
    
    protected function render_queue_tab() {
        global $wpdb;
        $queue_table = $wpdb->prefix . 'botsino_queue';
        
        echo '<div class="botsino-form">';
        echo '<h3>📋 صف درخواست‌ها</h3>';
        
        // دکمه‌های عملیات
        echo '<div style="margin-bottom: 15px;">';
        echo '<a href="?page=botsino-manager&tab=queue&action=process" class="button button-primary">⚡ اجرای صف</a> ';
        echo '<button type="button" id="delete-selected" class="button button-secondary">🗑️ حذف موارد انتخابی</button>';
        echo '</div>';
        
        $items = $wpdb->get_results("SELECT * FROM $queue_table ORDER BY created_at DESC LIMIT 100", ARRAY_A);
        
        if (empty($items)) {
            echo '<p>صف خالی است.</p></div>';
            return;
        }
        
        echo '<form id="queue-form" method="post">';
        echo '<input type="hidden" name="botsino_action" value="bulk_delete_queue">';
        wp_nonce_field('botsino_bulk_action');
        
        echo '<table class="wp-list-table widefat fixed striped">';
        echo '<thead><tr>';
        echo '<th style="width:30px"><input type="checkbox" id="select-all"></th>';
        echo '<th>ID</th><th>سفارش</th><th>ایمیل</th><th>تلفن</th><th>پلن</th><th>وضعیت</th><th>تاریخ</th><th>عملیات</th>';
        echo '</tr></thead><tbody>';
        
        foreach ($items as $item) {
            $user_data = maybe_unserialize($item['user_data']);
            $email = $user_data['email'] ?? '---';
            $phone = $user_data['phone'] ?? '---';
            $plan_id = $user_data['plan_id'] ?? 0;
            
            $status_class = '';
            $status_text = $item['status'];
            switch($item['status']) {
                case 'completed': $status_class = 'completed'; $status_text = '✅ تکمیل شده'; break;
                case 'pending': $status_class = 'pending'; $status_text = '⏳ در انتظار'; break;
                case 'processing': $status_class = 'processing'; $status_text = '🔄 در حال پردازش'; break;
                case 'failed': $status_class = 'failed'; $status_text = '❌ خطا'; break;
            }
            
            echo '<tr class="queue-row-' . $status_class . '">';
            echo '<td><input type="checkbox" name="queue_ids[]" value="' . $item['id'] . '" class="queue-checkbox"></td>';
            echo '<td>' . $item['id'] . '</td>';
            echo '<td>#' . $item['order_id'] . '</td>';
            echo '<td>' . esc_html($email) . '</td>';
            echo '<td>' . esc_html($phone) . '</td>';
            echo '<td>پلن ' . $plan_id . '</td>';
            echo '<td><span class="status-badge status-' . $status_class . '">' . $status_text . '</span></td>';
            echo '<td>' . date('Y-m-d H:i', strtotime($item['created_at'])) . '</td>';
            echo '<td style="white-space: nowrap;">';
            
            if ($item['status'] === 'failed' || $item['status'] === 'pending') {
                echo '<a href="?page=botsino-manager&tab=queue&action=retry&id=' . $item['id'] . '" class="button button-small" title="تلاش مجدد">🔄</a> ';
            }
            
            if ($item['status'] === 'completed') {
                echo '<a href="?page=botsino-manager&tab=queue&action=resend&id=' . $item['id'] . '" class="button button-small" title="ارسال مجدد اطلاعات">📨</a> ';
            }
            
            echo '<a href="?page=botsino-manager&tab=queue&action=delete&id=' . $item['id'] . '" class="button button-small button-link-delete" onclick="return confirm(\'مطمئنید؟\')" title="حذف">🗑️</a>';
            echo '</td>';
            echo '</tr>';
        }
        
        echo '</tbody></table>';
        echo '</form>';
        
        // آمار
        $stats = [
            'total' => count($items),
            'pending' => count(array_filter($items, fn($i) => $i['status'] === 'pending')),
            'completed' => count(array_filter($items, fn($i) => $i['status'] === 'completed')),
            'failed' => count(array_filter($items, fn($i) => $i['status'] === 'failed'))
        ];
        
        echo '<div style="margin-top: 15px; padding: 10px; background: #f0f0f1; border-radius: 3px;">';
        echo '<strong>آمار:</strong> ';
        echo 'کل: ' . $stats['total'] . ' | ';
        echo '⏳ در انتظار: ' . $stats['pending'] . ' | ';
        echo '✅ تکمیل: ' . $stats['completed'] . ' | ';
        echo '❌ خطا: ' . $stats['failed'];
        echo '</div>';
        
        echo '</div>';
        
        // JavaScript برای انتخاب همه و حذف دسته‌ای
        echo '<script>
        jQuery(document).ready(function($) {
            $("#select-all").on("change", function() {
                $(".queue-checkbox").prop("checked", $(this).prop("checked"));
            });
            
            $("#delete-selected").on("click", function() {
                var selected = $(".queue-checkbox:checked").length;
                if (selected === 0) {
                    alert("لطفاً حداقل یک مورد را انتخاب کنید");
                    return;
                }
                
                if (confirm("آیا از حذف " + selected + " مورد انتخاب شده مطمئن هستید؟")) {
                    $("#queue-form").attr("action", "?page=botsino-manager&tab=queue&action=bulk_delete").submit();
                }
            });
        });
        </script>';
    }
    
    protected function render_logs_tab() {
        require_once BOTSINO_PLUGIN_DIR . 'includes/Helpers/Logger.php';
        
        // فیلترها
        $type_filter = isset($_GET['log_type']) ? sanitize_key($_GET['log_type']) : '';
        $category_filter = isset($_GET['log_category']) ? sanitize_key($_GET['log_category']) : '';
        $search = isset($_GET['log_search']) ? sanitize_text_field($_GET['log_search']) : '';
        
        echo '<div class="botsino-form">';
        echo '<h3>📋 لاگ‌های سیستم</h3>';
        
        // آمار
        $stats = \BotsinoManager\Helpers\Logger::get_stats();
        echo '<div style="display: flex; gap: 15px; margin-bottom: 20px; flex-wrap: wrap;">';
        echo '<div class="log-stat-box" style="flex: 1; min-width: 150px; padding: 15px; background: #f0f0f1; border-radius: 5px; text-align: center;">';
        echo '<div style="font-size: 24px; font-weight: bold; color: #2271b1;">' . $stats['total'] . '</div>';
        echo '<div style="font-size: 12px; color: #666;">کل لاگ‌ها</div>';
        echo '</div>';
        echo '<div class="log-stat-box" style="flex: 1; min-width: 150px; padding: 15px; background: #d4edda; border-radius: 5px; text-align: center;">';
        echo '<div style="font-size: 24px; font-weight: bold; color: #155724;">✅ ' . $stats['success'] . '</div>';
        echo '<div style="font-size: 12px; color: #155724;">موفق</div>';
        echo '</div>';
        echo '<div class="log-stat-box" style="flex: 1; min-width: 150px; padding: 15px; background: #f8d7da; border-radius: 5px; text-align: center;">';
        echo '<div style="font-size: 24px; font-weight: bold; color: #721c24;">❌ ' . $stats['error'] . '</div>';
        echo '<div style="font-size: 12px; color: #721c24;">خطا</div>';
        echo '</div>';
        echo '<div class="log-stat-box" style="flex: 1; min-width: 150px; padding: 15px; background: #fff3cd; border-radius: 5px; text-align: center;">';
        echo '<div style="font-size: 24px; font-weight: bold; color: #856404;">⚠️ ' . $stats['warning'] . '</div>';
        echo '<div style="font-size: 12px; color: #856404;">هشدار</div>';
        echo '</div>';
        echo '<div class="log-stat-box" style="flex: 1; min-width: 150px; padding: 15px; background: #d1ecf1; border-radius: 5px; text-align: center;">';
        echo '<div style="font-size: 24px; font-weight: bold; color: #0c5460;">ℹ️ ' . $stats['info'] . '</div>';
        echo '<div style="font-size: 12px; color: #0c5460;">اطلاعات</div>';
        echo '</div>';
        echo '</div>';
        
        // فیلترها
        echo '<form method="get" style="margin-bottom: 15px; display: flex; gap: 10px; flex-wrap: wrap;">';
        echo '<input type="hidden" name="page" value="botsino-manager">';
        echo '<input type="hidden" name="tab" value="logs">';
        
        echo '<select name="log_type" style="min-width: 150px;">';
        echo '<option value="">همه انواع</option>';
        echo '<option value="success"' . selected($type_filter, 'success', false) . '>✅ موفق</option>';
        echo '<option value="error"' . selected($type_filter, 'error', false) . '>❌ خطا</option>';
        echo '<option value="warning"' . selected($type_filter, 'warning', false) . '>⚠️ هشدار</option>';
        echo '<option value="info"' . selected($type_filter, 'info', false) . '>ℹ️ اطلاعات</option>';
        echo '</select>';
        
        echo '<select name="log_category" style="min-width: 150px;">';
        echo '<option value="">همه دسته‌ها</option>';
        echo '<option value="user_creation"' . selected($category_filter, 'user_creation', false) . '>ساخت کاربر</option>';
        echo '<option value="message_send"' . selected($category_filter, 'message_send', false) . '>ارسال پیام</option>';
        echo '<option value="queue"' . selected($category_filter, 'queue', false) . '>صف</option>';
        echo '<option value="api"' . selected($category_filter, 'api', false) . '>API</option>';
        echo '<option value="system"' . selected($category_filter, 'system', false) . '>سیستم</option>';
        echo '</select>';
        
        echo '<input type="text" name="log_search" value="' . esc_attr($search) . '" placeholder="جستجو در پیام‌ها..." style="flex: 1; min-width: 200px;">';
        
        echo '<button type="submit" class="button">🔍 فیلتر</button>';
        echo '<a href="?page=botsino-manager&tab=logs" class="button">🔄 پاک کردن فیلتر</a>';
        echo '<a href="?page=botsino-manager&tab=logs&action=clear_logs" class="button button-secondary" onclick="return confirm(\'آیا مطمئن هستید؟\')">🗑️ پاک کردن همه</a>';
        echo '</form>';
        
        // دریافت لاگ‌ها
        $filters = [
            'type' => $type_filter,
            'category' => $category_filter,
            'search' => $search,
            'limit' => 100
        ];
        
        $logs = \BotsinoManager\Helpers\Logger::get_logs($filters);
        
        if (empty($logs)) {
            echo '<p>هیچ لاگی یافت نشد.</p></div>';
            return;
        }
        
        echo '<div style="overflow-x: auto;">';
        echo '<table class="wp-list-table widefat fixed striped" style="font-size: 13px;">';
        echo '<thead><tr>';
        echo '<th style="width: 50px;">ID</th>';
        echo '<th style="width: 80px;">نوع</th>';
        echo '<th style="width: 120px;">دسته‌بندی</th>';
        echo '<th>پیام</th>';
        echo '<th style="width: 140px;">تاریخ</th>';
        echo '<th style="width: 60px;">جزئیات</th>';
        echo '</tr></thead><tbody>';
        
        foreach ($logs as $log) {
            $type_badge = '';
            $row_class = '';
            
            switch($log['type']) {
                case 'success':
                    $type_badge = '<span class="status-badge status-completed">✅ موفق</span>';
                    $row_class = 'log-row-success';
                    break;
                case 'error':
                    $type_badge = '<span class="status-badge status-failed">❌ خطا</span>';
                    $row_class = 'log-row-error';
                    break;
                case 'warning':
                    $type_badge = '<span class="status-badge status-pending">⚠️ هشدار</span>';
                    $row_class = 'log-row-warning';
                    break;
                case 'info':
                    $type_badge = '<span class="status-badge status-processing">ℹ️ اطلاعات</span>';
                    $row_class = 'log-row-info';
                    break;
            }
            
            $category_label = $log['category'];
            switch($log['category']) {
                case 'user_creation': $category_label = '👤 ساخت کاربر'; break;
                case 'message_send': $category_label = '📨 ارسال پیام'; break;
                case 'queue': $category_label = '📋 صف'; break;
                case 'api': $category_label = '🔌 API'; break;
                case 'system': $category_label = '⚙️ سیستم'; break;
            }
            
            echo '<tr class="' . $row_class . '">';
            echo '<td>' . $log['id'] . '</td>';
            echo '<td>' . $type_badge . '</td>';
            echo '<td>' . esc_html($category_label) . '</td>';
            echo '<td>' . esc_html($log['message']) . '</td>';
            echo '<td>' . date('Y-m-d H:i:s', strtotime($log['created_at'])) . '</td>';
            echo '<td>';
            if (!empty($log['data']) && $log['data'] !== 'null') {
                echo '<button type="button" class="button button-small view-log-data" data-log-id="' . $log['id'] . '">👁️ مشاهده</button>';
                echo '<div id="log-data-' . $log['id'] . '" style="display:none;">' . esc_html($log['data']) . '</div>';
            }
            echo '</td>';
            echo '</tr>';
        }
        
        echo '</tbody></table>';
        echo '</div>';
        
        echo '</div>';
        
        // JavaScript برای نمایش جزئیات
        echo '<script>
        jQuery(document).ready(function($) {
            $(".view-log-data").on("click", function() {
                var logId = $(this).data("log-id");
                var data = $("#log-data-" + logId).text();
                
                try {
                    var jsonData = JSON.parse(data);
                    var formatted = JSON.stringify(jsonData, null, 2);
                    alert(formatted);
                } catch(e) {
                    alert(data);
                }
            });
        });
        </script>';
    }
    
    protected function add_styles() {
        echo '<style>
            .botsino-form { background: #fff; padding: 20px; margin: 20px 0; border: 1px solid #ccd0d4; }
            .botsino-dashboard { background: #fff; padding: 20px; margin: 20px 0; }
            
            /* Queue Status Badges */
            .status-badge {
                display: inline-block;
                padding: 4px 8px;
                border-radius: 3px;
                font-size: 12px;
                font-weight: 600;
                white-space: nowrap;
            }
            .status-badge.status-completed {
                background: #d4edda;
                color: #155724;
            }
            .status-badge.status-pending {
                background: #fff3cd;
                color: #856404;
            }
            .status-badge.status-processing {
                background: #d1ecf1;
                color: #0c5460;
            }
            .status-badge.status-failed {
                background: #f8d7da;
                color: #721c24;
            }
            
            /* Queue Row Colors */
            .queue-row-completed {
                background-color: #f0f9f0;
            }
            .queue-row-failed {
                background-color: #fff5f5;
            }
            .queue-row-pending {
                background-color: #fffef0;
            }
            
            /* Buttons */
            .button-small {
                padding: 2px 6px;
                font-size: 12px;
                height: auto;
                line-height: 1.5;
            }
            
            /* Log Row Colors */
            .log-row-success {
                background-color: #f0f9f0;
            }
            .log-row-error {
                background-color: #fff5f5;
            }
            .log-row-warning {
                background-color: #fffef0;
            }
            .log-row-info {
                background-color: #f0f8ff;
            }
        </style>';
    }
}
