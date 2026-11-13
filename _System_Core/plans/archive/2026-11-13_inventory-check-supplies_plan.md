---
status: shipped
spec: "[[specs/2026-11-13_inventory-check-supplies]]"
created: 2026-11-13
approved_by: chat
---

# Implementation Plan: Inventory check supplies

> Spec: [[specs/2026-11-13_inventory-check-supplies]]

## Summary

Add `kind=supply` to Check Inventory: Check-only lookup (SKU then PKG), scan/compare/apply via `material_packaging_set_on_hand` with source `inventory_check_apply`, SPA Supplies badges.

## Files

| File | Action |
|------|--------|
| `sql/migration_phase98_inventory_check_supply.sql` | create |
| `includes/inventory_check.php` | lookup helper, scan supply, label/serialize/compare branches |
| `includes/inventory_check_apply.php` | apply_supply_qty; unique-codes skip supply |
| `includes/material_inventory.php` | optional `$source` on set_on_hand |
| `includes/inventory_edit_log.php` | source label for inventory_check_apply |
| `api/v1/inventory_check.php` | `lookup` action |
| `InventoryCheckPage.tsx`, `inventoryCheck.ts`, kind badge component, history/applied modals | SPA |

## Deploy

- Run phase98 · rebuild SPA · hard refresh
