<?php
session_start();
require_once 'includes/config.php';
require_once 'includes/user_functions.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Not logged in']);
    exit;
}

$action = $_POST['action'] ?? '';
$code = $_POST['code'] ?? '';

if ($action === 'toggle' && $code) {
    $user = getUserById($_SESSION['user_id']);
    if ($user) {
        $currentFav = $user['favorite_currency'] ?? '';
        $newFav = ($currentFav === $code) ? null : $code;
        updateUser($user['id'], ['favorite_currency' => $newFav]);
        echo json_encode(['success' => true, 'favorite' => $newFav]);
    } else {
        echo json_encode(['success' => false, 'message' => 'User not found']);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid action']);
}
exit;
?>