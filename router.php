<?php
declare(strict_types=1);

$uri = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';

// Serve physical static files directly (e.g. public/app.html, assets, etc.)
$file = __DIR__ . $uri;
if ($uri !== '/' && is_file($file) && !is_dir($file)) {
    return false;
}

// Redirect root to frontend
if ($uri === '/' || $uri === '/index.php') {
    header('Location: /public/app.html', true, 302);
    exit;
}

// Delegate dynamic routes (API) to Laravel's index.php
$_SERVER['SCRIPT_NAME'] = '/index.php';
$_SERVER['SCRIPT_FILENAME'] = __DIR__ . '/public/index.php';

require __DIR__ . '/public/index.php';
return true;
