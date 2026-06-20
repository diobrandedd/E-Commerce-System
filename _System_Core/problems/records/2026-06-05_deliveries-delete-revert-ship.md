---
date: 2026-06-05
type: problem-record
status: fixed
area: deliveries
tags: [problem, deliveries, inventory]
---

# Problem: Deleted delivery row caused “already shipped” on rescan

## Symptom

After deleting a review-batch item, rescanning the same tracking returned “This parcel is already shipped.”

## Cause

→ [[causes/delete-item-left-parcel-shipped-state]]

## Fix

→ [[fixes/revert-shipped-parcel-and-deduct-on-last-delete]]

## Files

| Role | Path / note |
|------|-------------|
| Delete/revert flow | `includes/delivery_edit_requests.php` |
| Inventory rollback | `includes/honey_parcel_inventory.php` |
| Lookup repair | `api/v1/deliveries.php` |
| Module map | [[generated/modules/delivery_edit_requests]] |

## Deploy

- PHP only; refresh page and retry scan

## Links

- **Fragile edges:** [[003_Fragile_Edges#Deliveries delete vs parcel ship state]]

## AI rules

Deleting delivery evidence must reconcile parcel state and deductions, but only when that row is the last active delivery item.
