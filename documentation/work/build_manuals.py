"""Build the two EcoData manuals from reviewed content and Blade illustrations.

Run with the Codex bundled Python. Application sources and data are never changed.
"""
from __future__ import annotations

import json
import math
import re
from pathlib import Path
from xml.sax.saxutils import escape

from reportlab.lib import colors
from reportlab.lib.enums import TA_CENTER, TA_LEFT
from reportlab.lib.pagesizes import A4
from reportlab.lib.styles import ParagraphStyle, getSampleStyleSheet
from reportlab.lib.utils import ImageReader
from reportlab.pdfbase import pdfmetrics
from reportlab.pdfbase.ttfonts import TTFont
from reportlab.platypus import (
    BaseDocTemplate, Frame, PageTemplate, Paragraph, Spacer, PageBreak,
    Table, TableStyle, Image, KeepTogether, Flowable, CondPageBreak,
)
from reportlab.platypus.tableofcontents import TableOfContents

ROOT = Path(__file__).resolve().parents[2]
WORK = ROOT / 'documentation' / 'work'
IMAGES = ROOT / 'documentation' / 'images'
OUT = ROOT / 'output' / 'pdf'
OUT.mkdir(parents=True, exist_ok=True)
PAGE_W, PAGE_H = A4
MARGIN = 49
WIDTH = PAGE_W - MARGIN * 2
GREEN = colors.HexColor('#137B57')
INK = colors.HexColor('#17232B')
MUTED = colors.HexColor('#55646C')
LINE = colors.HexColor('#D9D9D9')
LIGHT = colors.HexColor('#F0F5F4')

for name, file in [('Segoe','segoeui.ttf'),('Segoe-Bold','segoeuib.ttf'),('Segoe-Italic','segoeuii.ttf')]:
    pdfmetrics.registerFont(TTFont(name, str(Path('C:/Windows/Fonts') / file)))
pdfmetrics.registerFontFamily('Segoe', normal='Segoe', bold='Segoe-Bold', italic='Segoe-Italic', boldItalic='Segoe-Bold')
pdfmetrics.registerFont(TTFont('Mono', 'C:/Windows/Fonts/consola.ttf'))

STYLES = {
    'body': ParagraphStyle('body', fontName='Segoe', fontSize=10.2, leading=14.7, textColor=INK, spaceAfter=7.2),
    'intro': ParagraphStyle('intro', fontName='Segoe', fontSize=11, leading=16.1, textColor=INK, spaceAfter=13),
    'h1': ParagraphStyle('h1', fontName='Segoe-Bold', fontSize=21, leading=26, textColor=colors.black, spaceAfter=13, keepWithNext=True),
    'h2': ParagraphStyle('h2', fontName='Segoe-Bold', fontSize=12.5, leading=17, textColor=colors.black, spaceBefore=12, spaceAfter=6.5, keepWithNext=True),
    'caption': ParagraphStyle('caption', fontName='Segoe', fontSize=8.5, leading=11.5, textColor=MUTED, spaceBefore=5, spaceAfter=13),
    'table': ParagraphStyle('table', fontName='Segoe', fontSize=8.6, leading=11.7, textColor=INK),
    'thead': ParagraphStyle('thead', fontName='Segoe-Bold', fontSize=8.8, leading=11.7, textColor=colors.white),
    'step': ParagraphStyle('step', fontName='Segoe', fontSize=10.2, leading=14.7, textColor=INK, leftIndent=20, firstLineIndent=-18, spaceAfter=6.5),
    'code': ParagraphStyle('code', fontName='Mono', fontSize=8.0, leading=11.2, textColor=INK, leftIndent=9, rightIndent=5, spaceBefore=0, spaceAfter=0, splitLongWords=True),
    'title': ParagraphStyle('title', fontName='Segoe-Bold', fontSize=32, leading=39, textColor=colors.black, spaceAfter=17),
    'subtitle': ParagraphStyle('subtitle', fontName='Segoe', fontSize=14.5, leading=21, textColor=colors.black, spaceAfter=15),
    'small': ParagraphStyle('small', fontName='Segoe', fontSize=9.3, leading=13.5, textColor=MUTED, spaceAfter=8),
}
for style in STYLES.values():
    style.allowWidows=0
    style.allowOrphans=0


def markup(value):
    s = escape(str(value))
    s = re.sub(r'\*\*(.+?)\*\*', r'<b>\1</b>', s)
    s = re.sub(r'`([^`]+)`', r'<font name="Mono" size="8.8">\1</font>', s)
    return s.replace('\n', '<br/>')


def para(text, style='body'):
    return Paragraph(markup(text), STYLES[style])


class ManualDoc(BaseDocTemplate):
    def __init__(self, filename, manual_title):
        super().__init__(str(filename), pagesize=A4, leftMargin=MARGIN, rightMargin=MARGIN,
                         topMargin=55, bottomMargin=48, title=f'EcoData {manual_title}',
                         author='EcoData', subject=f'{manual_title} de la plataforma EcoData',
                         pageCompression=1)
        self.manual_title = manual_title
        self.section_name = ''
        self.headings = []
        self.addPageTemplates(PageTemplate('manual', [Frame(MARGIN,48,WIDTH,PAGE_H-103,
                                                            leftPadding=0,rightPadding=0,
                                                            topPadding=0,bottomPadding=0)], self.page_draw))

    def beforeDocument(self):
        self.section_name = ''
        self.headings = []

    def page_draw(self, c, doc):
        c.saveState()
        if doc.page > 1:
            c.setFont('Segoe', 8.4)
            c.setFillColor(colors.black)
            c.drawString(MARGIN, PAGE_H-31, f'EcoData  |  {self.manual_title}')
            c.setFillColor(MUTED)
            c.setFont('Segoe', 7.8)
            c.drawRightString(PAGE_W-MARGIN, PAGE_H-31, 'Edición 1.0  ·  Octubre de 2026')
        c.setFont('Segoe', 8)
        c.setFillColor(MUTED)
        c.drawString(MARGIN, 25, 'EcoData  ·  Documentación de la aplicación')
        c.drawRightString(PAGE_W-MARGIN, 25, str(doc.page))
        c.restoreState()

    def afterFlowable(self, flowable):
        if isinstance(flowable, Paragraph) and getattr(flowable, 'section_key', None):
            text = flowable.getPlainText()
            key = flowable.section_key
            self.canv.bookmarkPage(key)
            self.canv.addOutlineEntry(text, key, 0, False)
            self.notify('TOCEntry', (0,text,self.page,key))
            self.headings.append({'title':text,'page':self.page})


class Diagram(Flowable):
    """Compact vector diagrams with explicit relationships and readable labels."""
    def __init__(self, key):
        super().__init__()
        self.key = key
        self.width = WIDTH
        self.height = {'architecture':310,'data_model':390,'data_model_diary':295,
                       'diary_states':240,'request_flow':200}.get(key,300)

    def wrap(self, aw, ah):
        return self.width,self.height

    def label(self,x,y,text,size=8.5,bold=False,color=INK,align='center'):
        c=self.canv
        c.setFont('Segoe-Bold' if bold else 'Segoe',size)
        c.setFillColor(color)
        if align=='center': c.drawCentredString(x,y,text)
        else: c.drawString(x,y,text)

    def box(self,x,y,w,h,title,lines=()):
        c=self.canv
        c.setFillColor(LIGHT); c.setStrokeColor(colors.HexColor('#ADBCB6'))
        c.roundRect(x,y,w,h,6,fill=1,stroke=1)
        self.label(x+w/2,y+h-19,title,9,True)
        for i,line in enumerate(lines): self.label(x+w/2,y+h-35-i*12,line,8.1)

    def arrow(self,x1,y1,x2,y2,label='',dx=0,dy=0):
        c=self.canv; c.setStrokeColor(GREEN); c.setFillColor(GREEN); c.setLineWidth(1.1)
        c.line(x1,y1,x2,y2)
        a=math.atan2(y2-y1,x2-x1); l=5
        p=c.beginPath();p.moveTo(x2,y2)
        p.lineTo(x2-l*math.cos(a-.45),y2-l*math.sin(a-.45))
        p.lineTo(x2-l*math.cos(a+.45),y2-l*math.sin(a+.45));p.close()
        c.drawPath(p,fill=1,stroke=0)
        if label: self.label((x1+x2)/2+dx,(y1+y2)/2+dy,label,7.7)

    def draw(self):
        if self.key=='architecture':
            self.box(0,230,142,70,'Navegador', ['Blade y Bootstrap','JavaScript y recursos CDN'])
            self.box(177,230,142,70,'Nginx', ['Puerto local 8081','Raíz public'])
            self.box(354,230,142,70,'PHP FPM', ['Laravel 13','Rutas y controladores'])
            self.arrow(142,267,177,267,'HTTP',dy=10)
            self.arrow(319,267,354,267,'9000',dy=10)
            self.box(177,121,142,72,'Aplicación', ['Sesión y roles','Validación y Eloquent'])
            self.arrow(425,230,319,177)
            self.box(0,16,142,72,'MySQL 8.4', ['Datos por colegio','Volumen persistente'])
            self.box(177,16,142,72,'Almacenamiento', ['Archivos privados','Imágenes institucionales'])
            self.box(354,16,142,72,'Correo y soporte', ['Transporte configurado','Redis según configuración'])
            self.arrow(204,121,105,88)
            self.arrow(248,121,248,88)
            self.arrow(292,121,389,88)
        elif self.key=='data_model':
            self.box(170,320,155,64,'schools', ['Colegio y personalización'])
            self.box(0,222,145,63,'grades / courses',['Estructura académica'])
            self.box(176,222,145,63,'users',['Cuenta y roles Spatie'])
            self.box(352,222,145,63,'weather_stations',['Estación del colegio'])
            self.arrow(190,320,72,285,'1 a N',dx=-11,dy=9)
            self.arrow(248,320,248,285,'1 a N',dx=24)
            self.arrow(307,320,424,285,'1 a N',dx=12,dy=9)
            self.box(0,112,145,67,'physical_variables',['Categoría global','Definición por colegio'])
            self.box(176,112,145,67,'physical_variable_records',['Autor y contexto','Fecha y origen'])
            self.box(352,112,145,67,'sensors',['Estación y variable'])
            self.arrow(248,222,248,179,'1 a N',dx=24)
            self.arrow(424,222,424,179,'1 a N',dx=24)
            self.box(94,5,230,62,'physical_variable_record_values',['Un valor tipado por registro y variable'])
            self.arrow(72,112,140,67,'1 a N',dx=-22,dy=1)
            self.arrow(248,112,248,67,'1 a N',dx=24)
        elif self.key=='data_model_diary':
            self.box(0,213,145,66,'field_diary_activities',['Colegio y destinatarios','Vigencia y tipo'])
            self.box(176,213,145,66,'field_diary_questions',['Tipo y opciones','Orden y obligatoriedad'])
            self.box(352,213,145,66,'users',['Estudiante / docente'])
            self.arrow(145,248,176,248,'1 a N',dy=10)
            self.box(0,95,145,67,'field_diary_submissions',['Actividad y estudiante','Estado y revisión'])
            self.box(176,95,145,67,'field_diary_answers',['Respuesta y archivo','Entrega y pregunta'])
            self.arrow(72,213,72,162,'1 a N',dx=-24)
            self.arrow(248,213,248,162,'1 a N',dx=24)
            self.arrow(145,130,176,130,'1 a N',dy=10)
            self.box(352,95,145,67,'laboratory_guides',['PDF por audiencia','Publicación y autor'])
            self.box(74,0,164,59,'environmental_events',['Colegio y periodo'])
            self.box(300,0,197,59,'environmental_event_acknowledgements',['Evento y usuario únicos'])
            self.arrow(238,29,300,29,'1 a N',dy=10)
        elif self.key=='diary_states':
            self.box(0,155,135,63,'Borrador',['Editable por estudiante'])
            self.box(181,155,135,63,'Enviado',['Bloqueado para edición'])
            self.box(362,155,135,63,'Revisado',['Revisión y nota opcional'])
            self.box(181,25,135,63,'Devuelto',['Puede corregirse','si sigue disponible'])
            self.arrow(135,186,181,186,'Enviar',dy=11)
            self.arrow(316,186,362,186,'Revisar',dy=11)
            self.arrow(226,155,226,88,'Devolver',dx=-30)
            self.arrow(272,88,272,155,'Reenviar',dx=30)
            self.arrow(181,57,70,155,'Guardar',dx=-10,dy=-8)
            self.label(248,4,'La vigencia y la asignación de la actividad controlan el acceso.',8.7)
        elif self.key=='request_flow':
            labels=[('Petición','Ruta web'),('Acceso','auth y rol'),('Contexto','Colegio'),('Validación','Request'),('Persistencia','Eloquent')]
            for i,(title,sub) in enumerate(labels):
                x=i*101
                self.box(x,102,92,64,title,[sub])
                if i<4:self.arrow(x+92,134,x+101,134)
            self.box(151,0,195,64,'Respuesta',['Blade, redirección o archivo'])
            self.arrow(450,102,346,40)
            self.label(248,81,'Autorizaciones adicionales en cada controlador',8.6)


def table(headers, rows, widths=None):
    n=len(headers)
    if not widths:
        if n==2: widths=[WIDTH*.31,WIDTH*.69]
        elif n==3: widths=[WIDTH*.23,WIDTH*.31,WIDTH*.46]
        elif n==4: widths=[WIDTH*.20,WIDTH*.20,WIDTH*.25,WIDTH*.35]
        elif n==5: widths=[WIDTH*.32]+[WIDTH*.17]*4
        else: widths=[WIDTH/n]*n
    elif sum(widths)<=1.05: widths=[WIDTH*x for x in widths]
    data=[[para(x,'thead') for x in headers]]+[[para(x,'table') for x in row] for row in rows]
    t=Table(data,colWidths=widths,repeatRows=1,hAlign='LEFT')
    t.setStyle(TableStyle([
        ('BACKGROUND',(0,0),(-1,0),colors.HexColor('#254A53')),
        ('ROWBACKGROUNDS',(0,1),(-1,-1),[colors.white,LIGHT]),
        ('GRID',(0,0),(-1,-1),.45,LINE),
        ('VALIGN',(0,0),(-1,-1),'MIDDLE'),
        ('LEFTPADDING',(0,0),(-1,-1),8),('RIGHTPADDING',(0,0),(-1,-1),8),
        ('TOPPADDING',(0,0),(-1,-1),7),('BOTTOMPADDING',(0,0),(-1,-1),7),
    ]))
    return [t,Spacer(1,10)]


def image_path(key):
    aliases={
        'record_create':['record_create','physical_record_create','physical_records_create'],
        'records':['records','physical_records'],
        'diary_activity':['diary_activity','field_diary_activity'],
        'diary_student':['diary_student','student_diary'],
        'diary_review':['diary_review','submission_review'],
        'user_import':['user_import','users_import'],
    }
    for k in aliases.get(key,[key]):
        for ext in ['.png','.jpg','.jpeg']:
            p=IMAGES/(k+ext)
            if p.exists():return p
    matches=list(IMAGES.glob(f'*{key}*.png'))
    return matches[0] if len(matches)==1 else None


def build(name,kind):
    content=json.loads((WORK/name).read_text(encoding='utf-8-sig'))
    title='Manual de usuario' if kind=='user' else 'Manual técnico'
    out=OUT/('EcoData_Manual_de_Usuario.pdf' if kind=='user' else 'EcoData_Manual_Tecnico.pdf')
    doc=ManualDoc(out,title)
    story=[]
    logo=ROOT/'src/public/img/logo-login.jpeg'
    story += [Spacer(1,14),Image(str(logo),width=204,height=136,hAlign='LEFT'),Spacer(1,34)]
    story.append(para(title,'title'))
    story.append(para('EcoData','subtitle'))
    description=(
        'Guía de uso para estudiantes, docentes, administradores de colegio y administradores generales.'
        if kind=='user' else
        'Arquitectura, instalación, datos, seguridad y operación de la plataforma de educación ambiental.'
    )
    story += [para(description,'intro'),Spacer(1,28)]
    story.append(para('Edición 1.0  ·  15 de octubre de 2026','small'))
    story.append(para('Referencia del código  e9bc600','small'))
    story.append(para('Plataforma web para organizar mediciones ambientales y actividades pedagógicas por institución educativa.','body'))
    story.append(PageBreak())
    story.append(para('Contenido','h1'))
    story.append(para('Seleccione un capítulo para ir a su página. Los marcadores del PDF permiten volver a navegar por los capítulos.','small'))
    toc=TableOfContents()
    toc.levelStyles=[ParagraphStyle('toc',fontName='Segoe',fontSize=10,leading=14,
                                   leftIndent=0,firstLineIndent=0,spaceBefore=8,textColor=INK)]
    toc.tableStyle=TableStyle([('LEFTPADDING',(0,0),(-1,-1),0),('RIGHTPADDING',(0,0),(-1,-1),0),
                              ('TOPPADDING',(0,0),(-1,-1),0),('BOTTOMPADDING',(0,0),(-1,-1),0)])
    story.append(toc)
    figure=0
    missing=[]
    for i,section in enumerate(content['sections'],1):
        if i==1:
            story.append(PageBreak())
        else:
            minimum=330
            if section.get('blocks') and section['blocks'][0]['type']=='image':
                first=section['blocks'][0]
                key=first['key']
                path=image_path(key)
                if path:
                    iw,ih=ImageReader(str(path)).getSize()
                    ih=min(WIDTH*ih/iw,first.get('max_height',440))
                else:
                    ih=Diagram(key).height
                heading_height=26*math.ceil((len(section['title'])+3)/44)
                intro_height=16.1*math.ceil(len(section.get('intro',''))/86)
                minimum=min(675,heading_height+intro_height+ih+65)
            story.append(CondPageBreak(minimum))
            story.append(Spacer(1,16))
        heading=para(f'{i}  {section["title"]}','h1'); heading.section_key=f'chapter-{i}'
        story.append(heading)
        if section.get('intro'):story.append(para(section['intro'],'intro'))
        for b in section.get('blocks',[]):
            typ=b['type']
            if typ in ['p','note']:story.append(para(b['text']))
            elif typ=='h2':story.append(para(b['text'],'h2'))
            elif typ in ['steps','list','bullets']:
                for j,s in enumerate(b['items'],1):
                    prefix=f'{j}.' if typ=='steps' else '•'
                    story.append(para(f'{prefix}  {s}','step'))
            elif typ=='table':story.extend(table(b['headers'],b['rows'],b.get('widths')))
            elif typ=='code':
                # Preserve blank lines and indentation; wrap command lines naturally.
                story.append(Spacer(1,5))
                for line in b['text'].splitlines():
                    story.append(Paragraph(escape(line).replace(' ','&#160;') or '&#160;',STYLES['code']))
                story.append(Spacer(1,10))
            elif typ=='image':
                key=b['key']
                if key in ['architecture','data_model','data_model_diary','diary_states','request_flow']:
                    img=Diagram(key)
                    suffix=''
                else:
                    path=image_path(key)
                    if not path: missing.append(key); continue
                    iw,ih=ImageReader(str(path)).getSize()
                    w=min(WIDTH,WIDTH*b.get('scale',1))
                    h=w*ih/iw
                    limit=b.get('max_height',440)
                    if h>limit:w*=limit/h;h=limit
                    img=Image(str(path),width=w,height=h,hAlign='CENTER')
                    suffix=' Vista ilustrativa con datos de ejemplo.'
                figure+=1
                cap=para(f'Figura {figure}. {b.get("caption",key)}{suffix}','caption')
                story.append(KeepTogether([Spacer(1,5),img,cap]))
            elif typ=='pagebreak':story.append(PageBreak())
            else:raise ValueError(f'Unknown block: {typ}')
    if missing:
        print('Missing image keys:', sorted(set(missing)))
    doc.multiBuild(story)
    summary={'file':str(out),'chapters':doc.headings,'figures':figure,'missing_images':sorted(set(missing))}
    (WORK/f'{kind}_build_report.json').write_text(json.dumps(summary,ensure_ascii=False,indent=2),encoding='utf8')
    print(json.dumps(summary,ensure_ascii=False))


if __name__=='__main__':
    import sys
    kinds=sys.argv[1:] or ['user','technical']
    for kind in kinds:
        build('user_content.json' if kind=='user' else 'technical_content.json',kind)
