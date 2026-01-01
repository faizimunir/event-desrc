# Auto-Refresh Halaman Payment Peserta

## Fitur

Halaman payment peserta akan **otomatis ter-update** setiap 10 detik jika:
- Event menggunakan metode pembayaran **Moota**
- Payment belum terverifikasi (status masih pending)

Setelah payment terverifikasi, halaman akan otomatis menampilkan pesan sukses tanpa perlu refresh manual.

---

## Cara Kerja

### 1. Livewire Polling

Menggunakan fitur `wire:poll` dari Livewire:
```blade
wire:poll.10s="refreshPayment"
```

- Polling setiap **10 detik**
- Hanya aktif jika `payment_confirmed = false`
- Hanya untuk event dengan `payment_method = 'moota'`

### 2. Method `refreshPayment()`

Method ini akan:
1. Reload data participant dan payment dari database
2. Cek apakah payment sudah terverifikasi
3. Jika sudah terverifikasi, update `payment_confirmed = true`
4. Tampilkan pesan sukses otomatis

### 3. Deteksi Verifikasi

Payment dianggap terverifikasi jika:
- `participant.status = 'confirmed'` **ATAU**
- `payment.status = 'verified'`

---

## Flow Lengkap

1. **Peserta melakukan transfer**
   - Halaman menampilkan "Menunggu Verifikasi Otomatis"
   - Polling aktif (setiap 10 detik)

2. **Moota mengirim webhook**
   - Webhook diproses di queue
   - Payment status diupdate ke `verified`
   - Participant status diupdate ke `confirmed`

3. **Polling mendeteksi perubahan**
   - `refreshPayment()` dipanggil
   - Data di-reload dari database
   - `payment_confirmed` menjadi `true`

4. **Halaman auto-update**
   - Polling berhenti (karena sudah confirmed)
   - Tampilan berubah ke pesan sukses
   - Peserta melihat konfirmasi

---

## Testing

### Test Manual:

1. Daftar event dengan metode Moota
2. Buka halaman payment
3. Lihat status "Menunggu Verifikasi Otomatis"
4. Lakukan transfer (atau simulasi webhook)
5. Tunggu maksimal 10 detik
6. Halaman akan otomatis update ke pesan sukses

### Cek Log:

```bash
tail -f storage/logs/laravel.log | grep "Payment auto-verified"
```

---

## Troubleshooting

### Polling Tidak Berjalan

**Cek:**
1. Pastikan event menggunakan `payment_method = 'moota'`
2. Pastikan `payment_confirmed = false`
3. Cek browser console untuk error JavaScript
4. Pastikan Livewire sudah ter-load

**Test:**
- Buka browser DevTools > Network
- Harus ada request setiap 10 detik ke endpoint Livewire

### Halaman Tidak Update Meski Payment Sudah Verified

**Cek:**
1. Cek di database apakah payment sudah `verified`:
   ```sql
   SELECT * FROM payments WHERE participant_id = [id];
   ```

2. Cek apakah participant sudah `confirmed`:
   ```sql
   SELECT status FROM participants WHERE id = [id];
   ```

3. Cek log untuk error:
   ```bash
   tail -f storage/logs/laravel.log
   ```

### Polling Terlalu Sering

**Ubah interval polling:**
```blade
wire:poll.30s="refreshPayment"  <!-- 30 detik -->
wire:poll.5s="refreshPayment"  <!-- 5 detik -->
```

---

## Optimasi

### Mengurangi Beban Server

Polling setiap 10 detik sudah cukup untuk UX yang baik tanpa membebani server. Jika perlu, bisa ditingkatkan ke 15-30 detik.

### Stop Polling Setelah Confirmed

Polling otomatis berhenti setelah `payment_confirmed = true`, jadi tidak ada beban tambahan setelah verifikasi.

---

## Catatan

- Polling hanya aktif untuk event Moota
- Polling berhenti otomatis setelah payment terverifikasi
- Tidak perlu refresh manual halaman
- UX lebih baik karena real-time update


