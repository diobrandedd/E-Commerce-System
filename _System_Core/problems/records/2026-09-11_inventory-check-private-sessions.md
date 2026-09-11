---
date: 2026-09-11
type: problem-record
status: fixed
area: inventory-check
tags: [inventory-check, private-sessions, compare]
---

# Problem: Shared inventory audit + compare showed unscanned products

## Symptom

One open audit was shared by all users; Complete/Comparison listed every honey size with 0 audited. No per-user accountability or merge of separate day counts.

## Cause

→ [[causes/inventory-check-shared-session]]

## Fix

→ [[fixes/inventory-check-private-scoped-compare]]

## Files

| Role | Path / note |
|------|-------------|
| SQL | `sql/migration_phase80_inventory_check_private_sessions.sql` |
| Domain | `includes/inventory_check.php` |
| API | `api/v1/inventory_check.php` |
| SPA | `InventoryCheckPage.tsx`, `inventoryCheck.ts` |

## Deploy

- Run phase 80; PHP + SPA build; hard refresh
