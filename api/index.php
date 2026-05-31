<?php
declare(strict_types=1);

require __DIR__ . '/../includes/http.php';
cors_headers();

$uri = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';
$base = rtrim(dirname($_SERVER['SCRIPT_NAME'] ?? ''), '/\\');
if ($base !== '' && str_starts_with($uri, $base)) {
    $uri = substr($uri, strlen($base)) ?: '/';
}
$uri = '/' . trim($uri, '/');
$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';

require __DIR__ . '/../config.php';
require __DIR__ . '/../includes/firestore-repo.php';
require __DIR__ . '/../includes/sessions.php';

session_purge_expired($pdo);

$segments = array_values(array_filter(explode('/', trim($uri, '/'))));
$v1 = array_search('v1', $segments, true);
if ($v1 === false) {
    json_response(['ok' => false, 'error' => 'Not found'], 404);
}
$segments = array_slice($segments, $v1);

$action = $segments[1] ?? '';

try {
    switch ($action) {
        case 'health':
            require __DIR__ . '/routes/health.php';
            break;
        case 'auth':
            require __DIR__ . '/routes/auth.php';
            break;
        case 'anon':
            require __DIR__ . '/routes/anon.php';
            break;
        case 'doc':
            require __DIR__ . '/routes/doc.php';
            break;
        case 'collection':
            require __DIR__ . '/routes/collection.php';
            break;
        case 'batch':
            require __DIR__ . '/routes/batch.php';
            break;
        case 'changes':
            require __DIR__ . '/routes/changes.php';
            break;
        default:
            json_response(['ok' => false, 'error' => 'Endpoint tidak dikenal'], 404);
    }
} catch (Throwable $e) {
    json_response(['ok' => false, 'error' => $e->getMessage()], 500);
}
