# Setup Webhook Moota untuk Development Local

## Masalah
Webhook dari Moota memerlukan URL yang dapat diakses dari internet. Localhost (`http://localhost:8000`) tidak dapat diakses dari luar, sehingga webhook tidak bisa dikirim ke aplikasi local.

## Solusi: Menggunakan Tunneling Service

Tunneling service membuat URL publik yang meneruskan request ke localhost Anda.

---

## Opsi 1: ngrok (Paling Populer)

### Kelebihan:
- Mudah digunakan
- Gratis dengan beberapa limit
- Banyak dokumentasi

### Kekurangan:
- URL berubah setiap restart (kecuali pakai paid plan)

### Langkah-langkah:

1. **Download ngrok:**
   - Kunjungi https://ngrok.com/download
   - Download sesuai OS Anda
   - Extract dan simpan di folder yang mudah diakses

2. **Jalankan Laravel:**
   ```bash
   php artisan serve
   ```
   (Catat port yang digunakan, biasanya 8000)

3. **Jalankan ngrok:**
   ```bash
   ngrok http 8000
   ```
   Atau jika ngrok tidak di PATH:
   ```bash
   ./ngrok http 8000
   ```

4. **Copy URL Forwarding:**
   ngrok akan menampilkan:
   ```
   Forwarding  https://abc123.ngrok.io -> http://localhost:8000
   ```
   Copy URL `https://abc123.ngrok.io`

5. **Setup di Moota:**
   - Login ke https://app.moota.co
   - Masuk ke Settings > Webhook
   - Tambahkan webhook URL: `https://abc123.ngrok.io/webhook/moota`
   - Pilih event: "Mutation Credit"

6. **Test Webhook:**
   - Lakukan transfer test
   - Cek log Laravel: `storage/logs/laravel.log`
   - Atau cek ngrok dashboard: http://127.0.0.1:4040

---

## Opsi 2: localtunnel (URL Custom, Gratis)

### Kelebihan:
- URL bisa custom (lebih mudah diingat)
- Gratis
- URL relatif stabil

### Kekurangan:
- Perlu install Node.js
- Kadang ada iklan di halaman pertama

### Langkah-langkah:

1. **Install Node.js** (jika belum):
   - Download dari https://nodejs.org/

2. **Install localtunnel:**
   ```bash
   npm install -g localtunnel
   ```

3. **Jalankan Laravel:**
   ```bash
   php artisan serve
   ```

4. **Jalankan localtunnel:**
   ```bash
   lt --port 8000 --subdomain your-event-system
   ```
   (Ganti `your-event-system` dengan nama custom Anda)

5. **Copy URL:**
   ```
   your url is: https://your-event-system.loca.lt
   ```

6. **Setup di Moota:**
   - Webhook URL: `https://your-event-system.loca.lt/webhook/moota`

---

## Opsi 3: Cloudflare Tunnel (Gratis, URL Tetap)

### Kelebihan:
- Gratis
- URL bisa dibuat tetap
- Reliable

### Kekurangan:
- Setup sedikit lebih kompleks

### Langkah-langkah:

1. **Install cloudflared:**
   - Download dari https://developers.cloudflare.com/cloudflare-one/connections/connect-apps/install-and-setup/installation/

2. **Jalankan Laravel:**
   ```bash
   php artisan serve
   ```

3. **Jalankan Cloudflare Tunnel:**
   ```bash
   cloudflared tunnel --url http://localhost:8000
   ```

4. **Copy URL yang diberikan**

5. **Setup di Moota:**
   - Webhook URL: `[url-dari-cloudflared]/webhook/moota`

---

## Opsi 4: serveo (SSH-based, Gratis)

### Kelebihan:
- Tidak perlu install software tambahan
- Hanya perlu SSH

### Kekurangan:
- Perlu SSH client
- URL bisa berubah

### Langkah-langkah:

1. **Jalankan Laravel:**
   ```bash
   php artisan serve
   ```

2. **Jalankan serveo:**
   ```bash
   ssh -R 80:localhost:8000 serveo.net
   ```
   (Jika port 80 tidak tersedia, gunakan port lain: `ssh -R 8080:localhost:8000 serveo.net`)

3. **Copy URL yang diberikan**

4. **Setup di Moota:**
   - Webhook URL: `[url-dari-serveo]/webhook/moota`

---

## Testing Webhook

### 1. Test dengan ngrok Web Interface:
- Buka http://127.0.0.1:4040 (saat ngrok berjalan)
- Klik "Request" untuk melihat request yang masuk
- Klik request untuk melihat detail

### 2. Test dengan Postman/curl:
```bash
curl -X POST https://your-tunnel-url/webhook/moota \
  -H "Content-Type: application/json" \
  -d '{
    "id": "test-123",
    "bank_id": "bank-456",
    "account_number": "1234567890",
    "amount": 150000,
    "type": "credit",
    "date": "2024-01-15 10:30:00",
    "note": "Test payment"
  }'
```

### 3. Cek Log Laravel:
```bash
tail -f storage/logs/laravel.log
```

### 4. Cek Queue:
Pastikan queue worker berjalan:
```bash
php artisan queue:work
```

---

## Troubleshooting

### Webhook tidak diterima:
1. **Cek apakah tunnel masih berjalan**
2. **Cek URL di Moota sudah benar** (harus `/webhook/moota`)
3. **Cek Laravel log** untuk error
4. **Cek queue worker** berjalan
5. **Test dengan Postman** untuk memastikan endpoint bisa diakses

### URL berubah setiap restart:
- Gunakan localtunnel dengan subdomain custom
- Atau gunakan Cloudflare Tunnel dengan setup permanen
- Atau gunakan ngrok paid plan

### Webhook diterima tapi tidak diproses:
1. **Cek queue worker berjalan:**
   ```bash
   php artisan queue:work
   ```
2. **Cek log untuk error:**
   ```bash
   tail -f storage/logs/laravel.log
   ```
3. **Cek MOOTA_API_KEY** sudah diisi di `.env`
4. **Cek database** migration sudah dijalankan

### Error "Connection refused":
- Pastikan Laravel server berjalan
- Pastikan port di tunnel sesuai dengan port Laravel
- Cek firewall tidak memblokir

---

## Rekomendasi

**Untuk Development:**
- **Quick testing:** Gunakan ngrok (paling mudah)
- **Stabil URL:** Gunakan localtunnel dengan subdomain custom
- **Production-like:** Gunakan Cloudflare Tunnel

**Untuk Production:**
- Gunakan domain/subdomain yang sudah di-deploy
- Setup SSL certificate
- Pastikan server dapat diakses dari internet

---

## Catatan Penting

1. **Jangan commit URL tunnel ke git** - URL tunnel adalah temporary
2. **Update URL di Moota** setiap kali tunnel URL berubah
3. **Test webhook** setelah setup untuk memastikan berfungsi
4. **Monitor log** untuk debugging
5. **Pastikan queue worker berjalan** untuk memproses webhook

---

## Contoh .env untuk Development

```env
# Moota Configuration
MOOTA_API_KEY=your_api_key_here
MOOTA_API_URL=https://app.moota.co/api/v2
MOOTA_WEBHOOK_URL=/webhook/moota

# Queue Configuration (untuk development)
QUEUE_CONNECTION=database
```

Jalankan queue worker:
```bash
php artisan queue:work
```

