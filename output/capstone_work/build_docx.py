from pathlib import Path
from copy import deepcopy
from docx import Document
from docx.shared import Inches,Pt,RGBColor
from docx.enum.text import WD_ALIGN_PARAGRAPH
from docx.oxml import OxmlElement
from docx.oxml.ns import qn
from docx.enum.table import WD_TABLE_ALIGNMENT,WD_CELL_VERTICAL_ALIGNMENT
import re,json,hashlib
W=Path(__file__).parent; OUT=W.parent/'documents';OUT.mkdir(exist_ok=True)
reference=Path('C:/Users/Admin/Downloads/OUTLINE-AND-SAMPLE-DOCU-CHPATER-123 (2).docx')
d=Document(reference)
sect=deepcopy(d.sections[0]._sectPr)
for e in list(d.element.body):d.element.body.remove(e)
d.element.body.append(sect)
s=d.sections[0];s.page_width=Inches(8.5);s.page_height=Inches(11);s.top_margin=Inches(1.5);s.bottom_margin=Inches(1);s.left_margin=Inches(1.5);s.right_margin=Inches(1)
from docx.enum.style import WD_STYLE_TYPE
for tocname in ['TOC 1','TOC 2','TOC 3']:
 if tocname not in d.styles:d.styles.add_style(tocname,WD_STYLE_TYPE.PARAGRAPH)
for nm in ['Normal','Title','Heading 1','Heading 2','Heading 3','Caption','List Paragraph','TOC 1','TOC 2']:
 try:st=d.styles[nm]
 except KeyError:continue
 st.font.name='Arial';st.font.size=Pt(12);st.font.color.rgb=RGBColor(0,0,0)
 st.paragraph_format.space_before=Pt(0);st.paragraph_format.space_after=Pt(6)
 st.paragraph_format.line_spacing=2
 if nm.startswith('Heading'):st.font.bold=True;st.paragraph_format.keep_with_next=True;st.paragraph_format.first_line_indent=Pt(0)
normal=d.styles['Normal'];normal.paragraph_format.first_line_indent=Inches(.5)
for nm in ['TOC 1','TOC 2','TOC 3']:
 try:d.styles[nm].font.name='Arial';d.styles[nm].font.size=Pt(11);d.styles[nm].paragraph_format.line_spacing=1.0;d.styles[nm].paragraph_format.space_after=Pt(3)
 except KeyError:pass
for h in [s.header,s.footer]:
 for p in h.paragraphs:p.clear()
p=s.footer.paragraphs[0];p.alignment=WD_ALIGN_PARAGRAPH.RIGHT;p.paragraph_format.first_line_indent=Pt(0)
r=p.add_run();f=OxmlElement('w:fldSimple');f.set(qn('w:instr'),'PAGE');r._r.addnext(f)
s.different_first_page_header_footer=True
for p in s.first_page_footer.paragraphs:p.clear()
# Remove old header shapes/sample chrome, retain institutional seal in title.
def inline(p,text):
 for k,part in enumerate(re.split(r'(\*\*.*?\*\*)',text)):
  r=p.add_run(part[2:-2] if part.startswith('**') else part);r.bold=True if part.startswith('**') else None
  r.font.name='Arial';r.font.color.rgb=RGBColor(0,0,0)
 return p
def para(text='',style=None,center=False,size=12):
 p=d.add_paragraph(style=style);inline(p,text)
 for r in p.runs:r.font.size=Pt(size)
 if center:p.alignment=WD_ALIGN_PARAGRAPH.CENTER;p.paragraph_format.first_line_indent=Pt(0)
 elif not style:p.alignment=WD_ALIGN_PARAGRAPH.JUSTIFY
 p.paragraph_format.widow_control=True
 return p
def heading(text,level=2,new=False):
 p=para(text,'Heading '+str(level));p.paragraph_format.page_break_before=new
 if level==1:p.alignment=WD_ALIGN_PARAGRAPH.CENTER
 return p
def page():
 p=d.add_paragraph();p.paragraph_format.space_after=Pt(0);p.paragraph_format.line_spacing=1;p.add_run().add_break(__import__('docx').enum.text.WD_BREAK.PAGE)
# Plain text URLs become hyperlinks in the reference list.
def link(p,url):
 rel=p.part.relate_to(url,'http://schemas.openxmlformats.org/officeDocument/2006/relationships/hyperlink',is_external=True)
 h=OxmlElement('w:hyperlink');h.set(qn('r:id'),rel);r=OxmlElement('w:r');pr=OxmlElement('w:rPr');f=OxmlElement('w:rFonts');f.set(qn('w:ascii'),'Arial');f.set(qn('w:hAnsi'),'Arial');pr.append(f);sz=OxmlElement('w:sz');sz.set(qn('w:val'),'22');pr.append(sz);r.append(pr);t=OxmlElement('w:t');t.text=url;r.append(t);h.append(r);p._p.append(h)
T=0
captions=[]
def table(title,headers,rows,widths=None,small=10):
 global T
 T+=1;p=para(f'Table 3.{T}  {title}',center=True,size=11);p.paragraph_format.line_spacing=1.15;p.paragraph_format.keep_with_next=True;captions.append(p.text)
 tb=d.add_table(rows=1,cols=len(headers));tb.alignment=WD_TABLE_ALIGNMENT.CENTER;tb.autofit=False
 widths=widths or [6/len(headers)]*len(headers)
 for i,w in enumerate(widths):tb.columns[i].width=Inches(w)
 for vals,row in [(headers,tb.rows[0])]+[(v,tb.add_row()) for v in rows]:
  for i,(val,cell) in enumerate(zip(vals,row.cells)):
   cell.width=Inches(widths[i]);cell.vertical_alignment=WD_CELL_VERTICAL_ALIGNMENT.CENTER
   p=cell.paragraphs[0];p.paragraph_format.first_line_indent=Pt(0);p.paragraph_format.line_spacing=1.0;p.paragraph_format.space_after=Pt(3);p.paragraph_format.space_before=Pt(3)
   txt=str(val);txt=txt.replace('_','_\u200b') if small<=9 else txt
   rr=p.add_run(txt);rr.font.name='Arial';rr.font.size=Pt(small);rr.font.color.rgb=RGBColor(0,0,0)
   if row is tb.rows[0]:rr.bold=True
   tcPr=cell._tc.get_or_add_tcPr();m=OxmlElement('w:tcMar')
   for tag in ['top','left','bottom','right']:
    e=OxmlElement('w:'+tag);e.set(qn('w:w'),'65');e.set(qn('w:type'),'dxa');m.append(e)
   tcPr.append(m)
  trPr=row._tr.get_or_add_trPr();trPr.append(OxmlElement('w:cantSplit'))
 for cell in tb.rows[0].cells:
  sh=OxmlElement('w:shd');sh.set(qn('w:fill'),'E7EBEE');cell._tc.get_or_add_tcPr().append(sh)
  for r in cell.paragraphs[0].runs:r.bold=True
 tb.rows[0]._tr.get_or_add_trPr().append(OxmlElement('w:tblHeader'))
 pr=tb._tbl.tblPr;b=OxmlElement('w:tblBorders')
 for side in ['top','left','bottom','right','insideH','insideV']:
  e=OxmlElement('w:'+side);e.set(qn('w:val'),'single');e.set(qn('w:sz'),'5');e.set(qn('w:color'),'999999');b.append(e)
 pr.append(b)
 after=d.add_paragraph();after.paragraph_format.space_after=Pt(3);after.paragraph_format.line_spacing=1;after.paragraph_format.first_line_indent=Pt(0)
 return tb

def figure(spec):
 from PIL import Image
 name,num,title=spec.split('|');path=W/'assets'/(name+'.png')
 # Put section heading on the figure page when directly adjacent.
 # Natural pagination keeps each image with its caption.
 im=Image.open(path);width=6;height=width*im.height/im.width
 if height>5.9:width=5.9*im.width/im.height
 p=d.add_paragraph();p.alignment=WD_ALIGN_PARAGRAPH.CENTER;p.paragraph_format.first_line_indent=Pt(0);p.paragraph_format.line_spacing=1;p.paragraph_format.keep_with_next=True
 p.add_run().add_picture(str(path),width=Inches(width))
 pp=para('Figure '+num+'  '+title,center=True,size=11);pp.paragraph_format.line_spacing=1.15;pp.paragraph_format.space_after=Pt(8)
 captions.append(pp.text)

modules=[('Integrated Inventory Management System','Stock categories, production stages, finished-goods stock-in, movements and review.','1'),('Product Costing and Pricing Calculator','Product formulas, material prices and yields, parcel recipes and selling prices.','2'),('Multi-Platform Order Processing and Waybill Management','VIP/SPX preparation, validation, PDF/Excel stamping and batch finalization.','3'),('Parcel Processing and Inventory Synchronization','Packing records, applicable stock movements, parcel history and separate photo documentation.','4'),('Point of Sale System','PIN access, selected stock source, checkout, receipts and sales history.','5'),('Return-to-Sender Management','Return cases, condition records, reviewed barcode stock-in and sellable balances.','6')]
ucs=[
('Inventory stock movement','Executive or authorized inventory operator','Material receipt or finished-stock operation','Valid product, stock unit, permission and source record.','1. Select category and product.\n2. Enter or scan the movement.\n3. Review quantity and source.\n4. Confirm the allowed operation.','The relevant balance and movement record reflect the confirmed quantity.','Reject invalid input; preserve balances when an unconfirmed action is cancelled.'),
('Product and parcel costing','Executive','Review or update of product costs','Configured material prices, units and formulas.','1. Select product or tier.\n2. Review quantities, prices and yield.\n3. Maintain formula lines.\n4. Review calculated cost and price.','The cost is reproducible from the stored inputs.','Deny non-executive access; identify missing configuration before relying on the result.'),
('Order preparation and finalization','Authorized CSR staff or team leader','Receipt of supported order files','Supported source files and an active processing batch.','1. Choose preparation tool.\n2. Upload and review data.\n3. Run separate PDF/Excel stamper.\n4. Download results and finalize batch.','Prepared outputs and finalized order/parcel records are available.','Report duplicates, unmatched records or missing fields; do not describe partial preparation as finalization.'),
('Before-shipment documentation','Executive or production manager','Parcel presented for photo recording','Readable or manually entered tracking identifier and an image.','1. Scan or type tracking.\n2. Capture or upload photo.\n3. Review and save.\n4. Continue to next record.','Photo is retrievable by tracking number; stock is unchanged.','Provide duplicate feedback and request valid image/tracking input. A CSR parcel is not required.'),
('Retail checkout','POS cashier','Walk-in customer purchase','Valid POS access, product prices and available selected stock.','1. Select or scan items.\n2. Select normal or RTS stock.\n3. Review total.\n4. Confirm checkout and issue receipt.','Sale and receipt exist; the selected stock is deducted by the completed transaction.','Handle insufficient stock and invalid input; restrict discounts to the authorized workflow.'),
('RTS case and sellable stock-in','Authorized RTS operator','Returned parcel received','Tracking or case information, item condition and readable product codes.','1. Record case and condition.\n2. Open stock-in session.\n3. Scan received sellable goods.\n4. Review and confirm session.','Case history is retained; confirmed sellable quantity is credited once.','Case save alone adds no sellable stock; discarding a draft leaves inventory unchanged.')]
def usecases():
 for i,(name,actor,trig,pre,flow,post,ex) in enumerate(ucs,1):
  rows=[('Use case',f'UC{i:02d}  {name}'),('Author','Cayunda, Klint Russel R.; Delos Santos, Brent R.; Fin, Noli III M.'),('Primary actor',actor),('Trigger',trig),('Preconditions',pre),('Basic flow',flow),('Postcondition',post),('Exceptions',ex)]
  table('Use Case Matrix for '+name,['Element','Specification'],rows,[1.15,4.85],10)

def dictionary():
 data=json.load(open(W/'schema.json'));rels=json.load(open(W/'relations.json'))
 tables=['users','roles','departments','suppliers','stocks','stock_entries','honey_warehouse_rows','honey_production_rows','honey_packaging_rows','dept_inventory_rows','products','product_formula','parcel_pack_tiers','parcel_pack_tier_lines','csr_online_batches','csr_online_order_lines','online_order_parcels','delivery_batches','delivery_batch_items','pos_sales','pos_receipt_items','rts_cases','rts_line_items','rts_inventory_rows','rts_stock_in_sessions','rts_stock_in_session_items','product_sku_barcodes','sku_barcode_stock_events']
 desc={'id':'Row identifier.','qty':'Recorded quantity in the applicable unit.','on_hand':'Operational material balance.','current_price':'Current unit price used in costing.','qty_per_unit':'Formula quantity required per sellable unit.','line_label':'Description for a material or custom cost line.','tracking_number':'Tracking identifier used for lookup and matching.','items_parsed_json':'Parsed parcel item representation.','items_raw':'Original item description.','password_hash':'Password verification hash.','source_line_item_id':'Originating return line, when applicable.','current_liter':'Current count of 1 L bottles.','initial_liter':'Initial count of 1 L bottles.','current_250ml':'Current count of 250 mL bottles.','initial_250ml':'Initial count of 250 mL bottles.','current_350ml':'Current count of 350 mL bottles.','initial_350ml':'Initial count of 350 mL bottles.','current_gallon':'Current count of gallon units.','initial_gallon':'Initial count of gallon units.','inventory_pool':'Separates sellable and damaged inventory.','status':'Current workflow state.','is_active':'Whether the record is enabled.','pool_yield_qty':'Usable output per costing pool unit.','production_deduct':'Whether configured production deduction applies.','match_rule':'Configured parcel composition matching rule.','deducted_at':'Timestamp recording parcel deduction.','code':'Stored barcode identifier.','qty_typical':'Typical recipe quantity.','lookup_kind':'Whether the scanned code resolves as SKU or unique unit.','line_role':'Classification as reusable or damaged.','photo_filename':'Stored photo file reference.','online_parcel_id':'Optional related parcel identifier.','confirmed_at':'Time the reviewed session was confirmed.','discarded_at':'Time the draft session was discarded.'}
 for t in tables:
  rows=[]
  for x in [v for v in data if v['TABLE_NAME']==t]:
   f=x['COLUMN_NAME'];typ=x['COLUMN_TYPE'];description=desc.get(f,f.replace('_',' ').capitalize()+'.')
   if f in ['current_qty','initial_qty']:
    description=('Current' if f=='current_qty' else 'Initial')+(' quantity in gallons.' if t in ['honey_warehouse_rows','honey_production_rows'] else ' quantity for the configured stock item.')
   fk=next((v for v in rels if v['TABLE_NAME']==t and v['COLUMN_NAME']==f),None)
   if fk:description+=' FK to '+fk['REFERENCED_TABLE_NAME']+'.'+fk['REFERENCED_COLUMN_NAME']+'.'
   if x['COLUMN_KEY']:description+=' Index: '+x['COLUMN_KEY']+'.'
   if x['EXTRA']:description+=' '+x['EXTRA']+'.'
   if typ.startswith('enum('):
    description+=' Values: '+typ[5:-1].replace("'",'').replace(',',', ')+'.';typ='enum'
   rows.append([f,typ,'Yes' if x['IS_NULLABLE']=='YES' else 'No',str(x['COLUMN_DEFAULT']) if x['COLUMN_DEFAULT'] is not None else '—',description])
  table('Data Dictionary for '+t,['Attribute','Type','Null','Default','Description'],rows,[1.18,1.05,.4,.6,2.77],9)

refs=[
('Beck, K., Beedle, M., van Bennekum, A., Cockburn, A., Cunningham, W., Fowler, M., Grenning, J., Highsmith, J., Hunt, A., Jeffries, R., Kern, J., Marick, B., Martin, R. C., Mellor, S., Schwaber, K., Sutherland, J., & Thomas, D. (2001).','Principles behind the Agile Manifesto.','https://agilemanifesto.org/principles.html'),
('Bhatti, M. A., & Bauirzhanovna, B. A. (2023).','Impact of intelligent inventory system on improvement of reverse logistics: A case of Saudi manufacturing industry. Operational Research in Engineering Sciences: Theory and Applications, 6(1).','https://oresta.org/menu-script/index.php/oresta/article/view/512'),
('Chocholáč, J., Hruška, R., & Sommerauerová, D. (2022).','Effectiveness of automatic identification technologies in shipment consolidation. Proceedings of the 10th Carpathian Logistics Congress (pp. 53–58).','https://doi.org/10.37904/clc.2022.4565'),
('Divina, J., Olan, A., Perez, N. C., Sarmiento, M., & Acepcion, R. (2025).','Data-driven point-of-sale and inventory system for Pastil sa Tabi: Integrating sales forecasting algorithms and predictive analytics. International Journal of Research and Innovation in Applied Science, 10(10), 826–838.','https://doi.org/10.51584/IJRIAS.2025.1010000066'),
('Ercilla, L., Garcia, J., Gonzales, A., & Relano, K. (2024).','A research proposal of inventory and POS system for Bida Mart in the City of San Jose Del Monte, Bulacan. Ascendens Asia Singapore – Bestlink College of the Philippines Journal of Multidisciplinary Research, 4(1).','https://ojs.aaresearchindex.com/index.php/aasgbcpjmra/article/view/13558'),
('Goedhart, J., Haijema, R., Akkerman, R., & de Leeuw, S. (2023).','Replenishment and fulfilment decisions for stores in an omni-channel retail network. European Journal of Operational Research, 311(3), 1009–1022.','https://doi.org/10.1016/j.ejor.2023.06.018'),
('GS1 US. (2026).','Advancing intelligent data capture in modern logistics operations.','https://documents.gs1us.org/adobe/assets/deliver/urn:aaid:aem:389a6053-bb10-454c-ac16-1c19ea2950f9/GS1-US-Advancing-Intelligent-Data-Capture-in-Modern-Logistics-Operations.pdf'),
('Kamnardsiri, T., Charoenkwan, P., Malang, C., & Wudhikarn, R. (2022).','1D barcode detection: Novel benchmark datasets and comprehensive comparison of deep convolutional neural network approaches. Sensors, 22(22), 8788.','https://doi.org/10.3390/s22228788'),
('Laping, A. K. T., Mendoza, C. A. R., Tiulentino, K. N. L., Villaraza, R. M., Bucu, G. C., & Ngo, J. K. (2023).','Designing an inventory management system for Convenience Store X using design thinking approach. Proceedings of the International Conference on Industrial Engineering and Operations Management (pp. 1703–1715).','https://ieomsociety.org/proceedings/2023manila/480.pdf'),
('Lorzano, E. R. B., Roda, I. G. S., Paulo, S. B. T., & Gabriel, A. D. (2022).','Food retail company web-based POS with inventory and stock management. WCSE 2022 Spring Event Proceedings (pp. 371–385).','https://doi.org/10.18178/wcse.2022.04.045'),
('Munifah, Henny, & Aqham, A. A. (2026).','Digitalization of production cost calculations using the variable costing method in food industry SMEs. Jurnal Ilmiah Sistem Informasi, 5(1).','https://doi.org/10.51903/ydwg6m74'),
('Nanayakkara, P. R., Jayalath, M. M., Thibbotuwawa, A., & Perera, H. N. (2022).','A circular reverse logistics framework for handling e-commerce returns. Cleaner Logistics and Supply Chain, 5, 100080.','https://doi.org/10.1016/j.clscn.2022.100080'),
('United Nations Department of Economic and Social Affairs. (n.d.-a).','Goal 8.','https://sdgs.un.org/goals/goal8'),
('United Nations Department of Economic and Social Affairs. (n.d.-b).','Goal 9.','https://sdgs.un.org/goals/goal9'),
('United Nations Department of Economic and Social Affairs. (n.d.-c).','Goal 12.','https://sdgs.un.org/goals/goal12')]

def references():
 for auth,title,url in refs:
  p=para(auth+' ',size=11)
  journals=['Operational Research in Engineering Sciences: Theory and Applications','Proceedings of the 10th Carpathian Logistics Congress','International Journal of Research and Innovation in Applied Science','Ascendens Asia Singapore – Bestlink College of the Philippines Journal of Multidisciplinary Research','European Journal of Operational Research','Sensors','Proceedings of the International Conference on Industrial Engineering and Operations Management','WCSE 2022 Spring Event Proceedings','Jurnal Ilmiah Sistem Informasi','Cleaner Logistics and Supply Chain']
  journal=next((j for j in journals if j in title),None)
  if journal:
   before,after=title.split(journal,1);p.add_run(before);p.add_run(journal).italic=True;p.add_run(after+' ')
  else:p.add_run(title+' ').italic=True
  for rr in p.runs:rr.font.name='Arial';rr.font.size=Pt(11)
  p.paragraph_format.left_indent=Inches(.5);p.paragraph_format.first_line_indent=Inches(-.5);p.paragraph_format.line_spacing=1.15;p.alignment=WD_ALIGN_PARAGRAPH.LEFT;link(p,url)

def special(tag):
 if tag=='@COVER':
  p=para(center=True);p.paragraph_format.line_spacing=1;p.add_run().add_picture(str(W/'format/image1.jpeg'),width=Inches(1.3))
  for t in ['An Information Technology Capstone Project','Presented to the Faculty of Information and Communication Technology','South East Asian Institute of Technology, Inc.','National Highway, Brgy. Crossing Ruber, Tupi, South Cotabato','In Partial Fulfillment of the Requirements for the Degree of','BACHELOR OF SCIENCE IN INFORMATION TECHNOLOGY','CAYUNDA, KLINT RUSSEL R.','DELOS SANTOS, BRENT R.','FIN, NOLI III M.','Adviser: [Name to be confirmed]','September 2026']:
   p=para(t,center=True);p.paragraph_format.line_spacing=1.15;p.paragraph_format.space_after=Pt(10)
  p=para('TABLE OF CONTENTS',center=True);p.runs[0].bold=True;p.paragraph_format.page_break_before=True
  p=d.add_paragraph();p.paragraph_format.first_line_indent=Pt(0)
  fld=OxmlElement('w:fldSimple');fld.set(qn('w:instr'),'TOC \\o "1-3" \\h \\z \\u');p._p.append(fld)
 elif tag=='@USECASES':usecases()
 elif tag=='@DICTIONARY':dictionary()
 elif tag=='@REFERENCES':references()
 elif tag=='@MODULES':table('List of Modules',['Module','Functions','Obj.'],modules,[2.15,3.3,.55])
 elif tag=='@SOFTWARE':table('Software Specification',['Component','Specification','Purpose'],[
 ('Client interface','React, TypeScript, Tailwind CSS','Browser screens and interactions.'),('Frontend build','Vite and project dependencies','Build the interface for deployment.'),('Server','PHP with session-based API actions','Validate access and execute business operations.'),('Database','MySQL accessed through PDO','Persist structured operational records.'),('Document utilities','Python and configured PDF/Excel libraries','Prepare and transform supported documents.'),('Development environment','Windows/XAMPP workspace; project editor','Local application development and maintenance.'),('Client browser','Compatible desktop or mobile browser','Access pages and supported scanning/camera workflows.')],[1.35,2.3,2.35])
 elif tag=='@HARDWARE':table('Hardware Requirements to Confirm',['Equipment','Functional requirement'],[
 ('Application server','Runs PHP, the database connection, and required Python utilities; storage sized for retained files and backups.'),('User workstation','Browser-capable computer with dependable network access.'),('Mobile device','Compatible browser and camera where camera scanning or photos are used.'),('Barcode scanner','Supported keyboard-wedge or equivalent input for the implemented scanning fields.'),('Printer','Receipt or barcode printer where printed output is used.'),('Network equipment','Router/access point and internet uplink where required by the hosting arrangement.'),('Backup storage','Separate capacity for the database and retained document/image files.')],[1.5,4.5])
 elif tag=='@PROGRAM':table('Program Specification',['Layer','Program responsibility'],[
 ('Presentation','React and TypeScript pages display role-appropriate workflows.'),('Authentication','PHP sessions, role/department checks, and POS authentication govern access.'),('Business processing','PHP handlers validate input, run the relevant stock or transaction action, and return a structured response.'),('Database access','PDO queries and transaction handling maintain persistent records.'),('File processing','Python utilities process supported PDF and Excel inputs; application storage controls their outputs.'),('Integrity controls','Validation, confirmation, duplicate checks, and history records protect the documented workflows.')],[1.3,4.7])
 elif tag=='@TESTS':table('Functional Verification Plan',['ID','Scenario','Expected evidence'],[
 ('T01','Inventory receipt and finished-stock confirmation','Correct units, movement record, and starting/ending balance.'),('T02','Product formula and parcel recipe calculation','Reproducible line values and totals; access denied to an unauthorized role.'),('T03','Prepare, stamp, and finalize an order batch','Correct generated files and records, with duplicate/unmatched input feedback.'),('T04','Packing and separate photo save','Expected packing movement; photo save alone leaves stock unchanged.'),('T05','POS checkout using normal and RTS stock','Correct receipt, sales entry, and deduction from the selected source.'),('T06','RTS case, draft cancellation, and confirmation','Case alone adds no sellable stock; cancelled draft has no effect; confirmation applies reviewed quantity once.')],[.5,2.2,3.3])

text=(W/'manuscript.md').read_text(encoding='utf8')
for block in re.split(r'\n\s*\n',text.strip()):
 # Process heading/directive lines individually even where adjacent.
 if block.startswith('#') or block.startswith('@'):
  lines=block.splitlines()
  for ln in lines:
   if ln.startswith('@FIG '):figure(ln[5:])
   elif ln.startswith('@'):special(ln)
   elif ln.startswith('#### '):heading(ln[5:],3)
   elif ln.startswith('### '):heading(ln[4:],2)
   elif ln.startswith('## '):
    p=para(ln[3:],center=True);p.runs[0].bold=True;p.paragraph_format.keep_with_next=True
   elif ln.startswith('# '):
    title=ln[2:]
    if title=='PYX TRACKER':p=para(title,'Title',True);p.runs[0].bold=True;p.paragraph_format.line_spacing=1.3
    else:heading(title,1,True)
   elif ln:para(ln,center=(ln=='Web-based Enterprise Operations Management System'))
 elif block.startswith('- ') or re.match(r'^\d+\. ',block):
  for ln in block.splitlines():
   p=para(ln);p.paragraph_format.first_line_indent=Inches(-.22);p.paragraph_format.left_indent=Inches(.22);p.alignment=WD_ALIGN_PARAGRAPH.LEFT
 else:para(block.replace('\n',' '))
settings=d.settings.element;u=OxmlElement('w:updateFields');u.set(qn('w:val'),'true');settings.append(u)
d.core_properties.title='Pyx Tracker: Web-based Enterprise Operations Management System';d.core_properties.author='Klint Russel R. Cayunda; Brent R. Delos Santos; Noli III M. Fin';d.core_properties.subject='Capstone documentation Chapters I to III'
out=OUT/'Pyx_Tracker_Capstone_Chapters_I_III.docx';d.save(out)
(W/'captions.json').write_text(json.dumps(captions,indent=2));print(str(out.resolve()));print('tables',T,'figures',len([x for x in captions if x.startswith('Figure')]))
