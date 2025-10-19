<?php
namespace BotsinoManager\Helpers;

defined('ABSPATH') || exit;

class DateHelper {
    
    public static function toTimestamp($date) {
        if (is_numeric($date)) {
            return (int)$date;
        }
        
        if ($date instanceof \DateTime) {
            return $date->getTimestamp();
        }
        
        $formats = ['Y-m-d H:i:s', 'Y-m-d', 'd-m-Y', 'm/d/Y'];
        foreach ($formats as $format) {
            $date_obj = \DateTime::createFromFormat($format, $date);
            if ($date_obj !== false) {
                return $date_obj->getTimestamp();
            }
        }
        
        error_log("Invalid date format: $date");
        return time();
    }
    
    public static function calculateExpiration($email, $plan_duration, $apply_mode = 'add', $prev_days = 0) {
        global $wpdb;
        $timezone = new \DateTimeZone('Asia/Tehran');
        $now = new \DateTime('now', $timezone);
        
        $table_name = $wpdb->prefix . \BotsinoManager\Config\Constants::EXPIRATIONS_TABLE;
        $user = $wpdb->get_row($wpdb->prepare(
            "SELECT expiration_date FROM {$table_name} WHERE user_email = %s ORDER BY id DESC LIMIT 1",
            $email
        ));
        
        if ($apply_mode === 'extend' && $user && $user->expiration_date) {
            $base_date = \DateTime::createFromFormat('Y-m-d H:i:s', $user->expiration_date, $timezone);
            if ($base_date > $now) {
                error_log("Extending from existing expiration: " . $base_date->format('Y-m-d'));
            } else {
                $base_date = $now;
                error_log("Existing expiration is past - starting from now");
            }
        } else {
            $base_date = $now;
            error_log("Starting from current date");
        }
        
        $duration_days = $plan_duration * 30;
        
        if ($apply_mode === 'extend' && $prev_days > 0) {
            $duration_days += $prev_days;
            error_log("Adding {$prev_days} days from previous plan");
        }
        
        $new_expiration = clone $base_date;
        $new_expiration->add(new \DateInterval("P{$duration_days}D"));
        
        error_log("Final expiration: " . $new_expiration->format('Y-m-d H:i:s'));
        return $new_expiration;
    }
}
