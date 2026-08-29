---
status: shipped
spec: "[[specs/2026-08-26_sku-barcode-inventory]]"
created: 2026-08-26
approved_by: chat
---

# Implementation Plan: SKU barcodes (one code per product)

> Spec: [[specs/2026-08-26_sku-barcode-inventory]]

## Summary

Replace unique per-unit barcode generation for new labels with a permanent SKU catalog (`product_sku_barcodes`). Print N identical Code128s. Stock-in / pack / POS / inventory check / restock use shared lookup (SKU then leftover unique) and qty. Keep unique unit tables for transition.

## Constraints (must read)

- [[000_System_Architecture]]
- [[003_Fragile_Edges]]
- UI: [[004_SPA_Design_System]] · [[005_SPA_UX_Patterns]]

## Impact preview

| Area | Change |
|------|--------|
| API | `sku_barcodes.php`; extend bottle/dept/rts print, stock-in, pack, POS, inventory_check |
| Includes | `sku_barcodes.php`; wire honey/dept/pos/inventory_check/pack_cancel |
| DB | `migration_phase69_sku_barcodes.sql` |
| SPA | Settings Barcode Settings; request/print panels; scan+qty UIs |
| Legacy / Python | none |

## Implementation steps

### Phase 1 — Data / backend

1. [x] Spec + this plan + System State
2. [ ] Migration + `includes/sku_barcodes.php` + API
3. [ ] Print sheets (identical copies); stop unique generate for new labels
4. [ ] Lookup + qty on stock-in / pack / POS / check / restock

### Phase 2 — Frontend

1. [ ] Settings Barcode Settings
2. [ ] Request/print + scan qty UX

### Phase 3 — Cleanup

1. [ ] Update 010 + 003
2. [ ] Regenerate memory bank; problem record; archive plan

## Approval

- [x] Human approved — OK to implement in Agent mode
- Approved date: 2026-08-26
