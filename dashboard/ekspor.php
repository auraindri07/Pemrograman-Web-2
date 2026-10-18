<?php
require_once '../includes/db.php';
require_once '../includes/auth_check.php';
requireTeacher();
$type = $_GET['type'] ?? '';
if ($type === 'nilai') {
    $stmt = $pdo->prepare('SELECT k.nama_kelas,mp.nama_mapel,t.judul_tugas,u.nama_lengkap,u.nisn,pt.tanggal_kumpul,pt.nilai,pt.feedback FROM pengumpulan_tugas pt JOIN tugas t ON t.id=pt.tugas_id JOIN guru_mapel gm ON gm.id=t.guru_mapel_id JOIN kelas k ON k.id=gm.kelas_id JOIN mata_pelajaran mp ON mp.id=gm.mapel_id JOIN users u ON u.id=pt.siswa_id WHERE gm.guru_id=? ORDER BY k.nama_kelas,t.id,u.nama_lengkap');
    $stmt->execute([$_SESSION['user_id']]);
    $columns = ['Kelas','Mata Pelajaran','Tugas','Siswa','NISN','Tanggal Kumpul','Nilai','Feedback'];
} elseif ($type === 'presensi') {
    $month = $_GET['bulan'] ?? date('Y-m');
    if (!preg_match('/^\d{4}-(0[1-9]|1[0-2])$/', $month)) { http_response_code(400); exit('Bulan tidak valid.'); }
    $stmt = $pdo->prepare("SELECT k.nama_kelas,mp.nama_mapel,u.nama_lengkap,u.nisn,p.tanggal_sesi,p.status_kehadiran,p.jam_kedatangan,p.catatan FROM presensi p JOIN guru_mapel gm ON gm.id=p.guru_mapel_id JOIN kelas k ON k.id=gm.kelas_id JOIN mata_pelajaran mp ON mp.id=gm.mapel_id JOIN users u ON u.id=p.siswa_id WHERE gm.guru_id=? AND DATE_FORMAT(p.tanggal_sesi,'%Y-%m')=? ORDER BY p.tanggal_sesi,k.nama_kelas,u.nama_lengkap");
    $stmt->execute([$_SESSION['user_id'],$month]);
    $columns = ['Kelas','Mata Pelajaran','Siswa','NISN','Tanggal','Status','Jam Kedatangan','Catatan'];
} else { http_response_code(400); exit('Jenis ekspor tidak valid.'); }
header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename="edulms-' . $type . '.csv"');
header('Cache-Control: private, no-store');
$out = fopen('php://output','w');
fwrite($out,"\xEF\xBB\xBF");
fputcsv($out,$columns,',','"','');
foreach ($stmt->fetchAll(PDO::FETCH_NUM) as $row) {
    // Avoid spreadsheet formula execution in user-controlled cells.
    $row = array_map(fn($cell) => preg_match('/^[=+@\-\t\r\n]/', (string)$cell) ? "'" . $cell : $cell, $row);
    fputcsv($out,$row,',','"','');
}
fclose($out);
