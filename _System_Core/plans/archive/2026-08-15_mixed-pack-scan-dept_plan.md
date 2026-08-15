---
status: approved
spec: "[[specs/2026-08-15_mixed-pack-scan-dept]]"
created: 2026-08-15
approved_by: chat
---

# Implementation Plan: Mixed pack scan (honey + dept)

## Summary

Extend Pack scan so one session can mix honey bottles with dept pouch/jar barcodes (pt, brc, jar_cacao, honey_comb). New companion items table; scan/confirm/get/list updated; History gains product filter.

## Constraints

- [[000_System_Architecture]] · [[003_Fragile_Edges]] · [[004_SPA_Design_System]] · [[005_SPA_UX_Patterns]]
- Do not break existing honey-only sessions or honey item FKs.

## Impact preview

| Area | Change |
|------|--------|
| DB | `sql/migration_phase60_pack_session_dept_items.sql` |
| Includes | `honey_bottle_pack_session_*` + dept lookup/deduct |
| API | `pack_session_list` gains `product_key` |
| SPA | Pack scan live + history filter/labels |

## Steps

1. Migration: `honey_bottle_pack_session_dept_items`
2. PHP: get/scan/confirm/list
3. API pass-through + SPA types/UI
4. Memory bank + archive plan

## Deploy

- Run phase 60 SQL on target DB
- `cd app && npm run build` + hard refresh
