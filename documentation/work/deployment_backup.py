import gzip, hashlib, json, os, shutil, subprocess, tarfile
from datetime import datetime, timezone
from pathlib import Path

root = Path('/home/josev/EcoData').resolve()
os.umask(0o077)
backup_root = root / 'backups'
backup_root.mkdir(exist_ok=True, mode=0o700)
os.chmod(backup_root, 0o700)
backup = backup_root / ('deployment-' + datetime.now(timezone.utc).strftime('%Y%m%dT%H%M%SZ'))
backup.mkdir(mode=0o700)
ignore = root / '.gitignore'
existing = ignore.read_text() if ignore.exists() else ''
if '/backups/' not in existing.splitlines():
    ignore.write_text(existing.rstrip() + ('\n' if existing.strip() else '') + '/backups/\n')

info = json.loads(subprocess.check_output(['docker','inspect','ecodata-mysql-1']))[0]
assert info['Config']['Labels']['com.docker.compose.project'] == 'ecodata'
volume = next(x for x in info['Mounts'] if x['Destination']=='/var/lib/mysql')
assert volume['Name'] == 'ecodata_ecodata_mysql_data'
db_env = dict(v.split('=',1) for v in info['Config']['Env'] if '=' in v)
assert db_env['MYSQL_DATABASE'] == 'EcoData_db'
assert info['State']['Running']
dump = subprocess.Popen(['docker','exec','ecodata-mysql-1','sh','-c',
    'MYSQL_PWD="$MYSQL_ROOT_PASSWORD" exec mysqldump --single-transaction --routines --triggers --events --hex-blob --no-tablespaces --databases "$MYSQL_DATABASE"'],
    stdout=subprocess.PIPE, stderr=subprocess.PIPE)
with gzip.open(backup/'database.sql.gz','wb') as output:
    shutil.copyfileobj(dump.stdout, output)
error = dump.stderr.read().decode(errors='replace')
if dump.wait() != 0:
    raise RuntimeError('El respaldo SQL falló: '+error[:500])
with gzip.open(backup/'database.sql.gz','rb') as stream:
    size = 0
    while chunk := stream.read(1024*1024):
        size += len(chunk)
assert size > 1000
shutil.copyfile(root/'src/.env', backup/'environment.env')
os.chmod(backup/'environment.env',0o600)
with tarfile.open(backup/'storage.tar.gz','w:gz') as archive:
    archive.add(root/'src/storage',arcname='storage')
with tarfile.open(backup/'storage.tar.gz','r:gz') as archive:
    members = len(archive.getmembers())
for name in ['compose.yaml','composer.lock','package-lock.json']:
    shutil.copyfile(root/'src'/name, backup/name)
manifest = {
  'created_utc':datetime.now(timezone.utc).isoformat(),
  'database':'EcoData_db','volume':volume['Name'],'sql_uncompressed_bytes':size,
  'storage_entries':members,
  'counts_before':{'users':19,'schools':15,'physical_variable_records':15,'physical_variable_record_values':15,'field_diary_activities':16,'field_diary_submissions':16,'field_diary_answers':16,'jobs':0,'failed_jobs':0},
  'key_fingerprint_before':'1cb2229de4fd4c7e55504f2839cad45e302717925b93bcaea5e4de09749be53d',
  'files':{}
}
for path in backup.iterdir():
    if path.is_file():
        os.chmod(path,0o600)
        manifest['files'][path.name]={'bytes':path.stat().st_size,'sha256':hashlib.sha256(path.read_bytes()).hexdigest()}
(backup/'manifest.json').write_text(json.dumps(manifest,indent=2))
(root/'documentation/work/deployment-backup-path.txt').write_text(str(backup)+'\n')
print(json.dumps({'backup':str(backup),'sql_bytes':size,'storage_entries':members,'files':list(manifest['files'])}))
