<?php

$applicantsDir = __DIR__ . '/../storage/app/private/applicants';
if (!file_exists($applicantsDir)) {
    echo "Directory not found: $applicantsDir\n";
    exit(1);
}

$dirs = glob($applicantsDir . '/*');
echo "Found " . count($dirs) . " applicant directories.\n";

$fixedCount = 0;
$skippedCount = 0;

foreach ($dirs as $dir) {
    if (!is_dir($dir)) continue;
    $cvPath = $dir . '/cv.dat';
    if (!file_exists($cvPath)) {
        continue;
    }

    $content = file_get_contents($cvPath);
    $data = json_decode($content, true);

    if (json_last_error() === JSON_ERROR_NONE && is_array($data)) {
        // It's a JSON string! Let's extract the details
        $name = $data['name'] ?? 'cv.pdf';
        $mime = $data['mime'] ?? 'application/pdf';
        $dataUrl = $data['data'] ?? '';

        $binaryData = null;
        if (str_starts_with($dataUrl, 'data:')) {
            $parts = explode(',', $dataUrl, 2);
            if (count($parts) === 2) {
                $binaryData = base64_decode($parts[1]);
                // extract mime from data URL if not explicitly provided
                if (preg_match('/^data:([^;]+);base64/', $parts[0], $m)) {
                    $mime = $m[1];
                }
            } else {
                $binaryData = $dataUrl; // fallback
            }
        } else if (preg_match('/^[a-zA-Z0-9\/\r\n+]*={0,2}$/', $dataUrl)) {
            $binaryData = base64_decode($dataUrl);
        } else {
            $binaryData = $dataUrl;
        }

        if ($binaryData !== null) {
            file_put_contents($cvPath, $binaryData);
            file_put_contents($dir . '/cv_mime.txt', $mime);
            file_put_contents($dir . '/cv_name.txt', $name);
            echo "Fixed applicant: " . basename($dir) . " ($name, $mime)\n";
            $fixedCount++;
        } else {
            echo "Failed to decode data for applicant: " . basename($dir) . "\n";
            $skippedCount++;
        }
    } else {
        // Not a JSON. Let's check if it's base64 or binary
        if (str_starts_with($content, 'data:')) {
            $parts = explode(',', $content, 2);
            if (count($parts) === 2) {
                $binaryData = base64_decode($parts[1]);
                if (preg_match('/^data:([^;]+);base64/', $parts[0], $m)) {
                    $mime = $m[1];
                } else {
                    $mime = 'application/pdf';
                }
                file_put_contents($cvPath, $binaryData);
                file_put_contents($dir . '/cv_mime.txt', $mime);
                file_put_contents($dir . '/cv_name.txt', 'cv.pdf');
                echo "Converted data URI to binary for applicant: " . basename($dir) . "\n";
                $fixedCount++;
            }
        } else {
            // Already raw binary/processed
            $skippedCount++;
        }
    }
}

echo "\nDone! Fixed: $fixedCount, Skipped/Already OK: $skippedCount\n";
