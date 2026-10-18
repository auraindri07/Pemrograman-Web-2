# Audit visual EduLMS - 15 Oktober 2026

Target 100% belum terbukti. Status strict seluruh halaman: **BELUM SESUAI**. Tinggi sama bukan bukti identik piksel; MAE dan SSIM di bawah tidak dinyatakan sebagai persentase fidelity. Klaim lama 91% tidak dipakai.

## Metode

- Sembilan PNG asli di EduLMS_UI dibuka dengan tool gambar; Figma tidak dapat diinspeksi pada sesi ini. Tidak ada klaim memperoleh layer/vector Figma.
- Chrome Windows lokal, lebar 1280 CSS px, DSF 1, font Plus Jakarta Sans lokal. Scrollbar disembunyikan melalui CDP; setiap capture mencatat innerWidth dan clientWidth yang sama. Ini mengoreksi pengukuran lama yang dapat kehilangan 15 px akibat scrollbar.
- Bingkai dekoratif referensi dipotong: `(8,7,1288,height-8)`, login `(16,18,1296,1043)`. Koordinat dan region galat terbesar ada di metrics.json. Gambar tidak di-warp atau diskalakan untuk memperkecil galat.
- Capture 1280 memakai tinggi referensi dan full-page. Capture 1440/390 memeriksa responsivitas; tidak dianggap sebagai pembuktian identik untuk referensi 1280.
- MAE RGB 0-255: lebih kecil lebih dekat. SSIM adalah rerata blok lokal 8x8 tanpa overlap (implementasi visual_compare.py), bukan Gaussian skimage. Metrik mencakup konten teks/foto/data; tidak ada semantic mask tervalidasi. Irisan tinggi bersama dipakai untuk metrik, delta tinggi dilaporkan terpisah.
- Capture before/iteration/refinement disimpan sebagai jejak iterasi. State fixture dan metode scrollbar berkembang, sehingga perbandingan skor lintas iterasi bukan eksperimen desain dengan data identik.
- Percobaan font alternatif/variable dan text rendering disimpan di visual-diff/font-experiment; tidak diadopsi karena belum membuktikan kecocokan lebih baik. Rasterisasi tulisan Chrome ini terlihat berbeda dari PNG; perubahan font/CSS blur tidak dipakai untuk menyamarkan perbedaan.

## Isolasi data

Aplikasi tetap PHP/PDO dengan sesi, role, CSRF, query dan penyimpanan nyata. Tidak ada tabel UI yang diganti screenshot atau array contoh. Aset yang diambil dari referensi hanya fotografi/logo/portrait kecil.
Fixture screenshot khusus `edulms_qa_fidelity_reference_20261015` dibuat dari database QA visual sebelumnya, tidak dari produksi. `reference_fixture.php` hanya menerima nama database persis itu dan CLI. Setiap halaman disiapkan sebagai snapshot terpisah karena sembilan PNG memuat jumlah mapel, periode dan angka yang tidak konsisten antarhalaman. Snapshot tidak dipanggil oleh aplikasi produksi.
Tes transaksi memakai `edulms_qa_regression_20261015` pada port 8101. Fixture visual memakai port 8100. Pengujian mengunggah berkas QA di folder upload bersama, dengan akses tetap diperiksa backend.
Inventaris baca-saja terakhir `edulms_db`: 10 users, 3 kelas, 2 penempatan, 6 penugasan; materi/tugas/pengumpulan/presensi/pengumuman semuanya 0. Catatan lama memuat 9 users; akun terbaru bertanggal 22:47, mendahului run tes HTTP 23:16. Akun tersebut dipertahankan. Pengerjaan UI ini tidak menulis fixture ke database aplikasi dan tidak menghapus data pengguna.

## Pengukuran terakhir

| Halaman | Tinggi referensi / aktual | Delta px | MAE RGB | SSIM lokal | Status strict |
|---|---:|---:|---:|---:|---|
| Dashboard Siswa | 1322 / 1322 | +0 | 7.1820 | 0.85662 | BELUM SESUAI |
| Mata Pelajaran Siswa | 891 / 891 | +0 | 4.5830 | 0.91843 | BELUM SESUAI |
| Tugas Siswa | 1275 / 1276 | +1 | 5.4420 | 0.87575 | BELUM SESUAI |
| Pengumuman Siswa | 840 / 840 | +0 | 7.8593 | 0.84357 | BELUM SESUAI |
| Dashboard Guru | 1534 / 1562 | +28 | 13.0401 | 0.77334 | BELUM SESUAI |
| Kelas Guru | 1626 / 1626 | +0 | 11.1900 | 0.79253 | BELUM SESUAI |
| Penilaian Guru | 1441 / 1444 | +3 | 9.5965 | 0.83110 | BELUM SESUAI |
| Presensi Guru | 1518 / 1518 | +0 | 8.5614 | 0.83646 | BELUM SESUAI |
| Login | 1025 / 1025 | +0 | 6.5522 | 0.88193 | BELUM SESUAI |

## Rincian halaman

### Dashboard Siswa

Referensi: `EduLMS_UI/Dashboard dengan Grafik Perkembangan Belajar.png`.

- Awal: Donut/grafik/kartu bertumpuk dan statistik kurang sesuai.
- Diperbaiki: Layout chart dan statistik, tren nilai bulanan dari database, delta nilai dihitung, 3 mapel aktif dengan materi terbanyak, preview deskripsi tugas nyata. Fixture dashboard menghasilkan 14 mapel, 20 tugas, 18 dinilai, rata-rata 88.5, 12 mapel tuntas.
- Sisa: Waktu belajar mengikuti log nyata (fixture belum mereplikasi 12.5 jam). Beberapa isi tugas dan persen progress berbeda. Rasterisasi tulisan dan emoji salam berbeda dari PNG.
- Bukti: [aktual](visual-diff/actual/index.png), [referensi sejajar](visual-diff/actual/index-reference.png), [side-by-side](visual-diff/actual/index-side-by-side.png), [overlay](visual-diff/actual/index-overlay.png), [heatmap](visual-diff/actual/index-heatmap.png).
- Semua lebar diuji tanpa overflow halaman maupun error PHP/JS. Tabel presensi tetap bisa digeser horizontal pada layar kecil.

### Mata Pelajaran Siswa

Referensi: `EduLMS_UI/Slide 1 - Mata Pelajaran.png`.

- Awal: Kartu kurang padat, ikon dan badge berbeda.
- Diperbaiki: Enam kartu dari query relasi kelas, bukan array contoh; urutan mata pelajaran, badge tingkat XI, nama pengajar ringkas, glyph resmi calculate/auto_stories/science/terminal/translate/biotech. Jumlah materi/tugas fixture 12/3,10/1,14/2,16/4,8/2,11/1.
- Sisa: Teks, ketebalan raster font, baseline beberapa label, dan ikon metadata kecil masih memiliki selisih piksel.
- Bukti: [aktual](visual-diff/actual/mata_pelajaran.png), [referensi sejajar](visual-diff/actual/mata_pelajaran-reference.png), [side-by-side](visual-diff/actual/mata_pelajaran-side-by-side.png), [overlay](visual-diff/actual/mata_pelajaran-overlay.png), [heatmap](visual-diff/actual/mata_pelajaran-heatmap.png).
- Semua lebar diuji tanpa overflow halaman maupun error PHP/JS. Tabel presensi tetap bisa digeser horizontal pada layar kecil.

### Tugas Siswa

Referensi: `EduLMS_UI/Slide 2 - Tugas.png`.

- Awal: Daftar terlalu kosong, tinggi baris dan kolom judul berbeda.
- Diperbaiki: Fixture khusus 5 tugas (4 menunggu, 1 nilai 90). Wrapping judul desktop mengikuti penyempitan kolom referensi, badge kategori ringkas, rosette verified, tile bahasa Inggris mint, metadata dan tombol tetap dinamis. Mobile memakai lebar judul normal.
- Sisa: Isi metadata/tanggal mengikuti waktu aplikasi. Tinggi tersisa +1 px dan raster font belum sama.
- Bukti: [aktual](visual-diff/actual/tugas.png), [referensi sejajar](visual-diff/actual/tugas-reference.png), [side-by-side](visual-diff/actual/tugas-side-by-side.png), [overlay](visual-diff/actual/tugas-overlay.png), [heatmap](visual-diff/actual/tugas-heatmap.png).
- Semua lebar diuji tanpa overflow halaman maupun error PHP/JS. Tabel presensi tetap bisa digeser horizontal pada layar kecil.

### Pengumuman Siswa

Referensi: `EduLMS_UI/Slide 3 - Pengumuman.png`.

- Awal: Kartu terlalu tinggi dan header/filter berbeda.
- Diperbaiki: Spacing header/kartu dan preview, badge kategori, pencarian dan filter database, detail dalam dialog, pagination tetap berfungsi.
- Sisa: Penerbit, tanggal, isi preview serta jumlah total edaran belum sama dengan PNG. Ikon penerbit masih domain umum. Raster teks berbeda.
- Bukti: [aktual](visual-diff/actual/pengumuman.png), [referensi sejajar](visual-diff/actual/pengumuman-reference.png), [side-by-side](visual-diff/actual/pengumuman-side-by-side.png), [overlay](visual-diff/actual/pengumuman-overlay.png), [heatmap](visual-diff/actual/pengumuman-heatmap.png).
- Semua lebar diuji tanpa overflow halaman maupun error PHP/JS. Tabel presensi tetap bisa digeser horizontal pada layar kecil.

### Dashboard Guru

Referensi: `EduLMS_UI/Dashboard Guru (Top Navbar).png`.

- Awal: Statistik, jadwal, panel bawah dan cover generik.
- Diperbaiki: Statistik 5 kelas/178 siswa/24 berkas, presensi pekanan 96.4%, ringkasan kelas dipadatkan, jadwal terdekat tampil dari query, rail sesi, 3 jadwal dan 3 tugas serta pengumuman akademik.
- Sisa: Urutan/judul tugas, isi jadwal dan pengumuman berbeda. PNG menumpuk pengumuman menutupi sebagian tugas ketiga; aplikasi mempertahankan seluruh kontrol bisa diakses. Mode hybrid/QR tidak direkayasa karena belum tersimpan dalam model. Tinggi +28 px.
- Bukti: [aktual](visual-diff/actual/guru.png), [referensi sejajar](visual-diff/actual/guru-reference.png), [side-by-side](visual-diff/actual/guru-side-by-side.png), [overlay](visual-diff/actual/guru-overlay.png), [heatmap](visual-diff/actual/guru-heatmap.png).
- Semua lebar diuji tanpa overflow halaman maupun error PHP/JS. Tabel presensi tetap bisa digeser horizontal pada layar kecil.

### Kelas Guru

Referensi: `EduLMS_UI/Slide Guru - Kelas & Mata Pelajaran.png`.

- Awal: Foto kelas tidak sesuai dan kartu terlalu tinggi.
- Diperbaiki: Lima foto cover asli dengan pemetaan subjek/kelas stabil, lima rombel unik, tabs tingkat kelas dengan track dari nama kelas, grid dan rail warna, 53 modul/10 tugas dari query.
- Sisa: Progress memakai rasio materi terbit terhadap seluruh materi; referensi memakai progress silabus/pertemuan. Belum ada model jurnal/silabus untuk menyamakan angka itu. Aksi yang belum didukung backend tidak dipalsukan.
- Bukti: [aktual](visual-diff/actual/guru_kelas.png), [referensi sejajar](visual-diff/actual/guru_kelas-reference.png), [side-by-side](visual-diff/actual/guru_kelas-side-by-side.png), [overlay](visual-diff/actual/guru_kelas-overlay.png), [heatmap](visual-diff/actual/guru_kelas-heatmap.png).
- Semua lebar diuji tanpa overflow halaman maupun error PHP/JS. Tabel presensi tetap bisa digeser horizontal pada layar kecil.

### Penilaian Guru

Referensi: `EduLMS_UI/Slide Guru - Penilaian Tugas.png`.

- Awal: Proporsi statistik, kartu, status dan panel bawah berbeda.
- Diperbaiki: Empat status visual: ditutup mendesak, koreksi berjalan, masih terbuka, dan tuntas; rata-rata/nilai tertinggi/tanggal dinilai dari database. ZIP aman tetap berfungsi. Editor bobot panduan rubrik memvalidasi total 100 dan menyimpan preferensi lokal per guru.
- Sisa: Angka 106 dinilai/100% tepat waktu berbeda dari contoh 152/92%; tanggal, nilai tertinggi dan isi beberapa tugas berbeda. Rubrik merupakan panduan lokal dan tidak mengubah kalkulasi nilai. Tinggi +3 px; font belum identik raster.
- Bukti: [aktual](visual-diff/actual/guru_penilaian.png), [referensi sejajar](visual-diff/actual/guru_penilaian-reference.png), [side-by-side](visual-diff/actual/guru_penilaian-side-by-side.png), [overlay](visual-diff/actual/guru_penilaian-overlay.png), [heatmap](visual-diff/actual/guru_penilaian-heatmap.png).
- Semua lebar diuji tanpa overflow halaman maupun error PHP/JS. Tabel presensi tetap bisa digeser horizontal pada layar kecil.

### Presensi Guru

Referensi: `EduLMS_UI/Slide Guru - Presensi Siswa.png`.

- Awal: Komposisi tabel, jam, catatan dan rekap berbeda.
- Diperbaiki: Statistik aktual 104/107 hadir dan 96.4% pekanan, enam baris per halaman, enam portrait asli, nama/NISN fixture cocok, label jam WIB, badge surat/olimpiade, kalender native berlabel Indonesia. Editor catatan/jam memperbarui field POST; simpan memakai backend dan CSRF existing.
- Sisa: Tanggal dan rekap bulan mengikuti waktu aplikasi; jumlah sesi bulanan/kategori catatan lain berbeda dari PNG. Raster font dan beberapa ikon kecil belum identik.
- Bukti: [aktual](visual-diff/actual/guru_presensi.png), [referensi sejajar](visual-diff/actual/guru_presensi-reference.png), [side-by-side](visual-diff/actual/guru_presensi-side-by-side.png), [overlay](visual-diff/actual/guru_presensi-overlay.png), [heatmap](visual-diff/actual/guru_presensi-heatmap.png).
- Semua lebar diuji tanpa overflow halaman maupun error PHP/JS. Tabel presensi tetap bisa digeser horizontal pada layar kecil.

### Login

Referensi: `EduLMS_UI/Html → Body.png`.

- Awal: Logo, peran, proporsi panel dan jarak form berbeda.
- Diperbaiki: Logo asli, kartu desktop 694 px, panel kiri 446 px, padding form dan spacing role/remember, glyph resmi peran/identitas/keamanan/token, tautan registrasi di footer.
- Sisa: Angka portal mengikuti database. SSO/token belum terhubung ke provider dan tidak dibuat seolah berfungsi. Teks ingat identitas sesuai perilaku aplikasi, bukan klaim sesi 30 hari. Font dan detail beberapa kontrol masih berbeda.
- Bukti: [aktual](visual-diff/actual/login.png), [referensi sejajar](visual-diff/actual/login-reference.png), [side-by-side](visual-diff/actual/login-side-by-side.png), [overlay](visual-diff/actual/login-overlay.png), [heatmap](visual-diff/actual/login-heatmap.png).
- Semua lebar diuji tanpa overflow halaman maupun error PHP/JS. Tabel presensi tetap bisa digeser horizontal pada layar kecil.

## Validasi

- 61 tes HTTP lulus: autentikasi, role, CSRF, admin CRUD, penugasan, unggah/unduh, arsip ZIP, nilai, feedback, presensi dan ekspor.
- 25 tes browser lulus, termasuk editor jam/catatan presensi, validasi/persistensi bobot rubrik, filter tingkat kelas, pagination, modal, chart dan menu mobile.
- 61 file PHP lolos lint; seluruh Python docs lolos parsing AST.
- 27 capture akhir (9 halaman x 3 lebar), semuanya clientWidth sesuai viewport dan bebas error/overflow. Bukti: capture.json pada actual, desktop1440, mobile.
- 296 kemunculan ikon/simbol di [audit ikon](ICON_FIDELITY_AUDIT.md); 75 glyph resmi Material Symbols Outlined disertakan dengan manifest sumber dan Apache LICENSE.

## Reproduksi

```powershell
$env:EDULMS_DB_NAME='edulms_qa_fidelity_20261015'
php docs/seed_visual_demo.php
$env:EDULMS_DB_NAME='edulms_qa_fidelity_reference_20261015'
php docs/reference_fixture.php login
php -S 127.0.0.1:8100 router.php
# Terminal lain: Chrome headless lokal, --remote-debugging-port=9223,
# --no-sandbox, --user-data-dir di .qa-tools/chrome-refinement.
python docs/visual_interactions.py --fixture
python docs/visual_capture.py --label actual --reference-height --fixture
python docs/visual_capture.py --label desktop1440 --width 1440 --fixture
python docs/visual_capture.py --label mobile --width 390 --fixture
python docs/visual_compare.py --label actual
python docs/build_visual_report.py
```

Untuk tes HTTP jalankan server lain pada 8101 dengan EDULMS_DB_NAME=edulms_qa_regression_20261015, set EDULMS_QA_URL=http://127.0.0.1:8101 lalu python docs/test_e2e.py. Gunakan database QA terpisah.

## Pekerjaan yang tersisa

1. Samakan konten/metadata referensi dalam fixture bila sumber state tersedia; jangan hardcode hasil query produksi.
2. Pastikan keluarga/weight/font dan environment raster asli dari sumber desain; font alternatif belum terbukti lebih baik.
3. Model pertemuan silabus, mode sesi hybrid/QR dan provider SSO/token memerlukan fitur/data nyata sebelum tampilan dapat merepresentasikannya.
4. Iterasi detail wrapping, baseline dan spacing yang masih terlihat di overlay. Jangan menandai SELESAI atau 100% selama selisih penting masih ada.
5. Perubahan pengguna di working tree dipertahankan. Tidak ada commit, reset, atau deployment.
