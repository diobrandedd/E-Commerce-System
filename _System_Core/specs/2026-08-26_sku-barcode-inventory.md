---
status: shipped
created: 2026-08-26
owner: ops
area: inventory
tags: [barcodes, sku, inventory, pos, packing, retail]
---

# SKU barcodes (one code per product)

> Theory — finished-goods stickers are permanent SKU barcodes (retail-friendly), not unique per-unit serials. Leftover unique 6-character stickers keep working until consumed.

Links: [[010_Product_Theory]] · [[006_AI_Directed_Engineering]] · [[specs/2026-08-14_honey-bottle-barcode-inventory]] · [[specs/2026-08-25_rts-barcode-inventory-cutover]] · [[plans/active/2026-08-26_sku-barcode-inventory_plan]]

## Problem

Retail stores that buy from us map barcodes to price in SKU/POS systems. Unique per-bottle codes (`K7M2P9`) cannot be saved as their product barcode. Printing a new unique sticker per unit is expensive and inconvenient for them and for us.

## Users & roles

| Role | Job |
|------|-----|
| Executive | Set permanent short codes in Barcode Settings (`settings.manage`) |
| Staff / TL / prod_manager | Request print qty; stock-in / pack / POS / inventory check by scan + qty |
| POS | Scan SKU → qty → price; leftover unique stickers still qty 1 |

## Desired outcome (success criteria)

- [ ] One barcode per SKU, reusable forever (e.g. 250ml Light always the same)
- [ ] Encoded value is a short string from Barcode Settings (typically peso price); does **not** auto-change when selling price changes
- [ ] Codes globally unique across all SKUs
- [ ] Request/print = product + how many identical copies
- [ ] Stock-in, pack, POS, inventory check, cancel restock: scan → show product → enter qty
- [ ] Old unique 6-character stickers still resolve as qty 1 until those units are gone
- [ ] New SKU stock-in credits aggregates only — no new `honey_bottle_units` / `dept_pouch_units` rows

## Behavior (business rules)

1. **Catalog** — honey: variant × size (12); dept: `pt`, `brc`, `jar_cacao`, `honey_comb`.
2. **Lookup order** — trim scan → `product_sku_barcodes.code` → else unique-unit lookup → else unknown.
3. **Print** — N copies of the same Code128; no unique-code generator for new labels.
4. **Stock-in** — SKU + qty onto packaging row / dept finished / RTS sellable; no unit insert. Packaging and finished-goods stock-in keep their existing behavior. **RTS stock-in uses a persistent draft review session:** continuous scans add draft lines only; users may edit SKU quantities or remove lines, cancel without changing inventory, or confirm all lines atomically.
5. **Pack / inventory check** — same continuous +1 / optional typed +N; unique leftovers keep unit lifecycle (+1 per distinct code). Pack confirm still finalizes deductions.
6. **POS** — SKU lines still use qty prompt at checkout (unchanged).
7. **Price hint** — Settings may show selling price as a hint; never overwrite barcode from price.

## Out of scope

- GS1 / official EAN-13 registration
- Auto-changing barcode when `products.selling_price` changes
- Wiping historical unique unit / sales barcode rows
- Retailer POS configuration

## Open decisions (locked by chat)

1. Encoded value = short string from settings (typically price) — **yes** (2026-08-26)
2. All flows scan → enter qty; keep old unique codes until gone — **yes** (2026-08-26); **amended 2026-09-04**: Pack, packaging stock-in, and inventory check also support continuous SKU re-scan (+1) with optional typed +N (not modal-only).
3. RTS stock-in review — **approved 2026-09-08**: scans stay continuous but are staged in an editable server-backed session. Cancel applies nothing; Confirm applies every valid line in one transaction.
4. RTS same-code handling — **approved 2026-09-08**: no time-based duplicate cooldown. Accept once while a barcode remains presented; rearm after it leaves view so presenting the same SKU again immediately adds another +1.

## Approval to plan

- [x] Spec reviewed by human (implement request 2026-08-26)
- [x] Ready for implementation
- [x] Continuous SKU scan UX approved (2026-09-04)
