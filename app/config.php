<?php

function env_value($key, $default = null) {
    $value = getenv($key);
    return $value === false || $value === '' ? $default : $value;
}

function env_bool($key, $default = false) {
    $value = getenv($key);

    if ($value === false || $value === '') {
        return $default;
    }

    return filter_var($value, FILTER_VALIDATE_BOOLEAN);
}

function database_url_config() {
    $uri = env_value('DATABASE_URL') ?: env_value('MYSQL_URI') ?: env_value('AIVEN_MYSQL_URI');
    if (!$uri) {
        return [];
    }

    $fields = parse_url($uri);
    if (!is_array($fields)) {
        return [];
    }

    $query = [];
    if (!empty($fields['query'])) {
        parse_str($fields['query'], $query);
    }

    $sslMode = $query['ssl-mode'] ?? $query['sslmode'] ?? $query['ssl_mode'] ?? null;

    return [
        'host' => $fields['host'] ?? null,
        'port' => $fields['port'] ?? null,
        'name' => isset($fields['path']) ? ltrim($fields['path'], '/') : null,
        'user' => isset($fields['user']) ? urldecode($fields['user']) : null,
        'pass' => isset($fields['pass']) ? urldecode($fields['pass']) : null,
        'ssl_mode' => $sslMode,
    ];
}

$dbUrl = database_url_config();

define('APP_NAME', env_value('APP_NAME', 'PABETAS'));
define('APP_ENV', env_value('APP_ENV', 'local'));
define('APP_DEBUG', env_bool('APP_DEBUG', false));
define('APP_URL', rtrim(env_value('APP_URL', ''), '/'));

define('DB_HOST', env_value('DB_HOST', $dbUrl['host'] ?? '127.0.0.1'));
define('DB_PORT', env_value('DB_PORT', $dbUrl['port'] ?? '3306'));
define('DB_NAME', env_value('DB_NAME', $dbUrl['name'] ?? 'pabetas'));
define('DB_USER', env_value('DB_USER', $dbUrl['user'] ?? 'root'));
define('DB_PASS', env_value('DB_PASS', $dbUrl['pass'] ?? ''));
define('DB_CHARSET', env_value('DB_CHARSET', 'utf8mb4'));

define('DB_SSL_MODE', strtolower(env_value('DB_SSL_MODE', $dbUrl['ssl_mode'] ?? '')));
define('DB_SSL_CA_PATH', env_value('DB_SSL_CA_PATH', 'app/ca.pem'));

define('SCHOOL_NAME', env_value('SCHOOL_NAME', 'SD Contoh Nusantara'));
define('SCHOOL_LOGO', env_value('SCHOOL_LOGO', 'assets/img/logo-pabetas.svg'));

define('SESSION_TIMEOUT', 2700);

define('REMEDIAL_MIN_SCORE', 70);
define('ACADEMIC_TEST_LIMIT', 5);
