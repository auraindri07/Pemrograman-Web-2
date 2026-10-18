<?php
/**
 * EduLMS - Kelola Kelas (Admin)
 * Buat & edit kelas, tetapkan wali kelas.
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

// Daftar guru untuk dropdown wali kelas
$guruStmt = $pdo->query("SELECT id, nama_lengkap FROM users WHERE role='guru' AND is_active=1 ORDER BY nama_lengkap");
$daftarGuru = $guruStmt->fetchAll();

try {
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    checkCsrfToken();
    validateAdminInput($pdo, basename(__FILE__), $_POST);
    $aksi = $_POST['action'] ?? '';

    if ($aksi === 'buat_kelas') {
        $nama   = trim($_POST['nama_kelas'] ?? '');
        $tingkat = intval($_POST['tingkat'] ?? 0);
        $jurusan = trim($_POST['jurusan'] ?? '') ?: null;
        $tahun  = trim($_POST['tahun_ajaran'] ?? '') ?: TAHUN_AJARAN_AKTIF;
        $wali   = intval($_POST['wali_kelas_id'] ?? 0) ?: null;

        if ($nama === '' || $tingkat < 10 || $tingkat > 12) {
            $pesan = 'Nama kelas wajib dan tingkat harus 10–12.'; $jenisPesan = 'galat';
        } else {
            $cek = $pdo->prepare("SELECT id FROM kelas WHERE nama_kelas=? AND tahun_ajaran=?");
            $cek->execute([$nama, $tahun]);
            if ($cek->fetch()) {
                $pesan = 'Kelas tersebut sudah ada pada tahun ajaran itu.'; $jenisPesan = 'galat';
            } else {
                $ins = $pdo->prepare("INSERT INTO kelas (nama_kelas, tingkat, jurusan, tahun_ajaran, wali_kelas_id) VALUES (?,?,?,?,?)");
                if ($ins->execute([$nama, $tingkat, $jurusan, $tahun, $wali])) {
                    $pesan = "Kelas '$nama' berhasil dibuat.";
                } else {
                    $pesan = 'Gagal menyimpan kelas.'; $jenisPesan = 'galat';
                }
            }
        }
    }

    elseif ($aksi === 'set_wali') {
        $kelasId = intval($_POST['kelas_id'] ?? 0);
        $wali = intval($_POST['wali_kelas_id'] ?? 0) ?: null;
        $stmt = $pdo->prepare("UPDATE kelas SET wali_kelas_id=? WHERE id=?");
        $stmt->execute([$wali, $kelasId]);
        $pesan = 'Wali kelas diperbarui.';
    }
}
} catch (InvalidArgumentException $exception) {
    $pesan = $exception->getMessage(); $jenisPesan = 'galat';
} catch (PDOException $exception) {
    error_log($exception->getMessage());
    $pesan = 'Data tidak dapat disimpan. Periksa duplikasi identitas dan relasi data.'; $jenisPesan = 'galat';
}

// Daftar kelas + info
$daftar = $pdo->query("
    SELECT k.*, u.nama_lengkap as nama_wali,
           (SELECT COUNT(*) FROM siswa_kelas sk WHERE sk.kelas_id=k.id AND sk.status='aktif') as jumlah_siswa
    FROM kelas k
    LEFT JOIN users u ON k.wali_kelas_id = u.id
    ORDER BY k.tahun_ajaran DESC, k.nama_kelas
")->fetchAll();

$listPager=uiPaginate($daftar,5);
$daftar=$listPager['items'];

$adminPageTitle = 'Kelola Kelas';
$adminActive = 'kelas';
require_once '../includes/admin_header.php';
?>

<?php if ($pesan !== ''): ?>
<div class="p-4 rounded-xl border text-sm font-semibold <?php echo $jenisPesan === 'sukses' ? 'bg-emerald-50 text-emerald-700 border-emerald-200' : 'bg-rose-50 text-rose-700 border-rose-200'; ?>">
    <?php echo e($pesan); ?>
</div>
<?php endif; ?>

<div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

    <!-- Form Buat Kelas -->
    <div class="bg-white rounded-2xl border border-slate-200 shadow-sm p-6 h-fit">
        <h2 class="text-sm font-bold text-slate-900 mb-1">Buat Kelas Baru</h2>
        <p class="text-[11px] text-slate-500 mb-4">Kelas menjadi wadah penugasan guru & enrolmen siswa.</p>
        <form method="POST" class="space-y-3">
            <input type="hidden" name="action" value="buat_kelas">
            <?php echo csrfField(); ?>
            <div>
                <label class="block text-xs font-semibold text-slate-600 mb-1">Nama Kelas</label>
                <input type="text" name="nama_kelas" required placeholder="cth. XI MIPA 1" class="w-full px-3 py-2 text-xs border border-slate-300 rounded-xl focus:outline-none focus:border-teal-600">
            </div>
            <div class="grid grid-cols-2 gap-3">
                <div>
                    <label class="block text-xs font-semibold text-slate-600 mb-1">Tingkat</label>
                    <select name="tingkat" required class="w-full px-3 py-2 text-xs border border-slate-300 rounded-xl focus:outline-none focus:border-teal-600">
                        <option value="10">10 (X)</option>
                        <option value="11">11 (XI)</option>
                        <option value="12">12 (XII)</option>
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-semibold text-slate-600 mb-1">Jurusan</label>
                    <input type="text" name="jurusan" placeholder="MIPA/IPS" class="w-full px-3 py-2 text-xs border border-slate-300 rounded-xl focus:outline-none focus:border-teal-600">
                </div>
            </div>
            <div>
                <label class="block text-xs font-semibold text-slate-600 mb-1">Tahun Ajaran</label>
                <input type="text" name="tahun_ajaran" value="<?php echo e(TAHUN_AJARAN_AKTIF); ?>" class="w-full px-3 py-2 text-xs border border-slate-300 rounded-xl focus:outline-none focus:border-teal-600">
            </div>
            <div>
                <label class="block text-xs font-semibold text-slate-600 mb-1">Wali Kelas (opsional)</label>
                <select name="wali_kelas_id" class="w-full px-3 py-2 text-xs border border-slate-300 rounded-xl focus:outline-none focus:border-teal-600">
                    <option value="0">— Pilih Guru —</option>
                    <?php foreach ($daftarGuru as $g): ?>
                    <option value="<?php echo $g['id']; ?>"><?php echo e($g['nama_lengkap']); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <button type="submit" class="w-full py-2.5 bg-teal-700 hover:bg-teal-800 text-white text-xs font-bold rounded-xl transition">
                Buat Kelas
            </button>
        </form>
    </div>

    <!-- Daftar Kelas -->
    <div class="lg:col-span-2 bg-white rounded-2xl border border-slate-200 shadow-sm">
        <div class="p-6 border-b border-slate-100">
            <h2 class="text-sm font-bold text-slate-900">Daftar Kelas</h2>
            <p class="text-[11px] text-slate-500"><?php echo $listPager['total']; ?> kelas terdaftar</p>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full text-xs">
                <thead>
                    <tr class="text-left text-[10px] uppercase text-slate-400 border-b border-slate-100">
                        <th class="py-2.5 px-4">Kelas</th>
                        <th class="py-2.5 px-4">Tingkat</th>
                        <th class="py-2.5 px-4">Tahun</th>
                        <th class="py-2.5 px-4">Siswa</th>
                        <th class="py-2.5 px-4">Wali Kelas</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($daftar as $k): ?>
                    <tr class="border-b border-slate-50 hover:bg-slate-50">
                        <td class="py-3 px-4 font-semibold text-slate-800"><?php echo e($k['nama_kelas']); ?> <span class="text-[10px] text-slate-400"><?php echo e($k['jurusan'] ?? ''); ?></span></td>
                        <td class="py-3 px-4 text-slate-600"><?php echo $k['tingkat']; ?></td>
                        <td class="py-3 px-4 text-slate-600"><?php echo e($k['tahun_ajaran']); ?></td>
                        <td class="py-3 px-4"><span class="px-2 py-0.5 rounded bg-teal-50 text-teal-700 font-bold"><?php echo $k['jumlah_siswa']; ?></span></td>
                        <td class="py-3 px-4">
                            <form method="POST" class="flex items-center space-x-1">
                                <input type="hidden" name="action" value="set_wali">
                                <input type="hidden" name="kelas_id" value="<?php echo $k['id']; ?>">
                                <?php echo csrfField(); ?>
                                <select name="wali_kelas_id" class="px-2 py-1.5 text-[11px] border border-slate-300 rounded-lg focus:outline-none focus:border-teal-600">
                                    <option value="0">— Wali —</option>
                                    <?php foreach ($daftarGuru as $g): ?>
                                    <option value="<?php echo $g['id']; ?>" <?php echo $k['wali_kelas_id'] == $g['id'] ? 'selected' : ''; ?>><?php echo e($g['nama_lengkap']); ?></option>
                                    <?php endforeach; ?>
                                </select>
                                <button type="submit" class="px-2 py-1.5 bg-slate-100 hover:bg-slate-200 text-slate-600 text-[10px] font-bold rounded-lg">Simpan</button>
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
