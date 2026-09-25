---
date: 2026-09-25
type: problem-record
status: fixed
area: dashboard-spa
tags: [dashboard, stock-ins, inventory, execute]
---

# Problem: Exec dashboard felt thin / non-actionable; no stock-in trends

> Exec home was a sparse ops landing with inert fulfillment cards and a display-stock showcase, while Inventory product pages had pool detail but no daily stock-in history chart.

## Symptom

Dashboard metrics did not deep-link into work; packing backlog had no urgency hierarchy. Users expected inventory trend visibility (daily stock-ins by family / honey variant×size) without turning `/dashboard` into BI theater or duplicating pool UI next to charts.

## Cause

→ [[causes/dashboard-action-hub-without-stock-ins-series]]

## Fix

→ [[fixes/exec-dashboard-stock-ins-trends]]

## Files

| Role | Path / note |
|------|-------------|
| Include | `includes/dashboard_stock_ins.php` |
| API | `api/v1/dashboard.php` (`stock_ins_series`) |
| Chart | `app/src/app/components/dashboard/StockInsTrendChart.tsx` |
| Exec strip | `DashboardInventoryHeadline.tsx`, `DashboardShippedSection.tsx`, `DashboardStatCard.tsx` |
| Inventory | `HoneyProductInventoryPage.tsx`, `DeptProductFinishedPage.tsx` |
| Plan | [[plans/archive/2026-09-25_exec-dashboard-stock-ins_plan]] |

## Deploy

- Hard refresh `app/dist/`
- No migration (reads `sku_barcode_stock_events`)

## AI rules

Daily stock-ins lead on charts; pool detail stays on Inventory pages. Exec fulfillment cards must deep-link. Do not add forecast charts to `/dashboard`.
