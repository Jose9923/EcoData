from pathlib import Path
import datetime,json,subprocess
root=Path('/home/josev/EcoData')
php="""<?php
require '/var/www/html/vendor/autoload.php';
$app = require '/var/www/html/bootstrap/app.php';
$app->make(\\Illuminate\\Contracts\\Console\\Kernel::class)->bootstrap();
echo json_encode(['pending'=>\\Illuminate\\Support\\Facades\\DB::table('jobs')->count(),'failed'=>\\Illuminate\\Support\\Facades\\DB::table('failed_jobs')->count()]);
"""
p=subprocess.run(['docker','exec','-i','--user','www-data','ecodata-app-1','php'],input=php,capture_output=True,text=True,check=True)
counts=json.loads(p.stdout)
assert counts=={'pending':0,'failed':0}
path=root/'documentation/work/deployment-result.json'
report=json.loads(path.read_text())
report['final_verified_utc']=datetime.datetime.now(datetime.timezone.utc).isoformat()
report['queue_probe']={'processed':True,**counts}
report['mail_verification']={'mode':'Mailtrap Sandbox','tls_connection_verified':True,'authentication_tested':False,'email_sent':False}
path.write_text(json.dumps(report,indent=2)+'\n')
backup=Path(report['backup'])
(backup/'deployment-result.json').write_text(json.dumps(report,indent=2)+'\n')
print(json.dumps({'deployment_verified':True,'queue_probe':report['queue_probe'],'report':str(path)}))
