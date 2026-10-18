<?php
/**
 * EduLMS - Penilaian Tugas
 * Grading and feedback for student submissions
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

// Handle grading submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'grade') {

    checkCsrfToken();

    $pengumpulan_id = $_POST['pengumpulan_id'];
    $nilai = filter_var($_POST['nilai'] ?? '', FILTER_VALIDATE_FLOAT);
    $feedback = trim($_POST['feedback']);

    // Verify ownership
    $stmtVerify = $pdo->prepare("
        SELECT pt.*, gm.guru_id
        FROM pengumpulan_tugas pt
        JOIN tugas t ON pt.tugas_id = t.id
        JOIN guru_mapel gm ON t.guru_mapel_id = gm.id
        WHERE pt.id = ? AND gm.guru_id = ?
    ");
    $stmtVerify->execute([$pengumpulan_id, $user_id]);

    if (!$stmtVerify->fetch()) {
        $error = 'Akses ditolak!';
    } elseif ($nilai === false || !is_finite($nilai) || $nilai < 0 || $nilai > 100) {
        $error = 'Nilai harus berupa angka antara 0 dan 100.';
    } else {
        $stmtUpdate = $pdo->prepare("
            UPDATE pengumpulan_tugas
            SET nilai = ?, feedback = ?, dinilai_oleh = ?, dinilai_pada = NOW()
            WHERE id = ?
        ");

        if ($stmtUpdate->execute([$nilai, $feedback, $user_id, $pengumpulan_id])) {
            $success = 'Nilai berhasil disimpan!';
        } else {
            $error = 'Gagal menyimpan nilai.';
        }
    }
}

// Get filter
$tugas_filter = $_GET['tugas_id'] ?? 'all';
$tugas_filter = ctype_digit((string)$tugas_filter) ? (int)$tugas_filter : 'all';
$search = trim($_GET['search'] ?? '');
$status_filter = $_GET['status'] ?? 'all';
$status_filter = in_array($status_filter, ['all','belum','sudah']) ? $status_filter : 'all';

// Get teacher's assignments
$stmtTugas = $pdo->prepare("
    SELECT
        t.id,
        t.judul_tugas,
        mp.nama_mapel,
        k.nama_kelas
    FROM tugas t
    JOIN guru_mapel gm ON t.guru_mapel_id = gm.id
    JOIN mata_pelajaran mp ON gm.mapel_id = mp.id
    JOIN kelas k ON gm.kelas_id = k.id
    WHERE gm.guru_id = ? AND t.is_published = 1
    ORDER BY t.batas_pengumpulan DESC
");
$stmtTugas->execute([$user_id]);
$tugasList = $stmtTugas->fetchAll();

// Get submissions
$sql = "
    SELECT
        pt.id,
        pt.siswa_id,
        pt.file_path,
        pt.file_name,
        pt.file_size,
        pt.catatan,
        pt.tanggal_kumpul,
        pt.status,
        pt.nilai,
        pt.feedback,
        u.nama_lengkap as nama_siswa,
        t.id as tugas_id,
        t.judul_tugas,
        t.bobot_nilai,
        mp.nama_mapel,
        k.nama_kelas
    FROM pengumpulan_tugas pt
    JOIN users u ON pt.siswa_id = u.id
    JOIN tugas t ON pt.tugas_id = t.id
    JOIN guru_mapel gm ON t.guru_mapel_id = gm.id
    JOIN mata_pelajaran mp ON gm.mapel_id = mp.id
    JOIN kelas k ON gm.kelas_id = k.id
    WHERE gm.guru_id = ?
";

$params = [$user_id];

if ($tugas_filter !== 'all') {
    $sql .= " AND t.id = ?";
    $params[] = $tugas_filter;
}

if ($status_filter === 'belum') {
    $sql .= " AND pt.nilai IS NULL";
} elseif ($status_filter === 'sudah') {
    $sql .= " AND pt.nilai IS NOT NULL";
}

if ($search !== '') {
    $sql .= " AND (t.judul_tugas LIKE ? OR u.nama_lengkap LIKE ? OR mp.nama_mapel LIKE ?)";
    array_push($params, "%$search%", "%$search%", "%$search%");
}
$sql .= " ORDER BY pt.tanggal_kumpul DESC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$submissions = $stmt->fetchAll();

// Count statistics
$totalSubmissions = count($submissions);
$belumDinilai = 0;
$sudahDinilai = 0;

foreach ($submissions as $s) {
    if ($s['nilai'] === null) {
        $belumDinilai++;
    } else {
        $sudahDinilai++;
    }
}
// Overview is grouped by assignment, matching the teacher reference.
$overview = $pdo->prepare("SELECT t.id, t.judul_tugas, t.batas_pengumpulan, mp.nama_mapel, k.nama_kelas,
 COUNT(pt.id) AS total, COUNT(pt.nilai) AS graded, AVG(pt.nilai) AS average, MAX(pt.nilai) AS highest, MAX(pt.dinilai_pada) AS completed_at,
 (SELECT COUNT(*) FROM siswa_kelas sk WHERE sk.kelas_id = gm.kelas_id AND sk.tahun_ajaran = gm.tahun_ajaran AND sk.status = 'aktif') AS students
 FROM tugas t JOIN guru_mapel gm ON gm.id=t.guru_mapel_id JOIN mata_pelajaran mp ON mp.id=gm.mapel_id
 JOIN kelas k ON k.id=gm.kelas_id LEFT JOIN pengumpulan_tugas pt ON pt.tugas_id=t.id
 WHERE gm.guru_id=? AND t.is_published=1
 GROUP BY t.id, gm.id, mp.id, k.id ORDER BY (COUNT(pt.id)>0 AND COUNT(pt.id)=COUNT(pt.nilai)), (t.batas_pengumpulan BETWEEN DATE_SUB(CURDATE(),INTERVAL 1 DAY) AND DATE_ADD(CURDATE(),INTERVAL 2 DAY)) DESC,t.batas_pengumpulan");
$overview->execute([$user_id]);
$assignmentOverview = $overview->fetchAll();
if ($tugas_filter === 'all') {
    $totalSubmissions = array_sum(array_column($assignmentOverview, 'total'));
    $sudahDinilai = array_sum(array_column($assignmentOverview, 'graded'));
    $belumDinilai = $totalSubmissions - $sudahDinilai;
}
?>
<?php
$onTime=$pdo->prepare("SELECT COUNT(*) AS total,SUM(pt.status='tepat_waktu') AS timely FROM pengumpulan_tugas pt JOIN tugas t ON t.id=pt.tugas_id JOIN guru_mapel gm ON gm.id=t.guru_mapel_id WHERE gm.guru_id=?");
$onTime->execute([$user_id]);$timing=$onTime->fetch();$onTimePercent=calculatePercentage($timing['timely']??0,$timing['total']);
$gradingClass=(int)($_GET['kelas']??0);
$classes=$pdo->prepare('SELECT DISTINCT k.id,k.nama_kelas FROM kelas k JOIN guru_mapel gm ON gm.kelas_id=k.id WHERE gm.guru_id=? AND gm.is_active=1 ORDER BY k.nama_kelas');$classes->execute([$user_id]);$gradingClasses=$classes->fetchAll();
$submissionTaskIds=array_column($submissions,'tugas_id');
$pendingTaskCount=$urgentTaskCount=$urgentFileCount=0;
foreach($assignmentOverview as $row){$left=$row['total']-$row['graded'];if($left>0){$pendingTaskCount++;if(strtotime($row['batas_pengumpulan'])>=strtotime('yesterday')&&date('Y-m-d',strtotime($row['batas_pengumpulan']))<=date('Y-m-d',strtotime('+1 day'))){$urgentTaskCount++;$urgentFileCount+=$left;}}}
$visibleAssignments=array_values(array_filter($assignmentOverview,function($row)use($status_filter,$search,$gradingClass,$gradingClasses,$submissionTaskIds){
 $left=$row['total']-$row['graded'];
 if($status_filter==='sudah'&&($left>0||!$row['total']))return false;
 if($status_filter==='belum'&&(!$left||strtotime($row['batas_pengumpulan'])<strtotime('yesterday')||date('Y-m-d',strtotime($row['batas_pengumpulan']))>date('Y-m-d',strtotime('+1 day'))))return false;
 if($gradingClass){$name='';foreach($gradingClasses as $c)if($c['id']==$gradingClass)$name=$c['nama_kelas'];if($row['nama_kelas']!==$name)return false;}
 return $search===''||mb_stripos($row['judul_tugas'].' '.$row['nama_mapel'],$search)!==false||in_array($row['id'],$submissionTaskIds); 
}));
if(($_GET['urut']??'')==='jauh')$visibleAssignments=array_reverse($visibleAssignments);
$gradingResultCount=count($visibleAssignments);
$gradingPageCount=max(1,(int)ceil($gradingResultCount/4));
$gradingPage=min($gradingPageCount,max(1,(int)($_GET['page']??1)));
$visibleAssignments=array_slice($visibleAssignments,($gradingPage-1)*4,4);
?>
<?php require __DIR__.'/../includes/views/grading.php'; ?>
