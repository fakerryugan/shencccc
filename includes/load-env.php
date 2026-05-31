<?php
declare(strict_types=1);

function muat_env(string $path): void
{
    if (!is_readable($path)) {
        return;
    }
    $baris = file($path, FILE_IGNORE_NEW_LINES);
    if ($baris === false) {
        return;
    }
    foreach ($baris as $line) {
        $line = trim($line);
        if ($line === '' || str_starts_with($line, '#')) {
            continue;
        }
        if (!str_contains($line, '=')) {
            continue;
        }
        [$key, $value] = explode('=', $line, 2);
        $key = trim($key);
        $value = trim($value);
        if ($value !== '' && ($value[0] === '"' || $value[0] === "'")) {
            $quote = $value[0];
            if (str_ends_with($value, $quote) && strlen($value) >= 2) {
                $value = substr($value, 1, -1);
            }
        }
        if ($key === '' || array_key_exists($key, $_ENV)) {
            continue;
        }
        $_ENV[$key] = $value;
        putenv("{$key}={$value}");
    }
}

function env_str(string $key, string $default = ''): string
{
    $v = $_ENV[$key] ?? getenv($key);
    if ($v === false || $v === '') {
        return $default;
    }
    return (string) $v;
}
