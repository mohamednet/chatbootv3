@echo off
cd /d %~dp0
:loop
php artisan schedule:run
timeout /t 1 /nobreak >nul
goto loop
