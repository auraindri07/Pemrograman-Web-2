"""Read-only browser checks against the populated local app; no fixture mutation.
python docs/compact_demo_checks.py --base-url http://127.0.0.1:8102
"""
import json
from pathlib import Path
import visual_capture as v
checks=[]
def check(name,expression):
    passed=bool(v.js(expression));checks.append({'test':name,'passed':passed})
    print(('PASS ' if passed else 'FAIL ')+name,flush=True)
    assert passed,name
v.cdp('Runtime.enable');v.cdp('Page.enable');v.cdp('Emulation.setScrollbarsHidden',{'hidden':True})
v.login('demo.kelas1.siswa01@edulms.test','DemoEduLMS!2026')
v.goto('dashboard/index.php')
check('Dummy student has up to ten individual graded tasks',"gradePoints().length>0&&gradePoints().length<=10&&gradePoints().every(point=>typeof point.value==='number')&&line.data.datasets[0].data.length===gradePoints().length&&Object.keys(Chart.instances).length===2")
v.goto('dashboard/mata_pelajaran.php')
check('Dummy student sees six assigned subjects',"document.querySelectorAll('.subject-card').length===6")
v.goto('dashboard/tugas.php')
first=v.js("[...document.querySelectorAll('.task-actions>a')].map(a=>a.href)")
check('Task page has five cards and a next page',"document.querySelectorAll('.task-row').length===5&&!![...document.querySelectorAll('.ui-pagination a')].find(a=>a.textContent==='Selanjutnya')")
v.goto('dashboard/tugas.php?page=2')
check('Task next page contains different records',"[...document.querySelectorAll('.task-actions>a')].every(a=>!"+json.dumps(first)+".includes(a.href))")
v.goto('dashboard/tugas.php?filter=belum&page=999')
check('Out-of-range task page clamps and retains filter',"document.querySelectorAll('.task-row').length>0&&[...document.querySelectorAll('.task-row')].every(r=>r.textContent.includes('Belum Dikirim'))")
v.goto('dashboard/pengumuman.php')
check('Announcement page has three cards',"document.querySelectorAll('.announcement-row').length===3")
first=v.js("[...document.querySelectorAll('.announcement-row h2')].map(x=>x.textContent)")
v.goto('dashboard/pengumuman.php?page=2')
check('Announcement next page contains different records',"[...document.querySelectorAll('.announcement-row h2')].every(x=>!"+json.dumps(first)+".includes(x.textContent))")
v.goto('dashboard/mapel_detail.php?id=1')
check('Sample material PDF downloads through authorized endpoint',"(async()=>{const a=document.querySelector('a[href*=\"download.php?type=materi\"]');if(!a)return false;const r=await fetch(a.href);return r.ok&&(await r.text()).startsWith('%PDF-')})()")
v.login('ahmad.dahlan@edulms.sch.id',role='guru')
v.goto('dashboard/guru_kelas.php')
check('Teacher sees five populated class cards',"document.querySelectorAll('.class-card').length===5")
v.goto('dashboard/guru_penilaian.php')
check('Grading page has four cards',"document.querySelectorAll('.grading-task').length===4")
first=v.js("[...document.querySelectorAll('.grading-task h2')].map(x=>x.textContent+'|'+x.parentElement.querySelector('.meta-line').textContent)")
v.goto('dashboard/guru_penilaian.php?page=2')
check('Grading next page contains different records',"[...document.querySelectorAll('.grading-task h2')].every(x=>!"+json.dumps(first)+".includes(x.textContent+'|'+x.parentElement.querySelector('.meta-line').textContent))")
(Path(__file__).parent/'visual-diff/compact-demo-checks.json').write_text(json.dumps(checks,indent=2),encoding='utf-8')
v.ws.close()
