<?php
declare(strict_types=1);

if ($method !== 'POST') {
    json_response(['ok' => false, 'error' => 'Method not allowed'], 405);
}

$sid = session_create($pdo, 'anon', ['type' => 'anonymous']);
json_response(['ok' => true, 'sessionId' => $sid]);
