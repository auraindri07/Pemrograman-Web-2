<?php
/**
 * EduLMS - Presensi Siswa (View Only)
 * Student attendance record viewing page
 */

require_once '../includes/db.php';
require_once '../includes/auth_check.php';
require_once '../includes/functions.php';

requireStudent();

$user_id = currentUserId();
$nama_lengkap = $_SESSION['nama_lengkap'] ?? 'Siswa';

// Get filter
$bulan = $_GET['bulan'] ?? date('Y-m');

// Get student's attendance records
$stmtPresensi = $pdo->prepare("
    SELECT 
        p.*,
        mp.nama_mapel,
        mp.icon_emoji,
        k.nama_kelas,
        u.nama_lengkap as nama_guru
    FROM presensi p
    JOIN guru_mapel gm ON p.guru_mapel_id = gm.id
    JOIN mata_pelajaran mp ON gm.mapel_id = mp.id
    JOIN kelas k ON gm.kelas_id = k.id
    JOIN users u ON gm.guru_id = u.id
    WHERE p.siswa_id = ? 
        AND DATE_FORMAT(p.tanggal_sesi, '%Y-%m') = ?
    ORDER BY p.tanggal_sesi DESC, p.jam_sesi DESC
");
$stmtPresensi->execute([$user_id, $bulan]);
$presensi = $stmtPresensi->fetchAll();

// Calculate statistics for selected month
$hadir = 0;
$sakit = 0;
$izin = 0;
$alfa = 0;

foreach ($presensi as $p) {
    switch ($p['status_kehadiran']) {
        case 'hadir': $hadir++; break;
        case 'sakit': $sakit++; break;
        case 'izin': $izin++; break;
        case 'alfa': $alfa++; break;
    }
}

$total = count($presensi);
$persentase_hadir = $total > 0 ? round(($hadir / $total) * 100, 1) : 0;

// Get available months (for filter)
$stmtBulan = $pdo->prepare("
    SELECT DISTINCT DATE_FORMAT(tanggal_sesi, '%Y-%m') as bulan
    FROM presensi
    WHERE siswa_id = ?
    ORDER BY bulan DESC
");
$stmtBulan->execute([$user_id]);
$bulanList = $stmtBulan->fetchAll();
$presensiPager=uiPaginate($presensi,12);$presensi=$presensiPager['items'];
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Presensi Saya - EduLMS</title>
    <link rel="stylesheet" href="../assets/css/tailwind.css">
    <link rel="stylesheet" href="../assets/fonts/jakarta.css">
    <style>
        body { font-family: 'Plus Jakarta Sans', sans-serif; }
    </style>
<?php require __DIR__ . '/../includes/portal_assets.php'; ?>
</head>
<body class="page-presensi bg-slate-50 text-slate-800 min-h-screen">

    <!-- Navbar -->
    <?php require __DIR__ . '/../includes/portal_nav.php'; ?>

    <!-- Main Content -->
    <main class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8 space-y-6">

        <!-- Header -->
        <div class="bg-white rounded-2xl p-6 border border-slate-200 shadow-sm">
            <div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-4">
                <div>
                    <h1 class="text-xl font-bold text-slate-900 mb-1"><i class="fa-solid fa-clipboard-list"></i> Rekap Presensi Saya</h1>
                    <p class="text-xs text-slate-500">Riwayat kehadiran per mata pelajaran</p>
                </div>
                
                <!-- Filter Bulan -->
                <form method="GET" data-search-form class="flex items-center gap-2">
                    <label class="text-xs font-semibold text-slate-600">Bulan:</label>
                    <select name="bulan" onchange="this.form.submit()" class="px-3 py-2 text-xs border border-slate-300 rounded-xl focus:outline-none focus:border-teal-600 bg-white">
                        <?php if (count($bulanList) > 0): ?>
                            <?php foreach ($bulanList as $b): ?>
                            <option value="<?php echo $b['bulan']; ?>" <?php echo $b['bulan'] === $bulan ? 'selected' : ''; ?>>
                                <?php 
                                $date = DateTime::createFromFormat('Y-m', $b['bulan']);
                                echo $date->format('F Y'); 
                                ?>
                            </option>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <option value="<?php echo date('Y-m'); ?>"><?php echo date('F Y'); ?></option>
                        <?php endif; ?>
                    </select>
                </form>
            </div>
        </div>

        <!-- Statistics Cards -->
        <div class="grid grid-cols-2 md:grid-cols-5 gap-4">
            <div class="bg-white p-4 rounded-2xl border border-slate-200 shadow-sm">
                <div class="text-xs font-semibold text-slate-500 mb-1">Total Sesi</div>
                <div class="text-2xl font-bold text-slate-900"><?php echo $total; ?></div>
            </div>
            <div class="bg-emerald-50 p-4 rounded-2xl border border-emerald-200 shadow-sm">
                <div class="text-xs font-semibold text-emerald-700 mb-1"><i class="fa-solid fa-circle-check"></i> Hadir</div>
                <div class="text-2xl font-bold text-emerald-700"><?php echo $hadir; ?></div>
            </div>
            <div class="bg-amber-50 p-4 rounded-2xl border border-amber-200 shadow-sm">
                <div class="text-xs font-semibold text-amber-700 mb-1"><?= uiIcon('medical',18) ?> Sakit</div>
                <div class="text-2xl font-bold text-amber-700"><?php echo $sakit; ?></div>
            </div>
            <div class="bg-blue-50 p-4 rounded-2xl border border-blue-200 shadow-sm">
                <div class="text-xs font-semibold text-blue-700 mb-1"><i class="fa-solid fa-file-lines"></i> Izin</div>
                <div class="text-2xl font-bold text-blue-700"><?php echo $izin; ?></div>
            </div>
            <div class="bg-rose-50 p-4 rounded-2xl border border-rose-200 shadow-sm">
                <div class="text-xs font-semibold text-rose-700 mb-1"><?= uiIcon('close',18) ?> Alfa</div>
                <div class="text-2xl font-bold text-rose-700"><?php echo $alfa; ?></div>
            </div>
        </div>

        <!-- Persentase Kehadiran -->
        <div class="bg-white rounded-2xl p-6 border border-slate-200 shadow-sm">
            <div class="flex items-center justify-between mb-3">
                <h3 class="text-sm font-bold text-slate-900">Persentase Kehadiran</h3>
                <span class="text-2xl font-bold text-teal-700"><?php echo $persentase_hadir; ?>%</span>
            </div>
            <div class="w-full bg-slate-100 h-3 rounded-full overflow-hidden">
                <div class="bg-teal-600 h-full rounded-full transition-all" style="width: <?php echo $persentase_hadir; ?>%"></div>
            </div>
            <p class="text-xs text-slate-500 mt-2">
                <?php if ($persentase_hadir >= 90): ?>
                    Luar biasa! Tingkat kehadiran Anda sangat baik.
                <?php elseif ($persentase_hadir >= 75): ?>
                    Bagus! Pertahankan kehadiran Anda.
                <?php else: ?>
                    Perhatian: Tingkat kehadiran perlu ditingkatkan.
                <?php endif; ?>
            </p>
        </div>

        <!-- Tabel Presensi -->
        <div class="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden">
            <div class="p-6 border-b border-slate-200">
                <h3 class="text-sm font-bold text-slate-900">Detail Rekap Presensi</h3>
            </div>
            
            <?php if (count($presensi) > 0): ?>
            <div class="overflow-x-auto">
                <table class="w-full text-xs">
                    <thead class="bg-slate-50 border-b border-slate-200">
                        <tr>
                            <th class="px-4 py-3 text-left font-bold text-slate-700">Tanggal</th>
                            <th class="px-4 py-3 text-left font-bold text-slate-700">Mata Pelajaran</th>
                            <th class="px-4 py-3 text-left font-bold text-slate-700">Kelas</th>
                            <th class="px-4 py-3 text-left font-bold text-slate-700">Guru</th>
                            <th class="px-4 py-3 text-center font-bold text-slate-700">Status</th>
                            <th class="px-4 py-3 text-left font-bold text-slate-700">Keterangan</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        <?php foreach ($presensi as $p): ?>
                        <tr class="hover:bg-slate-50 transition">
                            <td class="px-4 py-3 text-slate-700 font-medium">
                                <?php echo formatTanggalIndo($p['tanggal_sesi']); ?>
                                <?php if ($p['jam_sesi']): ?>
                                <br><span class="text-[10px] text-slate-400"><?php echo e(formatJam($p['jam_sesi'])); ?></span>
                                <?php endif; ?>
                            </td>
                            <td class="px-4 py-3">
                                <div class="flex items-center space-x-2">
                                    <span><?php echo uiSubjectGlyph($p['nama_mapel'], $p['icon_emoji']); ?></span>
                                    <span class="text-slate-700 font-medium"><?php echo e($p['nama_mapel']); ?></span>
                                </div>
                            </td>
                            <td class="px-4 py-3 text-slate-600"><?php echo e($p['nama_kelas']); ?></td>
                            <td class="px-4 py-3 text-slate-600"><?php echo e($p['nama_guru']); ?></td>
                            <td class="px-4 py-3 text-center">
                                <?php
                                $statusBadge = [
                                    'hadir' => '<span class="px-3 py-1 bg-emerald-50 text-emerald-700 border border-emerald-200 rounded-lg font-bold inline-block"><i class="fa-solid fa-circle-check"></i> Hadir</span>',
                                    'sakit' => ('<span class="px-3 py-1 bg-amber-50 text-amber-700 border border-amber-200 rounded-lg font-bold inline-block">'.uiIcon('medical',18).' Sakit</span>'),
                                    'izin' => '<span class="px-3 py-1 bg-blue-50 text-blue-700 border border-blue-200 rounded-lg font-bold inline-block"><i class="fa-solid fa-file-lines"></i> Izin</span>',
                                    'alfa' => ('<span class="px-3 py-1 bg-rose-50 text-rose-700 border border-rose-200 rounded-lg font-bold inline-block">'.uiIcon('close',18).' Alfa</span>'),
                                ];
                                echo $statusBadge[$p['status_kehadiran']] ?? $p['status_kehadiran'];
                                ?>
                            </td>
                            <td class="px-4 py-3 text-slate-600">
                                <?php echo $p['catatan'] ? e($p['catatan']) : '-'; ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <?php else: ?>
            <div class="p-12 text-center">
                <div class="text-6xl mb-4"><i class="fa-solid fa-clipboard-list text-slate-300"></i></div>
                <h3 class="text-lg font-bold text-slate-900 mb-2">Belum Ada Data Presensi</h3>
                <p class="text-sm text-slate-500">Data presensi untuk bulan ini belum tersedia.</p>
            </div>
            <?php endif; ?>
        </div>

        <?php uiPagination($presensiPager['page'],$presensiPager['pages'],$presensiPager['total'],'presensi'); ?>
    </main>

    <!-- Footer -->
    <?php require __DIR__ . '/../includes/portal_footer.php'; ?>

</body>
</html>
