"""Aligned visual comparison. Pixel/SSIM measures include content differences, never a fidelity percentage.
Only the decorative screenshot border is cropped; actual screenshots are never stretched.
"""
import argparse,json
from pathlib import Path
import numpy as np
from PIL import Image,ImageChops,ImageDraw
root=Path(__file__).resolve().parents[1]
refs={'index':'Dashboard dengan Grafik Perkembangan Belajar.png','mata_pelajaran':'Slide 1 - Mata Pelajaran.png','tugas':'Slide 2 - Tugas.png','pengumuman':'Slide 3 - Pengumuman.png','guru':'Dashboard Guru (Top Navbar).png','guru_kelas':'Slide Guru - Kelas & Mata Pelajaran.png','guru_penilaian':'Slide Guru - Penilaian Tugas.png','guru_presensi':'Slide Guru - Presensi Siswa.png','login':next(p.name for p in (root/'EduLMS_UI').glob('Html*.png'))}
parser=argparse.ArgumentParser();parser.add_argument('--label',default='actual');args=parser.parse_args()
folder=root/'docs/visual-diff'/args.label
results=[]
for name,filename in refs.items():
 source=Image.open(root/'EduLMS_UI'/filename).convert('RGB')
 bounds=(16,18,1296,1043) if name=='login' else (8,7,1288,source.height-8)
 ref=source.crop(bounds)
 actual=Image.open(folder/(name+'.png')).convert('RGB')
 assert actual.width==ref.width,'Capture at reference interior width, 1280 CSS px and DSF 1.'
 # Compare intersection separately from full-page geometry. No rescaling or silent alignment.
 w=ref.width;h=max(ref.height,actual.height);common=min(ref.height,actual.height)
 a=Image.new('RGB',(w,h),'#f7f8ff');b=a.copy();a.paste(ref);b.paste(actual)
 ref.save(folder/(name+'-reference.png'))
 side=Image.new('RGB',(w*2,h),'white');side.paste(a);side.paste(b,(w,0));side.save(folder/(name+'-side-by-side.png'))
 Image.blend(a,b,.5).save(folder/(name+'-overlay.png'))
 delta=np.abs(np.asarray(a,dtype=np.float32)-np.asarray(b,dtype=np.float32))
 heat=np.zeros((h,w,3),dtype=np.uint8);intensity=np.clip(delta.mean(2)*4,0,255).astype(np.uint8);heat[:,:,0]=intensity;heat[:,:,1]=intensity//4
 Image.fromarray(heat).save(folder/(name+'-heatmap.png'))
 aa=np.asarray(a.crop((0,0,w,common)).convert('L'),dtype=np.float64);bb=np.asarray(b.crop((0,0,w,common)).convert('L'),dtype=np.float64)
 # Non-overlapping 8x8 local SSIM (documented variant; not skimage's overlapping Gaussian method).
 ch=common//8*8;cw=w//8*8
 aa=aa[:ch,:cw].reshape(ch//8,8,cw//8,8).transpose(0,2,1,3).reshape(-1,64)
 bb=bb[:ch,:cw].reshape(ch//8,8,cw//8,8).transpose(0,2,1,3).reshape(-1,64)
 ma=aa.mean(1);mb=bb.mean(1);va=aa.var(1);vb=bb.var(1);cov=((aa-ma[:,None])*(bb-mb[:,None])).mean(1)
 ssim=np.mean(((2*ma*mb+6.5025)*(2*cov+58.5225))/((ma**2+mb**2+6.5025)*(va+vb+58.5225)))
 regions=[]
 for y in range(0,h,180):
  for x in range(0,w,320):regions.append({'x':x,'y':y,'width':min(320,w-x),'height':min(180,h-y),'mae':round(float(delta[y:y+180,x:x+320].mean()),3)})
 results.append({'page':name,'reference':filename,'crop':bounds,'reference_size':ref.size,'actual_size':actual.size,'height_delta':actual.height-ref.height,'mean_absolute_error_0_255':round(float(delta[:common].mean()),4),'local_ssim_8x8':round(float(ssim),5),'largest_differences':sorted(regions,key=lambda r:r['mae'],reverse=True)[:5]})
(folder/'metrics.json').write_text(json.dumps(results,indent=2),encoding='utf-8')
print(json.dumps([{k:v for k,v in item.items() if k not in ['largest_differences','reference','crop']} for item in results],indent=2))
