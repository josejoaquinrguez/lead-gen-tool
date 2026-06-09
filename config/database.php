<?php

require_once __DIR__ . '/env.php';

$runningInDocker = envBool('APP_DOCKER', file_exists('/.dockerenv'));

return [
    'enabled' => envBool('DB_ENABLED', $runningInDocker),
    'host' => envValue('DB_HOST', $runningInDocker ? 'mysql' : '127.0.0.1'),
    'port' => (int) envValue('DB_PORT', 3306),
    'database' => envValue('DB_DATABASE', 'lead_gen_tool'),
    'username' => envValue('DB_USERNAME', 'root'),
    'password' => envValue('DB_PASSWORD', ''),
    'charset' => envValue('DB_CHARSET', 'utf8mb4'),
];
