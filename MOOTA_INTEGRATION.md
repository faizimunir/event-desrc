# Integrasi Moota - Dokumentasi

## Ringkasan
Sistem pembayaran otomatis dengan verifikasi menggunakan Moota telah berhasil diintegrasikan ke dalam sistem event registration. Sistem ini mendukung dua metode pembayaran:
- **Manual**: Transfer + upload bukti pembayaran (sistem lama)
- **Moota**: Verifikasi otomatis berdasarkan mutasi rekening

## File yang Dibuat/Dimodifikasi

### 1. Database Migrations
- `database/migrations/2025_12_29_153456_add_payment_method_to_events_table.php`
  - Menambahkan kolom `payment_method` (enum: manual, moota) ke tabel `events`
  
- `database/migrations/2025_12_29_153501_add_payment_verification_fields_to_payments_table.php`
  - Menambahkan kolom `payment_verified_at` dan `payment_reference` ke tabel `payments`

### 2. Configuration
- `config/moota.php`
  - Konfigurasi API Moota (API key, URL, toleransi, dll)

### 3. Services
- `app/Services/MootaService.php`
  - Service untuk interaksi dengan API Moota
  - Fungsi: `getMutations()`, `matchTransaction()`, `verifyPayment()`, `getBanks()`

### 4. Jobs
- `app/Jobs/ProcessMootaWebhook.php`
  - Job untuk memproses webhook Moota di queue
  - Memastikan server tidak terbebani saat banyak registrasi

### 5. Controllers
- `app/Http/Controllers/Webhook/MootaWebhookController.php`
  - Controller untuk handle webhook dari Moota
  - Endpoint: `POST /webhook/moota`

### 6. Models (Updated)
- `app/Models/Event.php`
  - Menambahkan `payment_method` ke `$fillable`
  
- `app/Models/Payment.php`
  - Menambahkan `payment_verified_at` dan `payment_reference` ke `$fillable` dan `$casts`

### 7. Livewire Components (Updated)
- `app/Livewire/Admin/EventManagement.php`
  - Menambahkan field `payment_method` di form event
  
- `app/Livewire/Payment.php`
  - Update logika untuk menampilkan instruksi berbeda berdasarkan `payment_method`
  - Auto-create payment record untuk event Moota saat registrasi
  
- `app/Livewire/Registration.php`
  - Auto-create payment record untuk event Moota

### 8. Views (Updated)
- `resources/views/livewire/admin/event-management.blade.php`
  - Menambahkan dropdown "Metode Pembayaran" di form event
  
- `resources/views/livewire/payment.blade.php`
  - Menampilkan instruksi berbeda untuk manual vs Moota
  - Untuk Moota: tidak perlu upload bukti, hanya menunggu verifikasi otomatis

### 9. Routes
- `routes/web.php`
  - Menambahkan route: `POST /webhook/moota`

## Konfigurasi

### 1. Environment Variables
Tambahkan ke `.env`:
```env
MOOTA_API_KEY=your_moota_api_key_here
MOOTA_API_URL=https://app.moota.co/api/v2
MOOTA_WEBHOOK_URL=/webhook/moota
MOOTA_AMOUNT_TOLERANCE=0
MOOTA_MATCH_BY_UNIQUE_CODE=true
MOOTA_MATCH_BY_REGISTRATION_NUMBER=true
MOOTA_LOG_UNMATCHED=true
MOOTA_LOG_CHANNEL=daily
```

### 2. Setup Webhook di Moota

#### Untuk Production:
1. Login ke dashboard Moota (https://app.moota.co)
2. Masuk ke Settings > Webhook
3. Tambahkan webhook URL: `https://yourdomain.com/webhook/moota`
4. Pilih event: "Mutation Credit" (transaksi masuk)

#### Untuk Development Local (Menggunakan Tunneling):
Karena webhook memerlukan URL yang dapat diakses dari internet, sedangkan localhost tidak bisa diakses dari luar, gunakan tunneling service:

**Opsi 1: Menggunakan ngrok (Recommended)**
1. Download dan install ngrok dari https://ngrok.com/download
2. Jalankan Laravel development server:
   ```bash
   php artisan serve
   ```
3. Di terminal baru, jalankan ngrok:
   ```bash
   ngrok http 8000
   ```
   (Ganti 8000 dengan port yang digunakan Laravel)
4. Copy URL yang diberikan ngrok (contoh: `https://abc123.ngrok.io`)
5. Di dashboard Moota, tambahkan webhook URL: `https://abc123.ngrok.io/webhook/moota`
6. **Catatan:** URL ngrok gratis berubah setiap kali restart. Untuk URL tetap, gunakan ngrok paid plan atau alternatif lain.

**Opsi 2: Menggunakan localtunnel (Gratis, URL bisa custom)**
1. Install localtunnel secara global:
   ```bash
   npm install -g localtunnel
   ```
2. Jalankan Laravel development server:
   ```bash
   php artisan serve
   ```
3. Di terminal baru, jalankan localtunnel:
   ```bash
   lt --port 8000 --subdomain your-custom-name
   ```
   (Ganti 8000 dengan port Laravel, dan your-custom-name dengan nama custom)
4. Copy URL yang diberikan (contoh: `https://your-custom-name.loca.lt`)
5. Di dashboard Moota, tambahkan webhook URL: `https://your-custom-name.loca.lt/webhook/moota`

**Opsi 3: Menggunakan Cloudflare Tunnel (Gratis, URL tetap)**
1. Install cloudflared dari https://developers.cloudflare.com/cloudflare-one/connections/connect-apps/install-and-setup/installation/
2. Jalankan tunnel:
   ```bash
   cloudflared tunnel --url http://localhost:8000
   ```
3. Copy URL yang diberikan
4. Di dashboard Moota, tambahkan webhook URL: `[url-dari-cloudflared]/webhook/moota`

**Opsi 4: Menggunakan serveo (Gratis, SSH-based)**
1. Pastikan SSH tersedia
2. Jalankan:
   ```bash
   ssh -R 80:localhost:8000 serveo.net
   ```
3. Copy URL yang diberikan
4. Di dashboard Moota, tambahkan webhook URL: `[url-dari-serveo]/webhook/moota`

**Tips untuk Development:**
- Gunakan ngrok untuk testing cepat (URL berubah setiap restart)
- Gunakan localtunnel atau cloudflare tunnel untuk URL yang lebih stabil
- Pastikan queue worker berjalan: `php artisan queue:work`
- Test webhook dengan mengirim request manual atau menggunakan tool seperti Postman

## Cara Penggunaan

### Untuk Admin Event

1. **Membuat/Edit Event**
   - Buka Admin Panel > Kelola Event
   - Klik "Event Baru" atau "Edit" pada event yang ada
   - Pilih "Metode Pembayaran":
     - **Manual**: Sistem seperti biasa (transfer + upload bukti)
     - **Otomatis (Moota)**: Verifikasi otomatis via Moota

2. **Konfigurasi Moota**
   - Pastikan `MOOTA_API_KEY` sudah diisi di `.env`
   - Pastikan webhook sudah dikonfigurasi di dashboard Moota

### Untuk Peserta

#### Jika Event menggunakan Manual:
1. Daftar event seperti biasa
2. Transfer sesuai nominal yang tertera
3. Upload bukti pembayaran
4. Klik "Konfirmasi Pembayaran"
5. Tunggu verifikasi admin

#### Jika Event menggunakan Moota:
1. Daftar event seperti biasa
2. Transfer sesuai nominal yang tertera (termasuk kode unik)
3. **Tidak perlu upload bukti pembayaran**
4. Sistem akan otomatis mendeteksi dan memverifikasi pembayaran
5. Notifikasi konfirmasi akan dikirim otomatis via email dan WhatsApp

## Flow Pembayaran Moota

1. **Registrasi Peserta**
   - Peserta mendaftar event
   - Sistem otomatis membuat record `Payment` dengan status `pending`

2. **Transfer Peserta**
   - Peserta melakukan transfer sesuai nominal (termasuk kode unik)
   - Bank mengirim notifikasi ke Moota

3. **Webhook dari Moota**
   - Moota mengirim webhook ke `/webhook/moota`
   - Webhook di-queue untuk diproses

4. **Proses Verifikasi (Queue)**
   - Job `ProcessMootaWebhook` mencocokkan transaksi dengan payment
   - Matching berdasarkan:
     - Nominal (dengan toleransi)
     - Kode unik (jika ada di note)
     - Nomor registrasi (jika ada di note)
   - Jika cocok:
     - Update `Payment`: status → `verified`, isi `payment_verified_at` dan `payment_reference`
     - Update `Participant`: status → `confirmed`
     - Dispatch job `SendConfirmNotificationJob` (email + WhatsApp)

5. **Notifikasi**
   - Peserta menerima email dan WhatsApp dengan QR Code

## Matching Logic

Sistem mencocokkan transaksi dengan pembayaran berdasarkan:

1. **Nominal**: Harus sesuai dengan `package.price + unique_code` (dengan toleransi)
2. **Kode Unik**: Jika ada di note/deskripsi transaksi
3. **Nomor Registrasi**: Jika ada di note/deskripsi transaksi

**Prioritas Matching:**
1. Nominal + Kode Unik (paling akurat)
2. Nominal + Nomor Registrasi
3. Nominal saja (jika tidak ada note)

## Logging

- Transaksi yang berhasil diverifikasi: Log info
- Transaksi yang tidak cocok: Log warning (jika `MOOTA_LOG_UNMATCHED=true`)
- Error/Exception: Log error dengan detail lengkap

## Contoh Payload Webhook Moota

```json
{
  "id": "mutation_id_123",
  "bank_id": "bank_id_456",
  "account_number": "1234567890",
  "bank_type": "bca",
  "date": "2024-01-15 10:30:00",
  "amount": 150000,
  "type": "credit",
  "note": "TRANSFER REG-20240115-ABC123",
  "description": "Transfer masuk",
  "balance": 5000000
}
```

## Troubleshooting

### Webhook tidak terdeteksi
1. Pastikan webhook URL sudah benar di dashboard Moota
2. Pastikan server dapat diakses dari internet
3. Cek log Laravel untuk error

### Transaksi tidak cocok
1. Pastikan nominal transfer sesuai persis (termasuk kode unik)
2. Cek log untuk transaksi yang tidak cocok
3. Admin dapat melakukan verifikasi manual jika diperlukan

### Payment tidak terverifikasi
1. Cek apakah queue worker berjalan: `php artisan queue:work`
2. Cek log untuk error di job `ProcessMootaWebhook`
3. Pastikan `MOOTA_API_KEY` sudah benar

## Best Practices

1. **Queue Worker**: Pastikan queue worker selalu berjalan untuk memproses webhook
2. **Monitoring**: Monitor log untuk transaksi yang tidak cocok
3. **Backup**: Sistem manual tetap tersedia sebagai fallback
4. **Testing**: Test dengan nominal kecil terlebih dahulu

## Catatan Penting

- Sistem manual tetap berfungsi normal, tidak ada breaking changes
- Event yang sudah ada akan default menggunakan metode manual
- Payment yang sudah dibuat dengan metode manual tidak akan terpengaruh
- Webhook endpoint tidak memerlukan authentication (aman karena hanya menerima dari Moota)

