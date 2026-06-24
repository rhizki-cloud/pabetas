<?php

$uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH) ?: '/';
$uri = rawurldecode($uri);

if ($uri === '/install.php' && (getenv('APP_ENV') ?: 'local') === 'production') {
    http_response_code(404);
    exit('Not Found');
}

$publicRoot = realpath(__DIR__ . '/../web');

if (!$publicRoot) {
    http_response_code(500);
    exit('Folder web tidak ditemukan.');
}

$target = $uri === '/'
    ? $publicRoot . '/index.php'
    : realpath($publicRoot . $uri);

if (!$target || strpos($target, $publicRoot) !== 0 || !is_file($target)) {
    http_response_code(404);
    echo 'Halaman tidak ditemukan';
    exit;
}

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
    'ico'  => 'image/x-icon',
    'webp' => 'image/webp',
    'json' => 'application/json; charset=utf-8',
    'txt'  => 'text/plain; charset=utf-8',
];

header('Content-Type: ' . ($mimeTypes[$extension] ?? 'application/octet-stream'));
header('Cache-Control: public, max-age=31536000');

readfile($target);
exit;