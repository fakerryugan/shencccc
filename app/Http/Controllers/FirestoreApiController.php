<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use RuntimeException;

class FirestoreApiController extends Controller
{
    /**
     * GET /api/v1/health
     */
    public function health()
    {
        return response()->json(['ok' => true]);
    }

    /**
     * POST /api/v1/anon
     */
    public function anon()
    {
        $this->purgeExpiredSessions();
        $sid = $this->createSession('anon', ['type' => 'anonymous']);
        return response()->json(['ok' => true, 'sessionId' => $sid]);
    }

    /**
     * POST /api/v1/auth/hrd
     */
    public function authHrd(Request $request)
    {
        $this->purgeExpiredSessions();
        
        $username = trim((string) $request->input('username'));
        $password = (string) $request->input('password');

        if ($username === '' || $password === '') {
            return response()->json(['ok' => false, 'error' => 'Username dan password wajib'], 400);
        }

        $hash = hash('sha256', $password);
        $user = DB::table('hrd_users')
            ->where('username', $username)
            ->where('password_hash', $hash)
            ->first();

        if (!$user) {
            return response()->json(['ok' => false, 'error' => 'Login gagal'], 401);
        }

        $sid = $this->createSession('hrd', ['username' => $username]);
        return response()->json(['ok' => true, 'sessionId' => $sid, 'role' => 'hrd']);
    }

    /**
     * POST /api/v1/auth/pelamar
     */
    public function authPelamar(Request $request)
    {
        $this->purgeExpiredSessions();

        $nama = trim((string) ($request->input('nama') ?? $request->input('fullName') ?? ''));
        $wa = preg_replace('/\D+/', '', (string) ($request->input('whatsapp') ?? $request->input('wa') ?? ''));

        if ($nama === '' || $wa === '') {
            return response()->json(['ok' => false, 'error' => 'Nama dan WhatsApp wajib'], 400);
        }

        $indexPath = 'applicant_login_index/' . $wa;
        $doc = $this->getDocumentByPath($indexPath);

        if (!$doc) {
            return response()->json(['ok' => false, 'error' => 'Data login tidak ditemukan'], 401);
        }

        $storedName = trim((string) ($doc['data']['fullName'] ?? $doc['data']['nama'] ?? ''));
        if (mb_strtolower($storedName) !== mb_strtolower($nama)) {
            return response()->json(['ok' => false, 'error' => 'Nama tidak cocok'], 401);
        }

        $applicantId = (string) ($doc['data']['applicantId'] ?? $doc['data']['id'] ?? '');
        $sid = $this->createSession('pelamar', [
            'applicantId' => $applicantId,
            'whatsapp' => $wa,
            'fullName' => $storedName,
        ]);

        return response()->json([
            'ok' => true,
            'sessionId' => $sid,
            'role' => 'pelamar',
            'applicantId' => $applicantId
        ]);
    }

    /**
     * GET/PUT/POST/PATCH/DELETE /api/v1/doc/{path}
     */
    public function handleDoc(Request $request, $path)
    {
        $path = trim($path, '/');
        $method = $request->method();

        if ($method === 'GET') {
            $doc = $this->getDocumentByPath($path);
            if (!$doc) {
                return response()->json(['ok' => false, 'exists' => false], 404);
            }
            return response()->json([
                'ok' => true,
                'exists' => true,
                'path' => $doc['path'],
                'id' => $this->getPathId($path),
                'data' => $doc['data'],
                'updated_at' => $doc['updated_at'],
            ]);
        }

        $body = $request->json()->all();

        if ($method === 'PUT' || $method === 'POST') {
            if (isset($body['patch'])) {
                $this->updateDocumentPatch($path, $body['patch']);
            } else {
                $data = $body['data'] ?? [];
                $merge = !empty($body['merge']);
                $this->setDocument($path, $data, $merge);
            }
            
            $doc = $this->getDocumentByPath($path);
            return response()->json([
                'ok' => true,
                'path' => $path,
                'id' => $this->getPathId($path),
                'data' => $doc['data'] ?? []
            ]);
        }

        if ($method === 'DELETE') {
            $this->deleteDocument($path);
            return response()->json(['ok' => true]);
        }

        return response()->json(['ok' => false, 'error' => 'Method not allowed'], 405);
    }

    /**
     * GET/POST /api/v1/collection/{path}
     */
    public function handleCollection(Request $request, $path)
    {
        $path = trim($path, '/');
        $method = $request->method();

        if ($method === 'GET') {
            $wheres = null;
            $limit = null;

            if ($request->has('where')) {
                $decoded = json_decode((string) $request->input('where'), true);
                if (is_array($decoded)) {
                    $wheres = $decoded;
                }
            }

            if ($request->has('limit')) {
                $limit = (int) $request->input('limit');
            }

            $offset = max(0, (int) $request->input('offset', 0));
            $order = (string) $request->input('order', 'updated_at');
            if ($order !== 'data_at') {
                $order = 'updated_at';
            }

            // High Performance Indexed collection count
            if ($request->has('count')) {
                $count = $this->countCollection($path, $wheres);
                return response()->json(['ok' => true, 'count' => $count]);
            }

            $light = ($path === 'applicants') && empty($request->input('heavy'));
            $orderDir = strtoupper((string) $request->input('order_dir', 'DESC')) === 'ASC' ? 'ASC' : 'DESC';

            // High Performance Indexed collection listing
            $rows = $this->listCollection($path, $wheres, $limit, $offset, $order, $light, $orderDir);
            
            $docs = array_map(function ($r) {
                return [
                    'path' => $r['path'],
                    'id' => $this->getPathId($r['path']),
                    'data' => $r['data'],
                    'updated_at' => $r['updated_at'],
                ];
            }, $rows);

            $hasMore = $limit !== null && $limit > 0 && count($rows) >= $limit;
            return response()->json(['ok' => true, 'docs' => $docs, 'hasMore' => $hasMore]);
        }

        if ($method === 'POST') {
            $body = $request->json()->all();
            $data = $body['data'] ?? [];
            $id = trim((string) ($body['id'] ?? ''));
            if ($id === '') {
                $id = bin2hex(random_bytes(12));
            }
            $fullPath = $path . '/' . $id;
            $this->setDocument($fullPath, is_array($data) ? $data : [], false);
            return response()->json(['ok' => true, 'path' => $fullPath, 'id' => $id]);
        }

        return response()->json(['ok' => false, 'error' => 'Method not allowed'], 405);
    }

    /**
     * POST /api/v1/batch
     */
    public function batch(Request $request)
    {
        $body = $request->json()->all();
        $writes = $body['writes'] ?? [];

        if (!is_array($writes)) {
            return response()->json(['ok' => false, 'error' => 'writes harus array'], 400);
        }

        DB::beginTransaction();
        try {
            foreach ($writes as $w) {
                $op = $w['op'] ?? 'set';
                $path = trim($w['path'] ?? '', '/');
                if ($path === '') continue;

                if ($op === 'delete') {
                    $this->deleteDocument($path);
                } elseif ($op === 'update') {
                    $this->updateDocumentPatch($path, $w['data'] ?? []);
                } else {
                    $this->setDocument($path, $w['data'] ?? [], !empty($w['merge']));
                }
            }
            DB::commit();
            return response()->json(['ok' => true]);
        } catch (\Throwable $e) {
            DB::rollBack();
            return response()->json(['ok' => false, 'error' => $e->getMessage()], 500);
        }
    }

    /**
     * GET /api/v1/changes
     */
    public function changes(Request $request)
    {
        $since = $request->input('since', '1970-01-01T00:00:00Z');
        $prefix = trim((string) $request->input('prefix', ''), '/');

        $query = DB::table('fs_documents')
            ->where('updated_at', '>', $since);

        if ($prefix !== '') {
            $query->where(function($q) use ($prefix) {
                $q->where('path', $prefix)
                  ->orWhere('path', 'LIKE', $prefix . '/%');
            });
        }

        $rows = $query->orderBy('updated_at', 'ASC')
            ->limit(500)
            ->get(['path', 'updated_at']);

        $paths = [];
        foreach ($rows as $row) {
            $paths[] = [
                'path' => $row->path,
                'updated_at' => date('c', strtotime($row->updated_at))
            ];
        }

        return response()->json([
            'ok' => true,
            'changes' => $paths,
            'serverTime' => date('c')
        ]);
    }

    /* -------------------------------------------------------------------------- */
    /*                         DATABASE HELPERS (OPTIMIZED)                       */
    /* -------------------------------------------------------------------------- */

    private function getDocumentByPath(string $path): ?array
    {
        $path = trim($path, '/');
        if (str_starts_with($path, 'applicants/') && !str_contains(substr($path, 11), '/')) {
            $id = substr($path, 11);
            $app = DB::table('applicants')->where('id', $id)->first();
            if (!$app) return null;
            
            $files = $this->getApplicantFiles($id);
            
            $data = [
                'nama' => $app->nama,
                'namaNormalized' => $app->nama_normalized,
                'whatsapp' => $app->whatsapp,
                'whatsappNormalized' => $app->whatsapp_normalized,
                'tanggalLahir' => $app->tanggal_lahir,
                'umurSaatInput' => $app->umur_saat_input,
                'masihBekerja' => (bool) $app->masih_bekerja,
                'posisi' => $app->posisi,
                'posisiList' => json_decode($app->posisi_list ?? '[]', true) ?: [],
                'status' => $app->status,
                'source' => $app->source,
                'undanganByPosisi' => json_decode($app->undangan_by_posisi ?? '{}', true) ?: new \stdClass(),
                'accessToken' => $app->access_token,
                'cvMode' => $app->cv_mode,
                'catatan' => $app->catatan,
                'catatanList' => json_decode($app->catatan_list ?? '[]', true) ?: [],
                'createdAt' => $app->created_at ? ['__firestoreTimestamp' => date('c', strtotime($app->created_at))] : null,
                'updatedAt' => $app->updated_at ? ['__firestoreTimestamp' => date('c', strtotime($app->updated_at))] : null,
                'cvFile' => $files['cvFile'] ?? null,
                'cvFiles' => isset($files['cvFile']) ? [$files['cvFile']] : [],
                'photos' => $files['photos'] ?? [],
                'docPhotos' => $files['docPhotos'] ?? []
            ];
            
            return [
                'path' => $path,
                'data' => $data,
                'updated_at' => date('c', strtotime($app->updated_at ?? 'now')),
            ];
        }

        $row = DB::table('fs_documents')->where('path', $path)->first();
        if (!$row) {
            return null;
        }
        return [
            'path' => $row->path,
            'data' => json_decode($row->data, true) ?: [],
            'updated_at' => date('c', strtotime($row->updated_at)),
        ];
    }

    private function setDocument(string $path, array $data, bool $merge = false): void
    {
        $path = trim($path, '/');
        if (str_starts_with($path, 'applicants/') && !str_contains(substr($path, 11), '/')) {
            $id = substr($path, 11);
            if ($merge) {
                $existing = $this->getDocumentByPath($path);
                $data = array_merge($existing['data'] ?? [], $data);
            }

            $data = $this->normalizeFirestoreValues($data);

            $cvFile = $data['cvFile'] ?? null;
            $photos = $data['photos'] ?? [];
            $docPhotos = $data['docPhotos'] ?? [];

            $createdAt = null;
            if (isset($data['createdAt'])) {
                $createdAt = is_array($data['createdAt']) ? ($data['createdAt']['__firestoreTimestamp'] ?? $data['createdAt']['_seconds'] ?? null) : $data['createdAt'];
            }
            $updatedAt = null;
            if (isset($data['updatedAt'])) {
                $updatedAt = is_array($data['updatedAt']) ? ($data['updatedAt']['__firestoreTimestamp'] ?? $data['updatedAt']['_seconds'] ?? null) : $data['updatedAt'];
            }

            $created_at = $createdAt ? date('Y-m-d H:i:s', strtotime($createdAt)) : now();
            $updated_at = $updatedAt ? date('Y-m-d H:i:s', strtotime($updatedAt)) : now();

            DB::table('applicants')->upsert(
                [
                    'id' => $id,
                    'nama' => $data['nama'] ?? null,
                    'nama_normalized' => $data['namaNormalized'] ?? null,
                    'whatsapp' => $data['whatsapp'] ?? null,
                    'whatsapp_normalized' => $data['whatsappNormalized'] ?? null,
                    'tanggal_lahir' => $data['tanggalLahir'] ?? null,
                    'umur_saat_input' => $data['umurSaatInput'] ?? null,
                    'masih_bekerja' => !empty($data['masihBekerja']),
                    'posisi' => $data['posisi'] ?? null,
                    'posisi_list' => json_encode($data['posisiList'] ?? []),
                    'status' => $data['status'] ?? 'baru',
                    'source' => $data['source'] ?? null,
                    'undangan_by_posisi' => json_encode($data['undanganByPosisi'] ?? new \stdClass()),
                    'access_token' => $data['access_token'] ?? $data['accessToken'] ?? null,
                    'cv_mode' => $data['cvMode'] ?? null,
                    'catatan' => $data['catatan'] ?? null,
                    'catatan_list' => json_encode($data['catatanList'] ?? []),
                    'created_at' => $created_at,
                    'updated_at' => $updated_at,
                ],
                ['id'],
                ['nama', 'nama_normalized', 'whatsapp', 'whatsapp_normalized', 'tanggal_lahir', 'umur_saat_input', 'masih_bekerja', 'posisi', 'posisi_list', 'status', 'source', 'undangan_by_posisi', 'access_token', 'cv_mode', 'catatan', 'catatan_list', 'updated_at']
            );

            if ($cvFile !== null || !empty($photos) || !empty($docPhotos)) {
                $this->saveApplicantFiles($id, $cvFile, $photos, $docPhotos);
            }
            return;
        }

        if ($merge) {
            $existing = $this->getDocumentByPath($path);
            $data = array_merge($existing['data'] ?? [], $data);
        }

        $data = $this->normalizeFirestoreValues($data);
        if (!is_array($data)) {
            $data = [];
        }

        $parentPath = $this->getParentPath($path);
        $json = json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

        DB::table('fs_documents')->upsert(
            [
                'path' => $path,
                'parent_path' => $parentPath,
                'data' => $json,
                'updated_at' => now(),
            ],
            ['path'],
            ['data', 'parent_path', 'updated_at']
        );
    }

    private function updateDocumentPatch(string $path, array $patch): void
    {
        $existing = $this->getDocumentByPath($path);
        if (!$existing) {
            throw new RuntimeException('Dokumen tidak ditemukan: ' . $path);
        }

        $data = $this->applyFieldOps($existing['data'], $patch);
        $this->setDocument($path, $data, false);
    }

    private function deleteDocument(string $path): void
    {
        $path = trim($path, '/');
        if (str_starts_with($path, 'applicants/') && !str_contains(substr($path, 11), '/')) {
            $id = substr($path, 11);
            DB::table('applicants')->where('id', $id)->delete();
            $this->deleteApplicantFiles($id);
            return;
        }
        if ($path === 'applicants') {
            DB::table('applicants')->delete();
            $dir = storage_path('app/private/applicants');
            if (file_exists($dir)) {
                $this->deleteDirRecursive($dir);
            }
            return;
        }

        DB::table('fs_documents')
            ->where('path', $path)
            ->orWhere('path', 'LIKE', $path . '/%')
            ->delete();
    }

    private function saveApplicantFiles(string $id, $cvFile, array $photos, array $docPhotos): void
    {
        $dir = storage_path('app/private/applicants/' . $id);
        if (!file_exists($dir)) {
            mkdir($dir, 0755, true);
        }

        if ($cvFile !== null) {
            $name = 'cv.pdf';
            $mime = 'application/pdf';
            $dataStr = '';

            if (is_array($cvFile)) {
                $name = $cvFile['name'] ?? 'cv.pdf';
                $mime = $cvFile['mime'] ?? 'application/pdf';
                $dataStr = $cvFile['data'] ?? '';
            } else if (is_string($cvFile)) {
                $dataStr = $cvFile;
            }

            if (!empty($dataStr)) {
                if (str_starts_with($dataStr, 'data:')) {
                    $parts = explode(',', $dataStr, 2);
                    if (count($parts) === 2) {
                        file_put_contents($dir . '/cv.dat', base64_decode($parts[1]));
                        if (preg_match('/^data:([^;]+);base64/', $parts[0], $m)) {
                            $mime = $m[1];
                        }
                    } else {
                        file_put_contents($dir . '/cv.dat', $dataStr);
                    }
                } else if (preg_match('/^[a-zA-Z0-9\/\r\n+]*={0,2}$/', $dataStr)) {
                    file_put_contents($dir . '/cv.dat', base64_decode($dataStr));
                } else {
                    file_put_contents($dir . '/cv.dat', $dataStr);
                }
            }

            file_put_contents($dir . '/cv_mime.txt', $mime);
            file_put_contents($dir . '/cv_name.txt', $name);
        }

        // Clean existing photos first
        foreach (glob($dir . '/photo_*') as $oldPhoto) {
            @unlink($oldPhoto);
        }
        
        $savedPhotosMeta = [];
        foreach ($photos as $idx => $photo) {
            if (empty($photo)) continue;
            
            $photoData = '';
            $photoMime = 'image/jpeg';
            
            if (is_array($photo)) {
                $photoData = $photo['data'] ?? '';
                $photoMime = $photo['mime'] ?? 'image/jpeg';
            } else if (is_string($photo)) {
                $photoData = $photo;
            }

            if (empty($photoData)) continue;

            if (str_starts_with($photoData, 'data:')) {
                $parts = explode(',', $photoData, 2);
                if (count($parts) === 2) {
                    file_put_contents($dir . "/photo_{$idx}.dat", base64_decode($parts[1]));
                    if (preg_match('/^data:([^;]+);base64/', $parts[0], $m)) {
                        $photoMime = $m[1];
                    }
                } else {
                    file_put_contents($dir . "/photo_{$idx}.dat", $photoData);
                }
            } else if (preg_match('/^[a-zA-Z0-9\/\r\n+]*={0,2}$/', $photoData)) {
                file_put_contents($dir . "/photo_{$idx}.dat", base64_decode($photoData));
            } else {
                file_put_contents($dir . "/photo_{$idx}.dat", $photoData);
            }
            
            $savedPhotosMeta[$idx] = $photoMime;
        }
        
        if (!empty($savedPhotosMeta)) {
            file_put_contents($dir . '/photos_meta.json', json_encode($savedPhotosMeta));
        }

        // Clean existing docPhotos first
        foreach (glob($dir . '/doc_photo_*') as $oldDocPhoto) {
            @unlink($oldDocPhoto);
        }
        
        $savedDocPhotosMeta = [];
        foreach ($docPhotos as $idx => $docPhoto) {
            if (empty($docPhoto)) continue;
            
            $docPhotoData = '';
            $docPhotoMime = 'image/jpeg';
            
            if (is_array($docPhoto)) {
                $docPhotoData = $docPhoto['data'] ?? '';
                $docPhotoMime = $docPhoto['mime'] ?? 'image/jpeg';
            } else if (is_string($docPhoto)) {
                $docPhotoData = $docPhoto;
            }

            if (empty($docPhotoData)) continue;

            if (str_starts_with($docPhotoData, 'data:')) {
                $parts = explode(',', $docPhotoData, 2);
                if (count($parts) === 2) {
                    file_put_contents($dir . "/doc_photo_{$idx}.dat", base64_decode($parts[1]));
                    if (preg_match('/^data:([^;]+);base64/', $parts[0], $m)) {
                        $docPhotoMime = $m[1];
                    }
                } else {
                    file_put_contents($dir . "/doc_photo_{$idx}.dat", $docPhotoData);
                }
            } else if (preg_match('/^[a-zA-Z0-9\/\r\n+]*={0,2}$/', $docPhotoData)) {
                file_put_contents($dir . "/doc_photo_{$idx}.dat", base64_decode($docPhotoData));
            } else {
                file_put_contents($dir . "/doc_photo_{$idx}.dat", $docPhotoData);
            }
            
            $savedDocPhotosMeta[$idx] = $docPhotoMime;
        }
        
        if (!empty($savedDocPhotosMeta)) {
            file_put_contents($dir . '/doc_photos_meta.json', json_encode($savedDocPhotosMeta));
        }
    }

    private function getApplicantFiles(string $id): array
    {
        $dir = storage_path('app/private/applicants/' . $id);
        $cvFile = null;
        $photos = [];
        $docPhotos = [];

        if (file_exists($dir)) {
            if (file_exists($dir . '/cv.dat')) {
                $rawCv = file_get_contents($dir . '/cv.dat');
                $mime = file_exists($dir . '/cv_mime.txt') ? trim(file_get_contents($dir . '/cv_mime.txt')) : 'application/pdf';
                $name = file_exists($dir . '/cv_name.txt') ? trim(file_get_contents($dir . '/cv_name.txt')) : 'cv.pdf';
                
                $dataUrlPrefix = str_starts_with($mime, 'data:') ? $mime : 'data:' . $mime . ';base64';
                if (!str_contains($dataUrlPrefix, ';base64')) {
                    $dataUrlPrefix .= ';base64';
                }
                
                $cvFile = [
                    'name' => $name,
                    'mime' => $mime,
                    'data' => $dataUrlPrefix . ',' . base64_encode($rawCv)
                ];
            }

            $photosMeta = [];
            if (file_exists($dir . '/photos_meta.json')) {
                $photosMeta = json_decode(file_get_contents($dir . '/photos_meta.json'), true) ?: [];
            }

            $photoFiles = glob($dir . '/photo_*');
            sort($photoFiles);
            foreach ($photoFiles as $photoFile) {
                preg_match('/photo_(\d+)\.dat/', basename($photoFile), $matches);
                $idx = isset($matches[1]) ? (int)$matches[1] : count($photos);
                
                $rawPhoto = file_get_contents($photoFile);
                $mime = $photosMeta[$idx] ?? 'data:image/jpeg;base64';
                
                $dataUrlPrefix = str_starts_with($mime, 'data:') ? $mime : 'data:' . $mime . ';base64';
                if (!str_contains($dataUrlPrefix, ';base64')) {
                    $dataUrlPrefix .= ';base64';
                }
                $photos[$idx] = $dataUrlPrefix . ',' . base64_encode($rawPhoto);
            }
            $photos = array_values($photos);

            $docPhotosMeta = [];
            if (file_exists($dir . '/doc_photos_meta.json')) {
                $docPhotosMeta = json_decode(file_get_contents($dir . '/doc_photos_meta.json'), true) ?: [];
            }

            $docPhotoFiles = glob($dir . '/doc_photo_*');
            sort($docPhotoFiles);
            foreach ($docPhotoFiles as $docPhotoFile) {
                preg_match('/doc_photo_(\d+)\.dat/', basename($docPhotoFile), $matches);
                $idx = isset($matches[1]) ? (int)$matches[1] : count($docPhotos);
                
                $rawDocPhoto = file_get_contents($docPhotoFile);
                $mime = $docPhotosMeta[$idx] ?? 'data:image/jpeg;base64';
                
                $dataUrlPrefix = str_starts_with($mime, 'data:') ? $mime : 'data:' . $mime . ';base64';
                if (!str_contains($dataUrlPrefix, ';base64')) {
                    $dataUrlPrefix .= ';base64';
                }
                $docPhotos[$idx] = $dataUrlPrefix . ',' . base64_encode($rawDocPhoto);
            }
            $docPhotos = array_values($docPhotos);
        }

        return [
            'cvFile' => $cvFile,
            'photos' => $photos,
            'docPhotos' => $docPhotos
        ];
    }

    private function deleteApplicantFiles(string $id): void
    {
        $dir = storage_path('app/private/applicants/' . $id);
        if (file_exists($dir)) {
            $this->deleteDirRecursive($dir);
        }
    }

    private function deleteDirRecursive(string $dir): void
    {
        if (file_exists($dir)) {
            $files = array_diff(scandir($dir), ['.', '..']);
            foreach ($files as $file) {
                $path = $dir . '/' . $file;
                is_dir($path) ? $this->deleteDirRecursive($path) : @unlink($path);
            }
            @rmdir($dir);
        }
    }

    private function countCollection(string $collectionPath, ?array $wheres = null): int
    {
        $collectionPath = trim($collectionPath, '/');
        if ($collectionPath === 'applicants') {
            $query = DB::table('applicants');
            if ($wheres) {
                foreach ($wheres as $w) {
                    $field = preg_replace('/[^a-zA-Z0-9_]/', '', (string) ($w['field'] ?? ''));
                    $op = $w['op'] ?? '==';
                    if ($field === '' || $op !== '==') continue;
                    
                    $col = Str::snake($field);
                    $query->where($col, (string) ($w['value'] ?? ''));
                }
            }
            return $query->count();
        }

        $query = DB::table('fs_documents')->where('parent_path', $collectionPath);

        if ($wheres) {
            foreach ($wheres as $w) {
                $field = preg_replace('/[^a-zA-Z0-9_]/', '', (string) ($w['field'] ?? ''));
                $op = $w['op'] ?? '==';
                if ($field === '' || $op !== '==') continue;

                $query->where('data->' . $field, (string) ($w['value'] ?? ''));
            }
        }

        return $query->count();
    }

    private function listCollection(
        string $collectionPath,
        ?array $wheres = null,
        ?int $limit = null,
        int $offset = 0,
        string $order = 'updated_at',
        bool $lightApplicants = false,
        string $orderDir = 'DESC'
    ): array {
        $collectionPath = trim($collectionPath, '/');
        
        if ($collectionPath === 'applicants') {
            $query = DB::table('applicants');
            
            if ($wheres) {
                foreach ($wheres as $w) {
                    $field = preg_replace('/[^a-zA-Z0-9_]/', '', (string) ($w['field'] ?? ''));
                    $op = $w['op'] ?? '==';
                    if ($field === '' || $op !== '==') continue;
                    
                    $col = Str::snake($field);
                    $query->where($col, (string) ($w['value'] ?? ''));
                }
            }
            
            $query->orderBy($order, $orderDir);
            
            if ($limit !== null && $limit > 0) {
                $query->limit($limit);
            }
            if ($offset > 0) {
                $query->offset($offset);
            }
            
            $rows = $query->get();
            $results = [];
            
            foreach ($rows as $row) {
                $data = [
                    'nama' => $row->nama,
                    'namaNormalized' => $row->nama_normalized,
                    'whatsapp' => $row->whatsapp,
                    'whatsappNormalized' => $row->whatsapp_normalized,
                    'tanggalLahir' => $row->tanggal_lahir,
                    'umurSaatInput' => $row->umur_saat_input,
                    'masihBekerja' => (bool) $row->masih_bekerja,
                    'posisi' => $row->posisi,
                    'posisiList' => json_decode($row->posisi_list ?? '[]', true) ?: [],
                    'status' => $row->status,
                    'source' => $row->source,
                    'undanganByPosisi' => json_decode($row->undangan_by_posisi ?? '{}', true) ?: new \stdClass(),
                    'accessToken' => $row->access_token,
                    'cvMode' => $row->cv_mode,
                    'catatan' => $row->catatan,
                    'catatanList' => json_decode($row->catatan_list ?? '[]', true) ?: [],
                    'createdAt' => $row->created_at ? ['__firestoreTimestamp' => date('c', strtotime($row->created_at))] : null,
                    'updatedAt' => $row->updated_at ? ['__firestoreTimestamp' => date('c', strtotime($row->updated_at))] : null,
                    'photos' => []
                ];
                
                $results[] = [
                    'path' => 'applicants/' . $row->id,
                    'data' => $data,
                    'updated_at' => date('c', strtotime($row->updated_at ?? 'now')),
                ];
            }
            return $results;
        }

        $query = DB::table('fs_documents')->where('parent_path', $collectionPath);

        if ($wheres) {
            foreach ($wheres as $w) {
                $field = preg_replace('/[^a-zA-Z0-9_]/', '', (string) ($w['field'] ?? ''));
                $op = $w['op'] ?? '==';
                if ($field === '' || $op !== '==') continue;

                $query->where('data->' . $field, (string) ($w['value'] ?? ''));
            }
        }

        if ($order === 'data_at') {
            $driver = DB::connection()->getDriverName();
            if ($driver === 'mysql' || $driver === 'mariadb') {
                $query->orderBy(DB::raw("COALESCE(CAST(json_unquote(json_extract(data, '$.at._seconds')) AS SIGNED), UNIX_TIMESTAMP(updated_at))"), $orderDir);
            } else {
                $query->orderBy(DB::raw("COALESCE((data->'at'->>'_seconds')::bigint, EXTRACT(EPOCH FROM updated_at)::bigint)"), $orderDir);
            }
        } else {
            $query->orderBy('updated_at', $orderDir);
        }

        if ($limit !== null && $limit > 0) {
            $query->limit($limit);
        }

        if ($offset > 0) {
            $query->offset($offset);
        }

        $driver = DB::connection()->getDriverName();
        $select = ['path', 'updated_at'];

        if ($lightApplicants && $collectionPath === 'applicants') {
            if ($driver === 'mysql' || $driver === 'mariadb') {
                $select[] = DB::raw("JSON_REMOVE(data, '$.photos', '$.photo', '$.cvFile', '$.cvFiles', '$.cvPdf', '$.cvBase64', '$.docPhotos', '$.lampiran') AS data");
            } elseif ($driver === 'pgsql') {
                $select[] = DB::raw("(data - 'photos' - 'photo' - 'cvFile' - 'cvFiles' - 'cvPdf' - 'cvBase64' - 'docPhotos' - 'lampiran') AS data");
            } else {
                $select[] = 'data';
            }
        } else {
            $select[] = 'data';
        }

        $rows = $query->get($select);
        $results = [];

        foreach ($rows as $row) {
            $data = json_decode($row->data, true) ?: [];

            $results[] = [
                'path' => $row->path,
                'data' => $data,
                'updated_at' => date('c', strtotime($row->updated_at)),
            ];
        }

        return $results;
    }

    /* -------------------------------------------------------------------------- */
    /*                               UTILITY HELPERS                              */
    /* -------------------------------------------------------------------------- */

    private function getParentPath(string $path): string
    {
        $parts = explode('/', trim($path, '/'));
        if (count($parts) <= 1) {
            return '';
        }
        array_pop($parts);
        return implode('/', $parts);
    }

    private function getPathId(string $path): string
    {
        $parts = explode('/', $path);
        return end($parts) ?: $path;
    }

    private function normalizeFirestoreValues($value)
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
            $out[$k] = $this->normalizeFirestoreValues($v);
        }
        return $out;
    }

    private function applyFieldOps(array $data, array $patch): array
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

    /* -------------------------------------------------------------------------- */
    /*                              SESSION MANAGEMENT                             */
    /* -------------------------------------------------------------------------- */

    private function createSession(string $role, array $payload = [], int $ttlHours = 72): string
    {
        $id = bin2hex(random_bytes(24));
        $expires = now()->addHours($ttlHours);

        DB::table('app_sessions')->insert([
            'session_id' => $id,
            'role' => $role,
            'payload' => json_encode($payload),
            'expires_at' => $expires,
            'created_at' => now(),
        ]);

        return $id;
    }

    private function purgeExpiredSessions(): void
    {
        DB::table('app_sessions')->where('expires_at', '<=', now())->delete();
    }
}
