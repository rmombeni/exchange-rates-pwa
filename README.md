# 💱 نرخ ارز مرجع - PWA Web Application

[![Website](https://img.shields.io/badge/Website-timit.ir-blue.svg)](https://timit.ir/)
[![GitHub](https://img.shields.io/badge/GitHub-rmombeni-black.svg)](https://github.com/rmombeni)
[![PHP Version](https://img.shields.io/badge/PHP-7.4%2B-blue.svg)](https://php.net)
[![PWA](https://img.shields.io/badge/PWA-Enabled-success.svg)](https://web.dev/progressive-web-apps/)
[![License](https://img.shields.io/badge/License-MIT-green.svg)](LICENSE)
[![PRs Welcome](https://img.shields.io/badge/PRs-welcome-brightgreen.svg)](CONTRIBUTING.md)

> **یک وب‌اپلیکیشن کامل و آفلاین برای مشاهده نرخ لحظه‌ای ارز رسمی بانک مرکزی ایران**

[![Live Demo](https://img.shields.io/badge/Demo-Live-blueviolet.svg)](https://timit.ir/)
[![Install](https://img.shields.io/badge/Install-PWA-blue.svg)](#installation)

---

## 🌐 لینک‌های پروژه

- **سایت اصلی:** [https://timit.ir/](https://timit.ir/)
- **گیت‌هاب:** [https://github.com/rmombeni/exchange-rates-pwa](https://github.com/rmombeni/exchange-rates-pwa)
- **مخزن:** [rmombeni/exchange-rates-pwa](https://github.com/rmombeni/exchange-rates-pwa)

---

## 📸 پیش‌نمایش

| حالت روشن | حالت تاریک |
|-----------|------------|
| ![Light Mode](https://timit.ir/screenshots/light.png) | ![Dark Mode](https://timit.ir/screenshots/dark.png) |

> *(برای دیدن اسکرین‌شات‌ها، به [https://timit.ir/](https://timit.ir/) مراجعه کنید)*

---

## ✨ ویژگی‌ها

### 🎯 اصلی
- ✅ **نمایش لحظه‌ای** نرخ ۴۶ ارز رسمی بانک مرکزی
- ✅ **آپدیت خودکار** هر ۵ دقیقه
- ✅ **کش سمت سرور** برای کاهش بار
- ✅ **تاریخچه نرخ‌ها** (۳۰ روز اخیر)

### 🎨 رابط کاربری
- ✅ **طراحی مدرن** با افکت شیشه‌ای (Glassmorphism)
- ✅ **دارک/لایت مود** با کلید میانبر `Ctrl+Shift+D`
- ✅ **فونت وزیر** (لوکال، بدون نیاز به اینترنت)
- ✅ **کاملاً ریسپانسیو** (موبایل، تبلت، دسکتاپ)

### 🔧 ابزارها
- ✅ **جستجوی زنده** با فیلتر کردن جدول
- ✅ **مرتب‌سازی جدول** با کلیک روی ستون‌ها
- ✅ **تبدیل ریال به تومان** با یک کلیک
- ✅ **مقایسه و مبدل ارز** (تبدیل دو ارز به هم)
- ✅ **نمودار تغییرات** (۳۰ روز اخیر)
- ✅ **نمایش تغییرات درصدی** (نسبت به روز قبل)

### 💾 ذخیره‌سازی
- ✅ **مورد علاقه‌ها** (ذخیره در localStorage)
- ✅ **تاریخچه نرخ‌ها** (ذخیره در فایل‌های JSON)
- ✅ **خروجی CSV** (دانلود داده‌های جدول)

### 📱 PWA
- ✅ **قابل نصب** روی گوشی و کامپیوتر
- ✅ **آفلاین** (با Service Worker)
- ✅ **آیکون‌های اختصاصی**
- ✅ **نوتیفیکیشن‌های پیشرفته**

### ⌨️ میانبرهای کیبورد
| کلید | عملکرد |
|------|---------|
| `Ctrl+Shift+D` | تغییر تم |
| `Ctrl+F` | فوکوس روی جستجو |
| `Esc` | بستن مودال‌ها |

---

## 🚀 نصب و راه‌اندازی

### پیش‌نیازها
- PHP 7.4 یا بالاتر
- پسوند cURL فعال
- پسوند SimpleXML فعال
- وب سرور (Apache/Nginx)

### نصب سریع

```bash
# 1. کلون کردن مخزن
git clone https://github.com/rmombeni/exchange-rates-pwa.git
cd exchange-rates-pwa

# 2. تنظیم دسترسی‌ها
chmod 755 cache/ history/

# 3. قرار دادن فونت وزیر
# فایل Vazir.ttf را در پوشه fonts/ قرار دهید

# 4. اجرا
# فایل index.php را در مرورگر باز کنید
# یا از طریق دامنه: https://timit.ir/