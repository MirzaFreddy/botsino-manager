<?php
namespace BotsinoManager\Core;

defined('ABSPATH') || exit;

/**
 * Plugin Deactivation Handler
 */
class Deactivator {
    
    public static function deactivate() {
        self::clear_cron_events();
    }
    
    private static function clear_cron_events() {
        wp_clear_scheduled_hook('botsino_process_queue');
        wp_clear_scheduled_hook('botsino_process_message_queue');
        wp_clear_scheduled_hook('botsino_daily_expiration_check');
    }
}
