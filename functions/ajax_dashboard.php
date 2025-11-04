<?php

// ===============================
// AJAX DASHBOARD (versi fix status dan tanpa milisecond)
// ===============================

date_default_timezone_set('Asia/Jakarta');
include __DIR__.'/../functions/config.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

$current_user_id = (int) $_SESSION['user_id'];

// Ambil data monitor terakhir + waktu server last_seen
$sql = '
SELECT 
    m.data_monitor, 
    m.created_at,
    d.last_seen
FROM monitor m
JOIN devices d ON m.device_id = d.id
WHERE d.user_id = ?
ORDER BY m.id DESC
LIMIT 1
';
$stmt = $conn->prepare($sql);
$stmt->bind_param('i', $current_user_id);
$stmt->execute();
$result = $stmt->get_result();
$row = $result->fetch_assoc();

$response = [
    'status_perangkat' => 'Offline',
    'created_at' => null,
    'data' => [],
];

if ($row && isset($row['data_monitor'])) {
    // Decode data sensor
    $data = json_decode($row['data_monitor'], true) ?? [];
    $response['data'] = $data;

    // Format waktu tampil tanpa milidetik
    $response['created_at'] = $row['created_at']
        ? date('Y-m-d H:i:s', strtotime($row['created_at']))
        : null;

    // Gunakan waktu server (last_seen) untuk menentukan status Online/Offline
    $last_seen_ts = $row['last_seen'] ? strtotime($row['last_seen']) : false;
    $threshold = 30; // detik, bisa kamu ubah sesuai kebutuhan
    if ($last_seen_ts !== false && (time() - $last_seen_ts) <= $threshold) {
        $response['status_perangkat'] = 'Online';
    }
}

header('Content-Type: application/json; charset=utf-8');
echo json_encode($response, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
