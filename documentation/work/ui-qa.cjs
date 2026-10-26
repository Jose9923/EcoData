const fs = require('fs');
const path = require('path');
const { chromium } = require('C:/Users/josev/.cache/codex-runtimes/codex-primary-runtime/dependencies/node/node_modules/playwright');
const base = 'http://127.0.0.1:18086';
const out = path.join(__dirname, 'qa/app-fixes');
const report = { started: new Date().toISOString(), checks: [], consoleErrors: [], pageErrors: [], httpErrors: [], screenshots: [] };
const assert = (condition, message) => { if (!condition) throw new Error(message); };
async function check(name, fn) { try { const data = await fn(); report.checks.push({ name, ok: true, data }); console.log('PASS', name); } catch(error) { report.checks.push({ name, ok: false, error: error.stack }); console.log('FAIL', name, error.message); } }
async function screenshot(page, name) { await page.waitForTimeout(300); if(await page.locator('.swal2-confirm').isVisible()) { await page.locator('.swal2-confirm').click(); await page.locator('.swal2-container').waitFor({state:'hidden'}); } const file = path.join(out,name+'.png'); await page.screenshot({ path:file, fullPage:true }); report.screenshots.push(file); }
async function context(browser, name, mobile=false) {
 const ctx = await browser.newContext({ viewport: mobile ? {width:390,height:844} : {width:1365,height:1000}, deviceScaleFactor:1, isMobile:mobile, hasTouch:mobile });
 ctx.on('page',page=>{
  page.setDefaultTimeout(12000);
  page.on('console',msg=>{if(msg.type()==='error')report.consoleErrors.push({context:name,url:page.url(),text:msg.text()});});
  page.on('pageerror',error=>report.pageErrors.push({context:name,url:page.url(),text:error.message}));
  page.on('response',response=>{if(response.status()>=400)report.httpErrors.push({context:name,url:response.url(),status:response.status()});});
 });
 return ctx;
}
async function login(page, alias) {
 await page.goto(base+'/login');
 await page.locator('[name=email]').fill('qa-'+alias+'@example.test');
 await page.locator('[name=password]').fill('QA-Temporal-2026!');
 await Promise.all([page.waitForURL(url=>!url.pathname.endsWith('/login')),page.getByRole('button',{name:'Entrar a EcoData'}).click()]);
 await page.waitForLoadState('networkidle');
 assert(!page.url().includes('/login') && !page.url().includes('verify-email'),'Login did not reach a verified session: '+page.url());
}
(async()=>{
 const browser = await chromium.launch({executablePath:'C:/Program Files/Google/Chrome/Application/chrome.exe',headless:true,args:['--disable-gpu','--no-sandbox']});
 try {
  const studentContext = await context(browser,'student-desktop');
  const student = await studentContext.newPage();
  await check('desktop login student',async()=>{await student.goto(base+'/login');await screenshot(student,'01-login-desktop');await login(student,'student1');await screenshot(student,'02-dashboard-desktop');return {url:student.url()};});
  await check('student chart and school isolation',async()=>{
    await student.goto(base+'/mediciones/evolucion?variable_id=1');
    await student.getByRole('heading',{name:'Evolución de mediciones'}).waitFor();
    assert(await student.locator('svg[aria-labelledby="trend-title trend-description"] circle').count()>0,'Chart has no points');
    assert(await student.locator('#report-variable option[value="2"]').count()===0,'Other-school variable visible');
    await screenshot(student,'03-chart-desktop');
    return {points:await student.locator('svg[aria-labelledby="trend-title trend-description"] circle').count()};
  });
  await check('report email to verified account using array mail',async()=>{
    await student.goto(base+'/reports/mail?variable_id=1');
    await screenshot(student,'04-report-mail-desktop');
    const response=student.waitForResponse(r=>r.request().method()==='POST'&&r.url().includes('/reports/mail/send'));
    await student.getByRole('button',{name:'Enviar a mi correo'}).click();
    const r=await response;assert(r.status()===302,'Mail POST status '+r.status());
    await student.waitForLoadState('networkidle');
    assert((await student.locator('body').innerText()).includes('quedó en cola'),'No mail confirmation');
  });
  let oldRevision;
  await check('diary autosave persists across reload',async()=>{
    await student.goto(base+'/estudiante/field-diaries/1');
    await screenshot(student,'05-diary-initial-desktop');
    oldRevision=await student.locator('[name=revision]').inputValue();
    const saved=student.waitForResponse(r=>r.request().method()==='POST'&&r.url().endsWith('/field-diaries/1/save'));
    await student.locator('textarea[name="answers[1]"]').fill('Observé árboles y aves en el patio. Borrador de QA.');
    assert((await saved).status()===200,'Autosave failed');
    await student.getByText(/Borrador guardado a las/).waitFor();
    assert(await student.locator('#diary-state-label').innerText()==='Borrador','State card not refreshed after autosave');
    await screenshot(student,'06-diary-autosaved-desktop');
    await student.reload();
    assert((await student.locator('textarea[name="answers[1]"]').inputValue()).includes('Borrador de QA'),'Autosave text lost after reload');
  });
  await check('manual submit waits for active autosave and sends exactly once',async()=>{
    let submissions=0;
    student.on('request',r=>{if(r.method()==='POST'&&r.url().endsWith('/field-diaries/1/submit'))submissions++;});
    await student.route('**/field-diaries/1/save',async route=>{await new Promise(resolve=>setTimeout(resolve,700));await route.continue();});
    const started=student.waitForRequest(r=>r.method()==='POST'&&r.url().endsWith('/field-diaries/1/save'));
    await student.locator('textarea[name="answers[1]"]').fill('Observación final: árboles y aves. Envío durante guardado activo.');
    await started;
    const sent=student.waitForResponse(r=>r.request().method()==='POST'&&r.url().endsWith('/field-diaries/1/submit'));
    await student.getByRole('button',{name:'Enviar respuestas'}).click();
    const r=await sent; assert(r.status()===302,'Final submit status '+r.status());
    await student.waitForLoadState('networkidle');
    assert((await student.locator('body').innerText()).includes('Esta actividad ya fue enviada.'),'Submission not sent');
    assert(submissions===1,'Duplicate final submissions: '+submissions);
    await screenshot(student,'07-diary-sent-desktop');
    await student.unroute('**/field-diaries/1/save');
    return {submissions};
  });
  await check('late autosave conflict does not overwrite sent answer',async()=>{
    const csrf=await student.locator('[name=_token]').first().inputValue();
    const response=await studentContext.request.post(base+'/estudiante/field-diaries/1/save',{headers:{'Accept':'application/json','X-CSRF-TOKEN':csrf},data:{autosave:true,revision:oldRevision,answers:{1:'Respuesta antigua'}}});
    assert(response.status()===409,'Expected conflict, got '+response.status());
    await student.reload();
    assert((await student.locator('textarea[name="answers[1]"]').inputValue()).includes('Observación final'),'Final response overwritten');
  });
  const teacherContext=await context(browser,'teacher-desktop');
  const teacher=await teacherContext.newPage();
  await check('teacher login',async()=>{await login(teacher,'teacher1');});
  await check('CSV template download',async()=>{
    await teacher.goto(base+'/admin/physical-variable-record-imports/create');
    await teacher.locator('#import-station').selectOption('1');
    const downloaded=teacher.waitForEvent('download');
    await teacher.getByRole('button',{name:'Descargar plantilla de esta estación'}).click();
    const file=await downloaded; await file.saveAs(path.join(out,'qa-template.csv'));
    assert(fs.readFileSync(path.join(out,'qa-template.csv'),'utf8').includes('temperatura [C]'),'Wrong CSV headers');
  });
  await check('CSV preview and confirmation',async()=>{
    await teacher.locator('#csv-file').setInputFiles({name:'qa-records.csv',mimeType:'text/csv',buffer:Buffer.from('recorded_at,temperatura [C],observations\n2026-10-26 10:10,22.75,QA CSV fila 1\n2026-10-26 10:20,23.10,QA CSV fila 2\n')});
    const response=teacher.waitForResponse(r=>r.request().method()==='POST'&&r.url().endsWith('/preview'));
    await teacher.getByRole('button',{name:'Validar y ver vista previa'}).click();
    assert((await response).status()===302,'CSV preview POST failed');
    await teacher.waitForLoadState('networkidle');
    await teacher.locator('.swal2-confirm').waitFor({state:'visible',timeout:3000}).catch(()=>{}); if(await teacher.locator('.swal2-confirm').isVisible()) { await teacher.locator('.swal2-confirm').click(); await teacher.locator('.swal2-container').waitFor({state:'hidden'}); }
    await teacher.getByRole('heading',{name:/2\. Vista previa/}).waitFor();
    await screenshot(teacher,'08-csv-preview-desktop');
    const committed=teacher.waitForResponse(r=>r.request().method()==='POST'&&r.url().endsWith('/commit'));
    await teacher.getByRole('button',{name:/Confirmar y guardar 2 registros/}).click();
    const result=await committed;assert(result.status()===302,'CSV commit failed '+result.status());
    await teacher.waitForLoadState('networkidle');
    assert((await teacher.locator('body').innerText()).includes('2 registros guardados'),'CSV success missing');
    await teacher.locator('.swal2-confirm').waitFor({state:'visible',timeout:3000}).catch(()=>{}); if(await teacher.locator('.swal2-confirm').isVisible()) { await teacher.locator('.swal2-confirm').click(); await teacher.locator('.swal2-container').waitFor({state:'hidden'}); }
    await screenshot(teacher,'09-csv-completed-desktop');
  });
  await check('teacher reviews and student sees result',async()=>{
    await teacher.goto(base+'/admin/field-diary-submissions/1');
    await teacher.locator('#score').fill('88');
    await teacher.locator('#teacher_feedback').fill('La observación identifica elementos concretos del entorno.');
    await teacher.locator('#status').selectOption('revisado');
    await screenshot(teacher,'10-review-desktop');
    const reviewed=teacher.waitForResponse(r=>r.request().method()==='POST'&&r.url().endsWith('/review'));
    await teacher.getByRole('button',{name:'Guardar revisión'}).click();
    assert((await reviewed).status()===302,'Review failed');
    await teacher.waitForLoadState('networkidle');
    await student.reload();
    assert((await student.locator('body').innerText()).includes('Esta actividad ya fue revisada'),'Review not visible');
    assert(await student.getByRole('button',{name:'Enviar respuestas'}).count()===0,'Reviewed activity remains editable');
    await screenshot(student,'11-diary-reviewed-desktop');
  });
  const mobileContext=await context(browser,'student-mobile',true);
  const mobile=await mobileContext.newPage();
  await check('mobile login and graph layout',async()=>{
    await mobile.goto(base+'/login');await screenshot(mobile,'12-login-mobile');
    await login(mobile,'student1');await screenshot(mobile,'13-dashboard-mobile');
    await mobile.goto(base+'/mediciones/evolucion?variable_id=1');
    await screenshot(mobile,'14-chart-mobile');
    const geometry=await mobile.evaluate(()=>({width:innerWidth,body:document.documentElement.scrollWidth}));
    assert(geometry.body<=geometry.width+2,'Horizontal overflow '+JSON.stringify(geometry));
    return geometry;
  });
  await check('mobile reviewed diary and report layout',async()=>{
    await mobile.goto(base+'/estudiante/field-diaries/1');await screenshot(mobile,'15-diary-reviewed-mobile');
    const diary=await mobile.evaluate(()=>({width:innerWidth,body:document.documentElement.scrollWidth}));
    assert(diary.body<=diary.width+2,'Diary overflow');
    await mobile.goto(base+'/reports/mail?variable_id=1');await screenshot(mobile,'16-report-mail-mobile');
    const mail=await mobile.evaluate(()=>({width:innerWidth,body:document.documentElement.scrollWidth}));
    assert(mail.body<=mail.width+2,'Mail overflow');
  });
 } finally {
  report.finished=new Date().toISOString();
  fs.writeFileSync(path.join(out,'e2e-report.json'),JSON.stringify(report,null,2));
  await browser.close();
 }
 console.log(JSON.stringify({checks:report.checks.length,failed:report.checks.filter(x=>!x.ok).map(x=>x.name),consoleErrors:report.consoleErrors,pageErrors:report.pageErrors,httpErrors:report.httpErrors},null,2));
})().catch(error=>{console.error(error);process.exitCode=1;});
