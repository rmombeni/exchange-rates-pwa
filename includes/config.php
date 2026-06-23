<?php
// ===== نمایش خطاها =====
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// ===== تعریف مسیرها =====
define('ROOT_PATH', realpath(__DIR__ . '/..'));
define('CONFIG_PATH', ROOT_PATH . '/config');
define('CACHE_PATH', ROOT_PATH . '/cache');
define('HISTORY_PATH', ROOT_PATH . '/history');
define('SITE_URL', 'https://mozili.ir/arz/');
define('CACHE_TIME', 300);

// ===== اطمینان از وجود پوشه‌ها =====
$dirs = [CONFIG_PATH, CACHE_PATH, HISTORY_PATH];
foreach ($dirs as $dir) {
    if (!is_dir($dir)) {
        mkdir($dir, 0777, true);
    }
}

// ===== بارگذاری تنظیمات =====
function loadSettings() {
    $settingsFile = CONFIG_PATH . '/settings.json';
    if (file_exists($settingsFile)) {
        $content = file_get_contents($settingsFile);
        return json_decode($content, true);
    }
    return null;
}

function loadLanguages() {
    $langFile = CONFIG_PATH . '/languages.json';
    if (file_exists($langFile)) {
        $content = file_get_contents($langFile);
        return json_decode($content, true);
    }
    return null;
}

function saveSettings($data) {
    $settingsFile = CONFIG_PATH . '/settings.json';
    return file_put_contents($settingsFile, json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
}

function saveLanguages($data) {
    $langFile = CONFIG_PATH . '/languages.json';
    return file_put_contents($langFile, json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
}

// ===== بارگذاری =====
$settings = loadSettings();
$languages = loadLanguages();

// ===== تنظیمات پیش‌فرض =====
if (!$settings) {
    $settings = [
        'site' => [
            'name' => 'نرخ ارز مرجع',
            'url' => SITE_URL,
            'description' => 'نمایش لحظه‌ای نرخ ارزهای رسمی بانک مرکزی ایران',
            'keywords' => 'نرخ ارز, قیمت دلار, قیمت یورو',
            'author' => 'R.Mombeni',
            'email' => 'admin@mozili.ir',
            'default_language' => 'fa',
            'default_theme' => 'light',
            'maintenance' => false,
            'maintenance_message' => 'سایت در حال بروزرسانی است'
        ],
        'admin' => [
            'username' => 'admin',
            'password' => password_hash('Admin@2026', PASSWORD_DEFAULT),
            'email' => 'admin@mozili.ir',
            'last_login' => null
        ],
        'features' => [
            'pwa' => true,
            'dark_mode' => true,
            'search' => true,
            'favorites' => true,
            'converter' => true,
            'chart' => true,
            'csv_export' => true,
            'auto_refresh' => true,
            'refresh_interval' => 300
        ],
        'currencies' => [
            'major' => ['USD', 'EUR', 'GBP', 'CHF', 'CAD', 'AED', 'TRY'],
            'show_all' => true,
            'default_unit' => 'rial'
        ],
        'cache' => [
            'enabled' => true,
            'time' => 300,
            'clear_on_update' => true
        ],
        'seo' => [
            'og_title' => 'نرخ ارز مرجع - mozili.ir',
            'og_description' => 'نمایش لحظه‌ای نرخ ارزهای رسمی بانک مرکزی ایران',
            'og_image' => SITE_URL . 'icons/icon-512.png',
            'twitter_card' => 'summary_large_image',
            'robots' => 'index, follow'
        ],
        'social' => [
            'github' => 'https://github.com/rmombeni',
            'twitter' => '',
            'telegram' => '',
            'instagram' => ''
        ],
        'pwa' => [
            'enabled' => true,
            'name' => 'نرخ ارز مرجع',
            'short_name' => 'نرخ ارز',
            'theme_color' => '#0a2540',
            'background_color' => '#0a2540'
        ],
        'notifications' => [
            'enabled' => true,
            'sound' => true,
            'browser' => true,
            'email_alerts' => false
        ]
    ];
    saveSettings($settings);
}

// ===== زبان‌های پیش‌فرض =====
if (!$languages) {
    $languages = [
        'fa' => [
            'name' => 'فارسی',
            'dir' => 'rtl',
            'flag' => '🇮🇷',
            'active' => true,
            'translations' => [
                'site_title' => 'نرخ ارز مرجع',
                'site_description' => 'نمایش لحظه‌ای نرخ ارزهای رسمی بانک مرکزی ایران',
                'search_placeholder' => 'جستجو...',
                'featured_currencies' => 'ارزهای شاخص',
                'all_currencies' => 'لیست کامل نرخ ارزها',
                'currency' => 'ارز',
                'code' => 'کد',
                'currency_name' => 'نام ارز',
                'rate' => 'نرخ',
                'date' => 'تاریخ',
                'chart' => 'نمودار',
                'favorites' => 'علاقه‌مندی‌ها',
                'converter' => 'مبدل ارز',
                'export' => 'خروجی',
                'theme_light' => 'روشن',
                'theme_dark' => 'تاریک',
                'unit_rial' => 'ریال',
                'unit_toman' => 'تومان',
                'no_data' => 'هیچ داده‌ای موجود نیست',
                'error_connection' => 'خطا در اتصال به بانک مرکزی',
                'update_time' => 'آخرین بروزرسانی',
                'source' => 'منبع',
                'copyright' => 'کلیه حقوق محفوظ است',
                'login' => 'ورود',
                'register' => 'ثبت نام',
                'logout' => 'خروج',
                'username' => 'نام کاربری',
                'password' => 'رمز عبور',
                'email' => 'ایمیل',
                'fullname' => 'نام کامل',
                'confirm_password' => 'تکرار رمز عبور',
                'no_account' => 'حساب کاربری ندارید؟',
                'have_account' => 'قبلاً ثبت نام کرده‌اید؟',
                'admin_panel' => 'پنل مدیریت',
                'favorite_currency' => 'ارز دلخواه',
                'add_to_favorites' => 'افزودن به علاقه‌مندی‌ها',
                'added_to_favorites' => 'به علاقه‌مندی‌ها اضافه شد',
                'removed_from_favorites' => 'از علاقه‌مندی‌ها حذف شد',
                'login_required' => 'برای این کار ابتدا وارد شوید',
                'no_favorites' => 'هنوز ارزی به علاقه‌مندی‌ها اضافه نشده',
                'total_currencies' => 'کل ارزها',
                'highest_rate' => 'بالاترین نرخ',
                'lowest_rate' => 'پایین‌ترین نرخ',
                'average_rate' => 'میانگین',
                'row' => 'ردیف'
            ]
        ],
        'en' => [
            'name' => 'English',
            'dir' => 'ltr',
            'flag' => '🇬🇧',
            'active' => true,
            'translations' => [
                'site_title' => 'Exchange Rates',
                'site_description' => 'Live exchange rates from Central Bank of Iran',
                'search_placeholder' => 'Search...',
                'featured_currencies' => 'Featured Currencies',
                'all_currencies' => 'All Currencies',
                'currency' => 'Currency',
                'code' => 'Code',
                'currency_name' => 'Currency Name',
                'rate' => 'Rate',
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
                'copyright' => 'All rights reserved',
                'login' => 'Login',
                'register' => 'Register',
                'logout' => 'Logout',
                'username' => 'Username',
                'password' => 'Password',
                'email' => 'Email',
                'fullname' => 'Full Name',
                'confirm_password' => 'Confirm Password',
                'no_account' => 'Don\'t have an account?',
                'have_account' => 'Already have an account?',
                'admin_panel' => 'Admin Panel',
                'favorite_currency' => 'Favorite Currency',
                'add_to_favorites' => 'Add to favorites',
                'added_to_favorites' => 'Added to favorites',
                'removed_from_favorites' => 'Removed from favorites',
                'login_required' => 'Please login first',
                'no_favorites' => 'No favorites yet',
                'total_currencies' => 'Total Currencies',
                'highest_rate' => 'Highest Rate',
                'lowest_rate' => 'Lowest Rate',
                'average_rate' => 'Average Rate',
                'row' => 'Row'
            ]
        ]
    ];
    saveLanguages($languages);
}

// ===== تابع ترجمه =====
function __($key, $default = '') {
    global $translations;
    return isset($translations[$key]) ? $translations[$key] : $default;
}
?>