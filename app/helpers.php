<?php
function e($value) {
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

function app_base_url() {
    if (defined('APP_URL') && APP_URL !== '') {
        return rtrim(APP_URL, '/');
    }

    $proto = 'http';
    if (!empty($_SERVER['HTTP_X_FORWARDED_PROTO'])) {
        $proto = trim(explode(',', $_SERVER['HTTP_X_FORWARDED_PROTO'])[0]);
    } elseif (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') {
        $proto = 'https';
    }

    $host = $_SERVER['HTTP_X_FORWARDED_HOST'] ?? $_SERVER['HTTP_HOST'] ?? '';
    $host = trim(explode(',', $host)[0]);

    return $host !== '' ? $proto . '://' . $host : '';
}

function url($path = '') {
    if (preg_match('#^https?://#i', (string)$path)) {
        return $path;
    }

    $base = app_base_url();
    $path = ltrim((string)$path, '/');

    if ($base === '') {
        return '/' . $path;
    }

    return rtrim($base, '/') . '/' . $path;
}

function redirect($path) {
    $location = url($path);

    if (!headers_sent()) {
        header('Location: ' . $location, true, 302);
        exit;
    }

    echo '<!doctype html><meta charset="utf-8"><script>window.location.href=' . json_encode($location) . ';</script>';
    echo '<p>Mengalihkan ke <a href="' . e($location) . '">' . e($location) . '</a></p>';
    exit;
}

function csrf_token() {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function csrf_field() {
    return '<input type="hidden" name="csrf_token" value="' . e(csrf_token()) . '">';
}

function verify_csrf($token = null) {
    $token = $token ?? ($_POST['csrf_token'] ?? $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '');
    if (!hash_equals($_SESSION['csrf_token'] ?? '', $token)) {
        http_response_code(419);
        exit('CSRF token tidak valid. Muat ulang halaman lalu coba lagi.');
    }
}

function flash($key, $message = null) {
    if ($message !== null) {
        $_SESSION['flash'][$key] = $message;
        return;
    }
    $msg = $_SESSION['flash'][$key] ?? null;
    unset($_SESSION['flash'][$key]);
    return $msg;
}

function json_response($data, $status = 200) {
    http_response_code($status);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit;
}

function random_room_code($length = 6) {
    $chars = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789';
    $code = '';
    for ($i=0; $i<$length; $i++) $code .= $chars[random_int(0, strlen($chars)-1)];
    return $code;
}

function normalize_answer($answer) {
    $answer = trim(strtolower((string)$answer));
    $answer = str_replace([' ', '.', ','], ['', '', '.'], $answer);
    return $answer;
}
