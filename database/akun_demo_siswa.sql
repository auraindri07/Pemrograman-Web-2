-- ============================================================
-- EDULMS - AKUN DEMO SISWA (INKREMENTAL, AMAN)
-- Menambah 2 akun demo siswa berpassword "password" untuk
-- pengujian/demo. TIDAK mengubah/menghapus akun yang sudah ada.
-- ============================================================
USE edulms_db;

-- Akun demo siswa (dicek per email agar tidak dobel)
INSERT INTO users (nama_lengkap, email, password, role, nisn)
SELECT * FROM (
    SELECT 'Budi Santoso (Demo)' AS nl, 'budi.demo@student.edulms.sch.id' AS em,
           '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi' AS pw,
           'siswa' AS rl, '9000000001' AS nisn
    UNION ALL SELECT 'Aisyah Putri (Demo)', 'aisyah.demo@student.edulms.sch.id',
           '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'siswa', '9000000002'
) AS c
WHERE NOT EXISTS (SELECT 1 FROM users WHERE users.email = c.em);

-- Demo siswa masuk kelas (INSERT IGNORE agar tidak dobel)
INSERT IGNORE INTO siswa_kelas (siswa_id, kelas_id, tahun_ajaran, status) VALUES
((SELECT id FROM users WHERE email='budi.demo@student.edulms.sch.id'),  (SELECT id FROM kelas WHERE nama_kelas='XI MIPA 1' AND tahun_ajaran='2026/2027'), '2026/2027', 'aktif'),
((SELECT id FROM users WHERE email='aisyah.demo@student.edulms.sch.id'), (SELECT id FROM kelas WHERE nama_kelas='XI MIPA 1' AND tahun_ajaran='2026/2027'), '2026/2027', 'aktif');

-- Statistik demo untuk Budi
INSERT IGNORE INTO statistik_siswa (siswa_id, tahun_ajaran, rata_rata_nilai, persentase_kehadiran, jumlah_tugas_selesai, jumlah_tugas_total, waktu_belajar_jam) VALUES
((SELECT id FROM users WHERE email='budi.demo@student.edulms.sch.id'), '2026/2027', 88.50, 98.00, 18, 20, 14.50);
