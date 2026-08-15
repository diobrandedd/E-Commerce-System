---
status: approved
spec: "[[specs/2026-08-15_paragis-pouch-barcode-inventory]]"
created: 2026-08-15
approved_by: chat
---

# Implementation Plan: Paragis Tea Pouch Barcodes

> Spec: [[specs/2026-08-15_paragis-pouch-barcode-inventory]]

## Summary

Replace Dried Paragis **Pouch** with honey-style pouch barcodes: request (qty + custom batch), generate/print, scan-in to finished stock without WIP/packaging deducts.

## Constraints

- [[000_System_Architecture]] · [[003_Fragile_Edges]] · [[004_SPA_Design_System]] · [[005_SPA_UX_Patterns]]

## Impact preview

| Area | Change |
|------|--------|
| API | New `api/v1/paragis_pouch_barcodes.php`; gate `dept_pt_pouch` |
| Includes | New `includes/paragis_pouch_barcodes.php` |
| DB | `sql/migration_phase57_paragis_pouch_barcodes.sql` |
| SPA | RawDeptMaterialPage + panels + client lib |

## Implementation steps

### Phase 1 — Data / backend
1. Migration for requests, batches, barcodes, codes_issued, units
2. Include + API actions
3. scan_stock_in bumps finished only; dept_pt_pouch returns use-barcodes error

### Phase 2 — SPA
1. Client `paragisPouchBarcodes.ts`
2. Panels on Dried Paragis; remove Pouch button
3. Camera continuous scan + beeps

### Phase 3 — Docs / verify
1. Memory bank + problem record + regenerate maps
2. verify.ps1

## Done when

- Request with `JR073026` → generate → print → scan N → finished +N, WIP unchanged
- Pouch button gone on Dried Paragis
