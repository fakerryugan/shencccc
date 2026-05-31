@echo off
cd /d "%~dp0"
where pm2 >nul 2>&1 && pm2 delete sencha-recruitment 2>nul
echo Sencha Recruitment dihentikan.
pause
