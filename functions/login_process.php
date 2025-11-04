<?php

include 'config.php';
include 'functions.php';
include 'csrf.php';

// Proteksi brute force sederhana
if (!isset($_SESSION['login_attempt'])) {
    $_SESSION['login_attempt'] = 0;
    $_SESSION['last_attempt_time'] = time();
}
if (time() - $_SESSION['last_attempt_time'] > 900) {
    $_SESSION['login_attempt'] = 0;
}

if ($_SESSION['login_attempt'] >= 5) {
    $_SESSION['error'] = 'Terlalu banyak percobaan login. Coba lagi 15 menit lagi.';
    header('Location: ../pages/login.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (!verify_csrf_token($_POST['csrf_token'])) {
        $_SESSION['error'] = 'CSRF token tidak valid.';
        header('Location: ../pages/login.php');
        exit;
    }

    $username = clean_input($_POST['username']);
    $password = $_POST['password'];

    if (strlen($username) > 50 || strlen($password) > 255) {
        $_SESSION['error'] = 'Input tidak valid.';
        header('Location: ../pages/login.php');
        exit;
    }

    // Kolom yang benar adalah password_hash
    $stmt = $conn->prepare('SELECT id, username, password_hash FROM users WHERE username = ?');
    if ($stmt === false) {
        error_log('MySQL prepare error: '.$conn->error);
        $_SESSION['error'] = 'Terjadi kesalahan server.';
        header('Location: ../pages/login.php');
        exit;
    }

    $stmt->bind_param('s', $username);
    $stmt->execute();
    $stmt->store_result();

    if ($stmt->num_rows === 1) {
        $stmt->bind_result($id, $uname, $hashed_password);
        $stmt->fetch();

        if (password_verify($password, $hashed_password)) {
            session_regenerate_id(true);
            $_SESSION['user_id'] = $id;
            $_SESSION['username'] = htmlspecialchars($uname, ENT_QUOTES, 'UTF-8');
            $_SESSION['login_attempt'] = 0;
            $stmt->close();
            header('Location: ../index.php');
            exit;
        }
    }

    // Jika gagal login
    $stmt->close();
    ++$_SESSION['login_attempt'];
    $_SESSION['last_attempt_time'] = time();
    $_SESSION['error'] = 'Username atau password salah.';
    header('Location: ../pages/login.php');
    exit;
}
