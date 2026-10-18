<?php
/** Additive, repeatable local demo seed. CLI only; never resets tables/accounts. */
if (PHP_SAPI !== 'cli') { http_response_code(404); exit; }
require __DIR__.'/../includes/db.php';
require __DIR__.'/../includes/config.php';
if (!in_array('--apply', $argv, true)) {
    echo "Target: $dbname. Add ten dummy students and representative learning data, preserving original accounts.\nRun: php docs/seed_demo.php --apply\n";
    exit;
}
$marker='[EDULMS-DEMO-V1]';
$compact=!in_array('--large',$argv,true);
$lock=$pdo->query("SELECT GET_LOCK('edulms-demo-".preg_replace('/[^a-zA-Z0-9_]/','',$dbname)."',5)")->fetchColumn();
if (!$lock) throw new RuntimeException('Another demo seed is running.');
$inserted=[];
function demoFind(string $table, string $where, array $params): ?array {
    global $pdo;
    $stmt=$pdo->prepare("SELECT * FROM `$table` WHERE $where LIMIT 1");$stmt->execute($params);
    return $stmt->fetch() ?: null;
}
function demoInsert(string $table, array $values): int {
    global $pdo,$inserted;
    $columns=array_keys($values);
    $pdo->prepare("INSERT INTO `$table` (`".implode('`,`',$columns)."`) VALUES (".implode(',',array_fill(0,count($values),'?')).")")->execute(array_values($values));
    $id=(int)$pdo->lastInsertId();$inserted[$table][]=$id;return $id;
}
function demoPdf(string $relative,string $label): void {
    $path=dirname(__DIR__).'/'.$relative;
    if (is_file($path)) return;
    $stream="BT /F1 20 Tf 50 770 Td (EduLMS - Dokumen Demo) Tj 0 -35 Td /F1 12 Tf ($label) Tj 0 -24 Td (Data latihan untuk meninjau tampilan dan alur aplikasi.) Tj ET";
    $objects=['<< /Type /Catalog /Pages 2 0 R >>','<< /Type /Pages /Kids [3 0 R] /Count 1 >>','<< /Type /Page /Parent 2 0 R /MediaBox [0 0 595 842] /Resources << /Font << /F1 4 0 R >> >> /Contents 5 0 R >>','<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica >>',"<< /Length ".strlen($stream)." >>\nstream\n$stream\nendstream"];
    $pdf="%PDF-1.4\n";$offsets=[0];foreach($objects as $i=>$object){$offsets[]=strlen($pdf);$pdf.=($i+1)." 0 obj\n$object\nendobj\n";}
    $xref=strlen($pdf);$pdf.="xref\n0 6\n0000000000 65535 f \n";foreach(array_slice($offsets,1) as $offset)$pdf.=sprintf("%010d 00000 n \n",$offset);
    $pdf.="trailer\n<< /Size 6 /Root 1 0 R >>\nstartxref\n$xref\n%%EOF\n";
    if (file_put_contents($path,$pdf,LOCK_EX)===false) throw new RuntimeException('Cannot write demo document.');
}
try {
    $year=TAHUN_AJARAN_AKTIF;
    $teachers=$pdo->query("SELECT id FROM users WHERE role='guru' AND is_active=1 ORDER BY id LIMIT 6")->fetchAll(PDO::FETCH_COLUMN);
    $admin=$pdo->query("SELECT id FROM users WHERE role='admin' AND is_active=1 ORDER BY id LIMIT 1")->fetchColumn();
    $subjects=$pdo->query('SELECT id,nama_mapel FROM mata_pelajaran ORDER BY id LIMIT 6')->fetchAll();
    if(count($teachers)!==6||count($subjects)!==6||!$admin)throw new RuntimeException('Requires the six existing teachers/subjects and an administrator. No changes made.');
    // Protected, local backup before any database mutation. Do not overwrite previous backups.
    $backupDir=dirname(__DIR__).'/database/backups';if(!is_dir($backupDir))mkdir($backupDir,0775,true);
    $backupPath=$backupDir.'/before-demo-'.$dbname.'.json';
    if(!is_file($backupPath)){
        $backup=['database'=>$dbname,'created_at'=>date(DATE_ATOM),'tables'=>[]];
        foreach(['users','kelas','siswa_kelas','mata_pelajaran','guru_mapel','materi','tugas','pengumpulan_tugas','presensi','pengumuman','statistik_siswa'] as $table)$backup['tables'][$table]=$pdo->query("SELECT * FROM `$table`")->fetchAll();
        if(file_put_contents($backupPath,json_encode($backup,JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE),LOCK_EX)===false)throw new RuntimeException('Cannot save pre-seed backup.');
    }
    foreach(['materi'=>'Materi pembelajaran dan latihan mandiri.','tugas'=>'Lembar soal dan petunjuk pengerjaan.','pengumpulan'=>'Contoh jawaban siswa untuk latihan penilaian.'] as $type=>$label)demoPdf('uploads/'.$type.'/edulms-demo-v1.pdf',$label);
    $pdo->beginTransaction();
    // The user requested replacing our large demo with ten dummy students.
    // Remove only IDs recorded by our own earlier seed, children before parents.
    if($compact){
        foreach(glob($backupDir.'/demo-journal-'.$dbname.'-*.json') as $journalFile){
            $prior=json_decode(file_get_contents($journalFile),true);
            if(($prior['database']??null)!==$dbname||count($prior['inserted_ids']['users']??[])<=10)continue;
            $ids=$prior['inserted_ids'];
            foreach($ids['users'] as $id){$row=demoFind('users','id=?',[$id]);if($row&&!preg_match('/^demo\.kelas\d+\.siswa\d+@edulms\.test$/',$row['email']))throw new RuntimeException('Refusing to remove an unrelated account.');}
            foreach(['pengumpulan_tugas','presensi','statistik_siswa','pengumuman','materi','tugas','siswa_kelas','guru_mapel','kelas','users'] as $table){
                foreach(array_chunk($ids[$table]??[],500) as $chunk)$pdo->prepare("DELETE FROM `$table` WHERE id IN (".implode(',',array_fill(0,count($chunk),'?')).")")->execute($chunk);
            }
        }
    }
    $classes=[];
    foreach([['XI MIPA 1',11,36],['XI MIPA 2',11,36],['XI MIPA 3',11,35],['X MIPA 1',10,36],['X MIPA 2',10,35]] as $i=>[$name,$level,$capacity]){
        $class=demoFind('kelas','nama_kelas=? AND tahun_ajaran=?',[$name,$year]);
        $id=$class?(int)$class['id']:demoInsert('kelas',['nama_kelas'=>$name,'tingkat'=>$level,'jurusan'=>'MIPA','tahun_ajaran'=>$year,'wali_kelas_id'=>$teachers[$i%6],'kapasitas'=>$capacity]);
        $classes[]=['id'=>$id,'name'=>$name,'capacity'=>$capacity];
    }
    // Newly registered students with no class can now see the demo subjects too.
    $unassigned=$pdo->prepare("SELECT u.id FROM users u WHERE u.role='siswa' AND u.is_active=1 AND NOT EXISTS(SELECT 1 FROM siswa_kelas sk WHERE sk.siswa_id=u.id AND sk.tahun_ajaran=? AND sk.status='aktif')");$unassigned->execute([$year]);
    foreach($unassigned->fetchAll(PDO::FETCH_COLUMN) as $sid)demoInsert('siswa_kelas',['siswa_id'=>$sid,'kelas_id'=>$classes[0]['id'],'tahun_ajaran'=>$year,'status'=>'aktif']);
    $given=['Ahmad Fajar','Annisa','Citra Dewi','Dimas Arya','Dinda Putri','Farhan','Gita','Hafiz','Intan','Joko','Kirana','Lutfi','Maya','Nadia','Omar','Putri','Rafi','Salma','Taufik','Vina','Wahyu','Yasmin','Zahra','Aditya','Bagas','Dewi','Eka','Fadli','Galih','Hana','Irfan','Kevin','Laila','Naufal','Rizki','Sinta'];
    $family=['Pratama','Rahmawati','Lestari','Putra','Melati','Saputra','Anggraini','Maulana','Permata','Santoso','Wijaya','Ramadhan','Puspita','Azzahra','Hakim','Wulandari'];
    $password=password_hash('DemoEduLMS!2026',PASSWORD_DEFAULT);
    $rosters=[];
    foreach($classes as $ci=>$class){
        $q=$pdo->prepare("SELECT siswa_id FROM siswa_kelas WHERE kelas_id=? AND tahun_ajaran=? AND status='aktif' ORDER BY siswa_id");$q->execute([$class['id'],$year]);$roster=$q->fetchAll(PDO::FETCH_COLUMN);
        for($slot=1;$compact?$slot<=2:count($roster)<$class['capacity'];$slot++){
            $email=sprintf('demo.kelas%d.siswa%02d@edulms.test',$ci+1,$slot);$user=demoFind('users','email=?',[$email]);
            $sid=$user?(int)$user['id']:demoInsert('users',['nama_lengkap'=>$given[($slot-1)%count($given)].' '.$family[($slot+$ci*3-1)%count($family)],'email'=>$email,'password'=>$password,'role'=>'siswa','nisn'=>sprintf('88%02d%06d',$ci+1,$slot),'foto_profil'=>$ci===0&&$slot<=5?'assets/images/reference/student-avatar-'.($slot===1?1:($slot+1)).'.png':null]);
            if(!demoFind('siswa_kelas','siswa_id=? AND kelas_id=? AND tahun_ajaran=?',[$sid,$class['id'],$year]))demoInsert('siswa_kelas',['siswa_id'=>$sid,'kelas_id'=>$class['id'],'tahun_ajaran'=>$year,'status'=>'aktif']);
            if(!in_array($sid,$roster))$roster[]=$sid;
        }
        $rosters[$class['id']]=$roster;
    }
    $days=['Minggu','Senin','Selasa','Rabu','Kamis','Jumat','Sabtu'];$today=$days[(int)date('w')];$tomorrow=$days[(int)date('w',strtotime('+1 day'))];
    $topics=[['Konsep Matriks','Operasi Matriks','Determinan dan Invers','Transformasi Geometri','Vektor Ruang','Turunan Fungsi Aljabar'],['Teks Argumentasi','Struktur Paragraf','Menyusun Ringkasan','Teks Eksplanasi','Resensi Buku','Presentasi Ilmiah'],['Besaran dan Vektor','Gerak Lurus','Hukum Newton','Usaha dan Energi','Dinamika Partikel','Gelombang Elektromagnetik'],['Struktur HTML','Dasar CSS','Layout Responsif','JavaScript Interaktif','Formulir dan Validasi','Desain Antarmuka Web'],['Narrative Text','Grammar in Context','Reading Comprehension','Essay Writing','Listening Practice','Speaking Presentation'],['Struktur Sel','Jaringan Tumbuhan','Sistem Organ','Genetika Dasar','Ekosistem','Keanekaragaman Hayati']];
    $counts=$compact?[6,5,6,8,4,5]:[12,10,14,16,8,11];$dates=[];$cursor=strtotime('today');while(count($dates)<12){if((int)date('N',$cursor)<=5)$dates[]=date('Y-m-d',$cursor);$cursor=strtotime('-1 day',$cursor);}
    foreach($classes as $ci=>$class)foreach($subjects as $si=>$subject){
        $teacher=$si===0?(in_array($ci,[0,1,3])?$teachers[0]:$teachers[2]):($si===2?(in_array($ci,[2,4])?$teachers[0]:$teachers[2]):$teachers[$si]);
        // Preserve any existing teacher assignment for this class/subject/year.
        $assignment=demoFind('guru_mapel','kelas_id=? AND mapel_id=? AND tahun_ajaran=? AND is_active=1',[$class['id'],$subject['id'],$year]);
        $gm=$assignment?(int)$assignment['id']:demoInsert('guru_mapel',['guru_id'=>$teacher,'mapel_id'=>$subject['id'],'kelas_id'=>$class['id'],'tahun_ajaran'=>$year,'hari'=>$ci===1?$tomorrow:$today,'jam_mulai'=>$si===2?'09:30:00':'07:30:00','jam_selesai'=>$si===2?'11:00:00':'09:00:00','ruang'=>$si===2?'Laboratorium Fisika':'Ruang '.$class['name']]);
        if($assignment)$teacher=(int)$assignment['guru_id'];
        for($m=1;$m<=$counts[$si]+3;$m++){
            $title='Modul '.$m.': '.$topics[$si][($m-1)%6];
            if(!demoFind('materi','guru_mapel_id=? AND judul_materi=?',[$gm,$title]))demoInsert('materi',['guru_mapel_id'=>$gm,'judul_materi'=>$title,'deskripsi'=>$marker.' Pelajari konsep, contoh penyelesaian, dan latihan '.$subject['nama_mapel'].'.','konten'=>'<h2>'.$title.'</h2><p>Tujuan pembelajaran: memahami konsep dasar dan menerapkannya pada persoalan sehari-hari.</p><ol><li>Baca contoh pada dokumen terlampir.</li><li>Diskusikan hasil pengamatan bersama kelompok.</li><li>Kerjakan latihan dan tuliskan kesimpulan.</li></ol>','file_path'=>'uploads/materi/edulms-demo-v1.pdf','file_name'=>'Materi Pembelajaran.pdf','file_size'=>filesize(dirname(__DIR__).'/uploads/materi/edulms-demo-v1.pdf'),'urutan_modul'=>$m,'is_published'=>$m<=$counts[$si]?1:0,'published_at'=>$m<=$counts[$si]?date('Y-m-d H:i:s',strtotime('-'.($counts[$si]-$m+1).' days')):null]);
        }
        foreach(['Latihan '.$topics[$si][0],'Proyek '.$topics[$si][3],'Kuis '.$topics[$si][1]] as $kind=>$title){
            $task=demoFind('tugas','guru_mapel_id=? AND judul_tugas=?',[$gm,$title]);
            $deadline=$kind===2?date('Y-m-14 23:59:00',strtotime(date('Y-m-01').' -'.($si%5).' months')):date('Y-m-d',strtotime('+'.($kind===0?1+$si:7+$si).' days')).' 23:59:00';
            $tid=$task?(int)$task['id']:demoInsert('tugas',['guru_mapel_id'=>$gm,'judul_tugas'=>$title,'deskripsi'=>$marker.' Kerjakan '.$title.' secara mandiri. Unggah jawaban PDF beserta langkah dan kesimpulan.','instruksi'=>'Tuliskan nama dan kelas. Sertakan langkah pengerjaan dan sumber bacaan. Periksa kembali sebelum mengunggah.','file_path'=>'uploads/tugas/edulms-demo-v1.pdf','file_name'=>'Lembar Soal.pdf','batas_pengumpulan'=>$deadline,'format_file'=>'pdf','max_file_size'=>10485760,'bobot_nilai'=>100]);
            foreach($rosters[$class['id']] as $ri=>$sid){
                if(($kind===0&&$ri%5===0)||($kind===1&&$ri%3===0))continue;
                if(demoFind('pengumpulan_tugas','tugas_id=? AND siswa_id=?',[$tid,$sid]))continue;
                $graded=$kind===2||($kind===0&&$ri%4!==0);$late=$kind===2&&$ri%11===0;
                $submitted=$kind===2?date('Y-m-d H:i:s',strtotime($deadline.($late?' +2 hours':' -1 day'))):date('Y-m-d H:i:s',strtotime('-1 day'));
                // Historical quizzes give the chart five real monthly aggregates.
                $gradedAt=$graded?($kind===2?date('Y-m-d 14:00:00',strtotime($deadline.' +1 day')):date('Y-m-d H:i:s')):null;
                demoInsert('pengumpulan_tugas',['tugas_id'=>$tid,'siswa_id'=>$sid,'file_path'=>'uploads/pengumpulan/edulms-demo-v1.pdf','file_name'=>'Jawaban Siswa.pdf','file_size'=>filesize(dirname(__DIR__).'/uploads/pengumpulan/edulms-demo-v1.pdf'),'catatan'=>$marker.' Jawaban latihan dan hasil diskusi.','tanggal_kumpul'=>$submitted,'status'=>$late?'terlambat':'tepat_waktu','nilai'=>$graded?72+(($ri*7+$si*3)%27):null,'feedback'=>$graded?'Konsep sudah dipahami. Lengkapi langkah analisis dan perhatikan ketelitian perhitungan.':null,'dinilai_oleh'=>$graded?$teacher:null,'dinilai_pada'=>$gradedAt]);
            }
        }
        foreach($dates as $di=>$date)foreach($rosters[$class['id']] as $ri=>$sid){
            if(demoFind('presensi','guru_mapel_id=? AND siswa_id=? AND tanggal_sesi=?',[$gm,$sid,$date]))continue;
            $roll=($ri*7+$di*3+$si)%40;$status=$roll===0?'sakit':($roll===1?'izin':($roll===2?'alfa':'hadir'));
            demoInsert('presensi',['guru_mapel_id'=>$gm,'siswa_id'=>$sid,'tanggal_sesi'=>$date,'jam_sesi'=>$assignment['jam_mulai']??($si===2?'09:30:00':'07:30:00'),'status_kehadiran'=>$status,'jam_kedatangan'=>$status==='hadir'?($si===2?'09:25:00':sprintf('07:%02d:00',20+$ri%10)):null,'catatan'=>$status==='sakit'?'Surat dokter terlampir':($status==='izin'?'Dispensasi lomba olimpiade':($status==='alfa'?'Belum ada konfirmasi':'')),'dicatat_oleh'=>$teacher]);
        }
    }
    $notices=[['Batas Pengisian Nilai Tengah Semester (PTS)','Pastikan seluruh rekapitulasi nilai harian dan tugas terunggah sebelum akhir pekan ini ke sistem rapor elektronik terpadu sekolah.','penting','guru'],['Jadwal Penilaian Tengah Semester','Penilaian tengah semester dilaksanakan sesuai jadwal kelas masing-masing. Siapkan kartu ujian dan perlengkapan belajar.','akademik','semua'],['Libur Nasional dan Kegiatan Belajar Mandiri','Materi dan tugas terstruktur dapat diakses melalui tab Mata Pelajaran. Diskusikan kendala pembelajaran bersama guru pengampu.','agenda','semua'],['Pendaftaran Ekstrakurikuler Robotik dan Coding','Pendaftaran anggota baru dibuka untuk seluruh siswa. Kuota terbatas, hubungi pembina kegiatan untuk jadwal seleksi.','kegiatan','siswa'],['Pemeliharaan Portal Akademik','Pemeliharaan rutin dilakukan pada akhir pekan. Simpan jawaban dan unduh materi yang dibutuhkan sebelum jadwal pemeliharaan.','umum','semua'],['Pengumpulan Portofolio Semester','Unggah portofolio sesuai petunjuk tiap mata pelajaran dan pastikan seluruh dokumen dapat dibuka.','akademik','siswa'],['Rapat Koordinasi Wali Kelas','Guru dan wali kelas dimohon meninjau kehadiran, progres pembelajaran, serta tindak lanjut siswa binaan.','agenda','guru'],['Pekan Literasi Sekolah','Ikuti kegiatan membaca bersama, ulasan buku, dan presentasi karya pada pekan literasi sekolah.','kegiatan','semua'],['Pendataan Peserta Olimpiade','Siswa yang berminat mengikuti olimpiade dapat menghubungi guru pembina sesuai bidang masing-masing.','penting','siswa'],['Jadwal Konsultasi Akademik','Layanan konsultasi akademik tersedia setelah jam pelajaran. Daftarkan topik konsultasi melalui wali kelas.','umum','semua'],['Persiapan Praktikum Laboratorium','Bawa perlengkapan keselamatan dan baca panduan praktikum sebelum memasuki laboratorium.','akademik','siswa'],['Evaluasi Pembelajaran Bulanan','Rekap evaluasi bulanan dapat ditinjau melalui halaman penilaian dan ekspor nilai.','akademik','guru']];
    foreach($notices as $i=>[$title,$body,$category,$role])if(!demoFind('pengumuman','judul_pengumuman=?',[$title]))demoInsert('pengumuman',['pembuat_id'=>$admin,'judul_pengumuman'=>$title,'isi_pengumuman'=>$body.' '.$marker,'kategori'=>$category,'target_role'=>$role,'tanggal_terbit'=>date('Y-m-d H:i:s',strtotime('-'.$i.' days'))]);
    foreach($rosters as $roster)foreach($roster as $sid)if(!demoFind('statistik_siswa','siswa_id=? AND tahun_ajaran=?',[$sid,$year]))demoInsert('statistik_siswa',['siswa_id'=>$sid,'tahun_ajaran'=>$year,'waktu_belajar_jam'=>10+($sid%55)/10]);
    $pdo->commit();
    // A separate journal per run preserves the record IDs for review, including on reruns.
    $journal=['database'=>$dbname,'seed'=>$marker,'timestamp'=>date(DATE_ATOM),'inserted_counts'=>array_map('count',$inserted),'inserted_ids'=>$inserted];
    file_put_contents($backupDir.'/demo-journal-'.$dbname.'-'.date('Ymd-His').'.json',json_encode($journal,JSON_PRETTY_PRINT),LOCK_EX);
    echo json_encode(['database'=>$dbname,'inserted'=>array_map('count',$inserted),'dummy_account_password'=>'DemoEduLMS!2026','existing_passwords'=>'unchanged'],JSON_PRETTY_PRINT).PHP_EOL;
} catch(Throwable $error) { if($pdo->inTransaction())$pdo->rollBack();throw $error; }
finally { $pdo->query("SELECT RELEASE_LOCK('edulms-demo-".preg_replace('/[^a-zA-Z0-9_]/','',$dbname)."')"); }
