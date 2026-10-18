<?php
/**
 * EduLMS - Riwayat Akademik Siswa (Tahap 11)
 * Menampilkan riwayat per tahun ajaran: kelas diikuti, rata-rata nilai,
 * kehadiran, dan ketuntasan tugas. Data dipisah per tahun ajaran.
 */
require_once '../includes/db.php';
require_once '../includes/config.php';
require_once '../includes/auth_check.php';
require_once '../includes/functions.php';

requireStudent();

$user_id = currentUserId();
$nama_lengkap = $_SESSION['nama_lengkap'] ?? 'Siswa';

// Daftar tahun ajaran yang pernah diikuti siswa
$stmtYears = $pdo->prepare("SELECT DISTINCT tahun_ajaran FROM siswa_kelas WHERE siswa_id = ? ORDER BY tahun_ajaran DESC");
$stmtYears->execute([$user_id]);
$tahunList = $stmtYears->fetchAll(PDO::FETCH_COLUMN);

$riwayat = [];
foreach ($tahunList as $th) {
    // Kelas yang diikuti pada tahun ini
    $k = $pdo->prepare("
        SELECT k.nama_kelas, k.tingkat, k.jurusan, sk.status
        FROM siswa_kelas sk JOIN kelas k ON sk.kelas_id = k.id
        WHERE sk.siswa_id = ? AND sk.tahun_ajaran = ?
        ORDER BY k.nama_kelas
    ");
    $k->execute([$user_id, $th]);
    $kelas = $k->fetchAll();

    // Rata-rata nilai pada tahun ini
    $n = $pdo->prepare("
        SELECT AVG(pt.nilai) as rata, COUNT(pt.nilai) as jml
        FROM pengumpulan_tugas pt
        JOIN tugas t ON pt.tugas_id = t.id
        JOIN guru_mapel gm ON t.guru_mapel_id = gm.id
        WHERE pt.siswa_id = ? AND gm.tahun_ajaran = ? AND pt.nilai IS NOT NULL
    ");
    $n->execute([$user_id, $th]);
    $nilai = $n->fetch();
    $rata = $nilai['rata'] !== null ? floatval($nilai['rata']) : null;

    // Ketuntasan tugas pada tahun ini
    $tt = $pdo->prepare("
        SELECT COUNT(*) FROM tugas t
        JOIN guru_mapel gm ON t.guru_mapel_id = gm.id
        JOIN siswa_kelas sk ON gm.kelas_id = sk.kelas_id AND gm.tahun_ajaran = sk.tahun_ajaran
        WHERE sk.siswa_id = ? AND sk.tahun_ajaran = ? AND sk.status = 'aktif' AND t.is_published = 1
    ");
    $tt->execute([$user_id, $th]);
    $totalTugas = intval($tt->fetchColumn());

    $ss = $pdo->prepare("
        SELECT COUNT(DISTINCT pt.tugas_id) FROM pengumpulan_tugas pt
        JOIN tugas t ON pt.tugas_id = t.id
        JOIN guru_mapel gm ON t.guru_mapel_id = gm.id
        JOIN siswa_kelas sk ON gm.kelas_id = sk.kelas_id AND gm.tahun_ajaran = sk.tahun_ajaran
        WHERE pt.siswa_id = ? AND sk.siswa_id = ? AND sk.tahun_ajaran = ? AND sk.status = 'aktif' AND t.is_published = 1
    ");
    $ss->execute([$user_id, $user_id, $th]);
    $selesai = intval($ss->fetchColumn());

    // Kehadiran pada tahun ini
    $kh = $pdo->prepare("
        SELECT SUM(p.status_kehadiran = 'hadir') as hadir, COUNT(*) as total
        FROM presensi p JOIN guru_mapel gm ON p.guru_mapel_id = gm.id
        WHERE p.siswa_id = ? AND gm.tahun_ajaran = ?
    ");
    $kh->execute([$user_id, $th]);
    $hadir = $kh->fetch();
    $persenHadir = $hadir['total'] > 0 ? round(($hadir['hadir'] / $hadir['total']) * 100, 1) : null;

    $riwayat[$th] = [
        'kelas' => $kelas,
        'rata' => $rata,
        'jml_dinilai' => intval($nilai['jml']),
        'total_tugas' => $totalTugas,
        'selesai' => $selesai,
        'persen_hadir' => $persenHadir,
    ];
}

// Info kelas aktif (untuk navbar)
$stmtKelas = $pdo->prepare("
    SELECT k.nama_kelas FROM siswa_kelas sk JOIN kelas k ON sk.kelas_id = k.id
    WHERE sk.siswa_id = ? AND sk.status = 'aktif' LIMIT 1
");
$stmtKelas->execute([$user_id]);
$kelasAktif = $stmtKelas->fetchColumn();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Riwayat Akademik - EduLMS</title>
    <link rel="stylesheet" href="../assets/css/tailwind.css">
    <link rel="stylesheet" href="../assets/fonts/jakarta.css">
    <style>
        body { font-family: 'Plus Jakarta Sans', sans-serif; }
    </style>
<?php require __DIR__ . '/../includes/portal_assets.php'; ?>
</head>
<body class="page-riwayat bg-slate-50 text-slate-800 min-h-screen">

    <!-- Navbar Atas -->
    <?php require __DIR__ . '/../includes/portal_nav.php'; ?>

    <main class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8 space-y-6">

        <div class="bg-white rounded-2xl p-6 border border-slate-200 shadow-sm">
            <h1 class="text-xl font-bold text-slate-900">Riwayat Akademik</h1>
            <p class="text-xs text-slate-500 mt-1">Rekam jejak belajar per tahun ajaran — nilai, kehadiran, dan ketuntasan tugas.</p>
        </div>

        <?php if (count($riwayat) === 0): ?>
        <div class="bg-white rounded-2xl p-12 border border-slate-200 shadow-sm text-center">
            <div class="text-5xl mb-3"><?= uiIcon('folder',18) ?></div>
            <h3 class="text-lg font-bold text-slate-900 mb-1">Belum Ada Riwayat</h3>
            <p class="text-xs text-slate-500">Riwayat akan muncul setelah Anda ditempatkan ke kelas.</p>
        </div>
        <?php endif; ?>

        <?php foreach ($riwayat as $th => $r): ?>
        <?php
            $isAktif = ($th === TAHUN_AJARAN_AKTIF);
            $persenTuntas = $r['total_tugas'] > 0 ? round(($r['selesai'] / $r['total_tugas']) * 100) : null;
        ?>
        <div class="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden">
            <div class="px-6 py-4 border-b border-slate-100 flex items-center justify-between">
                <div class="flex items-center space-x-3">
                    <h2 class="text-sm font-bold text-slate-900">Tahun Ajaran <?php echo e($th); ?></h2>
                    <?php if ($isAktif): ?>
                    <span class="text-[10px] bg-emerald-50 text-emerald-700 font-semibold px-2 py-0.5 rounded-full border border-emerald-200">● Aktif</span>
                    <?php endif; ?>
                </div>
                <div class="flex flex-wrap gap-1.5">
                    <?php foreach ($r['kelas'] as $k): ?>
                    <span class="text-[10px] bg-slate-100 text-slate-600 font-semibold px-2 py-0.5 rounded border border-slate-200">
                        <?php echo e($k['nama_kelas']); ?> <span class="text-slate-400">(<?php echo e(ucfirst($k['status'])); ?>)</span>
                    </span>
                    <?php endforeach; ?>
                </div>
            </div>

            <div class="grid grid-cols-2 lg:grid-cols-4 divide-x divide-slate-100">
                <div class="p-5">
                    <div class="text-[10px] font-bold uppercase tracking-wider text-slate-400 mb-1">Rata-rata Nilai</div>
                    <div class="text-2xl font-extrabold text-slate-900"><?php echo $r['rata'] !== null ? number_format($r['rata'], 1) : '—'; ?></div>
                    <div class="text-[10px] text-slate-400 mt-1"><?php echo $r['jml_dinilai']; ?> tugas dinilai</div>
                </div>
                <div class="p-5">
                    <div class="text-[10px] font-bold uppercase tracking-wider text-slate-400 mb-1">Kehadiran</div>
                    <div class="text-2xl font-extrabold text-slate-900"><?php echo $r['persen_hadir'] !== null ? number_format($r['persen_hadir'], 0) . '%' : '—'; ?></div>
                    <div class="text-[10px] text-slate-400 mt-1">dari sesi tercatat</div>
                </div>
                <div class="p-5">
                    <div class="text-[10px] font-bold uppercase tracking-wider text-slate-400 mb-1">Tugas Tuntas</div>
                    <div class="text-2xl font-extrabold text-slate-900"><?php echo $r['selesai']; ?><span class="text-sm font-normal text-slate-400">/<?php echo $r['total_tugas']; ?></span></div>
                    <?php if ($persenTuntas !== null): ?>
                    <div class="text-[10px] text-indigo-600 font-bold mt-1"><?php echo $persenTuntas; ?>%</div>
                    <?php endif; ?>
                </div>
                <div class="p-5">
                    <div class="text-[10px] font-bold uppercase tracking-wider text-slate-400 mb-1">Status</div>
                    <div class="mt-1">
                        <?php
                        $sc = ['aktif' => 'bg-emerald-50 text-emerald-700 border-emerald-200', 'lulus' => 'bg-blue-50 text-blue-700 border-blue-200', 'pindah' => 'bg-amber-50 text-amber-700 border-amber-200', 'keluar' => 'bg-rose-50 text-rose-700 border-rose-200'];
                        foreach ($r['kelas'] as $k):
                        ?>
                        <span class="inline-block text-[10px] font-bold px-2 py-0.5 rounded border <?php echo $sc[$k['status']] ?? 'bg-slate-50 text-slate-600 border-slate-200'; ?> mr-1 mb-1">
                            <?php echo e($k['nama_kelas']); ?>: <?php echo e(ucfirst($k['status'])); ?>
                        </span>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        </div>
        <?php endforeach; ?>

    </main>

    <?php require __DIR__ . '/../includes/portal_footer.php'; ?>
</body>
</html>
