<?php
/**
 * EduLMS - Kelola Pengumuman (Admin)
 * Buat, ubah, terbitkan/nonaktifkan, hapus pengumuman.
 */
require_once '../includes/db.php';
require_once '../includes/config.php';
require_once '../includes/auth_check.php';
require_once '../includes/functions.php';
require_once '../includes/csrf.php';

requireAdmin();
require_once '../includes/admin_validation.php';

$user_id = currentUserId();

$pesan = '';
$jenisPesan = 'sukses';

try {
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    checkCsrfToken();
    validateAdminInput($pdo, basename(__FILE__), $_POST);
    $aksi = $_POST['action'] ?? '';

    if ($aksi === 'buat_pengumuman') {
        $judul = trim($_POST['judul'] ?? '');
        $isi   = trim($_POST['isi'] ?? '');
        $kategori = $_POST['kategori'] ?? 'umum';
        $target = $_POST['target_role'] ?? 'semua';
        $publish = isset($_POST['is_published']) ? 1 : 0;

        if ($judul === '' || $isi === '') {
            $pesan = 'Judul dan isi wajib diisi.'; $jenisPesan = 'galat';
        } elseif (!in_array($target, ['semua','guru','siswa'])) {
            $pesan = 'Target tidak valid.'; $jenisPesan = 'galat';
        } elseif (!in_array($kategori, ['penting','akademik','agenda','kegiatan','umum'])) {
            $pesan = 'Kategori tidak valid.'; $jenisPesan = 'galat';
        } else {
            $ins = $pdo->prepare("INSERT INTO pengumuman (judul_pengumuman, isi_pengumuman, kategori, target_role, is_published, pembuat_id) VALUES (?,?,?,?,?,?)");
            if ($ins->execute([$judul, $isi, $kategori, $target, $publish, $user_id])) {
                $pesan = 'Pengumuman berhasil disimpan.';
            } else {
                $pesan = 'Gagal menyimpan pengumuman.'; $jenisPesan = 'galat';
            }
        }
    }

    elseif ($aksi === 'toggle_publish') {
        $id = intval($_POST['pengumuman_id'] ?? 0);
        $stmt = $pdo->prepare("UPDATE pengumuman SET is_published = IF(is_published=1,0,1) WHERE id = ?");
        $stmt->execute([$id]);
        $pesan = 'Status terbit pengumuman diperbarui.';
    }

    elseif ($aksi === 'hapus') {
        $id = intval($_POST['pengumuman_id'] ?? 0);
        $stmt = $pdo->prepare("DELETE FROM pengumuman WHERE id = ?");
        $stmt->execute([$id]);
        $pesan = 'Pengumuman dihapus.';
    }
}
} catch (InvalidArgumentException $exception) {
    $pesan = $exception->getMessage(); $jenisPesan = 'galat';
} catch (PDOException $exception) {
    error_log($exception->getMessage());
    $pesan = 'Data tidak dapat disimpan. Periksa duplikasi identitas dan relasi data.'; $jenisPesan = 'galat';
}

$daftar = $pdo->query("
    SELECT p.*, u.nama_lengkap as nama_pembuat
    FROM pengumuman p
    LEFT JOIN users u ON p.pembuat_id = u.id
    ORDER BY p.created_at DESC
")->fetchAll();

$listPager=uiPaginate($daftar,5);
$daftar=$listPager['items'];

$adminPageTitle = 'Kelola Pengumuman';
$adminActive = 'pengumuman';
require_once '../includes/admin_header.php';
?>

<?php if ($pesan !== ''): ?>
<div class="p-4 rounded-xl border text-sm font-semibold <?php echo $jenisPesan === 'sukses' ? 'bg-emerald-50 text-emerald-700 border-emerald-200' : 'bg-rose-50 text-rose-700 border-rose-200'; ?>">
    <?php echo e($pesan); ?>
</div>
<?php endif; ?>

<div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

    <!-- Form Buat Pengumuman -->
    <div class="bg-white rounded-2xl border border-slate-200 shadow-sm p-6 h-fit">
        <h2 class="text-sm font-bold text-slate-900 mb-1">Buat Pengumuman</h2>
        <p class="text-[11px] text-slate-500 mb-4">Pengumuman tampil di dashboard guru & siswa sesuai target.</p>
        <form method="POST" class="space-y-3">
            <input type="hidden" name="action" value="buat_pengumuman">
            <?php echo csrfField(); ?>
            <div>
                <label class="block text-xs font-semibold text-slate-600 mb-1">Judul</label>
                <input type="text" name="judul" required class="w-full px-3 py-2 text-xs border border-slate-300 rounded-xl focus:outline-none focus:border-teal-600">
            </div>
            <div>
                <label class="block text-xs font-semibold text-slate-600 mb-1">Isi</label>
                <textarea name="isi" rows="5" required class="w-full px-3 py-2 text-xs border border-slate-300 rounded-xl focus:outline-none focus:border-teal-600"></textarea>
            </div>
            <div class="grid grid-cols-2 gap-3">
                <div>
                    <label class="block text-xs font-semibold text-slate-600 mb-1">Kategori</label>
                    <select name="kategori" class="w-full px-3 py-2 text-xs border border-slate-300 rounded-xl focus:outline-none focus:border-teal-600">
                        <option value="umum">Umum</option>
                        <option value="akademik">Akademik</option>
                        <option value="agenda">Agenda</option>
                        <option value="kegiatan">Kegiatan</option>
                        <option value="penting">Penting</option>
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-semibold text-slate-600 mb-1">Target</label>
                    <select name="target_role" class="w-full px-3 py-2 text-xs border border-slate-300 rounded-xl focus:outline-none focus:border-teal-600">
                        <option value="semua">Semua</option>
                        <option value="guru">Guru</option>
                        <option value="siswa">Siswa</option>
                    </select>
                </div>
            </div>
            <label class="flex items-center space-x-2 text-xs text-slate-600">
                <input type="checkbox" name="is_published" checked class="rounded border-slate-300 text-teal-700 focus:ring-teal-600">
                <span>Langsung terbit</span>
            </label>
            <button type="submit" class="w-full py-2.5 bg-teal-700 hover:bg-teal-800 text-white text-xs font-bold rounded-xl transition">
                Simpan Pengumuman
            </button>
        </form>
    </div>

    <!-- Daftar Pengumuman -->
    <div class="lg:col-span-2 bg-white rounded-2xl border border-slate-200 shadow-sm">
        <div class="p-6 border-b border-slate-100">
            <h2 class="text-sm font-bold text-slate-900">Daftar Pengumuman</h2>
            <p class="text-[11px] text-slate-500"><?php echo $listPager['total']; ?> pengumuman</p>
        </div>
        <div class="divide-y divide-slate-50">
            <?php if ($listPager['total'] === 0): ?>
            <div class="py-10 text-center text-slate-400 text-sm">Belum ada pengumuman.</div>
            <?php endif; ?>
            <?php foreach ($daftar as $p): ?>
            <div class="p-5 hover:bg-slate-50">
                <div class="flex items-start justify-between gap-3">
                    <div class="flex-1">
                        <div class="flex items-center flex-wrap gap-2 mb-1">
                            <?php
                            $kc = ['umum' => 'bg-slate-50 text-slate-600 border-slate-200', 'akademik' => 'bg-teal-50 text-teal-700 border-teal-200', 'agenda' => 'bg-blue-50 text-blue-700 border-blue-200', 'kegiatan' => 'bg-purple-50 text-purple-700 border-purple-200', 'penting' => 'bg-rose-50 text-rose-700 border-rose-200'];
                            $k = $p['kategori'];
                            ?>
                            <span class="px-2 py-0.5 rounded border text-[10px] font-bold uppercase <?php echo $kc[$k] ?? 'bg-slate-50 text-slate-600 border-slate-200'; ?>"><?php echo e($k); ?></span>
                            <span class="px-2 py-0.5 rounded border text-[10px] font-bold bg-slate-50 text-slate-600 border-slate-200"><?= uiIcon('arrow',18) ?> <?php echo e($p['target_role']); ?></span>
                            <?php if ($p['is_published']): ?>
                            <span class="px-2 py-0.5 rounded border text-[10px] font-bold bg-emerald-50 text-emerald-700 border-emerald-200">Terbit</span>
                            <?php else: ?>
                            <span class="px-2 py-0.5 rounded border text-[10px] font-bold bg-amber-50 text-amber-700 border-amber-200">Draft</span>
                            <?php endif; ?>
                        </div>
                        <h3 class="text-sm font-bold text-slate-900"><?php echo e($p['judul_pengumuman']); ?></h3>
                        <p class="text-xs text-slate-600 mt-1 line-clamp-2"><?php echo nl2br(e(mb_substr(uiDisplayText($p['isi_pengumuman']), 0, 160))); ?><?php echo mb_strlen($p['isi_pengumuman']) > 160 ? '…' : ''; ?></p>
                        <div class="text-[10px] text-slate-400 mt-2">
                            <?php echo formatTanggalIndo($p['created_at']); ?> • <?php echo e($p['nama_pembuat'] ?? 'Sistem'); ?>
                        </div>
                    </div>
                    <div class="flex flex-col space-y-1.5">
                        <form method="POST" onsubmit="return confirm('Ubah status terbit?')">
                            <input type="hidden" name="action" value="toggle_publish">
                            <input type="hidden" name="pengumuman_id" value="<?php echo $p['id']; ?>">
                            <?php echo csrfField(); ?>
                            <button type="submit" class="w-full px-3 py-1.5 rounded-lg text-[10px] font-bold border <?php echo $p['is_published'] ? 'bg-amber-50 text-amber-700 border-amber-200 hover:bg-amber-100' : 'bg-emerald-50 text-emerald-700 border-emerald-200 hover:bg-emerald-100'; ?>">
                                <?php echo $p['is_published'] ? 'Tarik' : 'Terbitkan'; ?>
                            </button>
                        </form>
                        <form method="POST" onsubmit="return confirm('Hapus pengumuman ini?')">
                            <input type="hidden" name="action" value="hapus">
                            <input type="hidden" name="pengumuman_id" value="<?php echo $p['id']; ?>">
                            <?php echo csrfField(); ?>
                            <button type="submit" class="w-full px-3 py-1.5 rounded-lg text-[10px] font-bold bg-rose-50 text-rose-600 border border-rose-200 hover:bg-rose-100">Hapus</button>
                        </form>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</div>

<?php require_once '../includes/admin_footer.php'; ?>
