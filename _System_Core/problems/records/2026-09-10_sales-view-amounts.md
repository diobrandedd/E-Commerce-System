---
date: 2026-09-10
type: problem-record
status: fixed
area: sales
tags: [sales, permissions, amounts]
---

# Problem: Sales page always showed peso amounts

## Symptom

Anyone with View sales could see ₱ revenue and receipt money; no way to allow unit visibility only.

## Cause

→ [[causes/sales-amounts-tied-to-view]]

## Fix

→ [[fixes/sales-view-amounts-permission]]

## Files

| Role | Path / note |
|------|-------------|
| Catalog | `includes/permissions.php` |
| Migration | `sql/migration_phase79_sales_view_amounts.sql` |
| API | `api/v1/sales.php` |
| SPA | `SalesPage.tsx`, `permissionKeys.ts` |

## Deploy

- Run phase 79 on each DB; PHP + `cd app && npm run build`; hard refresh
