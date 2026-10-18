# MASTER PROMPT — EDULMS
## REPLIKASI UI FIGMA & SCREENSHOT + PENYELESAIAN APLIKASI FULL FUNCTIONAL

### 1. PERAN DAN TUJUAN

Bertindak sebagai:
- Senior UI/UX Engineer
- Pixel-Perfect Frontend Developer
- Senior PHP Native Full-Stack Developer
- MySQL Database Engineer
- Software QA & End-to-End Tester

Saya memiliki proyek **EduLMS**, yaitu aplikasi Learning Management System berbasis PHP Native, MySQL, PDO, HTML, CSS, dan JavaScript.

Proyek ini sudah memiliki source code, database, halaman aplikasi, dan screenshot rancangan UI.

**TUGAS UTAMA:**

Lanjutkan dan sempurnakan proyek yang sudah ada sehingga:

1. Tampilan seluruh halaman semirip mungkin dengan screenshot dalam folder `EduLMS_UI`.
2. Menggunakan Figma EduLMS sebagai referensi tambahan.
3. Semua halaman terintegrasi dengan database.
4. Seluruh tombol, navigasi, form, filter, modal, grafik, dan fitur memiliki fungsi yang nyata.
5. Alur bisnis Admin, Guru, dan Siswa saling terhubung.
6. Aplikasi benar-benar bisa digunakan langsung di XAMPP, bukan sekadar prototype atau UI statis.

**JANGAN MEMBUAT PROYEK BARU DARI NOL.**

---

### 2. PRIORITAS PALING PENTING: BACA REFERENSI UI DAHULU

Sebelum mengubah kode apa pun, WAJIB membaca dan menganalisis folder:

`EduLMS_UI/`

Isi referensi:

1. `Dashboard dengan Grafik Perkembangan Belajar.png`
2. `Dashboard Guru (Top Navbar).png`
3. `Html → Body.png`
4. `Slide 1 - Mata Pelajaran.png`
5. `Slide 2 - Tugas.png`
6. `Slide 3 - Pengumuman.png`
7. `Slide Guru - Kelas & Mata Pelajaran.png`
8. `Slide Guru - Penilaian Tugas.png`
9. `Slide Guru - Presensi Siswa.png`

WAJIB membuka setiap gambar sebagai gambar visual, bukan sekadar membaca nama file atau metadata.

Lakukan analisis visual menyeluruh atas masing-masing screenshot.

Perhatikan secara detail:

- Struktur halaman dan komposisi layout.
- Posisi, tinggi, dan lebar navbar.
- Alignment setiap komponen.
- Typography, font weight, ukuran, dan line height.
- Warna background, teks, border, dan komponen.
- Padding, margin, whitespace, dan jarak antarelemen.
- Ukuran kartu, grid, dan responsivitas.
- Border radius dan box shadow.
- Desain tabel, filter, badge, dan status.
- Ikon dan ilustrasi.
- Warna serta bentuk grafik.
- Tata letak tombol.
- Empty state, hover state, dan active state.

**Screenshot adalah sumber kebenaran visual utama.**

Dilarang sekadar membuat tampilan yang "terinspirasi" dari screenshot.

Targetnya adalah mereplikasi screenshot sedekat mungkin secara visual.

Jangan mengganti desain menjadi gaya dashboard generik hanya karena lebih mudah dibuat.

### 3. REFERENSI FIGMA

Figma EduLMS:

https://www.figma.com/design/xPze1apaBQ4d6XjGRNPAlp/EduLMS?node-id=0-1&p=f&t=0sSsE7DrzgAPOm9D-0

Jika tersedia akses Figma melalui MCP atau integrasi lainnya, inspeksi frame, komponen, warna, spacing, dan typography secara langsung.

Jika Figma tidak dapat diakses:
- Jangan mengklaim sudah memeriksanya.
- Jangan berhenti bekerja.
- Gunakan seluruh PNG dari `EduLMS_UI` sebagai sumber utama.
- Gunakan `perancangan1.md` sebagai referensi cakupan halaman dan fitur.

Urutan prioritas:

1. Screenshot `EduLMS_UI` untuk kesesuaian visual.
2. Figma untuk spesifikasi desain tambahan yang dapat diakses.
3. `perancangan1.md` untuk cakupan fitur.
4. Source code dan database existing sebagai acuan implementasi.
5. `PANDUAN.md` untuk memahami penggunaan aplikasi.

Jika terdapat perbedaan antara desain dan implementasi existing, sesuaikan presentasi visual tanpa merusak aturan bisnis.

---

### 4. TECHNOLOGY LOCK — JANGAN MENGUBAH STACK

WAJIB pertahankan:

- PHP Native.
- PDO.
- MySQL/MariaDB.
- HTML, CSS, JavaScript.
- Tailwind CSS apabila sudah digunakan.
- Chart.js existing.
- XAMPP.
- Database `edulms_db`.

DILARANG:
- Migrasi ke Laravel.
- Menggunakan React atau Vue.
- Mengubah proyek menjadi SPA baru.
- Melakukan rewrite total.
- Menghapus fitur existing yang masih berfungsi.
- Mengganti library hanya karena preferensi.
- Menghapus atau mereset data database tanpa kebutuhan dan izin.

Gunakan ulang komponen dan fungsi existing sebisa mungkin.

---

### 5. AUDIT PROYEK SEBELUM MODIFIKASI

Periksa seluruh struktur proyek, terutama:

**Frontend**
- `assets/css/style.css`
- `assets/js/script.js`
- Seluruh halaman `dashboard/`
- Seluruh file `includes/`

**Backend**
- Seluruh file PHP pada `auth/`
- Seluruh file PHP pada `dashboard/`
- `includes/db.php`
- `includes/functions.php`
- `includes/auth_check.php`
- `includes/csrf.php`

**Database**
- `database/edulms_db.sql`
- `database/migrasi_inkremental.sql`
- `database/akun_demo_siswa.sql`

**Dokumentasi**
- `perancangan1.md`
- `PANDUAN.md`

Identifikasi:

1. Halaman yang sudah sesuai desain.
2. Halaman yang hanya sebagian sesuai.
3. Halaman yang belum mengikuti screenshot.
4. Tombol atau interaksi yang belum berfungsi.
5. Query database bermasalah.
6. Fitur yang belum saling terintegrasi.
7. Potensi error, keamanan, dan inkonsistensi data.

Buat file `docs/UI_AUDIT.md` yang berisi matriks:

Halaman | Referensi Screenshot | File PHP | Kondisi Visual | Kondisi Fungsional | Perbaikan

Setelah audit, **langsung lakukan implementasi**. Jangan berhenti hanya dengan memberikan laporan.

---

### 6. IMPLEMENTASI UI — PIXEL-LEVEL FIDELITY

Kerjakan satu halaman demi satu halaman berdasarkan screenshot.

Gunakan pendekatan:

**Inspect → Implement → Render → Compare → Refine → Test.**

Untuk setiap halaman:

1. Periksa screenshot asli.
2. Identifikasi ukuran dan posisi komponen.
3. Perbaiki HTML/CSS yang sudah ada.
4. Jalankan halaman secara lokal.
5. Ambil screenshot implementasi jika browser testing tersedia.
6. Bandingkan dengan screenshot referensi.
7. Perbaiki ketidaksesuaian visual.
8. Uji seluruh interaksi.
9. Lanjut ke halaman berikutnya.

Gunakan viewport desktop 1440px sebagai salah satu ukuran pembanding, ditambah ukuran asli gambar bila berbeda.

Jangan berhenti ketika halaman hanya terlihat "cukup mirip".

Perhatikan detail kecil seperti:
- Tinggi navbar.
- Lebar content container.
- Jarak antara heading dan card.
- Proporsi grid dashboard.
- Ukuran button.
- Alignment ikon.
- Konsistensi warna.
- Radius dan shadow.
- Padding dalam tabel.
- Tata letak chart.

Targetkan kesesuaian visual yang sangat tinggi. Jika pengukuran otomatis tersedia, gunakan perbandingan screenshot untuk menemukan perbedaan.

Jangan mengklaim kesesuaian 95–100% tanpa pengukuran nyata.

**PENTING:**

Jangan menggunakan screenshot keseluruhan halaman sebagai background atau satu gambar besar untuk memalsukan tampilan.

Seluruh UI harus dibangun menggunakan elemen HTML/CSS asli, responsif, dan interaktif.

---

### 7. DESIGN SYSTEM GLOBAL

Buat atau rapikan sistem desain reusable yang konsisten.

Komponen yang perlu diseragamkan:

- Global Navbar.
- Sidebar jika memang digunakan pada referensi.
- Page Container.
- Page Header.
- Button Primary/Secondary/Danger.
- Card.
- Stats Card.
- Data Table.
- Search Input.
- Filter Dropdown.
- Status Badge.
- Modal.
- Form Field.
- Pagination.
- Toast Notification.
- Loading State.
- Empty State.
- Error State.
- Chart Container.

**Jangan mengubah layout Top Navbar menjadi Sidebar apabila screenshot menggunakan Top Navbar.**

Prioritaskan CSS/component existing. Hindari membuat style duplikat yang saling bertabrakan.

Seluruh halaman harus mempunyai bahasa desain yang konsisten.

---

### 8. MODUL SISWA — UI DAN FUNGSI

**Dashboard Siswa**

Sesuaikan dengan screenshot dashboard siswa.

Implementasikan:
- Welcome banner.
- Statistik pembelajaran.
- Grafik perkembangan nilai.
- Ringkasan kehadiran.
- Progress pembelajaran.
- Tugas terdekat.
- Mata pelajaran aktif.
- Navigasi ke detail data.

Semua angka harus bersumber dari database atau hasil perhitungan data nyata. Jangan hardcode statistik palsu.

**Mata Pelajaran**

Sesuaikan dengan `Slide 1 - Mata Pelajaran.png`.

Pastikan:
- Mata pelajaran yang ditampilkan sesuai kelas siswa.
- Search berfungsi.
- Kartu mata pelajaran dapat dibuka.
- Materi dapat diakses.
- File dapat diunduh sesuai hak akses.
- Data guru dan mata pelajaran sesuai database.

**Tugas**

Sesuaikan dengan `Slide 2 - Tugas.png`.

Pastikan:
- Daftar tugas sesuai kelas dan mata pelajaran siswa.
- Filter status benar-benar berjalan.
- Tenggat waktu ditampilkan dengan benar.
- Siswa dapat mengumpulkan tugas.
- Upload file tersimpan dengan aman.
- Tugas yang telah dikumpulkan mempunyai status yang tepat.
- Nilai dan feedback ditampilkan setelah tersedia.

**Pengumuman**

Sesuaikan dengan `Slide 3 - Pengumuman.png`.

Pastikan:
- Daftar pengumuman berasal dari database.
- Pencarian bekerja.
- Detail pengumuman dapat dibaca.
- Target penerima sesuai aturan bisnis.
- Pengumuman terbaru tampil dengan benar.

---

### 9. MODUL GURU — UI DAN FUNGSI

Gunakan screenshot guru sebagai acuan utama.

**Dashboard Guru**
- Statistik mengajar.
- Kelas yang diampu.
- Mata pelajaran yang diajar.
- Tugas menunggu penilaian.
- Ringkasan kegiatan.
- Navigasi cepat.

**Kelas dan Mata Pelajaran**
- Hanya menampilkan kelas yang ditugaskan kepada guru tersebut.
- Guru dapat mengelola materi sesuai izin.
- Guru dapat membuat dan melihat tugas.
- Daftar siswa sesuai kelas yang diajar.

**Penilaian Tugas**
- Tampilkan tugas yang dikumpulkan.
- Guru dapat memeriksa jawaban/file.
- Input nilai.
- Berikan feedback.
- Simpan perubahan.
- Nilai otomatis tersedia pada akun siswa terkait.

**Presensi**
- Guru memilih kelas dan sesi/tanggal presensi sesuai otorisasi.
- Daftar siswa ditampilkan dari database.
- Status presensi dapat diisi.
- Data tersimpan dan dapat dilihat kembali.
- Perubahan harus konsisten dengan rekap kehadiran siswa.

Tidak boleh ada penilaian atau presensi yang memengaruhi siswa di luar kelas dan mata pelajaran yang diampu.

---

### 10. MODUL ADMIN

Screenshot dalam `EduLMS_UI` belum tentu mencakup seluruh halaman Admin.

Untuk bagian tanpa screenshot, gunakan design system yang telah diekstraksi dari referensi.

Pastikan Admin dapat:

- Mengelola akun pengguna.
- Mengelola kelas.
- Mengelola mata pelajaran.
- Mengelola penempatan guru.
- Mengelola penugasan guru.
- Mengelola pengumuman.
- Melihat informasi akademik sesuai kewenangan.

Seluruh CRUD wajib mempunyai:
- Validasi server-side.
- Validasi input yang sesuai.
- Error handling.
- Pesan sukses/gagal.
- Konfirmasi penghapusan.
- Pengecekan relasi data.
- Proteksi akses sesuai role.

Jangan membuat fitur Admin hanya sebagai tabel dan tombol tanpa proses database.

---

### 11. INTEGRASI ALUR BISNIS END-TO-END

Pastikan seluruh modul saling berhubungan.

**Skenario A — Admin mengelola pembelajaran**

Admin membuat kelas → menempatkan siswa → membuat mata pelajaran → menugaskan guru → kelas dan mata pelajaran tersedia di akun terkait.

**Skenario B — Guru memberikan materi**

Guru login → memilih kelas yang diampu → mengunggah materi → materi tersimpan → siswa pada kelas terkait dapat melihat dan mengunduh.

**Skenario C — Siklus tugas**

Guru membuat tugas → siswa melihat tugas → siswa mengumpulkan jawaban → guru menerima pengumpulan → guru menilai → siswa melihat nilai dan feedback → statistik dashboard diperbarui.

**Skenario D — Presensi**

Guru mencatat presensi siswa → data tersimpan → riwayat presensi siswa terbarui → rekap kehadiran menampilkan hasil yang sesuai.

**Skenario E — Pengumuman**

Admin menerbitkan pengumuman → penerima yang sesuai melihat pengumuman → fitur pencarian dan detail bekerja.

Semua skenario harus diuji, bukan hanya dijelaskan.

---

### 12. KEAMANAN DAN DATA

Pastikan:
- Semua query menggunakan PDO prepared statements.
- Login menggunakan password hash yang aman.
- Session dan role divalidasi pada server.
- CSRF protection untuk aksi perubahan data.
- Output HTML di-escape.
- Upload file divalidasi berdasarkan tipe, ukuran, dan keamanan.
- Unduhan file dibatasi sesuai hak akses.
- Tidak ada akses langsung ke data pengguna lain tanpa izin.
- Error teknis tidak membocorkan informasi sensitif.
- Tidak ada password database atau kredensial yang dimasukkan ke Git.

Pertahankan integritas data existing.

Jika membutuhkan migrasi database, buat migrasi inkremental yang dapat dijalankan dengan aman.

---

### 13. FITUR INTERAKSI UI

Seluruh elemen interaktif wajib benar-benar berfungsi.

Contoh:
- Search memfilter data nyata.
- Filter mengubah hasil data.
- Dropdown dapat dibuka dan dipilih.
- Modal dapat dibuka dan ditutup.
- Form tersimpan ke database.
- Tombol edit memuat data yang benar.
- Tombol hapus menjalankan validasi dan penghapusan sesuai aturan.
- Pagination berpindah halaman.
- Tombol download mengunduh file yang diizinkan.
- Grafik memakai data aktual.
- Toast sesuai hasil aksi.
- Active navigation sesuai halaman aktif.

**DILARANG membuat tombol dekoratif yang tidak memiliki aksi ketika desain mengindikasikan fungsi.**

Jika fitur belum didukung backend, implementasikan backend yang sesuai tanpa mengubah aturan bisnis secara sembarangan.

---

### 14. RESPONSIVE DESIGN

Prioritas pertama adalah kesesuaian dengan screenshot desktop.

Setelah desktop sesuai, pastikan tampilan tetap nyaman pada:
- Desktop besar.
- Laptop.
- Tablet.
- Smartphone.

Jangan merusak layout desktop demi membuat versi mobile.

Tabel lebar harus tetap dapat digunakan pada layar kecil.

Navbar, kartu, form, dan grafik harus menyesuaikan viewport.

---

### 15. PENGUJIAN OTOMATIS DAN MANUAL

Setelah implementasi, jalankan pengujian:

**A. PHP & Database**
- PHP syntax/lint.
- Database connection.
- Validasi query.
- Login/logout.
- Session.
- Role authorization.

**B. UI**
- Screenshot comparison.
- Responsive layout.
- Overflow.
- Alignment.
- Console/browser errors.
- Broken assets.

**C. Functional**
- CRUD Admin.
- Upload materi.
- Pengumpulan tugas.
- Penilaian.
- Presensi.
- Pengumuman.
- Search dan filter.
- Unduhan file.
- Statistik dan grafik.

**D. End-to-End**
Uji seluruh skenario Admin → Guru → Siswa menggunakan data pengujian yang aman.

Jangan mengklaim fitur sudah diuji jika belum benar-benar dijalankan.

Jika browser, XAMPP, atau database tidak tersedia dari lingkungan Codex, jelaskan kendalanya, lakukan seluruh pemeriksaan yang memungkinkan, dan sediakan instruksi pengujian manual.

---

### 16. CARA KERJA WAJIB

Laksanakan secara berurutan:

**FASE 1 — Discovery**
- Baca seluruh screenshot UI.
- Pelajari Figma jika tersedia.
- Audit source code dan database.
- Petakan screenshot ke halaman PHP.

**FASE 2 — UI Implementation**
- Rapikan design system.
- Implementasikan UI Siswa.
- Implementasikan UI Guru.
- Selaraskan UI Admin.
- Perbaiki responsivitas.

**FASE 3 — Backend Integration**
- Hubungkan seluruh UI dengan backend existing.
- Perbaiki fitur yang tidak berfungsi.
- Selesaikan integrasi database.
- Periksa otorisasi dan keamanan.

**FASE 4 — Verification**
- Jalankan aplikasi.
- Bandingkan screenshot.
- Refine UI.
- Uji seluruh alur bisnis.
- Perbaiki error.
- Lakukan regression testing.

**FASE 5 — Documentation**
Buat:
- `docs/UI_AUDIT.md`
- `docs/UI_FIDELITY_REPORT.md`
- `docs/FUNCTIONAL_TEST_REPORT.md`
- `docs/CHANGELOG_IMPLEMENTASI.md`
- `docs/PANDUAN_MENJALANKAN.md`

Catat fitur yang selesai, yang telah diuji, dan yang masih memiliki keterbatasan.

---

### 17. DEFINITION OF DONE

Pekerjaan dianggap selesai hanya apabila:

- [ ] Seluruh screenshot `EduLMS_UI` sudah dianalisis.
- [ ] Setiap halaman yang memiliki referensi sudah mengikuti desain semirip mungkin.
- [ ] Halaman tanpa screenshot tetap konsisten dengan design system.
- [ ] Tidak ada tombol palsu atau navigasi rusak.
- [ ] Semua form terhubung dengan backend.
- [ ] Data dashboard dihitung dari database.
- [ ] Alur Admin, Guru, dan Siswa saling terintegrasi.
- [ ] Hak akses dijalankan di server.
- [ ] Upload/download bekerja dengan benar dan aman.
- [ ] Tampilan desktop dan mobile berfungsi.
- [ ] Pengujian yang tersedia sudah dijalankan.
- [ ] Aplikasi dapat dijalankan melalui XAMPP.
- [ ] Dokumentasi diperbarui.

Jangan mencentang item yang belum terbukti.

---

### 18. ATURAN EKSEKUSI FINAL

Mulai sekarang, lakukan inspeksi folder `EduLMS_UI` terlebih dahulu.

**JANGAN langsung mengedit CSS sebelum memahami seluruh gambar.**

Setelah audit selesai, lanjutkan implementasi sampai seluruh pekerjaan dapat diselesaikan semaksimal mungkin.

Jangan berhenti hanya untuk bertanya apakah perlu melanjutkan fase berikutnya.

Gunakan keputusan teknis yang masuk akal apabila detail kecil belum ditentukan, tetapi jangan membuat asumsi untuk perubahan berisiko seperti penghapusan data, perubahan role, atau perombakan struktur database.

Pertahankan semua pekerjaan existing yang sudah benar.

Jangan menghapus atau menimpa perubahan pengguna yang tidak berkaitan dengan tugas ini.

Prioritaskan tiga hasil akhir:

**1. VISUAL FIDELITY:** Tampilan sangat mirip dengan screenshot.

**2. FUNCTIONAL COMPLETENESS:** Fitur benar-benar bisa digunakan.

**3. BUSINESS CONSISTENCY:** Data dan alur Admin, Guru, Siswa saling berhubungan dengan benar.

Target akhir adalah aplikasi **EduLMS yang visualnya sesuai rancangan, fungsional, aman, dan dapat dijalankan langsung secara lokal**, bukan sekadar demo HTML atau redesign dashboard.