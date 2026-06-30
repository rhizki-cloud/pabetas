<?php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/helpers.php';
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/session.php';

pabetas_boot_session();

require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/live_game.php';
require_once __DIR__ . '/academic.php';

header('X-Frame-Options: SAMEORIGIN');
header('X-Content-Type-Options: nosniff');
header('Referrer-Policy: strict-origin-when-cross-origin');

if (isset($_SESSION['user_id'])) {
    if (!isset($_SESSION['last_activity'])) {
        $_SESSION['last_activity'] = time();
    }
    if (time() - $_SESSION['last_activity'] > SESSION_TIMEOUT) {
        session_unset();
        session_destroy();
        redirect('login.php?timeout=1');
    }
    $_SESSION['last_activity'] = time();
}
