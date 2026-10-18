<?php
/**
 * EduLMS - Admin Header (partial)
 * Navbar & pembuka halaman untuk modul Admin.
 * Mengikuti gaya visual portal existing (teal, Plus Jakarta Sans).
 *
 * Variabel yang harus di-set sebelum include:
 *   $adminPageTitle  (string) -> judul halaman
 *   $adminActive     (string) -> kunci nav aktif (dashboard|pengguna|kelas|mapel|penempatan|penugasan|pengumuman)
 */
if (!isset($adminPageTitle)) $adminPageTitle = 'Admin';
if (!isset($adminActive)) $adminActive = '';
$adminNama = $_SESSION['nama_lengkap'] ?? 'Admin';

function adminNavActive($key, $adminActive) {
    return $key === $adminActive
        ? 'px-3 py-1.5 rounded-lg text-xs font-semibold bg-teal-50 text-teal-700'
        : 'px-3 py-1.5 rounded-lg text-xs font-medium text-slate-600 hover:bg-slate-100';
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo e($adminPageTitle); ?> - EduLMS Portal Admin</title>
    <link rel="stylesheet" href="../assets/css/tailwind.css">
    <link rel="stylesheet" href="../assets/fonts/jakarta.css">
    <style>
        body { font-family: 'Plus Jakarta Sans', sans-serif; }
    </style>
<?php require __DIR__ . '/../includes/portal_assets.php'; ?>
</head>
<body class="page-admin_header admin-portal bg-slate-50 text-slate-800 min-h-screen flex flex-col justify-between">

    <div>
        <!-- Navbar Atas Khusus Admin -->
        <?php require __DIR__ . '/../includes/portal_nav.php'; ?>

        <!-- Konten Utama -->
        <main class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8 w-full space-y-6">
        <?php if($adminActive!=='dashboard'): $moduleIcons=['pengguna'=>'users','kelas'=>'school','mapel'=>'book','penempatan'=>'person-check','penugasan'=>'teacher','pengumuman'=>'megaphone']; ?>
        <section class="admin-page-heading"><span class="admin-heading-icon"><?= uiIcon($moduleIcons[$adminActive]??'home',28) ?></span><div><p>ADMINISTRASI SEKOLAH</p><h1><?= e($adminPageTitle) ?></h1><span>Kelola data sekolah dan pantau informasi yang terhubung ke portal guru dan siswa.</span></div><a href="admin.php" class="admin-back-link"><?= uiIcon('home',17) ?> Dashboard</a></section>
        <?php endif; ?>
