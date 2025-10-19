<?php
namespace BotsinoManager\Database;

defined('ABSPATH') || exit;

use BotsinoManager\Config\Constants;

class DatabaseManager {
    
    protected $wpdb;
    protected $charset_collate;
    
    public function __construct() {
        global $wpdb;
        $this->wpdb = $wpdb;
        $this->charset_collate = $wpdb->get_charset_collate();
    }
    
    public function create_tables() {
        $this->create_queue_table();
        $this->create_message_queue_table();
        $this->create_reminders_table();
        $this->create_expirations_table();
        $this->create_message_logs_table();
        $this->create_logs_table();
    }
    
    private function create_queue_table() {
        $table_name = $this->wpdb->prefix . Constants::QUEUE_TABLE;
        
        $sql = "CREATE TABLE $table_name (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            order_id bigint(20) NOT NULL,
            user_data longtext NOT NULL,
            attempts tinyint(1) DEFAULT 0,
            last_attempt datetime DEFAULT NULL,
            next_attempt datetime DEFAULT NULL,
            status varchar(20) DEFAULT 'pending',
            method varchar(20) DEFAULT 'wp_remote',
            created_at datetime NOT NULL,
            processed_at datetime DEFAULT NULL,
            response longtext,
            PRIMARY KEY (id),
            KEY status (status),
            KEY order_id (order_id)
        ) {$this->charset_collate};";
        
        $this->execute_sql($sql);
    }
    
    private function create_message_queue_table() {
        $table_name = $this->wpdb->prefix . Constants::MESSAGE_QUEUE_TABLE;
        
        $sql = "CREATE TABLE $table_name (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            phone varchar(20) NOT NULL,
            fullname varchar(255) NOT NULL,
            username varchar(100) NOT NULL,
            password varchar(100) NOT NULL,
            message_type varchar(20) DEFAULT 'credentials',
            user_type varchar(10) DEFAULT 'new',
            status varchar(20) DEFAULT 'pending',
            attempts tinyint(3) DEFAULT 0,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            scheduled_at datetime DEFAULT NULL,
            processed_at datetime DEFAULT NULL,
            response TEXT NULL,
            PRIMARY KEY (id),
            KEY status_scheduled (status, scheduled_at)
        ) {$this->charset_collate};";
        
        $this->execute_sql($sql);
    }
    
    private function create_reminders_table() {
        $table_name = $this->wpdb->prefix . Constants::REMINDERS_TABLE;
        
        $sql = "CREATE TABLE $table_name (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            plan_type varchar(20) NOT NULL,
            days_before smallint(5) NOT NULL,
            message_type varchar(20) NOT NULL,
            message_text text NOT NULL,
            coupon_code varchar(100) DEFAULT NULL,
            coupon_type varchar(20) DEFAULT 'none',
            product_ids TEXT NULL,
            auto_settings TEXT NULL,
            active tinyint(1) DEFAULT 1,
            PRIMARY KEY (id)
        ) {$this->charset_collate};";
        
        $this->execute_sql($sql);
    }
    
    private function create_expirations_table() {
        $table_name = $this->wpdb->prefix . Constants::EXPIRATIONS_TABLE;
        
        $sql = "CREATE TABLE $table_name (
            id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            user_email varchar(100) NOT NULL,
            phone varchar(20) NOT NULL,
            fullname varchar(255) NOT NULL,
            plan_type varchar(20) NOT NULL,
            plan_id int(11) DEFAULT NULL,
            expiration_date date NOT NULL,
            last_reminder_1 int(1) DEFAULT 0,
            last_reminder_3 int(1) DEFAULT 0,
            last_reminder_7 int(1) DEFAULT 0,
            last_reminder_30 int(1) DEFAULT 0,
            updated_at datetime DEFAULT NULL,
            PRIMARY KEY (id),
            UNIQUE KEY user_email (user_email),
            KEY expiration_date (expiration_date)
        ) {$this->charset_collate};";
        
        $this->execute_sql($sql);
    }
    
    private function create_message_logs_table() {
        $table_name = $this->wpdb->prefix . Constants::MESSAGE_LOGS_TABLE;
        
        $sql = "CREATE TABLE $table_name (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            phone varchar(20) NOT NULL,
            type varchar(20) NOT NULL,
            content text NOT NULL,
            status varchar(20) NOT NULL,
            notes text,
            created_at datetime NOT NULL,
            PRIMARY KEY (id),
            KEY phone (phone),
            KEY created_at (created_at)
        ) {$this->charset_collate};";
        
        $this->execute_sql($sql);
    }
    
    private function create_logs_table() {
        $table_name = $this->wpdb->prefix . 'botsino_logs';
        
        $sql = "CREATE TABLE $table_name (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            type varchar(20) NOT NULL,
            category varchar(50) NOT NULL,
            message text NOT NULL,
            data longtext,
            created_at datetime NOT NULL,
            PRIMARY KEY (id),
            KEY type (type),
            KEY category (category),
            KEY created_at (created_at)
        ) {$this->charset_collate};";
        
        $this->execute_sql($sql);
    }
    
    private function execute_sql($sql) {
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
    }
    
    public function table_exists($table_constant) {
        $table_name = $this->wpdb->prefix . $table_constant;
        return $this->wpdb->get_var("SHOW TABLES LIKE '$table_name'") == $table_name;
    }
}
