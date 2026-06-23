<p align="center">
  <img src="https://img.shields.io/badge/Exchange%20Rates-PWA-blueviolet?style=for-the-badge&logo=pwa" alt="Exchange Rates PWA">
</p>

<h1 align="center">💱 Exchange Rates - PWA Web Application</h1>

<p align="center">
  <strong>یک وب‌اپلیکیشن کامل، آفلاین و چندزبانه برای مشاهده نرخ لحظه‌ای ارز بانک مرکزی ایران</strong>
</p>

<p align="center">
  <a href="https://mozili.ir/arz/"><img src="https://img.shields.io/badge/🌐%20Live%20Demo-mozili.ir-0a2540?style=for-the-badge&logo=google-chrome&logoColor=white" alt="Live Demo"></a>
  <a href="https://github.com/rmombeni/exchange-rates-pwa"><img src="https://img.shields.io/badge/📦%20GitHub-rmombeni-181717?style=for-the-badge&logo=github&logoColor=white" alt="GitHub"></a>
  <a href="https://reymit.ir/rmombeni"><img src="https://img.shields.io/badge/☕%20Support%20Us-reymit.ir-FF5E5B?style=for-the-badge&logo=ko-fi&logoColor=white" alt="Support"></a>
</p>

<p align="center">
  <a href="https://php.net"><img src="https://img.shields.io/badge/PHP-7.4%2B-777BB4?style=flat-square&logo=php&logoColor=white" alt="PHP Version"></a>
  <a href="https://web.dev/progressive-web-apps/"><img src="https://img.shields.io/badge/PWA-Enabled-5A0FC8?style=flat-square&logo=pwa&logoColor=white" alt="PWA"></a>
  <a href="LICENSE"><img src="https://img.shields.io/badge/License-MIT-1E7E34?style=flat-square&logo=opensourceinitiative&logoColor=white" alt="License"></a>
  <a href="CONTRIBUTING.md"><img src="https://img.shields.io/badge/PRs-Welcome-brightgreen?style=flat-square&logo=github&logoColor=white" alt="PRs Welcome"></a>
</p>

<br>

---

## 🌟 **Table of Contents**

- [✨ Features](#-features)
- [🌐 Languages](#-languages)
- [📸 Screenshots](#-screenshots)
- [🚀 Quick Start](#-quick-start)
- [📁 Project Structure](#-project-structure)
- [🛠️ Technologies](#️-technologies)
- [🔑 Admin Panel](#-admin-panel)
- [⚙️ Configuration](#️-configuration)
- [🤝 Contributing](#-contributing)
- [📜 License](#-license)
- [💖 Support](#-support)

---

## ✨ **Features**

<div align="center">

| 🎯 Core Features | 🎨 UI/UX | 🔧 Tools |
|------------------|----------|----------|
| ✅ Real-time 46+ rates | ✅ Glassmorphism Design | ✅ Live Search |
| ✅ Auto-update (5 min) | ✅ Dark/Light Mode | ✅ Table Sorting |
| ✅ Server Caching | ✅ Vazir Font (Local) | ✅ Rial/Toman Convert |
| ✅ 30-Day History | ✅ Fully Responsive | ✅ Currency Converter |
| ✅ PWA Installable | ✅ RTL/LTR Support | ✅ Chart & Graph |
| ✅ Offline Support | ✅ 5 Languages | ✅ CSV Export |

</div>

### 🛡️ **Complete Admin Panel**

| Section | Features |
|---------|----------|
| 👥 **Users** | Add, Edit, Delete, Activate/Deactivate Users |
| 🌍 **Languages** | Add, Edit, Delete, Toggle Languages |
| 💰 **Currencies** | Manage Major Currencies |
| ⚙️ **Settings** | Site Name, Description, SEO, Social Networks |
| 🗄️ **Cache** | Enable/Disable, Clear Cache |
| 💾 **Backup** | Create, Restore, Delete Backups |
| 🔔 **Notifications** | Email, Browser, Sound Alerts |
| 🛡️ **Security** | Change Password, Admin Info |

### ⌨️ **Keyboard Shortcuts**

| Key | Action |
|-----|--------|
| `Ctrl + Shift + D` | Toggle Dark/Light Mode |
| `Ctrl + F` | Focus on Search |
| `Ctrl + Shift + S` | Toggle Sidebar (Admin) |
| `Esc` | Close Modals |

---

## 🌐 **Languages**

<div align="center">

| Language | Code | Flag | Status |
|----------|------|------|--------|
| فارسی (Persian) | `fa` | 🇮🇷 | ✅ Active |
| English | `en` | 🇬🇧 | ✅ Active |
| العربية (Arabic) | `ar` | 🇸🇦 | ✅ Active |
| Türkçe (Turkish) | `tr` | 🇹🇷 | ✅ Active |
| Español (Spanish) | `es` | 🇪🇸 | ✅ Active |

</div>

> **💡 Easy to add new languages!** Just add a new language object to `config/languages.json`

---

## 📸 **Screenshots**

<div align="center">

| Light Mode | Dark Mode |
|------------|-----------|
| <img src="https://mozili.ir/arz/screenshots/light.png" width="400" alt="Light Mode"> | <img src="https://mozili.ir/arz/screenshots/dark.png" width="400" alt="Dark Mode"> |

| Admin Panel | Mobile View |
|-------------|-------------|
| <img src="https://mozili.ir/arz/screenshots/admin.png" width="400" alt="Admin Panel"> | <img src="https://mozili.ir/arz/screenshots/mobile.png" width="200" alt="Mobile View"> |

</div>

---

## 🚀 **Quick Start**

### 📋 **Prerequisites**

```bash
PHP 7.4+          # Backend
cURL Extension    # API calls
SimpleXML         # RSS parsing
JSON Extension    # Configuration
Web Server        # Apache/Nginx
