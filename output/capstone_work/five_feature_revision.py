from pathlib import Path
import re
W=Path(__file__).parent
src=(W/'manuscript.md').read_text(encoding='utf8')
# Remove complete POS sections and their accompanying text.
src=re.sub(r'### Point of Sale Systems.*?(?=### Return-to-Sender)', '',src,flags=re.S)
src=re.sub(r'#### Build 5 Point of Sale System.*?(?=#### Build 6)', '',src,flags=re.S)
src=re.sub(r'@FIG story_pos[^\n]*\n.*?(?=@FIG story_rts)', '',src,flags=re.S)
src='\n'.join(l for l in src.splitlines() if not any(t in l for t in ['5. Develop a POS','- Point of Sale System','**Point of Sale**']))
src=src.replace('6. Implement an RTS','5. Implement an RTS').replace('Build 6 Return','Build 5 Return')
src=src.replace('six','five').replace('retail sales, ','').replace(', retail sales','').replace(', sales baskets','').replace(', POS,',',').replace('packing, sales, or return','packing, or return').replace('later RTS sales','later stock reconciliation')
src=src.replace('The Point of Sale module records walk-in transactions, receipts, and stock changes. ','')
src=src.replace('The proposal identifies five problems.','Five problems from the proposal define the scope of this study.')
src=src.replace('(5) Manual retail recording delays transactions and their inventory updates. (6)','(5)')
src=src.replace('recorded estimated costs','estimated costs').replace('recorded retail sales, ','')
src=src.replace('receipt to sale or return','material receipt to fulfillment or return')
src=src.replace('online and walk-in customers','customers through its supported ordering channels')
src=src.replace('fulfillment, and sales','fulfillment, and return handling')
src=src.replace('stock, sales, costs','stock, orders, costs')
src=src.replace("Pyx's sales and packing workflows","Pyx's inventory and packing workflows")
src=src.replace('material requirements, sales, inventory','material requirements, inventory')
src=src.replace('costing, sales, and returns','costing, and returns')
src=src.replace('and retail prices','and selling prices')
src=src.replace('such as a receipt, generated file','such as a generated file')
src=src.replace('POS-to-receipt, ','')
src=src.replace('histories, stamped files, shipment evidence, receipts, and RTS records','histories, stamped files, shipment evidence, and RTS records')
# Two documentation actors; operational job titles are not separate users.
src=src.replace('Role-based access supports the responsibilities of executives, HR personnel, production managers, customer service staff, and cashiers.','The documentation defines two user categories: Admin/Executive and Staff. Admin/Executive controls product costing and selling prices; Staff performs permitted inventory, order, parcel, and return operations.')
src=src.replace('Its users include executives, HR personnel, production managers, authorized customer service and operational staff, and POS cashiers.','Its two user categories are Admin/Executive and Staff. Admin/Executive oversees the five features and exclusively maintains costing and pricing. Staff performs assigned inventory, order-processing, parcel-processing, and return tasks.')
src=src.replace('Authentication, staff and supplier records, permissions, dashboards, settings, and activity records support these five modules. They are shared controls rather than additional independent research objectives.','Authentication and permissions support these five features. Supporting records are documented only where they are needed for an included feature.')
src=src.replace('For executives,','For Admin/Executive,').replace('executive control','Admin/Executive control')
src=re.sub(r'For HR personnel,.*?(?=\n\n)', 'For Staff, the five-feature workflow provides a consistent place to record material receipts, production, packing, order preparation, shipment evidence, and returns. The expected benefit is less duplicate encoding and clearer responsibility for recorded operations.',src)
src=re.sub(r'For cashiers and customers,.*?(?=\n\n)', 'Customers are indirect beneficiaries of consistent order fulfillment and return documentation. They are not separate user accounts in the documented system.',src)
src=src.replace('Executives, HR personnel, production managers, customer service staff, and authorized warehouse and sales personnel participate in these workflows.','Admin/Executive and Staff perform the documented workflows. Operational assignments such as production, warehouse work, and customer-service processing are grouped under Staff for this documentation.')
src=src.replace('CSR personnel','Staff').replace('An executive selects','Admin/Executive selects').replace('Executives maintain','Admin/Executive maintains').replace('Executive permissions','Admin/Executive permissions')
src=src.replace('**Customer service representative** — A CSR staff member who handles supported order or return operations within assigned permissions.','**Staff** — The user category that carries out permitted inventory, order, parcel, and return operations.\n\n**Admin/Executive** — The user category that oversees the system features and maintains product formulas, material prices, parcel recipes, and selling prices.')
src=src.replace('appropriate to their assigned role and department','appropriate to Admin/Executive or Staff access and the assigned operation')
# Keep literature relevant to the five features; remove sales-system sources entirely.
src=re.sub(r'Lorzano et al\. \(2022\) linked.*?(?=\n\n)', 'Laping et al. (2023) used design thinking and user evaluation to develop inventory-interface alternatives for a Marikina convenience store handling perishable goods. The study provides a Philippine comparison for organizing stock information and selecting usable inventory screens. Its business setting differs from the production stages and return workflows of Pyx Food Products.',src)
src=src.replace('The studies have different emphases: ingredient requirements, user-centered interface selection, and intended retail integration. A proposal should not be treated as proof of a mature deployment. These distinctions suggest that inventory software needs a clear business-process model as well as usable screens.','The local study emphasizes user-centered interface selection. For Pyx Tracker, a usable screen must also preserve the unit, product category, and production stage associated with each stock movement. User feedback supports interface decisions, while balance checks establish whether a recorded operation changes the intended stock correctly.')
src=src.replace(' The local Bida Mart proposal identifies barcode integration as a future enhancement, not an established capability (Ercilla et al., 2024).','')
src=re.sub(r' Lorzano et al\. \(2022\) connect.*?equivalent parcel-costing systems\.', ' This study provides related costing evidence, while the Pyx implementation also maintains separate parcel-packaging recipes. The project evaluates its own calculations using known material quantities, unit prices, and yields.',src)
src=src.replace(' Divina et al. (2025) illustrate transaction and inventory integration in a Philippine food enterprise.','')
# Preserve the approved May–August window and grouped stage layout.
src=src.replace('Provisional Development and Implementation Gantt Chart','Development and Implementation Gantt Chart (May–August)')
src=re.sub(r'The 12-week relative schedule.*?(?=\n\n)', 'System development starts in May and ends in August. Each of the five features has Design and Development, Testing, and Implementation activities. The four numbered intervals in each month organize the schedule. Activity bars show a proposed allocation within the stated development period; exact milestone dates should be reconciled with project records.',src)
src=src.replace('The provisional Gantt and logical network diagrams','The detailed Gantt activity allocation and logical network diagrams')
src=re.sub(r'Human actors appear outside.*?(?=#### Use Case Matrix)', 'The use case diagram defines exactly two actors: Admin/Executive and Staff. Admin/Executive oversees the five system features and has exclusive access to product costing and pricing. Staff performs permitted inventory, order, parcel, and return operations. Dashed «include» arrows identify required behavior. Separate packing and photo-documentation goals preserve the distinction between stock movement and documentary evidence.\n\n@FIG use_five|3.5|Overall Use Case Diagram of Pyx Tracker\n\n',src,flags=re.S)
src=src.replace('Order and Sales Entity Relationships','Order and Parcel Entity Relationships')
# Sequential figure numbering after removing the five obsolete diagrams and storyboard.
n=0
def number(m):
 global n
 n+=1
 return '@FIG '+m[1]+'|3.'+str(n)+'|'+m[2]
src=re.sub(r'@FIG ([^|]+)\|3\.\d+\|([^\n]+)',number,src)
(W/'manuscript_five.md').write_text(src,encoding='utf8')
# Adapt the established template builder without modifying previous outputs.
b=(W/'build_docx.py').read_text(encoding='utf8')
b=b.replace("W/'assets'","W/'five_assets'").replace("W/'manuscript.md'","W/'manuscript_five.md'")
b=b.replace("'Pyx_Tracker_Capstone_Chapters_I_III.docx'","'Pyx_Tracker_Capstone_Five_Features.docx'")
b=b.replace("'pos_sales','pos_receipt_items',",'')
b=b.replace('and POS authentication','').replace('role/department checks','Admin/Executive and Staff permission checks')
b=b.replace('Receipt or barcode printer','Waybill or barcode printer')
b=re.sub(r" \('T05','POS checkout.*?\),\n",'',b)
b=b.replace("('T06','RTS", "('T05','RTS")
# Restrict database descriptions to fields required by these five features.
b=b.replace("f=x['COLUMN_NAME'];typ=x['COLUMN_TYPE'];description=", "f=x['COLUMN_NAME']\n   if f.startswith('pos_'):continue\n   typ=x['COLUMN_TYPE'];description=")
insert="""
modules=[m for m in modules if m[0]!='Point of Sale System']
modules=[(m[0],m[1],str(i)) for i,m in enumerate(modules,1)]
ucs=[u for u in ucs if u[0]!='Retail checkout']
ucs=[(u[0], 'Admin/Executive' if u[0]=='Product and parcel costing' else 'Admin/Executive and Staff', *u[2:]) for u in ucs]
refs=[r for r in refs if not r[0].startswith(('Divina,','Ercilla,','Lorzano,'))]
"""
b=b.replace("text=(W/'manuscript_five.md')",insert+"\ntext=(W/'manuscript_five.md')")
(W/'build_five.py').write_text(b,encoding='utf8')
print('Prepared five-feature manuscript and builder')
