<?php
/**
 * EduLMS - Pengumuman
 * Announcements with real data from database
 */

require_once '../includes/db.php';
require_once '../includes/auth_check.php';
require_once '../includes/functions.php';

requireAuth();

$user_id = currentUserId();
$nama_lengkap = $_SESSION['nama_lengkap'] ?? 'Pengguna';
$role = currentUserRole();

// Get search parameter
$search = trim($_GET['search'] ?? '');
$category = $_GET['kategori'] ?? '';
$page = max(1, (int)($_GET['page'] ?? 1));
$perPage = 3;

// Get announcements
$sql = "
    SELECT
        p.id,
        p.judul_pengumuman,
        p.isi_pengumuman,
        p.kategori,
        p.tanggal_terbit,
        u.nama_lengkap as pembuat
    FROM pengumuman p
    JOIN users u ON p.pembuat_id = u.id
    WHERE p.is_published = 1 AND p.tanggal_terbit <= NOW()
        AND (p.target_role = 'semua' OR p.target_role = ?)
";

$params = [$role];

if (!empty($search)) {
    $sql .= " AND (p.judul_pengumuman LIKE ? OR p.isi_pengumuman LIKE ?)";
    $searchParam = "%$search%";
    $params[] = $searchParam;
    $params[] = $searchParam;
}

if (in_array($category, ['penting','akademik','agenda','kegiatan','umum'])) {
    $sql .= " AND p.kategori = ?"; $params[] = $category;
}
$counter = $pdo->prepare("SELECT COUNT(*) FROM ($sql) AS filtered");
$counter->execute($params);
$totalAnnouncements = (int)$counter->fetchColumn();
$pageCount = max(1, (int)ceil($totalAnnouncements / $perPage));
$page = min($page, $pageCount);
$offset = ($page - 1) * $perPage;
$sql .= " ORDER BY p.tanggal_terbit DESC, p.id DESC LIMIT $perPage OFFSET $offset";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$pengumuman = $stmt->fetchAll();
// Kategori mapping
$kategoriMap = [
    'penting' => ['label' => 'Penting & Akademik', 'icon' => (uiIcon('megaphone',18)), 'class' => 'bg-teal-50 text-[#006d5b] border-teal-200'],
    'akademik' => ['label' => 'Akademik', 'icon' => '<i class="fa-solid fa-book"></i>', 'class' => 'bg-blue-50 text-blue-700 border-blue-200'],
    'agenda' => ['label' => 'Agenda Libur', 'icon' => (uiIcon('calendar',18)), 'class' => 'bg-amber-50 text-amber-700 border-amber-200'],
    'kegiatan' => ['label' => 'Kegiatan Siswa', 'icon' => (uiIcon('trend',18)), 'class' => 'bg-blue-50 text-blue-700 border-blue-200'],
    'umum' => ['label' => 'Umum', 'icon' => 'ℹ️', 'class' => 'bg-slate-50 text-slate-700 border-slate-200'],
];
?>
<?php require __DIR__.'/../includes/views/announcements.php'; ?>
