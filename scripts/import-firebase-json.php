<?php
/**
 * Impor dokumen Firestore (format export JSON) ke fs_documents.
 * Usage: php scripts/import-firebase-json.php path/to/export.json
 */
declare(strict_types=1);

if ($argc < 2) {
    fwrite(STDERR, "Usage: php import-firebase-json.php <file.json>\n");
    exit(1);
}

$file = $argv[1];
if (!is_readable($file)) {
    fwrite(STDERR, "File tidak bisa dibaca: {$file}\n");
    exit(1);
}

putenv('SENCHA_CLI_TEST=1');
require __DIR__ . '/../config.php';
require __DIR__ . '/../includes/firestore-repo.php';

$raw = file_get_contents($file);
$data = json_decode($raw, true);
if (!is_array($data)) {
    fwrite(STDERR, "JSON tidak valid\n");
    exit(1);
}

$docs = $data['documents'] ?? $data;
if (isset($data['collections'])) {
    $docs = [];
    foreach ($data['collections'] as $col => $items) {
        foreach ($items as $id => $body) {
            $docs[] = ['path' => "{$col}/{$id}", 'data' => $body];
        }
    }
}

$count = 0;
$pdo->beginTransaction();
try {
    foreach ($docs as $entry) {
        $path = $entry['path'] ?? '';
        if ($path === '' && isset($entry['name'])) {
            $path = preg_replace('#^.*/documents/#', '', $entry['name']);
        }
        if ($path === '') {
            continue;
        }
        $payload = $entry['data'] ?? $entry['fields'] ?? $entry;
        if (isset($payload['fields'])) {
            continue;
        }
        if (!is_array($payload)) {
            continue;
        }
        fs_doc_set($pdo, $path, $payload, false);
        $count++;
        if ($count % 200 === 0) {
            echo "… {$count}\n";
        }
    }
    $pdo->commit();
} catch (Throwable $e) {
    $pdo->rollBack();
    throw $e;
}

echo "Selesai: {$count} dokumen.\n";
