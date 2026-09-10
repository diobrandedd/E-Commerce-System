---
status: shipped
created: 2026-09-10
owner: ops
area: inventory
tags: [honey, production, warehouse, progress, gallons, overdrawn]
---

# Honey lot gallon progress bars (+ production overdraw)

> Compact per-row progress bars on Honey **Production** and **Warehouse** lots (`current_qty` / `initial_qty`). Production bottle scan **warns** but **allows negative** gallons so ops can see the shortfall and continue.

Links: [[010_Product_Theory]] · [[003_Fragile_Edges]] · [[005_SPA_UX_Patterns]] · [[plans/active/2026-09-10_honey-lot-gallon-progress_plan]]

## Problem

Gallon remaining is a bare number; depletion is hard to read at a glance. Production bottle scan **blocks** when gallons are short, which stops ops from finishing packaging when they already know the lot is wrong/short.

## Locked behavior (chat 2026-09-10)

1. Progress bar on each **Production** and **Warehouse** lot row (desktop + mobile). Grain = **per lot row**, not aggregated by supplier code.
2. Fill = remaining / initial; negative remaining → amber **overdrawn** state (empty bar + overdrawn copy).
3. Production bottle/SKU scan into packaging: if used gallons &gt; remaining → **warn**, still stock in, allow `current_qty` negative.
4. Warehouse → production stock-out stays hard-block on insufficient qty.
5. Packaging stage unchanged (no progress bars in this spec).
6. Settings `honey.ml_per_gallon` math unchanged.

## Success criteria

- [x] Production + Warehouse rows show compact gold progress with current/initial caption.
- [x] Overdrawn production lots show amber overdrawn UI after short scans.
- [x] Short production scan succeeds with a non-blocking warning in the scan panel.
- [x] Warehouse stock-out still rejects qty &gt; available.
- [x] Playwright smoke covers progress + overdrawn display.

## Non-goals

- Packaging progress bars
- Negative warehouse stock-out
- Supplier-code aggregated bars
- Changing ml-per-gallon Settings UI
