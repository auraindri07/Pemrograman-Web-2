<?php
/** Read-only, unpaginated teacher reports for browser printing / Save as PDF. */
require_once '../includes/db.php';
require_once '../includes/config.php';
require_once '../includes/auth_check.php';
require_once '../includes/functions.php';
requireTeacher();
header('Cache-Control: private, no-store');

$type = $_GET['type'] ?? 'kelas';
if (!is_string($type) || !in_array($type, ['kelas', 'presensi', 'nilai'], true)) {
    http_response_code(400); exit('Jenis laporan tidak valid.');
}
$month = $_GET['bulan'] ?? date('Y-m');
if (!is_string($month) || !preg_match('/^[1-9]\d{3}-(0[1-9]|1[0-2])$/', $month)) {
    http_response_code(400); exit('Bulan tidak valid.');
}
function reportId(string $key): int {
    $value = $_GET[$key] ?? '0';
    if (!is_string($value) || !ctype_digit($value) || strlen($value) > 9) {
        http_response_code(400); exit('Filter laporan tidak valid.');
    }
    return (int)$value;
}
$classId = reportId('kelas');
$taskId = reportId('tugas_id');
$teacherId = (int)currentUserId();
if ($classId) {
    $access = $pdo->prepare('SELECT id FROM guru_mapel WHERE guru_id=? AND kelas_id=? AND is_active=1 LIMIT 1');
    $access->execute([$teacherId, $classId]);
    if (!$access->fetchColumn()) { http_response_code(403); exit('Kelas bukan penugasan Anda.'); }
}
if ($taskId) {
    $access = $pdo->prepare('SELECT t.id FROM tugas t JOIN guru_mapel gm ON gm.id=t.guru_mapel_id WHERE t.id=? AND gm.guru_id=?');
    $access->execute([$taskId, $teacherId]);
    if (!$access->fetchColumn()) { http_response_code(403); exit('Tugas bukan penugasan Anda.'); }
}

$titles = ['kelas'=>'Rekap Kelas & Mengajar', 'presensi'=>'Rekap Presensi Bulanan', 'nilai'=>'Rekap Penilaian Tugas'];
$back = ['kelas'=>'guru_kelas.php', 'presensi'=>'guru_presensi.php', 'nilai'=>'guru_penilaian.php'];
$params = [$teacherId];
$classFilter = $classId ? ' AND gm.kelas_id=?' : '';
if ($classId) $params[] = $classId;
$groups = [];
$rows = [];
if ($type === 'kelas') {
    $query = $pdo->prepare("SELECT gm.id,k.nama_kelas,mp.nama_mapel,gm.tahun_ajaran,gm.hari,gm.jam_mulai,gm.jam_selesai,gm.ruang,
        (SELECT COUNT(*) FROM siswa_kelas sk WHERE sk.kelas_id=gm.kelas_id AND sk.tahun_ajaran=gm.tahun_ajaran AND sk.status='aktif') AS siswa,
        (SELECT COUNT(*) FROM materi m WHERE m.guru_mapel_id=gm.id AND m.is_published=1) AS materi,
        (SELECT COUNT(*) FROM tugas t WHERE t.guru_mapel_id=gm.id AND t.is_published=1) AS tugas
        FROM guru_mapel gm JOIN kelas k ON k.id=gm.kelas_id JOIN mata_pelajaran mp ON mp.id=gm.mapel_id
        WHERE gm.guru_id=? AND gm.is_active=1 $classFilter ORDER BY gm.tahun_ajaran DESC,k.nama_kelas,mp.nama_mapel,gm.id");
    $query->execute($params);
    $rows = $query->fetchAll();
} elseif ($type === 'presensi') {
    $query = $pdo->prepare("SELECT gm.id,k.nama_kelas,mp.nama_mapel,gm.tahun_ajaran,u.id AS siswa_id,u.nama_lengkap,u.nisn,
        COUNT(*) AS total,SUM(p.status_kehadiran='hadir') AS hadir,SUM(p.status_kehadiran='sakit') AS sakit,
        SUM(p.status_kehadiran='izin') AS izin,SUM(p.status_kehadiran='alfa') AS alfa
        FROM presensi p JOIN guru_mapel gm ON gm.id=p.guru_mapel_id JOIN kelas k ON k.id=gm.kelas_id
        JOIN mata_pelajaran mp ON mp.id=gm.mapel_id JOIN users u ON u.id=p.siswa_id
        WHERE gm.guru_id=? AND gm.is_active=1 $classFilter AND p.tanggal_sesi>=? AND p.tanggal_sesi<?
        GROUP BY gm.id,k.nama_kelas,mp.nama_mapel,gm.tahun_ajaran,u.id,u.nama_lengkap,u.nisn
        ORDER BY gm.tahun_ajaran DESC,k.nama_kelas,mp.nama_mapel,gm.id,u.nama_lengkap,u.id");
    $start = $month.'-01';
    $query->execute([...$params, $start, date('Y-m-d', strtotime($start.' +1 month'))]);
    $rows = $query->fetchAll();
} else {
    $taskFilter = $taskId ? ' AND t.id=?' : '';
    if ($taskId) $params[] = $taskId;
    $query = $pdo->prepare("SELECT t.id,t.judul_tugas,k.nama_kelas,mp.nama_mapel,gm.tahun_ajaran,u.nama_lengkap,u.nisn,
        pt.tanggal_kumpul,pt.nilai,pt.feedback
        FROM pengumpulan_tugas pt JOIN tugas t ON t.id=pt.tugas_id JOIN guru_mapel gm ON gm.id=t.guru_mapel_id
        JOIN kelas k ON k.id=gm.kelas_id JOIN mata_pelajaran mp ON mp.id=gm.mapel_id JOIN users u ON u.id=pt.siswa_id
        WHERE gm.guru_id=? $classFilter $taskFilter ORDER BY gm.tahun_ajaran DESC,k.nama_kelas,mp.nama_mapel,t.id,u.nama_lengkap,u.id");
    $query->execute($params);
    $rows = $query->fetchAll();
}
if ($type !== 'kelas') {
    foreach ($rows as $row) $groups[$row['id']][] = $row;
}
$years = array_values(array_unique(array_column($rows, 'tahun_ajaran')));
$yearLabel = $years ? implode(', ', $years) : TAHUN_AJARAN_AKTIF;
$graded = array_values(array_filter($rows, fn($row) => isset($row['nilai'])));
$average = $graded ? number_format(array_sum(array_column($graded, 'nilai')) / count($graded), 1, ',', '.') : '-';
$period = 'Seluruh data pada cakupan laporan';
if ($type === 'presensi') {
    $months = [1=>'Januari','Februari','Maret','April','Mei','Juni','Juli','Agustus','September','Oktober','November','Desember'];
    $period = $months[(int)substr($month,5,2)].' '.substr($month,0,4);
}
?>
<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= e($titles[$type].' - '.($type==='presensi'?$month:$yearLabel).' - EduLMS') ?></title>
    <link rel="stylesheet" href="../assets/fonts/jakarta.css">
    <link rel="stylesheet" href="../assets/css/report-print.css">
    <script src="../assets/js/script.js" defer></script>
</head>
<body class="report-page">
<div class="report-toolbar"><a href="<?= e($back[$type]) ?>">&larr; Kembali</a><p>Siap dicetak pada kertas A4. Pilih <strong>Simpan sebagai PDF</strong> pada dialog cetak.</p><button type="button" data-print>Cetak / Simpan PDF</button></div>
<main class="report-sheet">
    <header class="report-header"><div><span class="report-brand">EduLMS<span>PORTAL AKADEMIK</span></span><h1><?= e($titles[$type]) ?></h1><p><?= e($period) ?></p></div><span class="report-label">LAPORAN PENGAJAR</span></header>
    <dl class="report-meta"><div><dt>Nama pengajar</dt><dd><?= e($_SESSION['nama_lengkap']) ?></dd></div><div><dt>Tahun ajaran</dt><dd><?= e($yearLabel) ?></dd></div><div><dt>Cakupan laporan</dt><dd><?= $classId?'Kelas yang dipilih':'Seluruh kelas pengajar' ?><?= $taskId?' / tugas yang dipilih':'' ?></dd></div><div><dt>Dicetak pada</dt><dd><?= e(formatTanggalIndo(date('Y-m-d')).' · '.date('H:i').' WIB') ?></dd></div></dl>
    <div class="report-summary">
    <?php if ($type==='kelas'): ?>
        <span><b><?= count($rows) ?></b> Penugasan aktif</span><span><b><?= array_sum(array_column($rows,'materi')) ?></b> Materi terbit</span><span><b><?= array_sum(array_column($rows,'tugas')) ?></b> Tugas terbit</span>
    <?php elseif ($type==='presensi'): ?>
        <span><b><?= count($groups) ?></b> Kelas / mapel</span><span><b><?= array_sum(array_column($rows,'total')) ?></b> Catatan presensi</span><span><b><?= calculatePercentage(array_sum(array_column($rows,'hadir')),array_sum(array_column($rows,'total'))) ?>%</b> Kehadiran</span>
    <?php else: ?>
        <span><b><?= count($rows) ?></b> Pengumpulan</span><span><b><?= count($graded) ?></b> Sudah dinilai</span><span><b><?= e($average) ?></b> Rata-rata nilai</span>
    <?php endif; ?>
    </div>
    <aside class="report-note"><?php if ($type==='presensi'): ?>H = Hadir, S = Sakit, I = Izin, A = Alfa. Persentase dihitung dari catatan presensi yang tersimpan; siswa tanpa catatan bulan ini tidak ditampilkan.<?php elseif ($type==='nilai'): ?>Laporan mencakup pengumpulan yang sudah tersimpan. Nilai kosong ditulis “Belum dinilai” dan tidak dihitung dalam rata-rata.<?php else: ?>Jumlah siswa dihitung dari penempatan aktif pada kelas dan tahun ajaran terkait. Materi dan tugas dihitung dari yang sudah diterbitkan.<?php endif; ?></aside>
    <?php if (!$rows): ?><section class="report-empty"><h2>Belum ada data untuk laporan ini</h2><p><?= $type==='presensi'?'Belum ada presensi tersimpan pada bulan yang dipilih.':($type==='nilai'?'Belum ada pengumpulan tugas dalam cakupan laporan.':'Belum ada penugasan mengajar aktif.') ?></p></section>
    <?php elseif ($type==='kelas'): ?>
    <table class="report-table class-report"><caption>Daftar penugasan mengajar aktif</caption><colgroup><col class="col-no"><col style="width:17%"><col style="width:22%"><col style="width:24%"><col style="width:11%"><col style="width:10%"><col style="width:10%"></colgroup><thead><tr><th>No.</th><th>Kelas</th><th>Mata pelajaran</th><th>Jadwal / ruang</th><th class="number">Siswa</th><th class="number">Materi</th><th class="number">Tugas</th></tr></thead><tbody>
    <?php foreach ($rows as $i=>$row): ?><tr><td class="number"><?= $i+1 ?></td><td><strong><?= e($row['nama_kelas']) ?></strong><small><?= e($row['tahun_ajaran']) ?></small></td><td><?= e($row['nama_mapel']) ?></td><td><?= e($row['hari']?:'-') ?><small><?= e($row['jam_mulai']?substr($row['jam_mulai'],0,5).($row['jam_selesai']?'–'.substr($row['jam_selesai'],0,5):''):'Jam belum diatur') ?></small><small><?= e($row['ruang']?:'Ruang belum diatur') ?></small></td><td class="number"><?= (int)$row['siswa'] ?></td><td class="number"><?= (int)$row['materi'] ?></td><td class="number"><?= (int)$row['tugas'] ?></td></tr><?php endforeach; ?>
    </tbody></table>
    <?php else: foreach ($groups as $group): $first=$group[0]; ?>
    <table class="report-table <?= $type==='presensi'?'attendance-report':'grade-report' ?>">
        <?php if ($type==='presensi'): ?>
        <colgroup><col class="col-no"><col style="width:25%"><col style="width:18%"><col span="4" style="width:6%"><col style="width:12%"><col style="width:15%"></colgroup>
        <thead><tr class="report-group"><th colspan="9"><span><?= e($first['nama_kelas'].' / '.$first['nama_mapel']) ?></span><small>Tahun ajaran <?= e($first['tahun_ajaran']) ?> · <?= e($period) ?></small></th></tr><tr><th>No.</th><th>Nama siswa</th><th>NISN</th><th class="number">H</th><th class="number">S</th><th class="number">I</th><th class="number">A</th><th class="number">Total</th><th class="number">Hadir (%)</th></tr></thead>
        <tbody><?php foreach ($group as $i=>$row): ?><tr><td class="number"><?= $i+1 ?></td><td><?= e($row['nama_lengkap']) ?></td><td class="identifier"><?= e($row['nisn']?:'-') ?></td><?php foreach (['hadir','sakit','izin','alfa','total'] as $key): ?><td class="number"><?= (int)$row[$key] ?></td><?php endforeach; ?><td class="number"><?= calculatePercentage($row['hadir'],$row['total']) ?>%</td></tr><?php endforeach; ?></tbody>
        <?php else: ?>
        <colgroup><col class="col-no"><col style="width:23%"><col style="width:17%"><col style="width:18%"><col style="width:11%"><col style="width:25%"></colgroup>
        <thead><tr class="report-group"><th colspan="6"><span><?= e($first['judul_tugas']) ?></span><small><?= e($first['nama_kelas'].' / '.$first['nama_mapel'].' · '.$first['tahun_ajaran']) ?></small></th></tr><tr><th>No.</th><th>Nama siswa</th><th>NISN</th><th>Dikumpulkan</th><th class="number">Nilai</th><th>Umpan balik</th></tr></thead>
        <tbody><?php foreach ($group as $i=>$row): ?><tr><td class="number"><?= $i+1 ?></td><td><?= e($row['nama_lengkap']) ?></td><td class="identifier"><?= e($row['nisn']?:'-') ?></td><td><?= e(formatTanggalIndo($row['tanggal_kumpul'])) ?><small><?= e(date('H:i',strtotime($row['tanggal_kumpul']))) ?> WIB</small></td><td class="number"><?php if ($row['nilai']!==null): ?><strong><?= e(number_format((float)$row['nilai'],2,',','.')) ?></strong><?php else: ?><span class="ungraded">Belum dinilai</span><?php endif; ?></td><td class="report-feedback"><?= e($row['feedback']?:'-') ?></td></tr><?php endforeach; ?></tbody>
        <?php endif; ?>
    </table>
    <?php endforeach; endif; ?>


    <footer class="report-footer">EduLMS · <?= e($titles[$type]) ?> · Dokumen dibuat dari data yang tersimpan saat laporan dibuka.</footer>
</main>
</body>
</html>
