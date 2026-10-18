<?php
/**
 * EduLMS - Detail Mata Pelajaran
 * Subject detail page with materials and assignments
 */

require_once '../includes/db.php';
require_once '../includes/auth_check.php';
require_once '../includes/functions.php';

requireStudent();

$user_id = currentUserId();
$nama_lengkap = $_SESSION['nama_lengkap'] ?? 'Siswa';

$guru_mapel_id = $_GET['id'] ?? null;

if (!$guru_mapel_id) {
    header("Location: mata_pelajaran.php");
    exit();
}

// Verify student has access to this subject
$stmtAccess = $pdo->prepare("
    SELECT 
        mp.id,
        mp.nama_mapel,
        mp.deskripsi,
        mp.icon_emoji,
        mp.color_class,
        u.nama_lengkap as nama_guru,
        k.nama_kelas,
        gm.id as guru_mapel_id,
        gm.hari,
        gm.jam_mulai,
        gm.jam_selesai,
        gm.ruang
    FROM siswa_kelas sk
    JOIN kelas k ON sk.kelas_id = k.id
    JOIN guru_mapel gm ON gm.kelas_id = k.id AND gm.tahun_ajaran = sk.tahun_ajaran
    JOIN mata_pelajaran mp ON gm.mapel_id = mp.id
    JOIN users u ON gm.guru_id = u.id
    WHERE sk.siswa_id = ? AND gm.id = ? AND sk.status = 'aktif' AND gm.is_active = 1
");
$stmtAccess->execute([$user_id, $guru_mapel_id]);
$mapel = $stmtAccess->fetch();

if (!$mapel) {
    http_response_code(403);
    die('Anda tidak memiliki akses ke mata pelajaran ini.');
}

// Get materials
$stmtMateri = $pdo->prepare("
    SELECT * FROM materi 
    WHERE guru_mapel_id = ? AND is_published = 1
    ORDER BY urutan_modul, created_at DESC
");
$stmtMateri->execute([$guru_mapel_id]);
$materials = $stmtMateri->fetchAll();

// Get assignments
$stmtTugas = $pdo->prepare("
    SELECT 
        t.*,
        pt.id as pengumpulan_id,
        pt.tanggal_kumpul,
        pt.nilai,
        pt.status as status_pengumpulan
    FROM tugas t
    LEFT JOIN pengumpulan_tugas pt ON pt.tugas_id = t.id AND pt.siswa_id = ?
    WHERE t.guru_mapel_id = ? AND t.is_published = 1
    ORDER BY t.batas_pengumpulan DESC
");
$stmtTugas->execute([$user_id, $guru_mapel_id]);
$assignments = $stmtTugas->fetchAll();
$materialPager=uiPaginate($materials,5,'materi_page');$materials=$materialPager['items'];
$assignmentPager=uiPaginate($assignments,5,'tugas_page');$assignments=$assignmentPager['items'];

$colorMap = [
    'teal' => 'bg-teal-100 text-[#006d5b] border-teal-200',
    'blue' => 'bg-blue-100 text-blue-700 border-blue-200',
    'indigo' => 'bg-indigo-100 text-indigo-700 border-indigo-200',
    'emerald' => 'bg-emerald-100 text-emerald-700 border-emerald-200',
    'amber' => 'bg-amber-100 text-amber-700 border-amber-200',
    'rose' => 'bg-rose-100 text-rose-700 border-rose-200',
];
$colorClass = $colorMap[$mapel['color_class']] ?? 'bg-teal-100 text-[#006d5b] border-teal-200';
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo e($mapel['nama_mapel']); ?> - EduLMS</title>
    <link rel="stylesheet" href="../assets/css/tailwind.css">
    <link rel="stylesheet" href="../assets/fonts/jakarta.css">
    <style>
        body { font-family: 'Plus Jakarta Sans', sans-serif; }
    </style>
<?php require __DIR__ . '/../includes/portal_assets.php'; ?>
</head>
<body class="page-mapel_detail bg-slate-50 text-slate-800 min-h-screen">

    <!-- Navbar -->
    <?php require __DIR__ . '/../includes/portal_nav.php'; ?>

    <!-- Main Content -->
    <main class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8 space-y-6">

        <!-- Back Button -->
        <a href="mata_pelajaran.php" class="inline-flex items-center space-x-2 text-xs font-semibold text-slate-600 hover:text-[#006d5b] transition">
            <span><?= uiIcon('chevron-left',18) ?></span>
            <span>Kembali ke Mata Pelajaran</span>
        </a>

        <!-- Subject Header -->
        <div class="bg-white rounded-2xl p-6 border border-slate-200 shadow-sm">
            <div class="flex items-start justify-between gap-6">
                <div class="flex items-start gap-4">
                    <div class="w-16 h-16 rounded-2xl <?php echo $colorClass; ?> font-bold flex items-center justify-center text-2xl border">
                        <?php echo uiSubjectGlyph($mapel['nama_mapel'], $mapel['icon_emoji'], 28); ?>
                    </div>
                    <div>
                        <h1 class="text-2xl font-bold text-slate-900 mb-1"><?php echo e($mapel['nama_mapel']); ?></h1>
                        <p class="text-sm text-slate-600 mb-3"><?php echo e($mapel['deskripsi'] ?? 'Mata pelajaran ' . $mapel['nama_mapel']); ?></p>
                        <div class="flex flex-wrap items-center gap-3 text-xs text-slate-500">
                            <span class="flex items-center space-x-1.5">
                                <span><?= uiIcon('teacher',18) ?></span>
                                <span><?php echo e($mapel['nama_guru']); ?></span>
                            </span>
                            <span>•</span>
                            <span class="flex items-center space-x-1.5">
                                <span><?= uiIcon('school',18) ?></span>
                                <span><?php echo e($mapel['nama_kelas']); ?></span>
                            </span>
                            <?php if ($mapel['hari']): ?>
                            <span>•</span>
                            <span class="flex items-center space-x-1.5">
                                <span><?= uiIcon('calendar',18) ?></span>
                                <span><?php echo e($mapel['hari']); ?>,
                                    <?php $jamMulai = formatJam($mapel['jam_mulai']); $jamSelesai = formatJam($mapel['jam_selesai']); ?>
                                    <?php if ($jamMulai !== '-' && $jamSelesai !== '-'): ?>
                                        <?= e($jamMulai.' - '.$jamSelesai) ?>
                                    <?php elseif ($jamMulai !== '-'): ?>
                                        <?= e($jamMulai) ?> <span class="text-slate-400">(jam selesai belum diatur)</span>
                                    <?php elseif ($jamSelesai !== '-'): ?>
                                        Selesai <?= e($jamSelesai) ?>
                                    <?php else: ?>
                                        Jam belum diatur
                                    <?php endif; ?>
                                </span>
                            </span>
                            <?php endif; ?>
                            <?php if ($mapel['ruang']): ?>
                            <span>•</span>
                            <span class="flex items-center space-x-1.5">
                                <span><?= uiIcon('door',18) ?></span>
                                <span><?php echo e($mapel['ruang']); ?></span>
                            </span>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                <div class="flex items-center gap-2">
                    <div class="px-4 py-2 bg-teal-50 border border-teal-200 rounded-xl text-xs font-bold text-[#006d5b]">
                        <?php echo $materialPager['total']; ?> Materi
                    </div>
                    <div class="px-4 py-2 bg-amber-50 border border-amber-200 rounded-xl text-xs font-bold text-amber-700">
                        <?php echo $assignmentPager['total']; ?> Tugas
                    </div>
                </div>
            </div>
        </div>

        <!-- Tabs -->
        <div class="bg-white rounded-2xl p-1 border border-slate-200 shadow-sm inline-flex gap-1">
            <button onclick="showTab('materi')" id="tab-materi" class="tab-btn px-6 py-2 rounded-xl text-xs font-bold bg-teal-600 text-white transition">
                <?= uiIcon('book',18) ?> Materi Pembelajaran
            </button>
            <button onclick="showTab('tugas')" id="tab-tugas" class="tab-btn px-6 py-2 rounded-xl text-xs font-bold text-slate-600 hover:bg-slate-100 transition">
                <?= uiIcon('clipboard',18) ?> Tugas & Penilaian
            </button>
        </div>

        <!-- Tab Content: Materi -->
        <div id="content-materi" class="tab-content space-y-4">
            <?php if ($materialPager['total'] > 0): ?>
                <?php foreach ($materials as $materi): ?>
                <div class="bg-white rounded-2xl p-6 border border-slate-200 shadow-sm hover:shadow-md transition">
                    <div class="flex items-start justify-between gap-4">
                        <div class="flex-1">
                            <div class="flex items-center gap-3 mb-2">
                                <span class="px-2.5 py-1 bg-teal-50 border border-teal-200 text-[#006d5b] text-[10px] font-bold rounded-lg">
                                    Modul <?php echo $materi['urutan_modul']; ?>
                                </span>
                                <span class="text-[10px] text-slate-400"><?php echo formatTanggalIndo($materi['published_at']); ?></span>
                            </div>
                            <h3 class="text-base font-bold text-slate-900 mb-2"><?php echo e($materi['judul_materi']); ?></h3>
                            <?php if ($materi['deskripsi']): ?>
                            <p class="text-xs text-slate-600 mb-3"><?php echo e(uiDisplayText($materi['deskripsi'])); ?></p>
                            <?php endif; ?>
                            
                            <div class="flex flex-wrap items-center gap-3">
                                <?php if ($materi['file_path']): ?>
                                <a href="download.php?type=materi&id=<?php echo $materi['id']; ?>" target="_blank" class="inline-flex items-center space-x-2 px-4 py-2 bg-teal-600 hover:bg-teal-700 text-white text-xs font-bold rounded-xl transition">
                                    <span><?= uiIcon('download',18) ?></span>
                                    <span>Download File</span>
                                    <span class="text-[10px] opacity-80">(<?php echo formatFileSize($materi['file_size']); ?>)</span>
                                </a>
                                <?php endif; ?>
                                
                                <?php if ($materi['video_url']): ?>
                                <a href="<?php echo e($materi['video_url']); ?>" target="_blank" class="inline-flex items-center space-x-2 px-4 py-2 bg-rose-600 hover:bg-rose-700 text-white text-xs font-bold rounded-xl transition">
                                    <span><?= uiIcon('play',18) ?></span>
                                    <span>Tonton Video</span>
                                </a>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            <?php else: ?>
            <div class="bg-white rounded-2xl p-12 border border-slate-200 shadow-sm text-center">
                <div class="text-6xl mb-4"><?= uiIcon('book',18) ?></div>
                <h3 class="text-lg font-bold text-slate-900 mb-2">Belum Ada Materi</h3>
                <p class="text-sm text-slate-500">Materi pembelajaran akan ditambahkan oleh guru.</p>
            </div>
            <?php endif; ?>
            <?php uiPagination($materialPager['page'],$materialPager['pages'],$materialPager['total'],'materi','materi_page',['tab'=>'materi']); ?>
        </div>

        <!-- Tab Content: Tugas -->
        <div id="content-tugas" class="tab-content space-y-4 hidden">
            <?php if ($assignmentPager['total'] > 0): ?>
                <?php foreach ($assignments as $tugas): 
                    $isPassed = isDeadlinePassed($tugas['batas_pengumpulan']);
                    $isClose = isDeadlineClose($tugas['batas_pengumpulan']);
                ?>
                <div class="bg-white rounded-2xl p-6 border border-slate-200 shadow-sm hover:shadow-md transition">
                    <div class="flex items-start justify-between gap-4">
                        <div class="flex-1">
                            <div class="flex items-center gap-3 mb-2">
                                <?php if ($tugas['pengumpulan_id']): ?>
                                    <?php if ($tugas['nilai'] !== null): ?>
                                    <span class="px-3 py-1 bg-emerald-50 border border-emerald-200 text-emerald-700 text-xs font-bold rounded-lg">
                                        <?= uiIcon('check',18) ?> Dinilai
                                    </span>
                                    <?php else: ?>
                                    <span class="px-3 py-1 bg-blue-50 border border-blue-200 text-blue-700 text-xs font-bold rounded-lg">
                                        <?= uiIcon('clock',18) ?> Menunggu Penilaian
                                    </span>
                                    <?php endif; ?>
                                <?php elseif ($isPassed): ?>
                                    <span class="px-3 py-1 bg-rose-50 border border-rose-200 text-rose-700 text-xs font-bold rounded-lg">
                                        <?= uiIcon('alarm',18) ?> Terlambat
                                    </span>
                                <?php elseif ($isClose): ?>
                                    <span class="px-3 py-1 bg-amber-50 border border-amber-200 text-amber-700 text-xs font-bold rounded-lg animate-pulse">
                                        <?= uiIcon('bell',18) ?> Mendesak
                                    </span>
                                <?php else: ?>
                                    <span class="px-3 py-1 bg-slate-50 border border-slate-200 text-slate-700 text-xs font-bold rounded-lg">
                                        <?= uiIcon('clipboard',18) ?> Belum Dikumpulkan
                                    </span>
                                <?php endif; ?>
                                
                                <span class="text-[10px] text-slate-400">
                                    Deadline: <?php echo formatTanggalIndo($tugas['batas_pengumpulan'], 'd M Y H:i'); ?>
                                </span>
                            </div>
                            
                            <h3 class="text-base font-bold text-slate-900 mb-2"><?php echo e($tugas['judul_tugas']); ?></h3>
                            <p class="text-xs text-slate-600 mb-3"><?php echo e(uiDisplayText($tugas['deskripsi'])); ?></p>
                            
                            <?php if ($tugas['pengumpulan_id']): ?>
                                <div class="bg-slate-50 border border-slate-200 rounded-xl p-4 mb-3">
                                    <div class="flex items-center justify-between mb-2">
                                        <span class="text-xs font-bold text-slate-700">Status Pengumpulan:</span>
                                        <span class="text-xs text-slate-600">
                                            <?php echo formatTanggalIndo($tugas['tanggal_kumpul'], 'd M Y H:i'); ?>
                                        </span>
                                    </div>
                                    <?php if ($tugas['nilai'] !== null): ?>
                                    <div class="flex items-center justify-between">
                                        <span class="text-xs font-bold text-slate-700">Nilai:</span>
                                        <span class="text-lg font-bold text-teal-700"><?php echo $tugas['nilai']; ?></span>
                                    </div>
                                    <?php endif; ?>
                                </div>
                            <?php endif; ?>
                            
                            <div class="flex items-center gap-3">
                                <?php if (!$tugas['pengumpulan_id']): ?>
                                <a href="kumpulkan_tugas.php?id=<?php echo $tugas['id']; ?>" class="inline-flex items-center space-x-2 px-4 py-2 bg-teal-600 hover:bg-teal-700 text-white text-xs font-bold rounded-xl transition">
                                    <span><?= uiIcon('upload',18) ?></span>
                                    <span>Kumpulkan Tugas</span>
                                </a>
                                <?php else: ?>
                                <span class="inline-flex items-center space-x-2 px-4 py-2 bg-emerald-50 border border-emerald-200 text-emerald-700 text-xs font-bold rounded-xl">
                                    <span><?= uiIcon('check',18) ?></span>
                                    <span>Sudah Dikumpulkan</span>
                                </span>
                                <?php endif; ?>
                                
                                <?php if ($tugas['file_path']): ?>
                                <a href="download.php?type=tugas&id=<?php echo $tugas['id']; ?>" target="_blank" class="inline-flex items-center space-x-2 px-4 py-2 bg-slate-100 hover:bg-slate-200 text-slate-700 text-xs font-bold rounded-xl transition border border-slate-200">
                                    <span><?= uiIcon('file',18) ?></span>
                                    <span>Unduh Soal</span>
                                </a>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            <?php else: ?>
            <div class="bg-white rounded-2xl p-12 border border-slate-200 shadow-sm text-center">
                <div class="text-6xl mb-4"><?= uiIcon('clipboard',18) ?></div>
                <h3 class="text-lg font-bold text-slate-900 mb-2">Belum Ada Tugas</h3>
                <p class="text-sm text-slate-500">Tugas akan ditambahkan oleh guru.</p>
            </div>
            <?php endif; ?>
            <?php uiPagination($assignmentPager['page'],$assignmentPager['pages'],$assignmentPager['total'],'tugas','tugas_page',['tab'=>'tugas']); ?>
        </div>

    </main>

    <script>
        function showTab(tab) {
            // Hide all content
            document.querySelectorAll('.tab-content').forEach(content => {
                content.classList.add('hidden');
            });
            
            // Remove active class from all tabs
            document.querySelectorAll('.tab-btn').forEach(btn => {
                btn.classList.remove('bg-teal-600', 'text-white');
                btn.classList.add('text-slate-600', 'hover:bg-slate-100');
            });
            
            // Show selected content
            document.getElementById('content-' + tab).classList.remove('hidden');
            
            // Add active class to selected tab
            const activeTab = document.getElementById('tab-' + tab);
            activeTab.classList.add('bg-teal-600', 'text-white');
            activeTab.classList.remove('text-slate-600', 'hover:bg-slate-100');
        }
        showTab(<?= json_encode(($_GET['tab']??'')==='tugas'?'tugas':'materi') ?>);
    </script>

<?php require __DIR__ . '/../includes/portal_footer.php'; ?>
</body>
</html>
