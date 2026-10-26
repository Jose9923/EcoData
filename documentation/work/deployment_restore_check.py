from pathlib import Path
import datetime, gzip, hashlib, json, subprocess, time
root = Path('/home/josev/EcoData')
backup = Path((root/'documentation/work/deployment-backup-path.txt').read_text().strip())
manifest = json.loads((backup/'manifest.json').read_text())
for name, expected in manifest['files'].items():
    assert hashlib.sha256((backup/name).read_bytes()).hexdigest() == expected['sha256'], 'Backup checksum mismatch: '+name
name = 'ecodata-restore-check-' + datetime.datetime.now(datetime.timezone.utc).strftime('%Y%m%d%H%M%S')
started = False
try:
    subprocess.run(['docker','run','-d','--rm','--name',name,'--network','none','--tmpfs','/var/lib/mysql:rw,size=768m','--env','MYSQL_ALLOW_EMPTY_PASSWORD=yes','mysql:8.4'],check=True,capture_output=True)
    started = True
    ready = False
    for attempt in range(45):
        ping = subprocess.run(['docker','exec',name,'mysql','-uroot','--batch','--skip-column-names','-e','SELECT 1'],capture_output=True)
        if ping.returncode == 0:
            ready = True
            break
        time.sleep(1)
    assert ready, 'Temporary restore database did not become ready'
    sql = gzip.decompress((backup/'database.sql.gz').read_bytes())
    result = subprocess.run(['docker','exec','-i',name,'mysql','-uroot'],input=sql,capture_output=True)
    assert result.returncode == 0, 'Restore failed; inspect temporary database without exposing SQL'
    counts = {}
    for table, expected in manifest['counts_before'].items():
        result = subprocess.run(['docker','exec',name,'mysql','-uroot','--batch','--skip-column-names',manifest['database'],'-e','SELECT COUNT(*) FROM '+table],check=True,capture_output=True,text=True)
        counts[table] = int(result.stdout.strip())
        assert counts[table] == expected, 'Restore count mismatch: '+table
    report = {'verified_utc':datetime.datetime.now(datetime.timezone.utc).isoformat(),'checksums_valid':True,'restore_verified':True,'counts':counts}
    (backup/'restore-verification.json').write_text(json.dumps(report,indent=2)+'\n')
    print(json.dumps(report),flush=True)
finally:
    if started:
        subprocess.run(['docker','stop','--time','10',name],check=True,capture_output=True)
