<?php
namespace BotsinoManager\Helpers;

defined('ABSPATH') || exit;

class Logger {
    
    protected static $table_name;
    
    public static function init() {
        global $wpdb;
        self::$table_name = $wpdb->prefix . 'botsino_logs';
    }
    
    /**
     * ثبت لاگ
     * 
     * @param string $type نوع لاگ: success, error, warning, info
     * @param string $category دسته‌بندی: user_creation, message_send, queue, api, system
     * @param string $message پیام لاگ
     * @param array $data داده‌های اضافی
     */
    public static function log($type, $category, $message, $data = []) {
        global $wpdb;
        self::init();
        
        $wpdb->insert(self::$table_name, [
            'type' => $type,
            'category' => $category,
            'message' => $message,
            'data' => json_encode($data, JSON_UNESCAPED_UNICODE),
            'created_at' => current_time('mysql')
        ]);
        
        // همچنین در error_log وردپرس هم ثبت کن
        $log_message = sprintf(
            '[Botsino %s] [%s] %s',
            strtoupper($type),
            $category,
            $message
        );
        
        if (!empty($data)) {
            $log_message .= ' | Data: ' . json_encode($data, JSON_UNESCAPED_UNICODE);
        }
        
        error_log($log_message);
    }
    
    // متدهای کمکی
    public static function success($category, $message, $data = []) {
        self::log('success', $category, $message, $data);
    }
    
    public static function error($category, $message, $data = []) {
        self::log('error', $category, $message, $data);
    }
    
    public static function warning($category, $message, $data = []) {
        self::log('warning', $category, $message, $data);
    }
    
    public static function info($category, $message, $data = []) {
        self::log('info', $category, $message, $data);
    }
    
    /**
     * دریافت لاگ‌ها
     */
    public static function get_logs($filters = []) {
        global $wpdb;
        self::init();
        
        $where = ['1=1'];
        $params = [];
        
        if (!empty($filters['type'])) {
            $where[] = 'type = %s';
            $params[] = $filters['type'];
        }
        
        if (!empty($filters['category'])) {
            $where[] = 'category = %s';
            $params[] = $filters['category'];
        }
        
        if (!empty($filters['search'])) {
            $where[] = 'message LIKE %s';
            $params[] = '%' . $wpdb->esc_like($filters['search']) . '%';
        }
        
        $limit = isset($filters['limit']) ? intval($filters['limit']) : 100;
        $offset = isset($filters['offset']) ? intval($filters['offset']) : 0;
        
        $where_clause = implode(' AND ', $where);
        
        if (!empty($params)) {
            $query = $wpdb->prepare(
                "SELECT * FROM " . self::$table_name . " WHERE {$where_clause} ORDER BY created_at DESC LIMIT %d OFFSET %d",
                array_merge($params, [$limit, $offset])
            );
        } else {
            $query = $wpdb->prepare(
                "SELECT * FROM " . self::$table_name . " WHERE {$where_clause} ORDER BY created_at DESC LIMIT %d OFFSET %d",
                $limit,
                $offset
            );
        }
        
        return $wpdb->get_results($query, ARRAY_A);
    }
    
    /**
     * آمار لاگ‌ها
     */
    public static function get_stats() {
        global $wpdb;
        self::init();
        
        $stats = [
            'total' => 0,
            'success' => 0,
            'error' => 0,
            'warning' => 0,
            'info' => 0
        ];
        
        $results = $wpdb->get_results(
            "SELECT type, COUNT(*) as count FROM " . self::$table_name . " GROUP BY type",
            ARRAY_A
        );
        
        foreach ($results as $row) {
            $stats[$row['type']] = intval($row['count']);
            $stats['total'] += intval($row['count']);
        }
        
        return $stats;
    }
    
    /**
     * پاکسازی لاگ‌های قدیمی
     */
    public static function cleanup($days = 30) {
        global $wpdb;
        self::init();
        
        $date = date('Y-m-d H:i:s', strtotime("-{$days} days"));
        
        $deleted = $wpdb->query($wpdb->prepare(
            "DELETE FROM " . self::$table_name . " WHERE created_at < %s",
            $date
        ));
        
        self::info('system', "پاکسازی لاگ‌های قدیمی‌تر از {$days} روز", ['deleted' => $deleted]);
        
        return $deleted;
    }
    
    /**
     * پاکسازی تمام لاگ‌ها
     */
    public static function clear_all() {
        global $wpdb;
        self::init();
        
        return $wpdb->query("TRUNCATE TABLE " . self::$table_name);
    }
}
