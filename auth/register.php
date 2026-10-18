<?php
/**
 * EduLMS - User Registration (SECURE VERSION)
 * Public registration only for students
 */

session_start();
require_once '../includes/db.php';
require_once '../includes/csrf.php';
require_once '../includes/ui.php';

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    
    // Validate CSRF token
    checkCsrfToken();
    
    $nama = trim($_POST['nama_lengkap']);
    $email = trim($_POST['email']);
    $password = $_POST['password'];
    
    // SECURITY: Registrasi publik HANYA untuk siswa
    // Admin dan guru harus dibuat oleh administrator
    $role = 'siswa';
    
    if (empty($nama) || empty($email) || empty($password)) {
        $error = 'Semua kolom wajib diisi!';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Format email tidak valid!';
    } elseif (strlen($password) < 6) {
        $error = 'Password minimal 6 karakter!';
    } else {
        // Cek apakah email sudah terdaftar
        $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
        $stmt->execute([$email]);
        if ($stmt->rowCount() > 0) {
            $error = 'Email sudah terdaftar, silakan gunakan email lain.';
        } else {
            // Hash password
            $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
            
            $insert = $pdo->prepare("INSERT INTO users (nama_lengkap, email, password, role) VALUES (?, ?, ?, ?)");
            if ($insert->execute([$nama, $email, $hashedPassword, $role])) {
                $success = 'Registrasi berhasil! Silakan <a href="login.php" class="underline font-bold">Masuk ke Sistem</a>.';
            } else {
                $error = 'Terjadi kesalahan, coba lagi.';
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Daftar Akun - EduLMS</title>
    <link rel="stylesheet" href="../assets/css/tailwind.css">
<?php require __DIR__ . '/../includes/portal_assets.php'; ?>
</head>
<body class="page-register bg-slate-100 min-h-screen flex items-center justify-center font-sans">
    <div class="bg-white p-8 rounded-2xl shadow-sm border border-slate-200 max-w-md w-full">
        <h2 class="text-xl font-bold text-slate-800 mb-1 text-center">Buat Akun EduLMS</h2>
        <p class="text-xs text-slate-500 mb-6 text-center">Daftarkan akun baru untuk mulai masuk ke sistem.</p>

        <?php if (!empty($error)): ?>
            <div class="mb-4 p-3 bg-red-50 text-red-600 text-xs rounded-xl border border-red-200"><?php echo $error; ?></div>
        <?php endif; ?>
        <?php if (!empty($success)): ?>
            <div class="mb-4 p-3 bg-emerald-50 text-emerald-700 text-xs rounded-xl border border-emerald-200"><?php echo $success; ?></div>
        <?php endif; ?>

        <form action="" method="POST" class="space-y-4">
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
                <label class="block text-xs font-semibold text-slate-600 mb-1">Kata Sandi (minimal 6 karakter)</label>
                <input type="password" name="password" required minlength="6" class="w-full px-3 py-2 text-xs border border-slate-300 rounded-xl focus:outline-none focus:border-teal-600">
            </div>
            <div class="bg-blue-50 border border-blue-200 rounded-xl p-3 text-xs text-blue-800">
                <strong>Catatan:</strong> Registrasi publik hanya untuk siswa. Akun guru dan admin dibuat oleh administrator sekolah.
            </div>
            <button type="submit" class="w-full py-2.5 bg-[#006d5b] hover:bg-[#005547] text-white text-xs font-bold rounded-xl transition">
                Daftar Sekarang
            </button>
        </form>
        <div class="mt-4 text-center">
            <a href="login.php" class="text-xs text-slate-600 hover:text-[#006d5b]">Sudah punya akun? Masuk di sini</a>
        </div>
    </div>
<dialog class="portal-dialog" id="portal-info" aria-labelledby="portal-info-title"><button type="button" data-dialog-close aria-label="Tutup"><?= uiIcon('close',18) ?></button><h2 id="portal-info-title">Bantuan</h2><p></p></dialog>
</body>
</html>
