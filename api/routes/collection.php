<?php
declare(strict_types=1);

$path = implode('/', array_slice($segments, 2));
if ($path === '') {
    json_response(['ok' => false, 'error' => 'Path koleksi kosong'], 400);
}

$wheres = null;
$limit = null;
if ($method === 'GET') {
    if (!empty($_GET['where'])) {
        $decoded = json_decode((string) $_GET['where'], true);
        if (is_array($decoded)) {
            $wheres = $decoded;
        }
    }
    if (isset($_GET['limit'])) {
        $limit = (int) $_GET['limit'];
    }
    $offset = isset($_GET['offset']) ? max(0, (int) $_GET['offset']) : 0;
    $order = (string) ($_GET['order'] ?? 'updated_at');
    if ($order !== 'data_at') {
        $order = 'updated_at';
    }
    if (!empty($_GET['count'])) {
        json_response(['ok' => true, 'count' => fs_collection_count($pdo, $path, $wheres)]);
    }

    $light = !empty($_GET['light']) && $path === 'applicants';
    $orderDir = strtoupper((string) ($_GET['order_dir'] ?? 'DESC')) === 'ASC' ? 'ASC' : 'DESC';
    $rows = fs_collection_list($pdo, $path, $wheres, $limit, $offset, $order, $light, $orderDir);
    $docs = array_map(static function ($r) {
        return [
            'path' => $r['path'],
            'id' => fs_path_to_id($r['path']),
            'data' => $r['data'],
            'updated_at' => $r['updated_at'],
        ];
    }, $rows);
    $hasMore = $limit !== null && $limit > 0 && count($rows) >= $limit;
    json_response(['ok' => true, 'docs' => $docs, 'hasMore' => $hasMore]);
}

if ($method === 'POST') {
    $body = read_json_body();
    $data = $body['data'] ?? [];
    $id = trim((string) ($body['id'] ?? ''));
    if ($id === '') {
        $id = bin2hex(random_bytes(12));
    }
    $fullPath = fs_normalize_path($path, $id);
    fs_doc_set($pdo, $fullPath, is_array($data) ? $data : [], false);
    json_response(['ok' => true, 'path' => $fullPath, 'id' => $id]);
}

json_response(['ok' => false, 'error' => 'Method not allowed'], 405);
