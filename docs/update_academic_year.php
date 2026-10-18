<?php
/** CLI migration: keep relationships intact when updating the demo academic year. */
if(PHP_SAPI!=='cli'){http_response_code(404);exit;}
require __DIR__.'/../includes/db.php';
if(!in_array('--apply',$argv,true))exit("Run php docs/update_academic_year.php --apply to update 2024/2025 to 2026/2027.\n");
$tables=['kelas'=>[],'siswa_kelas'=>['siswa_id','kelas_id'],'guru_mapel'=>['guru_id','mapel_id','kelas_id'],'statistik_siswa'=>['siswa_id']];
$backup=['database'=>$dbname,'created_at'=>date(DATE_ATOM),'from'=>'2024/2025','to'=>'2026/2027','tables'=>[]];
$pdo->beginTransaction();
try{
    foreach($tables as $table=>$keys){
        $backup['tables'][$table]=$pdo->query("SELECT * FROM `$table` WHERE tahun_ajaran='2024/2025' FOR UPDATE")->fetchAll();
        if($keys){
            $columns=implode(',',$keys);
            if($pdo->query("SELECT 1 FROM `$table` WHERE tahun_ajaran IN ('2024/2025','2026/2027') GROUP BY $columns HAVING COUNT(*)>1 LIMIT 1")->fetchColumn())throw new RuntimeException("Duplicate target year in $table; no rows changed.");
        }
    }
    $dir=dirname(__DIR__).'/database/backups';if(!is_dir($dir))mkdir($dir,0775,true);
    $path=$dir.'/before-year-2026-2027-'.preg_replace('/[^a-zA-Z0-9_]/','',$dbname).'.json';
    if(!is_file($path)&&file_put_contents($path,json_encode($backup,JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE),LOCK_EX)===false)throw new RuntimeException('Cannot save backup.');
    $counts=[];
    foreach($tables as $table=>$keys)$counts[$table]=$pdo->exec("UPDATE `$table` SET tahun_ajaran='2026/2027' WHERE tahun_ajaran='2024/2025'");
    $pdo->commit();echo json_encode(['database'=>$dbname,'updated'=>$counts],JSON_PRETTY_PRINT).PHP_EOL;
}catch(Throwable $error){if($pdo->inTransaction())$pdo->rollBack();throw $error;}
