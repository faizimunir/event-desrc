# Panduan Monitoring Payment Moota

## Cara Melihat Apakah Payment dari Moota Terdeteksi

Ada beberapa cara untuk memantau dan melihat apakah payment dari Moota sudah terdeteksi dan terverifikasi:

---

## 1. Melihat di Admin Panel - Payment Proofs

### Lokasi:
Admin Panel > **Payment Proofs** (Bukti Pembayaran)

### Cara Cek:
1. Login ke Admin Panel
2. Buka menu **Payment Proofs** atau **Bukti Pembayaran**
3. Filter berdasarkan:
   - **Status**: Pilih "Verified" untuk melihat payment yang sudah terverifikasi Moota
   - **Event**: Pilih event tertentu
4. Payment yang terverifikasi Moota akan memiliki:
   - Status: **Verified** (hijau)
   - **Payment Reference**: ID mutation dari Moota
   - **Payment Verified At**: Waktu verifikasi otomatis
   - **Tidak ada bukti pembayaran** (karena otomatis)

### Indikator Payment Moota:
- ✅ Status = **Verified**
- ✅ Ada **Payment Reference** (mutation ID dari Moota)
- ✅ Ada **Payment Verified At** (timestamp)
- ✅ **Tidak ada** payment_proof (karena otomatis, tidak perlu upload)

---

## 2. Melihat Log Laravel

### Lokasi File Log:
```
storage/logs/laravel.log
```

### Cara Melihat Log:

#### Windows (PowerShell):
```powershell
Get-Content storage\logs\laravel.log -Tail 50 -Wait
```

#### Linux/Mac:
```bash
tail -f storage/logs/laravel.log
```

#### Atau dengan grep untuk filter Moota:
```bash
tail -f storage/logs/laravel.log | grep -i "moota"
```

### Log yang Dicari:

#### ✅ Webhook Diterima:
```
[INFO] Moota Webhook: Received and queued
mutation_id: "mutation_123"
amount: 150000
```

#### ✅ Payment Terverifikasi:
```
[INFO] Moota Webhook: Payment verified successfully
payment_id: 123
participant_id: 456
mutation_id: "mutation_123"
match_type: "unique_code"
```

#### ⚠️ Transaksi Tidak Cocok:
```
[WARNING] Moota Webhook: Unmatched transaction
amount: 150000
note: "TRANSFER"
mutation_id: "mutation_123"
```

#### ❌ Error:
```
[ERROR] Moota Webhook Processing Error: ...
```

---

## 3. Melihat di Database

### Query untuk Cek Payment Moota yang Terverifikasi:

```sql
SELECT 
    p.id,
    p.participant_id,
    p.amount,
    p.status,
    p.payment_reference,
    p.payment_verified_at,
    p.created_at,
    pt.name as participant_name,
    pt.email,
    pt.unique_code,
    ev.name as event_name
FROM payments p
JOIN participants pt ON p.participant_id = pt.id
JOIN packages pk ON pt.package_id = pk.id
JOIN events ev ON pk.event_id = ev.id
WHERE p.payment_reference IS NOT NULL
  AND p.payment_verified_at IS NOT NULL
  AND ev.payment_method = 'moota'
ORDER BY p.payment_verified_at DESC;
```

### Query untuk Cek Payment Pending (Belum Terverifikasi):

```sql
SELECT 
    p.id,
    p.participant_id,
    p.amount,
    p.status,
    pt.name as participant_name,
    pt.unique_code,
    ev.name as event_name
FROM payments p
JOIN participants pt ON p.participant_id = pt.id
JOIN packages pk ON pt.package_id = pk.id
JOIN events ev ON pk.event_id = ev.id
WHERE p.status = 'pending'
  AND ev.payment_method = 'moota'
ORDER BY p.created_at DESC;
```

### Query untuk Cek Transaksi yang Tidak Cocok (dari log):

Cek log untuk transaksi yang tidak cocok dengan pattern:
```
[WARNING] Moota Webhook: Unmatched transaction
```

---

## 4. Monitoring Queue

### Pastikan Queue Worker Berjalan:

```bash
php artisan queue:work
```

### Cek Failed Jobs:

```bash
php artisan queue:failed
```

### Cek Jobs di Database:

```sql
SELECT * FROM jobs ORDER BY created_at DESC LIMIT 10;
```

### Cek Failed Jobs di Database:

```sql
SELECT * FROM failed_jobs ORDER BY failed_at DESC LIMIT 10;
```

---

## 5. Test Webhook Manual

### Test dengan Postman/curl:

```bash
curl -X POST http://localhost:8000/webhook/moota \
  -H "Content-Type: application/json" \
  -d '{
    "id": "test-mutation-123",
    "bank_id": "bank-456",
    "account_number": "1234567890",
    "bank_type": "bca",
    "date": "2024-01-15 10:30:00",
    "amount": 150000,
    "type": "credit",
    "note": "TRANSFER REG-20240115-ABC123",
    "description": "Transfer masuk",
    "balance": 5000000
  }'
```

### Test dengan ngrok Web Interface:

Jika menggunakan ngrok, buka:
```
http://127.0.0.1:4040
```

Klik tab "Request" untuk melihat semua request yang masuk.

---

## 6. Checklist Verifikasi

### ✅ Payment Terdeteksi dan Terverifikasi Jika:

1. **Di Admin Panel:**
   - Status payment = **Verified** (hijau)
   - Ada **Payment Reference** (mutation ID)
   - Ada **Payment Verified At** (timestamp)
   - Participant status = **Confirmed**

2. **Di Log:**
   - Ada log: `Moota Webhook: Payment verified successfully`
   - Tidak ada error

3. **Di Database:**
   - `payments.status` = `verified`
   - `payments.payment_reference` IS NOT NULL
   - `payments.payment_verified_at` IS NOT NULL
   - `participants.status` = `confirmed`

4. **Peserta Menerima:**
   - Email konfirmasi
   - WhatsApp konfirmasi dengan QR Code

---

## 7. Troubleshooting

### Payment Tidak Terdeteksi:

1. **Cek Webhook URL di Moota:**
   - Pastikan URL benar: `https://yourdomain.com/webhook/moota`
   - Test URL di dashboard Moota

2. **Cek Log:**
   ```bash
   tail -f storage/logs/laravel.log | grep -i "moota"
   ```

3. **Cek Queue Worker:**
   ```bash
   php artisan queue:work
   ```

4. **Cek Nominal:**
   - Pastikan nominal transfer sesuai persis (termasuk kode unik)
   - Cek toleransi di `config/moota.php`: `MOOTA_AMOUNT_TOLERANCE`

5. **Cek Kode Unik:**
   - Pastikan kode unik ada di note/deskripsi transfer
   - Atau pastikan nomor registrasi ada di note

### Payment Terdeteksi Tapi Tidak Terverifikasi:

1. **Cek Log untuk Error:**
   ```bash
   tail -f storage/logs/laravel.log | grep -i "error"
   ```

2. **Cek Failed Jobs:**
   ```bash
   php artisan queue:failed
   ```

3. **Cek Database:**
   - Cek apakah payment sudah dibuat
   - Cek apakah participant sudah dibuat
   - Cek apakah event menggunakan `payment_method = 'moota'`

### Transaksi Tidak Cocok (Unmatched):

1. **Cek Log:**
   ```
   [WARNING] Moota Webhook: Unmatched transaction
   ```

2. **Kemungkinan Penyebab:**
   - Nominal tidak sesuai
   - Kode unik tidak ada di note
   - Tidak ada payment pending dengan nominal yang sama
   - Event tidak menggunakan metode Moota

3. **Solusi:**
   - Verifikasi manual di Admin Panel
   - Atau update note transfer untuk mencocokkan

---

## 8. Dashboard Monitoring (Opsional)

Untuk monitoring yang lebih mudah, Anda bisa membuat dashboard khusus yang menampilkan:

- Total payment Moota hari ini
- Payment yang pending
- Payment yang terverifikasi
- Transaksi yang tidak cocok

Contoh query:

```sql
-- Total Payment Moota Hari Ini
SELECT COUNT(*) as total
FROM payments p
JOIN participants pt ON p.participant_id = pt.id
JOIN packages pk ON pt.package_id = pk.id
JOIN events ev ON pk.event_id = ev.id
WHERE ev.payment_method = 'moota'
  AND DATE(p.created_at) = CURDATE();

-- Payment Pending
SELECT COUNT(*) as pending
FROM payments p
JOIN participants pt ON p.participant_id = pt.id
JOIN packages pk ON pt.package_id = pk.id
JOIN events ev ON pk.event_id = ev.id
WHERE ev.payment_method = 'moota'
  AND p.status = 'pending';

-- Payment Verified Hari Ini
SELECT COUNT(*) as verified
FROM payments p
JOIN participants pt ON p.participant_id = pt.id
JOIN packages pk ON pt.package_id = pk.id
JOIN events ev ON pk.event_id = ev.id
WHERE ev.payment_method = 'moota'
  AND p.status = 'verified'
  AND DATE(p.payment_verified_at) = CURDATE();
```

---

## 9. Command Line Tools

### Buat Artisan Command untuk Monitoring:

Buat file `app/Console/Commands/CheckMootaPayments.php`:

```php
<?php

namespace App\Console\Commands;

use App\Models\Payment;
use Illuminate\Console\Command;

class CheckMootaPayments extends Command
{
    protected $signature = 'moota:check-payments';
    protected $description = 'Check Moota payment status';

    public function handle()
    {
        $pending = Payment::whereHas('participant.package.event', function($q) {
            $q->where('payment_method', 'moota');
        })->where('status', 'pending')->count();

        $verified = Payment::whereHas('participant.package.event', function($q) {
            $q->where('payment_method', 'moota');
        })->where('status', 'verified')
        ->whereDate('payment_verified_at', today())
        ->count();

        $this->info("Pending: {$pending}");
        $this->info("Verified Today: {$verified}");
    }
}
```

Jalankan:
```bash
php artisan moota:check-payments
```

---

## Kesimpulan

**Cara Tercepat untuk Cek:**
1. Buka Admin Panel > Payment Proofs
2. Filter Status = "Verified"
3. Cek apakah ada Payment Reference dan Payment Verified At

**Cara Paling Detail:**
1. Cek log Laravel: `tail -f storage/logs/laravel.log`
2. Filter dengan: `grep -i "moota"`
3. Cari log: `Payment verified successfully`

**Cara untuk Debugging:**
1. Test webhook manual dengan Postman
2. Cek queue worker berjalan
3. Cek log untuk error
4. Cek database untuk payment status

