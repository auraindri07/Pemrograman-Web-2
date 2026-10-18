<?php
/** Reproducible screenshot states in a new QA schema; never modifies application data. */
if (PHP_SAPI !== 'cli') { http_response_code(404); exit; }
$name=getenv('EDULMS_DB_NAME')?:'';
if ($name!=='edulms_qa_fidelity_reference_20261015') exit("Use the dedicated reference QA database.\n");
$source='edulms_qa_fidelity_20261015';
$pdo=new PDO('mysql:host='.(getenv('EDULMS_DB_HOST')?:'localhost').';charset=utf8mb4',getenv('EDULMS_DB_USER')?:'root',getenv('EDULMS_DB_PASSWORD')?:'',[PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION,PDO::ATTR_DEFAULT_FETCH_MODE=>PDO::FETCH_ASSOC]);
$exists=$pdo->query("SELECT COUNT(*) FROM information_schema.SCHEMATA WHERE SCHEMA_NAME='$name'")->fetchColumn();
if (!$exists) {
 $pdo->exec("CREATE DATABASE `$name` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
 $tables=$pdo->query("SHOW TABLES FROM `$source`")->fetchAll(PDO::FETCH_COLUMN);
 $pdo->exec("USE `$name`");$pdo->exec('SET FOREIGN_KEY_CHECKS=0');
 foreach($tables as $table){$ddl=$pdo->query("SHOW CREATE TABLE `$source`.`$table`")->fetch(PDO::FETCH_NUM)[1];$pdo->exec($ddl);$pdo->exec("INSERT INTO `$table` SELECT * FROM `$source`.`$table`");}
 $pdo->exec('SET FOREIGN_KEY_CHECKS=1');
 $pdo->exec('CREATE TABLE qa_visual_snapshot (id INT PRIMARY KEY, data JSON NOT NULL)');
 $pdo->beginTransaction();
 try {
  $pdo->exec("UPDATE users SET nama_lengkap=CASE id WHEN 3 THEN 'Bu Siti Nurhaliza' WHEN 4 THEN 'Dr. Hendra Wijaya' WHEN 5 THEN 'Bu Rina Anggraini' WHEN 6 THEN 'Pak Agus Prabowo' WHEN 7 THEN 'Dra. Sulastri' ELSE nama_lengkap END");
  $pdo->exec("UPDATE guru_mapel SET kelas_id=3,ruang='Ruang Kelas XI MIPA 3' WHERE id=3");
  $pdo->exec("UPDATE guru_mapel SET ruang='Ruang Lab Komputer 2' WHERE id=1");
  $physicsRoster=$pdo->query('SELECT siswa_id FROM siswa_kelas WHERE kelas_id=3 ORDER BY siswa_id')->fetchAll(PDO::FETCH_COLUMN);
  $physicsSubmissions=$pdo->query('SELECT id FROM pengumpulan_tugas WHERE tugas_id=4 ORDER BY id')->fetchAll(PDO::FETCH_COLUMN);
  foreach($physicsSubmissions as $i=>$submissionId)$pdo->prepare('UPDATE pengumpulan_tugas SET siswa_id=? WHERE id=?')->execute([$physicsRoster[$i],$submissionId]);
  $pdo->exec("INSERT INTO guru_mapel (guru_id,mapel_id,kelas_id,tahun_ajaran,hari,jam_mulai,jam_selesai,ruang) VALUES (4,3,1,'2024/2025','Senin','09:30','11:00','Laboratorium Fisika')");
  $physics=(int)$pdo->lastInsertId();
  $pdo->exec("INSERT INTO materi (guru_mapel_id,judul_materi,deskripsi,urutan_modul,is_published) SELECT $physics,judul_materi,deskripsi,urutan_modul,is_published FROM materi WHERE guru_mapel_id=3");
  $pdo->exec("UPDATE siswa_kelas SET kelas_id=2 WHERE siswa_id=9 AND kelas_id=1");
  // XI MIPA 2 already has 36 students: exchange one roster entry with XI MIPA 1.
  $exchange=(int)$pdo->query('SELECT MAX(siswa_id) FROM siswa_kelas WHERE kelas_id=2 AND siswa_id<>9')->fetchColumn();
  $pdo->exec("UPDATE siswa_kelas SET kelas_id=1 WHERE siswa_id=$exchange AND kelas_id=2");
  $insert=$pdo->prepare('INSERT INTO tugas (guru_mapel_id,judul_tugas,deskripsi,batas_pengumpulan,format_file) VALUES (?,?,?,?,?)');
  $extra=[];
  foreach([[$physics,'Laporan Praktikum Kinematika',7,'pdf'],[1,'Evaluasi Matriks Transformasi',14,'pdf'],[4,'Latihan Tata Letak Responsif',15,'zip'],[4,'Proyek Antarmuka Web',16,'zip'],[4,'Evaluasi Pemrograman',17,'zip'],[5,'Listening Comprehension',18,'pdf'],[6,'Struktur dan Fungsi Sel',19,'pdf'],[$physics,'Evaluasi Dinamika Partikel',20,'pdf'],[7,'Latihan Turunan Aljabar',21,'pdf'],[8,'Persamaan Kuadrat',22,'pdf'],[9,'Pengukuran dan Vektor',23,'pdf'],[9,'Evaluasi Besaran Fisika',24,'pdf'],[3,'Evaluasi Kinematika',25,'pdf']] as [$gm,$title,$days,$format]){
   $insert->execute([$gm,$title,'Data QA untuk reproduksi komponen referensi.',date('Y-m-d',strtotime("+$days days")).' 23:59:00',$format]);$extra[$title]=(int)$pdo->lastInsertId();
  }
  $copy=$pdo->prepare("INSERT IGNORE INTO pengumpulan_tugas (tugas_id,siswa_id,file_path,file_name,file_size,nilai,tanggal_kumpul,dinilai_pada,dinilai_oleh) VALUES (?,8,'visual-fixture.pdf','visual-fixture.pdf',0,86.4,NOW(),NOW(),2)");$copy->execute([7]);
  // Rebuild only this QA schema's attendance to respect the corrected class ownership.
  $pdo->exec('DELETE FROM presensi');
  $attendance=$pdo->prepare("INSERT INTO presensi (guru_mapel_id,siswa_id,tanggal_sesi,jam_sesi,status_kehadiran,jam_kedatangan,catatan,dicatat_oleh) VALUES (?,?,?,'07:30',?,?,?,2)");
  foreach([1=>1,3=>3,7=>2] as $gm=>$class){$roster=$pdo->query("SELECT siswa_id FROM siswa_kelas WHERE kelas_id=$class ORDER BY siswa_id")->fetchAll(PDO::FETCH_COLUMN);foreach($roster as $i=>$sid){$status=$gm===1&&$sid==12?'sakit':($gm===1&&$sid==13?'izin':($gm===3&&$i===0?'sakit':'hadir'));$note=$sid==12?'Surat dokter terlampir':($sid==13?'Dispensasi lomba olimpiade':($sid==10?'On-time':''));$attendance->execute([$gm,$sid,date('Y-m-d'),$status,$status==='hadir'?'07:25:00':null,$note]);}}
  $latest=[1=>'Bab 4 Matriks Transformasi Geometri',7=>'Bab 4 Turunan Fungsi Aljabar',3=>'Bab 5 Dinamika Partikel & Gravitasi',8=>'Bab 3 Persamaan Kuadrat',9=>'Bab 3 Pengukuran & Vektor'];
  foreach($latest as $gm=>$title)$pdo->prepare('UPDATE materi SET judul_materi=? WHERE guru_mapel_id=? ORDER BY urutan_modul DESC,id DESC LIMIT 1')->execute([$title,$gm]);
  $pdo->exec("UPDATE pengumuman SET kategori='penting' WHERE id=1");
  $pdo->prepare('INSERT INTO qa_visual_snapshot VALUES (1,?)')->execute([json_encode(['physics'=>$physics,'extra'=>$extra])]);
  $pdo->commit();
 }catch(Throwable $error){if($pdo->inTransaction())$pdo->rollBack();throw $error;}
} else {$pdo->exec("USE `$name`");}
$stage=$argv[1]??'base';
$state=json_decode($pdo->query('SELECT data FROM qa_visual_snapshot WHERE id=1')->fetchColumn(),true);
$pdo->beginTransaction();
try {
 if(!isset($state['dashboard_assignments'])) {
  $state['base_student_submissions']=$pdo->query('SELECT * FROM pengumpulan_tugas WHERE siswa_id=8')->fetchAll();
  $state['dashboard_assignments']=[];$state['dashboard_tasks']=[];
  foreach(['Sejarah','Geografi','Ekonomi','Sosiologi','Seni Budaya','Pendidikan Pancasila','PJOK','Pendidikan Agama'] as $i=>$subject) {
   $pdo->prepare("INSERT INTO mata_pelajaran (kode_mapel,nama_mapel,kategori,icon_emoji,color_class) VALUES (?,?,'wajib','','teal')")->execute(['QA-VIS-D'.($i+1),$subject]);$mid=(int)$pdo->lastInsertId();
   $pdo->prepare("INSERT INTO guru_mapel (guru_id,mapel_id,kelas_id,tahun_ajaran,is_active) VALUES (3,?,1,'2024/2025',0)")->execute([$mid]);$gm=(int)$pdo->lastInsertId();$state['dashboard_assignments'][]=$gm;
   $pdo->prepare("INSERT INTO tugas (guru_mapel_id,judul_tugas,batas_pengumpulan,format_file) VALUES (?,?,?,'pdf')")->execute([$gm,'Evaluasi '.$subject,date('Y-m-d',strtotime('+30 days')).' 23:59:00']);$state['dashboard_tasks'][]=(int)$pdo->lastInsertId();
  }
  $pdo->exec('UPDATE users SET nisn=NULL WHERE id IN (8,10,11,12,13,14)');
  foreach([10=>'20241101',11=>'20241102',8=>'20241103',12=>'20241104',13=>'20241105',14=>'20241106'] as $sid=>$nisn)$pdo->prepare('UPDATE users SET nisn=? WHERE id=?')->execute([$nisn,$sid]);
  foreach([11=>'07:28:00',8=>'07:20:00',14=>'07:29:00'] as $sid=>$arrival)$pdo->prepare('UPDATE presensi SET jam_kedatangan=? WHERE guru_mapel_id=1 AND siswa_id=? AND tanggal_sesi=CURDATE()')->execute([$arrival,$sid]);
  $attendance=$pdo->prepare("INSERT INTO presensi (guru_mapel_id,siswa_id,tanggal_sesi,jam_sesi,status_kehadiran,jam_kedatangan,catatan,dicatat_oleh) VALUES (?,?,?,'07:30',?,?,?,2)");
  for($i=0;$i<47;$i++)$attendance->execute([1,8,date('Y-m-d',strtotime('2024-01-01 +'.$i.' days')),$i===0?'sakit':'hadir',$i===0?null:'07:20:00','']);
  $roster=$pdo->query('SELECT siswa_id FROM siswa_kelas WHERE kelas_id=1 ORDER BY siswa_id LIMIT 33')->fetchAll(PDO::FETCH_COLUMN);
  foreach($roster as $i=>$sid)$attendance->execute([1,$sid,date('Y-m-d',strtotime('-1 day')),$i<2?'sakit':'hadir',$i<2?null:'07:25:00','']);
  $pdo->prepare('UPDATE qa_visual_snapshot SET data=? WHERE id=1')->execute([json_encode($state)]);
 }
 // Each view is a separate snapshot, all rendered by the ordinary controllers/queries.
 $pdo->exec('DELETE FROM pengumpulan_tugas WHERE siswa_id=8');
 foreach($state['base_student_submissions'] as $row){$columns=array_keys($row);$pdo->prepare('INSERT INTO pengumpulan_tugas (`'.implode('`,`',$columns).'`) VALUES ('.implode(',',array_fill(0,count($row),'?')).')')->execute(array_values($row));}
 $dashboardIds=implode(',',$state['dashboard_assignments']);
 $pdo->exec("UPDATE guru_mapel SET is_active=0 WHERE id IN ($dashboardIds)");
 $pdo->exec('UPDATE tugas SET is_published=1');
 $deadline=$pdo->prepare('UPDATE tugas SET batas_pengumpulan=? WHERE id=?');
 foreach([1=>1,2=>3,3=>5,4=>7,5=>10,6=>2,7=>-3,$state['extra']['Laporan Praktikum Kinematika']=>7] as $id=>$days) $deadline->execute([date('Y-m-d',strtotime(($days>=0?'+':'').$days.' days')).' 23:59:00',$id]);
 if($stage==='tugas'){
  $ids=[1,2,3,5,$state['extra']['Laporan Praktikum Kinematika']];$pdo->exec('UPDATE tugas SET is_published=0 WHERE id NOT IN ('.implode(',',$ids).')');
 }
 if($stage==='index') {
  $pdo->exec("UPDATE guru_mapel SET is_active=1 WHERE id IN ($dashboardIds)");
  $pdo->exec('UPDATE tugas SET is_published=0 WHERE id='.(int)$state['extra']['Evaluasi Matriks Transformasi']);
  $studentTasks=$pdo->query("SELECT t.id FROM tugas t JOIN guru_mapel gm ON gm.id=t.guru_mapel_id WHERE gm.kelas_id=1 AND gm.is_active=1 AND t.is_published=1 ORDER BY t.id")->fetchAll(PDO::FETCH_COLUMN);
  $pending=[2,$state['extra']['Laporan Praktikum Kinematika']];
  $scores=[80,82,85,85,85,87,87,87,87,92,92,92,92,92,92,92,92,92];
  $months=[1,2,3,3,3,4,4,4,4,5,5,5,5,5,5,5,5,5];$index=0;
  $pdo->exec('DELETE FROM pengumpulan_tugas WHERE siswa_id=8');
  foreach($studentTasks as $tid){if(in_array((int)$tid,$pending,true))continue;$date=sprintf('2024-%02d-15 14:20:00',$months[$index]);$pdo->prepare("INSERT INTO pengumpulan_tugas (tugas_id,siswa_id,file_path,file_name,file_size,nilai,tanggal_kumpul,dinilai_pada,dinilai_oleh) VALUES (?,8,'visual-fixture.pdf','visual-fixture.pdf',0,?,?,?,2)")->execute([$tid,$scores[$index],$date,$date]);$index++;}
  $deadline->execute([date('Y-m-d').' 23:59:00',$state['extra']['Laporan Praktikum Kinematika']]);
  $deadline->execute([date('Y-m-d',strtotime('+1 day')).' 15:00:00',1]);
  $deadline->execute([date('Y-m-d',strtotime('+2 day')).' 20:00:00',2]);
  foreach([1=>'Modul 6: Integral Kalkulus Dasar',4=>'Modul 8: Desain UI Interaktif Tailwind',$state['physics']=>'Modul 4: Gelombang Elektromagnetik'] as $gm=>$title)$pdo->prepare('UPDATE materi SET judul_materi=? WHERE guru_mapel_id=? ORDER BY urutan_modul DESC,id DESC LIMIT 1')->execute([$title,$gm]);
 }
 if($stage!=='index')foreach([1=>'Bab 4 Matriks Transformasi Geometri',4=>'Modul 16: Materi Pemrograman',$state['physics']=>'Materi Fisika Terapan'] as $gm=>$title)$pdo->prepare('UPDATE materi SET judul_materi=? WHERE guru_mapel_id=? ORDER BY urutan_modul DESC,id DESC LIMIT 1')->execute([$title,$gm]);
 if(in_array($stage,['guru','guru_penilaian'],true)){
  $pdo->exec('UPDATE tugas SET is_published=0 WHERE id NOT IN (1,4,6,7)');
  $deadline->execute([date('Y-m-d',strtotime('-1 day')).' 23:59:00',1]);$deadline->execute([date('Y-m-d',strtotime('-2 day')).' 23:59:00',4]);
  $pdo->prepare('UPDATE tugas SET judul_tugas=? WHERE id=1')->execute(['Latihan Soal Matriks & Perkalian Vektor']);
 } else {$pdo->exec("UPDATE tugas SET judul_tugas='Latihan Soal Matriks & Vektor' WHERE id=1");}
 $pdo->commit();echo "Reference fixture: $stage ready in $name.\n";
}catch(Throwable $error){$pdo->rollBack();throw $error;}
