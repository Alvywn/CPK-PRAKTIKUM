# NusaChat — Aplikasi Pengirim Pesan & Buku Kontak Dasar (Kriteria 1 & 2)
**Proyek Praktikum Pemrograman Berbasis Platform: Frontend dan Backend**

Proyek ini adalah tahap **Kriteria 1 dan Kriteria 2** dari aplikasi pengirim pesan sederhana menggunakan **Pure Native Architecture** (**HTML5, CSS3 Murni, JavaScript Murni** pada sisi Frontend dan **PHP Murni + JWT** pada sisi Backend dengan basis data **MariaDB / MySQL**).

---

## Checklist Pemenuhan Kriteria 1 & 2

### Kriteria 1: Syarat Pengumpulan, Kepatuhan Lingkungan, dan Integritas Proyek
- [x] **Pemisahan Struktur Native**: Folder `frontend/` dan `backend/` dipisahkan secara tegas.
- [x] **Bebas Framework & Library**: Tidak menggunakan Bootstrap, Tailwind CSS, React, atau Laravel (hanya `firebase/php-jwt` v7.2.1 sesuai aturan).
- [x] **Disiplin Version Control**: Commit dipisahkan per perubahan logis menggunakan format *Conventional Commit Messages*.
- [x] **Laporan Durasi**: Tercatat pada [`docs/LOG_DURASI_PENGERJAAN.md`](docs/LOG_DURASI_PENGERJAAN.md).

### Kriteria 2: Penerapan Materi Dasar Praktikum PBP
- [x] **Komunikasi Asinkron**: Menggunakan `fetch()` API di [`frontend/js/app.js`](frontend/js/app.js).
- [x] **Format Data Standar**: Mengirim request menggunakan `FormData` dan `JSON`.
- [x] **Format Response API**: Server mengembalikan JSON valid melalui [`backend/helpers/response.php`](backend/helpers/response.php).
- [x] **Manajemen Pengguna**: Registrasi akun baru, login berbasis JWT, dan penghapusan akun pada [`backend/api/auth.php`](backend/api/auth.php).
- [x] **Manajemen Kontak Dasar (CRUD)**: Tambah, lihat, ubah, dan hapus kontak per akun pada [`backend/api/contacts.php`](backend/api/contacts.php).

---

## Cara Menjalankan Proyek

1. Pastikan layanan **MySQL / MariaDB** (Laragon/XAMPP) aktif. Database `db_messaging_k2` akan otomatis dibuat saat API pertama kali diakses, atau impor [`backend/database/schema.sql`](backend/database/schema.sql).
2. Jalankan server Backend (Port 8000):
   ```powershell
   php -S localhost:8000 -t backend
   ```
3. Jalankan server Frontend (Port 3000):
   ```powershell
   php -S localhost:3000 -t frontend
   ```
4. Buka **`http://localhost:3000`** di browser.
