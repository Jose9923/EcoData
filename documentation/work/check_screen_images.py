from PIL import Image, ImageOps, ImageDraw
from pathlib import Path
import json

root=Path(__file__).resolve().parents[1]
manifest=json.loads((root/'images/manifest.json').read_text(encoding='utf-8'))
for batch in range((len(manifest)+5)//6):
    sheet=Image.new('RGB',(1400,1560),'#dde4e6')
    draw=ImageDraw.Draw(sheet)
    for i,item in enumerate(manifest[batch*6:(batch+1)*6]):
        img=Image.open(root/'images'/item['file']).convert('RGB')
        img.thumbnail((680,480))
        x=(i%2)*700+10;y=(i//2)*520+30
        sheet.paste(img,(x,y))
        draw.text((x,y-20),item['file'],fill='black')
    sheet.save(root/'work'/f'screen_qa_{batch+1}.jpg')
print('contact sheets ready')
