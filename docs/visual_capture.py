"""Chrome CDP capture using existing websocket-client, no browser dependency install.
Run against the isolated visual fixture server on port 8100.
"""
import argparse,base64,json,time,urllib.request,websocket,subprocess,os
from pathlib import Path
root=Path(__file__).resolve().parents[1]
parser=argparse.ArgumentParser();parser.add_argument('--label',default='actual');parser.add_argument('--width',type=int,default=1280);parser.add_argument('--reference-height',action='store_true');parser.add_argument('--fixture',action='store_true');parser.add_argument('--base-url',default='http://127.0.0.1:8100');parser.add_argument('--cdp-port',type=int,default=9223);args=parser.parse_args()
out=root/'docs/visual-diff'/args.label;out.mkdir(parents=True,exist_ok=True)
targets=json.load(urllib.request.urlopen('http://127.0.0.1:'+str(args.cdp_port)+'/json'))
target=next(target for target in targets if target.get('type')=='page')
errors=[]
ws=websocket.create_connection(target['webSocketDebuggerUrl'],timeout=30,suppress_origin=True);serial=0
def cdp(method,params=None):
 global serial
 serial+=1;ws.send(json.dumps({'id':serial,'method':method,'params':params or {}}))
 while True:
  reply=json.loads(ws.recv())
  if reply.get('method')=='Runtime.exceptionThrown': errors.append(reply['params']['exceptionDetails'].get('text','JavaScript exception'))
  if reply.get('id')==serial:
   if 'error' in reply: raise RuntimeError(reply['error'])
   return reply.get('result',{})
def js(expression):
 r=cdp('Runtime.evaluate',{'expression':expression,'awaitPromise':True,'returnByValue':True})
 if 'exceptionDetails' in r: raise RuntimeError(r['exceptionDetails'])
 return r['result'].get('value')
reference_heights={'login':1025,'index':1322,'mata_pelajaran':891,'tugas':1275,'pengumuman':840,'guru':1534,'guru_kelas':1626,'guru_penilaian':1441,'guru_presensi':1518}
def goto(path):
 name=path.rsplit('/',1)[-1].split('.')[0]
 cdp('Emulation.setDeviceMetricsOverride',{'width':args.width,'height':reference_heights.get(name,900) if args.reference_height else 900,'deviceScaleFactor':1,'mobile':False})
 errors.clear()
 cdp('Page.navigate',{'url':args.base_url.rstrip('/')+'/'+path});time.sleep(.45)
 for _ in range(60):
  if js('document.readyState')=='complete':break
  time.sleep(.1)
 js('document.fonts.ready.then(()=>true)');time.sleep(.15)
def shot(name):
 js("if(window.Chart) Object.values(Chart.instances).forEach(c=>c.update('none')); window.scrollTo(0,0)")
 metrics=cdp('Page.getLayoutMetrics')['cssContentSize']
 result=cdp('Page.captureScreenshot',{'format':'png','captureBeyondViewport':True,'clip':{'x':0,'y':0,'width':args.width,'height':metrics['height'],'scale':1}})
 (out/(name+'.png')).write_bytes(base64.b64decode(result['data']))
 content=js('document.body.innerText')
 assert js('innerWidth')==args.width, 'Browser viewport changed unexpectedly'
 record={'page':name,'width':args.width,'layout_width':js('document.documentElement.clientWidth'),'height':metrics['height'],'overflow':js('document.documentElement.scrollWidth>innerWidth'),'php_error':any(x in content for x in ['Fatal error','Warning:','Parse error']),'url':js('location.href'),'js_errors':list(errors)}
 print(json.dumps(record),flush=True);return record
def login(email,password='password',role='siswa'):
 goto('auth/logout.php');goto('auth/login.php')
 js("document.querySelector('[data-role="+role+"]').click()")
 js("document.querySelector('#input-identifier').value="+json.dumps(email)+";document.querySelector('#password').value="+json.dumps(password)+";document.querySelector('form').requestSubmit()")
 time.sleep(.6)
 assert '/dashboard/' in js('location.href'),'Login failed'
def prepare_fixture(name):
 if args.fixture:
  env={**os.environ,'EDULMS_DB_NAME':'edulms_qa_fidelity_reference_20261015'}
  subprocess.run(['php',str(root/'docs/reference_fixture.php'),name],cwd=root,env=env,check=True,stdout=subprocess.DEVNULL)
def main():
 cdp('Emulation.setScrollbarsHidden',{'hidden':True});cdp('Runtime.enable');cdp('Page.enable');cdp('Emulation.setDeviceMetricsOverride',{'width':args.width,'height':900,'deviceScaleFactor':1,'mobile':False})
 records=[]
 prepare_fixture('login')
 goto('auth/logout.php');goto('auth/login.php');records.append(shot('login'))
 login('budi.demo@student.edulms.sch.id')
 for name in ['index','mata_pelajaran','tugas','pengumuman']:
  prepare_fixture(name)
  goto('dashboard/'+name+'.php');records.append(shot(name))
 login('ahmad.dahlan@edulms.sch.id',role='guru')
 for name in ['guru','guru_kelas','guru_penilaian','guru_presensi']:
  prepare_fixture(name)
  goto('dashboard/'+name+'.php');records.append(shot(name))
 (out/'capture.json').write_text(json.dumps(records,indent=2),encoding='utf-8')
 ws.close()
if __name__=='__main__': main()
