# ⚡ راهنمای سریع - آپلود به GitHub

## 🚀 دستورات سریع (کپی و اجرا کنید)

### مرحله 1: ساخت ریپو در GitHub
1. به https://github.com/new بروید
2. نام ریپو: `botsino-manager`
3. **Create repository** را بزنید

---

### مرحله 2: اجرای دستورات

```bash
# 1. رفتن به پوشه پلاگین
cd d:\WorkSpace\botsino\botsino-manager

# 2. Initialize کردن Git
git init

# 3. اضافه کردن فایل‌ها
git add .

# 4. اولین Commit
git commit -m "Initial commit: Botsino Manager Plugin v2.0.0"

# 5. تغییر نام branch به main
git branch -M main

# 6. اضافه کردن Remote (YOUR_USERNAME را عوض کنید!)
git remote add origin https://github.com/YOUR_USERNAME/botsino-manager.git

# 7. Push کردن
git push -u origin main
```

---

## 🔑 اگر رمز عبور خواست

از **Personal Access Token** استفاده کنید:

1. https://github.com/settings/tokens
2. **Generate new token (classic)**
3. انتخاب: `repo` (تمام موارد)
4. **Generate token**
5. توکن را کپی کنید
6. به جای رمز عبور استفاده کنید

---

## ✅ بررسی موفقیت

```bash
# بررسی وضعیت
git status

# باید بگوید: "nothing to commit, working tree clean"
```

سپس به GitHub بروید:
```
https://github.com/YOUR_USERNAME/botsino-manager
```

---

## 📝 دستورات بعدی (برای تغییرات آینده)

```bash
# اضافه کردن تغییرات
git add .

# Commit
git commit -m "توضیح تغییرات"

# Push
git push
```

---

## 🎯 فایل‌های مهم که باید در ریپو باشند

✅ `.gitignore` - فایل‌های نادیده گرفته شده
✅ `README.md` - مستندات اصلی
✅ `STRUCTURE.md` - ساختار پروژه
✅ `FINAL_FIX.md` - آخرین تغییرات
✅ `botsino-manager.php` - فایل اصلی پلاگین
✅ `includes/` - تمام کدهای PHP

❌ `*OLD*.php` - فایل‌های قدیمی
❌ `*_backup.php` - فایل‌های backup
❌ `HOTFIX*.md` - فایل‌های موقت
❌ `.env` - فایل‌های محیطی

---

## 🐛 رفع مشکل سریع

### خطا: "Permission denied"
```bash
git remote set-url origin https://github.com/YOUR_USERNAME/botsino-manager.git
```

### خطا: "Updates were rejected"
```bash
git pull origin main --rebase
git push
```

---

## 📚 راهنمای کامل

برای جزئیات بیشتر، فایل `GITHUB_SETUP.md` را مطالعه کنید.

---

## 🎉 تمام!

پلاگین شما حالا در GitHub است! 🚀
