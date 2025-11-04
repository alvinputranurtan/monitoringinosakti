<?php

error_reporting(E_ALL);
ini_set('display_errors', 1);

include __DIR__.'/functions/config.php';

// === AUTO DEBUG MODE ===
// Aktif hanya kalau akses dari browser (ada ?debug atau tidak ada JSON input)
$DEBUG = isset($_GET['debug']) || (empty($_SERVER['HTTP_USER_AGENT']) ? false : strpos($_SERVER['HTTP_USER_AGENT'], 'Mozilla') !== false);

header('Content-Type: application/json');

// === Ambil & parse payload JSON ===
$payload = file_get_contents('php://input');
$body = json_decode($payload, true);

// --- Helper: kirim output JSON (pretty-print jika debug) ---
function send_json($data, $code = 200, $debugMode = false)
{
    http_response_code($code);
    echo json_encode(
        $data,
        $debugMode ? JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE : JSON_UNESCAPED_UNICODE
    );
    exit;
}

// === Validasi awal ===
if (!$body || !isset($body['header']) || !isset($body['header']['api_key'])) {
    send_json(['status' => 'error', 'message' => 'Header atau API key tidak ditemukan'], 400, $DEBUG);
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
    send_json(['status' => 'error', 'message' => 'API key tidak valid'], 401, $DEBUG);
}

$row = $result->fetch_assoc();
$auth_device_id = (int) $row['device_id'];

// === Cocokkan device_id jika dikirim ===
if ($device_id && (int) $device_id !== $auth_device_id) {
    send_json(['status' => 'error', 'message' => 'Device ID tidak sesuai dengan API key'], 403, $DEBUG);
}

// === Validasi timestamp ===
if ($timestamp && !preg_match('/^\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}$/', $timestamp)) {
    $timestamp = null; // fallback ke waktu server
}

// === Encode data_monitor ===
$json_data = json_encode($data_monitor, JSON_UNESCAPED_UNICODE);

// === Insert ke tabel monitor ===
if ($timestamp) {
    $insert = $conn->prepare('INSERT INTO monitor (device_id, data_monitor, created_at) VALUES (?, ?, ?)');
    $insert->bind_param('iss', $auth_device_id, $json_data, $timestamp);
} else {
    $insert = $conn->prepare('INSERT INTO monitor (device_id, data_monitor) VALUES (?, ?)');
    $insert->bind_param('is', $auth_device_id, $json_data);
}

if ($insert->execute()) {
    $response = [
        'status' => 'success',
        'message' => 'Data berhasil disimpan',
        'device_id' => $auth_device_id,
        'created_at' => $timestamp ?: date('Y-m-d H:i:s'),
    ];

    // === Tambahkan detail debug hanya jika mode browser aktif ===
    if ($DEBUG) {
        $response['debug'] = [
            'raw_payload' => $payload,
            'parsed_header' => $body['header'],
            'parsed_data' => $body['data'] ?? [],
            'query_used' => $timestamp
                ? 'INSERT INTO monitor (device_id, data_monitor, created_at)'
                : 'INSERT INTO monitor (device_id, data_monitor)',
            'mysql_affected_rows' => $conn->affected_rows,
            'mysql_insert_id' => $conn->insert_id,
        ];
    }

    send_json($response, 200, $DEBUG);
} else {
    $msg = 'Gagal menyimpan data';
    if ($DEBUG) {
        $msg .= ' | MySQL error: '.$conn->error;
    }
    send_json(['status' => 'error', 'message' => $msg], 500, $DEBUG);
}

$conn->close();
