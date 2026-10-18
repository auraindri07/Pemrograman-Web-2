<?php
/** Integration checks inside a rolled-back transaction on a separate QA database. */
if (PHP_SAPI !== 'cli' || !str_starts_with(getenv('EDULMS_DB_NAME') ?: '', 'edulms_qa_')) exit('QA CLI only');
require __DIR__.'/../includes/db.php';
require __DIR__.'/../includes/config.php';
require __DIR__.'/../includes/functions.php';
require __DIR__.'/../includes/student_grade_chart.php';
error_reporting(E_ALL);
set_error_handler(function($severity,$message,$file,$line) { throw new ErrorException($message,0,$severity,$file,$line); });
$checks = 0;
function expectChart(bool $ok, string $name): void {
    global $checks;
    if (!$ok) throw new RuntimeException($name);
    $checks++; echo "PASS $name\n";
}
$pdo->beginTransaction();
try {
    $tag = bin2hex(random_bytes(5));
    $user = $pdo->prepare("INSERT INTO users (nama_lengkap,email,password,role) VALUES ('QA Chart',?,?,'siswa')");
    $user->execute(["chart-$tag@example.test",password_hash('ChartTest!2026',PASSWORD_DEFAULT)]);
    $sid = (int)$pdo->lastInsertId();
    $user->execute(["chart-other-$tag@example.test",password_hash('ChartTest!2026',PASSWORD_DEFAULT)]);
    $otherSid = (int)$pdo->lastInsertId();
    $teacher = (int)$pdo->query("SELECT id FROM users WHERE role='guru' LIMIT 1")->fetchColumn();
    $subject = (int)$pdo->query('SELECT id FROM mata_pelajaran LIMIT 1')->fetchColumn();
    $assignments = [];
    foreach (['2026/2027','2025/2026'] as $year) {
        $pdo->prepare('INSERT INTO kelas (nama_kelas,tingkat,tahun_ajaran) VALUES (?,11,?)')->execute(["QA Chart $tag $year",$year]);
        $kid = (int)$pdo->lastInsertId();
        $pdo->prepare('INSERT INTO guru_mapel (guru_id,mapel_id,kelas_id,tahun_ajaran) VALUES (?,?,?,?)')->execute([$teacher,$subject,$kid,$year]);
        $assignments[$year] = (int)$pdo->lastInsertId();
    }
    $add = function(int $gm, string $at, ?float $grade, ?int $student=null, ?string $gradedAt=null, string $title='QA Task') use ($pdo,$sid): int {
        $pdo->prepare('INSERT INTO tugas (guru_mapel_id,judul_tugas,batas_pengumpulan) VALUES (?,?,?)')->execute([$gm,$title,$at]);
        $tid = (int)$pdo->lastInsertId();
        $pdo->prepare("INSERT INTO pengumpulan_tugas (tugas_id,siswa_id,file_path,file_name,file_size,tanggal_kumpul,nilai,dinilai_pada) VALUES (?,?,'qa-not-a-real-file.pdf','qa.pdf',0,?,?,?)")->execute([$tid,$student??$sid,$at,$grade,$gradedAt??$at]);
        return (int)$pdo->lastInsertId();
    };
    $now = new DateTimeImmutable('2026-10-16');
    $load = fn() => studentGradeChart($pdo,$sid,'2026/2027',$now);
    $gm = $assignments['2026/2027'];
    expectChart($load()['current']['points'] === [], 'New student has no fabricated grades');
    $add($gm,'2026-07-01 00:00:00',0);
    expectChart(array_column($load()['current']['points'],'value') === [0.0], 'Semester start inclusive; zero is a real grade');
    $add($gm,'2026-09-01 12:00:00',null);
    $add($gm,'2026-09-01 12:00:00',99,$otherSid);
    $add($gm,'2026-06-30 23:59:59',99);
    $add($gm,'2027-01-01 00:00:00',99);
    expectChart(count($load()['current']['points']) === 1, 'Ungraded, other students and dates outside semester excluded');
    $add($gm,'2026-09-02 09:00:00',80);
    $add($gm,'2026-09-03 09:00:00',90,title:'Task </script><script>bad()</script>');
    $points = $load()['current']['points'];
    expectChart(array_column($points,'value') === [0.0,80.0,90.0], 'Tasks in the same month remain separate points');
    expectChart($points[2]['subject'] !== '' && str_contains($points[2]['dateLabel'],'2026'), 'Tooltip includes task, subject and timestamp');
    expectChart(!str_contains(json_encode($load(),JSON_HEX_TAG|JSON_HEX_AMP|JSON_HEX_APOS|JSON_HEX_QUOT),'</script>'), 'Task titles cannot terminate inline JSON script');
    for ($i=0;$i<12;$i++) $add($gm,sprintf('2026-10-%02d 09:00:00',$i+1),60+$i);
    expectChart(array_column($load()['current']['points'],'value') === array_map('floatval',range(62,71)), 'Latest ten evaluations displayed in chronological order');
    $tie = $add($gm,'2026-10-12 09:00:00',95);
    $points = $load()['current']['points'];
    expectChart(end($points)['id'] === $tie, 'Equal grading timestamps have stable ID ordering');
    $oldGm = $assignments['2025/2026'];
    $add($oldGm,'2025-10-01 09:00:00',77);
    $add($oldGm,'2026-02-01 09:00:00',88);
    expectChart(array_column($load()['previous']['points'],'value') === [77.0], 'Previous period is the same semester in the previous academic year');
    $sem2 = studentGradeChart($pdo,$sid,'2026/2027',new DateTimeImmutable('2027-03-01'));
    expectChart($sem2['current']['start']==='2027-01-01' && $sem2['current']['end']==='2027-07-01', 'Second semester dates match configured academic year');
    expectChart(array_column($sem2['previous']['points'],'value') === [88.0], 'Second semester compares January-June of previous year');
    echo "$checks checks passed. All fixture rows rolled back.\n";
} finally {
    $pdo->rollBack();
}
