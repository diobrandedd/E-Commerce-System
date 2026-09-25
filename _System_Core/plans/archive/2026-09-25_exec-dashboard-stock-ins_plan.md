---
status: approved
spec: chat-approved (impeccable critique follow-up)
created: 2026-09-25
approved_by: user chat "go ahead and build" + daily stock-ins lead
---

# Implementation Plan: Exec action hub + daily stock-ins charts

## Summary

Tighten exec `/dashboard` into an ops action hub (clickable fulfillment metrics + packing urgency) and add **daily stock-in** trends: quick view on exec dashboard (no duplicate pool wall) and full filters on inventory product pages. Series from `sku_barcode_stock_events`.

## Constraints

- [[000_System_Architecture]] · [[003_Fragile_Edges]] · [[004_SPA_Design_System]] · [[005_SPA_UX_Patterns]]
- Action hub first; chart secondary — no forecasts
- Do not re-show pool/on-hand cards beside the chart where the page already owns pool

## Impact preview

| Area | Change |
|------|--------|
| Includes | `dashboard_stock_ins.php` — daily series + filter options |
| API | `dashboard.php` action `stock_ins_series` |
| SPA | `DashboardStatCard` links/urgency; `StockInsTrendChart`; slim exec stock strip; honey + dept product pages |

## Implementation steps

### Phase 1 — Backend
1. [ ] `dashboard_stock_ins_daily_series` + families (all / honey / pt / brc / jar_cacao / honey_comb) + variant/size for honey
2. [ ] API `stock_ins_series` (days 7|30|90)

### Phase 2 — Exec hub
1. [ ] Stat cards deep-link; packing primary when > 0
2. [ ] Replace bulky display-stock hero with compact on-hand + quick stock-ins chart

### Phase 3 — Inventory
1. [ ] Full chart on Honey + DeptProductFinished pages (locked family where applicable)

### Phase 4
1. [ ] verify.ps1 · memory bank · problem record

## Done when

- Exec can click packing/holds/shipped/RTS into work
- Exec sees 7/30d daily stock-ins with family chips
- Inventory product pages show fuller filter chart without duplicating pool UI
