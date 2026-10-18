# Menjalankan EduLMS

## Laragon / XAMPP

1. Jalankan Apache dan MySQL. Simpan folder proyek sebagai `EduLMS` di document root (`C:\laragon\www\EduLMS` atau `C:\xampp\htdocs\EduLMS`).
2. Gunakan PHP 8.2+ dengan `pdo_mysql`, `fileinfo`, `mbstring`; aktifkan `zip` untuk validasi container DOCX/PPTX. Set `upload_max_filesize=20M` dan `post_max_size=24M`, lalu restart Apache.
3. Koneksi default menggunakan MySQL localhost, user lokal root tanpa password, database `edulms_db`. Untuk konfigurasi lain, gunakan environment `EDULMS_DB_HOST`, `EDULMS_DB_NAME`, `EDULMS_DB_USER`, `EDULMS_DB_PASSWORD`. Jangan menyimpan password nyata dalam source/Git.
4. Jika database belum ada, jalankan dari folder proyek: `php docs/install_fresh.php`. Installer membuat schema dan akun awal, serta **menolak berjalan jika database sudah ada**. Pada lingkungan sesi ini database utama sudah dibuat; tidak perlu mengulang.
5. Jika memakai DB existing, pertahankan isinya. Tinjau `database/migrasi_inkremental.sql` terlebih dahulu bersama backup dan tipe kolom ID. Jangan impor `database/edulms_db.sql` ke DB existing karena berisi DROP.
6. Buka `http://localhost/EduLMS/`. Pastikan Apache mengizinkan `.htaccess` agar file uploads dan direktori internal tidak dapat diakses langsung.

## Akun instalasi fresh

Semua akun awal menggunakan kata sandi `password` untuk penggunaan lokal. Ubah sebelum penggunaan sungguhan.

| Peran | Email |
|---|---|
| Admin | admin@edulms.sch.id |
| Guru Matematika | ahmad.dahlan@edulms.sch.id |
| Siswa | budi.demo@student.edulms.sch.id |
| Siswa kedua | aisyah.demo@student.edulms.sch.id |

Installer menyediakan enam akun guru, tiga kelas, enam mapel, serta dua siswa pada XI MIPA 1. Materi/tugas/nilai/presensi/pengumuman dimulai kosong; isikan melalui portal. Statistik kosong bukan error.

Tahun ajaran dipertahankan `2024/2025` sesuai data existing; set `EDULMS_TAHUN_AJARAN` bila sekolah memakai tahun lain. Jangan hanya mengubah label: kelas, penugasan dan penempatan siswa harus memakai tahun yang sama.

## Server PHP alternatif

Jalankan `php -S 127.0.0.1:8099 -t . router.php`, lalu buka `http://127.0.0.1:8099/`. **Gunakan router.php**: server PHP bawaan tidak membaca `.htaccess`. Server yang dipakai selama QA akan dihentikan setelah pekerjaan.

## Pengujian ulang

- Lint PowerShell: `Get-ChildItem -Recurse -Filter *.php auth,dashboard,includes | ForEach-Object { php -l $_.FullName }`.
- Cek DB (CLI saja): `php docs/check_database.php`.
- Untuk uji end-to-end, gunakan DB terpisah: set `$env:EDULMS_DB_NAME='edulms_qa_nama_baru'`, jalankan installer fresh, lalu server di port 8100 dengan router. Di terminal kedua set environment DB yang sama, lalu `python docs/test_e2e.py`. Jangan mengarahkannya ke DB produksi. Skrip menolak nama DB selain prefix `edulms_qa_`.
- Browser: `docs/browser_smoke.cjs` memerlukan Puppeteer Core dan Chrome lokal. Set `EDULMS_PUPPETEER` ke path modul, `EDULMS_QA_URL` ke URL server, dan opsional `EDULMS_QA_STUDENT` ke email siswa uji. Screenshot disimpan di `docs/screenshots`.
- Verifikasi manual: Admin membuat kelas/mapel dan penugasan → guru mengunggah materi/membuat tugas → siswa mengunduh dan mengumpulkan → guru menilai → siswa melihat feedback/statistik. Ulangi dengan presensi dan pengumuman target guru/siswa.

## Aset dan build

CSS, grafik, ikon dan font sudah disertakan lokal. **Tidak perlu Node/npm untuk menjalankan PHP.** Bila class Tailwind ditambah, instal build tool opsional: `npm.cmd install --prefix .qa-tools --no-package-lock tailwindcss@3.4.17`, lalu `node docs/build-css.cjs`. Konfigurasi ada di `tailwind.config.cjs`; output `assets/css/tailwind.css` disertakan dalam proyek. Folder `.qa-tools` tidak dilacak Git.

Integrasi Belajar.id/Google, token ujian, QR presensi dan rapor eksternal belum tersedia. Fitur ingat identitas hanya mengingat email/NISN/NIP pada browser dan tidak memperpanjang sesi 30 hari.
