-- ============================================================
-- EDULMS - SKRIP MIGRASI INKREMENTAL (TANPA DROP/TRUNCATE)
-- Tanggal: 2026-10-13
-- Fungsi : Menyelesaikan struktur DB yang belum lengkap:
--          1. Menambah kolom yang kurang pada tabel `users`
--             (nisn, nip, foto_profil, is_active, updated_at)
--          2. Membuat 11 tabel yang belum ada (CREATE IF NOT EXISTS)
--          3. Menambahkan data awal (guru, kelas, mapel, dll)
--             tanpa menghapus/mengubah data user yang sudah ada
-- Aman dijalankan berulang kali (idempotent).
-- ============================================================

USE edulms_db;

-- ============================================================
-- BAGIAN 1: TAMBAH KOLOM YANG KURANG PADA `users`
-- (MySQL tidak mendukung ADD COLUMN IF NOT EXISTS,
--  jadi dicek dulu lewat information_schema)
-- ============================================================

DROP PROCEDURE IF EXISTS tambah_kolom_jika_kurang;
DELIMITER $$
CREATE PROCEDURE tambah_kolom_jika_kurang(
    IN p_table VARCHAR(64),
    IN p_column VARCHAR(64),
    IN p_definition TEXT
)
BEGIN
    IF NOT EXISTS (
        SELECT 1 FROM information_schema.COLUMNS
        WHERE TABLE_SCHEMA = DATABASE()
          AND TABLE_NAME = p_table
          AND COLUMN_NAME = p_column
    ) THEN
        SET @ddl = CONCAT('ALTER TABLE `', p_table, '` ADD COLUMN `', p_column, '` ', p_definition);
        PREPARE stmt FROM @ddl;
        EXECUTE stmt;
        DEALLOCATE PREPARE stmt;
    END IF;
END$$
DELIMITER ;

-- Kolom login (dipakai auth/process_login.php & includes/auth_check.php)
CALL tambah_kolom_jika_kurang('users', 'nisn',       "VARCHAR(20) NULL UNIQUE COMMENT 'Nomor Induk Siswa Nasional' AFTER role");
CALL tambah_kolom_jika_kurang('users', 'nip',         "VARCHAR(30) NULL UNIQUE COMMENT 'Nomor Induk Pegawai' AFTER nisn");
CALL tambah_kolom_jika_kurang('users', 'foto_profil', "VARCHAR(255) NULL AFTER nip");
CALL tambah_kolom_jika_kurang('users', 'is_active',   "TINYINT(1) NOT NULL DEFAULT 1 AFTER foto_profil");
CALL tambah_kolom_jika_kurang('users', 'updated_at',  "TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP");

DROP PROCEDURE tambah_kolom_jika_kurang;

-- ============================================================
-- BAGIAN 2: BUAT 11 TABEL YANG BELUM ADA
-- CATATAN: kolom ID memakai INT (signed) agar cocok dengan
--          users.id yang signed, supaya FOREIGN KEY valid.
-- ============================================================

CREATE TABLE IF NOT EXISTS kelas (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nama_kelas VARCHAR(50) NOT NULL COMMENT 'Contoh: XI MIPA 1',
    tingkat INT NOT NULL COMMENT '10, 11, atau 12',
    jurusan VARCHAR(50) NULL COMMENT 'MIPA, IPS, Bahasa, dll',
    tahun_ajaran VARCHAR(20) NOT NULL COMMENT '2026/2027',
    wali_kelas_id INT NULL,
    kapasitas INT DEFAULT 36,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (wali_kelas_id) REFERENCES users(id) ON DELETE SET NULL,
    INDEX idx_tahun (tahun_ajaran),
    INDEX idx_tingkat (tingkat)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS siswa_kelas (
    id INT AUTO_INCREMENT PRIMARY KEY,
    siswa_id INT NOT NULL,
    kelas_id INT NOT NULL,
    tahun_ajaran VARCHAR(20) NOT NULL,
    status ENUM('aktif', 'pindah', 'lulus', 'keluar') DEFAULT 'aktif',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (siswa_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (kelas_id) REFERENCES kelas(id) ON DELETE CASCADE,
    UNIQUE KEY unique_siswa_kelas (siswa_id, kelas_id, tahun_ajaran),
    INDEX idx_siswa (siswa_id),
    INDEX idx_kelas (kelas_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS mata_pelajaran (
    id INT AUTO_INCREMENT PRIMARY KEY,
    kode_mapel VARCHAR(20) NOT NULL UNIQUE COMMENT 'MAT-01, FIS-02, dll',
    nama_mapel VARCHAR(255) NOT NULL,
    deskripsi TEXT NULL,
    kategori ENUM('wajib', 'peminatan', 'lintas_minat') DEFAULT 'wajib',
    icon_emoji VARCHAR(10) NULL COMMENT 'Emoji untuk display',
    color_class VARCHAR(50) NULL COMMENT 'CSS class untuk warna',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_kode (kode_mapel),
    INDEX idx_kategori (kategori)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS guru_mapel (
    id INT AUTO_INCREMENT PRIMARY KEY,
    guru_id INT NOT NULL,
    mapel_id INT NOT NULL,
    kelas_id INT NOT NULL,
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

CREATE TABLE IF NOT EXISTS materi (
    id INT AUTO_INCREMENT PRIMARY KEY,
    guru_mapel_id INT NOT NULL,
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
    updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (guru_mapel_id) REFERENCES guru_mapel(id) ON DELETE CASCADE,
    INDEX idx_guru_mapel (guru_mapel_id),
    INDEX idx_published (is_published)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS tugas (
    id INT AUTO_INCREMENT PRIMARY KEY,
    guru_mapel_id INT NOT NULL,
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
    updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (guru_mapel_id) REFERENCES guru_mapel(id) ON DELETE CASCADE,
    INDEX idx_guru_mapel (guru_mapel_id),
    INDEX idx_deadline (batas_pengumpulan),
    INDEX idx_published (is_published)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS pengumpulan_tugas (
    id INT AUTO_INCREMENT PRIMARY KEY,
    tugas_id INT NOT NULL,
    siswa_id INT NOT NULL,
    file_path VARCHAR(255) NOT NULL,
    file_name VARCHAR(255) NOT NULL,
    file_size INT NOT NULL,
    catatan TEXT NULL COMMENT 'Catatan dari siswa',
    tanggal_kumpul TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    status ENUM('tepat_waktu', 'terlambat') DEFAULT 'tepat_waktu',
    nilai DECIMAL(5,2) NULL COMMENT 'Nilai 0-100',
    feedback TEXT NULL COMMENT 'Feedback dari guru',
    dinilai_oleh INT NULL,
    dinilai_pada TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (tugas_id) REFERENCES tugas(id) ON DELETE CASCADE,
    FOREIGN KEY (siswa_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (dinilai_oleh) REFERENCES users(id) ON DELETE SET NULL,
    UNIQUE KEY unique_tugas_siswa (tugas_id, siswa_id),
    INDEX idx_tugas (tugas_id),
    INDEX idx_siswa (siswa_id),
    INDEX idx_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS presensi (
    id INT AUTO_INCREMENT PRIMARY KEY,
    guru_mapel_id INT NOT NULL,
    siswa_id INT NOT NULL,
    tanggal_sesi DATE NOT NULL,
    jam_sesi TIME NULL,
    status_kehadiran ENUM('hadir', 'sakit', 'izin', 'alfa') NOT NULL DEFAULT 'hadir',
    jam_kedatangan TIME NULL,
    catatan TEXT NULL COMMENT 'Keterangan/surat dokter',
    dicatat_oleh INT NOT NULL COMMENT 'ID guru yang input',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (guru_mapel_id) REFERENCES guru_mapel(id) ON DELETE CASCADE,
    FOREIGN KEY (siswa_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (dicatat_oleh) REFERENCES users(id) ON DELETE CASCADE,
    UNIQUE KEY unique_presensi (guru_mapel_id, siswa_id, tanggal_sesi),
    INDEX idx_guru_mapel (guru_mapel_id),
    INDEX idx_siswa (siswa_id),
    INDEX idx_tanggal (tanggal_sesi),
    INDEX idx_status (status_kehadiran)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS pengumuman (
    id INT AUTO_INCREMENT PRIMARY KEY,
    pembuat_id INT NOT NULL,
    judul_pengumuman VARCHAR(255) NOT NULL,
    isi_pengumuman TEXT NOT NULL,
    kategori ENUM('penting', 'akademik', 'agenda', 'kegiatan', 'umum') DEFAULT 'umum',
    target_role ENUM('semua', 'siswa', 'guru') DEFAULT 'semua',
    is_published TINYINT(1) DEFAULT 1,
    tanggal_terbit TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (pembuat_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_pembuat (pembuat_id),
    INDEX idx_kategori (kategori),
    INDEX idx_published (is_published),
    INDEX idx_tanggal (tanggal_terbit)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS statistik_siswa (
    id INT AUTO_INCREMENT PRIMARY KEY,
    siswa_id INT NOT NULL,
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

-- ============================================================
-- BAGIAN 3: DATA AWAL (tanpa menghapus data yang sudah ada)
-- Catatan: DB existing tidak punya akun guru sama sekali,
--          jadi 6 guru ditambahkan. Password semua guru: "password"
-- ============================================================

-- 3a. Akun guru (dicek dulu per email agar tidak dobel)
INSERT INTO users (nama_lengkap, email, password, role, nip)
SELECT * FROM (
    SELECT 'Ahmad Dahlan' AS nl, 'ahmad.dahlan@edulms.sch.id' AS em,
           '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi' AS pw,
           'guru' AS rl, '198503152010011001' AS nip
    UNION ALL SELECT 'Siti Nurhaliza', 'siti.nurhaliza@edulms.sch.id',
           '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'guru', '199001102015032001'
    UNION ALL SELECT 'Hendra Wijaya', 'hendra.wijaya@edulms.sch.id',
           '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'guru', '198705202012011002'
    UNION ALL SELECT 'Rina Anggraini', 'rina.anggraini@edulms.sch.id',
           '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'guru', '199203152016032001'
    UNION ALL SELECT 'Agus Prabowo', 'agus.prabowo@edulms.sch.id',
           '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'guru', '198808102013011001'
    UNION ALL SELECT 'Sulastri', 'sulastri@edulms.sch.id',
           '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'guru', '197512052005032001'
) AS calon
WHERE NOT EXISTS (SELECT 1 FROM users WHERE users.email = calon.em);

-- 3b. Beri NISN ke akun siswa yang belum punya (data tidak dihapus, hanya dilengkapi)
UPDATE users SET nisn = '0098765432' WHERE id = 3 AND nisn IS NULL; -- Kevin Julio
UPDATE users SET nisn = '0098765433' WHERE id = 5 AND nisn IS NULL; -- farhan
UPDATE users SET nisn = '0098765434' WHERE id = 6 AND nisn IS NULL; -- farhan agustian
UPDATE users SET nisn = '0098765435' WHERE id = 7 AND nisn IS NULL; -- rara
UPDATE users SET nisn = '0098765436' WHERE id = 8 AND nisn IS NULL; -- Daffa

-- 3c. Kelas (dicek per nama+tahun agar tidak dobel)
INSERT INTO kelas (nama_kelas, tingkat, jurusan, tahun_ajaran, wali_kelas_id)
SELECT c.nl, c.tingkat, c.jurusan, c.ta, c.wali
FROM (
    SELECT 'XI MIPA 1' AS nl, 11 AS tingkat, 'MIPA' AS jurusan, '2026/2027' AS ta,
           (SELECT id FROM users WHERE email = 'ahmad.dahlan@edulms.sch.id') AS wali
    UNION ALL SELECT 'XI MIPA 2', 11, 'MIPA', '2026/2027', (SELECT id FROM users WHERE email = 'siti.nurhaliza@edulms.sch.id')
    UNION ALL SELECT 'XI MIPA 3', 11, 'MIPA', '2026/2027', (SELECT id FROM users WHERE email = 'hendra.wijaya@edulms.sch.id')
    UNION ALL SELECT 'X MIPA 1',  10, 'MIPA', '2026/2027', (SELECT id FROM users WHERE email = 'rina.anggraini@edulms.sch.id')
    UNION ALL SELECT 'X MIPA 2',  10, 'MIPA', '2026/2027', (SELECT id FROM users WHERE email = 'agus.prabowo@edulms.sch.id')
) AS c
WHERE NOT EXISTS (
    SELECT 1 FROM kelas WHERE kelas.nama_kelas = c.nl AND kelas.tahun_ajaran = c.ta
);

-- 3d. Siswa masuk kelas (pakai akun siswa yang sudah ada di DB: id 3,5,6,7,8)
INSERT IGNORE INTO siswa_kelas (siswa_id, kelas_id, tahun_ajaran, status) VALUES
((SELECT id FROM users WHERE nisn = '0098765432'), (SELECT id FROM kelas WHERE nama_kelas = 'XI MIPA 1' AND tahun_ajaran = '2026/2027'), '2026/2027', 'aktif'),
((SELECT id FROM users WHERE nisn = '0098765433'), (SELECT id FROM kelas WHERE nama_kelas = 'XI MIPA 1' AND tahun_ajaran = '2026/2027'), '2026/2027', 'aktif'),
((SELECT id FROM users WHERE nisn = '0098765434'), (SELECT id FROM kelas WHERE nama_kelas = 'XI MIPA 1' AND tahun_ajaran = '2026/2027'), '2026/2027', 'aktif'),
((SELECT id FROM users WHERE nisn = '0098765435'), (SELECT id FROM kelas WHERE nama_kelas = 'XI MIPA 2' AND tahun_ajaran = '2026/2027'), '2026/2027', 'aktif'),
((SELECT id FROM users WHERE nisn = '0098765436'), (SELECT id FROM kelas WHERE nama_kelas = 'XI MIPA 2' AND tahun_ajaran = '2026/2027'), '2026/2027', 'aktif');

-- 3e. Mata pelajaran
INSERT INTO mata_pelajaran (kode_mapel, nama_mapel, deskripsi, kategori, icon_emoji, color_class)
SELECT c.kode, c.nama, c.desk, c.kat, c.emoji, c.warna
FROM (
    SELECT 'MAT-01' AS kode, 'Matematika Wajib' AS nama, 'Matematika tingkat SMA' AS desk, 'wajib' AS kat, '📊' AS emoji, 'teal' AS warna
    UNION ALL SELECT 'IND-01', 'Bahasa Indonesia', 'Bahasa Indonesia tingkat SMA', 'wajib', '📖', 'blue'
    UNION ALL SELECT 'FIS-01', 'Fisika Terapan', 'Fisika untuk MIPA', 'peminatan', '⚗️', 'indigo'
    UNION ALL SELECT 'TIK-01', 'Pemrograman Web & TIK', 'Teknologi Informasi dan Komunikasi', 'peminatan', '💻', 'emerald'
    UNION ALL SELECT 'ING-01', 'Bahasa Inggris', 'English Language', 'wajib', '🗣️', 'amber'
    UNION ALL SELECT 'BIO-01', 'Biologi', 'Biologi untuk MIPA', 'peminatan', '🧬', 'rose'
) AS c
WHERE NOT EXISTS (SELECT 1 FROM mata_pelajaran WHERE mata_pelajaran.kode_mapel = c.kode);

-- 3f. Penugasan guru-mapel-kelas
INSERT IGNORE INTO guru_mapel (guru_id, mapel_id, kelas_id, tahun_ajaran, hari, jam_mulai, jam_selesai, ruang) VALUES
((SELECT id FROM users WHERE email = 'ahmad.dahlan@edulms.sch.id'),    (SELECT id FROM mata_pelajaran WHERE kode_mapel='MAT-01'), (SELECT id FROM kelas WHERE nama_kelas='XI MIPA 1' AND tahun_ajaran='2026/2027'), '2026/2027', 'Senin', '07:30:00', '09:00:00', 'Lab Komputer 2'),
((SELECT id FROM users WHERE email = 'ahmad.dahlan@edulms.sch.id'),    (SELECT id FROM mata_pelajaran WHERE kode_mapel='MAT-01'), (SELECT id FROM kelas WHERE nama_kelas='XI MIPA 2' AND tahun_ajaran='2026/2027'), '2026/2027', 'Selasa', '09:30:00', '11:00:00', 'Ruang Kelas XI MIPA 2'),
((SELECT id FROM users WHERE email = 'ahmad.dahlan@edulms.sch.id'),    (SELECT id FROM mata_pelajaran WHERE kode_mapel='MAT-01'), (SELECT id FROM kelas WHERE nama_kelas='XI MIPA 3' AND tahun_ajaran='2026/2027'), '2026/2027', 'Rabu', '13:00:00', '14:30:00', 'Ruang Kelas XI MIPA 3'),
((SELECT id FROM users WHERE email = 'siti.nurhaliza@edulms.sch.id'),  (SELECT id FROM mata_pelajaran WHERE kode_mapel='IND-01'), (SELECT id FROM kelas WHERE nama_kelas='XI MIPA 1' AND tahun_ajaran='2026/2027'), '2026/2027', 'Kamis', '07:30:00', '09:00:00', 'Ruang Kelas XI MIPA 1'),
((SELECT id FROM users WHERE email = 'siti.nurhaliza@edulms.sch.id'),  (SELECT id FROM mata_pelajaran WHERE kode_mapel='IND-01'), (SELECT id FROM kelas WHERE nama_kelas='XI MIPA 2' AND tahun_ajaran='2026/2027'), '2026/2027', 'Jumat', '09:30:00', '11:00:00', 'Ruang Kelas XI MIPA 2'),
((SELECT id FROM users WHERE email = 'hendra.wijaya@edulms.sch.id'),   (SELECT id FROM mata_pelajaran WHERE kode_mapel='FIS-01'), (SELECT id FROM kelas WHERE nama_kelas='XI MIPA 1' AND tahun_ajaran='2026/2027'), '2026/2027', 'Selasa', '07:30:00', '09:00:00', 'Lab Fisika'),
((SELECT id FROM users WHERE email = 'hendra.wijaya@edulms.sch.id'),   (SELECT id FROM mata_pelajaran WHERE kode_mapel='FIS-01'), (SELECT id FROM kelas WHERE nama_kelas='XI MIPA 3' AND tahun_ajaran='2026/2027'), '2026/2027', 'Rabu', '09:30:00', '11:00:00', 'Ruang Kelas XI MIPA 3'),
((SELECT id FROM users WHERE email = 'rina.anggraini@edulms.sch.id'),  (SELECT id FROM mata_pelajaran WHERE kode_mapel='TIK-01'), (SELECT id FROM kelas WHERE nama_kelas='XI MIPA 1' AND tahun_ajaran='2026/2027'), '2026/2027', 'Jumat', '13:00:00', '15:00:00', 'Lab Komputer 1'),
((SELECT id FROM users WHERE email = 'agus.prabowo@edulms.sch.id'),    (SELECT id FROM mata_pelajaran WHERE kode_mapel='ING-01'), (SELECT id FROM kelas WHERE nama_kelas='XI MIPA 1' AND tahun_ajaran='2026/2027'), '2026/2027', 'Rabu', '07:30:00', '09:00:00', 'Ruang Kelas XI MIPA 1'),
((SELECT id FROM users WHERE email = 'sulastri@edulms.sch.id'),        (SELECT id FROM mata_pelajaran WHERE kode_mapel='BIO-01'), (SELECT id FROM kelas WHERE nama_kelas='XI MIPA 1' AND tahun_ajaran='2026/2027'), '2026/2027', 'Kamis', '09:30:00', '11:00:00', 'Lab Biologi');

-- 3g. Materi contoh
INSERT IGNORE INTO materi (guru_mapel_id, judul_materi, deskripsi, urutan_modul, is_published) VALUES
(1, 'Determinan & Invers Matriks', 'Materi tentang operasi matriks lanjutan', 1, 1),
(2, 'Integral Kalkulus Dasar', 'Pengenalan integral tak tentu dan tentu', 2, 1),
(6, 'Kinematika Gerak Lurus', 'Gerak lurus beraturan dan berubah beraturan', 1, 1),
(8, 'Desain UI Interaktif dengan Tailwind CSS', 'Membuat tampilan modern dengan Tailwind', 1, 1);

-- 3h. Tugas contoh (batas waktu 2027 agar masih relevan untuk demo)
INSERT IGNORE INTO tugas (guru_mapel_id, judul_tugas, deskripsi, batas_pengumpulan, format_file, bobot_nilai) VALUES
(1, 'Latihan Soal Matriks & Vektor', 'Kerjakan 15 soal tentang operasi matriks', '2027-05-18 23:59:00', 'PDF', 100),
(6, 'Laporan Praktikum Kinematika', 'Laporan hasil eksperimen gerak lurus', '2027-05-25 23:59:00', 'PDF', 100),
(8, 'Membuat Halaman Web Sederhana', 'Buat landing page dengan HTML, CSS, dan Tailwind', '2027-05-20 17:00:00', 'ZIP', 100),
(4, 'Ringkasan Teks Argumentasi', 'Buat ringkasan dari artikel yang diberikan', '2027-05-22 21:00:00', 'DOCX, PDF', 100),
(9, 'Essay Writing Narrative', 'Tulis essay narrative 500 kata', '2027-05-28 23:59:00', 'DOCX, PDF', 100);

-- 3i. Pengumpulan contoh (file fisik belum tentu ada; hanya referensi DB)
INSERT IGNORE INTO pengumpulan_tugas (tugas_id, siswa_id, file_path, file_name, file_size, status, nilai, feedback, dinilai_oleh, dinilai_pada) VALUES
(5, (SELECT id FROM users WHERE nisn='0098765432'), 'uploads/pengumpulan/essay_budi_20240526.pdf', 'essay_narrative_budi.pdf', 524288, 'tepat_waktu', 90.00, 'Tulisan bagus, struktur narrative jelas. Perhatikan tanda baca.', (SELECT id FROM users WHERE email='agus.prabowo@edulms.sch.id'), '2024-05-27 10:30:00');

-- 3j. Presensi contoh
INSERT IGNORE INTO presensi (guru_mapel_id, siswa_id, tanggal_sesi, jam_sesi, status_kehadiran, jam_kedatangan, dicatat_oleh) VALUES
(1, (SELECT id FROM users WHERE nisn='0098765432'), '2024-05-13', '07:30:00', 'hadir', '07:25:00', (SELECT id FROM users WHERE email='ahmad.dahlan@edulms.sch.id')),
(1, (SELECT id FROM users WHERE nisn='0098765433'), '2024-05-13', '07:30:00', 'hadir', '07:28:00', (SELECT id FROM users WHERE email='ahmad.dahlan@edulms.sch.id')),
(1, (SELECT id FROM users WHERE nisn='0098765434'), '2024-05-13', '07:30:00', 'sakit', NULL, (SELECT id FROM users WHERE email='ahmad.dahlan@edulms.sch.id')),
(1, (SELECT id FROM users WHERE nisn='0098765432'), '2024-05-14', '07:30:00', 'hadir', '07:20:00', (SELECT id FROM users WHERE email='ahmad.dahlan@edulms.sch.id')),
(1, (SELECT id FROM users WHERE nisn='0098765433'), '2024-05-14', '07:30:00', 'hadir', '07:30:00', (SELECT id FROM users WHERE email='ahmad.dahlan@edulms.sch.id'));

-- 3k. Pengumuman
INSERT INTO pengumuman (pembuat_id, judul_pengumuman, isi_pengumuman, kategori, target_role)
SELECT 1, c.judul, c.isi, c.kat, c.target
FROM (
    SELECT 'Jadwal Penilaian Tengah Semester (PTS) Genap' AS judul,
           'PTS Semester Genap akan dilaksanakan secara serentak mulai tanggal 3 Juni di ruang kelas masing-masing. Harap seluruh siswa mempersiapkan kartu ujian digital serta memverifikasi kelengkapan administrasi akademik sebelum tanggal pelaksanaan.' AS isi,
           'penting' AS kat, 'semua' AS target
    UNION ALL SELECT 'Libur Nasional & Kegiatan Belajar Mandiri',
           'Sehubungan dengan hari libur nasional, kegiatan belajar tatap muka ditiadakan dan dialihkan secara daring mandiri melalui portal EduLMS. Materi tugas terstruktur dapat diakses melalui tab Mata Pelajaran.',
           'agenda', 'semua'
    UNION ALL SELECT 'Pendaftaran Ekstrakurikuler Robotik & Coding',
           'Pendaftaran anggota baru club pemrograman dan robotik dibuka hingga akhir pekan ini melalui formulir online ruang kesiswaan. Kuota terbatas untuk 30 peserta angkatan pertama program semester gasal.',
           'kegiatan', 'siswa'
) AS c
WHERE NOT EXISTS (SELECT 1 FROM pengumuman WHERE pengumuman.judul_pengumuman = c.judul);

-- 3l. Statistik siswa contoh
INSERT IGNORE INTO statistik_siswa (siswa_id, tahun_ajaran, rata_rata_nilai, persentase_kehadiran, jumlah_tugas_selesai, jumlah_tugas_total, waktu_belajar_jam) VALUES
((SELECT id FROM users WHERE nisn='0098765432'), '2026/2027', 88.50, 98.00, 18, 20, 14.50);

-- ============================================================
-- SELESAI. Cek hasil:
--   SELECT COUNT(*) FROM users;                       -- 14 (8 lama + 6 guru)
--   SHOW TABLES;                                      -- 12 tabel
-- ============================================================
