const fs = require('fs');
const path = require('path');
const { chromium } = require('C:/Users/josev/.cache/codex-runtimes/codex-primary-runtime/dependencies/node/node_modules/playwright');
const base = 'http://127.0.0.1:18086';
const out = path.join(__dirname, 'qa/charts-module');
fs.mkdirSync(out, { recursive: true });
const report = { checks: [], pageErrors: [], consoleErrors: [], screenshots: [] };
const assert = (ok, text) => { if (!ok) throw new Error(text); };
async function check(name, action) {
  try { const detail = await action(); report.checks.push({name,ok:true,detail}); console.log('PASS',name); }
  catch (error) { report.checks.push({name,ok:false,error:error.message}); console.log('FAIL',name,error.message); }
}
async function login(page) {
  await page.goto(base+'/login');
  await page.locator('[name=email]').fill('qa-student1@example.test');
  await page.locator('[name=password]').fill('QA-Temporal-2026!');
  await Promise.all([page.waitForURL('**/dashboard'),page.getByRole('button',{name:'Entrar a EcoData'}).click()]);
}
async function ready(page) {
  await page.locator('[data-chart-download=trend]:not([disabled])').waitFor();
  await page.waitForTimeout(150);
}
async function screenshot(page,name) {
  const file=path.join(out,name+'.png');
  await page.screenshot({path:file,fullPage:true});
  report.screenshots.push(file);
}
const query='?variable_id=1&date_from=2026-10-01&date_to=2026-10-14';
(async()=>{
 const browser=await chromium.launch({executablePath:'C:/Program Files/Google/Chrome/Application/chrome.exe',headless:true,args:['--disable-gpu','--no-sandbox']});
 try {
   const context=await browser.newContext({viewport:{width:1440,height:1000},deviceScaleFactor:1});
   context.on('page',page=>{
     page.setDefaultTimeout(15000);
     page.on('pageerror',error=>report.pageErrors.push(error.message));
     page.on('console',msg=>{if(msg.type()==='error')report.consoleErrors.push(msg.text());});
   });
   const page=await context.newPage();
   await check('navigation and initial empty state',async()=>{
     await login(page);
     await page.locator('aside').getByRole('link',{name:'Gráficos',exact:true}).click();
     await page.getByRole('heading',{name:'Gráficos ambientales',exact:true}).waitFor();
     assert(await page.locator('canvas').count()===0,'Unexpected chart without variable');
     await screenshot(page,'01-empty-desktop');
   });
   await check('three charts and missing days',async()=>{
     await page.goto(base+'/graficos'+query);await ready(page);
     assert(await page.locator('canvas').count()===3,'Expected three charts');
     const rows=page.locator('#chart-data-body tr');
     assert(await rows.count()===14,'Expected all fourteen dates');
     assert((await rows.filter({hasText:'2026-10-05'}).innerText()).includes('Sin datos'),'Missing date was filled with a value');
     assert(!await page.locator('#charts-load-status').isVisible(),'Chart loading failed');
     const painted=await page.locator('#trend-chart').evaluate(c=>c.getContext('2d').getImageData(0,0,c.width,c.height).data.some((v,i)=>i%4===3&&v>0));
     assert(painted,'Canvas is empty');
     assert(await page.locator('#report-variable option[value="2"]').count()===0,'Foreign variable exposed');
     await screenshot(page,'02-charts-desktop');
     return {days:await rows.count()};
   });
   await check('previous period and limits switches',async()=>{
     assert(await page.locator('#compare-period').isChecked(),'Previous period should start enabled');
     const before=await page.locator('#trend-chart').evaluate(c=>c.toDataURL());
     await page.locator('#compare-period').uncheck();
     await page.locator('#show-limits').check();
     await page.waitForTimeout(350);
     const after=await page.locator('#trend-chart').evaluate(c=>c.toDataURL());
     assert(before!==after,'Toggles did not redraw chart');
     await page.locator('#compare-period').check();
   });
   await check('CSV and PNG downloads',async()=>{
     let download=page.waitForEvent('download');
     await page.locator('#download-chart-csv').click();
     const csv=await download;
     await csv.saveAs(path.join(out,'daily.csv'));
     const text=fs.readFileSync(path.join(out,'daily.csv'),'utf8');
     assert(text.includes('2026-10-01')&&text.includes('2026-10-14'),'CSV missing selected dates');
     assert(!text.includes('@example.test'),'CSV leaked identity');
     download=page.waitForEvent('download');
     await page.locator('[data-chart-download=trend]').click();
     const png=await download;await png.saveAs(path.join(out,'trend-export.png'));
     const buffer=fs.readFileSync(path.join(out,'trend-export.png'));
     assert(buffer.slice(1,4).toString()==='PNG'&&buffer.length>10000,'Invalid/empty PNG');
     return {csvBytes:Buffer.byteLength(text),pngBytes:buffer.length};
   });
   await check('weekly grouping and bars',async()=>{
     await page.locator('#chart-group-by').selectOption('week');
     await page.locator('#chart-type').selectOption('bar');
     await Promise.all([page.waitForURL(u=>u.searchParams.get('group_by')==='week'),page.getByRole('button',{name:'Actualizar gráficos',exact:true}).click()]);
     await ready(page);
     assert(await page.locator('#chart-data-body tr').count()===3,'Expected three partial/calendar weeks');
     assert(new URL(page.url()).searchParams.get('chart_type')==='bar','Bar selection lost');
     await screenshot(page,'03-weekly-bars-desktop');
   });
   await check('monthly grouping and station filter',async()=>{
     await page.goto(base+'/graficos'+query+'&group_by=month&weather_station_id=1');await ready(page);
     assert(await page.locator('#chart-data-body tr').count()===1,'Expected one month');
     const stationRows=page.locator('details').filter({hasText:'Ver datos por estación'}).locator('tbody tr');
     assert(await stationRows.count()===1,'Station filter did not restrict comparison');
   });
   await check('zero constant and no limits',async()=>{
     await page.goto(base+'/graficos?variable_id=3&date_from=2026-10-01&date_to=2026-10-01');await ready(page);
     assert(await page.locator('#show-limits').count()===0,'Limits shown without configuration');
     assert((await page.locator('.charts-kpis').innerText()).includes('0,00'),'Zero treated as missing data');
     assert(await page.locator('#chart-data-body tr').count()===1,'One-day range failed');
     await screenshot(page,'04-zero-desktop');
   });
   await check('empty result and forbidden variable',async()=>{
     await page.goto(base+'/graficos?variable_id=1&date_from=2025-01-01&date_to=2025-01-02');
     assert(await page.locator('canvas').count()===0,'No-data range rendered a misleading chart');
     assert((await page.locator('main').innerText()).includes('No encontramos valores'),'Missing no-data explanation');
     const response=await context.request.get(base+'/graficos'+query.replace('variable_id=1','variable_id=2'),{headers:{Accept:'application/json'}});
     assert(response.status()===422,'Foreign variable accepted');
   });
   await check('mobile charts and exports',async()=>{
     await page.setViewportSize({width:390,height:844});
     await page.goto(base+'/graficos'+query);await ready(page);
     assert(await page.evaluate(()=>document.documentElement.scrollWidth<=window.innerWidth+1),'Page overflows horizontally on mobile');
     for(const id of ['trend-chart','station-chart','distribution-chart']){
       const box=await page.locator('#'+id).boundingBox();assert(box&&box.width>200&&box.width<391,'Invalid mobile canvas '+id);
     }
     await screenshot(page,'05-charts-mobile');
     const download=page.waitForEvent('download');await page.locator('[data-chart-download=distribution]').click();
     await (await download).saveAs(path.join(out,'distribution-mobile.png'));
   });
   await check('table fallback without JavaScript',async()=>{
     const storage=await context.storageState();
     const nojs=await browser.newContext({javaScriptEnabled:false,storageState:storage,viewport:{width:1200,height:900}});
     const fallback=await nojs.newPage();
     await fallback.goto(base+'/graficos'+query+'&group_by=week');
     assert(await fallback.locator('#chart-data-body tr').count()===14,'Daily fallback data unavailable');
     assert(await fallback.locator('#download-chart-csv').isDisabled(),'Export should not pretend to work without JavaScript');
     await nojs.close();
   });
   await context.close();
 } finally {
   await browser.close();
   fs.writeFileSync(path.join(out,'e2e-report.json'),JSON.stringify(report,null,2));
   console.log(JSON.stringify({passed:report.checks.filter(c=>c.ok).length,total:report.checks.length,pageErrors:report.pageErrors,consoleErrors:report.consoleErrors}));
   if(report.checks.some(c=>!c.ok)||report.pageErrors.length||report.consoleErrors.length)process.exitCode=1;
 }
})().catch(error=>{console.error(error.message);process.exitCode=1;});
