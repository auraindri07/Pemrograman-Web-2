<?php
/**
 * EduLMS - Buat Tugas Baru
 * Create new assignment for students
 */

require_once '../includes/db.php';
require_once '../includes/auth_check.php';
require_once '../includes/functions.php';
require_once '../includes/csrf.php';
require_once '../includes/config.php';

requireTeacher();

$user_id = currentUserId();
$nama_lengkap = $_SESSION['nama_lengkap'] ?? 'Guru';

$error = '';
$success = '';

// Get teacher's subjects
$stmtSubjects = $pdo->prepare("
    SELECT
        gm.id as guru_mapel_id,
        mp.nama_mapel,
        k.nama_kelas
    FROM guru_mapel gm
    JOIN mata_pelajaran mp ON gm.mapel_id = mp.id
    JOIN kelas k ON gm.kelas_id = k.id
    WHERE gm.guru_id = ? AND gm.is_active = 1
    ORDER BY k.nama_kelas, mp.nama_mapel
");
$stmtSubjects->execute([$user_id]);
$subjects = $stmtSubjects->fetchAll();
$selectedAssignment = (int)filter_var($_POST['guru_mapel_id'] ?? $_GET['guru_mapel_id'] ?? null, FILTER_VALIDATE_INT);
if (!in_array($selectedAssignment, array_map('intval', array_column($subjects, 'guru_mapel_id')), true)) {
    $selectedAssignment = 0;
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    checkCsrfToken();

    $gm_id = $_POST['guru_mapel_id'];
    $judul = trim($_POST['judul_tugas']);
    $deskripsi = trim($_POST['deskripsi']);
    $instruksi = trim($_POST['instruksi']);
    $batas = $_POST['batas_pengumpulan'];
    $format_file = trim($_POST['format_file']);
    $max_size = intval($_POST['upload_limit_mb'] ?? 10) * 1024 * 1024;
    $bobot = intval($_POST['bobot_nilai']);

    // Verify ownership
    $stmtVerify = $pdo->prepare("SELECT id FROM guru_mapel WHERE id = ? AND guru_id = ?");
    $stmtVerify->execute([$gm_id, $user_id]);
    if (!$stmtVerify->fetch()) {
        $error = 'Akses ditolak!';
    } elseif ($judul === '' || !strtotime($batas) || $max_size < 1 || $max_size > UPLOAD_MAX_SIZE || $bobot < 1 || $bobot > 100 || !preg_match('/^(pdf|docx|doc|zip|pptx|ppt|jpg|jpeg|png)(\s*,\s*(pdf|docx|doc|zip|pptx|ppt|jpg|jpeg|png))*$/i', $format_file)) {
        $error = 'Periksa judul, tenggat, format, batas ukuran (maksimal 10 MB), dan bobot nilai (1â€“100).';
    } else {

        $file_path = null;
        $file_name = null;

        // Handle file upload (optional - soal template)
        if (isset($_FILES['file_soal']) && $_FILES['file_soal']['error'] !== UPLOAD_ERR_NO_FILE) {
            $file = $_FILES['file_soal'];
            $allowedExts = ['pdf', 'docx', 'doc'];
            $errors = validateFileUpload($file, $allowedExts, 10 * 1024 * 1024);

            if (count($errors) > 0) {
                $error = implode('<br>', $errors);
            } else {
                $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
                $newFileName = 'soal_' . time() . '_' . uniqid() . '.' . $ext;
                $uploadPath = UPLOAD_TUGAS_DIR . $newFileName;

                if (!is_dir(UPLOAD_TUGAS_DIR)) {
                    mkdir(UPLOAD_TUGAS_DIR, 0755, true);
                }

                if (move_uploaded_file($file['tmp_name'], $uploadPath)) {
                    $file_path = 'uploads/tugas/' . $newFileName;
                    $file_name = $file['name'];
                }
            }
            if (empty($error) && !$file_path) $error = 'Upload gagal. Silakan coba lagi.';
        }

        if (empty($error)) {
            $stmtInsert = $pdo->prepare("
                INSERT INTO tugas
                (guru_mapel_id, judul_tugas, deskripsi, instruksi, file_path, file_name, batas_pengumpulan, format_file, max_file_size, bobot_nilai, is_published)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 1)
            ");

            if ($stmtInsert->execute([$gm_id, $judul, $deskripsi, $instruksi, $file_path, $file_name, $batas, $format_file, $max_size, $bobot])) {
                $success = 'Tugas berhasil dibuat dan dipublikasikan!';

                // Clear form by redirecting
                header("Location: guru_buat_tugas.php?success=1");
                exit();
            } else {
                $error = 'Gagal menyimpan tugas.';
            }
        }
    }
}

if (isset($_GET['success'])) {
    $success = 'Tugas berhasil dibuat dan dipublikasikan!';
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Buat Tugas Baru - EduLMS Portal Guru</title>
    <link rel="stylesheet" href="../assets/css/tailwind.css">
    <link rel="stylesheet" href="../assets/fonts/jakarta.css">
    <style>
        body { font-family: 'Plus Jakarta Sans', sans-serif; }
    </style>
<?php require __DIR__ . '/../includes/portal_assets.php'; ?>
</head>
<body class="page-guru_buat_tugas bg-slate-50 text-slate-800 min-h-screen">

    <!-- Navbar -->
    <?php require __DIR__ . '/../includes/portal_nav.php'; ?>

    <main class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-8 space-y-6">

        <!-- Header -->
        <div class="flex items-center space-x-3">
            <a href="guru.php" class="text-xs font-semibold text-slate-600 hover:text-teal-700"><?= uiIcon('chevron-left',18) ?> Kembali ke Dashboard</a>
            <span class="text-slate-300">|</span>
            <h1 class="text-xl font-bold text-slate-900">Buat Tugas Baru</h1>
        </div>

        <?php if ($success): ?>
        <div class="bg-emerald-50 border border-emerald-200 rounded-xl p-4 text-sm text-emerald-800">
            <strong>âœ“</strong> <?php echo $success; ?>
            <a href="guru_kelas.php" class="underline font-semibold ml-2">Lihat Kelas</a>
        </div>
        <?php endif; ?>

        <?php if ($error): ?>
        <div class="bg-rose-50 border border-rose-200 rounded-xl p-4 text-sm text-rose-800">
            <strong>âœ—</strong> <?php echo $error; ?>
        </div>
        <?php endif; ?>

        <!-- Form -->
        <div class="bg-white rounded-2xl p-6 border border-slate-200 shadow-sm">
            <h2 class="text-lg font-bold text-slate-900 mb-6">ðŸ“ Formulir Tugas Baru</h2>

            <form action="" method="POST" enctype="multipart/form-data" class="space-y-5">
                <?php echo csrfField(); ?>

                <!-- Mata Pelajaran & Kelas -->
                <div>
                    <label class="block text-xs font-bold text-slate-700 mb-2">Mata Pelajaran & Kelas *</label>
                    <select name="guru_mapel_id" required class="w-full px-4 py-3 text-sm border border-slate-300 rounded-xl focus:outline-none focus:border-teal-600">
                        <option value="">-- Pilih Kelas & Mata Pelajaran --</option>
                        <?php foreach ($subjects as $s): ?>
                        <option value="<?php echo $s['guru_mapel_id']; ?>" <?= (int)$s['guru_mapel_id']===$selectedAssignment?'selected':'' ?>>
                            <?php echo e($s['nama_kelas']); ?> - <?php echo e($s['nama_mapel']); ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                    <p class="text-[10px] text-slate-400 mt-1">Tugas akan diberikan ke semua siswa di kelas tersebut</p>
                </div>

                <!-- Judul Tugas -->
                <div>
                    <label class="block text-xs font-bold text-slate-700 mb-2">Judul Tugas *</label>
                    <input type="text" name="judul_tugas" required
                        placeholder="Contoh: Latihan Soal Matriks & Vektor"
                        class="w-full px-4 py-3 text-sm border border-slate-300 rounded-xl focus:outline-none focus:border-teal-600">
                </div>

                <!-- Deskripsi -->
                <div>
                    <label class="block text-xs font-bold text-slate-700 mb-2">Deskripsi Singkat</label>
                    <textarea name="deskripsi" rows="2"
                        placeholder="Penjelasan singkat tentang tugas ini..."
                        class="w-full px-4 py-3 text-sm border border-slate-300 rounded-xl focus:outline-none focus:border-teal-600 resize-none"></textarea>
                </div>

                <!-- Instruksi Lengkap -->
                <div>
                    <label class="block text-xs font-bold text-slate-700 mb-2">Instruksi Pengerjaan</label>
                    <textarea name="instruksi" rows="4"
                        placeholder="Instruksi detail untuk siswa:&#10;1. Kerjakan semua soal dengan lengkap&#10;2. Tuliskan langkah penyelesaian&#10;3. Scan atau foto hasil pekerjaan dengan jelas"
                        class="w-full px-4 py-3 text-sm border border-slate-300 rounded-xl focus:outline-none focus:border-teal-600 resize-none"></textarea>
                </div>

                <!-- Upload File Soal (Optional) -->
                <div>
                    <label class="block text-xs font-bold text-slate-700 mb-2">Upload File Soal (Opsional)</label>
                    <input type="file" name="file_soal" accept=".pdf,.doc,.docx"
                        class="block w-full text-xs text-slate-500
                            file:mr-4 file:py-2 file:px-4
                            file:rounded-xl file:border-0
                            file:text-xs file:font-semibold
                            file:bg-teal-50 file:text-teal-700
                            hover:file:bg-teal-100">
                    <p class="text-[10px] text-slate-400 mt-1">Upload file soal jika ada (PDF, DOC, DOCX - Max 10MB)</p>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <!-- Batas Pengumpulan -->
                    <div>
                        <label class="block text-xs font-bold text-slate-700 mb-2">Batas Pengumpulan *</label>
                        <input type="datetime-local" name="batas_pengumpulan" required
                            min="<?php echo date('Y-m-d\TH:i'); ?>"
                            class="w-full px-4 py-3 text-sm border border-slate-300 rounded-xl focus:outline-none focus:border-teal-600">
                    </div>

                    <!-- Bobot Nilai -->
                    <div>
                        <label class="block text-xs font-bold text-slate-700 mb-2">Bobot Nilai *</label>
                        <input type="number" name="bobot_nilai" required value="100" min="1" max="100"
                            class="w-full px-4 py-3 text-sm border border-slate-300 rounded-xl focus:outline-none focus:border-teal-600">
                        <p class="text-[10px] text-slate-400 mt-1">Skala nilai 0-100</p>
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <!-- Format File yang Diterima -->
                    <div>
                        <label class="block text-xs font-bold text-slate-700 mb-2">Format File yang Diterima *</label>
                        <input type="text" name="format_file" required value="PDF, DOCX"
                            placeholder="Contoh: PDF, DOCX, ZIP"
                            class="w-full px-4 py-3 text-sm border border-slate-300 rounded-xl focus:outline-none focus:border-teal-600">
                        <p class="text-[10px] text-slate-400 mt-1">Pisahkan dengan koma</p>
                    </div>

                    <!-- Ukuran File Maksimal -->
                    <div>
                        <label class="block text-xs font-bold text-slate-700 mb-2">Ukuran File Maks (MB) *</label>
                        <input type="number" name="upload_limit_mb" required value="10" min="1" max="50"
                            class="w-full px-4 py-3 text-sm border border-slate-300 rounded-xl focus:outline-none focus:border-teal-600">
                    </div>
                </div>

                <!-- Submit Button -->
                <div class="flex items-center justify-between pt-6 border-t border-slate-100">
                    <a href="guru.php" class="px-4 py-2 bg-slate-100 hover:bg-slate-200 text-slate-700 text-xs font-semibold rounded-xl transition">
                        Batal
                    </a>
                    <button type="submit" class="px-6 py-3 bg-teal-600 hover:bg-teal-700 text-white text-sm font-bold rounded-xl transition shadow-sm flex items-center space-x-2">
                        <span>ðŸ“¤</span>
                        <span>Buat & Publikasikan Tugas</span>
                    </button>
                </div>
            </form>
        </div>

        <!-- Info Box -->
        <div class="bg-blue-50 border border-blue-200 rounded-xl p-4 text-xs text-blue-800">
            <strong>ðŸ’¡ Tips:</strong>
            <ul class="list-disc list-inside mt-2 space-y-1">
                <li>Pastikan instruksi jelas dan mudah dipahami siswa</li>
                <li>Set deadline yang realistis sesuai tingkat kesulitan tugas</li>
                <li>Upload file soal jika tugas berisi soal-soal yang kompleks</li>
                <li>Tugas akan langsung terlihat oleh siswa setelah dipublikasikan</li>
            </ul>
        </div>

    </main>

<?php require __DIR__ . '/../includes/portal_footer.php'; ?>
</body>
</html>
