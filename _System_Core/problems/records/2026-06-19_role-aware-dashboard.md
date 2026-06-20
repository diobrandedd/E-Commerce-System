---
date: 2026-06-19
type: problem-record
status: fixed
area: dashboard-spa
tags: [dashboard, roles, csr]
---

# Problem: Dashboard showed same inventory/cost data for all roles

> Single inventory-centric dashboard with financial tables visible to every role that could open `/dashboard`.

## Symptom

All roles landing on the dashboard saw stock counts, stock price history, and product cost recalculations regardless of role. CSR staff had no dashboard; staff/csr_staff login routes skipped dashboard. Financial peso tables were not gated to executive.

## Cause

Legacy SPA dashboard (`DashboardPage.tsx` + `api/v1/dashboard.php`) was built for exec/PM inventory home and never branched by role view. No CSR metrics endpoint existed.

## Fix

Role-aware `view` field on dashboard API (`inventory` | `csr` | `staff`); CSR metrics from `online_order_parcels` + RTS pool; financial queries executive-only (`can_view_financials`); SPA split into section components with `appFeedbackMotion`; all non-POS roles land on `/dashboard`.

## Files

| Role | Path / note |
|------|-------------|
| API | `api/v1/dashboard.php` |
| SPA page | `app/src/app/pages/DashboardPage.tsx` |
| Components | `app/src/app/components/dashboard/*` |
| Nav | `app/src/app/components/Sidebar.tsx`, `LoginPage.tsx` |

## Deploy

- `cd app && npm run build` + hard refresh
- Regenerate memory bank

## AI rules

Never return `recent_stock_changes` / `recent_cost_changes` to non-executive roles. CSR dashboard uses `view: csr` block only — do not show inventory cost tables to CSR.
