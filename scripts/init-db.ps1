# Inisialisasi skema PostgreSQL — Sencha Recruitment
$ErrorActionPreference = "Stop"
$root = Split-Path -Parent $PSScriptRoot
$sqlFile = Join-Path $root "schema.pgsql.sql"

$dbName = "sencha_recruitment"
$user = "postgres"
$dbHost = '127.0.0.1'
$port = '5432'

$envFile = Join-Path $root ".env"
if (Test-Path $envFile) {
    Get-Content $envFile | ForEach-Object {
        $line = $_.Trim()
        if ($line -eq '' -or $line.StartsWith('#')) { return }
        if ($line -match '^DB_HOST=(.+)$') { $dbHost = $Matches[1].Trim('"', "'") }
        if ($line -match '^DB_PORT=(.+)$') { $port = $Matches[1].Trim('"', "'") }
        if ($line -match '^DB_NAME=(.+)$') { $dbName = $Matches[1].Trim('"', "'") }
        if ($line -match '^DB_USER=(.+)$') { $user = $Matches[1].Trim('"', "'") }
        if ($line -match '^DB_PASS=(.+)$') { $env:PGPASSWORD = $Matches[1].Trim('"', "'") }
    }
}

$psql = $null
foreach ($p in @(
    "C:\Program Files\PostgreSQL\18\bin\psql.exe",
    "C:\Program Files\PostgreSQL\17\bin\psql.exe",
    "C:\Program Files\PostgreSQL\16\bin\psql.exe"
)) {
    if (Test-Path $p) { $psql = $p; break }
}
if (-not $psql) {
    $cmd = Get-Command psql -ErrorAction SilentlyContinue
    if ($cmd) { $psql = $cmd.Source }
}
if (-not $psql) {
    Write-Error "psql tidak ditemukan. Pasang PostgreSQL atau jalankan schema.pgsql.sql lewat pgAdmin."
}

Write-Host "Menjalankan skema ke database '$dbName' (buat database dulu di pgAdmin jika belum ada)..."
& $psql -h $dbHost -p $port -U $user -d $dbName -f $sqlFile
if ($LASTEXITCODE -ne 0) {
    Write-Error "Gagal. Periksa .env (DB_PASS) atau set PGPASSWORD."
}
Write-Host "Skema selesai."
