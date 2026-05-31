<?php
declare(strict_types=1);

$sub = $segments[2] ?? '';
$body = read_json_body();

if ($sub === 'hrd' && $method === 'POST') {
    $user = trim((string) ($body['username'] ?? ''));
    $pass = (string) ($body['password'] ?? '');
    if ($user === '' || $pass === '') {
        json_response(['ok' => false, 'error' => 'Username dan password wajib'], 400);
    }
    $hash = hash('sha256', $pass);
    $st = $pdo->prepare('SELECT username FROM hrd_users WHERE username = :u AND password_hash = :h');
    $st->execute(['u' => $user, 'h' => $hash]);
    if (!$st->fetch()) {
        json_response(['ok' => false, 'error' => 'Login gagal'], 401);
    }
    $sid = session_create($pdo, 'hrd', ['username' => $user]);
    json_response(['ok' => true, 'sessionId' => $sid, 'role' => 'hrd']);
}

if ($sub === 'pelamar' && $method === 'POST') {
    $nama = trim((string) ($body['nama'] ?? $body['fullName'] ?? ''));
    $wa = preg_replace('/\D+/', '', (string) ($body['whatsapp'] ?? $body['wa'] ?? ''));
    if ($nama === '' || $wa === '') {
        json_response(['ok' => false, 'error' => 'Nama dan WhatsApp wajib'], 400);
    }
    $indexPath = 'applicant_login_index/' . $wa;
    $doc = fs_doc_get($pdo, $indexPath);
    if (!$doc) {
        json_response(['ok' => false, 'error' => 'Data login tidak ditemukan'], 401);
    }
    $d = $doc['data'];
    $storedName = trim((string) ($d['fullName'] ?? $d['nama'] ?? ''));
    if (mb_strtolower($storedName) !== mb_strtolower($nama)) {
        json_response(['ok' => false, 'error' => 'Nama tidak cocok'], 401);
    }
    $applicantId = (string) ($d['applicantId'] ?? $d['id'] ?? '');
    $sid = session_create($pdo, 'pelamar', [
        'applicantId' => $applicantId,
        'whatsapp' => $wa,
        'fullName' => $storedName,
    ]);
    json_response(['ok' => true, 'sessionId' => $sid, 'role' => 'pelamar', 'applicantId' => $applicantId]);
}

json_response(['ok' => false, 'error' => 'Not found'], 404);
