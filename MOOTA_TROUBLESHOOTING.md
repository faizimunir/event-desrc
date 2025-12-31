# Troubleshooting Moota Webhook

## Masalah: Payment Tidak Terverifikasi Setelah Transfer

### Gejala:
- Transfer sudah dilakukan
- Webhook diterima (ada di log)
- Tapi status payment tetap "pending"
- Tidak ada update di Admin Panel

---

## Penyebab Umum

### 1. Payload Format Berbeda

**Masalah:** Moota mengirim payload dalam format array dengan struktur berbeda dari yang diharapkan.

**Solusi:** Controller sudah diupdate untuk handle:
- Payload sebagai array: `[{"mutation_id":...}]`
- Type "CR" bukan "credit"
- Field `mutation_id` bukan `id`
- Nested structure dengan `bank`, `payment_detail`, dll

**Cek Log:**
```bash
tail -f storage/logs/laravel.log | grep -i "moota"
```

Cari log:
- `Moota Webhook: Received and queued` = Webhook diterima
- `Moota Verify Payment: Starting verification` = Proses matching dimulai
- `Moota Verify Payment: Match found` = Payment cocok
- `Moota Verify Payment: No match found` = Payment tidak cocok

---

### 2. Nominal Tidak Cocok

**Masalah:** Nominal transfer tidak sesuai dengan yang diharapkan.

**Cek:**
1. Nominal yang diterima Moota: `amount` di log
2. Nominal yang diharapkan: `package.price + unique_code`
3. Toleransi: `MOOTA_AMOUNT_TOLERANCE` di config (default: 0)

**Contoh dari Log:**
```
amount: 200162
```

**Cara Cek Nominal yang Diharapkan:**
```sql
SELECT 
    p.id,
    p.amount,
    pt.unique_code,
    pk.price,
    (pk.price + CAST(pt.unique_code AS UNSIGNED)) as expected_amount
FROM payments p
JOIN participants pt ON p.participant_id = pt.id
JOIN packages pk ON pt.package_id = pk.id
WHERE p.status = 'pending';
```

**Solusi:**
- Pastikan transfer sesuai persis (termasuk kode unik)
- Atau tambahkan toleransi di `.env`:
  ```env
  MOOTA_AMOUNT_TOLERANCE=100
  ```

---

### 3. Kode Unik Tidak Ada di Note

**Masalah:** Transfer tidak menyertakan kode unik di note/deskripsi.

**Cek Log:**
```
note: null
```

**Solusi:**
- Sistem akan tetap mencocokkan berdasarkan nominal saja
- Tapi lebih baik sertakan kode unik di note transfer
- Atau sertakan nomor registrasi

---

### 4. Queue Worker Tidak Berjalan

**Masalah:** Webhook diterima tapi tidak diproses karena queue worker tidak berjalan.

**Cek:**
```bash
php artisan queue:work
```

**Solusi:**
1. Jalankan queue worker:
   ```bash
   php artisan queue:work
   ```

2. Atau gunakan supervisor untuk auto-restart:
   ```ini
   [program:laravel-worker]
   process_name=%(program_name)s_%(process_num)02d
   command=php /path/to/artisan queue:work --sleep=3 --tries=3
   autostart=true
   autorestart=true
   user=www-data
   numprocs=1
   redirect_stderr=true
   stdout_logfile=/path/to/worker.log
   ```

---

### 5. Payment Belum Dibuat

**Masalah:** Payment record belum dibuat saat registrasi.

**Cek:**
```sql
SELECT * FROM payments 
WHERE participant_id = [participant_id];
```

**Solusi:**
- Pastikan event menggunakan `payment_method = 'moota'`
- Payment akan otomatis dibuat saat registrasi untuk event Moota
- Jika belum ada, buat manual atau cek log registrasi

---

## Debugging Step by Step

### Step 1: Cek Webhook Diterima

```bash
tail -f storage/logs/laravel.log | grep "Moota Webhook: Received"
```

Harus muncul:
```
[INFO] Moota Webhook: Received and queued
mutation_id: "Z3W0Y79x5kn"
amount: 200162
```

### Step 2: Cek Queue Diproses

```bash
php artisan queue:work --verbose
```

Atau cek jobs table:
```sql
SELECT * FROM jobs ORDER BY created_at DESC LIMIT 5;
```

### Step 3: Cek Matching Process

```bash
tail -f storage/logs/laravel.log | grep "Moota Verify Payment"
```

Harus muncul:
```
[INFO] Moota Verify Payment: Starting verification
[INFO] Moota Verify Payment: Match found
```

Atau jika tidak cocok:
```
[WARNING] Moota Verify Payment: No match found
```

### Step 4: Cek Payment Status

```sql
SELECT 
    p.id,
    p.status,
    p.payment_reference,
    p.payment_verified_at,
    pt.unique_code,
    pk.price,
    (pk.price + CAST(pt.unique_code AS UNSIGNED)) as expected_amount
FROM payments p
JOIN participants pt ON p.participant_id = pt.id
JOIN packages pk ON pt.package_id = pk.id
WHERE p.status = 'pending'
ORDER BY p.created_at DESC;
```

---

## Test Manual Webhook

### Test dengan curl:

```bash
curl -X POST http://localhost:8000/webhook/moota \
  -H "Content-Type: application/json" \
  -d '[{
    "mutation_id": "test-123",
    "bank_id": "bank-456",
    "account_number": "098706543221",
    "amount": 200162,
    "type": "CR",
    "date": "2025-12-31 11:52:17",
    "note": null,
    "description": null
  }]'
```

### Test dengan Postman:

1. Method: POST
2. URL: `http://localhost:8000/webhook/moota`
3. Headers: `Content-Type: application/json`
4. Body (raw JSON):
```json
[{
  "mutation_id": "test-123",
  "bank_id": "bank-456",
  "account_number": "098706543221",
  "amount": 200162,
  "type": "CR",
  "date": "2025-12-31 11:52:17",
  "note": null
}]
```

---

## Checklist Verifikasi

- [ ] Webhook URL benar di dashboard Moota
- [ ] Queue worker berjalan: `php artisan queue:work`
- [ ] Log menunjukkan "Received and queued"
- [ ] Log menunjukkan "Starting verification"
- [ ] Nominal transfer sesuai (cek di database)
- [ ] Payment record sudah dibuat
- [ ] Event menggunakan `payment_method = 'moota'`
- [ ] Tidak ada error di log

---

## Update Terbaru

Controller sudah diupdate untuk handle:
- ✅ Payload sebagai array
- ✅ Type "CR" / "DB" 
- ✅ Field `mutation_id`
- ✅ Nested structure
- ✅ Logging yang lebih detail

Jika masih ada masalah, cek log untuk detail error.

