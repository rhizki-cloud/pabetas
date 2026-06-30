<?php

$uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH) ?: '/';
$uri = rawurldecode($uri);

if ($uri === '/install.php' && (getenv('APP_ENV') ?: 'local') === 'production') {
    http_response_code(404);
    exit('Not Found');
}

$publicRoot = realpath(__DIR__ . '/../public');

if ($uri === '/') {
    $target = $publicRoot . '/index.php';
} else {
    $target = realpath($publicRoot . $uri);
}

if ($target && str_starts_with($target, $publicRoot) && is_file($target)) {
    if (pathinfo($target, PATHINFO_EXTENSION) === 'php') {
        require $target;
        exit;
    }

    return false;
}

http_response_code(404);
echo 'Halaman tidak ditemukan';