import json
from collections import defaultdict
s=defaultdict(list)
for x in json.load(open('output/capstone_work/schema.json')):s[x['TABLE_NAME']].append(x)
for t in ['users','roles','departments','stocks','stock_entries','products','product_formula','csr_online_batches','csr_online_order_lines','online_order_parcels','delivery_batch_items','rts_cases','rts_line_items','rts_inventory_rows','rts_stock_in_sessions','rts_stock_in_session_items','pos_sales','pos_receipt_items','suppliers','honey_warehouse_rows','honey_production_rows','honey_packaging_rows','dept_inventory_rows','parcel_pack_tiers','parcel_pack_tier_lines','product_sku_barcodes','sku_barcode_stock_events']:
 print(t,':','; '.join(x['COLUMN_NAME']+' '+x['COLUMN_TYPE'] for x in s[t]))
print('RELATIONS',json.load(open('output/capstone_work/relations.json')))
