---
date: 2026-06-30
type: problem-record
status: fixed
area: sales-bulk-orders
tags: [problem, bulk-orders, honey, warehouse]
---

# Bulk honey orders (warehouse B2B)

## Problem

No workflow for large B2B honey sales (e.g. 100 gal) from warehouse with supplier-code traceability, COGS/revenue/margin, and controlled warehouse deduction.

## Fix

- Executive-only **Bulk orders** tab (`/sales/bulk-orders`)
- Draft with manual multi-supplier warehouse allocations + negotiation helper (margin % / ₱/gal / total ₱)
- **Confirm & deduct** reduces `honey_warehouse_rows.current_qty` (no production row)
- Void restores stock; supplier codes stored on `bulk_honey_order_allocations`

## Files

| Role | Path |
|------|------|
| Spec | `_System_Core/specs/2026-06-27_bulk-honey-orders.md` |
| Migration | `sql/migration_phase51_bulk_honey_orders.sql` |
| Core | `includes/bulk_honey_orders.php`, `includes/honey_inventory.php` (`honey_warehouse_bulk_deduct`) |
| API | `api/v1/bulk_orders.php` |
| SPA | `app/src/app/pages/sales/BulkHoneyOrdersPage.tsx`, `app/src/app/lib/bulkOrdersApi.ts` |

## Deploy

- `php tools/apply_migration_phase51.php`
- `cd app && npm run build`
