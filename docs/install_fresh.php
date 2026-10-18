<?php
// Fresh installation only. Refuse existing schemas, never DROP or reset data.
if (PHP_SAPI !== 'cli') { http_response_code(404); exit; }
$name = getenv('EDULMS_DB_NAME') ?: 'edulms_db';
if (!preg_match('/^[a-zA-Z0-9_]+$/', $name)) exit("Invalid database name\n");
$pdo = new PDO('mysql:host=' . (getenv('EDULMS_DB_HOST') ?: 'localhost') . ';charset=utf8mb4', getenv('EDULMS_DB_USER') ?: 'root', getenv('EDULMS_DB_PASSWORD') ?: '', [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
$check = $pdo->prepare('SELECT COUNT(*) FROM information_schema.SCHEMATA WHERE SCHEMA_NAME = ?');
$check->execute([$name]);
if ($check->fetchColumn()) exit("Database already exists. No changes made.\n");
$source = file_get_contents(__DIR__ . '/../database/edulms_db.sql');
$schema = substr($source, 0, strpos($source, '-- SEED DATA - USERS'));
$schema = preg_replace('/DROP TABLE IF EXISTS [^;]+;/', '', $schema);
$schema = str_replace('edulms_db', $name, $schema);
$pdo->exec($schema);
$pdo->exec('SET FOREIGN_KEY_CHECKS = 1');
$pdo->exec(file_get_contents(__DIR__.'/../database/portal_actions.sql'));
$pass = password_hash('password', PASSWORD_DEFAULT);
$insert = $pdo->prepare('INSERT INTO users (nama_lengkap,email,password,role,nip) VALUES (?,?,?,?,?)');
$insert->execute(['Administrator EduLMS','admin@edulms.sch.id',$pass,'admin',null]);
$teachers = [
 ['Ahmad Dahlan','ahmad.dahlan@edulms.sch.id','198503152010011001'],
 ['Siti Nurhaliza','siti.nurhaliza@edulms.sch.id','199001102015032001'],
 ['Hendra Wijaya','hendra.wijaya@edulms.sch.id','198705202012011002'],
 ['Rina Anggraini','rina.anggraini@edulms.sch.id','199203152016032001'],
 ['Agus Prabowo','agus.prabowo@edulms.sch.id','198808102013011001'],
 ['Sulastri','sulastri@edulms.sch.id','197512052005032001'],
];
foreach ($teachers as [$nama,$email,$nip]) $insert->execute([$nama,$email,$pass,'guru',$nip]);
$pdo->exec("INSERT INTO kelas (nama_kelas,tingkat,jurusan,tahun_ajaran,wali_kelas_id) VALUES ('XI MIPA 1',11,'MIPA','2026/2027',2),('XI MIPA 2',11,'MIPA','2026/2027',3),('XI MIPA 3',11,'MIPA','2026/2027',4)");
$student = $pdo->prepare('INSERT INTO users (nama_lengkap,email,password,role,nisn) VALUES (?,?,?,\'siswa\',?)');
$enroll = $pdo->prepare("INSERT INTO siswa_kelas (siswa_id,kelas_id,tahun_ajaran,status) VALUES (?,1,'2026/2027','aktif')");
foreach ([['Budi Santoso','budi.demo@student.edulms.sch.id','9000000001'],['Aisyah Putri','aisyah.demo@student.edulms.sch.id','9000000002']] as [$nama,$email,$nisn]) {
 $student->execute([$nama,$email,$pass,$nisn]); $enroll->execute([$pdo->lastInsertId()]);
}
// Reuse subject definitions from existing SQL, without its hard-coded user IDs.
preg_match('/INSERT INTO mata_pelajaran .*?;\s/s', $source, $subjects);
$pdo->exec($subjects[0]);
$assignment = $pdo->prepare("INSERT INTO guru_mapel (guru_id,mapel_id,kelas_id,tahun_ajaran,hari,jam_mulai,jam_selesai,ruang) VALUES (?,?,1,'2026/2027','Senin','07:30:00','09:00:00','Ruang XI MIPA 1')");
for ($i=1;$i<=6;$i++) $assignment->execute([$i+1,$i]);
echo "Fresh schema and demo accounts installed in $name. Password: password. Change before production.\n";
