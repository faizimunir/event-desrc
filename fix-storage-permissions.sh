#!/bin/bash

# Script untuk fix permission storage setelah setup Redis
# Usage: sudo ./fix-storage-permissions.sh

echo "=========================================="
echo "Fix Storage Permissions untuk Laravel"
echo "=========================================="
echo ""

# Detect web server user
if id "www-data" &>/dev/null; then
    WEB_USER="www-data"
elif id "apache" &>/dev/null; then
    WEB_USER="apache"
elif id "nginx" &>/dev/null; then
    WEB_USER="nginx"
else
    echo "Error: Tidak dapat menemukan user web server (www-data/apache/nginx)"
    exit 1
fi

echo "Web server user: $WEB_USER"
echo ""

# Get current directory (should be project root)
PROJECT_ROOT=$(pwd)
echo "Project root: $PROJECT_ROOT"
echo ""

# Check if we're in Laravel project
if [ ! -f "$PROJECT_ROOT/artisan" ]; then
    echo "Error: File artisan tidak ditemukan. Pastikan script dijalankan di root project Laravel."
    exit 1
fi

echo "1. Setting ownership storage dan bootstrap/cache..."
sudo chown -R $WEB_USER:$WEB_USER storage/
sudo chown -R $WEB_USER:$WEB_USER bootstrap/cache/
echo "   ✓ Done"
echo ""

echo "2. Setting permissions storage dan bootstrap/cache..."
sudo chmod -R 775 storage/
sudo chmod -R 775 bootstrap/cache/
echo "   ✓ Done"
echo ""

echo "3. Membuat folder upload jika belum ada..."
sudo mkdir -p storage/app/public/events
sudo mkdir -p storage/app/public/logos
sudo mkdir -p storage/app/public/payments
echo "   ✓ Done"
echo ""

echo "4. Setting ownership dan permission folder public..."
sudo chown -R $WEB_USER:$WEB_USER storage/app/public/
sudo chmod -R 775 storage/app/public/
echo "   ✓ Done"
echo ""

echo "5. Membuat symbolic link storage..."
php artisan storage:link
if [ -L "public/storage" ]; then
    sudo chown -h $WEB_USER:$WEB_USER public/storage
    echo "   ✓ Symbolic link berhasil dibuat"
else
    echo "   ⚠ Warning: Symbolic link mungkin sudah ada atau gagal dibuat"
fi
echo ""

echo "6. Testing write permission..."
if sudo -u $WEB_USER touch storage/app/public/test.txt 2>/dev/null; then
    sudo -u $WEB_USER rm -f storage/app/public/test.txt
    echo "   ✓ Write permission OK"
else
    echo "   ✗ Write permission FAILED - cek error di atas"
fi
echo ""

echo "=========================================="
echo "Selesai!"
echo "=========================================="
echo ""
echo "Jika masih ada masalah, cek:"
echo "1. User queue worker harus sama dengan web server ($WEB_USER)"
echo "2. Cek log: tail -f storage/logs/laravel.log"
echo "3. Cek PHP error log"
echo ""

