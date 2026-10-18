<?php
/**
 * EduLMS - Penugasan Guru (Admin)
 * Kelola tabel guru_mapel: guru x mapel x kelas x tahun ajaran.
 * Baris guru_mapel = satu kelas mengajar yang menjadi ID rujukan halaman guru.
 */
require_once '../includes/db.php';
require_once '../includes/config.php';
require_once '../includes/auth_check.php';
require_once '../includes/functions.php';
require_once '../includes/csrf.php';

requireAdmin();
require_once '../includes/admin_validation.php';

$pesan = '';
$jenisPesan = 'sukses';

$daftarGuru   = $pdo->query("SELECT id, nama_lengkap FROM users WHERE role='guru' AND is_active=1 ORDER BY nama_lengkap")->fetchAll();
$daftarMapel  = $pdo->query("SELECT id, nama_mapel, kode_mapel FROM mata_pelajaran ORDER BY nama_mapel")->fetchAll();
$daftarKelas  = $pdo->query("SELECT id, nama_kelas, jurusan, tahun_ajaran FROM kelas ORDER BY tahun_ajaran DESC, nama_kelas")->fetchAll();

try {
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    checkCsrfToken();
    validateAdminInput($pdo, basename(__FILE__), $_POST);
    $aksi = $_POST['action'] ?? '';

    if ($aksi === 'tugaskan') {
        $guruId  = intval($_POST['guru_id'] ?? 0);
        $mapelId = intval($_POST['mapel_id'] ?? 0);
        $kelasId = intval($_POST['kelas_id'] ?? 0);
        $tahun   = trim($_POST['tahun_ajaran'] ?? '') ?: TAHUN_AJARAN_AKTIF;
        $hari    = trim($_POST['hari'] ?? '') ?: null;
        $jam     = trim($_POST['jam_mulai'] ?? '') ?: null;
        $ruang   = trim($_POST['ruang'] ?? '') ?: null;

        if ($guruId <= 0 || $mapelId <= 0 || $kelasId <= 0) {
            $pesan = 'Guru, mata pelajaran, dan kelas wajib dipilih.'; $jenisPesan = 'galat';
        } else {
            // Cek duplikat (guru + mapel + kelas + tahun, aktif)
            $ck = $pdo->prepare("SELECT id FROM guru_mapel WHERE guru_id=? AND mapel_id=? AND kelas_id=? AND tahun_ajaran=?");
            $ck->execute([$guruId, $mapelId, $kelasId, $tahun]);
            if ($ck->fetch()) {
                $pesan = 'Penugasan tersebut sudah ada.'; $jenisPesan = 'galat';
            } else {
                $ins = $pdo->prepare("INSERT INTO guru_mapel (guru_id, mapel_id, kelas_id, tahun_ajaran, hari, jam_mulai, ruang, is_active) VALUES (?,?,?,?,?,?,?,1)");
                if ($ins->execute([$guruId, $mapelId, $kelasId, $tahun, $hari, $jam, $ruang])) {
                    $pesan = 'Penugasan guru berhasil dibuat.';
                } else {
                    $pesan = 'Gagal menyimpan penugasan.'; $jenisPesan = 'galat';
                }
            }
        }
    }

    elseif ($aksi === 'toggle_aktif') {
        $id = intval($_POST['guru_mapel_id'] ?? 0);
        $stmt = $pdo->prepare("UPDATE guru_mapel SET is_active = IF(is_active=1,0,1) WHERE id = ?");
        $stmt->execute([$id]);
        $pesan = 'Status penugasan diperbarui.';
    }
}
} catch (InvalidArgumentException $exception) {
    $pesan = $exception->getMessage(); $jenisPesan = 'galat';
} catch (PDOException $exception) {
    error_log($exception->getMessage());
    $pesan = 'Data tidak dapat disimpan. Periksa duplikasi identitas dan relasi data.'; $jenisPesan = 'galat';
}

// Daftar penugasan
$daftar = $pdo->query("
    SELECT gm.*, u.nama_lengkap as nama_guru, mp.nama_mapel, mp.kode_mapel, mp.icon_emoji,
           k.nama_kelas, k.jurusan
    FROM guru_mapel gm
    JOIN users u ON gm.guru_id = u.id
    JOIN mata_pelajaran mp ON gm.mapel_id = mp.id
    JOIN kelas k ON gm.kelas_id = k.id
    ORDER BY gm.tahun_ajaran DESC, u.nama_lengkap, k.nama_kelas
")->fetchAll();

$listPager=uiPaginate($daftar,5);
$daftar=$listPager['items'];

$adminPageTitle = 'Penugasan Guru';
$adminActive = 'penugasan';
require_once '../includes/admin_header.php';
?>

<?php if ($pesan !== ''): ?>
<div class="p-4 rounded-xl border text-sm font-semibold <?php echo $jenisPesan === 'sukses' ? 'bg-emerald-50 text-emerald-700 border-emerald-200' : 'bg-rose-50 text-rose-700 border-rose-200'; ?>">
    <?php echo e($pesan); ?>
</div>
<?php endif; ?>

<div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

    <!-- Form Penugasan -->
    <div class="bg-white rounded-2xl border border-slate-200 shadow-sm p-6 h-fit">
        <h2 class="text-sm font-bold text-slate-900 mb-1">Tugaskan Guru Mengajar</h2>
        <p class="text-[11px] text-slate-500 mb-4">Satu baris = guru mengajar satu mapel di satu kelas. Inilah ID rujukan portal guru.</p>
        <form method="POST" class="space-y-3">
            <input type="hidden" name="action" value="tugaskan">
            <?php echo csrfField(); ?>
            <div>
                <label class="block text-xs font-semibold text-slate-600 mb-1">Guru</label>
                <select name="guru_id" required class="w-full px-3 py-2 text-xs border border-slate-300 rounded-xl focus:outline-none focus:border-teal-600">
                    <option value="">— Pilih Guru —</option>
                    <?php foreach ($daftarGuru as $g): ?>
                    <option value="<?php echo $g['id']; ?>"><?php echo e($g['nama_lengkap']); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div>
                <label class="block text-xs font-semibold text-slate-600 mb-1">Mata Pelajaran</label>
                <select name="mapel_id" required class="w-full px-3 py-2 text-xs border border-slate-300 rounded-xl focus:outline-none focus:border-teal-600">
                    <option value="">— Pilih Mapel —</option>
                    <?php foreach ($daftarMapel as $m): ?>
                    <option value="<?php echo $m['id']; ?>"><?php echo e($m['nama_mapel']); ?> (<?php echo e($m['kode_mapel']); ?>)</option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div>
                <label class="block text-xs font-semibold text-slate-600 mb-1">Kelas</label>
                <select name="kelas_id" required class="w-full px-3 py-2 text-xs border border-slate-300 rounded-xl focus:outline-none focus:border-teal-600">
                    <option value="">— Pilih Kelas —</option>
                    <?php foreach ($daftarKelas as $k): ?>
                    <option value="<?php echo $k['id']; ?>"><?php echo e($k['nama_kelas']); ?> — <?php echo e($k['jurusan'] ?? ''); ?> (<?php echo e($k['tahun_ajaran']); ?>)</option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div>
                <label class="block text-xs font-semibold text-slate-600 mb-1">Tahun Ajaran</label>
                <input type="text" name="tahun_ajaran" value="<?php echo e(TAHUN_AJARAN_AKTIF); ?>" class="w-full px-3 py-2 text-xs border border-slate-300 rounded-xl focus:outline-none focus:border-teal-600">
            </div>
            <div class="grid grid-cols-3 gap-2">
                <div>
                    <label class="block text-xs font-semibold text-slate-600 mb-1">Hari</label>
                    <select name="hari" class="w-full px-3 py-2 text-xs border border-slate-300 rounded-xl focus:outline-none focus:border-teal-600">
                        <option value="">-</option>
                        <?php foreach (['Senin','Selasa','Rabu','Kamis','Jumat','Sabtu'] as $h): ?>
                        <option value="<?php echo $h; ?>"><?php echo $h; ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-semibold text-slate-600 mb-1">Jam</label>
                    <input type="time" name="jam_mulai" class="w-full px-2 py-2 text-xs border border-slate-300 rounded-xl focus:outline-none focus:border-teal-600">
                </div>
                <div>
                    <label class="block text-xs font-semibold text-slate-600 mb-1">Ruang</label>
                    <input type="text" name="ruang" placeholder="R-01" class="w-full px-2 py-2 text-xs border border-slate-300 rounded-xl focus:outline-none focus:border-teal-600">
                </div>
            </div>
            <button type="submit" class="w-full py-2.5 bg-teal-700 hover:bg-teal-800 text-white text-xs font-bold rounded-xl transition">
                Buat Penugasan
            </button>
        </form>
    </div>

    <!-- Daftar Penugasan -->
    <div class="lg:col-span-2 bg-white rounded-2xl border border-slate-200 shadow-sm">
        <div class="p-6 border-b border-slate-100">
            <h2 class="text-sm font-bold text-slate-900">Daftar Penugasan</h2>
            <p class="text-[11px] text-slate-500"><?php echo $listPager['total']; ?> baris penugasan</p>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full text-xs">
                <thead>
                    <tr class="text-left text-[10px] uppercase text-slate-400 border-b border-slate-100">
                        <th class="py-2.5 px-4">Guru</th>
                        <th class="py-2.5 px-4">Mapel</th>
                        <th class="py-2.5 px-4">Kelas</th>
                        <th class="py-2.5 px-4">Jadwal</th>
                        <th class="py-2.5 px-4">Status</th>
                        <th class="py-2.5 px-4 text-right">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($listPager['total'] === 0): ?>
                    <tr><td colspan="6" class="py-8 text-center text-slate-400">Belum ada penugasan.</td></tr>
                    <?php endif; ?>
                    <?php foreach ($daftar as $g): ?>
                    <tr class="border-b border-slate-50 hover:bg-slate-50">
                        <td class="py-3 px-4">
                            <div class="font-semibold text-slate-800"><?php echo e($g['nama_guru']); ?></div>
                            <div class="text-[10px] text-slate-400">ID <?php echo $g['id']; ?> • <?php echo e($g['tahun_ajaran']); ?></div>
                        </td>
                        <td class="py-3 px-4">
                            <div class="flex items-center space-x-1.5">
                                <span><?php echo uiSubjectGlyph($g['nama_mapel'], $g['icon_emoji']); ?></span>
                                <span class="text-slate-700 font-medium"><?php echo e($g['nama_mapel']); ?></span>
                            </div>
                        </td>
                        <td class="py-3 px-4 text-slate-600"><?php echo e($g['nama_kelas']); ?></td>
                        <td class="py-3 px-4 text-slate-500">
                            <?php echo $g['hari'] ? e($g['hari']) : '-'; ?>
                            <?php echo $g['jam_mulai'] ? ' ' . e(substr($g['jam_mulai'],0,5)) : ''; ?>
                            <?php echo $g['ruang'] ? ' • ' . e($g['ruang']) : ''; ?>
                        </td>
                        <td class="py-3 px-4">
                            <?php if ($g['is_active']): ?>
                            <span class="px-2 py-0.5 rounded border text-[10px] font-bold bg-emerald-50 text-emerald-700 border-emerald-200">Aktif</span>
                            <?php else: ?>
                            <span class="px-2 py-0.5 rounded border text-[10px] font-bold bg-slate-50 text-slate-500 border-slate-200">Nonaktif</span>
                            <?php endif; ?>
                        </td>
                        <td class="py-3 px-4 text-right">
                            <form method="POST" class="inline" onsubmit="return confirm('Ubah status penugasan ini?')">
                                <input type="hidden" name="action" value="toggle_aktif">
                                <input type="hidden" name="guru_mapel_id" value="<?php echo $g['id']; ?>">
                                <?php echo csrfField(); ?>
                                <button type="submit" class="px-2.5 py-1.5 rounded-lg text-[10px] font-bold border <?php echo $g['is_active'] ? 'bg-amber-50 text-amber-700 border-amber-200 hover:bg-amber-100' : 'bg-emerald-50 text-emerald-700 border-emerald-200 hover:bg-emerald-100'; ?>">
                                    <?php echo $g['is_active'] ? 'Nonaktifkan' : 'Aktifkan'; ?>
                                </button>
                            </form>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php require_once '../includes/admin_footer.php'; ?>
