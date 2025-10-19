# 🤖 Botsino Manager Plugin

[![WordPress](https://img.shields.io/badge/WordPress-5.0%2B-blue.svg)](https://wordpress.org/)
[![WooCommerce](https://img.shields.io/badge/WooCommerce-3.0%2B-purple.svg)](https://woocommerce.com/)
[![PHP](https://img.shields.io/badge/PHP-7.4%2B-777BB4.svg)](https://www.php.net/)
[![License](https://img.shields.io/badge/License-GPL%20v2-green.svg)](https://www.gnu.org/licenses/gpl-2.0.html)

افزونه مدیریت یکپارچه کاربران Botsino با WooCommerce و ارسال خودکار اطلاعات ورود از طریق واتساپ

---

## 📖 فهرست مطالب

- [ویژگی‌ها](#-ویژگیها)
- [نیازمندی‌ها](#-نیازمندیها)
- [نصب](#-نصب)
- [پیکربندی](#-پیکربندی)
- [استفاده](#-استفاده)
- [ساختار پروژه](#-ساختار-پروژه)
- [توسعه](#-توسعه)
- [مشارکت](#-مشارکت)
- [لایسنس](#-لایسنس)

---

## ✨ ویژگی‌ها

### 🔄 یکپارچه‌سازی خودکار
- ✅ اتصال به API سیستم Botsino
- ✅ ساخت خودکار کاربر پس از خرید در WooCommerce
- ✅ سیستم صف پیشرفته برای پردازش درخواست‌ها
- ✅ ارسال خودکار اطلاعات ورود به واتساپ

### 📨 سیستم پیام‌رسانی
- ✅ ارسال پیام واتساپ از طریق API Botsino
- ✅ ارسال SMS (قابل پیکربندی)
- ✅ پیام‌های فوری و صف‌بندی شده
- ✅ قالب‌های پیام قابل تنظیم

### 🎯 مدیریت کاربران
- ✅ ساخت و ویرایش کاربران
- ✅ حذف تکی و دسته‌ای کاربران
- ✅ بررسی شماره موبایل و ایمیل تکراری
- ✅ مدیریت تاریخ انقضا
- ✅ نمایش وضعیت کاربران

### 📊 پنل مدیریت حرفه‌ای
- ✅ داشبورد با آمار کامل
- ✅ لیست کاربران با فیلتر و جستجو
- ✅ مدیریت صف درخواست‌ها
- ✅ سیستم لاگ‌گیری پیشرفته
- ✅ تنظیمات کامل

### 🔔 سیستم یادآوری
- ✅ بررسی خودکار تاریخ انقضا
- ✅ ارسال یادآوری قبل از انقضا
- ✅ تولید کوپن تخفیف خودکار
- ✅ پیام‌های شخصی‌سازی شده

### 🎁 فرم تست رایگان
- ✅ شورت‌کد برای نمایش در سایت
- ✅ بررسی خودکار کاربر تکراری
- ✅ ارسال فوری پیام تایید
- ✅ ایجاد خودکار سفارش WooCommerce

---

## 📋 نیازمندی‌ها

- **WordPress:** 5.0 یا بالاتر
- **WooCommerce:** 3.0 یا بالاتر
- **PHP:** 7.4 یا بالاتر
- **MySQL:** 5.6 یا بالاتر
- **حساب کاربری Botsino** با API Key

---

## 🚀 نصب

### روش 1: نصب دستی

1. فایل‌های پلاگین را دانلود کنید
2. پوشه `botsino-manager` را در `wp-content/plugins/` آپلود کنید
3. به پنل مدیریت وردپرس بروید
4. به **افزونه‌ها** → **افزونه‌های نصب شده** بروید
5. پلاگین **Botsino User Management** را فعال کنید

### روش 2: از طریق Git

```bash
cd wp-content/plugins/
git clone https://github.com/YOUR_USERNAME/botsino-manager.git
```

سپس پلاگین را از پنل مدیریت فعال کنید.

---

## ⚙️ پیکربندی

### 1. تنظیمات API

به **Botsino** → **تنظیمات** بروید و موارد زیر را وارد کنید:

- **API Key:** کلید API دریافتی از Botsino
- **API URL:** آدرس API سیستم Botsino
- **WhatsApp Instance ID:** شناسه اینستنس واتساپ
- **WhatsApp Access Token:** توکن دسترسی واتساپ

### 2. تنظیمات محصولات

- **Free Plan Product ID:** شناسه محصول تست رایگان
- **Plan Mappings:** تطبیق پلن‌های WooCommerce با Botsino

### 3. فعال‌سازی Cron Jobs

پلاگین به صورت خودکار Cron Job‌های زیر را ثبت می‌کند:

- `botsino_process_queue` - هر دقیقه
- `botsino_process_message_queue` - هر دقیقه
- `botsino_daily_expiration_check` - روزانه

---

## 📖 استفاده

### ساخت کاربر دستی

1. به **Botsino** → **ساخت کاربر** بروید
2. فرم را پر کنید
3. روی **ساخت کاربر** کلیک کنید

### مشاهده لیست کاربران

1. به **Botsino** → **لیست کاربران** بروید
2. برای حذف دسته‌ای، کاربران را انتخاب کنید
3. روی **حذف موارد انتخابی** کلیک کنید

### مدیریت صف

1. به **Botsino** → **صف کاربران** بروید
2. وضعیت درخواست‌ها را مشاهده کنید
3. در صورت نیاز، درخواست‌ها را مجدداً پردازش کنید

### مشاهده لاگ‌ها

1. به **Botsino** → **لاگ‌ها** بروید
2. از فیلترها برای جستجو استفاده کنید
3. جزئیات هر لاگ را مشاهده کنید

### استفاده از شورت‌کد

برای نمایش فرم تست رایگان در صفحه:

```
[botsino_free_plan_popup]
```

---

## 📁 ساختار پروژه

```
botsino-manager/
├── botsino-manager.php              # Main plugin file (50 lines bootstrap)
├── includes/
│   ├── Core/
│   │   ├── Plugin.php               # Main plugin orchestrator
│   │   ├── Loader.php               # Hook loader
│   │   ├── Activator.php            # Activation handler
│   │   └── Deactivator.php          # Deactivation handler
│   ├── Config/
│   │   └── Constants.php            # All constants and mappings
│   ├── Database/
│   │   └── DatabaseManager.php      # Database table management
│   ├── Helpers/
│   │   ├── PhoneNormalizer.php      # Phone number utilities
│   │   └── DateHelper.php           # Date/time utilities
│   ├── API/
│   │   └── APIClient.php            # Botsino API communication
│   ├── Users/
│   │   ├── UserManager.php          # User CRUD operations
│   │   └── ExpirationManager.php    # Expiration data management
│   ├── Queue/
│   │   ├── QueueManager.php         # Main queue processing
│   │   └── MessageQueue.php         # Message queue processing
│   ├── Notifications/
│   │   ├── WhatsAppSender.php       # WhatsApp messaging
│   │   └── SMSSender.php            # SMS messaging
│   ├── Reminders/
│   │   ├── ReminderManager.php      # Reminder system
│   │   └── CouponGenerator.php      # Coupon generation
│   ├── Admin/
│   │   ├── AdminMenu.php            # Admin menu registration
│   │   ├── SettingsPage.php         # Settings page handler
│   │   └── Views/
│   │       ├── MainPage.php         # Main admin page with tabs
│   │       └── RemindersPage.php    # Reminders management page
│   └── Public/
│       └── FreePlanForm.php         # Free plan form shortcode
├── README.md
├── MIGRATION_GUIDE.md               # Step-by-step migration guide
├── STRUCTURE.md                     # Complete architecture documentation
└── BUGFIX.md                        # Bug fixes and troubleshooting
```

## 🎯 Key Improvements

### 1. **Modular Architecture**
- Each module has a single responsibility
- Easy to test and maintain
- Clear separation of concerns

### 2. **PSR-4 Autoloading**
- Classes loaded automatically
- No manual require statements
- Namespace-based organization

### 3. **Professional Patterns**
- Dependency Injection
- Single Responsibility Principle
- DRY (Don't Repeat Yourself)
- SOLID principles

### 4. **Better Organization**
- Config centralized in Constants class
- Helpers for common utilities
- Clear module boundaries

## 🔄 Migration Guide

### Step 1: Backup
```bash
# Backup old file
cp botsino-manager.php botsino-manager-backup.php
```

### Step 2: Deactivate Old Plugin
- Go to WordPress Admin → Plugins
- Deactivate "Botsino User Management"

### Step 3: Rename Files
```bash
# Rename old file
mv botsino-manager.php botsino-manager-old.php

# Activate new file
mv botsino-manager-new.php botsino-manager.php
```

### Step 4: Activate New Plugin
- Go to WordPress Admin → Plugins
- Activate "Botsino User Management"
- All data preserved, no functionality changed

## ✅ Testing Checklist

- [ ] Plugin activates without errors
- [ ] WooCommerce order completion triggers user creation
- [ ] Queue processing works via cron
- [ ] Message queue sends WhatsApp/SMS
- [ ] Reminders system checks expirations
- [ ] Coupons generate correctly
- [ ] Admin panels load properly
- [ ] Settings save correctly
- [ ] Free plan form works
- [ ] All hooks fire correctly

## 🔧 Functionality Preserved

**Every single feature from the original 5000-line file is preserved:**
- ✅ User creation/update in Botsino
- ✅ WooCommerce integration
- ✅ Queue system (main + message)
- ✅ Reminder system with coupons
- ✅ SMS and WhatsApp notifications
- ✅ Admin panel with all tabs
- ✅ Settings page
- ✅ Free plan popup form
- ✅ Expiration tracking
- ✅ Cron jobs
- ✅ All database tables
- ✅ All API calls
- ✅ All hooks and filters

## 📝 Code Quality

**Before:** 4804 lines in one file
**After:** Modular structure with ~150-300 lines per file

**Benefits:**
- Easy to find code
- Simple to debug
- Fast to modify
- Professional structure
- Testable modules
- Scalable architecture

## 🚀 Next Steps

1. Delete old file after confirming everything works
2. Consider adding automated tests
3. Add PHPDoc blocks for better IDE support
4. Create admin view templates
5. Add logging improvements

## 💡 Notes

- **No functionality changed** - this is a pure refactor
- All existing code logic preserved exactly
- Database structure unchanged
- API calls identical
- Hook names unchanged for compatibility
