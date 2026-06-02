import processor as p
import fitz
import re

pdf = r"c:\Users\Admin\Downloads\Jun-1-Waybills-290.pdf"
xlsx = r"c:\Users\Admin\Downloads\Today's Order - iPYX (5).xlsx"

target = "P7906KFXHXBAG"

doc = fitz.open(pdf)
found_page = None
for i in range(len(doc)):
    if target in doc[i].get_text().upper():
        found_page = i
        print("text layer page", i + 1)
        break
doc.close()

if found_page is None:
    doc = fitz.open(pdf)
    for i in range(len(doc)):
        tn, method = p.get_tracking_for_page(pdf, i, lambda x: None)
        if tn and p.norm_tn(tn) == p.norm_tn(target):
            found_page = i
            print("found via tracking page", i + 1, tn, method)
            break
    doc.close()

orders = p.parse_excel([xlsx], lambda x: None)
key = p.norm_tn(target)
print("excel key", key, "in orders", key in orders)
if key in orders:
    print("excel row", orders[key])
else:
    # fuzzy search
    for k, v in orders.items():
        if target in v.get("tn", "") or k == key:
            print("similar", k, v)

if found_page is not None:
    tn, method = p.get_tracking_for_page(pdf, found_page, lambda x: None)
    print("page", found_page + 1, "tn", tn, "method", method)
    row = orders.get(p.norm_tn(tn) if tn else "")
    print("match row", row)
    items = (row or {}).get("items", "")
    print("items repr", repr(items))
    print("items empty?", not items or not str(items).strip())
