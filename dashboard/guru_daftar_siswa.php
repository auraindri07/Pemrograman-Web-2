<?php
/**
 * EduLMS - Daftar Siswa per Kelas
 * Teacher view of student list in specific class
 */

require_once '../includes/db.php';
require_once '../includes/auth_check.php';
require_once '../includes/functions.php';

requireTeacher();

$user_id = currentUserId();
$nama_lengkap = $_SESSION['nama_lengkap'] ?? 'Guru';

$guru_mapel_id = $_GET['guru_mapel_id'] ?? null;

if (!$guru_mapel_id) {
    header("Location: guru_kelas.php");
    exit();
}

// Verify access
$stmtAccess = $pdo->prepare("
    SELECT 
        mp.nama_mapel,
        k.nama_kelas,
        k.tingkat,
        k.jurusan,
        gm.hari,
        gm.jam_mulai,
        gm.jam_selesai,
        gm.ruang
    FROM guru_mapel gm
    JOIN mata_pelajaran mp ON gm.mapel_id = mp.id
    JOIN kelas k ON gm.kelas_id = k.id
    WHERE gm.id = ? AND gm.guru_id = ? AND gm.is_active = 1
");
$stmtAccess->execute([$guru_mapel_id, $user_id]);
$classInfo = $stmtAccess->fetch();

if (!$classInfo) {
    http_response_code(403); die('Akses ditolak!');
}

// Get students
$stmtStudents = $pdo->prepare("
    SELECT 
        u.id,
        u.nama_lengkap,
        u.email,
        u.nisn,
        sk.status,
        (SELECT COUNT(*) FROM pengumpulan_tugas pt 
         JOIN tugas t ON pt.tugas_id = t.id 
         WHERE pt.siswa_id = u.id AND t.guru_mapel_id = ?) as tugas_terkumpul,
        (SELECT COUNT(*) FROM tugas t WHERE t.guru_mapel_id = ? AND t.is_published = 1) as total_tugas,
        (SELECT COUNT(*) FROM presensi p WHERE p.siswa_id = u.id AND p.guru_mapel_id = ? AND p.status_kehadiran = 'hadir') as hadir,
        (SELECT COUNT(*) FROM presensi p WHERE p.siswa_id = u.id AND p.guru_mapel_id = ?) as total_sesi
    FROM siswa_kelas sk
    JOIN users u ON sk.siswa_id = u.id
    JOIN guru_mapel gm ON sk.kelas_id = gm.kelas_id AND sk.tahun_ajaran = gm.tahun_ajaran
    WHERE gm.id = ? AND sk.status = 'aktif'
    ORDER BY u.nama_lengkap
");
$stmtStudents->execute([$guru_mapel_id, $guru_mapel_id, $guru_mapel_id, $guru_mapel_id, $guru_mapel_id]);
$students = $stmtStudents->fetchAll();

$totalSiswa = count($students);
$studentPager=uiPaginate($students,10);$students=$studentPager['items'];
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Daftar Siswa - EduLMS Portal Guru</title>
    <link rel="stylesheet" href="../assets/css/tailwind.css">
    <link rel="stylesheet" href="../assets/fonts/jakarta.css">
    <style>
        body { font-family: 'Plus Jakarta Sans', sans-serif; }
    </style>
<?php require __DIR__ . '/../includes/portal_assets.php'; ?>
</head>
<body class="page-guru_daftar_siswa bg-slate-50 text-slate-800 min-h-screen">

    <!-- Navbar -->
    <?php require __DIR__ . '/../includes/portal_nav.php'; ?>

    <main class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8 space-y-6">

        <!-- Back Button -->
        <a href="guru_kelas.php" class="inline-flex items-center space-x-2 text-xs font-semibold text-slate-600 hover:text-teal-700">
            <span><?= uiIcon('chevron-left',18) ?></span>
            <span>Kembali ke Kelas & Mata Pelajaran</span>
        </a>

        <!-- Class Info Header -->
        <div class="bg-white rounded-2xl p-6 border border-slate-200 shadow-sm">
            <div class="flex items-start justify-between">
                <div>
                    <h1 class="text-xl font-bold text-slate-900 mb-2"><?php echo e($classInfo['nama_kelas']); ?> - <?php echo e($classInfo['nama_mapel']); ?></h1>
                    <div class="flex flex-wrap items-center gap-3 text-xs text-slate-600">
                        <?php if ($classInfo['hari']): ?>
                        <span><i class="fa-solid fa-calendar-days"></i> <?php echo e($classInfo['hari']); ?>, <?php echo substr($classInfo['jam_mulai'], 0, 5); ?> - <?php echo substr($classInfo['jam_selesai'], 0, 5); ?></span>
                        <span>•</span>
                        <?php endif; ?>
                        <?php if ($classInfo['ruang']): ?>
                        <span><i class="fa-solid fa-school"></i> <?php echo e($classInfo['ruang']); ?></span>
                        <span>•</span>
                        <?php endif; ?>
                        <span><i class="fa-solid fa-users"></i> <?php echo $totalSiswa; ?> Siswa Aktif</span>
                    </div>
                </div>
                <div class="px-4 py-2 bg-teal-50 border border-teal-200 rounded-xl text-xs font-bold text-teal-700">
                    Tingkat <?php echo $classInfo['tingkat']; ?> <?php echo e($classInfo['jurusan']); ?>
                </div>
            </div>
        </div>

        <!-- Student List -->
        <div class="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden">
            <div class="p-6 border-b border-slate-200">
                <h3 class="text-sm font-bold text-slate-900">Daftar Siswa</h3>
            </div>
            
            <?php if ($totalSiswa > 0): ?>
            <div class="overflow-x-auto">
                <table class="w-full text-xs">
                    <thead class="bg-slate-50 border-b border-slate-200">
                        <tr>
                            <th class="px-4 py-3 text-left font-bold text-slate-700">No</th>
                            <th class="px-4 py-3 text-left font-bold text-slate-700">NISN</th>
                            <th class="px-4 py-3 text-left font-bold text-slate-700">Nama Lengkap</th>
                            <th class="px-4 py-3 text-left font-bold text-slate-700">Email</th>
                            <th class="px-4 py-3 text-center font-bold text-slate-700">Tugas</th>
                            <th class="px-4 py-3 text-center font-bold text-slate-700">Kehadiran</th>
                            <th class="px-4 py-3 text-center font-bold text-slate-700">Status</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        <?php $no = ($studentPager['page']-1)*10+1; foreach ($students as $student): 
                            $persentase_tugas = $student['total_tugas'] > 0 ? round(($student['tugas_terkumpul'] / $student['total_tugas']) * 100) : 0;
                            $persentase_hadir = $student['total_sesi'] > 0 ? round(($student['hadir'] / $student['total_sesi']) * 100) : 0;
                        ?>
                        <tr class="hover:bg-slate-50 transition">
                            <td class="px-4 py-3 text-slate-600"><?php echo $no++; ?></td>
                            <td class="px-4 py-3 text-slate-600 font-mono"><?php echo e($student['nisn']); ?></td>
                            <td class="px-4 py-3">
                                <div class="font-semibold text-slate-900"><?php echo e($student['nama_lengkap']); ?></div>
                            </td>
                            <td class="px-4 py-3 text-slate-600"><?php echo e($student['email']); ?></td>
                            <td class="px-4 py-3 text-center">
                                <div class="text-sm font-bold text-slate-900"><?php echo $student['tugas_terkumpul']; ?>/<?php echo $student['total_tugas']; ?></div>
                                <div class="text-[10px] <?php echo $persentase_tugas >= 80 ? 'text-emerald-600' : ($persentase_tugas >= 50 ? 'text-amber-600' : 'text-rose-600'); ?>">
                                    <?php echo $persentase_tugas; ?>%
                                </div>
                            </td>
                            <td class="px-4 py-3 text-center">
                                <div class="text-sm font-bold text-slate-900"><?php echo $student['hadir']; ?>/<?php echo $student['total_sesi']; ?></div>
                                <div class="text-[10px] <?php echo $persentase_hadir >= 80 ? 'text-emerald-600' : ($persentase_hadir >= 50 ? 'text-amber-600' : 'text-rose-600'); ?>">
                                    <?php echo $persentase_hadir; ?>%
                                </div>
                            </td>
                            <td class="px-4 py-3 text-center">
                                <span class="px-2.5 py-1 bg-emerald-50 text-emerald-700 border border-emerald-200 rounded-lg font-bold text-[10px]">
                                    <i class="fa-solid fa-check"></i> <?php echo ucfirst($student['status']); ?>
                                </span>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <?php else: ?>
            <div class="p-12 text-center">
                <div class="text-6xl mb-4"><i class="fa-solid fa-users text-slate-300"></i></div>
                <h3 class="text-lg font-bold text-slate-900 mb-2">Belum Ada Siswa</h3>
                <p class="text-sm text-slate-500">Belum ada siswa terdaftar di kelas ini.</p>
            </div>
            <?php endif; ?>
        </div>

        <!-- Quick Actions -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <a href="guru_presensi.php?guru_mapel_id=<?php echo $guru_mapel_id; ?>" class="bg-white p-4 rounded-xl border border-slate-200 hover:shadow-md transition flex items-center space-x-3">
                <div class="w-10 h-10 rounded-lg bg-emerald-50 text-emerald-700 flex items-center justify-center text-lg"><i class="fa-solid fa-clipboard-list"></i></div>
                <div>
                    <div class="text-xs font-bold text-slate-900">Input Presensi</div>
                    <div class="text-[10px] text-slate-500">Catat kehadiran siswa</div>
                </div>
            </a>
            
            <a href="guru_materi.php?guru_mapel_id=<?php echo $guru_mapel_id; ?>" class="bg-white p-4 rounded-xl border border-slate-200 hover:shadow-md transition flex items-center space-x-3">
                <div class="w-10 h-10 rounded-lg bg-blue-50 text-blue-700 flex items-center justify-center text-lg"><i class="fa-solid fa-book-open"></i></div>
                <div>
                    <div class="text-xs font-bold text-slate-900">Kelola Materi</div>
                    <div class="text-[10px] text-slate-500">Upload materi pembelajaran</div>
                </div>
            </a>
            
            <a href="guru_buat_tugas.php" class="bg-white p-4 rounded-xl border border-slate-200 hover:shadow-md transition flex items-center space-x-3">
                <div class="w-10 h-10 rounded-lg bg-teal-50 text-teal-700 flex items-center justify-center text-lg"><i class="fa-solid fa-file-lines"></i></div>
                <div>
                    <div class="text-xs font-bold text-slate-900">Buat Tugas Baru</div>
                    <div class="text-[10px] text-slate-500">Tambah penugasan siswa</div>
                </div>
            </a>
        </div>

        <?php uiPagination($studentPager['page'],$studentPager['pages'],$studentPager['total'],'siswa'); ?>
    </main>

<?php require __DIR__ . '/../includes/portal_footer.php'; ?>
</body>
</html>
