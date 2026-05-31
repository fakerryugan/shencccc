@echo off
setlocal
cd /d "%~dp0.."

where node >nul 2>&1
if errorlevel 1 exit /b 1

rem Tunggu jaringan/PostgreSQL siap setelah boot
timeout /t 15 /nobreak >nul

call npx pm2 resurrect >nul 2>&1
if errorlevel 1 (
    call npm run pm2:start >nul 2>&1
)

exit /b 0
