---
date: 2026-10-01
type: cause
area: honey-production
tags: [cause]
---

# Cause: Production list APIs omitted stock-out actor

`production_variant_list` / voided list returned lot qty and dates only. Actor lived on `honey_warehouse_stock_out_events.stocked_out_by` (and fallback `honey_production_rows.created_by`) but was never joined for the SPA.

→ Problem: [[records/2026-10-01_honey-production-stock-out-actor]]
