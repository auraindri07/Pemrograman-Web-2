<?php
require_once '../includes/db.php';require_once '../includes/auth_check.php';require_once '../includes/functions.php';require_once '../includes/csrf.php';requireAuth();
$user_id=currentUserId();$isAdmin=currentUserRole()==='admin';$error='';$success=$_SESSION['support_success']??'';unset($_SESSION['support_success']);
if($_SERVER['REQUEST_METHOD']==='POST'){
    foreach($_POST as $value){if(!is_string($value)){http_response_code(400);exit('Form tidak valid.');}}
 checkCsrfToken();
 try{
  if(($_POST['action']??'')==='reply'){
   if(!$isAdmin){http_response_code(403);exit('Akses ditolak.');}
   $response=trim((string)($_POST['tanggapan']??''));$state=$_POST['status']??'closed';
   if(mb_strlen($response)<3||mb_strlen($response)>4000||!in_array($state,['open','closed'],true))throw new InvalidArgumentException('Isi tanggapan 3-4000 karakter dan status yang valid.');
   $q=$pdo->prepare('UPDATE tiket_bantuan SET tanggapan=?,status=?,admin_id=?,responded_at=NOW() WHERE id=?');$q->execute([$response,$state,$user_id,(int)($_POST['id']??0)]);if(!$q->rowCount())throw new InvalidArgumentException('Tiket tidak ditemukan atau tanggapan tidak berubah.');
  }elseif(($_POST['action']??'')==='create'){
   $title=trim((string)($_POST['judul']??''));$message=trim((string)($_POST['pesan']??''));
   if(mb_strlen($title)<3||mb_strlen($title)>150||mb_strlen($message)<10||mb_strlen($message)>4000)throw new InvalidArgumentException('Isi judul 3-150 karakter dan pesan 10-4000 karakter.');
   $q=$pdo->prepare('SELECT COUNT(*) FROM tiket_bantuan WHERE user_id=? AND created_at>DATE_SUB(NOW(),INTERVAL 10 MINUTE)');$q->execute([$user_id]);if($q->fetchColumn()>=5)throw new InvalidArgumentException('Tunggu beberapa menit sebelum mengirim tiket berikutnya.');
   $pdo->prepare('INSERT INTO tiket_bantuan (user_id,judul,pesan) VALUES (?,?,?)')->execute([$user_id,$title,$message]);
  }else{throw new InvalidArgumentException('Tindakan tidak valid.');}
  $_SESSION['support_success']='Tiket berhasil disimpan.';header('Location: dukungan.php');exit;
 }catch(InvalidArgumentException $e){$error=$e->getMessage();}
 catch(PDOException $e){error_log($e->getMessage());$error='Tiket belum dapat disimpan. Coba kembali.';}
}
$status=$_GET['status']??'all';if(!in_array($status,['all','open','closed'],true))$status='all';
$sql='SELECT t.*,u.nama_lengkap FROM tiket_bantuan t JOIN users u ON u.id=t.user_id WHERE 1=1';$params=[];if(!$isAdmin){$sql.=' AND t.user_id=?';$params[]=$user_id;}if($status!=='all'){$sql.=' AND t.status=?';$params[]=$status;}$q=$pdo->prepare($sql.' ORDER BY t.created_at DESC,t.id DESC');$q->execute($params);$pager=uiPaginate($q->fetchAll(),5);
$uiTitle='Tiket Bantuan';$uiPage='view-support';require '../includes/ui_head.php';
?>
<main class="ui-container"><section class="ui-page-heading"><div><span class="eyebrow teal">PUSAT BANTUAN</span><h1>Tiket Bantuan</h1><p><?= $isAdmin?'Tanggapi kendala pengguna dan tandai penyelesaiannya.':'Kirim kendala Anda dan lihat tanggapan administrator di halaman ini.' ?></p></div><a class="ui-button white" href="bantuan.php">Panduan Penggunaan</a></section>
<?php if($error||$success): ?><p class="ui-feedback" role="status"><?= e($error?:$success) ?></p><?php endif; ?>
<?php if(!$isAdmin): ?><section class="ui-card request-form-panel"><h2>Buat Tiket</h2><p>Jangan sertakan kata sandi atau kode rahasia dalam pesan.</p><form method="post" class="ui-form"><?= csrfField() ?><input type="hidden" name="action" value="create"><label>Judul<input name="judul" minlength="3" maxlength="150" required></label><label>Detail kendala<textarea name="pesan" minlength="10" maxlength="4000" rows="4" required></textarea></label><button class="ui-button primary">Kirim Tiket</button></form></section><?php endif; ?>
<div class="ui-toolbar"><nav class="ui-segmented"><?php foreach(['all'=>'Semua','open'=>'Terbuka','closed'=>'Selesai'] as $key=>$label): ?><a class="<?= $status===$key?'active':'' ?>" href="<?= uiQuery(['status'=>$key,'page'=>1]) ?>"><?= $label ?></a><?php endforeach; ?></nav></div><section class="request-list">
<?php foreach($pager['items'] as $ticket): ?><article class="ui-card request-item"><div class="section-heading"><div><h2><?= e($ticket['judul']) ?></h2><p>#<?= (int)$ticket['id'] ?> / <?= e($ticket['nama_lengkap']) ?> / <?= e(formatTanggalIndo($ticket['created_at'],'d M Y H:i')) ?></p></div><span class="ui-badge <?= $ticket['status']==='closed'?'aqua':'blue' ?>"><?= $ticket['status']==='closed'?'Selesai':'Terbuka' ?></span></div><p class="request-message"><?= nl2br(e($ticket['pesan'])) ?></p><?php if($ticket['tanggapan']): ?><p class="request-response"><strong>Tanggapan admin:</strong> <?= nl2br(e($ticket['tanggapan'])) ?></p><?php endif; ?><?php if($isAdmin): ?><form method="post" class="ui-form"><?= csrfField() ?><input type="hidden" name="action" value="reply"><input type="hidden" name="id" value="<?= (int)$ticket['id'] ?>"><label>Tanggapan<textarea name="tanggapan" minlength="3" maxlength="4000" required><?= e($ticket['tanggapan']) ?></textarea></label><label>Status<select name="status"><option value="closed">Selesai</option><option value="open" <?= $ticket['status']==='open'?'selected':'' ?>>Terbuka</option></select></label><button class="ui-button primary">Simpan Tanggapan</button></form><?php endif; ?></article><?php endforeach; ?><?php if(!$pager['items'])uiEmpty('Belum Ada Tiket','Tiket sesuai filter akan tampil di sini.'); ?></section><?php uiPagination($pager['page'],$pager['pages'],$pager['total'],'tiket'); ?></main><?php require '../includes/ui_end.php'; ?>
