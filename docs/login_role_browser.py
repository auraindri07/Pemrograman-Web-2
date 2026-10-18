"""Exercise actual role tabs and submissions in Chrome. No account/data writes."""
import json
import time
import visual_capture as v

checks = []
captures = []

def check(name, expression):
    ok = bool(v.js(expression))
    checks.append({'test': name, 'passed': ok})
    print(('PASS ' if ok else 'FAIL ') + name, flush=True)
    assert ok, name

v.cdp('Runtime.enable')
v.cdp('Page.enable')
v.goto('auth/logout.php')
v.goto('auth/login.php')
v.js('localStorage.removeItem("edulms_remember_identifier")')
v.js('document.querySelector("#input-identifier").value="";document.querySelector("#password").value=""')
check('Default student role active', 'document.querySelector("#selected-role").value==="siswa" && document.querySelector("[data-role=siswa]").getAttribute("aria-pressed")==="true"')
for role in ['guru', 'admin', 'siswa']:
    v.js('document.querySelector("[data-role='+role+']").click()')
    check('Role tab updates submitted role: '+role, 'document.querySelector("#selected-role").value==='+json.dumps(role)+' && document.querySelectorAll(".role-btn[aria-pressed=true]").length===1')
    check('Role tab does not autofill credentials: '+role, 'document.querySelector("#input-identifier").value==="" && document.querySelector("#password").value===""')
v.js('document.querySelector("#input-identifier").value="budi.demo@student.edulms.sch.id";document.querySelector("#password").value="password";document.querySelector("[data-role=guru]").click()')
check('Switching role preserves manual entries', 'document.querySelector("#input-identifier").value==="budi.demo@student.edulms.sch.id" && document.querySelector("#password").value==="password"')
v.js('document.querySelector("form").requestSubmit()')
time.sleep(.8)
check('Student account rejected on teacher tab', 'location.search.includes("error=invalid") && document.body.innerText.includes("peran tidak sesuai")')
check('Teacher tab stays selected after error', 'document.querySelector("#selected-role").value==="guru" && document.querySelector("[data-role=guru]").getAttribute("aria-pressed")==="true" && document.querySelector("#label-identifier").textContent.includes("NIP")')
for width in [1280, 390]:
    v.args.width = width
    v.goto('auth/login.php?role=guru&error=invalid')
    record = v.shot('login-role-error-'+str(width))
    captures.append(record)
    assert not record['overflow'] and not record['js_errors'] and not record['php_error'], record
v.args.width = 1280
for role, email, page in [('siswa','budi.demo@student.edulms.sch.id','index.php'), ('guru','ahmad.dahlan@edulms.sch.id','guru.php'), ('admin','admin@edulms.sch.id','admin.php')]:
    v.login(email, role=role)
    check('Browser login routes '+role+' correctly', 'location.pathname.endsWith('+json.dumps('/dashboard/'+page)+')')
v.goto('auth/logout.php')
(v.out/'browser-checks.json').write_text(json.dumps(checks, indent=2), encoding='utf-8')
(v.out/'capture.json').write_text(json.dumps(captures, indent=2), encoding='utf-8')
v.ws.close()
print(len(checks), 'browser checks passed.')
