---
date: 2026-07-01
type: problem-record
status: fixed
area: fulfillment-online-parcels
tags: [problem, feature]
---

# Problem: No way to validate partner seller sold lists against shipped Online Parcels

## Symptom

Boss receives monthly sold-list Excel from partner sellers (Ian/Jono/Joven) and needs to confirm which order numbers were actually shipped in Pyx Tracker, with a highlighted export for audit.

## Cause

Online Parcels registry had `partner_seller` + `order_no` but no reconciliation tool or export workflow for external sold lists.

## Fix

Partner sold list page under **Order management** sidebar: upload Excel → match `order_no` to `online_order_parcels` (`partner_seller`, `status=shipped`) → summary stats + green/red Order No highlight export via `partner_sold_workbook.py`.

## Files

| Role | Path / note |
|------|-------------|
| API | `api/v1/partner_sold_reconcile.php` |
| PHP | `includes/partner_sold_reconcile.php` |
| Python | `includes/partner_sold_workbook.py` |
| SPA | `app/src/app/pages/fulfillment/PartnerSoldReconcilePage.tsx` |
| Nav | `app/src/app/components/Sidebar.tsx` (Order management group) |

## Verify

`tools/verify.ps1` PASS (PHP + SPA build + memory bank).
