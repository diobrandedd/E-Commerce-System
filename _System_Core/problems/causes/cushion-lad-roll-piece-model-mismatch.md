---
type: cause
tags: [cause, fulfillment-packaging, cushion, foam]
---

# Cause: Cushion and foam inventory model drifted from packing-floor reality

## What went wrong

Tier recipes mixed pre-cut cushion piece SKUs and multiple fixed roll SKUs, while ops actually uses two reusable cushion rolls (main/divider) plus LAD as a separate piece cushion for 350ml. Foam was modeled as sheet pieces even though stock-in is by roll and cutting happens during packing.

## Effects

- Tier deduct lines were harder to reason about and maintain
- Roll yields could not be updated in the stock-in action used by HR
- Bulk and gallon recipes used fixed roll SKUs instead of explicit column usage

## Prevent

Use explicit recipe units tied to physical handling:

- `column` for cushion roll cuts
- `bubble_piece` / `foam_piece` for cut pieces from roll yields
- `pcs` only for true piece SKUs (e.g., LAD cushion, honey comb)
