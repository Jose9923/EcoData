from pathlib import Path
import json

folder=Path(__file__).parent
u=folder/'user_content.json'
d=json.loads(u.read_text(encoding='utf8'))
for s in d['sections']:
    for b in s['blocks']:
        if b['type']=='image' and b['key']=='grades':b['caption']='Listado de grados y etiquetas del colegio.'
        if b['type']=='image' and b['key']=='diary_questions':b['caption']='Configuración del texto tipo orden y obligatoriedad de las preguntas.'
u.write_text(json.dumps(d,ensure_ascii=False,indent=2),encoding='utf8')

t=folder/'technical_content.json'
d=json.loads(t.read_text(encoding='utf8'))
for b in d['sections'][13]['blocks']:
    if b['type']=='table':b['widths']=[.40,.36,.24]
t.write_text(json.dumps(d,ensure_ascii=False,indent=2),encoding='utf8')
print('Final layout adjustments saved')
