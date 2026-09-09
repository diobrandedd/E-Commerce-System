---
date: 2026-09-04
type: cause
status: documented
area: honey-production
tags: [cause]
---

# Cause: Packaging scan bypassed production gallons

SKU / unique stock-in on Packaging credited `honey_packaging_rows` aggregates (custom batch) without calling production gallon deduct. `honey_production_stock_out` existed but never credited finished bottles and had no SPA UI.
