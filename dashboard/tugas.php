<?php
/**
 * EduLMS - Tugas Siswa
 * List of assignments with real data from database
 */

require_once '../includes/db.php';
require_once '../includes/auth_check.php';
require_once '../includes/functions.php';

// Require student authentication
requireStudent();

$user_id = currentUserId();
$nama_lengkap = $_SESSION['nama_lengkap'] ?? 'Siswa';

// Get filter
$filter = $_GET['filter'] ?? 'all';

// Get all assignments for this student
$sql = "
    SELECT 
        t.id,
        t.judul_tugas,
        t.deskripsi,
        t.batas_pengumpulan,
        t.format_file,
        t.file_path,
        mp.nama_mapel,
        mp.icon_emoji,
        mp.color_class,
        pt.id as pengumpulan_id,
        pt.tanggal_kumpul,
        pt.nilai,
        pt.feedback,
        pt.status as status_pengumpulan
    FROM siswa_kelas sk
    JOIN kelas k ON sk.kelas_id = k.id
    JOIN guru_mapel gm ON gm.kelas_id = k.id AND gm.tahun_ajaran = sk.tahun_ajaran
    JOIN tugas t ON t.guru_mapel_id = gm.id
    JOIN mata_pelajaran mp ON gm.mapel_id = mp.id
    LEFT JOIN pengumpulan_tugas pt ON pt.tugas_id = t.id AND pt.siswa_id = sk.siswa_id
    WHERE sk.siswa_id = ? 
        AND sk.status = 'aktif'
        AND t.is_published = 1
";

$params = [$user_id];

$sql .= " AND gm.is_active = 1 ORDER BY t.batas_pengumpulan ASC";
$stmtTugas = $pdo->prepare($sql);
$stmtTugas->execute($params);
$allTugas = $stmtTugas->fetchAll();
$totalTugas = count($allTugas);
$sudahSelesai = count(array_filter($allTugas, fn($t) => $t['pengumpulan_id'] !== null));
$belumSelesai = $totalTugas - $sudahSelesai;
$filter = in_array($filter, ['all','belum','sudah']) ? $filter : 'all';
$daftarTugas = array_values(array_filter($allTugas, fn($t) => $filter === 'all' || ($filter === 'belum' ? $t['pengumpulan_id'] === null : $t['pengumpulan_id'] !== null)));
if (($_GET['urut']??'')==='jauh') $daftarTugas=array_reverse($daftarTugas);
$taskResultCount=count($daftarTugas);
$taskPageCount=max(1,(int)ceil($taskResultCount/5));
$taskPage=min($taskPageCount,max(1,(int)($_GET['page']??1)));
$daftarTugas=array_slice($daftarTugas,($taskPage-1)*5,5);

// Color mapping
$colorMap = [
    'teal' => 'bg-teal-100 text-[#006d5b]',
    'blue' => 'bg-blue-100 text-blue-700',
    'indigo' => 'bg-indigo-100 text-indigo-700',
    'emerald' => 'bg-emerald-100 text-emerald-700',
    'amber' => 'bg-amber-100 text-amber-700',
    'rose' => 'bg-rose-100 text-rose-700',
];
?>
<?php require __DIR__.'/../includes/views/tasks.php'; ?>
