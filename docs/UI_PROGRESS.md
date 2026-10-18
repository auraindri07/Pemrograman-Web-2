> Pembaruan 16 Oktober 2026: perapian terbaru, 18 capture desktop/mobile dan 11 pemeriksaan browser tercatat di [UI_REFINEMENT_20261016.md](UI_REFINEMENT_20261016.md). Angka dan status audit di bawah merupakan checkpoint sebelumnya.

# UI progress - 15 Oktober 2026

**Target 100% belum tercapai.** Klaim lama 91% tidak digunakan sebagai bukti. Seluruh halaman masih BELUM SESUAI menurut definisi strict.

Diperbarui: glyph Material Symbols resmi, snapshot QA per halaman, badge/mapel/tugas, tabel presensi dengan editor, rubrik editable sebagai panduan lokal, spacing login, chart dari database, rail dan statistik guru.

| Halaman | Tinggi ref / aktual | Sisa |
|---|---:|---|
| Dashboard Siswa | 1322 / 1322 | Waktu belajar mengikuti log nyata (fixture belum mereplikasi 12.5 jam). Beberapa isi tugas dan persen progress berbeda. Rasterisasi tulisan dan emoji salam berbeda dari PNG. |
| Mata Pelajaran Siswa | 891 / 891 | Teks, ketebalan raster font, baseline beberapa label, dan ikon metadata kecil masih memiliki selisih piksel. |
| Tugas Siswa | 1275 / 1276 | Isi metadata/tanggal mengikuti waktu aplikasi. Tinggi tersisa +1 px dan raster font belum sama. |
| Pengumuman Siswa | 840 / 840 | Penerbit, tanggal, isi preview serta jumlah total edaran belum sama dengan PNG. Ikon penerbit masih domain umum. Raster teks berbeda. |
| Dashboard Guru | 1534 / 1562 | Urutan/judul tugas, isi jadwal dan pengumuman berbeda. PNG menumpuk pengumuman menutupi sebagian tugas ketiga; aplikasi mempertahankan seluruh kontrol bisa diakses. Mode hybrid/QR tidak direkayasa karena belum tersimpan dalam model. Tinggi +28 px. |
| Kelas Guru | 1626 / 1626 | Progress memakai rasio materi terbit terhadap seluruh materi; referensi memakai progress silabus/pertemuan. Belum ada model jurnal/silabus untuk menyamakan angka itu. Aksi yang belum didukung backend tidak dipalsukan. |
| Penilaian Guru | 1441 / 1444 | Angka 106 dinilai/100% tepat waktu berbeda dari contoh 152/92%; tanggal, nilai tertinggi dan isi beberapa tugas berbeda. Rubrik merupakan panduan lokal dan tidak mengubah kalkulasi nilai. Tinggi +3 px; font belum identik raster. |
| Presensi Guru | 1518 / 1518 | Tanggal dan rekap bulan mengikuti waktu aplikasi; jumlah sesi bulanan/kategori catatan lain berbeda dari PNG. Raster font dan beberapa ikon kecil belum identik. |
| Login | 1025 / 1025 | Angka portal mengikuti database. SSO/token belum terhubung ke provider dan tidak dibuat seolah berfungsi. Teks ingat identitas sesuai perilaku aplikasi, bukan klaim sesi 30 hari. Font dan detail beberapa kontrol masih berbeda. |

61 tes HTTP, 25 tes browser, 61 lint PHP dan 27 capture akhir lulus. Data produksi tidak diisi fixture.

[Laporan dan batas pengukuran](UI_FIDELITY_REPORT.md) - [audit ikon](ICON_FIDELITY_AUDIT.md) - [galeri visual](visual-diff/index.html).
