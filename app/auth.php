<?php
function current_user() {
    if (empty($_SESSION['user_id'])) return null;
    static $user = null;
    if ($user !== null) return $user;
    $stmt = db()->prepare('SELECT id, name, username, role, status, avatar_key FROM users WHERE id=? LIMIT 1');
    $stmt->execute([$_SESSION['user_id']]);
    $user = $stmt->fetch();
    return $user ?: null;
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
    session_unset();
    session_destroy();
}
