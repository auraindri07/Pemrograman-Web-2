<?php
/**
 * EduLMS - Mata Pelajaran Siswa
 * List of subjects with real data from database
 */

require_once '../includes/db.php';
require_once '../includes/auth_check.php';
require_once '../includes/functions.php';

// Require student authentication
requireStudent();

$user_id = currentUserId();
$nama_lengkap = $_SESSION['nama_lengkap'] ?? 'Siswa';

// Get search parameter
$search = trim($_GET['search'] ?? '');

// Get all subjects for this student
$sql = "
    SELECT DISTINCT 
        mp.id,
        mp.nama_mapel,
        mp.deskripsi,
        mp.icon_emoji,
        mp.color_class,
        u.nama_lengkap as nama_guru,
        k.nama_kelas,
        (SELECT COUNT(*) FROM materi m WHERE m.guru_mapel_id = gm.id AND m.is_published = 1) as jumlah_materi,
        (SELECT COUNT(*) FROM tugas t WHERE t.guru_mapel_id = gm.id AND t.is_published = 1) as jumlah_tugas,
        gm.id as guru_mapel_id
    FROM siswa_kelas sk
    JOIN kelas k ON sk.kelas_id = k.id
    JOIN guru_mapel gm ON gm.kelas_id = k.id AND gm.tahun_ajaran = sk.tahun_ajaran
    JOIN mata_pelajaran mp ON gm.mapel_id = mp.id
    JOIN users u ON gm.guru_id = u.id
    WHERE sk.siswa_id = ? AND sk.status = 'aktif' AND gm.is_active = 1
";

$params = [$user_id];

if (!empty($search)) {
    $sql .= " AND (mp.nama_mapel LIKE ? OR u.nama_lengkap LIKE ?)";
    $searchParam = "%$search%";
    $params[] = $searchParam;
    $params[] = $searchParam;
}

$sql .= " ORDER BY mp.id,gm.id";

$stmtMapel = $pdo->prepare($sql);
$stmtMapel->execute($params);
$mataPelajaran = $stmtMapel->fetchAll();

// Color mapping
$colorMap = [
    'teal' => 'bg-teal-100 text-[#006d5b]',
    'blue' => 'bg-blue-100 text-blue-700',
    'indigo' => 'bg-indigo-100 text-indigo-700',
    'emerald' => 'bg-emerald-100 text-emerald-700',
    'amber' => 'bg-amber-100 text-amber-700',
    'rose' => 'bg-rose-100 text-rose-700',
    'purple' => 'bg-purple-100 text-purple-700',
];
?>
<?php require __DIR__.'/../includes/views/subjects.php'; ?>
