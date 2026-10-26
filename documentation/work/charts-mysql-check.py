from pathlib import Path
import subprocess
source=Path('documentation/work/charts-mysql-check.php').read_bytes()
r=subprocess.run(['docker','exec','-i','--user','www-data','ecodata-app-1','php'],input=source,capture_output=True)
print(r.stdout.decode())
if r.returncode:
    print('Runtime verification failed; output intentionally excludes stderr to protect configuration.')
raise SystemExit(r.returncode)
