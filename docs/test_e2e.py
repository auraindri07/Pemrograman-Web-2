"""HTTP integration tests against a separate edulms_qa_* database only."""
import urllib.request,urllib.parse,urllib.error,http.cookiejar,re,json,subprocess,os,time
from pathlib import Path
root=Path(__file__).resolve().parents[1]
assert os.environ.get('EDULMS_DB_NAME','').startswith('edulms_qa_'), 'Use a separate QA database'
base=os.environ.get('EDULMS_QA_URL','http://127.0.0.1:8100').rstrip('/')+'/'
results=[]
def check(name,condition):
 results.append({'test':name,'passed':bool(condition)})
 print(('PASS ' if condition else 'FAIL ')+name,flush=True)
 if not condition: raise AssertionError(name)
def db(sql,params=[]):
 result=subprocess.check_output(['php',str(root/'docs/qa_query.php'),sql,json.dumps(params)],cwd=root,encoding='utf-8')
 return json.loads(result)
class Client:
 def __init__(self): self.opener=urllib.request.build_opener(urllib.request.HTTPCookieProcessor(http.cookiejar.CookieJar()))
 def get(self,path): return self.request(path)
 def request(self,path,data=None,file=None):
  headers={}
  if file:
   boundary='----EduLMSQABoundary'
   parts=[]
   for k,v in data.items(): parts.append(f'--{boundary}\r\nContent-Disposition: form-data; name="{k}"\r\n\r\n{v}\r\n'.encode())
   field,filename,content=file
   parts.append(f'--{boundary}\r\nContent-Disposition: form-data; name="{field}"; filename="{filename}"\r\nContent-Type: application/pdf\r\n\r\n'.encode()+content+b'\r\n')
   parts.append(f'--{boundary}--\r\n'.encode()); payload=b''.join(parts)
   headers['Content-Type']='multipart/form-data; boundary='+boundary
  else: payload=urllib.parse.urlencode(data).encode() if data is not None else None
  try:
   response=self.opener.open(urllib.request.Request(base+path,data=payload,headers=headers),timeout=20)
   code=response.status; body=response.read(); url=response.url
  except urllib.error.HTTPError as error: code=error.code;body=error.read();url=error.url
  content=body.decode('utf-8',errors='replace')
  if code==200 and ('Fatal error' in content or 'Warning:' in content): raise AssertionError(content[:1200])
  return code,content,url
 def post(self,path,data,file=None):
  content=self.get(path)[1]
  token=re.search(r'name="csrf_token" value="([^"]+)"',content)
  if token: data={**data,'csrf_token':token[1]}
  return self.request(path,data,file)
 def login(self,email,role='siswa'):
  response=self.post('auth/process_login.php',{'identifier':email,'password':'password','role':role})
  check('Login '+email,response[0]==200 and '/dashboard/' in response[2])

admin=Client();teacher=Client();student=Client();other=Client()
admin.login('admin@edulms.sch.id','admin');teacher.login('ahmad.dahlan@edulms.sch.id','guru');student.login('budi.demo@student.edulms.sch.id');other.login('siti.nurhaliza@edulms.sch.id','guru')
for page in ['admin','admin_pengguna','admin_kelas','admin_mapel','admin_penempatan','admin_penugasan','admin_pengumuman']:
 check('Admin page '+page,admin.get('dashboard/'+page+'.php')[0]==200)
check('Student cannot access admin',student.get('dashboard/admin.php')[0]==403)
check('CSRF rejected',teacher.request('dashboard/guru_presensi.php',{'action':'save'})[0]==403)
tag=str(int(time.time()))
email='qa'+tag+'@student.edulms.test'
body=admin.post('dashboard/admin_pengguna.php',{'action':'buat_pengguna','nama_lengkap':'QA Siswa '+tag,'email':email,'role':'siswa','password':'password','nisn':tag})[1]
sid=db('SELECT id FROM users WHERE email=?',[email])['rows'][0]['id'];check('Admin creates student',sid>0)
admin.post('dashboard/admin_kelas.php',{'action':'buat_kelas','nama_kelas':'QA Kelas '+tag,'tingkat':'11','jurusan':'MIPA','tahun_ajaran':'2024/2025','wali_kelas_id':'2'})
kid=db('SELECT id FROM kelas WHERE nama_kelas=?',['QA Kelas '+tag])['rows'][0]['id'];check('Admin creates class',kid>0)
admin.post('dashboard/admin_mapel.php',{'action':'buat_mapel','kode_mapel':'QA'+tag,'nama_mapel':'QA Mapel '+tag,'kategori':'wajib','icon_emoji':'📘','color_class':'teal','deskripsi':'Pengujian integrasi'})
mid=db('SELECT id FROM mata_pelajaran WHERE kode_mapel=?',['QA'+tag])['rows'][0]['id'];check('Admin creates subject',mid>0)
admin.post('dashboard/admin_penempatan.php',{'action':'tempatkan','siswa_id':sid,'kelas_id':kid,'tahun_ajaran':'2024/2025'})
check('Student enrollment stored',len(db('SELECT id FROM siswa_kelas WHERE siswa_id=? AND kelas_id=?',[sid,kid])['rows'])==1)
admin.post('dashboard/admin_penugasan.php',{'action':'tugaskan','guru_id':'2','mapel_id':mid,'kelas_id':kid,'tahun_ajaran':'2024/2025','hari':'Senin','jam_mulai':'07:30','ruang':'QA Lab'})
gm=db('SELECT id FROM guru_mapel WHERE kelas_id=? AND mapel_id=?',[kid,mid])['rows'][0]['id'];check('Teacher assignment stored',gm>0)
qa=Client();qa.login(email)
check('Assigned subject visible to student','QA Mapel '+tag in qa.get('dashboard/mata_pelajaran.php')[1])
pdf=b'%PDF-1.4\n1 0 obj<</Type/Catalog>>endobj\n%%EOF\n'
teacher.post('dashboard/guru_materi.php?guru_mapel_id='+str(gm),{'action':'add','guru_mapel_id':gm,'judul_materi':'QA Materi '+tag,'deskripsi':'Materi uji','video_url':'','urutan_modul':'1'},('file_materi','qa.pdf',pdf))
material=db('SELECT id FROM materi WHERE guru_mapel_id=?',[gm])['rows'][0]['id'];check('Material uploaded',material>0)
check('Student can download material',qa.get('dashboard/download.php?type=materi&id='+str(material))[1].startswith('%PDF'))
check('Other class cannot download material',student.get('dashboard/download.php?type=materi&id='+str(material))[0]==403)
deadline='2026-12-31T23:59'
created=teacher.post('dashboard/guru_buat_tugas.php',{'guru_mapel_id':gm,'judul_tugas':'QA Tugas '+tag,'deskripsi':'Latihan integrasi','instruksi':'Kumpulkan PDF','batas_pengumpulan':deadline,'format_file':'pdf','upload_limit_mb':'10','bobot_nilai':'100'},('file_soal','soal.pdf',pdf))
if not db('SELECT id FROM tugas WHERE guru_mapel_id=?',[gm])['rows']:
 print(re.sub('<[^>]+>',' ',created[1]),flush=True)
tid=db('SELECT id FROM tugas WHERE guru_mapel_id=?',[gm])['rows'][0]['id'];check('Assignment created',tid>0)
check('Assignment visible to student','QA Tugas '+tag in qa.get('dashboard/tugas.php')[1])
qa.post('dashboard/kumpulkan_tugas.php?id='+str(tid),{'catatan':'QA jawaban'},('file_tugas','jawaban.pdf',pdf))
submission=db('SELECT id FROM pengumpulan_tugas WHERE tugas_id=? AND siswa_id=?',[tid,sid])['rows'][0]['id'];check('Student submits file',submission>0)
check('Teacher downloads assignment archive',teacher.get('dashboard/download.php?type=arsip&id='+str(tid))[1].startswith('PK'))
check('Other teacher cannot download assignment archive',other.get('dashboard/download.php?type=arsip&id='+str(tid))[0]==403)
check('Student cannot download assignment archive',qa.get('dashboard/download.php?type=arsip&id='+str(tid))[0]==403)
teacher.post('dashboard/guru_penilaian.php?tugas_id='+str(tid),{'action':'grade','pengumpulan_id':submission,'nilai':'101','feedback':'invalid'})
check('Out-of-range grade rejected',db('SELECT nilai FROM pengumpulan_tugas WHERE id=?',[submission])['rows'][0]['nilai'] is None)
other.post('dashboard/guru_penilaian.php',{'action':'grade','pengumpulan_id':submission,'nilai':'10','feedback':'unauthorized'})
check('Other teacher cannot grade',db('SELECT nilai FROM pengumpulan_tugas WHERE id=?',[submission])['rows'][0]['nilai'] is None)
teacher.post('dashboard/guru_penilaian.php?tugas_id='+str(tid),{'action':'grade','pengumpulan_id':submission,'nilai':'88.5','feedback':'QA feedback tersinkron'})
check('Grade stored',float(db('SELECT nilai FROM pengumpulan_tugas WHERE id=?',[submission])['rows'][0]['nilai'])==88.5)
check('Grade and feedback visible to student','QA feedback tersinkron' in qa.get('dashboard/lihat_nilai.php?id='+str(tid))[1])
check('Dashboard reflects grade','88.5' in qa.get('dashboard/index.php')[1])
date='2026-10-15'
teacher.post('dashboard/guru_presensi.php?guru_mapel_id='+str(gm)+'&tanggal='+date,{'action':'save','guru_mapel_id':gm,'tanggal_sesi':date,'jam_sesi':'07:30:00',f'attendance[{sid}]':'hadir',f'arrival[{sid}]':'07:25',f'notes[{sid}]':'QA tepat waktu'})
check('Attendance stored',db('SELECT status_kehadiran FROM presensi WHERE guru_mapel_id=? AND siswa_id=?',[gm,sid])['rows'][0]['status_kehadiran']=='hadir')
check('Student attendance updated','QA Mapel '+tag in qa.get('dashboard/presensi.php?bulan=2026-10')[1])
check('Other teacher cannot read roster',other.get('dashboard/guru_presensi.php?guru_mapel_id='+str(gm))[0]==403)
teacher.post('dashboard/guru_presensi.php?guru_mapel_id='+str(gm),{'action':'save','guru_mapel_id':gm,'tanggal_sesi':date,'jam_sesi':'07:30:00','attendance[8]':'alfa'})
check('Foreign student attendance rejected',not db('SELECT id FROM presensi WHERE guru_mapel_id=? AND siswa_id=8',[gm])['rows'])
for target in ['semua','guru']:
 admin.post('dashboard/admin_pengumuman.php',{'action':'buat_pengumuman','judul':'QA '+target+' '+tag,'isi':'Isi lengkap pengumuman <script>alert(1)</script> '+tag,'kategori':'akademik','target_role':target,'is_published':'1'})
check('Published announcement visible','QA semua '+tag in qa.get('dashboard/pengumuman.php')[1])
check('Teacher-only announcement hidden from students','QA guru '+tag not in qa.get('dashboard/pengumuman.php')[1])
check('Announcement search filters real data','QA semua '+tag in qa.get('dashboard/pengumuman.php?search='+tag)[1])
check('Announcement category filter','QA semua '+tag not in qa.get('dashboard/pengumuman.php?kategori=agenda')[1])
check('Teacher grade export','QA Tugas '+tag in teacher.get('dashboard/ekspor.php?type=nilai')[1])
check('Teacher attendance export','QA tepat waktu' in teacher.get('dashboard/ekspor.php?type=presensi&bulan=2026-10')[1])
check('Student cannot export teacher data',qa.get('dashboard/ekspor.php?type=nilai')[0]==403)
check('Uploads blocked directly',qa.get('uploads/materi/')[0]==403)
check('Invalid attendance date rejected',teacher.get('dashboard/guru_presensi.php?tanggal=bad')[0]==400)
check('Invalid category handled',admin.post('dashboard/admin_mapel.php',{'action':'buat_mapel','kode_mapel':'BAD','nama_mapel':'Bad','kategori':'invalid'})[0]==200)
admin.post('dashboard/admin_mapel.php',{'action':'ubah_deskripsi','mapel_id':mid,'deskripsi':'QA deskripsi diperbarui'})
check('Admin updates subject',db('SELECT deskripsi FROM mata_pelajaran WHERE id=?',[mid])['rows'][0]['deskripsi']=='QA deskripsi diperbarui')
admin.post('dashboard/admin_kelas.php',{'action':'set_wali','kelas_id':kid,'wali_kelas_id':'3'})
check('Admin updates homeroom teacher',db('SELECT wali_kelas_id FROM kelas WHERE id=?',[kid])['rows'][0]['wali_kelas_id']==3)
admin.post('dashboard/admin_penugasan.php',{'action':'toggle_aktif','guru_mapel_id':gm})
check('Inactive subject hidden from student','QA Mapel '+tag not in qa.get('dashboard/mata_pelajaran.php')[1])
admin.post('dashboard/admin_penugasan.php',{'action':'toggle_aktif','guru_mapel_id':gm})
check('Assignment can be reactivated','QA Mapel '+tag in qa.get('dashboard/mata_pelajaran.php')[1])
admin.post('dashboard/admin_pengguna.php',{'action':'reset_password','user_id':sid,'password':'password'})
again=Client();again.login(email)
admin.post('dashboard/admin_pengguna.php',{'action':'toggle_aktif','user_id':sid})
check('Deactivated account loses active session','/auth/login.php' in again.get('dashboard/index.php')[2])
admin.post('dashboard/admin_pengguna.php',{'action':'toggle_aktif','user_id':sid})
announcement=db('SELECT id FROM pengumuman WHERE judul_pengumuman=?',['QA semua '+tag])['rows'][0]['id']
admin.post('dashboard/admin_pengumuman.php',{'action':'toggle_publish','pengumuman_id':announcement})
check('Unpublished announcement hidden','QA semua '+tag not in student.get('dashboard/pengumuman.php?search='+tag)[1])
admin.post('dashboard/admin_pengumuman.php',{'action':'toggle_publish','pengumuman_id':announcement})
check('Announcement detail HTML escaped','<script>alert(1)</script>' not in student.get('dashboard/pengumuman.php?search='+tag)[1])
admin.post('dashboard/admin_pengumuman.php',{'action':'hapus','pengumuman_id':announcement})
check('Admin deletes announcement',not db('SELECT id FROM pengumuman WHERE id=?',[announcement])['rows'])
teacher.post('dashboard/guru_presensi.php?guru_mapel_id='+str(gm)+'&tanggal='+date,{'action':'save','guru_mapel_id':gm,'tanggal_sesi':date,'jam_sesi':'07:30:00',f'attendance[{sid}]':'izin',f'notes[{sid}]':'QA izin'})
check('Attendance edits do not duplicate sessions',len(db('SELECT id FROM presensi WHERE guru_mapel_id=? AND siswa_id=? AND tanggal_sesi=?',[gm,sid,date])['rows'])==1)
check('Attendance edit visible to student','Izin' in qa.get('dashboard/presensi.php?bulan=2026-10')[1])
before=len(db('SELECT id FROM siswa_kelas WHERE siswa_id=?',[sid])['rows'])
admin.post('dashboard/admin_penempatan.php',{'action':'tempatkan','siswa_id':sid,'kelas_id':kid,'tahun_ajaran':'2025/2026'})
check('Mismatched enrollment year rejected',len(db('SELECT id FROM siswa_kelas WHERE siswa_id=?',[sid])['rows'])==before)
check('Logout clears session','/auth/login.php' in qa.get('auth/logout.php')[2] and '/auth/login.php' in qa.get('dashboard/index.php')[2])
(root/'docs/test-results.json').write_text(json.dumps(results,indent=2),encoding='utf-8')
print(f'{len(results)} checks passed. QA database and records retained for inspection.')
