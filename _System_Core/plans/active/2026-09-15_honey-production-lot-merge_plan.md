---
status: draft
spec: "[[specs/2026-09-15_honey-production-lot-merge]]"
created: 2026-09-15
approved_by: pending
---

# Implementation Plan: Honey production lot merge

> **AI drafts this** from a spec. Human approves before any code.  
> Spec: [[specs/2026-09-15_honey-production-lot-merge]]

## Summary

Stop part-by-part warehouse → production stock-outs from creating duplicate Production lots. Route **manual** `honey_warehouse_stock_out` through the same daily upsert used by scan stock-out (`daily_group_key`). Harden `production_list` so packaging joins do not multiply rows. Optionally consolidate existing same-day duplicate lots so the Production table / lot dropdown shrinks for current ops.

## Constraints (must read)

- [[000_System_Architecture]]
- [[003_Fragile_Edges]] (honey gallons live on stage rows; production overdraw allowed on bottle scan)
- [[specs/2026-09-10_honey-lot-gallon-progress]] — progress stays **per lot**, not supplier-code aggregate
- [[problems/fixes/honey-warehouse-supplier-date-barcode]] — existing scan merge + event ledger
- UI: [[004_SPA_Design_System]] · [[005_SPA_UX_Patterns]]

## Impact preview

| Area | Change |
|------|--------|
| API | `production_list` query; `warehouse_stock_out` behavior |
| Includes | `honey_warehouse_stock_out()` → shared daily upsert; extract helper from barcode stock-out if needed |
| DB | optional one-shot consolidate script / migration for NULL `daily_group_key` dupes |
| SPA | dropdown labels; guide text; no new route |
| Legacy / Python | none |

Run `impact.php` on `honey_warehouse_stock_out` and any extracted upsert helper before edit.

## Implementation steps

### Phase 1 — Shared daily upsert (backend)

1. [ ] Extract a shared helper (e.g. `honey_production_upsert_daily_lot`) from scan path logic: given supplier/code/arrival/variant/qty/date → upsert `honey_production_rows` by `daily_group_key`, write stock-out events when sources are known.
2. [ ] Change `honey_warehouse_stock_out()` to deduct the warehouse row, then **upsert** today’s daily lot instead of always INSERT; set `stock_out_date` + `daily_group_key`; write a `honey_warehouse_stock_out_events` row.
3. [ ] Keep warehouse hard-block when qty &gt; available.
4. [ ] Keep Light/Amber/Dark as separate keys.

### Phase 2 — List integrity

1. [ ] Fix `api_honey_production_list` (or include helper): one row per production lot — do not LEFT JOIN packaging in a way that duplicates lots (subquery / GROUP BY / omit packaging columns from list).
2. [ ] Enrich SPA Production-lot `<option>` labels with stock-out date when helpful (`code · variant · date · N gal`).

### Phase 3 — Historical consolidate (ops cleanup)

1. [ ] Script or migration tool: find production rows that share the same computed daily key (or supplier_code + arrival + variant + stock_out_date) with NULL/`missing` keys, sum gallons onto the keeper row, re-point packaging / events / barcode ties where safe, delete or mark empties.
2. [ ] Human gate: dry-run output before apply on prod.
3. [ ] Skip merge when variants differ or when packaging already branched in conflicting ways — log and leave for manual review.

### Phase 4 — UX copy + verify

1. [ ] Update Production Guide: part-by-part same day merges; variants stay separate.
2. [ ] Regenerate memory bank; problem record; [[002_Memory_Bank]]; archive plan; point [[001_System_State]].

## Files to touch (expected)

| File | Action |
|------|--------|
| `includes/honey_inventory.php` | modify `honey_warehouse_stock_out` |
| `includes/honey_warehouse_barcodes.php` | extract/share upsert helper |
| `api/v1/honey.php` | fix `production_list` |
| `app/src/app/components/honey/HoneyBottleBarcodePanels.tsx` | dropdown labels |
| `app/src/app/pages/honey/HoneyProductionPage.tsx` | guide copy if present |
| `sql/` or `tools/` | optional consolidate dry-run/apply |
| `_System_Core/problems/...` | record after ship |

## Test plan

- [ ] Manual stock-out 5 gal Amber `JR…`, then another 3 gal same warehouse row/day → **one** Production Amber lot with 8 gal.
- [ ] Scan stock-out still merges onto the same daily lot.
- [ ] Light and Amber same supplier code → **two** lots.
- [ ] Production list: lot with two packaging batches appears **once**.
- [ ] Dropdown shows one selectable Amber option; bottle stock-in deducts that lot.
- [ ] Warehouse stock-out still rejects qty &gt; available.
- [ ] Overdrawn production scan still warns and allows negative (unchanged).
- [ ] Consolidate dry-run on a DB copy with known duplicates.

## Risks & mitigations

| Risk | Mitigation |
|------|------------|
| Merging by supplier code alone mixes variants | Spec forbids; key includes variant |
| Re-pointing packaging on consolidate breaks batch uniqueness | Prefer same-day empty/no-packaging dupes first; leave conflicted rows |
| Event ledger gaps on old manual rows | New path always writes events; consolidate documents limits |
| Cross-day merge desired by ops | Ask before coding; out of scope unless approved |

## Rollback

- Revert PHP/SPA deploy; daily unique index already exists from phase 71.
- Consolidate is one-way — only run after dry-run approval; restore from DB backup if wrong.

## Approval

- [ ] Human approved — OK to implement in Agent mode
- Approved date: _
- Cross-day merge? **no** (default) / **yes** (extend plan)
