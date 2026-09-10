---
date: 2026-09-10
type: cause
---

# Cause: Honey lot qty shown without progress; production deduct blocked shortfalls

Warehouse and production rows already had `current_qty` / `initial_qty`, but the UI only rendered the remaining number. `honey_production_deduct_gallons` required `current_qty >= used`, so short bottle scans failed instead of completing with a visible shortfall.
