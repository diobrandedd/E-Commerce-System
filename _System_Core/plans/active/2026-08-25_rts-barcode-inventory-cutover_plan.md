---
status: draft
spec: "[[specs/2026-08-25_rts-barcode-inventory-cutover]]"
created: 2026-08-25
approved_by: pending
---

# Implementation Plan: RTS barcode inventory cutover

> Spec: [[specs/2026-08-25_rts-barcode-inventory-cutover]]  
> Related: [[specs/2026-08-21_rts-barcode-labels]] · [[specs/2026-07-14_rts-use-and-pos-source]] · [[problems/records/2026-08-25_rts-barcode-job-scan-progress]]

## Summary

Stop writing **sellable** RTS on-hand from case sync. Sellable stock enters only when a honey/dept barcode is scanned into the **RTS sellable** pool (creating/updating the unit + bumping `rts_inventory_rows`). Record RTS stays for cases (including damaged/leak qty on the case). Packing and POS must consume RTS by scanning units in that pool (block qty-only Use RTS for sellable). Sales/ship reporting counts RTS via existing `honey_sales_log.source` / pool use log, extended for pack-scan. No rewrite of historical packaging stock-ins or past sales.

## Constraints (must read)

- [[000_System_Architecture]] — IMS `api/v1` + includes; no new includes→api requires
- [[003_Fragile_Edges]] — dual auth; RTS SPA uses `rts_cases` / IMS; runtime schema probes
- UI: [[004_SPA_Design_System]] · [[005_SPA_UX_Patterns]] · feedback-animations
- Theory update required: [[010_Product_Theory]] §2 today says SPA inventory is a **synced mirror** — replace with barcode-backed sellable after cutover
- `source_line_item_id` already nullable (phase 47) — barcode-backed RTS rows can seed without a case line
- Do **not** wipe historical `rts_inventory_rows` or cases
- Early `rts_label` stickers already scanned into **packaging** and shipped: leave as-is (spec §8)

## Impact preview

| Area | Change |
|------|--------|
| API | RTS stock-in scan action(s); gate Use RTS; pack/POS scan rules; sales indicators |
| Includes | `rts_inventory_sync.php` (skip sellable sync), `honey_bottle_barcodes.php` / `dept_pouch_barcodes.php` (RTS stock-in + consume), pack session confirm, POS paths |
| DB | Small migration: cutover flag + optional `source_barcode_unit` provenance on inventory rows (or reuse seed rows only) |
| SPA | Record RTS copy; RTS inventory scan-in UI; disable sellable Use RTS qty; POS/pack scan-only for RTS; sales RTS counts |
| Legacy / Python | none |

_Run `impact.php` on: `rts_inventory_sync_run`, `honey_bottle_scan_stock_in`, `honey_bottle_pos_consume`, `honey_bottle_pack_session_confirm`, `dept_pouch_scan_stock_in`, `rts_inventory_deduct_sell_*` before edits._

## Design decisions (for implementers)

### Unit model (normalize)

Today `honey_bottle_rts_receive` sets `status = 'rts'` + `pool = rts_sellable`, while POS lookup requires `status = in_stock`. That blocks barcode POS for RTS units.

**Cutover standard:** available sellable RTS units use:

- Honey: `pool = 'rts_sellable'`, `status = 'in_stock'`
- Dept: add/use a pool column if missing, or mirror with `status = 'in_stock'` + explicit RTS flag/table — prefer aligning dept with a `pool` enum if not present; otherwise document dept equivalent in phase 1 spike

When packing/POS consumes: set `packed` / `sold` as today; deduct `rts_inventory_rows` (sellable) instead of packaging aggregates.

When recording a case with bottle codes (existing path): prefer moving into the same standard (`in_stock` + `rts_sellable`) **or** leave case-receive as optional/legacy and push staff to RTS stock-in scan — prefer one path: **RTS inventory scan-in** is canonical after cutover; case `bottle_codes` should not invent sellable qty (sync already stopped).

### Sellable sync cutover

In `rts_inventory_sync_run`:

- If cutover flag on: for lines that would map to **sellable**, advance cursor **without** inserting/updating sellable inventory rows
- Still sync **damaged** pool from case lines (damaged stays qty-on-case → damaged inventory mirror; no barcode required per locked decision)
- Flag stored in DB (e.g. `rts_sync_state.sellable_from_cases TINYINT NOT NULL DEFAULT 1`) so rollback is one UPDATE

### Stock-in into RTS (new)

New domain function(s), e.g. `honey_bottle_rts_stock_in_scan` / `dept_pouch_rts_stock_in_scan`:

| Prior state | Behavior |
|-------------|----------|
| Unknown code | Error |
| Code exists, no unit | Create unit `rts_sellable` + `in_stock`; +1 sellable `rts_inventory_rows` (seed row if needed); mark barcode scanned; **do not** +1 packaging |
| Unit packaging `in_stock` | Refuse or require explicit “move to RTS” (default: refuse — avoid double-counting); staff use packaging path for those leftovers |
| Unit already `rts_sellable` `in_stock` | Idempotent error “already in RTS inventory” |
| Unit sold/shipped/packed | Allowed path for “return already had barcode”: move to `rts_sellable`/`in_stock`, +1 RTS row, do not + packaging (similar intent to today’s receive, but inventory-backed) |

Accept both `rts_label` batches and normal production barcodes.

API: prefer `api/v1/rts.php` action `stock_in_scan` (or `bottle_barcodes.php` / `dept_pouch_barcodes.php` with `pool=rts_sellable`) gated by `rts.record` or new `rts.stock_in` — default reuse `rts.record` + inventory viewers who already scan.

### Consume

- **POS:** barcode scan required for RTS; unit must be `rts_sellable` + `in_stock`; deduct RTS row; `honey_sales_log.source = 'rts'`; remove reliance on manual stock_source for barcode carts (stock_source inferred from unit pool). Qty-only RTS checkout: disable when cutover on.
- **Pack scan:** pack session must accept `rts_sellable` units; on confirm deduct RTS not packaging; log `source = 'rts'` + `sale_type = 'parcel'`.
- **Use RTS** on RTS page: hide/disable for **sellable** when cutover on; keep damaged Use if still needed.

### Reporting

- Walk-in RTS: count `honey_sales_log` where `source = 'rts'` and `sale_type = 'walk_in'` (already partially true for barcode POS).
- Shipped RTS: same log with `sale_type = 'parcel'` + `source = 'rts'` after pack confirm fix.
- Dept: ensure pack/POS write analogous RTS markers (pool use log and/or sales fields).
- Sales UI: small RTS vs non-RTS indicator on exec Sales (and/or honey sales report) — do not mix into packaging-only queries without a filter toggle.

### UI copy

- Record RTS: “Saving the case does not add sellable stock — scan barcodes into RTS inventory.”
- RTS inventory page: scan-in control + subtitle not “Synced from operational RTS records” alone; show last scan / barcode-backed note; keep historical synced qty visible.
- Barcode labels: generate ≠ inventory (already partly true); ratio remains `status=scanned` (after cutover, scans into RTS should set scanned + unit in RTS pool — job expand can optionally show `unit_pool`).

## Implementation steps

### Phase 0 — Spec / theory gate

1. [ ] Human marks [[specs/2026-08-25_rts-barcode-inventory-cutover]] approved
2. [ ] Human approves **this** plan (checkbox below)
3. [ ] Update [[010_Product_Theory]] §2 for barcode-backed sellable RTS (theory before code)
4. [ ] Point [[001_System_State]] Active Spec/Plan here

### Phase 1 — Data / backend core

1. [ ] Migration phase68 (name TBD): `rts_sync_state.sellable_from_cases` (default `1`); enable cutover by setting `0` on deploy
2. [ ] Spike dept pouch units: confirm how to store RTS pool (add `pool` column if missing — migration)
3. [ ] `rts_inventory_sync_run`: honor flag — skip sellable inserts; keep damaged sync; still advance `last_line_item_id`
4. [ ] `honey_bottle_rts_stock_in_scan` + dept equivalent; bump sellable aggregates via existing seed/deduct helpers (inverse of deduct)
5. [ ] Normalize new stock-in to `pool=rts_sellable`, `status=in_stock`
6. [ ] Adjust `honey_bottle_rts_receive` / dept receive used from case save: either align to same unit state **without** double-counting inventory rows, or stop calling receive for sellable and document “scan after record”

### Phase 2 — Consume paths

1. [ ] POS honey/dept: scan RTS units; infer source from pool; block qty-only RTS when flag off
2. [ ] Pack session scan + confirm: allow RTS pool; deduct RTS; log `source=rts`
3. [ ] Disable sellable Use RTS API + UI when cutover on
4. [ ] Sales report / Sales page: RTS walk-in + RTS shipped counts (filter or badges)

### Phase 3 — SPA

1. [ ] `RTSRecordPage` (and related): copy that sellable stock is scan-only
2. [ ] `RTSPage`: scan-in UI (wedge/camera reuse); remove misleading “synced only” subtitle; gate Use RTS
3. [ ] POS / pack-scan pages: enforce barcode for RTS; feedback banners
4. [ ] Barcode labels footer/help: stock-in target = RTS inventory after cutover
5. [ ] Guide modal snippets where guides already exist

### Phase 4 — Integration & cleanup

1. [ ] Deploy migration; set `sellable_from_cases = 0` when ops ready
2. [ ] Verify loop (`tools/verify.ps1`); packaging-tester N/A
3. [ ] Regenerate memory bank; problem record; [[002_Memory_Bank]]
4. [ ] Archive this plan; mark spec shipped

## Files to touch (expected)

| File | Action |
|------|--------|
| `sql/migration_phase68_rts_barcode_inventory_cutover.sql` | create |
| `includes/rts_inventory_sync.php` | modify — skip sellable sync; maybe credit helper for stock-in |
| `includes/honey_bottle_barcodes.php` | modify — RTS stock-in; pack confirm; POS; receive align |
| `includes/dept_pouch_barcodes.php` | modify — RTS stock-in + pool; pack/POS |
| `includes/honey_pos.php` / `includes/dept_pos.php` | modify — scan-only RTS |
| `includes/honey_sales_report.php` | modify — RTS filters/counts |
| `api/v1/rts.php` | modify — stock_in_scan; gate use |
| `api/v1/bottle_barcodes.php` / `dept_pouch_barcodes.php` | modify if actions live there |
| `app/src/app/pages/RTSPage.tsx` | modify |
| `app/src/app/pages/rts/RTSRecordPage.tsx` | modify |
| `app/src/app/pages/rts/RTSBarcodeLabelsPage.tsx` | copy tweak |
| `app/src/app/pages/pos/*` · pack-scan pages | modify |
| `app/src/app/pages/exec/SalesPage.tsx` | modify |
| `_System_Core/010_Product_Theory.md` | modify §2 |
| `_System_Core/001_System_State.md` | modify when active |

## Test plan

- [ ] Record new sellable RTS case → sync runs → **sellable on-hand unchanged**; case visible in records
- [ ] Record damaged/leak lines → damaged pool still updates (if sync kept) / case shows lines
- [ ] Generate RTS labels → print → scan into RTS inventory → sellable +1 per code; packaging unchanged
- [ ] Scan production barcode (already existed) from a return into RTS → sellable +1
- [ ] Expand job on Barcode labels → scanned ratio increases
- [ ] POS: scan RTS unit → sale succeeds; sales log `source=rts`; sellable −1; packaging unchanged
- [ ] POS: cannot sell RTS by qty-only toggle when cutover on
- [ ] Pack scan: RTS unit → confirm → shipped/packed; sellable −1; sales/ship shows RTS
- [ ] Use RTS sellable button hidden/errors when cutover on
- [ ] Historical sellable qty from old sync still visible; no mass rewrite
- [ ] Rollback: set `sellable_from_cases = 1` restores case→sellable sync (new cases only); stock-in API can stay but ops stop using it

## Risks & mitigations

| Risk | Mitigation |
|------|------------|
| Double-count if sync and stock-in both add | Flag skips sellable sync; refuse packaging→RTS silent moves |
| `status=rts` vs `in_stock` breaks POS | Normalize to `in_stock` + `pool`; migrate or coerce on stock-in |
| Dept units lack pool | Phase 1 migration before stock-in |
| Pack confirm always deducted packaging | Branch on unit pool |
| Staff confusion during transition week | Copy on Record + RTS inventory; keep barcode labels generator |
| Executive set-totals fights barcode truth | Keep executive edit as override; document; optional later lock |

## Rollback

1. `UPDATE rts_sync_state SET sellable_from_cases = 1 WHERE id = 1;`
2. Re-enable Use RTS UI/API if gated on same flag
3. Leave barcode units already in `rts_sellable` in place (do not auto-delete)
4. Revert deploy if needed; migration flag column can remain

## Approval

- [ ] Human approved — OK to implement in Agent mode
- Approved date: _
- Approved by: _
