# Supervisor setup untuk queue WhatsApp (Whacenter)

Worker ini mengirim notifikasi WhatsApp secara serial dan memverifikasi status pesannya.
Jika worker mati, pesan tidak terkirim; `whacenter:watchdog` (scheduler) akan menandai log yang macet sebagai `failed`.

## 1) Env

```env
WHACENTER_QUEUE_CONNECTION=redis
WHACENTER_QUEUE=whatsapp
```

Pastikan Redis berjalan dan scheduler Laravel aktif (cron `* * * * * php artisan schedule:run`).

## 2) Program Supervisor

`/etc/supervisor/conf.d/desrc-whatsapp-worker.conf`:

```ini
[program:desrc-whatsapp-worker]
process_name=%(program_name)s_%(process_num)02d
command=php /var/www/desrc/artisan queue:work redis --queue=whatsapp --sleep=2 --tries=3 --timeout=120 --max-time=3600
autostart=true
autorestart=true
stopasgroup=true
killasgroup=true
user=www-data
numprocs=1
redirect_stderr=true
stdout_logfile=/var/www/desrc/storage/logs/worker-whatsapp.log
stopwaitsecs=150
```

Catatan:
- Gunakan `numprocs=1`: pengiriman WA harus serial (jeda acak antar pesan + `WithoutOverlapping`).
- Sesuaikan path dan `user` dengan server.

## 3) Reload

```bash
sudo supervisorctl reread
sudo supervisorctl update
sudo supervisorctl start desrc-whatsapp-worker:*
sudo supervisorctl status
```

## 4) Menu "WhatsApp Notifications" (kirim massal e-ticket)

Menu ini (permission `whatsapp_notification.read` untuk melihat, `whatsapp_notification.send` untuk mengirim)
tersedia untuk `super_admin`, `admin`, dan `organizer` (organizer hanya melihat event miliknya).

- Pengiriman massal dipecah per 25 pendaftar oleh `BulkSendTicketWhatsappJob` pada antrean `whatsapp` yang sama,
  jadi tidak perlu worker baru. Tiap pesan tetap lewat jadwal serial (jeda acak + istirahat per batch).
- Sebelum memulai, device dicek; run berhenti menambah antrean bila device terputus > 30 menit.
- Pendaftar yang pesannya masih `queued` otomatis dilewati, sehingga klik ganda / run ulang tidak membuat pesan dobel.
- Setelah deploy: `php artisan migrate` (membuat permission) lalu `php artisan queue:restart`.

## 5) Operasional

- Setelah deploy: `php artisan queue:restart`
- Cek log macet tanpa mengubah data: `php artisan whacenter:watchdog --dry-run`
- Cek koneksi device dan kirim tes: `php artisan whacenter:test 08xxxx --sync`
