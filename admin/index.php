<?php
// ===== نمایش خطاها برای دیباگ =====
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

session_start();
require_once '../includes/config.php';
require_once '../includes/user_functions.php';

// ============================================
// ===== تشخیص و حفظ زبان =====
// ============================================

$langCode = 'fa';

if (isset($_GET['lang']) && in_array($_GET['lang'], ['fa', 'en', 'ar', 'tr', 'es'])) {
    $langCode = $_GET['lang'];
    setcookie('admin_lang', $langCode, time() + 31536000, '/');
    $_SESSION['admin_lang'] = $langCode;
}
elseif (isset($_COOKIE['admin_lang']) && in_array($_COOKIE['admin_lang'], ['fa', 'en', 'ar', 'tr', 'es'])) {
    $langCode = $_COOKIE['admin_lang'];
}
elseif (isset($_SESSION['admin_lang']) && in_array($_SESSION['admin_lang'], ['fa', 'en', 'ar', 'tr', 'es'])) {
    $langCode = $_SESSION['admin_lang'];
}
elseif (isset($_COOKIE['language']) && in_array($_COOKIE['language'], ['fa', 'en', 'ar', 'tr', 'es'])) {
    $langCode = $_COOKIE['language'];
}

if (isset($languages[$langCode]) && $languages[$langCode]['active']) {
    $currentLang = $languages[$langCode];
} else {
    $currentLang = $languages['fa'];
    $langCode = 'fa';
}
$translations = $currentLang['translations'];
$langDir = $currentLang['dir'] ?? 'rtl';

// ===== تابع __ در config.php تعریف شده =====

$_SESSION['admin_lang'] = $langCode;
setcookie('admin_lang', $langCode, time() + 31536000, '/');

function switchLang($lang) {
    $currentTab = $_GET['tab'] ?? 'dashboard';
    return '?lang=' . $lang . '&tab=' . $currentTab;
}

// ============================================
// ===== بررسی لاگین =====
// ============================================

if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: login.php?lang=' . $langCode);
    exit;
}

$currentUser = [
    'id' => $_SESSION['admin_user_id'] ?? 1,
    'username' => $_SESSION['admin_username'] ?? 'admin',
    'role' => 'admin',
    'fullname' => 'مدیر سایت'
];

$message = '';
$messageType = '';
$activeTab = $_GET['tab'] ?? 'dashboard';

$usersData = loadUsers();
$totalUsers = count($usersData['users'] ?? []);
$languages = loadLanguages();
$settings = loadSettings();

// ============================================
// ===== پردازش فرم‌ها =====
// ============================================

// تنظیمات عمومی
if (isset($_POST['save_settings'])) {
    $settings['site']['name'] = trim($_POST['site_name'] ?? $settings['site']['name']);
    $settings['site']['url'] = trim($_POST['site_url'] ?? $settings['site']['url']);
    $settings['site']['description'] = trim($_POST['site_description'] ?? $settings['site']['description']);
    $settings['site']['keywords'] = trim($_POST['site_keywords'] ?? $settings['site']['keywords']);
    $settings['site']['author'] = trim($_POST['site_author'] ?? $settings['site']['author']);
    $settings['site']['email'] = trim($_POST['site_email'] ?? $settings['site']['email']);
    $settings['site']['default_language'] = $_POST['default_language'] ?? $settings['site']['default_language'];
    $settings['site']['default_theme'] = $_POST['default_theme'] ?? $settings['site']['default_theme'];
    $settings['site']['maintenance'] = isset($_POST['maintenance']);
    $settings['site']['maintenance_message'] = trim($_POST['maintenance_message'] ?? $settings['site']['maintenance_message']);
    saveSettings($settings);
    $message = '✅ ' . __('settings_saved', 'تنظیمات با موفقیت ذخیره شد!');
    $messageType = 'success';
}

// تغییر رمز عبور
if (isset($_POST['change_password'])) {
    $current = $_POST['current_password'] ?? '';
    $new = $_POST['new_password'] ?? '';
    $confirm = $_POST['confirm_password'] ?? '';
    if (password_verify($current, $settings['admin']['password'])) {
        if ($new === $confirm && strlen($new) >= 6) {
            $settings['admin']['password'] = password_hash($new, PASSWORD_DEFAULT);
            saveSettings($settings);
            $message = '✅ ' . __('password_changed', 'رمز عبور با موفقیت تغییر کرد!');
            $messageType = 'success';
        } else {
            $message = '❌ ' . __('error', 'رمز جدید با تاییدیه مطابقت ندارد یا کمتر از ۶ کاراکتر است!');
            $messageType = 'error';
        }
    } else {
        $message = '❌ ' . __('error', 'رمز عبور فعلی اشتباه است!');
        $messageType = 'error';
    }
}

// تنظیمات ویژگی‌ها
if (isset($_POST['save_features'])) {
    $settings['features']['pwa'] = isset($_POST['pwa']);
    $settings['features']['dark_mode'] = isset($_POST['dark_mode']);
    $settings['features']['search'] = isset($_POST['search']);
    $settings['features']['favorites'] = isset($_POST['favorites']);
    $settings['features']['converter'] = isset($_POST['converter']);
    $settings['features']['chart'] = isset($_POST['chart']);
    $settings['features']['csv_export'] = isset($_POST['csv_export']);
    $settings['features']['auto_refresh'] = isset($_POST['auto_refresh']);
    $settings['features']['refresh_interval'] = (int) ($_POST['refresh_interval'] ?? 300);
    saveSettings($settings);
    $message = '✅ ' . __('settings_saved', 'تنظیمات ویژگی‌ها ذخیره شد!');
    $messageType = 'success';
}

// تنظیمات ارزها
if (isset($_POST['save_currencies'])) {
    $major = $_POST['major_currencies'] ?? '';
    $settings['currencies']['major'] = array_filter(array_map('trim', explode(',', $major)));
    $settings['currencies']['show_all'] = isset($_POST['show_all']);
    $settings['currencies']['default_unit'] = $_POST['default_unit'] ?? 'rial';
    saveSettings($settings);
    $message = '✅ ' . __('settings_saved', 'تنظیمات ارزها ذخیره شد!');
    $messageType = 'success';
}

// اضافه کردن ارز به لیست شاخص
if (isset($_POST['add_major_currency'])) {
    $newCurrency = trim($_POST['new_major_currency'] ?? '');
    if ($newCurrency && !in_array($newCurrency, $settings['currencies']['major'])) {
        $settings['currencies']['major'][] = $newCurrency;
        saveSettings($settings);
        $message = "✅ " . __('currency_added', 'ارز با موفقیت اضافه شد!');
        $messageType = 'success';
    } else {
        $message = '❌ ' . __('error', 'ارز نامعتبر است یا قبلاً وجود دارد!');
        $messageType = 'error';
    }
}

// حذف ارز از لیست شاخص
if (isset($_GET['remove_major']) && isset($_GET['code'])) {
    $code = $_GET['code'];
    $settings['currencies']['major'] = array_filter($settings['currencies']['major'], function($item) use ($code) {
        return $item !== $code;
    });
    saveSettings($settings);
    header('Location: index.php?tab=currencies&lang=' . $langCode);
    exit;
}

// مدیریت زبان‌ها - افزودن
if (isset($_POST['add_language'])) {
    $code = trim($_POST['lang_code'] ?? '');
    $name = trim($_POST['lang_name'] ?? '');
    $dir = $_POST['lang_dir'] ?? 'rtl';
    $flag = trim($_POST['lang_flag'] ?? '🏳️');
    if ($code && $name && !isset($languages[$code])) {
        $languages[$code] = [
            'name' => $name,
            'dir' => $dir,
            'flag' => $flag,
            'active' => true,
            'translations' => [
                'site_title' => $name . ' Site',
                'site_description' => 'Site Description',
                'search_placeholder' => 'Search...',
                'featured_currencies' => 'Featured Currencies',
                'all_currencies' => 'All Currencies',
                'currency' => 'Currency',
                'rate' => 'Rate',
                'change' => 'Change',
                'date' => 'Date',
                'chart' => 'Chart',
                'favorites' => 'Favorites',
                'converter' => 'Converter',
                'export' => 'Export',
                'theme_light' => 'Light',
                'theme_dark' => 'Dark',
                'unit_rial' => 'Rial',
                'unit_toman' => 'Toman',
                'no_data' => 'No data available',
                'error_connection' => 'Connection error',
                'update_time' => 'Last update',
                'source' => 'Source',
                'copyright' => 'All rights reserved'
            ]
        ];
        saveLanguages($languages);
        $message = '✅ ' . __('language_added', 'زبان جدید با موفقیت اضافه شد!');
        $messageType = 'success';
    } else {
        $message = '❌ ' . __('error', 'کد زبان نامعتبر است یا قبلاً وجود دارد!');
        $messageType = 'error';
    }
}

// مدیریت زبان‌ها - ویرایش ترجمه
if (isset($_POST['save_translations'])) {
    $langCode = $_POST['lang_code'] ?? '';
    if (isset($languages[$langCode])) {
        foreach ($languages[$langCode]['translations'] as $key => $value) {
            if (isset($_POST['trans_' . $key])) {
                $languages[$langCode]['translations'][$key] = trim($_POST['trans_' . $key]);
            }
        }
        saveLanguages($languages);
        $message = '✅ ' . __('translations_saved', 'ترجمه‌ها با موفقیت ذخیره شدند!');
        $messageType = 'success';
    }
}

// مدیریت زبان‌ها - ویرایش اطلاعات
if (isset($_POST['edit_language_info'])) {
    $code = $_POST['lang_code'] ?? '';
    if (isset($languages[$code])) {
        $languages[$code]['name'] = trim($_POST['lang_name'] ?? $languages[$code]['name']);
        $languages[$code]['dir'] = $_POST['lang_dir'] ?? $languages[$code]['dir'];
        $languages[$code]['flag'] = trim($_POST['lang_flag'] ?? $languages[$code]['flag']);
        saveLanguages($languages);
        $message = '✅ ' . __('settings_saved', 'اطلاعات زبان با موفقیت به‌روزرسانی شد!');
        $messageType = 'success';
    }
}

// مدیریت زبان‌ها - فعال/غیرفعال
if (isset($_GET['toggle_lang']) && isset($_GET['code'])) {
    $code = $_GET['code'];
    if (isset($languages[$code]) && $code !== 'fa') {
        $languages[$code]['active'] = !$languages[$code]['active'];
        saveLanguages($languages);
        header('Location: index.php?tab=languages&lang=' . $langCode);
        exit;
    }
}

// مدیریت زبان‌ها - حذف
if (isset($_GET['delete_lang']) && isset($_GET['code'])) {
    $code = $_GET['code'];
    if (isset($languages[$code]) && $code !== 'fa') {
        unset($languages[$code]);
        saveLanguages($languages);
        header('Location: index.php?tab=languages&lang=' . $langCode);
        exit;
    }
}

// تنظیمات SEO
if (isset($_POST['save_seo'])) {
    $settings['seo']['og_title'] = trim($_POST['og_title'] ?? $settings['seo']['og_title']);
    $settings['seo']['og_description'] = trim($_POST['og_description'] ?? $settings['seo']['og_description']);
    $settings['seo']['og_image'] = trim($_POST['og_image'] ?? $settings['seo']['og_image']);
    $settings['seo']['twitter_card'] = $_POST['twitter_card'] ?? $settings['seo']['twitter_card'];
    $settings['seo']['robots'] = $_POST['robots'] ?? $settings['seo']['robots'];
    saveSettings($settings);
    $message = '✅ ' . __('settings_saved', 'تنظیمات SEO ذخیره شد!');
    $messageType = 'success';
}

// تنظیمات شبکه‌های اجتماعی
if (isset($_POST['save_social'])) {
    $settings['social']['github'] = trim($_POST['social_github'] ?? $settings['social']['github']);
    $settings['social']['twitter'] = trim($_POST['social_twitter'] ?? $settings['social']['twitter']);
    $settings['social']['telegram'] = trim($_POST['social_telegram'] ?? $settings['social']['telegram']);
    $settings['social']['instagram'] = trim($_POST['social_instagram'] ?? $settings['social']['instagram']);
    $settings['social']['youtube'] = trim($_POST['social_youtube'] ?? '');
    $settings['social']['linkedin'] = trim($_POST['social_linkedin'] ?? '');
    $settings['social']['whatsapp'] = trim($_POST['social_whatsapp'] ?? '');
    saveSettings($settings);
    $message = '✅ ' . __('settings_saved', 'تنظیمات شبکه‌های اجتماعی ذخیره شد!');
    $messageType = 'success';
}

// تنظیمات کش
if (isset($_POST['save_cache'])) {
    $settings['cache']['enabled'] = isset($_POST['cache_enabled']);
    $settings['cache']['time'] = (int) ($_POST['cache_time'] ?? 300);
    $settings['cache']['clear_on_update'] = isset($_POST['cache_clear_on_update']);
    saveSettings($settings);
    $message = '✅ ' . __('settings_saved', 'تنظیمات کش ذخیره شد!');
    $messageType = 'success';
}

// پاکسازی کش
if (isset($_GET['clear_cache'])) {
    $cacheDir = __DIR__ . '/../cache/';
    $files = glob($cacheDir . '*.json');
    $count = 0;
    foreach ($files as $file) {
        if (basename($file) !== '.htaccess' && basename($file) !== '.gitkeep') {
            unlink($file);
            $count++;
        }
    }
    $message = "✅ $count " . __('cache_cleared', 'فایل کش پاکسازی شد!');
    $messageType = 'success';
}

// بکاپ
if (isset($_GET['backup'])) {
    $backupDir = __DIR__ . '/../backup/';
    if (!is_dir($backupDir)) mkdir($backupDir, 0777, true);
    $date = date('Y-m-d_H-i-s');
    $files = ['settings.json', 'languages.json', 'users.json'];
    $count = 0;
    foreach ($files as $file) {
        $src = CONFIG_PATH . '/' . $file;
        if (file_exists($src)) {
            copy($src, $backupDir . str_replace('.json', "_{$date}.json", $file));
            $count++;
        }
    }
    $message = "✅ $count " . __('backup_created', 'فایل بکاپ ایجاد شد!');
    $messageType = 'success';
}

// ریستور بکاپ
if (isset($_GET['restore']) && isset($_GET['file'])) {
    $file = $_GET['file'];
    $backupDir = __DIR__ . '/../backup/';
    $filePath = $backupDir . $file;
    if (file_exists($filePath)) {
        if (strpos($file, 'settings_') === 0) copy($filePath, CONFIG_PATH . '/settings.json');
        elseif (strpos($file, 'languages_') === 0) copy($filePath, CONFIG_PATH . '/languages.json');
        elseif (strpos($file, 'users_') === 0) copy($filePath, CONFIG_PATH . '/users.json');
        $message = '✅ ' . __('backup_restored', 'بکاپ با موفقیت بازیابی شد!');
        $messageType = 'success';
    } else {
        $message = '❌ ' . __('error', 'فایل بکاپ یافت نشد!');
        $messageType = 'error';
    }
}

// حذف بکاپ
if (isset($_GET['delete_backup']) && isset($_GET['file'])) {
    $file = $_GET['file'];
    $backupDir = __DIR__ . '/../backup/';
    $filePath = $backupDir . $file;
    if (file_exists($filePath)) {
        unlink($filePath);
        $message = '✅ ' . __('backup_deleted', 'فایل بکاپ با موفقیت حذف شد!');
        $messageType = 'success';
    }
}

// تنظیمات نوتیفیکیشن
if (isset($_POST['save_notifications'])) {
    if (!isset($settings['notifications'])) $settings['notifications'] = [];
    $settings['notifications']['enabled'] = isset($_POST['notif_enabled']);
    $settings['notifications']['sound'] = isset($_POST['notif_sound']);
    $settings['notifications']['browser'] = isset($_POST['notif_browser']);
    $settings['notifications']['email_alerts'] = isset($_POST['notif_email']);
    saveSettings($settings);
    $message = '✅ ' . __('settings_saved', 'تنظیمات نوتیفیکیشن ذخیره شد!');
    $messageType = 'success';
}

// تنظیمات ادمین
if (isset($_POST['save_admin'])) {
    $settings['admin']['username'] = trim($_POST['admin_username'] ?? $settings['admin']['username']);
    $settings['admin']['email'] = trim($_POST['admin_email'] ?? $settings['admin']['email']);
    saveSettings($settings);
    $message = '✅ ' . __('settings_saved', 'اطلاعات ادمین به‌روزرسانی شد!');
    $messageType = 'success';
}

// تنظیمات PWA
if (isset($_POST['save_pwa'])) {
    if (!isset($settings['pwa'])) $settings['pwa'] = [];
    $settings['pwa']['enabled'] = isset($_POST['pwa_enabled']);
    $settings['pwa']['name'] = trim($_POST['pwa_name'] ?? $settings['site']['name']);
    $settings['pwa']['short_name'] = trim($_POST['pwa_short_name'] ?? 'نرخ ارز');
    $settings['pwa']['theme_color'] = trim($_POST['pwa_theme_color'] ?? '#0a2540');
    $settings['pwa']['background_color'] = trim($_POST['pwa_background_color'] ?? '#0a2540');
    saveSettings($settings);
    $message = '✅ ' . __('settings_saved', 'تنظیمات PWA ذخیره شد!');
    $messageType = 'success';
}

// به‌روزرسانی manifest.json
if (isset($_POST['update_manifest'])) {
    $manifestFile = ROOT_PATH . '/manifest.json';
    if (file_exists($manifestFile)) {
        $manifest = json_decode(file_get_contents($manifestFile), true);
        if ($manifest) {
            $manifest['name'] = $_POST['pwa_name'] ?? $manifest['name'] ?? 'نرخ ارز مرجع';
            $manifest['short_name'] = $_POST['pwa_short_name'] ?? $manifest['short_name'] ?? 'نرخ ارز';
            $manifest['theme_color'] = $_POST['pwa_theme_color'] ?? $manifest['theme_color'] ?? '#0a2540';
            $manifest['background_color'] = $_POST['pwa_background_color'] ?? $manifest['background_color'] ?? '#0a2540';
            file_put_contents($manifestFile, json_encode($manifest, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
            $message = '✅ ' . __('settings_saved', 'فایل manifest.json به‌روزرسانی شد!');
            $messageType = 'success';
        }
    }
}

// تنظیمات پیش‌فرض
if (!isset($settings['notifications'])) {
    $settings['notifications'] = ['enabled' => true, 'sound' => true, 'browser' => true, 'email_alerts' => false];
    saveSettings($settings);
}
if (!isset($settings['pwa'])) {
    $settings['pwa'] = ['enabled' => true, 'name' => $settings['site']['name'] ?? 'نرخ ارز مرجع', 'short_name' => 'نرخ ارز', 'theme_color' => '#0a2540', 'background_color' => '#0a2540'];
    saveSettings($settings);
}

// بارگذاری مجدد
$settings = loadSettings();
$languages = loadLanguages();

$backupDir = __DIR__ . '/../backup/';
if (!is_dir($backupDir)) mkdir($backupDir, 0777, true);
$backupFiles = glob($backupDir . '*.json');

$cacheFiles = glob(__DIR__ . '/../cache/*.json');
$cacheCount = 0;
$cacheSize = 0;
foreach ($cacheFiles as $file) {
    if (basename($file) !== '.htaccess' && basename($file) !== '.gitkeep') {
        $cacheCount++;
        $cacheSize += filesize($file);
    }
}

if (isset($_GET['msg']) && $_GET['msg'] === 'removed') {
    $message = '✅ ' . __('currency_removed', 'ارز با موفقیت حذف شد!');
    $messageType = 'success';
}

// ===== تشخیص تم =====
$theme = isset($_COOKIE['admin_theme']) ? $_COOKIE['admin_theme'] : 'light';
if (isset($_GET['theme'])) {
    $theme = $_GET['theme'] === 'dark' ? 'dark' : 'light';
    setcookie('admin_theme', $theme, time() + 31536000, '/');
    header('Location: ?tab=' . $activeTab . '&lang=' . $langCode);
    exit;
}
?>
<!DOCTYPE html>
<html lang="fa" dir="<?php echo $langDir; ?>" data-theme="<?php echo $theme; ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo __('admin_panel', 'پنل مدیریت'); ?></title>
    
    <style>
        @font-face {
            font-family: 'Vazir';
            src: url('../fonts/Vazir.ttf') format('truetype');
            font-weight: normal;
            font-style: normal;
        }
        @font-face {
            font-family: 'Vazir';
            src: url('../fonts/Vazir-Bold.ttf') format('truetype');
            font-weight: bold;
            font-style: normal;
        }
    </style>
    
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <style>
        /* ============================================
                   CSS VARIABLES
                ============================================ */
        :root {
            --bg-primary: #eef3f9;
            --bg-secondary: #ffffff;
            --bg-card: #ffffff;
            --bg-header: #0a2540;
            --sidebar-bg: linear-gradient(180deg, #0a2540, #0d1b2a);
            --text-primary: #0a2540;
            --text-secondary: #3a4a6a;
            --text-muted: #6b7d9a;
            --border-color: rgba(200, 215, 235, 0.3);
            --shadow: 0 30px 70px rgba(0, 20, 50, 0.12);
            --shadow-sm: 0 4px 12px rgba(0, 0, 0, 0.04);
            --hover-bg: #f6faff;
            --badge-bg: #eef3fa;
            --success: #1a7a4a;
            --danger: #c0392b;
            --warning: #ffc107;
            --info: #17a2b8;
            --glass-bg: rgba(255, 255, 255, 0.88);
            --glass-border: rgba(255, 255, 255, 0.5);
            --sidebar-text: #b0c4db;
            --sidebar-hover: rgba(255,255,255,0.06);
            --sidebar-active: rgba(127, 219, 154, 0.12);
            --input-bg: #fafbfc;
            --input-border: #d0ddea;
        }
        
        [data-theme="dark"] {
            --bg-primary: #0d1b2a;
            --bg-secondary: #1b2d45;
            --bg-card: #1a2d44;
            --bg-header: #0a1628;
            --sidebar-bg: linear-gradient(180deg, #0a1628, #0d1b2a);
            --text-primary: #e8edf5;
            --text-secondary: #b0c4db;
            --text-muted: #7a8fa8;
            --border-color: rgba(60, 90, 130, 0.4);
            --shadow: 0 30px 70px rgba(0, 0, 0, 0.5);
            --shadow-sm: 0 4px 12px rgba(0, 0, 0, 0.2);
            --hover-bg: #1a2d45;
            --badge-bg: #243b58;
            --success: #2ecc71;
            --danger: #e74c3c;
            --warning: #f39c12;
            --info: #3498db;
            --glass-bg: rgba(20, 40, 65, 0.9);
            --glass-border: rgba(60, 90, 130, 0.3);
            --sidebar-text: #8aabca;
            --sidebar-hover: rgba(255,255,255,0.04);
            --sidebar-active: rgba(127, 219, 154, 0.08);
            --input-bg: #1a2d45;
            --input-border: #2d4a6a;
        }
        
        /* ============================================
                   RESET & BASE
                ============================================ */
        * { margin: 0; padding: 0; box-sizing: border-box; }
        
        body {
            font-family: 'Vazir', 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: var(--bg-primary);
            display: flex;
            min-height: 100vh;
            direction: <?php echo $langDir; ?>;
            transition: background 0.3s ease;
        }
        
        /* ============================================
                   SIDEBAR
                ============================================ */
        .sidebar {
            width: 260px;
            background: var(--sidebar-bg);
            color: white;
            padding: 20px 0;
            position: fixed;
            top: 0;
            right: <?php echo $langDir === 'rtl' ? '0' : 'auto'; ?>;
            left: <?php echo $langDir === 'rtl' ? 'auto' : '0'; ?>;
            bottom: 0;
            overflow-y: auto;
            transition: all 0.3s ease;
            z-index: 1000;
            box-shadow: 4px 0 30px rgba(0, 0, 0, 0.2);
        }
        
        .sidebar-brand {
            padding: 0 20px 20px;
            border-bottom: 1px solid rgba(255,255,255,0.06);
            margin-bottom: 15px;
            text-align: center;
        }
        
        .sidebar-brand .icon {
            display: inline-block;
            background: rgba(255,255,255,0.1);
            padding: 12px 14px;
            border-radius: 16px;
            margin-bottom: 8px;
        }
        
        .sidebar-brand .icon i {
            font-size: 1.8rem;
            color: #7fdb9a;
        }
        
        .sidebar-brand h2 {
            font-size: 1.1rem;
            font-weight: 700;
            color: white;
        }
        
        .sidebar-brand small {
            font-size: 0.65rem;
            color: #8aabca;
            display: block;
            margin-top: 2px;
        }
        
        .sidebar-close {
            display: none;
            position: absolute;
            top: 12px;
            left: 16px;
            background: none;
            border: none;
            color: white;
            font-size: 1.5rem;
            cursor: pointer;
        }
        
        /* ===== منوی سایدبار ===== */
        .sidebar-menu {
            list-style: none;
            padding: 0 10px;
        }
        
        .sidebar-menu li { margin-bottom: 2px; }
        
        .sidebar-menu li a {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 10px 16px;
            color: var(--sidebar-text);
            text-decoration: none;
            border-radius: 10px;
            transition: all 0.3s ease;
            font-size: 0.85rem;
        }
        
        .sidebar-menu li a:hover {
            background: var(--sidebar-hover);
            color: white;
        }
        
        .sidebar-menu li a.active {
            background: var(--sidebar-active);
            color: #7fdb9a;
            box-shadow: inset 3px 0 0 #7fdb9a;
        }
        
        .sidebar-menu li a i {
            width: 20px;
            text-align: center;
            font-size: 0.95rem;
        }
        
        .sidebar-menu li a .badge {
            margin-right: auto;
            background: rgba(255,255,255,0.08);
            padding: 1px 10px;
            border-radius: 30px;
            font-size: 0.6rem;
            color: var(--sidebar-text);
        }
        
        .sidebar-menu .divider {
            height: 1px;
            background: rgba(255,255,255,0.06);
            margin: 12px 16px;
        }
        
        /* ============================================
                   MAIN CONTENT
                ============================================ */
        .main-content {
            <?php echo $langDir === 'rtl' ? 'margin-right: 260px;' : 'margin-left: 260px;'; ?>
            flex: 1;
            padding: 20px 25px 30px;
            min-height: 100vh;
            transition: margin 0.3s ease;
        }
        
        /* ============================================
                   HEADER
                ============================================ */
        .top-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 12px;
            background: var(--bg-secondary);
            padding: 14px 22px;
            border-radius: 14px;
            box-shadow: var(--shadow-sm);
            margin-bottom: 22px;
            border: 1px solid var(--border-color);
            transition: background 0.3s ease, border-color 0.3s ease;
        }
        
        .top-header .left {
            display: flex;
            align-items: center;
            gap: 12px;
        }
        
        .top-header .left h1 {
            font-size: 1.1rem;
            color: var(--text-primary);
            transition: color 0.3s ease;
        }
        
        .top-header .left h1 i {
            color: #1a4a7a;
            margin-<?php echo $langDir === 'rtl' ? 'left' : 'right'; ?>: 8px;
        }
        
        .top-header .right {
            display: flex;
            align-items: center;
            gap: 8px;
            flex-wrap: wrap;
        }
        
        .hamburger {
            display: none;
            background: none;
            border: none;
            font-size: 1.4rem;
            color: var(--text-primary);
            cursor: pointer;
            padding: 5px;
        }
        
        /* ===== Language Switcher ===== */
        .lang-switcher {
            display: flex;
            gap: 4px;
            align-items: center;
            background: var(--badge-bg);
            padding: 3px 6px;
            border-radius: 30px;
            border: 1px solid var(--border-color);
        }
        
        .lang-btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 30px;
            height: 30px;
            border-radius: 50%;
            border: 2px solid transparent;
            background: transparent;
            font-size: 0.9rem;
            cursor: pointer;
            transition: all 0.3s ease;
            text-decoration: none;
            line-height: 1;
        }
        
        .lang-btn:hover {
            border-color: var(--text-primary);
            transform: scale(1.1);
        }
        
        .lang-btn.active {
            border-color: var(--text-primary);
            background: var(--text-primary);
            transform: scale(1.1);
        }
        
        .lang-btn.active .flag {
            filter: brightness(10);
        }
        
        [data-theme="dark"] .lang-btn.active {
            border-color: #4a8aaa;
            background: #1a3a5a;
        }
        
        /* ===== Theme Toggle ===== */
        .theme-toggle {
            background: var(--badge-bg);
            border: 1px solid var(--border-color);
            color: var(--text-primary);
            width: 36px;
            height: 36px;
            border-radius: 50%;
            cursor: pointer;
            font-size: 1rem;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: all 0.3s ease;
        }
        
        .theme-toggle:hover {
            transform: scale(1.05);
            box-shadow: var(--shadow-sm);
        }
        
        /* ===== Header Buttons ===== */
        .header-btn {
            background: var(--badge-bg);
            border: 1px solid var(--border-color);
            color: var(--text-secondary);
            padding: 6px 14px;
            border-radius: 30px;
            font-family: 'Vazir', sans-serif;
            font-size: 0.75rem;
            cursor: pointer;
            transition: all 0.3s ease;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 6px;
        }
        
        .header-btn:hover {
            background: var(--hover-bg);
        }
        
        .header-btn.danger:hover {
            background: rgba(220, 53, 69, 0.1);
            border-color: var(--danger);
            color: var(--danger);
        }
        
        .header-btn .avatar {
            width: 28px;
            height: 28px;
            border-radius: 50%;
            background: var(--bg-header);
            color: white;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-weight: 700;
            font-size: 0.7rem;
        }
        
        /* ============================================
                   CARDS
                ============================================ */
        .card {
            background: var(--bg-card);
            border-radius: 14px;
            padding: 22px 24px;
            margin-bottom: 18px;
            box-shadow: var(--shadow-sm);
            border: 1px solid var(--border-color);
            animation: fadeIn 0.4s ease-out;
            transition: background 0.3s ease, border-color 0.3s ease;
        }
        
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(10px); }
            to { opacity: 1; transform: translateY(0); }
        }
        
        .card h2 {
            font-size: 0.95rem;
            color: var(--text-primary);
            margin-bottom: 16px;
            display: flex;
            align-items: center;
            gap: 10px;
            padding-bottom: 10px;
            border-bottom: 2px solid var(--border-color);
            transition: color 0.3s ease, border-color 0.3s ease;
        }
        
        .card h2 i { color: #1a4a7a; }
        
        /* ============================================
                   FORM
                ============================================ */
        .form-group { margin-bottom: 12px; }
        .form-group label {
            display: block;
            font-size: 0.8rem;
            color: var(--text-secondary);
            margin-bottom: 3px;
            font-weight: 600;
            transition: color 0.3s ease;
        }
        .form-group input, .form-group select, .form-group textarea {
            width: 100%;
            padding: 9px 14px;
            border: 2px solid var(--input-border);
            border-radius: 8px;
            font-size: 0.85rem;
            transition: all 0.3s ease;
            background: var(--input-bg);
            font-family: 'Vazir', sans-serif;
            color: var(--text-primary);
        }
        .form-group input:focus, .form-group select:focus, .form-group textarea:focus {
            outline: none;
            border-color: #0a2540;
            box-shadow: 0 0 0 3px rgba(10, 37, 64, 0.06);
        }
        .form-group textarea { resize: vertical; min-height: 50px; }
        .form-group .hint {
            font-size: 0.65rem;
            color: var(--text-muted);
            margin-top: 2px;
        }
        .form-row { display: grid; grid-template-columns: 1fr 1fr; gap: 12px; }
        .form-row-3 { display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 12px; }
        
        .form-check {
            display: flex;
            align-items: center;
            gap: 8px;
            margin-bottom: 5px;
        }
        .form-check input[type="checkbox"] {
            width: 17px;
            height: 17px;
            cursor: pointer;
            accent-color: #0a2540;
        }
        .form-check label {
            font-size: 0.82rem;
            color: var(--text-secondary);
            cursor: pointer;
            transition: color 0.3s ease;
        }
        
        /* ============================================
                   BUTTONS
                ============================================ */
        .btn {
            padding: 8px 22px;
            border: none;
            border-radius: 60px;
            font-size: 0.8rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            display: inline-flex;
            align-items: center;
            gap: 6px;
            font-family: 'Vazir', sans-serif;
            text-decoration: none;
        }
        .btn:hover { transform: translateY(-2px); box-shadow: var(--shadow-sm); }
        .btn-primary { background: #0a2540; color: white; }
        .btn-success { background: var(--success); color: white; }
        .btn-danger { background: var(--danger); color: white; }
        .btn-warning { background: var(--warning); color: #0a2540; }
        .btn-secondary { background: var(--badge-bg); color: var(--text-primary); border: 1px solid var(--border-color); }
        .btn-sm { padding: 4px 14px; font-size: 0.7rem; }
        .btn-block { width: 100%; justify-content: center; }
        
        /* ============================================
                   MESSAGE
                ============================================ */
        .message {
            padding: 10px 16px;
            border-radius: 10px;
            margin-bottom: 16px;
            display: flex;
            align-items: center;
            gap: 10px;
            font-size: 0.85rem;
        }
        .message.success { background: rgba(40, 167, 69, 0.12); color: var(--success); border-right: 4px solid var(--success); }
        .message.error { background: rgba(220, 53, 69, 0.12); color: var(--danger); border-right: 4px solid var(--danger); }
        .message.info { background: rgba(23, 162, 184, 0.12); color: var(--info); border-right: 4px solid var(--info); }
        
        /* ============================================
                   INFO GRID
                ============================================ */
        .info-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
            gap: 10px;
        }
        .info-item {
            background: var(--badge-bg);
            padding: 10px 14px;
            border-radius: 8px;
            transition: background 0.3s ease;
        }
        .info-item .label {
            font-size: 0.65rem;
            color: var(--text-muted);
        }
        .info-item .value {
            font-size: 0.9rem;
            font-weight: 600;
            color: var(--text-primary);
        }
        
        /* ============================================
                   LANGUAGE LIST
                ============================================ */
        .lang-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 10px 14px;
            border: 1px solid var(--border-color);
            border-radius: 10px;
            margin-bottom: 6px;
            transition: all 0.3s ease;
        }
        .lang-item:hover { background: var(--hover-bg); }
        .lang-item .lang-info { display: flex; align-items: center; gap: 12px; flex-wrap: wrap; }
        .lang-item .lang-info .flag { font-size: 1.3rem; }
        .lang-item .lang-info .name { font-weight: 600; color: var(--text-primary); font-size: 0.9rem; }
        .lang-item .lang-info .code { font-size: 0.65rem; color: var(--text-muted); background: var(--badge-bg); padding: 1px 10px; border-radius: 30px; }
        .lang-item .lang-info .dir { font-size: 0.6rem; background: var(--badge-bg); padding: 1px 10px; border-radius: 30px; color: var(--text-secondary); }
        .lang-item .lang-actions { display: flex; gap: 4px; flex-wrap: wrap; }
        
        /* ============================================
                   BACKUP LIST
                ============================================ */
        .backup-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 8px 14px;
            border: 1px solid var(--border-color);
            border-radius: 8px;
            margin-bottom: 4px;
        }
        .backup-item .info { font-size: 0.8rem; color: var(--text-secondary); }
        .backup-item .info .date { font-weight: 600; color: var(--text-primary); }
        .backup-item .info .size { color: var(--text-muted); font-size: 0.7rem; }
        .backup-item .actions { display: flex; gap: 4px; }
        
        /* ============================================
                   CURRENCY TAGS
                ============================================ */
        .major-currency-tag {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            background: var(--badge-bg);
            padding: 4px 12px;
            border-radius: 30px;
            font-size: 0.8rem;
            color: var(--text-primary);
            margin: 3px;
        }
        .major-currency-tag .remove {
            color: var(--danger);
            cursor: pointer;
            font-size: 0.7rem;
            transition: all 0.3s ease;
        }
        .major-currency-tag .remove:hover { transform: scale(1.2); }
        
        /* ============================================
                   TRANSLATIONS GRID
                ============================================ */
        .translations-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 8px;
            max-height: 300px;
            overflow-y: auto;
            padding: 5px;
        }
        .translations-grid .form-group { margin-bottom: 4px; }
        .translations-grid .form-group label {
            font-size: 0.7rem;
            color: var(--text-muted);
        }
        .translations-grid .form-group input {
            font-size: 0.8rem;
            padding: 6px 10px;
        }
        
        /* ============================================
                   PASSWORD TOGGLE
                ============================================ */
        .password-wrapper {
            position: relative;
            display: flex;
            align-items: center;
        }
        .password-wrapper input {
            padding-left: 45px;
        }
        .toggle-password {
            position: absolute;
            left: 12px;
            background: none;
            border: none;
            color: var(--text-muted);
            cursor: pointer;
            font-size: 1rem;
            padding: 5px;
            transition: all 0.3s ease;
        }
        .toggle-password:hover { color: var(--text-primary); }
        
        /* ============================================
                   RESPONSIVE
                ============================================ */
        @media (max-width: 992px) {
            .sidebar {
                transform: translateX(<?php echo $langDir === 'rtl' ? '100%' : '-100%'; ?>);
                width: 280px;
            }
            .sidebar.open { transform: translateX(0); }
            .sidebar-close { display: block; }
            .main-content {
                <?php echo $langDir === 'rtl' ? 'margin-right: 0;' : 'margin-left: 0;'; ?>
                padding: 14px;
            }
            .hamburger { display: block; }
            .form-row { grid-template-columns: 1fr; }
            .form-row-3 { grid-template-columns: 1fr; }
            .translations-grid { grid-template-columns: 1fr; }
        }
        
        @media (max-width: 768px) {
            .top-header {
                flex-direction: column;
                align-items: stretch;
                gap: 10px;
            }
            .top-header .right {
                justify-content: space-between;
                flex-wrap: wrap;
            }
            .card { padding: 18px 14px; }
            .info-grid { grid-template-columns: 1fr 1fr; }
            .lang-item { flex-direction: column; gap: 8px; align-items: stretch; }
            .lang-item .lang-actions { justify-content: center; }
            .backup-item { flex-direction: column; gap: 6px; align-items: stretch; }
            .backup-item .actions { justify-content: center; }
        }
        
        @media (max-width: 480px) {
            .top-header .right .btn-text { display: none; }
            .lang-btn { width: 26px; height: 26px; font-size: 0.8rem; }
            .info-grid { grid-template-columns: 1fr; }
        }
        
        /* ============================================
                   SCROLLBAR
                ============================================ */
        .sidebar::-webkit-scrollbar { width: 4px; }
        .sidebar::-webkit-scrollbar-track { background: rgba(255,255,255,0.02); }
        .sidebar::-webkit-scrollbar-thumb { background: rgba(255,255,255,0.15); border-radius: 10px; }
        
        .translations-grid::-webkit-scrollbar { width: 4px; }
        .translations-grid::-webkit-scrollbar-track { background: var(--badge-bg); border-radius: 10px; }
        .translations-grid::-webkit-scrollbar-thumb { background: var(--text-muted); border-radius: 10px; }
        
        ::-webkit-scrollbar { width: 6px; }
        ::-webkit-scrollbar-track { background: var(--bg-primary); }
        ::-webkit-scrollbar-thumb { background: var(--text-muted); border-radius: 10px; }
        ::-webkit-scrollbar-thumb:hover { background: var(--text-secondary); }
        
        /* ============================================
                   ALERT WARNING
                ============================================ */
        .alert-warning {
            background: rgba(255, 193, 7, 0.12);
            color: #856404;
            padding: 10px 16px;
            border-radius: 8px;
            border-right: 4px solid #ffc107;
            font-size: 0.8rem;
            margin-bottom: 12px;
        }
        
        [data-theme="dark"] .alert-warning {
            background: rgba(243, 156, 18, 0.12);
            color: #f39c12;
            border-right-color: #f39c12;
        }
    </style>
</head>
<body>
    
    <!-- ============================================
               SIDEBAR
            ============================================ -->
    <aside class="sidebar" id="sidebar">
        <button class="sidebar-close" onclick="closeSidebar()">
            <i class="fas fa-times"></i>
        </button>
        
        <div class="sidebar-brand">
            <div class="icon">
                <i class="fas fa-coins"></i>
            </div>
            <h2><?php echo __('site_title', 'نرخ ارز مرجع'); ?></h2>
            <small><?php echo __('admin_panel', 'پنل مدیریت'); ?></small>
        </div>
        
        <ul class="sidebar-menu">
            <!-- ===== داشبورد ===== -->
            <li>
                <a href="?tab=dashboard&lang=<?php echo $langCode; ?>" class="<?php echo $activeTab === 'dashboard' ? 'active' : ''; ?>">
                    <i class="fas fa-chart-pie"></i> <?php echo __('dashboard', 'داشبورد'); ?>
                </a>
            </li>
            
            <!-- ===== تنظیمات عمومی ===== -->
            <li>
                <a href="?tab=general&lang=<?php echo $langCode; ?>" class="<?php echo $activeTab === 'general' ? 'active' : ''; ?>">
                    <i class="fas fa-globe"></i> <?php echo __('general_settings', 'تنظیمات عمومی'); ?>
                </a>
            </li>
            
            <!-- ===== ویژگی‌ها ===== -->
            <li>
                <a href="?tab=features&lang=<?php echo $langCode; ?>" class="<?php echo $activeTab === 'features' ? 'active' : ''; ?>">
                    <i class="fas fa-cubes"></i> <?php echo __('features', 'ویژگی‌ها'); ?>
                </a>
            </li>
            
            <!-- ===== ارزها ===== -->
            <li>
                <a href="?tab=currencies&lang=<?php echo $langCode; ?>" class="<?php echo $activeTab === 'currencies' ? 'active' : ''; ?>">
                    <i class="fas fa-coins"></i> <?php echo __('currencies', 'ارزها'); ?>
                    <span class="badge"><?php echo count($settings['currencies']['major'] ?? []); ?></span>
                </a>
            </li>
            
            <!-- ===== زبان‌ها ===== -->
            <li>
                <a href="?tab=languages&lang=<?php echo $langCode; ?>" class="<?php echo $activeTab === 'languages' ? 'active' : ''; ?>">
                    <i class="fas fa-language"></i> <?php echo __('languages', 'زبان‌ها'); ?>
                    <span class="badge"><?php echo count($languages); ?></span>
                </a>
            </li>
            
            <!-- ===== کاربران ===== -->
            <li>
                <a href="users.php?lang=<?php echo $langCode; ?>">
                    <i class="fas fa-users"></i> <?php echo __('users', 'کاربران'); ?>
                    <span class="badge"><?php echo $totalUsers; ?></span>
                </a>
            </li>
            
            <li class="divider"></li>
            
            <!-- ===== SEO ===== -->
            <li>
                <a href="?tab=seo&lang=<?php echo $langCode; ?>" class="<?php echo $activeTab === 'seo' ? 'active' : ''; ?>">
                    <i class="fas fa-search"></i> <?php echo __('seo', 'SEO'); ?>
                </a>
            </li>
            
            <!-- ===== شبکه‌های اجتماعی ===== -->
            <li>
                <a href="?tab=social&lang=<?php echo $langCode; ?>" class="<?php echo $activeTab === 'social' ? 'active' : ''; ?>">
                    <i class="fas fa-share-alt"></i> <?php echo __('social_networks', 'شبکه‌های اجتماعی'); ?>
                </a>
            </li>
            
            <!-- ===== PWA ===== -->
            <li>
                <a href="?tab=pwa&lang=<?php echo $langCode; ?>" class="<?php echo $activeTab === 'pwa' ? 'active' : ''; ?>">
                    <i class="fas fa-mobile-alt"></i> <?php echo __('pwa_settings', 'PWA'); ?>
                    <span class="badge"><?php echo ($settings['pwa']['enabled'] ?? true) ? '✅' : '⛔'; ?></span>
                </a>
            </li>
            
            <li class="divider"></li>
            
            <!-- ===== کش ===== -->
            <li>
                <a href="?tab=cache&lang=<?php echo $langCode; ?>" class="<?php echo $activeTab === 'cache' ? 'active' : ''; ?>">
                    <i class="fas fa-broom"></i> <?php echo __('cache', 'کش'); ?>
                    <span class="badge"><?php echo $cacheCount; ?></span>
                </a>
            </li>
            
            <!-- ===== بکاپ ===== -->
            <li>
                <a href="?tab=backup&lang=<?php echo $langCode; ?>" class="<?php echo $activeTab === 'backup' ? 'active' : ''; ?>">
                    <i class="fas fa-database"></i> <?php echo __('backup', 'بکاپ'); ?>
                    <span class="badge"><?php echo count($backupFiles); ?></span>
                </a>
            </li>
            
            <!-- ===== امنیت ===== -->
            <li>
                <a href="?tab=security&lang=<?php echo $langCode; ?>" class="<?php echo $activeTab === 'security' ? 'active' : ''; ?>">
                    <i class="fas fa-shield-alt"></i> <?php echo __('security', 'امنیت'); ?>
                </a>
            </li>
            
            <!-- ===== نوتیفیکیشن ===== -->
            <li>
                <a href="?tab=notifications&lang=<?php echo $langCode; ?>" class="<?php echo $activeTab === 'notifications' ? 'active' : ''; ?>">
                    <i class="fas fa-bell"></i> <?php echo __('notifications', 'نوتیفیکیشن'); ?>
                    <span class="badge"><?php echo ($settings['notifications']['enabled'] ?? true) ? '✅' : '⛔'; ?></span>
                </a>
            </li>
            
            <li class="divider"></li>
            
            <!-- ===== لینک‌های خارجی ===== -->
            <li>
                <a href="https://mozili.ir/arz/" target="_blank">
                    <i class="fas fa-globe"></i> <?php echo __('back_to_site', 'مشاهده سایت'); ?>
                </a>
            </li>
            <li>
                <a href="logout.php?lang=<?php echo $langCode; ?>" style="color:#e74c3c;">
                    <i class="fas fa-sign-out-alt"></i> <?php echo __('logout', 'خروج'); ?>
                </a>
            </li>
        </ul>
    </aside>
    
    <!-- ============================================
               MAIN CONTENT
            ============================================ -->
    <main class="main-content" id="mainContent">
        
        <!-- ============================================
                   HEADER
                ============================================ -->
        <header class="top-header">
            <div class="left">
                <button class="hamburger" onclick="toggleSidebar()">
                    <i class="fas fa-bars"></i>
                </button>
                <h1>
                    <i class="fas fa-<?php 
                        $icons = [
                            'dashboard' => 'chart-pie',
                            'general' => 'globe',
                            'features' => 'cubes',
                            'currencies' => 'coins',
                            'languages' => 'language',
                            'seo' => 'search',
                            'social' => 'share-alt',
                            'pwa' => 'mobile-alt',
                            'cache' => 'broom',
                            'backup' => 'database',
                            'security' => 'shield-alt',
                            'notifications' => 'bell'
                        ];
                        echo $icons[$activeTab] ?? 'cog';
                    ?>"></i>
                    <?php 
                        $titles = [
                            'dashboard' => __('dashboard', 'داشبورد'),
                            'general' => __('general_settings', 'تنظیمات عمومی'),
                            'features' => __('features', 'ویژگی‌ها'),
                            'currencies' => __('currencies', 'ارزها'),
                            'languages' => __('languages', 'زبان‌ها'),
                            'seo' => __('seo', 'SEO'),
                            'social' => __('social_networks', 'شبکه‌های اجتماعی'),
                            'pwa' => __('pwa_settings', 'PWA'),
                            'cache' => __('cache', 'کش'),
                            'backup' => __('backup', 'بکاپ'),
                            'security' => __('security', 'امنیت'),
                            'notifications' => __('notifications', 'نوتیفیکیشن')
                        ];
                        echo $titles[$activeTab] ?? __('admin_panel', 'پنل مدیریت');
                    ?>
                </h1>
            </div>
            <div class="right">
                <!-- ===== Theme Toggle ===== -->
                <a href="?tab=<?php echo $activeTab; ?>&lang=<?php echo $langCode; ?>&theme=<?php echo $theme === 'light' ? 'dark' : 'light'; ?>" class="theme-toggle" title="<?php echo $theme === 'light' ? __('theme_dark', 'تم تاریک') : __('theme_light', 'تم روشن'); ?>">
                    <i class="fas fa-<?php echo $theme === 'light' ? 'moon' : 'sun'; ?>"></i>
                </a>
                
                <!-- ===== Language Switcher ===== -->
                <div class="lang-switcher">
                    <a href="<?php echo switchLang('fa'); ?>" class="lang-btn <?php echo $langCode === 'fa' ? 'active' : ''; ?>" title="فارسی">🇮🇷</a>
                    <a href="<?php echo switchLang('en'); ?>" class="lang-btn <?php echo $langCode === 'en' ? 'active' : ''; ?>" title="English">🇬🇧</a>
                    <a href="<?php echo switchLang('ar'); ?>" class="lang-btn <?php echo $langCode === 'ar' ? 'active' : ''; ?>" title="العربية">🇸🇦</a>
                    <a href="<?php echo switchLang('tr'); ?>" class="lang-btn <?php echo $langCode === 'tr' ? 'active' : ''; ?>" title="Türkçe">🇹🇷</a>
                    <a href="<?php echo switchLang('es'); ?>" class="lang-btn <?php echo $langCode === 'es' ? 'active' : ''; ?>" title="Español">🇪🇸</a>
                </div>
                
                <a href="?clear_cache=1&tab=<?php echo $activeTab; ?>&lang=<?php echo $langCode; ?>" class="header-btn" onclick="return confirm('<?php echo __('confirm_delete', 'آیا از پاکسازی کش مطمئن هستید؟'); ?>')">
                    <i class="fas fa-broom"></i>
                    <span class="btn-text"><?php echo __('cache', 'کش'); ?></span>
                </a>
                
                <span class="header-btn">
                    <span class="avatar"><?php echo strtoupper(substr($currentUser['fullname'] ?? $currentUser['username'] ?? 'A', 0, 1)); ?></span>
                    <?php echo htmlspecialchars($currentUser['fullname'] ?? $currentUser['username'] ?? 'admin'); ?>
                </span>
            </div>
        </header>
        
        <!-- ===== Message ===== -->
        <?php if ($message): ?>
        <div class="message <?php echo $messageType; ?>">
            <i class="fas fa-<?php echo $messageType === 'success' ? 'check-circle' : ($messageType === 'error' ? 'exclamation-circle' : 'info-circle'); ?>"></i>
            <?php echo $message; ?>
        </div>
        <?php endif; ?>
        
        <!-- ============================================================ -->
        <!-- ===== داشبورد ===== -->
        <!-- ============================================================ -->
        <?php if ($activeTab === 'dashboard'): ?>
        
        <div class="card">
            <h2><i class="fas fa-user-circle"></i> <?php echo __('user_info', 'اطلاعات کاربری'); ?></h2>
            <div class="info-grid">
                <div class="info-item">
                    <div class="label"><?php echo __('username', 'نام کاربری'); ?></div>
                    <div class="value"><?php echo htmlspecialchars($currentUser['username'] ?? 'admin'); ?></div>
                </div>
                <div class="info-item">
                    <div class="label"><?php echo __('fullname', 'نام کامل'); ?></div>
                    <div class="value"><?php echo htmlspecialchars($currentUser['fullname'] ?? '-'); ?></div>
                </div>
                <div class="info-item">
                    <div class="label"><?php echo __('role', 'نقش'); ?></div>
                    <div class="value"><?php echo __('admin', 'مدیر'); ?></div>
                </div>
                <div class="info-item">
                    <div class="label"><?php echo __('email', 'ایمیل'); ?></div>
                    <div class="value"><?php echo htmlspecialchars($settings['admin']['email'] ?? 'admin@localhost'); ?></div>
                </div>
                <div class="info-item">
                    <div class="label"><?php echo __('status', 'وضعیت سایت'); ?></div>
                    <div class="value">
                        <?php if ($settings['site']['maintenance'] ?? false): ?>
                        <span style="color:var(--danger);">🔧 <?php echo __('maintenance_mode', 'حالت تعمیرات'); ?></span>
                        <?php else: ?>
                        <span style="color:var(--success);">✅ <?php echo __('active', 'فعال'); ?></span>
                        <?php endif; ?>
                    </div>
                </div>
                <div class="info-item">
                    <div class="label"><?php echo __('users', 'کاربران'); ?></div>
                    <div class="value"><?php echo $totalUsers; ?> <?php echo __('user', 'نفر'); ?></div>
                </div>
            </div>
        </div>
        
        <div class="card">
            <h2><i class="fas fa-chart-simple"></i> <?php echo __('system_stats', 'آمار سیستم'); ?></h2>
            <div class="info-grid">
                <div class="info-item">
                    <div class="label"><?php echo __('php_version', 'نسخه PHP'); ?></div>
                    <div class="value"><?php echo phpversion(); ?></div>
                </div>
                <div class="info-item">
                    <div class="label"><?php echo __('currencies', 'ارزهای شاخص'); ?></div>
                    <div class="value"><?php echo count($settings['currencies']['major'] ?? []); ?> <?php echo __('currency', 'ارز'); ?></div>
                </div>
                <div class="info-item">
                    <div class="label"><?php echo __('languages', 'زبان‌های فعال'); ?></div>
                    <div class="value">
                        <?php 
                            $activeLangs = array_filter($languages, function($lang) {
                                return $lang['active'] ?? false;
                            });
                            echo count($activeLangs) . ' ' . __('language', 'زبان');
                        ?>
                    </div>
                </div>
                <div class="info-item">
                    <div class="label"><?php echo __('cache', 'فایل‌های کش'); ?></div>
                    <div class="value"><?php echo $cacheCount; ?> <?php echo __('file', 'فایل'); ?></div>
                </div>
                <div class="info-item">
                    <div class="label"><?php echo __('cache_size', 'حجم کش'); ?></div>
                    <div class="value"><?php echo round($cacheSize / 1024, 2); ?> KB</div>
                </div>
                <div class="info-item">
                    <div class="label"><?php echo __('backup', 'بکاپ‌ها'); ?></div>
                    <div class="value"><?php echo count($backupFiles); ?> <?php echo __('file', 'فایل'); ?></div>
                </div>
            </div>
        </div>
        
        <div class="card">
            <h2><i class="fas fa-link"></i> <?php echo __('quick_links', 'لینک‌های سریع'); ?></h2>
            <div style="display:flex;gap:10px;flex-wrap:wrap;">
                <a href="users.php?lang=<?php echo $langCode; ?>" class="btn btn-primary"><i class="fas fa-users"></i> <?php echo __('users', 'کاربران'); ?></a>
                <a href="?tab=languages&lang=<?php echo $langCode; ?>" class="btn btn-success"><i class="fas fa-language"></i> <?php echo __('languages', 'زبان‌ها'); ?></a>
                <a href="?tab=currencies&lang=<?php echo $langCode; ?>" class="btn btn-warning"><i class="fas fa-coins"></i> <?php echo __('currencies', 'ارزها'); ?></a>
                <a href="?tab=backup&lang=<?php echo $langCode; ?>" class="btn btn-secondary"><i class="fas fa-database"></i> <?php echo __('backup', 'بکاپ'); ?></a>
                <a href="?clear_cache=1&tab=<?php echo $activeTab; ?>&lang=<?php echo $langCode; ?>" class="btn btn-danger" onclick="return confirm('<?php echo __('confirm_delete', 'آیا از پاکسازی کش مطمئن هستید؟'); ?>')">
                    <i class="fas fa-broom"></i> <?php echo __('cache', 'پاکسازی کش'); ?>
                </a>
            </div>
        </div>
        
        <?php endif; ?>
        
        <!-- ============================================================ -->
        <!-- ===== تنظیمات عمومی ===== -->
        <!-- ============================================================ -->
        <?php if ($activeTab === 'general'): ?>
        
        <div class="card">
            <h2><i class="fas fa-globe"></i> <?php echo __('general_settings', 'تنظیمات عمومی'); ?></h2>
            <form method="POST">
                <input type="hidden" name="lang" value="<?php echo $langCode; ?>">
                <div class="form-group">
                    <label><?php echo __('site_name', 'نام سایت'); ?></label>
                    <input type="text" name="site_name" value="<?php echo htmlspecialchars($settings['site']['name'] ?? 'نرخ ارز مرجع'); ?>">
                </div>
                <div class="form-group">
                    <label><?php echo __('site_url', 'آدرس سایت'); ?></label>
                    <input type="url" name="site_url" value="<?php echo htmlspecialchars($settings['site']['url'] ?? ''); ?>">
                    <div class="hint"><?php echo __('site_url_hint', 'آدرس کامل سایت با https://'); ?></div>
                </div>
                <div class="form-group">
                    <label><?php echo __('site_description', 'توضیحات'); ?></label>
                    <textarea name="site_description" rows="2"><?php echo htmlspecialchars($settings['site']['description'] ?? ''); ?></textarea>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label><?php echo __('site_keywords', 'کلمات کلیدی'); ?></label>
                        <input type="text" name="site_keywords" value="<?php echo htmlspecialchars($settings['site']['keywords'] ?? ''); ?>">
                        <div class="hint"><?php echo __('keywords_hint', 'با کاما جدا کنید'); ?></div>
                    </div>
                    <div class="form-group">
                        <label><?php echo __('site_author', 'نویسنده'); ?></label>
                        <input type="text" name="site_author" value="<?php echo htmlspecialchars($settings['site']['author'] ?? ''); ?>">
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label><?php echo __('email', 'ایمیل'); ?></label>
                        <input type="email" name="site_email" value="<?php echo htmlspecialchars($settings['site']['email'] ?? ''); ?>">
                    </div>
                    <div class="form-group">
                        <label><?php echo __('default_language', 'زبان پیش‌فرض'); ?></label>
                        <select name="default_language">
                            <?php foreach ($languages as $code => $lang): ?>
                                <?php if ($lang['active'] ?? false): ?>
                                <option value="<?php echo $code; ?>" <?php echo ($settings['site']['default_language'] ?? 'fa') === $code ? 'selected' : ''; ?>>
                                    <?php echo $lang['flag'] . ' ' . $lang['name']; ?>
                                </option>
                                <?php endif; ?>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                <div class="form-group">
                    <label><?php echo __('default_theme', 'تم پیش‌فرض'); ?></label>
                    <select name="default_theme">
                        <option value="light" <?php echo ($settings['site']['default_theme'] ?? 'light') === 'light' ? 'selected' : ''; ?>><?php echo __('theme_light', 'روشن'); ?></option>
                        <option value="dark" <?php echo ($settings['site']['default_theme'] ?? 'light') === 'dark' ? 'selected' : ''; ?>><?php echo __('theme_dark', 'تاریک'); ?></option>
                    </select>
                </div>
                <div class="form-check">
                    <input type="checkbox" name="maintenance" id="maintenance" <?php echo ($settings['site']['maintenance'] ?? false) ? 'checked' : ''; ?>>
                    <label for="maintenance"><?php echo __('maintenance_mode', 'حالت تعمیرات'); ?></label>
                </div>
                <div class="form-group">
                    <label><?php echo __('maintenance_message', 'پیام تعمیرات'); ?></label>
                    <textarea name="maintenance_message" rows="2"><?php echo htmlspecialchars($settings['site']['maintenance_message'] ?? 'سایت در حال بروزرسانی است. لطفاً چند دقیقه دیگر مراجعه کنید.'); ?></textarea>
                </div>
                <button type="submit" name="save_settings" class="btn btn-primary">
                    <i class="fas fa-save"></i> <?php echo __('save', 'ذخیره'); ?>
                </button>
            </form>
        </div>
        
        <?php endif; ?>
        
        <!-- ============================================================ -->
        <!-- ===== ویژگی‌ها ===== -->
        <!-- ============================================================ -->
        <?php if ($activeTab === 'features'): ?>
        
        <div class="card">
            <h2><i class="fas fa-cubes"></i> <?php echo __('features', 'تنظیمات ویژگی‌ها'); ?></h2>
            <form method="POST">
                <div class="form-check">
                    <input type="checkbox" name="pwa" id="pwa" <?php echo ($settings['features']['pwa'] ?? true) ? 'checked' : ''; ?>>
                    <label for="pwa"><?php echo __('pwa_enable', 'فعال‌سازی PWA (قابلیت نصب)'); ?></label>
                </div>
                <div class="form-check">
                    <input type="checkbox" name="dark_mode" id="dark_mode" <?php echo ($settings['features']['dark_mode'] ?? true) ? 'checked' : ''; ?>>
                    <label for="dark_mode"><?php echo __('dark_mode', 'تم تاریک (دارک مود)'); ?></label>
                </div>
                <div class="form-check">
                    <input type="checkbox" name="search" id="search" <?php echo ($settings['features']['search'] ?? true) ? 'checked' : ''; ?>>
                    <label for="search"><?php echo __('search', 'جستجوی زنده'); ?></label>
                </div>
                <div class="form-check">
                    <input type="checkbox" name="favorites" id="favorites" <?php echo ($settings['features']['favorites'] ?? true) ? 'checked' : ''; ?>>
                    <label for="favorites"><?php echo __('favorites', 'علاقه‌مندی‌ها (ستاره)'); ?></label>
                </div>
                <div class="form-check">
                    <input type="checkbox" name="converter" id="converter" <?php echo ($settings['features']['converter'] ?? true) ? 'checked' : ''; ?>>
                    <label for="converter"><?php echo __('converter', 'مبدل ارز'); ?></label>
                </div>
                <div class="form-check">
                    <input type="checkbox" name="chart" id="chart" <?php echo ($settings['features']['chart'] ?? true) ? 'checked' : ''; ?>>
                    <label for="chart"><?php echo __('chart', 'نمودار تغییرات'); ?></label>
                </div>
                <div class="form-check">
                    <input type="checkbox" name="csv_export" id="csv_export" <?php echo ($settings['features']['csv_export'] ?? true) ? 'checked' : ''; ?>>
                    <label for="csv_export"><?php echo __('export', 'خروجی CSV'); ?></label>
                </div>
                <div class="form-check">
                    <input type="checkbox" name="auto_refresh" id="auto_refresh" <?php echo ($settings['features']['auto_refresh'] ?? true) ? 'checked' : ''; ?>>
                    <label for="auto_refresh"><?php echo __('auto_refresh', 'بروزرسانی خودکار'); ?></label>
                </div>
                <div class="form-group">
                    <label><?php echo __('refresh_interval', 'زمان بروزرسانی (ثانیه)'); ?></label>
                    <input type="number" name="refresh_interval" value="<?php echo $settings['features']['refresh_interval'] ?? 300; ?>" min="30" max="3600">
                    <div class="hint"><?php echo __('refresh_hint', 'حداقل ۳۰ ثانیه، حداکثر ۳۶۰۰ ثانیه'); ?></div>
                </div>
                <button type="submit" name="save_features" class="btn btn-primary">
                    <i class="fas fa-save"></i> <?php echo __('save', 'ذخیره'); ?>
                </button>
            </form>
        </div>
        
        <?php endif; ?>
        
        <!-- ============================================================ -->
        <!-- ===== ارزها ===== -->
        <!-- ============================================================ -->
        <?php if ($activeTab === 'currencies'): ?>
        
        <div class="card">
            <h2><i class="fas fa-coins"></i> <?php echo __('currencies', 'تنظیمات ارزها'); ?></h2>
            <form method="POST">
                <div class="form-group">
                    <label><?php echo __('major_currencies', 'ارزهای شاخص (با کاما جدا کنید)'); ?></label>
                    <input type="text" name="major_currencies" value="<?php echo htmlspecialchars(implode(', ', $settings['currencies']['major'] ?? [])); ?>">
                    <div class="hint"><?php echo __('major_hint', 'مثال: USD, EUR, GBP, CHF, CAD'); ?></div>
                </div>
                
                <div class="form-group">
                    <label><?php echo __('current_major', 'ارزهای شاخص فعلی:'); ?></label>
                    <div class="tag-group">
                        <?php foreach ($settings['currencies']['major'] ?? [] as $code): ?>
                        <span class="major-currency-tag">
                            <?php echo htmlspecialchars($code); ?>
                            <a href="?remove_major=1&code=<?php echo urlencode($code); ?>&tab=currencies&lang=<?php echo $langCode; ?>" class="remove" onclick="return confirm('<?php echo __('confirm_delete', 'آیا از حذف این ارز مطمئن هستید؟'); ?>')">
                                <i class="fas fa-times-circle"></i>
                            </a>
                        </span>
                        <?php endforeach; ?>
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label><?php echo __('add_currency', 'افزودن ارز جدید به لیست شاخص'); ?></label>
                        <input type="text" name="new_major_currency" placeholder="مثلاً: RUB">
                    </div>
                    <div class="form-group" style="display:flex;align-items:flex-end;">
                        <button type="submit" name="add_major_currency" class="btn btn-success" style="width:100%;">
                            <i class="fas fa-plus"></i> <?php echo __('add', 'افزودن'); ?>
                        </button>
                    </div>
                </div>
                
                <hr style="margin:16px 0;border-color:var(--border-color);">
                
                <div class="form-check">
                    <input type="checkbox" name="show_all" id="show_all" <?php echo ($settings['currencies']['show_all'] ?? true) ? 'checked' : ''; ?>>
                    <label for="show_all"><?php echo __('show_all_currencies', 'نمایش تمام ارزها در جدول'); ?></label>
                </div>
                <div class="form-group">
                    <label><?php echo __('default_unit', 'واحد پیش‌فرض'); ?></label>
                    <select name="default_unit">
                        <option value="rial" <?php echo ($settings['currencies']['default_unit'] ?? 'rial') === 'rial' ? 'selected' : ''; ?>><?php echo __('unit_rial', 'ریال'); ?></option>
                        <option value="toman" <?php echo ($settings['currencies']['default_unit'] ?? 'rial') === 'toman' ? 'selected' : ''; ?>><?php echo __('unit_toman', 'تومان'); ?></option>
                    </select>
                </div>
                <button type="submit" name="save_currencies" class="btn btn-primary">
                    <i class="fas fa-save"></i> <?php echo __('save', 'ذخیره'); ?>
                </button>
            </form>
        </div>
        
        <?php endif; ?>
        
        <!-- ============================================================ -->
        <!-- ===== زبان‌ها ===== -->
        <!-- ============================================================ -->
        <?php if ($activeTab === 'languages'): ?>
        
        <div class="card">
            <h2><i class="fas fa-language"></i> <?php echo __('languages', 'لیست زبان‌ها'); ?></h2>
            
            <?php foreach ($languages as $code => $lang): ?>
            <div class="lang-item">
                <div class="lang-info">
                    <span class="flag"><?php echo $lang['flag'] ?? '🏳️'; ?></span>
                    <span class="name"><?php echo htmlspecialchars($lang['name']); ?></span>
                    <span class="code"><?php echo $code; ?></span>
                    <span class="dir"><?php echo $lang['dir'] === 'rtl' ? __('rtl', 'راست‌چین') : __('ltr', 'چپ‌چین'); ?></span>
                    <span style="font-size:0.7rem;color:<?php echo $lang['active'] ? 'var(--success)' : 'var(--danger)'; ?>;">
                        <i class="fas fa-circle"></i> <?php echo $lang['active'] ? __('active', 'فعال') : __('inactive', 'غیرفعال'); ?>
                    </span>
                </div>
                <div class="lang-actions">
                    <a href="?toggle_lang=1&code=<?php echo $code; ?>&lang=<?php echo $langCode; ?>" class="btn btn-sm <?php echo $lang['active'] ? 'btn-warning' : 'btn-success'; ?>">
                        <i class="fas fa-<?php echo $lang['active'] ? 'pause' : 'play'; ?>"></i>
                        <?php echo $lang['active'] ? __('inactive', 'غیرفعال') : __('active', 'فعال'); ?>
                    </a>
                    <?php if ($code !== 'fa'): ?>
                    <a href="?delete_lang=1&code=<?php echo $code; ?>&lang=<?php echo $langCode; ?>" class="btn btn-sm btn-danger" onclick="return confirm('<?php echo __('confirm_delete', 'آیا از حذف این زبان مطمئن هستید؟'); ?>')">
                        <i class="fas fa-trash"></i> <?php echo __('delete', 'حذف'); ?>
                    </a>
                    <?php endif; ?>
                    <button class="btn btn-sm btn-primary" onclick="toggleTranslations('<?php echo $code; ?>')">
                        <i class="fas fa-edit"></i> <?php echo __('edit', 'ترجمه‌ها'); ?>
                    </button>
                    <button class="btn btn-sm btn-secondary" onclick="toggleLangInfo('<?php echo $code; ?>')">
                        <i class="fas fa-cog"></i>
                    </button>
                </div>
            </div>
            
            <!-- ===== ویرایش اطلاعات زبان ===== -->
            <div id="lang_info_<?php echo $code; ?>" style="display:none;background:var(--badge-bg);padding:14px 18px;border-radius:10px;margin-bottom:10px;">
                <form method="POST">
                    <input type="hidden" name="lang_code" value="<?php echo $code; ?>">
                    <div class="form-row">
                        <div class="form-group">
                            <label><?php echo __('language_name', 'نام زبان'); ?></label>
                            <input type="text" name="lang_name" value="<?php echo htmlspecialchars($lang['name']); ?>">
                        </div>
                        <div class="form-group">
                            <label><?php echo __('direction', 'جهت نوشتار'); ?></label>
                            <select name="lang_dir">
                                <option value="rtl" <?php echo $lang['dir'] === 'rtl' ? 'selected' : ''; ?>><?php echo __('rtl', 'راست‌چین (RTL)'); ?></option>
                                <option value="ltr" <?php echo $lang['dir'] === 'ltr' ? 'selected' : ''; ?>><?php echo __('ltr', 'چپ‌چین (LTR)'); ?></option>
                            </select>
                        </div>
                    </div>
                    <div class="form-group">
                        <label><?php echo __('flag', 'پرچم (اموجی)'); ?></label>
                        <input type="text" name="lang_flag" value="<?php echo htmlspecialchars($lang['flag'] ?? '🏳️'); ?>">
                    </div>
                    <button type="submit" name="edit_language_info" class="btn btn-sm btn-success">
                        <i class="fas fa-save"></i> <?php echo __('save', 'ذخیره اطلاعات'); ?>
                    </button>
                </form>
            </div>
            
            <!-- ===== ویرایش ترجمه‌ها ===== -->
            <div id="translations_<?php echo $code; ?>" style="display:none;background:var(--badge-bg);padding:14px 18px;border-radius:10px;margin-bottom:10px;">
                <form method="POST">
                    <input type="hidden" name="lang_code" value="<?php echo $code; ?>">
                    <h4 style="font-size:0.85rem;margin-bottom:10px;color:var(--text-primary);">
                        <i class="fas fa-edit"></i> <?php echo __('translations_of', 'ترجمه‌های'); ?> <?php echo htmlspecialchars($lang['name']); ?>
                    </h4>
                    <div class="translations-grid">
                        <?php foreach ($lang['translations'] as $key => $value): ?>
                        <div class="form-group">
                            <label><?php echo $key; ?></label>
                            <input type="text" name="trans_<?php echo $key; ?>" value="<?php echo htmlspecialchars($value); ?>">
                        </div>
                        <?php endforeach; ?>
                    </div>
                    <button type="submit" name="save_translations" class="btn btn-sm btn-success" style="margin-top:10px;">
                        <i class="fas fa-save"></i> <?php echo __('save', 'ذخیره ترجمه‌ها'); ?>
                    </button>
                </form>
            </div>
            <?php endforeach; ?>
        </div>
        
        <div class="card">
            <h2><i class="fas fa-plus-circle"></i> <?php echo __('add_language', 'افزودن زبان جدید'); ?></h2>
            <form method="POST">
                <div class="form-row">
                    <div class="form-group">
                        <label><?php echo __('language_code', 'کد زبان (مثلاً: de)'); ?></label>
                        <input type="text" name="lang_code" placeholder="کد دو حرفی" required>
                        <div class="hint"><?php echo __('language_code_hint', 'از کدهای استاندارد ISO استفاده کنید'); ?></div>
                    </div>
                    <div class="form-group">
                        <label><?php echo __('language_name', 'نام زبان'); ?></label>
                        <input type="text" name="lang_name" placeholder="مثلاً: Deutsch" required>
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label><?php echo __('direction', 'جهت نوشتار'); ?></label>
                        <select name="lang_dir">
                            <option value="rtl"><?php echo __('rtl', 'راست‌چین (RTL)'); ?></option>
                            <option value="ltr"><?php echo __('ltr', 'چپ‌چین (LTR)'); ?></option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label><?php echo __('flag', 'پرچم (اموجی)'); ?></label>
                        <input type="text" name="lang_flag" placeholder="🇩🇪" value="🏳️">
                    </div>
                </div>
                <button type="submit" name="add_language" class="btn btn-success">
                    <i class="fas fa-plus"></i> <?php echo __('add', 'افزودن زبان'); ?>
                </button>
            </form>
        </div>
        
        <script>
            function toggleTranslations(code) {
                const el = document.getElementById('translations_' + code);
                if (el) {
                    el.style.display = el.style.display === 'none' ? 'block' : 'none';
                    const info = document.getElementById('lang_info_' + code);
                    if (info) info.style.display = 'none';
                }
            }
            function toggleLangInfo(code) {
                const el = document.getElementById('lang_info_' + code);
                if (el) {
                    el.style.display = el.style.display === 'none' ? 'block' : 'none';
                    const trans = document.getElementById('translations_' + code);
                    if (trans) trans.style.display = 'none';
                }
            }
        </script>
        
        <?php endif; ?>
        
        <!-- ============================================================ -->
        <!-- ===== SEO ===== -->
        <!-- ============================================================ -->
        <?php if ($activeTab === 'seo'): ?>
        
        <div class="card">
            <h2><i class="fas fa-search"></i> <?php echo __('seo', 'تنظیمات SEO'); ?></h2>
            <form method="POST">
                <div class="form-group">
                    <label><?php echo __('og_title', 'عنوان OG (Open Graph)'); ?></label>
                    <input type="text" name="og_title" value="<?php echo htmlspecialchars($settings['seo']['og_title'] ?? ''); ?>">
                    <div class="hint"><?php echo __('og_title_hint', 'عنوانی که در شبکه‌های اجتماعی نمایش داده می‌شود'); ?></div>
                </div>
                <div class="form-group">
                    <label><?php echo __('og_description', 'توضیحات OG'); ?></label>
                    <textarea name="og_description" rows="2"><?php echo htmlspecialchars($settings['seo']['og_description'] ?? ''); ?></textarea>
                </div>
                <div class="form-group">
                    <label><?php echo __('og_image', 'تصویر OG'); ?></label>
                    <input type="url" name="og_image" value="<?php echo htmlspecialchars($settings['seo']['og_image'] ?? ''); ?>">
                    <div class="hint"><?php echo __('og_image_hint', 'آدرس کامل تصویر برای اشتراک‌گذاری'); ?></div>
                </div>
                <div class="form-group">
                    <label><?php echo __('twitter_card', 'Twitter Card'); ?></label>
                    <select name="twitter_card">
                        <option value="summary" <?php echo ($settings['seo']['twitter_card'] ?? '') === 'summary' ? 'selected' : ''; ?>>summary</option>
                        <option value="summary_large_image" <?php echo ($settings['seo']['twitter_card'] ?? '') === 'summary_large_image' ? 'selected' : ''; ?>>summary_large_image</option>
                        <option value="app" <?php echo ($settings['seo']['twitter_card'] ?? '') === 'app' ? 'selected' : ''; ?>>app</option>
                        <option value="player" <?php echo ($settings['seo']['twitter_card'] ?? '') === 'player' ? 'selected' : ''; ?>>player</option>
                    </select>
                </div>
                <div class="form-group">
                    <label><?php echo __('robots', 'Robots'); ?></label>
                    <select name="robots">
                        <option value="index, follow" <?php echo ($settings['seo']['robots'] ?? '') === 'index, follow' ? 'selected' : ''; ?>>index, follow</option>
                        <option value="index, nofollow" <?php echo ($settings['seo']['robots'] ?? '') === 'index, nofollow' ? 'selected' : ''; ?>>index, nofollow</option>
                        <option value="noindex, follow" <?php echo ($settings['seo']['robots'] ?? '') === 'noindex, follow' ? 'selected' : ''; ?>>noindex, follow</option>
                        <option value="noindex, nofollow" <?php echo ($settings['seo']['robots'] ?? '') === 'noindex, nofollow' ? 'selected' : ''; ?>>noindex, nofollow</option>
                    </select>
                </div>
                <button type="submit" name="save_seo" class="btn btn-primary">
                    <i class="fas fa-save"></i> <?php echo __('save', 'ذخیره'); ?>
                </button>
            </form>
        </div>
        
        <?php endif; ?>
        
        <!-- ============================================================ -->
        <!-- ===== شبکه‌های اجتماعی ===== -->
        <!-- ============================================================ -->
        <?php if ($activeTab === 'social'): ?>
        
        <div class="card">
            <h2><i class="fas fa-share-alt"></i> <?php echo __('social_networks', 'شبکه‌های اجتماعی'); ?></h2>
            <form method="POST">
                <div class="form-group">
                    <label><i class="fab fa-github"></i> <?php echo __('github', 'گیت‌هاب'); ?></label>
                    <input type="url" name="social_github" value="<?php echo htmlspecialchars($settings['social']['github'] ?? ''); ?>" placeholder="https://github.com/username">
                </div>
                <div class="form-group">
                    <label><i class="fab fa-twitter"></i> <?php echo __('twitter', 'توییتر'); ?></label>
                    <input type="url" name="social_twitter" value="<?php echo htmlspecialchars($settings['social']['twitter'] ?? ''); ?>" placeholder="https://twitter.com/username">
                </div>
                <div class="form-group">
                    <label><i class="fab fa-telegram"></i> <?php echo __('telegram', 'تلگرام'); ?></label>
                    <input type="url" name="social_telegram" value="<?php echo htmlspecialchars($settings['social']['telegram'] ?? ''); ?>" placeholder="https://t.me/username">
                </div>
                <div class="form-group">
                    <label><i class="fab fa-instagram"></i> <?php echo __('instagram', 'اینستاگرام'); ?></label>
                    <input type="url" name="social_instagram" value="<?php echo htmlspecialchars($settings['social']['instagram'] ?? ''); ?>" placeholder="https://instagram.com/username">
                </div>
                <div class="form-group">
                    <label><i class="fab fa-youtube"></i> <?php echo __('youtube', 'یوتیوب'); ?></label>
                    <input type="url" name="social_youtube" value="<?php echo htmlspecialchars($settings['social']['youtube'] ?? ''); ?>" placeholder="https://youtube.com/@channel">
                </div>
                <div class="form-group">
                    <label><i class="fab fa-linkedin"></i> <?php echo __('linkedin', 'لینکدین'); ?></label>
                    <input type="url" name="social_linkedin" value="<?php echo htmlspecialchars($settings['social']['linkedin'] ?? ''); ?>" placeholder="https://linkedin.com/in/username">
                </div>
                <div class="form-group">
                    <label><i class="fab fa-whatsapp"></i> <?php echo __('whatsapp', 'واتساپ'); ?></label>
                    <input type="url" name="social_whatsapp" value="<?php echo htmlspecialchars($settings['social']['whatsapp'] ?? ''); ?>" placeholder="https://wa.me/phone">
                </div>
                <button type="submit" name="save_social" class="btn btn-primary">
                    <i class="fas fa-save"></i> <?php echo __('save', 'ذخیره'); ?>
                </button>
            </form>
        </div>
        
        <?php endif; ?>
        
        <!-- ============================================================ -->
        <!-- ===== PWA ===== -->
        <!-- ============================================================ -->
        <?php if ($activeTab === 'pwa'): ?>
        
        <div class="card">
            <h2><i class="fas fa-mobile-alt"></i> <?php echo __('pwa_settings', 'تنظیمات PWA'); ?></h2>
            <div class="alert-warning">
                <i class="fas fa-info-circle"></i>
                <?php echo __('pwa_info', 'PWA (Progressive Web App) به کاربران اجازه می‌دهد سایت را روی گوشی یا کامپیوتر نصب کنند.'); ?>
            </div>
            <form method="POST">
                <div class="form-check">
                    <input type="checkbox" name="pwa_enabled" id="pwa_enabled" <?php echo ($settings['pwa']['enabled'] ?? true) ? 'checked' : ''; ?>>
                    <label for="pwa_enabled"><?php echo __('pwa_enable', 'فعال‌سازی PWA'); ?></label>
                </div>
                <div class="form-group">
                    <label><?php echo __('pwa_name', 'نام کامل PWA'); ?></label>
                    <input type="text" name="pwa_name" value="<?php echo htmlspecialchars($settings['pwa']['name'] ?? $settings['site']['name'] ?? 'نرخ ارز مرجع'); ?>">
                </div>
                <div class="form-group">
                    <label><?php echo __('pwa_short_name', 'نام کوتاه PWA'); ?></label>
                    <input type="text" name="pwa_short_name" value="<?php echo htmlspecialchars($settings['pwa']['short_name'] ?? 'نرخ ارز'); ?>">
                    <div class="hint"><?php echo __('pwa_short_hint', 'حداکثر ۱۲ کاراکتر'); ?></div>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label><?php echo __('theme_color', 'رنگ تم'); ?></label>
                        <input type="color" name="pwa_theme_color" value="<?php echo htmlspecialchars($settings['pwa']['theme_color'] ?? '#0a2540'); ?>">
                    </div>
                    <div class="form-group">
                        <label><?php echo __('background_color', 'رنگ پس‌زمینه'); ?></label>
                        <input type="color" name="pwa_background_color" value="<?php echo htmlspecialchars($settings['pwa']['background_color'] ?? '#0a2540'); ?>">
                    </div>
                </div>
                <div style="display:flex;gap:10px;flex-wrap:wrap;">
                    <button type="submit" name="save_pwa" class="btn btn-primary">
                        <i class="fas fa-save"></i> <?php echo __('save', 'ذخیره تنظیمات'); ?>
                    </button>
                    <button type="submit" name="update_manifest" class="btn btn-warning">
                        <i class="fas fa-file-code"></i> <?php echo __('update_manifest', 'به‌روزرسانی manifest.json'); ?>
                    </button>
                </div>
            </form>
        </div>
        
        <div class="card">
            <h2><i class="fas fa-info-circle"></i> <?php echo __('pwa_guide', 'راهنمای PWA'); ?></h2>
            <div style="font-size:0.85rem;color:var(--text-secondary);line-height:1.8;">
                <p><strong><?php echo __('pwa_icons', '1. آیکون‌ها:'); ?></strong> <?php echo __('pwa_icons_text', 'آیکون‌های PWA باید در پوشه'); ?> <code>icons/</code> <?php echo __('pwa_icons_text2', 'قرار داشته باشند.'); ?></p>
                <p><strong><?php echo __('pwa_sizes', '2. سایزهای مورد نیاز:'); ?></strong> 72x72, 96x96, 128x128, 144x144, 152x152, 192x192, 384x384, 512x512</p>
                <p><strong><?php echo __('pwa_sw', '3. Service Worker:'); ?></strong> <?php echo __('pwa_sw_text', 'فایل'); ?> <code>sw.js</code> <?php echo __('pwa_sw_text2', 'باید در روت سایت باشد.'); ?></p>
                <p><strong><?php echo __('pwa_test', '4. تست:'); ?></strong> <?php echo __('pwa_test_text', 'از Lighthouse در Chrome برای تست PWA استفاده کنید.'); ?></p>
            </div>
        </div>
        
        <?php endif; ?>
        
        <!-- ============================================================ -->
        <!-- ===== کش ===== -->
        <!-- ============================================================ -->
        <?php if ($activeTab === 'cache'): ?>
        
        <div class="card">
            <h2><i class="fas fa-broom"></i> <?php echo __('cache', 'مدیریت کش'); ?></h2>
            <div class="info-grid" style="margin-bottom:16px;">
                <div class="info-item">
                    <div class="label"><?php echo __('cache_files', 'تعداد فایل‌های کش'); ?></div>
                    <div class="value"><?php echo $cacheCount; ?></div>
                </div>
                <div class="info-item">
                    <div class="label"><?php echo __('cache_size', 'حجم کل کش'); ?></div>
                    <div class="value"><?php echo round($cacheSize / 1024, 2); ?> KB</div>
                </div>
                <div class="info-item">
                    <div class="label"><?php echo __('cache_status', 'وضعیت کش'); ?></div>
                    <div class="value">
                        <?php if ($settings['cache']['enabled'] ?? true): ?>
                        <span style="color:var(--success);">✅ <?php echo __('active', 'فعال'); ?></span>
                        <?php else: ?>
                        <span style="color:var(--danger);">❌ <?php echo __('inactive', 'غیرفعال'); ?></span>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            
            <form method="POST">
                <div class="form-check">
                    <input type="checkbox" name="cache_enabled" id="cache_enabled" <?php echo ($settings['cache']['enabled'] ?? true) ? 'checked' : ''; ?>>
                    <label for="cache_enabled"><?php echo __('cache_enable', 'فعال‌سازی کش'); ?></label>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label><?php echo __('cache_time', 'زمان کش (ثانیه)'); ?></label>
                        <input type="number" name="cache_time" value="<?php echo $settings['cache']['time'] ?? 300; ?>" min="60" max="3600">
                        <div class="hint"><?php echo __('cache_time_hint', 'حداقل ۶۰ ثانیه، حداکثر ۳۶۰۰ ثانیه'); ?></div>
                    </div>
                    <div class="form-group" style="display:flex;align-items:flex-end;">
                        <div class="form-check">
                            <input type="checkbox" name="cache_clear_on_update" id="cache_clear_on_update" <?php echo ($settings['cache']['clear_on_update'] ?? true) ? 'checked' : ''; ?>>
                            <label for="cache_clear_on_update"><?php echo __('cache_clear_on_update', 'پاکسازی کش هنگام بروزرسانی'); ?></label>
                        </div>
                    </div>
                </div>
                <button type="submit" name="save_cache" class="btn btn-primary">
                    <i class="fas fa-save"></i> <?php echo __('save', 'ذخیره تنظیمات'); ?>
                </button>
                <a href="?clear_cache=1&tab=<?php echo $activeTab; ?>&lang=<?php echo $langCode; ?>" class="btn btn-danger" onclick="return confirm('<?php echo __('confirm_delete', 'آیا از پاکسازی کش مطمئن هستید؟'); ?>')">
                    <i class="fas fa-broom"></i> <?php echo __('clear_cache', 'پاکسازی کش'); ?>
                </a>
            </form>
        </div>
        
        <?php endif; ?>
        
        <!-- ============================================================ -->
        <!-- ===== بکاپ ===== -->
        <!-- ============================================================ -->
        <?php if ($activeTab === 'backup'): ?>
        
        <div class="card">
            <h2><i class="fas fa-database"></i> <?php echo __('backup', 'مدیریت بکاپ'); ?></h2>
            
            <div style="display:flex;gap:10px;flex-wrap:wrap;margin-bottom:16px;">
                <a href="?backup=1&tab=<?php echo $activeTab; ?>&lang=<?php echo $langCode; ?>" class="btn btn-success">
                    <i class="fas fa-download"></i> <?php echo __('create_backup', 'ایجاد بکاپ جدید'); ?>
                </a>
            </div>
            
            <h4 style="font-size:0.9rem;color:var(--text-primary);margin-bottom:10px;">
                <i class="fas fa-list"></i> <?php echo __('backup_list', 'لیست بکاپ‌ها'); ?>
            </h4>
            
            <?php if (empty($backupFiles)): ?>
            <p style="color:var(--text-muted);text-align:center;padding:20px;">
                <i class="fas fa-database" style="font-size:2rem;display:block;margin-bottom:10px;"></i>
                <?php echo __('no_backup', 'هیچ فایل بکاپی یافت نشد!'); ?>
            </p>
            <?php else: ?>
                <?php foreach ($backupFiles as $file): 
                    $fileName = basename($file);
                    $fileSize = filesize($file);
                    $fileDate = date('Y/m/d H:i:s', filemtime($file));
                    $fileType = strpos($fileName, 'settings_') === 0 ? __('settings', 'تنظیمات') : 
                                (strpos($fileName, 'languages_') === 0 ? __('languages', 'زبان‌ها') : __('users', 'کاربران'));
                ?>
                <div class="backup-item">
                    <div class="info">
                        <span class="date"><?php echo $fileDate; ?></span>
                        <span style="margin:0 8px;">|</span>
                        <span><?php echo $fileType; ?></span>
                        <span style="margin:0 8px;">|</span>
                        <span class="size"><?php echo round($fileSize / 1024, 2); ?> KB</span>
                        <span style="font-size:0.6rem;color:var(--text-muted);margin-right:8px;"><?php echo $fileName; ?></span>
                    </div>
                    <div class="actions">
                        <a href="?restore=1&file=<?php echo urlencode($fileName); ?>&tab=<?php echo $activeTab; ?>&lang=<?php echo $langCode; ?>" class="btn btn-sm btn-warning" onclick="return confirm('<?php echo __('confirm_restore', 'آیا از بازیابی این بکاپ مطمئن هستید؟'); ?>')">
                            <i class="fas fa-undo"></i> <?php echo __('restore', 'بازیابی'); ?>
                        </a>
                        <a href="?delete_backup=1&file=<?php echo urlencode($fileName); ?>&tab=<?php echo $activeTab; ?>&lang=<?php echo $langCode; ?>" class="btn btn-sm btn-danger" onclick="return confirm('<?php echo __('confirm_delete', 'آیا از حذف این بکاپ مطمئن هستید؟'); ?>')">
                            <i class="fas fa-trash"></i> <?php echo __('delete', 'حذف'); ?>
                        </a>
                    </div>
                </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
        
        <div class="card">
            <h2><i class="fas fa-info-circle"></i> <?php echo __('backup_info', 'اطلاعات بکاپ'); ?></h2>
            <div style="font-size:0.85rem;color:var(--text-secondary);line-height:1.8;">
                <p>📁 <strong><?php echo __('backup_folder', 'پوشه بکاپ:'); ?></strong> <code>backup/</code></p>
                <p>📄 <strong><?php echo __('backup_files', 'فایل‌های بکاپ:'); ?></strong> settings_*.json, languages_*.json, users_*.json</p>
                <p>🔄 <strong><?php echo __('restore_info', 'بازیابی:'); ?></strong> <?php echo __('restore_info_text', 'با کلیک روی دکمه "بازیابی"، فایل بکاپ جایگزین فایل فعلی می‌شود.'); ?></p>
                <p>⚠️ <strong><?php echo __('backup_warning', 'هشدار:'); ?></strong> <?php echo __('backup_warning_text', 'قبل از بازیابی، از فایل‌های فعلی بکاپ بگیرید.'); ?></p>
            </div>
        </div>
        
        <?php endif; ?>
        
        <!-- ============================================================ -->
        <!-- ===== امنیت ===== -->
        <!-- ============================================================ -->
        <?php if ($activeTab === 'security'): ?>
        
        <div class="card">
            <h2><i class="fas fa-key"></i> <?php echo __('change_password', 'تغییر رمز عبور ادمین'); ?></h2>
            <form method="POST">
                <div class="form-group">
                    <label><?php echo __('current_password', 'رمز عبور فعلی'); ?></label>
                    <div class="password-wrapper">
                        <input type="password" name="current_password" id="current_password" placeholder="<?php echo __('current_password_placeholder', 'رمز عبور فعلی را وارد کنید'); ?>" required>
                        <button type="button" class="toggle-password" onclick="togglePassword('current_password')">
                            <i class="fas fa-eye"></i>
                        </button>
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label><?php echo __('new_password', 'رمز عبور جدید'); ?></label>
                        <div class="password-wrapper">
                            <input type="password" name="new_password" id="new_password" placeholder="<?php echo __('new_password_placeholder', 'حداقل ۶ کاراکتر'); ?>" required>
                            <button type="button" class="toggle-password" onclick="togglePassword('new_password')">
                                <i class="fas fa-eye"></i>
                            </button>
                        </div>
                    </div>
                    <div class="form-group">
                        <label><?php echo __('confirm_password', 'تکرار رمز جدید'); ?></label>
                        <div class="password-wrapper">
                            <input type="password" name="confirm_password" id="confirm_password" placeholder="<?php echo __('confirm_password_placeholder', 'تکرار رمز جدید'); ?>" required>
                            <button type="button" class="toggle-password" onclick="togglePassword('confirm_password')">
                                <i class="fas fa-eye"></i>
                            </button>
                        </div>
                    </div>
                </div>
                <button type="submit" name="change_password" class="btn btn-warning">
                    <i class="fas fa-key"></i> <?php echo __('change_password', 'تغییر رمز'); ?>
                </button>
            </form>
        </div>
        
        <div class="card">
            <h2><i class="fas fa-user-shield"></i> <?php echo __('admin_info', 'اطلاعات ادمین'); ?></h2>
            <form method="POST">
                <div class="form-row">
                    <div class="form-group">
                        <label><?php echo __('username', 'نام کاربری'); ?></label>
                        <input type="text" name="admin_username" value="<?php echo htmlspecialchars($settings['admin']['username'] ?? 'admin'); ?>">
                    </div>
                    <div class="form-group">
                        <label><?php echo __('email', 'ایمیل ادمین'); ?></label>
                        <input type="email" name="admin_email" value="<?php echo htmlspecialchars($settings['admin']['email'] ?? 'admin@localhost'); ?>">
                    </div>
                </div>
                <button type="submit" name="save_admin" class="btn btn-primary">
                    <i class="fas fa-save"></i> <?php echo __('save', 'ذخیره اطلاعات'); ?>
                </button>
            </form>
            <div style="margin-top:16px;font-size:0.8rem;color:var(--text-muted);">
                <p><i class="fas fa-clock"></i> <?php echo __('last_login', 'آخرین ورود:'); ?> <?php echo $settings['admin']['last_login'] ?? __('never', 'هنوز وارد نشده'); ?></p>
            </div>
        </div>
        
        <script>
            function togglePassword(id) {
                const input = document.getElementById(id);
                const icon = input.parentElement.querySelector('.toggle-password i');
                if (input.type === 'password') {
                    input.type = 'text';
                    icon.className = 'fas fa-eye-slash';
                } else {
                    input.type = 'password';
                    icon.className = 'fas fa-eye';
                }
            }
        </script>
        
        <?php endif; ?>
        
        <!-- ============================================================ -->
        <!-- ===== نوتیفیکیشن ===== -->
        <!-- ============================================================ -->
        <?php if ($activeTab === 'notifications'): ?>
        
        <div class="card">
            <h2><i class="fas fa-bell"></i> <?php echo __('notifications', 'تنظیمات نوتیفیکیشن'); ?></h2>
            <form method="POST">
                <div class="form-check">
                    <input type="checkbox" name="notif_enabled" id="notif_enabled" <?php echo ($settings['notifications']['enabled'] ?? true) ? 'checked' : ''; ?>>
                    <label for="notif_enabled"><?php echo __('notif_enable', 'فعال‌سازی نوتیفیکیشن‌ها'); ?></label>
                </div>
                <div class="form-check">
                    <input type="checkbox" name="notif_sound" id="notif_sound" <?php echo ($settings['notifications']['sound'] ?? true) ? 'checked' : ''; ?>>
                    <label for="notif_sound"><?php echo __('notif_sound', 'پخش صدا هنگام نوتیفیکیشن'); ?></label>
                </div>
                <div class="form-check">
                    <input type="checkbox" name="notif_browser" id="notif_browser" <?php echo ($settings['notifications']['browser'] ?? true) ? 'checked' : ''; ?>>
                    <label for="notif_browser"><?php echo __('notif_browser', 'نوتیفیکیشن مرورگر'); ?></label>
                </div>
                <div class="form-check">
                    <input type="checkbox" name="notif_email" id="notif_email" <?php echo ($settings['notifications']['email_alerts'] ?? false) ? 'checked' : ''; ?>>
                    <label for="notif_email"><?php echo __('notif_email', 'هشدار از طریق ایمیل'); ?></label>
                </div>
                <button type="submit" name="save_notifications" class="btn btn-primary">
                    <i class="fas fa-save"></i> <?php echo __('save', 'ذخیره تنظیمات'); ?>
                </button>
            </form>
        </div>
        
        <?php endif; ?>
        
        <!-- ===== فوتر ===== -->
        <div style="text-align:center;padding:20px 0;font-size:0.7rem;color:var(--text-muted);border-top:1px solid var(--border-color);margin-top:10px;">
            <i class="fas fa-copyright"></i> <?php echo date('Y'); ?> 
            <a href="https://mozili.ir/arz/" style="color:var(--text-primary);text-decoration:none;"><?php echo __('site_title', 'نرخ ارز مرجع'); ?></a>
            &bull; <?php echo __('version', 'نسخه'); ?> 3.0
        </div>
        
    </main>
    
    <!-- ============================================
               SCRIPTS
            ============================================ -->
    <script>
        // ===== سایدبار =====
        function toggleSidebar() {
            document.getElementById('sidebar').classList.toggle('open');
        }
        
        function closeSidebar() {
            document.getElementById('sidebar').classList.remove('open');
        }
        
        // ===== بستن با کلیک خارج =====
        document.addEventListener('click', function(e) {
            const sidebar = document.getElementById('sidebar');
            const hamburger = document.querySelector('.hamburger');
            if (window.innerWidth <= 992) {
                if (!sidebar.contains(e.target) && !hamburger?.contains(e.target)) {
                    closeSidebar();
                }
            }
        });
        
        // ===== کیبورد شورت‌کات =====
        document.addEventListener('keydown', function(e) {
            if (e.ctrlKey && e.shiftKey && (e.key === 's' || e.key === 'S')) {
                e.preventDefault();
                toggleSidebar();
            }
            if (e.key === 'Escape') {
                closeSidebar();
            }
        });
        
        // ===== خودکار بستن پیام‌ها =====
        setTimeout(() => {
            document.querySelectorAll('.message').forEach(el => {
                el.style.transition = 'opacity 0.5s ease';
                el.style.opacity = '0';
                setTimeout(() => el.remove(), 500);
            });
        }, 5000);
        
        console.log('🔑 <?php echo __('admin_panel', 'پنل مدیریت'); ?>');
        console.log('👤 <?php echo __('username', 'نام کاربری'); ?>: <?php echo htmlspecialchars($currentUser['username'] ?? 'admin'); ?>');
        console.log('🌐 <?php echo __('language', 'زبان'); ?>: <?php echo $langCode; ?>');
        console.log('🎨 <?php echo __('theme', 'تم'); ?>: <?php echo $theme; ?>');
        console.log('📱 Ctrl+Shift+S برای سایدبار');
    </script>
    
</body>
</html>
