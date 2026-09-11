# Problem: Inventory check batch/date documentation

**Date:** 2026-09-11

## Problem

Auditors needed batch number and bottling date on every count line for the trail, without using those fields for inventory matching. Confirm/history needed one expandable group per batch when multiple bottling dates exist.

## Cause

Scan-only redesign made batch optional; compare had no nested batch documentation view.

## Fix

- Require `batch_no` + `bottling_date` on scan/update (docs only).
- Phase 81 unique key `(event_id, code, batch_no, bottling_date)` for SKU lines.
- Compare payload `batches[]` with expandable SPA UI for all roles that see compare.
- Scan → pending docs → confirm line.

## Files

- `includes/inventory_check.php`, `api/v1/inventory_check.php`
- `sql/migration_phase81_inventory_check_scan_docs.sql`
- `app/src/app/pages/inventory/InventoryCheckPage.tsx`
- `app/src/app/components/inventory/InventoryCheckBatchDocs.tsx`
- `app/src/app/components/inventory/InventoryCheckHistoryDetailModal.tsx`
- `app/src/app/lib/inventoryCheck.ts`
- `tests/inventory/inventory-check-scan-only.spec.ts`
