<?php
session_start();
require_once 'includes/config.php';
require_once 'includes/user_functions.php';

// ===== پردازش ورود =====
if (isset($_POST['login'])) {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    
    $user = getUserByUsername($username);
    if ($user && password_verify($password, $user['password'])) {
        if ($user['status'] === 'inactive') {
            $_SESSION['login_error'] = 'حساب کاربری شما غیرفعال است!';
            header('Location: index.php');
            exit;
        }
        
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['username'] = $user['username'];
        $_SESSION['last_activity'] = time();
        
        // به‌روزرسانی زمان آخرین ورود
        $user['last_login'] = date('Y-m-d H:i:s');
        updateUser($user['id'], ['last_login' => $user['last_login']]);
        
        // ذخیره تنظیمات کاربر در کوکی
        if (!empty($user['language'])) setcookie('language', $user['language'], time() + 31536000, '/');
        if (!empty($user['theme'])) setcookie('theme', $user['theme'], time() + 31536000, '/');
        if (!empty($user['unit'])) setcookie('unit', $user['unit'], time() + 31536000, '/');
        if (!empty($user['favorite_currency'])) setcookie('favorite_currency', $user['favorite_currency'], time() + 31536000, '/');
        
        header('Location: index.php');
        exit;
    } else {
        $_SESSION['login_error'] = 'نام کاربری یا رمز عبور اشتباه است!';
        header('Location: index.php');
        exit;
    }
}

// ===== پردازش ثبت‌نام =====
if (isset($_POST['register'])) {
    $username = trim($_POST['reg_username'] ?? '');
    $email = trim($_POST['reg_email'] ?? '');
    $fullname = trim($_POST['reg_fullname'] ?? '');
    $password = $_POST['reg_password'] ?? '';
    $confirm = $_POST['reg_confirm'] ?? '';
    
    // اعتبارسنجی
    $errors = [];
    if (empty($username)) $errors[] = 'نام کاربری الزامی است';
    if (strlen($username) < 3) $errors[] = 'نام کاربری حداقل ۳ کاراکتر باشد';
    if (!preg_match('/^[a-zA-Z0-9_]+$/', $username)) $errors[] = 'نام کاربری فقط شامل حروف انگلیسی، اعداد و زیرخط باشد';
    if (empty($email)) $errors[] = 'ایمیل الزامی است';
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = 'ایمیل نامعتبر است';
    if (empty($password)) $errors[] = 'رمز عبور الزامی است';
    if (strlen($password) < 6) $errors[] = 'رمز عبور حداقل ۶ کاراکتر باشد';
    if ($password !== $confirm) $errors[] = 'رمز عبور با تکرار آن مطابقت ندارد';
    if (getUserByUsername($username)) $errors[] = 'نام کاربری قبلاً ثبت شده است';
    if (getUserByEmail($email)) $errors[] = 'ایمیل قبلاً ثبت شده است';
    
    if (empty($errors)) {
        $result = addUser($username, $password, $email, $fullname, 'user', 'active');
        if ($result['success']) {
            $_SESSION['register_success'] = 'ثبت‌نام با موفقیت انجام شد! لطفاً وارد شوید.';
            header('Location: index.php');
            exit;
        } else {
            $_SESSION['register_error'] = $result['message'];
            header('Location: index.php');
            exit;
        }
    } else {
        $_SESSION['register_error'] = implode(' | ', $errors);
        header('Location: index.php');
        exit;
    }
}

// ===== پردازش تغییر ارز دلخواه =====
if (isset($_POST['action']) && $_POST['action'] === 'toggle_favorite') {
    session_start();
    if (!isset($_SESSION['user_id'])) {
        echo json_encode(['success' => false, 'message' => 'Not logged in']);
        exit;
    }
    
    $code = $_POST['code'] ?? '';
    $user = getUserById($_SESSION['user_id']);
    if ($user) {
        $currentFav = $user['favorite_currency'] ?? '';
        $newFav = ($currentFav === $code) ? null : $code;
        updateUser($user['id'], ['favorite_currency' => $newFav]);
        echo json_encode(['success' => true, 'favorite' => $newFav]);
    } else {
        echo json_encode(['success' => false, 'message' => 'User not found']);
    }
    exit;
}
?>