<?php

ini_set('display_errors', 1);
error_reporting(E_ALL);

require_once __DIR__.'/config.php';
header('Content-Type: application/json; charset=utf-8');

// === Ambil & parse payload JSON ===
$payload = file_get_contents('php://input');
$body = json_decode($payload, true);

if (!$body || !isset($body['header']) || !isset($body['header']['api_key'])) {
    http_response_code(400);
    echo json_encode([
        'status' => 'error',
        'message' => 'Header atau API key tidak ditemukan',
    ]);
    exit;
}

$api_key = $body['header']['api_key'];
$device_id = $body['header']['device_id'] ?? null;

// === Validasi API Key ===
// api_key disimpan di DB dalam bentuk SHA256 binary = UNHEX(SHA2(key,256))
$sql = "SELECT device_id 
        FROM device_auth 
        WHERE api_key_sha256 = UNHEX(SHA2(?, 256))
        AND status='active'
        AND (expires_at IS NULL OR expires_at > NOW())";

$stmt = $conn->prepare($sql);
$stmt->bind_param('s', $api_key);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    http_response_code(401);
    echo json_encode([
        'status' => 'error',
        'message' => 'API key tidak valid',
    ]);
    exit;
}

$row = $result->fetch_assoc();
$auth_device_id = (int) $row['device_id'];

// === Cocokkan device_id (jika dikirim oleh device) ===
if ($device_id && (int) $device_id !== $auth_device_id) {
    http_response_code(403);
    echo json_encode([
        'status' => 'error',
        'message' => 'Device ID tidak sesuai dengan API key',
    ]);
    exit;
}

// === Ambil konfigurasi aktif untuk device ini ===
$sql_config = 'SELECT version, data_configuration
               FROM configurations
               WHERE device_id = ? AND is_active = 1 AND deleted_at IS NULL
               ORDER BY version DESC LIMIT 1';

$stmt_config = $conn->prepare($sql_config);
$stmt_config->bind_param('i', $auth_device_id);
$stmt_config->execute();
$result_config = $stmt_config->get_result();

if ($result_config->num_rows === 0) {
    echo json_encode([
        'status' => 'error',
        'message' => 'Konfigurasi aktif tidak ditemukan',
    ]);
    exit;
}

$config = $result_config->fetch_assoc();
$config_json = json_decode($config['data_configuration'], true);

// === Kirim hanya device_configuration ===
if (!isset($config_json['device_configuration'])) {
    echo json_encode([
        'status' => 'error',
        'message' => 'device_configuration tidak ditemukan dalam konfigurasi',
    ]);
    exit;
}

// ✅ Output final — simple & clean
echo json_encode([
    'status' => 'success',
    'device_id' => $auth_device_id,
    'version' => (int) $config['version'],
    'device_configuration' => $config_json['device_configuration'],
], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

$conn->close();
