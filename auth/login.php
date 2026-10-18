<?php
session_start();
require_once '../includes/csrf.php';
require_once '../includes/ui.php';
require_once '../includes/db.php';
$portalMaterials = (int)$pdo->query('SELECT COUNT(*) FROM materi WHERE is_published=1')->fetchColumn();
$portalStudents = (int)$pdo->query("SELECT COUNT(*) FROM users WHERE role='siswa' AND is_active=1")->fetchColumn();
$selectedRole=is_string($_GET['role']??null)&&in_array($_GET['role'],['siswa','guru','admin'],true)?$_GET['role']:'siswa';
$roleCopy=[
    'siswa'=>['NISN / Email Akun Belajar','10 digit nomor NISN','Masukkan NISN atau email sekolah'],
    'guru'=>['NIP / Email Kedinasan','Nomor Induk Pegawai','Masukkan NIP atau email sekolah'],
    'admin'=>['Email Administrator','Akun khusus Admin IT','Masukkan email admin']
];

// Handle error messages
$error = '';
if (isset($_GET['error'])) {
    switch ($_GET['error']) {
        case 'empty':
            $error = 'Email/NISN dan password harus diisi!';
            break;
        case 'invalid':
            $error = 'Identitas, kata sandi, atau peran tidak sesuai. Periksa pilihan Siswa, Guru, atau Admin.';
            break;
        case 'role':
            $error = 'Pilih peran Siswa, Guru, atau Admin sebelum masuk.';
            break;
        case 'inactive':
            $error = 'Akun Anda tidak aktif. Hubungi administrator.';
            break;
        default:
            $error = 'Terjadi kesalahan. Silakan coba lagi.';
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - EduLMS Portal Terpadu</title>
    <!-- Tailwind CSS CDN -->
    <link rel="stylesheet" href="../assets/css/tailwind.css">
    <!-- Google Fonts: Inter / Poppins -->
    <link rel="stylesheet" href="../assets/fonts/jakarta.css">
    <!-- FontAwesome untuk Icon -->
    <link rel="stylesheet" href="../assets/vendor/fontawesome/css/all.min.css">

<?php require __DIR__ . '/../includes/portal_assets.php'; ?>
</head>
<body class="page-login login-page bg-[#f0f4f8] min-h-screen flex flex-col items-center justify-center font-sans relative overflow-x-hidden text-slate-800">

    <!-- Efek Cahaya Background Abstrak -->
    <div class="absolute top-10 left-1/4 w-96 h-96 bg-cyan-200/40 rounded-full blur-3xl pointer-events-none"></div>
    <div class="absolute bottom-10 right-1/4 w-96 h-96 bg-teal-100/50 rounded-full blur-3xl pointer-events-none"></div>

    <!-- Container Kartu Utama -->
    <div class="w-full max-w-[960px] bg-white rounded-2xl shadow-[0_10px_40px_rgba(0,0,0,0.06)] grid grid-cols-1 md:grid-cols-12 overflow-hidden mx-4 my-8 z-10 border border-slate-100">

        <!-- KOLOM KIRI: Banner Informasi (5 Kolom) -->
        <div class="login-information md:col-span-5 bg-[#f8fafc] p-8 lg:p-10 flex flex-col justify-between border-r border-slate-100 relative">
            <div>
                <!-- Badge Tahun Ajaran -->
                <div class="inline-flex items-center gap-2 bg-[#e2f0ed] text-[#006d5b] text-xs font-semibold px-3 py-1 rounded-full mb-6">
                    <span class="w-1.5 h-1.5 rounded-full bg-[#006d5b]"></span>
                    Tahun Ajaran <?= htmlspecialchars(uiYear(),ENT_QUOTES,'UTF-8') ?>
                </div>

                <!-- Judul Sistem -->
                <p class="text-[11px] font-bold tracking-wider text-slate-400 uppercase mb-2">Sistem Manajemen Akademik</p>
                <h1 class="text-2xl lg:text-[28px] font-bold text-slate-900 leading-tight mb-4">
                    Ekosistem Belajar Cerdas & Terintegrasi
                </h1>
                <p class="text-xs text-slate-500 leading-relaxed mb-6">
                    Akses cepat jadwal mengajar, modul interaktif Kurikulum Merdeka, penilaian tugas, dan evaluasi hasil belajar berkala.
                </p>


            </div>

            <!-- Bagian Keamanan Terenkripsi -->
            <div class="login-security flex items-start gap-3 pt-4 border-t border-slate-200/60">
                <div class="w-8 h-8 rounded-full bg-blue-50 flex items-center justify-center text-[#006d5b] shrink-0 mt-0.5">
                    <?= uiIcon('shield',15) ?>
                </div>
                <div>
                    <h4 class="text-xs font-bold text-slate-800">Keamanan Terenkripsi</h4>
                    <p class="text-[11px] text-slate-500 leading-tight">Kata sandi terlindungi dan akses sesuai peran</p>
                </div>
            </div>
        </div>

        <!-- KOLOM KANAN: Form Login (7 Kolom) -->
        <div class="md:col-span-7 p-8 lg:p-10 flex flex-col justify-between bg-white">
            <div>
                <!-- Header Form & Versi -->
                <div class="flex justify-between items-center mb-6">
                    <div class="flex items-center gap-2">
                        <img src="../assets/images/reference/logo.png" alt="" width="24" height="20">
                        <span class="login-brand font-bold text-slate-800 text-base tracking-tight">EduLMS</span>
                        <span class="bg-[#e6f4f1] text-[#006d5b] text-[10px] font-semibold px-2 py-0.5 rounded">Portal Terpadu</span>
                    </div>
                    <span class="text-[11px] text-slate-400 font-medium">v4.2</span>
                </div>

                <!-- Sambutan -->
                <div class="login-greeting mb-6">
                    <h2 class="text-xl font-bold text-slate-900 mb-1">Selamat Datang Kembali</h2>
                    <p class="text-xs text-slate-500">Masuk ke akun Anda untuk mengelola kelas dan materi belajar.</p>
                </div>

                <!-- Error Message -->
                <?php if (!empty($error)): ?>
                <div class="mb-4 p-3 bg-rose-50 border border-rose-200 text-rose-700 text-xs rounded-xl flex items-start space-x-2">
                    <i class="fa-solid fa-triangle-exclamation text-sm"></i>
                    <span><?php echo htmlspecialchars($error); ?></span>
                </div>
                <?php endif; ?>

                <!-- PILIHAN PERAN (MASUK SEBAGAI) -->
                <div class="login-role mb-5">
                    <label class="block text-[11px] font-bold text-slate-400 uppercase tracking-wider mb-2">MASUK SEBAGAI</label>
                    <div class="grid grid-cols-3 gap-2 bg-[#f1f5f9] p-1 rounded-xl text-xs font-semibold text-slate-600">
                        <button type="button" class="role-btn py-2 px-3 rounded-lg <?= $selectedRole==='siswa'?'bg-white text-slate-900 shadow-xs':'hover:text-slate-900' ?> flex items-center justify-center gap-2 transition" aria-pressed="<?= $selectedRole==='siswa'?'true':'false' ?>" data-role="siswa">
                            <?= uiIcon('school',17) ?> Siswa
                        </button>
                        <button type="button" class="role-btn py-2 px-3 rounded-lg <?= $selectedRole==='guru'?'bg-white text-slate-900 shadow-xs':'hover:text-slate-900' ?> flex items-center justify-center gap-2 transition" aria-pressed="<?= $selectedRole==='guru'?'true':'false' ?>" data-role="guru">
                            <?= uiIcon('teacher',17) ?> Guru
                        </button>
                        <button type="button" class="role-btn py-2 px-3 rounded-lg <?= $selectedRole==='admin'?'bg-white text-slate-900 shadow-xs':'hover:text-slate-900' ?> flex items-center justify-center gap-2 transition" aria-pressed="<?= $selectedRole==='admin'?'true':'false' ?>" data-role="admin">
                            <?= uiIcon('shield',17) ?> Admin
                        </button>
                    </div>
                </div>

                <!-- FORM INPUT -->
                <form action="process_login.php" method="POST" class="space-y-4">
                    <!-- CSRF Token -->
                    <?php echo csrfField(); ?>
                    <input type="hidden" name="role" id="selected-role" value="<?= htmlspecialchars($selectedRole,ENT_QUOTES,'UTF-8') ?>">
                    <!-- Input NISN / Email -->
                    <div>
                        <div class="flex justify-between items-center mb-1">
                            <label class="text-xs font-semibold text-slate-700" id="label-identifier"><?= htmlspecialchars($roleCopy[$selectedRole][0],ENT_QUOTES,'UTF-8') ?></label>
                            <span class="text-[10px] text-slate-400" id="hint-identifier"><?= htmlspecialchars($roleCopy[$selectedRole][1],ENT_QUOTES,'UTF-8') ?></span>
                        </div>
                        <div class="relative">
                            <span class="absolute inset-y-0 left-0 pl-3.5 flex items-center text-slate-400 text-xs">
                                <?= uiIcon('id',18) ?>
                            </span>
                            <input type="text" required autocomplete="username" name="identifier" id="input-identifier" placeholder="<?= htmlspecialchars($roleCopy[$selectedRole][2],ENT_QUOTES,'UTF-8') ?>"
                                class="w-full pl-10 pr-4 py-2.5 bg-slate-50/50 border border-slate-200 rounded-xl text-xs text-slate-800 placeholder-slate-400 focus:outline-none focus:border-[#006d5b] focus:bg-white transition">
                        </div>
                    </div>

                    <!-- Input Kata Sandi -->
                    <div>
                        <div class="flex justify-between items-center mb-1">
                            <label class="text-xs font-semibold text-slate-700">Kata Sandi</label>
                            <a href="#portal-info" data-info="Untuk pengelolaan akun, kata sandi dan data pribadi, hubungi administrator sekolah. Panduan lokal tersedia pada PANDUAN.md. Portal menggunakan akun sekolah yang tersimpan di EduLMS." class="text-xs text-[#006d5b] font-semibold hover:underline">Lupa Sandi?</a>
                        </div>
                        <div class="relative">
                            <span class="absolute inset-y-0 left-0 pl-3.5 flex items-center text-slate-400 text-xs">
                                <?= uiIcon('lock',17) ?>
                            </span>
                            <input type="password" required autocomplete="current-password" name="password" id="password" placeholder="Masukkan kata sandi akun"
                                class="w-full pl-10 pr-10 py-2.5 bg-slate-50/50 border border-slate-200 rounded-xl text-xs text-slate-800 placeholder-slate-400 focus:outline-none focus:border-[#006d5b] focus:bg-white transition">
                            <button type="button" onclick="togglePassword()" class="absolute inset-y-0 right-0 pr-3.5 flex items-center text-slate-400 hover:text-slate-600 text-xs">
                                <i class="fa-regular fa-eye" id="eye-icon"></i>
                            </button>
                        </div>
                    </div>

                    <!-- Checkbox Ingat Perangkat -->
                    <div class="login-remember flex items-center gap-2 pt-1">
                        <input type="checkbox" id="remember" name="remember" class="w-4 h-4 rounded border-slate-300 text-[#006d5b] focus:ring-[#006d5b] cursor-pointer">
                        <label for="remember" class="text-xs text-slate-500 cursor-pointer select-none">Ingat identitas saya di perangkat ini</label>
                    </div>

                    <!-- Tombol Masuk Utama -->
                    <button type="submit" class="w-full py-2.5 bg-[#006d5b] hover:bg-[#005a4b] text-white text-xs font-bold rounded-xl shadow-sm transition flex items-center justify-center gap-2 cursor-pointer mt-2">
                        Masuk ke Sistem <i class="fa-solid fa-arrow-right text-[10px]"></i>
                    </button>

                    <!-- Tambahkan di bawah tombol Masuk ke Sistem atau bagian bawah form login -->
                    <!-- Tambahkan kode berikut di sini -->
<!-- KODE DITAMBAHKAN DI BAWAH TOMBOL MASUK KE SISTEM -->
<div style="text-align: center; margin-top: 12px; margin-bottom: 16px; font-size: 14px; color: #4b5563;">
    Belum punya akun? <a href="register.php" style="color: #2563eb; font-weight: 600; text-decoration: none; margin-left: 4px;">Daftar sekarang</a>
</div>

                </form>

                <!-- Divider ATAU MASUK LEWAT -->
                <div class="relative flex py-3 items-center">
                    <div class="flex-grow border-t border-slate-100"></div>
                    <span class="flex-shrink mx-4 text-[10px] uppercase font-bold tracking-wider text-slate-400">Atau masuk lewat</span>
                    <div class="flex-grow border-t border-slate-100"></div>
                </div>

                <!-- Tombol Alternatif -->
                <div class="login-alternatives grid grid-cols-2 gap-2">
                    <button type="button" data-info="Integrasi ini belum dikonfigurasi sekolah. Gunakan email, NISN atau NIP beserta kata sandi akun EduLMS, atau hubungi administrator." class="py-2 px-3 bg-slate-50 hover:bg-slate-100 border border-slate-200/80 rounded-xl text-xs font-semibold text-slate-700 flex items-center justify-center gap-2 transition">
                        <img src="../assets/images/google.svg" width="16" height="16" alt=""> Akun Belajar.id
                    </button>
                    <button type="button" data-info="Integrasi ini belum dikonfigurasi sekolah. Gunakan email, NISN atau NIP beserta kata sandi akun EduLMS, atau hubungi administrator." class="py-2 px-3 bg-slate-50 hover:bg-slate-100 border border-slate-200/80 rounded-xl text-xs font-semibold text-slate-700 flex items-center justify-center gap-2 transition">
                        <?= uiIcon('key',18) ?> Token Ujian
                    </button>
                </div>
            </div>

            <!-- Footer Bantuan Kendala Akun -->
            <div class="mt-6 pt-4 border-t border-slate-100 flex items-center justify-between bg-[#f8fafc] -mx-8 -mb-8 lg:-mx-10 lg:-mb-10 px-8 py-3.5">
                <div class="flex items-center gap-2.5">
                    <div class="text-slate-400 text-sm">
                        <i class="fa-regular fa-circle-question"></i>
                    </div>
                    <div>
                        <p class="text-[11px] font-bold text-slate-700 leading-tight">Mengalami kendala akun?</p>
                        <p class="text-[10px] text-slate-400">Hubungi Tim Helpdesk IT & Administrator Sekolah</p>
                    </div>
                </div>
                <a href="#portal-info" data-info="Untuk pengelolaan akun, kata sandi dan data pribadi, hubungi administrator sekolah. Panduan lokal tersedia pada PANDUAN.md. Portal menggunakan akun sekolah yang tersimpan di EduLMS." class="px-3 py-1.5 bg-white border border-slate-200 text-slate-700 hover:bg-slate-50 text-[11px] font-semibold rounded-lg shadow-2xs transition">
                    Hubungi
                </a>
            </div>

        </div>
    </div>

    <!-- Copyright Bawah -->
    <div class="text-center text-[10px] text-slate-400 space-y-1 z-10 pb-4">
        <p>&copy; <?= date('Y') ?> EduLMS Indonesia. Terdaftar di Pusat Data dan Teknologi Informasi Pendidikan.</p>
        <div class="flex justify-center gap-3 font-medium flex-wrap">
            <a href="register.php" class="hover:underline">Daftar Akun</a><span>&bull;</span>
            <a href="#portal-info" data-info="Untuk pengelolaan akun, kata sandi dan data pribadi, hubungi administrator sekolah. Panduan lokal tersedia pada PANDUAN.md. Portal menggunakan akun sekolah yang tersimpan di EduLMS." class="hover:underline">Panduan Pengguna</a>
            <span>&bull;</span>
            <a href="#portal-info" data-info="Untuk pengelolaan akun, kata sandi dan data pribadi, hubungi administrator sekolah. Panduan lokal tersedia pada PANDUAN.md. Portal menggunakan akun sekolah yang tersimpan di EduLMS." class="hover:underline">Kebijakan Privasi</a>
            <span>&bull;</span>
            <a href="#portal-info" data-info="Untuk pengelolaan akun, kata sandi dan data pribadi, hubungi administrator sekolah. Panduan lokal tersedia pada PANDUAN.md. Portal menggunakan akun sekolah yang tersimpan di EduLMS." class="hover:underline">Status Layanan</a>
        </div>
    </div>
    <script>
    const remembered = localStorage.getItem('edulms_identifier');
    if (remembered) { document.getElementById('input-identifier').value = remembered; document.getElementById('remember').checked = true; }
    document.querySelector('form').addEventListener('submit', () => {
      if (document.getElementById('remember').checked) localStorage.setItem('edulms_identifier', document.getElementById('input-identifier').value);
      else localStorage.removeItem('edulms_identifier');
    });
    </script>
    <!-- Script Interaktif Sederhana (Role Switcher & Show/Hide Password) -->
    <script>
        // Toggle Show/Hide Password
        function togglePassword() {
            const passwordInput = document.getElementById('password');
            const eyeIcon = document.getElementById('eye-icon');
            if (passwordInput.type === 'password') {
                passwordInput.type = 'text';
                eyeIcon.classList.remove('fa-eye');
                eyeIcon.classList.add('fa-eye-slash');
            } else {
                passwordInput.type = 'password';
                eyeIcon.classList.remove('fa-eye-slash');
                eyeIcon.classList.add('fa-eye');
            }
        }

        // Role selection is submitted and verified again by the server.
        const roleButtons = document.querySelectorAll('.role-btn');
        const roleCopy = <?= json_encode($roleCopy) ?>;
        function selectLoginRole(role) {
            if (!Object.hasOwn(roleCopy, role)) return;
            document.getElementById('selected-role').value = role;
            roleButtons.forEach(button => {
                const active = button.dataset.role === role;
                button.classList.toggle('bg-white', active);
                button.classList.toggle('text-slate-900', active);
                button.classList.toggle('shadow-xs', active);
                button.classList.toggle('hover:text-slate-900', !active);
                button.setAttribute('aria-pressed', String(active));
                button.querySelector('i,svg')?.classList.toggle('text-[#006d5b]', active);
            });
            document.getElementById('label-identifier').textContent = roleCopy[role][0];
            document.getElementById('hint-identifier').textContent = roleCopy[role][1];
            document.getElementById('input-identifier').placeholder = roleCopy[role][2];
        }
        roleButtons.forEach(button => button.addEventListener('click', () => selectLoginRole(button.dataset.role)));
        selectLoginRole(<?= json_encode($selectedRole) ?>);
    </script>
<dialog class="portal-dialog" id="portal-info" aria-labelledby="portal-info-title"><button type="button" data-dialog-close aria-label="Tutup"><?= uiIcon('close',18) ?></button><h2 id="portal-info-title">Bantuan</h2><p></p></dialog>
</body>
</html>
