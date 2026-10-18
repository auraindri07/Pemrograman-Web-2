<?php
// CLI-only read-only inventory; never expose schema details over HTTP.
if (PHP_SAPI !== 'cli') { http_response_code(404); exit; }
require __DIR__ . '/../includes/db.php';
echo "Database connection OK\n";
foreach (['users','kelas','siswa_kelas','guru_mapel','materi','tugas','pengumpulan_tugas','presensi','pengumuman'] as $table) {
    echo $table . ': ' . $pdo->query("SELECT COUNT(*) FROM `$table`")->fetchColumn() . "\n";
}
