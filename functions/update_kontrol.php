<?php

require_once __DIR__.'/config.php';
header('Content-Type: application/json');

$config_id = $_POST['config_id'] ?? null;
$kontrol_json = $_POST['kontrol'] ?? null;

if (!$config_id || !$kontrol_json) {
    echo json_encode(['success' => false, 'message' => 'Data tidak lengkap.']);
    exit;
}

$kontrol_data = json_decode($kontrol_json, true);
if (json_last_error() !== JSON_ERROR_NONE) {
    echo json_encode(['success' => false, 'message' => 'Format JSON kontrol tidak valid.']);
    exit;
}

$sql = 'SELECT data_configuration FROM configurations WHERE id = ?';
$stmt = $conn->prepare($sql);
$stmt->bind_param('i', $config_id);
$stmt->execute();
$res = $stmt->get_result();

if (!$res || $res->num_rows === 0) {
    echo json_encode(['success' => false, 'message' => 'Konfigurasi tidak ditemukan.']);
    exit;
}

$row = $res->fetch_assoc();
$data_config = json_decode($row['data_configuration'], true);
$data_config['kontrol'] = $kontrol_data;

$new_json = json_encode($data_config, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
$update = $conn->prepare('UPDATE configurations SET data_configuration = ?, updated_at = NOW() WHERE id = ?');
$update->bind_param('si', $new_json, $config_id);

if ($update->execute()) {
    echo json_encode(['success' => true, 'message' => '✅ Konfigurasi berhasil diperbarui.']);
} else {
    echo json_encode(['success' => false, 'message' => '❌ Gagal menyimpan konfigurasi.']);
}
