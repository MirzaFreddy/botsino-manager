<?php
/**
 * Plugin Name: Botsino User Management
 * Plugin URI: https://ultrabot.ir
 * Description: افزونه مدیریت کاربران و اتصال به سیستم Botsino
 * Version: 2.0.0
 * Author: MirzaFreddy
 * Author URI: https://ultrabot.ir
 * License: GPL2
 * Text Domain: botsino-manager
 * Domain Path: /languages
 */

defined('ABSPATH') || exit;

// Plugin Constants
define('BOTSINO_VERSION', '2.0.0');
define('BOTSINO_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('BOTSINO_PLUGIN_URL', plugin_dir_url(__FILE__));
define('BOTSINO_PLUGIN_BASENAME', plugin_basename(__FILE__));

// Autoloader
spl_autoload_register(function ($class) {
    $prefix = 'BotsinoManager\\';
    $base_dir = BOTSINO_PLUGIN_DIR . 'includes/';
    
    $len = strlen($prefix);
    if (strncmp($prefix, $class, $len) !== 0) {
        return;
    }
    
    $relative_class = substr($class, $len);
    $file = $base_dir . str_replace('\\', '/', $relative_class) . '.php';
    
    if (file_exists($file)) {
        require $file;
    }
});

// Activation Hook
register_activation_hook(__FILE__, function() {
    require_once BOTSINO_PLUGIN_DIR . 'includes/Core/Activator.php';
    BotsinoManager\Core\Activator::activate();
});

// Deactivation Hook
register_deactivation_hook(__FILE__, function() {
    require_once BOTSINO_PLUGIN_DIR . 'includes/Core/Deactivator.php';
    BotsinoManager\Core\Deactivator::deactivate();
});

// Initialize Plugin
require_once BOTSINO_PLUGIN_DIR . 'includes/Core/Plugin.php';

function run_botsino_manager() {
    $plugin = new BotsinoManager\Core\Plugin();
    $plugin->run();
}

run_botsino_manager();
