<?php
/**
 * EduLMS - Dashboard Siswa
 * Student portal dashboard with real data from database
 */

require_once '../includes/db.php';
require_once '../includes/config.php';
require_once '../includes/auth_check.php';
require_once '../includes/functions.php';

// Require student authentication
requireStudent();

$user_id = currentUserId();
$nama_lengkap = $_SESSION['nama_lengkap'] ?? 'Siswa';

// Get student class info
$stmtKelas = $pdo->prepare("
    SELECT k.nama_kelas, k.tahun_ajaran 
    FROM siswa_kelas sk
    JOIN kelas k ON sk.kelas_id = k.id
    WHERE sk.siswa_id = ? AND sk.status = 'aktif'
    LIMIT 1
");
$stmtKelas->execute([$user_id]);
$kelasInfo = $stmtKelas->fetch();

// Get statistics — DIHITUNG LANGSUNG dari data transaksi (bukan cache statis)
// 1) Rata-rata nilai (dari pengumpulan yang sudah dinilai)
$stmt = $pdo->prepare("SELECT AVG(pt.nilai) as rata, COUNT(pt.nilai) as jml FROM pengumpulan_tugas pt WHERE pt.siswa_id = ? AND pt.nilai IS NOT NULL");
$stmt->execute([$user_id]);
$aggNilai = $stmt->fetch();
$rataNilai = $aggNilai['rata'] !== null ? floatval($aggNilai['rata']) : 0.0;
$jmlDinilai = intval($aggNilai['jml']);

// 2) Persentase kehadiran (dari presensi)
$stmt = $pdo->prepare("SELECT SUM(status_kehadiran = 'hadir') as hadir, COUNT(*) as total FROM presensi WHERE siswa_id = ?");
$stmt->execute([$user_id]);
$kh = $stmt->fetch();
$persenHadir = $kh['total'] > 0 ? round(($kh['hadir'] / $kh['total']) * 100, 1) : 0.0;
if ($persenHadir >= 90)      $labelHadir = 'Sangat Baik';
elseif ($persenHadir >= 80)  $labelHadir = 'Baik';
elseif ($persenHadir >= 70)  $labelHadir = 'Perlu Perhatian';
else                         $labelHadir = 'Kurang';

// 3) Tugas selesai vs total (pada kelas & tahun ajaran aktif)
$stmt = $pdo->prepare("
    SELECT COUNT(*) as total
    FROM tugas t
    JOIN guru_mapel gm ON t.guru_mapel_id = gm.id
    JOIN siswa_kelas sk ON gm.kelas_id = sk.kelas_id AND gm.tahun_ajaran = sk.tahun_ajaran
    WHERE sk.siswa_id = ? AND sk.status = 'aktif' AND t.is_published = 1
");
$stmt->execute([$user_id]);
$totalTugas = intval($stmt->fetchColumn());

$stmt = $pdo->prepare("
    SELECT COUNT(DISTINCT pt.tugas_id) as selesai
    FROM pengumpulan_tugas pt
    JOIN tugas t ON pt.tugas_id = t.id
    JOIN guru_mapel gm ON t.guru_mapel_id = gm.id
    JOIN siswa_kelas sk ON gm.kelas_id = sk.kelas_id AND gm.tahun_ajaran = sk.tahun_ajaran
    WHERE pt.siswa_id = ? AND sk.siswa_id = ? AND sk.status = 'aktif' AND t.is_published = 1
");
$stmt->execute([$user_id, $user_id]);
$tugasSelesai = intval($stmt->fetchColumn());
$persenTuntas = $totalTugas > 0 ? round(($tugasSelesai / $totalTugas) * 100) : 0;
$sisaTuntas   = 100 - $persenTuntas;

// 4) Waktu belajar: satu-satunya metrik tanpa tabel transaksi -> ambil dari statistik_siswa
$stmtWaktu = $pdo->prepare("SELECT waktu_belajar_jam FROM statistik_siswa WHERE siswa_id = ? AND tahun_ajaran = ?");
$stmtWaktu->execute([$user_id, TAHUN_AJARAN_AKTIF ?? '2026/2027']);
$waktuBelajar = $stmtWaktu->fetchColumn();
$waktuBelajar = $waktuBelajar !== false && $waktuBelajar !== null ? floatval($waktuBelajar) : 0.0;

// Get active subjects for this student
$stmtMapel = $pdo->prepare("
    SELECT DISTINCT 
        mp.id,
        gm.id as guru_mapel_id,
        mp.nama_mapel,
        mp.icon_emoji,
        mp.color_class,
        (SELECT judul_materi FROM materi m WHERE m.guru_mapel_id=gm.id AND m.is_published=1 ORDER BY urutan_modul DESC,id DESC LIMIT 1) AS latest_material,
        (SELECT COUNT(*) FROM pengumpulan_tugas pt JOIN tugas t ON t.id=pt.tugas_id WHERE t.guru_mapel_id=gm.id AND pt.siswa_id=sk.siswa_id AND t.is_published=1) AS completed_tasks,
        u.nama_lengkap as nama_guru,
        (SELECT COUNT(*) FROM materi m WHERE m.guru_mapel_id = gm.id AND m.is_published = 1) as jumlah_materi,
        (SELECT COUNT(*) FROM tugas t WHERE t.guru_mapel_id = gm.id AND t.is_published = 1) as jumlah_tugas
    FROM siswa_kelas sk
    JOIN kelas k ON sk.kelas_id = k.id
    JOIN guru_mapel gm ON gm.kelas_id = k.id AND gm.tahun_ajaran = sk.tahun_ajaran
    JOIN mata_pelajaran mp ON gm.mapel_id = mp.id
    JOIN users u ON gm.guru_id = u.id
    WHERE sk.siswa_id = ? AND sk.status = 'aktif' AND gm.is_active = 1
    ORDER BY jumlah_materi DESC, mp.id
    LIMIT 3
");
$stmtMapel->execute([$user_id]);
$mataPelajaranAktif = $stmtMapel->fetchAll();
usort($mataPelajaranAktif,fn($a,$b)=>(int)$a['id']<=>(int)$b['id']);

// Get upcoming assignments
$stmtTugas = $pdo->prepare("
    SELECT 
        t.id,
        t.judul_tugas,
        t.batas_pengumpulan,
        t.deskripsi, t.format_file, t.max_file_size,
        mp.nama_mapel,
        mp.icon_emoji,
        mp.color_class,
        pt.id as sudah_dikumpulkan,
        pt.nilai
    FROM siswa_kelas sk
    JOIN kelas k ON sk.kelas_id = k.id
    JOIN guru_mapel gm ON gm.kelas_id = k.id AND gm.tahun_ajaran = sk.tahun_ajaran
    JOIN tugas t ON t.guru_mapel_id = gm.id
    JOIN mata_pelajaran mp ON gm.mapel_id = mp.id
    LEFT JOIN pengumpulan_tugas pt ON pt.tugas_id = t.id AND pt.siswa_id = sk.siswa_id
    WHERE sk.siswa_id = ? 
        AND sk.status = 'aktif'
        AND t.is_published = 1
        AND t.batas_pengumpulan >= NOW()
    ORDER BY t.batas_pengumpulan ASC
    LIMIT 3
");
$stmtTugas->execute([$user_id]);
$tugasTerdekat = $stmtTugas->fetchAll();

// Subject completion is calculated per teaching assignment, not mislabeled task completion.
$subjectCompletion = $pdo->prepare("SELECT gm.id,
 (SELECT COUNT(*) FROM tugas t WHERE t.guru_mapel_id=gm.id AND t.is_published=1) AS total,
 (SELECT COUNT(*) FROM pengumpulan_tugas pt JOIN tugas t ON t.id=pt.tugas_id WHERE t.guru_mapel_id=gm.id AND t.is_published=1 AND pt.siswa_id=?) AS done
 FROM guru_mapel gm JOIN siswa_kelas sk ON sk.kelas_id=gm.kelas_id AND sk.tahun_ajaran=gm.tahun_ajaran
 WHERE sk.siswa_id=? AND sk.status='aktif' AND gm.is_active=1");
$subjectCompletion->execute([$user_id,$user_id]);
$subjectRows = $subjectCompletion->fetchAll();
$completedSubjects = count(array_filter($subjectRows, fn($subject) => $subject['total'] > 0 && $subject['done'] >= $subject['total']));
$subjectPercent = count($subjectRows) ? (int)floor($completedSubjects / count($subjectRows) * 100) : 0;
// One point per graded task, rather than grouping all grades in the same month.
require_once '../includes/student_grade_chart.php';
$gradeChartPeriods = studentGradeChart($pdo, (int)$user_id, TAHUN_AJARAN_AKTIF);
$currentGrades = array_column($gradeChartPeriods['current']['points'], 'value');
$gradeCount = count($currentGrades);
$gradeDelta = $gradeCount >= 2 && $currentGrades[$gradeCount-2] > 0
    ? ($currentGrades[$gradeCount-1]-$currentGrades[$gradeCount-2])/$currentGrades[$gradeCount-2]*100 : null;
require __DIR__.'/../includes/views/student_dashboard.php'; ?>
