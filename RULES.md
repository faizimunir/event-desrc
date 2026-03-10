# RULES – WAJIB DIPATUHI AI & DEVELOPER

1. Jangan menambah tabel / kolom tanpa update DATA_MODEL.md
2. Jangan membuat fitur di luar SPEC.md
3. Semua akses data harus lewat permission Spatie
4. Jangan rewrite file besar untuk task kecil
5. Maksimal 10 file berubah per task
6. Jika perlu file baru:
   - jelaskan alasan
   - tunggu persetujuan
7. Tidak ada logic bisnis di Blade / View
8. Tidak ada helper global tanpa justifikasi

# Standar UI dan backend

## UI (Blade) — permission & role aktif
- **Permission**: pakai `@canAs()` — JANGAN `@can()` untuk tombol/action
- **Role aktif**: `@activeRole()` / `@activeAnyRole()` hanya kalau memang role-specific view
