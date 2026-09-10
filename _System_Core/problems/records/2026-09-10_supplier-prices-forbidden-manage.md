---
date: 2026-09-10
type: problem-record
status: fixed
area: suppliers
tags: [suppliers, permissions, pricing]
---

# Problem: Supplier Master purchase prices showed Forbidden with Manage suppliers

## Symptom

Users with **Manage suppliers** could create/edit suppliers, but expanding a row for honey/dept purchase prices showed red **Forbidden.**

## Cause

→ [[causes/supplier-prices-executive-edit-only]]

## Fix

→ [[fixes/supplier-prices-accept-manage]]

## Files

| Role | Path / note |
|------|-------------|
| API | `api/v1/honey.php` (`supplier_*_prices` / `_save`) |
| SPA | `SuppliersPage.tsx`, `honeyPermissions.ts` |
| Catalog | `includes/permissions.php` (`suppliers.manage` description) |

## Deploy

- PHP + `cd app && npm run build` + hard refresh
