<?php
require_once '../includes/db.php';
require_once '../includes/auth_check.php';
require_once '../includes/functions.php';
requireAuth();
?>
<!doctype html><html lang="id"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1"><title>Bantuan — EduLMS</title><link rel="stylesheet" href="../assets/css/tailwind.css"><link rel="stylesheet" href="../assets/fonts/jakarta.css"><?php require '../includes/portal_assets.php'; ?></head>
<body><?php require '../includes/portal_nav.php'; ?><main class="max-w-7xl mx-auto space-y-6">
<section class="bg-white rounded-2xl p-6"><span class="portal-badge">Pusat Bantuan</span><h1 class="text-xl font-bold my-3">Belajar dan mengajar bersama EduLMS</h1><p>Untuk perubahan akun, kata sandi, penempatan kelas, atau penugasan mengajar, hubungi administrator sekolah Anda.</p></section>
<section class="bg-white rounded-2xl p-6 space-y-3"><h2 class="text-lg font-bold">Butuh bantuan lebih lanjut?</h2><p>Kirim kendala dan pantau tanggapan administrator melalui tiket bantuan.</p><a href="dukungan.php" class="ui-button primary"><?= uiIcon('message',18) ?> <?= currentUserRole()==='admin'?'Kelola Tiket Bantuan':'Kirim & Lihat Tiket' ?></a></section>
<section id="panduan" class="bg-white rounded-2xl p-6 space-y-4"><h2 class="text-lg font-bold">Panduan Belajar</h2><p><strong>Siswa:</strong> buka Mata Pelajaran untuk membaca materi. Pada Tugas, unduh soal, lalu unggah jawaban sesuai format. Nilai dan feedback tampil setelah guru menilai. Riwayat memuat nilai dan kehadiran Anda.</p><p><strong>Guru:</strong> pilih Kelas & Mata Pelajaran untuk mengunggah materi dan membuat tugas. Gunakan Penilaian Tugas untuk memeriksa berkas dan menyimpan nilai 0–100. Pada Presensi Siswa, pilih kelas dan tanggal, isi status serta catatan, lalu simpan.</p><p><strong>Admin:</strong> buat pengguna, kelas, dan mata pelajaran; tempatkan siswa lalu tugaskan guru pada kelas dan tahun ajaran yang sama. Terbitkan pengumuman sesuai penerimanya.</p></section>
<section id="privasi" class="bg-white rounded-2xl p-6 space-y-3"><h2 class="text-lg font-bold">Privasi dan Akses</h2><p>Data akademik disimpan dalam database sekolah. Berkas materi, tugas dan jawaban hanya dapat diunduh oleh pengguna yang berhak. Gunakan tombol Keluar setelah memakai perangkat bersama.</p><p>Permintaan koreksi data dan pengelolaan akun ditangani administrator sekolah. Portal ini tidak terhubung dengan Google, Belajar.id, atau sistem rapor eksternal.</p></section>
</main><?php require '../includes/portal_footer.php'; ?></body></html>
