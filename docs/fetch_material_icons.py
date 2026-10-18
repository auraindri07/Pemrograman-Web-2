"""Vendor the exact outline glyph family identified in the reference PNGs.
Downloads only official Google Material Symbols SVGs + Apache license, never executables.
"""
import concurrent.futures,json,urllib.request
from pathlib import Path
root=Path(__file__).resolve().parents[1]
folder=root/'assets/vendor/material-symbols';folder.mkdir(parents=True,exist_ok=True)
mapping={'arrow':'arrow_forward','logout':'logout','search':'search','bell':'notifications','message':'chat','play':'play_circle','clock':'schedule','calendar':'calendar_today','check':'check','checks':'done_all','check-circle':'check_circle','clipboard':'assignment','book':'menu_book','math':'calculate','sigma':'functions','flask':'science','code':'code','terminal':'terminal','translate':'translate','microscope':'biotech','user':'person','users':'groups','school':'school','shield':'verified_user','trend':'auto_graph','plus':'add','plus-circle':'add_circle','folder':'folder_open','download':'download','upload':'upload','file':'description','file-upload':'upload_file','eye':'visibility','lock':'lock','key':'key','id':'badge','help':'help','filter':'tune','sort':'sort','chevron':'expand_more','home':'home','building':'domain','paperclip':'attach_file','megaphone':'campaign','save':'save','edit':'edit_note','print':'print','table':'table_chart','bulb':'lightbulb','external':'open_in_new','pie':'pie_chart','medical':'medical_services','link':'link','close':'close','info':'info','alarm':'alarm','timer':'timer','rubric':'rule','sync-report':'cloud_sync','history':'history','teacher':'cast_for_education','grade-review':'rate_review','clipboard-clock':'pending_actions','person-check':'how_to_reg','session':'co_present','verified':'verified','trophy':'emoji_events','book-auto':'auto_stories','calendar-check':'event_available','calendar-session':'event_upcoming','door':'meeting_room','cloud-download':'cloud_download','chevron-left':'chevron_left','chevron-right':'chevron_right','sync-data':'sync_saved_locally'}
req=urllib.request.Request('https://api.github.com/repos/google/material-design-icons/commits/master',headers={'User-Agent':'EduLMS-reference-audit'})
revision=json.load(urllib.request.urlopen(req,timeout=30))['sha']
base=f'https://raw.githubusercontent.com/google/material-design-icons/{revision}/'
def fetch(item):
 alias,name=item
 relative=f'symbols/web/{name}/materialsymbolsoutlined/{name}_24px.svg'
 data=urllib.request.urlopen(base+relative,timeout=30).read()
 assert b'<svg' in data and b'<script' not in data
 (folder/(name+'.svg')).write_bytes(data)
 return alias,{'name':name,'source':base+relative}
with concurrent.futures.ThreadPoolExecutor(max_workers=6) as pool:
 entries=dict(pool.map(fetch,mapping.items()))
license=urllib.request.urlopen(base+'LICENSE',timeout=30).read();(folder/'LICENSE').write_bytes(license)
(folder/'manifest.json').write_text(json.dumps({'repository':'https://github.com/google/material-design-icons','revision':revision,'icons':entries},indent=2),encoding='utf-8')
print(f'Vendored {len(entries)} official SVG glyphs, revision {revision}.')
