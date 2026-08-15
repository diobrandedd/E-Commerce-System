---
date: 2026-08-15
spec: specs/2026-08-15_cacao-defect-scan
approved: true
---

# Plan: Jar Cacao defect scan + history

## Phase 1 — Schema

`sql/migration_phase62_dept_pouch_defect_log.sql` → `dept_pouch_defect_log`
(id, product_key, barcode_id, unit_id, code, batch_no, scanned_by, scanned_at, note nullable).

## Phase 2 — Backend

`includes/dept_pouch_barcodes.php`:
- `DEPT_POUCH_DEFECT_KEYS = ['jar_cacao']`
- `dept_pouch_defect_scan($pdo, $userId, $productKey, $code)` — gate product; require unit
  `in_stock`; else throw **Not in inventory**; mark `damaged` + `consumed_ref_type=defect`;
  `dept_inventory_deduct`; insert log.
- `dept_pouch_defect_history($pdo, $productKey, $page)` — newest first.

API `dept_pouch_barcodes.php`: `defect_scan` (POST), `defect_history` (GET).

## Phase 3 — SPA

- Client methods + `DeptPouchDefectPanel` (scan input/camera + history table).
- Jar Cacao page only: **Defect** header button + modal.

## Phase 4 — Verify

Run migration locally; verify; memory bank + problem record; archive plan.
