from pathlib import Path
import shutil
exec((Path(__file__).parent/'diagrams.py').read_text(encoding='utf-8-sig').split('# IPO')[0])
old=A;A=W/'five_assets';A.mkdir(exist_ok=True)
for p in old.glob('*.png'):shutil.copy2(p,A/p.name)
mods=['Integrated Inventory\nManagement','Product Costing\nand Pricing','Multi-Platform Orders\nand Waybills','Parcel Processing\nand Inventory Sync','Return-to-Sender\nManagement']
ss=[['Receive materials','Track production','Confirm finished stock','Review movements'],['Maintain formulas','Calculate costs','Define parcel recipes','Maintain prices'],['Prepare order files','Validate order data','Stamp waybills','Finalize batches'],['Record packing scans','Confirm movements','Save shipment photos','Review parcel history'],['Record return case','Classify condition','Review stock-in','Confirm sellable stock']]
c=Chart('fdd',1370);c.box(600,10,600,110,'Pyx Tracker',size=45);c.line([(900,120),(900,200),(180,200),(1620,200)])
for i,(m,subs) in enumerate(zip(mods,ss)):
 x=10+i*360;mid=x+170;c.line([(mid,200),(mid,260)])
 c.d.rectangle((x,260,x+335,460),fill='#fff4bb',outline='black',width=3);c.text((x+14,285),str(i+1)+'. '+m,35,True,307,'center')
 c.line([(mid,460),(mid,510),(x+15,510),(x+15,1235)])
 for j,s in enumerate(subs):
  y=550+j*190;c.line([(x+15,y+60),(x+40,y+60)]);c.box(x+40,y,295,135,f'{i+1}.{j+1} {s}',size=33)
c.save()
c=Chart('gantt',1540);left=700;cw=67;rh=64
c.text((10,15),'System Development Schedule | May–August',43,True,1780,'center');c.box(10,90,690,140,'Features and activities',size=35)
for k,m in enumerate(['May','June','July','August']):c.box(left+k*4*cw,90,4*cw,80,m,size=36)
for j in range(16):c.box(left+j*cw,170,cw,60,str(j%4+1),size=29)
plans=[[(1,4),(5,5),(6,6)],[(3,6),(7,7),(8,8)],[(5,9),(10,10),(11,11)],[(7,11),(12,12),(13,13)],[(10,14),(15,15),(16,16)]]
for i,m in enumerate(mods):
 y=230+i*4*rh;c.d.rectangle((10,y,1772,y+rh),fill='#e9edf0',outline='#333',width=2);c.text((25,y+14),f'Feature {i+1} — '+m.replace('\n',' '),31,True,1730)
 for k,label in enumerate(['Design and Development','Testing','Implementation']):
  yy=y+(k+1)*rh;c.d.rectangle((10,yy,left,yy+rh),outline='#999',width=2);c.text((40,yy+14),label,31,w=650)
  for j in range(16):
   c.d.rectangle((left+j*cw,yy,left+(j+1)*cw,yy+rh),outline='#aaa',width=2)
   if plans[i][k][0]<=j+1<=plans[i][k][1]:c.d.rectangle((left+j*cw+2,yy+12,left+(j+1)*cw-2,yy+rh-12),fill=['#557c99','#839a65','#a67751'][k])
c.save()
c=Chart('agile',1020);c.box(10,420,280,150,'Requirements',size=35)
for i,m in enumerate(['Inventory','Costing and pricing','Orders and waybills','Parcel processing','Return-to-Sender']):
 y=20+i*190;c.text((350,y),f'Build {i+1}  {m}',32,True,1350)
 for x,t in [(430,'Design and\ndevelopment'),(940,'Testing'),(1390,'Implementation')]:c.box(x,y+48,390 if x==430 else 330,100,t,size=32)
 c.line([(290,495),(325,495),(325,y+98),(430,y+98)],True);c.line([(820,y+98),(940,y+98)],True);c.line([(1270,y+98),(1390,y+98)],True)
 c.line([(1555,y+148),(1555,y+175),(625,y+175),(625,y+148)],True,True)
c.save()
c=Chart('ipo',1200)
for i,(t,b) in enumerate([('INPUT','Raw-material and product data\nHoney stage quantities\nDepartment finished goods\nPackaging and supplies\nMaterial prices and formulas\nOrder sheets and waybills\nTracking codes and photos\nReturn condition and goods'),('PROCESS','Validate user access\nRecord inventory movements\nCalculate product and parcel costs\nPrepare and stamp order files\nFinalize order batches\nRecord packing movements\nSave shipment evidence\nConfirm reviewed RTS stock-in'),('OUTPUT','Updated stock balances\nProduction and movement history\nCost and selling-price records\nStamped PDFs and master Excel\nFinalized order and parcel records\nPacking and photo history\nReturn case and claim history\nConfirmed sellable RTS stock')]):
 c.box(10+i*605,40,565,1100,t,b,36)
 if i<2:c.line([(575+i*605,590),(615+i*605,590)],True)
c.save()
# One system boundary and exactly two actor symbols.
c=Chart('use_five',2300);c.im=Image.new('RGB',(2400,2300),'white');c.d=ImageDraw.Draw(c.im)
c.d.rectangle((300,20,2120,2250),outline='black',width=4)
c.text((330,55),'PYX TRACKER',48,True,1740,'center');c.text((330,120),'Inventory, Costing, Orders, Parcels and Returns',35,True,1740,'center')
def actor(x,y,label):
 c.d.ellipse((x-32,y-120,x+32,y-56),outline='black',width=4);c.line([(x,y-56),(x,y+85)]);c.line([(x-65,y),(x+65,y)]);c.line([(x,y+85),(x-55,y+180)]);c.line([(x,y+85),(x+55,y+180)]);c.text((x-135,y+210),label,35,True,270,'center')
def oval(x,y,label):
 c.d.ellipse((x-250,y-85,x+250,y+85),outline='#555',width=4);ls=wrap(label,430,font(35));c.text((x-215,y-len(ls)*22),label,35,w=430,align='center')
actor(135,1090,'Admin/\nExecutive');actor(2270,1090,'Staff')
goals=[('Record inventory movement','Update relevant stock balance'),('Calculate product and parcel cost','Read maintained formulas and prices'),('Finalize order batch','Validate prepared order data'),('Confirm packing movement','Record applicable stock change'),('Save shipment documentation','Store tracking number and photo'),('Confirm RTS stock-in','Credit reviewed sellable quantity')]
for i,(main,sub) in enumerate(goals):
 y=340+i*335;oval(910,y,main);oval(1740,y,sub)
 c.line([(1160,y),(1490,y)],True,True);c.text((1170,y-60),'«include»',31,w=310,align='center')
 c.line([(200,1090),(255,1090),(255,y),(660,y)])
 if i!=1:c.line([(2205,1090),(2160,1090),(2160,y-130),(1210,y-130),(1210,y),(1160,y)])
c.text((360,2130),'Costing and pricing are restricted to Admin/Executive.',33,True,1670,'center');c.save()
# Rebuild existing storyboards with only the five-feature navigation.
story=(W/'storyboard_refine.py').read_text(encoding='utf8').split('# APA-style')[0]
story=story[story.index('# Source-derived'):]
story=story.replace("for name,title,sub,cols,vals,field,btn,note in configs:","configs=[x for x in configs if x[0]!='pos']\nfor name,title,sub,cols,vals,field,btn,note in configs:")
story=story.replace('Dashboard\\n','').replace('\\nPOS','').replace('Executive reviews','Admin/Executive reviews')
exec(story)
# Five business functions, with identity as supporting data rather than a new feature.
c=Chart('database',1300);c.box(500,10,800,120,'Shared relational database',size=40)
items=[('Inventory and production','stocks, stock_entries\nhoney stage rows\ndept_inventory_rows'),('Costing and pricing','products, product_formula\nparcel_pack_tiers\nparcel_pack_tier_lines'),('Orders and waybills','csr_online_batches\ncsr_online_order_lines\nonline_order_parcels'),('Parcel processing','delivery_batches\ndelivery_batch_items\npacking and photo records'),('Return-to-Sender','rts_cases, rts_line_items\nrts_inventory_rows\nrts_stock_in_sessions')]
for i,(t,b) in enumerate(items):
 x=20+(i%2)*900;y=240+(i//2)*325;c.box(x,y,850,285,t,b,36)
c.text((930,990),'Supporting access records:\nusers, roles, departments',35,w=800);c.save()
rels=json.load(open(W/'relations.json'));c=Chart('erd_orders',1030)
for i,(p,ch,f) in enumerate([('csr_online_batches','csr_online_order_lines','batch_id'),('csr_online_order_lines','online_order_parcels','csr_line_id'),('online_order_parcels','delivery_batch_items','online_parcel_id')]):
 assert any(r['TABLE_NAME']==ch and r['COLUMN_NAME']==f and r['REFERENCED_TABLE_NAME']==p for r in rels)
 y=30+i*325;c.box(10,y,660,220,p,'id [primary key]',35);c.box(1050,y,740,220,ch,f+' [foreign key]',35);c.line([(670,y+115),(1050,y+115)],True);c.text((700,y+55),'1',36,w=60);c.text((850,y+55),'0..many',36,w=190);c.text((695,y+150),'has',30,w=335,align='center')
c.save()
# Reuse the source block structure for the business canvas with scoped labels.
orig=(W/'diagrams.py').read_text(encoding='utf8')
bmc=orig[orig.index('# BMC'):orig.index('# Gantt')]
for a,b in [('Record retail sales','Prepare order records'),('Transaction receipts','Order documentation'),('Walk-in POS','Online order processing'),('Walk-in retail buyers','Product buyers'),('Walk-in retail product sales','Product order revenue'),('Collection of approved receivables','Fulfilled product orders')]:bmc=bmc.replace(a,b)
exec(bmc)
c=Chart('topology',1120);c.box(650,410,530,180,'Router / access point',size=40)
for x,y,t in [(20,40,'Admin/Executive workstation'),(1230,40,'Staff workstation'),(20,890,'Staff mobile device'),(1230,890,'Application server')]:
 c.box(x,y,550,150,t,size=36);c.line([(x+275,y+150 if y<410 else y),(915,410 if y<410 else 590)])
c.text((160,670),'Logical local access topology\nRemote hosting requires an internet connection.',34,w=1480,align='center');c.save()
print('Five-feature diagrams complete')
