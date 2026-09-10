---
date: 2026-09-10
type: fix
---

# Fix: Per-row gallon progress + warn-allow production overdraw

1. `HoneyLotGallonProgress` on Production and Warehouse lot rows (fill = current/initial; amber when negative).
2. Production bottle/SKU stock-in deducts without a non-negative gate; returns `production_overdrawn` + `warning`; scan panel shows the warn and keeps depleted/overdrawn lots selectable.
3. Warehouse stock-out still blocks insufficient qty.
