<?php
declare(strict_types=1);

/** Long-poll ringan: daftar path yang berubah sejak timestamp (untuk onSnapshot) */
if ($method !== 'GET') {
    json_response(['ok' => false, 'error' => 'Method not allowed'], 405);
}

$since = $_GET['since'] ?? '1970-01-01T00:00:00Z';
$prefix = trim((string) ($_GET['prefix'] ?? ''), '/');

$sql = 'SELECT path, updated_at FROM fs_documents WHERE updated_at > :since::timestamptz';
$params = ['since' => $since];
if ($prefix !== '') {
    $sql .= ' AND (path = :pfx OR path LIKE :pfxlike)';
    $params['pfx'] = $prefix;
    $params['pfxlike'] = $prefix . '/%';
}
$sql .= ' ORDER BY updated_at ASC LIMIT 500';

$st = $pdo->prepare($sql);
$st->execute($params);
$paths = [];
while ($row = $st->fetch()) {
    $paths[] = ['path' => $row['path'], 'updated_at' => $row['updated_at']];
}

json_response(['ok' => true, 'changes' => $paths, 'serverTime' => date('c')]);
