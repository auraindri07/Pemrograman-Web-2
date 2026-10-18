# Perapian UI - 16 Oktober 2026

Target terbaru pengguna: mendekati acuan. Tidak ada klaim identik 100% atau skor fidelity baru.

## Diperbarui

- Ketebalan judul mapel, kartu kelas, jadwal, dan penilaian diselaraskan ke bobot 700. Heading kelas desktop kembali 36 px dengan jarak huruf yang lebih dekat acuan.
- Unit statistik guru diperbesar; tombol koreksi diposisikan sejajar dengan label status agar lebih dekat komposisi acuan.
- Angka kartu kelas, warna ikon tugas, lebar tombol Siswa/Buka Silabus, dan badge progress diperbaiki. Persen progress tampil sebagai bilangan bulat; data sumber tetap nyata.
- Opsi kartu kelas memakai SVG tiga titik buatan lokal, bukan karakter teks. Ikon Material Symbols lain tetap lokal.
- Label teknis EDULMS-DEMO-V1 disaring saat menampilkan deskripsi. Record database tidak diubah.
- Background halaman kelas mendapat cahaya mint halus. Navbar admin teks sejajar brand, panel kiri login penuh, tahun 2026/2027, dan seluruh alur baru tetap dipertahankan.

## Verifikasi

- Sembilan halaman desktop 1280 dan sembilan halaman mobile 390 dirender tanpa overflow halaman maupun error PHP/JavaScript.
- Sebelas tes browser pada aplikasi dengan dummy data lulus: grafik lima bulan, enam mapel, pagination tugas/pengumuman/penilaian, serta PDF terotentikasi.
- 67 file PHP lolos lint; view guru yang diubah terakhir juga diperiksa ulang.
- Tidak ada seed, migrasi, atau perubahan transaksi akademik pada iterasi UI ini.

## Bukti

[Galeri acuan / sebelum / sesudah](visual-diff/ui-near-review.html). Capture dan metadata lengkap: `visual-diff/ui-near-before/`, `ui-near-after/`, `ui-near-mobile/`.

## Batas

Font pada Chrome Windows ini masih memiliki rasterisasi berbeda dari PNG sumber. Isi jadwal, rombel, nilai, tanggal, dan progress berbeda karena memakai data aplikasi. Dimensi halaman sama atau lebih dekat tidak membuktikan identik piksel. Snapshot dan metrik lama dalam UI_FIDELITY_REPORT.md tetap merupakan riwayat, bukan hasil pengukuran baru.
