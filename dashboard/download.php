<?php
/**
 * EduLMS - Endpoint Unduh File Terotentikasi (Tahap 13)
 *
 * Menyajikan berkas terlampir (materi / soal tugas / pengumpulan siswa)
 * melalui pemeriksaan sesi + otorisasi, bukan lewat link statis langsung.
 *
 * Parameter:
 *   ?type=materi|tugas|pengumpulan&id=<id>
 *
 * Otorisasi:
 *   - admin   : boleh semua
 *   - guru    : hanya berkas dari guru_mapel miliknya
 *   - siswa   : materi/soal → harus terdaftar aktif di kelas & tahun ajaran itu
 *               pengumpulan → hanya berkas miliknya sendiri
 */

require_once '../includes/db.php';
require_once '../includes/config.php';
require_once '../includes/auth_check.php';
require_once '../includes/functions.php';

requireAuth();

$type = $_GET['type'] ?? '';
$id   = intval($_GET['id'] ?? 0);
$role = currentUserRole();
$uid  = currentUserId();

// The grading overview offers one archive for the teacher's own assignment.
if ($type === 'arsip' && $id > 0) {
    requireTeacher();
    $owner = $pdo->prepare('SELECT t.id FROM tugas t JOIN guru_mapel gm ON gm.id=t.guru_mapel_id WHERE t.id=? AND gm.guru_id=?');
    $owner->execute([$id,$uid]);
    if (!$owner->fetchColumn()) { http_response_code(403); exit('Anda tidak berhak mengakses berkas ini.'); }
    if (!class_exists('ZipArchive')) { http_response_code(503); exit('Unduhan arsip belum tersedia. Unduh jawaban satu per satu melalui layar koreksi.'); }
    $files = $pdo->prepare('SELECT id,siswa_id,file_path,file_name FROM pengumpulan_tugas WHERE tugas_id=? ORDER BY id');
    $files->execute([$id]);
    $baseUpload = realpath(UPLOAD_PENGUMPULAN_DIR);
    $safeFiles = [];
    foreach ($files as $file) {
        $real = realpath(dirname(__DIR__).'/'.$file['file_path']);
        if ($real && $baseUpload && str_starts_with($real,$baseUpload.DIRECTORY_SEPARATOR) && is_file($real)) {
            $safeFiles[] = [$real,$file['siswa_id'].'-'.$file['id'].'-'.preg_replace('/[^\w.\-]+/','_',basename($file['file_name']))];
        }
    }
    if (!$safeFiles) { http_response_code(404); exit('Belum ada berkas jawaban yang tersedia untuk diunduh.'); }
    $temporary = tempnam(sys_get_temp_dir(),'edulms-archive-');
    if ($temporary === false) { http_response_code(503); exit('Arsip tidak dapat dibuat saat ini.'); }
    try {
        $zip = new ZipArchive();
        if ($zip->open($temporary,ZipArchive::OVERWRITE) !== true) { unlink($temporary); http_response_code(503); exit('Arsip tidak dapat dibuat saat ini.'); }
        foreach ($safeFiles as [$real,$entry]) $zip->addFile($real,$entry);
        $zip->close();
        header('Content-Type: application/zip');
        header('Content-Disposition: attachment; filename="jawaban-tugas-'.$id.'.zip"');
        header('Cache-Control: private, no-store');
        header('X-Content-Type-Options: nosniff');
        readfile($temporary);
    } finally { if (is_file($temporary)) unlink($temporary); }
    exit;
}

if ($id <= 0 || !in_array($type, ['materi', 'tugas', 'pengumpulan'])) {
    http_response_code(400);
    die('Permintaan tidak valid.');
}

$filePath = null;
$fileName = null;

if ($type === 'materi' || $type === 'tugas') {
    $table = ($type === 'materi') ? 'materi' : 'tugas';
    $stmt = $pdo->prepare("
        SELECT f.file_path, f.file_name, f.is_published, gm.is_active, gm.guru_id, gm.kelas_id, gm.tahun_ajaran
        FROM $table f
        JOIN guru_mapel gm ON f.guru_mapel_id = gm.id
        WHERE f.id = ?
    ");
    $stmt->execute([$id]);
    $row = $stmt->fetch();

    if (!$row) { http_response_code(404); die('Berkas tidak ditemukan.'); }

    $allowed = false;
    if ($role === 'admin') {
        $allowed = true;
    } elseif ($role === 'guru') {
        $allowed = ((int)$row['guru_id'] === (int)$uid);
    } elseif ($role === 'siswa') {
        $cek = $pdo->prepare("
            SELECT 1 FROM siswa_kelas
            WHERE siswa_id = ? AND kelas_id = ? AND tahun_ajaran = ? AND status = 'aktif'
            LIMIT 1
        ");
        $cek->execute([$uid, $row['kelas_id'], $row['tahun_ajaran']]);
        $allowed = (bool)$cek->fetchColumn() && $row['is_published'] && $row['is_active'];
    }

    if (!$allowed) { http_response_code(403); die('Anda tidak berhak mengakses berkas ini.'); }
    $filePath = $row['file_path'];
    $fileName = $row['file_name'];

} elseif ($type === 'pengumpulan') {
    $stmt = $pdo->prepare("
        SELECT pt.file_path, pt.file_name, pt.siswa_id, gm.guru_id
        FROM pengumpulan_tugas pt
        JOIN tugas t ON pt.tugas_id = t.id
        JOIN guru_mapel gm ON t.guru_mapel_id = gm.id
        WHERE pt.id = ?
    ");
    $stmt->execute([$id]);
    $row = $stmt->fetch();

    if (!$row) { http_response_code(404); die('Berkas tidak ditemukan.'); }

    $allowed = false;
    if ($role === 'admin') {
        $allowed = true;
    } elseif ($role === 'guru') {
        $allowed = ((int)$row['guru_id'] === (int)$uid);
    } elseif ($role === 'siswa') {
        $allowed = ((int)$row['siswa_id'] === (int)$uid);
    }

    if (!$allowed) { http_response_code(403); die('Anda tidak berhak mengakses berkas ini.'); }
    $filePath = $row['file_path'];
    $fileName = $row['file_name'];
}

// ===== Resolusi path aman (cegah path traversal) =====
if (!$filePath) { http_response_code(404); die('Berkas tidak ditemukan.'); }

$baseUpload = realpath(UPLOAD_DIR);
$real = realpath(dirname(__DIR__) . '/' . $filePath);

if ($real === false || $baseUpload === false || !str_starts_with($real, $baseUpload . DIRECTORY_SEPARATOR) || !is_file($real)) {
    http_response_code(404);
    die('Berkas tidak ditemukan atau path tidak valid.');
}

// ===== Stream berkas =====
$ext = strtolower(pathinfo($real, PATHINFO_EXTENSION));
$mimeMap = [
    'pdf'  => 'application/pdf',
    'doc'  => 'application/msword',
    'docx' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
    'ppt'  => 'application/vnd.ms-powerpoint',
    'pptx' => 'application/vnd.openxmlformats-officedocument.presentationml.presentation',
    'xls'  => 'application/vnd.ms-excel',
    'xlsx' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
    'zip'  => 'application/zip',
    'jpg'  => 'image/jpeg',
    'jpeg' => 'image/jpeg',
    'png'  => 'image/png',
];
$mime = $mimeMap[$ext] ?? 'application/octet-stream';

// Gambar/PDF ditampilkan inline, lainnya sebagai unduhan
$disposition = in_array($ext, ['pdf', 'jpg', 'jpeg', 'png']) ? 'inline' : 'attachment';

// Naman file unduhan: ganti karakter berbahaya, pakai nama asli dari DB
$safeName = preg_replace('/[^\w.\- ]+/', '_', $fileName ?: basename($real));

while (ob_get_level() > 0) { ob_end_clean(); }

header('Content-Type: ' . $mime);
header('Content-Length: ' . filesize($real));
header('Content-Disposition: ' . $disposition . '; filename="' . $safeName . '"');
header('X-Content-Type-Options: nosniff');
header('Cache-Control: private, no-store');

readfile($real);
exit();
