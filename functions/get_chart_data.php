<?php

declare(strict_types=1);
header('Content-Type: application/json; charset=utf-8');

require_once __DIR__.'/config.php';

// Pastikan session aktif
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Cek login user
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

$current_user_id = (int) $_SESSION['user_id'];
$period = $_GET['period'] ?? 'hourly';

// 🔹 Tambahkan prefix "m." biar gak ambigu
$whereTime = 'm.created_at >= DATE_SUB(NOW(), INTERVAL 24 HOUR)';
if ($period === 'daily') {
    $whereTime = 'm.created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)';
}

// 🔹 Query ambil data JSON milik user login
$sql = "
SELECT 
    m.data_monitor, 
    m.created_at
FROM monitor m
JOIN devices d ON m.device_id = d.id
WHERE d.user_id = ?
  AND $whereTime
ORDER BY m.created_at ASC
";

$stmt = $conn->prepare($sql);
if (!$stmt) {
    http_response_code(500);
    echo json_encode(['error' => 'Query prepare failed', 'detail' => $conn->error]);
    exit;
}

$stmt->bind_param('i', $current_user_id);
$stmt->execute();
$res = $stmt->get_result();

// 🔹 Kumpulkan data
$raw = [];
while ($row = $res->fetch_assoc()) {
    $data = json_decode($row['data_monitor'], true);
    if (!is_array($data)) {
        continue;
    }
    $time = date('Y-m-d H:i', strtotime($row['created_at']));
    $raw[$time] = $data;
}

if (empty($raw)) {
    echo json_encode(['labels' => [], 'datasets' => []]);
    exit;
}

// 🔹 Ambil semua key unik dari JSON
$allKeys = [];
foreach ($raw as $entry) {
    $allKeys = array_unique(array_merge($allKeys, array_keys($entry)));
}

// 🔹 Bentuk hasil JSON final
$labels = array_keys($raw);
$datasets = [];
foreach ($allKeys as $key) {
    $values = [];
    foreach ($raw as $entry) {
        $values[] = isset($entry[$key]) && is_numeric($entry[$key]) ? (float) $entry[$key] : null;
    }
    $datasets[$key] = [
        'label' => ucwords(str_replace('_', ' ', $key)),
        'values' => $values,
    ];
}

echo json_encode(['labels' => $labels, 'datasets' => $datasets], JSON_UNESCAPED_UNICODE);
