---
status: approved
created: 2026-08-15
area: inventory
tags: [honey-comb, barcodes, inventory, dept]
---

# Honey Comb Barcode Inventory

## Summary

Track every Honey Comb jar as a unique Code128 unit. Staff enter qty + custom batch code (e.g. `JR073026`), generate stickers, print A4 sheets, then scan each jar into finished stock. Scanning **replaces** the Produce button. Raw-bag, jar packaging, and sticker formula deducts stay **off** for this path (MVP). Aggregate finished qty on `dept_inventory_rows` (`product_key=honey_comb`) is a mirror of scanned units.

UI home: **Raw Honey → Packaging**, in a dedicated **Honey Comb barcodes** section below the liquid-honey packaging batch table (own request list; not mixed with bottle packaging rows).

## Roles (MVP)

| Action | staff / TL / prod_manager / executive |
|--------|----------------------------------------|
| Create request (qty + batch code) | yes |
| Generate / print | yes |
| Scan stock-in | yes |

No variant or size. No CSR-only split for MVP.

## Out of scope

- Raw / packaging / sticker deduct on Honey Comb barcode stock-in
- Pack sessions, POS, RTS unit lifecycle for Honey Comb stickers
- Changing raw bag stock-in on `/inventory/raw-materials/honey-comb`
- Liquid honey bottle variant/size barcodes

## Links

- Plan: [[plans/archive/2026-08-15_honey-comb-barcode-inventory_plan]]
- Prior: [[specs/2026-08-15_paragis-pouch-barcode-inventory]] (Honey Comb was previously out of scope)
