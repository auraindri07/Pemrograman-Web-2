<?php
/**
 * EduLMS - Lihat Nilai Tugas
 * Halaman detail nilai tugas untuk siswa (diakses dari daftar tugas).
 */

require_once '../includes/db.php';
require_once '../includes/auth_check.php';
require_once '../includes/functions.php';
require_once '../includes/config.php';

requireStudent();

$user_id = currentUserId();
$nama_lengkap = $_SESSION['nama_lengkap'] ?? 'Siswa';

$tugas_id = intval($_GET['id'] ?? 0);

if ($tugas_id <= 0) {
    header("Location: tugas.php");
    exit();
}

// Detail tugas + guru + mapel
$stmtTugas = $pdo->prepare("
    SELECT 
        t.id,
        t.judul_tugas,
        t.deskripsi,
        t.instruksi,
        t.batas_pengumpulan,
        t.format_file,
        t.file_path,
        t.file_name,
        mp.nama_mapel,
        u.nama_lengkap as nama_guru,
        gm.id as guru_mapel_id
    FROM tugas t
    JOIN guru_mapel gm ON t.guru_mapel_id = gm.id
    JOIN mata_pelajaran mp ON gm.mapel_id = mp.id
    JOIN users u ON gm.guru_id = u.id
    WHERE t.id = ? AND t.is_published = 1
");
$stmtTugas->execute([$tugas_id]);
$tugas = $stmtTugas->fetch();

if (!$tugas) {
    header("Location: tugas.php");
    exit();
}

// Wajib terdaftar aktif di kelas tugas ini
$stmtCek = $pdo->prepare("
    SELECT sk.id 
    FROM siswa_kelas sk
    JOIN kelas k ON sk.kelas_id = k.id
    JOIN guru_mapel gm ON gm.kelas_id = k.id AND gm.tahun_ajaran = sk.tahun_ajaran
    WHERE sk.siswa_id = ? AND gm.id = ? AND sk.status = 'aktif'
");
$stmtCek->execute([$user_id, $tugas['guru_mapel_id']]);
if (!$stmtCek->fetch()) {
    http_response_code(403);
    die('Anda tidak memiliki akses ke tugas ini.');
}

// Pengumpulan + nilai milik siswa ini
$stmtNilai = $pdo->prepare("
    SELECT id, file_path, file_name, tanggal_kumpul, status, nilai, feedback, dinilai_pada
    FROM pengumpulan_tugas
    WHERE tugas_id = ? AND siswa_id = ?
");
$stmtNilai->execute([$tugas_id, $user_id]);
$pengumpulan = $stmtNilai->fetch();

$dinilai = ($pengumpulan && $pengumpulan['nilai'] !== null);

// Tampilan nilai: bulat tanpa desimal, desimal ditampilkan apa adanya
function tampilNilai($nilai)
{
    $v = (float)$nilai;
    return (fmod($v, 1) == 0) ? number_format($v, 0) : number_format($v, 1);
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Lihat Nilai - <?php echo e($tugas['judul_tugas']); ?> - EduLMS</title>
    <link rel="stylesheet" href="../assets/css/tailwind.css">
    <link rel="stylesheet" href="../assets/fonts/jakarta.css">
    <style>
        body { font-family: 'Plus Jakarta Sans', sans-serif; }
    </style>
<?php require __DIR__ . '/../includes/portal_assets.php'; ?>
</head>
<body class="page-lihat_nilai bg-slate-50 text-slate-800 min-h-screen">

    <!-- Navbar -->
    <?php require __DIR__ . '/../includes/portal_nav.php'; ?>

    <!-- Main Content -->
    <main class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-8 space-y-6">

        <!-- Back Button -->
        <div>
            <a href="tugas.php" class="inline-flex items-center space-x-2 text-xs font-semibold text-slate-600 hover:text-[#006d5b]">
                <span><?= uiIcon('chevron-left',18) ?></span>
                <span>Kembali ke Daftar Tugas</span>
            </a>
        </div>

        <!-- Assignment Info -->
        <div class="bg-white rounded-2xl p-6 border border-slate-200 shadow-sm">
            <div class="flex items-start justify-between mb-4">
                <div>
                    <span class="text-[10px] bg-blue-50 text-blue-700 font-bold px-2.5 py-1 rounded-lg border border-blue-200"><?php echo e($tugas['nama_mapel']); ?></span>
                    <h1 class="text-xl font-bold text-slate-900 mt-2"><?php echo e($tugas['judul_tugas']); ?></h1>
                    <p class="text-xs text-slate-500 mt-1">Oleh: <?php echo e($tugas['nama_guru']); ?></p>
                </div>
                <div class="text-right">
                    <div class="text-[10px] uppercase tracking-wider text-slate-400 font-bold">Batas Waktu</div>
                    <div class="text-sm font-bold text-slate-900"><?php echo formatTanggalIndo($tugas['batas_pengumpulan'], 'd M Y H:i'); ?> WIB</div>
                </div>
            </div>

            <?php if ($tugas['deskripsi']): ?>
            <div class="border-t border-slate-100 pt-4 mt-1">
                <h3 class="text-xs font-bold text-slate-900 mb-2">Deskripsi Tugas:</h3>
                <p class="text-xs text-slate-600 leading-relaxed"><?php echo nl2br(e(uiDisplayText($tugas['deskripsi']))); ?></p>
            </div>
            <?php endif; ?>
        </div>

        <!-- Nilai -->
        <?php if ($dinilai): ?>
        <div class="bg-white rounded-2xl border border-teal-200 shadow-sm overflow-hidden">
            <div class="bg-gradient-to-r from-teal-600 to-[#006d5b] px-6 py-5 flex items-center justify-between">
                <div>
                    <div class="text-[10px] uppercase tracking-wider text-teal-100 font-bold">Hasil Penilaian</div>
                    <div class="text-sm font-semibold text-white mt-0.5"><?php echo e($tugas['nama_mapel']); ?></div>
                </div>
                <div class="text-right">
                    <span class="text-4xl font-extrabold text-white"><?php echo tampilNilai($pengumpulan['nilai']); ?></span>
                    <span class="text-sm font-semibold text-teal-100">/100</span>
                </div>
            </div>
            <div class="p-6 space-y-4">
                <?php if ($pengumpulan['feedback']): ?>
                <div>
                    <div class="text-[10px] uppercase tracking-wider text-slate-400 font-bold mb-1">Catatan dari Guru</div>
                    <p class="text-sm text-slate-700 leading-relaxed bg-slate-50 rounded-xl p-4 border border-slate-100"><?php echo nl2br(e($pengumpulan['feedback'])); ?></p>
                </div>
                <?php endif; ?>
                <div class="grid grid-cols-2 gap-4 text-xs">
                    <div>
                        <span class="text-slate-500">Dinilai pada:</span>
                        <span class="font-semibold text-slate-900 ml-2"><?php echo $pengumpulan['dinilai_pada'] ? formatTanggalIndo($pengumpulan['dinilai_pada'], 'd M Y H:i') : '-'; ?></span>
                    </div>
                    <div>
                        <span class="text-slate-500">Status Pengumpulan:</span>
                        <span class="font-semibold <?php echo $pengumpulan['status'] === 'tepat_waktu' ? 'text-emerald-600' : 'text-rose-600'; ?> ml-2">
                            <?php echo $pengumpulan['status'] === 'tepat_waktu' ? (uiIcon('check-circle',18).' Tepat Waktu') : (uiIcon('alarm',18).' Terlambat'); ?>
                        </span>
                    </div>
                </div>
            </div>
        </div>

        <?php elseif ($pengumpulan): ?>
        <div class="bg-blue-50 border border-blue-200 rounded-2xl p-6 flex items-center space-x-4">
            <div class="w-12 h-12 rounded-xl bg-blue-100 text-blue-600 flex items-center justify-center text-2xl shrink-0"><?= uiIcon('clock',18) ?></div>
            <div>
                <h3 class="text-sm font-bold text-blue-900">Menunggu Penilaian</h3>
                <p class="text-xs text-blue-700 mt-0.5">Tugas Anda sudah dikumpulkan pada <?php echo formatTanggalIndo($pengumpulan['tanggal_kumpul'], 'd M Y H:i'); ?>. Nilai akan muncul setelah dinilai guru.</p>
            </div>
        </div>

        <?php else: ?>
        <div class="bg-amber-50 border border-amber-200 rounded-2xl p-6 flex items-center space-x-4">
            <div class="w-12 h-12 rounded-xl bg-amber-100 text-amber-600 flex items-center justify-center text-2xl shrink-0"><?= uiIcon('upload',18) ?></div>
            <div>
                <h3 class="text-sm font-bold text-amber-900">Belum Dikumpulkan</h3>
                <p class="text-xs text-amber-700 mt-0.5">Anda belum mengumpulkan tugas ini, sehingga belum ada nilai.</p>
                <a href="kumpulkan_tugas.php?id=<?php echo $tugas['id']; ?>" class="inline-flex items-center space-x-1.5 mt-3 px-4 py-2 bg-[#006d5b] hover:bg-[#005547] text-white text-xs font-bold rounded-xl transition shadow-sm">
                    <span><?= uiIcon('upload',18) ?> Kumpulkan Sekarang</span>
                </a>
            </div>
        </div>
        <?php endif; ?>

        <!-- Jawaban yang dikirim -->
        <?php if ($pengumpulan): ?>
        <div class="bg-white rounded-2xl p-6 border border-slate-200 shadow-sm">
            <h3 class="text-xs font-bold text-slate-900 mb-4">Jawaban yang Anda Kirim</h3>
            <div class="flex items-center justify-between gap-4">
                <div class="flex items-center space-x-3 min-w-0">
                    <div class="w-10 h-10 rounded-xl bg-teal-50 text-[#006d5b] flex items-center justify-center text-lg shrink-0"><?= uiIcon('paperclip',18) ?></div>
                    <div class="min-w-0">
                        <div class="text-xs font-bold text-slate-900 truncate"><?php echo e($pengumpulan['file_name']); ?></div>
                        <div class="text-[10px] text-slate-500">Diserahkan <?php echo formatTanggalIndo($pengumpulan['tanggal_kumpul'], 'd M Y H:i'); ?> WIB</div>
                    </div>
                </div>
                <?php if (!empty($pengumpulan['file_path'])): ?>
                <a href="download.php?type=pengumpulan&id=<?php echo $pengumpulan['id']; ?>" target="_blank" class="px-4 py-2 bg-slate-100 hover:bg-slate-200 text-slate-700 text-xs font-bold rounded-xl transition border border-slate-200 shrink-0">
                    <?= uiIcon('download',18) ?> Lihat Berkas
                </a>
                <?php endif; ?>
            </div>
        </div>
        <?php endif; ?>

        <!-- Soal dari guru -->
        <?php if (!empty($tugas['file_path'])): ?>
        <div class="bg-white rounded-2xl p-6 border border-slate-200 shadow-sm">
            <h3 class="text-xs font-bold text-slate-900 mb-4">Berkas Soal dari Guru</h3>
            <div class="flex items-center justify-between gap-4">
                <div class="flex items-center space-x-3 min-w-0">
                    <div class="w-10 h-10 rounded-xl bg-slate-100 text-slate-600 flex items-center justify-center text-lg shrink-0"><?= uiIcon('file',18) ?></div>
                    <div class="min-w-0">
                        <div class="text-xs font-bold text-slate-900 truncate"><?php echo e($tugas['file_name'] ?? 'Lampiran Soal'); ?></div>
                        <div class="text-[10px] text-slate-500">Format: <?php echo e($tugas['format_file']); ?></div>
                    </div>
                </div>
                <a href="download.php?type=tugas&id=<?php echo $tugas['id']; ?>" target="_blank" class="px-4 py-2 bg-slate-100 hover:bg-slate-200 text-slate-700 text-xs font-bold rounded-xl transition border border-slate-200 shrink-0">
                    <?= uiIcon('download',18) ?> Lihat Soal
                </a>
            </div>
        </div>
        <?php endif; ?>

    </main>

    <?php require __DIR__ . '/../includes/portal_footer.php'; ?>

</body>
</html>
