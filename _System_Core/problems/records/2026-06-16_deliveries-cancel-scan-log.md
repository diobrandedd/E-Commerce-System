---
date: 2026-06-16
type: problem-record
status: fixed
area: deliveries
tags: [problem, deliveries, inventory]
---

# Problem: Cancelled parcels were not operationally handled

> Main hub note — scanner cancellation needed to restore inventory and stay auditable.

## Symptom

Warehouse could scan and ship parcels, but when courier marked a parcel as cancelled there was no scanner cancellation flow, no automatic shipped-deduct reversal, and no dedicated cancelled-order log for audit.

## Cause

→ [[causes/deliveries-cancel-flow-missing]]

## Fix

→ [[fixes/deliveries-cancel-scan-revert-and-log]]

## Files

| Role | Path / note |
|------|-------------|
| Primary | `includes/online_order_parcels.php` |
| Primary | `api/v1/deliveries.php` |
| Primary | `app/src/app/pages/deliveries/DeliveriesPage.tsx` |
| Primary | `app/src/app/pages/deliveries/DeliveriesReviewPage.tsx` |
| Related | `app/src/app/lib/deliveriesApi.ts` |
| Migration | `sql/migration_phase32_delivery_cancel_events.sql` |

## Deploy

- Run SQL: `sql/migration_phase32_delivery_cancel_events.sql`
- Build SPA: `cd app && npm run build`
- Hard refresh browser after deploy
- Regenerate memory bank indexes

## Links

- **Fragile edges:** [[003_Fragile_Edges#Deliveries delete vs parcel ship state]]
- **Patterns:** [[005_SPA_UX_Patterns#Deliveries (prod_manager)]]
- **Spec (if any):** n/a (chat-directed operational fix)

## AI rules (optional)

Never cancel shipped parcels by status update alone; always revert deduct + insert cancel audit row in one transaction.
