@echo off
php artisan queue:work database --queue=default --tries=3
