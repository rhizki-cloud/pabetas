<?php

function env_value($key, $default = null) {
    $value = getenv($key);
    return $value === false ? $default : $value;
}

function env_bool($key, $default = false) {
    $value = getenv($key);

    if ($value === false) {
        return $default;
    }

    return filter_var($value, FILTER_VALIDATE_BOOLEAN);
}

define('APP_NAME', env_value('APP_NAME', 'PABETAS'));
define('APP_ENV', env_value('APP_ENV', 'local'));
define('APP_DEBUG', env_bool('APP_DEBUG', true));
define('APP_URL', rtrim(env_value('APP_URL', ''), '/'));

define('DB_HOST', env_value('DB_HOST', '127.0.0.1'));
define('DB_PORT', env_value('DB_PORT', '3306'));
define('DB_NAME', env_value('DB_NAME', 'pabetas'));
define('DB_USER', env_value('DB_USER', 'root'));
define('DB_PASS', env_value('DB_PASS', ''));
define('DB_CHARSET', env_value('DB_CHARSET', 'utf8mb4'));

define('SCHOOL_NAME', env_value('SCHOOL_NAME', 'SD Contoh Nusantara'));
define('SCHOOL_LOGO', env_value('SCHOOL_LOGO', 'assets/img/logo-pabetas.svg'));

define('SESSION_TIMEOUT', 2700);

define('REMEDIAL_MIN_SCORE', 70);
define('ACADEMIC_TEST_LIMIT', 5);