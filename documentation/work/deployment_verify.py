from pathlib import Path
import datetime, hashlib, json, re, subprocess, urllib.request, urllib.error
root=Path('/home/josev/EcoData')
backup=Path((root/'documentation/work/deployment-backup-path.txt').read_text().strip())
before=json.loads((backup/'manifest.json').read_text())
php=(root/'documentation/work/deploy-inspect.php').read_text().replace("'/app/","'/var/www/html/")
result=subprocess.run(['docker','exec','-i','--user','www-data','ecodata-app-1','php'],input=php,capture_output=True,text=True,check=True)
after=json.loads(result.stdout)
assert after['counts']==before['counts_before'], 'Existing records changed'
assert after['app_key_fingerprint']==before['key_fingerprint_before'], 'Application key changed'
assert all(after[key] for key in ['migration_applied','audit_table','receipts_table'])
class NoRedirect(urllib.request.HTTPRedirectHandler):
    def redirect_request(self,*args,**kwargs):
        return None
opener=urllib.request.build_opener(NoRedirect)
http={}
login=''
for path in ['/up','/login','/dashboard']:
    try:
        res=opener.open('http://localhost:8081'+path,timeout=15)
    except urllib.error.HTTPError as error:
        res=error
    body=res.read().decode('utf-8',errors='replace')
    http[path]={'status':res.code,'location':res.headers.get('Location')}
    if path=='/login': login=body
assert http['/up']['status']==200
assert http['/login']['status']==200
assert http['/dashboard']['status']==302 and http['/dashboard']['location'].endswith('/login')
assets=sorted(set(re.findall(r'(?:src|href)=["\']([^"\']*/build/[^"\']+)["\']',login)))
assert assets, 'No compiled login assets found'
for asset in assets:
    url=asset if asset.startswith('http') else 'http://localhost:8081'+asset
    with urllib.request.urlopen(url,timeout=15) as res:
        assert res.status==200
        http[url.split('localhost:8081')[-1]]={'status':res.status,'bytes':len(res.read())}
containers={}
for service in ['mysql','redis','app','nginx','queue','scheduler']:
    name='ecodata-'+service+'-1'
    info=json.loads(subprocess.run(['docker','inspect',name],capture_output=True,text=True,check=True).stdout)[0]
    containers[service]={'id':info['Id'][:12],'running':info['State']['Running'],'started_at':info['State']['StartedAt'],'restart_count':info['RestartCount'],'user':info['Config']['User'],'command':info['Config']['Cmd'],'restart_policy':info['HostConfig']['RestartPolicy']['Name'],'health':info['State'].get('Health',{}).get('Status')}
    assert info['State']['Running'], 'Service not running: '+service
    if service!='mysql': assert info['RestartCount']==0
assert containers['mysql']['id']=='c1c8bf97c28d', 'MySQL was unexpectedly recreated'
report={'verified_utc':datetime.datetime.now(datetime.timezone.utc).isoformat(),'backup':str(backup),'data_preserved':True,'app_key_preserved':True,'runtime':after,'http':http,'containers':containers}
(root/'documentation/work/deployment-result.json').write_text(json.dumps(report,indent=2)+'\n')
print(json.dumps(report,indent=2))
