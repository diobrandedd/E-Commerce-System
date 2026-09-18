---
date: 2026-09-18
type: problem-record
status: fixed
area: honey-production
tags: [problem]
---

# Problem: Empty production lots kept stale “of 205 gal” max

## Symptom

Light showed **0% of 205 gal** after gallons were used up. Ops expected the max to clear when empty so the next warehouse stock-out starts a fresh “of X gal”.

## Cause

→ [[causes/honey-production-initial-never-reset]]

## Fix

→ [[fixes/honey-production-reset-initial-on-empty]]

## Files

| Role | Path / note |
|------|-------------|
| Helpers | `includes/honey_inventory.php` (`honey_production_deduct_gallons`, `honey_production_refill_initial_qty`, upsert lot) |
| Migration | `sql/migration_phase80_honey_production_reset_empty_initial.sql` |
| UI copy | `HoneyLotGallonProgress.tsx` |

## Deploy

- Run phase 80 once on prod
- Deploy PHP (+ SPA for empty-state copy)

## Links

- **Fragile edges:** [[003_Fragile_Edges#Inventory arrangement (material on-hand)]]
