from pathlib import Path
exec((Path(__file__).parent/'diagrams.py').read_text(encoding='utf-8-sig').split('# IPO')[0])
# Source-derived screen structure, with empty/sample fields and explicit wireframe label.
configs=[('inventory','Inventory','Category: Products  |  Department: Honey',['Product / lot','Unit','On hand'],['Selected product','bottles','—'],'Scan or enter quantity','Review stock movement','Choose category and product. Review the quantity and source before confirming.'),('orders','Online Orders','Tools: VIP  |  SPX  |  Excel  |  Stamper',['Order / tracking','Items','Review'],['Source order','Product lines','Pending'],'Upload source PDF / Excel','Review prepared output','Preparation tools create inputs. Use the separate stamper, then finalize the batch.'),('costing','Product Costing','Mode: Products  |  Parcel recipes',['Formula line','Quantity','Cost'],['Material / custom cost','—','—'],'Select product or parcel tier','Review cost and price','Executive reviews quantities, material prices, yields and selling prices.'),('pos','Point of Sale','Stock source: Normal stock  |  RTS stock',['Cart item','Quantity','Amount'],['Scanned product','—','—'],'Scan product barcode','Confirm checkout','Authenticate with POS PIN. Check stock source and totals. Complete sale and issue receipt.'),('rts','Return-to-Sender','Cases  |  Sellable stock  |  Damaged stock',['Scanned item','Quantity','Condition'],['Product code','—','Sellable'],'Scan into draft stock-in session','Confirm reviewed stock-in','Record the case separately. Review received sellable units. Cancel leaves stock unchanged.')]
for name,title,sub,cols,vals,field,btn,note in configs:
 c=Chart('story_'+name,1130);c.box(10,10,1780,100,title+'  |  Schematic storyboard',size=42)
 c.box(10,130,260,970,'Menu','Dashboard\nInventory\nOrders\nDeliveries\nCosting\nPOS\nRTS',33)
 c.box(290,130,1090,125,sub,size=33)
 c.box(290,295,1090,115,field,size=35)
 widths=[500,270,320];x=290
 for j in range(3):
  c.box(x,460,widths[j],100,cols[j],size=34);c.box(x,560,widths[j],170,vals[j],size=34);x+=widths[j]
 c.box(680,790,700,125,btn,size=36)
 c.box(1410,130,380,970,'Action and purpose',note,35)
 c.text((310,990),'Wireframe only. Dashes are sample fields, not company data.',32,w=1040);c.save()
c=Chart('story_parcel',1130);c.box(10,10,1780,100,'Deliveries  |  Schematic storyboard',size=42)
c.box(10,130,260,970,'Menu','Dashboard\nInventory\nOrders\nDeliveries\nRTS',33)
c.box(300,130,1090,110,'Before-shipment documentation',size=38);c.box(300,280,1090,110,'Tracking number  [scan or type]',size=38)
c.box(300,430,1090,325,'Photo preview\n[capture or upload image]',size=40);c.box(720,810,670,115,'Save photo',size=40)
c.box(1420,130,370,970,'Action and purpose','Enter tracking. Capture or upload a photo. Save the documentary record. This action does not deduct stock.',35)
c.text((310,995),'Wireframe only. No actual parcel image or tracking data.',32,w=1060);c.save()
# APA-style italics on journal/proceedings names and standalone reports.
p=W/'build_docx.py';s=p.read_text(encoding='utf8')
old="p=para(auth+' '+title+' ',size=11);p.paragraph_format.left_indent=Inches(.5);"
new="""p=para(auth+' ',size=11)
  journals=['Operational Research in Engineering Sciences: Theory and Applications','Proceedings of the 10th Carpathian Logistics Congress','International Journal of Research and Innovation in Applied Science','Ascendens Asia Singapore – Bestlink College of the Philippines Journal of Multidisciplinary Research','European Journal of Operational Research','Sensors','Proceedings of the International Conference on Industrial Engineering and Operations Management','WCSE 2022 Spring Event Proceedings','Jurnal Ilmiah Sistem Informasi','Cleaner Logistics and Supply Chain']
  journal=next((j for j in journals if j in title),None)
  if journal:
   before,after=title.split(journal,1);p.add_run(before);p.add_run(journal).italic=True;p.add_run(after+' ')
  else:p.add_run(title+' ').italic=True
  for rr in p.runs:rr.font.name='Arial';rr.font.size=Pt(11)
  p.paragraph_format.left_indent=Inches(.5);"""
s=s.replace(old,new)
p.write_text(s,encoding='utf8')
