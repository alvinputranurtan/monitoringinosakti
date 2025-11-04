<?php

// ===============================
// AJAX DASHBOARD (fix status Offline terus)
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

$sql = '
SELECT m.data_monitor, m.created_at
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
    $data = json_decode($row['data_monitor'], true) ?? [];
    $response['data'] = $data;

    // Format created_at tanpa milidetik
    $formatted_time = date('Y-m-d H:i:s', strtotime($row['created_at']));
    $response['created_at'] = $formatted_time;

    // --- Hitung selisih waktu dengan timezone yang sama ---
    $now = new DateTime('now', new DateTimeZone('Asia/Jakarta'));
    $last = new DateTime($row['created_at'], new DateTimeZone('Asia/Jakarta'));
    $diff = $now->getTimestamp() - $last->getTimestamp();

    // Kalau selisih <= 60 detik, berarti Online
    if ($diff <= 60) {
        $response['status_perangkat'] = 'Online';
    }

    // Debug opsional (hapus setelah yakin)
    $response['debug'] = [
        'now' => $now->format('Y-m-d H:i:s'),
        'created_at' => $formatted_time,
        'diff_seconds' => $diff,
    ];
}

header('Content-Type: application/json; charset=utf-8');
echo json_encode($response, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
