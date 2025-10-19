<?php
namespace BotsinoManager\Core;

defined('ABSPATH') || exit;

/**
 * Main Plugin Class
 */
class Plugin {
    
    protected $loader;
    protected $version;
    
    public function __construct() {
        $this->version = BOTSINO_VERSION;
        $this->load_dependencies();
        $this->set_locale();
        $this->define_admin_hooks();
        $this->define_public_hooks();
        $this->define_cron_hooks();
    }
    
    private function load_dependencies() {
        require_once BOTSINO_PLUGIN_DIR . 'includes/Core/Loader.php';
        require_once BOTSINO_PLUGIN_DIR . 'includes/Config/Constants.php';
        require_once BOTSINO_PLUGIN_DIR . 'includes/Database/DatabaseManager.php';
        require_once BOTSINO_PLUGIN_DIR . 'includes/Helpers/PhoneNormalizer.php';
        require_once BOTSINO_PLUGIN_DIR . 'includes/Helpers/DateHelper.php';
        require_once BOTSINO_PLUGIN_DIR . 'includes/API/APIClient.php';
        require_once BOTSINO_PLUGIN_DIR . 'includes/Queue/QueueManager.php';
        require_once BOTSINO_PLUGIN_DIR . 'includes/Queue/MessageQueue.php';
        require_once BOTSINO_PLUGIN_DIR . 'includes/Users/UserManager.php';
        require_once BOTSINO_PLUGIN_DIR . 'includes/Users/ExpirationManager.php';
        require_once BOTSINO_PLUGIN_DIR . 'includes/Reminders/ReminderManager.php';
        require_once BOTSINO_PLUGIN_DIR . 'includes/Reminders/CouponGenerator.php';
        require_once BOTSINO_PLUGIN_DIR . 'includes/Notifications/SMSSender.php';
        require_once BOTSINO_PLUGIN_DIR . 'includes/Notifications/WhatsAppSender.php';
        require_once BOTSINO_PLUGIN_DIR . 'includes/Admin/AdminMenu.php';
        require_once BOTSINO_PLUGIN_DIR . 'includes/Admin/SettingsPage.php';
        require_once BOTSINO_PLUGIN_DIR . 'includes/Public/FreePlanForm.php';
        
        $this->loader = new Loader();
    }
    
    private function set_locale() {
        date_default_timezone_set('Asia/Tehran');
    }
    
    private function define_admin_hooks() {
        $admin_menu = new \BotsinoManager\Admin\AdminMenu();
        $this->loader->add_action('admin_menu', $admin_menu, 'register_menus');
        $this->loader->add_action('admin_init', $admin_menu, 'handle_actions');
        
        $settings = new \BotsinoManager\Admin\SettingsPage();
        $this->loader->add_action('admin_init', $settings, 'save_settings');
    }
    
    private function define_public_hooks() {
        $free_plan = new \BotsinoManager\PublicArea\FreePlanForm();
        $this->loader->add_shortcode('botsino_free_plan_popup', $free_plan, 'render');
        $this->loader->add_action('wp_ajax_botsino_process_free_plan', $free_plan, 'handle_ajax');
        $this->loader->add_action('wp_ajax_nopriv_botsino_process_free_plan', $free_plan, 'handle_ajax');
        
        // WooCommerce Integration
        $queue_manager = new \BotsinoManager\Queue\QueueManager();
        $this->loader->add_action('woocommerce_order_status_completed', $queue_manager, 'add_order_to_queue', 10, 1);
    }
    
    private function define_cron_hooks() {
        // Cron Schedules
        $this->loader->add_filter('cron_schedules', $this, 'add_cron_intervals');
        
        // Queue Processing
        $queue_manager = new \BotsinoManager\Queue\QueueManager();
        $this->loader->add_action('botsino_process_queue', $queue_manager, 'process');
        
        // Message Queue Processing
        $message_queue = new \BotsinoManager\Queue\MessageQueue();
        $this->loader->add_action('botsino_process_message_queue', $message_queue, 'process');
        
        // Daily Expiration Check
        $reminder_manager = new \BotsinoManager\Reminders\ReminderManager();
        $this->loader->add_action('botsino_daily_expiration_check', $reminder_manager, 'check_expirations');
    }
    
    public function add_cron_intervals($schedules) {
        $schedules['every_minute'] = [
            'interval' => 60,
            'display' => 'هر دقیقه'
        ];
        $schedules['every_minute_messages'] = [
            'interval' => 60,
            'display' => 'هر دقیقه - پیام‌ها'
        ];
        return $schedules;
    }
    
    public function run() {
        $this->loader->run();
    }
    
    public function get_version() {
        return $this->version;
    }
}
