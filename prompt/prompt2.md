# MASTER PROMPT FINAL V2.0 — EduLMS
## FULL DEVELOPMENT, BUSINESS LOGIC VALIDATION, DATABASE INTEGRATION, UI FIDELITY & END-TO-END TESTING

# 1. PERAN DAN MISI UTAMA

Kamu bertindak sebagai tim pengembang profesional yang terdiri dari:

1. Senior PHP Native Full-Stack Developer
2. Senior System Analyst
3. Business Process Analyst
4. Database Architect (MySQL/MariaDB)
5. Application Security Engineer
6. UI Fidelity Engineer
7. QA Automation & Functional Tester
8. Technical Documentation Engineer

Saya memiliki proyek **EduLMS**, yaitu aplikasi Learning Management System berbasis web untuk lingkungan SMA.

Proyek ini SUDAH DIKEMBANGKAN dan memiliki source code, database, rancangan UI, serta beberapa modul yang sudah berfungsi.

Namun, implementasinya masih belum sepenuhnya selesai. Sebagian fitur baru berfungsi secara individual dan belum terhubung menjadi proses bisnis LMS yang utuh.

**MISI UTAMA:**

Lakukan audit menyeluruh, analisis logika bisnis, perbaikan keamanan, penyempurnaan database, penyelesaian fitur, integrasi antarmodul, sinkronisasi dashboard, verifikasi UI, dan pengujian end-to-end hingga EduLMS menjadi aplikasi yang benar-benar dapat digunakan sesuai kebutuhan proyek.

JANGAN hanya mengaktifkan tombol atau melengkapi halaman.

Pastikan setiap proses mempunyai hubungan data, hak akses, aturan bisnis, dan hasil yang konsisten.

Kerjakan tahapan secara berurutan dalam satu rangkaian pekerjaan. Setelah satu tahap selesai dan tervalidasi, lanjutkan ke tahap berikutnya tanpa menunggu instruksi tambahan, kecuali terdapat keputusan yang benar-benar tidak boleh diasumsikan.

---

# 2. ATURAN MUTLAK — TECHNOLOGY LOCK

Teknologi existing adalah keputusan FINAL.

WAJIB mempertahankan:

- Backend: PHP Native.
- Database: MySQL/MariaDB.
- Database access: PDO.
- Frontend: HTML, CSS, JavaScript existing.
- Styling: Tailwind CSS yang sudah digunakan.
- Grafik: Chart.js existing.
- Environment: XAMPP.
- Database: `edulms_db`.
- Arsitektur dasar dan struktur proyek existing.

DILARANG:

- Migrasi ke Laravel.
- Migrasi ke CodeIgniter.
- Migrasi ke React.
- Migrasi ke Vue.
- Mengganti backend ke Node.js atau bahasa lain.
- Mengganti framework CSS.
- Menginstal framework baru tanpa kebutuhan.
- Membuat ulang proyek dari nol.
- Melakukan rewrite massal.
- Mengganti library yang sudah berfungsi hanya karena preferensi developer.

Gunakan kembali struktur dan komponen existing jika memungkinkan.

Perubahan struktur diperbolehkan hanya apabila diperlukan untuk memperbaiki kesalahan atau menjaga integritas sistem, tanpa mengubah stack teknologi.

---

# 3. ATURAN MUTLAK — UI DESIGN LOCK

## Referensi desain utama

Figma EduLMS:

https://www.figma.com/design/xPze1apaBQ4d6XjGRNPAlp/EduLMS?node-id=0-1&p=f

Referensi tambahan:

- 9 screenshot PNG UI EduLMS.
- Source code frontend existing.
- Aset gambar dan ikon existing.

## Kewajiban

Desain Figma dan PNG merupakan referensi visual resmi.

WAJIB mempertahankan:

- Warna.
- Layout.
- Navbar.
- Sidebar.
- Posisi menu.
- Tipografi.
- Ukuran font.
- Komponen card.
- Spacing.
- Padding.
- Margin.
- Border radius.
- Shadow.
- Ikon.
- Tabel.
- Grafik.
- Tombol.
- Form.
- Responsive behavior.
- Hirarki visual.

**PRINSIP UTAMA: PERTAHANKAN UI, PERBAIKI DAN LENGKAPI FUNGSINYA.**

Jangan melakukan redesign.

Jangan mengganti halaman dengan template dashboard generik.

Jangan mengubah tampilan halaman yang sudah sesuai.

Untuk halaman yang desain PNG-nya tersedia tetapi belum diimplementasikan, buat halaman yang mengikuti referensi tersebut sedekat mungkin.

Untuk fitur tambahan yang belum mempunyai desain khusus, prioritaskan penggunaan ulang komponen existing tanpa memperkenalkan identitas visual baru.

Khusus Portal Admin, jangan mengklaim desain baru sudah sesuai Figma karena referensi Portal Admin belum tersedia. Jika UI administrasi minimum diperlukan untuk menuntaskan proses bisnis, gunakan pola komponen existing, tandai sebagai implementasi sementara, dan dokumentasikan kebutuhan validasi desain.

Jika akses Figma langsung tidak tersedia, gunakan PNG yang disediakan.

Jangan menyatakan pixel-perfect tanpa melakukan perbandingan screenshot pada viewport yang sama.

**FUNCTIONAL DEVELOPMENT TIDAK BOLEH MENJADI ALASAN REDESIGN.**

---

# 4. KONTEKS PROYEK EXISTING

Baca kondisi proyek terbaru terlebih dahulu. Jangan menganggap dokumentasi lama selalu sama dengan implementasi aktual.

Dokumen acuan:

`Alur Sistem EduLMS — Analisis Lengkap v1.1`

Struktur utama yang telah didokumentasikan:

- `auth/`
- `includes/`
- `dashboard/`
- `uploads/`
- `database/`

Tabel yang telah didokumentasikan:

1. `users`
2. `kelas`
3. `siswa_kelas`
4. `mata_pelajaran`
5. `guru_mapel`
6. `materi`
7. `tugas`
8. `pengumpulan_tugas`
9. `presensi`
10. `pengumuman`
11. `statistik_siswa`

Relasi terpenting adalah `guru_mapel`, yaitu penghubung guru, mata pelajaran, kelas, dan konteks tahun ajaran.

Materi, tugas, dan presensi menggunakan relasi tersebut.

**JANGAN membongkar arsitektur relasi ini jika sebenarnya sudah benar.**

Lakukan penyempurnaan secara incremental berdasarkan masalah yang ditemukan.

Dokumen alur merupakan acuan analisis, bukan bukti bahwa implementasi sudah benar.

Setiap klaim harus diverifikasi terhadap source code dan database aktif.

---

# 5. TAHAP 1 — AUDIT TEKNIS DAN BUSINESS LOGIC

Lakukan audit menyeluruh terhadap:

- Struktur proyek.
- Seluruh file PHP.
- Seluruh JavaScript.
- Seluruh stylesheet.
- Query database.
- Struktur tabel.
- Primary key dan foreign key.
- Form.
- Tombol.
- Link navigasi.
- Session.
- Hak akses.
- Upload dan download.
- Fitur siswa.
- Fitur guru.
- Fitur admin.
- Dashboard dan grafik.
- Desain PNG.
- Dokumentasi existing.

## Analisis teknis

Identifikasi:

1. Fitur yang sudah berfungsi.
2. Fitur yang hanya berupa UI.
3. Fitur yang belum terhubung database.
4. Tombol yang tidak mempunyai aksi.
5. Link yang rusak.
6. Query yang salah.
7. Bug autentikasi.
8. Bug otorisasi.
9. Duplikasi data.
10. Ketidaksesuaian antara UI dan backend.
11. Risiko keamanan.
12. Masalah konsistensi data.

## Analisis logika bisnis

Periksa apakah:

- Pengguna baru mendapatkan role yang benar.
- Siswa dapat ditempatkan ke kelas.
- Guru dapat ditugaskan ke mata pelajaran dan kelas.
- Guru hanya mengelola kelasnya.
- Siswa hanya melihat pembelajaran dari kelasnya.
- Tugas terhubung dengan pengumpulan.
- Pengumpulan terhubung dengan penilaian.
- Penilaian terhubung dengan dashboard.
- Presensi terhubung dengan rekap.
- Pengumuman mempunyai pembuat dan penerima yang jelas.
- Tahun ajaran membatasi konteks data.
- Data historis tetap konsisten.

Buat:

`AUDIT_EDULMS.md`

Tambahkan laporan khusus:

`BUSINESS_LOGIC_ANALYSIS.md`

Gunakan kategori:

- SUDAH BENAR
- BELUM LENGKAP
- SALAH LOGIKA
- TIDAK TERHUBUNG
- BELUM DIIMPLEMENTASIKAN
- PERLU KEPUTUSAN BISNIS

Untuk setiap masalah, sertakan file atau fungsi terkait, dampak, solusi, dan prioritas.

Setelah audit, lanjutkan implementasi berdasarkan hasilnya.

---

# 6. TAHAP 2 — PERBAIKAN FONDASI KEAMANAN

Sebelum modifikasi, periksa status Git dan buat backup yang tidak menimpa perubahan existing.

Pastikan:

- Role login berasal dari database.
- Password menggunakan `password_verify()`.
- Password baru disimpan menggunakan `password_hash()`.
- Session diregenerasi setelah login.
- Logout menghancurkan session dengan benar.
- Pengguna tidak aktif ditolak.
- Session memiliki timeout.
- Query memakai PDO prepared statements.
- Output HTML di-escape.
- Seluruh aksi perubahan data menggunakan metode yang sesuai.
- Form perubahan data dilindungi CSRF.
- Endpoint mengecek role dan kepemilikan data.
- File upload divalidasi secara aman.

Perbaiki aksi penghapusan materi yang menggunakan GET `?delete=id`.

Gunakan POST dengan CSRF dan pengecekan hak akses.

Jangan menghapus data produksi untuk menguji fitur.

Pastikan seluruh perbaikan tidak mengubah tampilan desain.

---

# 7. TAHAP 3 — VALIDASI MODEL BISNIS ADMIN, GURU DAN SISWA

Ini adalah salah satu tahap terpenting.

EduLMS harus mempunyai pemisahan peran yang jelas.

## A. ADMIN — PENGELOLA DATA SEKOLAH

Tanggung jawab Admin:

- Mengelola akun guru.
- Mengelola akun siswa.
- Mengaktifkan dan menonaktifkan akun.
- Mengelola kelas.
- Mengelola mata pelajaran.
- Menempatkan siswa ke kelas.
- Menugaskan guru ke kelas dan mata pelajaran.
- Mengelola tahun ajaran.
- Mengelola pengumuman sekolah.

Admin bukan Guru.

Jangan otomatis menganggap admin berhak mengubah materi, nilai, tugas, atau presensi guru.

Saat ini dokumen menjelaskan Admin diarahkan ke halaman `guru.php` menggunakan `requireTeacherOrAdmin()`.

Evaluasi dan perbaiki masalah tersebut.

Target:

- Admin mempunyai hak akses administratif yang jelas.
- Guru hanya mempunyai hak akses akademik sesuai penugasannya.
- Siswa hanya mempunyai hak akses pembelajaran sesuai enrolmen.

Jangan membuat akses lintas role secara sembarangan.

Karena desain dashboard Admin belum tersedia, bedakan pekerjaan backend dan kebutuhan UI.

Implementasikan fungsi administrasi minimum menggunakan pola visual existing bila diperlukan, kemudian tandai bahwa desain final Admin masih memerlukan validasi.

## B. GURU — PENGELOLA PEMBELAJARAN

Guru hanya boleh:

- Melihat kelas yang diampu.
- Mengakses siswa pada kelas tersebut.
- Mengelola materi sendiri.
- Membuat dan mengelola tugas sendiri.
- Melihat pengumpulan tugas terkait.
- Menilai tugas siswa terkait.
- Mengisi presensi kelas yang diampu.
- Melihat rekap akademik terkait.

Guru tidak boleh:

- Mengubah role pengguna.
- Membuat akun admin.
- Mengubah penugasan guru lain.
- Mengubah nilai dari kelas yang tidak diampunya.
- Mengakses file jawaban dari kelas yang tidak berhak diakses.

## C. SISWA — PESERTA PEMBELAJARAN

Siswa hanya boleh:

- Melihat mata pelajaran yang diikuti.
- Membaca materi terpublikasi.
- Mengunduh materi yang diizinkan.
- Melihat tugas yang diberikan.
- Mengumpulkan tugas sendiri.
- Melihat status pengumpulan sendiri.
- Melihat nilai dan feedback sendiri.
- Melihat presensi sendiri.
- Membaca pengumuman yang ditujukan kepadanya.

Siswa tidak boleh:

- Mengubah nilai.
- Mengisi presensi.
- Melihat pengumpulan siswa lain.
- Mengakses kelas yang tidak diikuti.
- Membuat atau menghapus tugas guru.

Pemeriksaan ini harus diterapkan pada backend, bukan hanya menyembunyikan tombol.

---

# 8. TAHAP 4 — PERBAIKAN ALUR REGISTRASI DAN PENEMPATAN SISWA

Masalah yang harus diselesaikan:

Registrasi siswa membuat akun, tetapi belum tentu membuat keanggotaan kelas.

Akibatnya siswa dapat login tanpa mempunyai mata pelajaran.

## Alur yang diperlukan

1. Siswa melakukan registrasi jika registrasi publik memang diizinkan.
2. Sistem menyimpan akun dengan role `siswa`.
3. Akun belum otomatis memiliki keanggotaan kelas.
4. Admin melakukan verifikasi dan/atau penempatan siswa ke kelas sesuai kebijakan.
5. Data disimpan dalam `siswa_kelas`.
6. Sistem mengetahui kelas aktif siswa.
7. Mata pelajaran diambil berdasarkan penugasan guru terhadap kelas tersebut.
8. Siswa dapat mengakses materi dan tugas yang relevan.

## Aturan

- Jangan otomatis menempatkan siswa ke kelas acak.
- Jangan memberikan akses akademik ke semua kelas.
- Jangan membuat enrolmen palsu.
- Jangan menghapus akun siswa yang belum mempunyai kelas.
- Jangan otomatis menonaktifkan akun existing akibat perubahan aturan registrasi.
- Tampilkan empty state yang sesuai bagi siswa yang belum mempunyai kelas.

Jika kebijakan verifikasi belum ditentukan, gunakan alur penempatan oleh Admin sebagai asumsi kerja dan dokumentasikan asumsi tersebut.

Gunakan status siswa kelas secara konsisten.

Siswa dengan status pindah, lulus, atau keluar tidak boleh dianggap sebagai anggota aktif dalam query pembelajaran tahun berjalan.

Tetap pertahankan data historis sesuai kebutuhan.

---

# 9. TAHAP 5 — PERBAIKAN ALUR KELAS, MATA PELAJARAN DAN GURU

Pertahankan konsep `guru_mapel` sebagai pusat penugasan mengajar.

## Alur yang benar

Admin membuat kelas.

Admin membuat atau memilih mata pelajaran.

Admin menugaskan guru kepada kelas dan mata pelajaran.

Sistem menyimpan relasi `guru_mapel`.

Guru login dan melihat kelas serta mata pelajaran yang diampu.

Siswa kelas terkait otomatis melihat mata pelajaran tersebut.

## Aturan integritas

- Hindari penugasan duplikat yang tidak mempunyai alasan bisnis.
- Validasi apakah user yang ditugaskan memang berperan sebagai guru.
- Validasi kelas dan mata pelajaran masih berlaku.
- Jangan menampilkan mata pelajaran dari kelas lain.
- Periksa tahun ajaran aktif.
- Hindari duplikasi mata pelajaran pada daftar siswa akibat JOIN.
- Jangan menggunakan `mata_pelajaran.id` sebagai pengganti `guru_mapel.id` secara tidak konsisten.

Jika guru berbeda mengajar mata pelajaran yang sama pada kelas berbeda, keduanya harus diperlakukan sebagai penugasan terpisah.

Jangan mengubah identitas relasi existing tanpa migrasi yang aman.

---

# 10. TAHAP 6 — PERBAIKAN SIKLUS MATERI

Alur utama:

Guru membuat materi → Guru menerbitkan → Siswa mengakses → Siswa membaca atau mengunduh.

## Fitur

Guru dapat:

- Membuat materi.
- Mengedit judul.
- Mengedit deskripsi.
- Mengunggah file.
- Mengubah file apabila diperlukan.
- Menambahkan URL video jika didukung.
- Menyimpan draft.
- Menerbitkan materi.
- Menarik materi dari publikasi.
- Menghapus materi sesuai kebijakan.

Siswa hanya dapat melihat materi dengan status terpublikasi dan yang berasal dari kelasnya.

## Aturan bisnis

- Materi draft tidak tampil pada siswa.
- Materi harus terkait `guru_mapel` yang valid.
- Guru tidak bisa mengubah materi guru lain.
- File materi tidak boleh dibuka oleh pengguna yang tidak berhak.
- Pengeditan materi tidak boleh membuat data duplikat.
- Jika file diganti, kelola file lama dengan aman.
- Penghapusan materi menggunakan POST dan CSRF.
- Jangan menghapus file fisik sebelum perubahan database dinyatakan berhasil.

Jangan menambahkan fitur pembelajaran yang tidak diminta.

---

# 11. TAHAP 7 — PERBAIKAN SIKLUS TUGAS SECARA MENYELURUH

Ini adalah inti LMS dan harus menjadi prioritas utama.

## A. Alur tugas

1. Guru membuat tugas.
2. Guru menentukan kelas dan mata pelajaran yang diampu.
3. Guru menentukan instruksi.
4. Guru menentukan batas pengumpulan.
5. Guru menentukan format file yang diperbolehkan.
6. Guru menyimpan draft atau menerbitkan tugas.
7. Tugas terpublikasi muncul pada siswa yang berhak.
8. Siswa membuka detail tugas.
9. Siswa mengunggah jawaban.
10. Sistem menyimpan pengumpulan beserta waktu aktual.
11. Guru melihat daftar pengumpulan.
12. Guru memberi nilai dan feedback.
13. Siswa melihat hasil penilaian.
14. Dashboard dan rekap akademik diperbarui.

## B. Pisahkan status tugas, pengumpulan dan penilaian

JANGAN menggunakan satu kolom status untuk seluruh proses jika menimbulkan ambiguitas.

Status publikasi tugas:

- Draft
- Terpublikasi

Status pengumpulan:

- Belum dikumpulkan
- Sudah dikumpulkan

Ketepatan waktu:

- Tepat waktu
- Terlambat

Status penilaian:

- Belum dinilai
- Sudah dinilai

Gunakan pemisahan status secara logis dengan memanfaatkan struktur existing sejauh memungkinkan.

Tidak semua status perlu menjadi kolom baru jika dapat dihitung secara konsisten dari data yang tersedia.

Contoh:

Siswa sudah upload sebelum deadline tetapi belum mendapat nilai.

Status yang benar:

- Pengumpulan: Sudah Dikumpulkan
- Ketepatan: Tepat Waktu
- Penilaian: Belum Dinilai

Jangan menampilkan siswa sebagai sudah selesai dinilai hanya karena file sudah dikumpulkan.

## C. Aturan deadline

- Gunakan timezone aplikasi yang konsisten.
- Simpan waktu pengumpulan sebenarnya.
- Jangan memalsukan timestamp.
- Tentukan ketepatan waktu berdasarkan deadline yang berlaku.
- Jika keterlambatan diperbolehkan, tandai Terlambat.
- Jika keterlambatan tidak diperbolehkan, tolak sesuai aturan yang disepakati.
- Jangan diam-diam menerima atau menolak keterlambatan tanpa dasar aturan.

Gunakan aturan default yang konsisten dan dokumentasikan karena kebijakan keterlambatan sekolah belum ditetapkan.

Jika deadline berubah setelah ada pengumpulan, jangan secara diam-diam mengubah riwayat waktu pengumpulan lama.

## D. Revisi pengumpulan

Existing menggunakan UNIQUE `(tugas_id, siswa_id)`.

Pertahankan aturan satu pengumpulan aktif per siswa per tugas, kecuali ditemukan kebutuhan lain.

Jika memungkinkan penggantian file sebelum deadline:

- Update pengumpulan existing.
- Jangan membuat pengumpulan duplikat.
- Jangan mengubah nilai yang sudah diberikan tanpa aturan yang jelas.
- Simpan waktu penggantian jika diperlukan.

Jangan membuat fitur multi-attempt kompleks tanpa kebutuhan.

## E. Penilaian

Guru dapat:

- Melihat jawaban siswa.
- Mengunduh file jawaban.
- Memberikan nilai.
- Memberikan feedback.
- Memperbarui nilai jika diizinkan.
- Melihat status belum dinilai/sudah dinilai.

Validasi:

- Nilai harus dalam rentang yang ditentukan sistem, misalnya 0–100 bila skala tersebut yang dipakai.
- Nilai 0 adalah nilai valid, bukan dianggap belum dinilai.
- Hanya guru yang berwenang dapat menilai.
- Siswa hanya melihat nilainya sendiri.

Jangan membuat rumus nilai akhir atau pembobotan rapor yang belum ditentukan sekolah.

---

# 12. TAHAP 8 — PERBAIKAN PRESENSI

Periksa struktur dan logika presensi existing.

Saat ini dokumentasi menyebutkan keunikan presensi berdasarkan:

`guru_mapel_id + siswa_id + tanggal_sesi`

Model tersebut sesuai jika hanya terdapat satu sesi mata pelajaran per tanggal.

Tetapi bisa bermasalah jika satu mata pelajaran mempunyai lebih dari satu sesi pada hari yang sama.

## Aturan

Pertama, analisis kebutuhan aplikasi dan rancangan UI.

Jika kebutuhannya satu sesi per mata pelajaran per tanggal, pertahankan struktur sederhana dan dokumentasikan batasannya.

Jika diperlukan lebih dari satu sesi, gunakan identitas pertemuan atau sesi yang unik dengan migrasi aman.

Jangan menambahkan arsitektur presensi rumit tanpa kebutuhan.

## Alur presensi

Guru memilih kelas dan mata pelajaran.

Guru memilih tanggal atau sesi.

Sistem menampilkan siswa aktif pada kelas tersebut.

Guru memilih status:

- Hadir
- Sakit
- Izin
- Alfa

Guru menyimpan presensi.

Sistem menyimpan atau memperbarui data sesuai identitas sesi.

Siswa melihat riwayat kehadirannya sendiri.

## Validasi penting

- Siswa belum diabsen tidak otomatis dianggap Alfa.
- Presensi yang belum disimpan bukan data kehadiran final.
- Cegah duplikasi presensi.
- Guru hanya boleh mengisi kelas yang diampu.
- Siswa tidak bisa mengubah presensi.
- Status disimpan secara konsisten.
- Data tetap tersedia untuk rekap bulanan.

Fitur Tandai Semua Hadir boleh digunakan hanya sebagai bantuan input, bukan penyimpanan otomatis sebelum pengguna menyimpan.

---

# 13. TAHAP 9 — PENGELOLAAN PENGUMUMAN

Dokumentasi existing menunjukkan pengumuman baru dapat dibaca dan masih bergantung pada data seed.

Perbaiki alur ini.

## Minimum yang diperlukan

Admin dapat:

- Membuat pengumuman.
- Mengedit pengumuman.
- Menentukan target penerima.
- Menyimpan draft jika struktur mendukung.
- Menerbitkan.
- Menarik pengumuman dari publikasi.
- Menghapus sesuai kebijakan.

Siswa dan Guru dapat melihat pengumuman yang relevan.

Filter penerima harus diproses di backend.

Jika pengumuman hanya ditujukan kepada siswa, pengguna lain tidak boleh memperoleh kontennya melalui manipulasi parameter.

Pertahankan desain pengumuman existing.

Untuk pengumuman per kelas, jangan implementasikan penargetan kelas secara kompleks kecuali dibutuhkan desain atau alur bisnis yang sudah tersedia.

---

# 14. TAHAP 10 — PERBAIKAN DASHBOARD DAN STATISTIK

Periksa seluruh angka dashboard.

Dokumentasi menyebutkan:

- `statistik_siswa` merupakan cache yang belum otomatis diperbarui.
- Sebagian grafik nilai siswa masih menggunakan angka hardcoded.
- Beberapa statistik guru sudah menggunakan agregasi database.

Perbaiki agar dashboard mencerminkan data nyata.

## Dashboard Siswa

Gunakan sumber data aktual untuk:

- Jumlah mata pelajaran aktif.
- Jumlah tugas.
- Jumlah tugas sudah dikumpulkan.
- Jumlah tugas belum dikumpulkan.
- Tugas mendekati deadline.
- Nilai yang sudah diberikan.
- Rata-rata nilai.
- Persentase kehadiran.
- Pengumuman terbaru.

## Dashboard Guru

Hitung dari penugasan guru:

- Jumlah kelas diampu.
- Jumlah siswa terkait.
- Jumlah tugas aktif.
- Jumlah pengumpulan belum dinilai.
- Jadwal mengajar.
- Aktivitas akademik terbaru.

## Aturan statistik

- Tidak boleh ada angka dummy yang ditampilkan sebagai data nyata.
- Jangan menampilkan grafik nilai hardcoded.
- Jangan membagi dengan nol.
- Tangani kasus belum ada nilai.
- Bedakan nilai kosong dengan nilai 0.
- Jangan menghitung tugas yang sama dua kali akibat JOIN.
- Jangan memasukkan data kelas yang tidak relevan.
- Gunakan tahun ajaran sesuai konteks dashboard.

Jika `statistik_siswa` tetap digunakan sebagai cache, buat mekanisme pembaruan yang benar ketika nilai atau presensi berubah.

Untuk skala proyek kuliah, pertimbangkan query agregasi langsung apabila lebih sederhana dan performanya masih memadai.

Jangan memakai cache statis yang mudah menghasilkan angka tidak konsisten.

Jika metrik waktu belajar tidak mempunyai sumber data nyata, jangan memalsukan angkanya. Pertahankan desain komponen jika perlu, tetapi tampilkan keadaan belum tersedia secara jujur.

---

# 15. TAHAP 11 — TAHUN AJARAN DAN HISTORI AKADEMIK

Ini penting untuk menjaga konsistensi data.

Contoh:

Seorang siswa berada di kelas X IPA 1 pada tahun ajaran 2026/2027.

Pada 2027/2028, siswa pindah ke XI IPA 1.

Sistem tidak boleh menghilangkan riwayat kelas, tugas, nilai, maupun presensi sebelumnya.

## Kewajiban

- Pertahankan histori enrolmen.
- Pertahankan histori penugasan guru.
- Jangan mencampur kelas lintas tahun ajaran.
- Pastikan data dashboard memakai tahun ajaran yang sesuai.
- Jangan mengubah histori lama saat kelas siswa berubah.
- Pastikan guru melihat kelas sesuai penugasan tahun ajaran terkait.
- Cegah enrolmen aktif yang bertentangan jika aturan sekolah hanya mengizinkan satu rombel aktif per tahun.

Periksa `TAHUN_AJARAN_AKTIF` dalam konfigurasi.

Jika saat ini masih hardcoded, evaluasi solusi yang paling ringan dan aman.

Tidak wajib membuat modul kalender akademik kompleks.

Gunakan model data existing jika sudah memadai.

Jangan merombak seluruh database hanya untuk menyelesaikan masalah ini.

---

# 16. TAHAP 12 — DATABASE INTEGRITY DAN MIGRASI

Sebelum mengubah database:

1. Baca schema aktif.
2. Baca migrasi existing.
3. Bandingkan dengan dokumentasi.
4. Identifikasi tabel yang sudah ada.
5. Identifikasi relasi yang kurang.
6. Periksa data existing.
7. Buat backup apabila akses database tersedia.

DILARANG:

- DROP DATABASE.
- TRUNCATE data existing.
- Reset akun.
- Menghapus data pengguna.
- Mengganti primary key sembarangan.
- Menghapus foreign key untuk menyembunyikan error.
- Mengubah schema dengan cara yang menghilangkan data.

Gunakan migrasi incremental.

Prioritaskan:

- Konsistensi foreign key.
- Unique constraint yang benar.
- Index untuk pencarian.
- Integritas relasi kelas-siswa.
- Integritas guru-mapel.
- Integritas tugas-pengumpulan.
- Integritas presensi.
- Integritas tahun ajaran.

Pastikan setiap perubahan kompatibel dengan source code yang diperbaiki.

Jika memungkinkan, migrasi harus aman dijalankan ulang atau mendeteksi perubahan yang sudah diterapkan.

Jangan otomatis mengeksekusi migrasi destruktif.

Sediakan SQL dan instruksi rollback atau pemulihan jika relevan.

---

# 17. TAHAP 13 — KEAMANAN UPLOAD DAN DOWNLOAD

File upload adalah fitur penting dan harus dilindungi.

Validasi:

- Ukuran file.
- Ekstensi.
- MIME type.
- Error upload.
- Nama file.
- Hak akses pengguna.

Gunakan nama file tersimpan yang aman dan unik.

Cegah eksekusi PHP atau script lain dari direktori upload.

Jika memungkinkan, simpan file di luar public web root dan layani download melalui endpoint yang memeriksa izin.

Jika harus memakai direktori existing di dalam web root, terapkan perlindungan server yang sesuai untuk XAMPP/Apache.

## Aturan akses

Siswa hanya dapat mengunduh:

- Materi dari kelasnya.
- File tugas yang ditujukan kepadanya.
- Jawabannya sendiri.

Guru hanya dapat mengakses file pengumpulan dari kelas/tugas yang diampunya.

Jangan mengandalkan URL sulit ditebak sebagai mekanisme keamanan.

## Konsistensi file dan database

Jika upload file berhasil tetapi INSERT database gagal:

- Bersihkan file yang tidak terpakai.
- Jangan membuat referensi database yang rusak.

Jika proses penggantian file gagal:

- Jangan menghilangkan file lama yang masih digunakan.

Lakukan validasi serta penanganan error yang tepat.

---

# 18. TAHAP 14 — EDGE CASE DAN VALIDASI LOGIKA

Uji kondisi yang sering terlewat.

## Autentikasi

- Password salah.
- Akun tidak aktif.
- Session kedaluwarsa.
- Pengguna mengakses halaman tanpa login.
- Siswa membuka URL guru.
- Guru membuka data kelas guru lain.

## Kelas

- Siswa belum ditempatkan ke kelas.
- Guru belum mempunyai penugasan.
- Kelas belum mempunyai mata pelajaran.
- Siswa sudah pindah kelas.
- Tahun ajaran berubah.

## Materi

- Materi belum terpublikasi.
- Materi tanpa file.
- File materi tidak tersedia.
- Guru mengedit materi.
- Guru menghapus materi.

## Tugas

- Tugas masih draft.
- Tugas terpublikasi.
- Deadline belum lewat.
- Deadline sudah lewat.
- Siswa belum mengumpulkan.
- Siswa sudah mengumpulkan.
- Siswa mencoba mengumpulkan dua kali.
- Siswa mencoba mengakses tugas kelas lain.
- Guru belum memberi nilai.
- Guru memberi nilai 0.
- Guru memberi nilai penuh.
- Guru memperbarui nilai.

## Presensi

- Guru belum menyimpan presensi.
- Siswa tidak mempunyai catatan kehadiran.
- Guru menyimpan presensi yang sudah ada.
- Dua sesi pada hari yang sama jika didukung.
- Guru mencoba mengisi kelas lain.
- Status hadir/sakit/izin/alfa dihitung benar.

## Dashboard

- Belum ada tugas.
- Belum ada nilai.
- Belum ada presensi.
- Belum ada materi.
- Tidak ada data pada grafik.
- Terdapat nilai 0.
- Ada banyak siswa dan kelas.
- Tidak ada pembagian dengan nol.

Setiap edge case harus menghasilkan respons yang aman dan jelas.

---

# 19. TAHAP 15 — END-TO-END BUSINESS PROCESS TESTING

Jangan hanya menguji setiap halaman secara terpisah.

Lakukan simulasi proses bisnis utuh.

## SKENARIO A — ADMIN MENYIAPKAN PEMBELAJARAN

1. Admin login.
2. Admin membuat atau memilih kelas uji.
3. Admin membuat atau memilih mata pelajaran.
4. Admin menambahkan atau memilih akun guru uji.
5. Admin menempatkan siswa uji ke kelas.
6. Admin menugaskan guru mengajar.
7. Sistem menyimpan semua relasi.

Hasil yang diharapkan:

Guru memperoleh kelas tersebut dan siswa memperoleh mata pelajaran yang benar.

## SKENARIO B — GURU MEMBERIKAN MATERI

1. Guru login.
2. Guru melihat kelas yang diampu.
3. Guru membuat materi.
4. Guru mengunggah file.
5. Guru menerbitkan materi.
6. Siswa login.
7. Siswa membuka mata pelajaran.
8. Materi tampil dan dapat diunduh.

Hasil yang diharapkan:

Materi hanya muncul untuk siswa yang berhak.

## SKENARIO C — SIKLUS TUGAS DAN NILAI

1. Guru membuat tugas.
2. Guru menerbitkan tugas.
3. Siswa melihat tugas.
4. Siswa mengunggah jawaban.
5. Sistem mencatat waktu pengumpulan.
6. Guru melihat jawaban.
7. Guru memberikan nilai.
8. Guru menyimpan feedback.
9. Siswa membuka hasil.
10. Dashboard memperbarui statistik.

Hasil yang diharapkan:

Pengumpulan, penilaian, dan dashboard menunjukkan data yang konsisten.

## SKENARIO D — PRESENSI

1. Guru memilih kelas.
2. Guru memilih tanggal/sesi.
3. Guru mengisi status siswa.
4. Guru menyimpan.
5. Sistem menyimpan tanpa duplikasi.
6. Siswa melihat riwayat.
7. Statistik kehadiran diperbarui.

Hasil yang diharapkan:

Jumlah dan persentase kehadiran dihitung dari catatan yang valid.

## SKENARIO E — PERGANTIAN TAHUN AJARAN

1. Siapkan data kelas tahun sebelumnya.
2. Siapkan data kelas tahun aktif.
3. Hubungkan siswa sesuai enrolmen.
4. Periksa penugasan guru.
5. Periksa dashboard.
6. Periksa riwayat.

Hasil yang diharapkan:

Data tahun lama tidak hilang dan tidak bercampur dengan data tahun berjalan.

## SKENARIO F — SECURITY TESTING

1. Siswa mencoba membuka halaman guru.
2. Siswa mencoba mengunduh jawaban siswa lain.
3. Guru mencoba mengubah nilai kelas lain.
4. Pengguna mengubah parameter ID pada URL.
5. Pengguna mengirim request tanpa CSRF.
6. Pengguna mengunggah file tidak diizinkan.

Hasil yang diharapkan:

Seluruh akses tidak sah ditolak oleh backend.

---

# 20. TAHAP 16 — QUALITY ASSURANCE UI

Setelah fungsi inti selesai, periksa kesesuaian desain.

Bandingkan setiap halaman dengan screenshot PNG.

Periksa:

- Navbar.
- Sidebar.
- Card.
- Warna.
- Font.
- Spacing.
- Table.
- Badge.
- Button.
- Modal.
- Form.
- Grafik.
- Ikon.
- Alignment.
- Responsive layout.

Jika tampilan sudah sesuai, jangan diubah.

Jika terdapat perbedaan, ubah bagian yang tidak sesuai saja.

Jangan merombak file CSS atau seluruh halaman hanya karena satu komponen bermasalah.

Bila lingkungan memungkinkan, ambil screenshot aplikasi pada viewport yang sama dengan PNG desain.

Catat hasil perbandingan dalam laporan.

Jika browser atau akses desain tidak tersedia, tandai pemeriksaan visual sebagai belum lengkap, bukan lulus.

---

# 21. TAHAP 17 — PRIORITAS IMPLEMENTASI

Gunakan urutan prioritas berikut.

## P0 — WAJIB

1. Keamanan autentikasi dan otorisasi.
2. Pemisahan hak akses Admin dan Guru.
3. Pengelolaan akun.
4. Pengelolaan kelas.
5. Penempatan siswa.
6. Penugasan guru ke mata pelajaran.
7. Integritas database.
8. Pembatasan akses data.
9. Perbaikan bug yang menghambat proses utama.

## P1 — FITUR INTI LMS

1. Pengelolaan materi lengkap.
2. Pembuatan dan pengelolaan tugas.
3. Pengumpulan tugas.
4. Status pengumpulan.
5. Penilaian dan feedback.
6. Presensi.
7. Pengumuman.
8. Dashboard dinamis.
9. Rekap akademik minimum.
10. Konsistensi tahun ajaran.

## P2 — PENYEMPURNAAN

1. Filter dan pencarian.
2. Pagination.
3. Empty state.
4. Error handling.
5. Notifikasi berhasil/gagal.
6. Perbaikan responsivitas.
7. Kesempurnaan visual.
8. Optimasi query.
9. Dokumentasi lengkap.

Jangan mengerjakan fitur tambahan kosmetik sebelum proses bisnis utama berfungsi.

---

# 22. FITUR YANG TIDAK BOLEH DIKARANG

Jangan mengimplementasikan fitur berikut tanpa kebutuhan jelas:

- AI tutor.
- Video conference.
- SSO Belajar.id.
- Sinkronisasi rapor eksternal.
- Integrasi WhatsApp.
- QR presensi otomatis.
- Payment gateway.
- Token ujian pihak ketiga.
- Sistem ujian online kompleks.
- Gamification.
- Rekomendasi belajar berbasis AI.

Jika fitur tersebut muncul dalam desain sebagai teks atau placeholder, jangan mengklaim sudah berfungsi.

Dokumentasikan sebagai integrasi potensial atau fitur di luar cakupan.

Fokus pada proses LMS yang sudah dirancang.

---

# 23. ATURAN EKSEKUSI OTOMATIS

Saya ingin seluruh pekerjaan dijalankan melalui satu master prompt ini.

JANGAN berhenti setelah membuat audit.

JANGAN berhenti setelah membuat ERD.

JANGAN berhenti setelah menyelesaikan login.

JANGAN berhenti setelah membuat database.

JANGAN berhenti setelah menyelesaikan fitur siswa.

JANGAN berhenti setelah menyelesaikan fitur guru.

Lanjutkan secara berurutan sampai seluruh fitur inti yang dapat dikerjakan selesai.

Setelah menyelesaikan setiap modul:

1. Periksa sintaks.
2. Periksa koneksi database.
3. Periksa hak akses.
4. Periksa aturan bisnis.
5. Uji alur normal.
6. Uji alur gagal.
7. Uji integrasi.
8. Perbaiki bug.
9. Dokumentasikan progres.
10. Lanjutkan ke modul berikutnya.

Kerjakan perubahan secara incremental.

Jangan melakukan rewrite massal.

Jangan menghapus data existing.

Jangan menggunakan solusi palsu atau data hardcoded untuk menyatakan fitur sudah selesai.

Jika suatu pekerjaan memerlukan keputusan bisnis yang belum ditentukan, pilih implementasi paling sederhana yang aman hanya jika tidak bertentangan dengan kebutuhan.

Catat asumsi secara eksplisit.

Jika keputusan berisiko merusak data, mengubah hak akses secara besar, atau menciptakan aturan sekolah yang tidak diketahui, jangan mengambil keputusan sembarangan. Tunda hanya bagian yang terhalang, kemudian lanjutkan pekerjaan lain.

Jika sesi atau konteks model mendekati batasnya, simpan progres secara jelas agar dapat dilanjutkan tanpa mengulang pekerjaan.

---

# 24. DOKUMENTASI WAJIB

Buat atau perbarui dokumen:

## AUDIT_EDULMS.md

- Kondisi awal.
- Bug.
- Fitur existing.
- Fitur belum selesai.
- Prioritas perbaikan.

## BUSINESS_LOGIC_ANALYSIS.md

- Alur Admin.
- Alur Guru.
- Alur Siswa.
- Relasi data.
- Aturan bisnis.
- Edge case.
- Masalah yang ditemukan.
- Solusi dan asumsi yang digunakan.

## DATABASE_CHANGES.md

- Perubahan tabel.
- Perubahan relasi.
- Foreign key.
- Index.
- Migrasi.
- Kompatibilitas data existing.

## IMPLEMENTASI_EDULMS.md

- Modul selesai.
- Modul belum selesai.
- File diubah.
- Fungsi ditambahkan.
- Bug diperbaiki.

## TESTING_EDULMS.md

Gunakan status:

- PASS
- FAIL
- BELUM DIUJI
- BLOCKED

Cantumkan langkah pengujian dan bukti hasil.

## PROGRESS_EDULMS.md

Catat:

- Tahapan selesai.
- Tahapan sedang dikerjakan.
- Tahapan belum dikerjakan.
- Kendala.
- Perubahan terakhir.
- Langkah lanjutan.

## README.md

Jelaskan:

- Cara menjalankan di XAMPP.
- Konfigurasi PHP.
- Setup database.
- Cara menjalankan migrasi.
- Role dan hak akses.
- Cara menguji alur bisnis.
- Ketergantungan aplikasi.
- Keterbatasan yang masih ada.

Jangan menyimpan password, token, secret key, atau kredensial pribadi dalam dokumentasi.

---

# 25. DEFINITION OF DONE

EduLMS dianggap selesai secara fungsional jika:

1. PHP Native tetap digunakan.
2. Stack existing tidak diganti.
3. UI existing tetap dipertahankan.
4. Database existing aman.
5. Role pengguna benar.
6. Admin dapat menyiapkan data akademik.
7. Siswa dapat ditempatkan ke kelas.
8. Guru dapat ditugaskan ke mata pelajaran.
9. Guru dapat mengelola materi.
10. Guru dapat membuat tugas.
11. Siswa dapat mengumpulkan tugas.
12. Guru dapat menilai.
13. Siswa dapat melihat hasil penilaian.
14. Guru dapat mengisi presensi.
15. Siswa dapat melihat riwayat presensi.
16. Pengumuman dapat dikelola.
17. Dashboard menggunakan data nyata.
18. Tahun ajaran tidak mencampur histori akademik.
19. Akses tidak sah ditolak.
20. Download file dilindungi.
21. Navigasi inti berfungsi.
22. Proses bisnis diuji end-to-end.
23. Dokumentasi tersedia.
24. Bug kritis tidak tersisa.
25. Fitur yang belum bisa diselesaikan dilaporkan secara jujur.

Jangan menyatakan proyek 100% selesai hanya karena seluruh halaman dapat dibuka.

Fitur harus mempunyai proses bisnis yang benar dan menghasilkan data yang konsisten.

---

# 26. INSTRUKSI FINAL

Sekarang mulai audit dan pengembangan EduLMS menggunakan source code, database, desain PNG, Figma, dan dokumen alur sistem yang tersedia dalam workspace.

Gunakan prinsip:

**ADMIN MENYIAPKAN DATA → GURU MENGELOLA PEMBELAJARAN → SISWA MENGIKUTI PEMBELAJARAN → GURU MENILAI → SISTEM MEMPERBARUI REKAP DAN DASHBOARD.**

Semua tahapan harus saling terhubung.

JANGAN membuat ulang aplikasi dari nol.

JANGAN mengganti PHP Native.

JANGAN mengganti framework atau teknologi existing.

JANGAN melakukan redesign UI.

JANGAN merusak database.

JANGAN menambahkan fitur besar yang tidak dibutuhkan.

JANGAN hanya mengimplementasikan CRUD tanpa memvalidasi logika bisnis.

JANGAN berhenti setelah audit jika pekerjaan dapat dilanjutkan.

JANGAN mengklaim pengujian berhasil tanpa benar-benar mengujinya.

**Target akhir bukan sekadar EduLMS terlihat selesai, tetapi EduLMS benar-benar bekerja sebagai satu sistem pembelajaran yang terintegrasi.**

Mulai dari audit kondisi terbaru, validasi alur bisnis, kemudian implementasikan seluruh perbaikan secara bertahap sampai selesai.

**PRIORITAS FINAL:**

BUSINESS LOGIC CORRECTNESS + FUNCTIONAL COMPLETENESS + DATABASE INTEGRITY + SECURITY + FIGMA UI FIDELITY.

Mulai sekarang.