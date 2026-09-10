---
status: shipped
spec: "[[specs/2026-09-10_sales-view-amounts]]"
created: 2026-09-10
approved_by: chat 2026-09-10
---

# Implementation Plan: Sales view amounts

> Spec: [[specs/2026-09-10_sales-view-amounts]]

## Summary

Add `sales.view_amounts`; migrate grant for existing `sales.view`; strip money in `sales.php`; hide ₱ on SalesPage.

## Files

| File | Action |
|------|--------|
| `includes/permissions.php` | catalog + prod_manager preset |
| `sql/migration_phase79_sales_view_amounts.sql` | grant continuity |
| `api/v1/sales.php` | strip + flag |
| `permissionKeys.ts` / `SalesPage.tsx` | SPA |
