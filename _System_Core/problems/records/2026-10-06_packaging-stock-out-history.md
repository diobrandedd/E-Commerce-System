---
date: 2026-10-06
type: problem-record
status: fixed
area: packaging-materials
tags: [problem, packaging, history, stock-out]
---

# Problem: No packaging materials stock-out history across items

> Main hub — audit who scanned/manual-deducted packaging warehouse stock.

## Symptom

Stock-outs were logged in `stock_entries` (`use:` + `entered_by`) but only visible per-item in the detail modal. Ops could not review all stock-outs and actors in one place.

## Cause

→ [[causes/packaging-stock-out-no-cross-item-history]]

## Fix

→ [[fixes/packaging-stock-out-history-page]]

## Files

| Role | Path / note |
|------|-------------|
| Primary | `includes/material_inventory.php` (`material_packaging_stock_out_history`) |
| API | `api/v1/materials.php` (`packaging_stock_out_history`) |
| SPA | `app/src/app/components/inventory/PackagingStockOutHistoryModal.tsx` |
| Nav | History button on `PackagingMaterialsPage.tsx` (no sidebar sibling) |
| Spec | [[specs/2026-10-06_packaging-stock-out-history]] |

## Deploy

- `cd app && npm run build` + hard refresh; regenerate memory bank

## Links

- **Plan archive:** [[plans/archive/2026-10-06_packaging-stock-out-history_plan]]
- **Related:** [[problems/records/2026-10-06_packaging-barcode-scan-stockout]]
