"""Role login integration checks. Run only against the isolated QA database/server."""
import http.cookiejar
import json
import os
from pathlib import Path
import re
import subprocess
import urllib.error
import urllib.parse
import urllib.request
import uuid

assert os.environ.get('EDULMS_DB_NAME', '').startswith('edulms_qa_'), 'QA database required'
root = Path(__file__).resolve().parents[1]
base = os.environ.get('EDULMS_QA_URL', 'http://127.0.0.1:8101').rstrip('/') + '/'
results = []
accounts = {'siswa': 'budi.demo@student.edulms.sch.id', 'guru': 'ahmad.dahlan@edulms.sch.id', 'admin': 'admin@edulms.sch.id'}
destinations = {'siswa': 'index.php', 'guru': 'guru.php', 'admin': 'admin.php'}

def check(name, ok):
    results.append({'test': name, 'passed': bool(ok)})
    print(('PASS ' if ok else 'FAIL ') + name, flush=True)
    assert ok, name

def db(sql, params=None):
    return json.loads(subprocess.check_output(['php', str(root/'docs/qa_query.php'), sql, json.dumps(params or [])], encoding='utf-8'))['rows']

class Client:
    def __init__(self):
        self.cookies = http.cookiejar.CookieJar()
        self.opener = urllib.request.build_opener(urllib.request.HTTPCookieProcessor(self.cookies))

    def request(self, path, data=None):
        try:
            response = self.opener.open(base + path, urllib.parse.urlencode(data).encode() if data is not None else None, timeout=20)
        except urllib.error.HTTPError as error:
            response = error
        body = response.read().decode('utf-8')
        assert not any(error in body for error in ['Fatal error', 'Warning:', 'Parse error']), body[:500]
        return response.code, body, response.url

    def token(self):
        return re.search(r'name="csrf_token" value="([^"]+)"', self.request('auth/login.php')[1])[1]

    def login(self, role, identifier=None, password='password', extra=None):
        return self.request('auth/process_login.php', {'csrf_token': self.token(), 'role': role, 'identifier': identifier or accounts.get(role, accounts['siswa']), 'password': password, **(extra or {})})

for account_role, email in accounts.items():
    for selected_role in accounts:
        client = Client()
        response = client.login(selected_role, email)
        if account_role == selected_role:
            check(f'{account_role} accepted only at its portal', response[2].endswith('/dashboard/' + destinations[account_role]))
            if account_role != 'admin':
                check(f'{account_role} cannot open admin dashboard', client.request('dashboard/admin.php')[0] == 403)
        else:
            check(f'{account_role} rejected at {selected_role} portal', 'error=invalid' in response[2] and 'name="role" id="selected-role" value="'+selected_role+'"' in response[1])
            check(f'{account_role}/{selected_role} mismatch leaves no session', '/auth/login.php' in client.request('dashboard/index.php')[2])

client = Client()
check('Default form submits student role', 'id="selected-role" value="siswa"' in client.request('auth/login.php')[1])
for role in ['', 'owner', 'SISWA', "admin' OR 1=1 --"]:
    check('Invalid role rejected: '+repr(role), 'error=role' in client.login(role)[2])
for key, value in [('role[]', 'admin'), ('role', None)]:
    data = {'csrf_token': client.token(), 'identifier': accounts['admin'], 'password': 'password'}
    if value is not None:
        data[key] = value
    check('Array or omitted role rejected: '+key, 'error=role' in client.request('auth/process_login.php', data)[2])
check('Wrong password rejected', 'error=invalid' in client.login('guru', password='wrong')[2])
for key in ['identifier', 'password']:
    check('Array field rejected: '+key, 'error=empty' in client.login('siswa', extra={key: '', key+'[]': 'bad'})[2])
check('Missing CSRF rejected', client.request('auth/process_login.php', {'role': 'admin', 'identifier': accounts['admin'], 'password': 'password'})[0] == 403)
check('Array CSRF rejected', client.request('auth/process_login.php', {'csrf_token[]': client.token(), 'role': 'siswa'})[0] == 403)
for role, column in [('siswa', 'nisn'), ('guru', 'nip')]:
    identifier = db('SELECT '+column+' FROM users WHERE email=?', [accounts[role]])[0][column]
    check(column+' authenticates correct role', Client().login(role, identifier)[2].endswith('/dashboard/'+destinations[role]))
    check(column+' rejected under admin role', 'error=invalid' in Client().login('admin', identifier)[2])
client = Client()
token = client.token()
old_session = next(cookie.value for cookie in client.cookies if cookie.name == 'PHPSESSID')
client.login('admin')
check('Successful login rotates session ID', next(cookie.value for cookie in client.cookies if cookie.name == 'PHPSESSID') != old_session)
client.login('guru', accounts['siswa'])
check('Failed new login clears prior admin session', '/auth/login.php' in client.request('dashboard/admin.php')[2])
inactive_email = 'qa-disabled-'+uuid.uuid4().hex+'@edulms.test'
try:
    db("INSERT INTO users (nama_lengkap,email,password,role,is_active) SELECT 'QA Disabled',?,password,'siswa',0 FROM users WHERE email=?", [inactive_email, accounts['siswa']])
    check('Inactive student rejected', 'error=invalid' in Client().login('siswa', inactive_email)[2])
finally:
    db('DELETE FROM users WHERE email=?', [inactive_email])
(root/'docs/login-role-results.json').write_text(json.dumps(results, indent=2), encoding='utf-8')
print(f'{len(results)} role login checks passed.')
