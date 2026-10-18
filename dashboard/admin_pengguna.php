<?php
/**
 * EduLMS - Kelola Pengguna (Admin)
 * Buat akun guru/siswa, aktifkan/nonaktifkan, reset password.
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

// ===== AKSI =====
try {
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    checkCsrfToken();
    validateAdminInput($pdo, basename(__FILE__), $_POST);
    $aksi = $_POST['action'] ?? '';

    // Buat akun baru
    if ($aksi === 'buat_pengguna') {
        $nama  = trim($_POST['nama_lengkap'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $role  = $_POST['role'] ?? 'siswa';
        $pass  = $_POST['password'] ?? '';
        $nisn  = trim($_POST['nisn'] ?? '') ?: null;
        $nip   = trim($_POST['nip'] ?? '') ?: null;

        if (!in_array($role, ['guru', 'siswa'])) {
            $pesan = 'Role tidak valid.'; $jenisPesan = 'galat';
        } elseif ($nama === '' || $email === '' || $pass === '') {
            $pesan = 'Nama, email, dan password wajib diisi.'; $jenisPesan = 'galat';
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $pesan = 'Format email tidak valid.'; $jenisPesan = 'galat';
        } elseif (strlen($pass) < 6) {
            $pesan = 'Password minimal 6 karakter.'; $jenisPesan = 'galat';
        } else {
            $cek = $pdo->prepare("SELECT id FROM users WHERE email = ?");
            $cek->execute([$email]);
            if ($cek->fetch()) {
                $pesan = 'Email sudah terdaftar.'; $jenisPesan = 'galat';
            } else {
                $hash = password_hash($pass, PASSWORD_DEFAULT);
                $ins = $pdo->prepare("INSERT INTO users (nama_lengkap, email, password, role, nisn, nip, is_active) VALUES (?,?,?,?,?,?,1)");
                if ($ins->execute([$nama, $email, $hash, $role, $role === 'siswa' ? $nisn : null, $role === 'guru' ? $nip : null])) {
                    $pesan = "Akun $role '$nama' berhasil dibuat.";
                } else {
                    $pesan = 'Gagal menyimpan pengguna.'; $jenisPesan = 'galat';
                }
            }
        }
    }

    // Aktif / nonaktif
    elseif ($aksi === 'toggle_aktif') {
        $id = intval($_POST['user_id'] ?? 0);
        if ($id === (int)$user_id) {
            $pesan = 'Tidak dapat menonaktifkan akun Anda sendiri.'; $jenisPesan = 'galat';
        } else {
            $stmt = $pdo->prepare("UPDATE users SET is_active = IF(is_active=1,0,1) WHERE id = ?");
            $stmt->execute([$id]);
            $pesan = 'Status akun diperbarui.';
            $jenisPesan = 'sukses';
        }
    }

    // Reset password
    elseif ($aksi === 'reset_password') {
        $id = intval($_POST['user_id'] ?? 0);
        $pass = $_POST['password'] ?? '';
        if (strlen($pass) < 6) {
            $pesan = 'Password baru minimal 6 karakter.'; $jenisPesan = 'galat';
        } else {
            $hash = password_hash($pass, PASSWORD_DEFAULT);
            $stmt = $pdo->prepare("UPDATE users SET password = ? WHERE id = ?");
            $stmt->execute([$hash, $id]);
            $pesan = 'Password berhasil direset.'; $jenisPesan = 'sukses';
        }
    }
}
} catch (InvalidArgumentException $exception) {
    $pesan = $exception->getMessage(); $jenisPesan = 'galat';
} catch (PDOException $exception) {
    error_log($exception->getMessage());
    $pesan = 'Data tidak dapat disimpan. Periksa duplikasi identitas dan relasi data.'; $jenisPesan = 'galat';
}

// ===== FILTER & DAFTAR =====
$filterRole = $_GET['role'] ?? 'all';
$sql = "SELECT id, nama_lengkap, email, role, nisn, nip, is_active, created_at FROM users";
$params = [];
if (in_array($filterRole, ['guru', 'siswa', 'admin'])) {
    $sql .= " WHERE role = ?";
    $params[] = $filterRole;
}
$sql .= " ORDER BY FIELD(role,'admin','guru','siswa'), nama_lengkap";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$daftar = $stmt->fetchAll();

$listPager=uiPaginate($daftar,5);
$daftar=$listPager['items'];

$adminPageTitle = 'Kelola Pengguna';
$adminActive = 'pengguna';
require_once '../includes/admin_header.php';
?>

<?php if ($pesan !== ''): ?>
<div class="p-4 rounded-xl border text-sm font-semibold <?php echo $jenisPesan === 'sukses' ? 'bg-emerald-50 text-emerald-700 border-emerald-200' : 'bg-rose-50 text-rose-700 border-rose-200'; ?>">
    <?php echo e($pesan); ?>
</div>
<?php endif; ?>

<div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

    <!-- Form Buat Akun -->
    <div class="bg-white rounded-2xl border border-slate-200 shadow-sm p-6 h-fit">
        <h2 class="text-sm font-bold text-slate-900 mb-1">Buat Akun Baru</h2>
        <p class="text-[11px] text-slate-500 mb-4">Tambahkan akun guru dan siswa beserta identitasnya.</p>
        <form method="POST" class="space-y-3">
            <input type="hidden" name="action" value="buat_pengguna">
            <?php echo csrfField(); ?>
            <div>
                <label class="block text-xs font-semibold text-slate-600 mb-1">Nama Lengkap</label>
                <input type="text" name="nama_lengkap" required class="w-full px-3 py-2 text-xs border border-slate-300 rounded-xl focus:outline-none focus:border-teal-600">
            </div>
            <div>
                <label class="block text-xs font-semibold text-slate-600 mb-1">Email</label>
                <input type="email" name="email" required class="w-full px-3 py-2 text-xs border border-slate-300 rounded-xl focus:outline-none focus:border-teal-600">
            </div>
            <div>
                <label class="block text-xs font-semibold text-slate-600 mb-1">Role</label>
                <select name="role" id="roleSelect" required class="w-full px-3 py-2 text-xs border border-slate-300 rounded-xl focus:outline-none focus:border-teal-600">
                    <option value="siswa">Siswa</option>
                    <option value="guru">Guru</option>
                </select>
            </div>
            <div id="nisnWrap">
                <label class="block text-xs font-semibold text-slate-600 mb-1">NISN (opsional)</label>
                <input type="text" name="nisn" class="w-full px-3 py-2 text-xs border border-slate-300 rounded-xl focus:outline-none focus:border-teal-600">
            </div>
            <div id="nipWrap" class="hidden">
                <label class="block text-xs font-semibold text-slate-600 mb-1">NIP (opsional)</label>
                <input type="text" name="nip" class="w-full px-3 py-2 text-xs border border-slate-300 rounded-xl focus:outline-none focus:border-teal-600">
            </div>
            <div>
                <label class="block text-xs font-semibold text-slate-600 mb-1">Password (min 6)</label>
                <input type="password" name="password" required minlength="6" class="w-full px-3 py-2 text-xs border border-slate-300 rounded-xl focus:outline-none focus:border-teal-600">
            </div>
            <button type="submit" class="w-full py-2.5 bg-teal-700 hover:bg-teal-800 text-white text-xs font-bold rounded-xl transition">
                Buat Akun
            </button>
        </form>
    </div>

    <!-- Daftar Pengguna -->
    <div class="lg:col-span-2 bg-white rounded-2xl border border-slate-200 shadow-sm">
        <div class="p-6 border-b border-slate-100 flex flex-wrap justify-between items-center gap-3">
            <div>
                <h2 class="text-sm font-bold text-slate-900">Daftar Pengguna</h2>
                <p class="text-[11px] text-slate-500"><?php echo $listPager['total']; ?> terdaftar</p>
            </div>
            <div class="flex space-x-1 text-xs">
                <?php
                $tabs = ['all' => 'Semua', 'admin' => 'Admin', 'guru' => 'Guru', 'siswa' => 'Siswa'];
                foreach ($tabs as $k => $lbl):
                    $cls = $filterRole === $k ? 'bg-teal-50 text-teal-700 border-teal-200' : 'bg-white text-slate-500 border-slate-200 hover:bg-slate-50';
                ?>
                <a href="admin_pengguna.php?role=<?php echo $k; ?>" class="px-3 py-1.5 rounded-lg font-semibold border <?php echo $cls; ?>"><?php echo $lbl; ?></a>
                <?php endforeach; ?>
            </div>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full text-xs">
                <thead>
                    <tr class="text-left text-[10px] uppercase text-slate-400 border-b border-slate-100">
                        <th class="py-2.5 px-4">Nama</th>
                        <th class="py-2.5 px-4">Role</th>
                        <th class="py-2.5 px-4">NISN/NIP</th>
                        <th class="py-2.5 px-4">Status</th>
                        <th class="py-2.5 px-4 text-right">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($daftar as $u): ?>
                    <tr class="border-b border-slate-50 hover:bg-slate-50">
                        <td class="py-3 px-4">
                            <div class="font-semibold text-slate-800"><?php echo e($u['nama_lengkap']); ?></div>
                            <div class="text-[10px] text-slate-500"><?php echo e($u['email']); ?></div>
                        </td>
                        <td class="py-3 px-4">
                            <?php
                            $rc = ['admin' => 'bg-purple-50 text-purple-700 border-purple-200', 'guru' => 'bg-blue-50 text-blue-700 border-blue-200', 'siswa' => 'bg-teal-50 text-teal-700 border-teal-200'];
                            ?>
                            <span class="px-2 py-0.5 rounded border text-[10px] font-bold uppercase <?php echo $rc[$u['role']]; ?>"><?php echo $u['role']; ?></span>
                        </td>
                        <td class="py-3 px-4 text-slate-500"><?php echo e($u['nisn'] ?? $u['nip'] ?? '-'); ?></td>
                        <td class="py-3 px-4">
                            <?php if ($u['is_active']): ?>
                            <span class="px-2 py-0.5 rounded border text-[10px] font-bold bg-emerald-50 text-emerald-700 border-emerald-200">Aktif</span>
                            <?php else: ?>
                            <span class="px-2 py-0.5 rounded border text-[10px] font-bold bg-rose-50 text-rose-700 border-rose-200">Nonaktif</span>
                            <?php endif; ?>
                        </td>
                        <td class="py-3 px-4 text-right">
                            <div class="inline-flex items-center space-x-1">
                                <?php if ((int)$u['id'] !== (int)$user_id): ?>
                                <form method="POST" class="inline" onsubmit="return confirm('Ubah status akun ini?')">
                                    <input type="hidden" name="action" value="toggle_aktif">
                                    <input type="hidden" name="user_id" value="<?php echo $u['id']; ?>">
                                    <?php echo csrfField(); ?>
                                    <button type="submit" class="px-2.5 py-1.5 rounded-lg text-[10px] font-bold border <?php echo $u['is_active'] ? 'bg-amber-50 text-amber-700 border-amber-200 hover:bg-amber-100' : 'bg-emerald-50 text-emerald-700 border-emerald-200 hover:bg-emerald-100'; ?>">
                                        <?php echo $u['is_active'] ? 'Nonaktifkan' : 'Aktifkan'; ?>
                                    </button>
                                </form>
                                <?php endif; ?>
                                <button type="button" onclick="openReset(<?php echo $u['id']; ?>, <?php echo e(json_encode($u['nama_lengkap'], JSON_HEX_APOS | JSON_HEX_QUOT)); ?>)"
                                        class="px-2.5 py-1.5 rounded-lg text-[10px] font-bold bg-slate-50 text-slate-600 border border-slate-200 hover:bg-slate-100">
                                    Reset Password
                                </button>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Modal Reset Password -->
<div id="resetModal" class="hidden fixed inset-0 bg-black/40 z-50 flex items-center justify-center p-4">
    <div class="bg-white rounded-2xl p-6 w-full max-w-sm shadow-xl">
        <h3 class="text-sm font-bold text-slate-900 mb-1">Reset Password</h3>
        <p class="text-[11px] text-slate-500 mb-4">Untuk: <span id="resetNama" class="font-semibold"></span></p>
        <form method="POST" class="space-y-3">
            <input type="hidden" name="action" value="reset_password">
            <input type="hidden" name="user_id" id="resetUserId">
            <?php echo csrfField(); ?>
            <div>
                <label class="block text-xs font-semibold text-slate-600 mb-1">Password Baru (min 6)</label>
                <input type="password" name="password" required minlength="6" class="w-full px-3 py-2 text-xs border border-slate-300 rounded-xl focus:outline-none focus:border-teal-600">
            </div>
            <div class="flex space-x-2">
                <button type="button" onclick="closeReset()" class="flex-1 py-2 bg-slate-100 hover:bg-slate-200 text-slate-700 text-xs font-bold rounded-xl transition">Batal</button>
                <button type="submit" class="flex-1 py-2 bg-teal-700 hover:bg-teal-800 text-white text-xs font-bold rounded-xl transition">Simpan</button>
            </div>
        </form>
    </div>
</div>

<script>
function openReset(id, nama) {
    document.getElementById('resetUserId').value = id;
    document.getElementById('resetNama').textContent = nama;
    document.getElementById('resetModal').classList.remove('hidden');
}
function closeReset() { document.getElementById('resetModal').classList.add('hidden'); }
// Toggle NISN/NIP berdasarkan role
document.getElementById('roleSelect').addEventListener('change', function () {
    var isGuru = this.value === 'guru';
    document.getElementById('nipWrap').classList.toggle('hidden', !isGuru);
    document.getElementById('nisnWrap').classList.toggle('hidden', isGuru);
});
</script>

<?php require_once '../includes/admin_footer.php'; ?>
