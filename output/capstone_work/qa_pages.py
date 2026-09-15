from pathlib import Path
from PIL import Image,ImageDraw
from pypdf import PdfReader
w=Path(__file__).parent;r=PdfReader(w/'final.pdf');print('Pages',len(r.pages))
for i,p in enumerate(r.pages):
 t=p.extract_text() or '';print(i+1,len(t),t[:75].replace('\n',' '))
for start in range(0,len(r.pages),12):
 canvas=Image.new('RGB',(1440,((min(12,len(r.pages)-start)+3)//4)*480),'#ccc');dr=ImageDraw.Draw(canvas)
 for j in range(min(12,len(r.pages)-start)):
  im=Image.open(w/'final_render'/f'page-{start+j+1}.png');im.thumbnail((350,450));x=(j%4)*360;y=(j//4)*480;canvas.paste(im,(x,y+23));dr.text((x+4,y+4),str(start+j+1),fill='black')
 canvas.save(w/f'final_contact_{start//12}.png')
