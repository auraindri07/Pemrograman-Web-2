# Data dummy ringan

Database aplikasi `edulms_db` sudah terisi 10 murid dummy baru. Tiga akun murid sebelumnya dan enam akun guru tetap dipertahankan, termasuk password dan profilnya.

- 10 murid dummy, dibagi dua murid per kelas pada 5 kelas.
- 6 guru yang sudah tersedia mengampu 6 mata pelajaran.
- 260 materi (170 terbit, 90 draft), 90 tugas, 168 pengumpulan dengan nilai dan status menunggu koreksi.
- 936 presensi selama 12 hari belajar, dengan status hadir, sakit, izin, dan alfa.
- 12 pengumuman untuk siswa, guru, atau semua pengguna.
- Nilai historis pada lima bulan: grafik dashboard dihitung dari data tersebut, bukan angka hardcode pada tampilan.
- PDF contoh tersedia untuk materi, soal, dan jawaban; unduhan tetap melewati autentikasi aplikasi.

## Akun

| Akun | Email | Password |
|---|---|---|
| Murid dummy kelas XI MIPA 1 | `demo.kelas1.siswa01@edulms.test` | `DemoEduLMS!2026` |
| Murid dummy kedua | `demo.kelas1.siswa02@edulms.test` | `DemoEduLMS!2026` |
| Guru demo yang sudah tersedia | `ahmad.dahlan@edulms.sch.id` | `password` pada instalasi demo awal; seeder tidak meresetnya |
| Siswa demo yang sudah tersedia | `budi.demo@student.edulms.sch.id` | `password` pada instalasi demo awal; seeder tidak meresetnya |

Untuk kelas lain, ganti `kelas1` menjadi `kelas2` sampai `kelas5`, masing-masing `siswa01` dan `siswa02`.

## Menjalankan ulang

```powershell
$env:EDULMS_DB_NAME='edulms_db'
php docs/seed_demo.php --apply
```

Seeder hanya dapat berjalan melalui CLI. Pengulangan tanpa perubahan data tidak menambahkan duplikat. Snapshot data asli dan daftar ID yang dibuat disimpan lokal dalam `database/backups/`, yang diblokir dari HTTP dan diabaikan Git. Seed besar sebelumnya diganti hanya berdasarkan ID pada jurnal milik seeder; akun asli tidak dihapus.

Preview lokal: http://127.0.0.1:8102/auth/login.php. Aplikasi Laragon yang memakai database yang sama juga langsung membaca data ini.

Data ini untuk meninjau aplikasi dengan isi nyata. Perbedaan jumlah murid, periode, dan transaksi terhadap PNG referensi tetap memengaruhi screenshot; kesamaan tinggi halaman saja tidak membuktikan UI 100%.
