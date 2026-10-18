# MASTER PROMPT — EDULMS EXACT UI REPLICATION
## STRICT 100% VISUAL FIDELITY — NO PREMATURE COMPLETION

Kamu berperan sebagai Principal Frontend Engineer, Visual Regression Engineer, UI Reverse Engineer, dan PHP Native Developer.

Kamu sedang MELANJUTKAN proyek EduLMS yang sudah dikerjakan oleh Codex dan Claude Sonnet sebelumnya.

**MASALAH: HASIL UI SAAT INI MASIH BERBEDA DARI REFERENSI ASLI.**

Saya tidak menerima hasil yang hanya "cukup mirip", "90% sesuai", atau "sudah bagus".

**TARGET: 100% VISUAL FIDELITY terhadap seluruh screenshot dalam `EduLMS_UI`, termasuk layout, warna, font, ikon, emoji, simbol, gambar, ukuran, posisi, spacing, dan detail kecil.**

Angka 100% adalah target pengerjaan, BUKAN angka keberhasilan yang boleh kamu klaim tanpa pembuktian.

---

## 1. JANGAN PERCAYA LAPORAN PROGRES SEBELUMNYA

Baca file:

- `docs/UI_PROGRESS.md`
- `docs/UI_FIDELITY_REPORT.md`
- `docs/EMOJI_REPLACEMENT_LOG.md`

Dokumentasi sebelumnya mengklaim Overall Visual Fidelity sebesar 91%.

**ANGGAP KLAIM TERSEBUT BELUM TERBUKTI.**

Lakukan audit visual ulang secara independen.

Jangan menggunakan status "Completed" sebelumnya sebagai alasan tidak memperbaiki suatu halaman.

Saya sudah membandingkan screenshot dan menemukan masalah:

1. Halaman Mata Pelajaran hanya menampilkan sedikit kartu dibanding enam kartu pada referensi.
2. Halaman Tugas terlihat terlalu kosong dibanding referensi.
3. Dashboard Guru memiliki perbedaan pada panel-panel bagian bawah.
4. Cover gambar kelas Guru masih berbeda.
5. Penilaian Guru belum mengikuti proporsi referensi.
6. Presensi Guru belum sama komposisi visualnya.
7. Ikon dan emoji tidak konsisten dengan screenshot asli.
8. Kepadatan halaman, spacing, dan tinggi komponen masih berbeda.

**TUGASMU ADALAH MEMPERBAIKI TEMUAN TERSEBUT, BUKAN MEMBELA HASIL SEBELUMNYA.**

---

## 2. SUMBER KEBENARAN VISUAL

Gunakan urutan prioritas:

1. Seluruh PNG dalam `EduLMS_UI/`.
2. Aset gambar asli yang tersedia di `assets/images/reference/`.
3. Figma jika dapat diakses.
4. File PHP/CSS existing hanya sebagai dasar implementasi.

Referensi Figma:

https://www.figma.com/design/xPze1apaBQ4d6XjGRNPAlp/EduLMS?node-id=0-1

Jangan menjadikan screenshot hasil implementasi lama sebagai acuan desain.

Jangan menjadikan HTML existing sebagai sumber kebenaran visual.

WAJIB membuka PNG menggunakan tool yang benar-benar dapat melihat gambar.

Jika tidak bisa melihat PNG, jangan mengarang hasil inspeksi.

---

## 3. ATURAN ICON, SIMBOL, DAN EMOJI — SANGAT PENTING

Saya ingin setiap ikon, simbol, dan emoji SEMIRIP MUNGKIN dengan gambar referensi.

**DILARANG mengganti ikon berdasarkan kreativitas atau preferensi pribadi.**

Untuk SETIAP ikon pada screenshot:

1. Identifikasi bentuk visual aslinya.
2. Tentukan apakah itu emoji, SVG, icon font, atau gambar.
3. Cari aset asli yang sudah tersedia.
4. Jika tersedia, gunakan aset asli.
5. Jika berbentuk emoji, gunakan karakter dan rendering yang cocok dengan referensi.
6. Jika berbentuk ikon outline, gunakan SVG atau icon library dengan bentuk dan stroke yang cocok.
7. Jika berbentuk ikon solid, gunakan bentuk solid yang sesuai.
8. Jika tidak tersedia, buat SVG yang mereplikasi bentuk referensi sedekat mungkin.

Jangan menggunakan Font Awesome untuk semua ikon secara otomatis.

Jangan menggunakan emoji sebagai pengganti seluruh ikon.

Jangan mengganti emoji yang memang ada di referensi menjadi ikon generik.

Jangan mengganti ikon yang ada di referensi menjadi emoji asal-asalan.

### DETAIL YANG HARUS SAMA

- Bentuk ikon.
- Gaya outline atau filled.
- Ketebalan stroke.
- Warna.
- Background ikon.
- Ukuran.
- Posisi.
- Padding.
- Border radius.
- Jarak ikon dengan teks.
- Alignment vertikal.

Jika ikon berada di dalam kotak berwarna, kotak tersebut juga harus sesuai.

Perhatikan bahwa emoji dapat tampil berbeda di Windows, Linux, dan browser. Jangan memaksakan emoji Unicode jika bentuknya tidak cocok dengan referensi.

Jika diperlukan, gunakan SVG lokal dengan ukuran dan bentuk yang sesuai.

### BUAT ICON INVENTORY

Buat:

`docs/ICON_FIDELITY_AUDIT.md`

Catat:

| Halaman | Elemen | Bentuk pada Referensi | Implementasi Saat Ini | Tindakan |
|---|---|---|---|---|

Lakukan untuk semua ikon pada sembilan screenshot, termasuk ikon kecil.

---

## 4. WAJIB MENGGUNAKAN ASET VISUAL YANG SUDAH ADA

Periksa folder:

`assets/images/reference/`

Terdapat aset referensi untuk avatar, logo, dan gambar kelas.

Jangan menggunakan placeholder SVG generik jika aset asli sudah tersedia.

Khusus halaman Kelas Guru:

- Cocokkan cover setiap kartu dengan screenshot.
- Gunakan gambar referensi yang sesuai.
- Cocokkan cropping.
- Cocokkan object-fit dan object-position.
- Cocokkan rasio gambar.
- Jangan gunakan satu gambar yang sama untuk semua kartu apabila screenshot menggunakan gambar berbeda.

Periksa juga `docs/extract_reference_assets.ps1` untuk memahami proses penyediaan aset.

Jangan mengekstrak bagian screenshot menjadi aset untuk menggantikan satu halaman utuh. Komponen UI tetap harus interaktif.

---

## 5. PERBAIKAN PRIORITAS PER HALAMAN

### A. Dashboard Siswa

Referensi:
`Dashboard dengan Grafik Perkembangan Belajar.png`

Periksa dan samakan:
- Navbar.
- Welcome banner.
- Badge.
- Tombol dan ikon.
- Empat statistik utama.
- Grafik perkembangan nilai.
- Donut chart.
- Kartu mata pelajaran aktif.
- Tugas terdekat.
- Padding dan proporsi.

Jangan hanya menyamakan warna badge.

Pastikan susunan dan dimensi keseluruhan dashboard sesuai.

### B. Mata Pelajaran Siswa

Referensi:
`Slide 1 - Mata Pelajaran.png`

Referensi menggunakan grid enam kartu.

Implementasi aktual hanya memperlihatkan satu kartu.

Investigasi apakah penyebabnya:
- Data database.
- Filter query.
- Penempatan siswa.
- Relasi kelas dan mata pelajaran.
- Data demo yang tidak lengkap.
- Kesalahan rendering.

Jangan langsung hardcode enam mata pelajaran.

Jika data demo kurang, buat SEED DATA DEMO TERPISAH yang aman dan idempotent. Minta izin sebelum mengubah data existing yang berisiko.

Untuk pengujian visual, gunakan dataset representatif sesuai jumlah dan jenis komponen referensi.

Samakan:
- Grid tiga kolom pada desktop.
- Tinggi kartu.
- Ikon dan background ikon.
- Badge kelas.
- Nama mata pelajaran.
- Informasi pengajar.
- Progress bar.
- Tombol.
- Spacing.

### C. Daftar Tugas Siswa

Referensi:
`Slide 2 - Tugas.png`

Perbaiki:
- Kepadatan daftar.
- Tabs.
- Filter status.
- Border setiap tugas.
- Warna status.
- Ikon tugas.
- Badge tenggat.
- Tombol pengumpulan.
- Jarak setiap row.
- Panjang dan posisi informasi.

Jika tugas demo kurang, siapkan fixture khusus testing yang konsisten dengan referensi tanpa mengganti data produksi.

### D. Pengumuman Siswa

Referensi:
`Slide 3 - Pengumuman.png`

Samakan:
- Header.
- Search.
- Filter.
- Status kategori.
- Layout setiap pengumuman.
- Ikon.
- Tinggi kartu.
- Tombol baca.
- Pagination.
- Whitespace.

### E. Dashboard Guru

Referensi:
`Dashboard Guru (Top Navbar).png`

Perhatikan:
- Top navbar.
- Welcome banner.
- Statistik.
- Jadwal mengajar.
- Panel penilaian.
- Daftar tugas.
- Pengumuman.
- Quick actions.

Jangan menghilangkan atau menyederhanakan komponen yang ada dalam referensi.

### F. Kelas dan Mata Pelajaran Guru

Referensi:
`Slide Guru - Kelas & Mata Pelajaran.png`

Prioritas:
- Cover gambar kelas.
- Proporsi kartu.
- Jumlah kolom.
- Informasi kelas.
- Progress bar.
- Ikon.
- Tombol.
- Spacing.
- Panel tambahan bagian bawah.

Gunakan aset lokal yang sudah tersedia.

### G. Penilaian Tugas Guru

Referensi:
`Slide Guru - Penilaian Tugas.png`

Samakan:
- Header.
- Statistik.
- Filter tabs.
- Search.
- Daftar tugas.
- Border status.
- Progress indikator.
- Warna.
- Tombol.
- Panel informasi bawah.

### H. Presensi Guru

Referensi:
`Slide Guru - Presensi Siswa.png`

Samakan:
- Statistik kehadiran.
- Filter kelas dan tanggal.
- Toolbar.
- Tabel siswa.
- Badge presensi.
- Tombol aksi.
- Pagination.
- Rekap bulanan.
- Tinggi dan proporsi setiap bagian.

### I. Login

Referensi:
`Html → Body.png`

Pertahankan komponen yang sudah mirip, tetapi periksa ulang:
- Rasio dua panel.
- Warna.
- Ukuran card.
- Typography.
- Form.
- Logo.
- Ikon.
- Spacing.

---

## 6. RENDER DAN COMPARE — WAJIB

Proyek telah memiliki:

- `docs/browser_smoke.cjs`
- `docs/test_e2e.py`
- `docs/screenshots/`
- `.qa-tools/`

Periksa dan manfaatkan tools tersebut sebelum menambahkan dependency baru.

Untuk setiap halaman:

1. Jalankan PHP dan database.
2. Login menggunakan akun testing yang sesuai.
3. Render halaman.
4. Capture screenshot.
5. Gunakan viewport 1296px atau ukuran referensi yang sesuai.
6. Samakan device scale factor.
7. Samakan state halaman, data demo, font, dan scroll position.
8. Bandingkan dengan PNG asli.
9. Buat overlay dan difference image.
10. Perbaiki perbedaan yang ditemukan.
11. Capture ulang.
12. Ulangi.

Lakukan juga pemeriksaan pada viewport 1440px dan mobile setelah kesesuaian viewport referensi tercapai.

Jangan membandingkan screenshot dengan ukuran, zoom, atau panjang halaman berbeda tanpa melakukan alignment terlebih dahulu.

---

## 7. AUTOMATED VISUAL REGRESSION

Jika tersedia Python Pillow, OpenCV, atau Playwright:

Bangun visual comparison yang menghasilkan:

- Reference screenshot.
- Actual screenshot.
- Side-by-side.
- Overlay 50%.
- Difference heatmap.
- Structural similarity bila memungkinkan.
- Daftar perbedaan terbesar.

Simpan ke:

`docs/visual-diff/`

Gunakan hasil perbandingan untuk memperbaiki layout secara objektif.

Tetapkan target kecocokan setinggi mungkin.

Jangan mengklaim 100% jika masih ada perbedaan terukur yang signifikan.

Jika referensi berisi data contoh berbeda, pisahkan perbedaan konten dinamis dan perbedaan desain.

---

## 8. CONTINUOUS REFINEMENT — JANGAN BERHENTI SEBELUM SESUAI

Jalankan siklus:

REFERENCE → RENDER → COMPARE → IDENTIFY → FIX → RENDER AGAIN.

Tidak boleh berhenti hanya karena:
- Sudah tiga iterasi.
- Sudah memperbaiki CSS.
- Sudah mengubah ikon.
- Layout terasa cukup bagus.
- Halaman berhasil dimuat.
- Ada laporan lama yang menyebut 91%.
- Semua tombol sudah berfungsi.

Lanjutkan perbaikan selama perbedaan visual yang relevan masih dapat diperbaiki.

Jangan melakukan loop tanpa perubahan nyata. Setiap iterasi wajib mempunyai temuan visual, tindakan koreksi, dan verifikasi hasil.

Jika suatu perbedaan tidak bisa diperbaiki karena aset tidak ada, data tidak tersedia, atau tool tidak mendukung, catat hambatannya secara spesifik.

Jika konteks atau kuota habis, tulis checkpoint dan instruksi melanjutkan pekerjaan.

---

## 9. PERTAHANKAN SISTEM EXISTING

Jangan merusak:
- PHP Native.
- PDO.
- MySQL.
- Authentication.
- Session.
- Role authorization.
- CRUD.
- Upload/download.
- Chart dinamis.
- Presensi.
- Penilaian.
- Pengumuman.
- Alur bisnis.

Jangan membuat halaman statis untuk mengejar screenshot.

Jangan mengubah data asli tanpa izin.

Jika diperlukan dataset pengujian, gunakan database testing atau seed yang terpisah.

---

## 10. DEFINISI SELESAI

Suatu halaman boleh diberi status SELESAI hanya ketika:

1. Screenshot asli sudah dibaca.
2. Screenshot aktual berhasil diambil.
3. Kedua screenshot sudah dibandingkan.
4. Layout utama sesuai.
5. Komponen, ukuran, dan spacing sudah sesuai.
6. Warna dan font sudah sesuai.
7. Ikon, simbol, dan emoji sudah disesuaikan satu per satu.
8. Gambar atau cover sudah sesuai.
9. Tidak ada perbedaan penting yang masih bisa diperbaiki.
10. Fungsi existing tidak mengalami regression.

Jika masih ada perbedaan besar, status harus **BELUM SESUAI**.

Jangan mencantumkan persentase keberhasilan yang tidak berasal dari metode pengukuran terverifikasi.

---

## 11. OUTPUT WAJIB

Perbarui:

- `docs/UI_FIDELITY_REPORT.md`
- `docs/UI_PROGRESS.md`
- `docs/ICON_FIDELITY_AUDIT.md`

Untuk setiap halaman, laporkan:
- Referensi PNG.
- Screenshot aktual terakhir.
- Perbedaan awal.
- Perbaikan yang dilakukan.
- Perbedaan tersisa.
- Hasil visual comparison.
- Status fungsional.

Jika masih ada pekerjaan yang dapat dilakukan, lanjutkan otomatis tanpa menunggu perintah baru.

---

# FINAL EXECUTION COMMAND

MULAI SEKARANG.

Buka ulang SEMBILAN screenshot dalam `EduLMS_UI`.

Abaikan klaim 91% dari laporan sebelumnya.

Audit ulang aplikasi saat ini secara visual.

Prioritaskan perbedaan terbesar pada Mata Pelajaran, Tugas, Dashboard Guru, Cover Kelas, Penilaian, Presensi, serta seluruh ikon dan emoji.

Gunakan aset referensi yang sudah tersedia.

Lakukan visual comparison menggunakan screenshot aktual aplikasi.

Perbaiki, capture ulang, bandingkan ulang, dan ulangi sampai seluruh perbedaan visual yang bisa diperbaiki terselesaikan.

JANGAN REDESIGN.

JANGAN MEMBUAT ULANG PROYEK.

JANGAN BERHENTI PADA HASIL YANG HANYA 90% MIRIP.

**TUJUAN: REPRODUKSI VISUAL EDULMS SED EKAT MUNGKIN DENGAN REFERENSI ASLI, TERMASUK SELURUH IKON, EMOJI, SIMBOL, DAN DETAIL UI, TANPA MENGORBANKAN FUNGSIONALITAS.**