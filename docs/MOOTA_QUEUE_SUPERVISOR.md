# Supervisor setup untuk queue MOOTA (QRIS statis)

Dokumen ini untuk menjalankan worker khusus webhook MOOTA agar proses QRIS statis tetap cepat dan stabil di production.

## 1) Pastikan env aplikasi

Set variabel berikut di `.env` server:

```env
QUEUE_CONNECTION=redis
MOOTA_QUEUE_CONNECTION=redis
MOOTA_QUEUE=moota
```

Lalu refresh config:

```bash
php artisan config:clear
php artisan cache:clear
```

## 2) Buat program Supervisor

Contoh konfigurasi `/etc/supervisor/conf.d/desrc-moota-worker.conf`:

```ini
[program:desrc-moota-worker]
process_name=%(program_name)s_%(process_num)02d
command=php /var/www/desrc/artisan queue:work redis --queue=moota --sleep=1 --tries=5 --timeout=60 --max-time=3600
autostart=true
autorestart=true
stopasgroup=true
killasgroup=true
user=www-data
numprocs=1
redirect_stderr=true
stdout_logfile=/var/www/desrc/storage/logs/worker-moota.log
stopwaitsecs=3600
```

Catatan:
- Ubah path `/var/www/desrc` sesuai lokasi project di server.
- Ubah `user=www-data` sesuai user web server (mis. `forge`, `nginx`, dll).
- `numprocs=1` cukup untuk mulai; bisa dinaikkan saat traffic tinggi.

## 3) Reload Supervisor

```bash
sudo supervisorctl reread
sudo supervisorctl update
sudo supervisorctl start desrc-moota-worker:*
sudo supervisorctl status
```

## 4) Operasional harian

- Restart worker setelah deploy:

```bash
php artisan queue:restart
sudo supervisorctl restart desrc-moota-worker:*
```

- Cek log worker:

```bash
tail -f /var/www/desrc/storage/logs/worker-moota.log
```

- Cek failed jobs Laravel:

```bash
php artisan queue:failed
php artisan queue:retry all
```
