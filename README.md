# NusaChat (Tugas Praktikum PBP — Kriteria 1 & 2)

Halo! Ini adalah project sederhana buat tugas **Praktikum Pemrograman Berbasis Platform (PBP)**.  
Di tahap ini baru ngerjain **Kriteria 1 dan Kriteria 2** dulu ya (bikin akun, login, hapus akun, sama simpan/ubah/hapus daftar kontak).

---

## Di Dalam Project Ini Ada Apa Aja?

Biar rapi dan nggak campur aduk, foldernya dipisah jadi dua bagian:

1. **`frontend/`** — Bagian yang kelihatan di browser (tampilan webnya):
   - `index.html`: Kerangka halaman webnya.
   - `style.css`: Buat ngatur warna, kotak, dan posisi biar enak dilihat (pakai CSS biasa, tanpa Bootstrap atau Tailwind).
   - `app.js`: Otak di sisi browser yang ngirim dan nerima data ke server pakai `fetch()`.
2. **`backend/`** — Bagian dapur/server yang ngurus data ke database:
   - `config/database.php`: Penghubung dari PHP ke database MySQL.
   - `database/schema.sql`: Catatan struktur tabel (`users` dan `contacts`).
   - `helpers/common.php`: Kumpulan fungsi pembantu buat kirim balasan JSON dan cek token login (JWT).
   - `api/auth.php`: Ngurus daftar akun baru, login, cek profil, dan hapus akun.
   - `api/contacts.php`: Ngurus tambah, lihat, ubah, dan hapus kontak.
   - `lib/php-jwt-7.2.1/`: Library JWT resmi (boleh dipakai sesuai aturan praktikum).

---

## Yang Sudah Dikerjakan (Kriteria 1 & 2)

- **Kriteria 1 (Aturan Dasar & Kerapian)**:
  - Folder `frontend` dan `backend` sudah dipisah.
  - Murni pakai HTML, CSS, JS, dan PHP biasa (nggak pakai React, Laravel, Bootstrap, atau Tailwind).
  - Setiap perubahan sudah di-commit satu per satu ke Git.
- **Kriteria 2 (Fitur Dasar)**:
  - Browser ngobrol sama server pakai `fetch()` (kirim data pakai `FormData` dan `JSON`).
  - Server selalu membalas pakai format `JSON` yang rapi.
  - Bisa **Daftar Akun Baru**, **Login**, dan **Hapus Akun**.
  - Bisa **Tambah, Lihat, Ubah, dan Hapus Kontak** di masing-masing akun.

---

## Cara Menjalankan di Laptop (Step by Step)

1. **Nyalakan MySQL dulu**
   - Buka **Laragon** (atau XAMPP), lalu klik **Start** di bagian **MySQL**.
   - Nggak perlu ribet bikin database manual, nanti database `db_messaging_k2` otomatis dibuat sendiri pas aplikasinya dijalankan pertama kali.

2. **Jalankan Server Backend (Terminal 1)**
   Buka terminal di dalam folder project ini, lalu ketik:
   ```powershell
   php -S localhost:8000 -t backend
   ```

3. **Jalankan Server Frontend (Terminal 2)**
   Buka satu terminal lagi di folder project yang sama, lalu ketik:
   ```powershell
   php -S localhost:3000 -t frontend
   ```

4. **Buka di Browser**
   Buka Chrome atau Firefox, terus ketik alamat:
   `http://localhost:3000`

   Selesai! Tinggal coba daftar akun baru, login, terus tambah-tambah kontak deh.
