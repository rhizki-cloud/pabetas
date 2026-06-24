<?php
function db() {
    static $pdo = null;
    if ($pdo instanceof PDO) {
        return $pdo;
    }

    $dsn = 'mysql:host=' . DB_HOST . ';port=' . DB_PORT . ';dbname=' . DB_NAME . ';charset=' . DB_CHARSET;
    try {
        $pdo = new PDO($dsn, DB_USER, DB_PASS, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ]);
        return $pdo;
    } catch (PDOException $e) {
        if (defined('APP_DEBUG') && APP_DEBUG) {
            exit('<h2>Koneksi database gagal</h2><p>' . htmlspecialchars($e->getMessage()) . '</p><p>Jalankan <b>/install.php</b> atau import <b>database/pabetas_advanced.sql</b>.</p>');
        }
        exit('Koneksi database gagal. Hubungi administrator.');
    }
}
