-- ============================================
-- EDULMS DATABASE - COMPLETE SCHEMA
-- Learning Management System
-- Version: 1.0
-- Date: 2026-10-12
-- ============================================

-- Create database if not exists
CREATE DATABASE IF NOT EXISTS edulms_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE edulms_db;

-- Disable foreign key checks for clean installation
SET FOREIGN_KEY_CHECKS = 0;

-- ============================================
-- TABLE: users
-- User authentication and profiles
-- ============================================
DROP TABLE IF EXISTS users;
CREATE TABLE users (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    nama_lengkap VARCHAR(255) NOT NULL,
    email VARCHAR(255) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    role ENUM('siswa', 'guru', 'admin') NOT NULL DEFAULT 'siswa',
    nisn VARCHAR(20) NULL UNIQUE COMMENT 'Nomor Induk Siswa Nasional',
    nip VARCHAR(30) NULL UNIQUE COMMENT 'Nomor Induk Pegawai',
    foto_profil VARCHAR(255) NULL,
    is_active TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_email (email),
    INDEX idx_role (role),
    INDEX idx_nisn (nisn),
    INDEX idx_nip (nip)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- TABLE: kelas
-- Classes/Rombongan Belajar
-- ============================================
DROP TABLE IF EXISTS kelas;
CREATE TABLE kelas (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    nama_kelas VARCHAR(50) NOT NULL COMMENT 'Contoh: XI MIPA 1',
    tingkat INT NOT NULL COMMENT '10, 11, atau 12',
    jurusan VARCHAR(50) NULL COMMENT 'MIPA, IPS, Bahasa, dll',
    tahun_ajaran VARCHAR(20) NOT NULL COMMENT '2026/2027',
    wali_kelas_id INT UNSIGNED NULL,
    kapasitas INT DEFAULT 36,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (wali_kelas_id) REFERENCES users(id) ON DELETE SET NULL,
    INDEX idx_tahun (tahun_ajaran),
    INDEX idx_tingkat (tingkat)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- TABLE: siswa_kelas
-- Student-Class relationship (many-to-many)
-- ============================================
DROP TABLE IF EXISTS siswa_kelas;
CREATE TABLE siswa_kelas (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    siswa_id INT UNSIGNED NOT NULL,
    kelas_id INT UNSIGNED NOT NULL,
    tahun_ajaran VARCHAR(20) NOT NULL,
    status ENUM('aktif', 'pindah', 'lulus', 'keluar') DEFAULT 'aktif',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (siswa_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (kelas_id) REFERENCES kelas(id) ON DELETE CASCADE,
    UNIQUE KEY unique_siswa_kelas (siswa_id, kelas_id, tahun_ajaran),
    INDEX idx_siswa (siswa_id),
    INDEX idx_kelas (kelas_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- TABLE: mata_pelajaran
-- Subjects
-- ============================================
DROP TABLE IF EXISTS mata_pelajaran;
CREATE TABLE mata_pelajaran (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    kode_mapel VARCHAR(20) NOT NULL UNIQUE COMMENT 'MAT-01, FIS-02, dll',
    nama_mapel VARCHAR(255) NOT NULL,
    deskripsi TEXT NULL,
    kategori ENUM('wajib', 'peminatan', 'lintas_minat') DEFAULT 'wajib',
    icon_emoji VARCHAR(10) NULL COMMENT 'Emoji untuk display',
    color_class VARCHAR(50) NULL COMMENT 'CSS class untuk warna',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_kode (kode_mapel),
    INDEX idx_kategori (kategori)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- TABLE: guru_mapel
-- Teacher-Subject-Class assignment
-- ============================================
DROP TABLE IF EXISTS guru_mapel;
CREATE TABLE guru_mapel (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    guru_id INT UNSIGNED NOT NULL,
    mapel_id INT UNSIGNED NOT NULL,
    kelas_id INT UNSIGNED NOT NULL,
    tahun_ajaran VARCHAR(20) NOT NULL,
    hari VARCHAR(20) NULL COMMENT 'Senin, Selasa, dll',
    jam_mulai TIME NULL,
    jam_selesai TIME NULL,
    ruang VARCHAR(50) NULL,
    is_active TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (guru_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (mapel_id) REFERENCES mata_pelajaran(id) ON DELETE CASCADE,
    FOREIGN KEY (kelas_id) REFERENCES kelas(id) ON DELETE CASCADE,
    UNIQUE KEY unique_guru_mapel_kelas (guru_id, mapel_id, kelas_id, tahun_ajaran),
    INDEX idx_guru (guru_id),
    INDEX idx_mapel (mapel_id),
    INDEX idx_kelas (kelas_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- TABLE: materi
-- Learning materials
-- ============================================
DROP TABLE IF EXISTS materi;
CREATE TABLE materi (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    guru_mapel_id INT UNSIGNED NOT NULL,
    judul_materi VARCHAR(255) NOT NULL,
    deskripsi TEXT NULL,
    konten TEXT NULL COMMENT 'Konten teks/HTML',
    file_path VARCHAR(255) NULL COMMENT 'Path ke file PDF/PPT',
    file_name VARCHAR(255) NULL,
    file_size INT NULL COMMENT 'Size dalam bytes',
    video_url VARCHAR(500) NULL COMMENT 'Link YouTube, dll',
    urutan_modul INT DEFAULT 1,
    is_published TINYINT(1) DEFAULT 1,
    published_at TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (guru_mapel_id) REFERENCES guru_mapel(id) ON DELETE CASCADE,
    INDEX idx_guru_mapel (guru_mapel_id),
    INDEX idx_published (is_published)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- TABLE: tugas
-- Assignments
-- ============================================
DROP TABLE IF EXISTS tugas;
CREATE TABLE tugas (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    guru_mapel_id INT UNSIGNED NOT NULL,
    judul_tugas VARCHAR(255) NOT NULL,
    deskripsi TEXT NULL,
    instruksi TEXT NULL,
    file_path VARCHAR(255) NULL COMMENT 'File soal/template',
    file_name VARCHAR(255) NULL,
    batas_pengumpulan DATETIME NOT NULL,
    format_file VARCHAR(100) NULL COMMENT 'PDF, DOCX, ZIP, dll',
    max_file_size INT DEFAULT 10485760 COMMENT 'Default 10MB dalam bytes',
    bobot_nilai INT DEFAULT 100,
    is_published TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (guru_mapel_id) REFERENCES guru_mapel(id) ON DELETE CASCADE,
    INDEX idx_guru_mapel (guru_mapel_id),
    INDEX idx_deadline (batas_pengumpulan),
    INDEX idx_published (is_published)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- TABLE: pengumpulan_tugas
-- Assignment submissions
-- ============================================
DROP TABLE IF EXISTS pengumpulan_tugas;
CREATE TABLE pengumpulan_tugas (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    tugas_id INT UNSIGNED NOT NULL,
    siswa_id INT UNSIGNED NOT NULL,
    file_path VARCHAR(255) NOT NULL,
    file_name VARCHAR(255) NOT NULL,
    file_size INT NOT NULL,
    catatan TEXT NULL COMMENT 'Catatan dari siswa',
    tanggal_kumpul TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    status ENUM('tepat_waktu', 'terlambat') DEFAULT 'tepat_waktu',
    nilai DECIMAL(5,2) NULL COMMENT 'Nilai 0-100',
    feedback TEXT NULL COMMENT 'Feedback dari guru',
    dinilai_oleh INT UNSIGNED NULL,
    dinilai_pada TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (tugas_id) REFERENCES tugas(id) ON DELETE CASCADE,
    FOREIGN KEY (siswa_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (dinilai_oleh) REFERENCES users(id) ON DELETE SET NULL,
    UNIQUE KEY unique_tugas_siswa (tugas_id, siswa_id),
    INDEX idx_tugas (tugas_id),
    INDEX idx_siswa (siswa_id),
    INDEX idx_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- TABLE: presensi
-- Attendance records
-- ============================================
DROP TABLE IF EXISTS presensi;
CREATE TABLE presensi (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    guru_mapel_id INT UNSIGNED NOT NULL,
    siswa_id INT UNSIGNED NOT NULL,
    tanggal_sesi DATE NOT NULL,
    jam_sesi TIME NULL,
    status_kehadiran ENUM('hadir', 'sakit', 'izin', 'alfa') NOT NULL DEFAULT 'hadir',
    jam_kedatangan TIME NULL,
    catatan TEXT NULL COMMENT 'Keterangan/surat dokter',
    dicatat_oleh INT UNSIGNED NOT NULL COMMENT 'ID guru yang input',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (guru_mapel_id) REFERENCES guru_mapel(id) ON DELETE CASCADE,
    FOREIGN KEY (siswa_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (dicatat_oleh) REFERENCES users(id) ON DELETE CASCADE,
    UNIQUE KEY unique_presensi (guru_mapel_id, siswa_id, tanggal_sesi),
    INDEX idx_guru_mapel (guru_mapel_id),
    INDEX idx_siswa (siswa_id),
    INDEX idx_tanggal (tanggal_sesi),
    INDEX idx_status (status_kehadiran)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- TABLE: pengumuman
-- Announcements
-- ============================================
DROP TABLE IF EXISTS pengumuman;
CREATE TABLE pengumuman (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    pembuat_id INT UNSIGNED NOT NULL,
    judul_pengumuman VARCHAR(255) NOT NULL,
    isi_pengumuman TEXT NOT NULL,
    kategori ENUM('penting', 'akademik', 'agenda', 'kegiatan', 'umum') DEFAULT 'umum',
    target_role ENUM('semua', 'siswa', 'guru') DEFAULT 'semua',
    is_published TINYINT(1) DEFAULT 1,
    tanggal_terbit TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (pembuat_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_pembuat (pembuat_id),
    INDEX idx_kategori (kategori),
    INDEX idx_published (is_published),
    INDEX idx_tanggal (tanggal_terbit)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- TABLE: statistik_siswa
-- Student statistics cache (for dashboard)
-- ============================================
DROP TABLE IF EXISTS statistik_siswa;
CREATE TABLE statistik_siswa (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    siswa_id INT UNSIGNED NOT NULL,
    tahun_ajaran VARCHAR(20) NOT NULL,
    rata_rata_nilai DECIMAL(5,2) DEFAULT 0,
    persentase_kehadiran DECIMAL(5,2) DEFAULT 0,
    jumlah_tugas_selesai INT DEFAULT 0,
    jumlah_tugas_total INT DEFAULT 0,
    waktu_belajar_jam DECIMAL(6,2) DEFAULT 0 COMMENT 'Total jam belajar',
    last_calculated TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (siswa_id) REFERENCES users(id) ON DELETE CASCADE,
    UNIQUE KEY unique_siswa_tahun (siswa_id, tahun_ajaran),
    INDEX idx_siswa (siswa_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Re-enable foreign key checks
SET FOREIGN_KEY_CHECKS = 1;

-- ============================================
-- SEED DATA - USERS
-- ============================================

-- Admin account
INSERT INTO users (nama_lengkap, email, password, role) VALUES
('Administrator EduLMS', 'admin@edulms.sch.id', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin');
-- Password: password

-- Guru accounts
INSERT INTO users (nama_lengkap, email, password, role, nip) VALUES
('Ahmad Dahlan', 'ahmad.dahlan@edulms.sch.id', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'guru', '198503152010011001'),
('Siti Nurhaliza', 'siti.nurhaliza@edulms.sch.id', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'guru', '199001102015032001'),
('Hendra Wijaya', 'hendra.wijaya@edulms.sch.id', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'guru', '198705202012011002'),
('Rina Anggraini', 'rina.anggraini@edulms.sch.id', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'guru', '199203152016032001'),
('Agus Prabowo', 'agus.prabowo@edulms.sch.id', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'guru', '198808102013011001'),
('Sulastri', 'sulastri@edulms.sch.id', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'guru', '197512052005032001');
-- All passwords: password

-- Siswa accounts
INSERT INTO users (nama_lengkap, email, password, role, nisn) VALUES
('Budi Santoso', 'budi.santoso@student.edulms.sch.id', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'siswa', '0098765432'),
('Aisyah Putri', 'aisyah.putri@student.edulms.sch.id', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'siswa', '0098765433'),
('Rizky Pratama', 'rizky.pratama@student.edulms.sch.id', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'siswa', '0098765434'),
('Dewi Lestari', 'dewi.lestari@student.edulms.sch.id', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'siswa', '0098765435'),
('Fajar Ramadhan', 'fajar.ramadhan@student.edulms.sch.id', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'siswa', '0098765436');
-- All passwords: password

-- ============================================
-- SEED DATA - KELAS
-- ============================================
INSERT INTO kelas (nama_kelas, tingkat, jurusan, tahun_ajaran, wali_kelas_id) VALUES
('XI MIPA 1', 11, 'MIPA', '2026/2027', 2),
('XI MIPA 2', 11, 'MIPA', '2026/2027', 3),
('XI MIPA 3', 11, 'MIPA', '2026/2027', 4),
('X MIPA 1', 10, 'MIPA', '2026/2027', 5),
('X MIPA 2', 10, 'MIPA', '2026/2027', 6);

-- ============================================
-- SEED DATA - SISWA_KELAS
-- ============================================
INSERT INTO siswa_kelas (siswa_id, kelas_id, tahun_ajaran, status) VALUES
(7, 1, '2026/2027', 'aktif'),  -- Budi di XI MIPA 1
(8, 1, '2026/2027', 'aktif'),  -- Aisyah di XI MIPA 1
(9, 1, '2026/2027', 'aktif'),  -- Rizky di XI MIPA 1
(10, 2, '2026/2027', 'aktif'), -- Dewi di XI MIPA 2
(11, 2, '2026/2027', 'aktif'); -- Fajar di XI MIPA 2

-- ============================================
-- SEED DATA - MATA_PELAJARAN
-- ============================================
INSERT INTO mata_pelajaran (kode_mapel, nama_mapel, deskripsi, kategori, icon_emoji, color_class) VALUES
('MAT-01', 'Matematika Wajib', 'Matematika tingkat SMA', 'wajib', '📊', 'teal'),
('IND-01', 'Bahasa Indonesia', 'Bahasa Indonesia tingkat SMA', 'wajib', '📖', 'blue'),
('FIS-01', 'Fisika Terapan', 'Fisika untuk MIPA', 'peminatan', '⚗️', 'indigo'),
('TIK-01', 'Pemrograman Web & TIK', 'Teknologi Informasi dan Komunikasi', 'peminatan', '💻', 'emerald'),
('ING-01', 'Bahasa Inggris', 'English Language', 'wajib', '🗣️', 'amber'),
('BIO-01', 'Biologi', 'Biologi untuk MIPA', 'peminatan', '🧬', 'rose');

-- ============================================
-- SEED DATA - GURU_MAPEL (Assignments)
-- ============================================
INSERT INTO guru_mapel (guru_id, mapel_id, kelas_id, tahun_ajaran, hari, jam_mulai, jam_selesai, ruang) VALUES
-- Ahmad Dahlan mengajar Matematika
(2, 1, 1, '2026/2027', 'Senin', '07:30:00', '09:00:00', 'Lab Komputer 2'),
(2, 1, 2, '2026/2027', 'Selasa', '09:30:00', '11:00:00', 'Ruang Kelas XI MIPA 2'),
(2, 1, 3, '2026/2027', 'Rabu', '13:00:00', '14:30:00', 'Ruang Kelas XI MIPA 3'),

-- Siti Nurhaliza mengajar Bahasa Indonesia
(3, 2, 1, '2026/2027', 'Kamis', '07:30:00', '09:00:00', 'Ruang Kelas XI MIPA 1'),
(3, 2, 2, '2026/2027', 'Jumat', '09:30:00', '11:00:00', 'Ruang Kelas XI MIPA 2'),

-- Hendra Wijaya mengajar Fisika
(4, 3, 1, '2026/2027', 'Selasa', '07:30:00', '09:00:00', 'Lab Fisika'),
(4, 3, 3, '2026/2027', 'Rabu', '09:30:00', '11:00:00', 'Ruang Kelas XI MIPA 3'),

-- Rina Anggraini mengajar Pemrograman Web
(5, 4, 1, '2026/2027', 'Jumat', '13:00:00', '15:00:00', 'Lab Komputer 1'),

-- Agus Prabowo mengajar Bahasa Inggris
(6, 5, 1, '2026/2027', 'Rabu', '07:30:00', '09:00:00', 'Ruang Kelas XI MIPA 1'),

-- Sulastri mengajar Biologi
(7, 6, 1, '2026/2027', 'Kamis', '09:30:00', '11:00:00', 'Lab Biologi');

-- ============================================
-- SEED DATA - MATERI
-- ============================================
INSERT INTO materi (guru_mapel_id, judul_materi, deskripsi, urutan_modul, is_published) VALUES
(1, 'Determinan & Invers Matriks', 'Materi tentang operasi matriks lanjutan', 1, 1),
(1, 'Integral Kalkulus Dasar', 'Pengenalan integral tak tentu dan tentu', 2, 1),
(3, 'Kinematika Gerak Lurus', 'Gerak lurus beraturan dan berubah beraturan', 1, 1),
(4, 'Desain UI Interaktif dengan Tailwind CSS', 'Membuat tampilan modern dengan Tailwind', 1, 1);

-- ============================================
-- SEED DATA - TUGAS
-- ============================================
INSERT INTO tugas (guru_mapel_id, judul_tugas, deskripsi, batas_pengumpulan, format_file, bobot_nilai) VALUES
(1, 'Latihan Soal Matriks & Vektor', 'Kerjakan 15 soal tentang operasi matriks', '2024-05-18 23:59:00', 'PDF', 100),
(3, 'Laporan Praktikum Kinematika', 'Laporan hasil eksperimen gerak lurus', '2024-05-25 23:59:00', 'PDF', 100),
(4, 'Membuat Halaman Web Sederhana', 'Buat landing page dengan HTML, CSS, dan Tailwind', '2024-05-20 17:00:00', 'ZIP', 100),
(2, 'Ringkasan Teks Argumentasi', 'Buat ringkasan dari artikel yang diberikan', '2024-05-22 21:00:00', 'DOCX, PDF', 100),
(5, 'Essay Writing Narrative', 'Tulis essay narrative 500 kata', '2024-05-28 23:59:00', 'DOCX, PDF', 100);

-- ============================================
-- SEED DATA - PENGUMPULAN_TUGAS (Sample)
-- ============================================
INSERT INTO pengumpulan_tugas (tugas_id, siswa_id, file_path, file_name, file_size, status, nilai, feedback, dinilai_oleh, dinilai_pada) VALUES
(5, 7, 'uploads/pengumpulan/essay_budi_20240526.pdf', 'essay_narrative_budi.pdf', 524288, 'tepat_waktu', 90.00, 'Tulisan bagus, struktur narrative jelas. Perhatikan tanda baca.', 6, '2024-05-27 10:30:00');

-- ============================================
-- SEED DATA - PRESENSI (Sample)
-- ============================================
INSERT INTO presensi (guru_mapel_id, siswa_id, tanggal_sesi, jam_sesi, status_kehadiran, jam_kedatangan, dicatat_oleh) VALUES
(1, 7, '2024-05-13', '07:30:00', 'hadir', '07:25:00', 2),
(1, 8, '2024-05-13', '07:30:00', 'hadir', '07:28:00', 2),
(1, 9, '2024-05-13', '07:30:00', 'sakit', NULL, 2),
(1, 7, '2024-05-14', '07:30:00', 'hadir', '07:20:00', 2),
(1, 8, '2024-05-14', '07:30:00', 'hadir', '07:30:00', 2);

-- ============================================
-- SEED DATA - PENGUMUMAN
-- ============================================
INSERT INTO pengumuman (pembuat_id, judul_pengumuman, isi_pengumuman, kategori, target_role) VALUES
(1, 'Jadwal Penilaian Tengah Semester (PTS) Genap', 'PTS Semester Genap akan dilaksanakan secara serentak mulai tanggal 3 Juni 2024 di ruang kelas masing-masing. Harap seluruh siswa mempersiapkan kartu ujian digital serta memverifikasi kelengkapan administrasi akademik sebelum tanggal pelaksanaan.', 'penting', 'semua'),
(1, 'Libur Nasional & Kegiatan Belajar Mandiri', 'Sehubungan dengan hari libur nasional pada Kamis mendatang, kegiatan belajar tatap muka ditiadakan dan dialihkan secara daring mandiri melalui portal EduLMS. Materi tugas terstruktur dapat diakses melalui tab Mata Pelajaran.', 'agenda', 'semua'),
(1, 'Pendaftaran Ekstrakurikuler Robotik & Coding', 'Pendaftaran anggota baru club pemrograman dan robotik dibuka hingga akhir pekan ini melalui formulir online ruang kesiswaan. Kuota terbatas untuk 30 peserta angkatan pertama program semester gasal.', 'kegiatan', 'siswa');

-- ============================================
-- SEED DATA - STATISTIK_SISWA
-- ============================================
INSERT INTO statistik_siswa (siswa_id, tahun_ajaran, rata_rata_nilai, persentase_kehadiran, jumlah_tugas_selesai, jumlah_tugas_total, waktu_belajar_jam) VALUES
(7, '2026/2027', 88.50, 98.00, 18, 20, 14.50);

-- ============================================
-- END OF SCHEMA
-- ============================================
