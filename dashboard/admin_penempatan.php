<?php
/**
 * EduLMS - Penempatan Siswa (Admin)
 * Kelola enrolmen siswa -> kelas (tabel siswa_kelas).
 * Alur: admin menempatkan siswa agar bisa mengikuti mapel.
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

// Daftar kelas (untuk form & filter)
$daftarKelas = $pdo->query("SELECT * FROM kelas ORDER BY tahun_ajaran DESC, nama_kelas")->fetchAll();
$kelasMap = [];
foreach ($daftarKelas as $k) { $kelasMap[$k['id']] = $k; }

try {
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    checkCsrfToken();
    validateAdminInput($pdo, basename(__FILE__), $_POST);
    $aksi = $_POST['action'] ?? '';

    if ($aksi === 'tempatkan') {
        $siswaId = intval($_POST['siswa_id'] ?? 0);
        $kelasId = intval($_POST['kelas_id'] ?? 0);
        $tahun   = trim($_POST['tahun_ajaran'] ?? '') ?: TAHUN_AJARAN_AKTIF;

        if ($siswaId <= 0 || $kelasId <= 0) {
            $pesan = 'Pilih siswa dan kelas terlebih dahulu.'; $jenisPesan = 'galat';
        } else {
            // Cek siswa valid
            $cs = $pdo->prepare("SELECT id FROM users WHERE id=? AND role='siswa'");
            $cs->execute([$siswaId]);
            if (!$cs->fetch()) {
                $pesan = 'Pengguna bukan siswa yang valid.'; $jenisPesan = 'galat';
            } else {
                // Cek sudah terdaftar aktif di kelas lain/tahun sama
                $ck = $pdo->prepare("SELECT id FROM siswa_kelas WHERE siswa_id=? AND tahun_ajaran=? AND status='aktif'");
                $ck->execute([$siswaId, $tahun]);
                if ($ck->fetch()) {
                    $pesan = 'Siswa sudah terdaftar aktif pada tahun ajaran tersebut.'; $jenisPesan = 'galat';
                } else {
                    $ins = $pdo->prepare("INSERT INTO siswa_kelas (siswa_id, kelas_id, tahun_ajaran, status) VALUES (?,?,?, 'aktif')");
                    if ($ins->execute([$siswaId, $kelasId, $tahun])) {
                        $pesan = 'Siswa berhasil ditempatkan ke kelas.';
                    } else {
                        $pesan = 'Gagal menyimpan penempatan.'; $jenisPesan = 'galat';
                    }
                }
            }
        }
    }

    elseif ($aksi === 'ubah_status') {
        $enrolId = intval($_POST['enrol_id'] ?? 0);
        $status  = $_POST['status'] ?? 'aktif';
        if (!in_array($status, ['aktif', 'pindah', 'lulus', 'keluar'])) {
            $pesan = 'Status tidak valid.'; $jenisPesan = 'galat';
        } else {
            $stmt = $pdo->prepare("UPDATE siswa_kelas SET status = ? WHERE id = ?");
            $stmt->execute([$status, $enrolId]);
            $pesan = 'Status enrolmen diperbarui.';
        }
    }

    elseif ($aksi === 'cabut') {
        $enrolId = intval($_POST['enrol_id'] ?? 0);
        $stmt = $pdo->prepare("DELETE FROM siswa_kelas WHERE id = ?");
        $stmt->execute([$enrolId]);
        $pesan = 'Penempatan siswa dicabut.';
    }
}
} catch (InvalidArgumentException $exception) {
    $pesan = $exception->getMessage(); $jenisPesan = 'galat';
} catch (PDOException $exception) {
    error_log($exception->getMessage());
    $pesan = 'Data tidak dapat disimpan. Periksa duplikasi identitas dan relasi data.'; $jenisPesan = 'galat';
}

// ===== DAFTAR ENROLMEN =====
$filterKelas = intval($_GET['kelas_id'] ?? 0);
$sql = "
    SELECT sk.*, u.nama_lengkap, u.email, u.nisn, k.nama_kelas, k.tingkat
    FROM siswa_kelas sk
    JOIN users u ON sk.siswa_id = u.id
    JOIN kelas k ON sk.kelas_id = k.id
";
$params = [];
if ($filterKelas > 0) { $sql .= " WHERE sk.kelas_id = ?"; $params[] = $filterKelas; }
$sql .= " ORDER BY sk.tahun_ajaran DESC, k.nama_kelas, u.nama_lengkap";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$daftarEnrol = $stmt->fetchAll();

// Siswa yang belum punya enrolmen aktif (opsional cepat)
$siswaBelum = $pdo->query("
    SELECT u.id, u.nama_lengkap, u.email, u.nisn
    FROM users u
    WHERE u.role='siswa' AND u.is_active=1
      AND NOT EXISTS (SELECT 1 FROM siswa_kelas sk WHERE sk.siswa_id=u.id AND sk.status='aktif')
    ORDER BY u.nama_lengkap
")->fetchAll();

// Prefill siswa dari query param (dari dashboard)
$prefillSiswa = intval($_GET['siswa'] ?? 0);

$listPager=uiPaginate($daftarEnrol,5);
$daftarEnrol=$listPager['items'];

$adminPageTitle = 'Penempatan Siswa';
$adminActive = 'penempatan';
require_once '../includes/admin_header.php';
?>

<?php if ($pesan !== ''): ?>
<div class="p-4 rounded-xl border text-sm font-semibold <?php echo $jenisPesan === 'sukses' ? 'bg-emerald-50 text-emerald-700 border-emerald-200' : 'bg-rose-50 text-rose-700 border-rose-200'; ?>">
    <?php echo e($pesan); ?>
</div>
<?php endif; ?>

<!-- Siswa Belum Ditempatkan -->
<?php if (count($siswaBelum) > 0): ?>
<div class="bg-amber-50 border border-amber-200 rounded-2xl p-5">
    <div class="flex items-center justify-between mb-3">
        <div>
            <h2 class="text-sm font-bold text-amber-800">Siswa Belum Ditempatkan (<?php echo count($siswaBelum); ?>)</h2>
            <p class="text-[11px] text-amber-700">Siswa ini belum bisa mengakses mapel sampai dimasukkan ke kelas</p>
        </div>
    </div>
    <div class="flex flex-wrap gap-2">
        <?php foreach ($siswaBelum as $s): ?>
        <div class="bg-white border border-amber-200 rounded-xl px-3 py-2 flex items-center space-x-3">
            <div>
                <div class="text-xs font-semibold text-slate-800"><?php echo e($s['nama_lengkap']); ?></div>
                <div class="text-[10px] text-slate-400"><?php echo e($s['nisn'] ?? $s['email']); ?></div>
            </div>
            <a href="#formTempatkan" onclick="pilihSiswa(<?php echo $s['id']; ?>)"
               class="px-2.5 py-1.5 bg-teal-700 hover:bg-teal-800 text-white text-[10px] font-bold rounded-lg">Tempatkan</a>
        </div>
        <?php endforeach; ?>
    </div>
</div>
<?php endif; ?>

<div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

    <!-- Form Penempatan -->
    <div id="formTempatkan" class="bg-white rounded-2xl border border-slate-200 shadow-sm p-6 h-fit">
        <h2 class="text-sm font-bold text-slate-900 mb-1">Tempatkan Siswa ke Kelas</h2>
        <p class="text-[11px] text-slate-500 mb-4">Satu siswa bisa di beberapa kelas (per tahun ajaran).</p>
        <form method="POST" class="space-y-3">
            <input type="hidden" name="action" value="tempatkan">
            <?php echo csrfField(); ?>
            <div>
                <label class="block text-xs font-semibold text-slate-600 mb-1">Siswa</label>
                <select name="siswa_id" id="siswaSelect" required class="w-full px-3 py-2 text-xs border border-slate-300 rounded-xl focus:outline-none focus:border-teal-600">
                    <option value="">— Pilih Siswa —</option>
                    <?php
                    // Tampilkan semua siswa aktif
                    $allSiswa = $pdo->query("SELECT id, nama_lengkap, nisn FROM users WHERE role='siswa' AND is_active=1 ORDER BY nama_lengkap")->fetchAll();
                    foreach ($allSiswa as $s):
                    ?>
                    <option value="<?php echo $s['id']; ?>" <?php echo $prefillSiswa == $s['id'] ? 'selected' : ''; ?>>
                        <?php echo e($s['nama_lengkap']); ?> (<?php echo e($s['nisn'] ?? 'tanpa NISN'); ?>)
                    </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div>
                <label class="block text-xs font-semibold text-slate-600 mb-1">Kelas</label>
                <select name="kelas_id" required class="w-full px-3 py-2 text-xs border border-slate-300 rounded-xl focus:outline-none focus:border-teal-600">
                    <option value="">— Pilih Kelas —</option>
                    <?php foreach ($daftarKelas as $k): ?>
                    <option value="<?php echo $k['id']; ?>">
                        <?php echo e($k['nama_kelas']); ?> — <?php echo e($k['jurusan'] ?? ''); ?> (<?php echo e($k['tahun_ajaran']); ?>)
                    </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div>
                <label class="block text-xs font-semibold text-slate-600 mb-1">Tahun Ajaran</label>
                <input type="text" name="tahun_ajaran" value="<?php echo e(TAHUN_AJARAN_AKTIF); ?>" class="w-full px-3 py-2 text-xs border border-slate-300 rounded-xl focus:outline-none focus:border-teal-600">
            </div>
            <button type="submit" class="w-full py-2.5 bg-teal-700 hover:bg-teal-800 text-white text-xs font-bold rounded-xl transition">
                Tempatkan
            </button>
        </form>
    </div>

    <!-- Daftar Enrolmen -->
    <div class="lg:col-span-2 bg-white rounded-2xl border border-slate-200 shadow-sm">
        <div class="p-6 border-b border-slate-100 flex flex-wrap justify-between items-center gap-3">
            <div>
                <h2 class="text-sm font-bold text-slate-900">Daftar Penempatan</h2>
                <p class="text-[11px] text-slate-500"><?php echo $listPager['total']; ?> baris enrolmen</p>
            </div>
            <form method="GET" class="flex items-center space-x-2">
                <select name="kelas_id" onchange="this.form.submit()" class="px-3 py-1.5 text-[11px] border border-slate-300 rounded-lg focus:outline-none focus:border-teal-600">
                    <option value="0">Semua Kelas</option>
                    <?php foreach ($daftarKelas as $k): ?>
                    <option value="<?php echo $k['id']; ?>" <?php echo $filterKelas == $k['id'] ? 'selected' : ''; ?>><?php echo e($k['nama_kelas']); ?> (<?php echo e($k['tahun_ajaran']); ?>)</option>
                    <?php endforeach; ?>
                </select>
            </form>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full text-xs">
                <thead>
                    <tr class="text-left text-[10px] uppercase text-slate-400 border-b border-slate-100">
                        <th class="py-2.5 px-4">Siswa</th>
                        <th class="py-2.5 px-4">Kelas</th>
                        <th class="py-2.5 px-4">Tahun</th>
                        <th class="py-2.5 px-4">Status</th>
                        <th class="py-2.5 px-4 text-right">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($listPager['total'] === 0): ?>
                    <tr><td colspan="5" class="py-8 text-center text-slate-400">Belum ada penempatan.</td></tr>
                    <?php endif; ?>
                    <?php foreach ($daftarEnrol as $e): ?>
                    <tr class="border-b border-slate-50 hover:bg-slate-50">
                        <td class="py-3 px-4">
                            <div class="font-semibold text-slate-800"><?php echo e($e['nama_lengkap']); ?></div>
                            <div class="text-[10px] text-slate-400"><?php echo e($e['nisn'] ?? $e['email']); ?></div>
                        </td>
                        <td class="py-3 px-4 text-slate-600"><?php echo e($e['nama_kelas']); ?></td>
                        <td class="py-3 px-4 text-slate-600"><?php echo e($e['tahun_ajaran']); ?></td>
                        <td class="py-3 px-4">
                            <?php
                            $sc = ['aktif' => 'bg-emerald-50 text-emerald-700 border-emerald-200', 'pindah' => 'bg-amber-50 text-amber-700 border-amber-200', 'lulus' => 'bg-blue-50 text-blue-700 border-blue-200', 'keluar' => 'bg-rose-50 text-rose-700 border-rose-200'];
                            ?>
                            <span class="px-2 py-0.5 rounded border text-[10px] font-bold <?php echo $sc[$e['status']]; ?>"><?php echo $e['status']; ?></span>
                        </td>
                        <td class="py-3 px-4 text-right">
                            <div class="inline-flex items-center space-x-1">
                                <form method="POST" class="inline-flex items-center space-x-1">
                                    <input type="hidden" name="action" value="ubah_status">
                                    <input type="hidden" name="enrol_id" value="<?php echo $e['id']; ?>">
                                    <?php echo csrfField(); ?>
                                    <select name="status" class="px-2 py-1.5 text-[10px] border border-slate-300 rounded-lg focus:outline-none focus:border-teal-600">
                                        <?php foreach (['aktif','pindah','lulus','keluar'] as $st): ?>
                                        <option value="<?php echo $st; ?>" <?php echo $e['status'] === $st ? 'selected' : ''; ?>><?php echo ucfirst($st); ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                    <button type="submit" class="px-2 py-1.5 bg-slate-100 hover:bg-slate-200 text-slate-600 text-[10px] font-bold rounded-lg">OK</button>
                                </form>
                                <form method="POST" class="inline" onsubmit="return confirm('Cabut penempatan ini?')">
                                    <input type="hidden" name="action" value="cabut">
                                    <input type="hidden" name="enrol_id" value="<?php echo $e['id']; ?>">
                                    <?php echo csrfField(); ?>
                                    <button type="submit" class="px-2 py-1.5 bg-rose-50 hover:bg-rose-100 text-rose-600 text-[10px] font-bold rounded-lg border border-rose-200">Cabut</button>
                                </form>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<script>
function pilihSiswa(id) {
    var sel = document.getElementById('siswaSelect');
    if (sel) { sel.value = String(id); }
}
</script>

<?php require_once '../includes/admin_footer.php'; ?>
