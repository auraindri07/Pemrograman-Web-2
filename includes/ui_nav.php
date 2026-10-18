<?php
require_once __DIR__.'/ui.php';
$navRole = $_SESSION['role'] ?? 'siswa';
$navPage = basename($_SERVER['SCRIPT_NAME']);
$navItems = $navRole === 'guru' ? [
 'guru.php'=>'Dashboard Guru','guru_kelas.php'=>'Kelas & Mata Pelajaran','guru_penilaian.php'=>'Penilaian Tugas','guru_presensi.php'=>'Presensi Siswa',
] : ($navRole === 'admin' ? [
 'admin.php'=>'Dashboard','admin_pengguna.php'=>'Pengguna','admin_kelas.php'=>'Kelas','admin_mapel.php'=>'Mata Pelajaran',
 'admin_penempatan.php'=>'Penempatan Siswa','admin_penugasan.php'=>'Penugasan Guru','admin_pengumuman.php'=>'Pengumuman',
] : ['index.php'=>'Dashboard','mata_pelajaran.php'=>'Mata Pelajaran','tugas.php'=>'Tugas','pengumuman.php'=>'Pengumuman']);
$navParent = ['mapel_detail.php'=>'mata_pelajaran.php','kumpulkan_tugas.php'=>'tugas.php','lihat_nilai.php'=>'tugas.php','guru_materi.php'=>'guru_kelas.php','guru_buat_tugas.php'=>'guru_kelas.php','guru_daftar_siswa.php'=>'guru_kelas.php'];
$navActive = $navParent[$navPage] ?? $navPage;
$navName = $_SESSION['nama_lengkap'] ?? 'Pengguna';
$navPending = 0;
$navSubtitle=ucfirst($navRole);
if ($navRole === 'guru' && isset($pdo)) {
 $navCounter = $pdo->prepare('SELECT COUNT(*) FROM pengumpulan_tugas pt JOIN tugas t ON t.id=pt.tugas_id JOIN guru_mapel gm ON gm.id=t.guru_mapel_id WHERE gm.guru_id=? AND pt.nilai IS NULL');
 $navCounter->execute([$_SESSION['user_id']]); $navPending = (int)$navCounter->fetchColumn();
 $navSubjects=$pdo->prepare('SELECT DISTINCT mp.nama_mapel,mp.id FROM guru_mapel gm JOIN mata_pelajaran mp ON mp.id=gm.mapel_id WHERE gm.guru_id=? AND gm.is_active=1 ORDER BY mp.id');$navSubjects->execute([$_SESSION['user_id']]);
 $subjectNames=array_unique(array_map(fn($name)=>explode(' ',$name)[0],$navSubjects->fetchAll(PDO::FETCH_COLUMN)));
 $navSubtitle='Guru '.implode(' & ',array_slice($subjectNames,0,2));
}
?>
<header class="portal-header <?= $navRole==='admin'?'admin-header':'' ?>"><div class="portal-nav-wrap">
 <a class="portal-brand" href="<?= e(array_key_first($navItems)) ?>" aria-label="EduLMS beranda"><img src="../assets/images/reference/logo.png" width="24" height="20" alt=""><span>EduLMS</span><?php if ($navRole !== 'siswa'): ?><small>Portal <?= e(ucfirst($navRole)) ?></small><?php endif; ?></a>
 <button type="button" class="portal-menu-button" aria-controls="portal-navigation" aria-expanded="false"><?= uiIcon('menu',18) ?> Menu</button>
 <nav id="portal-navigation" class="portal-navigation" aria-label="Navigasi utama">
 <?php foreach ($navItems as $url=>$label): ?><a href="<?= e($url) ?>" <?= $url === $navActive ? 'aria-current="page"' : '' ?>><?= e($label) ?><?php if ($url==='guru_penilaian.php' && $navPending): ?><span class="nav-count"><?= $navPending ?></span><?php endif; ?></a><?php endforeach; ?>
 </nav>
 <?php if ($navRole==='guru'): ?><div class="nav-utilities"><a href="pengumuman.php" aria-label="Pesan sekolah"><?= uiIcon('message',19) ?></a><a href="pengumuman.php" class="notification-link" aria-label="Pengumuman terbaru"><?= uiIcon('bell',19) ?><i></i></a></div><?php endif; ?>
 <div class="portal-account"><details class="profile-menu"><summary><img class="portal-avatar" src="../assets/images/reference/avatar.png" alt=""><span><strong><?= e($navName) ?></strong><small><?= e($navSubtitle) ?></small></span></summary><div class="profile-popover"><?php if ($navRole==='siswa'): ?><a href="riwayat.php">Riwayat Nilai & Kehadiran</a><a href="presensi.php">Presensi Saya</a><?php endif; ?><a href="bantuan.php">Bantuan & Panduan</a><a href="../auth/logout.php">Keluar</a></div></details><a class="portal-logout" href="../auth/logout.php" aria-label="Keluar"><?= uiIcon('logout',18) ?><?php if ($navRole!=='guru'): ?><span>Keluar</span><?php endif; ?></a></div>
</div></header>
