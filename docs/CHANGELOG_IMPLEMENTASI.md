# Perubahan implementasi — 15 Oktober 2026

- Membaca instruksi `prompt/prompt.md`, perancangan, panduan dan kesembilan screenshot. Mempertahankan PHP Native/PDO/MySQL serta perubahan lokal yang sudah ada.
- Menambahkan shared navbar, footer, aset portal, menu mobile, design tokens, style kartu/form/tabel, fokus keyboard dan dialog. Menyelaraskan seluruh halaman portal dengan referensi teal/lavender.
- Menyediakan Tailwind hasil build, Chart.js, font dan Font Awesome lokal beserta lisensinya. Aplikasi tidak memerlukan npm saat dijalankan.
- Menambahkan entry point root untuk mengarahkan pengguna ke login/dashboard sesuai sesi.
- Memperbaiki ID link mapel pada dashboard dan konsistensi tahun ajaran pada akses siswa/roster guru. Ketuntasan mapel dihitung per penugasan, terpisah dari persentase tugas selesai.
- Menstabilkan hitungan tab tugas saat filter berubah; tugas terlambat tetap termasuk belum selesai.
- Mengganti alert detail pengumuman dengan dialog berisi data sebenarnya; menambahkan kategori, pagination dan batas tanggal terbit.
- Menambahkan pencarian kelas guru, cover ilustrasi lokal, ringkasan koreksi per tugas, pencarian siswa/tugas, validasi nilai dan ekspor CSV.
- Menghubungkan Detail Sesi guru ke presensi; menampilkan jadwal hari ini/besok dan pengumuman terbaru untuk guru.
- Membatasi GET/POST presensi pada penugasan dan siswa yang benar; memvalidasi tanggal/status/jam, menyimpan dalam transaksi, menambahkan jam datang/catatan, filter/pagination dan rekap bulanan.
- Memperbaiki benturan `max_file_size` dengan field khusus upload PHP; batas formulir kini bernama `upload_limit_mb`.
- Menambahkan pemeriksaan identitas/tahun ajaran/role dan penanganan duplikasi/relasi pada aksi admin. Akun dinonaktifkan diperiksa lagi pada setiap request yang memakai koneksi DB.
- Memperketat MIME upload, publikasi file unduhan dan prefix direktori. Menutup akses langsung uploads serta direktori internal dengan `.htaccess` dan router pengembangan.
- Menambahkan bantuan/panduan/privasi lokal dan penjelasan jujur untuk login alternatif yang belum dikonfigurasi. Opsi ingat identitas menyimpan identifier di browser, bukan password.
- Menambahkan installer fresh tanpa reset data, konfigurasi DB lewat environment, helper build dan skrip pengujian HTTP/browser.

Database `edulms_db` belum ada pada awal sesi, sehingga dibuat beserta akun demo awal. Installer menolak database yang sudah ada. Dataset SQL lama tidak diimpor mentah karena mengandung DROP dan ID seed siswa yang tidak konsisten; installer memakai definisi schema existing serta relasi yang dibuat dari ID hasil insert.

Tidak ada commit, push, deploy atau penghapusan perubahan pengguna yang dilakukan.
