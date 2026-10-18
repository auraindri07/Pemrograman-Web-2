# 📚 EduLMS — Panduan Penggunaan

**EduLMS** adalah aplikasi *Learning Management System* (LMS) sekolah yang dibangun dengan **PHP Native** (tanpa framework) + MySQL (`edulms_db`). Web ini menghubungkan tiga role: **Admin**, **Guru**, dan **Siswa** — mulai dari pengaturan data sekolah, pembelajaran (materi & tugas), penilaian, presensi, sampai pengumuman.

Pembaruan UI dan pengujian 15 Oktober 2026: lihat [panduan instalasi terbaru](docs/PANDUAN_MENJALANKAN.md), [hasil pengujian](docs/FUNCTIONAL_TEST_REPORT.md), dan [laporan kesesuaian UI](docs/UI_FIDELITY_REPORT.md). Akun awal fresh memakai admin@edulms.sch.id; data demo lama di bawah dapat berbeda pada database existing. Opsi ingat identitas hanya menyimpan identifier di browser, bukan sesi 30 hari. Aset UI sekarang tersedia lokal tanpa CDN.

---

## 1. Cara Mengakses & Masuk (Login)

1. Buka browser, akses: **`http://localhost/EduLMS/`** (atau `http://127.0.0.1:8099/` jika memakai server bawaan).
2. Klik tombol **Masuk / Login** → halaman `auth/login.php`.
3. Isi:
   - **Email / NISN / NIP** — bisa dipilih salah satu (NISN untuk siswa, NIP untuk guru).
   - **Kata Sandi**
   - Centang **remember me** (opsional), lalu klik **Masuk**.
4. Setelah berhasil, Anda langsung diarahkan ke dashboard sesuai role Anda.

> 💡 **Tips:** Di halaman login ada tombol cepat **Siswa / Guru / Admin** — klik salah satu dan kolom akan terisi otomatis dengan akun demo role tersebut, tinggal klik Masuk.

Sistem **membatasi pendaftaran publik hanya untuk siswa**. Guru & admin dibuat/diatur oleh admin. Peran (role) diambil dari database, bukan dari input pengguna, dan semua form dilindungi **token CSRF**.

---

## 2. Akun Demo (Email & Password)

✅ Semua akun di bawah ini **sudah diverifikasi bisa login**.

### 🔵 Admin

| Nama | Email (untuk login) | Password | Role |
|---|---|---|---|
| Administrator EduLMS | `admin@edulms.sch.id` | `password` | Admin |

### 🟢 Guru

| Nama | Email (untuk login) | NIP (alternatif login) | Password | Role |
|---|---|---|---|---|
| Ahmad Dahlan | `ahmad.dahlan@edulms.sch.id` | 198503152010011001 | `password` | Guru |
| Siti Nurhaliza | `siti.nurhaliza@edulms.sch.id` | 199001102015032001 | `password` | Guru |
| Hendra Wijaya | `hendra.wijaya@edulms.sch.id` | 198705202012011002 | `password` | Guru |
| Rina Anggraini | `rina.anggraini@edulms.sch.id` | 199203152016032001 | `password` | Guru |
| Agus Prabowo | `agus.prabowo@edulms.sch.id` | 198808102013011001 | `password` | Guru |
| Sulastri | `sulastri@edulms.sch.id` | 197512052005032001 | `password` | Guru |

### 🟠 Siswa

| Nama | Email (untuk login) | NISN (alternatif login) | Password | Role |
|---|---|---|---|---|
| Budi Santoso (Demo) | `budi.demo@student.edulms.sch.id` | 9000000001 | `password` | Siswa |
| Aisyah Putri (Demo) | `aisyah.demo@student.edulms.sch.id` | 9000000002 | `password` | Siswa |

---

## 3. Cara Pakai per Role

### 🛡️ Admin — `dashboard/admin.php`
Mengelola seluruh data dasar sekolah. Menu (7 halaman):

| Halaman | Fungsi |
|---|---|
| **Dashboard** | Ringkasan jumlah pengguna, kelas, mata pelajaran, aktivitas. |
| **Pengguna** | Tambah/ubah/hapus akun admin, guru, siswa (password di-hash). |
| **Kelas** | Kelola data kelas (mis. XI IPA 1, XII IPS 2). |
| **Mata Pelajaran** | Kelola daftar mata pelajaran + ikon/warna. |
| **Penempatan Siswa** | Masukkan siswa ke kelas (daftarkan siswa per kelas). |
| **Penugasan Guru** | Tugaskan guru ke mata pelajaran + kelas (inilah yang membuka menu guru). |
| **Pengumuman** | Tulis/hapus pengumuman sekolah yang tampil untuk semua siswa & guru. |

**Alur pertama kali:** *Pengguna → Kelas → Mata Pelajaran → Penempatan Siswa → Penugasan Guru → Pengumuman.*

### 🟢 Guru — `dashboard/guru.php`
Menu (4 menu, konsisten di semua halaman guru):

| Menu | Fungsi |
|---|---|
| **Dashboard Guru** | Ringkasan kelas, tugas belum dinilai, jumlah siswa. |
| **Kelas & Mata Pelajaran** | Buka kelas yang diampu → **Kelola Materi** (unggah PDF/dokumen), **Daftar Siswa**, **Buat Tugas** (unggah soal + atur batas waktu & format file). |
| **Penilaian Tugas** | Lihat berkas jawaban siswa, beri **nilai (0–100)** + feedback, unduh dokumen jawaban. Badge angka = tugas belum dinilai. |
| **Presensi Siswa** | Isi presensi (Hadir/Izin/Sakit/Alfa) per siswa per tanggal. |

### 🟠 Siswa — `dashboard/index.php`
Menu (5 menu, konsisten di semua halaman siswa):

| Menu | Fungsi |
|---|---|
| **Dashboard** | Statistik pribadi: rata-rata nilai, presensi, tugas — grafik perkembangan 6 bulan terakhir, ketuntasan per mapel. |
| **Mata Pelajaran** | Daftar mapel → buka detail: materi (unduh), tugas, nilai per tugas. |
| **Tugas** | Daftar tugas aktif → **📄 Lihat Soal** (berkas dari guru), **📤 Kumpulkan Tugas** (upload jawaban), **👁️ Lihat Nilai** (skor + catatan guru). |
| **Riwayat** | Riwayat nilai & presensi per tahun ajaran. |
| **Pengumuman** | Baca pengumuman sekolah. |

*(Halaman presensi pribadi juga dapat diakses dari dashboard/detail mapel.)*

---

## 4. Alur Lengkap Uji Coba (End-to-End)

1. **Login Admin** → buat kelas, mata pelajaran, tempatkan siswa (Budi/Aisyah), tugaskan guru (mis. Ahmad Dahlan).
2. **Login Guru (Ahmad Dahlan)** → buka *Kelas & Mata Pelajaran* → unggah **materi**, lalu **Buat Tugas** + unggah **soal (PDF/docx)**.
3. **Login Siswa (Budi)** → menu *Tugas* → **Lihat Soal** → **Kumpulkan Tugas** (upload jawaban).
4. **Login Guru lagi** → *Penilaian Tugas* → buka jawaban Budi → isi **nilai + feedback** → simpan.
5. **Login Siswa (Budi)** → *Tugas* → **Lihat Nilai** (skor + catatan guru muncul) → cek *Dashboard* (statistik & grafik ikut berubah).

---

## 5. Info Teknis Singkat

| Item | Keterangan |
|---|---|
| Teknologi | PHP Native (teruji PHP 8.4.12), Tailwind CSS lokal, MySQL |
| Database | `edulms_db` (jangan DROP/TRUNCATE — perubahan lewat migrasi inkremental) |
| Struktur | `/auth` login·register · `/dashboard` semua halaman per role · `/includes` koneksi, fungsi, CSRF, header bersama · `/uploads` berkas materi/tugas |
| Keamanan | Password `password_hash()`, sesi role-based (`requireStudent/Teacher/Admin`), CSRF token, endpoint unduh terotentikasi (`dashboard/download.php`), proteksi path traversal |
| File unduhan | Tidak bisa diakses langsung sebagai link statis — harus lewat session login |

> File ini dibuat sebagai panduan penggunaan. Dokumen lain: `perancangan1.md` (desain), `prompt/prompt2.md` (spesifikasi 17 tahap), `database/LAPORAN_PERBAIKAN_DATABASE.md` (laporan perbaikan DB).
