<!-- ============================================ -->
<!-- ENGLISH VERSION -->
<!-- ============================================ -->

# 💱 Exchange Rates - PWA Web Application

[![Website](https://img.shields.io/badge/Website-mozili.ir-blue.svg)](https://mozili.ir/arz/)
[![GitHub](https://img.shields.io/badge/GitHub-rmombeni-black.svg)](https://github.com/rmombeni)
[![PHP Version](https://img.shields.io/badge/PHP-7.4%2B-blue.svg)](https://php.net)
[![PWA](https://img.shields.io/badge/PWA-Enabled-success.svg)](https://web.dev/progressive-web-apps/)
[![License](https://img.shields.io/badge/License-MIT-green.svg)](LICENSE)
[![PRs Welcome](https://img.shields.io/badge/PRs-welcome-brightgreen.svg)](CONTRIBUTING.md)

> **A complete offline PWA web application for viewing real-time exchange rates from the Central Bank of Iran**

[![Live Demo](https://img.shields.io/badge/Demo-Live-blueviolet.svg)](https://mozili.ir/arz/)
[![Install](https://img.shields.io/badge/Install-PWA-blue.svg)](#installation)

---

## 🌐 Available Languages

| Language | Code | Flag | Status |
|----------|------|------|--------|
| فارسی (Persian) | fa | 🇮🇷 | ✅ Active |
| English | en | 🇬🇧 | ✅ Active |
| العربية (Arabic) | ar | 🇸🇦 | ✅ Active |
| Türkçe (Turkish) | tr | 🇹🇷 | ✅ Active |
| Español (Spanish) | es | 🇪🇸 | ✅ Active |

---

## 📸 Screenshots

| Light Mode | Dark Mode | Admin Panel |
|------------|-----------|-------------|
| ![Light Mode](https://mozili.ir/arz/screenshots/light.png) | ![Dark Mode](https://mozili.ir/arz/screenshots/dark.png) | ![Admin Panel](https://mozili.ir/arz/screenshots/admin.png) |

---

## ✨ Features

### 🎯 Core Features
- ✅ **Real-time display** of 46+ official exchange rates from Central Bank of Iran
- ✅ **Auto-update** every 5 minutes
- ✅ **Server-side caching** to reduce load
- ✅ **Rate history** (last 30 days)

### 🎨 User Interface
- ✅ **Modern design** with Glassmorphism effect
- ✅ **Dark/Light mode** with keyboard shortcut `Ctrl+Shift+D`
- ✅ **Vazir font** (local, no internet needed)
- ✅ **Fully responsive** (mobile, tablet, desktop)

### 🔧 Tools
- ✅ **Live search** with table filtering
- ✅ **Table sorting** by clicking on columns
- ✅ **Rial to Toman conversion** with one click
- ✅ **Currency converter** (convert between two currencies)
- ✅ **Change chart** (30 days history)
- ✅ **Percentage change display** (compared to previous day)

### 💾 Storage
- ✅ **Favorites** (stored in localStorage)
- ✅ **Rate history** (stored in JSON files)
- ✅ **CSV export** (download table data)

### 📱 PWA
- ✅ **Installable** on phone and computer
- ✅ **Offline** (with Service Worker)
- ✅ **Custom icons**
- ✅ **Advanced notifications**

### 🛡️ Complete Admin Panel
- ✅ **User Management** (Add, Edit, Delete, Activate/Deactivate)
- ✅ **Language Management** (Add, Edit, Delete, Activate/Deactivate)
- ✅ **Currency Management** (Add/Remove from major list)
- ✅ **General Settings** (Site name, description, SEO, social networks)
- ✅ **Cache & Backup Management**
- ✅ **PWA & Notification Settings**
- ✅ **Activity Log System**

### ⌨️ Keyboard Shortcuts

| Key | Action |
|-----|--------|
| `Ctrl+Shift+D` | Toggle theme |
| `Ctrl+F` | Focus on search |
| `Ctrl+Shift+S` | Toggle sidebar (Admin) |
| `Esc` | Close modals |

---

## 🚀 Installation

### Prerequisites
- PHP 7.4 or higher
- cURL extension enabled
- SimpleXML extension enabled
- JSON extension enabled
- Web server (Apache/Nginx)

### Quick Install

```bash
# 1. Clone repository
git clone https://github.com/rmombeni/exchange-rates-pwa.git
cd exchange-rates-pwa

# 2. Set permissions
chmod 755 cache/ history/ backup/ logs/ config/

# 3. Place Vazir font
# Put Vazir.ttf in the fonts/ folder

# 4. Run
# Open index.php in your browser


Docker Installation
bash

# Build Docker image
docker build -t exchange-rates .

# Run container
docker run -p 8080:80 exchange-rates

🔑 Admin Login
Item	Value
Admin Panel URL	https://yourdomain.com/arz/admin/login.php
Username	admin
Password	Admin@2026

    ⚠️ Security Recommendation: Change the password immediately after first login!

📁 Project Structure
text

/arz/
├── index.php              (Main page)
├── history.php            (History for charts)
├── manifest.json          (PWA)
├── sw.js                  (Service Worker)
├── admin/
│   ├── index.php          (Complete admin panel)
│   ├── login.php          (Admin login)
│   └── logout.php         (Logout)
├── config/
│   ├── settings.json      (Main settings)
│   ├── languages.json     (Languages)
│   └── users.json         (Users)
├── includes/
│   ├── config.php
│   ├── functions.php
│   └── user_functions.php
├── fonts/
│   └── Vazir.ttf
├── icons/
│   ├── icon-72.png
│   ├── icon-96.png
│   ├── icon-128.png
│   ├── icon-144.png
│   ├── icon-152.png
│   ├── icon-192.png
│   ├── icon-384.png
│   └── icon-512.png
├── cache/
├── history/
├── backup/
└── logs/

⚙️ Advanced Settings
Cache Time
php

// In includes/config.php
define('CACHE_TIME', 300); // 300 = 5 minutes

Major Currencies
json

// In config/settings.json
"major": ["USD", "EUR", "GBP", "CHF", "CAD", "AED", "TRY"]

Theme Colors
css

/* In CSS :root variables */
--bg-header: #0a2540;
--success: #1a7a4a;

🛠️ Technologies Used

    PHP 7.4+ - Backend

    HTML5 + CSS3 - Frontend

    JavaScript (Vanilla) - Interactions

    Chart.js - Charts

    Font Awesome - Icons

    Service Worker - PWA

    JSON - Lightweight database

🤝 Contributing

    Fork the repository

    Create a new branch (git checkout -b feature/AmazingFeature)

    Commit your changes (git commit -m 'Add some AmazingFeature')

    Push to the branch (git push origin feature/AmazingFeature)

    Open a Pull Request

Contribution Guidelines

    Follow PSR-12 coding standards

    Update documentation

    Add tests

    Use Conventional Commits

🐛 Bug Reports

If you find a bug, please open an Issue with:

    Detailed description of the problem

    Steps to reproduce

    Browser and OS version

    Screenshots (if possible)

📜 License

This project is licensed under the MIT License. See the LICENSE file for details.
👨‍💻 Developer

[R.Mombeni]
https://img.shields.io/badge/GitHub-100000?style=for-the-badge&logo=github&logoColor=white
https://img.shields.io/badge/Website-mozili.ir-blue?style=for-the-badge&logo=google-chrome&logoColor=white
⭐ Support

If this project was useful for you, please give it a ⭐ and share it with others!

https://img.shields.io/github/stars/rmombeni/exchange-rates-pwa.svg?style=social&label=Star
https://img.shields.io/github/forks/rmombeni/exchange-rates-pwa.svg?style=social&label=Fork

Made with ❤️ for the Iranian community
<!-- ============================================ --><!-- PERSIAN VERSION (فارسی) --><!-- ============================================ -->
💱 نرخ ارز مرجع - وب‌اپلیکیشن PWA

https://img.shields.io/badge/Website-mozili.ir-blue.svg
https://img.shields.io/badge/GitHub-rmombeni-black.svg
https://img.shields.io/badge/PHP-7.4%252B-blue.svg
https://img.shields.io/badge/PWA-Enabled-success.svg
https://img.shields.io/badge/License-MIT-green.svg

    یک وب‌اپلیکیشن کامل و آفلاین برای مشاهده نرخ لحظه‌ای ارز رسمی بانک مرکزی ایران

https://img.shields.io/badge/Demo-Live-blueviolet.svg
🌐 زبان‌های موجود
زبان	کد	پرچم	وضعیت
فارسی	fa	🇮🇷	✅ فعال
انگلیسی	en	🇬🇧	✅ فعال
عربی	ar	🇸🇦	✅ فعال
ترکی	tr	🇹🇷	✅ فعال
اسپانیایی	es	🇪🇸	✅ فعال
✨ ویژگی‌ها
🎯 اصلی

    ✅ نمایش لحظه‌ای نرخ ۴۶+ ارز رسمی بانک مرکزی

    ✅ آپدیت خودکار هر ۵ دقیقه

    ✅ کش سمت سرور برای کاهش بار

    ✅ تاریخچه نرخ‌ها (۳۰ روز اخیر)

🎨 رابط کاربری

    ✅ طراحی مدرن با افکت شیشه‌ای (Glassmorphism)

    ✅ دارک/لایت مود با کلید میانبر Ctrl+Shift+D

    ✅ فونت وزیر (لوکال، بدون نیاز به اینترنت)

    ✅ کاملاً ریسپانسیو (موبایل، تبلت، دسکتاپ)

🔧 ابزارها

    ✅ جستجوی زنده با فیلتر کردن جدول

    ✅ مرتب‌سازی جدول با کلیک روی ستون‌ها

    ✅ تبدیل ریال به تومان با یک کلیک

    ✅ مبدل ارز (تبدیل دو ارز به هم)

    ✅ نمودار تغییرات (۳۰ روز اخیر)

    ✅ نمایش تغییرات درصدی (نسبت به روز قبل)

📱 PWA

    ✅ قابل نصب روی گوشی و کامپیوتر

    ✅ آفلاین (با Service Worker)

    ✅ آیکون‌های اختصاصی

🛡️ پنل مدیریت کامل

    ✅ مدیریت کاربران (افزودن، ویرایش، حذف، فعال/غیرفعال)

    ✅ مدیریت زبان‌ها (افزودن، ویرایش، حذف، فعال/غیرفعال)

    ✅ مدیریت ارزهای شاخص (افزودن/حذف)

    ✅ تنظیمات عمومی (نام سایت، توضیحات، سئو، شبکه‌های اجتماعی)

    ✅ مدیریت کش و بکاپ

    ✅ تنظیمات PWA و نوتیفیکیشن

🚀 نصب و راه‌اندازی
پیش‌نیازها

    PHP 7.4 یا بالاتر

    پسوند cURL فعال

    پسوند SimpleXML فعال

    پسوند JSON فعال

نصب سریع
bash

# 1. کلون کردن مخزن
git clone https://github.com/rmombeni/exchange-rates-pwa.git
cd exchange-rates-pwa

# 2. تنظیم دسترسی‌ها
chmod 755 cache/ history/ backup/ logs/ config/

# 3. قرار دادن فونت وزیر
# فایل Vazir.ttf را در پوشه fonts/ قرار دهید

# 4. اجرا
# فایل index.php را در مرورگر باز کنید

🔑 ورود به پنل مدیریت
مورد	مقدار
آدرس پنل	https://yourdomain.com/arz/admin/login.php
نام کاربری	admin
رمز عبور	Admin@2026

    ⚠️ توصیه امنیتی: حتماً بعد از اولین ورود، رمز عبور را تغییر دهید!

🤝 مشارکت

    فورک کنید

    برنچ جدید ایجاد کنید (git checkout -b feature/AmazingFeature)

    کامیت کنید (git commit -m 'Add some AmazingFeature')

    پوش کنید (git push origin feature/AmazingFeature)

    Pull Request باز کنید

📜 مجوز

این پروژه تحت مجوز MIT منتشر شده است.
👨‍💻 توسعه‌دهنده

[R.Mombeni]
https://img.shields.io/badge/GitHub-100000?style=for-the-badge&logo=github&logoColor=white

ساخته شده با ❤️ برای جامعه ایرانی
<!-- ============================================ --><!-- ARABIC VERSION (العربية) --><!-- ============================================ -->
💱 أسعار الصرف - تطبيق ويب PWA

https://img.shields.io/badge/Website-mozili.ir-blue.svg
https://img.shields.io/badge/GitHub-rmombeni-black.svg
https://img.shields.io/badge/PHP-7.4%252B-blue.svg
https://img.shields.io/badge/PWA-Enabled-success.svg

    تطبيق ويب كامل وغير متصل لعرض أسعار الصرف لحظياً من البنك المركزي الإيراني

https://img.shields.io/badge/Demo-Live-blueviolet.svg
✨ الميزات
🎯 الأساسية

    ✅ عرض لحظي لأسعار ٤٦+ عملة رسمية من البنك المركزي الإيراني

    ✅ تحديث تلقائي كل ٥ دقائق

    ✅ تخزين مؤقت لتقليل الحمل

🎨 واجهة المستخدم

    ✅ تصميم حديث مع تأثير الزجاج

    ✅ وضع مظلم/فاتح مع اختصار Ctrl+Shift+D

    ✅ خط وزير (محلي، بدون حاجة للإنترنت)

    ✅ متجاوب بالكامل

🛡️ لوحة الإدارة

    ✅ إدارة المستخدمين (إضافة، تعديل، حذف، تفعيل/تعطيل)

    ✅ إدارة اللغات (إضافة، تعديل، حذف، تفعيل/تعطيل)

    ✅ إدارة العملات الرئيسية (إضافة/حذف)

    ✅ الإعدادات العامة

🚀 التثبيت
bash

git clone https://github.com/rmombeni/exchange-rates-pwa.git
cd exchange-rates-pwa
chmod 755 cache/ history/ backup/ logs/ config/

🔑 تسجيل الدخول
العنصر	القيمة
رابط لوحة الإدارة	https://yourdomain.com/arz/admin/login.php
اسم المستخدم	admin
كلمة المرور	Admin@2026
📜 الترخيص

هذا المشروع مرخص تحت رخصة MIT.
<!-- ============================================ --><!-- TURKISH VERSION (Türkçe) --><!-- ============================================ -->
💱 Döviz Kurları - PWA Web Uygulaması

https://img.shields.io/badge/Website-mozili.ir-blue.svg
https://img.shields.io/badge/GitHub-rmombeni-black.svg
https://img.shields.io/badge/PHP-7.4%252B-blue.svg
https://img.shields.io/badge/PWA-Enabled-success.svg

    İran Merkez Bankası resmi döviz kurlarını görüntülemek için tam ve çevrimdışı bir PWA web uygulaması

https://img.shields.io/badge/Demo-Live-blueviolet.svg
✨ Özellikler
🎯 Temel

    ✅ Gerçek zamanlı 46+ resmi döviz kuru

    ✅ Otomatik güncelleme her 5 dakikada

    ✅ Sunucu önbelleği yükü azaltmak için

🎨 Arayüz

    ✅ Modern tasarım Cam efekti ile

    ✅ Karanlık/Aydınlık mod Ctrl+Shift+D kısayolu ile

    ✅ Vazir fontu (yerel, internet gerekmez)

    ✅ Tamamen duyarlı

🛡️ Yönetim Paneli

    ✅ Kullanıcı Yönetimi (Ekle, Düzenle, Sil, Aktif/Pasif)

    ✅ Dil Yönetimi (Ekle, Düzenle, Sil, Aktif/Pasif)

    ✅ Para Birimi Yönetimi (Ekle/Çıkar)

    ✅ Genel Ayarlar

🚀 Kurulum
bash

git clone https://github.com/rmombeni/exchange-rates-pwa.git
cd exchange-rates-pwa
chmod 755 cache/ history/ backup/ logs/ config/

🔑 Giriş
Öğe	Değer
Yönetim Paneli URL	https://yourdomain.com/arz/admin/login.php
Kullanıcı Adı	admin
Şifre	Admin@2026
📜 Lisans

Bu proje MIT Lisansı ile lisanslanmıştır.
<!-- ============================================ --><!-- SPANISH VERSION (Español) --><!-- ============================================ -->
💱 Tipos de Cambio - Aplicación Web PWA

https://img.shields.io/badge/Website-mozili.ir-blue.svg
https://img.shields.io/badge/GitHub-rmombeni-black.svg
https://img.shields.io/badge/PHP-7.4%252B-blue.svg
https://img.shields.io/badge/PWA-Enabled-success.svg

    Una aplicación web PWA completa y sin conexión para ver tipos de cambio en tiempo real del Banco Central de Irán

https://img.shields.io/badge/Demo-Live-blueviolet.svg
✨ Características
🎯 Principales

    ✅ Visualización en tiempo real de 46+ tipos de cambio oficiales

    ✅ Actualización automática cada 5 minutos

    ✅ Caché del lado del servidor para reducir carga

🎨 Interfaz

    ✅ Diseño moderno con efecto Glassmorphism

    ✅ Modo oscuro/claro con atajo Ctrl+Shift+D

    ✅ Fuente Vazir (local, sin necesidad de internet)

    ✅ Completamente responsive

🛡️ Panel de Administración

    ✅ Gestión de Usuarios (Agregar, Editar, Eliminar, Activar/Desactivar)

    ✅ Gestión de Idiomas (Agregar, Editar, Eliminar, Activar/Desactivar)

    ✅ Gestión de Monedas (Agregar/Eliminar)

    ✅ Configuración General

🚀 Instalación
bash

git clone https://github.com/rmombeni/exchange-rates-pwa.git
cd exchange-rates-pwa
chmod 755 cache/ history/ backup/ logs/ config/

🔑 Inicio de Sesión
Elemento	Valor
URL del Panel	https://yourdomain.com/arz/admin/login.php
Usuario	admin
Contraseña	Admin@2026
📜 Licencia

Este proyecto está bajo la Licencia MIT.
<!-- ============================================ --><!-- COMMON FOOTER (All Languages) --><!-- ============================================ -->
📊 Project Statistics

https://img.shields.io/github/stars/rmombeni/exchange-rates-pwa.svg?style=social&label=Star
https://img.shields.io/github/forks/rmombeni/exchange-rates-pwa.svg?style=social&label=Fork
https://img.shields.io/github/watchers/rmombeni/exchange-rates-pwa.svg?style=social&label=Watch
https://img.shields.io/github/issues/rmombeni/exchange-rates-pwa.svg
https://img.shields.io/github/last-commit/rmombeni/exchange-rates-pwa.svg
📞 Contact & Support

    Website: https://mozili.ir/

    GitHub: https://github.com/rmombeni

    Email: admin@mozili.ir

⭐⭐⭐ If you like this project, please give it a star! ⭐⭐⭐
<div align="center"> <sub>Built with ❤️ by <a href="https://github.com/rmombeni">R.Mombeni</a></sub> </div> ```
