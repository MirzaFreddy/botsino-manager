<?php
namespace BotsinoManager\Users;

defined('ABSPATH') || exit;

use BotsinoManager\Config\Constants;

class ExpirationManager {
    
    protected $wpdb;
    protected $table_name;
    
    public function __construct() {
        global $wpdb;
        $this->wpdb = $wpdb;
        $this->table_name = $wpdb->prefix . Constants::EXPIRATIONS_TABLE;
    }
    
    public function update($email, $phone, $fullname, $plan_id, $expiration_date) {
        $plan_type = ($plan_id == 1) ? 'free' : 'paid';
        
        error_log("Updating expiration data for: {$email}");
        error_log("Original expiration date: {$expiration_date}");
        
        if ($expiration_date instanceof \DateTime) {
            $date_obj = $expiration_date;
        } else {
            $date_obj = \DateTime::createFromFormat('Y-m-d', $expiration_date);
        }
        
        if (!$date_obj) {
            error_log("Invalid date format: {$expiration_date}");
            return false;
        }
        
        $formatted_date = $date_obj->format('Y-m-d');
        error_log("Formatted expiration date: {$formatted_date}");
        
        $data = [
            'user_email' => $email,
            'phone' => $phone,
            'fullname' => $fullname,
            'plan_type' => $plan_type,
            'plan_id' => (int)$plan_id,
            'expiration_date' => $formatted_date
        ];
        
        $column_check = $this->wpdb->get_var($this->wpdb->prepare(
            "SHOW COLUMNS FROM {$this->table_name} LIKE %s", 
            'updated_at'
        ));
        
        if ($column_check) {
            $data['updated_at'] = current_time('mysql');
        }
        
        $result = $this->wpdb->replace($this->table_name, $data);
        
        if ($result === false) {
            error_log("Database error in update: " . $this->wpdb->last_error);
            return false;
        }
        
        error_log("User expiration data updated: {$email} | {$formatted_date} | plan_id: {$plan_id}");
        return true;
    }
}
