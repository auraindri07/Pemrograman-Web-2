<?php
/**
 * EduLMS - Kelola Mata Pelajaran (Admin)
 * Buat & edit mata pelajaran.
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

try {
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    checkCsrfToken();
    validateAdminInput($pdo, basename(__FILE__), $_POST);
    $aksi = $_POST['action'] ?? '';

    if ($aksi === 'buat_mapel') {
        $kode   = strtoupper(trim($_POST['kode_mapel'] ?? ''));
        $nama   = trim($_POST['nama_mapel'] ?? '');
        $kat    = $_POST['kategori'] ?? 'umum';
        $emoji  = trim($_POST['icon_emoji'] ?? 'book') ?: 'book';
        if(!in_array($emoji,['book','math','flask','terminal','translate','microscope','school'],true))$emoji='book';
        $warna  = trim($_POST['color_class'] ?? 'bg-teal-50 text-teal-700') ?: 'bg-teal-50 text-teal-700';
        $desk   = trim($_POST['deskripsi'] ?? '');

        if ($kode === '' || $nama === '') {
            $pesan = 'Kode & nama mata pelajaran wajib diisi.'; $jenisPesan = 'galat';
        } else {
            $cek = $pdo->prepare("SELECT id FROM mata_pelajaran WHERE kode_mapel = ?");
            $cek->execute([$kode]);
            if ($cek->fetch()) {
                $pesan = 'Kode mata pelajaran sudah dipakai.'; $jenisPesan = 'galat';
            } else {
                $ins = $pdo->prepare("INSERT INTO mata_pelajaran (kode_mapel, nama_mapel, deskripsi, kategori, icon_emoji, color_class) VALUES (?,?,?,?,?,?)");
                if ($ins->execute([$kode, $nama, $desk, $kat, $emoji, $warna])) {
                    $pesan = "Mata pelajaran '$nama' berhasil dibuat.";
                } else {
                    $pesan = 'Gagal menyimpan mata pelajaran.'; $jenisPesan = 'galat';
                }
            }
        }
    }

    elseif ($aksi === 'ubah_deskripsi') {
        $id   = intval($_POST['mapel_id'] ?? 0);
        $desk = trim($_POST['deskripsi'] ?? '');
        $stmt = $pdo->prepare("UPDATE mata_pelajaran SET deskripsi = ? WHERE id = ?");
        $stmt->execute([$desk, $id]);
        $pesan = 'Deskripsi mata pelajaran diperbarui.';
    }
}
} catch (InvalidArgumentException $exception) {
    $pesan = $exception->getMessage(); $jenisPesan = 'galat';
} catch (PDOException $exception) {
    error_log($exception->getMessage());
    $pesan = 'Data tidak dapat disimpan. Periksa duplikasi identitas dan relasi data.'; $jenisPesan = 'galat';
}

// Daftar mapel + jumlah penggunaan
$daftar = $pdo->query("
    SELECT mp.*,
           (SELECT COUNT(*) FROM guru_mapel gm WHERE gm.mapel_id = mp.id AND gm.is_active=1) as jumlah_guru,
           (SELECT COUNT(*) FROM materi m JOIN guru_mapel gm ON m.guru_mapel_id = gm.id WHERE gm.mapel_id = mp.id) as jumlah_materi
    FROM mata_pelajaran mp
    ORDER BY mp.kategori, mp.nama_mapel
")->fetchAll();

$listPager=uiPaginate($daftar,5);
$daftar=$listPager['items'];

$adminPageTitle = 'Kelola Mata Pelajaran';
$adminActive = 'mapel';
require_once '../includes/admin_header.php';
?>

<?php if ($pesan !== ''): ?>
<div class="p-4 rounded-xl border text-sm font-semibold <?php echo $jenisPesan === 'sukses' ? 'bg-emerald-50 text-emerald-700 border-emerald-200' : 'bg-rose-50 text-rose-700 border-rose-200'; ?>">
    <?php echo e($pesan); ?>
</div>
<?php endif; ?>

<div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

    <!-- Form Buat Mapel -->
    <div class="bg-white rounded-2xl border border-slate-200 shadow-sm p-6 h-fit">
        <h2 class="text-sm font-bold text-slate-900 mb-1">Buat Mata Pelajaran</h2>
        <p class="text-[11px] text-slate-500 mb-4">Mapel dipakai guru untuk mengajar & membuat materi/tugas.</p>
        <form method="POST" class="space-y-3">
            <input type="hidden" name="action" value="buat_mapel">
            <?php echo csrfField(); ?>
            <div class="grid grid-cols-2 gap-3">
                <div>
                    <label class="block text-xs font-semibold text-slate-600 mb-1">Kode</label>
                    <input type="text" name="kode_mapel" required placeholder="MTK" class="w-full px-3 py-2 text-xs border border-slate-300 rounded-xl focus:outline-none focus:border-teal-600">
                </div>
                <div>
                    <label class="block text-xs font-semibold text-slate-600 mb-1">Ikon</label>
                    <select name="icon_emoji" class="w-full px-3 py-2 text-xs border border-slate-300 rounded-xl"><option value="book">Buku</option><option value="math">Matematika</option><option value="flask">Sains</option><option value="terminal">Komputer</option><option value="translate">Bahasa</option><option value="microscope">Biologi</option><option value="school">Pendidikan</option></select>
                </div>
            </div>
            <div>
                <label class="block text-xs font-semibold text-slate-600 mb-1">Nama Mapel</label>
                <input type="text" name="nama_mapel" required placeholder="Matematika" class="w-full px-3 py-2 text-xs border border-slate-300 rounded-xl focus:outline-none focus:border-teal-600">
            </div>
            <div>
                <label class="block text-xs font-semibold text-slate-600 mb-1">Kategori</label>
                <select name="kategori" class="w-full px-3 py-2 text-xs border border-slate-300 rounded-xl focus:outline-none focus:border-teal-600">
                    <option value="wajib">Wajib</option>
                    <option value="peminatan">Peminatan</option>
                    <option value="lintas_minat">Lintas Minat</option>
                </select>
            </div>
            <div>
                <label class="block text-xs font-semibold text-slate-600 mb-1">Deskripsi (opsional)</label>
                <textarea name="deskripsi" rows="3" class="w-full px-3 py-2 text-xs border border-slate-300 rounded-xl focus:outline-none focus:border-teal-600"></textarea>
            </div>
            <button type="submit" class="w-full py-2.5 bg-teal-700 hover:bg-teal-800 text-white text-xs font-bold rounded-xl transition">
                Buat Mata Pelajaran
            </button>
        </form>
    </div>

    <!-- Daftar Mapel -->
    <div class="lg:col-span-2 bg-white rounded-2xl border border-slate-200 shadow-sm">
        <div class="p-6 border-b border-slate-100">
            <h2 class="text-sm font-bold text-slate-900">Daftar Mata Pelajaran</h2>
            <p class="text-[11px] text-slate-500"><?php echo $listPager['total']; ?> mapel terdaftar</p>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full text-xs">
                <thead>
                    <tr class="text-left text-[10px] uppercase text-slate-400 border-b border-slate-100">
                        <th class="py-2.5 px-4">Mapel</th>
                        <th class="py-2.5 px-4">Kategori</th>
                        <th class="py-2.5 px-4">Guru</th>
                        <th class="py-2.5 px-4">Materi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($daftar as $m): ?>
                    <tr class="border-b border-slate-50 hover:bg-slate-50 align-top">
                        <td class="py-3 px-4">
                            <div class="flex items-center space-x-2">
                                <span class="text-lg"><?php echo uiSubjectGlyph($m['nama_mapel'], $m['icon_emoji']); ?></span>
                                <div>
                                    <div class="font-semibold text-slate-800"><?php echo e($m['nama_mapel']); ?></div>
                                    <div class="text-[10px] text-slate-400"><?php echo e($m['kode_mapel']); ?> — <?php echo e(mb_substr((string)$m['deskripsi'], 0, 50)); ?><?php echo mb_strlen((string)$m['deskripsi']) > 50 ? '…' : ''; ?></div>
                                </div>
                            </div>
                        </td>
                        <td class="py-3 px-4">
                            <span class="px-2 py-0.5 rounded border text-[10px] font-bold bg-slate-50 text-slate-600 border-slate-200 uppercase"><?php echo e($m['kategori']); ?></span>
                        </td>
                        <td class="py-3 px-4 text-slate-600"><?php echo $m['jumlah_guru']; ?></td>
                        <td class="py-3 px-4 text-slate-600"><?php echo $m['jumlah_materi']; ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php require_once '../includes/admin_footer.php'; ?>
