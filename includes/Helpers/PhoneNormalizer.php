<?php
namespace BotsinoManager\Helpers;

defined('ABSPATH') || exit;

class PhoneNormalizer {
    
    public static function normalize($phone) {
        $phone = preg_replace('/[^0-9]/', '', $phone);
        
        if (strpos($phone, '0') === 0) {
            return '98' . substr($phone, 1);
        }
        
        if (substr($phone, 0, 2) !== '98') {
            return '98' . $phone;
        }
        
        return $phone;
    }
    
    public static function validate($phone) {
        $normalized = self::normalize($phone);
        return preg_match('/^98\d{10}$/', $normalized);
    }
}
