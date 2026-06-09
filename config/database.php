<?php

if (!function_exists('envValue')) {
function envValue(string $key, mixed $default): mixed
{
    $value = getenv($key);

    if ($value !== false) {
        return $value;
    }

    if (isset($_ENV[$key])) {
        return $_ENV[$key];
    }

    if (isset($_SERVER[$key])) {
        return $_SERVER[$key];
    }

    return $default;
}
}

if (!function_exists('envBool')) {
    function envBool(string $key, bool $default): bool
    {
        $value = envValue($key, null);

        return $value === null
            ? $default
            : in_array(strtolower((string) $value), ['1', 'true', 'yes', 'on'], true);
    }
}

$runningInDocker = envBool('APP_DOCKER', file_exists('/.dockerenv'));

return [
    'enabled' => envBool('DB_ENABLED', $runningInDocker),
    'host' => envValue('DB_HOST', $runningInDocker ? 'mysql' : '127.0.0.1'),
    'port' => (int) envValue('DB_PORT', 3306),
    'database' => envValue('DB_DATABASE', 'lead_gen_tool'),
    'username' => envValue('DB_USERNAME', 'root'),
    'password' => envValue('DB_PASSWORD', $runningInDocker ? 'root' : ''),
    'charset' => envValue('DB_CHARSET', 'utf8mb4'),
];
