"""Read-only report checks and Chrome PDF generation. Requires existing local servers/CDP.

python docs/print_report_checks.py --base-url http://127.0.0.1:8102 --label print-review
"""
import base64
import http.cookiejar
import json
import re
import urllib.error
import urllib.parse
import urllib.request
from PyPDF2 import PdfReader
import visual_capture as v

checks = []
captures = []

def check(name, condition):
    checks.append({'test': name, 'passed': bool(condition)})
    print(('PASS ' if condition else 'FAIL ') + name, flush=True)
    assert condition, name

class Client:
    def __init__(self):
        self.opener = urllib.request.build_opener(urllib.request.HTTPCookieProcessor(http.cookiejar.CookieJar()))

    def get(self, path, data=None):
        try:
            response = self.opener.open(v.args.base_url+'/'+path, urllib.parse.urlencode(data).encode() if data is not None else None, timeout=20)
        except urllib.error.HTTPError as error:
            response = error
        body = response.read().decode('utf-8')
        check('No PHP error: '+path, not any(term in body for term in ['Fatal error', 'Warning:', 'Parse error']))
        return response.code, body, response.url

    def login(self, role, email):
        token = re.search(r'name="csrf_token" value="([^"]+)"',self.get('auth/login.php')[1])[1]
        response = self.get('auth/process_login.php', {'csrf_token': token, 'role': role, 'identifier': email, 'password': 'password'})
        check(role+' login succeeds', '/dashboard/' in response[2])

anon, student, teacher = Client(), Client(), Client()
check('Anonymous cannot print teacher reports', '/auth/login.php' in anon.get('dashboard/cetak.php')[2])
student.login('siswa', 'budi.demo@student.edulms.sch.id')
check('Students cannot print teacher reports', student.get('dashboard/cetak.php?type=nilai')[0] == 403)
teacher.login('guru', 'ahmad.dahlan@edulms.sch.id')
for query in ['type=invalid', 'type[]=nilai', 'type=presensi&bulan=2026-13', 'type=presensi&bulan[]=2026-10', 'kelas[]=1', 'tugas_id=abc']:
    check('Malformed report filter rejected: '+query, teacher.get('dashboard/cetak.php?'+query)[0] == 400)
for query in ['kelas=999999999', 'tugas_id=999999999']:
    check('Unowned resource rejected: '+query, teacher.get('dashboard/cetak.php?'+query)[0] == 403)

v.cdp('Runtime.enable')
v.cdp('Page.enable')
v.login('ahmad.dahlan@edulms.sch.id',role='guru')
for page, link in [('guru_kelas.php','type=kelas'), ('guru_presensi.php','type=presensi'), ('guru_penilaian.php','type=nilai')]:
    v.goto('dashboard/'+page)
    check('Report button available: '+page, v.js('!!document.querySelector('+json.dumps('a[href*="cetak.php?'+link+'"]')+')'))
    captures.append(v.shot('screen-'+page.split('.')[0]))

def pdf(name):
    v.cdp('Emulation.setEmulatedMedia', {'media': 'print'})
    data = v.cdp('Page.printToPDF', {'printBackground': True, 'preferCSSPageSize': True, 'displayHeaderFooter': False})
    path = v.out/(name+'.pdf')
    path.write_bytes(base64.b64decode(data['data']))
    reader = PdfReader(path)
    texts = [page.extract_text() for page in reader.pages]
    check(name+' uses A4 portrait', all(abs(float(page.mediabox.width)-595.28)<2 and abs(float(page.mediabox.height)-841.89)<2 for page in reader.pages))
    check(name+' excludes screen controls', all('Simpan sebagai PDF' not in text and 'Kembali' not in text for text in texts))
    check(name+' has no blank PDF page', all(len(text.strip()) > 30 for text in texts))
    if len(texts)>1:
        names = v.js('Array.from(document.querySelectorAll(".report-table tbody tr td:nth-child(2)")).map(td=>td.innerText.split("\\n")[0])')
        if names:
            check(name+' has no trailing page with only document notes', any(student in texts[-1] for student in names))
    print(name, len(reader.pages), 'PDF pages', flush=True)
    return texts

for kind in ['kelas','presensi','nilai']:
    v.cdp('Emulation.setEmulatedMedia', {'media': 'screen'})
    v.goto('dashboard/cetak.php?type='+kind+'&bulan=2026-10')
    check(kind+' report has no pagination', v.js('!document.querySelector(".ui-pagination,.attendance-pagination")'))
    check(kind+' report escapes content without JS errors', not v.errors)
    captures.append(v.shot(kind+'-preview'))
    v.cdp('Emulation.setEmulatedMedia', {'media': 'print'})
    check(kind+' hides print toolbar', v.js('getComputedStyle(document.querySelector(".report-toolbar")).display==="none"'))
    check(kind+' fits printable width', v.js('Array.from(document.querySelectorAll(".report-table")).every(table=>table.getBoundingClientRect().right<=document.querySelector(".report-sheet").getBoundingClientRect().right+1)'))
    texts = pdf(kind)
    if kind == 'kelas' and v.js('document.querySelectorAll(".report-table tbody tr").length') <= 5:
        check('Short class report fits one page without orphan footer', len(texts) == 1)
    check(kind+' PDF includes report title', v.js('document.querySelector("h1").textContent') in texts[0])
    expected = v.js('Array.from(document.querySelectorAll(".report-table tbody tr td:nth-child(2)")).map(td=>td.innerText.split("\\n")[0])')
    all_text = '\n'.join(texts)
    check(kind+' PDF includes all table names', all(name in all_text for name in expected))
    if kind == 'nilai':
        # Stress layout only in browser memory: a long table and wrapped feedback.
        v.js("""(()=>{const table=document.querySelector('.report-table');if(!table)return;document.querySelectorAll('.report-table').forEach(t=>{if(t!==table)t.remove()});const body=table.tBodies[0],row=body.rows[0].cloneNode(true);body.replaceChildren();for(let i=1;i<=90;i++){const clone=row.cloneNode(true);clone.cells[0].textContent=i;clone.cells[1].textContent='Siswa Cetak '+String(i).padStart(3,'0');clone.cells[5].textContent='Umpan balik pengujian: langkah pengerjaan dan hasil sudah diperiksa. '.repeat(i%7===0?6:1);body.append(clone)}})()""")
        if v.js('!!document.querySelector(".report-table")'):
            stress = pdf('nilai-multipage-stress')
            check('Stress PDF contains 90 rows across multiple pages', len(stress)>2 and all('Siswa Cetak '+str(i).zfill(3) in '\n'.join(stress) for i in range(1,91)))
            check('Multi-page table repeats its column headers', all('Nama siswa' in text for text in stress if 'Siswa Cetak' in text))

v.cdp('Emulation.setEmulatedMedia', {'media': 'screen'})
v.goto('dashboard/cetak.php?type=presensi&bulan=1900-01')
check('Empty report has clear explanation', v.js('!!document.querySelector(".report-empty")'))
pdf('presensi-empty')
# Browser Print fallback must reveal saved DOM rows hidden by client pagination.
v.goto('dashboard/guru_presensi.php')
v.cdp('Emulation.setEmulatedMedia', {'media':'print'})
check('Fallback print hides toolbar and pagination', v.js('Array.from(document.querySelectorAll(".portal-header,.attendance-pagination,.attendance-toolbar")).every(el=>getComputedStyle(el).display==="none")'))
check('Fallback print reveals every attendance row', v.js('Array.from(document.querySelectorAll("[data-attendance-row]")).every(el=>getComputedStyle(el).display!=="none")'))
v.cdp('Emulation.setEmulatedMedia', {'media':'screen'})
v.goto('auth/logout.php')
(v.out/'checks.json').write_text(json.dumps(checks,indent=2),encoding='utf-8')
(v.out/'capture.json').write_text(json.dumps(captures,indent=2),encoding='utf-8')
v.ws.close()
print(len(checks), 'checks passed.')
