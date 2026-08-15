---
date: 2026-08-15
status: approved
area: inventory-defect
---

# Spec: Jar Cacao finished-jar defect scan

## Goal

Staff scan a Jar Cacao sticker to mark a finished jar as defective: remove it from finished
inventory when it is in stock, otherwise show **Not in inventory**. Keep a defect history
(who / when / code / batch). Do **not** move units into the RTS damaged pool.

## Scope

- Product: `jar_cacao` only (UI on the Jar Cacao raw/finished page).
- Success path: unit `in_stock` → status `damaged`, finished qty −1, history row.
- Failure: no unit, or status ≠ `in_stock` → message **Not in inventory** (no mutation).
- History: paginated list of defect scans for cacao.

## Out of scope

- Paragis / BRC / Honey Comb (extend later via the same keys constant).
- RTS pool intake.
- Reversing a defect.
