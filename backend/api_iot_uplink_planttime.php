<?php

error_reporting(E_ALL);
ini_set('display_errors', 1);

include __DIR__.'/../functions/config.php';

$DEBUG = isset($_GET['debug']) || (empty($_SERVER['HTTP_USER_AGENT']) ? false : strpos($_SERVER['HTTP_USER_AGENT'], 'Mozilla') !== false);

header('Content-Type: application/json');

// Helper JSON response
function send_json($data, $code = 200, $debugMode = false)
{
    http_response_code($code);
    echo json_encode(
        $data,
        $debugMode ? JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE : JSON_UNESCAPED_UNICODE
    );
    exit;
}

// === Ambil & parse JSON payload ===
$payload = file_get_contents('php://input');
$body = json_decode($payload, true);

if (!$body || !isset($body['header']) || !isset($body['header']['api_key'])) {
    send_json(['status' => 'error', 'message' => 'Header atau API key tidak ditemukan'], 400, $DEBUG);
}

$api_key = $body['header']['api_key'];
$device_id = $body['header']['device_id'] ?? null;

// Data yang dikirim device
$new_plant_time = $body['data']['plant_time'] ?? null;
if (!$new_plant_time) {
    send_json(['status' => 'error', 'message' => 'plant_time tidak ditemukan dalam data'], 400, $DEBUG);
}

// === Validasi API Key ===
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

// === Validasi device_id (jika dikirim) ===
if ($device_id && (int) $device_id !== $auth_device_id) {
    send_json(['status' => 'error', 'message' => 'Device ID tidak sesuai dengan API key'], 403, $DEBUG);
}

// === Ambil konfigurasi aktif ===
$sql_config = 'SELECT id, version, data_configuration
               FROM configurations
               WHERE device_id = ? AND is_active = 1 AND deleted_at IS NULL
               ORDER BY version DESC LIMIT 1';

$stmt_config = $conn->prepare($sql_config);
$stmt_config->bind_param('i', $auth_device_id);
$stmt_config->execute();
$result_config = $stmt_config->get_result();

if ($result_config->num_rows === 0) {
    send_json(['status' => 'error', 'message' => 'Konfigurasi aktif tidak ditemukan'], 404, $DEBUG);
}

$config = $result_config->fetch_assoc();
$config_id = (int) $config['id'];
$data_config = json_decode($config['data_configuration'], true);

// Pastikan node ada
if (!isset($data_config['device_configuration']) || !is_array($data_config['device_configuration'])) {
    $data_config['device_configuration'] = [];
}

// === Update plant_time ===
$data_config['device_configuration']['plant_time'] = $new_plant_time;

// Encode kembali
$new_json = json_encode($data_config, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

// === Simpan ke DB ===
$update = $conn->prepare('UPDATE configurations SET data_configuration = ?, updated_at = NOW() WHERE id = ?');
$update->bind_param('si', $new_json, $config_id);
$update->execute();

// === Response sukses ===
$response = [
    'status' => 'success',
    'message' => 'plant_time berhasil diperbarui',
    'device_id' => $auth_device_id,
    'version' => (int) $config['version'],
    'plant_time' => $new_plant_time,
];

if ($DEBUG) {
    $response['debug'] = [
        'raw_payload' => $payload,
        'parsed_data' => $body['data'],
        'config_id' => $config_id,
        'query_used' => "UPDATE configurations SET data_configuration = ... WHERE id = $config_id",
    ];
}

send_json($response, 200, $DEBUG);

$conn->close();
