from pathlib import Path
import json, re, subprocess
root=Path('/home/josev/EcoData')
backup=Path((root/'documentation/work/deployment-backup-path.txt').read_text().strip())
assert json.loads((backup/'restore-verification.json').read_text())['restore_verified']
info=json.loads(subprocess.run(['docker','inspect','ecodata-mysql-1'],check=True,capture_output=True,text=True).stdout)[0]
values=dict(x.split('=',1) for x in info['Config']['Env'] if '=' in x)
assert info['Config']['Labels']['com.docker.compose.project']=='ecodata'
secret=values['MYSQL_ROOT_PASSWORD']
envpath=root/'src/.env'
original=envpath.read_bytes()
text=original.decode()
present=re.search(r'^MYSQL_ROOT_PASSWORD=(.+)$',text,re.M)
changed=False
if not present or not present.group(1).strip():
    candidate="MYSQL_ROOT_PASSWORD='"+secret.replace("\\","\\\\").replace("'","\\'")+"'\n"
    # Check dotenv can parse the new setting without revealing its contents.
    payload=json.dumps({'candidate':candidate,'expected':secret}).encode()
    validator='require "/app/vendor/autoload.php"; $x=json_decode(stream_get_contents(STDIN),true); exit((Dotenv\\Dotenv::parse($x["candidate"])["MYSQL_ROOT_PASSWORD"] ?? null)===$x["expected"] ? 0 : 1);'
    subprocess.run(['docker','run','--rm','-i','--mount','type=bind,src='+str(root/'src')+',dst=/app,readonly','ecodata-app','php','-r',validator],input=payload,check=True,capture_output=True)
    text=re.sub(r'^MYSQL_ROOT_PASSWORD=.*(?:\n|$)','',text,flags=re.M)
    envpath.write_text(text.rstrip('\r\n')+'\n'+candidate)
    changed=True
try:
    config=json.loads(subprocess.run(['docker','compose','config','--format','json'],cwd=root/'src',check=True,capture_output=True,text=True).stdout)
    confenv=config['services']['mysql']['environment']
    for key in ['MYSQL_ROOT_PASSWORD','MYSQL_DATABASE','MYSQL_USER','MYSQL_PASSWORD']:
        assert confenv[key]==values[key], 'Compose credential mismatch for '+key
    assert config['volumes']['ecodata_mysql_data']['name']=='ecodata_ecodata_mysql_data'
except Exception:
    if changed: envpath.write_bytes(original)
    raise
print(json.dumps({'root_password_setting_added':changed,'existing_database_credentials_preserved':True,'compose_valid':True,'existing_volume_preserved':True}))
