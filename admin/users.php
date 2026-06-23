<?php
// ===== نمایش خطاها =====
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
    $currentAction = $_GET['action'] ?? 'list';
    $currentId = isset($_GET['id']) ? '&id=' . $_GET['id'] : '';
    return '?lang=' . $lang . '&action=' . $currentAction . $currentId;
}

// ============================================
// ===== بررسی لاگین =====
// ============================================

if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: login.php?lang=' . $langCode);
    exit;
}

// فقط ادمین اصلی می‌تواند کاربران را مدیریت کند
$currentUser = getUserById($_SESSION['admin_user_id'] ?? 1);
if (!$currentUser || $currentUser['role'] !== 'admin') {
    header('Location: index.php?error=access_denied&lang=' . $langCode);
    exit;
}

// ============================================
// ===== تشخیص تم =====
// ============================================

$theme = isset($_COOKIE['admin_theme']) ? $_COOKIE['admin_theme'] : 'light';
if (isset($_GET['theme'])) {
    $theme = $_GET['theme'] === 'dark' ? 'dark' : 'light';
    setcookie('admin_theme', $theme, time() + 31536000, '/');
    header('Location: ?lang=' . $langCode . '&action=' . ($_GET['action'] ?? 'list') . (isset($_GET['id']) ? '&id=' . $_GET['id'] : ''));
    exit;
}

$message = '';
$messageType = '';
$action = $_GET['action'] ?? 'list';
$userId = $_GET['id'] ?? 0;

// ===== پردازش فرم‌ها =====

// 1. افزودن کاربر جدید
if (isset($_POST['add_user'])) {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    $email = trim($_POST['email'] ?? '');
    $fullname = trim($_POST['fullname'] ?? '');
    $role = $_POST['role'] ?? 'user';
    $status = $_POST['status'] ?? 'active';
    
    if (empty($username) || empty($password) || empty($email)) {
        $message = '❌ ' . __('error_required_fields', 'فیلدهای نام کاربری، رمز عبور و ایمیل الزامی هستند!');
        $messageType = 'error';
    } else {
        $result = addUser($username, $password, $email, $fullname, $role, $status);
        $message = $result['success'] ? __('user_added_success', 'کاربر با موفقیت اضافه شد!') : $result['message'];
        $messageType = $result['success'] ? 'success' : 'error';
        
        if ($result['success']) {
            logUserActivity($_SESSION['admin_user_id'] ?? 1, 'add_user', "کاربر {$username} اضافه شد");
            header('Location: users.php?lang=' . $langCode . '&message=' . urlencode($message) . '&type=success');
            exit;
        }
    }
}

// 2. ویرایش کاربر
if (isset($_POST['edit_user'])) {
    $id = (int) ($_POST['user_id'] ?? 0);
    $data = [
        'email' => trim($_POST['email'] ?? ''),
        'fullname' => trim($_POST['fullname'] ?? ''),
        'role' => $_POST['role'] ?? 'user',
        'status' => $_POST['status'] ?? 'active',
        'bio' => trim($_POST['bio'] ?? '')
    ];
    
    if (!empty($_POST['new_password'])) {
        $data['password'] = $_POST['new_password'];
    }
    
    $result = updateUser($id, $data);
    $message = $result['success'] ? __('user_updated_success', 'کاربر با موفقیت به‌روزرسانی شد!') : $result['message'];
    $messageType = $result['success'] ? 'success' : 'error';
    
    if ($result['success']) {
        logUserActivity($_SESSION['admin_user_id'] ?? 1, 'edit_user', "کاربر با ID $id ویرایش شد");
        header('Location: users.php?lang=' . $langCode . '&message=' . urlencode($message) . '&type=success');
        exit;
    }
}

// 3. حذف کاربر
if (isset($_GET['delete']) && isset($_GET['id'])) {
    $id = (int) $_GET['id'];
    $result = deleteUser($id);
    $message = $result['success'] ? __('user_deleted_success', 'کاربر با موفقیت حذف شد!') : $result['message'];
    $messageType = $result['success'] ? 'success' : 'error';
    
    if ($result['success']) {
        logUserActivity($_SESSION['admin_user_id'] ?? 1, 'delete_user', "کاربر با ID $id حذف شد");
        header('Location: users.php?lang=' . $langCode . '&message=' . urlencode($result['message']) . '&type=success');
        exit;
    }
}

// 4. تغییر وضعیت کاربر
if (isset($_GET['toggle']) && isset($_GET['id'])) {
    $id = (int) $_GET['id'];
    if ($id != 1) {
        $result = toggleUserStatus($id);
        $message = $result['success'] ? __('user_status_changed_success', 'وضعیت کاربر با موفقیت تغییر کرد!') : $result['message'];
        $messageType = $result['success'] ? 'success' : 'error';
        
        if ($result['success']) {
            logUserActivity($_SESSION['admin_user_id'] ?? 1, 'toggle_user', "وضعیت کاربر با ID $id تغییر کرد");
            header('Location: users.php?lang=' . $langCode . '&message=' . urlencode($message) . '&type=success');
            exit;
        }
    } else {
        $message = '❌ ' . __('error_cannot_toggle_admin', 'امکان تغییر وضعیت ادمین اصلی وجود ندارد!');
        $messageType = 'error';
        header('Location: users.php?lang=' . $langCode . '&message=' . urlencode($message) . '&type=error');
        exit;
    }
}

// 5. نمایش فرم ویرایش
$editUser = null;
if ($action === 'edit' && $userId > 0) {
    $editUser = getUserById($userId);
}

// بارگذاری لیست کاربران
$usersData = loadUsers();
$users = $usersData['users'] ?? [];

// دریافت پیام از URL
if (isset($_GET['message'])) {
    $message = urldecode($_GET['message']);
    $messageType = $_GET['type'] ?? 'info';
}
?>
<!DOCTYPE html>
<html lang="fa" dir="<?php echo $langDir; ?>" data-theme="<?php echo $theme; ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo __('users', 'مدیریت کاربران'); ?> | <?php echo __('admin_panel', 'پنل ادمین'); ?></title>
    
    <style>
        @font-face {
            font-family: 'Vazir';
            src: url('../fonts/Vazir.ttf') format('truetype');
            font-weight: normal;
        }
        @font-face {
            font-family: 'Vazir';
            src: url('../fonts/Vazir-Bold.ttf') format('truetype');
            font-weight: bold;
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
            font-family: 'Vazir', 'Segoe UI', sans-serif;
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
            border-bottom: 1px solid rgba(255, 255, 255, 0.06);
            margin-bottom: 15px;
            text-align: center;
        }
        
        .sidebar-brand .icon {
            display: inline-block;
            background: rgba(255, 255, 255, 0.1);
            padding: 12px 14px;
            border-radius: 16px;
            margin-bottom: 8px;
        }
        
        .sidebar-brand .icon i { font-size: 1.8rem; color: #7fdb9a; }
        .sidebar-brand h2 { font-size: 1.1rem; font-weight: 700; color: white; }
        .sidebar-brand small { font-size: 0.65rem; color: #8aabca; display: block; margin-top: 2px; }
        
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
        
        .sidebar-menu li a i { width: 20px; text-align: center; font-size: 0.95rem; }
        .sidebar-menu li a .badge {
            margin-right: auto;
            background: rgba(255, 255, 255, 0.08);
            padding: 1px 10px;
            border-radius: 30px;
            font-size: 0.6rem;
            color: var(--sidebar-text);
        }
        
        .sidebar-menu .divider {
            height: 1px;
            background: rgba(255, 255, 255, 0.06);
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
        .btn-primary:hover { box-shadow: 0 6px 20px rgba(10, 37, 64, 0.2); }
        .btn-success { background: var(--success); color: white; }
        .btn-success:hover { box-shadow: 0 6px 20px rgba(26, 122, 74, 0.2); }
        .btn-warning { background: var(--warning); color: #0a2540; }
        .btn-warning:hover { box-shadow: 0 6px 20px rgba(255, 193, 7, 0.2); }
        .btn-danger { background: var(--danger); color: white; }
        .btn-danger:hover { box-shadow: 0 6px 20px rgba(192, 57, 43, 0.2); }
        .btn-secondary { background: var(--badge-bg); color: var(--text-primary); border: 1px solid var(--border-color); }
        .btn-secondary:hover { background: var(--hover-bg); }
        .btn-sm { padding: 4px 12px; font-size: 0.7rem; }
        
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
                   TABLE
                ============================================ */
        .table-wrapper {
            overflow-x: auto;
        }
        
        table {
            width: 100%;
            border-collapse: collapse;
            font-size: 0.85rem;
        }
        
        thead {
            background: var(--badge-bg);
        }
        
        thead th {
            padding: 10px 12px;
            text-align: <?php echo $langDir === 'rtl' ? 'right' : 'left'; ?>;
            font-weight: 600;
            color: var(--text-primary);
            border-bottom: 2px solid var(--border-color);
            white-space: nowrap;
            font-size: 0.75rem;
        }
        
        tbody tr {
            border-bottom: 1px solid var(--border-color);
            transition: background 0.15s ease;
        }
        
        tbody tr:hover { background: var(--hover-bg); }
        tbody td { padding: 8px 12px; vertical-align: middle; color: var(--text-secondary); }
        
        .status-badge {
            display: inline-block;
            padding: 2px 12px;
            border-radius: 30px;
            font-size: 0.65rem;
            font-weight: 600;
        }
        .status-active { background: rgba(46, 204, 113, 0.15); color: var(--success); }
        .status-inactive { background: rgba(231, 76, 60, 0.15); color: var(--danger); }
        
        .role-badge {
            display: inline-block;
            padding: 2px 10px;
            border-radius: 30px;
            font-size: 0.65rem;
            font-weight: 600;
        }
        .role-admin { background: rgba(231, 76, 60, 0.15); color: var(--danger); }
        .role-editor { background: rgba(243, 156, 18, 0.15); color: var(--warning); }
        .role-user { background: rgba(46, 204, 113, 0.15); color: var(--success); }
        .role-viewer { background: rgba(108, 117, 125, 0.15); color: #6c757d; }
        
        .actions {
            display: flex;
            gap: 4px;
            flex-wrap: wrap;
        }
        
        .user-count {
            font-size: 0.75rem;
            color: var(--text-muted);
            margin-<?php echo $langDir === 'rtl' ? 'right' : 'left'; ?>: auto;
        }
        
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
        }
        
        @media (max-width: 600px) {
            .top-header {
                flex-direction: column;
                align-items: stretch;
                gap: 10px;
            }
            .top-header .right { justify-content: space-between; }
            .card { padding: 16px 14px; }
            table { font-size: 0.75rem; }
            thead th, tbody td { padding: 6px 8px; }
            .actions { flex-direction: column; }
            .btn-sm { width: 100%; justify-content: center; }
        }
        
        /* ============================================
                   SCROLLBAR
                ============================================ */
        .sidebar::-webkit-scrollbar { width: 4px; }
        .sidebar::-webkit-scrollbar-track { background: rgba(255, 255, 255, 0.02); }
        .sidebar::-webkit-scrollbar-thumb { background: rgba(255, 255, 255, 0.15); border-radius: 10px; }
        
        ::-webkit-scrollbar { width: 6px; }
        ::-webkit-scrollbar-track { background: var(--bg-primary); }
        ::-webkit-scrollbar-thumb { background: var(--text-muted); border-radius: 10px; }
        ::-webkit-scrollbar-thumb:hover { background: var(--text-secondary); }
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
            <div class="icon"><i class="fas fa-coins"></i></div>
            <h2><?php echo __('site_title', 'نرخ ارز'); ?></h2>
            <small><?php echo __('admin_panel', 'پنل مدیریت'); ?></small>
        </div>
        
        <ul class="sidebar-menu">
            <li><a href="index.php?tab=dashboard&lang=<?php echo $langCode; ?>"><i class="fas fa-chart-pie"></i> <?php echo __('dashboard', 'داشبورد'); ?></a></li>
            <li><a href="index.php?tab=general&lang=<?php echo $langCode; ?>"><i class="fas fa-globe"></i> <?php echo __('general_settings', 'تنظیمات عمومی'); ?></a></li>
            <li><a href="index.php?tab=features&lang=<?php echo $langCode; ?>"><i class="fas fa-cubes"></i> <?php echo __('features', 'ویژگی‌ها'); ?></a></li>
            <li><a href="index.php?tab=currencies&lang=<?php echo $langCode; ?>"><i class="fas fa-coins"></i> <?php echo __('currencies', 'ارزها'); ?></a></li>
            <li><a href="index.php?tab=languages&lang=<?php echo $langCode; ?>"><i class="fas fa-language"></i> <?php echo __('languages', 'زبان‌ها'); ?></a></li>
            <li><a href="users.php?lang=<?php echo $langCode; ?>" class="active"><i class="fas fa-users"></i> <?php echo __('users', 'کاربران'); ?> <span class="badge"><?php echo count($users); ?></span></a></li>
            <li><a href="index.php?tab=seo&lang=<?php echo $langCode; ?>"><i class="fas fa-search"></i> <?php echo __('seo', 'SEO'); ?></a></li>
            <li><a href="index.php?tab=social&lang=<?php echo $langCode; ?>"><i class="fas fa-share-alt"></i> <?php echo __('social_networks', 'شبکه‌های اجتماعی'); ?></a></li>
            <li><a href="index.php?tab=pwa&lang=<?php echo $langCode; ?>"><i class="fas fa-mobile-alt"></i> <?php echo __('pwa_settings', 'PWA'); ?></a></li>
            <li class="divider"></li>
            <li><a href="index.php?tab=cache&lang=<?php echo $langCode; ?>"><i class="fas fa-broom"></i> <?php echo __('cache', 'کش'); ?></a></li>
            <li><a href="index.php?tab=backup&lang=<?php echo $langCode; ?>"><i class="fas fa-database"></i> <?php echo __('backup', 'بکاپ'); ?></a></li>
            <li><a href="index.php?tab=security&lang=<?php echo $langCode; ?>"><i class="fas fa-shield-alt"></i> <?php echo __('security', 'امنیت'); ?></a></li>
            <li><a href="index.php?tab=notifications&lang=<?php echo $langCode; ?>"><i class="fas fa-bell"></i> <?php echo __('notifications', 'نوتیفیکیشن'); ?></a></li>
            <li class="divider"></li>
            <li><a href="https://mozili.ir/arz/" target="_blank"><i class="fas fa-globe"></i> <?php echo __('back_to_site', 'مشاهده سایت'); ?></a></li>
            <li><a href="logout.php?lang=<?php echo $langCode; ?>" style="color:#e74c3c;"><i class="fas fa-sign-out-alt"></i> <?php echo __('logout', 'خروج'); ?></a></li>
        </ul>
    </aside>
    
    <!-- ============================================
               MAIN CONTENT
            ============================================ -->
    <main class="main-content">
        
        <!-- ============================================
                   HEADER
                ============================================ -->
        <header class="top-header">
            <div class="left">
                <button class="hamburger" onclick="toggleSidebar()">
                    <i class="fas fa-bars"></i>
                </button>
                <h1>
                    <i class="fas fa-users"></i>
                    <?php echo __('user_management', 'مدیریت کاربران'); ?>
                    <span class="user-count">(<?php echo count($users); ?> <?php echo __('user', 'کاربر'); ?>)</span>
                </h1>
            </div>
            <div class="right">
                <!-- ===== Theme Toggle ===== -->
                <a href="?lang=<?php echo $langCode; ?>&action=<?php echo $action; ?><?php echo isset($_GET['id']) ? '&id=' . $_GET['id'] : ''; ?>&theme=<?php echo $theme === 'light' ? 'dark' : 'light'; ?>" class="theme-toggle" title="<?php echo $theme === 'light' ? __('theme_dark', 'تم تاریک') : __('theme_light', 'تم روشن'); ?>">
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
                
                <span class="header-btn">
                    <span class="avatar"><?php echo strtoupper(substr($currentUser['fullname'] ?? $currentUser['username'] ?? 'A', 0, 1)); ?></span>
                    <?php echo htmlspecialchars($currentUser['fullname'] ?? $currentUser['username'] ?? 'admin'); ?>
                </span>
            </div>
        </header>
        
        <!-- ===== پیام ===== -->
        <?php if ($message): ?>
        <div class="message <?php echo $messageType; ?>">
            <i class="fas fa-<?php echo $messageType === 'success' ? 'check-circle' : ($messageType === 'error' ? 'exclamation-circle' : 'info-circle'); ?>"></i>
            <?php echo $message; ?>
        </div>
        <?php endif; ?>
        
        <!-- ===== دکمه افزودن کاربر ===== -->
        <div style="margin-bottom:16px;display:flex;gap:10px;flex-wrap:wrap;">
            <a href="?action=add&lang=<?php echo $langCode; ?>" class="btn btn-success">
                <i class="fas fa-user-plus"></i> <?php echo __('add_new_user', 'افزودن کاربر جدید'); ?>
            </a>
            <a href="users.php?lang=<?php echo $langCode; ?>" class="btn btn-secondary">
                <i class="fas fa-list"></i> <?php echo __('user_list', 'لیست کاربران'); ?>
            </a>
        </div>
        
        <?php if ($action === 'add' || $action === 'edit'): ?>
        <!-- ===== فرم افزودن/ویرایش کاربر ===== -->
        <div class="card">
            <h2>
                <i class="fas fa-<?php echo $action === 'add' ? 'user-plus' : 'user-edit'; ?>"></i>
                <?php echo $action === 'add' ? __('add_new_user', 'افزودن کاربر جدید') : __('edit_user', 'ویرایش کاربر'); ?>
            </h2>
            
            <form method="POST">
                <input type="hidden" name="lang" value="<?php echo $langCode; ?>">
                <?php if ($action === 'edit' && $editUser): ?>
                <input type="hidden" name="user_id" value="<?php echo $editUser['id']; ?>">
                <?php endif; ?>
                
                <div class="form-row">
                    <div class="form-group">
                        <label><i class="fas fa-user"></i> <?php echo __('username', 'نام کاربری'); ?></label>
                        <input type="text" name="username" 
                               value="<?php echo $action === 'edit' && $editUser ? htmlspecialchars($editUser['username']) : ''; ?>" 
                               <?php echo $action === 'edit' ? 'readonly style="background:var(--badge-bg);"' : 'required'; ?>>
                        <?php if ($action === 'edit'): ?>
                        <div class="hint">⚠️ <?php echo __('username_readonly', 'نام کاربری قابل تغییر نیست'); ?></div>
                        <?php endif; ?>
                    </div>
                    <div class="form-group">
                        <label><i class="fas fa-envelope"></i> <?php echo __('email', 'ایمیل'); ?></label>
                        <input type="email" name="email" 
                               value="<?php echo $action === 'edit' && $editUser ? htmlspecialchars($editUser['email']) : ''; ?>" 
                               required>
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label><i class="fas fa-user-tag"></i> <?php echo __('fullname', 'نام کامل'); ?></label>
                        <input type="text" name="fullname" 
                               value="<?php echo $action === 'edit' && $editUser ? htmlspecialchars($editUser['fullname']) : ''; ?>">
                    </div>
                    <div class="form-group">
                        <label><i class="fas fa-lock"></i> <?php echo $action === 'edit' ? __('new_password_optional', 'رمز عبور جدید (اختیاری)') : __('password', 'رمز عبور'); ?></label>
                        <input type="password" name="<?php echo $action === 'edit' ? 'new_password' : 'password'; ?>" 
                               <?php echo $action === 'add' ? 'required' : ''; ?>
                               placeholder="<?php echo $action === 'edit' ? __('enter_to_change', 'برای تغییر وارد کنید') : __('min_6_chars', 'حداقل ۶ کاراکتر'); ?>">
                        <?php if ($action === 'edit'): ?>
                        <div class="hint"><?php echo __('password_change_hint', 'برای تغییر رمز عبور، مقدار جدید را وارد کنید. در غیر این صورت خالی بگذارید.'); ?></div>
                        <?php endif; ?>
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label><i class="fas fa-user-shield"></i> <?php echo __('role', 'نقش کاربری'); ?></label>
                        <select name="role">
                            <option value="admin" <?php echo ($action === 'edit' && $editUser && $editUser['role'] === 'admin') ? 'selected' : ''; ?>><?php echo __('admin', 'مدیر'); ?></option>
                            <option value="editor" <?php echo ($action === 'edit' && $editUser && $editUser['role'] === 'editor') ? 'selected' : ''; ?>><?php echo __('editor', 'نویسنده'); ?></option>
                            <option value="user" <?php echo ($action === 'edit' && $editUser && $editUser['role'] === 'user') ? 'selected' : ''; ?>><?php echo __('user', 'کاربر عادی'); ?></option>
                            <option value="viewer" <?php echo ($action === 'edit' && $editUser && $editUser['role'] === 'viewer') ? 'selected' : ''; ?>><?php echo __('viewer', 'بازدیدکننده'); ?></option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label><i class="fas fa-circle"></i> <?php echo __('status', 'وضعیت'); ?></label>
                        <select name="status">
                            <option value="active" <?php echo ($action === 'edit' && $editUser && $editUser['status'] === 'active') ? 'selected' : ''; ?>><?php echo __('active', 'فعال'); ?></option>
                            <option value="inactive" <?php echo ($action === 'edit' && $editUser && $editUser['status'] === 'inactive') ? 'selected' : ''; ?>><?php echo __('inactive', 'غیرفعال'); ?></option>
                        </select>
                    </div>
                </div>
                
                <div class="form-group">
                    <label><i class="fas fa-info-circle"></i> <?php echo __('bio', 'بیوگرافی'); ?></label>
                    <textarea name="bio" rows="2" placeholder="<?php echo __('bio_placeholder', 'توضیحات درباره کاربر...'); ?>"><?php echo $action === 'edit' && $editUser ? htmlspecialchars($editUser['bio'] ?? '') : ''; ?></textarea>
                </div>
                
                <div style="display:flex;gap:10px;flex-wrap:wrap;">
                    <button type="submit" name="<?php echo $action === 'add' ? 'add_user' : 'edit_user'; ?>" class="btn btn-primary">
                        <i class="fas fa-save"></i>
                        <?php echo $action === 'add' ? __('add_user', 'افزودن کاربر') : __('save_changes', 'ذخیره تغییرات'); ?>
                    </button>
                    
                    <a href="users.php?lang=<?php echo $langCode; ?>" class="btn btn-secondary">
                        <i class="fas fa-arrow-<?php echo $langDir === 'rtl' ? 'right' : 'left'; ?>"></i> <?php echo __('back', 'بازگشت'); ?>
                    </a>
                </div>
            </form>
        </div>
        <?php else: ?>
        
        <!-- ===== لیست کاربران ===== -->
        <div class="card">
            <h2>
                <i class="fas fa-list"></i>
                <?php echo __('user_list', 'لیست کاربران'); ?>
                <span class="user-count">(<?php echo count($users); ?> <?php echo __('user', 'کاربر'); ?>)</span>
            </h2>
            
            <div class="table-wrapper">
                <table>
                    <thead>
                        <tr>
                            <th style="width:50px;">#</th>
                            <th><?php echo __('username', 'نام کاربری'); ?></th>
                            <th><?php echo __('fullname', 'نام کامل'); ?></th>
                            <th><?php echo __('email', 'ایمیل'); ?></th>
                            <th><?php echo __('role', 'نقش'); ?></th>
                            <th><?php echo __('status', 'وضعیت'); ?></th>
                            <th><?php echo __('created_at', 'تاریخ ثبت'); ?></th>
                            <th style="width:150px;"><?php echo __('actions', 'عملیات'); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($users)): ?>
                        <tr>
                            <td colspan="8" style="text-align:center;padding:30px;color:var(--text-muted);">
                                <i class="fas fa-user-slash" style="font-size:2rem;display:block;margin-bottom:10px;"></i>
                                <?php echo __('no_users', 'هیچ کاربری یافت نشد!'); ?>
                            </td>
                        </tr>
                        <?php else: ?>
                        <?php foreach ($users as $user): ?>
                        <tr>
                            <td><?php echo $user['id']; ?></td>
                            <td>
                                <strong><?php echo htmlspecialchars($user['username']); ?></strong>
                                <?php if ($user['id'] == 1): ?>
                                <span style="font-size:0.6rem;background:var(--warning);color:var(--text-primary);padding:1px 8px;border-radius:30px;"><?php echo __('main', 'اصلی'); ?></span>
                                <?php endif; ?>
                            </td>
                            <td><?php echo htmlspecialchars($user['fullname'] ?? '-'); ?></td>
                            <td style="direction:ltr;font-size:0.8rem;"><?php echo htmlspecialchars($user['email']); ?></td>
                            <td>
                                <span class="role-badge role-<?php echo $user['role']; ?>">
                                    <?php echo getUserRoleName($user['role']); ?>
                                </span>
                            </td>
                            <td>
                                <span class="status-badge status-<?php echo $user['status']; ?>">
                                    <?php echo $user['status'] === 'active' ? '✅ ' . __('active', 'فعال') : '⛔ ' . __('inactive', 'غیرفعال'); ?>
                                </span>
                            </td>
                            <td style="font-size:0.7rem;"><?php echo date('Y/m/d', strtotime($user['created_at'])); ?></td>
                            <td>
                                <div class="actions">
                                    <a href="?action=edit&id=<?php echo $user['id']; ?>&lang=<?php echo $langCode; ?>" class="btn btn-sm btn-primary">
                                        <i class="fas fa-edit"></i>
                                    </a>
                                    <?php if ($user['id'] != 1): ?>
                                    <a href="?toggle=1&id=<?php echo $user['id']; ?>&lang=<?php echo $langCode; ?>" class="btn btn-sm btn-warning" 
                                       onclick="return confirm('<?php echo __('toggle_user_confirm', 'آیا از تغییر وضعیت این کاربر مطمئن هستید؟'); ?>')">
                                        <i class="fas fa-<?php echo $user['status'] === 'active' ? 'pause' : 'play'; ?>"></i>
                                    </a>
                                    <a href="?delete=1&id=<?php echo $user['id']; ?>&lang=<?php echo $langCode; ?>" class="btn btn-sm btn-danger" 
                                       onclick="return confirm('<?php echo __('delete_user_confirm', 'آیا از حذف این کاربر مطمئن هستید؟ این عمل غیرقابل بازگشت است!'); ?>')">
                                        <i class="fas fa-trash"></i>
                                    </a>
                                    <?php else: ?>
                                    <span style="font-size:0.6rem;color:var(--text-muted);">🔒</span>
                                    <?php endif; ?>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
        <?php endif; ?>
        
        <!-- ===== فوتر ===== -->
        <div style="text-align:center;padding:20px 0;font-size:0.7rem;color:var(--text-muted);border-top:1px solid var(--border-color);margin-top:10px;">
            <i class="fas fa-copyright"></i> <?php echo date('Y'); ?> 
            <a href="https://mozili.ir/arz/" style="color:var(--text-primary);text-decoration:none;"><?php echo __('site_title', 'نرخ ارز مرجع'); ?></a>
            &bull; <?php echo __('version', 'نسخه'); ?> 3.0
        </div>
        
    </main>
    
    <script>
        // ============================================
        // ===== SIDEBAR =====
        // ============================================
        function toggleSidebar() {
            document.getElementById('sidebar').classList.toggle('open');
        }
        
        function closeSidebar() {
            document.getElementById('sidebar').classList.remove('open');
        }
        
        // بستن با کلیک خارج
        document.addEventListener('click', function(e) {
            const sidebar = document.getElementById('sidebar');
            const hamburger = document.querySelector('.hamburger');
            if (window.innerWidth <= 992) {
                if (!sidebar.contains(e.target) && !hamburger?.contains(e.target)) {
                    closeSidebar();
                }
            }
        });
        
        // ===== KEYBOARD SHORTCUTS =====
        document.addEventListener('keydown', function(e) {
            if (e.ctrlKey && e.shiftKey && (e.key === 's' || e.key === 'S')) {
                e.preventDefault();
                toggleSidebar();
            }
            if (e.key === 'Escape') {
                closeSidebar();
            }
        });
        
        // ===== AUTO HIDE MESSAGES =====
        setTimeout(() => {
            document.querySelectorAll('.message').forEach(el => {
                el.style.transition = 'opacity 0.5s ease';
                el.style.opacity = '0';
                setTimeout(() => el.remove(), 500);
            });
        }, 5000);
        
        console.log('🔑 <?php echo __('user_management', 'مدیریت کاربران'); ?>');
        console.log('👤 <?php echo __('user', 'کاربر'); ?>: <?php echo htmlspecialchars($currentUser['username'] ?? 'admin'); ?>');
        console.log('🌐 <?php echo __('language', 'زبان'); ?>: <?php echo $langCode; ?>');
        console.log('🎨 <?php echo __('theme', 'تم'); ?>: <?php echo $theme; ?>');
        console.log('📱 Ctrl+Shift+S برای سایدبار');
    </script>
    
</body>
</html>