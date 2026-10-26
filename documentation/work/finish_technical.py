import json
from pathlib import Path

path = Path(__file__).with_name('technical_content.json')
data = json.loads(path.read_text(encoding='utf-8'))
for block in data['sections'][4]['blocks']:
    if block['type'] != 'code':
        continue
    lines = []
    for line in block['text'].splitlines():
        if line == 'cd src':
            lines.append('# Ejecutar desde src de la copia aislada (Bash)')
        elif len(line) > 90 and 'run --rm app ' in line:
            prefix, command = line.split('run --rm app ', 1)
            lines.extend([prefix + 'run --rm app ' + chr(92), '  ' + command])
        else:
            lines.append(line)
    block['text'] = '\n'.join(lines)
for block in data['sections'][5]['blocks']:
    if block['type'] == 'table':
        for row in block['rows']:
            for i, cell in enumerate(row):
                row[i] = cell.replace('con station_id en NULL', 'con weather_station_id en NULL')
for block in data['sections'][13]['blocks']:
    if block['type'] == 'table':
        for row in block['rows']:
            row[2] = row[2].replace('/', ' / ')
path.write_text(json.dumps(data, ensure_ascii=False, indent=2), encoding='utf-8')
print('Technical commands, FK name and route access typography corrected.')
