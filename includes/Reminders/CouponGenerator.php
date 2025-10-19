<?php
namespace BotsinoManager\Reminders;

defined('ABSPATH') || exit;

class CouponGenerator {
    
    public function generate_auto($settings, $email, $product_ids = '') {
        if (is_string($settings)) {
            $settings = json_decode($settings, true);
        }
        
        if (!is_array($settings)) {
            error_log("Invalid auto coupon settings");
            return false;
        }
        
        $prefix = $settings['prefix'] ?? 'RENEW-';
        $length = $settings['length'] ?? 8;
        
        $characters = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789';
        $random = '';
        for ($i = 0; $i < $length; $i++) {
            $random .= $characters[rand(0, strlen($characters) - 1)];
        }
        
        $coupon_code = $prefix . $random;
        
        $coupon = new \WC_Coupon();
        $coupon->set_code($coupon_code);
        $coupon->set_discount_type('percent');
        $coupon->set_amount($settings['discount'] ?? 10);
        $coupon->set_usage_limit(1);
        $coupon->set_usage_limit_per_user(1);
        $coupon->set_date_expires(time() + ($settings['expiry'] ?? 30) * DAY_IN_SECONDS);
        $coupon->set_email_restrictions([$email]);
        
        if (!empty($product_ids)) {
            $ids = explode(',', $product_ids);
            $coupon->set_product_ids($ids);
        }
        
        try {
            $coupon->save();
            error_log("Auto coupon created: $coupon_code for $email");
            return $coupon_code;
        } catch (\Exception $e) {
            error_log("Error creating auto coupon: " . $e->getMessage());
            return false;
        }
    }
    
    public function create_renewal($coupon_code, $email, $product_ids = '') {
        if (empty($coupon_code)) {
            $coupon_code = get_option('botsino_global_renewal_coupon');
            if (empty($coupon_code)) return false;
        }
        
        $coupon_id = wc_get_coupon_id_by_code($coupon_code);
        
        if (!$coupon_id) {
            $coupon = new \WC_Coupon();
            $coupon->set_code($coupon_code);
            $coupon->set_discount_type('percent');
            $coupon->set_amount(10);
            $coupon->set_usage_limit(1);
            $coupon->set_date_expires(time() + (30 * DAY_IN_SECONDS));
            
            if (!empty($product_ids)) {
                $ids = explode(',', $product_ids);
                $coupon->set_product_ids($ids);
                error_log("Coupon product restrictions: " . print_r($ids, true));
            }
            
            try {
                $coupon->save();
                error_log("Renewal coupon created: {$coupon_code}");
                return true;
            } catch (\Exception $e) {
                error_log("Coupon creation error: " . $e->getMessage());
                return false;
            }
        }
        
        $coupon = new \WC_Coupon($coupon_id);
        
        if ($coupon->get_date_expires() && $coupon->get_date_expires()->getTimestamp() < time()) {
            $coupon->set_date_expires(time() + (30 * DAY_IN_SECONDS));
            $coupon->save();
            error_log("Coupon expiry extended: {$coupon_code}");
        }
        
        return true;
    }
}
