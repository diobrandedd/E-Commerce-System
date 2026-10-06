---
date: 2026-10-06
type: cause
area: packaging-materials
---

# Cause: Packaging stock-outs only exposed per stock detail

`material_packaging_deduct_for_use` writes negative `stock_entries` with `use:` labels and `entered_by`, but the only SPA reader was `material_packaging_recent_entries` on a single `stock_id`. No paginated cross-catalog history API or page existed.
