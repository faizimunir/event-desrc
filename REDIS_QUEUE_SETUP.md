# Setup Redis Queue dengan Auto-Start Queue Worker

## Keuntungan Menggunakan Redis untuk Queue

1. **Lebih Cepat**: Redis lebih cepat dari database untuk queue
2. **Lebih Efisien**: Tidak membebani database
3. **Scalable**: Bisa handle banyak job sekaligus
4. **Real-time**: Job diproses lebih cepat

---

## Step 1: Install Redis

### Windows (XAMPP)

1. **Download Redis untuk Windows:**
   - Option 1: Memurai (Recommended) - https://www.memurai.com/
   - Option 2: WSL2 dengan Redis
   - Option 3: Docker dengan Redis

2. **Install Memurai (Paling Mudah):**
   - Download dari https://www.memurai.com/get-memurai
   - Install seperti aplikasi biasa
   - Redis akan berjalan sebagai Windows Service otomatis

3. **Atau Install via Chocolatey:**
   ```powershell
   choco install redis-64
   ```

### Linux (Ubuntu/Debian)

```bash
sudo apt-get update
sudo apt-get install redis-server
sudo systemctl enable redis-server
sudo systemctl start redis-server
sudo systemctl status redis-server
```

### Mac

```bash
brew install redis
brew services start redis
```

---

## Step 2: Install PHP Redis Extension

### Windows (XAMPP)

1. **Download php_redis.dll:**
   - Cek PHP version: `php -v`
   - Download dari: https://pecl.php.net/package/redis
   - Atau dari: https://windows.php.net/downloads/pecl/releases/redis/

2. **Copy ke folder PHP:**
   - Copy `php_redis.dll` ke `C:\xampp\php\ext\`

3. **Edit php.ini:**
   - Buka `C:\xampp\php\php.ini`
   - Tambahkan: `extension=redis`
   - Restart Apache

4. **Verify:**
   ```powershell
   php -m | findstr redis
   ```

### Linux

```bash
sudo apt-get install php-redis
# atau
sudo pecl install redis
```

### Mac

```bash
pecl install redis
```

---

## Step 3: Install Predis Package (Laravel)

Laravel sudah include Predis, tapi pastikan ada di `composer.json`:

```bash
composer require predis/predis
```

Atau jika sudah ada, skip langkah ini.

---

## Step 4: Konfigurasi .env

Tambahkan/update di `.env`:

```env
# Queue Configuration
QUEUE_CONNECTION=redis

# Redis Configuration
REDIS_HOST=127.0.0.1
REDIS_PASSWORD=null
REDIS_PORT=6379
REDIS_DB=0

# Redis Queue Configuration
REDIS_QUEUE_CONNECTION=default
REDIS_QUEUE=default
REDIS_QUEUE_RETRY_AFTER=90
```

---

## Step 5: Test Redis Connection

```bash
php artisan tinker
```

Di tinker:
```php
Redis::ping();
// Harus return: "PONG"
```

Atau test queue:
```php
\App\Jobs\ProcessMootaWebhook::dispatch(['test' => 'data']);
```

---

## Step 6: Setup Auto-Start Queue Worker

### Windows (XAMPP) - Menggunakan Task Scheduler

#### Option 1: Batch File + Task Scheduler

1. **Buat file `start-queue-worker.bat`** di root project:
```batch
@echo off
cd /d C:\xampp\htdocs\event-desrc
php artisan queue:work redis --sleep=3 --tries=3 --max-time=3600 --timeout=90
```

2. **Buat Task Scheduler:**
   - Buka "Task Scheduler" (taskschd.msc)
   - Create Basic Task
   - Name: "Laravel Queue Worker"
   - Trigger: "When the computer starts"
   - Action: "Start a program"
   - Program: `C:\xampp\htdocs\event-desrc\start-queue-worker.bat`
   - Check "Run whether user is logged on or not"
   - Check "Run with highest privileges"

#### Option 2: NSSM (Non-Sucking Service Manager) - Recommended

1. **Download NSSM:**
   - https://nssm.cc/download

2. **Install Service:**
   ```powershell
   # Extract NSSM ke folder (misal C:\nssm)
   cd C:\nssm\win64
   
   # Install service
   .\nssm install LaravelQueueWorker
   ```

3. **Konfigurasi Service:**
   - Application: `C:\xampp\php\php.exe`
   - Startup directory: `C:\xampp\htdocs\event-desrc`
   - Arguments: `artisan queue:work redis --sleep=3 --tries=3 --max-time=3600 --timeout=90`
   - Service name: `LaravelQueueWorker`

4. **Start Service:**
   ```powershell
   .\nssm start LaravelQueueWorker
   ```

5. **Cek Status:**
   ```powershell
   .\nssm status LaravelQueueWorker
   ```

### Linux - Menggunakan Supervisor (Recommended)

1. **Install Supervisor:**
   ```bash
   sudo apt-get install supervisor
   ```

2. **Buat Config File:**
   ```bash
   sudo nano /etc/supervisor/conf.d/laravel-worker.conf
   ```

3. **Isi Config:**
   ```ini
   [program:laravel-worker]
   process_name=%(program_name)s_%(process_num)02d
   command=php /path/to/artisan queue:work redis --sleep=3 --tries=3 --max-time=3600 --timeout=90
   autostart=true
   autorestart=true
   stopasgroup=true
   killasgroup=true
   user=www-data
   numprocs=1
   redirect_stderr=true
   stdout_logfile=/path/to/storage/logs/worker.log
   stopwaitsecs=3600
   ```

4. **Update Path:**
   - Ganti `/path/to/artisan` dengan path lengkap ke `artisan`
   - Ganti `/path/to/storage/logs/worker.log` dengan path lengkap

5. **Reload Supervisor:**
   ```bash
   sudo supervisorctl reread
   sudo supervisorctl update
   sudo supervisorctl start laravel-worker:*
   ```

6. **Cek Status:**
   ```bash
   sudo supervisorctl status
   ```

### Linux - Menggunakan Systemd

1. **Buat Service File:**
   ```bash
   sudo nano /etc/systemd/system/laravel-worker.service
   ```

2. **Isi Config:**
   ```ini
   [Unit]
   Description=Laravel Queue Worker
   After=network.target redis.service

   [Service]
   User=www-data
   Group=www-data
   Restart=always
   ExecStart=/usr/bin/php /path/to/artisan queue:work redis --sleep=3 --tries=3 --max-time=3600 --timeout=90
   StandardOutput=journal
   StandardError=journal

   [Install]
   WantedBy=multi-user.target
   ```

3. **Enable dan Start:**
   ```bash
   sudo systemctl daemon-reload
   sudo systemctl enable laravel-worker
   sudo systemctl start laravel-worker
   sudo systemctl status laravel-worker
   ```

---

## Step 7: Monitoring Queue Worker

### Cek Status (Windows - NSSM)

```powershell
C:\nssm\win64\nssm status LaravelQueueWorker
```

### Cek Status (Linux - Supervisor)

```bash
sudo supervisorctl status laravel-worker:*
```

### Cek Status (Linux - Systemd)

```bash
sudo systemctl status laravel-worker
```

### Cek Log

```bash
# Windows
type storage\logs\worker.log

# Linux
tail -f storage/logs/worker.log
```

### Cek Queue di Redis

```bash
php artisan tinker
```

```php
Redis::llen('queues:default');
// Return jumlah job di queue
```

---

## Step 8: Restart Queue Worker

### Windows (NSSM)

```powershell
C:\nssm\win64\nssm restart LaravelQueueWorker
```

### Linux (Supervisor)

```bash
sudo supervisorctl restart laravel-worker:*
```

### Linux (Systemd)

```bash
sudo systemctl restart laravel-worker
```

### Manual Restart (Semua OS)

```bash
php artisan queue:restart
```

---

## Troubleshooting

### Redis Tidak Bisa Connect

**Cek Redis Running:**
```bash
# Windows
netstat -an | findstr 6379

# Linux
sudo systemctl status redis-server
```

**Test Connection:**
```bash
php artisan tinker
Redis::ping();
```

### Queue Worker Tidak Start

**Cek Error:**
- Windows: Cek Event Viewer atau log file
- Linux: `sudo supervisorctl tail laravel-worker:laravel-worker_00 stderr`

**Cek Permission:**
- Pastikan user memiliki akses ke folder project
- Pastikan user bisa execute `php artisan`

### Job Tidak Diproses

**Cek Queue Connection:**
```bash
php artisan tinker
config('queue.default');
// Harus return: "redis"
```

**Cek Redis Queue:**
```php
Redis::llen('queues:default');
```

**Clear Queue (jika stuck):**
```bash
php artisan queue:clear redis
```

---

## Perbandingan: Database vs Redis

| Feature | Database | Redis |
|---------|----------|-------|
| Speed | Lambat | Cepat |
| Scalability | Terbatas | Sangat baik |
| Resource | Membebani DB | Ringan |
| Setup | Mudah | Perlu install Redis |
| Monitoring | SQL query | Redis CLI |

**Rekomendasi:** Gunakan Redis untuk production, Database untuk development.

---

## Checklist Setup

- [ ] Redis terinstall dan running
- [ ] PHP Redis extension terinstall
- [ ] Predis package terinstall
- [ ] `.env` sudah diupdate: `QUEUE_CONNECTION=redis`
- [ ] Test connection: `Redis::ping()` return "PONG"
- [ ] Queue worker service terinstall
- [ ] Queue worker auto-start saat boot
- [ ] Test webhook dan cek log

---

## Quick Commands

```bash
# Test Redis
php artisan tinker
Redis::ping();

# Test Queue
php artisan queue:work redis --once

# Monitor Queue
php artisan queue:monitor redis

# Clear Queue
php artisan queue:clear redis

# Restart Worker
php artisan queue:restart
```

