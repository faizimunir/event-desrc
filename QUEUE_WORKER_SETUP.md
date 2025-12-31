# Setup Queue Worker untuk Moota Webhook

## Masalah: Webhook Diterima Tapi Tidak Diproses

Jika log menunjukkan:
```
[INFO] Moota Webhook: Received and queued
```

Tapi tidak ada log selanjutnya seperti:
```
[INFO] Moota Webhook Job: Starting processing
```

**Ini berarti queue worker tidak berjalan!**

---

## ⚡ Quick Setup: Redis + Auto-Start (Recommended)

Untuk setup lengkap dengan Redis dan auto-start, lihat: **`REDIS_QUEUE_SETUP.md`**

**Keuntungan Redis:**
- ✅ Lebih cepat dari database
- ✅ Tidak membebani database
- ✅ Lebih scalable
- ✅ Real-time processing

---

## Solusi: Jalankan Queue Worker

### 1. Cek Queue Connection

Pastikan di `.env`:
```env
QUEUE_CONNECTION=database
```

### 2. Pastikan Tabel Jobs Ada

```bash
php artisan migrate
```

### 3. Jalankan Queue Worker

#### Windows (PowerShell):
```powershell
php artisan queue:work
```

#### Linux/Mac:
```bash
php artisan queue:work
```

#### Dengan Verbose (untuk debugging):
```bash
php artisan queue:work --verbose
```

---

## Cara Cek Apakah Queue Worker Berjalan

### 1. Cek Jobs di Database

```sql
SELECT * FROM jobs ORDER BY created_at DESC LIMIT 5;
```

Jika ada data, berarti job sudah di-queue tapi belum diproses.

### 2. Cek Failed Jobs

```bash
php artisan queue:failed
```

Atau di database:
```sql
SELECT * FROM failed_jobs ORDER BY failed_at DESC LIMIT 5;
```

### 3. Cek Log Setelah Jalankan Queue Worker

Setelah menjalankan `php artisan queue:work`, harus muncul log:
```
[INFO] Moota Webhook Job: Starting processing
[INFO] Moota Verify Payment: Starting verification
```

---

## Menjalankan Queue Worker di Background (Production)

### Windows (Task Scheduler)

Buat file `run-queue.bat`:
```batch
@echo off
cd C:\xampp\htdocs\event-desrc
php artisan queue:work --tries=3 --timeout=90
```

Atau gunakan Windows Task Scheduler untuk auto-start.

### Linux (Supervisor - Recommended)

Install supervisor:
```bash
sudo apt-get install supervisor
```

Buat file `/etc/supervisor/conf.d/laravel-worker.conf`:
```ini
[program:laravel-worker]
process_name=%(program_name)s_%(process_num)02d
command=php /path/to/artisan queue:work database --sleep=3 --tries=3 --max-time=3600
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

Reload supervisor:
```bash
sudo supervisorctl reread
sudo supervisorctl update
sudo supervisorctl start laravel-worker:*
```

### Linux (Systemd)

Buat file `/etc/systemd/system/laravel-worker.service`:
```ini
[Unit]
Description=Laravel Queue Worker
After=network.target

[Service]
User=www-data
Group=www-data
Restart=always
ExecStart=/usr/bin/php /path/to/artisan queue:work database --sleep=3 --tries=3

[Install]
WantedBy=multi-user.target
```

Enable dan start:
```bash
sudo systemctl enable laravel-worker
sudo systemctl start laravel-worker
sudo systemctl status laravel-worker
```

---

## Development: Auto-restart Queue Worker

### Menggunakan Laravel Herd (jika pakai)

Herd sudah include queue worker otomatis.

### Menggunakan npm script

Di `package.json`:
```json
{
  "scripts": {
    "queue": "php artisan queue:work"
  }
}
```

Jalankan:
```bash
npm run queue
```

---

## Troubleshooting

### Queue Worker Berhenti Setelah Satu Job

**Masalah:** Queue worker berhenti setelah memproses satu job.

**Solusi:** Gunakan `queue:listen` (tapi lebih berat) atau pastikan tidak ada error:
```bash
php artisan queue:work --tries=3 --timeout=90
```

### Job Stuck di Queue

**Cek:**
```sql
SELECT * FROM jobs WHERE reserved_at IS NOT NULL;
```

**Clear stuck jobs:**
```bash
php artisan queue:restart
```

### Memory Limit Error

**Solusi:** Tambahkan `--max-jobs` dan `--max-time`:
```bash
php artisan queue:work --max-jobs=1000 --max-time=3600
```

---

## Testing Queue Worker

### 1. Test dengan Artisan Command

Buat test job:
```bash
php artisan make:job TestQueue
```

Dispatch job:
```php
TestQueue::dispatch();
```

Jalankan queue worker dan cek log.

### 2. Test Moota Webhook

Setelah queue worker berjalan, test webhook lagi. Harus muncul log:
```
[INFO] Moota Webhook: Received and queued
[INFO] Moota Webhook Job: Starting processing
[INFO] Moota Verify Payment: Starting verification
```

---

## Checklist

- [ ] `QUEUE_CONNECTION=database` di `.env`
- [ ] Tabel `jobs` sudah dibuat (migrate)
- [ ] Queue worker berjalan: `php artisan queue:work`
- [ ] Log menunjukkan "Starting processing"
- [ ] Tidak ada error di log
- [ ] Failed jobs kosong

---

## Catatan Penting

**Queue worker HARUS berjalan terus-menerus!**

- Di development: Jalankan manual di terminal terpisah
- Di production: Gunakan supervisor atau systemd untuk auto-restart
- Jika queue worker mati, webhook akan di-queue tapi tidak diproses

