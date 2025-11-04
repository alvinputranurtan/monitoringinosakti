<?php

include 'config.php';
include 'functions.php';

// log_activity("User {$_SESSION['username']} logout.");
$_SESSION = [];
session_destroy();
header('Location: login.php');
exit;
