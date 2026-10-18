const fs = require('fs');
const path = require('path');
const puppeteer = require(process.env.EDULMS_PUPPETEER || 'puppeteer-core');
const base = process.env.EDULMS_QA_URL || 'http://127.0.0.1:8099';
const studentEmail = process.env.EDULMS_QA_STUDENT || 'budi.demo@student.edulms.sch.id';
(async () => {
 const browser = await puppeteer.launch({executablePath:'C:/Program Files/Google/Chrome/Application/chrome.exe',headless:true,args:['--no-sandbox','--disable-gpu'],userDataDir:path.join(require('os').tmpdir(),'edulms-browser-qa')});
 const page = await browser.newPage();
 const errors=[];
 page.on('pageerror',err=>errors.push(err.message));
 page.on('requestfailed',r=>errors.push(r.url()+': '+r.failure().errorText));
 await page.setViewport({width:1440,height:1000});
 await page.goto(base+'/auth/login.php',{waitUntil:'networkidle2'});
 await page.screenshot({path:'docs/screenshots/login-desktop.png',fullPage:true});
 await page.type('#input-identifier',studentEmail);
 await page.type('#password','password');
 await Promise.all([page.waitForNavigation({waitUntil:'networkidle2'}),page.click('button[type=submit]')]);
 console.log('Login URL:',page.url());
 for(const file of ['index','mata_pelajaran','tugas','pengumuman','riwayat']) {
   await page.goto(base+'/dashboard/'+file+'.php',{waitUntil:'networkidle2'});
   const content=await page.content();
   await page.evaluate(() => { if (window.Chart) Object.values(Chart.instances).forEach(chart => chart.update('none')); });
   console.log(file, await page.title(), 'overflow:', await page.evaluate(()=>document.documentElement.scrollWidth>innerWidth), 'PHP error:', /Fatal error|Warning:|Parse error/.test(content));
   await page.screenshot({path:'docs/screenshots/'+file+'-desktop.png',fullPage:true});
   if(file==='pengumuman' && await page.$('[data-dialog-open]')) {
     await page.click('[data-dialog-open]');
     console.log('Announcement modal:', await page.$eval('dialog[open]',el=>el.textContent.includes('Isi lengkap')));
     await page.keyboard.press('Escape');
     console.log('Escape closes modal:', await page.$('dialog[open]') === null);
   }
 }
 await page.setViewport({width:390,height:844});
 await page.goto(base+'/dashboard/index.php',{waitUntil:'networkidle2'});
 await page.click('.portal-menu-button');
 console.log('Mobile menu:',await page.$eval('.portal-menu-button',el=>el.getAttribute('aria-expanded')));
 console.log('Mobile overflow:',await page.evaluate(()=>document.documentElement.scrollWidth>innerWidth));
 await page.screenshot({path:'docs/screenshots/student-mobile.png',fullPage:true});
 for(const width of [390,768,1280]) {
   await page.setViewport({width,height:844});
   for(const file of ['index','mata_pelajaran','tugas','pengumuman']) {
     await page.goto(base+'/dashboard/'+file+'.php',{waitUntil:'networkidle2'});
     console.log('Responsive',file,width,'overflow:',await page.evaluate(()=>document.documentElement.scrollWidth>innerWidth));
   }
 }
 await page.goto(base+'/auth/logout.php',{waitUntil:'networkidle2'});
 await page.setViewport({width:1440,height:1000});
 await page.click('[data-role=guru]');
 await page.type('#input-identifier','ahmad.dahlan@edulms.sch.id');await page.type('#password','password');
 await Promise.all([page.waitForNavigation({waitUntil:'networkidle2'}),page.click('button[type=submit]')]);
 for(const file of ['guru','guru_kelas','guru_penilaian','guru_presensi']) {
   const response=await page.goto(base+'/dashboard/'+file+'.php',{waitUntil:'networkidle2'});
   console.log(file,response.status(),'overflow:',await page.evaluate(()=>document.documentElement.scrollWidth>innerWidth),'PHP error:',/Fatal error|Warning:|Parse error/.test(await page.content()));
   await page.screenshot({path:'docs/screenshots/'+file+'-desktop.png',fullPage:true});
   await page.setViewport({width:390,height:844});
   console.log('Teacher mobile',file,'overflow:',await page.evaluate(()=>document.documentElement.scrollWidth>innerWidth));
   await page.screenshot({path:'docs/screenshots/'+file+'-mobile.png',fullPage:true});
   await page.setViewport({width:1440,height:1000});
   if(file==='guru_penilaian') {
     const detail=await page.$('.grading-task a');
     if(detail) {
       const url=await detail.evaluate(el=>el.href);
       await page.goto(url,{waitUntil:'networkidle2'});
       const gradeButton=await page.$('button[onclick^="openGradeModal"]');
       if(gradeButton) {
         await gradeButton.click();
         console.log('Grading modal opens:', await page.$eval('#modalGrade',el=>el.open));
         await page.keyboard.press('Escape');
         console.log('Grading modal closes:', await page.$eval('#modalGrade',el=>!el.open));
       }
     }
   }
 }
 console.log('Browser errors:',JSON.stringify(errors));
 await browser.close();
})().catch(err=>{console.error(err);process.exit(1)});
