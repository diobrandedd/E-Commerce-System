from pathlib import Path
from docx import Document
from PIL import Image,ImageDraw
import pypdfium2 as pdfium,json
w=Path('output/capstone_work')
pdf=pdfium.PdfDocument(w/'reference.pdf');print('reference pages',len(pdf))
for start in range(0,len(pdf),12):
 canvas=Image.new('RGB',(1200, ((min(12,len(pdf)-start)+3)//4)*430),'#ccc');dr=ImageDraw.Draw(canvas)
 for j,p in enumerate(list(pdf)[start:start+12]):
  im=p.render(scale=.4).to_pil();im.thumbnail((290,400));x=(j%4)*300;y=(j//4)*430;canvas.paste(im,(x,y+22));dr.text((x+5,y+3),str(start+j+1),fill='black')
 canvas.save(w/f'ref_contact_{start//12}.png')
d=Document('C:/Users/Admin/Downloads/OUTLINE-AND-SAMPLE-DOCU-CHPATER-123 (2).docx')
for i in [0,13,41,42,43,91,319,355,381,405,418,448,621]:
 e=list(d.element.body)[i]
 from docx.text.paragraph import Paragraph
 p=Paragraph(e,d)
 print(i,p.text[:60], 'style',p.style.name,'align',p.alignment,'line',p.paragraph_format.line_spacing,'runs',[(r.font.name,r.font.size.pt if r.font.size else None,r.bold) for r in p.runs][:4])
for e in d.element.body:
 imgs=e.findall('.//{http://schemas.openxmlformats.org/drawingml/2006/main}blip')
 if imgs:
  print('IMAGE AT',list(d.element.body).index(e),[(d.part.related_parts[x.get('{http://schemas.openxmlformats.org/officeDocument/2006/relationships}embed')].partname) for x in imgs])

