---
status: approved
spec: "[[specs/2026-08-14_honey-bottle-barcode-inventory]]"
created: 2026-08-14
approved_by: chat 2026-08-14
---

# Implementation Plan: Honey Bottle Barcode Inventory

> Spec: [[specs/2026-08-14_honey-bottle-barcode-inventory]]

## Summary

Add per-bottle barcodes inside Pyx Tracker. Units are truth; `honey_packaging_rows` aggregates are a transactional mirror. Scan is stock-in and the sales-log writer. Ship scan stops deducting.

## Constraints (must read)

- [[000_System_Architecture]]
- [[003_Fragile_Edges]]
- UI: [[004_SPA_Design_System]] · [[005_SPA_UX_Patterns]]

## Impact preview

| Area | Change |
|------|--------|
| API | new `api/v1/bottle_barcodes.php`; sales.php channel; pos checkout unit ids; ship deduct off |
| Includes | new `honey_bottle_barcodes.php`; parcel ship; honey production stock-out; POS; sales report; RTS create |
| DB | `sql/migration_phase54_honey_bottle_barcodes.sql` |
| SPA | request / admin / scan / pack / reconcile pages; POS scan; sales channel pie |

## Implementation steps

See Cursor plan Honey Bottle Barcode Inventory. Phases 1–6 in that file.

## Approval

- [x] Human approved — OK to implement in Agent mode
- Approved date: 2026-08-14
