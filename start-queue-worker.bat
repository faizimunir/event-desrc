@echo off
REM Laravel Queue Worker - Auto Start Script
REM Update path sesuai lokasi project Anda

cd /d C:\xampp\htdocs\event-desrc

echo Starting Laravel Queue Worker...
echo Press Ctrl+C to stop

php artisan queue:work redis --sleep=3 --tries=3 --max-time=3600 --timeout=90

pause

