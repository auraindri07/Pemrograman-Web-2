-- Additive migration for authenticated portal actions. Existing records are retained.
CREATE TABLE IF NOT EXISTS pengajuan_rombel (
 id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
 guru_id INT UNSIGNED NOT NULL,
 sumber_guru_mapel_id INT UNSIGNED NULL,
 nama_kelas VARCHAR(50) NOT NULL,
 tingkat INT NOT NULL,
 jurusan VARCHAR(50) NULL,
 tahun_ajaran VARCHAR(20) NOT NULL,
 alasan TEXT NOT NULL,
 status ENUM('pending','approved','rejected') NOT NULL DEFAULT 'pending',
 request_key CHAR(64) NULL UNIQUE,
 tanggapan TEXT NULL,
 admin_id INT UNSIGNED NULL,
 kelas_id INT UNSIGNED NULL,
 guru_mapel_id INT UNSIGNED NULL,
 created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
 processed_at DATETIME NULL,
 FOREIGN KEY (guru_id) REFERENCES users(id) ON DELETE CASCADE,
 FOREIGN KEY (sumber_guru_mapel_id) REFERENCES guru_mapel(id) ON DELETE SET NULL,
 FOREIGN KEY (admin_id) REFERENCES users(id) ON DELETE SET NULL,
 FOREIGN KEY (kelas_id) REFERENCES kelas(id) ON DELETE SET NULL,
 FOREIGN KEY (guru_mapel_id) REFERENCES guru_mapel(id) ON DELETE SET NULL,
 INDEX idx_rombel_status (status,created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
CREATE TABLE IF NOT EXISTS tiket_bantuan (
 id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
 user_id INT UNSIGNED NOT NULL,
 judul VARCHAR(150) NOT NULL,
 pesan TEXT NOT NULL,
 status ENUM('open','closed') NOT NULL DEFAULT 'open',
 tanggapan TEXT NULL,
 admin_id INT UNSIGNED NULL,
 created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
 responded_at DATETIME NULL,
 FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
 FOREIGN KEY (admin_id) REFERENCES users(id) ON DELETE SET NULL,
 INDEX idx_tiket_status (status,created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
