<?php
namespace BotsinoManager\Admin;

defined('ABSPATH') || exit;

class AdminMenu {
    
    public function register_menus() {
        add_menu_page(
            'مدیریت Botsino',
            'Botsino',
            'manage_options',
            'botsino-manager',
            [$this, 'render_main_page'],
            'dashicons-businessperson',
            30
        );
        
        add_submenu_page(
            'botsino-manager',
            'تنظیمات Botsino',
            'تنظیمات',
            'manage_options',
            'botsino-settings',
            [$this, 'render_settings_page']
        );
        
        add_submenu_page(
            'botsino-manager',
            'مدیریت یادآوری تمدید اشتراک',
            'یادآوری اشتراک',
            'manage_options',
            'botsino-reminders',
            [$this, 'render_reminders_page']
        );
    }
    
    public function render_main_page() {
        require_once BOTSINO_PLUGIN_DIR . 'includes/Admin/Views/MainPage.php';
        $page = new \BotsinoManager\Admin\Views\MainPage();
        $page->render();
    }
    
    public function render_settings_page() {
        require_once BOTSINO_PLUGIN_DIR . 'includes/Admin/SettingsPage.php';
        $page = new SettingsPage();
        $page->render();
    }
    
    public function render_reminders_page() {
        require_once BOTSINO_PLUGIN_DIR . 'includes/Admin/Views/RemindersPage.php';
        $page = new \BotsinoManager\Admin\Views\RemindersPage();
        $page->render();
    }
    
    public function handle_actions() {
        if (!current_user_can('manage_options')) {
            return;
        }
        
        if (isset($_POST['botsino_action'])) {
            $this->handle_user_actions();
        }
    }
    
    protected function handle_user_actions() {
        $action = sanitize_text_field($_POST['botsino_action']);
        
        switch ($action) {
            case 'create_user':
                $this->create_user();
                break;
            case 'process_queue':
                $this->process_queue();
                break;
            case 'clear_logs':
                $this->clear_logs();
                break;
        }
    }
    
    protected function create_user() {
        require_once BOTSINO_PLUGIN_DIR . 'includes/Users/UserManager.php';
        $user_manager = new \BotsinoManager\Users\UserManager();
        
        $email = sanitize_email($_POST['user_email']);
        $phone = sanitize_text_field($_POST['user_phone']);
        $fullname = sanitize_text_field($_POST['fullname']);
        $plan_id = (int)$_POST['plan_id'];
        $status = (int)$_POST['status'];
        
        $result = $user_manager->create($email, $phone, $plan_id, $fullname, $status);
        
        if ($result['success']) {
            add_settings_error('botsino_messages', 'botsino_message', 'کاربر با موفقیت ایجاد شد', 'updated');
        } else {
            add_settings_error('botsino_messages', 'botsino_message', 'خطا در ایجاد کاربر', 'error');
        }
        
        settings_errors('botsino_messages');
    }
    
    protected function process_queue() {
        require_once BOTSINO_PLUGIN_DIR . 'includes/Queue/QueueManager.php';
        $queue = new \BotsinoManager\Queue\QueueManager();
        $processed = $queue->process();
        
        add_settings_error('botsino_messages', 'botsino_message', "{$processed} درخواست پردازش شد", 'updated');
        settings_errors('botsino_messages');
    }
    
    protected function clear_logs() {
        $log_file = ABSPATH . 'wp-content/debug.log';
        if (file_exists($log_file)) {
            file_put_contents($log_file, '');
            add_settings_error('botsino_messages', 'botsino_message', 'فایل لاگ پاک شد', 'updated');
        }
        settings_errors('botsino_messages');
    }
}
