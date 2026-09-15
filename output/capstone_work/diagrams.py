from pathlib import Path
from PIL import Image,ImageDraw,ImageFont
import textwrap,math,json,hashlib
W=Path(__file__).parent; A=W/'assets';A.mkdir(exist_ok=True)
FONT='C:/Windows/Fonts/arial.ttf'; BOLD='C:/Windows/Fonts/arialbd.ttf'
def font(s=32,b=False):return ImageFont.truetype(BOLD if b else FONT,s)
def wrap(s,w,f):
 out=[]
 for para in s.split('\n'):
  line=''
  for wd in para.split():
   if f.getlength(line+' '+wd)>w and line:out.append(line);line=wd
   else:line=(line+' '+wd).strip()
  out.append(line)
 return out
class Chart:
 def __init__(self,name,h=1200):self.name=name;self.im=Image.new('RGB',(1800,h),'white');self.d=ImageDraw.Draw(self.im)
 def text(self,xy,s,size=32,b=False,w=1700,align='left'):
  f=font(size,b); lines=wrap(s,w,f);x,y=xy
  for line in lines:
   xx=x+(w-f.getlength(line))/2 if align=='center' else x
   self.d.text((xx,y),line,font=f,fill='black');y+=size*1.25
  return y
 def box(self,x,y,w,h,title,body='',size=32,fill='#eef1f3'):
  self.d.rectangle((x,y,x+w,y+h),fill='white',outline='#333333',width=3)
  if body:
   ls=wrap(title,w-30,font(size,True));hh=len(ls)*size*1.25+24;self.d.rectangle((x,y,x+w,y+hh),fill=fill,outline='#333333',width=2)
   self.text((x+15,y+10),title,size,True,w-30);self.text((x+18,y+hh+16),body,size,False,w-36)
  else:self.text((x+12,y+max(12,(h-len(wrap(title,w-24,font(size,True)))*size*1.25)/2)),title,size,True,w-24,'center')
 def line(self,points,arrow=False,dashed=False):
  if dashed:
   for a,b in zip(points,points[1:]):
    n=max(1,int(math.dist(a,b)/14))
    for j in range(0,n,2):self.d.line((a[0]+(b[0]-a[0])*j/n,a[1]+(b[1]-a[1])*j/n,a[0]+(b[0]-a[0])*min(j+1,n)/n,a[1]+(b[1]-a[1])*min(j+1,n)/n),fill='#333',width=3)
  else:self.d.line(points,fill='#333',width=3)
  if arrow:
   a,b=points[-2:]; ang=math.atan2(b[1]-a[1],b[0]-a[0]);self.d.line([ (b[0]-18*math.cos(ang-.45),b[1]-18*math.sin(ang-.45)),b,(b[0]-18*math.cos(ang+.45),b[1]-18*math.sin(ang+.45))],fill='#333',width=3)
 def save(self):self.im.save(A/(self.name+'.png'))
# IPO
c=Chart('ipo',1250)
for i,(t,b) in enumerate([
('INPUT','Product and supplier data\nRaw and finished stock quantities\nPurchase prices and formulas\nOrder sheets and waybill PDFs\nTracking and product scans\nShipment and return photos\nPOS basket and stock source\nReturn condition and case data'),
('PROCESS','Validate role and input\nRecord production movements\nCompute unit and parcel cost\nPrepare and match order files\nConfirm barcode stock changes\nStore shipment evidence\nPost sales and receipts\nReview RTS stock-in session'),
('OUTPUT','Updated on-hand balances\nProduction and movement history\nCost and pricing information\nStamped PDFs and master Excel\nTraceable packing records\nBefore-shipment photo record\nSales history and receipts\nRTS case and sellable balances')]):
 c.box(15+i*605,80,565,1060,t,b,36)
 if i<2:c.line([(580+i*605,605),(620+i*605,605)],True)
c.save()
# Incremental agile diagram
c=Chart('agile',1200);c.box(10,485,275,150,'Requirements',size=35)
mods=['Inventory','Costing','Online orders','Parcel processing','POS','RTS']
for i,m in enumerate(mods):
 y=30+i*185;c.text((350,y),f'Build {i+1}  {m}',32,True,w=1350)
 for x,txt in [(430,'Design and\ndevelopment'),(940,'Testing'),(1390,'Implementation')]:c.box(x,y+48,390 if x==430 else 330,96,txt,size=32)
 c.line([(285,560),(325,560),(325,y+96),(430,y+96)],True)
 c.line([(820,y+96),(940,y+96)],True);c.line([(1270,y+96),(1390,y+96)],True)
 c.line([(1555,y+144),(1555,y+165),(625,y+165),(625,y+144)],True,True)
c.save()
# BMC retains 9-block layout
c=Chart('bmc',1780)
blocks=[(10,10,345,1270,'Key partners','Raw-material suppliers\nPackaging suppliers\nCourier partners\nShopee SPX\nWebsite and Facebook channels'),(365,10,345,650,'Key activities','Receive materials\nProduce and pack goods\nPrepare online orders\nRecord retail sales\nHandle returns'),(365,670,345,610,'Key resources','Product stocks\nCompany personnel\nPyx Tracker\nWeb server and database\nScanners and cameras'),(720,10,350,1270,'Value proposition','Traceable food-product operations\nConsistent order preparation\nVisible stock movements\nDocumented shipments\nRepeatable cost calculations'),(1080,10,345,650,'Customer relationships','CSR order coordination\nTransaction receipts\nReturn and claim follow-up'),(1080,670,345,610,'Channels','Shopee SPX\nVIP website orders\nFacebook orders\nWalk-in POS'),(1435,10,355,1270,'Customer segments','Online buyers\nWalk-in retail buyers\nCustomers requesting return assistance'),(10,1290,880,465,'Cost structure','Raw materials and manufacturing\nProduct and parcel packaging\nPersonnel and operating expenses\nSystem hosting and maintenance'),(900,1290,890,465,'Revenue streams','Online product sales\nWalk-in retail product sales\nCollection of approved receivables')]
for x,y,w,h,t,b in blocks:c.box(x,y,w,h,t,b,36)
c.save()
# Gantt relative schedule, not invented completed dates
c=Chart('gantt',1170);left=580;cw=95;top=150;rh=82
c.box(10,20,1780,90,'Provisional 12-week implementation schedule',size=40)
c.box(10,top,left-10,100,'Activity',size=34)
for j in range(12):c.box(left+j*cw,top,cw,100,str(j+1),size=32)
rows=[('Requirements and workflow review',1,2),('Database and interface design',2,4),('Build 1  Inventory',3,5),('Build 2  Costing',4,6),('Build 3  Online orders',5,7),('Build 4  Parcel processing',6,8),('Build 5  POS',7,9),('Build 6  RTS',8,10),('Integration and user evaluation',9,11),('Deployment and documentation',11,12)]
for i,(name,a,b) in enumerate(rows):
 y=250+i*rh;c.box(10,y,left-10,rh,name,size=30)
 for j in range(12):
  c.d.rectangle((left+j*cw,y,left+(j+1)*cw,y+rh),outline='#aaa',width=2)
  if a<=j+1<=b:c.d.rectangle((left+j*cw+3,y+13,left+(j+1)*cw-3,y+rh-13),fill='#667b8d')
c.text((15,1090),'Week numbers indicate planned sequence, not verified calendar dates.',31,w=1760);c.save()
# FDD overview and deeper breakdown
c=Chart('fdd',1480);c.box(520,10,800,130,'0  Pyx Tracker',size=45);c.line([(920,140),(920,210),(40,210),(40,1330)])
fd=[('1  Integrated Inventory Management System',['1.1 Receive stock','1.2 Track production','1.3 Confirm finished stock','1.4 Review stock movements']),('2  Product Costing and Pricing Calculator',['2.1 Maintain formulas','2.2 Calculate product cost','2.3 Define parcel recipes','2.4 Maintain selling prices']),('3  Multi-Platform Order Processing and Waybill Management',['3.1 Prepare VIP / SPX files','3.2 Validate order data','3.3 Stamp PDF and Excel','3.4 Finalize order batches']),('4  Parcel Processing and Inventory Synchronization',['4.1 Record packing scans','4.2 Confirm stock movements','4.3 Save shipment photos','4.4 Review parcel history']),('5  Point of Sale System',['5.1 Authenticate cashier','5.2 Select stock and items','5.3 Post checkout','5.4 Issue receipt and history']),('6  Return-to-Sender Management',['6.1 Record return case','6.2 Classify return condition','6.3 Review barcode stock-in','6.4 Confirm sellable balance'])]
for i,(title,subs) in enumerate(fd):
 y=260+i*192;c.line([(40,y+60),(135,y+60)]);c.box(135,y,560,128,title,size=34);c.line([(695,y+64),(780,y+64)]);c.box(780,y,995,164,'\n'.join(subs),size=33)
c.save()
# UML use cases, split by workflow to keep associations legible
for name,actor,uc,extra in [('use_inventory','Executive /\nProduction manager',['Receive and monitor stock','Record production movements','Confirm finished-goods stock'],None),('use_orders','CSR staff / TL',['Prepare order files','Stamp waybills','Finalize order batch'],None),('use_pos','POS cashier',['Open POS session','Complete checkout','Deduct selected stock'],('Apply approved discount','Complete checkout')),('use_rts','Authorized RTS\noperator',['Record return case','Review stock-in session','Confirm sellable stock'],None)]:
 c=Chart(name,930);c.d.rectangle((490,20,1760,890),outline='black',width=4);c.text((520,40),'Pyx Tracker',38,True,w=1180)
 c.d.ellipse((190,340,270,420),outline='black',width=4);c.line([(230,420),(230,560)]);c.line([(130,470),(330,470)]);c.line([(230,560),(160,670)]);c.line([(230,560),(300,670)]);c.text((20,700),actor,35,True,w=440,align='center')
 for i,label in enumerate(uc):
  y=155+i*220;c.d.ellipse((650,y,1550,y+140),outline='#222',width=4);c.text((680,y+40),label,34,w=840,align='center');c.line([(330,470),(650,y+70)])
 if name=='use_pos':
  # checkout includes deduction; optional discount extends checkout
  c.line([(1550,445),(1650,445),(1650,665),(1550,665)],True,True);c.text((1300,540),'«include»',28,w=330)
  c.d.ellipse((650,785,1280,865),outline='black',width=3);c.text((680,803),'Apply approved discount',29,w=570,align='center');c.line([(650,825),(565,825),(565,445),(650,445)],True,True);c.text((510,680),'«extend»',26,w=220)
 c.save()
# Storyboard schematic pages — clearly labeled, no fabricated live screens
stories=[('inventory','Integrated inventory','Raw materials | Packaging | Supplies | Products',['Choose inventory category','Select product and stock lot','Enter or scan quantity','Review and confirm movement']),('orders','Online orders','VIP | SPX | Validate Excel | PDF + Excel stamper',['Upload source order documents','Review names and item variants','Run the separate stamper tool','Download output and finalize batch']),('costing','Product costing','Products | Parcel recipes',['Select product or parcel tier','Review material price and yield','Maintain formula or custom costs','Review cost and selling price']),('parcel','Parcel documentation','Tracking lookup | Before-shipment photo',['Scan or enter tracking number','Capture or upload parcel photo','Save the documentary record','Continue to the next parcel']),('pos','Point of sale','Product catalog | Cart | Receipt',['Sign in with POS PIN','Scan products and choose stock source','Review total and confirm checkout','Print or view transaction receipt']),('rts','Return-to-Sender','Cases | Sellable inventory | Damaged inventory',['Find parcel and record return case','Inspect and classify returned items','Scan sellable stock into draft session','Review session and confirm stock-in'])]
for name,title,nav,steps in stories:
 c=Chart('story_'+name,1080);c.box(10,10,1780,105,title+'  |  Storyboard wireframe',size=40)
 c.box(10,135,330,895,'Navigation','Dashboard\nInventory\nOrders\nDeliveries\nCosting\nPOS\nRTS',34)
 c.box(370,135,1400,130,nav,size=33)
 for i,s in enumerate(steps):c.box(400,300+i*150,1340,110,f'{i+1}   {s}',size=38)
 c.text((410,945),'Schematic interaction sequence; not a production screenshot.',32,w=1300);c.save()
# Database map, logical area view
c=Chart('database',1290);c.box(480,10,860,110,'Shared relational database',size=42)
for i,(t,b) in enumerate([('Identity and access','users, roles, departments, pos_pins'),('Inventory and production','stocks, stock_entries, honey stage rows, dept_inventory_rows'),('Costing and prices','products, product_formula, parcel_pack_tiers, parcel_pack_tier_lines'),('Online fulfillment','csr_online_batches, csr_online_order_lines, online_order_parcels, delivery records'),('Retail sales','pos_sales, pos_receipt_items, ar_credits'),('Returns','rts_cases, rts_line_items, rts_inventory_rows, rts_stock_in_sessions')]):
 x=20+(i%2)*900;y=240+(i//2)*330;c.box(x,y,860,280,t,b,37);c.line([(900,120),(900,190),(x+430,190),(x+430,y)],True)
c.save()
# ERD subset physical FK relationships, three separate diagrams
rels=json.load(open(W/'relations.json'))
sets=[('erd_inventory',['departments','users','stocks','stock_entries','products','product_formula']),('erd_orders',['csr_online_batches','csr_online_order_lines','online_order_parcels','delivery_batch_items','pos_sales','pos_receipt_items']),('erd_rts',['rts_cases','rts_line_items','rts_inventory_rows','rts_stock_in_sessions','rts_stock_in_session_items','product_sku_barcodes'])]
schema=json.load(open(W/'schema.json'))
for name,tables in sets:
 c=Chart(name,1450)
 # parent/child table pairs in vertical columns; list relationship keys explicitly under entities
 for i,t in enumerate(tables):
  x=20+(i%2)*910;y=30+(i//2)*460
  fs=[z for z in schema if z['TABLE_NAME']==t]
  keys=[z['COLUMN_NAME']+(' [PK]' if z['COLUMN_KEY']=='PRI' else '') for z in fs if z['COLUMN_KEY']=='PRI']
  keys += [r['COLUMN_NAME']+' → '+r['REFERENCED_TABLE_NAME']+'.'+r['REFERENCED_COLUMN_NAME'] for r in rels if r['TABLE_NAME']==t and r['REFERENCED_TABLE_NAME'] in tables]
  c.box(x,y,860,380,t,'\n'.join(keys) or 'id [PK]',32)
  if i%2==0:c.line([(x+860,y+190),(x+910,y+190)],False)
 c.text((20,1400),'Arrows in field labels identify verified foreign keys; parent 1 to child 0..many.',29,w=1760);c.save()
# Network design / topology / stack
c=Chart('network',1050)
c.box(20,30,520,220,'Client devices','Desktop browser\nPhone browser\nScanner / camera input',37)
c.box(640,30,510,220,'Network access','Company LAN / Wi-Fi\nInternet if remotely hosted',37);c.line([(540,140),(640,140)],True)
c.box(1250,30,530,220,'Web application','React interface\nPHP session APIs',37);c.line([(1150,140),(1250,140)],True)
c.box(1020,480,750,200,'Data and files','MySQL database\nControlled document and image storage',37);c.line([(1515,250),(1515,480)],True)
c.box(20,480,800,200,'Document processing','Python PDF and spreadsheet utilities\nInvoked by server workflows',37);c.line([(1250,240),(420,390),(420,480)],True)
c.text((25,820),'Logical deployment design. Exact hosting address, router model, and installed hardware require deployment records.',38,w=1740);c.save()
c=Chart('topology',1180);c.box(670,440,500,180,'Router / access point',size=40)
for x,y,t in [(50,40,'Executive workstation'),(1190,40,'CSR workstation'),(50,900,'Warehouse phone'),(1190,900,'POS workstation'),(670,40,'Application server')]:
 c.box(x,y,530 if x!=670 else 500,150,t,size=37);c.line([(x+250,y+150 if y<440 else y),(920,440 if y<440 else 620)])
c.text((150,700),'Star topology for local access\nA remote server would be reached through the internet uplink.',34,w=1500,align='center');c.save()
c=Chart('stack',1030)
for i,(t,b) in enumerate([('Client presentation','React and TypeScript\nTailwind CSS\nWeb browser'),('Application services','PHP\nSession authentication\nRole and CSRF checks'),('Persistent storage','MySQL / PDO\nInventory and transactions\nDocument file storage'),('Processing utilities','Python\nPDF matching and stamping\nExcel preparation')]):c.box(10+i*450,80,430,810,t,b,38)
c.save()
print('Generated',len(list(A.glob('*.png'))),'diagrams')
