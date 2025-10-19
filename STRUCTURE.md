# ساختار کامل پلاگین Botsino Manager

## 📐 معماری کلی

این پلاگین بر اساس معماری **Modular MVC** طراحی شده است.

## 🗂️ نقشه کامل فایل‌ها

```
botsino-manager/
│
├── botsino-manager.php              # فایل اصلی پلاگین (Bootstrap)
│   └── Responsibilities:
│       ├── تعریف ثابت‌ها
│       ├── Autoloader
│       ├── Hook‌های فعال‌سازی/غیرفعال‌سازی
│       └── اجرای Plugin class
│
├── includes/
│   │
│   ├── Core/                        # هسته اصلی
│   │   ├── Plugin.php               # کلاس اصلی پلاگین
│   │   │   └── وظایف:
│   │   │       ├── بارگذاری وابستگی‌ها
│   │   │       ├── تنظیم زمان
│   │   │       ├── تعریف Hook‌ها
│   │   │       └── اجرای Loader
│   │   │
│   │   ├── Loader.php               # مدیریت Hook‌ها
│   │   │   └── وظایف:
│   │   │       ├── ثبت Action‌ها
│   │   │       ├── ثبت Filter‌ها
│   │   │       ├── ثبت Shortcode‌ها
│   │   │       └── اجرای همه Hook‌ها
│   │   │
│   │   ├── Activator.php            # مدیریت فعال‌سازی
│   │   │   └── وظایف:
│   │   │       ├── ایجاد جداول
│   │   │       ├── برنامه‌ریزی Cron‌ها
│   │   │       ├── تنظیم Options پیش‌فرض
│   │   │       └── فعال‌سازی Logging
│   │   │
│   │   └── Deactivator.php          # مدیریت غیرفعال‌سازی
│   │       └── وظایف:
│   │           └── پاک کردن Cron Job‌ها
│   │
│   ├── Config/                      # تنظیمات و ثابت‌ها
│   │   └── Constants.php
│   │       └── شامل:
│   │           ├── نام جداول
│   │           ├── تنظیمات پلن‌ها
│   │           ├── نقشه وضعیت‌ها
│   │           ├── مدت زمان پلن‌ها
│   │           └── نگاشت SKU به پلن
│   │
│   ├── Database/                    # مدیریت دیتابیس
│   │   └── DatabaseManager.php
│   │       └── وظایف:
│   │           ├── ایجاد جدول صف
│   │           ├── ایجاد جدول پیام‌ها
│   │           ├── ایجاد جدول یادآوری‌ها
│   │           ├── ایجاد جدول انقضاها
│   │           └── ایجاد جدول لاگ‌ها
│   │
│   ├── Helpers/                     # توابع کمکی
│   │   ├── PhoneNormalizer.php
│   │   │   └── وظایف:
│   │   │       ├── نرمال‌سازی شماره تلفن
│   │   │       └── اعتبارسنجی شماره
│   │   │
│   │   └── DateHelper.php
│   │       └── وظایف:
│   │           ├── تبدیل تاریخ به Timestamp
│   │           └── محاسبه تاریخ انقضا
│   │
│   ├── API/                         # ارتباط با API
│   │   └── APIClient.php
│   │       └── وظایف:
│   │           ├── دریافت اطلاعات کاربر
│   │           ├── ایجاد کاربر
│   │           ├── به‌روزرسانی کاربر
│   │           └── دریافت لیست کاربران
│   │
│   ├── Users/                       # مدیریت کاربران
│   │   ├── UserManager.php
│   │   │   └── وظایف:
│   │   │       ├── ایجاد کاربر جدید
│   │   │       ├── به‌روزرسانی کاربر موجود
│   │   │       ├── ارسال پیام تایید فوری
│   │   │       └── مدیریت رمز عبور
│   │   │
│   │   └── ExpirationManager.php
│   │       └── وظایف:
│   │           └── به‌روزرسانی تاریخ انقضا
│   │
│   ├── Queue/                       # سیستم صف
│   │   ├── QueueManager.php
│   │   │   └── وظایف:
│   │   │       ├── افزودن سفارش به صف
│   │   │       ├── پردازش صف
│   │   │       ├── استخراج پلن از سفارش
│   │   │       └── پاکسازی صف
│   │   │
│   │   └── MessageQueue.php
│   │       └── وظایف:
│   │           ├── افزودن پیام به صف
│   │           ├── پردازش صف پیام‌ها
│   │           ├── ارسال پیام اطلاعات
│   │           └── پاکسازی پیام‌ها
│   │
│   ├── Notifications/               # سیستم اطلاع‌رسانی
│   │   ├── WhatsAppSender.php
│   │   │   └── وظایف:
│   │   │       ├── ارسال پیام تایید فوری
│   │   │       ├── ارسال اطلاعات ورود
│   │   │       ├── ارسال یادآوری
│   │   │       └── ارسال پیام متنی عمومی
│   │   │
│   │   └── SMSSender.php
│   │       └── وظایف:
│   │           ├── ارسال با الگو (Pattern)
│   │           ├── ارسال با API Key
│   │           ├── ارسال با Username/Password
│   │           └── ارسال یادآوری
│   │
│   ├── Reminders/                   # سیستم یادآوری
│   │   ├── ReminderManager.php
│   │   │   └── وظایف:
│   │   │       ├── بررسی انقضاها
│   │   │       ├── پردازش یادآوری‌ها
│   │   │       ├── ارسال پیام به کاربران
│   │   │       └── ثبت لاگ پیام‌ها
│   │   │
│   │   └── CouponGenerator.php
│   │       └── وظایف:
│   │           ├── تولید کوپن خودکار
│   │           └── ایجاد کوپن تمدید
│   │
│   ├── Admin/                       # پنل مدیریت
│   │   ├── AdminMenu.php
│   │   │   └── وظایف:
│   │   │       ├── ثبت منوها
│   │   │       ├── رندر صفحات
│   │   │       ├── مدیریت Action‌ها
│   │   │       └── ایجاد/پردازش کاربران
│   │   │
│   │   └── SettingsPage.php
│   │       └── وظایف:
│   │           ├── ذخیره تنظیمات
│   │           └── رندر صفحه تنظیمات
│   │
│   └── Public/                      # بخش عمومی
│       └── FreePlanForm.php
│           └── وظایف:
│               ├── رندر فرم
│               ├── مدیریت AJAX
│               ├── ایجاد سفارش
│               └── افزودن به صف
│
├── README.md                        # مستندات اصلی
├── MIGRATION_GUIDE.md              # راهنمای مهاجرت
└── STRUCTURE.md                    # این فایل
```

## 🔄 جریان اجرای کد

### 1. فعال‌سازی پلاگین
```
botsino-manager.php
└── Activator::activate()
    ├── DatabaseManager::create_tables()
    ├── Schedule Cron Jobs
    ├── Set Default Options
    └── Enable Logging
```

### 2. اجرای پلاگین
```
botsino-manager.php
└── run_botsino_manager()
    └── Plugin::__construct()
        ├── load_dependencies()
        ├── set_locale()
        ├── define_admin_hooks()
        ├── define_public_hooks()
        └── define_cron_hooks()
    └── Plugin::run()
        └── Loader::run()
```

### 3. پردازش سفارش WooCommerce
```
WooCommerce Order Completed
└── QueueManager::add_order_to_queue()
    ├── Extract Plan from Order
    ├── Calculate Expiration
    └── UserManager::create()
        ├── APIClient::create_user()
        ├── MessageQueue::add()
        └── ExpirationManager::update()
```

### 4. پردازش صف (Cron Job)
```
Cron: botsino_process_queue
└── QueueManager::process()
    ├── Get Pending Items
    └── For Each Item:
        ├── UserManager::create()
        └── MessageQueue::add()
```

### 5. ارسال پیام (Cron Job)
```
Cron: botsino_process_message_queue
└── MessageQueue::process()
    ├── Get Pending Messages
    └── For Each Message:
        └── WhatsAppSender::send_credentials()
```

### 6. بررسی یادآوری (Cron Job روزانه)
```
Cron: botsino_daily_expiration_check
└── ReminderManager::check_expirations()
    ├── Get Active Reminders
    └── For Each Reminder:
        ├── Find Users to Remind
        ├── CouponGenerator::generate()
        └── WhatsAppSender::send_reminder()
```

## 🎨 الگوهای طراحی استفاده شده

### 1. **Dependency Injection**
```php
class UserManager {
    protected $api;
    protected $expiration_manager;
    
    public function __construct() {
        $this->api = new APIClient();
        $this->expiration_manager = new ExpirationManager();
    }
}
```

### 2. **Single Responsibility**
هر کلاس یک مسئولیت دارد:
- `WhatsAppSender` فقط واتساپ می‌فرستد
- `SMSSender` فقط SMS می‌فرستد
- `DatabaseManager` فقط دیتابیس را مدیریت می‌کند

### 3. **Factory Pattern** (در CouponGenerator)
```php
public function generate_auto($settings, $email, $product_ids)
public function create_renewal($coupon_code, $email, $product_ids)
```

### 4. **Hook/Observer Pattern** (در Loader)
```php
public function add_action($hook, $component, $callback)
public function add_filter($hook, $component, $callback)
```

## 📈 مزایای ساختار فعلی

### قابلیت نگهداری (Maintainability)
- ✅ هر ماژول مستقل است
- ✅ تغییرات محلی هستند
- ✅ عیب‌یابی آسان

### مقیاس‌پذیری (Scalability)
- ✅ افزودن ماژول جدید آسان
- ✅ توسعه موازی ممکن است
- ✅ تست کردن مستقل

### عملکرد (Performance)
- ✅ Autoloading بهینه
- ✅ فقط کلاس‌های مورد نیاز بارگذاری می‌شوند
- ✅ حافظه کمتر مصرف می‌شود

### خوانایی (Readability)
- ✅ کد واضح و قابل فهم
- ✅ Namespace‌های منظم
- ✅ نام‌گذاری معنادار

این ساختار طبق استانداردهای **WordPress Coding Standards** و **PSR-4** طراحی شده است.
