<?php
/**
 * EduLMS - Dashboard Guru
 * Teacher portal dashboard with real data from database
 */

require_once '../includes/db.php';
require_once '../includes/config.php';
require_once '../includes/auth_check.php';
require_once '../includes/functions.php';

// Require teacher authentication (portal akademik — admin TIDAK di sini)
requireTeacher();

$user_id = currentUserId();
$nama_lengkap = $_SESSION['nama_lengkap'] ?? 'Pak Guru';

// Get teacher statistics
$stmtStats = $pdo->prepare("
    SELECT 
        COUNT(DISTINCT gm.kelas_id) as total_kelas,
        COUNT(DISTINCT sk.siswa_id) as total_siswa,
        (SELECT COUNT(*) 
         FROM pengumpulan_tugas pt 
         JOIN tugas t ON pt.tugas_id = t.id
         JOIN guru_mapel gm2 ON t.guru_mapel_id = gm2.id
         WHERE gm2.guru_id = ? AND pt.nilai IS NULL) as tugas_belum_dinilai
    FROM guru_mapel gm
    LEFT JOIN siswa_kelas sk ON sk.kelas_id = gm.kelas_id AND sk.status = 'aktif' AND sk.tahun_ajaran=gm.tahun_ajaran
    WHERE gm.guru_id = ? AND gm.is_active = 1
");
$stmtStats->execute([$user_id, $user_id]);
$stats = $stmtStats->fetch();

// Get today's schedule
$hariIni = date('l');
$hariIndo = [
    'Monday' => 'Senin',
    'Tuesday' => 'Selasa', 
    'Wednesday' => 'Rabu',
    'Thursday' => 'Kamis',
    'Friday' => 'Jumat',
    'Saturday' => 'Sabtu',
    'Sunday' => 'Minggu'
];
$hari = $hariIndo[$hariIni] ?? 'Senin';
$besok = $hariIndo[date('l', strtotime('+1 day'))];

$stmtJadwal = $pdo->prepare("
    SELECT 
        gm.id as guru_mapel_id,
        gm.hari,
        gm.jam_mulai,
        gm.jam_selesai,
        gm.ruang,
        (SELECT judul_materi FROM materi m WHERE m.guru_mapel_id=gm.id AND m.is_published=1 ORDER BY urutan_modul DESC,id DESC LIMIT 1) AS latest_material,
        mp.nama_mapel,
        k.nama_kelas,
        (SELECT COUNT(*) FROM siswa_kelas WHERE kelas_id = k.id AND status = 'aktif') as jumlah_siswa
    FROM guru_mapel gm
    JOIN mata_pelajaran mp ON gm.mapel_id = mp.id
    JOIN kelas k ON gm.kelas_id = k.id
    WHERE gm.guru_id = ? AND gm.hari IN (?, ?) AND gm.is_active = 1
    ORDER BY FIELD(gm.hari, ?, ?), gm.jam_mulai

");
$stmtJadwal->execute([$user_id, $hari, $besok, $hari, $besok]);
$jadwalHariIni = $stmtJadwal->fetchAll();

// Get assignments needing grading
$stmtTugasPerluDinilai = $pdo->prepare("
    SELECT 
        t.id,
        t.judul_tugas,
        t.batas_pengumpulan,
        mp.nama_mapel,
        k.nama_kelas,
        (SELECT COUNT(*) FROM pengumpulan_tugas WHERE tugas_id = t.id) as jumlah_terkumpul,
        (SELECT COUNT(*) FROM siswa_kelas WHERE kelas_id = k.id AND status = 'aktif') as total_siswa
    FROM tugas t
    JOIN guru_mapel gm ON t.guru_mapel_id = gm.id
    JOIN mata_pelajaran mp ON gm.mapel_id = mp.id
    JOIN kelas k ON gm.kelas_id = k.id
    JOIN pengumpulan_tugas pt ON pt.tugas_id = t.id
    WHERE gm.guru_id = ? 
        AND t.is_published = 1
        AND pt.nilai IS NULL
    GROUP BY t.id
    ORDER BY t.batas_pengumpulan ASC
    LIMIT 3
");
$stmtTugasPerluDinilai->execute([$user_id]);
$tugasPerluDinilai = $stmtTugasPerluDinilai->fetchAll();

$jadwalMengajarHariIni = count(array_filter($jadwalHariIni, fn($session) => $session['hari'] === $hari));
$teacherAnnouncement = $pdo->query("SELECT id,judul_pengumuman,isi_pengumuman FROM pengumuman WHERE is_published=1 AND tanggal_terbit <= NOW() AND target_role IN ('guru','semua') ORDER BY tanggal_terbit DESC LIMIT 1")->fetch();
?>
<?php
$teacherClasses=$pdo->prepare('SELECT DISTINCT k.nama_kelas,k.tingkat FROM kelas k JOIN guru_mapel gm ON gm.kelas_id=k.id WHERE gm.guru_id=? AND gm.is_active=1 ORDER BY k.tingkat DESC,k.nama_kelas');$teacherClasses->execute([$user_id]);$teacherClassNames=$teacherClasses->fetchAll(PDO::FETCH_COLUMN);
$groupedNames=[];foreach($teacherClassNames as $className){if(preg_match('/^(.*) (\d+)$/u',$className,$parts))$groupedNames[$parts[1]][]=$parts[2];else $groupedNames[$className]=[];}
$teacherClassSummary=implode(' / ',array_map(fn($base,$numbers)=>$base.($numbers?' '.implode(', ',$numbers):''),array_keys($groupedNames),array_values($groupedNames)));

$attendance=$pdo->prepare("SELECT COUNT(*) AS total,SUM(p.status_kehadiran='hadir') AS hadir FROM presensi p JOIN guru_mapel gm ON gm.id=p.guru_mapel_id WHERE gm.guru_id=? AND p.tanggal_sesi>=DATE_SUB(CURDATE(),INTERVAL 6 DAY)");$attendance->execute([$user_id]);$attendance=$attendance->fetch();$teacherAttendance=calculatePercentage($attendance['hadir']??0,$attendance['total']);
require __DIR__.'/../includes/views/teacher_dashboard.php'; ?>
