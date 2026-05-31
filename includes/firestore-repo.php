<?php
declare(strict_types=1);

/** Path Firestore: applicants/id, applicants/id/messages/msgId */
function fs_normalize_path(string ...$segments): string
{
    $parts = [];
    foreach ($segments as $seg) {
        if ($seg === null || $seg === '') {
            continue;
        }
        $parts[] = trim((string) $seg, '/');
    }
    return implode('/', $parts);
}

function fs_doc_get(PDO $pdo, string $path): ?array
{
    $st = $pdo->prepare('SELECT path, data, updated_at FROM fs_documents WHERE path = :p');
    $st->execute(['p' => $path]);
    $row = $st->fetch();
    if (!$row) {
        return null;
    }
    return [
        'path' => $row['path'],
        'data' => json_decode($row['data'] ?: '{}', true) ?: [],
        'updated_at' => $row['updated_at'],
    ];
}

/** Field berat yang tidak dikirim saat list ringan pelamar (foto/CV tetap di DB). */
function fs_applicant_light_data(array $data): array
{
    foreach (['photos', 'cvPdf', 'cvFiles', 'docPhotos', 'lampiran', 'cvBase64'] as $k) {
        unset($data[$k]);
    }
    return $data;
}

function fs_collection_count(PDO $pdo, string $collectionPath, ?array $wheres = null): int
{
    $collectionPath = trim($collectionPath, '/');
    $sql = 'SELECT COUNT(*) FROM fs_documents WHERE path LIKE :prefix AND path NOT LIKE :nosub';
    $params = [
        'prefix' => $collectionPath . '/%',
        'nosub' => $collectionPath . '/%/%',
    ];

    if ($wheres) {
        $wi = 0;
        foreach ($wheres as $w) {
            $field = preg_replace('/[^a-zA-Z0-9_]/', '', (string) ($w['field'] ?? ''));
            $op = $w['op'] ?? '==';
            if ($field === '' || $op !== '==') {
                continue;
            }
            $key = 'wf' . $wi;
            $sql .= " AND data->>'{$field}' = :{$key}";
            $params[$key] = (string) ($w['value'] ?? '');
            $wi++;
        }
    }

    $st = $pdo->prepare($sql);
    $st->execute($params);
    return (int) $st->fetchColumn();
}

function fs_collection_list(
    PDO $pdo,
    string $collectionPath,
    ?array $wheres = null,
    ?int $limit = null,
    int $offset = 0,
    string $order = 'updated_at',
    bool $lightApplicants = false,
    string $orderDir = 'DESC'
): array {
    $collectionPath = trim($collectionPath, '/');
    $sql = 'SELECT path, data, updated_at FROM fs_documents WHERE path LIKE :prefix AND path NOT LIKE :nosub';
    $params = [
        'prefix' => $collectionPath . '/%',
        'nosub' => $collectionPath . '/%/%',
    ];

    if ($wheres) {
        $wi = 0;
        foreach ($wheres as $w) {
            $field = preg_replace('/[^a-zA-Z0-9_]/', '', (string) ($w['field'] ?? ''));
            $op = $w['op'] ?? '==';
            if ($field === '' || $op !== '==') {
                continue;
            }
            $key = 'wf' . $wi;
            $sql .= " AND data->>'{$field}' = :{$key}";
            $params[$key] = (string) ($w['value'] ?? '');
            $wi++;
        }
    }

    $dir = strtoupper($orderDir) === 'ASC' ? 'ASC' : 'DESC';
    if ($order === 'data_at') {
        $sql .= " ORDER BY COALESCE((data->'at'->>'_seconds')::bigint, EXTRACT(EPOCH FROM updated_at)::bigint) {$dir} NULLS LAST";
    } else {
        $sql .= " ORDER BY updated_at {$dir}";
    }

    if ($limit !== null && $limit > 0) {
        $sql .= ' LIMIT ' . (int) $limit;
    }
    if ($offset > 0) {
        $sql .= ' OFFSET ' . (int) $offset;
    }

    $st = $pdo->prepare($sql);
    $st->execute($params);
    $rows = [];
    $isApplicantRoot = ($collectionPath === 'applicants');
    while ($row = $st->fetch()) {
        $data = json_decode($row['data'] ?: '{}', true) ?: [];
        if ($lightApplicants && $isApplicantRoot) {
            $data = fs_applicant_light_data($data);
        }
        $rows[] = [
            'path' => $row['path'],
            'data' => $data,
            'updated_at' => $row['updated_at'],
        ];
    }
    return $rows;
}

function fs_match_wheres(array $data, array $wheres): bool
{
    foreach ($wheres as $w) {
        $field = $w['field'] ?? '';
        $op = $w['op'] ?? '==';
        $val = $w['value'] ?? null;
        $cur = $data[$field] ?? null;
        if ($op === '==') {
            if ($cur != $val) {
                return false;
            }
        }
    }
    return true;
}

function fs_apply_field_ops(array $data, array $patch): array
{
    foreach ($patch as $key => $val) {
        if (is_array($val) && isset($val['__op'])) {
            $op = $val['__op'];
            if ($op === 'delete') {
                unset($data[$key]);
            } elseif ($op === 'increment') {
                $data[$key] = (int) ($data[$key] ?? 0) + (int) ($val['amount'] ?? 0);
            } elseif ($op === 'arrayUnion') {
                $existing = is_array($data[$key] ?? null) ? $data[$key] : [];
                $add = is_array($val['elements'] ?? null) ? $val['elements'] : [];
                $data[$key] = array_values(array_unique(array_merge($existing, $add), SORT_REGULAR));
            } elseif ($op === 'arrayRemove') {
                $existing = is_array($data[$key] ?? null) ? $data[$key] : [];
                $rem = is_array($val['elements'] ?? null) ? $val['elements'] : [];
                $data[$key] = array_values(array_filter($existing, fn($x) => !in_array($x, $rem, true)));
            }
        } elseif (is_array($val) && isset($val['__ts']) && $val['__ts'] === 'server') {
            $data[$key] = date('c');
        } else {
            $data[$key] = $val;
        }
    }
    return $data;
}

/** Ubah placeholder Firestore { __ts: server } / _seconds ke nilai yang bisa dibaca UI. */
function fs_normalize_firestore_values(mixed $value): mixed
{
    if (!is_array($value)) {
        return $value;
    }
    if (isset($value['__ts']) && $value['__ts'] === 'server') {
        return date('c');
    }
    if (isset($value['_seconds']) && is_numeric($value['_seconds'])) {
        return date('c', (int) $value['_seconds']);
    }
    $out = [];
    foreach ($value as $k => $v) {
        $out[$k] = fs_normalize_firestore_values($v);
    }
    return $out;
}

function fs_doc_set(PDO $pdo, string $path, array $data, bool $merge = false): void
{
    if ($merge) {
        $existing = fs_doc_get($pdo, $path);
        $data = array_merge($existing['data'] ?? [], $data);
    }
    $data = fs_normalize_firestore_values($data);
    if (!is_array($data)) {
        $data = [];
    }
    $json = json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    $st = $pdo->prepare(
        'INSERT INTO fs_documents (path, data, updated_at) VALUES (:p, :d::jsonb, NOW())
         ON CONFLICT (path) DO UPDATE SET data = EXCLUDED.data, updated_at = NOW()'
    );
    $st->execute(['p' => $path, 'd' => $json ?: '{}']);
}

function fs_doc_update(PDO $pdo, string $path, array $patch): void
{
    $existing = fs_doc_get($pdo, $path);
    if (!$existing) {
        throw new RuntimeException('Dokumen tidak ditemukan: ' . $path);
    }
    $data = fs_apply_field_ops($existing['data'], $patch);
    fs_doc_set($pdo, $path, $data, false);
}

function fs_doc_delete(PDO $pdo, string $path): void
{
    $st = $pdo->prepare('DELETE FROM fs_documents WHERE path = :p OR path LIKE :child');
    $st->execute(['p' => $path, 'child' => $path . '/%']);
}

function fs_batch_write(PDO $pdo, array $writes): void
{
    $pdo->beginTransaction();
    try {
        foreach ($writes as $w) {
            $op = $w['op'] ?? 'set';
            $path = $w['path'] ?? '';
            if ($path === '') {
                continue;
            }
            if ($op === 'delete') {
                fs_doc_delete($pdo, $path);
            } elseif ($op === 'update') {
                fs_doc_update($pdo, $path, $w['data'] ?? []);
            } else {
                fs_doc_set($pdo, $path, $w['data'] ?? [], !empty($w['merge']));
            }
        }
        $pdo->commit();
    } catch (Throwable $e) {
        $pdo->rollBack();
        throw $e;
    }
}

function fs_path_to_id(string $path): string
{
    $parts = explode('/', $path);
    return end($parts) ?: $path;
}
