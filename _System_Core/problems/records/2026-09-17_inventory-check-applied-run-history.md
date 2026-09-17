---
date: 2026-09-17
type: problem-record
status: fixed
area: inventory-check
tags: [problem]
---

# Problem: No history view for confirmed / merged audit stock updates

## Symptom

After Complete or merged Compare + Confirm, executives could not browse frozen mismatch lines; merge confirms were hard to find from History.

## Cause

→ [[causes/inventory-check-no-applied-run-history]]

## Fix

→ [[fixes/inventory-check-applied-run-history]]

## Files

| Role | Path / note |
|------|-------------|
| Primary | `includes/inventory_check.php`, `includes/inventory_check_apply.php` |
| API | `api/v1/inventory_check.php` |
| SPA | `InventoryCheckPage.tsx`, `InventoryCheckAppliedRunDetailModal.tsx`, `InventoryCheckHistoryDetailModal.tsx`, `inventoryCheck.ts` |

## Deploy

- PHP + `cd app && npm run build` + hard refresh
