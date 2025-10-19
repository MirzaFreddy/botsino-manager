# 🚀 راهنمای ساخت ریپوی GitHub

این راهنما به شما کمک می‌کند پلاگین Botsino Manager را در GitHub منتشر کنید.

---

## 📋 پیش‌نیازها

1. ✅ حساب کاربری GitHub
2. ✅ نصب Git روی سیستم
3. ✅ دسترسی به Terminal/Command Prompt

---

## 🔧 مراحل نصب Git (اگر نصب نیست)

### Windows
```bash
# دانلود از:
https://git-scm.com/download/win

# یا با Chocolatey:
choco install git
```

### تنظیمات اولیه Git
```bash
git config --global user.name "نام شما"
git config --global user.email "your-email@example.com"
```

---

## 📦 مرحله 1: ساخت ریپو در GitHub

### 1.1 ورود به GitHub
- به https://github.com بروید
- وارد حساب کاربری خود شوید

### 1.2 ساخت ریپوی جدید
1. روی **+** در گوشه بالا راست کلیک کنید
2. **New repository** را انتخاب کنید
3. اطلاعات زیر را وارد کنید:

```
Repository name: botsino-manager
Description: WordPress plugin for Botsino integration with WooCommerce
Visibility: ✅ Public (یا Private اگر می‌خواهید خصوصی باشد)

❌ Initialize this repository with:
   [ ] Add a README file (چون ما خودمان داریم)
   [ ] Add .gitignore (چون ما خودمان داریم)
   [ ] Choose a license
```

4. روی **Create repository** کلیک کنید

---

## 💻 مرحله 2: آماده‌سازی پروژه محلی

### 2.1 باز کردن Terminal در پوشه پلاگین

```bash
# به پوشه پلاگین بروید
cd d:\WorkSpace\botsino\botsino-manager
```

### 2.2 بررسی فایل‌ها

قبل از commit، مطمئن شوید این فایل‌ها وجود دارند:

```
✅ .gitignore
✅ README.md
✅ STRUCTURE.md
✅ FINAL_FIX.md
✅ botsino-manager.php
✅ includes/ (تمام فایل‌های PHP)
```

فایل‌هایی که **نباید** commit شوند (در .gitignore هستند):

```
❌ *OLD*.php
❌ *_backup.php
❌ HOTFIX*.md (فایل‌های موقت)
❌ .env
❌ *.log
```

---

## 🎯 مرحله 3: Initialize کردن Git

### 3.1 ایجاد ریپوی محلی

```bash
# Initialize کردن Git
git init

# بررسی وضعیت
git status
```

خروجی باید فایل‌های سبز (جدید) را نشان دهد.

### 3.2 اضافه کردن فایل‌ها به Staging

```bash
# اضافه کردن همه فایل‌ها (به جز موارد gitignore)
git add .

# بررسی فایل‌های اضافه شده
git status
```

### 3.3 اولین Commit

```bash
git commit -m "Initial commit: Botsino Manager Plugin v2.0.0

Features:
- WooCommerce integration
- User management with bulk delete
- Advanced queue system
- WhatsApp notifications
- Logging system
- Duplicate email/phone validation
"
```

---

## 🔗 مرحله 4: اتصال به GitHub

### 4.1 اضافه کردن Remote

```bash
# جایگزین کردن YOUR_USERNAME با نام کاربری GitHub خود
git remote add origin https://github.com/YOUR_USERNAME/botsino-manager.git

# بررسی Remote
git remote -v
```

### 4.2 تنظیم Branch اصلی

```bash
# تغییر نام branch به main
git branch -M main
```

### 4.3 Push کردن به GitHub

```bash
# اولین Push
git push -u origin main
```

اگر از شما نام کاربری و رمز عبور خواست:
- **Username:** نام کاربری GitHub
- **Password:** Personal Access Token (نه رمز عبور معمولی!)

---

## 🔑 مرحله 5: ساخت Personal Access Token (اگر نیاز بود)

اگر Git از شما رمز عبور خواست:

1. به GitHub بروید
2. **Settings** → **Developer settings** → **Personal access tokens** → **Tokens (classic)**
3. **Generate new token** → **Generate new token (classic)**
4. اطلاعات را وارد کنید:
   ```
   Note: Git Access for Botsino Manager
   Expiration: 90 days (یا No expiration)
   
   Scopes:
   ✅ repo (تمام موارد)
   ```
5. **Generate token** را بزنید
6. توکن را کپی کنید (فقط یک بار نمایش داده می‌شود!)
7. از این توکن به جای رمز عبور استفاده کنید

---

## ✅ مرحله 6: تایید موفقیت‌آمیز بودن

### 6.1 بررسی در GitHub

1. به https://github.com/YOUR_USERNAME/botsino-manager بروید
2. باید تمام فایل‌ها را ببینید
3. README.md به صورت خودکار نمایش داده می‌شود

### 6.2 بررسی محلی

```bash
# بررسی وضعیت
git status

# باید بگوید: "nothing to commit, working tree clean"
```

---

## 🔄 مرحله 7: کارهای بعدی (اختیاری)

### 7.1 اضافه کردن Topics

در صفحه ریپو در GitHub:
1. روی **⚙️ Settings** کلیک کنید
2. در بخش **Topics** موارد زیر را اضافه کنید:
   ```
   wordpress
   woocommerce
   plugin
   whatsapp
   api-integration
   persian
   botsino
   ```

### 7.2 ساخت Release

1. به تب **Releases** بروید
2. **Create a new release** را بزنید
3. اطلاعات را وارد کنید:
   ```
   Tag: v2.0.0
   Title: Botsino Manager v2.0.0
   Description: نسخه اول پلاگین با تمام ویژگی‌ها
   ```
4. **Publish release** را بزنید

### 7.3 فعال کردن GitHub Pages (برای مستندات)

1. **Settings** → **Pages**
2. **Source:** Deploy from a branch
3. **Branch:** main → /docs (اگر پوشه docs دارید)
4. **Save**

---

## 📝 دستورات مفید Git برای آینده

### اضافه کردن تغییرات جدید

```bash
# بررسی تغییرات
git status

# اضافه کردن فایل‌های تغییر یافته
git add .

# یا فایل خاص
git add includes/Users/UserManager.php

# Commit
git commit -m "Fix: رفع مشکل ایمیل تکراری"

# Push
git push
```

### ساخت Branch جدید برای Feature

```bash
# ساخت branch جدید
git checkout -b feature/new-feature

# کار روی feature...

# Commit
git commit -m "Add: ویژگی جدید"

# Push branch
git push -u origin feature/new-feature

# بازگشت به main
git checkout main

# Merge کردن
git merge feature/new-feature
```

### بررسی تاریخچه

```bash
# لیست commit‌ها
git log

# لیست کوتاه
git log --oneline

# تغییرات یک فایل
git log -- includes/Users/UserManager.php
```

### برگشت به عقب (Undo)

```bash
# برگشت تغییرات یک فایل
git checkout -- filename.php

# برگشت آخرین commit (محلی)
git reset --soft HEAD~1

# برگشت کامل (خطرناک!)
git reset --hard HEAD~1
```

---

## 🎨 بهبود README در GitHub

بعد از Push، می‌توانید README را بهبود دهید:

### اضافه کردن Screenshot

1. پوشه `screenshots/` بسازید
2. اسکرین‌شات‌ها را اضافه کنید
3. در README.md:
   ```markdown
   ## 📸 تصاویر
   
   ![Dashboard](screenshots/dashboard.png)
   ![User List](screenshots/user-list.png)
   ```

### اضافه کردن Badge‌های بیشتر

```markdown
![GitHub release](https://img.shields.io/github/v/release/YOUR_USERNAME/botsino-manager)
![GitHub issues](https://img.shields.io/github/issues/YOUR_USERNAME/botsino-manager)
![GitHub stars](https://img.shields.io/github/stars/YOUR_USERNAME/botsino-manager)
```

---

## 🐛 رفع مشکلات رایج

### مشکل 1: "Permission denied"

```bash
# استفاده از HTTPS به جای SSH
git remote set-url origin https://github.com/YOUR_USERNAME/botsino-manager.git
```

### مشکل 2: "Updates were rejected"

```bash
# Pull کردن تغییرات
git pull origin main --rebase

# سپس Push
git push
```

### مشکل 3: فایل‌های بزرگ

```bash
# حذف فایل از Git (ولی نه از دیسک)
git rm --cached large-file.zip

# اضافه به .gitignore
echo "*.zip" >> .gitignore

# Commit
git commit -m "Remove large files"
```

---

## 📚 منابع یادگیری

- [Git Documentation](https://git-scm.com/doc)
- [GitHub Guides](https://guides.github.com/)
- [Git Cheat Sheet](https://education.github.com/git-cheat-sheet-education.pdf)

---

## ✅ Checklist نهایی

قبل از Push، این موارد را بررسی کنید:

- [ ] `.gitignore` وجود دارد
- [ ] `README.md` کامل است
- [ ] فایل‌های حساس (API Keys) در `.gitignore` هستند
- [ ] فایل‌های backup در `.gitignore` هستند
- [ ] کد تست شده است
- [ ] Commit message واضح است
- [ ] Branch درست است (main)
- [ ] Remote URL صحیح است

---

## 🎉 تبریک!

پلاگین شما حالا در GitHub منتشر شده است! 🚀

**لینک ریپو:**
```
https://github.com/YOUR_USERNAME/botsino-manager
```

**Clone کردن:**
```bash
git clone https://github.com/YOUR_USERNAME/botsino-manager.git
```

---

## 📞 پشتیبانی

اگر سوالی دارید:
- GitHub Issues: https://github.com/YOUR_USERNAME/botsino-manager/issues
- Email: your-email@example.com
