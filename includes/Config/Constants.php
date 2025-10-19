<?php
namespace BotsinoManager\Config;

defined('ABSPATH') || exit;

/**
 * Plugin Constants
 */
class Constants {
    
    const QUEUE_TABLE = 'botsino_queue';
    const MESSAGE_QUEUE_TABLE = 'botsino_message_queue';
    const REMINDERS_TABLE = 'botsino_reminders';
    const EXPIRATIONS_TABLE = 'botsino_user_expirations';
    const MESSAGE_LOGS_TABLE = 'botsino_message_logs';
    
    const MAX_ATTEMPTS = 3;
    const PASSWORD_SALT = 'botsino_salt_string';
    
    const PLANS = [
        1 => 'رایگان',
        2 => 'پایه',
        3 => 'استاندارد',
        4 => 'پرمیوم',
        5 => 'پایه',
        6 => 'استاندارد',
        7 => 'پایه',
        8 => 'استاندارد',
        9 => 'پایه'
    ];
    
    const STATUSES = [
        0 => ['label' => 'مسدودشده', 'class' => 'blocked'],
        1 => ['label' => 'غیرفعال', 'class' => 'inactive'],
        2 => ['label' => 'فعال', 'class' => 'active']
    ];
    
    const PLAN_DURATIONS = [
        1 => 3,      // رایگان: 3 روز
        2 => 3,      // پایه 12 ماه
        3 => 6,      // استاندارد 12 ماه
        4 => 12,     // پرمیوم 12 ماه
        5 => 6,      // پایه 6 ماه
        6 => 6,      // استاندارد 6 ماه
        7 => 3,      // پایه 3 ماه
        8 => 3,      // استاندارد 3 ماه
        9 => 1       // پایه 1 ماه
    ];
    
    const SKU_DURATION_MAPPING = [
        'plan1' => 0.24,   // رایگان (3 روز)
        'plan2' => 12,     // پایه 12 ماه
        'plan3' => 12,     // استاندارد 12 ماه
        'plan4' => 12,     // پرمیوم 12 ماه
        'plan5' => 6,      // پایه 6 ماه
        'plan6' => 6,      // استاندارد 6 ماه
        'plan7' => 3,      // پایه 3 ماه
        'plan8' => 3,      // استاندارد 3 ماه
        'plan9' => 1       // پایه 1 ماه
    ];
    
    const REVERSE_PLAN_MAPPING = [
        1 => 1,
        2 => 2,
        3 => 3,
        4 => 4,
        5 => 2,
        6 => 3,
        7 => 2,
        8 => 3,
        9 => 2
    ];
}
