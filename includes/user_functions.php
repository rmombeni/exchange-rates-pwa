<?php
// ===== توابع مدیریت کاربران =====

define('USERS_FILE', ROOT_PATH . '/config/users.json');

function loadUsers() {
    if (!file_exists(USERS_FILE)) {
        // ایجاد کاربر پیش‌فرض
        $defaultUsers = [
            'users' => [
                [
                    'id' => 1,
                    'username' => 'admin',
                    'password' => password_hash('Admin@2026', PASSWORD_DEFAULT),
                    'email' => 'admin@mozili.ir',
                    'fullname' => 'مدیر سایت',
                    'role' => 'admin',
                    'status' => 'active',
                    'created_at' => date('Y-m-d H:i:s'),
                    'last_login' => null,
                    'avatar' => null,
                    'bio' => 'مدیر اصلی سایت'
                ]
            ]
        ];
        file_put_contents(USERS_FILE, json_encode($defaultUsers, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
        return $defaultUsers;
    }
    
    $content = file_get_contents(USERS_FILE);
    return json_decode($content, true);
}

function saveUsers($data) {
    return file_put_contents(USERS_FILE, json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
}

function getUserById($id) {
    $data = loadUsers();
    foreach ($data['users'] as $user) {
        if ($user['id'] == $id) {
            return $user;
        }
    }
    return null;
}

function getUserByUsername($username) {
    $data = loadUsers();
    foreach ($data['users'] as $user) {
        if ($user['username'] === $username) {
            return $user;
        }
    }
    return null;
}

function getUserByEmail($email) {
    $data = loadUsers();
    foreach ($data['users'] as $user) {
        if ($user['email'] === $email) {
            return $user;
        }
    }
    return null;
}

function getNextUserId() {
    $data = loadUsers();
    $maxId = 0;
    foreach ($data['users'] as $user) {
        if ($user['id'] > $maxId) {
            $maxId = $user['id'];
        }
    }
    return $maxId + 1;
}

function addUser($username, $password, $email, $fullname, $role = 'user', $status = 'active') {
    $data = loadUsers();
    
    // بررسی تکراری نبودن
    if (getUserByUsername($username)) {
        return ['success' => false, 'message' => 'نام کاربری قبلاً وجود دارد!'];
    }
    if (getUserByEmail($email)) {
        return ['success' => false, 'message' => 'ایمیل قبلاً ثبت شده است!'];
    }
    
    $newUser = [
        'id' => getNextUserId(),
        'username' => $username,
        'password' => password_hash($password, PASSWORD_DEFAULT),
        'email' => $email,
        'fullname' => $fullname,
        'role' => $role,
        'status' => $status,
        'created_at' => date('Y-m-d H:i:s'),
        'last_login' => null,
        'avatar' => null,
        'bio' => ''
    ];
    
    $data['users'][] = $newUser;
    saveUsers($data);
    
    return ['success' => true, 'message' => 'کاربر با موفقیت اضافه شد!', 'user' => $newUser];
}

function updateUser($id, $data) {
    $usersData = loadUsers();
    foreach ($usersData['users'] as $key => $user) {
        if ($user['id'] == $id) {
            foreach ($data as $field => $value) {
                if ($field !== 'id' && $field !== 'password') {
                    $usersData['users'][$key][$field] = $value;
                }
                if ($field === 'password' && !empty($value)) {
                    $usersData['users'][$key]['password'] = password_hash($value, PASSWORD_DEFAULT);
                }
            }
            saveUsers($usersData);
            return ['success' => true, 'message' => 'کاربر با موفقیت به‌روزرسانی شد!'];
        }
    }
    return ['success' => false, 'message' => 'کاربر یافت نشد!'];
}

function deleteUser($id) {
    // جلوگیری از حذف ادمین اصلی
    if ($id == 1) {
        return ['success' => false, 'message' => 'امکان حذف کاربر ادمین اصلی وجود ندارد!'];
    }
    
    $data = loadUsers();
    $found = false;
    foreach ($data['users'] as $key => $user) {
        if ($user['id'] == $id) {
            unset($data['users'][$key]);
            $found = true;
            break;
        }
    }
    
    if ($found) {
        $data['users'] = array_values($data['users']);
        saveUsers($data);
        return ['success' => true, 'message' => 'کاربر با موفقیت حذف شد!'];
    }
    
    return ['success' => false, 'message' => 'کاربر یافت نشد!'];
}

function toggleUserStatus($id) {
    $data = loadUsers();
    foreach ($data['users'] as $key => $user) {
        if ($user['id'] == $id) {
            $data['users'][$key]['status'] = $user['status'] === 'active' ? 'inactive' : 'active';
            saveUsers($data);
            return ['success' => true, 'message' => 'وضعیت کاربر تغییر کرد!'];
        }
    }
    return ['success' => false, 'message' => 'کاربر یافت نشد!'];
}

function getUserRoleName($role) {
    $roles = [
        'admin' => 'مدیر',
        'editor' => 'نویسنده',
        'user' => 'کاربر عادی',
        'viewer' => 'بازدیدکننده'
    ];
    return $roles[$role] ?? $role;
}

function getRoleBadge($role) {
    $colors = [
        'admin' => '#dc3545',
        'editor' => '#ffc107',
        'user' => '#28a745',
        'viewer' => '#6c757d'
    ];
    return $colors[$role] ?? '#6c757d';
}

function getStatusBadge($status) {
    if ($status === 'active') {
        return '<span style="background:#28a745;color:white;padding:2px 12px;border-radius:30px;font-size:0.7rem;">فعال</span>';
    } else {
        return '<span style="background:#dc3545;color:white;padding:2px 12px;border-radius:30px;font-size:0.7rem;">غیرفعال</span>';
    }
}

function logUserActivity($userId, $action, $details = '') {
    $logFile = ROOT_PATH . '/logs/activity.log';
    if (!is_dir(dirname($logFile))) {
        mkdir(dirname($logFile), 0777, true);
    }
    
    $log = date('Y-m-d H:i:s') . " | User: $userId | Action: $action | Details: $details\n";
    file_put_contents($logFile, $log, FILE_APPEND);
}
?>