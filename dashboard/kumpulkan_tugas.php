<?php
/**
 * EduLMS - Kumpulkan Tugas
 * Student assignment submission form with file upload
 */

require_once '../includes/db.php';
require_once '../includes/auth_check.php';
require_once '../includes/functions.php';
require_once '../includes/csrf.php';
require_once '../includes/config.php';

requireStudent();

$user_id = currentUserId();
$nama_lengkap = $_SESSION['nama_lengkap'] ?? 'Siswa';

$tugas_id = $_GET['id'] ?? null;
$error = '';
$success = '';

if (!$tugas_id) {
    header("Location: tugas.php");
    exit();
}

// Get assignment details
$stmtTugas = $pdo->prepare("
    SELECT 
        t.id,
        t.judul_tugas,
        t.deskripsi,
        t.instruksi,
        t.batas_pengumpulan,
        t.format_file,
        t.file_path,
        t.max_file_size,
        t.bobot_nilai,
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

// Check if student is enrolled in this class
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

// Check if already submitted
$stmtPengumpulan = $pdo->prepare("
    SELECT * FROM pengumpulan_tugas 
    WHERE tugas_id = ? AND siswa_id = ?
");
$stmtPengumpulan->execute([$tugas_id, $user_id]);
$sudahDikumpulkan = $stmtPengumpulan->fetch();

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !$sudahDikumpulkan) {
    
    checkCsrfToken();
    
    $catatan = trim($_POST['catatan'] ?? '');
    
    // Validate file upload
    if (!isset($_FILES['file_tugas']) || $_FILES['file_tugas']['error'] === UPLOAD_ERR_NO_FILE) {
        $error = 'File tugas harus diupload!';
    } else {
        $file = $_FILES['file_tugas'];
        
        // Parse allowed extensions
        $allowedExts = array_map('trim', explode(',', strtolower($tugas['format_file'])));
        
        // Validate file
        $errors = validateFileUpload($file, $allowedExts, $tugas['max_file_size']);
        
        if (count($errors) > 0) {
            $error = implode('<br>', $errors);
        } else {
            // Check deadline
            $status = 'tepat_waktu';
            if (strtotime($tugas['batas_pengumpulan']) < time()) {
                $status = 'terlambat';
            }
            
            // Generate unique filename
            $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
            $newFileName = 'tugas_' . $tugas_id . '_siswa_' . $user_id . '_' . time() . '.' . $ext;
            $uploadPath = UPLOAD_PENGUMPULAN_DIR . $newFileName;
            
            // Create directory if not exists
            if (!is_dir(UPLOAD_PENGUMPULAN_DIR)) {
                mkdir(UPLOAD_PENGUMPULAN_DIR, 0755, true);
            }
            
            // Move uploaded file
            if (move_uploaded_file($file['tmp_name'], $uploadPath)) {
                
                // Insert to database
                $stmtInsert = $pdo->prepare("
                    INSERT INTO pengumpulan_tugas 
                    (tugas_id, siswa_id, file_path, file_name, file_size, catatan, status, tanggal_kumpul)
                    VALUES (?, ?, ?, ?, ?, ?, ?, NOW())
                ");
                
                $relativePath = 'uploads/pengumpulan/' . $newFileName;
                
                if ($stmtInsert->execute([
                    $tugas_id,
                    $user_id,
                    $relativePath,
                    $file['name'],
                    $file['size'],
                    $catatan,
                    $status
                ])) {
                    $success = 'Tugas berhasil dikumpulkan!';
                    
                    // Refresh to show submitted state
                    $stmtPengumpulan->execute([$tugas_id, $user_id]);
                    $sudahDikumpulkan = $stmtPengumpulan->fetch();
                    
                } else {
                    $error = 'Gagal menyimpan data pengumpulan. Silakan coba lagi.';
                    unlink($uploadPath); // Delete uploaded file
                }
                
            } else {
                $error = 'Gagal mengupload file. Silakan coba lagi.';
            }
        }
    }
}

$isDeadlinePassed = strtotime($tugas['batas_pengumpulan']) < time();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kumpulkan Tugas - <?php echo e($tugas['judul_tugas']); ?> - EduLMS</title>
    <link rel="stylesheet" href="../assets/css/tailwind.css">
    <link rel="stylesheet" href="../assets/fonts/jakarta.css">
    <style>
        body { font-family: 'Plus Jakarta Sans', sans-serif; }
    </style>
<?php require __DIR__ . '/../includes/portal_assets.php'; ?>
</head>
<body class="page-kumpulkan_tugas bg-slate-50 text-slate-800 min-h-screen">

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
                    <div class="text-sm font-bold <?php echo $isDeadlinePassed ? 'text-rose-600' : 'text-slate-900'; ?>">
                        <?php echo formatTanggalIndo($tugas['batas_pengumpulan'], 'd M Y H:i'); ?>
                    </div>
                    <?php if ($isDeadlinePassed): ?>
                    <span class="text-[10px] text-rose-600 font-semibold"><?= uiIcon('alarm',18) ?> Sudah Terlewat</span>
                    <?php endif; ?>
                </div>
            </div>
            
            <?php if ($tugas['deskripsi']): ?>
            <div class="border-t border-slate-100 pt-4 mt-4">
                <h3 class="text-xs font-bold text-slate-900 mb-2">Deskripsi Tugas:</h3>
                <p class="text-xs text-slate-600 leading-relaxed"><?php echo nl2br(e(uiDisplayText($tugas['deskripsi']))); ?></p>
            </div>
            <?php endif; ?>
            
            <?php if ($tugas['instruksi']): ?>
            <div class="border-t border-slate-100 pt-4 mt-4">
                <h3 class="text-xs font-bold text-slate-900 mb-2">Instruksi Pengerjaan:</h3>
                <p class="text-xs text-slate-600 leading-relaxed"><?php echo nl2br(e($tugas['instruksi'])); ?></p>
            </div>
            <?php endif; ?>
            
            <?php if (!empty($tugas['file_path'])): ?>
            <div class="border-t border-slate-100 pt-4 mt-4">
                <a href="download.php?type=tugas&id=<?php echo $tugas['id']; ?>" target="_blank" class="inline-flex items-center space-x-2 px-4 py-2 bg-slate-100 hover:bg-slate-200 text-slate-700 text-xs font-bold rounded-xl transition border border-slate-200 shadow-sm">
                    <span><?= uiIcon('file',18) ?></span>
                    <span>Lihat Soal dari Guru</span>
                </a>
            </div>
            <?php endif; ?>

            <div class="border-t border-slate-100 pt-4 mt-4 grid grid-cols-2 gap-4 text-xs">
                <div>
                    <span class="text-slate-500">Format File:</span>
                    <span class="font-semibold text-slate-900 ml-2"><?php echo e($tugas['format_file']); ?></span>
                </div>
                <div>
                    <span class="text-slate-500">Ukuran Maks:</span>
                    <span class="font-semibold text-slate-900 ml-2"><?php echo formatFileSize($tugas['max_file_size']); ?></span>
                </div>
            </div>
        </div>

        <?php if ($success): ?>
        <div class="bg-emerald-50 border border-emerald-200 rounded-2xl p-4 text-sm text-emerald-800">
            <strong><?= uiIcon('check',18) ?> Berhasil!</strong> <?php echo $success; ?>
        </div>
        <?php endif; ?>

        <?php if ($error): ?>
        <div class="bg-rose-50 border border-rose-200 rounded-2xl p-4 text-sm text-rose-800">
            <strong><?= uiIcon('close',18) ?> Error!</strong> <?php echo $error; ?>
        </div>
        <?php endif; ?>

        <!-- Submission Form or Status -->
        <?php if ($sudahDikumpulkan): ?>
        
        <!-- Already Submitted -->
        <div class="bg-teal-50 border border-teal-200 rounded-2xl p-6">
            <div class="flex items-center space-x-3 mb-4">
                <div class="w-12 h-12 rounded-xl bg-teal-600 text-white flex items-center justify-center text-2xl"><?= uiIcon('check',18) ?></div>
                <div>
                    <h3 class="text-lg font-bold text-slate-900">Tugas Sudah Dikumpulkan</h3>
                    <p class="text-xs text-slate-600">Diserahkan pada <?php echo formatTanggalIndo($sudahDikumpulkan['tanggal_kumpul'], 'd M Y H:i'); ?></p>
                </div>
            </div>
            
            <div class="bg-white rounded-xl p-4 space-y-3">
                <div class="flex justify-between items-center text-xs">
                    <span class="text-slate-500">File yang Dikumpulkan:</span>
                    <span class="font-semibold text-slate-900"><?php echo e($sudahDikumpulkan['file_name']); ?></span>
                </div>
                <div class="flex justify-between items-center text-xs">
                    <span class="text-slate-500">Ukuran File:</span>
                    <span class="font-semibold text-slate-900"><?php echo formatFileSize($sudahDikumpulkan['file_size']); ?></span>
                </div>
                <div class="flex justify-between items-center text-xs">
                    <span class="text-slate-500">Status:</span>
                    <span class="font-semibold <?php echo $sudahDikumpulkan['status'] === 'tepat_waktu' ? 'text-emerald-700' : 'text-rose-700'; ?>">
                        <?php echo $sudahDikumpulkan['status'] === 'tepat_waktu' ? (uiIcon('check',18).' Tepat Waktu') : (uiIcon('alarm',18).' Terlambat'); ?>
                    </span>
                </div>
                <?php if ($sudahDikumpulkan['catatan']): ?>
                <div class="pt-3 border-t border-slate-100">
                    <span class="text-xs text-slate-500 block mb-1">Catatan Anda:</span>
                    <p class="text-xs text-slate-700"><?php echo nl2br(e(uiDisplayText($sudahDikumpulkan['catatan']))); ?></p>
                </div>
                <?php endif; ?>
            </div>
            
            <?php if ($sudahDikumpulkan['nilai'] !== null): ?>
            <div class="bg-white rounded-xl p-4 mt-4 border-2 border-teal-200">
                <div class="text-center">
                    <div class="text-xs uppercase tracking-wider text-slate-500 font-bold mb-2">Nilai Anda</div>
                    <div class="text-4xl font-black text-[#006d5b] mb-2"><?php echo number_format($sudahDikumpulkan['nilai'], 0); ?></div>
                    <div class="text-xs text-slate-500">dari <?php echo $tugas['bobot_nilai']; ?> poin</div>
                    
                    <?php if ($sudahDikumpulkan['feedback']): ?>
                    <div class="mt-4 pt-4 border-t border-slate-100 text-left">
                        <div class="text-xs font-bold text-slate-700 mb-2">Feedback Guru:</div>
                        <p class="text-xs text-slate-600 leading-relaxed"><?php echo nl2br(e($sudahDikumpulkan['feedback'])); ?></p>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
            <?php else: ?>
            <div class="bg-blue-50 border border-blue-200 rounded-xl p-4 mt-4 text-center text-xs text-blue-800">
                <?= uiIcon('clock',18) ?> Menunggu penilaian dari guru
            </div>
            <?php endif; ?>
        </div>
        
        <?php else: ?>
        
        <!-- Submission Form -->
        <div class="bg-white rounded-2xl p-6 border border-slate-200 shadow-sm">
            <h2 class="text-lg font-bold text-slate-900 mb-4"><?= uiIcon('upload',18) ?> Kumpulkan Tugas Anda</h2>
            
            <form action="" method="POST" enctype="multipart/form-data" class="space-y-4">
                <?php echo csrfField(); ?>
                
                <!-- File Upload -->
                <div>
                    <label class="block text-xs font-bold text-slate-700 mb-2">Upload File Tugas *</label>
                    <div class="border-2 border-dashed border-slate-300 rounded-xl p-6 text-center hover:border-teal-500 transition">
                        <input type="file" name="file_tugas" id="file_tugas" required 
                            accept=".<?php echo str_replace([',', ' '], [',.', ''], $tugas['format_file']); ?>"
                            class="block w-full text-xs text-slate-500
                                file:mr-4 file:py-2 file:px-4
                                file:rounded-xl file:border-0
                                file:text-xs file:font-semibold
                                file:bg-teal-50 file:text-teal-700
                                hover:file:bg-teal-100
                                cursor-pointer">
                        <p class="text-[10px] text-slate-400 mt-2">
                            Format: <?php echo e($tugas['format_file']); ?> | Maksimal: <?php echo formatFileSize($tugas['max_file_size']); ?>
                        </p>
                    </div>
                </div>
                
                <!-- Catatan -->
                <div>
                    <label class="block text-xs font-bold text-slate-700 mb-2">Catatan (Opsional)</label>
                    <textarea name="catatan" rows="3" 
                        placeholder="Tambahkan catatan atau keterangan tambahan untuk guru..."
                        class="w-full px-4 py-3 text-xs border border-slate-300 rounded-xl focus:outline-none focus:border-teal-600 resize-none"></textarea>
                </div>
                
                <!-- Submit Button -->
                <div class="flex items-center justify-between pt-4 border-t border-slate-100">
                    <a href="tugas.php" class="px-4 py-2 bg-slate-100 hover:bg-slate-200 text-slate-700 text-xs font-semibold rounded-xl transition">
                        Batal
                    </a>
                    <button type="submit" class="px-6 py-2.5 bg-[#006d5b] hover:bg-[#005547] text-white text-xs font-bold rounded-xl transition shadow-sm flex items-center space-x-2">
                        <span><?= uiIcon('upload',18) ?></span>
                        <span>Kumpulkan Tugas</span>
                    </button>
                </div>
            </form>
        </div>
        
        <?php endif; ?>

    </main>

<?php require __DIR__ . '/../includes/portal_footer.php'; ?>
</body>
</html>