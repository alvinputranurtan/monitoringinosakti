<?php

include 'config.php';

$res = $conn->query('SELECT NOW() AS mysql_time, @@session.time_zone AS mysql_tz');
$row = $res->fetch_assoc();

echo json_encode([
    'php_time' => date('Y-m-d H:i:s'),
    'mysql_time' => $row['mysql_time'],
    'mysql_timezone' => $row['mysql_tz'],
], JSON_PRETTY_PRINT);
