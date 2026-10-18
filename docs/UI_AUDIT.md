# UI AUDIT REPORT - EduLMS
**Tanggal:** 15 Oktober 2026  
**Status:** Discovery & Implementation Phase

## PEMETAAN SCREENSHOT KE FILE

| Screenshot | File PHP | Role | Kondisi Visual | Kondisi Fungsional | Prioritas |
|------------|----------|------|----------------|-------------------|-----------|
| `Html → Body.png` | `auth/login.php` | All | ⚠️ Perlu perbaikan emot/ikon | ✅ Berfungsi | P0 |
| `Dashboard dengan Grafik Perkembangan Belajar.png` | `dashboard/index.php` | Siswa | ⚠️ Perlu verifikasi | ✅ Berfungsi | P0 |
| `Slide 1 - Mata Pelajaran.png` | `dashboard/mata_pelajaran.php` | Siswa | ⚠️ Perlu verifikasi | ✅ Berfungsi | P1 |
| `Slide 2 - Tugas.png` | `dashboard/tugas.php` | Siswa | ⚠️ Perlu verifikasi | ✅ Berfungsi | P1 |
| `Slide 3 - Pengumuman.png` | `dashboard/pengumuman.php` | Siswa | ⚠️ Perlu verifikasi | ✅ Berfungsi | P1 |
| `Dashboard Guru (Top Navbar).png` | `dashboard/guru.php` | Guru | ⚠️ Perlu verifikasi | ✅ Berfungsi | P0 |
| `Slide Guru - Kelas & Mata Pelajaran.png` | `dashboard/guru_kelas.php` | Guru | ⚠️ Perlu verifikasi | ✅ Berfungsi | P1 |
| `Slide Guru - Penilaian Tugas.png` | `dashboard/guru_penilaian.php` | Guru | ⚠️ Perlu verifikasi | ✅ Berfungsi | P1 |
| `Slide Guru - Presensi Siswa.png` | `dashboard/guru_presensi.php` | Guru | ⚠️ Perlu verifikasi | ✅ Berfungsi | P1 |

## ANALISIS VISUAL DARI SCREENSHOT

### 1. Login Page (`Html → Body.png`)
**TEMUAN KRITIS:**
- ❌ Menggunakan emoji text biasa (👋, ⚠️) - HARUS diganti dengan FontAwesome icons
- ✅ Layout 2 kolom sudah benar
- ✅ Badge "Tahun Ajaran 2024/2025" sudah ada
- ✅ Statistik box sudah ada
- ✅ 3 role button (Siswa, Guru, Admin) sudah ada
- ⚠️ Perlu verifikasi alignment dan spacing detail

**PERBAIKAN YANG DIBUTUHKAN:**
1. Ganti emoji 👋 dengan `<i class="fa-solid fa-hand-wave"></i>` atau ikon serupa
2. Ganti emoji ⚠️ dengan `<i class="fa-solid fa-triangle-exclamation"></i>`
3. Ganti emoji lainnya dengan FontAwesome icons

### 2. Dashboard Siswa (`Dashboard dengan Grafik Perkembangan Belajar.png`)
**ELEMEN UTAMA:**
- Heading: "Halo, Budi Santoso 👋" dengan badge semester & status
- 4 Stats Card: Rata-rata Nilai, Kehadiran, Tugas Selesai, Waktu Belajar
- Grafik Perkembangan Nilai (Line Chart)
- Ketuntasan Mata Pelajaran (Circular Progress)
- Mata Pelajaran Aktif (3 cards)
- Tugas Terdekat (3 items)
- Top Navbar dengan menu

**ICON YANG HARUS DIGANTI:**
- ❌ 👋 → `<i class="fa-solid fa-hand-wave"></i>`
- Pastikan semua ikon menggunakan FontAwesome, bukan emoji

### 3. Mata Pelajaran (`Slide 1 - Mata Pelajaran.png`)
**ELEMEN UTAMA:**
- Header: "Mata Pelajaran Saya" dengan badge semester
- Search bar di kanan atas
- Grid 3 kolom kartu mata pelajaran
- Setiap kartu: Ikon, Nama Mapel, Nama Guru, Jumlah Materi & Tugas, Button "Buka Pelajaran"

**DETAIL:**
- Card memiliki ikon dengan background teal/cyan di pojok kiri atas
- Badge "Kelas XI" di pojok kanan atas
- Progress bar untuk setiap mata pelajaran
- Button hijau teal "Buka Pelajaran →"

### 4. Tugas (`Slide 2 - Tugas.png`)
**ELEMEN UTAMA:**
- Header: "Daftar Tugas" dengan indikator jumlah
- Tab filter: "Semua Tugas", "Belum Selesai", "Sudah Dikumpulkan"
- Status indicators: "4 Menunggu", "1 Selesai"
- Urutan toggle: "Urutan: Tenggat Terdekat"
- List tugas dengan badge mata pelajaran, status, deadline, button action

**DETAIL:**
- Status badge: "Mendesak" (merah), "Nilai Tersedia" (hijau)
- Icon mata pelajaran di sebelah kiri
- Batas pengumpulan dengan warna merah jika urgent
- Button "Kumpulkan Tugas" (hijau) atau "Lihat Nilai"

### 5. Pengumuman (`Slide 3 - Pengumuman.png`)
**ELEMEN UTAMA:**
- Badge "Pusat Edaran Resmi" di atas
- Header: "Pengumuman Sekolah"
- Search bar + Filter button
- List pengumuman dengan kategori badge, tanggal, judul, preview, "Lihat Detail Pengumuman →"
- Pagination di bawah

**DETAIL:**
- Badge kategori: "Penting & Akademik", "Agenda Libur", "Kegiatan Siswa"
- Icon dari/tanggal di setiap item
- Preview teks dengan ellipsis

### 6. Dashboard Guru (`Dashboard Guru (Top Navbar).png`)
**ELEMEN KUNCI:**
- Banner header hijau dengan "Selamat Bertugas, Pak Guru"
- Button "Buat Tugas Baru" + "Tambah Materi" di kanan
- 4 Stats: Total Kelas Diajar, Total Siswa Aktif, Tugas Perlu Dinilai (dengan badge "Mendesak"), Jadwal Mengajar Hari Ini
- 2 section besar: "Jadwal Mengajar Hari Ini & Besok" + "Perlu Dinilai Segera"
- Timeline jadwal dengan jam, mata pelajaran, ruang, jumlah siswa
- List tugas dengan progress bar pengumpulan
- Info box di bawah tentang sinkronisasi presensi
- Banner pengumuman "Batas Pengisian Nilai Tengah Semester (PTS)"

### 7. Kelas & Mata Pelajaran Guru (`Slide Guru - Kelas & Mata Pelajaran.png`)
**ELEMEN UTAMA:**
- Badge "MANAJEMEN ROMBEL"
- Header: "Kelas & Mata Pelajaran Diampu"
- Search bar + button "Tambah Kelas/Modul Baru"
- Tab: "Semua Kelas", "Kelas XI MIPA", "Kelas X MIPA"
- Indikator: "53 Modul Siap", "10 Tugas Aktif", "Rerata Progres 64%"
- Grid kartu kelas dengan foto header, badge kelas, nama mapel, wali kelas, progress silabus, jumlah modul & tugas, button "Buka Silabus" + "Siswa"
- Card "Buka Rombel Baru" untuk menambah
- Summary beban mengajar di bawah dengan circular progress 64%

### 8. Penilaian Tugas (`Slide Guru - Penilaian Tugas.png`)
**ELEMEN UTAMA:**
- Badge "EVALUASI AKTIF"
- Header: "Penilaian Tugas & Evaluasi Siswa"
- 3 stats: "24 Perlu Dinilai", "152 Sudah Dinilai", "92% Tepat Waktu"
- Tab filter: "Semua Tugas Menunggu", "Mendesak H-1", "Sudah Selesai Dinilai"
- Button "Ekspor Lembar Rekap"
- Search + Filter dropdown
- List tugas dengan status, progress bar, tenggat, jumlah terkumpul, button action
- Sidebar "Panduan Rubrik Penilaian Cepat" dengan breakdown kriteria
- Info box "Sinkronisasi Rapor Instan"

### 9. Presensi Siswa (`Slide Guru - Presensi Siswa.png`)
**ELEMEN UTAMA:**
- Header: "Presensi & Kehadiran Siswa"
- Dropdown "Senin, 10 Februari 2025" + button "Buka Presensi Sesi Baru"
- 4 stats: "96.4% Rata-rata Pekan Ini", "104/107 Siswa Hadir Hari Ini", "3 Siswa Izin & Sakit", "0 Siswa Tanpa Keterangan (ALFA)"
- Card session aktif: "Matematika Wajib - XI MIPA 1" dengan jam, ruang, total siswa, button "Tandai Semua Hadir" + "Simpan Presensi"
- Search siswa
- Filter status tabs
- Tabel dengan foto siswa, nama, NISN, status presensi (Hadir/Sakit/Izin/Alfa), jam kedatangan, keterangan, aksi cepat
- Pagination
- Rekap Presensi & Kehadiran Bulanan per kelas

## PERBAIKAN PRIORITAS

### P0 - CRITICAL (Harus dikerjakan segera)
1. **Ganti SEMUA emoji dengan FontAwesome icons** di seluruh halaman
2. Verifikasi Login Page alignment dan spacing
3. Verifikasi Dashboard Siswa layout dan grafik
4. Verifikasi Dashboard Guru layout dan components

### P1 - HIGH (Implementasi detail)
1. Mata Pelajaran page - card design dan grid
2. Tugas page - filter dan list layout
3. Pengumuman page - badge dan preview
4. Guru Kelas page - card dengan foto header
5. Guru Penilaian page - progress bars dan rubrik
6. Guru Presensi page - tabel dan status badges

### P2 - MEDIUM (Refinement)
1. Hover states
2. Transition animations
3. Responsive adjustments
4. Loading states
5. Empty states

### P3 - LOW (Polish)
1. Micro animations
2. Shadow refinements
3. Typography kerning
4. Icon sizing consistency

## LANGKAH IMPLEMENTASI

1. ✅ Baca semua screenshot (SELESAI)
2. 🔄 Perbaiki emoji menjadi FontAwesome icons (SEDANG DIKERJAKAN)
3. ⏳ Verifikasi pixel-perfect setiap halaman
4. ⏳ Test fungsionalitas
5. ⏳ Documentation

## CATATAN PENTING
- User request: **JANGAN gunakan emoji biasa, harus FontAwesome icons**
- Semua 👋, ⚠️, ✅, dan emoji lain HARUS diganti
- Tetap pertahankan PHP Native + MySQL
- Jangan merusak fitur yang sudah berfungsi
