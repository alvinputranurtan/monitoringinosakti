<?php

require_once __DIR__.'/config.php';
header('Content-Type: application/json; charset=utf-8');

$config_id = isset($_POST['config_id']) ? (int) $_POST['config_id'] : 0;
$kontrol_json = $_POST['kontrol'] ?? '';

if ($config_id <= 0 || $kontrol_json === '') {
    echo json_encode(['success' => false, 'message' => 'Data tidak lengkap.']);
    exit;
}

$incoming = json_decode($kontrol_json, true);
if (!is_array($incoming)) {
    echo json_encode(['success' => false, 'message' => 'Format JSON kontrol tidak valid.']);
    exit;
}

$stmt = $conn->prepare('SELECT data_configuration FROM configurations WHERE id = ?');
$stmt->bind_param('i', $config_id);
$stmt->execute();
$res = $stmt->get_result();

if ($res->num_rows === 0) {
    echo json_encode(['success' => false, 'message' => 'Konfigurasi tidak ditemukan.']);
    exit;
}

$row = $res->fetch_assoc();
$current = json_decode($row['data_configuration'], true);
if (!is_array($current)) {
    $current = [];
}

// Jaga root web_control
if (!isset($current['web_control']) || !is_array($current['web_control'])) {
    $current['web_control'] = [];
}

// HANYA update choose
if (isset($incoming['choose']) && is_array($incoming['choose'])) {
    if (!isset($current['web_control']['choose']) || !is_array($current['web_control']['choose'])) {
        $current['web_control']['choose'] = [];
    }
    foreach ($incoming['choose'] as $key => $val) {
        $current['web_control']['choose'][$key] = $val;
    }
}

// ENCODE: RAPIH & HUMAN-FRIENDLY
$new_json = json_encode($current, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

$update = $conn->prepare('UPDATE configurations SET data_configuration = ?, updated_at = NOW() WHERE id = ?');
$update->bind_param('si', $new_json, $config_id);
$update->execute();

echo json_encode(['success' => true, 'message' => '✅ Plant type tersimpan.']);
