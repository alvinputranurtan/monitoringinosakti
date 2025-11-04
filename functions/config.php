<?php

// =========================================================
// Session Configuration
// =========================================================
if (session_status() === PHP_SESSION_NONE) {
    ini_set('session.cookie_httponly', 1);
    ini_set('session.cookie_secure', 1);
    ini_set('session.gc_maxlifetime', 86400); // 24 jam
    session_start();
}

// =========================================================
// Load Environment (.env)
// =========================================================
require_once __DIR__.'/../vendor/autoload.php';
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__.'/..');
$dotenv->safeLoad();

// =========================================================
// Environment & Timezone
// =========================================================
date_default_timezone_set($_ENV['APP_TIMEZONE'] ?? 'Asia/Jakarta');

// =========================================================
// Database Connection (semua dari .env)
// =========================================================
$host = $_ENV['DB_HOST'] ?? null;
$user = $_ENV['DB_USER'] ?? null;
$password = $_ENV['DB_PASS'] ?? null;
$dbname = $_ENV['DB_NAME'] ?? null;

// Validasi env wajib
if (!$host || !$user || !$dbname) {
    exit('❌ Konfigurasi database belum lengkap di file .env');
}

// Koneksi ke database
$conn = new mysqli($host, $user, $password, $dbname);
if ($conn->connect_error) {
    exit('❌ Koneksi database gagal: '.$conn->connect_error);
}

// Charset & timezone MySQL
$conn->set_charset('utf8mb4');
$conn->query("SET time_zone = '+07:00'");

// =========================================================
// Secure Session Handling
// =========================================================
if (!isset($_SESSION['initialized'])) {
    session_regenerate_id(true);
    $_SESSION['initialized'] = true;
}

// =========================================================
// Constant App (opsional untuk meta info)
// =========================================================
if (!defined('APP_NAME')) {
    define('APP_NAME', $_ENV['APP_NAME'] ?? 'Smart Agro IoT Core');
}
if (!defined('APP_ENV')) {
    define('APP_ENV', $_ENV['APP_ENV'] ?? 'production');
}
