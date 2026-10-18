# Cetak dan Simpan PDF

Laporan cetak menggunakan halaman khusus A4. Navigasi, formulir input, tombol, dan pagination tidak ikut masuk ke PDF.

## Cara menggunakan

Login sebagai **Guru**, lalu buka salah satu menu berikut:

| Menu | Tombol | Isi laporan |
|---|---|---|
| Kelas & Mata Pelajaran | Ikon cetak pada ringkasan mengajar | Seluruh penugasan aktif, jadwal, ruang, jumlah siswa, materi terbit, dan tugas terbit. |
| Presensi Siswa | Cetak / PDF pada Rekap Presensi & Kehadiran Bulanan | Presensi tersimpan pada bulan tanggal yang dipilih, dikelompokkan per kelas dan mata pelajaran. |
| Penilaian Tugas | Cetak / PDF di samping ekspor rekap | Pengumpulan tugas, nilai, dan umpan balik. Mengikuti pilihan kelas dan tugas; pencarian dan tab status tidak membatasi laporan. |

1. Buka laporan melalui tombol tersebut.
2. Periksa judul, nama pengajar, tahun ajaran, dan periode pada pratinjau.
3. Klik **Cetak / Simpan PDF**.
4. Pada dialog cetak browser, pilih printer atau tujuan **Simpan sebagai PDF**.
5. Gunakan kertas **A4**, orientasi **Portrait**, dan skala **100%** atau **Default**. Nonaktifkan header/footer bawaan browser agar URL dan judul browser tidak ikut tercetak. Laporan sudah memiliki footer dan nomor halaman sendiri pada browser yang mendukung margin halaman CSS.

## Perilaku laporan

- Semua baris dalam cakupan laporan dimuat, termasuk yang tidak tampil karena pagination pada dashboard.
- Kepala tabel dan identitas kelompok diulang ketika tabel berlanjut ke halaman berikutnya.
- Nama, NISN, dan umpan balik panjang dibungkus dalam kolomnya.
- Presensi mencetak data yang sudah disimpan. Perubahan pada formulir presensi perlu disimpan terlebih dahulu.
- Siswa tanpa catatan presensi pada bulan terkait tidak ditampilkan dalam rekap bulanan.
- Pengumpulan tanpa nilai ditandai **Belum dinilai** dan tidak masuk perhitungan rata-rata.
- Data kosong memiliki penjelasan, bukan tabel kosong tanpa konteks.
- Guru hanya dapat mengakses data laporannya sendiri. Akun siswa tidak dapat membuka laporan pengajar.

Cetak langsung melalui perintah Print browser pada halaman portal juga memakai stylesheet cetak bersama untuk menyembunyikan navigasi, kontrol, dan pagination. Untuk rekap lengkap, gunakan tombol laporan khusus di atas.

## Berkas implementasi

- `dashboard/cetak.php`: data dan template laporan pengajar, tanpa pagination.
- `assets/css/report-print.css`: tampilan pratinjau, ukuran A4, tabel, dan pemisahan halaman laporan.
- `assets/css/portal-print.css`: aturan cetak umum halaman portal.

## Verifikasi

Jalankan server lokal dan Chrome CDP yang digunakan oleh alat verifikasi visual, lalu:

```powershell
python docs/print_report_checks.py --base-url http://127.0.0.1:8102 --label print-review
```

Sesuaikan alamat dengan server lokal yang digunakan. Skrip menggunakan akun demo yang sudah tersedia dan tidak menulis data pembelajaran ke database. Uji 90 baris dengan umpan balik panjang dilakukan hanya pada DOM browser.

Hasil pemeriksaan dan contoh PDF disimpan di `docs/visual-diff/print-review/`, termasuk `kelas.pdf`, `presensi.pdf`, `nilai.pdf`, `presensi-empty.pdf`, dan `nilai-multipage-stress.pdf`.
