<?php
declare(strict_types=1);

$path = implode('/', array_slice($segments, 2));
if ($path === '') {
    json_response(['ok' => false, 'error' => 'Path dokumen kosong'], 400);
}

if ($method === 'GET') {
    $doc = fs_doc_get($pdo, $path);
    if (!$doc) {
        json_response(['ok' => false, 'exists' => false], 404);
    }
    json_response([
        'ok' => true,
        'exists' => true,
        'path' => $doc['path'],
        'id' => fs_path_to_id($path),
        'data' => $doc['data'],
        'updated_at' => $doc['updated_at'],
    ]);
}

$body = read_json_body();

if ($method === 'PUT' || $method === 'POST') {
    $data = $body['data'] ?? [];
    $merge = !empty($body['merge']);
    if (isset($body['patch'])) {
        fs_doc_update($pdo, $path, $body['patch']);
    } else {
        fs_doc_set($pdo, $path, is_array($data) ? $data : [], $merge);
    }
    $doc = fs_doc_get($pdo, $path);
    json_response(['ok' => true, 'path' => $path, 'id' => fs_path_to_id($path), 'data' => $doc['data'] ?? []]);
}

if ($method === 'DELETE') {
    fs_doc_delete($pdo, $path);
    json_response(['ok' => true]);
}

json_response(['ok' => false, 'error' => 'Method not allowed'], 405);
