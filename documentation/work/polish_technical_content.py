"""Editorial pass preserving reviewed technical facts."""
from pathlib import Path
import json

p=Path(__file__).with_name('technical_content.json')
d=json.loads(p.read_text(encoding='utf8'))
s=d['sections']
titles={0:'Propósito y alcance técnico',2:'Plataforma y servicios',
        6:'Diccionario de identidad y estructura académica',7:'Diccionario de variables y mediciones',
        8:'Diccionario de estaciones y sensores',9:'Diccionario de guías y eventos',
        10:'Diccionario del Diario de Campo',12:'Acceso y aislamiento por colegio',
        16:'Exportaciones y almacenamiento',17:'Correo y procesos auxiliares',
        19:'Operación y diagnóstico'}
for idx,title in titles.items():s[idx]['title']=title
s[0]['intro']='Este manual describe la arquitectura y la operación técnica de EcoData. Está dirigido a desarrolladores, administradores de infraestructura y responsables de mantenimiento.'
for b in s[0]['blocks']:
    if b['type']=='p' and b['text'].startswith('La documentación corresponde'):
        b['text']='La revisión abarca código, migraciones, dependencias bloqueadas, rutas y configuración. La aplicación y su suite de pruebas no se ejecutaron durante esta revisión. Los procedimientos de instalación y operación deben validarse en el entorno independiente descrito antes de aplicarlos a una instalación con datos.'
    if b['type']=='table':
        for row in b['rows']:
            if row[0]=='Fecha de elaboración':row[1]='15 de octubre de 2026. Revisión del código e9bc600.'
            if row[0]=='Datos de ejemplo':row[1]='Ilustraciones con datos ficticios y ejemplos de configuración sin credenciales del entorno.'
            if row[0]=='Resultado de pruebas':row[1]='Revisión estática del código. Los casos de aceptación del capítulo de pruebas se proponen para ejecución posterior.'
s[4]['intro']=s[4]['intro'].replace(' Esta guía no certifica un despliegue ejecutado.','')

for section in s:
    for b in section['blocks']:
        if b['type']=='image':
            if b['key']=='diary_states':b['caption']='Flujo de trabajo habitual del Diario de Campo. La actividad debe seguir disponible para corregir una devolución.'
            if b['key']=='data_model':b['caption']='Relaciones seleccionadas de identidad e infraestructura. El diccionario detalla las claves y restricciones completas.'
            if b['key']=='record_create':b['caption']='Los campos del formulario alimentan la validación dinámica del controlador de registros.'
        if b['type']=='code':
            text=b['text']
            # Explicit Bash continuations keep long backup commands copyable.
            text=text.replace('mysqldump -u root -p --single-transaction --routines --triggers --events --databases EcoData_db --result-file=/tmp/ecodata.sql',
                'mysqldump -u root -p \\\n  --single-transaction --routines --triggers --events \\\n  --databases EcoData_db --result-file=/tmp/ecodata.sql')
            b['text']=text

p.write_text(json.dumps(d,ensure_ascii=False,indent=2),encoding='utf8')
print('Technical content polished')
