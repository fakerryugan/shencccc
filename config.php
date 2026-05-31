<?php
declare(strict_types=1);

require __DIR__ . '/includes/load-env.php';
muat_env(__DIR__ . '/.env');

$DB_HOST = env_str('DB_HOST', '127.0.0.1');
$DB_PORT = env_str('DB_PORT', '5432');
$DB_NAME = env_str('DB_NAME', 'sencha_recruitment');
$DB_USER = env_str('DB_USER', 'postgres');
$DB_PASS = env_str('DB_PASS', '');

$configLocal = __DIR__ . '/config.local.php';
if (is_file($configLocal)) {
    require $configLocal;
}

if (($envPass = getenv('PGPASSWORD')) !== false && $envPass !== '') {
    $DB_PASS = $envPass;
}

$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
];

$dbNameQuoted = str_replace("'", "''", $DB_NAME);
$dsn = "pgsql:host={$DB_HOST};port={$DB_PORT};dbname='{$dbNameQuoted}'";

try {
    $pdo = new PDO($dsn, $DB_USER, $DB_PASS, $options);
    $pdo->exec("SET client_encoding TO 'UTF8'");
} catch (PDOException $e) {
    $detail = $e->getMessage();
    if (PHP_SAPI === 'cli' || getenv('SENCHA_CLI_TEST')) {
        throw $e;
    }
    http_response_code(500);
    if (stripos($detail, 'could not find driver') !== false) {
        exit('PHP belum punya driver PostgreSQL (pdo_pgsql). Aktifkan di php.ini lalu restart server.');
    }
    exit('Kesalahan koneksi PostgreSQL ke ' . $DB_HOST . '. Periksa .env — ' . $detail);
}
