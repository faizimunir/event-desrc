# Spesifikasi: Registrasi Rider + Orang Tua (WA) + Aktivasi Akun

## Keputusan yang disepakati

1. **WhatsApp:** unique per user (satu nomor WA = satu user).
2. **Cek “mungkin rider yang sama”:** berdasarkan **nama + DOB + gender + WA** (hanya cek rider yang sudah terhubung ke user dengan WA yang sama).
3. **OTP WhatsApp:** menggunakan **Whacenter**.
4. **Login:** setelah aktivasi dengan **email + password**. Di halaman login ada **opsi untuk aktivasi** (mis. “Aktifkan akun” untuk user yang baru punya WA/registrasi event).

---

## 1. Model data (konsep)

### `users`
- `name` — Nama Orang Tua (dari form registrasi).
- `email` — nullable sampai user aktivasi; setelah aktivasi unique.
- `password` — nullable sampai user set password saat aktivasi.
- `whatsapp` — **unique**, nomor WA orang tua (format normalisasi, e.g. 62xxx).
- `email_verified_at` / `activated_at` — nullable; terisi setelah aktivasi berhasil.
- Role: **member** (untuk orang tua yang daftar rider).

### `riders`
- `user_id` — FK ke `users` (orang tua yang “memiliki” rider ini).
- Field lain tetap: name, nickname, dob, gender, pob, number_plate, dll.

### Similarity check
- Cek hanya di rider yang `rider.user_id` = user dengan `users.whatsapp` = WA yang diinput.
- Kriteria kemiripan: **nama** (similarity/fuzzy), **DOB** sama, **gender** sama.

---

## 2. Alur registrasi event (dengan orang tua + WA)

1. Form: **Nama Orang Tua**, **No WA**, lalu data **rider** (nama, DOB, gender, dll).
2. Sebelum submit: backend cek rider dengan `user.whatsapp = WA` dan (nama mirip + DOB sama + gender sama).
3. Jika ketemu: tampil “Mungkin Anda sudah terdaftar: [Nama], [DOB]. Pakai profil ini atau daftar sebagai rider baru?” → user pilih.
4. Submit:
   - User: cari by `whatsapp` (unique); kalau belum ada, buat user baru (name, whatsapp, role member).
   - Rider: pakai yang dipilih atau buat baru; set `rider.user_id`.
   - Buat registration event seperti sekarang.

---

## 3. Aktivasi akun (email + password + OTP WA)

- **Kapan:** user memilih “Aktifkan akun” (bisa dari dashboard/profil atau dari **halaman login**).
- **Langkah:**
  1. User input **nomor WA** (yang sudah dipakai saat registrasi rider).
  2. Backend cari user by `whatsapp`; kalau tidak ada, tampil error.
  3. User input **email** + **password** (+ konfirmasi password).
  4. Backend simpan email (unique) dan password (hash); kirim **OTP ke WA** via **Whacenter**.
  5. User input **OTP** → verifikasi → set `email_verified_at` / `activated_at` → akun aktif.
- **Login:** hanya setelah aktivasi, dengan **email + password**. Tidak ada login hanya dengan WA (kecuali nanti ada fitur terpisah).

---

## 4. Opsi aktivasi di halaman login

- Di halaman login: tambah link/ tombol **“Aktifkan akun”** (atau “Sudah daftar rider? Aktifkan akun di sini”).
- Flow-nya sama seperti di atas: input WA → cek user → input email + password → OTP via Whacenter → verifikasi → bisa login dengan email + password.

---

## 5. Integrasi Whacenter

- Dokumentasi/API Whacenter untuk kirim pesan (OTP) ke nomor WhatsApp.
- Simpan konfigurasi (API key / endpoint) di `.env`; baca di config.
- Service/helper: kirim OTP, simpan OTP di cache/session dengan TTL (e.g. 5–10 menit), verifikasi kode yang diinput user.

---

## 6. Ringkasan

| Item              | Keputusan                                                |
|-------------------|----------------------------------------------------------|
| WhatsApp          | Unique per user                                          |
| Cek duplikat rider| Nama + DOB + gender + WA (rider milik user dengan WA itu) |
| OTP               | Whacenter                                                |
| Login             | Email + password setelah aktivasi                        |
| Aktivasi          | Opsi aktivasi tersedia di halaman login                   |

File ini bisa dipakai sebagai acuan saat implementasi (migration, similarity, form, halaman login, dan integrasi Whacenter).
