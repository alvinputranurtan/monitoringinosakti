<?php

include 'config.php';
header('Content-Type: application/json');

// === Ambil & parse payload JSON ===
$payload = file_get_contents('php://input');
$body = json_decode($payload, true);

if (!$body || !isset($body['header']) || !isset($body['header']['api_key'])) {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => 'Header atau API key tidak ditemukan']);
    exit;
}

$api_key = $body['header']['api_key'];
$device_id = $body['header']['device_id'] ?? null;
$timestamp = $body['header']['timestamp'] ?? null;
$data_monitor = $body['data'] ?? [];

// === Validasi API key ===
$sql = "SELECT device_id FROM device_auth 
        WHERE api_key_sha256 = UNHEX(SHA2(?, 256)) 
        AND status='active'
        AND (expires_at IS NULL OR expires_at > NOW())";
$stmt = $conn->prepare($sql);
$stmt->bind_param('s', $api_key);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    http_response_code(401);
    echo json_encode(['status' => 'error', 'message' => 'API key tidak valid']);
    exit;
}

$row = $result->fetch_assoc();
$auth_device_id = (int) $row['device_id'];

// === Cocokkan device_id bila dikirim ===
if ($device_id && (int) $device_id !== $auth_device_id) {
    http_response_code(403);
    echo json_encode(['status' => 'error', 'message' => 'Device ID tidak sesuai dengan API key']);
    exit;
}

// === Validasi timestamp ===
if ($timestamp) {
    if (!preg_match('/^\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}$/', $timestamp)) {
        $timestamp = null; // fallback ke waktu server
    }
}

// === Encode data_monitor ke JSON ===
$json_data = json_encode($data_monitor, JSON_UNESCAPED_UNICODE);

// === Insert data ke tabel monitor ===
if ($timestamp) {
    $insert = $conn->prepare('INSERT INTO monitor (device_id, data_monitor, created_at) VALUES (?, ?, ?)');
    $insert->bind_param('iss', $auth_device_id, $json_data, $timestamp);
} else {
    $insert = $conn->prepare('INSERT INTO monitor (device_id, data_monitor) VALUES (?, ?)');
    $insert->bind_param('is', $auth_device_id, $json_data);
}

if ($insert->execute()) {
    echo json_encode([
        'status' => 'success',
        'message' => 'Data berhasil disimpan',
        'device_id' => $auth_device_id,
        'created_at' => $timestamp ?: date('Y-m-d H:i:s'),
    ]);
} else {
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => 'Gagal menyimpan data']);
}

$conn->close();
