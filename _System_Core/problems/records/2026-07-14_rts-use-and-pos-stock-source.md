---
date: 2026-07-14
type: problem-record
status: fixed
area: rts
tags: [rts, pos, inventory, use-rts]
---

# Problem: RTS Deduct/Sell had no packaging/POS use trail

## Symptom

Sellable RTS stock was deducted via **Deduct / Sell** with no clear “where used” trail, and POS always pulled packaging/dept FG with no way to sell from the RTS pool.

## Cause

No `rts_pool_use_log`; POS checkout had no `stock_source`.

## Fix

→ [[fixes/rts-use-and-pos-stock-source]]

## Files

| Role | Path / note |
|------|-------------|
| Migration | `sql/migration_phase53_rts_pool_use_log.sql` |
| Includes | `includes/rts_inventory_sync.php`, `honey_pos.php`, `dept_pos.php` |
| API | `api/v1/rts.php`, `api/v1/pos.php` |
| SPA | `app/src/app/pages/RTSPage.tsx`, `RTSDamagedInventoryPage.tsx`, POS honey/dept + `POSStockSourceModal.tsx` |
| Damaged follow-up | Exec Edit/Add on damaged pool (`pool: damaged`); `damaged_use_log` lists `rts_damaged_use_events` (date / summary / account) |

## Deploy

- Run `php tools/apply_migration_phase53.php` on each env
- `cd app && npm run build` + hard refresh
- Regenerate memory bank (verify already ran generate)

## Links

- Spec: [[specs/2026-07-14_rts-use-and-pos-source]]
- Plan: [[plans/archive/2026-07-14_rts-use-and-pos-source_plan]]
