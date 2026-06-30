<?php
require_once __DIR__ . '/../app/init.php';
header('Content-Type: text/plain; charset=utf-8');

$username = trim($_GET['u'] ?? 'siswa');
$password = $_GET['p'] ?? 'siswa123';

echo "PABETAS Login Check\n";
echo "===================\n";
echo "REQUEST_METHOD: " . ($_SERVER['REQUEST_METHOD'] ?? '-') . "\n";
echo "USERNAME_TEST: " . $username . "\n";
echo "SESSION_ACTIVE: " . (session_status() === PHP_SESSION_ACTIVE ? 'yes' : 'no') . "\n";
echo "COOKIE_PRESENT: " . (!empty($_COOKIE[auth_cookie_name()]) ? 'yes' : 'no') . "\n";

try {
    $stmt = db()->prepare('SELECT id, name, username, password, role, status FROM users WHERE username=? LIMIT 1');
    $stmt->execute([$username]);
    $user = $stmt->fetch();

    if (!$user) {
        echo "USER_RESULT: NOT_FOUND\n";
        echo "ACTION: Import ulang database/pabetas_advanced.sql atau buat user demo.\n";
        exit;
    }

    echo "USER_RESULT: FOUND\n";
    echo "USER_ID: " . (int)$user['id'] . "\n";
    echo "ROLE: " . ($user['role'] ?? '-') . "\n";
    echo "STATUS: " . ($user['status'] ?? '-') . "\n";
    echo "HASH_PREFIX: " . substr((string)$user['password'], 0, 7) . "\n";
    echo "PASSWORD_VERIFY: " . (password_verify($password, $user['password']) ? 'OK' : 'FAILED') . "\n";

    if ((int)$user['status'] !== 1) {
        echo "ACTION: status user harus 1.\n";
    } elseif (!password_verify($password, $user['password'])) {
        echo "ACTION: Password di database tidak cocok dengan akun demo. Reset password demo.\n";
    } else {
        echo "ACTION: User dan password OK. Jika tombol tetap balik ke login, masalahnya di cookie/session redirect.\n";
    }
} catch (Throwable $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
}
