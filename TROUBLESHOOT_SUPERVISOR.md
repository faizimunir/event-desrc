# Troubleshooting Supervisor "spawn error"

## Error: `laravel-worker:laravel-worker_00: ERROR (spawn error)`

Error ini biasanya terjadi karena:
1. Path yang salah
2. Permission issue
3. PHP path tidak ditemukan
4. User tidak memiliki akses

---

## Step 1: Cek Config File

### Lokasi Config:
```bash
sudo nano /etc/supervisor/conf.d/laravel-worker.conf
```

### Pastikan Format Benar:
```ini
[program:laravel-worker]
process_name=%(program_name)s_%(process_num)02d
command=php /var/www/event.desrc/event-desrc/artisan queue:work redis --sleep=3 --tries=3 --max-time=3600 --timeout=90
directory=/var/www/event.desrc/event-desrc
autostart=true
autorestart=true
stopasgroup=true
killasgroup=true
user=www-data
numprocs=1
redirect_stderr=true
stdout_logfile=/var/www/event.desrc/event-desrc/storage/logs/worker.log
stderr_logfile=/var/www/event.desrc/event-desrc/storage/logs/worker-error.log
stopwaitsecs=3600
```

**PENTING:**
- `command` harus menggunakan full path ke `php` dan `artisan`
- `directory` harus di-set ke project root
- `user` harus user yang memiliki akses ke project

---

## Step 2: Cek PHP Path

```bash
which php
# Output contoh: /usr/bin/php

php -v
# Harus menampilkan versi PHP
```

Update config dengan path yang benar:
```ini
command=/usr/bin/php /var/www/event.desrc/event-desrc/artisan queue:work redis --sleep=3 --tries=3 --max-time=3600 --timeout=90
```

---

## Step 3: Cek Permission

### Cek Ownership:
```bash
ls -la /var/www/event.desrc/event-desrc/
```

### Jika perlu, ubah ownership:
```bash
sudo chown -R www-data:www-data /var/www/event.desrc/event-desrc/
```

### Cek Permission:
```bash
sudo -u www-data php /var/www/event.desrc/event-desrc/artisan --version
```

Jika error, berarti user `www-data` tidak bisa akses. Coba dengan user lain atau ubah ownership.

---

## Step 4: Cek Log Supervisor

```bash
sudo tail -f /var/log/supervisor/supervisord.log
```

Atau:
```bash
sudo supervisorctl tail laravel-worker:laravel-worker_00 stderr
```

---

## Step 5: Test Command Manual

Test command yang akan dijalankan supervisor:

```bash
# Test sebagai user www-data
sudo -u www-data php /var/www/event.desrc/event-desrc/artisan queue:work redis --once

# Atau test sebagai user eljo
php /var/www/event.desrc/event-desrc/artisan queue:work redis --once
```

Jika berhasil, berarti command OK. Jika error, perbaiki dulu.

---

## Step 6: Reload Supervisor

Setelah update config:

```bash
# Reread config
sudo supervisorctl reread

# Update
sudo supervisorctl update

# Start
sudo supervisorctl start laravel-worker:*
```

---

## Step 7: Cek Status Detail

```bash
sudo supervisorctl status laravel-worker:*
```

Jika masih error, cek log:
```bash
sudo supervisorctl tail laravel-worker:laravel-worker_00
```

---

## Common Issues & Solutions

### Issue 1: PHP Path Salah

**Error:** `php: command not found`

**Solution:**
```bash
which php
# Gunakan full path di config
command=/usr/bin/php /path/to/artisan ...
```

### Issue 2: Permission Denied

**Error:** `Permission denied`

**Solution:**
```bash
# Ubah ownership
sudo chown -R www-data:www-data /var/www/event.desrc/event-desrc/

# Atau ubah user di config
user=eljo  # Ganti dengan user yang memiliki akses
```

### Issue 3: Directory Tidak Ada

**Error:** `No such file or directory`

**Solution:**
```bash
# Pastikan directory ada
ls -la /var/www/event.desrc/event-desrc/

# Pastikan artisan file ada
ls -la /var/www/event.desrc/event-desrc/artisan
```

### Issue 4: Redis Tidak Running

**Error:** `Connection refused`

**Solution:**
```bash
sudo systemctl status redis-server
sudo systemctl start redis-server
```

---

## Config Template yang Benar

Buat file `/etc/supervisor/conf.d/laravel-worker.conf`:

```ini
[program:laravel-worker]
process_name=%(program_name)s_%(process_num)02d
command=/usr/bin/php /var/www/event.desrc/event-desrc/artisan queue:work redis --sleep=3 --tries=3 --max-time=3600 --timeout=90
directory=/var/www/event.desrc/event-desrc
autostart=true
autorestart=true
stopasgroup=true
killasgroup=true
user=www-data
numprocs=1
redirect_stderr=true
stdout_logfile=/var/www/event.desrc/event-desrc/storage/logs/worker.log
stderr_logfile=/var/www/event.desrc/event-desrc/storage/logs/worker-error.log
stopwaitsecs=3600
environment=HOME="/home/www-data",USER="www-data"
```

**Update:**
- `/usr/bin/php` dengan path PHP Anda (`which php`)
- `/var/www/event.desrc/event-desrc` dengan path project Anda
- `www-data` dengan user yang memiliki akses

---

## Quick Fix Script

Buat script untuk auto-detect dan setup:

```bash
#!/bin/bash
PHP_PATH=$(which php)
PROJECT_PATH="/var/www/event.desrc/event-desrc"
USER="www-data"

cat > /tmp/laravel-worker.conf << EOF
[program:laravel-worker]
process_name=%(program_name)s_%(process_num)02d
command=$PHP_PATH $PROJECT_PATH/artisan queue:work redis --sleep=3 --tries=3 --max-time=3600 --timeout=90
directory=$PROJECT_PATH
autostart=true
autorestart=true
stopasgroup=true
killasgroup=true
user=$USER
numprocs=1
redirect_stderr=true
stdout_logfile=$PROJECT_PATH/storage/logs/worker.log
stderr_logfile=$PROJECT_PATH/storage/logs/worker-error.log
stopwaitsecs=3600
environment=HOME="/home/$USER",USER="$USER"
EOF

sudo cp /tmp/laravel-worker.conf /etc/supervisor/conf.d/laravel-worker.conf
sudo supervisorctl reread
sudo supervisorctl update
sudo supervisorctl start laravel-worker:*
```

