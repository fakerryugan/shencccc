@echo off
setlocal EnableDelayedExpansion
title Sencha Recruitment - Start
cd /d "%~dp0"
set "APP_PORT=2022"
set "APP_URL=http://127.0.0.1:%APP_PORT%/public/app.html"
set "PM2_NAME=sencha-recruitment"
set "SENCHA_RECRUITMENT_PORT=%APP_PORT%"
set "PHP_CLI_SERVER_WORKERS=10"

echo ============================================================
echo    SENCHA RECRUITMENT - Start (PHP + PostgreSQL)
echo ============================================================
echo.

if not exist ".env" (
    echo [!] Salin .env.example ke .env lalu isi DB_PASS
    copy /Y ".env.example" ".env" >nul 2>&1
)

where php >nul 2>&1
if errorlevel 1 (
    echo [!] PHP tidak ada di PATH. Pasang XAMPP/BtSoft atau tambahkan php.exe ke PATH.
    pause
    exit /b 1
)

where pm2 >nul 2>&1
if errorlevel 1 (
    echo [*] PM2 belum ada. npm install lalu npm run pm2:start
    echo [*] Menjalankan php -S langsung...
    start "" "http://127.0.0.1:%APP_PORT%/public/app.html"
    php -S 0.0.0.0:%APP_PORT% -t . router.php
    exit /b 0
)

call npm run pm2:start 2>nul
if errorlevel 1 (
    npm install
    call npm run pm2:start
)

timeout /t 2 /nobreak >nul
start "" "%APP_URL%"
echo Buka: %APP_URL%
echo Stop: STOP.bat atau npm run pm2:stop
pause
