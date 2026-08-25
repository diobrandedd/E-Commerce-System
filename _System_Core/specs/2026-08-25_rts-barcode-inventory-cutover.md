---
status: draft
created: 2026-08-25
owner: ops
area: rts
tags: [rts, barcodes, inventory, pos, packing, cutover]
---

# RTS barcode inventory cutover

> Theory — Record RTS stays for cases; sellable RTS stock enters and leaves only by barcode scan. Damaged/leak stays qty-on-case (no scan into sellable inventory).

Links: [[010_Product_Theory]] · [[006_AI_Directed_Engineering]] · [[specs/2026-08-21_rts-barcode-labels]] · [[specs/2026-07-14_rts-use-and-pos-source]] · [[plans/active/2026-08-25_rts-barcode-inventory-cutover_plan]]

## Problem

RTS inventory today is mostly a **synced mirror of case lines**, while RTS barcode labels only create stickers that stock into **packaging / finished** — not the RTS sellable pool. That is misleading and will not scale once returns already have barcodes.

Staff need: case recording for ops, barcode scan for sellable RTS on-hand, and packing/POS consume by scan — with sales showing how many were RTS.

## Users & roles

| Role | Job |
|------|-----|
| CSR / RTS recorders | Record RTS cases (and damaged/leak lines) as today |
| Ops with barcode stock-in | Scan sellable RTS units into RTS inventory |
| Packing / deliveries | Must scan RTS barcodes when packing from RTS stock |
| POS | Must scan RTS barcodes when selling RTS walk-in |
| Executive / viewers | See RTS inventory, history, RTS sold/shipped indicators |

## Desired outcome (success criteria)

- [ ] **Record RTS** still creates cases and line history; it does **not** write sellable RTS inventory qty for new cases after cutover
- [ ] Sellable RTS on-hand only increases when a unit barcode is **scanned into RTS inventory**
- [ ] Unlabeled physical RTS → Barcode labels → stick → scan into RTS inventory
- [ ] Returns that already have barcodes → scan into RTS inventory (no new sticker required)
- [ ] Damaged / leak still recorded on the case; **no** scan into sellable RTS inventory (units go back toward production / damaged handling as today)
- [ ] Packing and walk-in POS that use RTS stock **must scan** RTS barcodes (no qty-only Use RTS for sellable)
- [ ] Sales / ship reporting can show how many units were RTS (POS vs shipped)
- [ ] Historical cases and old `rts_inventory_rows` qty remain as records; cutover applies to **new** sellable inventory path only

## Behavior (business rules)

1. **Recorder kept** — case capture, attachments, claim status, damaged/leak lines unchanged in purpose.
2. **Cutover** — for new work after go-live: sellable inventory is barcode-backed only. Existing case records stay; do not rewrite past sales or past packaging stock-ins as RTS.
3. **No barcode on unit** — generate RTS label stickers first, then scan into RTS sellable inventory (generator still does not invent stock).
4. **Has barcode** — scan into RTS sellable inventory on the RTS stock-in path.
5. **Damaged / leak** — record on case only for inventory purposes of this feature; do not require scan into sellable RTS pool (going back to production / damaged flow).
6. **Consume** — packing and POS must scan barcodes that belong to RTS sellable units; reject or block qty-only sellable Use RTS once cutover is on.
7. **Reporting** — POS and ship paths mark / count RTS consumption so sales can show RTS vs non-RTS.
8. **Already shipped `rts_label` stickers** — out of scope to fix: if they were stocked into packaging and shipped/sold as non-RTS packaging, leave history as-is.
9. **Printed but not yet scanned stickers** — may still be scanned into RTS sellable under the new stock-in rule (physical unit still on hand).

## User flow (high level)

1. Staff records RTS case (sellable + damaged/leak lines as needed).
2. For sellable units without stickers: Barcode labels → print → stick.
3. Staff scans each sellable unit into **RTS inventory**.
4. Later: packing scans those barcodes into parcels, or POS scans for walk-in sale.
5. Dashboard / sales show RTS sold (walk-in) and RTS shipped counts from scan-backed consumption.

## Data & integrity (conceptual)

- Case = ops truth (what returned, claim, photos, damaged/leak).
- Barcode unit in RTS sellable pool = sellable on-hand truth after cutover.
- Aggregate RTS inventory rows stay as a rollup for UI; they must stay consistent with barcode units for new stock-in/out (implementation detail in plan).
- Provenance `rts_label` on batches remains useful for unlabeled→sticker origin; future returns may use production barcodes scanned into RTS.
- No destructive wipe of historical cases or past inventory rows.

## UI/UX intent

- Record RTS: clear that sellable stock is added by **scan**, not by saving the case.
- RTS inventory: stock-in by scan; on-hand reflects scanned sellable units.
- Barcode labels: stickers for unlabeled RTS; copy states generate ≠ inventory.
- POS / packing: scan-required for RTS; sales indicator for RTS walk-in / shipped.

## Out of scope

- Rewriting past POS/ship transactions that used packaging stock from early `rts_label` scans
- Auto-assigning damaged/leak into sellable RTS via scan
- Removing case recording
- Multi-tenant or customer-facing returns portal

## Open decisions (locked by chat 2026-08-25)

1. Damaged/leak: record only; no sellable inventory scan — **yes**
2. Cutover: new inventory path only; keep records — **yes**
3. Packing/POS: must scan RTS barcode — **yes**

## Approval to plan

- [ ] Spec reviewed by human
- [ ] Ready for implementation plan (draft: [[plans/active/2026-08-25_rts-barcode-inventory-cutover_plan]])
