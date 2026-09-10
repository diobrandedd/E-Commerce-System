---
date: 2026-09-10
type: problem-record
status: fixed
area: inventory-check
tags: [inventory-check, scan, batch]
---

# Problem: Check Inventory Add-to-list required batch; compare was packaging-lot based

## Symptom

Auditors had to enter packaging batch numbers to count finished bottles/pouches. Matching was tied to packaging lots rather than finished on-hand totals.

## Cause

→ [[causes/inventory-check-batch-required]]

## Fix

→ [[fixes/inventory-check-scan-only-no-batch]]

## Files

| Role | Path / note |
|------|-------------|
| Domain | `includes/inventory_check.php` |
| SPA | `InventoryCheckPage.tsx`, `BarcodeCameraScannerModal.tsx` |
| Spec | [[specs/2026-09-10_inventory-check-scan-only]] |

## Deploy

- PHP + `cd app && npm run build` + hard refresh; regenerate memory bank
