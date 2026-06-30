<?php

function auth_is_https_request() {
    if (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') return true;
    if (($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https') return true;
    if (($_SERVER['HTTP_X_FORWARDED_SSL'] ?? '') === 'on') return true;
    return false;
}

function auth_cookie_name() {
    return 'pabetas_auth';
}

function auth_cookie_secret() {
    $parts = [
        defined('APP_URL') ? APP_URL : '',
        defined('DB_HOST') ? DB_HOST : '',
        defined('DB_NAME') ? DB_NAME : '',
        defined('DB_USER') ? DB_USER : '',
        defined('DB_PASS') ? DB_PASS : '',
        __DIR__,
    ];
    return hash('sha256', implode('|', $parts));
}

function auth_base64url_encode($value) {
    return rtrim(strtr(base64_encode($value), '+/', '-_'), '=');
}

function auth_base64url_decode($value) {
    $value = strtr($value, '-_', '+/');
    $pad = strlen($value) % 4;
    if ($pad) $value .= str_repeat('=', 4 - $pad);
    return base64_decode($value, true);
}

function auth_cookie_options($expires) {
    return [
        'expires' => $expires,
        'path' => '/',
        'secure' => auth_is_https_request(),
        'httponly' => true,
        'samesite' => 'Lax',
    ];
}

function auth_write_cookie($userId, $role) {
    $expires = time() + SESSION_TIMEOUT;
    $payload = json_encode([
        'uid' => (int) $userId,
        'role' => (string) $role,
        'exp' => $expires,
    ], JSON_UNESCAPED_SLASHES);

    $encoded = auth_base64url_encode($payload);
    $signature = hash_hmac('sha256', $encoded, auth_cookie_secret());
    setcookie(auth_cookie_name(), $encoded . '.' . $signature, auth_cookie_options($expires));
}

function auth_clear_cookie() {
    setcookie(auth_cookie_name(), '', auth_cookie_options(time() - 3600));
    unset($_COOKIE[auth_cookie_name()]);
}

function auth_read_cookie_payload() {
    $raw = $_COOKIE[auth_cookie_name()] ?? '';
    if (!$raw || !str_contains($raw, '.')) return null;

    [$encoded, $signature] = explode('.', $raw, 2);
    $expected = hash_hmac('sha256', $encoded, auth_cookie_secret());
    if (!hash_equals($expected, $signature)) return null;

    $json = auth_base64url_decode($encoded);
    if ($json === false) return null;

    $payload = json_decode($json, true);
    if (!is_array($payload)) return null;
    if (empty($payload['uid']) || empty($payload['role']) || empty($payload['exp'])) return null;
    if ((int) $payload['exp'] < time()) return null;

    return $payload;
}

function auth_hydrate_from_cookie() {
    if (!empty($_SESSION['user_id'])) return true;

    $payload = auth_read_cookie_payload();
    if (!$payload) return false;

    $stmt = db()->prepare('SELECT id, name, username, role, status, avatar_key FROM users WHERE id=? AND status=1 LIMIT 1');
    $stmt->execute([(int) $payload['uid']]);
    $user = $stmt->fetch();
    if (!$user) {
        auth_clear_cookie();
        return false;
    }

    $_SESSION['user_id'] = (int) $user['id'];
    $_SESSION['role'] = $user['role'];
    $_SESSION['name'] = $user['name'];
    $_SESSION['last_activity'] = time();
    auth_write_cookie($user['id'], $user['role']);
    return true;
}

function current_user() {
    if (empty($_SESSION['user_id'])) {
        auth_hydrate_from_cookie();
    }

    if (empty($_SESSION['user_id'])) return null;

    static $user = null;
    if ($user !== null && (int) $user['id'] === (int) $_SESSION['user_id']) return $user;

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

    if (session_status() === PHP_SESSION_ACTIVE) {
        session_regenerate_id(true);
    }

    $_SESSION['user_id'] = (int) $user['id'];
    $_SESSION['role'] = $user['role'];
    $_SESSION['name'] = $user['name'];
    $_SESSION['last_activity'] = time();

    auth_write_cookie($user['id'], $user['role']);
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
    $_SESSION = [];
    if (session_status() === PHP_SESSION_ACTIVE) {
        session_unset();
        session_destroy();
    }
    auth_clear_cookie();
}
