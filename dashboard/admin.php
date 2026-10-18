<?php
/**
 * EduLMS - Dashboard Admin
 * Ringkasan data sekolah + pintasan pengelolaan.
 * (Desain Portal Admin final belum tersedia di Figma — mengadopsi pola visual portal existing)
 */

require_once '../includes/db.php';
require_once '../includes/config.php';
require_once '../includes/auth_check.php';
require_once '../includes/functions.php';

requireAdmin();

$user_id = currentUserId();
$nama_lengkap = $_SESSION['nama_lengkap'] ?? 'Admin';

// Statistik ringkas ( agregasi langsung — data nyata )
$st = [];
$st['guru']       = $pdo->query("SELECT COUNT(*) FROM users WHERE role='guru' AND is_active=1")->fetchColumn();
$st['siswa']      = $pdo->query("SELECT COUNT(*) FROM users WHERE role='siswa' AND is_active=1")->fetchColumn();
$st['siswa_blnKelas'] = $pdo->query("
    SELECT COUNT(*) FROM users u
    WHERE u.role='siswa' AND u.is_active=1
      AND NOT EXISTS (SELECT 1 FROM siswa_kelas sk WHERE sk.siswa_id=u.id AND sk.status='aktif')
")->fetchColumn();
$st['kelas']      = $pdo->query("SELECT COUNT(*) FROM kelas")->fetchColumn();
$st['mapel']      = $pdo->query("SELECT COUNT(*) FROM mata_pelajaran")->fetchColumn();
$st['penugasan']  = $pdo->query("SELECT COUNT(*) FROM guru_mapel WHERE is_active=1")->fetchColumn();
$st['pengumuman'] = $pdo->query("SELECT COUNT(*) FROM pengumuman WHERE is_published=1")->fetchColumn();

// Siswa belum ditempatkan (perlu tindakan admin)
$stmtBlm = $pdo->query("
    SELECT u.id, u.nama_lengkap, u.email, u.nisn
    FROM users u
    WHERE u.role='siswa' AND u.is_active=1
      AND NOT EXISTS (SELECT 1 FROM siswa_kelas sk WHERE sk.siswa_id=u.id AND sk.status='aktif')
    ORDER BY u.nama_lengkap
    LIMIT 6
");
$siswaBelumKelas = $stmtBlm->fetchAll();

$pendingRequests=(int)$pdo->query("SELECT COUNT(*) FROM pengajuan_rombel WHERE status='pending'")->fetchColumn();
$openTickets=(int)$pdo->query("SELECT COUNT(*) FROM tiket_bantuan WHERE status='open'")->fetchColumn();
$adminPageTitle = 'Dashboard Admin';
$adminActive = 'dashboard';
require_once '../includes/admin_header.php';
?>

<!-- Banner Sambutan -->
<div class="bg-gradient-to-r from-teal-800 to-teal-700 rounded-3xl p-8 text-white shadow-lg relative overflow-hidden">
    <div class="space-y-2 relative z-10">
        <div class="flex items-center space-x-3 text-teal-200 text-xs font-semibold tracking-wide uppercase">
            <span><i class="fa-solid fa-circle text-[6px]"></i> Panel Administrasi Sekolah</span>
            <span>•</span>
            <span>Tahun Ajaran <?php echo e(TAHUN_AJARAN_AKTIF); ?></span>
        </div>
        <h1 class="text-2xl sm:text-3xl font-extrabold tracking-tight">Selamat Datang, <?php echo e($nama_lengkap); ?></h1>
        <p class="text-xs sm:text-sm text-teal-100 max-w-2xl leading-relaxed">
            Kelola data dasar sekolah — akun, kelas, mata pelajaran, penempatan siswa, dan penugasan guru.
            Data yang Anda siapkan menjadi dasar portal Guru dan Siswa.
        </p>
    </div>
</div>

<section class="admin-action-inbox"><a href="pengajuan_rombel.php" class="admin-inbox-card"><span class="icon-tile aqua"><?= uiIcon('school',24) ?></span><span><strong>Pengajuan Rombel</strong><small><?= $pendingRequests ?> menunggu keputusan</small></span><?= uiIcon('arrow',18) ?></a><a href="dukungan.php" class="admin-inbox-card"><span class="icon-tile blue"><?= uiIcon('message',24) ?></span><span><strong>Tiket Bantuan</strong><small><?= $openTickets ?> tiket terbuka</small></span><?= uiIcon('arrow',18) ?></a></section>
<!-- Kartu Statistik -->
<div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-6 gap-4">
    <?php
    $kartu = [
        ['label' => 'Guru Aktif', 'val' => $st['guru'], 'icon' => '<i class="fa-solid fa-chalkboard-user"></i>', 'link' => 'admin_pengguna.php?role=guru'],
        ['label' => 'Siswa Aktif', 'val' => $st['siswa'], 'icon' => '<i class="fa-solid fa-graduation-cap"></i>', 'link' => 'admin_pengguna.php?role=siswa'],
        ['label' => 'Siswa Belum Kelas', 'val' => $st['siswa_blnKelas'], 'icon' => '<i class="fa-solid fa-triangle-exclamation"></i>', 'link' => 'admin_penempatan.php', 'warn' => $st['siswa_blnKelas'] > 0],
        ['label' => 'Kelas', 'val' => $st['kelas'], 'icon' => '<i class="fa-solid fa-school"></i>', 'link' => 'admin_kelas.php'],
        ['label' => 'Mata Pelajaran', 'val' => $st['mapel'], 'icon' => '<i class="fa-solid fa-book"></i>', 'link' => 'admin_mapel.php'],
        ['label' => 'Penugasan Guru', 'val' => $st['penugasan'], 'icon' => '<i class="fa-solid fa-clipboard-list"></i>', 'link' => 'admin_penugasan.php'],
    ];
    foreach ($kartu as $k):
        $border = !empty($k['warn']) ? 'border-rose-200 bg-rose-50/30' : 'border-slate-200';
    ?>
    <a href="<?php echo $k['link']; ?>" class="bg-white p-5 rounded-2xl border <?php echo $border; ?> shadow-sm flex flex-col justify-between hover:shadow-md transition">
        <div class="flex justify-between items-start mb-2">
            <span class="text-[10px] uppercase font-bold text-slate-400 leading-tight"><?php echo $k['label']; ?></span>
            <span class="text-lg"><?php echo $k['icon']; ?></span>
        </div>
        <div class="text-2xl font-extrabold <?php echo !empty($k['warn']) ? 'text-rose-600' : 'text-slate-900'; ?>">
            <?php echo $k['val']; ?>
        </div>
    </a>
    <?php endforeach; ?>
</div>

<!-- Dua kolom: Siswa Belum Kelas + Pintasan -->
<div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

    <!-- Siswa Belum Ditempatkan -->
    <div class="lg:col-span-2 bg-white rounded-2xl border border-slate-200 shadow-sm">
        <div class="p-6 border-b border-slate-100 flex justify-between items-center">
            <div>
                <h2 class="text-sm font-bold text-slate-900">Siswa Belum Ditempatkan ke Kelas</h2>
                <p class="text-[11px] text-slate-500">Tempatkan siswa agar dapat mengakses mata pelajaran</p>
            </div>
            <a href="admin_penempatan.php" class="text-xs font-bold text-teal-700 hover:underline">Kelola <?= uiIcon('arrow',18) ?></a>
        </div>
        <div class="p-4">
            <?php if (count($siswaBelumKelas) > 0): ?>
            <div class="overflow-x-auto">
                <table class="w-full text-xs">
                    <thead>
                        <tr class="text-left text-[10px] uppercase text-slate-400 border-b border-slate-100">
                            <th class="py-2 pr-3">Nama</th>
                            <th class="py-2 pr-3">Email</th>
                            <th class="py-2 pr-3">NISN</th>
                            <th class="py-2 text-right">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($siswaBelumKelas as $s): ?>
                        <tr class="border-b border-slate-50 hover:bg-slate-50">
                            <td class="py-2.5 pr-3 font-semibold text-slate-800"><?php echo e($s['nama_lengkap']); ?></td>
                            <td class="py-2.5 pr-3 text-slate-500"><?php echo e($s['email']); ?></td>
                            <td class="py-2.5 pr-3 text-slate-500"><?php echo e($s['nisn'] ?? '-'); ?></td>
                            <td class="py-2.5 text-right">
                                <a href="admin_penempatan.php?siswa=<?php echo $s['id']; ?>" class="px-3 py-1.5 bg-teal-700 hover:bg-teal-800 text-white text-[11px] font-bold rounded-lg transition">Tempatkan</a>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <?php else: ?>
            <div class="text-center py-10">
                <div class="text-4xl mb-2"><i class="fa-solid fa-circle-check text-emerald-500"></i></div>
                <p class="text-sm text-slate-500">Semua siswa aktif sudah memiliki kelas.</p>
            </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Pintasan Pengelolaan -->
    <div class="space-y-3">
        <div class="bg-white p-6 rounded-2xl border border-slate-200 shadow-sm">
            <h2 class="text-sm font-bold text-slate-900 mb-1">Pintasan Pengelolaan</h2>
            <p class="text-[11px] text-slate-500 mb-4">Alur: siapkan data <?= uiIcon('arrow',18) ?> guru mengajar <?= uiIcon('arrow',18) ?> siswa belajar</p>
            <div class="space-y-2">
                <a href="admin_pengguna.php" class="flex items-center justify-between p-3 bg-slate-50 hover:bg-teal-50 rounded-xl border border-slate-200 transition">
                    <span class="text-xs font-semibold text-slate-700"><i class="fa-solid fa-plus"></i> Kelola Akun (Guru & Siswa)</span>
                    <span class="text-slate-400"><?= uiIcon('arrow',18) ?></span>
                </a>
                <a href="admin_kelas.php" class="flex items-center justify-between p-3 bg-slate-50 hover:bg-teal-50 rounded-xl border border-slate-200 transition">
                    <span class="text-xs font-semibold text-slate-700"><i class="fa-solid fa-school"></i> Kelola Kelas</span>
                    <span class="text-slate-400"><?= uiIcon('arrow',18) ?></span>
                </a>
                <a href="admin_mapel.php" class="flex items-center justify-between p-3 bg-slate-50 hover:bg-teal-50 rounded-xl border border-slate-200 transition">
                    <span class="text-xs font-semibold text-slate-700"><i class="fa-solid fa-book"></i> Kelola Mata Pelajaran</span>
                    <span class="text-slate-400"><?= uiIcon('arrow',18) ?></span>
                </a>
                <a href="admin_penugasan.php" class="flex items-center justify-between p-3 bg-slate-50 hover:bg-teal-50 rounded-xl border border-slate-200 transition">
                    <span class="text-xs font-semibold text-slate-700"><i class="fa-solid fa-clipboard-list"></i> Tugaskan Guru Mengajar</span>
                    <span class="text-slate-400"><?= uiIcon('arrow',18) ?></span>
                </a>
                <a href="admin_pengumuman.php" class="flex items-center justify-between p-3 bg-slate-50 hover:bg-teal-50 rounded-xl border border-slate-200 transition">
                    <span class="text-xs font-semibold text-slate-700"><?= uiIcon('megaphone',18) ?> Buat Pengumuman</span>
                    <span class="text-slate-400"><?= uiIcon('arrow',18) ?></span>
                </a>
            </div>
        </div>
    </div>

</div>

<?php require_once '../includes/admin_footer.php'; ?>
