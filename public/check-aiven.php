<?php
require_once __DIR__ . '/../app/config.php';

header('Content-Type: text/plain; charset=utf-8');

function line($label, $value = '') {
    echo $label . ($value !== '' ? ': ' . $value : '') . PHP_EOL;
}

line('PABETAS Aiven Connection Check');
line(str_repeat('=', 34));
line('APP_ENV', APP_ENV);
line('APP_DEBUG', APP_DEBUG ? 'true' : 'false');
line('DB_HOST', DB_HOST);
line('DB_HOST_LENGTH', (string) strlen(DB_HOST));
line('DB_PORT', DB_PORT);
line('DB_NAME', DB_NAME);
line('DB_USER', DB_USER);
line('DB_SSL_MODE', DB_SSL_MODE ?: '(empty)');
line('DB_SSL_CA_PATH', DB_SSL_CA_PATH);
line('DB_SSL_CA_EXISTS', is_file(dirname(__DIR__) . '/' . ltrim(DB_SSL_CA_PATH, '/')) ? 'yes' : 'no');
line('');

$host = trim(DB_HOST);
$port = (int) DB_PORT;

line('[1] DNS TEST');
$ips = @gethostbynamel($host);
if ($ips === false || empty($ips)) {
    line('DNS_RESULT', 'FAILED');
    line('ACTION', 'DB_HOST tidak bisa di-resolve. Pakai host Public Access dari Aiven, bukan private/VPC host. Pastikan ENV Production di Vercel sudah di-redeploy.');
    exit;
}
line('DNS_RESULT', 'OK');
line('IP', implode(', ', $ips));
line('');

line('[2] TCP PORT TEST');
$errno = 0;
$errstr = '';
$socket = @fsockopen($host, $port, $errno, $errstr, 8);
if (!$socket) {
    line('TCP_RESULT', 'FAILED');
    line('ERROR', trim($errno . ' ' . $errstr));
    line('ACTION', 'Port salah atau public access Aiven belum aktif. Copy port dari Aiven Connection Information.');
    exit;
}
fclose($socket);
line('TCP_RESULT', 'OK');
line('');

line('[3] PDO MYSQL TEST');
$dsn = 'mysql:host=' . $host . ';port=' . $port . ';dbname=' . DB_NAME . ';charset=' . DB_CHARSET;
$options = [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES => false,
];

$sslMode = strtolower((string) DB_SSL_MODE);
$caPath = dirname(__DIR__) . '/' . ltrim(DB_SSL_CA_PATH, '/');
if (in_array($sslMode, ['required', 'require', 'verify-ca', 'verify-full', 'true', '1'], true)) {
    if (!is_file($caPath)) {
        line('PDO_RESULT', 'FAILED');
        line('ERROR', 'CA certificate tidak ditemukan: ' . $caPath);
        line('ACTION', 'Download CA certificate dari Aiven, rename menjadi ca.pem, simpan ke app/ca.pem.');
        exit;
    }
    if (defined('PDO::MYSQL_ATTR_SSL_CA')) {
        $options[PDO::MYSQL_ATTR_SSL_CA] = $caPath;
    }
}

try {
    $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
    $row = $pdo->query('SELECT 1 AS ok')->fetch();
    line('PDO_RESULT', 'OK');
    line('QUERY_RESULT', json_encode($row));
    line('');
    line('STATUS', 'KONEKSI DATABASE BERHASIL');
} catch (Throwable $e) {
    line('PDO_RESULT', 'FAILED');
    line('ERROR', $e->getMessage());
    line('ACTION', 'Cek DB_USER, DB_PASS, DB_NAME, SSL CA, dan pastikan file SQL sudah di-import.');
}
