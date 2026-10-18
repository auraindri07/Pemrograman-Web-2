"""Read-only browser checks/captures for authenticated workflows.
Run with --base-url http://127.0.0.1:8101 --label portal-actions.
"""
import json
import visual_capture as v
records=[];checks=[]
def check(name,expression):
 passed=bool(v.js(expression));checks.append({'test':name,'passed':passed});print(('PASS ' if passed else 'FAIL ')+name,flush=True);assert passed,name
v.cdp('Runtime.enable');v.cdp('Page.enable');v.cdp('Emulation.setScrollbarsHidden',{'hidden':True})
for role,email,pages in [
 ('teacher','ahmad.dahlan@edulms.sch.id',['pengajuan_rombel.php','dukungan.php','guru_kelas.php']),
 ('student','budi.demo@student.edulms.sch.id',['bantuan.php','dukungan.php']),
 ('admin','admin@edulms.sch.id',['admin.php','pengajuan_rombel.php?status=all','dukungan.php'])]:
 v.args.width=1280;v.login(email,role={'teacher':'guru','student':'siswa','admin':'admin'}[role])
 for page in pages:
  for width in [1280,390]:
   v.args.width=width;v.goto('dashboard/'+page)
   name=role+'-'+page.split('.')[0]+'-'+str(width)
   row=v.shot(name);records.append(row);assert not row['overflow'] and not row['php_error'] and not row['js_errors'],row
   if role=='teacher' and page=='guru_kelas.php':check('Class request button opens real page '+str(width),"!!document.querySelector('.class-add-card a[href=\"pengajuan_rombel.php\"]')")
   if page.startswith('pengajuan_rombel') and role=='teacher':check('Request form required fields '+str(width),"document.querySelector('select[name=sumber_guru_mapel_id]').required&&document.querySelector('textarea[name=alasan]').required")
   if role=='admin' and page=='admin.php':check('Admin queues are discoverable '+str(width),"document.querySelectorAll('.admin-action-inbox a').length===2")
   if role=='admin' and page=='dukungan.php':check('Admin can respond through actual form '+str(width),"!!document.querySelector('form textarea[name=tanggapan]')")
   if role=='student' and page=='dukungan.php':check('Student form and visible reply '+str(width),"!!document.querySelector('form input[name=judul]')&&!!document.querySelector('.request-response')")
(v.out/'capture.json').write_text(json.dumps(records,indent=2),encoding='utf-8')
(v.out/'browser-checks.json').write_text(json.dumps(checks,indent=2),encoding='utf-8')
v.ws.close();print(len(records),'captures and',len(checks),'browser checks passed.')
