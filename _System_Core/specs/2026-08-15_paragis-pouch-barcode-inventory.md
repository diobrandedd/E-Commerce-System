---
status: approved
created: 2026-08-15
area: inventory
tags: [paragis, black-rice, barcodes, inventory, dept]
---

# Dept Pouch Barcode Inventory (Paragis Tea, Black Rice Coffee)

> **2026-08-15 extension:** shipped Paragis-only first, then generalized to any dept product in
> `DEPT_POUCH_BARCODE_KEYS` (currently `pt`, `brc`, `jar_cacao`). Tables are `dept_pouch_*` with a `product_key`
> column (migration phase58). For Black Rice Coffee the flow replaces **Produce** exactly as it
> replaced **Pouch** for Paragis. See
> [[problems/records/2026-08-15_dept-pouch-barcodes-black-rice]].

## Summary

Track every Paragis Tea pouch as a unique Code128 unit. Staff enter qty + custom batch code (e.g. `JR073026`), generate stickers, print A4 sheets, then scan each pouch into finished stock. Scanning **replaces** the Pouch button. WIP tea-bag and packaging formula deducts stay **off** for this path (MVP). Aggregate finished qty on `dept_inventory_rows` (`product_key=pt`) is a mirror of scanned units.

## Roles (MVP)

| Action | staff / TL / prod_manager / executive |
|--------|----------------------------------------|
| Create request (qty + batch code) | yes |
| Generate / print | yes |
| Scan stock-in | yes |

No CSR-only split for MVP.

## Out of scope

- WIP / packaging deduct on pouch barcode stock-in
- Pack sessions, POS, RTS for dept pouches/jars
- Honey Comb barcodes — **superseded** by [[specs/2026-08-15_honey-comb-barcode-inventory]] (Produce retired; Packaging-page section)

- Changing raw bag stock-in

## Links

- Plan: [[plans/archive/2026-08-15_paragis-pouch-barcode-inventory_plan]]
