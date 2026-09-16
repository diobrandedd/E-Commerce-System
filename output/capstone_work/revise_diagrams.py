from pathlib import Path
from docx import Document
from docx.shared import Inches
import hashlib
exec((Path(__file__).parent/'diagrams.py').read_text(encoding='utf-8-sig').split('# IPO')[0])
names=['gantt','fdd','use_inventory','use_orders','use_pos','use_rts','use_costing','use_parcel']
old={hashlib.sha256((A/(n+'.png')).read_bytes()).hexdigest():n for n in names}
src=W.parent/'documents/Pyx_Tracker_Capstone_Chapters_I_III.docx'
doc=Document(src)
modules=['Integrated Inventory\nManagement','Product Costing\nand Pricing','Multi-Platform Orders\nand Waybills','Parcel Processing\nand Inventory Sync','Point of Sale','Return-to-Sender\nManagement']
subs=[['Receive stock','Track production','Confirm finished stock','Review movements'],['Maintain formulas','Calculate costs','Define parcel recipes','Maintain prices'],['Prepare order files','Validate order data','Stamp waybills','Finalize batches'],['Record packing scans','Confirm movements','Save shipment photos','Review parcel history'],['Authenticate cashier','Select stock and items','Post checkout','Issue receipt'],['Record return case','Classify condition','Review stock-in','Confirm sellable stock']]
# Top-down functional tree: root, six peer functions, then their subfunctions.
c=Chart('fdd',1350)
c.box(610,15,580,115,'Pyx Tracker',size=44)
c.line([(900,130),(900,200),(145,200),(1655,200)])
for i,(m,ss) in enumerate(zip(modules,subs)):
 x=10+i*300;mid=x+135
 c.line([(mid,200),(mid,270)])
 c.d.rectangle((x,270,x+270,450),fill='#fff4bb',outline='#333',width=3)
 c.text((x+10,294),str(i+1)+'. '+m,31,True,250,'center')
 c.line([(mid,450),(mid,500),(x+12,500),(x+12,1210)])
 for j,s in enumerate(ss):
  y=550+j*190;c.line([(x+12,y+65),(x+40,y+65)])
  c.box(x+40,y,235,130,f'{i+1}.{j+1} {s}',size=29)
c.save()
# Four months, four planning intervals each, three activities per feature.
c=Chart('gantt',1800);left=700;cw=67;rh=64;top=170
c.text((10,15),'System Development Schedule | May–August',43,True,1780,'center')
c.box(10,90,690,140,'Features and activities',size=35)
for k,m in enumerate(['May','June','July','August']):c.box(left+k*4*cw,90,4*cw,80,m,size=36)
for j in range(16):c.box(left+j*cw,170,cw,60,str(j%4+1),size=29)
plans=[[(1,4),(5,5),(6,6)],[(3,6),(7,7),(8,8)],[(5,9),(10,10),(11,11)],[(7,11),(12,12),(13,13)],[(9,13),(14,14),(15,15)],[(10,14),(15,15),(16,16)]]
for i,m in enumerate(modules):
 y=230+i*4*rh
 c.d.rectangle((10,y,1772,y+rh),fill='#e9edf0',outline='#333',width=2)
 c.text((25,y+14),f'Feature {i+1} — '+m.replace('\n',' '),31,True,1730)
 for k,label in enumerate(['Design and Development','Testing','Implementation']):
  yy=y+(k+1)*rh;c.d.rectangle((10,yy,left,yy+rh),outline='#999',width=2);c.text((40,yy+14),label,31,w=650)
  for j in range(16):
   c.d.rectangle((left+j*cw,yy,left+(j+1)*cw,yy+rh),outline='#aaa',width=2)
   if plans[i][k][0]<=j+1<=plans[i][k][1]:c.d.rectangle((left+j*cw+2,yy+12,left+(j+1)*cw-2,yy+rh-12),fill=['#557c99','#839a65','#a67751'][k])
c.save()
def actor(c,x,y,label):
 c.d.ellipse((x-27,y,x+27,y+54),outline='black',width=3)
 c.line([(x,y+54),(x,y+140)]);c.line([(x-65,y+90),(x+65,y+90)])
 c.line([(x,y+140),(x-50,y+215)]);c.line([(x,y+140),(x+50,y+215)])
 c.text((x-170,y+235),label,31,True,340,'center')
def oval(c,y,label):
 c.d.ellipse((630,y,1330,y+145),outline='black',width=3)
 ls=wrap(label,620,font(35));c.text((670,y+(145-len(ls)*43.75)/2),label,35,w=620,align='center')
data=[('inventory','Inventory and Production','Production\nManager',['Receive and monitor stock','Record production movements','Confirm finished-goods stock']),('orders','Online Orders','CSR Staff /\nTeam Leader',['Prepare order files','Stamp waybills','Finalize order batch']),('pos','Point of Sale','POS Cashier',['Open POS session','Complete checkout','Deduct selected stock']),('rts','Return-to-Sender','Authorized\nRTS Operator',['Record return case','Review stock-in session','Confirm sellable stock']),('costing','Product Costing','Executive',['Maintain product formula','Review parcel recipe cost','Maintain selling price']),('parcel','Parcel Documentation','Production\nManager',['Find tracking record','Capture shipment photo','Save photo documentation'])]
for name,title,role,ucs in data:
 c=Chart('use_'+name,1250)
 c.d.rectangle((440,20,1510,1220),outline='black',width=3)
 c.text((480,40),'«Subsystem»\nPyx Tracker — '+title,37,True,990)
 actor(c,195,410,role)
 ys=[220,480,740]
 for j,(y,label) in enumerate(zip(ys,ucs)):
  oval(c,y,label)
  if name!='pos' or j<2:c.line([(260,500),(360,500),(360,y+72),(630,y+72)])
 if name=='pos':
  c.line([(980,625),(980,740)],True,True);c.text((1010,655),'«include»',30,w=280)
  oval(c,1030,'Apply approved discount')
  c.line([(1330,1102),(1420,1102),(1420,552),(1330,552)],True,True)
  c.text((1130,950),'«extend»',30,w=270)
 c.save()
replaced=[]
for rel in doc.part.rels.values():
 if 'image' not in rel.reltype:continue
 part=rel.target_part;n=old.get(hashlib.sha256(part.blob).hexdigest())
 if n:part._blob=(A/(n+'.png')).read_bytes();replaced.append(n)
assert sorted(replaced)==sorted(names),replaced
# Preserve image widths and set correct aspect ratios for the replacement artwork.
for shape in doc.inline_shapes:
 rid=shape._inline.graphic.graphicData.pic.blipFill.blip.embed
 part=doc.part.related_parts[rid]
 for n in names:
  if part.blob==(A/(n+'.png')).read_bytes():
   im=Image.open(A/(n+'.png'));shape.height=int(shape.width*im.height/im.width)
for p in doc.paragraphs:
 if 'The 12-week relative schedule' in p.text:
  p.text='System development starts in May and ends in August. The chart groups the six system features, each with Design and Development, Testing, and Implementation activities. Numbered columns divide each month into four planning intervals. Activity bars show a proposed allocation within the confirmed May–August period; exact milestone dates should be reconciled with the project records.'
 if 'Provisional Development and Implementation Gantt Chart' in p.text:
  p.text=p.text.replace('Provisional Development and Implementation Gantt Chart','Development and Implementation Gantt Chart (May–August)')
 if 'The provisional Gantt and logical network diagrams' in p.text:
  p.text=p.text.replace('The provisional Gantt and logical network diagrams','The detailed Gantt activity allocation and logical network diagrams')
out=src.with_name('Pyx_Tracker_Capstone_Chapters_I_III_Revised.docx');doc.save(out)
print(out)
