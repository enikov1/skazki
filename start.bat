@echo off
cd /d "%~dp0"
where node >nul 2>nul || (echo Node.js is required. Install Node.js 20 or newer, then run this file again. & pause & exit /b 1)
if not exist node_modules\gulp (echo Installing project dependencies... & call npm install || exit /b 1)
call npm run dev
pause
