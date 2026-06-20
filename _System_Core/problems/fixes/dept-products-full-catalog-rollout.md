---
date: 2026-06-05
type: fix
---

# Fix: Canonical dept catalog + unified stock-in + deduct

`dept_products.php` defines `pt`, `brc`, `jar_cacao`. Stock-in updates on-hand and costing price. Parcel ship and POS checkout deduct `dept_inventory_rows`. Migration seeds stocks, products, 1:1 formulas.
