
# 📄 PRODUCT REQUIREMENTS DOCUMENT

# DESRC.ID – Event & Race Management System

---

## 1. Product Overview

**Nama Produk:** DESRC.ID
**Tipe:** Web Application (PWA + Admin Panel)
**Target:** Komunitas & Organizer event pushbike / race

### Problem Statement

Saat ini event pushbike biasanya:

* Registrasi manual / Google Form
* Validasi pembayaran manual
* Input scoring rawan salah
* Rekap hasil lambat
* Dokumentasi tercecer
* Tidak ada database rider terpusat

Ini tidak scalable, tidak profesional, dan tidak efisien.

### Solution

DESRC.ID adalah sistem terintegrasi untuk:

* Registrasi event
* Manajemen rider/member
* Pembayaran online/offline
* Race scoring realtime
* Leaderboard live
* Laporan & dokumentasi terpusat

---

## 2. Target User

### 1️⃣ Organizer

* Membuat & mengelola event
* Mengatur kategori & kuota
* Mengelola pembayaran
* Mengakses laporan

### 2️⃣ RC / Race Committee

* Input hasil heat/final
* Validasi hasil
* Monitoring leaderboard

### 3️⃣ Rider / Orang Tua

* Daftar event
* Upload dokumen
* Cek jadwal heat
* Cek hasil race

### 4️⃣ Super Admin

* Multi organizer management
* Monitoring sistem
* Manajemen tenant

---

## 3. Product Scope

### ✅ In Scope (MVP)

* Registrasi Event
* Manajemen Rider
* Payment Integration (Midtrans)
* Manual Payment Confirmation
* Race Scoring (heat & final)
* Live Leaderboard
* Export PDF hasil
* Role & Permission
* Dashboard Admin

### ❌ Out of Scope (Phase 2+)

* Native mobile app
* AI ranking prediction
* Sponsor marketplace
* Merchandise marketplace

---

## 4. Core Features

---

## A. Event Management

### Functional Requirements

* Buat event
* Atur tanggal & lokasi
* Buat kategori / kelas
* Set kuota per kategori
* Set harga & voucher
* Publish / Draft mode
* Multi-event support

### Validation Rules

* Kuota tidak boleh overbook
* Payment status harus valid sebelum masuk start list
* Tidak bisa publish tanpa minimal 1 kategori

---

## B. Rider / Member Management

* Rider bisa punya akun atau custom (tanpa login)
* Data rider:

  * Nama
  * Panggilan
  * DOB
  * Kota
  * Tim
  * No KIA
  * Foto
* Riwayat event
* Import/export CSV
* Verifikasi dokumen

---

## C. Registration & Payment

### Flow:

1. Pilih Event
2. Pilih Kategori
3. Input Data Rider
4. Validasi Kuota
5. Pilih Payment Method
6. Generate Invoice
7. Payment
8. Status → Paid / Pending
9. E-ticket + QR Code

### Payment Method

* Online via Midtrans
* Manual transfer
* Cash (admin confirm)

### Rules

* Payment expired otomatis
* Auto update via webhook
* Admin bisa override (dengan audit log)

---

## D. Race Management & Scoring

### Format Race

* Time Trial
* Heat
* Final
* Grand Final

### Scoring Mode

* Best Time
* Point System
* Custom point

### Tie Breaker

* Best lap
* Urutan heat
* Umur
* Manual override

### RC Panel

* Multi-device input
* Realtime leaderboard
* Lock result
* Export PDF
* Print mode

---

## E. Dashboard & Insight

Organizer Dashboard:

* Total peserta
* Total revenue
* Kategori terfavorit
* Payment status breakdown
* Check-in progress
* Race progress

Super Admin Dashboard:

* Total event aktif
* Organizer aktif
* Total transaksi
* System health

---

## F. Gallery & Dokumentasi

* Upload dokumentasi per event
* Integrasi Google Drive (opsional)
* Thumbnail auto generate
* Lazy load super cepat
* Album per kategori

---

## G. Role & Permission

Roles:

* Super Admin
* Organizer
* RC
* Wali / Rider

RBAC:

* Granular permission
* Audit log setiap perubahan penting

---

## 5. Non-Functional Requirements

### Performance

* 500 concurrent users (target awal)
* Response time < 500ms
* Leaderboard realtime delay < 2 detik

### Security

* 2FA untuk admin
* Tenant isolation (tenant_id)
* CSRF protection
* Payment webhook verification
* Encrypted sensitive data

### Reliability

* Backup harian
* Queue system
* Retry webhook
* Graceful degradation saat traffic tinggi

### Scalability

* Monolith modular
* Single DB (tenant_id)
* Redis cache
* Queue worker

---

## 6. Technical Stack (Proposed)

Backend:

* Laravel (Monolith Modular)

Frontend:

* Blade + Livewire / Volt
* PWA for RC Panel

Database:

* MySQL

Realtime:

* Laravel WebSockets

PDF:

* Snappy

Observability:

* Telescope
* Horizon

---

## 7. Success Metrics (KPIs)

* 90% event gunakan sistem tanpa manual backup
* 0 error fatal di hasil final
* 95% payment auto-detected
* < 3% komplain hasil scoring
* 80% organizer repeat use

---

## 8. Risk Analysis

| Risk                   | Impact       | Mitigation                 |
| ---------------------- | ------------ | -------------------------- |
| RC salah input         | Fatal        | Confirmation + lock system |
| Payment gagal webhook  | Revenue loss | Retry + manual confirm     |
| Server down saat event | Chaos        | Load test + backup VPS     |
| Kuota overbook         | Chaos        | Atomic transaction         |

---

## 9. Roadmap

### Phase 1 (MVP – 3 bulan)

* Event
* Registration
* Payment
* Scoring basic
* Dashboard

### Phase 2

* Multi organizer full
* Insight analytics
* Gallery
* Voucher advanced

### Phase 3

* Public ranking system
* API external
* Sponsor exposure module

---
