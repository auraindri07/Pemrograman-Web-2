<?php
/**
 * EduLMS - Kelas & Mata Pelajaran Guru
 * Teacher's class and subject management
 */

require_once '../includes/db.php';
require_once '../includes/auth_check.php';
require_once '../includes/functions.php';

requireTeacher();

$user_id = currentUserId();
$nama_lengkap = $_SESSION['nama_lengkap'] ?? 'Guru';

// Get filter
$kelas_filter = $_GET['kelas'] ?? 'all';
$search = trim($_GET['search'] ?? '');

// Get teacher's classes and subjects
$sql = "
    SELECT DISTINCT
        k.id as kelas_id,
        k.nama_kelas,
        k.tingkat,
        mp.id as mapel_id,
        mp.nama_mapel,
        mp.icon_emoji,
        mp.color_class, mp.kategori,
 (SELECT nama_lengkap FROM users WHERE id=k.wali_kelas_id) AS nama_wali,
 (SELECT COUNT(*) FROM materi WHERE guru_mapel_id=gm.id) AS planned_modules,
 (SELECT judul_materi FROM materi WHERE guru_mapel_id=gm.id AND is_published=1 ORDER BY urutan_modul DESC,id DESC LIMIT 1) AS latest_module,
        gm.id as guru_mapel_id,
        gm.hari,
        gm.jam_mulai,
        gm.ruang,
        (SELECT COUNT(*) FROM siswa_kelas WHERE kelas_id = k.id AND status = 'aktif') as jumlah_siswa,
        (SELECT COUNT(*) FROM materi WHERE guru_mapel_id = gm.id AND is_published = 1) as jumlah_materi,
        (SELECT COUNT(*) FROM tugas WHERE guru_mapel_id = gm.id AND is_published = 1) as jumlah_tugas
    FROM guru_mapel gm
    JOIN kelas k ON gm.kelas_id = k.id
    JOIN mata_pelajaran mp ON gm.mapel_id = mp.id
    WHERE gm.guru_id = ? AND gm.is_active = 1
";

$params = [$user_id];

if (preg_match('/^level-(10|11|12)$/',(string)$kelas_filter,$levelMatch)) {
    $sql .= " AND k.tingkat = ?";
    $params[] = (int)$levelMatch[1];
} elseif ($kelas_filter !== 'all') {
    $sql .= " AND k.id = ?";
    $params[] = (int)$kelas_filter;
}

if ($search !== '') {
    $sql .= " AND (k.nama_kelas LIKE ? OR mp.nama_mapel LIKE ?)";
    $params[] = "%$search%"; $params[] = "%$search%";
}
$sql .= " ORDER BY k.tingkat DESC,k.nama_kelas, gm.id";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$daftarKelas = $stmt->fetchAll();

// Get unique classes for filter
$stmtKelas = $pdo->prepare("
    SELECT DISTINCT k.id, k.nama_kelas, k.tingkat
    FROM guru_mapel gm
    JOIN kelas k ON gm.kelas_id = k.id
    WHERE gm.guru_id = ? AND gm.is_active = 1
    ORDER BY k.nama_kelas
");
$stmtKelas->execute([$user_id]);
$kelasFilter = $stmtKelas->fetchAll();

$totalKelas = count($kelasFilter);
$totalMapel = count($daftarKelas);

$colorMap = [
    'teal' => 'bg-teal-100 text-[#006d5b]',
    'blue' => 'bg-blue-100 text-blue-700',
    'indigo' => 'bg-indigo-100 text-indigo-700',
    'emerald' => 'bg-emerald-100 text-emerald-700',
    'amber' => 'bg-amber-100 text-amber-700',
    'rose' => 'bg-rose-100 text-rose-700',
];
?>
<?php
$count=$pdo->prepare("SELECT COUNT(DISTINCT sk.siswa_id) FROM siswa_kelas sk JOIN guru_mapel gm ON gm.kelas_id=sk.kelas_id AND gm.tahun_ajaran=sk.tahun_ajaran WHERE gm.guru_id=? AND gm.is_active=1 AND sk.status='aktif'");$count->execute([$user_id]);$classStudentCount=$count->fetchColumn();
$hours=$pdo->prepare('SELECT SUM(TIME_TO_SEC(TIMEDIFF(jam_selesai,jam_mulai)))/2700 FROM guru_mapel WHERE guru_id=? AND is_active=1');$hours->execute([$user_id]);$teachingHours=round((float)$hours->fetchColumn(),1);
$sessions=$pdo->prepare('SELECT COUNT(DISTINCT p.guru_mapel_id,p.tanggal_sesi) FROM presensi p JOIN guru_mapel gm ON gm.id=p.guru_mapel_id WHERE gm.guru_id=?');$sessions->execute([$user_id]);$recordedSessions=$sessions->fetchColumn();
?>
<?php require __DIR__.'/../includes/views/classes.php'; ?>
