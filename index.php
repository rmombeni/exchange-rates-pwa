<?php
// ===== فعال‌سازی نمایش خطاها (فقط برای دیباگ) =====
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// ===== تنظیمات اولیه =====
date_default_timezone_set('Asia/Tehran');

// ===== بارگذاری تنظیمات =====
require_once 'includes/config.php';

// ============================================
// ===== دریافت تنظیمات از پنل =====
// ============================================

// ===== زبان =====
$langCode = $settings['site']['default_language'] ?? 'fa';
if (isset($_COOKIE['language']) && isset($languages[$_COOKIE['language']]) && $languages[$_COOKIE['language']]['active']) {
    $langCode = $_COOKIE['language'];
}

$currentLang = $languages[$langCode] ?? $languages['fa'];
$translations = $currentLang['translations'] ?? [];
$langDir = $currentLang['dir'] ?? 'rtl';

// ===== تم =====
$theme = $settings['site']['default_theme'] ?? 'light';
if (isset($_COOKIE['theme'])) {
    $theme = $_COOKIE['theme'];
}

// ===== واحد پول =====
$unit = $settings['currencies']['default_unit'] ?? 'rial';
if (isset($_COOKIE['unit'])) {
    $unit = $_COOKIE['unit'];
}

// ===== تابع تبدیل نرخ =====
function formatRate($rate, $unit = 'rial') {
    if ($unit === 'toman') {
        $rate = $rate / 10000;
    }
    return number_format($rate);
}

// ===== تابع دریافت نرخ ارز =====
function getExchangeRates() {
    $cacheFile = CACHE_PATH . '/rates.json';
    
    if (file_exists($cacheFile) && (time() - filemtime($cacheFile)) < CACHE_TIME) {
        $cached = json_decode(file_get_contents($cacheFile), true);
        if ($cached && !isset($cached['error'])) {
            return $cached;
        }
    }
    
    $url = 'https://cbi.ir/ExRatesRss.aspx';
    
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_TIMEOUT, 15);
    curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36');
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    curl_setopt($ch, CURLOPT_REFERER, 'https://cbi.ir/');
    
    $xmlContent = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    if ($httpCode !== 200 || empty($xmlContent)) {
        if (file_exists($cacheFile)) {
            return json_decode(file_get_contents($cacheFile), true);
        }
        return ['error' => 'خطا در دریافت اطلاعات'];
    }
    
    $xml = simplexml_load_string($xmlContent);
    if ($xml === false) {
        return ['error' => 'خطا در پردازش اطلاعات'];
    }
    
    $channelInfo = [
        'title' => (string) $xml->channel->title,
        'description' => (string) $xml->channel->description,
        'pubDate' => (string) $xml->channel->pubDate,
        'lastBuildDate' => (string) $xml->channel->lastBuildDate
    ];
    
    $items = [];
    foreach ($xml->channel->item as $item) {
        $title = (string) $item->title;
        $description = (string) $item->description;
        $category = (string) $item->category;
        $author = (string) $item->author;
        $pubDate = (string) $item->pubDate;
        
        $rate = (int) $description;
        
        preg_match('/^(.*?)\s+([A-Z0-9]+)\s+در/', $title, $codeMatches);
        if (count($codeMatches) >= 3) {
            $currencyName = trim($codeMatches[1]);
            $currencyCode = $codeMatches[2];
        } else {
            $currencyCode = $category;
            preg_match('/^(.*?)\s+[A-Z0-9]+/', $title, $nameMatches);
            $currencyName = isset($nameMatches[1]) ? trim($nameMatches[1]) : $title;
        }
        
        preg_match('/تاریخ\s*([0-9]{4}\/[0-9]{1,2}\/[0-9]{1,2})/', $title, $dateMatches);
        $persianDate = isset($dateMatches[1]) ? $dateMatches[1] : $author;
        
        $items[] = [
            'code' => $currencyCode,
            'name' => $currencyName,
            'rate' => $rate,
            'rate_formatted' => number_format($rate),
            'persian_date' => $persianDate,
            'pubDate' => $pubDate,
            'category' => $category
        ];
    }
    
    $result = [
        'info' => $channelInfo,
        'items' => $items
    ];
    
    file_put_contents($cacheFile, json_encode($result));
    return $result;
}

// ===== اجرای تابع =====
$result = getExchangeRates();
$lastUpdate = date('Y/m/d H:i:s');

// ===== ارزهای شاخص =====
$majorCodes = $settings['currencies']['major'] ?? ['USD', 'EUR', 'GBP', 'CHF', 'CAD', 'AED', 'TRY'];
$allCurrencies = [];
$featuredCurrencies = [];

if (!isset($result['error'])) {
    $allCurrencies = $result['items'];
    foreach ($allCurrencies as $item) {
        if (in_array($item['code'], $majorCodes)) {
            $featuredCurrencies[] = $item;
        }
    }
    $channelInfo = $result['info'];
}

// ===== آمار =====
$rates = array_column($allCurrencies, 'rate');
$statistics = !empty($rates) ? [
    'max' => max($rates),
    'min' => min($rates),
    'avg' => round(array_sum($rates) / count($rates)),
    'count' => count($rates)
] : null;

// ============================================
// ===== SEO =====
// ============================================
$seo = $settings['seo'] ?? [];
$siteTitle = $seo['og_title'] ?? $settings['site']['name'] ?? 'نرخ ارز مرجع';
$siteDesc = $seo['og_description'] ?? $settings['site']['description'] ?? '';
$siteImage = $seo['og_image'] ?? $settings['site']['url'] . 'icons/icon-512.png';
$siteUrl = $settings['site']['url'] ?? 'https://mozili.ir/arz/';
$robots = $seo['robots'] ?? 'index, follow';
$twitterCard = $seo['twitter_card'] ?? 'summary_large_image';

// ============================================
// ===== بررسی حالت تعمیرات =====
// ============================================
if (isset($settings['site']['maintenance']) && $settings['site']['maintenance'] === true) {
    echo '<!DOCTYPE html><html><head><meta charset="UTF-8"><title>تعمیرات</title>';
    echo '<style>body{font-family:Vazir,sans-serif;display:flex;justify-content:center;align-items:center;min-height:100vh;background:#f0f4fc;direction:rtl;}</style>';
    echo '</head><body><div style="text-align:center;padding:40px;background:white;border-radius:20px;box-shadow:0 20px 60px rgba(0,0,0,0.1);max-width:500px;">';
    echo '<h1 style="font-size:2rem;">🔧 ' . htmlspecialchars($settings['site']['maintenance_message'] ?? 'سایت در حال بروزرسانی است') . '</h1>';
    echo '<p style="color:#6b7d9a;">لطفاً چند دقیقه دیگر مراجعه کنید.</p>';
    echo '</div></body></html>';
    exit;
}

// ============================================
// ===== پردازش فرم‌ها =====
// ============================================

// ===== تغییر زبان =====
if (isset($_GET['lang']) && isset($languages[$_GET['lang']]) && $languages[$_GET['lang']]['active']) {
    $newLang = $_GET['lang'];
    setcookie('language', $newLang, time() + 31536000, '/');
    header('Location: ' . strtok($_SERVER['REQUEST_URI'], '?'));
    exit;
}

// ===== تغییر تم =====
if (isset($_GET['theme'])) {
    $newTheme = $_GET['theme'] === 'dark' ? 'dark' : 'light';
    setcookie('theme', $newTheme, time() + 31536000, '/');
    header('Location: ' . strtok($_SERVER['REQUEST_URI'], '?'));
    exit;
}

// ===== تغییر واحد =====
if (isset($_GET['unit'])) {
    $newUnit = $_GET['unit'] === 'toman' ? 'toman' : 'rial';
    setcookie('unit', $newUnit, time() + 31536000, '/');
    header('Location: ' . strtok($_SERVER['REQUEST_URI'], '?'));
    exit;
}
?>
<!DOCTYPE html>
<html lang="fa" dir="<?php echo $langDir; ?>" data-theme="<?php echo $theme; ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    
    <!-- ===== SEO ===== -->
    <title><?php echo htmlspecialchars($siteTitle); ?></title>
    <meta name="description" content="<?php echo htmlspecialchars($siteDesc); ?>">
    <meta name="keywords" content="<?php echo htmlspecialchars($settings['site']['keywords'] ?? ''); ?>">
    <meta name="author" content="<?php echo htmlspecialchars($settings['site']['author'] ?? ''); ?>">
    <meta name="robots" content="<?php echo htmlspecialchars($robots); ?>">
    
    <!-- ===== Open Graph ===== -->
    <meta property="og:title" content="<?php echo htmlspecialchars($siteTitle); ?>">
    <meta property="og:description" content="<?php echo htmlspecialchars($siteDesc); ?>">
    <meta property="og:url" content="<?php echo htmlspecialchars($siteUrl); ?>">
    <meta property="og:type" content="website">
    <meta property="og:image" content="<?php echo htmlspecialchars($siteImage); ?>">
    
    <!-- ===== Twitter Card ===== -->
    <meta name="twitter:card" content="<?php echo htmlspecialchars($twitterCard); ?>">
    <meta name="twitter:title" content="<?php echo htmlspecialchars($siteTitle); ?>">
    <meta name="twitter:description" content="<?php echo htmlspecialchars($siteDesc); ?>">
    <meta name="twitter:image" content="<?php echo htmlspecialchars($siteImage); ?>">
    
    <!-- ===== PWA ===== -->
    <?php if ($settings['pwa']['enabled'] ?? true): ?>
    <meta name="theme-color" content="<?php echo htmlspecialchars($settings['pwa']['theme_color'] ?? '#0a2540'); ?>">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
    <link rel="manifest" href="manifest.json">
    <link rel="apple-touch-icon" href="icons/icon-192.png">
    <?php endif; ?>
    
    <!-- ===== فونت وزیر لوکال (از پوشه fonts) ===== -->
    <style>
        @font-face {
            font-family: 'Vazir';
            src: url('fonts/Vazir.ttf') format('truetype');
            font-weight: normal;
            font-style: normal;
        }
        @font-face {
            font-family: 'Vazir';
            src: url('fonts/Vazir-Bold.ttf') format('truetype');
            font-weight: bold;
            font-style: normal;
        }
    </style>
    
    <!-- ===== Font Awesome (آیکون‌ها - CDN) ===== -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <!-- ===== Chart.js ===== -->
    <?php if ($settings['features']['chart'] ?? true): ?>
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
    <?php endif; ?>
    
    <style>
        /* ===== CSS Variables ===== */
        :root {
            --bg-primary: #eef3f9;
            --bg-secondary: #ffffff;
            --bg-card: #ffffff;
            --bg-header: #0a2540;
            --bg-input: #fafbfc;
            --text-primary: #0a2540;
            --text-secondary: #3a4a6a;
            --text-muted: #6b7d9a;
            --border-color: rgba(200, 215, 235, 0.3);
            --shadow: 0 30px 70px rgba(0, 20, 50, 0.12);
            --shadow-sm: 0 4px 12px rgba(0, 0, 0, 0.04);
            --gradient-bg: linear-gradient(145deg, #eef3f9 0%, #dce4ef 100%);
            --table-header: linear-gradient(135deg, #f0f4fa, #e4ebf3);
            --badge-bg: #eef3fa;
            --hover-bg: #f6faff;
            --glass-bg: rgba(255, 255, 255, 0.88);
            --glass-border: rgba(255, 255, 255, 0.5);
            --success: #1a7a4a;
            --danger: #c0392b;
            --warning: #ffc107;
            --info: #17a2b8;
            --radius: 16px;
            --transition: all 0.3s ease;
        }
        
        [data-theme="dark"] {
            --bg-primary: #0d1b2a;
            --bg-secondary: #1b2d45;
            --bg-card: #1a2d44;
            --bg-header: #0a1628;
            --bg-input: #1a2d45;
            --text-primary: #e8edf5;
            --text-secondary: #b0c4db;
            --text-muted: #7a8fa8;
            --border-color: rgba(60, 90, 130, 0.4);
            --shadow: 0 30px 70px rgba(0, 0, 0, 0.5);
            --shadow-sm: 0 4px 12px rgba(0, 0, 0, 0.2);
            --gradient-bg: linear-gradient(145deg, #0d1b2a 0%, #1a2d45 100%);
            --table-header: linear-gradient(135deg, #1a2d45, #243b58);
            --badge-bg: #243b58;
            --hover-bg: #1a2d45;
            --glass-bg: rgba(20, 40, 65, 0.9);
            --glass-border: rgba(60, 90, 130, 0.3);
            --success: #2ecc71;
            --danger: #e74c3c;
            --warning: #f39c12;
            --info: #3498db;
        }
        
        /* ===== Reset ===== */
        * { margin: 0; padding: 0; box-sizing: border-box; }
        
        body {
            font-family: 'Vazir', 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: var(--gradient-bg);
            min-height: 100vh;
            padding: 16px;
            transition: background 0.3s ease, color 0.3s ease;
            color: var(--text-primary);
            direction: <?php echo $langDir; ?>;
        }
        
        /* ===== Container ===== */
        .container {
            max-width: 1400px;
            margin: 0 auto;
            background: var(--glass-bg);
            backdrop-filter: blur(12px);
            -webkit-backdrop-filter: blur(12px);
            border-radius: 32px;
            padding: 20px 22px 28px;
            box-shadow: var(--shadow);
            border: 1px solid var(--glass-border);
            transition: all 0.3s ease;
            animation: fadeIn 0.4s ease-out;
        }
        
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(15px); }
            to { opacity: 1; transform: translateY(0); }
        }
        
        /* ===== Header ===== */
        .header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 10px;
            margin-bottom: 16px;
            padding-bottom: 14px;
            border-bottom: 2px solid var(--border-color);
        }
        
        .header-left {
            display: flex;
            align-items: center;
            gap: 12px;
        }
        
        .header-left .logo-icon {
            background: var(--bg-header);
            color: white;
            width: 44px;
            height: 44px;
            border-radius: 14px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.3rem;
            box-shadow: 0 4px 12px rgba(10, 42, 74, 0.2);
            transition: background 0.3s ease;
        }
        
        .header-left h1 {
            font-size: 1.4rem;
            font-weight: 700;
            color: var(--text-primary);
            transition: color 0.3s ease;
        }
        .header-left h1 small {
            font-size: 0.65rem;
            font-weight: 400;
            color: var(--text-muted);
            display: block;
        }
        
        .header-actions {
            display: flex;
            align-items: center;
            gap: 6px;
            flex-wrap: wrap;
        }
        
        /* ===== دکمه‌ها ===== */
        .btn {
            padding: 8px 18px;
            border: none;
            border-radius: 60px;
            font-family: 'Vazir', sans-serif;
            font-size: 0.8rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            display: inline-flex;
            align-items: center;
            gap: 6px;
            text-decoration: none;
        }
        .btn:hover { transform: translateY(-2px); }
        
        .btn-primary { background: var(--bg-header); color: white; box-shadow: 0 4px 14px rgba(10, 37, 64, 0.15); }
        .btn-primary:hover { box-shadow: 0 8px 25px rgba(10, 37, 64, 0.25); }
        .btn-success { background: var(--success); color: white; }
        .btn-success:hover { box-shadow: 0 8px 25px rgba(26, 122, 74, 0.25); }
        .btn-danger { background: var(--danger); color: white; }
        .btn-danger:hover { box-shadow: 0 8px 25px rgba(192, 57, 43, 0.25); }
        .btn-warning { background: var(--warning); color: #0a2540; }
        .btn-warning:hover { box-shadow: 0 8px 25px rgba(255, 193, 7, 0.25); }
        .btn-outline { background: transparent; color: var(--text-primary); border: 2px solid var(--border-color); }
        .btn-outline:hover { background: var(--bg-secondary); }
        .btn-sm { padding: 4px 12px; font-size: 0.7rem; }
        .btn-icon {
            width: 38px;
            height: 38px;
            border-radius: 50%;
            border: 1px solid var(--border-color);
            background: var(--bg-secondary);
            color: var(--text-primary);
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: all 0.3s ease;
            font-size: 0.95rem;
            box-shadow: var(--shadow-sm);
        }
        .btn-icon:hover { transform: scale(1.05); }
        
        /* ===== Language Switcher ===== */
        .lang-switcher {
            display: flex;
            gap: 2px;
        }
        .lang-switcher a {
            padding: 4px 8px;
            border-radius: 30px;
            font-size: 0.7rem;
            text-decoration: none;
            color: var(--text-muted);
            transition: all 0.3s ease;
            border: 1px solid transparent;
        }
        .lang-switcher a:hover { background: var(--badge-bg); }
        .lang-switcher a.active {
            background: var(--bg-header);
            color: white;
            border-color: var(--bg-header);
        }
        
        /* ===== Tools Bar ===== */
        .tools-bar {
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
            margin-bottom: 16px;
            align-items: center;
            padding: 8px 12px;
            background: var(--bg-secondary);
            border-radius: 12px;
            border: 1px solid var(--border-color);
        }
        
        .tools-bar .search-box {
            display: flex;
            align-items: center;
            background: var(--bg-input);
            border: 1px solid var(--border-color);
            border-radius: 60px;
            padding: 4px 14px;
            gap: 8px;
            flex: 1;
            max-width: 280px;
            transition: all 0.3s ease;
        }
        .tools-bar .search-box:focus-within {
            border-color: var(--bg-header);
            box-shadow: 0 0 0 3px rgba(10, 37, 64, 0.08);
        }
        .tools-bar .search-box i { color: var(--text-muted); }
        .tools-bar .search-box input {
            border: none;
            outline: none;
            background: transparent;
            padding: 6px 0;
            font-family: 'Vazir', sans-serif;
            font-size: 0.8rem;
            width: 100%;
            color: var(--text-primary);
        }
        .tools-bar .search-box input::placeholder { color: var(--text-muted); }
        
        /* ===== Stats Bar ===== */
        .stats-bar {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(100px, 1fr));
            gap: 8px;
            margin-bottom: 18px;
            padding: 12px 16px;
            background: var(--bg-secondary);
            border-radius: 12px;
            border: 1px solid var(--border-color);
        }
        .stat-item { text-align: center; }
        .stat-item .label { font-size: 0.6rem; color: var(--text-muted); }
        .stat-item .value { font-size: 0.9rem; font-weight: 700; color: var(--text-primary); }
        .stat-item .value small { font-size: 0.6rem; font-weight: 400; color: var(--text-muted); }
        
        /* ===== Featured Currencies ===== */
        .featured-section { margin-bottom: 24px; }
        .section-title {
            font-size: 0.95rem;
            font-weight: 600;
            color: var(--text-primary);
            margin-bottom: 12px;
            display: flex;
            align-items: center;
            gap: 8px;
            flex-wrap: wrap;
        }
        .section-title i { color: var(--success); }
        .section-title span {
            background: var(--bg-header);
            color: white;
            font-size: 0.6rem;
            padding: 1px 10px;
            border-radius: 30px;
        }
        .count-badge {
            background: var(--badge-bg);
            padding: 1px 12px;
            border-radius: 30px;
            font-size: 0.65rem;
            color: var(--text-secondary);
        }
        
        .featured-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(120px, 1fr));
            gap: 10px;
        }
        
        .featured-card {
            background: var(--bg-card);
            padding: 12px 6px 10px;
            border-radius: 18px;
            text-align: center;
            box-shadow: var(--shadow-sm);
            border: 1px solid var(--border-color);
            transition: all 0.3s ease;
            cursor: pointer;
            position: relative;
        }
        .featured-card:hover { transform: translateY(-3px); box-shadow: 0 12px 28px rgba(0, 40, 80, 0.08); }
        .featured-card .code {
            font-size: 0.85rem;
            font-weight: 700;
            color: var(--text-primary);
        }
        .featured-card .name {
            font-size: 0.55rem;
            color: var(--text-muted);
            display: block;
        }
        .featured-card .rate {
            font-size: 0.95rem;
            font-weight: 700;
            color: var(--text-primary);
            direction: ltr;
            background: var(--badge-bg);
            padding: 1px 8px;
            border-radius: 30px;
            display: inline-block;
        }
        .featured-card .rate small { font-size: 0.5rem; font-weight: 400; color: var(--text-muted); }
        
        /* ===== Table ===== */
        .table-wrapper {
            background: var(--bg-card);
            border-radius: 18px;
            overflow: hidden;
            border: 1px solid var(--border-color);
        }
        .table-scroll { overflow-x: auto; padding: 2px 0; }
        table { width: 100%; border-collapse: collapse; font-size: 0.8rem; }
        thead { background: var(--table-header); }
        thead th {
            padding: 10px 12px;
            text-align: <?php echo $langDir === 'rtl' ? 'right' : 'left'; ?>;
            font-weight: 600;
            color: var(--text-primary);
            border-bottom: 2px solid var(--border-color);
            white-space: nowrap;
            font-size: 0.7rem;
            cursor: pointer;
            user-select: none;
            transition: background 0.2s;
        }
        thead th:hover { background: rgba(0,0,0,0.02); }
        thead th i { margin-<?php echo $langDir === 'rtl' ? 'left' : 'right'; ?>: 4px; color: var(--text-muted); }
        thead th .sort-icon { font-size: 0.6rem; }
        
        tbody tr {
            transition: background 0.15s ease;
            border-bottom: 1px solid var(--border-color);
        }
        tbody tr:hover { background: var(--hover-bg); }
        tbody tr:last-child { border-bottom: none; }
        tbody td { padding: 8px 12px; vertical-align: middle; color: var(--text-secondary); }
        
        .td-code { font-weight: 700; color: var(--text-primary); font-size: 0.75rem; white-space: nowrap; }
        .td-name { font-size: 0.8rem; }
        .td-rate {
            font-weight: 600;
            color: var(--text-primary);
            direction: ltr;
            font-size: 0.85rem;
            white-space: nowrap;
        }
        .td-rate small { font-weight: 400; color: var(--text-muted); font-size: 0.55rem; }
        .td-date { font-size: 0.65rem; white-space: nowrap; }
        
        .badge-persian {
            background: var(--badge-bg);
            padding: 1px 10px;
            border-radius: 30px;
            font-size: 0.6rem;
            color: var(--text-secondary);
            display: inline-block;
            white-space: nowrap;
        }
        
        /* ===== Modal ===== */
        .modal {
            display: none;
            position: fixed;
            top: 0; left: 0; right: 0; bottom: 0;
            background: rgba(0,0,0,0.6);
            z-index: 10000;
            justify-content: center;
            align-items: center;
            padding: 20px;
            backdrop-filter: blur(8px);
        }
        .modal.show { display: flex; }
        .modal-content {
            background: var(--bg-secondary);
            border-radius: 24px;
            padding: 24px;
            max-width: 700px;
            width: 100%;
            max-height: 90vh;
            overflow-y: auto;
            box-shadow: 0 40px 80px rgba(0,0,0,0.4);
            animation: modalIn 0.3s ease-out;
        }
        @keyframes modalIn {
            from { opacity: 0; transform: scale(0.9) translateY(20px); }
            to { opacity: 1; transform: scale(1) translateY(0); }
        }
        .modal-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 16px;
        }
        .modal-header h3 { color: var(--text-primary); font-size: 1.1rem; }
        .modal-close {
            background: none;
            border: none;
            font-size: 1.5rem;
            color: var(--text-muted);
            cursor: pointer;
            transition: all 0.3s ease;
        }
        .modal-close:hover { color: var(--text-primary); transform: rotate(90deg); }
        .chart-container { height: 300px; }
        
        /* ===== Footer ===== */
        .footer {
            margin-top: 20px;
            padding-top: 14px;
            border-top: 1px solid var(--border-color);
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 8px;
            font-size: 0.65rem;
            color: var(--text-muted);
        }
        .footer i { margin-<?php echo $langDir === 'rtl' ? 'left' : 'right'; ?>: 4px; }
        .update-time {
            background: var(--badge-bg);
            padding: 3px 14px;
            border-radius: 40px;
            display: inline-flex;
            align-items: center;
            gap: 6px;
            font-size: 0.7rem;
            color: var(--text-primary);
        }
        
        /* ===== Toast ===== */
        .toast {
            position: fixed;
            bottom: 30px;
            left: 50%;
            transform: translateX(-50%);
            background: var(--bg-header);
            color: white;
            padding: 10px 22px;
            border-radius: 60px;
            font-size: 0.8rem;
            box-shadow: 0 8px 30px rgba(0,0,0,0.3);
            z-index: 9999;
            opacity: 0;
            transition: all 0.5s ease;
            pointer-events: none;
            font-family: 'Vazir', sans-serif;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        .toast.show { opacity: 1; pointer-events: auto; }
        
        /* ===== Error ===== */
        .error-box {
            padding: 18px 22px;
            border-radius: 16px;
            color: var(--danger);
            border-right: 4px solid var(--danger);
            margin: 14px 0;
            background: rgba(192, 57, 43, 0.08);
        }
        [data-theme="dark"] .error-box {
            background: rgba(231, 76, 60, 0.12);
        }
        
        /* ===== Responsive ===== */
        @media (max-width: 992px) {
            .container { padding: 16px 12px; }
            .header-left h1 { font-size: 1.1rem; }
            .featured-grid { grid-template-columns: repeat(4, 1fr); }
        }
        
        @media (max-width: 768px) {
            body { padding: 8px; }
            .container { padding: 12px 10px; border-radius: 20px; }
            .header { flex-direction: column; align-items: stretch; gap: 6px; }
            .header-left { justify-content: center; }
            .header-left .logo-icon { width: 36px; height: 36px; font-size: 1rem; }
            .header-left h1 { font-size: 1rem; text-align: center; }
            .header-actions { justify-content: center; flex-wrap: wrap; }
            .tools-bar { flex-direction: column; align-items: stretch; }
            .tools-bar .search-box { max-width: 100%; }
            .stats-bar { grid-template-columns: repeat(2, 1fr); padding: 8px 12px; }
            .featured-grid { grid-template-columns: repeat(3, 1fr); gap: 6px; }
            .featured-card { padding: 8px 4px; }
            .featured-card .rate { font-size: 0.8rem; }
            table { font-size: 0.65rem; }
            thead th, tbody td { padding: 5px 6px; }
            .td-rate { font-size: 0.7rem; }
            .modal-content { padding: 16px; margin: 10px; }
            .chart-container { height: 200px; }
            .footer { flex-direction: column; text-align: center; }
        }
        
        @media (max-width: 480px) {
            .featured-grid { grid-template-columns: repeat(2, 1fr); }
            .header-actions .btn-sm { font-size: 0.6rem; padding: 3px 8px; }
            .btn-icon { width: 32px; height: 32px; font-size: 0.8rem; }
        }
        
        /* ===== Scrollbar ===== */
        .table-scroll::-webkit-scrollbar { height: 4px; }
        .table-scroll::-webkit-scrollbar-track { background: var(--badge-bg); border-radius: 10px; }
        .table-scroll::-webkit-scrollbar-thumb { background: var(--text-muted); border-radius: 10px; }
        
        ::-webkit-scrollbar { width: 6px; }
        ::-webkit-scrollbar-track { background: var(--badge-bg); }
        ::-webkit-scrollbar-thumb { background: var(--text-muted); border-radius: 10px; }
        ::-webkit-scrollbar-thumb:hover { background: var(--text-secondary); }
        
        /* ===== Print ===== */
        @media print {
            .header-actions, .tools-bar, .stats-bar, .featured-section, .footer { display: none; }
            body { padding: 0; background: white; }
            .container { box-shadow: none; border: none; }
        }
    </style>
</head>
<body>
    
    <!-- ===== Toast ===== -->
    <div id="toast" class="toast">
        <i class="fas fa-check-circle"></i>
        <span id="toastMessage"></span>
    </div>
    
    <!-- ===== Modal: Chart ===== -->
    <div id="chartModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3 id="chartTitle"><i class="fas fa-chart-line"></i> نمودار تغییرات نرخ</h3>
                <button class="modal-close" onclick="closeChart()">&times;</button>
            </div>
            <div class="chart-container">
                <canvas id="historyChart"></canvas>
            </div>
        </div>
    </div>
    
    <!-- ===== Main Container ===== -->
    <div class="container">
        
        <!-- ===== Header ===== -->
        <header class="header">
            <div class="header-left">
                <div class="logo-icon"><i class="fas fa-coins"></i></div>
                <div>
                    <h1>
                        <?php 
                        // استفاده از تابع __ از فایل config.php
                        echo __('site_title', 'نرخ ارز مرجع'); 
                        ?>
                        <small><?php echo __('site_description', 'نمایش لحظه‌ای نرخ ارزهای رسمی بانک مرکزی'); ?></small>
                    </h1>
                </div>
            </div>
            <div class="header-actions">
                <!-- ===== Language Switcher ===== -->
                <div class="lang-switcher">
                    <?php foreach ($languages as $code => $lang): ?>
                        <?php if ($lang['active'] ?? false): ?>
                        <a href="?lang=<?php echo $code; ?>" class="<?php echo $langCode === $code ? 'active' : ''; ?>" title="<?php echo htmlspecialchars($lang['name']); ?>">
                            <?php echo $lang['flag'] ?? '🏳️'; ?>
                        </a>
                        <?php endif; ?>
                    <?php endforeach; ?>
                </div>
                
                <!-- ===== Theme Toggle ===== -->
                <a href="?theme=<?php echo $theme === 'light' ? 'dark' : 'light'; ?>" class="btn-icon" title="<?php echo $theme === 'light' ? __('theme_dark', 'تم تاریک') : __('theme_light', 'تم روشن'); ?>">
                    <i class="fas fa-<?php echo $theme === 'light' ? 'moon' : 'sun'; ?>"></i>
                </a>
                
                <!-- ===== Unit Toggle ===== -->
                <a href="?unit=<?php echo $unit === 'rial' ? 'toman' : 'rial'; ?>" class="btn-icon" title="<?php echo $unit === 'rial' ? __('unit_toman', 'تومان') : __('unit_rial', 'ریال'); ?>">
                    <i class="fas fa-<?php echo $unit === 'rial' ? 'dollar-sign' : 'toman-sign'; ?>"></i>
                </a>
                
                <!-- ===== Admin Panel ===== -->
                <a href="admin/" class="btn btn-primary btn-sm">
                    <i class="fas fa-cog"></i> <?php echo __('admin_panel', 'مدیریت'); ?>
                </a>
            </div>
        </header>
        
        <!-- ============================================================ -->
        <!-- ===== CONTENT ===== -->
        <!-- ============================================================ -->
        
        <?php if (isset($result['error'])): ?>
            <div class="error-box">
                <i class="fas fa-exclamation-triangle"></i>
                <?php echo htmlspecialchars($result['error']); ?>
            </div>
        <?php elseif (empty($allCurrencies)): ?>
            <div class="error-box">
                <i class="fas fa-info-circle"></i>
                <?php echo __('no_data', 'هیچ داده‌ای برای نمایش وجود ندارد.'); ?>
            </div>
        <?php else: ?>
            
            <!-- ===== Stats Bar ===== -->
            <?php if ($statistics): ?>
            <div class="stats-bar">
                <div class="stat-item">
                    <div class="label"><?php echo __('total_currencies', 'کل ارزها'); ?></div>
                    <div class="value"><?php echo $statistics['count']; ?></div>
                </div>
                <div class="stat-item">
                    <div class="label"><?php echo __('highest_rate', 'بالاترین نرخ'); ?></div>
                    <div class="value"><?php echo formatRate($statistics['max'], $unit); ?><small> <?php echo $unit === 'toman' ? 'تومان' : 'IRR'; ?></small></div>
                </div>
                <div class="stat-item">
                    <div class="label"><?php echo __('lowest_rate', 'پایین‌ترین نرخ'); ?></div>
                    <div class="value"><?php echo formatRate($statistics['min'], $unit); ?><small> <?php echo $unit === 'toman' ? 'تومان' : 'IRR'; ?></small></div>
                </div>
                <div class="stat-item">
                    <div class="label"><?php echo __('average_rate', 'میانگین'); ?></div>
                    <div class="value"><?php echo formatRate($statistics['avg'], $unit); ?><small> <?php echo $unit === 'toman' ? 'تومان' : 'IRR'; ?></small></div>
                </div>
            </div>
            <?php endif; ?>
            
            <!-- ===== Tools Bar ===== -->
            <div class="tools-bar">
                <?php if ($settings['features']['search'] ?? true): ?>
                <div class="search-box">
                    <i class="fas fa-search"></i>
                    <input type="text" id="searchInput" placeholder="<?php echo __('search_placeholder', 'جستجو...'); ?>">
                </div>
                <?php endif; ?>
                
                <?php if ($settings['features']['csv_export'] ?? true): ?>
                <button class="btn btn-outline btn-sm" onclick="exportCSV()">
                    <i class="fas fa-file-csv"></i> <?php echo __('export', 'خروجی'); ?>
                </button>
                <?php endif; ?>
                
                <span class="count-badge" id="visibleCount"><?php echo count($allCurrencies); ?> <?php echo __('currency', 'ارز'); ?></span>
            </div>
            
            <!-- ===== Featured Currencies ===== -->
            <?php if (!empty($featuredCurrencies)): ?>
            <div class="featured-section">
                <div class="section-title">
                    <i class="fas fa-star"></i> <?php echo __('featured_currencies', 'ارزهای شاخص'); ?>
                    <span><?php echo __('featured', 'ویژه'); ?></span>
                </div>
                <div class="featured-grid" id="featuredGrid">
                    <?php foreach ($featuredCurrencies as $item): ?>
                    <div class="featured-card" data-code="<?php echo $item['code']; ?>" onclick="showChart('<?php echo $item['code']; ?>')">
                        <span class="code"><?php echo htmlspecialchars($item['code']); ?></span>
                        <span class="name"><?php echo htmlspecialchars($item['name']); ?></span>
                        <span class="rate" data-rial="<?php echo $item['rate']; ?>">
                            <?php echo formatRate($item['rate'], $unit); ?><small> <?php echo $unit === 'toman' ? 'تومان' : 'IRR'; ?></small>
                        </span>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php endif; ?>
            
            <!-- ===== All Currencies Table ===== -->
            <div class="table-section">
                <div class="section-title" style="margin-bottom:10px;">
                    <i class="fas fa-list-ul"></i> <?php echo __('all_currencies', 'لیست کامل نرخ ارزها'); ?>
                    <span class="count-badge" id="visibleCountTable"><?php echo count($allCurrencies); ?> <?php echo __('currency', 'ارز'); ?></span>
                </div>
                
                <div class="table-wrapper">
                    <div class="table-scroll">
                        <table id="ratesTable">
                            <thead>
                                <tr>
                                    <th style="width:40px;" onclick="sortTable(0)"><i class="fas fa-hashtag"></i> <span class="sort-icon">⇅</span></th>
                                    <th onclick="sortTable(1)"><i class="fas fa-tag"></i> <?php echo __('code', 'کد'); ?> <span class="sort-icon">⇅</span></th>
                                    <th onclick="sortTable(2)"><i class="fas fa-flag"></i> <?php echo __('currency_name', 'نام ارز'); ?> <span class="sort-icon">⇅</span></th>
                                    <th style="text-align:center;" onclick="sortTable(3)"><i class="fas fa-exchange-alt"></i> <?php echo __('rate', 'نرخ'); ?> <span class="sort-icon">⇅</span></th>
                                    <th onclick="sortTable(4)"><i class="fas fa-calendar-alt"></i> <?php echo __('date', 'تاریخ'); ?> <span class="sort-icon">⇅</span></th>
                                    <?php if ($settings['features']['chart'] ?? true): ?>
                                    <th style="width:50px;"><i class="fas fa-chart-simple"></i></th>
                                    <?php endif; ?>
                                </tr>
                            </thead>
                            <tbody id="tableBody">
                                <?php 
                                $rowNum = 1;
                                foreach ($allCurrencies as $item): 
                                ?>
                                <tr data-code="<?php echo $item['code']; ?>" data-rate="<?php echo $item['rate']; ?>">
                                    <td style="color:var(--text-muted);font-size:0.7rem;"><?php echo $rowNum++; ?></td>
                                    <td class="td-code"><?php echo htmlspecialchars($item['code']); ?></td>
                                    <td class="td-name"><?php echo htmlspecialchars($item['name']); ?></td>
                                    <td class="td-rate" style="text-align:center;" data-rial="<?php echo $item['rate']; ?>">
                                        <?php echo formatRate($item['rate'], $unit); ?><small> <?php echo $unit === 'toman' ? 'تومان' : 'IRR'; ?></small>
                                    </td>
                                    <td class="td-date">
                                        <span class="badge-persian">
                                            <i class="fas fa-calendar"></i> <?php echo htmlspecialchars($item['persian_date']); ?>
                                        </span>
                                    </td>
                                    <?php if ($settings['features']['chart'] ?? true): ?>
                                    <td>
                                        <button class="btn-icon" style="width:28px;height:28px;font-size:0.7rem;" onclick="showChart('<?php echo $item['code']; ?>')">
                                            <i class="fas fa-chart-simple"></i>
                                        </button>
                                    </td>
                                    <?php endif; ?>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
            
        <?php endif; ?>
        
        <!-- ===== Footer ===== -->
        <div class="footer">
            <div>
                <i class="fas fa-database"></i> 
                <strong id="totalCount"><?php echo count($allCurrencies); ?></strong> <?php echo __('currency', 'ارز'); ?>
                &nbsp;|&nbsp; <i class="fas fa-clock"></i> 
                <?php echo __('update_time', 'آخرین بروزرسانی'); ?>: 
                <?php echo isset($channelInfo['pubDate']) ? date('Y/m/d H:i', strtotime($channelInfo['pubDate'])) : $lastUpdate; ?>
            </div>
            <div class="update-time">
                <i class="fas fa-check-circle" style="color:var(--success);"></i>
                <?php echo __('source', 'منبع'); ?>: <?php echo htmlspecialchars($channelInfo['title'] ?? 'بانک مرکزی'); ?>
            </div>
        </div>
        
    </div>
    
    <!-- ===== Scripts ===== -->
    <script>
    (function() {
        'use strict';
        
        // ===== داده‌ها =====
        let currentSort = { column: 0, direction: 'asc' };
        let chartInstance = null;
        
        // ===== DOM =====
        const searchInput = document.getElementById('searchInput');
        const tableBody = document.getElementById('tableBody');
        const toast = document.getElementById('toast');
        const toastMessage = document.getElementById('toastMessage');
        const visibleCount = document.getElementById('visibleCount');
        const visibleCountTable = document.getElementById('visibleCountTable');
        
        // ===== Toast =====
        function showToast(msg, type = 'success') {
            toastMessage.textContent = msg;
            toast.className = 'toast show';
            toast.querySelector('i').className = type === 'success' ? 'fas fa-check-circle' : 'fas fa-exclamation-circle';
            clearTimeout(toast._timeout);
            toast._timeout = setTimeout(() => toast.classList.remove('show'), 3000);
        }
        
        // ===== Search =====
        if (searchInput) {
            searchInput.addEventListener('input', function() {
                const term = this.value.toLowerCase().trim();
                const rows = tableBody.querySelectorAll('tr');
                let visible = 0;
                rows.forEach(row => {
                    const text = row.textContent.toLowerCase();
                    const match = text.includes(term);
                    row.style.display = match ? '' : 'none';
                    if (match) visible++;
                });
                if (visibleCount) visibleCount.textContent = visible + ' <?php echo __('currency', 'ارز'); ?>';
                if (visibleCountTable) visibleCountTable.textContent = visible + ' <?php echo __('currency', 'ارز'); ?>';
            });
        }
        
        // ===== Sort =====
        function sortTable(column) {
            const rows = Array.from(tableBody.querySelectorAll('tr'));
            const direction = currentSort.column === column && currentSort.direction === 'asc' ? 'desc' : 'asc';
            currentSort = { column, direction };
            
            rows.sort((a, b) => {
                let aVal, bVal;
                const cells = a.cells;
                if (column === 3) { // نرخ
                    aVal = parseInt(a.getAttribute('data-rate') || '0');
                    bVal = parseInt(b.getAttribute('data-rate') || '0');
                } else {
                    aVal = cells[column]?.textContent.trim() || '';
                    bVal = cells[column]?.textContent.trim() || '';
                }
                if (typeof aVal === 'string') {
                    return direction === 'asc' ? aVal.localeCompare(bVal) : bVal.localeCompare(aVal);
                }
                return direction === 'asc' ? aVal - bVal : bVal - aVal;
            });
            
            tableBody.innerHTML = '';
            rows.forEach(row => tableBody.appendChild(row));
            
            document.querySelectorAll('.sort-icon').forEach(el => el.textContent = '⇅');
            const headers = document.querySelectorAll('thead th');
            const icon = headers[column]?.querySelector('.sort-icon');
            if (icon) icon.textContent = direction === 'asc' ? '↑' : '↓';
        }
        
        // ===== Chart Modal =====
        function showChart(code) {
            <?php if (!($settings['features']['chart'] ?? true)): ?>
            showToast('<?php echo __('chart_disabled', 'نمودار غیرفعال است'); ?>', 'error');
            return;
            <?php endif; ?>
            
            const modal = document.getElementById('chartModal');
            const title = document.getElementById('chartTitle');
            title.innerHTML = '<i class="fas fa-chart-line"></i> نمودار تغییرات نرخ ' + code;
            modal.classList.add('show');
            
            // دریافت تاریخچه از سرور
            fetch('history.php?code=' + encodeURIComponent(code))
                .then(res => res.json())
                .then(data => {
                    const ctx = document.getElementById('historyChart').getContext('2d');
                    if (chartInstance) chartInstance.destroy();
                    chartInstance = new Chart(ctx, {
                        type: 'line',
                        data: {
                            labels: data.map(d => d.date.substring(5, 16)),
                            datasets: [{
                                label: 'نرخ ' + code,
                                data: data.map(d => d.rate),
                                borderColor: '#1a7a4a',
                                backgroundColor: 'rgba(26, 122, 74, 0.1)',
                                fill: true,
                                tension: 0.3
                            }]
                        },
                        options: {
                            responsive: true,
                            maintainAspectRatio: false,
                            plugins: {
                                legend: { display: true }
                            },
                            scales: {
                                y: { beginAtZero: false }
                            }
                        }
                    });
                })
                .catch(() => showToast('❌ <?php echo __('error_loading_chart', 'خطا در بارگذاری نمودار'); ?>', 'error'));
        }
        
        function closeChart() {
            document.getElementById('chartModal').classList.remove('show');
            if (chartInstance) {
                chartInstance.destroy();
                chartInstance = null;
            }
        }
        
        // ===== Close modal on outside click =====
        document.getElementById('chartModal')?.addEventListener('click', function(e) {
            if (e.target === this) closeChart();
        });
        
        // ===== Export CSV =====
        function exportCSV() {
            <?php if (!($settings['features']['csv_export'] ?? true)): ?>
            showToast('<?php echo __('export_disabled', 'خروجی CSV غیرفعال است'); ?>', 'error');
            return;
            <?php endif; ?>
            
            const rows = tableBody.querySelectorAll('tr');
            let csv = '<?php echo __('row', 'ردیف'); ?>,<?php echo __('code', 'کد'); ?>,<?php echo __('currency_name', 'نام ارز'); ?>,<?php echo __('rate', 'نرخ'); ?>,<?php echo __('date', 'تاریخ'); ?>\n';
            let i = 1;
            rows.forEach(row => {
                if (row.style.display === 'none') return;
                const cells = row.cells;
                const code = cells[1]?.textContent.trim() || '';
                const name = cells[2]?.textContent.trim() || '';
                const rate = cells[3]?.textContent.replace(/IRR|تومان/g, '').replace(/,/g, '').trim() || '';
                const date = cells[4]?.textContent.trim() || '';
                csv += i++ + ',' + code + ',' + name + ',' + rate + ',' + date + '\n';
            });
            const blob = new Blob(['\uFEFF' + csv], { type: 'text/csv;charset=utf-8;' });
            const link = document.createElement('a');
            link.href = URL.createObjectURL(blob);
            link.download = 'نرخ_ارز_' + new Date().toISOString().slice(0,10) + '.csv';
            link.click();
            showToast('📥 <?php echo __('csv_downloaded', 'فایل CSV دانلود شد'); ?>');
        }
        
        // ===== Keyboard shortcuts =====
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') {
                closeChart();
            }
            if (e.ctrlKey && e.key === 'f') {
                e.preventDefault();
                searchInput?.focus();
            }
        });
        
        // ===== Auto refresh =====
        <?php if ($settings['features']['auto_refresh'] ?? true): ?>
        setTimeout(() => location.reload(), <?php echo ($settings['features']['refresh_interval'] ?? 300) * 1000; ?>);
        <?php endif; ?>
        
        // ===== Init =====
        console.log('💱 <?php echo __('site_title', 'نرخ ارز مرجع'); ?>');
        console.log('🌐 زبان: <?php echo $langCode; ?>');
        console.log('🎨 تم: <?php echo $theme; ?>');
        console.log('💰 واحد: <?php echo $unit; ?>');
        console.log('📊 تعداد ارزها: <?php echo count($allCurrencies); ?>');
        
        // اکسپورت توابع برای استفاده در HTML
        window.sortTable = sortTable;
        window.showChart = showChart;
        window.closeChart = closeChart;
        window.exportCSV = exportCSV;
        
    })();
    </script>
    
</body>
</html>