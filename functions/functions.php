<?php

function clean_input($data)
{
    return htmlspecialchars(trim($data), ENT_QUOTES, 'UTF-8');
}

// function log_activity($message)
// {
//     $file = 'log.txt';
//     $time = date('Y-m-d H:i:s');
//     file_put_contents($file, "[$time] $message\n", FILE_APPEND);
// }
