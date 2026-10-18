<?php
/** CLI-only, dedicated visual fixture. Never touches the application database. */
if (PHP_SAPI !== 'cli') { http_response_code(404); exit; }
$name = getenv('EDULMS_DB_NAME') ?: '';
if (!preg_match('/^edulms_qa_fidelity_[a-zA-Z0-9_]+$/', $name)) exit("Use EDULMS_DB_NAME=edulms_qa_fidelity_<name>\n");
$server = new PDO('mysql:host='.(getenv('EDULMS_DB_HOST') ?: 'localhost').';charset=utf8mb4', getenv('EDULMS_DB_USER') ?: 'root', getenv('EDULMS_DB_PASSWORD') ?: '', [PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION]);
$exists=$server->prepare('SELECT COUNT(*) FROM information_schema.SCHEMATA WHERE SCHEMA_NAME=?'); $exists->execute([$name]);
if ($exists->fetchColumn()) exit("Fixture already exists; no changes made.\n");
require __DIR__.'/install_fresh.php';
require __DIR__.'/../includes/db.php';
$pdo->beginTransaction();
try {
 $pdo->exec("UPDATE users SET nama_lengkap='Pak Ahmad Dahlan, S.Pd.' WHERE id=2");
 $names=[1=>'Matematika Wajib',2=>'Bahasa Indonesia',3=>'Fisika Terapan',4=>'Pemrograman Web & TIK',5=>'Bahasa Inggris',6=>'Biologi'];
 foreach ($names as $id=>$label) $pdo->prepare('UPDATE mata_pelajaran SET nama_mapel=? WHERE id=?')->execute([$label,$id]);
 // Five teaching assignments, each with a distinct cover. Student keeps six subjects in XI MIPA 1.
 $pdo->exec("UPDATE guru_mapel SET guru_id=2 WHERE id=3");
 $pdo->exec("INSERT INTO kelas (nama_kelas,tingkat,jurusan,tahun_ajaran,wali_kelas_id) VALUES ('X MIPA 1',10,'MIPA','2024/2025',3),('X MIPA 2',10,'MIPA','2024/2025',4)");
 $day=['Minggu','Senin','Selasa','Rabu','Kamis','Jumat','Sabtu'][(int)date('w')];
 $pdo->prepare('UPDATE guru_mapel SET hari=? WHERE id IN (1,3)')->execute([$day]);
 $pdo->exec("UPDATE guru_mapel SET jam_mulai='09:30:00' WHERE id=3");
 $pdo->exec("INSERT INTO guru_mapel (guru_id,mapel_id,kelas_id,tahun_ajaran,hari,jam_mulai,jam_selesai,ruang) VALUES (2,1,2,'2024/2025','Jumat','13:00','14:30','Ruang XI MIPA 2'),(2,1,4,'2024/2025','Senin','07:30','09:00','Ruang X MIPA 1'),(2,3,5,'2024/2025','Selasa','07:30','09:00','Lab Fisika')");
 $pass=password_hash('password',PASSWORD_DEFAULT);
 $add=$pdo->prepare("INSERT INTO users (nama_lengkap,email,password,role,nisn) VALUES (?,?,?,'siswa',?)");
 $enroll=$pdo->prepare("INSERT INTO siswa_kelas (siswa_id,kelas_id,tahun_ajaran) VALUES (?,?,'2024/2025')");
 $roster=['Ahmad Fajar Pratama','Annisa Rahmawati','Citra Dewi Lestari','Dimas Arya Putra','Dinda Putri Melati'];
 foreach ($roster as $i=>$label) { $add->execute([$label,'visual'.($i+1).'@student.edulms.test',$pass,'2024110'.($i+1)]);$enroll->execute([$pdo->lastInsertId(),1]); }
 foreach ([1=>29,2=>36,3=>35,4=>36,5=>35] as $class=>$count) for($i=0;$i<$count;$i++) { $add->execute([sprintf('Siswa %s %02d',$class,$i+1),"visual.c$class.s$i@student.edulms.test",$pass,sprintf('990%02d%05d',$class,$i)]);$enroll->execute([$pdo->lastInsertId(),$class]); }
 $material=$pdo->prepare('INSERT INTO materi (guru_mapel_id,judul_materi,deskripsi,urutan_modul,is_published) VALUES (?,?,?,?,1)');
 foreach ([1=>12,2=>10,3=>14,4=>16,5=>8,6=>11,7=>10,8=>8,9=>9] as $gm=>$count) for($i=1;$i<=$count;$i++) $material->execute([$gm,"Modul $i: ".($gm===1?'Matriks Transformasi Geometri':'Materi Pembelajaran'),'Bahan belajar untuk pengujian visual.',$i]);
 $task=$pdo->prepare('INSERT INTO tugas (guru_mapel_id,judul_tugas,deskripsi,batas_pengumpulan,format_file) VALUES (?,?,?,?,?)');
 $titles=[[1,'Latihan Soal Matriks & Vektor','+1 day','pdf'],[4,'Membuat Halaman Web Sederhana','+3 day','zip'],[2,'Ringkasan Teks Argumentasi','+5 day','docx,pdf'],[3,'Laporan Praktikum Kinematika','+7 day','pdf'],[5,'Essay Writing Narrative','+10 day','pdf'],[7,'Tugas Mandiri: Turunan Fungsi Aljabar Sederhana','+2 day','pdf'],[1,'Kuis Singkat Vektor Geometri Ruang Tiga Dimensi','-3 day','pdf']];
 foreach ($titles as [$gm,$label,$offset,$format]) $task->execute([$gm,$label,'Latihan evaluasi pembelajaran.',date('Y-m-d',strtotime($offset)).' 23:59:00',$format]);
 $submission=$pdo->prepare("INSERT INTO pengumpulan_tugas (tugas_id,siswa_id,file_path,file_name,file_size,nilai,tanggal_kumpul,dinilai_pada,dinilai_oleh) VALUES (?,?, 'visual-fixture.pdf','visual-fixture.pdf',0,?,?,?,2)");
 $submission->execute([5,8,90,date('Y-m-d H:i:s'),date('Y-m-d H:i:s')]);
 foreach ([1=>[32,20],4=>[34,26],6=>[28,24],7=>[36,36]] as $tid=>[$count,$graded]) {
  $class=$tid===6?2:1;$ids=$pdo->query("SELECT siswa_id FROM siswa_kelas WHERE kelas_id=$class AND siswa_id<>8 ORDER BY siswa_id")->fetchAll(PDO::FETCH_COLUMN);
  foreach(array_slice($ids,0,$count) as $i=>$sid) $submission->execute([$tid,$sid,$i<$graded?86.4:null,date('Y-m-d H:i:s'),$i<$graded?date('Y-m-d H:i:s'):null]);
 }
 $attendance=$pdo->prepare("INSERT INTO presensi (guru_mapel_id,siswa_id,tanggal_sesi,jam_sesi,status_kehadiran,jam_kedatangan,catatan,dicatat_oleh) VALUES (?,?,?,'07:30',?,?,?,2)");
 foreach([1,3,7] as $gm) { $class=$gm===7?2:1;$ids=$pdo->query("SELECT siswa_id FROM siswa_kelas WHERE kelas_id=$class")->fetchAll(PDO::FETCH_COLUMN);foreach($ids as $sid) $attendance->execute([$gm,$sid,date('Y-m-d'),$sid==12?'sakit':($sid==13?'izin':'hadir'),$sid==12||$sid==13?null:'07:25:00',$sid==12?'Surat dokter terlampir':($sid==13?'Dispensasi lomba olimpiade':'')]); }
 $notice=$pdo->prepare("INSERT INTO pengumuman (pembuat_id,judul_pengumuman,isi_pengumuman,kategori,target_role,tanggal_terbit) VALUES (1,?,?,?,'semua',?)");
 foreach ([['Jadwal Penilaian Tengah Semester (PTS) Genap','PTS Semester Genap akan dilaksanakan secara serentak mulai tanggal 3 Juni 2024 di ruang kelas masing-masing. Harap seluruh siswa mempersiapkan kartu ujian digital serta memverifikasi kelengkapan administrasi akademik sebelum tanggal pelaksanaan.','akademik','2024-05-15'],['Libur Nasional & Kegiatan Belajar Mandiri','Sehubungan dengan hari libur nasional pada Kamis mendatang, kegiatan belajar tatap muka ditiadakan dan dialihkan secara daring mandiri melalui portal EduLMS. Materi tugas terstruktur dapat diakses melalui tab Mata Pelajaran.','agenda','2024-05-12'],['Pendaftaran Ekstrakurikuler Robotik & Coding','Pendaftaran anggota baru club pemrograman dan robotik dibuka hingga akhir pekan ini melalui formulir online ruang kesiswaan. Kuota terbatas untuk 30 peserta angkatan pertama program semester gasal.','kegiatan','2024-05-08']] as $row) $notice->execute($row);
 $pdo->exec("INSERT INTO statistik_siswa (siswa_id,tahun_ajaran,waktu_belajar_jam) VALUES (8,'2024/2025',14.5)");
 $photo=$pdo->prepare('UPDATE users SET foto_profil=? WHERE nama_lengkap=?');
 foreach(['Ahmad Fajar Pratama','Annisa Rahmawati','Budi Santoso','Citra Dewi Lestari','Dimas Arya Putra','Dinda Putri Melati'] as $i=>$studentName) $photo->execute(['assets/images/reference/student-avatar-'.($i+1).'.png',$studentName]);
 $pdo->commit();
 echo "Representative visual fixture ready. Application database unchanged.\n";
} catch(Throwable $error) { $pdo->rollBack(); throw $error; }
