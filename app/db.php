<?php

function db_resolve_path($path) {
    if (!$path) {
        return null;
    }

    // Absolute Unix path or Windows path.
    if (str_starts_with($path, '/') || preg_match('/^[A-Za-z]:[\\\\\/]/', $path)) {
        return $path;
    }

    return dirname(__DIR__) . '/' . ltrim($path, '/');
}

function db() {
    static $pdo = null;
    if ($pdo instanceof PDO) {
        return $pdo;
    }

    $dsn = 'mysql:host=' . DB_HOST . ';port=' . DB_PORT . ';dbname=' . DB_NAME . ';charset=' . DB_CHARSET;

    $options = [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ];

    $sslMode = strtolower((string) DB_SSL_MODE);
    $sslCaPath = db_resolve_path(DB_SSL_CA_PATH);

    if (in_array($sslMode, ['required', 'require', 'verify-ca', 'verify-full', 'true', '1'], true)) {
        if ($sslCaPath && is_file($sslCaPath)) {
            // Aiven recommends verify-ca with the downloaded ca.pem certificate.
            $dsn .= ';sslmode=verify-ca;sslrootcert=' . $sslCaPath;

            $sslCaAttr = null;

            if (class_exists('Pdo\\Mysql') && defined('Pdo\\Mysql::ATTR_SSL_CA')) {
                $sslCaAttr = constant('Pdo\\Mysql::ATTR_SSL_CA');
            } elseif (PHP_VERSION_ID < 80500 && defined('PDO::MYSQL_ATTR_SSL_CA')) {
                $sslCaAttr = PDO::MYSQL_ATTR_SSL_CA;
            }
            
            if ($sslCaAttr !== null) {
                $options[$sslCaAttr] = $sslCaPath;
            }
        } else {
            // Keep this explicit so the deployment fails with a clear message in debug mode.
            throw new RuntimeException('DB_SSL_MODE aktif, tetapi file CA tidak ditemukan di: ' . ($sslCaPath ?: DB_SSL_CA_PATH));
        }
    }

    try {
        $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
        return $pdo;
    } catch (Throwable $e) {
        error_log('DB connection failed: ' . $e->getMessage());

        if (defined('APP_DEBUG') && APP_DEBUG) {
            $safe = [
                'host' => DB_HOST,
                'port' => DB_PORT,
                'database' => DB_NAME,
                'user' => DB_USER,
                'ssl_mode' => DB_SSL_MODE,
                'ssl_ca_path' => DB_SSL_CA_PATH,
            ];

            exit(
                '<h2>Koneksi database gagal</h2>' .
                '<p>' . htmlspecialchars($e->getMessage()) . '</p>' .
                '<pre>' . htmlspecialchars(print_r($safe, true)) . '</pre>' .
                '<p>Periksa environment variables Vercel, CA certificate Aiven, dan pastikan database sudah di-import.</p>'
            );
        }

        exit('Koneksi database gagal. Hubungi administrator.');
    }
}
