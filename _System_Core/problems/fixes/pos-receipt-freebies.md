---
date: 2026-11-17
type: fix
---

# Fix: `pos_receipt_freebies` + freebie cart split

- Lookup FRB codes; split freebie items before paid cart build; record give-out + `pos_receipt_freebies` on checkout/AR.
- Sales attaches freebie lines to checkouts; `honey_sales_freebie_breakdown` for walk-in + shipped.
- Full refund restocks freebie on_hand via `dept_raw_record_freebie_restock`.
