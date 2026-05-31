<?php
declare(strict_types=1);

if ($method !== 'POST') {
    json_response(['ok' => false, 'error' => 'Method not allowed'], 405);
}

$body = read_json_body();
$writes = $body['writes'] ?? [];
if (!is_array($writes)) {
    json_response(['ok' => false, 'error' => 'writes harus array'], 400);
}

fs_batch_write($pdo, $writes);
json_response(['ok' => true]);
