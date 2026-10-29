const {chromium:pw}=require('playwright');
(async()=>{
 const options={headless:true};
 if(process.env.HC_BROWSER_EXECUTABLE){options.executablePath=process.env.HC_BROWSER_EXECUTABLE;options.args=['--no-sandbox','--disable-gpu','--single-process','--no-zygote'];}
 const browser=await pw.launch(options);
 const base=process.env.HC_TEST_BASE_URL||'http://127.0.0.1:8090';
 const context=await browser.newContext({viewport:{width:1440,height:1000}}),page=await context.newPage();
 const errors=[];page.on('pageerror',e=>errors.push(e.message));
 await page.goto(base+'/index.php');
 await page.goto(base+'/dang-nhap.php');await page.locator('#email').fill('a@example.test');await page.locator('#password').fill('TestPass123!');await Promise.all([page.waitForURL('**/suc-khoe.php'),page.locator('form.auth-card button').click()]);

 await page.goto(base+'/bmi.php');await page.locator('#height').fill('170');await page.locator('#weight').fill('67');await Promise.all([page.waitForURL('**/bmi.php'),page.locator('form.bmi-panel button').click()]);
 await page.goto(base+'/suc-khoe.php');await page.waitForTimeout(300);
 await page.setViewportSize({width:390,height:844});
 for(const path of ['index.php','suc-khoe.php','bac-si.php','dat-lich.php?doctor=1','bmi.php','tai-khoan.php','tin-tuc.php','huong-dan-app.php']) {
  await page.goto(base+'/'+path);await page.waitForTimeout(100);
  const overflow=await page.evaluate(()=>document.documentElement.scrollWidth>innerWidth+1);
  if(overflow)throw Error('Mobile horizontal overflow: '+path);


 }
 await page.locator('#menu-toggle').click();if(await page.locator('#menu-toggle').getAttribute('aria-expanded')!=='true')throw Error('Menu failed');
 await page.locator('#chatbot-toggle').click();if(!await page.locator('#chatbot-window').isVisible())throw Error('Chat toggle failed');await page.locator('#chatClose').click();
 const registration=await page.evaluate(async()=>{const r=await navigator.serviceWorker.ready;return r.scope;});if(!registration)throw Error('No service worker');
 const cached=await page.evaluate(async()=>{const names=await caches.keys();let out=[];for(const n of names){const c=await caches.open(n);out.push(...(await c.keys()).map(r=>r.url));}return out;});
 if(cached.some(u=>u.includes('.php')||u.includes('/api/')))throw Error('Private page cached');
 await context.setOffline(true);await page.goto(base+'/suc-khoe.php');if(!await page.getByRole('heading',{name:'Bạn đang ngoại tuyến'}).isVisible())throw Error('Offline fallback failed');await context.setOffline(false);
 // Admin mobile view.
 await page.goto(base+'/dang-nhap.php?role=admin');await page.locator('#email').fill('admin@example.test');await page.locator('#password').fill('TestPass123!');await Promise.all([page.waitForURL('**/admin/dashboard.php'),page.locator('form.auth-card button').click()]);
 for(const path of ['dashboard.php','bac-si.php?edit=1','lich-kham.php','bai-viet.php','nguoi-dung.php']) {await page.goto(base+'/admin/'+path);if(await page.evaluate(()=>document.documentElement.scrollWidth>innerWidth+1))throw Error('Admin mobile overflow: '+path);}
 if(errors.length)throw Error(errors.join('\n'));
 console.log('Browser passed: desktop, 13 mobile pages, navigation, chat toggle, service worker, private-cache exclusion and offline fallback.');await browser.close();
})().catch(e=>{console.error(e);process.exit(1)});
