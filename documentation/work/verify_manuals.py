"""Render PDFs and record structural/layout checks for the manuals."""
from pathlib import Path
import json
import subprocess
import sys

from PIL import Image, ImageDraw
from pypdf import PdfReader
import pdfplumber

ROOT=Path(__file__).resolve().parents[2]
OUT=ROOT/'output/pdf'
QA=ROOT/'documentation/work/qa'
POPPLER=Path('C:/Users/josev/.cache/codex-runtimes/codex-primary-runtime/dependencies/native/poppler/Library/bin/pdftoppm.exe')


def verify(path):
    kind='user' if 'Usuario' in path.name else 'technical'
    dest=QA/kind
    dest.mkdir(parents=True,exist_ok=True)
    # Remove only prior QA images in this exact task-local directory.
    resolved_dest=dest.resolve()
    if not resolved_dest.is_relative_to(QA.resolve()):raise RuntimeError('QA directory escaped workspace')
    for pattern in ['page-*.png','review-*.jpg']:
        for old in dest.glob(pattern):
            if old.resolve().parent!=resolved_dest:raise RuntimeError('Unexpected QA file location')
            old.unlink()
    result=subprocess.run([str(POPPLER),'-r','105','-png',str(path),str(dest/'page')],capture_output=True,text=True)
    if result.returncode: raise RuntimeError(result.stderr)
    reader=PdfReader(path)
    text='\n'.join(p.extract_text() or '' for p in reader.pages)
    suspicious=[x for x in ['Undefined','undefined','{{','@foreach','@endif','TODO','Lorem ipsum','[INSERT'] if x in text]
    outside=[]
    page_stats=[]
    with pdfplumber.open(path) as pdf:
        for i,p in enumerate(pdf.pages,1):
            bad=[c for c in p.chars if c['x0']<-1 or c['x1']>p.width+1 or c['top']<-1 or c['bottom']>p.height+1]
            if bad:outside.append({'page':i,'chars':len(bad),'sample':''.join(c['text'] for c in bad[:30])})
            page_stats.append({'page':i,'chars':len(p.chars),'images':len(p.images)})
    pngs=sorted(dest.glob('page-*.png'))
    # Two pages per contact sheet preserve legibility while accelerating visual review.
    for start in range(0,len(pngs),2):
        cells=[]
        for f in pngs[start:start+2]:
            img=Image.open(f).convert('RGB')
            img.thumbnail((820,1160))
            cell=Image.new('RGB',(850,1200),'#DFE5E3')
            cell.paste(img,((850-img.width)//2,28))
            draw=ImageDraw.Draw(cell);draw.text((18,8),f'{kind} - {f.stem}',fill='black')
            cells.append(cell)
        sheet=Image.new('RGB',(850*len(cells),1200),'white')
        for j,cell in enumerate(cells):sheet.paste(cell,(850*j,0))
        sheet.save(dest/f'review-{start+1:02d}-{start+len(cells):02d}.jpg',quality=88)
    report={'pdf':str(path),'pages':len(reader.pages),'bytes':path.stat().st_size,'characters':len(text),
            'suspicious_text':suspicious,'outside_page':outside,'page_stats':page_stats,
            'rendered_pages':len(pngs),'outline_entries':len(reader.outline)}
    (dest/'validation.json').write_text(json.dumps(report,indent=2,ensure_ascii=False),encoding='utf8')
    (dest/'extracted.txt').write_text(text,encoding='utf8')
    print(json.dumps({k:v for k,v in report.items() if k!='page_stats'},ensure_ascii=False))


if __name__=='__main__':
    for path in OUT.glob('EcoData_Manual*.pdf'):
        if not sys.argv[1:] or any(k.lower() in path.name.lower() for k in sys.argv[1:]): verify(path)
