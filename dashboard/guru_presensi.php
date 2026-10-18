<?php
/**
 * EduLMS - Input Presensi Siswa
 * Teacher attendance input for students
 */

require_once '../includes/db.php';
require_once '../includes/auth_check.php';
require_once '../includes/functions.php';
require_once '../includes/csrf.php';

requireTeacher();

$user_id = currentUserId();
$nama_lengkap = $_SESSION['nama_lengkap'] ?? 'Guru';

$error = '';
$success = '';

// Validate session selection before any query or write.
$selected_guru_mapel_id = (int)($_GET['guru_mapel_id'] ?? $_POST['guru_mapel_id'] ?? 0);
$selected_date = $_GET['tanggal'] ?? $_POST['tanggal_sesi'] ?? date('Y-m-d');
$dateCheck = DateTimeImmutable::createFromFormat('!Y-m-d', $selected_date);
if (!$dateCheck || $dateCheck->format('Y-m-d') !== $selected_date) {
    http_response_code(400); exit('Tanggal sesi tidak valid.');
}
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    checkCsrfToken();
    $guru_mapel_id = (int)($_POST['guru_mapel_id'] ?? 0);
    $tanggal = $_POST['tanggal_sesi'] ?? '';
    $jam = $_POST['jam_sesi'] ?? '07:00:00';
    $attendance = $_POST['attendance'] ?? [];
    $stmtVerify = $pdo->prepare('SELECT id, kelas_id, tahun_ajaran FROM guru_mapel WHERE id=? AND guru_id=? AND is_active=1');
    $stmtVerify->execute([$guru_mapel_id, $user_id]);
    $owned = $stmtVerify->fetch();
    $validDate = DateTimeImmutable::createFromFormat('!Y-m-d', $tanggal);
    if (!$owned) {
        http_response_code(403); $error = 'Akses ditolak!';
    } elseif (!$validDate || $validDate->format('Y-m-d') !== $tanggal || $tanggal > date('Y-m-d') || !preg_match('/^([01][0-9]|2[0-3]):[0-5][0-9](:[0-5][0-9])?$/', $jam) || !is_array($attendance) || !$attendance) {
        $error = 'Periksa tanggal, jam, dan daftar presensi.';
    } else {
        $roster = $pdo->prepare("SELECT siswa_id FROM siswa_kelas WHERE kelas_id=? AND tahun_ajaran=? AND status='aktif'");
        $roster->execute([$owned['kelas_id'],$owned['tahun_ajaran']]);
        $allowedStudents = array_map('intval', $roster->fetchAll(PDO::FETCH_COLUMN));
        foreach ($attendance as $siswa_id => $status) {
            $arrival = $_POST['arrival'][$siswa_id] ?? '';
            if (!in_array((int)$siswa_id, $allowedStudents, true) || !in_array($status, ['hadir','sakit','izin','alfa'], true) || ($arrival !== '' && !preg_match('/^([01][0-9]|2[0-3]):[0-5][0-9](:[0-5][0-9])?$/', $arrival))) {
                $error = 'Siswa, status, atau jam kedatangan tidak valid.'; break;
            }
        }
        if ($error === '') {
            try {
                $pdo->beginTransaction();
                $save = $pdo->prepare('INSERT INTO presensi (guru_mapel_id,siswa_id,tanggal_sesi,jam_sesi,status_kehadiran,jam_kedatangan,catatan,dicatat_oleh)
                  VALUES (?,?,?,?,?,?,?,?) ON DUPLICATE KEY UPDATE jam_sesi=VALUES(jam_sesi),status_kehadiran=VALUES(status_kehadiran),jam_kedatangan=VALUES(jam_kedatangan),catatan=VALUES(catatan),dicatat_oleh=VALUES(dicatat_oleh)');
                foreach ($attendance as $siswa_id => $status) {
                    $arrival = $status === 'hadir' ? ($_POST['arrival'][$siswa_id] ?? '') : '';
                    $save->execute([$guru_mapel_id,$siswa_id,$tanggal,$jam,$status,$arrival ?: null,trim($_POST['notes'][$siswa_id] ?? ''),$user_id]);
                }
                $pdo->commit();
                $success = 'Presensi berhasil disimpan untuk ' . count($attendance) . ' siswa!';
                $selected_guru_mapel_id = $guru_mapel_id; $selected_date = $tanggal;
            } catch (PDOException $exception) {
                if ($pdo->inTransaction()) $pdo->rollBack();
                error_log($exception->getMessage()); $error = 'Presensi gagal disimpan. Silakan coba lagi.';
            }
        }
    }
}

// Get teacher's subjects
$stmtSubjects = $pdo->prepare("
    SELECT
        gm.id as guru_mapel_id,
        gm.jam_mulai,
        mp.nama_mapel,
        k.nama_kelas
    FROM guru_mapel gm
    JOIN mata_pelajaran mp ON gm.mapel_id = mp.id
    JOIN kelas k ON gm.kelas_id = k.id
    WHERE gm.guru_id = ? AND gm.is_active = 1
    ORDER BY k.tingkat DESC,k.nama_kelas, gm.id
");
$stmtSubjects->execute([$user_id]);
$subjects = $stmtSubjects->fetchAll();

// Set default if not specified
if (!$selected_guru_mapel_id && count($subjects) > 0) {
    $selected_guru_mapel_id = $subjects[0]['guru_mapel_id'];
}

// Get students for selected class
$students = [];
$classInfo = null;
if ($selected_guru_mapel_id) {
    // Get class info
    $stmtInfo = $pdo->prepare("
        SELECT
            mp.nama_mapel,
            k.nama_kelas,
            gm.jam_mulai, gm.ruang
        FROM guru_mapel gm
        JOIN mata_pelajaran mp ON gm.mapel_id = mp.id
        JOIN kelas k ON gm.kelas_id = k.id
        WHERE gm.id = ? AND gm.guru_id = ? AND gm.is_active = 1
    ");
    $stmtInfo->execute([$selected_guru_mapel_id, $user_id]);
    if (!$classInfo = $stmtInfo->fetch()) { http_response_code(403); exit("Akses ditolak!"); }


    // Get students
    $stmtStudents = $pdo->prepare("
        SELECT
            u.id,
            u.nama_lengkap,
            u.nisn,
            u.foto_profil,
            p.status_kehadiran,
            p.jam_kedatangan,
            p.catatan
        FROM siswa_kelas sk
        JOIN users u ON sk.siswa_id = u.id
        JOIN guru_mapel gm ON sk.kelas_id = gm.kelas_id AND sk.tahun_ajaran = gm.tahun_ajaran
        LEFT JOIN presensi p ON p.siswa_id = u.id
            AND p.guru_mapel_id = gm.id
            AND p.tanggal_sesi = ?
        WHERE gm.id = ? AND gm.guru_id = ? AND sk.status = 'aktif'
        ORDER BY u.nama_lengkap
    ");
    $stmtStudents->execute([$selected_date, $selected_guru_mapel_id, $user_id]);
    $students = $stmtStudents->fetchAll();
}

// Calculate stats
$hadir = $sakit = $izin = $alfa = 0;
foreach ($students as $s) {
    if ($s['status_kehadiran'] === 'hadir') $hadir++;
    elseif ($s['status_kehadiran'] === 'sakit') $sakit++;
    elseif ($s['status_kehadiran'] === 'izin') $izin++;
    elseif ($s['status_kehadiran'] === 'alfa') $alfa++;
}
$total = count($students);
$persentase = $total > 0 ? round(($hadir / $total) * 100, 1) : 0;
$recap = $pdo->prepare("SELECT gm.id, k.nama_kelas, mp.nama_mapel, COUNT(p.id) AS total, COUNT(DISTINCT p.tanggal_sesi) AS sessions,
 (SELECT COUNT(*) FROM siswa_kelas sk WHERE sk.kelas_id=gm.kelas_id AND sk.tahun_ajaran=gm.tahun_ajaran AND sk.status='aktif') AS students,
 SUM(p.status_kehadiran='hadir') AS hadir, SUM(p.status_kehadiran='sakit') AS sakit,
 SUM(p.status_kehadiran='izin') AS izin, SUM(p.status_kehadiran='alfa') AS alfa
 FROM guru_mapel gm JOIN kelas k ON k.id=gm.kelas_id JOIN mata_pelajaran mp ON mp.id=gm.mapel_id
 LEFT JOIN presensi p ON p.guru_mapel_id=gm.id AND DATE_FORMAT(p.tanggal_sesi,'%Y-%m')=?
 WHERE gm.guru_id=? AND gm.is_active=1 GROUP BY gm.id,k.id,mp.id HAVING total > 0 ORDER BY k.tingkat DESC,k.nama_kelas,gm.id");
$recap->execute([substr($selected_date,0,7),$user_id]);
$monthlyRecap = $recap->fetchAll();
?>
<?php
$daily=$pdo->prepare("SELECT COUNT(p.id) AS total,COUNT(DISTINCT p.guru_mapel_id) AS sessions, SUM(p.status_kehadiran='hadir') AS hadir,SUM(p.status_kehadiran='sakit') AS sakit,SUM(p.status_kehadiran='izin') AS izin,SUM(p.status_kehadiran='alfa') AS alfa FROM presensi p JOIN guru_mapel gm ON gm.id=p.guru_mapel_id WHERE gm.guru_id=? AND gm.is_active=1 AND p.tanggal_sesi=?");$daily->execute([$user_id,$selected_date]);$dailyAttendance=$daily->fetch();
$week=$pdo->prepare("SELECT COUNT(*) AS total,SUM(p.status_kehadiran='hadir') AS hadir FROM presensi p JOIN guru_mapel gm ON gm.id=p.guru_mapel_id WHERE gm.guru_id=? AND gm.is_active=1 AND p.tanggal_sesi BETWEEN DATE_SUB(?,INTERVAL 6 DAY) AND ?");$week->execute([$user_id,$selected_date,$selected_date]);$w=$week->fetch();$weeklyAttendance=calculatePercentage($w['hadir']??0,$w['total']);
?>
<?php require __DIR__.'/../includes/views/attendance.php'; ?>
