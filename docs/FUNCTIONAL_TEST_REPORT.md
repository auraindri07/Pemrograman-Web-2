> Pembaruan: 61 tes HTTP dan 25 interaksi browser lulus pada run terakhir. Data dummy ringan kini ditambahkan ke aplikasi sesuai permintaan pengguna; akun dan data asli dipertahankan. Lihat [DATA_DUMMY.md](DATA_DUMMY.md) dan [bukti visual](UI_FIDELITY_REPORT.md).

# Laporan pengujian — 15 Oktober 2026

## Lingkungan

PHP 8.4.12, MySQL lokal, database utama `edulms_db`, database QA terpisah `edulms_qa_regression_20261015`. Server PHP lokal digunakan untuk HTTP integration test; Chrome headless/Puppeteer untuk browser. Database utama tidak tersedia saat awal inspeksi; dibuat melalui installer fresh yang menolak database yang sudah ada. Tidak ada DROP/TRUNCATE database existing yang dijalankan.

## Hasil teruji

`docs/test_e2e.py` berhasil menjalankan **61 pemeriksaan** pada database QA:

- Login admin, dua guru, siswa dan siswa uji; logout; penolakan siswa pada halaman admin.
- Ketujuh halaman admin merespons tanpa PHP error.
- Admin membuat pengguna, kelas, mapel, penempatan dan penugasan; siswa melihat mapel yang ditugaskan.
- Admin memperbarui deskripsi mapel dan wali kelas; mengubah status penugasan; reset password dan login ulang.
- Akun yang dinonaktifkan kehilangan akses pada request berikutnya; dapat diaktifkan kembali.
- Guru mengunggah materi PDF dan soal; siswa yang berhak mengunduh; siswa kelas lain ditolak.
- Guru membuat tugas; siswa melihat dan mengumpulkan PDF; guru memberi nilai dan feedback; siswa melihatnya dan statistik dashboard berubah.
- Nilai 101 ditolak; guru lain tidak dapat menilai pengumpulan milik guru yang diuji.
- Presensi disimpan dan tampil pada akun siswa; perubahan status memperbarui baris tanpa duplikasi; guru lain tidak dapat membaca roster; siswa di luar kelas ditolak.
- Pengumuman diterbitkan, dicari, difilter, disembunyikan, diterbitkan kembali dan dihapus. Pengumuman guru tidak terlihat oleh siswa. HTML berbahaya pada isi tidak dieksekusi sebagai markup.
- Ekspor nilai/presensi guru berisi data benar; ekspor guru ditolak untuk siswa.
- POST tanpa CSRF ditolak. Tanggal tidak valid, kategori tidak valid dan tahun ajaran penempatan yang tidak cocok ditangani.
- Akses langsung direktori uploads ditolak ketika memakai router pengembangan.

Data QA berlabel `QA` disimpan pada database QA agar dapat ditinjau. Database utama berisi akun/kelas/mapel awal; data transaksi uji tidak dimasukkan ke database utama. Berkas hasil tes ada pada direktori uploads dengan nama unik.

## Browser

Pemeriksaan render: login, dashboard siswa, mata pelajaran, tugas, pengumuman, riwayat, dashboard guru, kelas guru, penilaian, presensi. Render ulang setelah aset dilokalkan menunjukkan **0 JavaScript error dan 0 request aset gagal**. Menu mobile dapat dibuka; dialog pengumuman menampilkan isi lengkap dan menutup lewat Escape. Desktop 1440px dan mobile 390px pada empat halaman guru tidak mengalami overflow halaman. Lebar 390/768/1280 diuji pada empat halaman utama siswa; overflow pengumuman tablet ditemukan dan diperbaiki, kemudian diverifikasi ulang.

Dialog penilaian juga diuji: dapat dibuka dari berkas pengumpulan dan ditutup dengan Escape. Verifikasi ulang header penilaian di 390px tidak mengalami overflow. Verifikasi ulang pengumuman di 768px dan seluruh kombinasi viewport siswa yang disebutkan di atas tidak mengalami overflow.

## Pemeriksaan source

- PHP lint dijalankan untuk semua file PHP aplikasi dan helper CLI setelah implementasi.
- Koneksi database nyata berhasil; prepared statements digunakan untuk input dinamis.
- Unduhan memeriksa publikasi, penugasan aktif, kepemilikan, enrolmen dan batas direktori upload.
- File PHP tidak menggunakan kredensial rahasia baru; konfigurasi menerima environment variables.

## Batas pembuktian

- Tidak semua kombinasi CRUD, kegagalan jaringan, tab bersamaan dan format dokumen Office diuji. PDF upload/download telah dijalankan; validasi MIME OOXML dan tipe lain diperiksa pada source.
- Pengujian menggunakan server PHP dan MySQL Laragon. Konfigurasi Apache/XAMPP serta `.htaccess` perlu diperiksa di mesin tujuan; itu belum dijalankan di XAMPP terpisah.
- Keamanan akses yang diuji bukan audit keamanan menyeluruh.
- SSO/QR/token ujian/rapor luar bukan integrasi yang selesai. Tidak ada klaim seluruh spesifikasi besar pada prompt sudah 100% selesai.
