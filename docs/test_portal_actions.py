"""HTTP tests for authenticated actions, on a QA database only. No production writes."""
import json,os,re,time,subprocess,urllib.request,urllib.parse,urllib.error,http.cookiejar
from pathlib import Path
root=Path(__file__).resolve().parents[1]
assert os.environ.get('EDULMS_DB_NAME','').startswith('edulms_qa_')
base=os.environ.get('EDULMS_QA_URL','http://127.0.0.1:8101').rstrip('/')+'/'
results=[]
def check(name,condition):
 results.append({'test':name,'passed':bool(condition)});print(('PASS ' if condition else 'FAIL ')+name,flush=True);assert condition,name
def db(sql,params=[]):
 return json.loads(subprocess.check_output(['php',str(root/'docs/qa_query.php'),sql,json.dumps(params)],cwd=root,encoding='utf-8'))['rows']
class Client:
 def __init__(self):self.opener=urllib.request.build_opener(urllib.request.HTTPCookieProcessor(http.cookiejar.CookieJar()))
 def request(self,path,data=None):
  try:
   r=self.opener.open(urllib.request.Request(base+path,data=urllib.parse.urlencode(data).encode() if data is not None else None),timeout=20);status=r.status;body=r.read().decode('utf-8');url=r.url
  except urllib.error.HTTPError as e:status=e.code;body=e.read().decode('utf-8');url=e.url
  assert 'Fatal error' not in body and 'Warning:' not in body,body[:500]
  return status,body,url
 def get(self,path):return self.request(path)
 def post(self,path,data):
  token=re.search(r'name="csrf_token" value="([^"]+)"',self.get(path)[1]);return self.request(path,{**data,'csrf_token':token[1]} if token else data)
 def login(self,email,role='siswa'):
  token=re.search(r'name="csrf_token" value="([^"]+)"',self.get('auth/login.php')[1]);r=self.request('auth/process_login.php',{'identifier':email,'password':'password','role':role,'csrf_token':token[1]});assert '/dashboard/' in r[2]
a,t,s,o,anon=[Client() for _ in range(5)]
a.login('admin@edulms.sch.id','admin');t.login('ahmad.dahlan@edulms.sch.id','guru');s.login('budi.demo@student.edulms.sch.id');o.login('siti.nurhaliza@edulms.sch.id','guru')
tag=str(time.time_ns());name='QA Rombel '+tag;year='2026/2027'
check('Anonymous must log in', '/auth/login.php' in anon.get('dashboard/pengajuan_rombel.php')[2])
check('Students cannot request teacher classes',s.get('dashboard/pengajuan_rombel.php')[0]==403)
check('Class request rejects missing CSRF',t.request('dashboard/pengajuan_rombel.php',{'action':'create'})[0]==403)
check('Malformed class form rejected',t.request('dashboard/pengajuan_rombel.php',{'action':'create','nama_kelas[]':'invalid'})[0]==400)
gm=db('SELECT id FROM guru_mapel WHERE guru_id=2 AND is_active=1 ORDER BY id LIMIT 1')[0]['id']
foreign=db('SELECT id FROM guru_mapel WHERE guru_id=3 AND is_active=1 ORDER BY id LIMIT 1')[0]['id']
payload={'action':'create','sumber_guru_mapel_id':gm,'nama_kelas':name,'tingkat':11,'jurusan':'MIPA','tahun_ajaran':year,'alasan':'Kebutuhan kelas pengajaran QA terpisah.'}
t.post('dashboard/pengajuan_rombel.php',{**payload,'sumber_guru_mapel_id':foreign})
check('Other teacher assignment rejected',not db('SELECT id FROM pengajuan_rombel WHERE nama_kelas=?',[name]))
t.post('dashboard/pengajuan_rombel.php',{**payload,'tahun_ajaran':'2026/2029'})
check('Non-consecutive school year rejected',not db('SELECT id FROM pengajuan_rombel WHERE nama_kelas=?',[name]))
check('Request link opens actual form','name="sumber_guru_mapel_id"' in t.get('dashboard/pengajuan_rombel.php')[1])
t.post('dashboard/pengajuan_rombel.php',payload)
row=db('SELECT * FROM pengajuan_rombel WHERE nama_kelas=?',[name])[0];rid=row['id']
check('Teacher request stored',row['status']=='pending' and int(row['guru_id'])==2)
t.post('dashboard/pengajuan_rombel.php',payload)
check('Repeat submission creates no duplicate',len(db('SELECT id FROM pengajuan_rombel WHERE nama_kelas=?',[name]))==1)
check('Other teacher cannot see request',name not in o.get('dashboard/pengajuan_rombel.php')[1])
check('Teacher cannot approve requests',t.post('dashboard/pengajuan_rombel.php',{'action':'approve','id':rid})[0]==403)
before=db('SELECT COUNT(*) n FROM siswa_kelas')[0]['n']
a.post('dashboard/pengajuan_rombel.php',{'action':'approve','id':rid,'tanggapan':'Disetujui untuk pengujian.'})
row=db('SELECT * FROM pengajuan_rombel WHERE id=?',[rid])[0]
check('Approval persists decision and creates class',row['status']=='approved' and row['kelas_id'] is not None)
assignment=db('SELECT * FROM guru_mapel WHERE id=?',[row['guru_mapel_id']])[0]
check('Approved assignment belongs to requester',int(assignment['guru_id'])==2 and int(assignment['kelas_id'])==int(row['kelas_id']))
check('New class is visible to teacher',name in t.get('dashboard/guru_kelas.php')[1])
a.post('dashboard/pengajuan_rombel.php',{'action':'approve','id':rid,'tanggapan':'Ulang'})
check('Approval replay does not duplicate class',len(db('SELECT id FROM kelas WHERE nama_kelas=? AND tahun_ajaran=?',[name,year]))==1)
check('Approval does not move students',db('SELECT COUNT(*) n FROM siswa_kelas')[0]['n']==before)
rejected=name+' R';t.post('dashboard/pengajuan_rombel.php',{**payload,'nama_kelas':rejected});rejectid=db('SELECT id FROM pengajuan_rombel WHERE nama_kelas=?',[rejected])[0]['id']
a.post('dashboard/pengajuan_rombel.php',{'action':'reject','id':rejectid,'tanggapan':''})
check('Reject requires a reason',db('SELECT status FROM pengajuan_rombel WHERE id=?',[rejectid])[0]['status']=='pending')
a.post('dashboard/pengajuan_rombel.php',{'action':'reject','id':rejectid,'tanggapan':'Kapasitas saat ini mencukupi.'})
check('Rejection does not create a class',not db('SELECT id FROM kelas WHERE nama_kelas=?',[rejected]))
check('Teacher sees rejection response','Kapasitas saat ini mencukupi.' in t.get('dashboard/pengajuan_rombel.php?status=rejected')[1])
check('Help button links to ticket workflow','href="dukungan.php"' in s.get('dashboard/bantuan.php')[1])
check('Ticket creation requires CSRF',s.request('dashboard/dukungan.php',{'action':'create','judul':'Test','pesan':'Test pesan panjang'})[0]==403)
check('Malformed ticket form rejected',s.request('dashboard/dukungan.php',{'action':'create','pesan[]':'invalid'})[0]==400)
title='QA Tiket '+tag;message='Tidak dapat melihat materi. <script>alert(1)</script>'
s.post('dashboard/dukungan.php',{'action':'create','judul':title,'pesan':message})
ticket=db('SELECT * FROM tiket_bantuan WHERE judul=?',[title])[0];tid=ticket['id']
check('Student ticket stored',ticket['status']=='open' and int(ticket['user_id'])==8)
check('Other users cannot read ticket',title not in t.get('dashboard/dukungan.php')[1])
check('Ticket content is HTML-escaped','<script>alert(1)</script>' not in s.get('dashboard/dukungan.php')[1] and '&lt;script&gt;' in s.get('dashboard/dukungan.php')[1])
check('Student cannot post an admin reply',s.post('dashboard/dukungan.php',{'action':'reply','id':tid,'tanggapan':'Palsu','status':'closed'})[0]==403)
a.post('dashboard/dukungan.php',{'action':'reply','id':tid,'tanggapan':'Silakan muat ulang halaman materi.','status':'closed'})
check('Admin reply stored and closes ticket',db('SELECT status FROM tiket_bantuan WHERE id=?',[tid])[0]['status']=='closed')
check('Owner sees response','Silakan muat ulang halaman materi.' in s.get('dashboard/dukungan.php?status=closed')[1])
check('Closed ticket excluded from open filter',title not in s.get('dashboard/dukungan.php?status=open')[1])
check('Admin dashboard links to queues','href="pengajuan_rombel.php"' in a.get('dashboard/admin.php')[1] and 'href="dukungan.php"' in a.get('dashboard/admin.php')[1])
(root/'docs/portal-action-results.json').write_text(json.dumps(results,indent=2),encoding='utf-8');print(len(results),'checks passed.')
