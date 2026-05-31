<?php
declare(strict_types=1);

function session_id_from_request(): ?string
{
    $h = $_SERVER['HTTP_X_SESSION_ID'] ?? '';
    if ($h !== '') {
        return preg_replace('/[^a-zA-Z0-9_-]/', '', $h);
    }
    return null;
}

function session_create(PDO $pdo, string $role, array $payload = [], int $ttlHours = 72): string
{
    $id = bin2hex(random_bytes(24));
    $expires = (new DateTimeImmutable('+' . $ttlHours . ' hours'))->format('Y-m-d H:i:sP');
    $st = $pdo->prepare(
        'INSERT INTO app_sessions (session_id, role, payload, expires_at) VALUES (:id, :role, :payload::jsonb, :exp)'
    );
    $st->execute([
        'id' => $id,
        'role' => $role,
        'payload' => json_encode($payload, JSON_UNESCAPED_UNICODE),
        'exp' => $expires,
    ]);
    return $id;
}

function session_get(PDO $pdo, ?string $id): ?array
{
    if (!$id) {
        return null;
    }
    $st = $pdo->prepare(
        'SELECT session_id, role, payload, expires_at FROM app_sessions WHERE session_id = :id AND expires_at > NOW()'
    );
    $st->execute(['id' => $id]);
    $row = $st->fetch();
    if (!$row) {
        return null;
    }
    return [
        'session_id' => $row['session_id'],
        'role' => $row['role'],
        'payload' => json_decode($row['payload'] ?: '{}', true) ?: [],
        'expires_at' => $row['expires_at'],
    ];
}

function session_purge_expired(PDO $pdo): void
{
    $pdo->exec('DELETE FROM app_sessions WHERE expires_at <= NOW()');
}
