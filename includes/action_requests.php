<?php
/** Authenticated request workflows; all writes keep the original academic records. */
function submitClassRequest(PDO $pdo,int $teacher,array $input): void {
    $source=(int)($input['sumber_guru_mapel_id']??0);
    $name=trim((string)($input['nama_kelas']??''));$level=(int)($input['tingkat']??0);
    $track=trim((string)($input['jurusan']??''));$year=trim((string)($input['tahun_ajaran']??''));
    $reason=trim((string)($input['alasan']??''));
    if($name===''||mb_strlen($name)>50||!in_array($level,[10,11,12],true)||mb_strlen($track)>50||mb_strlen($reason)<10||mb_strlen($reason)>2000)throw new InvalidArgumentException('Lengkapi nama kelas, tingkat, dan alasan 10-2000 karakter.');
    if(!preg_match('/^(\d{4})\/(\d{4})$/',$year,$m)||(int)$m[2]!==((int)$m[1]+1))throw new InvalidArgumentException('Tahun ajaran harus berurutan.');
    $q=$pdo->prepare('SELECT id FROM guru_mapel WHERE id=? AND guru_id=? AND is_active=1');$q->execute([$source,$teacher]);
    if(!$q->fetchColumn())throw new InvalidArgumentException('Pilih mata pelajaran yang Anda ampu.');
    $key=hash('sha256',$teacher.'|'.$source.'|'.$year.'|'.mb_strtolower($name));
    $q=$pdo->prepare('SELECT id FROM pengajuan_rombel WHERE request_key=?');$q->execute([$key]);
    if($q->fetchColumn())throw new InvalidArgumentException('Pengajuan yang sama masih menunggu keputusan admin.');
    $pdo->prepare('INSERT INTO pengajuan_rombel (guru_id,sumber_guru_mapel_id,nama_kelas,tingkat,jurusan,tahun_ajaran,alasan,request_key) VALUES (?,?,?,?,?,?,?,?)')->execute([$teacher,$source,$name,$level,$track?:null,$year,$reason,$key]);
}
function decideClassRequest(PDO $pdo,int $admin,int $id,string $action,string $response): void {
    if(!in_array($action,['approve','reject'],true)||mb_strlen($response)>2000||($action==='reject'&&mb_strlen(trim($response))<3))throw new InvalidArgumentException('Isi tanggapan penolakan, maksimal 2000 karakter.');
    $pdo->beginTransaction();
    try{
        $q=$pdo->prepare('SELECT * FROM pengajuan_rombel WHERE id=? FOR UPDATE');$q->execute([$id]);$request=$q->fetch();
        if(!$request||$request['status']!=='pending')throw new InvalidArgumentException('Pengajuan sudah diproses atau tidak ditemukan.');
        $class=$assignment=null;
        if($action==='approve'){
            $q=$pdo->prepare("SELECT gm.mapel_id FROM guru_mapel gm JOIN users u ON u.id=gm.guru_id WHERE gm.id=? AND gm.guru_id=? AND gm.is_active=1 AND u.is_active=1 AND u.role='guru'");$q->execute([$request['sumber_guru_mapel_id'],$request['guru_id']]);$subject=$q->fetchColumn();
            if(!$subject)throw new InvalidArgumentException('Penugasan sumber atau akun guru sudah tidak aktif.');
            $q=$pdo->prepare('SELECT id,tingkat,jurusan FROM kelas WHERE nama_kelas=? AND tahun_ajaran=? FOR UPDATE');$q->execute([$request['nama_kelas'],$request['tahun_ajaran']]);$existing=$q->fetch();
            if($existing){if((int)$existing['tingkat']!==(int)$request['tingkat']||($existing['jurusan']??'')!==($request['jurusan']??''))throw new InvalidArgumentException('Kelas bernama sama memiliki tingkat atau jurusan berbeda.');$class=(int)$existing['id'];}
            else{$pdo->prepare('INSERT INTO kelas (nama_kelas,tingkat,jurusan,tahun_ajaran) VALUES (?,?,?,?)')->execute([$request['nama_kelas'],$request['tingkat'],$request['jurusan'],$request['tahun_ajaran']]);$class=(int)$pdo->lastInsertId();}
            $q=$pdo->prepare('SELECT id,is_active FROM guru_mapel WHERE guru_id=? AND mapel_id=? AND kelas_id=? AND tahun_ajaran=?');$q->execute([$request['guru_id'],$subject,$class,$request['tahun_ajaran']]);$existing=$q->fetch();
            if($existing){if(!$existing['is_active'])throw new InvalidArgumentException('Penugasan sudah ada tetapi nonaktif. Aktifkan melalui Penugasan Guru.');$assignment=(int)$existing['id'];}
            else{$pdo->prepare('INSERT INTO guru_mapel (guru_id,mapel_id,kelas_id,tahun_ajaran) VALUES (?,?,?,?)')->execute([$request['guru_id'],$subject,$class,$request['tahun_ajaran']]);$assignment=(int)$pdo->lastInsertId();}
        }
        $pdo->prepare('UPDATE pengajuan_rombel SET status=?,request_key=NULL,tanggapan=?,admin_id=?,kelas_id=?,guru_mapel_id=?,processed_at=NOW() WHERE id=?')->execute([$action==='approve'?'approved':'rejected',trim($response)?:null,$admin,$class,$assignment,$id]);
        $pdo->commit();
    }catch(Throwable $error){if($pdo->inTransaction())$pdo->rollBack();throw $error;}
}
