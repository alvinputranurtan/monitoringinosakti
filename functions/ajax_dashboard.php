<?php

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

$current_user_id = $_SESSION['user_id'];

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
    $response['created_at'] = $row['created_at'];

    $last_time = strtotime($row['created_at']);
    if ($last_time !== false && (time() - $last_time) <= 15) {
        $response['status_perangkat'] = 'Online';
    }
}

header('Content-Type: application/json');
echo json_encode($response);
