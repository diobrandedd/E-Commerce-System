---
status: shipped
approved: true
spec: "[[specs/2026-07-14_rts-use-and-pos-source]]"
created: 2026-07-14
approved_by: chat
---

# Plan: Use RTS + POS stock source

Approved via chat (implement attached plan).

## Approach

1. Migration `rts_pool_use_log` with `used_where` `pos` | `packaging`.
2. Log on RTS deduct; Use RTS API → packaging; POS `stock_source=rts` → pos.
3. SPA: Use RTS + Used RTS table; POS pre-checkout source modal.

## Files

- `sql/migration_phase53_rts_pool_use_log.sql`
- `includes/rts_inventory_sync.php`, `honey_pos.php`, `dept_pos.php`
- `api/v1/rts.php`, `api/v1/pos.php`
- `app/src/app/pages/RTSPage.tsx`, POS honey/dept pages
