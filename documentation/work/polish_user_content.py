"""Apply final editorial and illustration refinements to the user manual."""
from pathlib import Path
import json

p=Path(__file__).with_name('user_content.json')
d=json.loads(p.read_text(encoding='utf8'))
s=d['sections']
titles={0:'Propósito y alcance del manual',2:'Acceso y recuperación de la cuenta',
        6:'Consultar editar y exportar registros físicos',13:'Configuración de colegios y grupos',
        19:'Alcance del cargue CSV meteorológico'}
for idx,title in titles.items():s[idx]['title']=title

captions={
    'login':'Formulario de inicio de sesión.',
    'dashboard':'Vista del Dashboard y navegación para una cuenta de administración.',
    'profile':'Mi perfil y formularios para actualizar datos y contraseña.',
    'record_create':'Contexto y fecha de una nueva medición ambiental.',
    'records':'Listado de registros físicos y controles de filtrado.',
    'guides':'Guías disponibles para un estudiante.',
    'diary_student':'Instrucciones y disponibilidad de una actividad para el estudiante.',
    'diary_activity':'Formulario para crear una actividad de Diario de Campo.',
    'diary_review':'Resultado de revisión y retroalimentación docente.',
    'events':'Formulario de publicación de un evento ambiental.',
    'schools':'Formulario de colegio e identidad visual.',
    'grades':'Formulario de grado y etiqueta.',
    'courses':'Curso asociado a colegio y grado.',
    'users':'Listado de usuarios y asignación académica.',
    'user_import':'Archivo Excel y modo de importación de usuarios.',
    'categories':'Configuración de una categoría global de variables.',
    'variables':'Tipo unidad y límites de una variable física.',
    'stations':'Listado de estaciones del colegio.',
    'sensors':'Identificación y características de un sensor.',
}
for section in s:
    for b in section['blocks']:
        if b['type']=='image' and b['key'] in captions:
            b['caption']=captions[b['key']]
            if b['key']=='login':b['max_height']=325
            if b['key'] in ['diary_activity','profile']:b['max_height']=475

# Short role headings avoid splitting words across narrow table columns.
role_table=next(b for b in s[1]['blocks'] if b['type']=='table')
role_table['headers']=['Función','Superadmin','Admin de colegio','Docente','Estudiante']


def add_before(chapter,predicate,block):
    blocks=s[chapter]['blocks']
    if any(b.get('key')==block.get('key') for b in blocks if b['type']=='image'):return
    idx=next(i for i,b in enumerate(blocks) if predicate(b))
    blocks.insert(idx,block)


add_before(5,lambda b:b['type']=='table',{'type':'image','key':'record_variables','caption':'Campos de las variables y límites indicados durante la captura.'})
add_before(8,lambda b:b['type']=='table',{'type':'image','key':'diary_answers','caption':'Preguntas y acciones para guardar o enviar respuestas.'})
add_before(9,lambda b:b['type']=='steps',{'type':'image','key':'guide_form','caption':'Publicación de una guía PDF para un colegio o grupo.'})
add_before(10,lambda b:b['type']=='table',{'type':'image','key':'diary_questions','caption':'Configuración del texto tipo y opciones de las preguntas.'})
add_before(12,lambda b:b['type']=='h2',{'type':'image','key':'event_calendar','caption':'Consulta de eventos ambientales para usuarios autenticados.'})
add_before(18,lambda b:b['type']=='table',{'type':'image','key':'sensor_maintenance','caption':'Fechas de mantenimiento estado y observaciones del sensor.'})

note='Aunque el formulario permite configurar de 0 a 10 decimales, la base de datos de esta versión conserva cuatro cifras decimales en los límites y valores numéricos. Configure como máximo cuatro decimales si necesita preservar la precisión; solicite una ampliación técnica antes de trabajar con mayor detalle.'
if not any(b.get('text')==note for b in s[16]['blocks']):
    idx=next(i for i,b in enumerate(s[16]['blocks']) if b['type']=='table')
    s[16]['blocks'].insert(idx+1,{'type':'p','text':note})

p.write_text(json.dumps(d,ensure_ascii=False,indent=2),encoding='utf8')
print('User content polished')
