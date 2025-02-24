@echo off
cd /d %~dp0
php artisan trials:process-reminders
