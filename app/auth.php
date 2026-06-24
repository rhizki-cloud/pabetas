<?php

function auth_cookie_options($maxAge = null) {
    $secure = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
        || (($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https');

    return [
        'expires' => $maxAge ? time() + $maxAge : time() - 3600,
        'path' => '/',
        'secure' => $secure,
        'httponly' => true,
        'samesite' => 'Lax',
    ];
}

function auth_make_cookie_value($user) {
    $data = $user['id'] . '|' . $user['role'];
    $sig = hash_hmac('sha256', $data, AUTH_SECRET);

    return base64_encode($data . '|' . $sig);
}

function auth_set_cookie($user) {
    $maxAge = AUTH_COOKIE_DAYS * 24 * 60 * 60;
    setcookie(AUTH_COOKIE, auth_make_cookie_value($user), auth_cookie_options($maxAge));
}

function auth_clear_cookie() {
    setcookie(AUTH_COOKIE, '', auth_cookie_options(null));
}

function auth_restore_from_cookie() {
    if (!empty($_SESSION['user_id'])) {
        return true;
    }

    $raw = $_COOKIE[AUTH_COOKIE] ?? '';

    if ($raw === '') {
        return false;
    }

    $decoded = base64_decode($raw, true);

    if (!$decoded) {
        return false;
    }

    $parts = explode('|', $decoded);

    if (count($parts) !== 3) {
        return false;
    }

    [$userId, $role, $sig] = $parts;
    $data = $userId . '|' . $role;
    $expected = hash_hmac('sha256', $data, AUTH_SECRET);

    if (!hash_equals($expected, $sig)) {
        auth_clear_cookie();
        return false;
    }

    $stmt = db()->prepare('SELECT id, name, username, role, status, avatar_key FROM users WHERE id=? AND status=1 LIMIT 1');
    $stmt->execute([$userId]);
    $user = $stmt->fetch();

    if (!$user) {
        auth_clear_cookie();
        return false;
    }

    $_SESSION['user_id'] = $user['id'];
    $_SESSION['role'] = $user['role'];
    $_SESSION['name'] = $user['name'];
    $_SESSION['last_activity'] = time();

    return true;
}

function current_user() {
    auth_restore_from_cookie();

    if (empty($_SESSION['user_id'])) {
        return null;
    }

    static $user = null;

    if ($user !== null) {
        return $user;
    }

    $stmt = db()->prepare('SELECT id, name, username, role, status, avatar_key FROM users WHERE id=? AND status=1 LIMIT 1');
    $stmt->execute([$_SESSION['user_id']]);
    $user = $stmt->fetch();

    if (!$user) {
        logout_user();
        return null;
    }

    return $user;
}

function login_user($username, $password) {
    $stmt = db()->prepare('SELECT * FROM users WHERE username=? AND status=1 LIMIT 1');
    $stmt->execute([$username]);
    $user = $stmt->fetch();

    if (!$user || !password_verify($password, $user['password'])) {
        return false;
    }

    session_regenerate_id(true);

    $_SESSION['user_id'] = $user['id'];
    $_SESSION['role'] = $user['role'];
    $_SESSION['name'] = $user['name'];
    $_SESSION['last_activity'] = time();

    auth_set_cookie($user);

    return true;
}

function require_login() {
    if (!current_user()) {
        redirect('login.php');
    }
}

function require_role($role) {
    require_login();

    if (($_SESSION['role'] ?? '') !== $role) {
        http_response_code(403);
        exit('Akses ditolak. Halaman ini hanya untuk ' . e($role) . '.');
    }
}

function logout_user() {
    auth_clear_cookie();
    session_unset();
    session_destroy();
}