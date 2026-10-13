---
date: 2026-10-10
type: problem-record
status: fixed
area: honey-inventory
tags: [problem, honey, pack, cancel, pool]
---

# Problem: Products → Honey ignored pack deduct and cancel restock

## Symptom

Cancelling or packing honey changed the inventory audit, but Products → Honey stayed on the same bottle counts.

## Cause

→ [[causes/honey-pack-cancel-missed-variant-pool]]

## Fix

→ [[fixes/honey-pool-pack-and-cancel-restock]]

## Files

| Role | Path |
|------|------|
| Pool adjust | `includes/honey_bottle_barcodes.php` |
| Pack SKU | `includes/sku_barcodes.php` |
| Cancel restock | `includes/pack_cancel_restock.php` |
| Audit | `includes/inventory_check.php`, `includes/inventory_check_apply.php` |

## AI rules

Finished Light/Amber/Dark bottle qty for pack, cancel restock, and inventory check is the variant POOL row only.
