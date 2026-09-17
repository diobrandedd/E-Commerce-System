---
date: 2026-09-17
status: approved
area: honey-inventory
---

# Spec: Honey packaging pools + production variant rows

## Goal

1. Packaging stock-in: scan barcode + qty only (no production lot / batch / bottling date picker).
2. Deduct production gallons FIFO by variant across supplier lots.
3. Finished honey UI: three expandable variant cards (Light / Amber / Dark) with size breakdown.
4. Production UI: three variant rows, expandable to supplier lots + gallons.

## Live data

Nothing deleted. Phase 79 folds live lot `current_*` into POOL rows; old rows remain for history/FKs; unique stickers keep working.

## Out of scope

Warehouse multi-variant stock-in changes; removing unique barcode tables; dept products.
