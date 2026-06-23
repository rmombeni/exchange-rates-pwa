<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

$code = isset($_GET['code']) ? preg_replace('/[^A-Z0-9]/', '', $_GET['code']) : '';

if (empty($code)) {
    echo json_encode([]);
    exit;
}

$file = __DIR__ . '/history/' . $code . '.json';
if (!file_exists($file)) {
    echo json_encode([]);
    exit;
}

$data = json_decode(file_get_contents($file), true);
if (!is_array($data)) {
    echo json_encode([]);
    exit;
}

// فقط 30 روز اخیر
$data = array_slice($data, -30);
echo json_encode($data);
?>