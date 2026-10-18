# Fungsi tombol setelah login - 16 Oktober 2026

Bagian login, Google/Belajar.id, lupa sandi, dan token ujian ditunda sesuai arahan pengguna. Perubahan ini berfokus pada tombol di dalam portal.

## Pengajuan rombel

- Guru: Kelas & Mata Pelajaran > **Ajukan Rombel Baru**.
- Form meminta penugasan mata pelajaran milik guru, nama kelas, tingkat, jurusan, tahun ajaran, dan alasan.
- Guru dapat melihat status pengajuannya sendiri dan tanggapan admin.
- Admin: Dashboard > **Pengajuan Rombel**. Admin dapat menyetujui atau menolak dengan alasan.
- Persetujuan membuat kelas dan penugasan guru secara transaksional. Jika kelas yang cocok sudah ada, kelas tersebut digunakan. Persetujuan ulang tidak membuat duplikat.
- Penempatan siswa, wali kelas, serta jadwal tetap dikelola melalui menu admin terkait; pengajuan tidak memindahkan siswa atau menyalin silabus secara otomatis.

## Tiket bantuan

- Siswa/guru: Bantuan & Dukungan > **Kirim & Lihat Tiket**.
- Tiket tersimpan di database. Pengguna hanya melihat tiketnya sendiri.
- Admin: Dashboard > **Tiket Bantuan**, lalu isi tanggapan dan pilih Terbuka atau Selesai.
- Pemilik tiket dapat membaca tanggapan dari halaman tiket. Lima tiket per pengguna per 10 menit menjadi batas pengiriman.
- Notifikasi dalam aplikasi berupa antrean dan jumlah tiket pada dashboard admin; tidak mengirim email.

## Database dan instalasi

Migration aditif `database/portal_actions.sql` menambahkan `pengajuan_rombel` dan `tiket_bantuan`. Migration sudah dijalankan pada database aplikasi dan database pengujian, tanpa mengubah akun maupun transaksi akademik sebelumnya.

```powershell
php docs/migrate_portal_actions.php
```

Migration dapat dijalankan ulang. Installer fresh juga membuat kedua tabel ini. Jika mengimpor SQL utama secara manual, jalankan migration setelah impor.

## Bukti pengujian

- 30 pemeriksaan HTTP khusus alur baru: peran/otorisasi, CSRF, input tidak valid, kepemilikan, validasi tahun, duplikasi, persetujuan, penolakan, privasi tiket, dan escaping HTML. Hasil: `docs/portal-action-results.json`.
- 61 tes integrasi aplikasi sebelumnya tetap lulus. Hasil: `docs/test-results.json`.
- 10 pemeriksaan browser dan 16 screenshot desktop 1280/mobile 390 tanpa overflow atau error PHP/JavaScript: `docs/visual-diff/portal-actions/`.
- 67 file PHP lolos lint. Login Google dan pengiriman email tidak termasuk pengujian ini.

Pengujian transaksi menggunakan database QA terpisah. Tidak ada tiket atau pengajuan uji yang ditambahkan ke database aplikasi.
