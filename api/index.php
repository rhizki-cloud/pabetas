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
    $extension = strtolower(pathinfo($target, PATHINFO_EXTENSION));

    if ($extension === 'php') {
        require $target;
        exit;
    }

    $mimeTypes = [
        'css'  => 'text/css; charset=utf-8',
        'js'   => 'application/javascript; charset=utf-8',
        'svg'  => 'image/svg+xml',
        'png'  => 'image/png',
        'jpg'  => 'image/jpeg',
        'jpeg' => 'image/jpeg',
        'gif'  => 'image/gif',
        'webp' => 'image/webp',
        'ico'  => 'image/x-icon',
        'json' => 'application/json; charset=utf-8',
        'pdf'  => 'application/pdf',
        'mp3'  => 'audio/mpeg',
        'wav'  => 'audio/wav',
        'woff' => 'font/woff',
        'woff2'=> 'font/woff2',
    ];

    header('Content-Type: ' . ($mimeTypes[$extension] ?? 'application/octet-stream'));
    header('Cache-Control: public, max-age=86400');
    readfile($target);
    exit;
}

http_response_code(404);
echo 'Halaman tidak ditemukan';
