<?php
function app_is_https_request() {
    if (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') return true;
    if (($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https') return true;
    if (($_SERVER['HTTP_X_FORWARDED_SSL'] ?? '') === 'on') return true;
    return false;
}

if (session_status() === PHP_SESSION_NONE) {
    session_set_cookie_params([
        'lifetime' => 0,
        'path' => '/',
        'httponly' => true,
        'samesite' => 'Lax',
        'secure' => app_is_https_request(),
    ]);
    session_start();
}

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/helpers.php';
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/live_game.php';
require_once __DIR__ . '/academic.php';

header('X-Frame-Options: SAMEORIGIN');
header('X-Content-Type-Options: nosniff');
header('Referrer-Policy: strict-origin-when-cross-origin');

// Vercel serverless kadang tidak menjaga session file antar request.
// Fallback cookie bertanda tangan ini mengisi ulang $_SESSION sebelum halaman dicek.
auth_hydrate_from_cookie();

if (isset($_SESSION['user_id'])) {
    if (!isset($_SESSION['last_activity'])) {
        $_SESSION['last_activity'] = time();
    }

    if (time() - $_SESSION['last_activity'] > SESSION_TIMEOUT) {
        logout_user();
        redirect('login.php?timeout=1');
    }

    $_SESSION['last_activity'] = time();
    auth_write_cookie($_SESSION['user_id'], $_SESSION['role'] ?? 'murid');
}
