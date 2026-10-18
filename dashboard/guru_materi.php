<?php
/**
 * EduLMS - Kelola Materi Pembelajaran
 * Teacher material management with upload functionality
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

// Get guru_mapel_id from GET or default to first
$guru_mapel_id = $_GET['guru_mapel_id'] ?? null;

// Get teacher's subjects for dropdown
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

// Set default if not specified
if (!$guru_mapel_id && count($subjects) > 0) {
    $guru_mapel_id = $subjects[0]['guru_mapel_id'];
}

// Handle delete (POST + CSRF — melindungi dari CSRF & predausing)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'delete_materi' && $guru_mapel_id) {
    checkCsrfToken();
    $delete_id = intval($_POST['materi_id']);
    
    // Verify ownership
    $stmtCheck = $pdo->prepare("
        SELECT m.*, gm.guru_id 
        FROM materi m
        JOIN guru_mapel gm ON m.guru_mapel_id = gm.id
        WHERE m.id = ? AND gm.guru_id = ?
    ");
    $stmtCheck->execute([$delete_id, $user_id]);
    $materiToDelete = $stmtCheck->fetch();
    
    if ($materiToDelete) {
        // Delete file if exists
        if ($materiToDelete['file_path'] && file_exists($materiToDelete['file_path'])) {
            unlink($materiToDelete['file_path']);
        }
        
        $stmtDelete = $pdo->prepare("DELETE FROM materi WHERE id = ?");
        if ($stmtDelete->execute([$delete_id])) {
            $success = 'Materi berhasil dihapus!';
        }
    }
}

// Handle add/upload
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'add') {
    
    checkCsrfToken();
    
    $gm_id = $_POST['guru_mapel_id'];
    $judul = trim($_POST['judul_materi']);
    $deskripsi = trim($_POST['deskripsi']);
    $video_url = trim($_POST['video_url'] ?? '');
    $urutan = intval($_POST['urutan_modul'] ?? 1);
    
    // Verify ownership
    $stmtVerify = $pdo->prepare("SELECT id FROM guru_mapel WHERE id = ? AND guru_id = ?");
    $stmtVerify->execute([$gm_id, $user_id]);
    if (!$stmtVerify->fetch()) {
        $error = 'Akses ditolak!';
    } elseif ($judul === '' || $urutan < 1 || ($video_url !== '' && (!filter_var($video_url, FILTER_VALIDATE_URL) || !in_array(strtolower(parse_url($video_url, PHP_URL_SCHEME) ?? ''), ['https','http'])))) {
        $error = 'Judul, urutan modul, atau URL video tidak valid.';
    } else {
        
        $file_path = null;
        $file_name = null;
        $file_size = null;
        
        // Handle file upload
        if (isset($_FILES['file_materi']) && $_FILES['file_materi']['error'] !== UPLOAD_ERR_NO_FILE) {
            $file = $_FILES['file_materi'];
            $allowedExts = ['pdf', 'pptx', 'ppt', 'docx', 'doc'];
            $errors = validateFileUpload($file, $allowedExts, 20 * 1024 * 1024); // 20MB
            
            if (count($errors) > 0) {
                $error = implode('<br>', $errors);
            } else {
                $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
                $newFileName = 'materi_' . time() . '_' . uniqid() . '.' . $ext;
                $uploadPath = UPLOAD_MATERI_DIR . $newFileName;
                
                if (!is_dir(UPLOAD_MATERI_DIR)) {
                    mkdir(UPLOAD_MATERI_DIR, 0755, true);
                }
                
                if (move_uploaded_file($file['tmp_name'], $uploadPath)) {
                    $file_path = 'uploads/materi/' . $newFileName;
                    $file_name = $file['name'];
                    $file_size = $file['size'];
                }
            }
            if (empty($error) && !$file_path) $error = 'Upload gagal. Silakan coba lagi.';
        }
        
        if (empty($error)) {
            $stmtInsert = $pdo->prepare("
                INSERT INTO materi 
                (guru_mapel_id, judul_materi, deskripsi, file_path, file_name, file_size, video_url, urutan_modul, is_published, published_at)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, 1, NOW())
            ");
            
            if ($stmtInsert->execute([$gm_id, $judul, $deskripsi, $file_path, $file_name, $file_size, $video_url, $urutan])) {
                $success = 'Materi berhasil ditambahkan!';
                $guru_mapel_id = $gm_id; // Update current selection
            } else {
                $error = 'Gagal menyimpan materi.';
            }
        }
    }
}

// Get materials for selected subject
$materials = [];
if ($guru_mapel_id) {
    $stmtMaterials = $pdo->prepare("
        SELECT 
            m.*,
            mp.nama_mapel,
            k.nama_kelas
        FROM materi m
        JOIN guru_mapel gm ON m.guru_mapel_id = gm.id
        JOIN mata_pelajaran mp ON gm.mapel_id = mp.id
        JOIN kelas k ON gm.kelas_id = k.id
        WHERE m.guru_mapel_id = ? AND gm.guru_id = ?
        ORDER BY m.urutan_modul, m.created_at DESC
    ");
    $stmtMaterials->execute([$guru_mapel_id, $user_id]);
    $materials = $stmtMaterials->fetchAll();
}

$materialPager=uiPaginate($materials,5);$materials=$materialPager['items'];
$currentSubject = null;
foreach ($subjects as $s) {
    if ($s['guru_mapel_id'] == $guru_mapel_id) {
        $currentSubject = $s;
        break;
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kelola Materi - EduLMS Portal Guru</title>
    <link rel="stylesheet" href="../assets/css/tailwind.css">
    <link rel="stylesheet" href="../assets/fonts/jakarta.css">
    <style>
        body { font-family: 'Plus Jakarta Sans', sans-serif; }
    </style>
<?php require __DIR__ . '/../includes/portal_assets.php'; ?>
</head>
<body class="page-guru_materi bg-slate-50 text-slate-800 min-h-screen">

    <!-- Navbar -->
    <?php require __DIR__ . '/../includes/portal_nav.php'; ?>

    <main class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8 space-y-6">

        <!-- Header -->
        <div class="flex items-center space-x-3">
            <a href="guru_kelas.php" class="text-xs font-semibold text-slate-600 hover:text-teal-700"><?= uiIcon('chevron-left',18) ?> Kembali</a>
            <span class="text-slate-300">|</span>
            <h1 class="text-xl font-bold text-slate-900">Kelola Materi Pembelajaran</h1>
        </div>

        <?php if ($success): ?>
        <div class="bg-emerald-50 border border-emerald-200 rounded-xl p-4 text-sm text-emerald-800">
            <strong><i class="fa-solid fa-circle-check"></i></strong> <?php echo $success; ?>
        </div>
        <?php endif; ?>

        <?php if ($error): ?>
        <div class="bg-rose-50 border border-rose-200 rounded-xl p-4 text-sm text-rose-800">
            <strong><?= uiIcon('close',18) ?></strong> <?php echo $error; ?>
        </div>
        <?php endif; ?>

        <!-- Subject Selector & Add Button -->
        <div class="bg-white rounded-2xl p-6 border border-slate-200 shadow-sm flex flex-col md:flex-row justify-between items-start md:items-center gap-4">
            <div class="flex-1">
                <label class="block text-xs font-bold text-slate-700 mb-2">Pilih Mata Pelajaran & Kelas:</label>
                <select onchange="window.location.href='guru_materi.php?guru_mapel_id=' + this.value" class="w-full md:w-96 px-4 py-2 text-sm border border-slate-300 rounded-xl focus:outline-none focus:border-teal-600">
                    <?php foreach ($subjects as $s): ?>
                    <option value="<?php echo $s['guru_mapel_id']; ?>" <?php echo $s['guru_mapel_id'] == $guru_mapel_id ? 'selected' : ''; ?>>
                        <?php echo e($s['nama_kelas']); ?> - <?php echo e($s['nama_mapel']); ?>
                    </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <button onclick="document.getElementById('modalAdd').classList.remove('hidden')" class="px-4 py-2.5 bg-teal-600 hover:bg-teal-700 text-white text-xs font-bold rounded-xl transition shadow-sm flex items-center space-x-2">
                <span><i class="fa-solid fa-plus"></i></span>
                <span>Tambah Materi Baru</span>
            </button>
        </div>

        <!-- Materials List -->
        <?php if (count($materials) > 0): ?>
        <div class="space-y-4">
            <?php foreach ($materials as $m): ?>
            <div class="bg-white p-5 rounded-2xl border border-slate-200 shadow-sm hover:shadow-md transition">
                <div class="flex justify-between items-start gap-4">
                    <div class="flex-1">
                        <div class="flex items-center space-x-2 mb-2">
                            <span class="text-xs font-bold bg-teal-50 text-teal-700 px-2.5 py-1 rounded border border-teal-200">
                                Modul <?php echo $m['urutan_modul']; ?>
                            </span>
                            <?php if ($m['is_published']): ?>
                            <span class="text-xs font-semibold text-emerald-700 bg-emerald-50 px-2 py-0.5 rounded border border-emerald-200">
                                <i class="fa-solid fa-circle-check"></i> Published
                            </span>
                            <?php endif; ?>
                        </div>
                        <h3 class="text-base font-bold text-slate-900 mb-2"><?php echo e($m['judul_materi']); ?></h3>
                        <?php if ($m['deskripsi']): ?>
                        <p class="text-xs text-slate-600 mb-3 leading-relaxed"><?php echo nl2br(e(uiDisplayText($m['deskripsi']))); ?></p>
                        <?php endif; ?>
                        
                        <div class="flex flex-wrap items-center gap-3 text-xs">
                            <?php if ($m['file_path']): ?>
                            <a href="download.php?type=materi&id=<?php echo $m['id']; ?>" target="_blank" class="flex items-center space-x-2 bg-slate-50 hover:bg-teal-50 px-3 py-1.5 rounded-lg border border-slate-200 transition">
                                <span><?= uiIcon('paperclip',18) ?></span>
                                <span class="font-semibold text-slate-900"><?php echo e($m['file_name']); ?></span>
                                <span class="text-slate-500">(<?php echo formatFileSize($m['file_size']); ?>)</span>
                                <span class="text-teal-700 font-bold">⬇</span>
                            </a>
                            <?php endif; ?>
                            
                            <?php if ($m['video_url']): ?>
                            <div class="flex items-center space-x-2 bg-blue-50 px-3 py-1.5 rounded-lg border border-blue-200">
                                <span><?= uiIcon('play',18) ?></span>
                                <a href="<?php echo e($m['video_url']); ?>" target="_blank" class="font-semibold text-blue-700 hover:underline">Link Video</a>
                            </div>
                            <?php endif; ?>
                            
                            <div class="text-slate-400">
                                <i class="fa-solid fa-calendar-days"></i> <?php echo formatTanggalIndo($m['created_at']); ?>
                            </div>
                        </div>
                    </div>
                    
                    <div class="flex items-center space-x-2">
                        <form method="POST" action="guru_materi.php?guru_mapel_id=<?php echo $guru_mapel_id; ?>"
                              onsubmit="return confirm('Yakin ingin menghapus materi ini?')">
                            <input type="hidden" name="action" value="delete_materi">
                            <input type="hidden" name="materi_id" value="<?php echo $m['id']; ?>">
                            <?php echo csrfField(); ?>
                            <button type="submit"
                                    class="px-3 py-2 bg-rose-50 hover:bg-rose-100 text-rose-700 text-xs font-semibold rounded-lg transition border border-rose-200">
                                <?= uiIcon('trash',18) ?> Hapus
                            </button>
                        </form>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        <?php else: ?>
        <div class="bg-white rounded-2xl p-12 border border-slate-200 shadow-sm text-center">
            <div class="text-6xl mb-4"><i class="fa-solid fa-book-open text-slate-300"></i></div>
            <h3 class="text-lg font-bold text-slate-900 mb-2">Belum Ada Materi</h3>
            <p class="text-sm text-slate-500 mb-6">Klik tombol "Tambah Materi Baru" untuk mulai menambahkan materi pembelajaran.</p>
        </div>
        <?php endif; ?>

        <?php uiPagination($materialPager['page'],$materialPager['pages'],$materialPager['total'],'materi'); ?>
    </main>

    <!-- Modal Add Material -->
    <div id="modalAdd" class="hidden fixed inset-0 bg-black/50 flex items-center justify-center z-50 p-4">
        <div class="bg-white rounded-2xl max-w-2xl w-full max-h-[90vh] overflow-y-auto">
            <div class="p-6 border-b border-slate-200 flex justify-between items-center sticky top-0 bg-white">
                <h2 class="text-lg font-bold text-slate-900">Tambah Materi Pembelajaran</h2>
                <button onclick="document.getElementById('modalAdd').classList.add('hidden')" class="text-slate-400 hover:text-slate-600">
                    <span class="text-2xl">×</span>
                </button>
            </div>
            
            <form action="" method="POST" enctype="multipart/form-data" class="p-6 space-y-4">
                <?php echo csrfField(); ?>
                <input type="hidden" name="action" value="add">
                
                <div>
                    <label class="block text-xs font-bold text-slate-700 mb-2">Mata Pelajaran & Kelas *</label>
                    <select name="guru_mapel_id" required class="w-full px-4 py-2 text-sm border border-slate-300 rounded-xl focus:outline-none focus:border-teal-600">
                        <?php foreach ($subjects as $s): ?>
                        <option value="<?php echo $s['guru_mapel_id']; ?>" <?php echo $s['guru_mapel_id'] == $guru_mapel_id ? 'selected' : ''; ?>>
                            <?php echo e($s['nama_kelas']); ?> - <?php echo e($s['nama_mapel']); ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div>
                    <label class="block text-xs font-bold text-slate-700 mb-2">Judul Materi *</label>
                    <input type="text" name="judul_materi" required placeholder="Contoh: Integral Kalkulus Dasar"
                        class="w-full px-4 py-2 text-sm border border-slate-300 rounded-xl focus:outline-none focus:border-teal-600">
                </div>
                
                <div>
                    <label class="block text-xs font-bold text-slate-700 mb-2">Deskripsi</label>
                    <textarea name="deskripsi" rows="3" placeholder="Penjelasan singkat tentang materi ini..."
                        class="w-full px-4 py-2 text-sm border border-slate-300 rounded-xl focus:outline-none focus:border-teal-600 resize-none"></textarea>
                </div>
                
                <div>
                    <label class="block text-xs font-bold text-slate-700 mb-2">Upload File (PDF, PPT, DOCX)</label>
                    <input type="file" name="file_materi" accept=".pdf,.ppt,.pptx,.doc,.docx"
                        class="block w-full text-xs text-slate-500
                            file:mr-4 file:py-2 file:px-4
                            file:rounded-xl file:border-0
                            file:text-xs file:font-semibold
                            file:bg-teal-50 file:text-teal-700
                            hover:file:bg-teal-100">
                    <p class="text-[10px] text-slate-400 mt-1">Maksimal 20MB</p>
                </div>
                
                <div>
                    <label class="block text-xs font-bold text-slate-700 mb-2">Link Video (YouTube, dll)</label>
                    <input type="url" name="video_url" placeholder="https://youtube.com/watch?v=..."
                        class="w-full px-4 py-2 text-sm border border-slate-300 rounded-xl focus:outline-none focus:border-teal-600">
                </div>
                
                <div>
                    <label class="block text-xs font-bold text-slate-700 mb-2">Urutan Modul</label>
                    <input type="number" name="urutan_modul" value="1" min="1"
                        class="w-full px-4 py-2 text-sm border border-slate-300 rounded-xl focus:outline-none focus:border-teal-600">
                </div>
                
                <div class="flex justify-end space-x-3 pt-4 border-t border-slate-100">
                    <button type="button" onclick="document.getElementById('modalAdd').classList.add('hidden')" 
                        class="px-4 py-2 bg-slate-100 hover:bg-slate-200 text-slate-700 text-xs font-semibold rounded-xl transition">
                        Batal
                    </button>
                    <button type="submit" class="px-6 py-2 bg-teal-600 hover:bg-teal-700 text-white text-xs font-bold rounded-xl transition shadow-sm">
                        Simpan Materi
                    </button>
                </div>
            </form>
        </div>
    </div>

<?php require __DIR__ . '/../includes/portal_footer.php'; ?>
</body>
</html>