# Add missing use-case diagrams and replace relation layouts with explicit FK edges.
exec((__import__('pathlib').Path(__file__).parent/'diagrams.py').read_text(encoding='utf-8-sig').split('# IPO')[0])
for name,actor,ucs in [('use_costing','Executive',['Maintain product formula','Review parcel recipe cost','Maintain selling price']),('use_parcel','Executive /\nProduction manager',['Find tracking record','Capture shipment photo','Save photo documentation'])]:
 c=Chart(name,930);c.d.rectangle((490,20,1760,890),outline='black',width=4);c.text((520,40),'Pyx Tracker',38,True,w=1180)
 c.d.ellipse((190,340,270,420),outline='black',width=4);c.line([(230,420),(230,560)]);c.line([(130,470),(330,470)]);c.line([(230,560),(160,670)]);c.line([(230,560),(300,670)]);c.text((20,700),actor,35,True,w=440,align='center')
 for i,label in enumerate(ucs):
  y=155+i*220;c.d.ellipse((650,y,1550,y+140),outline='#222',width=4);c.text((680,y+40),label,34,w=840,align='center');c.line([(330,470),(650,y+70)])
 c.save()
rels=json.load(open(W/'relations.json'))
groups={'erd_inventory':[('departments','users','dept_id'),('departments','stocks','dept_id'),('stocks','stock_entries','stock_id'),('products','product_formula','product_id')], 'erd_orders':[('csr_online_batches','csr_online_order_lines','batch_id'),('csr_online_order_lines','online_order_parcels','csr_line_id'),('online_order_parcels','delivery_batch_items','online_parcel_id'),('pos_sales','pos_receipt_items','sale_id')], 'erd_rts':[('rts_cases','rts_line_items','case_id'),('rts_line_items','rts_inventory_rows','source_line_item_id'),('rts_stock_in_sessions','rts_stock_in_session_items','session_id'),('product_sku_barcodes','rts_stock_in_session_items','sku_id')]}
for name,rs in groups.items():
 c=Chart(name,1320)
 for i,(p,ch,f) in enumerate(rs):
  assert any(r['TABLE_NAME']==ch and r['COLUMN_NAME']==f and r['REFERENCED_TABLE_NAME']==p for r in rels)
  y=30+i*315;c.box(10,y,660,220,p,'id [primary key]',35);c.box(1050,y,740,220,ch,f+' [foreign key]',35)
  c.line([(670,y+115),(1050,y+115)],True);c.text((700,y+55),'1',36,w=60);c.text((850,y+55),'0..many',36,w=190)
  c.text((695,y+150),'references',30,w=335,align='center')
 c.save()
