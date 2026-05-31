<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class ImportSenchaBackup extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'sencha:import-backup {file : The path to the JSON backup file}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Import Sencha Recruitment Firestore backup JSON, writing applicants to MySQL and CV files to private storage';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $filePath = $this->argument('file');

        if (!file_exists($filePath) || !is_readable($filePath)) {
            $this->error("File not found or not readable: {$filePath}");
            return Command::FAILURE;
        }

        $this->info("Reading backup file (this may take a few seconds)...");
        $startTime = microtime(true);

        $jsonContent = file_get_contents($filePath);
        $backup = json_decode($jsonContent, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            $this->error("Invalid JSON format: " . json_last_error_msg());
            return Command::FAILURE;
        }

        $collections = $backup['collections'] ?? [];
        if (empty($collections)) {
            $this->warn("No collections found in backup.");
            return Command::SUCCESS;
        }

        $this->info("Cleaning and recreating storage folder...");
        $applicantsDir = storage_path('app/private/applicants');
        if (file_exists($applicantsDir)) {
            $this->deleteDirRecursive($applicantsDir);
        }
        mkdir($applicantsDir, 0755, true);

        $this->info("Parsing and preparing records...");
        $otherRows = [];
        $applicantRows = [];
        $fileImportCount = 0;

        foreach ($collections as $colName => $items) {
            // Handle single document collections represented as a single object
            if (is_array($items) && isset($items['id']) && isset($items['data'])) {
                $items = [$items];
            }

            if (!is_array($items)) continue;

            $this->info("Processing collection: {$colName} (" . count($items) . " items)...");

            if ($colName === 'applicants') {
                foreach ($items as $item) {
                    if (!is_array($item)) continue;
                    
                    $id = $item['id'] ?? '';
                    if ($id === '') continue;

                    $data = $item['data'] ?? [];
                    $data = $this->normalizeFirestoreValues($data);

                    // Extract heavy CV & Photo files
                    $cvFile = $data['cvFile'] ?? null;
                    $photos = $data['photos'] ?? [];
                    $docPhotos = $data['docPhotos'] ?? [];

                    // Save files as binary inside storage
                    $this->saveApplicantFiles($id, $cvFile, $photos, $docPhotos);
                    $fileImportCount++;

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

                    $applicantRows[] = [
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
                    ];

                    // Process nested messages
                    if (isset($item['messages']) && is_array($item['messages'])) {
                        foreach ($item['messages'] as $msg) {
                            if (!is_array($msg)) continue;
                            $msgId = $msg['id'] ?? '';
                            $msgData = $msg['data'] ?? [];
                            if ($msgId === '') continue;

                            $msgData = $this->normalizeFirestoreValues($msgData);
                            $otherRows[] = [
                                'path' => "applicants/{$id}/messages/{$msgId}",
                                'parent_path' => "applicants/{$id}/messages",
                                'data' => json_encode($msgData, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
                                'updated_at' => now(),
                            ];
                        }
                    }

                    // Process nested visits
                    if (isset($item['visits']) && is_array($item['visits'])) {
                        foreach ($item['visits'] as $v) {
                            if (!is_array($v)) continue;
                            $vId = $v['id'] ?? '';
                            $vData = $v['data'] ?? [];
                            if ($vId === '') continue;

                            $vData = $this->normalizeFirestoreValues($vData);
                            $otherRows[] = [
                                'path' => "applicants/{$id}/visits/{$vId}",
                                'parent_path' => "applicants/{$id}/visits",
                                'data' => json_encode($vData, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
                                'updated_at' => now(),
                            ];
                        }
                    }
                }
            } else {
                foreach ($items as $item) {
                    if (!is_array($item)) continue;
                    
                    $id = $item['id'] ?? '';
                    if ($id === '') continue;

                    $path = $colName . '/' . $id;
                    $parentPath = $colName;
                    $data = $item['data'] ?? [];

                    $data = $this->normalizeFirestoreValues($data);

                    $otherRows[] = [
                        'path' => $path,
                        'parent_path' => $parentPath,
                        'data' => json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
                        'updated_at' => now(),
                    ];
                }
            }
        }

        $this->info("\nRunning bulk upsert in transactions...");
        
        DB::beginTransaction();
        try {
            // Upsert applicants
            if (!empty($applicantRows)) {
                $chunks = array_chunk($applicantRows, 100);
                foreach ($chunks as $chunk) {
                    DB::table('applicants')->upsert(
                        $chunk,
                        ['id'],
                        ['nama', 'nama_normalized', 'whatsapp', 'whatsapp_normalized', 'tanggal_lahir', 'umur_saat_input', 'masih_bekerja', 'posisi', 'posisi_list', 'status', 'source', 'undangan_by_posisi', 'access_token', 'cv_mode', 'catatan', 'catatan_list', 'updated_at']
                    );
                }
                $this->info("Successfully imported " . count($applicantRows) . " applicants into SQL database.");
            }

            // Upsert other collections
            if (!empty($otherRows)) {
                $chunks = array_chunk($otherRows, 200);
                foreach ($chunks as $chunk) {
                    DB::table('fs_documents')->upsert(
                        $chunk,
                        ['path'],
                        ['data', 'parent_path', 'updated_at']
                    );
                }
                $this->info("Successfully imported " . count($otherRows) . " documents to general fs_documents table.");
            }

            DB::commit();
            
            $duration = round(microtime(true) - $startTime, 2);
            $this->info("\nSuccessfully completed import in {$duration} seconds!");
            $this->info("Applicants imported: " . count($applicantRows));
            $this->info("Files directories created: {$fileImportCount}");
            $this->info("Other documents imported: " . count($otherRows));
            
            return Command::SUCCESS;
        } catch (\Throwable $e) {
            DB::rollBack();
            $this->error("\nImport failed: " . $e->getMessage());
            return Command::FAILURE;
        }
    }

    /**
     * Save base64/structured CV & photos as binary files inside storage
     */
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

    /**
     * Recursively delete directory
     */
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

    /**
     * Normalize Firestore values (__ts, _seconds) into ISO strings
     */
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
}
