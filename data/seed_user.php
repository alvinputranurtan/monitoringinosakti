<?php

require_once __DIR__.'/../functions/config.php';

// ================================
// Data user baru
// ================================
$username = 'AlvinUser';
$email = 'alvinuser@inosakti.com';
$password = 'AlvinUser2025';
$role = 'user';
$data_user = [
    'rumah' => 'klipang',
    'deskripsi_singkat' => 'ini akun user punya alvin',
];

// ================================
// Hash password
// ================================
$password_hash = password_hash($password, PASSWORD_BCRYPT);
$data_user_json = json_encode($data_user, JSON_UNESCAPED_UNICODE);

// ================================
// Cek apakah user sudah ada
// ================================
$check = $conn->prepare('SELECT id FROM users WHERE username = ? OR email = ?');
$check->bind_param('ss', $username, $email);
$check->execute();
$check->store_result();

if ($check->num_rows > 0) {
    echo "⚠️ User '$username' atau '$email' sudah ada.\n";
    $check->close();
    exit;
}
$check->close();

// ================================
// Insert ke database
// ================================
$stmt = $conn->prepare('
    INSERT INTO users (username, email, password_hash, role, data_user, created_at)
    VALUES (?, ?, ?, ?, ?, NOW())
');
$stmt->bind_param('sssss', $username, $email, $password_hash, $role, $data_user_json);

if ($stmt->execute()) {
    echo "✅ User '$username' berhasil dibuat!\n";
    echo '🆔 ID: '.$stmt->insert_id."\n";
    echo "📧 Email: $email\n";
    echo "🔑 Password: $password\n";
} else {
    echo '❌ Gagal insert user: '.$stmt->error."\n";
}

$stmt->close();
$conn->close();
