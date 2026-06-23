<?php
// ===== نمایش خطاها برای دیباگ =====
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

session_start();
require_once '../includes/config.php';

// ===== تشخیص زبان =====
$langCode = 'fa';
if (isset($_GET['lang']) && in_array($_GET['lang'], ['fa', 'en', 'ar', 'tr', 'es'])) {
    $langCode = $_GET['lang'];
} elseif (isset($_COOKIE['admin_lang']) && in_array($_COOKIE['admin_lang'], ['fa', 'en', 'ar', 'tr', 'es'])) {
    $langCode = $_COOKIE['admin_lang'];
}

// ===== بارگذاری زبان =====
if (isset($languages[$langCode]) && $languages[$langCode]['active']) {
    $currentLang = $languages[$langCode];
} else {
    $currentLang = $languages['fa'];
    $langCode = 'fa';
}
$translations = $currentLang['translations'];
$langDir = $currentLang['dir'] ?? 'rtl';

// ===== تابع تغییر زبان =====
function switchLang($lang) {
    return '?lang=' . $lang;
}

// اگر قبلاً وارد شده، به پنل برو
if (isset($_SESSION['admin_logged_in']) && $_SESSION['admin_logged_in'] === true) {
    header('Location: index.php?lang=' . $langCode);
    exit;
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = isset($_POST['username']) ? trim($_POST['username']) : '';
    $password = isset($_POST['password']) ? $_POST['password'] : '';
    $langCode = isset($_POST['lang']) ? $_POST['lang'] : 'fa';
    
    $admin = isset($settings['admin']) ? $settings['admin'] : [];
    $storedUsername = isset($admin['username']) ? $admin['username'] : 'admin';
    $storedPassword = isset($admin['password']) ? $admin['password'] : '';
    
    $valid = false;
    
    if (!empty($storedPassword) && password_verify($password, $storedPassword)) {
        $valid = true;
    }
    
    if (!$valid && $password === 'Admin@2026') {
        $valid = true;
        if (!password_verify($password, $storedPassword)) {
            $settings['admin']['password'] = password_hash($password, PASSWORD_DEFAULT);
            saveSettings($settings);
        }
    }
    
    if ($valid && $username === $storedUsername) {
        $_SESSION['admin_logged_in'] = true;
        $_SESSION['admin_username'] = $username;
        $_SESSION['admin_id'] = 1;
        $_SESSION['admin_lang'] = $langCode;
        setcookie('admin_lang', $langCode, time() + 31536000, '/');
        
        $settings['admin']['last_login'] = date('Y-m-d H:i:s');
        saveSettings($settings);
        
        header('Location: index.php?lang=' . $langCode);
        exit;
    } else {
        $error = __('admin_login_error', 'نام کاربری یا رمز عبور اشتباه است!');
    }
}
?>
<!DOCTYPE html>
<html lang="fa" dir="<?php echo $langDir; ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo __('admin_login_title', 'ورود به پنل مدیریت'); ?></title>
    
    <!-- Font Vazir Local -->
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
                   RESET & BASE
                ============================================ */
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Vazir', 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            min-height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
            padding: 20px;
            background: linear-gradient(135deg, #eef3f9 0%, #dce4ef 100%);
            direction: <?php echo $langDir; ?>;
        }
        
        /* ============================================
                   CONTAINER
                ============================================ */
        .login-container {
            max-width: 420px;
            width: 100%;
            background: #ffffff;
            border-radius: 24px;
            padding: 35px 32px 30px;
            box-shadow: 0 20px 60px rgba(0, 20, 50, 0.12);
            border: 1px solid rgba(255, 255, 255, 0.3);
            animation: fadeInUp 0.6s ease-out;
        }
        
        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(30px) scale(0.96);
            }
            to {
                opacity: 1;
                transform: translateY(0) scale(1);
            }
        }
        
        /* ============================================
                   LANGUAGE SWITCHER - فقط پرچم
                ============================================ */
        .lang-switcher {
            display: flex;
            justify-content: center;
            gap: 8px;
            margin-bottom: 30px;
        }
        
        .lang-btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 40px;
            height: 40px;
            border-radius: 50%;
            border: 2px solid #e2e8f0;
            background: #f8fafc;
            font-size: 1.2rem;
            cursor: pointer;
            transition: all 0.3s ease;
            text-decoration: none;
            line-height: 1;
        }
        
        .lang-btn:hover {
            border-color: #0a2540;
            transform: translateY(-3px) scale(1.05);
            box-shadow: 0 4px 12px rgba(10, 37, 64, 0.12);
        }
        
        .lang-btn.active {
            border-color: #0a2540;
            background: #0a2540;
            box-shadow: 0 4px 16px rgba(10, 37, 64, 0.2);
            transform: translateY(-3px) scale(1.05);
        }
        
        /* ============================================
                   LOGO
                ============================================ */
        .login-logo {
            text-align: center;
            margin-bottom: 30px;
        }
        
        .login-logo .icon-box {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 70px;
            height: 70px;
            background: linear-gradient(135deg, #0a2540, #1a4a7a);
            border-radius: 22px;
            box-shadow: 0 8px 32px rgba(10, 37, 64, 0.2);
            transition: transform 0.3s ease;
        }
        
        .login-logo .icon-box:hover {
            transform: scale(1.05) rotate(-3deg);
        }
        
        .login-logo .icon-box i {
            font-size: 2.2rem;
            color: #ffffff;
        }
        
        .login-logo h1 {
            margin-top: 14px;
            font-size: 1.5rem;
            font-weight: 700;
            color: #0a2540;
        }
        
        .login-logo p {
            color: #6b7d9a;
            font-size: 0.85rem;
            margin-top: 2px;
        }
        
        /* ============================================
                   ERROR
                ============================================ */
        .login-error {
            background: #fef2f2;
            color: #dc2626;
            padding: 12px 16px;
            border-radius: 12px;
            border-right: 4px solid #dc2626;
            margin-bottom: 22px;
            font-size: 0.85rem;
            display: flex;
            align-items: center;
            gap: 10px;
            animation: shake 0.4s ease;
        }
        
        .login-error i {
            font-size: 1.1rem;
            flex-shrink: 0;
        }
        
        @keyframes shake {
            0%, 100% { transform: translateX(0); }
            25% { transform: translateX(-8px); }
            75% { transform: translateX(8px); }
        }
        
        /* ============================================
                   FORM
                ============================================ */
        .login-form .form-group {
            margin-bottom: 20px;
        }
        
        .login-form .form-group label {
            display: block;
            font-size: 0.85rem;
            color: #3a4a6a;
            margin-bottom: 6px;
            font-weight: 600;
        }
        
        .login-form .form-group label i {
            margin-<?php echo $langDir === 'rtl' ? 'left' : 'right'; ?>: 8px;
            color: #1a4a7a;
        }
        
        .input-wrapper {
            position: relative;
            display: flex;
            align-items: center;
            background: #f8fafc;
            border: 2px solid #e2e8f0;
            border-radius: 12px;
            transition: all 0.3s ease;
        }
        
        .input-wrapper:focus-within {
            border-color: #0a2540;
            box-shadow: 0 0 0 4px rgba(10, 37, 64, 0.06);
            background: #ffffff;
        }
        
        .input-wrapper input {
            width: 100%;
            padding: 14px 16px;
            border: none;
            background: transparent;
            font-family: 'Vazir', sans-serif;
            font-size: 0.95rem;
            color: #0a2540;
            outline: none;
            direction: <?php echo $langDir === 'rtl' ? 'rtl' : 'ltr'; ?>;
        }
        
        .input-wrapper input::placeholder {
            color: #94a3b8;
            font-family: 'Vazir', sans-serif;
        }
        
        /* ===== Toggle Password ===== */
        .toggle-password {
            position: absolute;
            <?php echo $langDir === 'rtl' ? 'left: 14px;' : 'right: 14px;'; ?>
            background: none;
            border: none;
            color: #94a3b8;
            cursor: pointer;
            padding: 6px;
            font-size: 1rem;
            transition: all 0.3s ease;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        .toggle-password:hover {
            color: #0a2540;
        }
        
        /* ============================================
                   BUTTON
                ============================================ */
        .btn-login {
            width: 100%;
            padding: 14px;
            background: linear-gradient(135deg, #0a2540, #1a4a7a);
            color: #ffffff;
            border: none;
            border-radius: 12px;
            font-family: 'Vazir', sans-serif;
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            margin-top: 4px;
            box-shadow: 0 4px 16px rgba(10, 37, 64, 0.15);
        }
        
        .btn-login:hover {
            transform: translateY(-3px);
            box-shadow: 0 8px 30px rgba(10, 37, 64, 0.25);
        }
        
        .btn-login:active {
            transform: translateY(0) scale(0.98);
        }
        
        /* ============================================
                   HINT
                ============================================ */
        .login-hint {
            text-align: center;
            margin-top: 16px;
            font-size: 0.75rem;
            color: #6b7d9a;
        }
        
        .login-hint .key {
            display: inline-block;
            background: #f1f4f9;
            padding: 2px 14px;
            border-radius: 30px;
            font-weight: 700;
            color: #0a2540;
            font-family: monospace;
            font-size: 0.85rem;
        }
        
        /* ============================================
                   FOOTER
                ============================================ */
        .login-footer {
            text-align: center;
            margin-top: 24px;
            padding-top: 16px;
            border-top: 1px solid #f1f4f9;
            font-size: 0.7rem;
            color: #6b7d9a;
            display: flex;
            justify-content: center;
            align-items: center;
            gap: 12px;
            flex-wrap: wrap;
        }
        
        .login-footer a {
            color: #0a2540;
            text-decoration: none;
            font-weight: 500;
            transition: color 0.3s ease;
            display: inline-flex;
            align-items: center;
            gap: 4px;
        }
        
        .login-footer a:hover {
            color: #1a4a7a;
            text-decoration: underline;
        }
        
        .login-footer .version {
            background: #f1f4f9;
            padding: 2px 12px;
            border-radius: 30px;
            font-size: 0.6rem;
            color: #6b7d9a;
        }
        
        /* ============================================
                   DARK MODE
                ============================================ */
        @media (prefers-color-scheme: dark) {
            body {
                background: linear-gradient(135deg, #0d1b2a 0%, #1a2d45 100%);
            }
            
            .login-container {
                background: #1a2d45;
                border-color: rgba(60, 90, 130, 0.2);
                box-shadow: 0 20px 60px rgba(0, 0, 0, 0.4);
            }
            
            .login-logo h1 {
                color: #e8edf5;
            }
            
            .login-logo p {
                color: #8aabca;
            }
            
            .lang-btn {
                border-color: #2d4a6a;
                background: #1a2d45;
            }
            
            .lang-btn:hover {
                border-color: #4a8aaa;
                background: #243b58;
            }
            
            .lang-btn.active {
                border-color: #4a8aaa;
                background: #1a3a5a;
            }
            
            .login-form .form-group label {
                color: #b0c4db;
            }
            
            .input-wrapper {
                background: #1a2d45;
                border-color: #2d4a6a;
            }
            
            .input-wrapper:focus-within {
                border-color: #4a8aaa;
                background: #1a2d45;
                box-shadow: 0 0 0 4px rgba(74, 138, 170, 0.1);
            }
            
            .input-wrapper input {
                color: #e8edf5;
            }
            
            .input-wrapper input::placeholder {
                color: #5a7a9a;
            }
            
            .toggle-password {
                color: #5a7a9a;
            }
            
            .toggle-password:hover {
                color: #e8edf5;
            }
            
            .btn-login {
                background: linear-gradient(135deg, #1a3a5a, #2a5a7a);
                box-shadow: 0 4px 16px rgba(26, 58, 90, 0.3);
            }
            
            .btn-login:hover {
                box-shadow: 0 8px 30px rgba(26, 58, 90, 0.5);
            }
            
            .login-hint {
                color: #8aabca;
            }
            
            .login-hint .key {
                background: #1a2d45;
                color: #e8edf5;
                border: 1px solid #2d4a6a;
            }
            
            .login-footer {
                border-top-color: #2d4a6a;
                color: #6b8aaa;
            }
            
            .login-footer a {
                color: #7fdb9a;
            }
            
            .login-footer a:hover {
                color: #a8e6c8;
            }
            
            .login-footer .version {
                background: #1a2d45;
                color: #6b8aaa;
            }
            
            .login-error {
                background: rgba(220, 38, 38, 0.1);
                color: #f87171;
                border-right-color: #dc2626;
            }
        }
        
        /* ============================================
                   RESPONSIVE
                ============================================ */
        @media (max-width: 480px) {
            .login-container {
                padding: 28px 20px 24px;
                border-radius: 20px;
            }
            
            .lang-btn {
                width: 34px;
                height: 34px;
                font-size: 1rem;
            }
            
            .login-logo .icon-box {
                width: 60px;
                height: 60px;
                border-radius: 18px;
            }
            
            .login-logo .icon-box i {
                font-size: 1.8rem;
            }
            
            .login-logo h1 {
                font-size: 1.3rem;
            }
            
            .input-wrapper input {
                padding: 12px 14px;
                font-size: 0.9rem;
            }
            
            .btn-login {
                padding: 12px;
                font-size: 0.95rem;
            }
            
            .login-footer {
                flex-direction: column;
                gap: 4px;
            }
        }
        
        @media (max-width: 360px) {
            .login-container {
                padding: 20px 14px 18px;
                border-radius: 16px;
            }
            
            .lang-btn {
                width: 30px;
                height: 30px;
                font-size: 0.85rem;
            }
            
            .login-logo .icon-box {
                width: 50px;
                height: 50px;
                border-radius: 14px;
            }
            
            .login-logo .icon-box i {
                font-size: 1.5rem;
            }
            
            .login-logo h1 {
                font-size: 1.1rem;
            }
        }
    </style>
</head>
<body>
    
    <div class="login-container">
        
        <!-- ===== Language Switcher - فقط پرچم ===== -->
        <div class="lang-switcher">
            <a href="<?php echo switchLang('fa'); ?>" class="lang-btn <?php echo $langCode === 'fa' ? 'active' : ''; ?>" title="فارسی">
                🇮🇷
            </a>
            <a href="<?php echo switchLang('en'); ?>" class="lang-btn <?php echo $langCode === 'en' ? 'active' : ''; ?>" title="English">
                🇬🇧
            </a>
            <a href="<?php echo switchLang('ar'); ?>" class="lang-btn <?php echo $langCode === 'ar' ? 'active' : ''; ?>" title="العربية">
                🇸🇦
            </a>
            <a href="<?php echo switchLang('tr'); ?>" class="lang-btn <?php echo $langCode === 'tr' ? 'active' : ''; ?>" title="Türkçe">
                🇹🇷
            </a>
            <a href="<?php echo switchLang('es'); ?>" class="lang-btn <?php echo $langCode === 'es' ? 'active' : ''; ?>" title="Español">
                🇪🇸
            </a>
        </div>
        
        <!-- ===== Logo ===== -->
        <div class="login-logo">
            <div class="icon-box">
                <i class="fas fa-shield-alt"></i>
            </div>
            <h1><?php echo __('admin_login_title', 'پنل مدیریت'); ?></h1>
            <p><?php echo __('admin_login_subtitle', 'ورود به بخش مدیریت سایت'); ?></p>
        </div>
        
        <!-- ===== Error ===== -->
        <?php if ($error): ?>
        <div class="login-error">
            <i class="fas fa-exclamation-circle"></i>
            <span><?php echo $error; ?></span>
        </div>
        <?php endif; ?>
        
        <!-- ===== Form ===== -->
        <form method="POST" class="login-form">
            <input type="hidden" name="lang" value="<?php echo $langCode; ?>">
            
            <div class="form-group">
                <label><i class="fas fa-user"></i> <?php echo __('username', 'نام کاربری'); ?></label>
                <div class="input-wrapper">
                    <input type="text" name="username" placeholder="<?php echo __('username_placeholder', 'نام کاربری را وارد کنید'); ?>" required autofocus>
                </div>
            </div>
            
            <div class="form-group">
                <label><i class="fas fa-lock"></i> <?php echo __('password', 'رمز عبور'); ?></label>
                <div class="input-wrapper">
                    <input type="password" name="password" id="passwordInput" placeholder="<?php echo __('password_placeholder', 'رمز عبور را وارد کنید'); ?>" required>
                    <button type="button" class="toggle-password" id="togglePassword">
                        <i class="fas fa-eye" id="eyeIcon"></i>
                    </button>
                </div>
            </div>
            
            <button type="submit" class="btn-login">
                <i class="fas fa-sign-in-alt"></i>
                <?php echo __('admin_login_button', 'ورود به پنل'); ?>
            </button>
        </form>
        
        <!-- ===== Hint ===== -->
        <div class="login-hint">
            🔑 <?php echo __('admin_login_hint', 'رمز پیش‌فرض'); ?>: <span class="key">Admin@2026</span>
        </div>
        
        <!-- ===== Footer ===== -->
        <div class="login-footer">
            <a href="https://mozili.ir/arz/">
                <i class="fas fa-arrow-<?php echo $langDir === 'rtl' ? 'right' : 'left'; ?>"></i>
                <?php echo __('back_to_site', 'بازگشت به سایت'); ?>
            </a>
            <span class="version"><?php echo __('version', 'نسخه'); ?> 3.0</span>
        </div>
        
    </div>
    
    <!-- ============================================
               SCRIPTS
            ============================================ -->
    <script>
        // ===== Toggle Password =====
        const toggleBtn = document.getElementById('togglePassword');
        const passwordInput = document.getElementById('passwordInput');
        const eyeIcon = document.getElementById('eyeIcon');
        let isVisible = false;
        
        toggleBtn.addEventListener('click', function(e) {
            e.preventDefault();
            isVisible = !isVisible;
            passwordInput.type = isVisible ? 'text' : 'password';
            eyeIcon.className = 'fas ' + (isVisible ? 'fa-eye-slash' : 'fa-eye');
        });
        
        // ===== Keyboard Shortcut =====
        document.addEventListener('keydown', function(e) {
            if (e.ctrlKey && e.shiftKey && (e.key === 'p' || e.key === 'P')) {
                e.preventDefault();
                toggleBtn.click();
            }
        });
        
        console.log('🔑 <?php echo __('admin_login_title', 'پنل مدیریت'); ?>');
        console.log('👤 <?php echo __('username', 'نام کاربری'); ?>: admin');
        console.log('🔑 <?php echo __('password', 'رمز عبور'); ?>: Admin@2026');
        console.log('🌐 5 زبان: فارسی, English, العربية, Türkçe, Español');
    </script>
    
</body>
</html>