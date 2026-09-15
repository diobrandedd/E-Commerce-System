from pathlib import Path
p=Path('output/capstone_work/build_docx.py');s=p.read_text(encoding='utf-8-sig')
s=s.replace("r.bold=part.startswith('**')","r.bold=True if part.startswith('**') else None")
s=s.replace("if d.paragraphs and d.paragraphs[-1].style.name.startswith('Heading'):\n  d.paragraphs[-1].paragraph_format.page_break_before=True\n else:page()","# Natural pagination keeps each image with its caption.")
s=s.replace("elif ln:para(ln,center=True)","elif ln:para(ln,center=(ln=='Web-based Enterprise Operations Management System'))")
s=s.replace("heading('TABLE OF CONTENTS',1,True)","p=para('TABLE OF CONTENTS',center=True);p.runs[0].bold=True;p.paragraph_format.page_break_before=True")
s=s.replace("p.paragraph_format.line_spacing=1.5;link(p,url)","p.paragraph_format.line_spacing=1.15;p.alignment=WD_ALIGN_PARAGRAPH.LEFT;link(p,url)")
s=s.replace("('Objective'],modules", "('Objective'],modules")
p.write_text(s,encoding='utf8')
# Improve FDD: every subfunction is a separate child, with sufficient padding.
exec((Path('output/capstone_work/diagrams.py')).read_text(encoding='utf-8-sig').split('# IPO')[0])
c=Chart('fdd',1760);c.box(500,10,850,130,'0  Pyx Tracker',size=46);c.line([(925,140),(925,190),(35,190),(35,1640)])
fd=[('1  Integrated Inventory Management System',['Receive stock','Track production','Confirm finished stock','Review movements']),('2  Product Costing and Pricing Calculator',['Maintain formulas','Calculate product cost','Define parcel recipes','Maintain selling prices']),('3  Multi-Platform Order Processing and Waybill Management',['Prepare VIP / SPX files','Validate order data','Stamp PDF and Excel','Finalize order batches']),('4  Parcel Processing and Inventory Synchronization',['Record packing scans','Confirm stock movements','Save shipment photos','Review parcel history']),('5  Point of Sale System',['Authenticate cashier','Select stock and items','Post checkout','Issue receipt and history']),('6  Return-to-Sender Management',['Record return case','Classify return condition','Review barcode stock-in','Confirm sellable balance'])]
for i,(title,subs) in enumerate(fd):
 y=225+i*250;c.line([(35,y+100),(115,y+100)]);c.box(115,y+5,630,200,title,size=38);c.line([(745,y+105),(795,y+105),(795,y+24),(795,y+204)])
 for j,sub in enumerate(subs):
  yy=y+j*58;c.line([(795,yy+24),(855,yy+24)]);c.box(855,yy,920,50,f'{i+1}.{j+1}  {sub}',size=33)
c.save()
# Connect database areas through a common bus outside boxes.
c=Chart('database',1260);c.box(450,10,900,110,'Shared relational database',size=42);c.line([(900,120),(900,1120)])
for i,(t,b) in enumerate([('Identity and access','users, roles, departments, pos_pins'),('Inventory and production','stocks, stock_entries, honey stage rows, dept_inventory_rows'),('Costing and prices','products, product_formula, parcel_pack_tiers, parcel_pack_tier_lines'),('Online fulfillment','csr_online_batches, csr_online_order_lines, online_order_parcels, delivery records'),('Retail sales','pos_sales, pos_receipt_items, ar_credits'),('Returns','rts_cases, rts_line_items, rts_inventory_rows, rts_stock_in_sessions')]):
 x=10+(i%2)*950;y=190+(i//2)*335;c.box(x,y,820,275,t,b,37);c.line([(900,y+130),(830 if i%2==0 else 960,y+130)])
c.save()
# Replace ambiguous directed relationship labels.
exec(Path('output/capstone_work/diagram_refine.py').read_text(encoding='utf-8-sig').replace("c.line([(670,y+115),(1050,y+115)],True)","c.line([(670,y+115),(1050,y+115)])").replace("'references',30","'has',30"))
